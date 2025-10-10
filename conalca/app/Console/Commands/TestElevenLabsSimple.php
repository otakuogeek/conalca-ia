<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ElevenLabsService;

class TestElevenLabsSimple extends Command
{
    protected $signature = 'elevenlabs:test-simple';
    protected $description = 'Prueba simple de ElevenLabs para debug';

    public function handle()
    {
        $this->info('🧪 Prueba simple de ElevenLabs...');
        
        try {
            $elevenLabs = new ElevenLabsService();
            
            // Probar solo obtener voces
            $this->line('1. Probando getVoices()...');
            $voices = $elevenLabs->getVoices();
            $this->info('   ✅ Voces obtenidas: ' . count($voices['voices'] ?? []));
            
            // Probar TTS con texto muy simple
            $this->line('2. Probando textToSpeech()...');
            $result = $elevenLabs->textToSpeech(
                'Hola', 
                env('ELEVENLABS_DEFAULT_VOICE_ID', 'JBFqnCBsd6RMkjVDRZzb')
            );
            
            $this->info('   ✅ Audio generado: ' . strlen($result['audio_content']) . ' bytes');
            
            $this->info('🎉 ¡ElevenLabs funcionando correctamente!');
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->line('Línea: ' . $e->getLine());
            $this->line('Archivo: ' . $e->getFile());
            return 1;
        }
    }
}
