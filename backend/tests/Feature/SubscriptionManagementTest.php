<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantPlanAssignment;
use App\Models\User;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Database\Factories\PlanFactory;
use Database\Factories\TenantFactory;
use Database\Factories\TenantPlanAssignmentFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Faker\Factory as FakerFactory;

class SubscriptionManagementTest extends TestCase
{
    use RefreshDatabase;
    
    protected $faker;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->faker = \Faker\Factory::create();
        
        // Create test plans
        $this->plan1 = Plan::firstOrCreate(
            ['code' => 'basic-plan'],
            [
                'name' => 'Basic Plan',
                'price' => 2900, // $29.00
                'billing_cycle' => 'monthly',
                'is_active' => true,
            ]
        );
        
        $this->plan2 = Plan::firstOrCreate(
            ['code' => 'pro-plan'],
            [
                'name' => 'Pro Plan',
                'price' => 7900, // $79.00
                'billing_cycle' => 'monthly',
                'is_active' => true,
            ]
        );

        // Create a unique test tenant with a consistent code for testing
        $testTenantCode = 'test-tenant-' . uniqid();
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'code' => $testTenantCode,
            'primary_domain' => 'test-tenant.localhost',
            'status' => 'active',
            'plan_id' => $this->plan1->id,
        ]);
        
        // Log the created tenant
        \Log::info('Created test tenant', [
            'id' => $this->tenant->id,
            'code' => $this->tenant->code,
        ]);

        // Create or get the test user
        $this->user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Attach user to tenant through the pivot table if not already attached
        if (!$this->user->tenants()->where('tenant_id', $this->tenant->id)->exists()) {
            $this->user->tenants()->attach($this->tenant->id);
        }
        
        // Create or get the subscription
        $this->subscription = TenantPlanAssignment::firstOrCreate(
            ['tenant_id' => $this->tenant->id],
            [
                'plan_id' => $this->plan1->id,
                'starts_at' => now()->subMonth(),
                'ends_at' => now()->addMonth(),
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'stripe_subscription_id' => 'sub_test_' . $this->faker->uuid,
            ]
        );
        
        // Refresh the tenant and subscription to ensure relationships are loaded
        $this->tenant->refresh();
        $this->subscription->refresh();
        $this->user->refresh();
        
        // Create a Sanctum token for the user
        $token = $this->user->createToken('test-token')->plainTextToken;
        
        // Set the Authorization header with the token
        $this->withHeader('Authorization', 'Bearer ' . $token);
        
        // Make sure the user is authenticated
        $this->actingAs($this->user, 'sanctum');
        
        // Mock notifications
        Notification::fake();
        
        // Log the tenant and user info for debugging
        \Log::info('Test setup complete', [
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'tenant_code' => $this->tenant->code,
            'subscription_id' => $this->subscription->id,
            'user_tenants' => $this->user->tenants->pluck('id')->toArray(),
            'all_tenants' => Tenant::select('id', 'code', 'name')->get()->toArray(),
        ]);
    }
    
    /** @test */
    public function it_can_change_subscription_plan()
    {
        // Refresh tenant to ensure we have the latest data
        $this->tenant->refresh();
        
        // Debug: Log the tenant and request details
        \Log::info('Test - Before request', [
            'tenant_id' => $this->tenant->id,
            'tenant_code' => $this->tenant->code,
            'user_id' => $this->user->id,
            'auth_user' => auth()->user()?->id,
            'request_url' => "/api/subscriptions/{$this->tenant->code}/change-plan",
            'plan_id' => $this->plan2->id,
            'all_tenants' => \App\Models\Tenant::all()->toArray(),
            'user_tenants' => $this->user->tenants->pluck('id')->toArray(),
        ]);
        
        // Make the API request with the X-Tenant-Key header
        $response = $this->withHeaders([
            'X-Tenant-Key' => $this->tenant->code,
            'Accept' => 'application/json',
        ])->postJson("/api/subscriptions/{$this->tenant->code}/change-plan", [
            'plan_id' => $this->plan2->id,
            'billing_cycle' => 'monthly',
            'prorate' => true,
        ]);
        
        // Log the response for debugging
        \Log::info('Test - After request', [
            'status' => $response->status(),
            'content' => $response->getContent(),
            'headers' => $response->headers->all(),
        ]);
        
        // Debug the response
        if ($response->status() !== 200) {
            dump('Change plan response:', [
                'status' => $response->status(),
                'content' => $response->getContent(),
                'headers' => $response->headers->all(),
            ]);
            
            // Log the database state for debugging
            $tenants = \DB::table('tenants')->get();
            $tenantUsers = \DB::table('tenant_user')->get();
            
            \Log::error('Test - After failed request', [
                'tenants' => $tenants,
                'tenant_users' => $tenantUsers,
                'auth_user' => auth()->user(),
            ]);
        }
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Subscription plan changed successfully',
            ]);
            
        // Assert the subscription was updated
        $this->assertDatabaseHas('tenant_plan_assignments', [
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan2->id,
            'status' => 'active',
        ]);
        
        // Assert the old subscription was marked as canceled
        $this->assertDatabaseHas('tenant_plan_assignments', [
            'id' => $this->subscription->id,
            'status' => 'canceled',
            'cancellation_reason' => 'changed_plan',
        ]);
    }
    
    /** @test */
    public function it_can_cancel_subscription()
    {
        $response = $this->withHeaders([
            'X-Tenant-Key' => $this->tenant->code,
            'Accept' => 'application/json',
        ])->postJson("/api/subscriptions/{$this->tenant->code}/cancel");
        
        // Debug the response
        if ($response->status() !== 200) {
            dump('Cancel subscription response:', $response->json());
        }
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Subscription has been cancelled',
            ]);
            
        $this->assertDatabaseHas('tenant_plan_assignments', [
            'id' => $this->subscription->id,
            'status' => 'canceled', // Note: 'canceled' with one 'l' to match the database enum
            'cancellation_reason' => 'user_cancelled',
        ]);
    }
    
    /** @test */
    public function it_can_reactivate_cancelled_subscription()
    {
        // First, cancel the subscription
        $this->subscription->update([
            'status' => 'canceled',
            'ends_at' => now()->addDays(30),
            'cancellation_reason' => 'user_cancellation',
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Key' => $this->tenant->code,
            'Accept' => 'application/json',
        ])->postJson("/api/subscriptions/{$this->tenant->code}/reactivate");
        
        // Debug the response
        if ($response->status() !== 200) {
            dump('Reactivate subscription response:', $response->json());
        }

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Subscription has been reactivated',
            ]);
            
        $this->assertDatabaseHas('tenant_plan_assignments', [
            'id' => $this->subscription->id,
            'status' => 'active',
            'cancellation_reason' => null,
        ]);
    }
    
    /** @test */
    public function it_validates_plan_change_request()
    {
        $response = $this->withHeaders([
            'X-Tenant-Key' => $this->tenant->code,
            'Accept' => 'application/json',
        ])->postJson("/api/subscriptions/{$this->tenant->code}/change-plan", []);
        
        // Debug the response
        if ($response->status() !== 422) {
            dump('Validation response:', $response->json());
        }

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plan_id', 'billing_cycle']);
    }
}
