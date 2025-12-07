<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;
use App\Channels\TeamsChannel;

class NotificationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->when(TeamsChannel::class)
            ->needs('$webhookUrl')
            ->giveConfig('services.teams.webhook_url');

        Notification::extend('teams', function ($app) {
            return new TeamsChannel();
        });
    }

    public function register()
    {
        //
    }
}
