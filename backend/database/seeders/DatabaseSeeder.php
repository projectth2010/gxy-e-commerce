<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create default admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]
        );

        // Seed plans and features
        $this->call([
            PlanFeatureSeeder::class,
        ]);

        // Create test users if in local environment
        if (app()->environment('local')) {
            // Create test users
            User::factory(10)->create();
        }
    }
}
