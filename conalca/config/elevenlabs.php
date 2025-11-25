<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ElevenLabs Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for ElevenLabs Text-to-Speech service
    |
    */

    'api_key' => env('ELEVENLABS_API_KEY'),

    'default_voice_id' => env('ELEVENLABS_DEFAULT_VOICE_ID', 'qHkrJuifPpn95wK3rm2A'),
    
    // Configuración específica de la voz Andrea
    'andrea_voice' => [
        'id' => 'qHkrJuifPpn95wK3rm2A',
        'name' => 'Andrea',
        'description' => 'Voz oficial de CONALCA - Asistente virtual',
        'mandatory' => true, // Siempre usar esta voz, no permitir cambios
        'fallback_allowed' => false, // No permitir fallback a otras voces
    ],

    'default_model' => env('ELEVENLABS_MODEL_ID', 'eleven_multilingual_v2'),
    
    'base_url' => env('ELEVENLABS_BASE_URL', 'https://api.elevenlabs.io/v1'),
    
    'timeout' => env('ELEVENLABS_TIMEOUT', 30),
    
    'output_format' => env('ELEVENLABS_OUTPUT_FORMAT', 'mp3_44100_192'),
    
    // Sistema de caché para optimizar rendimiento
    'cache' => [
        'enabled' => env('ELEVENLABS_CACHE_ENABLED', true),
        'ttl' => env('ELEVENLABS_CACHE_TTL', 43200), // 12 horas
        'prefix' => 'elevenlabs_audio_',
        'cleanup_interval' => env('ELEVENLABS_CACHE_CLEANUP', 3600), // 1 hora
    ],
    
    // Optimizaciones específicas para telefonía
    'phone_optimization' => [
        'enhance_clarity' => true,
        'normalize_volume' => true,
        'compression_level' => 'medium',
    ],
    
    'voice_settings' => [
        'spanish' => [
            'stability' => 0.90,        // Aumentado para mayor consistencia en llamadas
            'similarity_boost' => 0.80, // Mejorado para mantener identidad de voz
            'style' => 0.10,           // Aumentado para mayor expresividad
            'use_speaker_boost' => true,
        ],
        'english' => [
            'stability' => 0.85,
            'similarity_boost' => 0.80,
            'style' => 0.12,
            'use_speaker_boost' => true,
        ]
    ],
    
    'spanish_voices' => [
        'george' => [
            'voice_id' => 'JBFqnCBsd6RMkjVDRZzb',
            'name' => 'George',
            'description' => 'Calm, warm male voice - great for professional use',
            'gender' => 'male',
            'recommended' => true,
        ],
        'adam' => [
            'voice_id' => 'pNInz6obpgDQGcFmaJgB',
            'name' => 'Adam',
            'description' => 'Deep, engaging male voice',
            'gender' => 'male',
            'recommended' => true,
        ],
        'bella' => [
            'voice_id' => 'EXAVITQu4vr4xnSDxMaL',
            'name' => 'Bella',
            'description' => 'Sweet, friendly female voice',
            'gender' => 'female',
            'recommended' => true,
        ],
        'antoni' => [
            'voice_id' => 'ErXwobaYiN019PkySvjV',
            'name' => 'Antoni',
            'description' => 'Well-rounded male voice',
            'gender' => 'male',
            'recommended' => false,
        ],
        'arnold' => [
            'voice_id' => 'VR6AewLTigWG4xSOukaG',
            'name' => 'Arnold',
            'description' => 'Crisp, authoritative male voice',
            'gender' => 'male',
            'recommended' => false,
        ]
    ],
    
    'limits' => [
        'max_text_length' => 4000,        // Aumentado para respuestas más completas
        'max_concurrent_requests' => 8,   // Aumentado para mejor rendimiento
    ],
    
    // Configuración específica para conversaciones telefónicas
    'conversation' => [
        'max_turns' => 20,                // Máximo de intercambios por conversación
        'response_timeout' => 10,         // Tiempo máximo para generar respuesta
        'silence_timeout' => 6,           // Tiempo de espera por silencio
        'fallback_to_twilio' => true,     // Usar TTS de Twilio como fallback
    ],
    
    // Mensajes predefinidos para optimización
    'predefined_messages' => [
        'welcome' => 'Hola, soy Andrea, tu asistente virtual de CONALCA. ¿En qué puedo ayudarte hoy?',
        'listening' => 'Te escucho...',
        'repeat' => 'Disculpa, no he podido entender lo que has dicho. ¿Podrías repetirlo por favor?',
        'error' => 'Lo siento, estamos experimentando problemas técnicos. Te transferiré con un asesor.',
        'goodbye' => 'Gracias por comunicarte con CONALCA. ¡Que tengas un excelente día!',
    ],

    // Configuración del webhook para recibir actualizaciones de estado
    'webhook' => [
        'url' => env('ELEVENLABS_WEBHOOK_URL', 'https://conalcaia.conalca.com.co/api/elevenlabs-webhook'),
        'enabled' => env('ELEVENLABS_WEBHOOK_ENABLED', true),
        'secret' => env('ELEVENLABS_WEBHOOK_SECRET', null),
        'events' => [
            'call_initiated',
            'call_ringing',
            'call_answered',
            'call_completed',
            'call_failed',
            'call_busy',
            'call_no_answer',
        ],
    ],

    // Configuración de agente conversacional
    'agent' => [
        'id' => env('ELEVENLABS_AGENT_ID', 'agent_9801k67a3m3afxcvy15rdcxq04jd'),
        'name' => 'Andrea - CONALCA',
        'phone_number_id' => env('ELEVENLABS_PHONE_NUMBER_ID', 'phnum_2501k663pcm6fynsjctames01yxb'),
        'max_call_duration' => env('ELEVENLABS_MAX_CALL_DURATION', 300), // 5 minutos
        'language' => 'es',
        'country_code' => 'CO',
    ],
];
