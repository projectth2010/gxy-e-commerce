<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StripeWebhookHandler;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeWebhookHandler $handler)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload, 
                $sigHeader, 
                $webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            return response()->json([
                'error' => 'Invalid payload',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (SignatureVerificationException $e) {
            return response()->json([
                'error' => 'Invalid signature',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }

        // Handle the event
        $handler->handleEvent($event);

        return response()->json(['status' => 'success']);
    }
}
