<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LlamadaConductor;
use App\Models\CotizacionModel;
use App\Models\DriverCallResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Endpoints que el agente conversacional de ElevenLabs usa como "tools"
 * durante una llamada saliente a conductores.
 *
 * Cada tool se invoca vía webhook desde ElevenLabs Conversational AI.
 */
class ElevenLabsAgentToolsController extends Controller
{
    /**
     * Tool: get_conductor_by_telefono
     *
     * Busca un conductor en llamadas_conductores por su número de teléfono.
     * Devuelve: driver_id, nombre_conductor, placa, tipo_vehiculo, ciudad_actual, cotizacion_id.
     */
    public function getConductorByTelefono(Request $request)
    {
        try {
            $telefono = $request->input('telefono');

            if (!$telefono) {
                return response()->json(['error' => 'Parámetro telefono requerido'], 400);
            }

            // Limpiar el número: quitar +57, espacios, guiones
            $telefonoLimpio = preg_replace('/[^0-9]/', '', $telefono);
            // Si empieza con 57 y tiene más de 10 dígitos, quitar el prefijo
            if (strlen($telefonoLimpio) > 10 && str_starts_with($telefonoLimpio, '57')) {
                $telefonoLimpio = substr($telefonoLimpio, 2);
            }

            Log::info('ElevenLabs Tool: get_conductor_by_telefono', [
                'telefono_original' => $telefono,
                'telefono_limpio' => $telefonoLimpio
            ]);

            // Buscar en llamadas_conductores (tabla principal) – los más recientes primero
            $conductores = LlamadaConductor::where('telefono', 'LIKE', '%' . $telefonoLimpio . '%')
                ->whereIn('estado_llamada', ['en_progreso', 'pendiente'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            if ($conductores->isEmpty()) {
                // Fallback: buscar sin filtro de estado
                $conductores = LlamadaConductor::where('telefono', 'LIKE', '%' . $telefonoLimpio . '%')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
            }

            $result = $conductores->map(function ($c) {
                return [
                    'driver_id' => $c->id,
                    'nombre_conductor' => $c->nombre_conductor,
                    'placa' => $c->placa,
                    'tipo_vehiculo' => $c->tipo_vehiculo ?? $c->clase_vehiculo,
                    'ciudad_actual' => $c->ciudad_actual ?? $c->ciudad_origen,
                    'cotizacion_id' => $c->cotizacion_id,
                    'group_cotization_id' => $c->group_cotization_id,
                    'telefono' => $c->telefono,
                    'carroceria' => $c->carroceria,
                    'capacidad' => $c->capacidad,
                    'mercancia' => $c->mercancia,
                    'ciudad_origen' => $c->ciudad_origen,
                    'ciudad_destino' => $c->ciudad_destino,
                    'peso_carga' => $c->peso_carga,
                    'empaque' => $c->empaque,
                    'estado_llamada' => $c->estado_llamada,
                ];
            });

            Log::info('ElevenLabs Tool: get_conductor_by_telefono resultado', [
                'telefono' => $telefonoLimpio,
                'conductores_encontrados' => $conductores->count()
            ]);

            return response()->json([
                'success' => true,
                'conductores' => $result,
                'total' => $conductores->count()
            ]);

        } catch (\Exception $e) {
            Log::error('ElevenLabs Tool: Error en get_conductor_by_telefono', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Tool: get_cotizaciones
     *
     * Obtiene los detalles de una cotización por ID o lista paginada.
     * Devuelve: ciudad_origen, ciudad_destino, peso_mercancia, tipo_mercancia,
     *           tipo_embajale, vehiculo_requerido, tipo_carroceria, fechas.
     */
    public function getCotizaciones(Request $request)
    {
        try {
            $cotizacionId = $request->input('cotizacion_id');
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 10);

            Log::info('ElevenLabs Tool: get_cotizaciones', [
                'cotizacion_id' => $cotizacionId,
                'page' => $page,
                'limit' => $limit
            ]);

            if ($cotizacionId) {
                // Búsqueda por ID específico
                $cotizacion = CotizacionModel::find($cotizacionId);

                if (!$cotizacion) {
                    return response()->json(['error' => 'Cotización no encontrada'], 404);
                }

                return response()->json([
                    'success' => true,
                    'cotizaciones' => [[
                        'id' => $cotizacion->id,
                        'group_cotization_id' => $cotizacion->group_cotization_id,
                        'ciudad_origen' => $cotizacion->ciudad_origen,
                        'ciudad_destino' => $cotizacion->ciudad_destino,
                        'peso_mercancia' => $cotizacion->peso_mercancia,
                        'tipo_mercancia' => $cotizacion->tipo_mercancia,
                        'tipo_embajale' => $cotizacion->tipo_embajale,
                        'vehiculo_requerido' => $cotizacion->vehiculo_requerido,
                        'tipo_carroceria' => $cotizacion->tipo_carroceria,
                        'valor_declarado' => $cotizacion->valor_declarado,
                        'consolidado_expreso' => $cotizacion->consolidado_expreso,
                        'regimen_nacionalizado' => $cotizacion->regimen_nacionalizado,
                        'temperatura_mercancia' => $cotizacion->temperatura_mercancia,
                        'dimensiones_exactas' => $cotizacion->dimensiones_exactas,
                        'seguro' => $cotizacion->seguro,
                        'fecha_hora_descargue_cargue' => $cotizacion->fecha_hora_descargue_cargue,
                        'decision_cliente' => $cotizacion->decision_cliente,
                        'created_at' => $cotizacion->created_at?->format('Y-m-d H:i:s'),
                    ]],
                    'total' => 1
                ]);
            }

            // Paginación general
            $cotizaciones = CotizacionModel::where('decision_cliente', 'aceptada')
                ->orderBy('created_at', 'desc')
                ->paginate($limit, ['*'], 'page', $page);

            $result = $cotizaciones->map(function ($c) {
                return [
                    'id' => $c->id,
                    'group_cotization_id' => $c->group_cotization_id,
                    'ciudad_origen' => $c->ciudad_origen,
                    'ciudad_destino' => $c->ciudad_destino,
                    'peso_mercancia' => $c->peso_mercancia,
                    'tipo_mercancia' => $c->tipo_mercancia,
                    'tipo_embajale' => $c->tipo_embajale,
                    'vehiculo_requerido' => $c->vehiculo_requerido,
                    'tipo_carroceria' => $c->tipo_carroceria,
                    'valor_declarado' => $c->valor_declarado,
                    'decision_cliente' => $c->decision_cliente,
                    'created_at' => $c->created_at?->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'cotizaciones' => $result,
                'total' => $cotizaciones->total(),
                'page' => $cotizaciones->currentPage(),
                'last_page' => $cotizaciones->lastPage()
            ]);

        } catch (\Exception $e) {
            Log::error('ElevenLabs Tool: Error en get_cotizaciones', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Tool: precioviaje
     *
     * Obtiene el precio del viaje para una cotización específica.
     * Busca en la tabla cotizacion_models el campo "valor".
     */
    public function precioViaje(Request $request)
    {
        try {
            $cotizacionId = $request->input('cotizacion_id');

            if (!$cotizacionId) {
                return response()->json(['error' => 'Parámetro cotizacion_id requerido'], 400);
            }

            Log::info('ElevenLabs Tool: precioviaje', [
                'cotizacion_id' => $cotizacionId
            ]);

            $cotizacion = CotizacionModel::find($cotizacionId);

            if (!$cotizacion) {
                return response()->json(['error' => 'Cotización no encontrada'], 404);
            }

            // Formatear el valor como moneda colombiana
            $valorNumerico = floatval(str_replace(['.', ',', '$', ' '], '', $cotizacion->valor ?? '0'));
            $valorFormateado = '$' . number_format($valorNumerico, 0, ',', '.');

            return response()->json([
                'success' => true,
                'cotizacion_id' => $cotizacion->id,
                'valor' => $cotizacion->valor,
                'valor_formateado' => $valorFormateado,
                'ciudad_origen' => $cotizacion->ciudad_origen,
                'ciudad_destino' => $cotizacion->ciudad_destino,
                'vehiculo_requerido' => $cotizacion->vehiculo_requerido,
            ]);

        } catch (\Exception $e) {
            Log::error('ElevenLabs Tool: Error en precioviaje', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Tool: save_driver_decision
     *
     * Registra la decisión del conductor (aceptó=1, rechazó=0) y actualiza
     * tanto driver_call_responses como llamadas_conductores y llamadas.
     */
    public function saveDriverDecision(Request $request)
    {
        try {
            $decision = $request->input('decision'); // 1 = aceptó, 0 = rechazó
            $conversationId = $request->input('conversation_id');
            $cotizacionModelId = $request->input('cotizacion_model_id');
            $driverId = $request->input('driver_id'); // ID de llamadas_conductores

            Log::info('ElevenLabs Tool: save_driver_decision', [
                'decision' => $decision,
                'conversation_id' => $conversationId,
                'cotizacion_model_id' => $cotizacionModelId,
                'driver_id' => $driverId
            ]);

            if ($decision === null || !$conversationId) {
                return response()->json([
                    'error' => 'Parámetros requeridos: decision, conversation_id'
                ], 400);
            }

            $responseStatus = $decision == 1 ? 'accepted' : 'rejected';

            // 1. Buscar el conductor en llamadas_conductores
            $conductor = null;
            if ($driverId) {
                $conductor = LlamadaConductor::find($driverId);
            }

            // Si no lo encontramos por ID, buscar por conversation_id
            if (!$conductor && $conversationId) {
                $conductor = LlamadaConductor::where('elevenlabs_conversation_id', $conversationId)->first();
            }

            if ($conductor) {
                $conductor->update([
                    'estado_llamada' => 'completada',
                    'respuesta_llamada' => $responseStatus,
                    'notas' => ($decision == 1 ? 'Conductor ACEPTÓ el viaje' : 'Conductor RECHAZÓ el viaje') .
                        ' - Registrado por agente ElevenLabs'
                ]);

                Log::info('Conductor actualizado en llamadas_conductores', [
                    'conductor_id' => $conductor->id,
                    'nombre' => $conductor->nombre_conductor,
                    'decision' => $responseStatus
                ]);
            }

            // 2. Crear registro en driver_call_responses
            $callResponse = DriverCallResponse::create([
                'cotizacion_id' => $cotizacionModelId ?? $conductor?->cotizacion_id,
                'driver_id' => $driverId ?? $conductor?->id,
                'driver_name' => $conductor?->nombre_conductor ?? 'Desconocido',
                'driver_phone' => $conductor?->telefono,
                'vehicle_type' => $conductor?->tipo_vehiculo ?? $conductor?->clase_vehiculo,
                'vehicle_plate' => $conductor?->placa,
                'call_status' => 'completed',
                'response_status' => $responseStatus,
                'response_time' => now(),
                'elevenlabs_conversation_id' => $conversationId,
                'notes' => $decision == 1
                    ? 'Conductor aceptó el viaje vía agente IA'
                    : 'Conductor rechazó el viaje vía agente IA',
            ]);

            // 3. Actualizar la tabla llamadas si existe
            $llamada = \App\Models\Llamada::where('elevenlabs_conversation_id', $conversationId)->first();
            if ($llamada) {
                $llamada->update([
                    'status' => $decision == 1
                        ? \App\Models\Llamada::STATUS_ACEPTADA
                        : \App\Models\Llamada::STATUS_RECHAZADA,
                    'call_status' => 'completed',
                    'call_completed_at' => now(),
                ]);
            }

            // 4. Si aceptó, marcar en la cotización
            if ($decision == 1 && ($cotizacionModelId || $conductor?->cotizacion_id)) {
                $cotId = $cotizacionModelId ?? $conductor->cotizacion_id;
                $cotizacion = CotizacionModel::find($cotId);
                if ($cotizacion && !$cotizacion->selected_driver_id) {
                    $cotizacion->update([
                        'selected_driver_id' => $driverId ?? $conductor?->id
                    ]);
                }
            }

            Log::info('ElevenLabs Tool: save_driver_decision COMPLETADO', [
                'driver_call_response_id' => $callResponse->id,
                'decision' => $responseStatus,
                'conductor_id' => $conductor?->id,
                'cotizacion_id' => $cotizacionModelId ?? $conductor?->cotizacion_id
            ]);

            return response()->json([
                'success' => true,
                'message' => $decision == 1
                    ? 'Decisión registrada: Conductor ACEPTÓ el viaje'
                    : 'Decisión registrada: Conductor RECHAZÓ el viaje',
                'decision' => $responseStatus,
                'driver_call_response_id' => $callResponse->id
            ]);

        } catch (\Exception $e) {
            Log::error('ElevenLabs Tool: Error en save_driver_decision', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
