<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ElevenLabsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ElevenLabsController extends Controller
{
    protected $elevenLabs;

    public function __construct()
    {
        $this->elevenLabs = new ElevenLabsService();
    }

    /**
     * Get all available voices
     */
    public function getVoices(): JsonResponse
    {
        try {
            $voices = $this->elevenLabs->getVoices();
            return response()->json([
                'success' => true,
                'data' => $voices
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recommended Spanish voices
     */
    public function getSpanishVoices(): JsonResponse
    {
        try {
            $voices = $this->elevenLabs->getSpanishVoices();
            return response()->json([
                'success' => true,
                'data' => $voices
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available models
     */
    public function getModels(): JsonResponse
    {
        try {
            $models = $this->elevenLabs->getModels();
            return response()->json([
                'success' => true,
                'data' => $models
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test text-to-speech
     */
    public function testTts(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string|max:1000',
            'voice_id' => 'nullable|string',
            'model_id' => 'nullable|string'
        ]);

        try {
            $result = $this->elevenLabs->textToSpeech(
                $request->input('text'),
                $request->input('voice_id', 'JBFqnCBsd6RMkjVDRZzb'),
                $request->input('model_id', 'eleven_multilingual_v2')
            );

            // Save test file
            $filename = 'test_' . time() . '.mp3';
            $filePath = $this->elevenLabs->saveAudioFile($result['audio_content'], $filename);

            return response()->json([
                'success' => true,
                'data' => [
                    'file_path' => $filePath,
                    'size' => $result['size'],
                    'content_type' => $result['content_type'],
                    'url' => asset('storage/' . $filePath)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
