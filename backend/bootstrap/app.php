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
        // Apply tenant context to all web and API routes
        $middleware->web(\App\Http\Middleware\TenantContextMiddleware::class);
        $middleware->api(\App\Http\Middleware\TenantContextMiddleware::class);
        
        // Add other middleware as needed
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withProviders([
        // Register our service provider
        \App\Providers\TenancyServiceProvider::class,
    ])
    ->create();
