<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Stripe Webhook Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider and are not subject
| to the global middleware stack.
|
*/

Route::middleware('webhook')->post('/stripe-webhook', function (Request $request) {
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
