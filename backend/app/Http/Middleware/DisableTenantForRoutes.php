<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DisableTenantForRoutes
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$routes)
    {
        foreach ($routes as $route) {
            if ($request->is($route)) {
                // Skip tenant middleware for this route
                return $next($request);
            }
        }

        // For other routes, apply the tenant middleware
        return app(\App\Http\Middleware\TenantMiddleware::class)->handle($request, $next);
    }
}
