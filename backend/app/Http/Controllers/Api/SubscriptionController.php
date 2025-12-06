<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantPlanAssignment;
use App\Services\StripeService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SubscriptionController extends Controller
{
    use AuthorizesRequests;
    
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get the current subscription
     */
    /**
     * Change the subscription plan
     * 
     * @param Request $request
     * @param Tenant $tenant
     * @param SubscriptionService $subscriptionService
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePlan(Request $request, Tenant $tenant, SubscriptionService $subscriptionService)
    {
        // Debug: Log the resolved tenant
        \Log::info('Resolved tenant in changePlan:', [
            'tenant_id' => $tenant->id,
            'tenant_code' => $tenant->code,
            'tenant_name' => $tenant->name,
            'url' => $request->fullUrl(),
            'route_parameters' => $request->route()->parameters(),
            'request_data' => $request->all(),
            'user_id' => auth()->id(),
            'user_tenants' => auth()->user()->tenants->pluck('id'),
        ]);
        
        // Check if the tenant exists and the user has access to it
        if (!$tenant->exists) {
            \Log::error('Tenant not found', [
                'requested_tenant' => $request->route('tenant'),
                'user_id' => auth()->id(),
            ]);
            return response()->json(['error' => 'TENANT_NOT_FOUND'], 400);
        }
        
        $this->authorize('update', $tenant);
        
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'prorate' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $newPlan = Plan::findOrFail($request->plan_id);
            
            $subscription = $subscriptionService->changePlan(
                $tenant,
                $newPlan,
                $request->billing_cycle,
                $request->boolean('prorate', true)
            );

            return response()->json([
                'message' => 'Subscription plan changed successfully',
                'subscription' => new SubscriptionResource($subscription)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to change subscription plan: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to change subscription plan: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancel the current subscription
     * 
     * @param Tenant $tenant
     * @param SubscriptionService $subscriptionService
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Tenant $tenant, SubscriptionService $subscriptionService)
    {
        // Debug: Log the resolved tenant
        \Log::info('Cancel subscription - Resolved tenant:', [
            'id' => $tenant->id,
            'code' => $tenant->code,
            'name' => $tenant->name,
            'route' => request()->fullUrl(),
            'route_parameters' => request()->route()->parameters(),
        ]);
        $this->authorize('update', $tenant);

        try {
            $subscription = $tenant->activeSubscription;
            
            if (!$subscription) {
                return response()->json([
                    'message' => 'No active subscription found',
                ], 400);
            }
            
            $subscriptionService->cancelSubscription($tenant, 'user_cancelled');
            
            // Refresh the subscription to get the updated end date
            $subscription->refresh();
            
            return response()->json([
                'message' => 'Subscription has been cancelled',
                'cancelled_at' => now()->toDateTimeString(),
                'ends_at' => $subscription->ends_at ? $subscription->ends_at->toDateTimeString() : null
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to cancel subscription: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to cancel subscription: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Reactivate a cancelled subscription
     * 
     * @param Tenant $tenant
     * @param SubscriptionService $subscriptionService
     * @return \Illuminate\Http\JsonResponse
     */
    public function reactivate(Tenant $tenant, SubscriptionService $subscriptionService)
    {
        $this->authorize('update', $tenant);

        try {
            $subscription = $tenant->subscriptions()
                ->where('status', 'canceled')
                ->latest()
                ->first();
                
            if (!$subscription) {
                return response()->json([
                    'message' => 'No canceled subscription found to reactivate',
                ], 400);
            }
            
            $subscriptionService->reactivateSubscription($tenant);
            
            // Refresh the subscription to get the updated end date
            $subscription->refresh();
            
            return response()->json([
                'message' => 'Subscription has been reactivated',
                'reactivated_at' => now()->toDateTimeString(),
                'ends_at' => $subscription->ends_at ? $subscription->ends_at->toDateTimeString() : null
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to reactivate subscription: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to reactivate subscription: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get the current subscription
     * 
     * @param Tenant $tenant
     * @return \Illuminate\Http\JsonResponse
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
