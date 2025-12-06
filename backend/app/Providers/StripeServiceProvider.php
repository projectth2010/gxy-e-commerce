<?php

namespace App\Providers;

use App\Services\StripeWebhookHandler;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class StripeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StripeClient::class, function () {
            return new StripeClient([
                'api_key' => config('services.stripe.secret'),
                'stripe_version' => '2023-10-16',
            ]);
        });

        $this->app->singleton(StripeWebhookHandler::class, function ($app) {
            return new StripeWebhookHandler(
                $app->make(StripeClient::class)
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
