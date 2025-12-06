<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
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
    public function it_handles_subscription_created_event()
    {
        $user = User::factory()->create(['stripe_id' => 'cus_test123']);
        $plan = SubscriptionPlan::factory()->create(['stripe_plan_id' => 'price_test123']);

        $payload = [
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => [
                    'id' => 'sub_test123',
                    'customer' => $user->stripe_id,
                    'status' => 'active',
                    'items' => [
                        'data' => [
                            ['price' => ['id' => $plan->stripe_plan_id]]
                        ]
                    ],
                    'current_period_end' => now()->addMonth()->timestamp,
                ]
            ]
        ];

        $response = $this->postJson('/api/webhook/stripe', $payload, [
            'Stripe-Signature' => $this->generateSignature($payload)
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('subscriptions', [
            'stripe_subscription_id' => 'sub_test123',
            'user_id' => $user->id,
            'stripe_status' => 'active'
        ]);
    }

    /** @test */
    public function it_handles_subscription_updated_event()
    {
        $user = User::factory()->create(['stripe_id' => 'cus_test123']);
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'stripe_subscription_id' => 'sub_test123',
            'stripe_status' => 'active'
        ]);

        $payload = [
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => $subscription->stripe_subscription_id,
                    'status' => 'past_due',
                    'cancel_at_period_end' => true,
                    'current_period_end' => now()->addDays(10)->timestamp,
                ]
            ]
        ];

        $response = $this->postJson('/api/webhook/stripe', $payload, [
            'Stripe-Signature' => $this->generateSignature($payload)
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'stripe_status' => 'past_due',
            'ends_at' => now()->addDays(10)->toDateTimeString()
        ]);
    }

    /** @test */
    public function it_handles_invoice_payment_failed()
    {
        $user = User::factory()->create(['stripe_id' => 'cus_test123']);
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'stripe_subscription_id' => 'sub_test123',
            'stripe_status' => 'active'
        ]);

        $payload = [
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'subscription' => $subscription->stripe_subscription_id,
                    'attempt_count' => 3,
                    'next_payment_attempt' => now()->addDays(3)->timestamp,
                ]
            ]
        ];

        $response = $this->postJson('/api/webhook/stripe', $payload, [
            'Stripe-Signature' => $this->generateSignature($payload)
        ]);

        $response->assertStatus(200);
        // Add assertions for failed payment handling (e.g., notification sent)
    }

    /** @test */
    public function it_handles_customer_subscription_deleted()
    {
        $user = User::factory()->create(['stripe_id' => 'cus_test123']);
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'stripe_subscription_id' => 'sub_test123',
            'stripe_status' => 'active'
        ]);

        $payload = [
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => $subscription->stripe_subscription_id,
                    'status' => 'canceled',
                    'canceled_at' => now()->timestamp,
                ]
            ]
        ];

        $this->assertWebhookCall($payload, [
            'id' => $subscription->id,
            'stripe_status' => 'canceled',
            'ends_at' => now()->toDateTimeString()
        ]);
    }

    protected function assertWebhookCall($payload, $expectedData = [])
    {
        $response = $this->postJson('/api/webhook/stripe', $payload, [
            'Stripe-Signature' => $this->generateSignature($payload)
        ]);

        $response->assertStatus(200);

        if ($expectedData) {
            $this->assertDatabaseHas('subscriptions', $expectedData);
        }
    }

    /**
     * Generate a signature for a given payload.
     */
    protected function generateSignature(array $payload): string
    {
        $timestamp = time();
        $secret = config('services.stripe.webhook_secret');
        $signedPayload = "{$timestamp}." . json_encode($payload);
        $signature = hash_hmac('sha256', $signedPayload, $secret);
        
        return "t={$timestamp},v1={$signature}";
    }
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
