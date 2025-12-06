<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ExcludeFromTenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip tenant middleware for webhook routes
        if ($request->is('api/webhook/*')) {
            return $next($request);
        }

        // Apply tenant middleware for other routes
        return app(\App\Http\Middleware\TenantMiddleware::class)->handle($request, $next);
    }
}
