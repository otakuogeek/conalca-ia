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
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Content Security Policy configuration
        $csp = [
            "default-src 'self' data: 'unsafe-eval' 'unsafe-inline'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' *.googleapis.com *.gstatic.com *.jsdelivr.net *.cloudflare.com",
            "img-src 'self' data: * blob:",
            "style-src 'self' 'unsafe-inline' *.googleapis.com *.gstatic.com *.jsdelivr.net *.cloudflare.com",
            "font-src 'self' data: *.googleapis.com *.gstatic.com *.jsdelivr.net *.cloudflare.com",
            "connect-src 'self' *.elevenlabs.ai *.openai.com *.jsdelivr.net *.cloudflare.com"
        ];

        $response->headers->set('Content-Security-Policy', implode('; ', $csp));
        
        // Additional security headers
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');

        return $response;
    }
}
