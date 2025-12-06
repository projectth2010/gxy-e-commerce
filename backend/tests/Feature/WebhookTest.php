<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantPlanAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Stripe webhook secret
        config(['services.stripe.webhook_secret' => 'test_webhook_secret']);
    }

    /** @test */
    public function it_handles_subscription_updated_event()
    {
        $tenant = Tenant::factory()->create([
            'stripe_id' => 'cus_test123',
        ]);

        $payload = [
            'id' => 'evt_test123',
            'object' => 'event',
            'api_version' => '2020-08-27',
            'created' => time(),
            'data' => [
                'object' => [
                    'id' => 'sub_test123',
                    'object' => 'subscription',
                    'customer' => 'cus_test123',
                    'status' => 'active',
                    'current_period_start' => now()->timestamp,
                    'current_period_end' => now()->addMonth()->timestamp,
                    'plan' => [
                        'id' => 'price_test123',
                        'object' => 'plan',
                        'active' => true,
                        'amount' => 999,
                        'currency' => 'usd',
                        'interval' => 'month',
                        'product' => 'prod_test123',
                    ],
                ],
            ],
            'livemode' => false,
            'pending_webhooks' => 1,
            'request' => null,
            'type' => 'customer.subscription.updated',
        ];

        $response = $this->postJson('/stripe-webhook', $payload, [
            'Content-Type' => 'application/json',
        ]);

        // Debug output
        if ($response->status() !== 200) {
            dump('Response status: ' . $response->status());
            dump('Response content: ' . $response->getContent());
        }

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
    }

    protected function generateSignature($payload)
    {
        $timestamp = time();
        $signedPayload = "{$timestamp}.".json_encode($payload);
        $signature = hash_hmac('sha256', $signedPayload, config('services.stripe.webhook_secret'));
        
        return "t={$timestamp},v1={$signature}";
    }
}
