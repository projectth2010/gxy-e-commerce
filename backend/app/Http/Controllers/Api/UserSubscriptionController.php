<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscribeRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class UserSubscriptionController extends Controller
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
        $this->middleware('auth:api');
    }

    /**
     * Get the current user's subscription.
     */
    public function index(): JsonResponse
    {
        $subscription = Auth::user()->currentSubscription();
        
        if (!$subscription) {
            return response()->json(['message' => 'No active subscription found'], 404);
        }
        
        return response()->json([
            'data' => new SubscriptionResource($subscription)
        ]);
    }

    /**
     * Subscribe to a plan.
     *
     * @throws \Exception
     */
    public function store(SubscribeRequest $request): JsonResponse
    {
        $user = Auth::user();
        $plan = SubscriptionPlan::findOrFail($request->input('plan_id'));
        $paymentMethodId = $request->input('payment_method_id');
        
        try {
            // Create or get Stripe customer
            if (!$user->stripe_id) {
                $customer = $this->stripe->customers->create([
                    'email' => $user->email,
                    'name' => $user->name,
                    'payment_method' => $paymentMethodId,
                    'invoice_settings' => [
                        'default_payment_method' => $paymentMethodId,
                    ],
                ]);
                $user->stripe_id = $customer->id;
                $user->save();
            } else {
                $customer = $this->stripe->customers->retrieve($user->stripe_id);
                $this->stripe->paymentMethods->attach($paymentMethodId, [
                    'customer' => $customer->id,
                ]);
                $this->stripe->customers->update($customer->id, [
                    'invoice_settings' => [
                        'default_payment_method' => $paymentMethodId,
                    ],
                ]);
            }
            
            // Create subscription
            $subscription = $this->stripe->subscriptions->create([
                'customer' => $user->stripe_id,
                'items' => [
                    ['price' => $plan->stripe_plan_id],
                ],
                'payment_behavior' => 'default_incomplete',
                'expand' => ['latest_invoice.payment_intent'],
                'trial_period_days' => $plan->trial_days,
            ]);
            
            // Save subscription to database
            $userSubscription = $user->subscriptions()->create([
                'subscription_plan_id' => $plan->id,
                'stripe_subscription_id' => $subscription->id,
                'stripe_status' => $subscription->status,
                'stripe_price_id' => $plan->stripe_plan_id,
                'trial_ends_at' => $subscription->trial_end ? now()->parse($subscription->trial_end) : null,
                'ends_at' => null,
            ]);
            
            return response()->json([
                'message' => 'Subscription created successfully',
                'requires_action' => $subscription->latest_invoice->payment_intent->status === 'requires_action',
                'payment_intent_client_secret' => $subscription->latest_invoice->payment_intent->client_secret,
                'subscription' => new SubscriptionResource($userSubscription)
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Subscription failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel the current subscription.
     */
    public function destroy(): JsonResponse
    {
        $user = Auth::user();
        $subscription = $user->currentSubscription();
        
        if (!$subscription) {
            return response()->json(['message' => 'No active subscription found'], 404);
        }
        
        try {
            // Cancel at period end
            $stripeSubscription = $this->stripe->subscriptions->update(
                $subscription->stripe_subscription_id,
                ['cancel_at_period_end' => true]
            );
            
            $subscription->update([
                'stripe_status' => $stripeSubscription->status,
                'ends_at' => now()->parse($stripeSubscription->cancel_at)
            ]);
            
            return response()->json([
                'message' => 'Subscription will be cancelled at the end of the billing period',
                'ends_at' => $subscription->ends_at
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Resume a cancelled subscription.
     */
    public function resume(): JsonResponse
    {
        $user = Auth::user();
        $subscription = $user->currentSubscription();
        
        if (!$subscription || !$subscription->isCanceled()) {
            return response()->json(['message' => 'No cancellable subscription found'], 400);
        }
        
        try {
            $stripeSubscription = $this->stripe->subscriptions->update(
                $subscription->stripe_subscription_id,
                ['cancel_at_period_end' => false]
            );
            
            $subscription->update([
                'stripe_status' => $stripeSubscription->status,
                'ends_at' => null
            ]);
            
            return response()->json([
                'message' => 'Subscription has been resumed',
                'data' => new SubscriptionResource($subscription)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to resume subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
