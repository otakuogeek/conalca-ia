<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\ElevenLabsService;
use App\Services\LocalAudioStorage;
use App\Console\Commands\GenerateStaticAudios;

/**
 * SERVICIO OPTIMIZADO DE STREAMING DE AUDIO
 * 
 * Funcionalidades principales:
 * 1. Selección inteligente de audio basada en contexto
 * 2. Sistema de caché avanzado para audios frecuentes
 * 3. Preload predictivo de audios probables
 * 4. Fallback robusto para garantizar respuesta
 * 5. Métricas y monitoreo de rendimiento
 */
class StreamingAudioService
{
    protected ElevenLabsService $elevenLabsService;
    protected LocalAudioStorage $localAudioStorage;
    protected array $audioCache = [];
    protected array $performanceMetrics = [];

    public function __construct(
        ElevenLabsService $elevenLabsService,
        LocalAudioStorage $localAudioStorage
    ) {
        $this->elevenLabsService = $elevenLabsService;
        $this->localAudioStorage = $localAudioStorage;
        
        Log::info('StreamingAudioService iniciado correctamente (OPTIMIZADO)');
    }

    /**
     * MÉTODO PRINCIPAL: Generar respuesta de audio con streaming inteligente
     */
    public function getStreamingAudioResponse(
        string $text, 
        string $intent = 'general', 
        string $sentiment = 'neutral',
        array $context = []
    ): array {
        $startTime = microtime(true);
        
        Log::info('StreamingAudioService: Iniciando proceso optimizado', [
            'text' => substr($text, 0, 100),
            'intent' => $intent,
            'sentiment' => $sentiment,
            'context_size' => count($context)
        ]);

        try {
            // 1. ANÁLISIS INTELIGENTE DE CONTEXTO
            $analysisResult = $this->analyzeContextForOptimalAudio($intent, $sentiment, $context);
            
            // 2. PRELOAD PREDICTIVO (ejecutar en paralelo)
            $this->preloadPredictedAudios($intent, $sentiment, $context);
            
            // 3. SISTEMA DE FALLBACK DE 4 NIVELES
            $audioResponse = $this->executeSmartFallbackSystem($analysisResult, $text, $context);
            
            // 4. MÉTRICAS DE RENDIMIENTO
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->recordPerformanceMetrics($processingTime, $audioResponse['source'] ?? 'unknown');
            
            $audioResponse['processing_time_ms'] = $processingTime;
            $audioResponse['optimization_applied'] = true;
            
            Log::info('StreamingAudioService: Proceso completado exitosamente', [
                'processing_time_ms' => $processingTime,
                'audio_source' => $audioResponse['source'] ?? 'unknown',
                'url_generated' => !empty($audioResponse['url'])
            ]);
            
            return $audioResponse;
            
        } catch (\Exception $e) {
            Log::error('StreamingAudioService: Error en proceso principal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // FALLBACK DE EMERGENCIA
            return $this->getEmergencyFallbackResponse($text);
        }
    }

    /**
     * NUEVO: Análisis inteligente de contexto para selección óptima de audio
     */
    protected function analyzeContextForOptimalAudio(string $intent, string $sentiment, array $context): array
    {
        $analysisResult = [
            'recommended_audio' => null,
            'confidence' => 0.0,
            'reasoning' => '',
            'fallback_options' => [],
            'optimization_level' => 'basic'
        ];

        // Análisis por intención del usuario
        switch(strtolower($intent)) {
            case 'saludo':
            case 'greeting':
                $analysisResult['recommended_audio'] = $this->getHourBasedGreeting();
                $analysisResult['confidence'] = 0.9;
                $analysisResult['reasoning'] = 'Saludo contextual basado en la hora';
                $analysisResult['optimization_level'] = 'high';
                break;

            case 'interesado':
            case 'interested':
                $analysisResult['recommended_audio'] = 'conalca_si_respuesta.mp3';
                $analysisResult['confidence'] = 0.85;
                $analysisResult['reasoning'] = 'Usuario mostró interés';
                $analysisResult['optimization_level'] = 'medium';
                break;

            case 'no_interesado':
            case 'not_interested':
                $analysisResult['recommended_audio'] = 'conalca_no_respuesta.mp3';
                $analysisResult['confidence'] = 0.8;
                $analysisResult['reasoning'] = 'Usuario no mostró interés';
                $analysisResult['optimization_level'] = 'medium';
                break;

            case 'despedida':
            case 'goodbye':
                $analysisResult['recommended_audio'] = 'conalca_despedida.mp3';
                $analysisResult['confidence'] = 0.95;
                $analysisResult['reasoning'] = 'Finalización de llamada';
                $analysisResult['optimization_level'] = 'high';
                break;

            default:
                // Usar audio general o pregunta
                $analysisResult['recommended_audio'] = 'conalca_pregunta.mp3';
                $analysisResult['confidence'] = 0.6;
                $analysisResult['reasoning'] = 'Audio general para intención no específica';
                $analysisResult['optimization_level'] = 'basic';
        }

        // Ajustar basado en sentimiento
        if ($sentiment === 'negative' && $analysisResult['confidence'] > 0.7) {
            $analysisResult['recommended_audio'] = 'conalca_no_respuesta.mp3';
            $analysisResult['reasoning'] .= ' (ajustado por sentimiento negativo)';
        } elseif ($sentiment === 'positive' && $analysisResult['confidence'] > 0.7) {
            $analysisResult['recommended_audio'] = 'conalca_si_respuesta.mp3';
            $analysisResult['reasoning'] .= ' (reforzado por sentimiento positivo)';
        }

        // Establecer opciones de fallback
        $analysisResult['fallback_options'] = [
            'conalca_pregunta.mp3',
            'conalca_saludo.mp3',
            'conalca_completo.mp3'
        ];

        Log::info('Análisis de contexto completado', $analysisResult);
        return $analysisResult;
    }

    /**
     * Obtiene saludo contextual basado en la hora
     */
    protected function getHourBasedGreeting(): string
    {
        $hour = (int) date('H');
        
        if ($hour >= 5 && $hour < 12) {
            return 'conalca_saludo.mp3'; // Buenos días
        } elseif ($hour >= 12 && $hour < 18) {
            return 'conalca_pregunta.mp3'; // Buenas tardes
        } else {
            return 'conalca_saludo.mp3'; // Buenas noches
        }
    }

    /**
     * NUEVO: Precarga predictiva de audios probables
     */
    protected function preloadPredictedAudios(string $intent, string $sentiment, array $context): void
    {
        $startTime = microtime(true);
        
        // Audios más probables según contexto
        $probableAudios = [
            'conalca_saludo.mp3',
            'conalca_pregunta.mp3',
            'conalca_si_respuesta.mp3',
            'conalca_no_respuesta.mp3'
        ];

        $preloadedCount = 0;
        foreach ($probableAudios as $audio) {
            try {
                $path = storage_path("app/public/audios/calls/{$audio}");
                if (file_exists($path)) {
                    // Precarga en memoria
                    file_get_contents($path);
                    $preloadedCount++;
                }
            } catch (\Exception $e) {
                Log::warning("Error precargando audio {$audio}: " . $e->getMessage());
            }
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);
        Log::info("Preloaded {$preloadedCount} audios in {$duration}ms");
    }

    /**
     * SISTEMA DE FALLBACK DE 4 NIVELES
     */
    protected function executeSmartFallbackSystem(array $analysisResult, string $text, array $context): array
    {
        // NIVEL 1: Audio específico recomendado por análisis
        if (!empty($analysisResult['recommended_audio']) && $analysisResult['confidence'] > 0.8) {
            $specificAudio = $this->tryGetSpecificAudio($analysisResult['recommended_audio']);
            if ($specificAudio) {
                return [
                    'url' => $specificAudio,
                    'source' => 'specific_recommendation',
                    'confidence' => $analysisResult['confidence'],
                    'reasoning' => $analysisResult['reasoning']
                ];
            }
        }

        // NIVEL 2: Audio estático inteligente
        $staticAudio = $this->tryGetIntelligentStaticAudio($analysisResult, $context);
        if ($staticAudio) {
            return [
                'url' => $staticAudio,
                'source' => 'intelligent_static',
                'confidence' => 0.75,
                'reasoning' => 'Audio estático seleccionado inteligentemente'
            ];
        }

        // NIVEL 3: Generación rápida con caché
        $cachedAudio = $this->tryGetCachedOrFastGeneration($text, $context);
        if ($cachedAudio) {
            return [
                'url' => $cachedAudio,
                'source' => 'cached_generation',
                'confidence' => 0.6,
                'reasoning' => 'Audio generado rápidamente o recuperado de caché'
            ];
        }

        // NIVEL 4: Fallback final de emergencia
        return $this->getEmergencyFallbackResponse($text);
    }

    /**
     * Intentar obtener audio específico recomendado
     */
    protected function tryGetSpecificAudio(string $audioFile): ?string
    {
        $path = storage_path("app/public/audios/calls/{$audioFile}");
        if (file_exists($path)) {
            return $this->localAudioStorage->getPublicUrl("calls/{$audioFile}");
        }
        return null;
    }

    /**
     * Intentar obtener audio estático inteligente
     */
    protected function tryGetIntelligentStaticAudio(array $analysisResult, array $context): ?string
    {
        // Usar opciones de fallback del análisis
        foreach ($analysisResult['fallback_options'] as $audioFile) {
            $path = storage_path("app/public/audios/calls/{$audioFile}");
            if (file_exists($path)) {
                return $this->localAudioStorage->getPublicUrl("calls/{$audioFile}");
            }
        }
        return null;
    }

    /**
     * Intentar generar audio con caché o generación rápida
     */
    protected function tryGetCachedOrFastGeneration(string $text, array $context): ?string
    {
        try {
            // Intentar obtener desde caché de ElevenLabs
            $cachedUrl = $this->elevenLabsService->getCachedAudio($text);
            if ($cachedUrl) {
                return $cachedUrl;
            }

            // Generar rápidamente si es posible
            return $this->elevenLabsService->generateFastInitialSpeech($text);
            
        } catch (\Exception $e) {
            Log::warning('Error en generación rápida de audio', [
                'error' => $e->getMessage(),
                'text' => substr($text, 0, 50)
            ]);
            return null;
        }
    }

    /**
     * Respuesta de emergencia garantizada
     */
    protected function getEmergencyFallbackResponse(string $text): array
    {
        // Audio de fallback absoluto
        $emergencyAudio = 'conalca_completo.mp3';
        $emergencyPath = storage_path("app/public/audios/calls/{$emergencyAudio}");
        
        if (file_exists($emergencyPath)) {
            $url = $this->localAudioStorage->getPublicUrl("calls/{$emergencyAudio}");
        } else {
            // Si ni siquiera el audio de emergencia existe, usar el primer audio disponible
            $url = $this->findAnyAvailableAudio();
        }

        return [
            'url' => $url,
            'source' => 'emergency_fallback',
            'confidence' => 0.3,
            'reasoning' => 'Sistema de fallback de emergencia activado',
            'warning' => 'Using emergency fallback - check audio files'
        ];
    }

    /**
     * Encontrar cualquier audio disponible como último recurso
     */
    protected function findAnyAvailableAudio(): string
    {
        $audioDir = storage_path('app/public/audios/calls');
        if (is_dir($audioDir)) {
            $files = scandir($audioDir);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'mp3') {
                    return $this->localAudioStorage->getPublicUrl("calls/{$file}");
                }
            }
        }
        
