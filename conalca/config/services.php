<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'local_storage' => [
        'enabled' => env('LOCAL_AUDIO_STORAGE', true),
        'audio_path' => env('LOCAL_AUDIO_PATH', 'storage/app/public/audios'),
        'recordings_path' => env('LOCAL_RECORDINGS_PATH', 'storage/app/public/recordings'),
        'cleanup_hours' => env('LOCAL_AUDIO_CLEANUP_HOURS', 24),
        'base_url' => env('LOCAL_FILES_BASE_URL', '/storage'),
    ],

    'silog' => [
        'base' => env('SILOG_BASE'),
        'user' => env('SILOG_USER'),
        'pass' => env('SILOG_PASS'),
    ],

    'elevenlabs' => [
        'api_key' => env('ELEVENLABS_API_KEY'),
        'default_voice_id' => env('ELEVENLABS_DEFAULT_VOICE_ID', 'qHkrJuifPpn95wK3rm2A'),
        'model_id' => env('ELEVENLABS_MODEL_ID', 'eleven_multilingual_v2'),
        'optimize_streaming_latency' => env('ELEVENLABS_OPTIMIZE_STREAMING_LATENCY', 1),
        'output_format' => env('ELEVENLABS_OUTPUT_FORMAT', 'mp3_44100_128'),
        'agent_id' => env('ELEVENLABS_AGENT_ID'),
        'agent_phone_number_id' => env('ELEVENLABS_AGENT_PHONE_NUMBER_ID'),
        'agent_name' => env('ELEVENLABS_AGENT_NAME', 'Conalca'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    'local_audio' => [
        'enabled' => env('LOCAL_AUDIO_STORAGE', true),
        'path' => env('LOCAL_AUDIO_PATH', 'storage/app/public/audios'),
        'cleanup_hours' => env('LOCAL_AUDIO_CLEANUP_HOURS', 1),
    ],

];
