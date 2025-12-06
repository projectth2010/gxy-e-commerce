<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Tenant;
use App\Policies\TenantPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the WebhookRouteServiceProvider
        $this->app->register(\App\Providers\WebhookRouteServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Explicit route model binding for Tenant
        Route::bind('tenant', function ($value) {
            return Tenant::where('id', $value)
                ->orWhere('code', $value)
                ->firstOrFail();
        });

        // Register the TenantPolicy
        Gate::policy(Tenant::class, TenantPolicy::class);
    }
}
