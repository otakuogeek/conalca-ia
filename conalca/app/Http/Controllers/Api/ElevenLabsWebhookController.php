<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CallQueueManager;
use App\Models\Llamada;
use App\Models\LlamadaConductor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ElevenLabsWebhookController extends Controller
{
    /**
     * Webhook principal que recibe eventos post-llamada de ElevenLabs.
     * 
     * Tipos de eventos:
     * - post_call_transcription: Llamada completada con transcripción
     * - post_call_audio: Audio de la llamada (solo se confirma recepción)
     * - call_initiation_failure: Llamada falló al iniciar (busy, no-answer, etc.)
     * 
     * Cada vez que una llamada termina o falla, se libera un slot y se despacha
     * la siguiente llamada en cola (máximo 2 simultáneas por límite Zadarma).
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        $type = $payload['type'] ?? 'unknown';
        $data = $payload['data'] ?? [];
        $conversationId = $data['conversation_id'] ?? null;

        Log::info('ElevenLabs Webhook recibido', [
            'type' => $type,
            'conversation_id' => $conversationId,
            'agent_id' => $data['agent_id'] ?? null,
            'event_timestamp' => $payload['event_timestamp'] ?? null,
        ]);

        if (!$conversationId) {
            Log::warning('ElevenLabs Webhook: sin conversation_id', ['payload' => $payload]);
            return response()->json(['status' => 'error', 'message' => 'Missing conversation_id'], 400);
        }

        try {
            match ($type) {
                'post_call_transcription' => $this->handleTranscription($conversationId, $data),
                'call_initiation_failure' => $this->handleCallFailure($conversationId, $data),
                'post_call_audio' => $this->handleAudio($conversationId, $data),
                default => Log::info('ElevenLabs Webhook: tipo desconocido', ['type' => $type]),
            };
        } catch (\Exception $e) {
            Log::error('ElevenLabs Webhook: Error procesando evento', [
                'type' => $type,
                'conversation_id' => $conversationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Siempre responder 200 para que ElevenLabs no desactive el webhook
        return response()->json(['status' => 'received', 'type' => $type]);
    }

    /**
     * Procesar webhook de transcripción (llamada completada)
     */
    private function handleTranscription(string $conversationId, array $data): void
    {
        Log::info('ElevenLabs Webhook: post_call_transcription', [
            'conversation_id' => $conversationId,
            'status' => $data['status'] ?? 'unknown',
            'call_duration' => $data['metadata']['call_duration_secs'] ?? null,
            'call_successful' => $data['analysis']['call_successful'] ?? null,
        ]);

        // Extraer datos relevantes del transcript para guardar en la llamada
        $webhookData = [
            'transcript' => $data['transcript'] ?? null,
            'analysis' => $data['analysis'] ?? null,
            'metadata' => $data['metadata'] ?? [],
            'conversation_initiation_client_data' => $data['conversation_initiation_client_data'] ?? null,
        ];

        // Actualizar datos extra si disponibles
        $this->updateCallMetadata($conversationId, $data);

        // Delegar al CallQueueManager: marca completada + despacha siguiente
        CallQueueManager::onCallCompleted($conversationId, $webhookData);
    }

    /**
     * Procesar webhook de fallo de llamada
     */
    private function handleCallFailure(string $conversationId, array $data): void
    {
        $failureReason = $data['failure_reason'] ?? 'unknown';
        $metadata = $data['metadata'] ?? [];

        Log::info('ElevenLabs Webhook: call_initiation_failure', [
            'conversation_id' => $conversationId,
            'failure_reason' => $failureReason,
            'metadata_type' => $metadata['type'] ?? 'unknown',
            'sip_status_code' => $metadata['body']['sip_status_code'] ?? null,
        ]);

        // Delegar al CallQueueManager: marca fallida + despacha siguiente
        CallQueueManager::onCallFailed($conversationId, $failureReason, $metadata);
    }

    /**
     * Procesar webhook de audio (solo confirmar recepción, no necesitamos el audio)
     */
    private function handleAudio(string $conversationId, array $data): void
    {
        Log::info('ElevenLabs Webhook: post_call_audio recibido (audio descartado)', [
            'conversation_id' => $conversationId,
        ]);
        // No procesamos el audio por ahora, solo confirmamos recepción
    }

    /**
     * Actualizar metadata adicional de la llamada desde el webhook
     */
    private function updateCallMetadata(string $conversationId, array $data): void
    {
        $llamada = Llamada::where('elevenlabs_conversation_id', $conversationId)->first();
        if (!$llamada) {
            return;
        }

        $metadata = $data['metadata'] ?? [];
        $analysis = $data['analysis'] ?? [];

        $updateData = [];

        // Guardar tiempos detallados
        if (isset($metadata['start_time_unix_secs'])) {
            $updateData['call_started_at'] = \Carbon\Carbon::createFromTimestamp($metadata['start_time_unix_secs']);
        }
        if (isset($metadata['call_duration_secs'])) {
            $updateData['call_duration_seconds'] = $metadata['call_duration_secs'];
            $updateData['talk_duration_seconds'] = $metadata['call_duration_secs'];
        }

        // Guardar análisis y metadata completa
        $updateData['elevenlabs_response'] = [
            'analysis' => $analysis,
            'termination_reason' => $metadata['termination_reason'] ?? null,
            'cost' => $metadata['cost'] ?? null,
        ];

        // Guardar transcripción resumida
        if (isset($analysis['transcript_summary'])) {
            $updateData['internal_notes'] = 'Resumen: ' . substr($analysis['transcript_summary'], 0, 500);
        }

        if (!empty($updateData)) {
            $llamada->update($updateData);
        }
    }

    /**
     * Endpoint para consultar el estado actual de la cola de llamadas
     */
    public function getQueueStatus(Request $request)
    {
        $cotizacionId = $request->input('cotizacion_id');

        return response()->json([
            'success' => true,
            'queue_status' => CallQueueManager::getQueueStatus($cotizacionId ? (int) $cotizacionId : null),
            'max_concurrent' => CallQueueManager::getMaxConcurrentCalls(),
            'timestamp' => now()->toISOString(),
        ]);
    }
}
