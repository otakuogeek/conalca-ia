<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Llamada;
use App\Models\LlamadaConductor;
use App\Services\CallQueueManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CallHangupController extends Controller
{
    private const ALLOWED_ROLES = [
        'SUPER ADMIN',
        'JEFE COMERCIAL',
        'GERENTE DE CUENTA',
        'ASISTENTE COMERCIAL',
        'SAC',
    ];

    private const ACTIVE_CALL_STATUSES = [
        Llamada::CALL_STATUS_INITIATED,
        Llamada::CALL_STATUS_RINGING,
        Llamada::CALL_STATUS_ANSWERED,
    ];

    public function __invoke(Request $request, Llamada $llamada)
    {
        $user = $request->user();

        if (!$this->userCanHangupCalls($user)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para colgar llamadas.',
            ], 403);
        }

        if (!$this->isHangupAllowed($llamada)) {
            return response()->json([
                'success' => false,
                'message' => 'La llamada ya no está activa ni pendiente.',
            ], 409);
        }

        $provider = config('services.elevenlabs.call_provider', 'zadarma');
        $remoteHangup = false;

        if ($this->needsRemoteHangup($llamada)) {
            if ($provider !== 'twilio') {
                return response()->json([
                    'success' => false,
                    'message' => 'El proveedor actual no tiene colgado remoto configurado.',
                ], 422);
            }

            $twilioResult = $this->hangupTwilioCall($llamada);

            if (!$twilioResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $twilioResult['message'],
                ], $twilioResult['status']);
            }

            $remoteHangup = true;
        }

        $this->markCallAsCancelled($llamada, $user, $remoteHangup);
        CallQueueManager::dispatchNextCalls($llamada->id_cotizacion);

        return response()->json([
            'success' => true,
            'message' => $remoteHangup
                ? 'Llamada colgada correctamente.'
                : 'Llamada cancelada antes de iniciar.',
            'llamada_id' => $llamada->id_llamada,
            'remote_hangup' => $remoteHangup,
            'queue_status' => 'cancelled',
            'call_status' => Llamada::CALL_STATUS_CANCELLED,
        ]);
    }

    private function userCanHangupCalls($user): bool
    {
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(self::ALLOWED_ROLES)) {
            return true;
        }

        foreach (['calls.hangup', 'colgar llamadas', 'hangup calls'] as $permission) {
            if (method_exists($user, 'can') && $user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    private function isHangupAllowed(Llamada $llamada): bool
    {
        if (in_array($llamada->queue_status, ['pending', 'processing'], true)) {
            return true;
        }

        return in_array($llamada->call_status, self::ACTIVE_CALL_STATUSES, true);
    }

    private function needsRemoteHangup(Llamada $llamada): bool
    {
        return ($llamada->elevenlabs_conversation_id || $llamada->elevenlabs_sip_call_id)
            && (
                $llamada->queue_status === 'processing'
                || in_array($llamada->call_status, self::ACTIVE_CALL_STATUSES, true)
            );
    }

    private function hangupTwilioCall(Llamada $llamada): array
    {
        $accountSid = config('services.twilio.sid');
        $authToken = config('services.twilio.token');
        $callSid = $llamada->elevenlabs_sip_call_id;

        if (!$accountSid || !$authToken) {
            return [
                'success' => false,
                'status' => 500,
                'message' => 'Twilio no está configurado para colgar llamadas.',
            ];
        }

        if (!$callSid || !preg_match('/^CA[a-f0-9]{32}$/i', $callSid)) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'La llamada no tiene un SID válido de Twilio para colgarla remotamente.',
            ];
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($accountSid, $authToken)
                ->timeout(10)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Calls/{$callSid}.json", [
                    'Status' => 'completed',
                ]);
        } catch (\Throwable $e) {
            Log::error('CallHangupController: Error conectando con Twilio', [
                'llamada_id' => $llamada->id_llamada,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 502,
                'message' => 'No se pudo conectar con Twilio para colgar la llamada.',
            ];
        }

        if (!$response->successful()) {
            Log::warning('CallHangupController: Twilio rechazó el colgado', [
                'llamada_id' => $llamada->id_llamada,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return [
                'success' => false,
                'status' => 502,
                'message' => 'Twilio no permitió colgar esta llamada.',
            ];
        }

        return ['success' => true, 'status' => 200, 'message' => 'ok'];
    }

    private function markCallAsCancelled(Llamada $llamada, $user, bool $remoteHangup): void
    {
        $now = now();
        $metadata = is_array($llamada->call_metadata) ? $llamada->call_metadata : [];
        $metadata['manual_hangup'] = [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'remote_hangup' => $remoteHangup,
            'provider' => config('services.elevenlabs.call_provider', 'zadarma'),
            'at' => $now->toISOString(),
        ];

        $note = sprintf(
            '[%s] Llamada %s manualmente por %s.',
            $now->toDateTimeString(),
            $remoteHangup ? 'colgada' : 'cancelada',
            $user?->email ?? 'usuario autenticado'
        );

        $llamada->update([
            'status' => Llamada::STATUS_FINALIZADA,
            'call_status' => Llamada::CALL_STATUS_CANCELLED,
            'queue_status' => 'cancelled',
            'failure_reason' => $remoteHangup ? 'Colgada manualmente' : 'Cancelada antes de iniciar',
            'call_ended_at' => $now,
            'call_completed_at' => $now,
            'processing_completed_at' => $now,
            'call_metadata' => $metadata,
            'call_notes' => $remoteHangup ? 'Llamada colgada manualmente' : 'Llamada cancelada antes de iniciar',
            'internal_notes' => trim(($llamada->internal_notes ?? '') . "\n" . $note),
        ]);

        if ($llamada->conductor_id) {
            $conductor = LlamadaConductor::find($llamada->conductor_id);
            if ($conductor) {
                $conductor->update([
                    'estado_llamada' => 'cancelada',
                    'fecha_llamada' => $now,
                    'notas' => trim(($conductor->notas ?? '') . "\n" . $note),
                ]);
            }
        }

        Log::info('CallHangupController: Llamada cancelada manualmente', [
            'llamada_id' => $llamada->id_llamada,
            'cotizacion_id' => $llamada->id_cotizacion,
            'user_id' => $user?->id,
            'remote_hangup' => $remoteHangup,
        ]);
    }
}