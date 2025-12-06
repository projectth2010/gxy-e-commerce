<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use App\Models\Tenant;
use App\Models\TenantPlanAssignment;
use App\Services\NotificationService;

class WebhookController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->middleware('verify.stripe.webhook');
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $event = null;

        try {
            $event = Webhook::constructEvent(
                $payload, 
                $sigHeader, 
                config('services.stripe.webhook_secret')
            );
        } catch (\UnexpectedValueException $e) {
            Log::error('Invalid webhook payload', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Invalid webhook signature', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('Processing webhook event', ['type' => $event->type]);

        switch ($event->type) {
            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;
            case 'invoice.payment_succeeded':
                $this->handlePaymentSucceeded($event->data->object);
                break;
            case 'invoice.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;
            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;
        }

        return response()->json(['status' => 'success']);
    }

    protected function handleSubscriptionUpdated($subscription)
    {
        $tenant = Tenant::where('stripe_id', $subscription->customer)->first();
        
        if (!$tenant) {
            Log::error('Tenant not found for subscription', ['stripe_id' => $subscription->customer]);
            return;
        }

        $plan = Plan::where('stripe_plan_id', $subscription->plan->id)->first();
        
        $tenant->subscriptions()->create([
            'plan_id' => $plan->id,
            'stripe_subscription_id' => $subscription->id,
            'stripe_status' => $subscription->status,
            'starts_at' => Carbon::createFromTimestamp($subscription->current_period_start),
            'ends_at' => Carbon::createFromTimestamp($subscription->current_period_end),
            'status' => $this->mapStripeStatus($subscription->status),
        ]);
    }

    protected function handlePaymentSucceeded($invoice)
    {
        // Handle successful payment
    }

    protected function handlePaymentFailed($invoice)
    {
        // Handle failed payment
    }

    protected function handleSubscriptionDeleted($subscription)
    {
        // Handle subscription cancellation/expiration
    }

    protected function mapStripeStatus($stripeStatus)
    {
        $statusMap = [
            'active' => 'active',
            'trialing' => 'active',
            'past_due' => 'past_due',
            'canceled' => 'canceled',
            'unpaid' => 'past_due',
        ];

        return $statusMap[$stripeStatus] ?? 'inactive';
    }
}
