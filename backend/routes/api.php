<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

// Public routes
Route::post('/login', [\App\Http\Controllers\Api\Auth\AuthController::class, 'login']);

// Stripe webhook (must be public)
Route::post('/webhook/stripe', [\App\Http\Controllers\Api\Webhook\StripeWebhookController::class, 'handleWebhook'])
    ->name('webhook.stripe');

// Public health check endpoint
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::get('/user', [\App\Http\Controllers\Api\Auth\AuthController::class, 'user']);
    Route::post('/logout', [\App\Http\Controllers\Api\Auth\AuthController::class, 'logout']);

    // Center Control Panel API Routes
    Route::prefix('center')->group(function () {
        // Plan management
        Route::apiResource('plans', \App\Http\Controllers\Api\Center\PlanController::class)
            ->except(['destroy']);
        
        // Feature management
        Route::apiResource('features', \App\Http\Controllers\Api\Center\FeatureController::class)
            ->except(['destroy']);
        
        // Tenant plan assignments
        Route::apiResource('tenant-plan-assignments', \App\Http\Controllers\Api\Center\TenantPlanAssignmentController::class)
            ->except(['update', 'destroy']);
        
        // Cancel a plan assignment
        Route::post('tenant-plan-assignments/{tenant_plan_assignment}/cancel', 
            [\App\Http\Controllers\Api\Center\TenantPlanAssignmentController::class, 'cancel']
        )->name('tenant-plan-assignments.cancel');

        // Subscription management
        Route::prefix('tenants/{tenant}')->group(function () {
            Route::get('subscription', [\App\Http\Controllers\Api\SubscriptionController::class, 'show']);
            Route::post('subscription', [\App\Http\Controllers\Api\SubscriptionController::class, 'store']);
            Route::post('subscription/payment-method', [\App\Http\Controllers\Api\SubscriptionController::class, 'updatePaymentMethod']);
            Route::post('subscription/cancel', [\App\Http\Controllers\Api\SubscriptionController::class, 'cancel']);
            Route::post('subscription/resume', [\App\Http\Controllers\Api\SubscriptionController::class, 'resume']);
            Route::post('subscription/setup-intent', [\App\Http\Controllers\Api\SubscriptionController::class, 'setupIntent']);
        });
    });

    // Tenant-facing APIs
    Route::middleware(['tenant'])->prefix('tenant')->group(function () {
        // Event tracking
        Route::post('/track', [\App\Http\Controllers\Api\EventTrackingController::class, 'store'])
            ->name('tenant.track');
        
        // Add more tenant-facing routes here
    });
});
