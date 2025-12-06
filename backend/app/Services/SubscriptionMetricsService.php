<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class SubscriptionMetricsService
{
    public function getActiveSubscriptionsCount(): int
    {
        return Subscription::where('stripe_status', 'active')->count();
    }

    public function getTrialSubscriptionsCount(): int
    {
        return Subscription::whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())
            ->count();
    }

    public function getChurnRate(int $days = 30): float
    {
        $endDate = now();
        $startDate = now()->subDays($days);
        
        $cancelledCount = Subscription::where('stripe_status', 'canceled')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();
            
        $activeAtStart = Subscription::where('stripe_status', 'active')
            ->where('created_at', '<=', $startDate)
            ->count();
            
        return $activeAtStart > 0 ? ($cancelledCount / $activeAtStart) * 100 : 0;
    }

    public function getMRR(): float
    {
        return Subscription::with('plan')
            ->where('stripe_status', 'active')
            ->get()
            ->sum(function ($subscription) {
                return $subscription->plan->price ?? 0;
            });
    }

    public function getSubscriptionGrowth(int $days = 30): array
    {
        $endDate = now();
        $startDate = now()->subDays($days);
        
        return Subscription::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }

    public function getPlanDistribution(): array
    {
        return SubscriptionPlan::withCount(['subscriptions' => function ($query) {
                $query->where('stripe_status', 'active');
            }])
            ->get()
            ->mapWithKeys(function ($plan) {
                return [$plan->name => $plan->subscriptions_count];
            })
            ->toArray();
    }

    public function getFailedPayments(int $days = 30): int
    {
        return DB::table('failed_jobs')
            ->where('queue', 'stripe-webhooks')
            ->where('payload', 'like', '%invoice.payment_failed%')
            ->where('failed_at', '>=', now()->subDays($days))
            ->count();
    }

    public function getMRRGrowth(int $days = 30): float
    {
        $endDate = now();
        $startDate = now()->subDays($days * 2); // Get data for 2x period to calculate growth
        
        $mrrData = Subscription::with('plan')
            ->where('stripe_status', 'active')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->groupBy(function($subscription) {
                return $subscription->created_at->format('Y-m');
            })
            ->map(function($group) {
                return $group->sum('plan.price');
            });

        if ($mrrData->count() < 2) {
            return 0;
        }

        $currentPeriod = $mrrData->last();
        $previousPeriod = $mrrData->values()[0];

        if ($previousPeriod == 0) {
            return 100.0;
        }

        return (($currentPeriod - $previousPeriod) / $previousPeriod) * 100;
    }

    public function getMRRTrend(int $days = 30): array
    {
        $endDate = now();
        $startDate = now()->subDays($days);
        
        // Initialize date range with zero values
        $period = CarbonPeriod::create($startDate, $endDate);
        $mrrData = [];
        
        foreach ($period as $date) {
            $mrrData[$date->format('Y-m-d')] = 0;
        }

        // Get actual MRR data
        $subscriptions = Subscription::with('plan')
            ->where('stripe_status', 'active')
            ->where('created_at', '<=', $endDate)
            ->get();

        foreach ($subscriptions as $subscription) {
            $period = CarbonPeriod::create(
                max($subscription->created_at->startOfDay(), $startDate),
                $subscription->ends_at ? min($subscription->ends_at, $endDate) : $endDate
            );

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');
                $mrrData[$dateStr] = ($mrrData[$dateStr] ?? 0) + ($subscription->plan->price / $date->daysInMonth);
            }
        }

        return $mrrData;
    }

    public function getChurnTrend(int $days = 90): array
    {
        $endDate = now();
        $startDate = now()->subDays($days);
        
        $cancellations = Subscription::select(
                DB::raw('DATE(cancelled_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->whereNotNull('cancelled_at')
            ->whereBetween('cancelled_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        return $cancellations;
    }

    public function getCancellationReasons(int $days = 90): array
    {
        return [
            'too_expensive' => 35,
            'missing_features' => 25,
            'switched_service' => 20,
            'poor_customer_service' => 10,
            'other' => 10,
        ];
        // In a real app, this would come from a cancellation feedback form
    }

    public function getUpcomingRenewals(int $days = 30): Collection
    {
        return Subscription::with(['plan', 'user'])
            ->where('stripe_status', 'active')
            ->whereBetween('current_period_end', [now(), now()->addDays($days)])
            ->orderBy('current_period_end')
            ->get()
            ->map(function($subscription) {
                return [
                    'id' => $subscription->id,
                    'user' => $subscription->user->name,
                    'plan' => $subscription->plan->name,
                    'renewal_date' => $subscription->current_period_end->format('Y-m-d'),
                    'amount' => $subscription->plan->price,
                ];
            });
    }

    public function getTrialConversionRate(int $days = 30): float
    {
        $endDate = now();
        $startDate = now()->subDays($days);
        
        $trials = Subscription::whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>=', $startDate)
            ->where('trial_ends_at', '<=', $endDate)
            ->count();
            
        $converted = Subscription::whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>=', $startDate)
            ->where('trial_ends_at', '<=', $endDate)
            ->where('stripe_status', 'active')
            ->where('trial_ends_at', '<', now())
            ->count();
            
        return $trials > 0 ? ($converted / $trials) * 100 : 0;
    }
}
