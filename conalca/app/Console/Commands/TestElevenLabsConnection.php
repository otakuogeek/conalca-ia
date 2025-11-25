<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestElevenLabsConnection extends Command
{
    protected $signature = 'test:elevenlabs-connection';
    protected $description = 'Prueba la conexión directa a ElevenLabs API';

    public function handle()
    {
        $this->info('🔑 Probando conexión a ElevenLabs...');
        
        $apiKey = env('ELEVENLABS_API_KEY');
        $this->info("📋 API Key: " . substr($apiKey, 0, 10) . "...");
        
        try {
            // Test básico - obtener voces
            $response = Http::withHeaders([
                'xi-api-key' => $apiKey,
                'Content-Type' => 'application/json'
            ])->get('https://api.elevenlabs.io/v1/voices');
            
            if ($response->successful()) {
                $this->info('✅ Conexión exitosa a ElevenLabs');
                $voices = $response->json();
                $this->info('📢 Voces disponibles: ' . count($voices['voices']));
                
                // Buscar la voz Andrea
                $andreaFound = false;
                foreach ($voices['voices'] as $voice) {
                    if ($voice['voice_id'] === 'qHkrJuifPpn95wK3rm2A') {
                        $andreaFound = true;
                        $this->info("🎤 Voz Andrea encontrada: " . $voice['name']);
                        break;
                    }
                }
                
                if (!$andreaFound) {
                    $this->warn('⚠️ Voz Andrea no encontrada en la cuenta');
                }
                
            } else {
                $this->error('❌ Error en conexión: ' . $response->status());
                $this->error('📄 Respuesta: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error de conexión: ' . $e->getMessage());
        }
        
        return 0;
    }
}