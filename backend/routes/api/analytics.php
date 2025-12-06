<?php

use App\Http\Controllers\Api\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    // Subscription Analytics
    Route::prefix('analytics/subscriptions')->group(function () {
        Route::get('/overview', [AnalyticsController::class, 'getOverview']);
        Route::get('/mrr-trends', [AnalyticsController::class, 'getMRRTrends']);
        Route::get('/churn-analysis', [AnalyticsController::class, 'getChurnAnalysis']);
        Route::get('/health', [AnalyticsController::class, 'getSubscriptionHealth']);
    });
});
