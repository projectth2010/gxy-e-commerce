<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Services\SubscriptionMetricsService;
use Illuminate\Support\Facades\Log;

class UpdateSubscriptionMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:update-metrics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update and cache subscription metrics for the dashboard';

    /**
     * The subscription metrics service instance.
     *
     * @var \App\Services\SubscriptionMetricsService
     */
    protected $metricsService;

    /**
     * Create a new command instance.
     *
     * @param  \App\Services\SubscriptionMetricsService  $metricsService
     * @return void
     */
    public function __construct(SubscriptionMetricsService $metricsService)
    {
        parent::__construct();
        $this->metricsService = $metricsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting subscription metrics update...');
        
        try {
            // Update and cache key metrics
            $this->updateMetric('mrr', $this->metricsService->getMonthlyRecurringRevenue());
            $this->updateMetric('arr', $this->metricsService->getAnnualRecurringRevenue());
            $this->updateMetric('active_subscriptions', $this->metricsService->getActiveSubscriptionCount());
            $this->updateMetric('trial_subscriptions', $this->metricsService->getTrialSubscriptionCount());
            $this->updateMetric('churn_rate', $this->metricsService->getChurnRate());
            $this->updateMetric('mrr_growth_rate', $this->metricsService->getMrrGrowthRate());
            $this->updateMetric('arpu', $this->metricsService->getAverageRevenuePerUser());
            $this->updateMetric('ltv', $this->metricsService->getLTV());
            $this->updateMetric('retention_rate', $this->metricsService->getRetentionRate());
            
            // Update revenue by plan (cache for 6 hours)
            Cache::put('metrics:revenue_by_plan', $this->metricsService->getRevenueByPlan(), now()->addHours(6));
            
            // Update upcoming renewals (cache for 1 hour)
            Cache::put('metrics:upcoming_renewals', $this->metricsService->getUpcomingRenewals(), now()->addHour());
            
            // Update recent failed payments (cache for 1 hour)
            Cache::put('metrics:recent_failed_payments', $this->metricsService->getRecentFailedPayments(), now()->addHour());
            
            // Update customer engagement metrics (cache for 6 hours)
            Cache::put('metrics:engagement_metrics', $this->metricsService->getCustomerEngagementMetrics(), now()->addHours(6));
            
            // Update subscription health insights (cache for 12 hours)
            Cache::put('metrics:health_insights', $this->metricsService->getSubscriptionHealthInsights(), now()->addHours(12));
            
            $this->info('Subscription metrics updated successfully!');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            Log::error('Failed to update subscription metrics: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            
            $this->error('Failed to update subscription metrics: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Update a single metric in the cache with a 1-hour TTL
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function updateMetric(string $key, $value): void
    {
        Cache::put("metrics:{$key}", $value, now()->addHour());
        $this->line("Updated metric: {$key}");
    }
}
