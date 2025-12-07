<?php

use App\Http\Controllers\Admin\SubscriptionDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Subscription Dashboard
    Route::get('/subscriptions/dashboard', [SubscriptionDashboardController::class, 'index'])
        ->name('admin.subscriptions.dashboard');
        
    Route::get('/subscriptions/metrics', [SubscriptionDashboardController::class, 'getMetricsData'])
        ->name('admin.subscriptions.metrics');
});
