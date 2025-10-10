<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ElevenLabsService;
use App\Services\StreamingAudioService;
use Illuminate\Support\Facades\Log;

class OptimizeVoiceSystem extends Command
{
    protected $signature = 'voice:optimize {--cleanup : Limpiar cache antiguo} {--warmup : Precalentar cache} {--stats : Mostrar estadísticas} {--all : Ejecutar todas las optimizaciones}';
    protected $description = 'Optimiza el sistema de voz completo con cache inteligente y mantenimiento';

    protected $elevenLabsService;
    protected $streamingAudioService;

    public function __construct(
        ElevenLabsService $elevenLabsService,
        StreamingAudioService $streamingAudioService
    ) {
        parent::__construct();
        $this->elevenLabsService = $elevenLabsService;
        $this->streamingAudioService = $streamingAudioService;
    }

    public function handle()
    {
        $this->info('🎵 CONALCA AI - Optimizador del Sistema de Voz v2.0');
        $this->info('================================================');
        $this->newLine();

        $shouldCleanup = $this->option('cleanup') || $this->option('all');
        $shouldWarmup = $this->option('warmup') || $this->option('all');
        $shouldShowStats = $this->option('stats') || $this->option('all');

        // 1. Mostrar estadísticas antes de optimizar
        if ($shouldShowStats) {
            $this->showSystemStats();
        }

        // 2. Limpieza de cache
        if ($shouldCleanup) {
            $this->performCacheCleanup();
        }

        // 3. Precalentamiento del cache
        if ($shouldWarmup) {
            $this->performCacheWarmup();
        }

        // 4. Mostrar estadísticas después de optimizar
        if ($shouldShowStats && ($shouldCleanup || $shouldWarmup)) {
            $this->newLine();
            $this->info('📊 Estadísticas después de la optimización:');
            $this->showSystemStats();
        }

        $this->newLine();
        $this->info('✅ Optimización del sistema de voz completada');
        
        return 0;
    }

    protected function showSystemStats()
    {
        $this->info('📊 Estadísticas del Sistema de Voz:');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        try {
            // Estadísticas de cache de ElevenLabs
            $cacheStats = $this->elevenLabsService->getCacheStats();
            if ($cacheStats) {
                $this->line("📦 Cache de ElevenLabs:");
                $this->line("   - Audios en cache: {$cacheStats['total_cached']}");
                $this->line("   - Hits hoy: {$cacheStats['cache_hits_today']}");
                $this->line("   - Tamaño total: {$cacheStats['total_size_mb']} MB");
            }

            // Estadísticas de audios estáticos
            $audioStats = $this->streamingAudioService->getAudioStorageStats();
            if ($audioStats) {
                $this->line("🔊 Audios Estáticos:");
                $this->line("   - Archivos estáticos: {$audioStats['static_audios']['count']}");
                $this->line("   - Tamaño estáticos: {$audioStats['static_audios']['size_mb']} MB");
                $this->line("   - Archivos temporales: {$audioStats['temp_audios']['count']}");
                $this->line("   - Tamaño temporales: {$audioStats['temp_audios']['size_mb']} MB");
                $this->line("   - Total: {$audioStats['total_size_mb']} MB");
            }

            // Verificar API de ElevenLabs
            $this->line("🔗 Conectividad:");
            if ($this->elevenLabsService->validateApiKey()) {
                $this->line("   - ElevenLabs API: ✅ Conectado");
            } else {
                $this->line("   - ElevenLabs API: ❌ Error de conexión");
            }

        } catch (\Exception $e) {
            $this->error("Error obteniendo estadísticas: " . $e->getMessage());
        }

        $this->newLine();
    }

    protected function performCacheCleanup()
    {
        $this->info('🧹 Limpiando cache antiguo...');

        try {
            // Limpiar cache de ElevenLabs
            $cleanedFiles = $this->elevenLabsService->cleanupCache();
            $this->line("   - Cache ElevenLabs: {$cleanedFiles} archivos eliminados");

            // Limpiar audios temporales
            $cleanedTemp = $this->streamingAudioService->cleanupTemporaryAudios(60); // 60 minutos
            $this->line("   - Audios temporales: {$cleanedTemp} archivos eliminados");

            $this->info('✅ Limpieza de cache completada');

        } catch (\Exception $e) {
            $this->error("Error durante la limpieza: " . $e->getMessage());
            Log::error('Error en voice:optimize cleanup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        $this->newLine();
    }

    protected function performCacheWarmup()
    {
        $this->info('🔥 Precalentando cache con frases comunes...');

        try {
            // Frases específicas de CONALCA para precalentar
            $commonPhrases = [
                'Hola, le hablamos de CONALCA. Tenemos una propuesta de trabajo.',
                'Buenos días, le hablamos de CONALCA. Esperamos que tenga un excelente día.',
                'Buenas tardes, le hablamos de CONALCA. Gracias por atender nuestra llamada.',
                'Buenas noches, le hablamos de CONALCA. Disculpe la hora.',
                'Perfecto en unos minutos mi compañera se pondrá en contacto con usted para confirmar detalles.',
                'Muchas gracias por su tiempo será ya en otra ocasión que tenga un buen día',
                '¿Estaría interesado en esta propuesta de transporte?',
                'Disculpe no lo escuché bien. ¿Estaría interesado en esta propuesta?',
                '¿Puede confirmar esa información por favor?',
                'Un momento por favor, estoy procesando su información.'
            ];

            $cachedCount = $this->elevenLabsService->warmUpCache($commonPhrases);
            $this->line("   - Frases cacheadas: {$cachedCount}/" . count($commonPhrases));

            $this->info('✅ Precalentamiento de cache completado');

        } catch (\Exception $e) {
            $this->error("Error durante el precalentamiento: " . $e->getMessage());
            Log::error('Error en voice:optimize warmup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        $this->newLine();
    }
}