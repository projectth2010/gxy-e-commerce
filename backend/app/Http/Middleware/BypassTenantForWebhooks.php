<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BypassTenantForWebhooks
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
        if ($request->is('api/stripe-webhook')) {
            return $next($request);
        }

        // For other routes, apply the tenant middleware
        return app(\App\Http\Middleware\TenantMiddleware::class)->handle($request, $next);
    }
}
