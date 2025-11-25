<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class ElevenLabsWebhookAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Validar que la petición viene de ElevenLabs
        $userAgent = $request->header('User-Agent', '');
        $contentType = $request->header('Content-Type', '');
        
        // Log de la petición para debugging
        Log::info('Webhook recibido', [
            'user_agent' => $userAgent,
            'content_type' => $contentType,
            'ip' => $request->ip(),
            'method' => $request->method(),
            'path' => $request->path(),
        ]);

        // Validar Content-Type
        if (!str_contains($contentType, 'application/json')) {
            Log::warning('Webhook rechazado: Content-Type inválido', [
                'content_type' => $contentType,
                'ip' => $request->ip()
            ]);
            return response()->json(['error' => 'Invalid content type'], 400);
        }

        // Validar método HTTP
        if (!$request->isMethod('post')) {
            Log::warning('Webhook rechazado: Método inválido', [
                'method' => $request->method(),
                'ip' => $request->ip()
            ]);
            return response()->json(['error' => 'Method not allowed'], 405);
        }

        // Validar webhook secret si está configurado
        $webhookSecret = config('elevenlabs.webhook.secret');
        if ($webhookSecret) {
            $signature = $request->header('X-ElevenLabs-Signature');
            $webhookSignature = $request->header('X-Webhook-Signature');
            
            // Intentar con ambos headers posibles
            $receivedSignature = $signature ?: $webhookSignature;
            
            if (!$receivedSignature) {
                Log::warning('Webhook rechazado: Sin firma', [
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all()
                ]);
                return response()->json(['error' => 'Missing signature'], 401);
            }

            // Verificar la firma
            $payload = $request->getContent();
            $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
            
            if (!hash_equals($expectedSignature, $receivedSignature)) {
                Log::warning('Webhook rechazado: Firma inválida', [
                    'ip' => $request->ip(),
                    'received' => $receivedSignature,
                    'expected' => $expectedSignature
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        // Validar que el payload no esté vacío
        if (empty($request->getContent())) {
            Log::warning('Webhook rechazado: Payload vacío', [
                'ip' => $request->ip()
            ]);
            return response()->json(['error' => 'Empty payload'], 400);
        }

        // Validar estructura básica del JSON
        $payload = $request->json();
        if (!$payload || !$payload->has('event_type')) {
            Log::warning('Webhook rechazado: Estructura JSON inválida', [
                'ip' => $request->ip(),
                'payload' => $request->getContent()
            ]);
            return response()->json(['error' => 'Invalid JSON structure'], 400);
        }

        Log::info('Webhook autenticado exitosamente', [
            'event_type' => $payload->get('event_type'),
            'conversation_id' => $payload->get('conversation_id'),
            'ip' => $request->ip()
        ]);

        return $next($request);
    }
}
