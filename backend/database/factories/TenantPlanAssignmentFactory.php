<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantPlanAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantPlanAssignmentFactory extends Factory
{
    protected $model = TenantPlanAssignment::class;

    public function definition()
    {
        return [
            'tenant_id' => Tenant::factory(),
            'plan_id' => Plan::factory(),
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'stripe_subscription_id' => 'sub_test_' . $this->faker->uuid,
            'stripe_price_id' => 'price_test_' . $this->faker->uuid,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function active()
    {
        return $this->state([
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    public function cancelled()
    {
        return $this->state([
            'status' => 'canceled', // Note: 'canceled' with one 'l' to match the database enum
            'cancellation_reason' => 'user_cancelled',
            'ends_at' => now()->addDays(14), // Grace period
        ]);
    }
}
