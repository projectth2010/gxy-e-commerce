<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company,
            'code' => strtoupper($this->faker->unique()->word),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
