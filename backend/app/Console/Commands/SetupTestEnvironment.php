<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantPlanAssignment;
use App\Models\Plan;
use App\Models\Feature;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SetupTestEnvironment extends Command
{
    protected $signature = 'setup:test';
    protected $description = 'Setup a test environment with sample data';

    public function handle()
    {
        $this->info('Setting up test environment...');

        // Create test tenant
        $tenant = Tenant::firstOrCreate(
            ['domain' => 'test-tenant.local'],
            [
                'name' => 'Test Tenant',
                'code' => 'test' . rand(1000, 9999),
                'status' => 'active'
            ]
        );

        // Create test user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]
        );

        if (!$user->tenants()->where('tenant_id', $tenant->id)->exists()) {
            $tenant->users()->attach($user->id);
        }

        // Create test plan
        $plan = Plan::firstOrCreate(
            ['code' => 'pro-plan'],
            [
                'name' => 'Pro Plan',
                'description' => 'Professional plan with all features',
                'price' => 29.99,
                'billing_cycle' => 'monthly',
                'trial_days' => 14,
                'is_active' => true,
                'sort_order' => 1,
                'stripe_price_id' => 'price_test_' . uniqid(),
            ]
        );

        // Create test features
        $features = [
            ['name' => 'Users', 'code' => 'users', 'description' => 'Number of users'],
            ['name' => 'Products', 'code' => 'products', 'description' => 'Number of products'],
            ['name' => 'Storage', 'code' => 'storage', 'description' => 'Storage in GB'],
        ];

        foreach ($features as $feature) {
            $plan->features()->updateOrCreate(
                ['code' => $feature['code']],
                $feature
            );
        }

        // Assign plan to tenant
        $assignment = TenantPlanAssignment::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
            ],
            [
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'trial_ends_at' => now()->addDays(14),
                'status' => 'active',
                'billing_cycle' => $plan->billing_cycle,
                'stripe_subscription_id' => 'sub_test_' . uniqid(),
                'stripe_status' => 'active',
            ]
        );

        $this->info('Test environment ready!');
        $this->line('Tenant ID: ' . $tenant->id);
        $this->line('User email: test@example.com');
        $this->line('Password: password');
        $this->line('You can now test notifications with: php artisan notification:test payment_succeeded --tenant=' . $tenant->id);
    }
}
