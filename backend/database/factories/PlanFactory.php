<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'code' => strtoupper($this->faker->unique()->word),
            'description' => $this->faker->sentence,
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'billing_cycle' => $this->faker->randomElement(['monthly', 'yearly']),
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
