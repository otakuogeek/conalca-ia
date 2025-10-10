<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DatabaseConnectionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Test database connection
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            Log::error('Database connection error: ' . $e->getMessage());

            // If it's a login request, redirect with error
            if ($request->is('login') || $request->is('auth/*')) {
                return redirect()->route('auth.login')->with('database_error', true);
            }

            // For other requests, return a proper error response
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Database connection error',
                    'message' => 'Unable to connect to database. Please try again later.'
                ], 503);
            }

            return response()->view('errors.database', [], 503);
        }

        return $next($request);
    }
}