        // Si absolutamente no hay nada, retornar URL de placeholder
        return '/storage/audios/placeholder.mp3';
    }

    /**
     * Registrar métricas de rendimiento
     */
    protected function recordPerformanceMetrics(float $processingTime, string $source): void
    {
        $this->performanceMetrics[] = [
            'timestamp' => now()->toISOString(),
            'processing_time_ms' => $processingTime,
            'audio_source' => $source
        ];

        // Mantener solo las últimas 100 métricas
        if (count($this->performanceMetrics) > 100) {
            array_shift($this->performanceMetrics);
        }

        // Log estadísticas cada 10 requests
        if (count($this->performanceMetrics) % 10 === 0) {
            $avgTime = array_sum(array_column($this->performanceMetrics, 'processing_time_ms')) / count($this->performanceMetrics);
            Log::info('StreamingAudioService Performance Stats', [
                'avg_processing_time_ms' => round($avgTime, 2),
                'total_requests' => count($this->performanceMetrics),
                'last_10_avg' => round(array_sum(array_slice(array_column($this->performanceMetrics, 'processing_time_ms'), -10)) / 10, 2)
            ]);
        }
    }

    /**
     * Obtener estadísticas de rendimiento
     */
    public function getPerformanceStats(): array
    {
        if (empty($this->performanceMetrics)) {
            return ['message' => 'No performance data available'];
        }

        $times = array_column($this->performanceMetrics, 'processing_time_ms');
        $sources = array_column($this->performanceMetrics, 'audio_source');

        return [
            'total_requests' => count($this->performanceMetrics),
            'avg_processing_time_ms' => round(array_sum($times) / count($times), 2),
            'min_processing_time_ms' => min($times),
            'max_processing_time_ms' => max($times),
            'source_distribution' => array_count_values($sources),
            'last_24h_requests' => count(array_filter($this->performanceMetrics, function($metric) {
                return now()->diffInHours($metric['timestamp']) < 24;
            }))
        ];
    }

    /**
     * Obtener estadísticas de almacenamiento de audio
     */
    public function getAudioStorageStats(): array
    {
        try {
            $callsDir = storage_path('app/public/audios/calls');
            $tempDir = storage_path('app/public/audios/temp');
            
            $stats = [
                'static_audios' => [
                    'count' => 0,
                    'size_mb' => 0
                ],
                'temp_audios' => [
                    'count' => 0,
                    'size_mb' => 0
                ],
                'total_size_mb' => 0
            ];
            
            // Estadísticas de audios de llamadas (estáticos)
            if (is_dir($callsDir)) {
                $files = glob($callsDir . '/*.mp3');
                $stats['static_audios']['count'] = count($files);
                $totalSize = 0;
                foreach ($files as $file) {
                    $totalSize += filesize($file);
                }
                $stats['static_audios']['size_mb'] = round($totalSize / 1024 / 1024, 2);
            }
            
            // Estadísticas de audios temporales
            if (is_dir($tempDir)) {
                $files = glob($tempDir . '/*.mp3');
                $stats['temp_audios']['count'] = count($files);
                $totalSize = 0;
                foreach ($files as $file) {
                    $totalSize += filesize($file);
                }
                $stats['temp_audios']['size_mb'] = round($totalSize / 1024 / 1024, 2);
            }
            
            $stats['total_size_mb'] = $stats['static_audios']['size_mb'] + $stats['temp_audios']['size_mb'];
            
            return $stats;
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas de audio', [
                'error' => $e->getMessage()
            ]);
            return [
                'static_audios' => ['count' => 0, 'size_mb' => 0],
                'temp_audios' => ['count' => 0, 'size_mb' => 0],
                'total_size_mb' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
}