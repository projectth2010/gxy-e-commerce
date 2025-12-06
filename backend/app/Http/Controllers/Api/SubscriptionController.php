<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get the current subscription
     */
    public function show(Tenant $tenant)
    {
        $this->authorize('view', $tenant);
        
        $subscription = $tenant->activeSubscription;
        
        if (!$subscription) {
            return response()->json([
                'message' => 'No active subscription found',
                'subscription' => null,
            ]);
        }

        return new SubscriptionResource($subscription);
    }

    /**
     * Create a new subscription
     */
    public function store(Request $request, Tenant $tenant)
    {
        $this->authorize('update', $tenant);

        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'payment_method' => 'required|string',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        
        try {
            // Create or get the Stripe customer
            $customer = $this->stripeService->getCustomer($tenant);
            
            // Create the subscription
            $subscription = $this->stripeService->createSubscription(
                $tenant, 
                $plan, 
                $request->payment_method
            );

            // Create the subscription record
            $subscription = $tenant->planAssignments()->create([
                'plan_id' => $plan->id,
                'stripe_subscription_id' => $subscription->id,
                'stripe_status' => $subscription->status,
                'stripe_price_id' => $plan->stripe_price_id,
                'status' => 'active',
                'billing_cycle' => $plan->billing_cycle,
                'starts_at' => now(),
                'trial_ends_at' => $plan->trial_days ? now()->addDays($plan->trial_days) : null,
            ]);

            return new SubscriptionResource($subscription);

        } catch (\Exception $e) {
            Log::error('Subscription Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the payment method
     */
    public function updatePaymentMethod(Request $request, Tenant $tenant)
    {
        $this->authorize('update', $tenant);

        $request->validate([
            'payment_method' => 'required|string',
        ]);

        try {
            $this->stripeService->updatePaymentMethod($tenant, $request->payment_method);
            return response()->json(['message' => 'Payment method updated successfully']);
        } catch (\Exception $e) {
            Log::error('Update Payment Method Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update payment method',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel the subscription
     */
    public function cancel(Tenant $tenant)
    {
        $this->authorize('update', $tenant);

        $subscription = $tenant->activeSubscription;
        
        if (!$subscription) {
            return response()->json([
                'message' => 'No active subscription found',
            ], 400);
        }

        try {
            $this->stripeService->cancelSubscription($subscription);
            
            $subscription->update([
                'status' => 'cancelled',
                'ends_at' => $subscription->current_period_end,
                'cancellation_reason' => 'user_cancelled',
            ]);

            return response()->json([
                'message' => 'Subscription cancelled successfully',
                'ends_at' => $subscription->ends_at,
            ]);

        } catch (\Exception $e) {
            Log::error('Cancel Subscription Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to cancel subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resume the subscription
     */
    public function resume(Tenant $tenant)
    {
        $this->authorize('update', $tenant);

        $subscription = $tenant->activeSubscription;
        
        if (!$subscription) {
            return response()->json([
                'message' => 'No active subscription found',
            ], 400);
        }

        try {
            $this->stripeService->resumeSubscription($subscription);
            
            $subscription->update([
                'status' => 'active',
                'ends_at' => null,
                'cancellation_reason' => null,
            ]);

            return response()->json([
                'message' => 'Subscription resumed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Resume Subscription Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to resume subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the payment intent for setting up payment methods
     */
    public function setupIntent(Request $request, Tenant $tenant)
    {
        $this->authorize('update', $tenant);

        try {
            $customer = $this->stripeService->getCustomer($tenant);
            
            $intent = \Stripe\SetupIntent::create([
                'customer' => $customer->id,
                'payment_method_types' => ['card'],
            ]);

            return response()->json([
                'client_secret' => $intent->client_secret,
            ]);

        } catch (\Exception $e) {
            Log::error('Setup Intent Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create setup intent',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
