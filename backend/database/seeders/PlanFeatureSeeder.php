<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanFeatureSeeder extends Seeder
{
    public function run()
    {
        // Create features
        $features = [
            [
                'name' => 'Products',
                'code' => 'products',
                'description' => 'Number of products allowed',
                'type' => 'integer',
                'default_value' => '0',
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'Storage',
                'code' => 'storage',
                'description' => 'Storage space in MB',
                'type' => 'integer',
                'default_value' => '100',
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'name' => 'Users',
                'code' => 'users',
                'description' => 'Number of users allowed',
                'type' => 'integer',
                'default_value' => '1',
                'is_active' => true,
                'sort_order' => 30,
            ],
            [
                'name' => 'Custom Domain',
                'code' => 'custom_domain',
                'description' => 'Ability to use custom domain',
                'type' => 'boolean',
                'default_value' => '0',
                'is_active' => true,
                'sort_order' => 40,
            ],
            [
                'name' => 'API Access',
                'code' => 'api_access',
                'description' => 'Access to API',
                'type' => 'boolean',
                'default_value' => '0',
                'is_active' => true,
                'sort_order' => 50,
            ],
        ];

        $createdFeatures = [];
        foreach ($features as $featureData) {
            $feature = Feature::firstOrCreate(
                ['code' => $featureData['code']],
                $featureData
            );
            $createdFeatures[$feature->code] = $feature;
        }

        // Create plans
        $plans = [
            [
                'name' => 'Starter',
                'code' => 'starter',
                'description' => 'Perfect for small businesses getting started',
                'price' => 29.00,
                'billing_cycle' => 'monthly',
                'trial_days' => 14,
                'is_active' => true,
                'sort_order' => 10,
                'features' => [
                    'products' => '100',
                    'storage' => '1024', // 1GB
                    'users' => '2',
                    'custom_domain' => '0',
                    'api_access' => '0',
                ],
            ],
            [
                'name' => 'Professional',
                'code' => 'professional',
                'description' => 'For growing businesses with more needs',
                'price' => 99.00,
                'billing_cycle' => 'monthly',
                'trial_days' => 14,
                'is_active' => true,
                'sort_order' => 20,
                'features' => [
                    'products' => '1000',
                    'storage' => '5120', // 5GB
                    'users' => '5',
                    'custom_domain' => '1',
                    'api_access' => '1',
                ],
            ],
            [
                'name' => 'Enterprise',
                'code' => 'enterprise',
                'description' => 'For large businesses with custom needs',
                'price' => 299.00,
                'billing_cycle' => 'monthly',
                'trial_days' => 30,
                'is_active' => true,
                'sort_order' => 30,
                'features' => [
                    'products' => '10000',
                    'storage' => '51200', // 50GB
                    'users' => '20',
                    'custom_domain' => '1',
                    'api_access' => '1',
                ],
            ],
        ];

        foreach ($plans as $planData) {
            $features = $planData['features'];
            unset($planData['features']);
            
            $plan = Plan::firstOrCreate(
                ['code' => $planData['code']],
                $planData
            );

            // Sync features
            $syncData = [];
            foreach ($features as $featureCode => $value) {
                if (isset($createdFeatures[$featureCode])) {
                    $syncData[$createdFeatures[$featureCode]->id] = ['value' => $value];
                }
            }
            
            $plan->features()->sync($syncData);
        }
    }
}
