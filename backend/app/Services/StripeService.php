<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantPlanAssignment;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class StripeService
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient([
            'api_key' => config('services.stripe.secret'),
            'stripe_version' => '2023-10-16',
        ]);
    }

    /**
     * Create a new Stripe customer for the tenant
     */
    public function createCustomer(Tenant $tenant, $paymentMethod = null)
    {
        try {
            $customer = $this->stripe->customers->create([
                'email' => $tenant->email,
                'name' => $tenant->name,
                'metadata' => [
                    'tenant_id' => $tenant->id,
                    'tenant_code' => $tenant->code,
                ],
                'payment_method' => $paymentMethod,
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethod,
                ],
            ]);

            $tenant->update([
                'stripe_id' => $customer->id,
            ]);

            return $customer;
        } catch (\Exception $e) {
            \Log::error('Stripe Customer Creation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new subscription
     */
    public function createSubscription(Tenant $tenant, Plan $plan, $paymentMethod = null)
    {
        $customer = $this->getCustomer($tenant);

        // If no payment method provided, use the default one
        if ($paymentMethod) {
            $this->stripe->paymentMethods->attach($paymentMethod, [
                'customer' => $customer->id,
            ]);

            // Update default payment method
            $this->stripe->customers->update($customer->id, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethod,
                ],
            ]);
        }

        // Create subscription
        $subscription = $this->stripe->subscriptions->create([
            'customer' => $customer->id,
            'items' => [
                [
                    'price' => $plan->stripe_price_id,
                ],
            ],
            'payment_behavior' => 'default_incomplete',
            'expand' => ['latest_invoice.payment_intent'],
            'metadata' => [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
            ],
        ]);

        return $subscription;
    }

    /**
     * Get Stripe customer for tenant
     */
    public function getCustomer(Tenant $tenant)
    {
        if (!$tenant->stripe_id) {
            return $this->createCustomer($tenant);
        }

        try {
            return $this->stripe->customers->retrieve($tenant->stripe_id);
        } catch (\Exception $e) {
            // If customer not found, create a new one
            if ($e->getStripeCode() === 'resource_missing') {
                return $this->createCustomer($tenant);
            }
            throw $e;
        }
    }

    /**
     * Update the payment method
     */
    public function updatePaymentMethod(Tenant $tenant, $paymentMethod)
    {
        $customer = $this->getCustomer($tenant);

        $this->stripe->paymentMethods->attach($paymentMethod, [
            'customer' => $customer->id,
        ]);

        return $this->stripe->customers->update($customer->id, [
            'invoice_settings' => [
                'default_payment_method' => $paymentMethod,
            ],
        ]);
    }

    /**
     * Cancel subscription at period end
     */
    public function cancelSubscription(TenantPlanAssignment $subscription)
    {
        return $this->stripe->subscriptions->update(
            $subscription->stripe_subscription_id,
            ['cancel_at_period_end' => true]
        );
    }

    /**
     * Resume subscription
     */
    public function resumeSubscription(TenantPlanAssignment $subscription)
    {
        return $this->stripe->subscriptions->update(
            $subscription->stripe_subscription_id,
            ['cancel_at_period_end' => false]
        );
    }

    /**
     * Handle Stripe webhooks
     */
    public function handleWebhook($payload, $signature, $secret)
    {
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $signature, $secret
            );

            switch ($event->type) {
                case 'customer.subscription.created':
                case 'customer.subscription.updated':
                    $this->handleSubscriptionUpdated($event->data->object);
                    break;
                case 'customer.subscription.deleted':
                    $this->handleSubscriptionDeleted($event->data->object);
                    break;
                case 'invoice.payment_succeeded':
                    $this->handleInvoicePaymentSucceeded($event->data->object);
                    break;
                case 'invoice.payment_failed':
                    $this->handleInvoicePaymentFailed($event->data->object);
                    break;
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            \Log::error('Stripe Webhook Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    protected function handleSubscriptionUpdated($subscription)
    {
        $assignment = TenantPlanAssignment::where('stripe_subscription_id', $subscription->id)->first();
        
        if ($assignment) {
            $assignment->update([
                'stripe_status' => $subscription->status,
                'ends_at' => $subscription->cancel_at ? \Carbon\Carbon::createFromTimestamp($subscription->cancel_at) : null,
                'trial_ends_at' => $subscription->trial_end ? \Carbon\Carbon::createFromTimestamp($subscription->trial_end) : null,
            ]);
        }
    }

    protected function handleSubscriptionDeleted($subscription)
    {
        $assignment = TenantPlanAssignment::where('stripe_subscription_id', $subscription->id)->first();
        
        if ($assignment) {
            $assignment->update([
                'status' => 'canceled',
                'ends_at' => now(),
                'cancellation_reason' => 'canceled_from_stripe',
            ]);
        }
    }

    protected function handleInvoicePaymentSucceeded($invoice)
    {
        // Handle successful payment
        // You can extend this to send receipts, update accounting, etc.
    }

    protected function handleInvoicePaymentFailed($invoice)
    {
        // Handle failed payment
        // You can send notifications, suspend the account, etc.
    }
}
