<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionMetricsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected $metricsService;

    public function __construct(SubscriptionMetricsService $metricsService)
    {
        $this->middleware('auth');
        $this->metricsService = $metricsService;
    }

    public function index()
    {
        $overview = [
            'mrr' => $this->metricsService->getMRR(),
            'active_subscriptions' => $this->metricsService->getActiveSubscriptionsCount(),
            'trial_subscriptions' => $this->metricsService->getTrialSubscriptionsCount(),
            'churn_rate_30d' => $this->metricsService->getChurnRate(30),
            'mrr_growth' => $this->metricsService->getMRRGrowth(30),
            'plan_distribution' => $this->metricsService->getPlanDistribution(),
        ];

        $mrrTrends = [
            'data' => $this->metricsService->getMRRTrend(30),
        ];

        $churnAnalysis = [
            'churn_rate' => $this->metricsService->getChurnRate(90),
            'churn_trend' => $this->metricsService->getChurnTrend(90),
            'cancellation_reasons' => $this->metricsService->getCancellationReasons(90),
        ];

        $health = [
            'failed_payments' => $this->metricsService->getFailedPayments(7),
            'upcoming_renewals' => $this->metricsService->getUpcomingRenewals(7)->toArray(),
            'trial_conversion_rate' => $this->metricsService->getTrialConversionRate(30),
        ];

        return Inertia::render('Dashboard/SubscriptionAnalytics', [
            'overview' => $overview,
            'mrrTrends' => $mrrTrends,
            'churnAnalysis' => $churnAnalysis,
            'health' => $health,
        ]);
    }
}
