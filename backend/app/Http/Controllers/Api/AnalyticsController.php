<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionMetricsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    protected SubscriptionMetricsService $metrics;

    public function __construct(SubscriptionMetricsService $metrics)
    {
        $this->middleware('auth:api');
        $this->metrics = $metrics;
    }

    public function getOverview(): JsonResponse
    {
        return response()->json([
            'mrr' => $this->metrics->getMRR(),
            'active_subscriptions' => $this->metrics->getActiveSubscriptionsCount(),
            'trial_subscriptions' => $this->metrics->getTrialSubscriptionsCount(),
            'churn_rate_30d' => $this->metrics->getChurnRate(30),
            'mrr_growth' => $this->metrics->getMRRGrowth(30),
            'plan_distribution' => $this->metrics->getPlanDistribution(),
        ]);
    }

    public function getMRRTrends(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        return response()->json([
            'data' => $this->metrics->getMRRTrend($days),
            'period' => $days . ' days',
        ]);
    }

    public function getChurnAnalysis(Request $request): JsonResponse
    {
        $days = $request->get('days', 90);
        return response()->json([
            'churn_rate' => $this->metrics->getChurnRate($days),
            'churn_trend' => $this->metrics->getChurnTrend($days),
            'cancellation_reasons' => $this->metrics->getCancellationReasons($days),
        ]);
    }

    public function getSubscriptionHealth(): JsonResponse
    {
        return response()->json([
            'failed_payments' => $this->metrics->getFailedPayments(7),
            'upcoming_renewals' => $this->metrics->getUpcomingRenewals(7),
            'trial_conversion_rate' => $this->metrics->getTrialConversionRate(30),
        ]);
    }
}
