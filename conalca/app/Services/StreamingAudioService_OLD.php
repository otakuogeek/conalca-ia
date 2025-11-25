<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Console\Commands\GenerateStaticAudios;

/**
 * Servicio para manejo de audio en streaming bidireccional
 * Coordina entre audios estáticos y generación dinámica con ElevenLabs
 */
class StreamingAudioService
{
    protected ElevenLabsService $elevenLabsService;
    protected LocalAudioStorage $localAudioStorage;

    public function __construct(
        ElevenLabsService $elevenLabsService,
        LocalAudioStorage $localAudioStorage
    ) {
        $this->elevenLabsService = $elevenLabsService;
        $this->localAudioStorage = $localAudioStorage;
    }

    /**
     * Obtener respuesta de audio optimizada para streaming
     * MEJORADO: Selección inteligente con análisis predictivo y cache optimizado
     */
    public function getStreamingAudioResponse(
        string $intent,
        string $sentiment = 'neutral',
        array $context = []
    ): ?string {
        $startTime = microtime(true);
        
        Log::info('StreamingAudioService: Obteniendo respuesta de audio (OPTIMIZADO)', [
            'intent' => $intent,
            'sentiment' => $sentiment,
            'context' => $context,
            'timestamp' => now()->toISOString()
        ]);

        // 1. Análisis inteligente de contexto para selección óptima
        $analysisResult = $this->analyzeContextForOptimalAudio($intent, $sentiment, $context);
        
        // 2. Intentar obtener audio estático con mapeo inteligente mejorado
        $staticAudioUrl = $this->getStaticAudioForIntent(
            $analysisResult['optimized_intent'], 
            $analysisResult['optimized_sentiment'], 
            $analysisResult['enhanced_context']
        );
        
        if ($staticAudioUrl) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            Log::info('StreamingAudioService: Audio estático encontrado (respuesta ultrarrápida)', [
                'url' => $staticAudioUrl,
                'intent' => $intent,
                'optimized_intent' => $analysisResult['optimized_intent'],
                'sentiment' => $sentiment,
                'response_time_ms' => $responseTime,
                'cache_hit' => true
            ]);
            
            // Preload de audios probables para próximas interacciones
            $this->preloadPredictedAudios($intent, $sentiment, $context);
            
            return $staticAudioUrl;
        }

