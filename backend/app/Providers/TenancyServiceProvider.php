<?php

namespace App\Providers;

use App\Core\Tenancy\TenantResolver;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;

class TenancyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('currentTenant', function () {
            return null; // Will be set in the middleware
        });

        $this->app->bind(TenantResolver::class, function ($app) {
            return new TenantResolver();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Add a macro to the Request class to easily get the current tenant
        Request::macro('tenant', function () {
            return app('currentTenant');
        });
    }
}
