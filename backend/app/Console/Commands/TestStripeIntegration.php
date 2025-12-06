<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\Tenant;
use App\Services\StripeService;
use Illuminate\Console\Command;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentMethod;
use Stripe\Stripe;

class TestStripeIntegration extends Command
{
    protected $signature = 'stripe:test {tenant_id} {plan_id} {payment_method_id?}';
    protected $description = 'Test Stripe integration with a test subscription';

    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        parent::__construct();
        $this->stripeService = $stripeService;
    }

    public function handle()
    {
        $tenant = Tenant::findOrFail($this->argument('tenant_id'));
        $plan = Plan::findOrFail($this->argument('plan_id'));
        $paymentMethodId = $this->argument('payment_method_id');

        $this->info("Testing Stripe integration for Tenant: {$tenant->name} (ID: {$tenant->id})");
        $this->info("Plan: {$plan->name} (ID: {$plan->id}, Price: {$plan->price})");

        try {
            // Step 1: Create or get customer
            $this->info('\n1. Creating/Retrieving Stripe customer...');
            $customer = $this->stripeService->getCustomer($tenant);
            $this->info("   Customer ID: {$customer->id}");

            // Step 2: Attach payment method if provided
            if ($paymentMethodId) {
                $this->info("\n2. Attaching payment method: {$paymentMethodId}");
                $this->stripeService->updatePaymentMethod($tenant, $paymentMethodId);
                $this->info('   Payment method attached successfully');
            } else {
                $this->info('\n2. No payment method provided, creating test payment method...');
                
                // Create a test payment method
                Stripe::setApiKey(config('services.stripe.secret'));
                $paymentMethod = PaymentMethod::create([
                    'type' => 'card',
                    'card' => [
                        'number' => '4242424242424242',
                        'exp_month' => 12,
                        'exp_year' => date('Y') + 1,
                        'cvc' => '123',
                    ],
                ]);
                
                $this->stripeService->updatePaymentMethod($tenant, $paymentMethod->id);
                $this->info("   Test payment method created and attached: {$paymentMethod->id}");
            }

            // Step 3: Create subscription
            $this->info('\n3. Creating subscription...');
            $subscription = $this->stripeService->createSubscription($tenant, $plan);
            
            $this->info("   Subscription created successfully!");
            $this->info("   Subscription ID: {$subscription->id}");
            $this->info("   Status: {$subscription->status}");
            $this->info("   Current period end: " . date('Y-m-d H:i:s', $subscription->current_period_end));

            // Step 4: Save subscription to database
            $assignment = $tenant->planAssignments()->create([
                'plan_id' => $plan->id,
                'stripe_subscription_id' => $subscription->id,
                'stripe_status' => $subscription->status,
                'stripe_price_id' => $plan->stripe_price_id,
                'status' => 'active',
                'billing_cycle' => $plan->billing_cycle,
                'starts_at' => now(),
                'trial_ends_at' => $plan->trial_days ? now()->addDays($plan->trial_days) : null,
            ]);

            $this->info("\n4. Subscription saved to database with ID: {$assignment->id}");

            // Step 5: Show subscription details
            $this->info("\nSubscription Details:");
            $this->info("-------------------");
            $this->info("Tenant: {$tenant->name} (ID: {$tenant->id})");
            $this->info("Plan: {$plan->name} (ID: {$plan->id})");
            $this->info("Status: {$subscription->status}");
            $this->info("Stripe Subscription ID: {$subscription->id}");
            $this->info("Current Period End: " . date('Y-m-d H:i:s', $subscription->current_period_end));

            if ($subscription->trial_end) {
                $this->info("Trial End: " . date('Y-m-d H:i:s', $subscription->trial_end));
            }

            $this->info("\nTest completed successfully!");

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            if ($e instanceof ApiErrorException && $e->getStripeCode()) {
                $this->error("Stripe Error: " . $e->getStripeCode() . ' - ' . $e->getMessage());
            }
            return 1;
        }

        return 0;
    }
}
