<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SilogtranService
{

    private string $base;

    public function __construct()
    {
        $this->base = rtrim(config('services.silog.base'), '/');   // ej: https://conalca.colombiasoftware.net
    }

    /* -----------------------------------------------------------------
     |  TOKEN
     |-----------------------------------------------------------------*/
    public function token(): string
    {
        return Cache::remember('silog_token', 100 /* minutos */, function () {

            $response = Http::asForm()->post(
                $this->base . '/index.php?api=servicio.Seguridad.login',
                [
                    'usuario_login'    => config('services.silog.user'),
                    'usuario_password' => config('services.silog.pass'),
                ]
            );

            throw_if(!$response->successful() || !$response->json('success'),
                     \Exception::class,
                     'Login Silogtran falló: ' . $response->body());

            return $response->json('data.token');   // => token plano
        });
    }

    /* -----------------------------------------------------------------
     |  LLAMADA GENÉRICA
     |-----------------------------------------------------------------*/
    public function call(string $api, array $payload = [], string $method = 'post')
    {
        $url = $this->base . '/index.php?api=' . $api;

        return Http::withHeaders([
                    'Authorization' => $this->token(),          // SIN “Bearer ”
                    'Content-Type'  => 'application/json',
                ])->{$method}($url, $payload);
    }

    /* -----------------------------------------------------------------
     |  CONSULTAR CLIENTE
     |-----------------------------------------------------------------*/

    /**
     * Consulta clientes en Silogtran mediante la API consultarCliente.
     *
     * @param  string|null $documento  NIT / documento del cliente
     * @param  string|null $calificacion  A, B, C o D
     * @return array  Respuesta completa de la API
     */
    public function consultarCliente(?string $documento = null, ?string $calificacion = null): array
    {
        $payload = [];

        if ($documento) {
            $payload['documento_cliente'] = $documento;
        }
        if ($calificacion) {
            $payload['calificacion'] = $calificacion;
        }

        $response = $this->call(
            'servicio.ConsultasInforme.consultarCliente',
            $payload
        );

        Log::debug('[ConsultarCliente] HTTP Silogtran', [
            'status'  => $response->status(),
            'body'    => $response->body(),
            'payload' => $payload,
        ]);

        return $response->json() ?? [];
    }

    /* -----------------------------------------------------------------
     |  SOLICITUD DE TRANSPORTE
     |-----------------------------------------------------------------*/

    public function crearSolicitudTransporte(array $payload): array
    {
        $response = $this->call('servicio.Solicitudtransporte.crearST', $payload);

        // Guarda la respuesta cruda en el log para depurar
        Log::debug('[ST] HTTP Silogtran',
                ['status' => $response->status(), 'body' => $response->body()]);

        // Silogtran siempre devuelve JSON aun con 4xx; si no lo hace
        // (error de proxy, timeout, etc.) lanza excepción.
        if (! $response->header('Content-Type') ||
            ! str_contains($response->header('Content-Type'), 'json')) {
            throw new \Exception(
                'Silogtran devolvió contenido no-JSON. HTTP '.$response->status());
        }

        // Cuando sea 400/422 simplemente devolvemos el array para que
        // el controlador lo procese y se vean las validaciones.
        return $response->json();
    }
}
