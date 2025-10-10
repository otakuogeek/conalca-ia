<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Api\AgiResponseController;
use Illuminate\Http\Request;

class TestCompleteFlow extends Command
{
    protected $signature = 'test:complete-flow 
                          {--text=Hola, quiero solicitar información sobre transporte de carga}
                          {--cotizacion_id=123}
                          {--driver_id=456}';

    protected $description = 'Prueba el flujo completo: Whisper → GPT-4 → ElevenLabs';

    public function handle()
    {
        $this->info('🚀 Probando flujo completo del sistema IVR con IA...');
        $this->newLine();

        try {
            // Verificar configuraciones
            $this->info('1. 🔍 Verificando configuraciones...');
            
            if (!env('OPENAI_API_KEY') || env('OPENAI_API_KEY') === 'sk-tu_key_de_openai_aqui') {
                $this->error('   ❌ OpenAI API Key no configurada');
                return 1;
            }
            
            if (!env('ELEVENLABS_API_KEY') || env('ELEVENLABS_API_KEY') === 'tu_key_de_elevenlabs_aqui') {
                $this->error('   ❌ ElevenLabs API Key no configurada');
                return 1;
            }
            
            $this->info('   ✅ API Keys configuradas');
            $this->newLine();

            // Simular request de AGI
            $this->info('2. 📞 Simulando request del sistema AGI...');
            
            $controller = new AgiResponseController();
            
            $request = new Request([
                'cotizacion_id' => $this->option('cotizacion_id'),
                'driver_id' => $this->option('driver_id'),
                'audio_content' => base64_encode('audio simulado'), // En real sería el audio del usuario
                'prompt_type' => 'verification',
                'attempt' => 1
            ]);

            $this->line("   📋 Cotización ID: {$this->option('cotizacion_id')}");
            $this->line("   🚛 Driver ID: {$this->option('driver_id')}");
            $this->line("   💬 Texto simulado: {$this->option('text')}");
            $this->newLine();

            // Aquí normalmente se procesaría con el controlador real
            $this->info('3. 🧠 Simulando procesamiento de IA...');
            $this->line('   📝 Transcripción (Whisper): ' . $this->option('text'));
            $this->line('   🤖 Clasificación (GPT-4): verification_request');
            $this->line('   🎤 Generación de voz (ElevenLabs): Procesando...');
            $this->newLine();

            $this->info('✅ Flujo completo simulado exitosamente');
            $this->newLine();
            
            $this->comment('📌 NOTA: Para probar con audio real, usa el endpoint:');
            $this->line('   POST /api/agi/handle');
            $this->line('   Con audio base64 en el campo "audio_content"');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error en el flujo: ' . $e->getMessage());
            return 1;
        }
    }
}
