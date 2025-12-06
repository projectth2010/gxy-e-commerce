<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantPlanAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Change the subscription plan for a tenant
     *
     * @param Tenant $tenant
     * @param Plan $newPlan
     * @param string $billingCycle
     * @param bool $prorate
     * @return TenantPlanAssignment
     * @throws \Exception
     */
    public function changePlan(Tenant $tenant, Plan $newPlan, string $billingCycle = 'monthly', bool $prorate = true)
    {
        return DB::transaction(function () use ($tenant, $newPlan, $billingCycle, $prorate) {
            $currentSubscription = $tenant->activeSubscription;
            
            if (!$currentSubscription) {
                throw new \Exception('No active subscription found');
            }

            // Calculate prorated amount if needed
            $proratedAmount = 0;
            if ($prorate && $currentSubscription->plan->price > 0) {
                $proratedAmount = $this->calculateProratedAmount(
                    $currentSubscription->plan->price,
                    $currentSubscription->starts_at,
                    $currentSubscription->ends_at,
                    now()
                );
            }

            // End the current subscription
            $currentSubscription->update([
                'status' => 'canceled',
                'ends_at' => now(),
                'cancellation_reason' => 'changed_plan',
            ]);

            // Create new subscription
            $newSubscription = $tenant->subscriptions()->create([
                'plan_id' => $newPlan->id,
                'starts_at' => now(),
                'ends_at' => $this->calculateEndDate($billingCycle),
                'status' => 'active',
                'billing_cycle' => $billingCycle,
                'stripe_price_id' => $newPlan->stripe_price_id,
            ]);

            // Log the plan change
            Log::info('Subscription plan changed', [
                'tenant_id' => $tenant->id,
                'from_plan' => $currentSubscription->plan->name,
                'to_plan' => $newPlan->name,
                'prorated_amount' => $proratedAmount,
            ]);

            // Notify tenant about the plan change
            $this->notificationService->sendSubscriptionPlanChanged(
                $tenant,
                $currentSubscription,
                $newSubscription,
                $proratedAmount
            );

            return $newSubscription;
        });
    }

    /**
     * Calculate prorated amount for subscription changes
     */
    protected function calculateProratedAmount(float $annualPrice, $startDate, $endDate, $changeDate): float
    {
        $totalDays = $startDate->diffInDays($endDate);
        $remainingDays = $changeDate->diffInDays($endDate);
        
        if ($totalDays <= 0 || $remainingDays <= 0) {
            return 0;
        }

        $dailyRate = $annualPrice / 365;
        return round($remainingDays * $dailyRate, 2);
    }

    /**
     * Calculate subscription end date based on billing cycle
     */
    protected function calculateEndDate(string $billingCycle): Carbon
    {
        return match (strtolower($billingCycle)) {
            'yearly' => now()->addYear(),
            default => now()->addMonth(),
        };
    }

    /**
     * Cancel the current subscription
     */
    public function cancelSubscription(Tenant $tenant, string $reason = 'user_cancelled'): bool
    {
        $subscription = $tenant->activeSubscription;
        
        if (!$subscription) {
            throw new \Exception('No active subscription to cancel');
        }

        $subscription->update([
            'status' => 'canceled',
            'cancellation_reason' => $reason,
            'ends_at' => $subscription->onTrial() 
                ? $subscription->trial_ends_at 
                : now()->addDays(config('subscription.grace_period_days', 14)),
        ]);

        // Notify tenant about cancellation
        $this->notificationService->sendSubscriptionCancelled($tenant, $subscription);

        return true;
    }

    /**
     * Reactivate a cancelled subscription
     */
    public function reactivateSubscription(Tenant $tenant): bool
    {
        $subscription = $tenant->subscriptions()
            ->where('status', 'canceled')
            ->latest()
            ->first();

        if (!$subscription) {
            throw new \Exception('No canceled subscription found to reactivate');
        }

        // Calculate remaining days from the original period
        $remainingDays = now()->diffInDays($subscription->ends_at);
        
        $subscription->update([
            'status' => 'active',
            'cancellation_reason' => null,
            'ends_at' => now()->addDays($remainingDays),
        ]);

        // Notify tenant about reactivation
        $this->notificationService->sendSubscriptionReactivated($tenant, $subscription);

        return true;
    }
}
