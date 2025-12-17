<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Webhooks de Twilio que no pueden incluir tokens CSRF
        'api/twilio/webhook/*',
        // AGI endpoints que son llamados desde Python
        'api/agi-*',
        // Lambda endpoints legacy
        'api/lambda/*',
        // Temporalmente excluir login hasta resolver problema de sesión
        'login',
        // Login simplificado para debugging
        'simple-login',
        // Rutas del agente conversacional (webhooks de Twilio)
        'api/agent/*',
        // Rutas del agente de voz
        'api/voice/*',
        // Sincronización de vehículos (proceso largo)
        'vehiculos/sincronizar',
        // API de Arcangel (protegidas por Sanctum)
        'api/arcangel/*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        // Solo para debugging en entorno local/debug
        if (config('app.debug')) {
            Log::info('CSRF Debug', [
                'url' => $request->url(),
                'method' => $request->method(),
                'session_id' => $request->session()->getId(),
                'csrf_token_input' => $request->input('_token'),
                'csrf_token_session' => $request->session()->token(),
                'csrf_token_header' => $request->header('X-CSRF-TOKEN'),
                'cookies' => $request->cookies->all(),
                'session_started' => $request->session()->isStarted(),
            ]);
        }

        return parent::handle($request, $next);
    }

    /**
     * Determine if the session and input CSRF tokens match.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function tokensMatch($request)
    {
        $token = $this->getTokenFromRequest($request);
        
        if (config('app.debug')) {
            Log::info('CSRF Token Match Check', [
                'session_token' => $request->session()->token(),
                'request_token' => $token,
                'match' => hash_equals($request->session()->token(), (string) $token)
            ]);
        }

        return hash_equals($request->session()->token(), (string) $token);
    }
}
