<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class StripeWebhookMiddleware
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
        // Skip IP validation in testing environment
        if (app()->environment('testing')) {
            return $next($request);
        }

        $ip = $request->ip();
        
        // Check if IP is in the whitelist
        $isValidIp = $this->isValidIp($ip);
        
        if (!$isValidIp) {
            $this->logSecurityEvent('unauthorized_ip', [
                'ip' => $ip,
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
            ]);
            
            return response()->json(['error' => 'Unauthorized IP address'], 403);
        }

        // Check rate limiting if enabled
        $rateLimiting = config('stripe.rate_limiting', []);
        if ($rateLimiting['enabled'] ?? false) {
            $key = 'stripe_webhook:' . $ip;
            $maxAttempts = $rateLimiting['max_attempts'] ?? 100;
            $decayMinutes = $rateLimiting['decay_minutes'] ?? 1;

            if (Cache::has($key) && Cache::get($key) >= $maxAttempts) {
                $this->logSecurityEvent('rate_limit_exceeded', [
                    'ip' => $ip,
                    'attempts' => Cache::get($key),
                ]);
                
                return response()->json(['error' => 'Too Many Attempts'], 429);
            }

            Cache::add($key, 0, now()->addMinutes($decayMinutes));
            Cache::increment($key);
        }

        return $next($request);
    }

    /**
     * Log security-related events
     *
     * @param string $event
     * @param array $data
     * @return void
     */
    protected function logSecurityEvent(string $event, array $data = []): void
    {
        $logging = config('stripe.logging', []);
        
        if ($logging['enabled'] ?? true) {
            $channel = $logging['channel'] ?? 'stack';
            
            Log::channel($channel)->warning("Stripe Webhook Security: {$event}", [
                'event' => $event,
                'timestamp' => now()->toIso8601String(),
                'data' => $data,
            ]);
        }
    }

    /**
     * Check if the given IP is in the whitelist
     *
     * @param string $ip
     * @return bool
     */
    protected function isValidIp(string $ip): bool
    {
        $stripeIps = config('stripe.webhook_ips', []);
        
        foreach ($stripeIps as $stripeIp) {
            if ($this->ipInRange($ip, $stripeIp)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if an IP is within a CIDR range
     *
     * @param string $ip
     * @param string $range
     * @return bool
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            $range .= '/32';
        }
        
        list($range, $netmask) = explode('/', $range, 2);
        
        $ipDecimal = ip2long($ip);
        $rangeDecimal = ip2long($range);
        
        if ($ipDecimal === false || $rangeDecimal === false) {
            return false;
        }
        
        $wildcardDecimal = pow(2, (32 - $netmask)) - 1;
        $netmaskDecimal = ~ $wildcardDecimal;
        
        return (($ipDecimal & $netmaskDecimal) == ($rangeDecimal & $netmaskDecimal));
    }
}
