<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ElevenLabsService;
use Illuminate\Support\Facades\Storage;

class TestAndreaVoice extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:andrea-voice {text?} {--save-file}';

    /**
     * The console command description.
     */
    protected $description = 'Probar específicamente la voz Andrea de ElevenLabs';

    protected $elevenLabsService;

    public function __construct(ElevenLabsService $elevenLabsService)
    {
        parent::__construct();
        $this->elevenLabsService = $elevenLabsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎙️ Probando la voz Andrea de ElevenLabs');
        $this->newLine();

        // Verificar configuración
        if (!$this->checkConfiguration()) {
            return 1;
        }

        // Texto a generar
        $text = $this->argument('text') ?? 'Hola, soy Andrea, tu asistente virtual de CONALCA. ¿En qué puedo ayudarte hoy?';
        
        $this->info("📝 Texto a generar: {$text}");
        $this->newLine();

        // Generar audio
        $this->info('🔊 Generando audio con voz Andrea...');
        $startTime = microtime(true);

        try {
            $andreaVoiceId = config('elevenlabs.andrea_voice.id', 'qHkrJuifPpn95wK3rm2A');
            $audioContent = $this->elevenLabsService->generateSpeech($text, $andreaVoiceId);
            $generationTime = round((microtime(true) - $startTime) * 1000);

            if (empty($audioContent)) {
                $this->error('❌ No se pudo generar audio');
                return 1;
            }

            $this->info("✅ Audio generado exitosamente");
            $this->info("⏱️ Tiempo de generación: {$generationTime}ms");
            $this->info("📦 Tamaño del archivo: " . $this->formatBytes(strlen($audioContent)));

            // Guardar archivo si se solicita
            if ($this->option('save-file')) {
                $filename = 'andrea_voice_test_' . date('Y-m-d_H-i-s') . '.mp3';
                $filePath = "voices/tests/{$filename}";
                
                Storage::disk('public')->put($filePath, $audioContent);
                $fullPath = Storage::disk('public')->path($filePath);
                
                $this->info("💾 Archivo guardado en: {$fullPath}");
            }

            // Mostrar detalles técnicos
            $this->showTechnicalDetails($andreaVoiceId, $generationTime, strlen($audioContent));

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error generando audio: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Verificar configuración
     */
    protected function checkConfiguration()
    {
        $this->info('🔍 Verificando configuración...');

        // Verificar API key
        if (empty(config('elevenlabs.api_key'))) {
            $this->error('❌ ElevenLabs API key no configurada');
            $this->comment('Configura ELEVENLABS_API_KEY en tu archivo .env');
            return false;
        }

        // Verificar voice ID de Andrea
        $andreaVoiceId = config('elevenlabs.andrea_voice.id');
        $defaultVoiceId = config('elevenlabs.default_voice_id');

        if ($andreaVoiceId !== $defaultVoiceId) {
            $this->warn('⚠️ Voice ID por defecto no coincide con Andrea');
            $this->comment("Default: {$defaultVoiceId}");
            $this->comment("Andrea: {$andreaVoiceId}");
        }

        // Verificar configuración de Andrea
        $andreaConfig = config('elevenlabs.andrea_voice');
        if ($andreaConfig['mandatory']) {
            $this->info('✅ Voz Andrea configurada como obligatoria');
        }

        if (!$andreaConfig['fallback_allowed']) {
            $this->info('✅ Fallback deshabilitado - solo voz Andrea');
        }

        $this->info('✅ Configuración verificada');
        $this->newLine();

        return true;
    }

    /**
     * Mostrar detalles técnicos
     */
    protected function showTechnicalDetails($voiceId, $generationTime, $audioSize)
    {
        $this->newLine();
        $this->info('📊 Detalles técnicos:');
        
        $this->table(
            ['Parámetro', 'Valor'],
            [
                ['Voice ID', $voiceId],
                ['Es voz Andrea', $voiceId === 'qHkrJuifPpn95wK3rm2A' ? 'Sí' : 'No'],
                ['Modelo', config('elevenlabs.default_model')],
                ['Formato de salida', config('elevenlabs.output_format')],
                ['Configuración de voz', json_encode(config('elevenlabs.voice_settings.spanish'))],
                ['Tiempo de generación', $generationTime . 'ms'],
                ['Tamaño de audio', $this->formatBytes($audioSize)],
                ['Optimización telefónica', config('elevenlabs.phone_optimization.enhance_clarity') ? 'Habilitada' : 'Deshabilitada'],
            ]
        );

        $this->newLine();
        $this->comment('💡 Para guardar el archivo de audio, usa: --save-file');
        $this->comment('💡 Para probar otro texto, úsalo como argumento: "texto personalizado"');
    }

    /**
     * Formatear bytes
     */
    protected function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}