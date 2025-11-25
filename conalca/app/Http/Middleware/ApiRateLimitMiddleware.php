<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $limiter = 'api'): Response
    {
        $key = $this->resolveRequestSignature($request);
        
        // Define diferentes límites según el endpoint
        $limits = $this->getApiLimits($request, $limiter);
        
        foreach ($limits as $limit) {
            if (RateLimiter::tooManyAttempts($key . ':' . $limit->key, $limit->maxAttempts)) {
                return response()->json([
                    'error' => 'Too many requests',
                    'retry_after' => RateLimiter::availableIn($key . ':' . $limit->key)
                ], 429);
            }
            
            RateLimiter::hit($key . ':' . $limit->key, $limit->decayMinutes * 60);
        }

        return $next($request);
    }

    /**
     * Resolve request signature for rate limiting
     */
    protected function resolveRequestSignature(Request $request): string
    {
        if ($user = $request->user()) {
            return 'user:' . $user->id;
        }

        return 'ip:' . $request->ip();
    }

    /**
     * Get API rate limits based on endpoint
     */
    protected function getApiLimits(Request $request, string $limiter): array
    {
        switch ($limiter) {
            case 'twilio':
                return [Limit::perMinute(30)->by('twilio')];
            
            case 'elevenlabs':
                return [
                    Limit::perMinute(10)->by('elevenlabs'),
                    Limit::perHour(100)->by('elevenlabs:hourly')
                ];
            
            case 'calls':
                return [Limit::perMinute(5)->by('calls')];
                
            default:
                return [Limit::perMinute(60)->by('api')];
        }
    }
}