<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class SubscriptionMetricsService
{
    protected $subscriptionModel;
    protected $userModel;
    protected $paymentModel;

    public function __construct()
    {
        $this->subscriptionModel = config('subscription.models.subscription', \App\Models\Subscription::class);
        $this->userModel = config('auth.providers.users.model', \App\Models\User::class);
        $this->paymentModel = config('subscription.models.payment', \App\Models\Payment::class);
    }
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

    public function getTotalCustomerCount(): int
    {
        return $this->userModel::has('subscriptions')->count();
    }

    public function getPaidUserCount(): int
    {
        return $this->subscriptionModel::where('stripe_status', 'active')
            ->whereNull('trial_ends_at')
            ->count();
    }

    public function getMonthlyRecurringRevenue(): float
    {
        return (float) $this->subscriptionModel::where('stripe_status', 'active')
            ->whereNull('trial_ends_at')
            ->join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->sum('subscription_plans.price');
    }

    public function getAnnualRecurringRevenue(): float
    {
        return $this->getMonthlyRecurringRevenue() * 12;
    }

    public function getActiveSubscriptionCount(): int
    {
        return $this->subscriptionModel::where('stripe_status', 'active')
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->count();
    }

    public function getTrialSubscriptionCount(): int
    {
        return $this->subscriptionModel::whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())
            ->count();
    }

    /**
     * Calculate churn rate for a given period
     * 
     * @param int $days Number of days to calculate churn for (default: 30)
     * @return float Churn rate as a decimal (e.g., 0.05 for 5%)
     */
    /**
     * Calculate churn rate for a given period
     * 
     * @param int $days Number of days to calculate churn for (default: 30)
     * @return float Churn rate as a decimal (e.g., 0.05 for 5%)
     */
    public function getChurnRate(int $days = 30): float
    {
        $startDate = now()->subDays($days);
        $endDate = now();
        
        $startCount = $this->subscriptionModel::where('created_at', '<=', $startDate)
            ->whereIn('stripe_status', ['active', 'trialing'])
            ->count();
            
        if ($startCount === 0) {
            return 0.0;
        }
            
        $churned = $this->subscriptionModel::whereBetween('ends_at', [$startDate, $endDate])
            ->where('stripe_status', 'cancelled')
            ->count();
            
        return $churned / $startCount;
    }

    /**
     * Calculate MRR growth rate with caching
     */
    public function getMrrGrowthRate(): float
    {
        return Cache::remember('mrr_growth_rate', now()->addHours(6), function () {
            try {
                $currentMrr = $this->getMonthlyRecurringRevenue();
                $previousMonthMrr = $this->subscriptionModel::where('subscriptions.stripe_status', 'active')
                    ->whereNull('subscriptions.trial_ends_at')
                    ->where('subscriptions.updated_at', '>=', now()->subMonths(2))
                    ->where('subscriptions.updated_at', '<=', now()->subMonth())
                    ->join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
                    ->sum('subscription_plans.price');
                    
                if ($previousMonthMrr == 0) {
                    return $currentMrr > 0 ? 100.0 : 0.0;
                }
                
                $growthRate = (($currentMrr - $previousMonthMrr) / $previousMonthMrr) * 100;
                
                // Log significant changes for monitoring
                if (abs($growthRate) > 20) {
                    Log::channel('subscription')->warning('Significant MRR change detected', [
                        'current_mrr' => $currentMrr,
                        'previous_mrr' => $previousMonthMrr,
                        'growth_rate' => $growthRate,
                        'period' => 'monthly'
                    ]);
                }
                
                return $growthRate;
                
            } catch (\Exception $e) {
                Log::error('Error calculating MRR growth rate: ' . $e->getMessage(), [
                    'exception' => $e,
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Fallback to a safe default in case of errors
                return 0.0;
            }
        });
    }
    
    /**
     * Get customer lifetime value (LTV)
     */
    public function getCustomerLifetimeValue(): float
    {
        return Cache::remember('customer_ltv', now()->addDay(), function () {
            try {
                $avgSubscriptionValue = $this->subscriptionModel::where('stripe_status', 'active')
                    ->whereNull('trial_ends_at')
                    ->avg(DB::raw('(amount / 100)'));
                    
                $avgCustomerLifespan = 12; // Default to 12 months
                
                return $avgSubscriptionValue * $avgCustomerLifespan;
                
            } catch (\Exception $e) {
                Log::error('Error calculating customer LTV: ' . $e->getMessage());
                return 0.0;
            }
        });
    }
    
    /**
     * Get customer acquisition cost (CAC)
     */
    public function getCustomerAcquisitionCost(): float
    {
        // This would typically come from your marketing data
        // For now, we'll return a static value or calculate from payments
        return 0.0; // Implement based on your business logic
    }
    
    /**
     * Get expansion MRR (upgrades, add-ons)
     */
    public function getExpansionMrr(): float
    {
        return Cache::remember('expansion_mrr', now()->addHours(6), function () {
            try {
                // This would track upgrades and add-ons
                // Implementation depends on your subscription model
                return 0.0; // Implement based on your business logic
            } catch (\Exception $e) {
                Log::error('Error calculating expansion MRR: ' . $e->getMessage());
                return 0.0;
            }
        });
    }
    
    /**
     * Get customer retention rate
     */
    public function getRetentionRate(): float
    {
        $churnRate = $this->getChurnRate();
        return max(0, 100 - ($churnRate * 100));
    }

    public function getAverageRevenuePerUser(): float
    {
        $activeSubscriptions = $this->subscriptionModel::where('subscriptions.stripe_status', 'active')
            ->whereNull('subscriptions.trial_ends_at')
            ->join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->select('subscription_plans.price')
            ->get();
            
        if ($activeSubscriptions->isEmpty()) {
            return 0.0;
        }
        
        return (float) $activeSubscriptions->avg('price');
    }

    public function getRevenueByPlan(): array
    {
        return $this->subscriptionModel::where('subscriptions.stripe_status', 'active')
            ->whereNull('subscriptions.trial_ends_at')
            ->join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->select('subscription_plans.name', DB::raw('SUM(subscription_plans.price) as total_revenue'))
            ->groupBy('subscription_plans.name')
            ->pluck('total_revenue', 'name')
            ->mapWithKeys(function ($amount, $name) {
                return [$name ?: 'Unknown Plan' => (float) $amount];
            })
            ->toArray();
    }

    public function getHistoricalData(string $metric, string $period): array
    {
        $endDate = now();
        $startDate = $this->getStartDateFromPeriod($period);
        
        $data = [];
        $period = CarbonPeriod::create($startDate, $endDate);
        
        foreach ($period as $date) {
            $data[$date->format('Y-m-d')] = $this->getMetricForDate($metric, $date);
        }
        
        return [
            'labels' => array_keys($data),
            'datasets' => [
                [
                    'label' => ucfirst(str_replace('_', ' ', $metric)),
                    'data' => array_values($data),
                    'borderColor' => '#3B82F6',
                    'fill' => false,
                ]
            ]
        ];
    }
    
    protected function getStartDateFromPeriod(string $period): Carbon
    {
        return match ($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '12m' => now()->subMonths(12),
            default => now()->subDays(30),
        };
    }
    
    protected function getMetricForDate(string $metric, Carbon $date)
    {
        return match ($metric) {
            'mrr' => $this->getMrrForDate($date),
            'active_subscriptions' => $this->getActiveSubscriptionsForDate($date),
            'churn_rate' => $this->getChurnRateForDate($date),
            default => 0,
        };
    }
    
    protected function getMrrForDate(Carbon $date): float
    {
        return (float) $this->subscriptionModel::where('stripe_status', 'active')
            ->whereNull('trial_ends_at')
            ->whereDate('created_at', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $date);
            })
            ->sum(DB::raw('(amount / 100)'));
    }
    
    protected function getActiveSubscriptionsForDate(Carbon $date): int
    {
        return $this->subscriptionModel::where('stripe_status', 'active')
            ->whereDate('created_at', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $date);
            })
            ->count();
    }
    
    protected function getChurnRateForDate(Carbon $date): float
    {
        $startDate = (clone $date)->subMonth();
        
        $startCount = $this->subscriptionModel::where('stripe_status', 'active')
            ->whereDate('created_at', '<=', $startDate)
            ->where(function ($query) use ($startDate) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $startDate);
            })
            ->count();
            
        if ($startCount === 0) {
            return 0.0;
        }
        
        $churnedCount = $this->subscriptionModel::where('stripe_status', 'cancelled')
            ->whereDate('ends_at', '>=', $startDate)
            ->whereDate('ends_at', '<=', $date)
            ->count();
            
        return ($churnedCount / $startCount) * 100; // Return as percentage
    }

    public function getSubscriptionHealthScore(): int
    {
        $score = 100;
        
        // Deduct for high churn rate
        $churnRate = $this->getChurnRate();
        if ($churnRate > 0.05) { // 5% churn rate
            $score -= min(30, ($churnRate - 0.05) * 100);
        }
        
        // Deduct for many failed payments
        $failedPayments = $this->getRecentFailedPaymentsCount();
        if ($failedPayments > 0) {
            $score -= min(20, $failedPayments);
        }
        
        // Deduct for many expiring cards
        $expiringCards = $this->getExpiringCardsCount(30);
        if ($expiringCards > 0) {
            $score -= min(10, $expiringCards);
        }
        
        return max(0, (int) round($score));
    }
    
    protected function getRecentFailedPaymentsCount(int $days = 30): int
    {
        return $this->paymentModel::where('status', 'failed')
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
    }
    
    /**
     * Get count of payment methods expiring soon
     * 
     * @param int $daysAhead Number of days to check for expiration (default: 30)
     * @return int Number of expiring payment methods
     */
    public function getExpiringCardsCount(int $daysAhead = 30): int
    {
        return $this->userModel::whereHas('paymentMethods', function ($query) use ($daysAhead) {
            $query->where('exp_month', now()->month)
                ->where('exp_year', now()->year)
                ->where('card_expires_soon', true);
        })->count();
    }
    
    public function getMRR(): float
    {
        return $this->getMonthlyRecurringRevenue();
    }

    public function getARR(): float
    {
        return $this->getMRR() * 12;
    }

    public function getARPU(): float
    {
        $mrr = $this->getMRR();
        $activeUsers = $this->getPaidUserCount();
        
        return $activeUsers > 0 ? $mrr / $activeUsers : 0;
    }

    public function getLTV(): float
    {
        $arpu = $this->getARPU();
        $churnRate = $this->getChurnRate(90); // 90-day churn rate
        
        return $churnRate > 0 ? $arpu / $churnRate : $arpu * 12; // Assume 1 year if no churn
    }

    public function getRenewalSuccessRate(): float
    {
        $renewalAttempts = $this->paymentModel::where('type', 'renewal')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
            
        $successfulRenewals = $this->paymentModel::where('type', 'renewal')
            ->where('status', 'succeeded')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
            
        return $renewalAttempts > 0 ? $successfulRenewals / $renewalAttempts : 1;
    }

    public function getRecentPaymentFailures(int $hours = 24): int
    {
        return $this->paymentModel::where('status', 'failed')
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();
    }

    public function getNewSubscriptionsCount(int $days = 7): int
    {
        return $this->subscriptionModel::where('created_at', '>=', now()->subDays($days))
            ->count();
    }

    public function getCancellationsCount(int $days = 7): int
    {
        return $this->subscriptionModel::where('stripe_status', 'cancelled')
            ->where('cancelled_at', '>=', now()->subDays($days))
            ->count();
    }

    public function getTrialsEndingSoonCount(int $days = 7): int
    {
        return $this->subscriptionModel::whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDays($days)])
            ->count();
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
        
        $trend = [];
        
        // Get daily churn counts
        $results = $this->subscriptionModel::selectRaw('DATE(ends_at) as date, COUNT(*) as churn_count')
            ->where('ends_at', '>=', $startDate)
            ->where('ends_at', '<=', $endDate)
            ->whereNotNull('ends_at')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Get total active at start
        $totalActive = $this->subscriptionModel::where('stripe_status', 'active')
            ->whereNull('ends_at')
            ->where('created_at', '<', $startDate)
            ->count();
            
        // Calculate daily churn rate
        $currentActive = $totalActive;
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $churnCount = $results[$dateStr]->churn_count ?? 0;
            
            // Calculate churn rate for the day
            $churnRate = $currentActive > 0 ? ($churnCount / $currentActive) * 100 : 0;
            $trend[$dateStr] = round($churnRate, 2);
            
            // Update active count for next day
            // (simplified - should also account for new activations)
            $currentActive = max(0, $currentActive - $churnCount);
            
            $currentDate->addDay();
        }

        return $trend;
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

    public function getUpcomingRenewals(int $days = 30): array
    {
        return Cache::remember("upcoming_renewals_{$days}", now()->addHour(), function () use ($days) {
            try {
                return $this->subscriptionModel::with(['plan', 'user'])
                    ->where('stripe_status', 'active')
                    ->whereBetween('current_period_end', [now(), now()->addDays($days)])
                    ->orderBy('current_period_end')
                    ->get()
                    ->map(function($subscription) {
                        return [
                            'id' => $subscription->id,
                            'user_id' => $subscription->user_id,
                            'user' => $subscription->user->name ?? 'Unknown User',
                            'plan' => $subscription->plan->name ?? 'Unknown Plan',
                            'renewal_date' => $subscription->current_period_end->format('Y-m-d'),
                            'amount' => $subscription->plan->price ?? 0,
                            'status' => $this->getRenewalStatus($subscription)
                        ];
                    })->toArray();
            } catch (\Exception $e) {
                Log::error('Error fetching upcoming renewals: ' . $e->getMessage());
                return [];
            }
        });
    }

    public function getRecentFailedPayments(int $limit = 10): array
    {
        return Cache::remember("recent_failed_payments_{$limit}", now()->addMinutes(30), function () use ($limit) {
            try {
                return $this->paymentModel::with(['user', 'subscription.plan'])
                    ->where('status', 'failed')
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get()
                    ->map(function($payment) {
                        return [
                            'id' => $payment->id,
                            'user_id' => $payment->user_id,
                            'user_name' => $payment->user->name ?? 'Unknown User',
                            'amount' => $payment->amount / 100,
                            'currency' => strtoupper($payment->currency),
                            'subscription' => $payment->subscription->plan->name ?? 'Unknown Plan',
                            'failed_at' => $payment->created_at->format('Y-m-d H:i:s'),
                            'error' => $payment->failure_reason
                        ];
                    })->toArray();
            } catch (\Exception $e) {
                Log::error('Error fetching failed payments: ' . $e->getMessage());
                return [];
            }
        });
    }

    public function getCustomerEngagementMetrics(int $days = 30): array
    {
        return Cache::remember("customer_engagement_{$days}", now()->addHours(6), function () use ($days) {
            try {
                $startDate = now()->subDays($days);
                
                return [
                    'active_users' => $this->getActiveUsersCount($startDate),
                    'login_frequency' => $this->getAverageLoginFrequency($startDate),
                    'feature_usage' => $this->getFeatureUsage($startDate),
                    'support_tickets' => $this->getSupportTicketMetrics($startDate),
                    'nps_score' => $this->getNetPromoterScore($startDate)
                ];
            } catch (\Exception $e) {
                Log::error('Error calculating engagement metrics: ' . $e->getMessage());
                return [];
            }
        });
    }

    public function getSubscriptionHealthInsights(): array
    {
        return [
            'at_risk_subscriptions' => $this->getAtRiskSubscriptions(),
            'expiring_trials' => $this->getExpiringTrials(),
            'payment_issues' => $this->getPaymentIssues(),
            'engagement_trend' => $this->getEngagementTrend()
        ];
    }

    protected function getRenewalStatus($subscription): string
    {
        if ($subscription->hasIncompletePayment()) {
            return 'payment_required';
        }
        
        if ($subscription->onGracePeriod()) {
            return 'cancelling';
        }
        
        if ($subscription->onTrial()) {
            return 'trial';
        }
        
        return 'active';
    }

    protected function getActiveUsersCount(Carbon $since): int
    {
        return $this->userModel::whereHas('sessions', function($query) use ($since) {
            $query->where('last_activity', '>=', $since);
        })->count();
    }

    protected function getAverageLoginFrequency(Carbon $since): float
    {
        $logins = DB::table('sessions')
            ->select('user_id', DB::raw('COUNT(*) as login_count'))
            ->where('last_activity', '>=', $since->timestamp)
            ->groupBy('user_id')
            ->get();
            
        return $logins->avg('login_count') ?? 0;
    }

    protected function getFeatureUsage(Carbon $since): array
    {
        // Implement based on your feature tracking
        return [];
    }

    protected function getSupportTicketMetrics(Carbon $since): array
    {
        // Implement based on your support system
        return [];
    }

    protected function getNetPromoterScore(Carbon $since): ?float
    {
        // Implement based on your NPS surveys
        return null;
    }

    protected function getAtRiskSubscriptions(): array
    {
        return []; // Implement based on your risk criteria
    }

    protected function getExpiringTrials(): array
    {
        return []; // Implement based on your trial logic
    }

    protected function getPaymentIssues(): array
    {
        return []; // Implement based on your payment processing
    }

    protected function getEngagementTrend(): array
    {
        return []; // Implement trend analysis
    }
}
