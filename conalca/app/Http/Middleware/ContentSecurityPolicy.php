<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    // public function handle(Request $request, Closure $next): Response
    // {
    //     $response = $next($request);

    //     // Content Security Policy configuration
    //     $csp = [
    //         "default-src 'self' data: 'unsafe-eval' 'unsafe-inline'",
    //         "script-src 'self' 'unsafe-inline' 'unsafe-eval' *.googleapis.com *.gstatic.com *.jsdelivr.net *.cloudflare.com",
    //         "img-src 'self' data: * blob:",
    //         "style-src 'self' 'unsafe-inline' *.googleapis.com *.gstatic.com *.jsdelivr.net *.cloudflare.com",
    //         "font-src 'self' data: *.googleapis.com *.gstatic.com *.jsdelivr.net *.cloudflare.com",
    //         "connect-src 'self' *.elevenlabs.ai *.openai.com *.jsdelivr.net *.cloudflare.com"
    //     ];

    //     $response->headers->set('Content-Security-Policy', implode('; ', $csp));
        
    //     // Additional security headers
    //     $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
    //     $response->headers->set('X-XSS-Protection', '1; mode=block');
    //     $response->headers->set('X-Content-Type-Options', 'nosniff');
    //     $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');

    //     return $response;
    // }
       public function handle(Request $request, Closure $next): Response
    {
        // During local development we skip CSP so Vite can inject @vite/client freely.
        if (app()->environment('local')) {
            return $next($request);
        }

        // Check if Vite dev server is running (hot file exists)
        $viteDevRunning = file_exists(public_path('hot'));

        $response = $next($request);

        $scriptSrc = [
            "'self'",
            "'unsafe-inline'",
            "'unsafe-eval'",
            "*.googleapis.com",
            "*.gstatic.com",
            "*.jsdelivr.net",
            "*.cloudflare.com",
        ];

        $styleSrc = [
            "'self'",
            "'unsafe-inline'",
            "*.googleapis.com",
            "*.gstatic.com",
            "*.jsdelivr.net",
            "*.cloudflare.com",
        ];

        $connectSrc = [
            "'self'",
            "*.elevenlabs.ai",
            "*.openai.com",
            "api.openai.com",
            "*.jsdelivr.net",
            "*.cloudflare.com",
        ];

        // If Vite dev server is running, add localhost:5173 and network IP
        if ($viteDevRunning) {
            $viteUrls = [
                "http://localhost:5173",
                "http://127.0.0.1:5173",
                "http://0.0.0.0:5173",
                "ws://localhost:5173",
                "ws://127.0.0.1:5173",
                "ws://0.0.0.0:5173",
                "http://172.31.18.72:5173",
                "ws://172.31.18.72:5173",
                "http://13.56.4.123:5173",
                "ws://13.56.4.123:5173",
            ];
            $scriptSrc = array_merge($scriptSrc, $viteUrls);
            $styleSrc = array_merge($styleSrc, $viteUrls);
            $connectSrc = array_merge($connectSrc, $viteUrls);
        }

        $imgSrc = [
            "'self'",
            "data:",
            "blob:",
            "https://ui-avatars.com",
        ];

        $fontSrc = [
            "'self'",
            "data:",
            "*.googleapis.com",
            "*.gstatic.com",
            "*.jsdelivr.net",
            "*.cloudflare.com",
        ];

        $csp = [
            "default-src 'self' data: blob:",
            "script-src "  . implode(' ', $scriptSrc),
            "style-src "   . implode(' ', $styleSrc),
            "connect-src " . implode(' ', $connectSrc),
            "img-src "     . implode(' ', $imgSrc),
            "font-src "    . implode(' ', $fontSrc),
        ];

        $response->headers->set('Content-Security-Policy', implode('; ', $csp));
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');

        return $response;
    }
}
