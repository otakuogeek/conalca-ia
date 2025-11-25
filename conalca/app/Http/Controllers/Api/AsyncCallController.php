<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\CotizacionModel;
use App\Models\VehicleClass;
use App\Models\VehicleOwnerHolderDriver;
use App\Models\Llamada;
use App\Jobs\CallDriverJob;

class AsyncCallController extends Controller
{
    /**
     * Iniciar llamadas de forma completamente asíncrona para evitar timeouts
     */
    public function initiateAsyncGroupCalls(Request $request)
    {
        try {
            $groupCotizationId = $request->input('group_cotization_id');

            if (!$groupCotizationId) {
                return response()->json(['error' => 'ID de grupo de cotización requerido'], 400);
            }

            Log::info('Iniciando llamadas asíncronas mejoradas para grupo', [
                'group_cotization_id' => $groupCotizationId
            ]);

            // Buscar cotizaciones del grupo que estén ACEPTADAS
            $cotizaciones = CotizacionModel::where('group_cotization_id', $groupCotizationId)
                ->where('decision_cliente', 'aceptada')
                ->get();
            
            if ($cotizaciones->isEmpty()) {
                return response()->json([
                    'error' => 'No se encontraron cotizaciones para el grupo: ' . $groupCotizationId,
                    'group_cotization_id' => $groupCotizationId
                ], 404);
            }

            $totalDriversScheduled = 0;
            $cotizacionesProcessed = 0;
            $errors = [];

            // Respuesta inmediata para evitar timeout
            return response()->json([
                'success' => true,
                'message' => 'Procesando llamadas de forma asíncrona...',
                'group_cotization_id' => $groupCotizationId,
                'total_cotizaciones' => $cotizaciones->count(),
                'status' => 'processing',
                'estimated_completion' => now()->addMinutes(5)->toISOString()
            ])->header('X-Async-Processing', 'true');

        } catch (\Exception $e) {
            Log::error("Error en llamadas asíncronas: " . $e->getMessage());
            
            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar el estado de las llamadas del grupo
     */
    public function getGroupCallStatus(Request $request)
    {
        try {
            $groupCotizationId = $request->input('group_cotization_id');

            if (!$groupCotizationId) {
                return response()->json(['error' => 'ID de grupo requerido'], 400);
            }

            // Obtener todas las cotizaciones ACEPTADAS del grupo
            $cotizaciones = CotizacionModel::where('group_cotization_id', $groupCotizationId)
                ->where('decision_cliente', 'aceptada')
                ->get();
            $cotizacionIds = $cotizaciones->pluck('id');

            // Obtener estado de las llamadas
            $llamadas = Llamada::whereIn('id_cotizacion', $cotizacionIds)
                ->where('created_at', '>=', now()->subHours(2)) // Últimas 2 horas
                ->get();

            $statusSummary = [
                'total_llamadas' => $llamadas->count(),
                'pendientes' => $llamadas->where('status', 'pendiente')->count(),
                'en_curso' => $llamadas->where('status', 'en_curso')->count(),
                'completadas' => $llamadas->where('status', 'completada')->count(),
                'fallidas' => $llamadas->where('status', 'fallida')->count(),
                'con_conversation_id' => $llamadas->whereNotNull('elevenlabs_conversation_id')->count()
            ];

            // Últimas llamadas para mostrar progreso
            $recentCalls = $llamadas->sortByDesc('created_at')->take(5)->map(function($llamada) {
                return [
                    'id_llamada' => $llamada->id_llamada,
                    'cotizacion_id' => $llamada->id_cotizacion,
                    'chofer_id' => $llamada->chofer_id,
                    'numero_destino' => $llamada->numero_destino,
                    'status' => $llamada->status,
                    'call_status' => $llamada->call_status,
                    'conversation_id' => $llamada->elevenlabs_conversation_id,
                    'created_at' => $llamada->created_at->toISOString()
                ];
            });

            return response()->json([
                'success' => true,
                'group_cotization_id' => $groupCotizationId,
                'total_cotizaciones' => $cotizaciones->count(),
                'summary' => $statusSummary,
                'recent_calls' => $recentCalls,
                'last_updated' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error("Error obteniendo estado de llamadas: " . $e->getMessage());
            
            return response()->json([
                'error' => 'Error obteniendo estado',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formatear número de teléfono para llamadas internacionales
     */
    private function formatPhoneNumber($phoneNumber)
    {
        if (!$phoneNumber || $phoneNumber === 'N/A') {
            return null;
        }

        // Limpiar el número pero preservar el signo +
        $cleanNumber = trim($phoneNumber);
        
        // Tomar solo el primer número si hay varios separados por ' - ' o '-'
        $firstPhone = explode(' - ', $cleanNumber)[0];
        $firstPhone = explode('-', $firstPhone)[0];
        
        // Si ya tiene el signo + al inicio, es un número internacional
        if (str_starts_with($firstPhone, '+')) {
            // Limpiar todo excepto números y el signo +
            $cleanPhone = preg_replace('/[^\d+]/', '', $firstPhone);
            
            // Validar que tenga al menos 7 dígitos después del +
            $digitsOnly = substr($cleanPhone, 1);
            if (strlen($digitsOnly) >= 7 && strlen($digitsOnly) <= 15) {
                return $cleanPhone;
            }
        }
        
        // Si no tiene +, limpiar solo números
        $digitsOnly = preg_replace('/\D/', '', $firstPhone);
        
        // Si empieza con 57, usar tal como está
        if (substr($digitsOnly, 0, 2) === '57') {
            return '+' . $digitsOnly;
        }
        
        // Si es número de 10 dígitos de Colombia, agregar +57
        if (strlen($digitsOnly) == 10) {
            return '+57' . $digitsOnly;
        }
        
        // Si es número de 7 dígitos, agregar código de área (1 para Bogotá por defecto)
        if (strlen($digitsOnly) == 7) {
            return '+571' . $digitsOnly;
        }
        
        // Para otros casos, intentar agregar +57
        return '+57' . $digitsOnly;
    }
}