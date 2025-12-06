<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Stripe API responses
        Http::fake([
            'https://api.stripe.com/v1/payment_methods' => Http::response([
                'id' => 'pm_test_123',
                'card' => ['last4' => '4242']
            ], 200),
            'https://api.stripe.com/v1/customers' => Http::response([
                'id' => 'cus_test_123',
                'email' => 'test@example.com'
            ], 200),
            'https://api.stripe.com/v1/subscriptions' => Http::response([
                'id' => 'sub_test_123',
                'status' => 'active',
                'current_period_end' => now()->addMonth()->timestamp,
                'items' => ['data' => [['price' => ['id' => 'price_test_123']]]]
            ], 200),
        ]);
    }

    /** @test */
    public function user_can_subscribe_to_a_plan()
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create([
            'stripe_plan_id' => 'price_test_123',
            'price' => 1000, // $10.00
        ]);

        $response = $this->actingAs($user, 'api')->postJson('/api/subscription', [
            'plan_id' => $plan->id,
            'payment_method_id' => 'pm_test_123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'subscription' => [
                    'id', 'status', 'plan'
                ]
            ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'stripe_status' => 'active'
        ]);
    }

    /** @test */
    public function subscription_fails_with_invalid_payment_method()
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        // Simulate payment method failure
        Http::fake([
            'https://api.stripe.com/v1/payment_methods' => Http::response([
                'error' => ['message' => 'Invalid payment method']
            ], 402)
        ]);

        $response = $this->actingAs($user, 'api')->postJson('/api/subscription', [
            'plan_id' => $plan->id,
            'payment_method_id' => 'invalid_pm',
        ]);

        $response->assertStatus(500);
    }

    /** @test */
    public function user_can_cancel_subscription()
    {
        $user = User::factory()->create([
            'stripe_id' => 'cus_test_123'
        ]);
        
        $subscription = $user->subscriptions()->create([
            'subscription_plan_id' => SubscriptionPlan::factory()->create()->id,
            'stripe_subscription_id' => 'sub_test_123',
            'stripe_status' => 'active',
            'stripe_price_id' => 'price_test_123',
        ]);

        // Mock Stripe subscription update response
        Http::fake([
            'https://api.stripe.com/v1/subscriptions/sub_test_123' => Http::response([
                'id' => 'sub_test_123',
                'status' => 'active',
                'cancel_at_period_end' => true,
                'current_period_end' => now()->addMonth()->timestamp
            ], 200)
        ]);

        $response = $this->actingAs($user, 'api')
            ->deleteJson('/api/subscription');

        $response->assertOk()
            ->assertJson(['message' => 'Subscription will be cancelled at the end of the billing period']);
    }

    /** @test */
    public function user_can_change_plan()
    {
        $user = User::factory()->create([
            'stripe_id' => 'cus_test_123'
        ]);
        
        $currentPlan = SubscriptionPlan::factory()->create([
            'stripe_plan_id' => 'price_current_123'
        ]);
        
        $newPlan = SubscriptionPlan::factory()->create([
            'stripe_plan_id' => 'price_new_123'
        ]);
        
        $subscription = $user->subscriptions()->create([
            'subscription_plan_id' => $currentPlan->id,
            'stripe_subscription_id' => 'sub_test_123',
            'stripe_status' => 'active',
            'stripe_price_id' => $currentPlan->stripe_plan_id,
        ]);

        // Mock Stripe subscription update response
        Http::fake([
            'https://api.stripe.com/v1/subscriptions/sub_test_123' => Http::response([
                'id' => 'sub_test_123',
                'status' => 'active',
                'items' => ['data' => [['price' => ['id' => 'price_new_123']]]]
            ], 200)
        ]);

        $response = $this->actingAs($user, 'api')
            ->putJson("/api/subscription/{$subscription->id}/change-plan", [
                'plan_id' => $newPlan->id
            ]);

        $response->assertOk()
            ->assertJson(['message' => 'Subscription plan updated successfully']);
    }
}
