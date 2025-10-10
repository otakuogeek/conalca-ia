<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MockTtsService
{
    /**
     * Servicio mock para simular ElevenLabs cuando la API key no funciona
     */
    public function textToSpeech(
        string $text, 
        string $voiceId = 'mock-voice',
        string $modelId = 'mock-model',
        array $voiceSettings = []
    ): array {
        
        Log::info('Mock TTS Service: Simulando generación de audio', [
            'text' => substr($text, 0, 100) . '...',
            'voice_id' => $voiceId,
            'model_id' => $modelId
        ]);
        
        // Simular contenido de audio (en realidad sería MP3 binario)
        $mockAudioContent = "MOCK_AUDIO_DATA_" . str_repeat("A", 1000); // 1KB de datos simulados
        
        return [
            'audio_content' => $mockAudioContent,
            'content_type' => 'audio/mpeg',
            'size' => strlen($mockAudioContent)
        ];
    }
    
    public function saveAudioFile(string $audioContent, string $filename): string
    {
        $filePath = 'audio/' . $filename;
        
        // Crear directorio si no existe
        $fullPath = storage_path('app/public/audio');
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        
        // Guardar archivo mock
        file_put_contents(storage_path('app/public/' . $filePath), $audioContent);
        
        Log::info('Mock TTS: Archivo guardado', ['path' => $filePath]);
        
        return $filePath;
    }
}
