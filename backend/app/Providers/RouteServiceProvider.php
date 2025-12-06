<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapWebhookRoutes();
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Define the "webhook" routes for the application.
     *
     * These routes are loaded by the RouteServiceProvider and all of them will
     * be assigned to the "webhook" middleware group. Make something great!
     *
     * @return void
     */
    protected function mapWebhookRoutes()
    {
        // Register webhook routes without any middleware
        Route::group([], function () {
            require base_path('routes/webhook.php');
        });
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(function () {
                 require base_path('routes/api.php');
                 require base_path('routes/subscriptions.php');
             });
    }

    public function boot()
    {
        parent::boot();
        
        $this->configureRateLimiting();

        // Explicitly bind the Tenant model for route model binding
        Route::model('tenant', \App\Models\Tenant::class);
        
        // Exclude webhook routes from tenant middleware
        $this->excludeWebhookRoutesFromTenant();
        
        // Use a custom resolver for the tenant parameter
        Route::bind('tenant', function ($value, $route) {
            // Log the binding attempt with detailed information
            $logContext = [
                'value' => $value,
                'route' => $route->uri(),
                'method' => request()?->method(),
                'url' => request()?->fullUrl(),
                'full_url' => request()?->fullUrl(),
                'path' => request()?->path(),
                'route_parameters' => request()?->route()?->parameters(),
                'user_id' => auth()->check() ? auth()->id() : null,
                'all_tenants' => \App\Models\Tenant::select('id', 'code', 'name')->get()->toArray(),
            ];
            
            \Log::info('RouteServiceProvider - Attempting to resolve tenant', $logContext);

            // First try to find by code (exact match)
            $tenant = \App\Models\Tenant::where('code', $value)->first();
            
            // If not found by code, try by ID if the value is numeric
            if (!$tenant && is_numeric($value)) {
                $tenant = \App\Models\Tenant::find($value);
            }
            
            // If still not found, log detailed error
            if (!$tenant) {
                $errorContext = [
                    'requested_value' => $value,
                    'all_tenants' => \App\Models\Tenant::select('id', 'code', 'name')->get()->toArray(),
                    'route_parameters' => request()?->route()?->parameters(),
                    'user_id' => auth()->check() ? auth()->id() : null,
                    'request_headers' => request()?->headers->all(),
                ];
                
                \Log::error('RouteServiceProvider - Tenant not found', $errorContext);
                
                // For API responses, return JSON
                if (request()?->wantsJson() || request()?->is('api/*')) {
                    return response()->json([
                        'error' => 'TENANT_NOT_FOUND',
                        'message' => 'The requested tenant could not be found',
                        'requested_value' => $value,
                        'available_tenants' => \App\Models\Tenant::select('id', 'code', 'name')->get()->toArray(),
                    ], 400);
                }
                
                abort(400, 'TENANT_NOT_FOUND');
            }
            
            // Verify the authenticated user has access to this tenant
            $user = auth()->user();
            $userHasAccess = $user ? $tenant->users->contains($user->id) : false;
            
            if ($user && !$userHasAccess) {
                $warningContext = [
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                    'tenant_code' => $tenant->code,
                    'user_tenants' => $user->tenants->pluck('id')->toArray(),
                ];
                
                \Log::warning('RouteServiceProvider - User does not have access to tenant', $warningContext);
                
                if (request()?->wantsJson() || request()?->is('api/*')) {
                    return response()->json([
                        'error' => 'FORBIDDEN',
                        'message' => 'You do not have access to this tenant',
                    ], 403);
                }
                
                abort(403, 'FORBIDDEN');
            }
            
            \Log::info('RouteServiceProvider - Successfully resolved tenant', [
                'tenant_id' => $tenant->id,
                'tenant_code' => $tenant->code,
                'user_has_access' => $userHasAccess,
                'user_id' => $user ? $user->id : null,
            ]);
            
            return $tenant;
        });

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
    
    /**
     * Exclude webhook routes from tenant middleware
     */
    protected function excludeWebhookRoutesFromTenant()
    {
        $router = $this->app['router'];
        
        // Get all routes
        $routes = $router->getRoutes()->getRoutes();
        
        foreach ($routes as $route) {
            // Check if this is a webhook route
            if (str_contains($route->uri, 'webhook')) {
                // Remove tenant middleware
                $route->forgetMiddleware(['tenant', 'tenant-context']);
            }
        }
    }
}
