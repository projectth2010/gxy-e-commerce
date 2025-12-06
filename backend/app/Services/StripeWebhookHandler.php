<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\StripeClient;

class StripeWebhookHandler
{
    protected StripeClient $stripe;

    public function __construct(StripeClient $stripe)
    {
        $this->stripe = $stripe;
    }

    public function handleEvent(Event $event): void
    {
        $method = 'handle' . str_replace('.', '', $event->type);

        if (method_exists($this, $method)) {
            $this->$method($event);
        } else {
            Log::info('Unhandled Stripe event', ['type' => $event->type]);
        }
    }

    protected function handleCustomersubscriptioncreated(Event $event): void
    {
        $stripeSubscription = $event->data->object;
        $user = User::where('stripe_id', $stripeSubscription->customer)->first();

        if (!$user) {
            Log::error('User not found for subscription', [
                'stripe_customer_id' => $stripeSubscription->customer,
                'subscription_id' => $stripeSubscription->id
            ]);
            return;
        }

        $user->subscriptions()->updateOrCreate(
            ['stripe_subscription_id' => $stripeSubscription->id],
            [
                'stripe_status' => $stripeSubscription->status,
                'stripe_price_id' => $stripeSubscription->items->data[0]->price->id,
                'trial_ends_at' => $stripeSubscription->trial_end ? now()->createFromTimestamp($stripeSubscription->trial_end) : null,
                'ends_at' => $stripeSubscription->cancel_at ? now()->createFromTimestamp($stripeSubscription->cancel_at) : null,
            ]
        );
    }

    protected function handleCustomersubscriptionupdated(Event $event): void
    {
        $stripeSubscription = $event->data->object;
        
        Subscription::where('stripe_subscription_id', $stripeSubscription->id)
            ->update([
                'stripe_status' => $stripeSubscription->status,
                'ends_at' => $stripeSubscription->cancel_at ? now()->createFromTimestamp($stripeSubscription->cancel_at) : null,
            ]);
    }

    protected function handleCustomersubscriptiondeleted(Event $event): void
    {
        $stripeSubscription = $event->data->object;
        
        Subscription::where('stripe_subscription_id', $stripeSubscription->id)
            ->update([
                'stripe_status' => 'canceled',
                'ends_at' => now(),
            ]);
    }

    protected function handleInvoicesubscriptioncreated(Event $event): void
    {
        // Handle new subscription invoice
        $invoice = $event->data->object;
        Log::info('New subscription invoice created', ['invoice_id' => $invoice->id]);
    }

    protected function handleInvoicepaymentsucceeded(Event $event): void
    {
        $invoice = $event->data->object;
        
        if ($invoice->billing_reason === 'subscription_create') {
            $subscription = Subscription::where('stripe_subscription_id', $invoice->subscription)->first();
            if ($subscription) {
                $subscription->update([
                    'stripe_status' => 'active',
                ]);
            }
        }
    }

    protected function handleInvoicepaymentfailed(Event $event): void
    {
        $invoice = $event->data->object;
        
        $subscription = Subscription::where('stripe_subscription_id', $invoice->subscription)->first();
        if ($subscription) {
            $subscription->update([
                'stripe_status' => 'past_due',
            ]);
        }
        
        // TODO: Send payment failure notification
    }
}
