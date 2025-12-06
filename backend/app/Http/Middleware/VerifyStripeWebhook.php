<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class VerifyStripeWebhook
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('services.stripe.webhook_secret')) {
            Log::error('Stripe webhook secret is not configured');
            throw new AccessDeniedHttpException('Webhook not configured');
        }

        $signature = $request->header('Stripe-Signature');
        
        if (!$signature) {
            Log::error('Missing Stripe-Signature header');
            throw new AccessDeniedHttpException('Missing signature');
        }

        try {
            Webhook::constructEvent(
                $request->getContent(),
                $signature,
                config('services.stripe.webhook_secret')
            );
        } catch (\Exception $e) {
            Log::error('Webhook verification failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);
            throw new AccessDeniedHttpException('Invalid signature');
        }

        return $next($request);
    }
}