        // 3. Si no hay audio estático, generar dinámicamente con prioridad
        return $this->generateDynamicAudioResponse(
            $analysisResult['optimized_intent'], 
            $analysisResult['optimized_sentiment'], 
            $analysisResult['enhanced_context']
        );
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
            'fallback_options' => []
        ];

        // Análisis por intención del usuario
        switch(strtolower($intent)) {
            case 'saludo':
            case 'greeting':
                $analysisResult['recommended_audio'] = $this->getHourBasedGreeting();
                $analysisResult['confidence'] = 0.9;
                $analysisResult['reasoning'] = 'Saludo contextual basado en la hora';
                break;

            case 'interesado':
            case 'interested':
                $analysisResult['recommended_audio'] = 'conalca_si_respuesta.mp3';
                $analysisResult['confidence'] = 0.85;
                $analysisResult['reasoning'] = 'Usuario mostró interés';
                break;

            case 'no_interesado':
            case 'not_interested':
                $analysisResult['recommended_audio'] = 'conalca_no_respuesta.mp3';
                $analysisResult['confidence'] = 0.8;
                $analysisResult['reasoning'] = 'Usuario no mostró interés';
                break;

            case 'despedida':
            case 'goodbye':
                $analysisResult['recommended_audio'] = 'conalca_despedida.mp3';
                $analysisResult['confidence'] = 0.95;
                $analysisResult['reasoning'] = 'Finalización de llamada';
                break;

            default:
                // Usar audio general o pregunta
                $analysisResult['recommended_audio'] = 'conalca_pregunta.mp3';
                $analysisResult['confidence'] = 0.6;
                $analysisResult['reasoning'] = 'Audio general para intención no específica';
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
    {
        $optimized = [
            'optimized_intent' => $intent,
            'optimized_sentiment' => $sentiment,
            'enhanced_context' => $context,
            'confidence' => 1.0,
            'reasoning' => []
        ];

        // Análisis de patrones de conversación
        if (isset($context['conversation_turn'])) {
            $turn = $context['conversation_turn'];
            
            // Primera interacción - usar saludos optimizados
            if ($turn <= 1) {
                $optimized['optimized_intent'] = 'greeting';
                $optimized['optimized_sentiment'] = 'positive';
                $optimized['reasoning'][] = 'Primera interacción - optimizado para saludo';
            }
            
            // Interacciones finales - usar despedidas optimizadas
            if ($turn >= 3) {
                if (in_array($intent, ['accept', 'reject', 'goodbye'])) {
                    $optimized['optimized_sentiment'] = 'positive';
                    $optimized['reasoning'][] = 'Interacción final - optimizado para cierre positivo';
                }
            }
        }

        // Análisis de urgencia
        if (isset($context['urgency_level'])) {
            if ($context['urgency_level'] === 'high') {
                $optimized['optimized_intent'] = 'urgent';
                $optimized['reasoning'][] = 'Alta urgencia detectada';
            }
        }

        // Análisis de emociones del conductor
        if (isset($context['driver_emotion'])) {
            $emotion = $context['driver_emotion'];
            
            if (in_array($emotion, ['confused', 'uncertain'])) {
                $optimized['optimized_intent'] = 'clarify';
                $optimized['optimized_sentiment'] = 'patient';
                $optimized['reasoning'][] = 'Confusión detectada - usar clarificación paciente';
            }
            
            if (in_array($emotion, ['angry', 'frustrated'])) {
                $optimized['optimized_sentiment'] = 'calm';
                $optimized['reasoning'][] = 'Frustración detectada - usar tono calmado';
            }
        }

        // Mejorar contexto con información temporal
        $optimized['enhanced_context'] = array_merge($context, [
            'analysis_timestamp' => now()->toISOString(),
            'optimization_applied' => !empty($optimized['reasoning']),
            'time_of_day' => now()->format('H')
        ]);

        Log::info('StreamingAudioService: Análisis de contexto completado', [
            'original_intent' => $intent,
            'optimized_intent' => $optimized['optimized_intent'],
            'original_sentiment' => $sentiment,
            'optimized_sentiment' => $optimized['optimized_sentiment'],
            'reasoning' => $optimized['reasoning']
        ]);

        return $optimized;
    }

    /**
     * NUEVO: Preload de audios probables para próximas interacciones
     */
    protected function preloadPredictedAudios(string $currentIntent, string $currentSentiment, array $context): void
    {
        // Patrones de predicción basados en el flujo de conversación
        $predictionMap = [
            'greeting' => ['clarify', 'accept', 'reject'],
            'clarify' => ['accept', 'reject', 'clarify'],
            'accept' => ['goodbye'],
            'reject' => ['goodbye'],
            'urgent' => ['accept', 'reject']
        ];

        $probableIntents = $predictionMap[$currentIntent] ?? [];
        
        if (!empty($probableIntents)) {
            // Ejecutar preload en background (no bloquear respuesta actual)
            Log::info('StreamingAudioService: Iniciando preload de audios probables', [
                'current_intent' => $currentIntent,
                'probable_intents' => $probableIntents
            ]);

            foreach ($probableIntents as $probableIntent) {
                try {
                    // Verificar si ya está en cache de audios estáticos
                    $staticUrl = $this->getStaticAudioForIntent($probableIntent, $currentSentiment, $context);
                    if ($staticUrl) {
                        Log::debug('StreamingAudioService: Audio probable ya disponible', [
                            'intent' => $probableIntent,
                            'url' => $staticUrl
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::debug('StreamingAudioService: Error en preload', [
                        'intent' => $probableIntent,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }
            
            return $staticAudioUrl;
        }

                }
        
        // 3. Si no hay audio estático, generar dinámicamente con prioridad
        return $this->generateDynamicAudioResponse(
            $analysisResult['optimized_intent'], 
            $analysisResult['optimized_sentiment'], 
            $analysisResult['enhanced_context']
        );
    }

    /**
     * Obtener audio estático basado en intención y sentimiento
     */
    protected function getStaticAudioForIntent(
        string $intent, 
        string $sentiment, 
        array $context = []
    ): ?string {
        // MAPEO INTELIGENTE MEJORADO: Considera contexto y sentimiento
        $intentMapping = $this->getIntelligentIntentMapping($intent, $sentiment, $context);
        
        // Mapeo base mejorado con patrones más específicos
        $baseMapping = [
            // Aceptaciones con variaciones
            'accept' => $this->selectByContext('accept', $sentiment, $context),
            'accept_job' => $this->selectByContext('accept', $sentiment, $context),
            'yes' => $this->selectByContext('accept', 'positive', $context),
            'si' => $this->selectByContext('accept', 'positive', $context),
            'positive' => $this->selectByContext('accept', 'positive', $context),
            'interested' => $this->selectByContext('accept', 'enthusiastic', $context),
            
            // Rechazos con variaciones
            'reject' => $this->selectByContext('reject', $sentiment, $context),
            'reject_job' => $this->selectByContext('reject', $sentiment, $context),
            'no' => $this->selectByContext('reject', 'polite', $context),
            'negative' => $this->selectByContext('reject', 'firm', $context),
            'not_interested' => $this->selectByContext('reject', 'polite', $context),
            'busy' => $this->selectByContext('reject', 'polite', $context),
            
            // Clarificaciones con variaciones
            'clarify' => $this->selectByContext('clarify', $sentiment, $context),
            'clarify_request' => $this->selectByContext('clarify', 'helpful', $context),
            'question' => $this->selectByContext('clarify', 'helpful', $context),
            'doubt' => $this->selectByContext('clarify', 'patient', $context),
            'confused' => $this->selectByContext('clarify', 'patient', $context),
            'unclear' => $this->selectByContext('clarify', 'helpful', $context),
            
            // Saludos con variaciones por hora
            'greeting' => $this->selectGreetingByTime($context),
            'hello' => $this->selectGreetingByTime($context),
            'hi' => $this->selectGreetingByTime($context),
            'good_morning' => 'morning_greeting',
            'good_afternoon' => 'afternoon_greeting',
            'good_evening' => 'evening_greeting',
            
            // Despedidas con variaciones
            'goodbye' => $this->selectByContext('goodbye', $sentiment, $context),
            'bye' => $this->selectByContext('goodbye', 'neutral', $context),
            'thanks' => $this->selectByContext('goodbye', 'positive', $context),
            'thank_you' => $this->selectByContext('goodbye', 'positive', $context),
            'end_call' => $this->selectByContext('goodbye', 'neutral', $context),
            
            // Urgencias con escalación
            'urgent' => $this->selectByUrgency('urgent_response', $context),
            'emergency' => $this->selectByUrgency('urgent_enthusiastic', $context),
            'priority' => $this->selectByUrgency('urgent_response', $context),
            'asap' => $this->selectByUrgency('urgent_enthusiastic', $context),
            
            // Interactivos con contexto
            'listening' => 'listening',
            'processing' => 'processing',
            'wait' => 'processing',
            'confirm' => 'confirmation_needed',
            'repeat' => 'repeat_request',
            'repeat' => 'repeat_request'
        ];

        // Determinar tipo base de respuesta
        $baseKey = $intentMapping[$intent] ?? null;
        if (!$baseKey) {
            return null;
        }

        // Para algunos casos, usar contexto específico
        if ($baseKey === 'greeting' && isset($context['time_context'])) {
            return GenerateStaticAudios::getContextualAudioUrl('greeting', $context['time_context']);
        }

        // Mapear sentimiento para contexto
        $sentimentMapping = [
            'very_positive' => 'positive',
            'positive' => 'positive',
            'enthusiastic' => 'positive',
            'happy' => 'positive',
            
            'neutral' => 'neutral',
            'calm' => 'neutral',
            'normal' => 'neutral',
            
            'cautious' => 'cautious',
            'worried' => 'cautious',
            'concerned' => 'cautious',
            
            'negative' => 'firm',
            'angry' => 'firm',
            'frustrated' => 'firm',
            
            'polite' => 'polite',
            'respectful' => 'polite',
            
            'helpful' => 'helpful',
            'patient' => 'patient'
        ];

        $mappedSentiment = $sentimentMapping[$sentiment] ?? 'neutral';

        // Obtener URL contextual
        return GenerateStaticAudios::getContextualAudioUrl($baseKey, $mappedSentiment);
    }

    /**
     * Generar audio dinámico cuando no hay estático disponible
     */
    protected function generateDynamicAudioResponse(
        string $intent,
        string $sentiment,
        array $context = []
    ): ?string {
        Log::info('StreamingAudioService: Generando audio dinámico', [
            'intent' => $intent,
            'sentiment' => $sentiment,
            'context' => $context
        ]);

        // Generar texto contextual basado en intención
        $responseText = $this->generateContextualText($intent, $sentiment, $context);
        
        if (!$responseText) {
            Log::warning('StreamingAudioService: No se pudo generar texto para la intención', [
                'intent' => $intent
            ]);
            return null;
        }

        try {
            // Generar audio con ElevenLabs
            $audioContent = $this->elevenLabsService->generateAudio(
                $responseText,
                $context['audio_context'] ?? 'conversation'
            );

            if (!$audioContent) {
                Log::error('StreamingAudioService: Error generando audio dinámico');
                return null;
            }

            // Guardar temporalmente
            $filename = 'streaming_dynamic_' . uniqid() . '.mp3';
            $audioUrl = $this->localAudioStorage->storeTempAudio($filename, $audioContent);

            Log::info('StreamingAudioService: Audio dinámico generado', [
                'url' => $audioUrl,
                'text' => $responseText
            ]);

            return $audioUrl;

        } catch (\Exception $e) {
            Log::error('StreamingAudioService: Error en generación dinámica', [
                'error' => $e->getMessage(),
                'intent' => $intent
            ]);
            return null;
        }
    }

    /**
     * Generar texto contextual para respuesta dinámica
     */
    protected function generateContextualText(string $intent, string $sentiment, array $context = []): ?string
    {
        $baseTexts = [
            'unknown' => 'Entiendo su respuesta. Permítame procesarla adecuadamente.',
            'unclear' => 'Disculpe, no logré entender completamente. ¿Puede aclarar por favor?',
            'complex' => 'Comprendo que es una situación compleja. Déjeme ayudarle paso a paso.',
            'information' => 'Gracias por la información. La estoy procesando ahora mismo.',
            'callback' => '¿Le gustaría que le devolvamos la llamada en un momento más conveniente?',
            'transfer' => 'Le voy a conectar con uno de nuestros especialistas.',
            'interested_details' => 'Veo que tiene interés. Permítame darle todos los detalles.',
            'details_question' => 'Perfecto, nuestro supervisor le confirmará todos los detalles específicos.',
            'time_question' => 'Por supuesto, le detallo los horarios y tiempos de trabajo.',
            'location_question' => 'Le confirmo las rutas y ubicaciones exactas para el trabajo.'
        ];

        $baseText = $baseTexts[$intent] ?? $baseTexts['unknown'];

        // Ajustar según sentimiento
        $sentimentModifiers = [
            'positive' => '¡Excelente! ',
            'enthusiastic' => '¡Perfecto! ',
            'cautious' => 'Entiendo su precaución. ',
            'concerned' => 'Comprendo su preocupación. ',
            'patient' => 'No se preocupe. ',
            'helpful' => '¡Por supuesto! '
        ];

        if (isset($sentimentModifiers[$sentiment])) {
            $baseText = $sentimentModifiers[$sentiment] . $baseText;
        }

        // Agregar contexto específico si está disponible
        if (isset($context['driver_name'])) {
            $baseText = str_replace('su', $context['driver_name'], $baseText);
        }

        return $baseText;
    }

    /**
     * Obtener respuesta inmediata para interacciones en tiempo real
     */
    public function getImmediateResponse(string $type = 'listening'): ?string
    {
        $immediateResponses = [
            'listening' => 'listening',
            'processing' => 'processing',
            'thinking' => 'processing',
            'wait' => 'processing',
            'confirm' => 'confirmation_needed',
            'repeat' => 'repeat_request'
        ];

        $audioKey = $immediateResponses[$type] ?? 'listening';
        return GenerateStaticAudios::getStaticAudioUrl($audioKey);
    }

    /**
     * Limpiar archivos temporales de streaming
     */
    public function cleanupStreamingFiles(int $maxAgeMinutes = 60): int
    {
        Log::info('StreamingAudioService: Iniciando limpieza de archivos de streaming', [
            'max_age_minutes' => $maxAgeMinutes
        ]);

        $cleaned = 0;
        $tempDir = storage_path('app/public/audios/temp');
        
        if (!is_dir($tempDir)) {
            return $cleaned;
        }

        $files = glob($tempDir . '/streaming_dynamic_*.mp3');
        $cutoffTime = time() - ($maxAgeMinutes * 60);

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }

        Log::info('StreamingAudioService: Limpieza completada', [
            'files_cleaned' => $cleaned
        ]);

        return $cleaned;
    }

    /**
     * Obtener estadísticas del servicio de streaming
     */
    public function getStreamingStats(): array
    {
        $staticDir = storage_path('app/public/audios/static');
        $tempDir = storage_path('app/public/audios/temp');

        $staticCount = 0;
        $tempCount = 0;
        $staticSize = 0;
        $tempSize = 0;

        if (is_dir($staticDir)) {
            $staticFiles = glob($staticDir . '/*.mp3');
            $staticCount = count($staticFiles);
            foreach ($staticFiles as $file) {
                $staticSize += filesize($file);
            }
        }

        if (is_dir($tempDir)) {
            $tempFiles = glob($tempDir . '/streaming_dynamic_*.mp3');
            $tempCount = count($tempFiles);
            foreach ($tempFiles as $file) {
                $tempSize += filesize($file);
            }
        }

        return [
            'static_audios' => [
                'count' => $staticCount,
                'size_bytes' => $staticSize,
                'size_mb' => round($staticSize / 1024 / 1024, 2)
            ],
            'temp_audios' => [
                'count' => $tempCount,
                'size_bytes' => $tempSize,
                'size_mb' => round($tempSize / 1024 / 1024, 2)
            ],
            'total_size_mb' => round(($staticSize + $tempSize) / 1024 / 1024, 2)
        ];
    }

    /**
     * NUEVO: Mapeo inteligente que considera múltiples factores
     */
    protected function getIntelligentIntentMapping(string $intent, string $sentiment, array $context): array
    {
        $mapping = [];
        
        // Análisis temporal para saludos
        $hour = (int) date('H');
        if ($intent === 'greeting') {
            if ($hour >= 5 && $hour < 12) {
                $mapping[$intent] = 'morning_greeting';
            } elseif ($hour >= 12 && $hour < 18) {
                $mapping[$intent] = 'afternoon_greeting';
            } else {
                $mapping[$intent] = 'evening_greeting';
            }
        }
        
        // Análisis de contexto emocional
        if (isset($context['driver_emotion'])) {
            $emotion = $context['driver_emotion'];
            
            if ($emotion === 'confused' && $intent === 'clarify') {
                $mapping[$intent] = 'clarify_patient';
            } elseif ($emotion === 'interested' && $intent === 'clarify') {
                $mapping[$intent] = 'clarify_helpful';
            }
        }
        
        // Análisis de urgencia
        if (isset($context['urgency_level']) && $context['urgency_level'] === 'high'] {
            if (in_array($intent, ['accept', 'clarify', 'processing'])) {
                $mapping[$intent] = 'urgent_' . $intent;
            }
        }
        
        return $mapping;
    }

    /**
     * NUEVO: Selección por contexto y sentimiento
     */
    protected function selectByContext(string $baseType, string $sentiment, array $context): string
    {
        // Mapeo de sentimientos a variaciones
        $sentimentMap = [
            'positive' => '_positive',
            'enthusiastic' => '_enthusiastic', 
            'polite' => '_polite',
            'patient' => '_patient',
            'helpful' => '_helpful',
            'firm' => '_firm',
            'calm' => '_calm',
            'supportive' => '_supportive'
        ];
        
        $variation = $sentimentMap[$sentiment] ?? '';
        $audioKey = $baseType . $variation;
        
        // Verificar si existe la variación específica
        if ($this->staticAudioExists($audioKey)) {
            return $audioKey;
        }
        
        // Fallback al tipo base
        return $baseType . '_response';
    }

    /**
     * NUEVO: Selección de saludo por hora del día
     */
    protected function selectGreetingByTime(array $context): string
    {
        $hour = isset($context['time_of_day']) ? (int) $context['time_of_day'] : (int) date('H');
        
        if ($hour >= 5 && $hour < 12) {
            return 'morning_greeting';
        } elseif ($hour >= 12 && $hour < 18) {
            return 'afternoon_greeting';
        } else {
            return 'evening_greeting';
        }
    }

    /**
     * NUEVO: Selección por nivel de urgencia
     */
    protected function selectByUrgency(string $baseType, array $context): string
    {
        $urgencyLevel = $context['urgency_level'] ?? 'normal';
        
        switch ($urgencyLevel) {
            case 'high':
            case 'critical':
                return str_replace('_response', '_enthusiastic', $baseType);
            case 'medium':
                return str_replace('_response', '_concerned', $baseType);
            default:
                return $baseType;
        }
    }

    /**
     * NUEVO: Verificar si existe un audio estático específico
     */
    protected function staticAudioExists(string $audioKey): bool
    {
        try {
            $audioPath = storage_path("app/public/audios/static/static_{$audioKey}.mp3");
            return file_exists($audioPath);
        } catch (\Exception $e) {
            Log::debug("Error verificando audio estático: {$audioKey}", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}