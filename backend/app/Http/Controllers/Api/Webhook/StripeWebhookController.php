<?php

namespace App\Http\Controllers\Api\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantPlanAssignment;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class StripeWebhookController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe Webhook Error - Invalid Payload: ' . $e->getMessage());
            throw new BadRequestHttpException('Invalid payload');
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe Webhook Error - Invalid Signature: ' . $e->getMessage());
            throw new BadRequestHttpException('Invalid signature');
        }

        Log::info('Stripe Webhook Received: ' . $event->type);

        if (method_exists($this, $method = 'handle' . str_replace('.', '', $event->type))) {
            return $this->{$method}($event->data->object);
        }

        return response()->json(['status' => 'received']);
    }

    protected function handleCheckoutSessionCompleted($session)
    {
        $subscriptionId = $session->subscription;
        $subscription = \Stripe\Subscription::retrieve($subscriptionId);
        
        $assignment = $this->updateSubscription($subscription);
        
        // Send notification
        if ($assignment) {
            $notificationService = app(\App\Services\NotificationService::class);
            $notificationService->sendSubscriptionCreated($assignment->tenant, $assignment);
        }
        
        return response()->json(['status' => 'success']);
    }

    protected function handleCustomerSubscriptionUpdated($subscription)
    {
        $assignment = $this->updateSubscription($subscription);
        
        // Send notification if subscription was updated
        if ($assignment) {
            $notificationService = app(\App\Services\NotificationService::class);
            $notificationService->sendSubscriptionUpdated($assignment->tenant, $assignment);
            
            // Check for trial ending soon
            if ($assignment->onTrial()) {
                $notificationService->sendTrialEndingNotification($assignment->tenant, $assignment);
            }
            
            // Check for subscription ending soon
            if ($assignment->ends_at) {
                $notificationService->sendSubscriptionEndingNotification($assignment->tenant, $assignment);
            }
        }
        
        return response()->json(['status' => 'success']);
    }

    protected function handleCustomerSubscriptionDeleted($subscription)
    {
        $assignment = TenantPlanAssignment::where('stripe_subscription_id', $subscription->id)->first();
        
        if ($assignment) {
            $assignment->update([
                'status' => 'cancelled',
                'ends_at' => now(),
                'stripe_status' => $subscription->status,
            ]);
            
            // Update tenant status if needed
            $assignment->tenant->update(['status' => 'inactive']);
        }
        
        return response()->json(['status' => 'success']);
    }

    protected function handleInvoicePaymentSucceeded($invoice)
    {
        // Get the subscription
        $subscription = \Stripe\Subscription::retrieve($invoice->subscription);
        $assignment = TenantPlanAssignment::where('stripe_subscription_id', $subscription->id)->first();
        
        if ($assignment) {
            $notificationService = app(\App\Services\NotificationService::class);
            $notificationService->sendPaymentSucceeded(
                $assignment->tenant,
                number_format($invoice->amount_paid / 100, 2),
                $invoice->hosted_invoice_url
            );
        }
        
        return response()->json(['status' => 'success']);
    }

    protected function handleInvoicePaymentFailed($invoice)
    {
        $subscription = \Stripe\Subscription::retrieve($invoice->subscription);
        $assignment = $this->updateSubscription($subscription);
        
        if ($assignment) {
            $notificationService = app(\App\Services\NotificationService::class);
            $notificationService->sendPaymentFailed(
                $assignment->tenant,
                number_format($invoice->amount_due / 100, 2),
                'Please update your payment method to avoid service interruption.'
            );
        }
        
        return response()->json(['status' => 'success']);
    }

    protected function updateSubscription($subscription)
    {
        $assignment = TenantPlanAssignment::where('stripe_subscription_id', $subscription->id)->first();
        
        if ($assignment) {
            $assignment->update([
                'stripe_status' => $subscription->status,
                'ends_at' => $subscription->cancel_at ? now()->timestamp($subscription->cancel_at) : null,
                'trial_ends_at' => $subscription->trial_end ? now()->timestamp($subscription->trial_end) : null,
            ]);
            
            // Update tenant status based on subscription status
            $status = $subscription->status === 'active' ? 'active' : 'inactive';
            $assignment->tenant->update(['status' => $status]);
        }
    }
}
