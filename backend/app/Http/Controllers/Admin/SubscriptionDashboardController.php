<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionMetricsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionDashboardController extends Controller
{
    protected $metrics;

    public function __construct(SubscriptionMetricsService $metrics)
    {
        $this->middleware('auth');
        $this->middleware('can:view_subscription_dashboard');
        $this->metrics = $metrics;
    }

    public function index()
    {
        $metrics = [
            // Core Metrics
            'mrr' => $this->metrics->getMonthlyRecurringRevenue(),
            'arr' => $this->metrics->getAnnualRecurringRevenue(),
            'active_subscriptions' => $this->metrics->getActiveSubscriptionCount(),
            'trial_subscriptions' => $this->metrics->getTrialSubscriptionCount(),
            'churn_rate' => $this->metrics->getChurnRate(),
            'mrr_growth_rate' => $this->metrics->getMrrGrowthRate(),
            'average_revenue_per_user' => $this->metrics->getAverageRevenuePerUser(),
            'recent_alerts' => $this->getRecentAlerts(),
            'subscription_health' => $this->metrics->getSubscriptionHealthScore(),
            'revenue_by_plan' => $this->metrics->getRevenueByPlan(),
            
            // New Metrics
            'customer_lifetime_value' => $this->metrics->getCustomerLifetimeValue(),
            'customer_acquisition_cost' => $this->metrics->getCustomerAcquisitionCost(),
            'expansion_mrr' => $this->metrics->getExpansionMrr(),
            'retention_rate' => $this->metrics->getRetentionRate(),
            'upcoming_renewals' => $this->metrics->getUpcomingRenewals(),
            'recent_failed_payments' => $this->metrics->getRecentFailedPayments(),
            'engagement_metrics' => $this->metrics->getCustomerEngagementMetrics(),
            'health_insights' => $this->metrics->getSubscriptionHealthInsights(),
        ];

        return Inertia::render('Admin/Subscriptions/Dashboard', [
            'metrics' => $metrics,
            'filters' => request()->all(['search', 'plan', 'status', 'date_from', 'date_to']),
        ]);
    }

    protected function getRecentAlerts(int $limit = 10)
    {
        $logFile = storage_path('logs/subscription.log');
        $alerts = [];

        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);
            $logEntries = array_filter(explode("\n", $logContent));
            $logEntries = array_slice($logEntries, -$limit); // Get last $limit entries

            foreach ($logEntries as $entry) {
                if ($json = json_decode($entry, true)) {
                    $alerts[] = [
                        'timestamp' => $json['timestamp'] ?? now()->toDateTimeString(),
                        'level' => $json['level'] ?? 'info',
                        'message' => $json['message'] ?? 'No message',
                        'context' => $json['context'] ?? [],
                    ];
                }
            }
        }

        return array_reverse($alerts); // Show newest first
    }

    public function getMetricsData(Request $request)
    {
        $validated = $request->validate([
            'period' => 'required|in:7d,30d,90d,12m',
            'metric' => 'required|in:mrr,active_subscriptions,churn_rate',
        ]);

        $data = $this->metrics->getHistoricalData(
            $validated['metric'],
            $validated['period']
        );

        return response()->json($data);
    }
}
