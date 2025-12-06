<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Create a new middleware group for webhooks that excludes tenant middleware
        $middleware->group('webhook', [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
        
        // Replace the default tenant middleware with our custom one that can be disabled for specific routes
        $middleware->alias([
            'tenant' => \App\Http\Middleware\DisableTenantForRoutes::class,
        ]);
        
        // Apply tenant context to web and API routes
        $middleware->web(\App\Http\Middleware\TenantContextMiddleware::class);
        $middleware->api(\App\Http\Middleware\TenantContextMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withProviders([
        // Register our service providers
        \App\Providers\TenancyServiceProvider::class,
        \App\Providers\RouteServiceProvider::class,
    ])
    ->create();
