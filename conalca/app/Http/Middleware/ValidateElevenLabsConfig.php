<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ValidateElevenLabsConfig
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\JsonResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!env('ELEVENLABS_API_KEY')) {
            return response()->json([
                'success' => false,
                'error' => 'ElevenLabs API key not configured. Please set ELEVENLABS_API_KEY in your .env file.',
                'documentation' => 'Check ELEVENLABS_IMPLEMENTATION.md for setup instructions'
            ], 500);
        }

        return $next($request);
    }
}
