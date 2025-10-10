<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DiagnoseTwilioRoutes extends Command
{
    protected $signature = 'twilio:diagnose';
    protected $description = 'Diagnosticar todas las rutas de Twilio para verificar que estén funcionando';

    public function handle()
    {
        $this->info('🔍 CONALCA - Diagnóstico de Rutas Twilio');
        $this->info('==========================================');

        $baseUrl = config('app.url');
        
        $routes = [
            'Voice Welcome (GET)' => '/api/voice/welcome',
            'Voice Welcome (POST)' => '/api/voice/welcome',
            'Voice Agent Start (GET)' => '/api/voice/agent/start',
            'Voice Agent Start (POST)' => '/api/voice/agent/start',
            'Voice Services Menu (GET)' => '/api/voice/services/menu',
            'Voice Services Menu (POST)' => '/api/voice/services/menu',
            'TwiML Outbound (GET)' => '/api/twilio/twiml/outbound?message=test&call_id=123',
            'TwiML Outbound (POST)' => '/api/twilio/twiml/outbound?message=test&call_id=123',
            'Webhook Status (POST)' => '/api/twilio/webhook/status',
            'Webhook Voice (POST)' => '/api/twilio/webhook/voice',
            'Webhook Recording (POST)' => '/api/twilio/webhook/recording',
            'Webhook Transcribe (POST)' => '/api/twilio/webhook/transcribe',
        ];

        foreach ($routes as $name => $route) {
            $this->line('');
            $this->info("🧪 Probando: $name");
            $this->line("🔗 URL: $baseUrl$route");
            
            try {
                $method = str_contains($name, '(POST)') ? 'POST' : 'GET';
                
                $response = Http::timeout(10)->$method($baseUrl . $route);
                
                $statusCode = $response->status();
                $statusIcon = $this->getStatusIcon($statusCode);
                
                $this->line("$statusIcon Status: $statusCode");
                
                if ($statusCode >= 200 && $statusCode < 400) {
                    $contentType = $response->header('Content-Type');
                    if (str_contains($contentType, 'xml')) {
                        $this->line("✅ Content-Type: $contentType (TwiML válido)");
                        
                        // Verificar que sea XML válido
                        $content = $response->body();
                        if (str_contains($content, '<Response>')) {
                            $this->line("✅ TwiML válido detectado");
                        } else {
                            $this->line("⚠️  Respuesta no parece ser TwiML válido");
                        }
                    } else {
                        $this->line("ℹ️  Content-Type: $contentType");
                    }
                } else {
                    $this->error("❌ Error: " . $response->body());
                }
                
            } catch (\Exception $e) {
                $this->error("❌ Excepción: " . $e->getMessage());
            }
        }

        $this->line('');
        $this->info('📋 Resumen de configuración necesaria en Twilio:');
        $this->line('');
        $this->line('Voice Configuration:');
        $this->line("  📞 A call comes in: Webhook");
        $this->line("  🔗 URL: $baseUrl/api/voice/welcome");
        $this->line("  📤 HTTP: POST");
        $this->line('');
        $this->line('Call Status Changes:');
        $this->line("  🔗 URL: $baseUrl/api/twilio/webhook/status");
        $this->line("  📤 HTTP: POST");
        $this->line('');
        $this->info('✅ Diagnóstico completado');
    }

    private function getStatusIcon($statusCode)
    {
        if ($statusCode >= 200 && $statusCode < 300) {
            return '✅';
        } elseif ($statusCode >= 300 && $statusCode < 400) {
            return '🔄';
        } elseif ($statusCode >= 400 && $statusCode < 500) {
            return '❌';
        } else {
            return '💥';
        }
    }
}