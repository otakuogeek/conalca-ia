<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DebugApiKeys extends Command
{
    protected $signature = 'debug:api-keys';
    protected $description = 'Debug de las API keys configuradas';

    public function handle()
    {
        $this->info('🔍 Debug de API Keys...');
        $this->newLine();

        // OpenAI
        $openaiKey = env('OPENAI_API_KEY');
        $this->line('OpenAI API Key:');
        $this->line('  Configurada: ' . ($openaiKey ? '✅ Sí' : '❌ No'));
        $this->line('  Longitud: ' . strlen($openaiKey ?? ''));
        $this->line('  Empieza con: ' . substr($openaiKey ?? '', 0, 10) . '...');
        $this->newLine();

        // ElevenLabs
        $elevenLabsKey = env('ELEVENLABS_API_KEY');
        $this->line('ElevenLabs API Key:');
        $this->line('  Configurada: ' . ($elevenLabsKey ? '✅ Sí' : '❌ No'));
        $this->line('  Longitud: ' . strlen($elevenLabsKey ?? ''));
        $this->line('  Empieza con: ' . substr($elevenLabsKey ?? '', 0, 10) . '...');
        $this->newLine();

        // Voice ID
        $voiceId = env('ELEVENLABS_DEFAULT_VOICE_ID');
        $this->line('ElevenLabs Voice ID:');
        $this->line('  Configurada: ' . ($voiceId ? '✅ Sí' : '❌ No'));
        $this->line('  Valor: ' . ($voiceId ?? 'No configurada'));
        $this->newLine();

        $this->info('🔧 Si alguna API key parece incorrecta, verifica el archivo .env');
    }
}
