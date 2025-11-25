<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ElevenLabsService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.elevenlabs.io/v1';
    protected $defaultVoiceId;
    protected $modelId;
    protected $optimizeStreamingLatency;
    protected $outputFormat;
    
    protected $voiceStability;
    protected $voiceSimilarity;
    protected $voiceStyle;
    protected $voiceBoost;
    protected $timeout;

    public function __construct()
    {
        $this->apiKey = config('services.elevenlabs.api_key');
        $this->defaultVoiceId = config('services.elevenlabs.default_voice_id');
        $this->modelId = config('services.elevenlabs.model_id');
        $this->optimizeStreamingLatency = config('services.elevenlabs.optimize_streaming_latency');
        $this->outputFormat = config('services.elevenlabs.output_format');
        
        // Configuraciones optimizadas de voz para máxima fluidez
        $this->voiceStability = env('ELEVENLABS_VOICE_STABILITY', 0.95);
        $this->voiceSimilarity = env('ELEVENLABS_VOICE_SIMILARITY', 0.85);
        $this->voiceStyle = env('ELEVENLABS_VOICE_STYLE', 0.15);
        $this->voiceBoost = env('ELEVENLABS_VOICE_BOOST', true);
        $this->timeout = env('ELEVENLABS_TIMEOUT', 45);
    }
    
    /**
     * Obtiene la lista de voces disponibles
     */
    public function getAvailableVoices()
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'xi-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->get("{$this->baseUrl}/voices");

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Error al obtener voces de ElevenLabs', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return ['voices' => []];
            }
        } catch (\Exception $e) {
            Log::error('Excepción al obtener voces de ElevenLabs', [
                'error' => $e->getMessage()
            ]);
            return ['voices' => []];
        }
    }

    /**
     * Valida si una voz existe
     */
    public function voiceExists($voiceId)
    {
        $voices = $this->getAvailableVoices();
        foreach ($voices['voices'] as $voice) {
            if ($voice['voice_id'] === $voiceId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Genera audio a partir de texto usando ElevenLabs y lo guarda como archivo
     * Retorna la URL pública del archivo generado
     */
    public function generateSpeech($text, $voiceId = null, $modelId = null)
    {
        $voiceId = $voiceId ?? $this->defaultVoiceId;
        $modelId = $modelId ?? $this->modelId;
        
        try {
            Log::info("Generando audio con ElevenLabs", [
                'text_length' => strlen($text),
                'voice_id' => $voiceId,
                'model_id' => $modelId
            ]);
            
            // Configuración optimizada para máxima fluidez y calidad
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'xi-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'audio/mpeg'
                ])
                ->post("{$this->baseUrl}/text-to-speech/{$voiceId}/stream", [
                    'text' => $text,
                    'model_id' => $modelId,
                    'voice_settings' => [
                        'stability' => $this->voiceStability,
                        'similarity_boost' => $this->voiceSimilarity,
                        'style' => $this->voiceStyle,
                        'use_speaker_boost' => $this->voiceBoost
                    ],
                    'optimize_streaming_latency' => $this->optimizeStreamingLatency,
                    'output_format' => $this->outputFormat
                ]);
            
            if ($response->successful()) {
                // Generar nombre único para el archivo
                $filename = 'elevenlabs_' . uniqid() . '.mp3';
                $path = 'public/audios/' . $filename;
                
                // Guardar el archivo de audio
                \Storage::put($path, $response->body());
                
                // Retornar URL pública
                $publicUrl = env('APP_URL') . '/storage/audios/' . $filename;
                
                Log::info("Audio generado y guardado exitosamente", [
                    'filename' => $filename,
                    'url' => $publicUrl
                ]);
                
                return $publicUrl;
            } else {
                Log::error('Error en ElevenLabs API', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Excepción en ElevenLabs API: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Genera audio con configuración optimizada para conversaciones telefónicas
     */
    public function generatePhoneOptimizedSpeech($text, $voiceId = null, $context = 'conversation')
    {
        $voiceId = $voiceId ?? $this->defaultVoiceId;
        
        // Configuraciones específicas por contexto para máxima fluidez
        $contextSettings = [
            'greeting' => [
                'stability' => 0.98,
                'similarity_boost' => 0.90,
                'style' => 0.05,
                'use_speaker_boost' => true
            ],
            'conversation' => [
                'stability' => $this->voiceStability,
                'similarity_boost' => $this->voiceSimilarity,
                'style' => $this->voiceStyle,
                'use_speaker_boost' => $this->voiceBoost
            ],
            'urgent' => [
                'stability' => 0.85,
                'similarity_boost' => 0.95,
                'style' => 0.25,
                'use_speaker_boost' => true
            ]
        ];

        $voiceSettings = $contextSettings[$context] ?? $contextSettings['conversation'];
        
        try {
            Log::info("Generando audio optimizado para teléfono", [
                'context' => $context,
                'text_length' => strlen($text),
                'voice_id' => $voiceId,
                'settings' => $voiceSettings
            ]);
            
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'xi-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'audio/mpeg'
                ])
                ->post("{$this->baseUrl}/text-to-speech/{$voiceId}/stream", [
                    'text' => $text,
                    'model_id' => $this->modelId,
                    'voice_settings' => $voiceSettings,
                    'optimize_streaming_latency' => $this->optimizeStreamingLatency,
                    'output_format' => $this->outputFormat
                ]);
            
            if ($response->successful()) {
                // Generar nombre único para el archivo con contexto
                $filename = 'elevenlabs_' . $context . '_' . uniqid() . '.mp3';
                $path = 'public/audios/' . $filename;
                
                // Guardar el archivo de audio
                \Storage::put($path, $response->body());
                
                // Retornar URL pública
                $publicUrl = env('APP_URL') . '/storage/audios/' . $filename;
                
                Log::info("Audio optimizado generado exitosamente", [
                    'filename' => $filename,
                    'url' => $publicUrl,
                    'size' => strlen($response->body())
                ]);
                
                return $publicUrl;
            } else {
                Log::error('Error en ElevenLabs API (optimized)', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Excepción en ElevenLabs API (optimized): ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Genera audio y retorna el contenido binario (para archivos estáticos)
     */
    public function generateAudioContent($text, $voiceId = null, $context = 'conversation')
    {
        $voiceId = $voiceId ?? $this->defaultVoiceId;
        
        // Configuraciones específicas por contexto
        $contextSettings = [
            'greeting' => [
                'stability' => 0.98,
                'similarity_boost' => 0.90,
                'style' => 0.05,
                'use_speaker_boost' => true
            ],
            'conversation' => [
                'stability' => $this->voiceStability,
                'similarity_boost' => $this->voiceSimilarity,
                'style' => $this->voiceStyle,
                'use_speaker_boost' => $this->voiceBoost
            ],
            'farewell' => [
                'stability' => 0.92,
                'similarity_boost' => 0.88,
                'style' => 0.10,
                'use_speaker_boost' => true
            ]
        ];

        $voiceSettings = $contextSettings[$context] ?? $contextSettings['conversation'];
        
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'xi-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'audio/mpeg'
                ])
                ->post("{$this->baseUrl}/text-to-speech/{$voiceId}/stream", [
                    'text' => $text,
                    'model_id' => $this->modelId,
                    'voice_settings' => $voiceSettings,
                    'optimize_streaming_latency' => $this->optimizeStreamingLatency,
                    'output_format' => $this->outputFormat
                ]);
            
            if ($response->successful()) {
                return $response->body();
            } else {
                Log::error('Error en ElevenLabs API (content)', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Excepción en ElevenLabs API (content): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene las voces disponibles
     */
    public function getVoices()
    {
        $cacheKey = 'elevenlabs_voices';
        
        return Cache::remember($cacheKey, 3600, function () {
            try {
                Log::info("Obteniendo voces disponibles de ElevenLabs");
                
                $response = Http::withHeaders([
                    'xi-api-key' => $this->apiKey
                ])->get("{$this->baseUrl}/voices");
                
                if ($response->successful()) {
                    $voices = $response->json();
                    Log::info("Voces obtenidas exitosamente", ['count' => count($voices['voices'])]);
                    return $voices;
                } else {
                    Log::error('Error al obtener voces de ElevenLabs: ' . $response->body());
                    return ['voices' => []];
                }
            } catch (\Exception $e) {
                Log::error('Excepción al obtener voces: ' . $e->getMessage());
                return ['voices' => []];
            }
        });
    }
    
    /**
     * Valida que la API key esté configurada correctamente
     */
    public function validateApiKey()
    {
        try {
            $response = Http::withHeaders([
                'xi-api-key' => $this->apiKey
            ])->get("{$this->baseUrl}/user");
            
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Error al validar API key: ' . $e->getMessage());
            return false;
        }
    }
}
