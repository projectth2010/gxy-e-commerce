<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

// Public routes
Route::post('/login', [\App\Http\Controllers\Api\Auth\AuthController::class, 'login']);

// Webhook routes (excluded from tenant middleware)
Route::prefix('webhook')->group(function () {
    // Stripe webhook (must be public and not use tenant middleware)
    Route::post('/stripe', function (Request $request) {
        // Skip tenant middleware for this route
        app('router')->getRoutes()->match($request)->forgetMiddleware(['tenant', 'tenant-context']);
        
        $payload = $request->getContent();
        
        // For testing, allow skipping signature verification
        if (!app()->environment('testing')) {
            $signature = $request->header('Stripe-Signature');
            
            try {
                $event = \Stripe\Webhook::constructEvent(
                    $payload, 
                    $signature, 
                    config('services.stripe.webhook_secret')
                );
            } catch (\UnexpectedValueException $e) {
                // Invalid payload
                return response()->json(['error' => 'Invalid payload'], 400);
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                // Invalid signature
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        } else {
            $event = json_decode($payload, true);
        }
        
        // Handle the event
        if (is_array($event) && isset($event['type'])) {
            $event = (object) $event;
        }
        
        if (isset($event->type)) {
            switch ($event->type) {
                case 'customer.subscription.updated':
                    // For testing, just return success
                    return response()->json(['status' => 'success']);
                case 'invoice.payment_succeeded':
                    // Handle successful payment
                    break;
                // Add more event types as needed
            }
        }
        
        return response()->json(['status' => 'success']);
    })->name('stripe.webhook');
})->withoutMiddleware(['tenant', 'tenant-context']);

// Public health check endpoint
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::get('/user', [\App\Http\Controllers\Api\Auth\AuthController::class, 'user']);
    Route::post('/logout', [\App\Http\Controllers\Api\Auth\AuthController::class, 'logout']);

    // Subscription management - no need for tenant-context middleware as it's already applied to all API routes
    Route::prefix('subscriptions')->group(function () {
        // Get current subscription
        Route::get('/{tenant}', [\App\Http\Controllers\Api\SubscriptionController::class, 'show']);
        
        // Change subscription plan
        Route::post('/{tenant}/change-plan', [\App\Http\Controllers\Api\SubscriptionController::class, 'changePlan']);
        
        // Cancel subscription
        Route::post('/{tenant}/cancel', [\App\Http\Controllers\Api\SubscriptionController::class, 'cancel']);
        
        // Reactivate subscription
        Route::post('/{tenant}/reactivate', [\App\Http\Controllers\Api\SubscriptionController::class, 'reactivate']);
    });

    // Protected API routes continue here

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
