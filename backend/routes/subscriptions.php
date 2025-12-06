<?php

use App\Http\Controllers\Api\SubscriptionPlanController;
use App\Http\Controllers\Api\UserSubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Subscription API Routes
|--------------------------------------------------------------------------
*/

// Public routes (no auth required)
Route::get('/plans', [SubscriptionPlanController::class, 'index']);
Route::get('/plans/{subscriptionPlan}', [SubscriptionPlanController::class, 'show']);

// Protected routes (require authentication)
Route::middleware('auth:api')->group(function () {
    // Subscription management
    Route::prefix('subscription')->group(function () {
        // Get current subscription
        Route::get('/', [UserSubscriptionController::class, 'index']);
        
        // Create new subscription
        Route::post('/', [UserSubscriptionController::class, 'store']);
        
        // Cancel subscription (at period end)
        Route::delete('/', [UserSubscriptionController::class, 'destroy']);
        
        // Resume cancelled subscription
        Route::post('/resume', [UserSubscriptionController::class, 'resume']);
    });
    
    // Admin routes (protected by 'admin' middleware)
    Route::middleware('admin')->prefix('admin')->group(function () {
        // Subscription plan management
        Route::apiResource('plans', SubscriptionPlanController::class)->except(['index', 'show']);
        
        // Additional admin-only subscription endpoints can be added here
    });
});
