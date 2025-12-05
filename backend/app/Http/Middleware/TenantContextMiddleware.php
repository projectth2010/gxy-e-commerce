<?php

namespace App\Http\Middleware;

use App\Core\Tenancy\TenantResolver;
use Closure;
use Illuminate\Http\Request;

class TenantContextMiddleware
{
    public function __construct(
        protected TenantResolver $resolver,
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        $tenant = $this->resolver->resolve($request);

        if (! $tenant) {
            return response()->json([
                'error' => 'TENANT_NOT_FOUND',
            ], 400);
        }

        app()->instance('currentTenant', $tenant);

        return $next($request);
    }
}
