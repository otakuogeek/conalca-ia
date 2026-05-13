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
     * Tool: get_contexto_inicial_conductor
     *
     * Devuelve en una sola respuesta el conductor, la cotización asociada
     * y el precio del flete para reducir latencia en ElevenLabs.
     */
    public function getContextoInicialConductor(Request $request)
    {
        try {
            $telefono = $request->input('telefono');
            $conversationId = $request->input('conversation_id');

            if (!$telefono) {
                return response()->json([
                    'success' => false,
                    'modo' => 'NO_ENCONTRADO',
                    'error' => 'Parámetro telefono requerido',
                ], 400);
            }

            $telefonoLimpio = $this->normalizePhone($telefono);

            Log::info('ElevenLabs Tool: get_contexto_inicial_conductor', [
                'telefono_original' => $telefono,
                'telefono_limpio' => $telefonoLimpio,
                'conversation_id' => $conversationId,
            ]);

            // Estrategia estricta para evitar cotizaciones cruzadas (problema
            // detectado con números de prueba reusados, ej. caso 1969 / SOO360):
            //
            // 1) Si llega conversation_id intentar SIEMPRE resolver primero
            //    por ese id (LlamadaConductor + Llamada::elevenlabs_conversation_id).
            // 2) Solo si NO llega conversation_id se permite fallback por teléfono,
            //    y en ese caso se exige que el conductor tenga estado en progreso
            //    o pendiente reciente para no agarrar una cotización vieja.
            $conductor = $conversationId ? $this->findConductorByConversationId($conversationId) : null;
            $fuenteBusqueda = $conductor ? 'conversation_id' : null;

            if (!$conductor) {
                if ($conversationId) {
                    Log::warning('ElevenLabs Tool: conversation_id no resolvió conductor; se intenta fallback estricto por teléfono', [
                        'conversation_id' => $conversationId,
                        'telefono' => $telefonoLimpio,
                    ]);
                }

                $conductor = $this->findLatestConductorByPhone($telefonoLimpio);
                $fuenteBusqueda = $conductor ? 'telefono_fallback' : null;
            }

            if (!$conductor) {
                Log::warning('ElevenLabs Tool: NO_ENCONTRADO definitivo', [
                    'telefono' => $telefonoLimpio,
                    'conversation_id' => $conversationId,
                ]);

                return response()->json([
                    'success' => false,
                    'modo' => 'NO_ENCONTRADO',
                    'conductor' => null,
                    'cotizacion' => null,
                    'precio' => null,
                ]);
            }

            $cotizacion = $conductor->cotizacion;
            $precio = $this->buildPrecioPayload($cotizacion);
            $llamada = $conversationId
                ? \App\Models\Llamada::where('elevenlabs_conversation_id', $conversationId)->first()
                : null;
            $conversationIdCoincide = !$conversationId
                || $conversationId === $conductor->elevenlabs_conversation_id
                || $conversationId === $conductor->call_id
                || ($llamada && (int) $llamada->conductor_id === (int) $conductor->id);
            $telefonoCoincide = $this->phonesMatch($telefono, $conductor->telefono)
                || ($llamada && $this->phonesMatch($telefono, $llamada->numero_destino));

            return response()->json([
                'success' => true,
                'modo' => $cotizacion ? 'OFERTA_CONCRETA' : 'BUSQUEDA_DISPONIBILIDAD',
                'conversation_id' => $conversationId,
                'telefono_buscado' => $telefono,
                'telefono_normalizado' => $telefonoLimpio,
                'validacion' => [
                    'fuente_busqueda' => $fuenteBusqueda,
                    'telefono_coincide' => $telefonoCoincide,
                    'conversation_id_coincide' => $conversationIdCoincide,
                ],
                'conductor' => [
                    'id' => $conductor->id,
                    'identificador_unico' => $conductor->identificador_unico,
                    'nombre_conductor' => $conductor->nombre_conductor,
                    'placa' => $conductor->placa,
                    'tipo_vehiculo' => $conductor->tipo_vehiculo ?? $conductor->clase_vehiculo,
                    'ciudad_actual' => $conductor->ciudad_actual ?? $conductor->ciudad,
                    'cotizacion_id' => $conductor->cotizacion_id,
                    'telefono' => $conductor->telefono,
                    'elevenlabs_conversation_id' => $conductor->elevenlabs_conversation_id,
                    'call_id' => $conductor->call_id,
                    'llamada_id' => $llamada?->id_llamada,
                    'numero_destino' => $llamada?->numero_destino,
                ],
                'cotizacion' => $this->buildCotizacionPayload($cotizacion),
                'precio' => $precio,
            ]);
        } catch (\Exception $e) {
            Log::error('ElevenLabs Tool: Error en get_contexto_inicial_conductor', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'modo' => 'NO_ENCONTRADO',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

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
                        'flete' => $cotizacion->flete,
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
                    'flete' => $c->flete,
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

            // El valor que se le paga al conductor es el FLETE
            $fleteNumerico = floatval($cotizacion->flete ?? 0);
            $fleteFormateado = '$' . number_format($fleteNumerico, 0, ',', '.');

            return response()->json([
                'success' => true,
                'cotizacion_id' => $cotizacion->id,
                'valor_flete' => $fleteNumerico,
                'valor_flete_formateado' => $fleteFormateado,
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
            $decision = $request->input('decision');
            $conversationId = $request->input('conversation_id');
            $cotizacionModelId = $request->input('cotizacion_model_id');
            $driverId = $request->input('driver_id');
            $identificadorUnico = $request->input('identificador_unico');
            $notes = trim((string) ($request->input('notas') ?? $request->input('notes') ?? ''));
            $retryAfterMinutes = max(1, (int) ($request->input('retry_after_minutes') ?? 10));
            $decisionState = $this->resolveDriverDecision($decision);
            $responseStatus = $decisionState['response_status'];
            $requiresRetry = $decisionState['requires_retry'];
            $defaultNotes = $notes !== '' ? $notes : $decisionState['default_notes'];

            Log::info('ElevenLabs Tool: save_driver_decision', [
                'decision' => $decision,
                'conversation_id' => $conversationId,
                'cotizacion_model_id' => $cotizacionModelId,
                'driver_id' => $driverId,
                'identificador_unico' => $identificadorUnico,
                'response_status' => $responseStatus,
                'requires_retry' => $requiresRetry,
            ]);

            if ($decision === null || !$conversationId) {
                return response()->json([
                    'error' => 'Parámetros requeridos: decision, conversation_id'
                ], 400);
            }

            // 1. Buscar el conductor en llamadas_conductores
            $conductor = null;
            if ($driverId) {
                $conductor = LlamadaConductor::find($driverId);
            }

            if (!$conductor && $identificadorUnico) {
                $conductor = LlamadaConductor::where('identificador_unico', $identificadorUnico)->first();
            }

            // Si no lo encontramos por ID, buscar por conversation_id
            if (!$conductor && $conversationId) {
                $conductor = $this->findConductorByConversationId($conversationId);
            }

            $llamada = \App\Models\Llamada::where('elevenlabs_conversation_id', $conversationId)->first();

            if (!$conductor) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se encontró conductor para guardar la decisión',
                ], 404);
            }

            if ($identificadorUnico && $conductor->identificador_unico !== $identificadorUnico) {
                Log::warning('ElevenLabs Tool: identificador_unico no coincide con conductor resuelto', [
                    'identificador_recibido' => $identificadorUnico,
                    'identificador_conductor' => $conductor->identificador_unico,
                    'conversation_id' => $conversationId,
                    'conductor_id' => $conductor->id,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'El identificador_unico no coincide con el conductor de la conversación',
                ], 409);
            }

            $conversationMatches = !$conversationId
                || $conversationId === $conductor->elevenlabs_conversation_id
                || $conversationId === $conductor->call_id
                || ($llamada && (int) $llamada->conductor_id === (int) $conductor->id);

            if (!$conversationMatches) {
                Log::warning('ElevenLabs Tool: conversation_id no coincide con conductor resuelto', [
                    'conversation_id' => $conversationId,
                    'conductor_id' => $conductor->id,
                    'llamada_conductor_id' => $llamada?->conductor_id,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'conversation_id no coincide con el conductor; decisión no guardada',
                ], 409);
            }

            $legacyDriverId = $conductor?->chofer_id_local;
            $resolvedDriverId = $legacyDriverId ?? $driverId ?? $conductor?->id;
            $resolvedCotizacionId = $cotizacionModelId ?? $conductor?->cotizacion_id;

            if ($requiresRetry) {
                if ($conductor) {
                    $conductor->update([
                        'estado_llamada' => 'pendiente',
                        'fecha_llamada' => now(),
                        'notas' => $defaultNotes,
                    ]);
                }

                if ($llamada) {
                    $callMetadata = is_array($llamada->call_metadata) ? $llamada->call_metadata : [];
                    $callMetadata['agent_followup'] = 'retry';
                    $callMetadata['retry_after_minutes'] = $retryAfterMinutes;
                    $callMetadata['retry_reason'] = $defaultNotes;
                    $callMetadata['retry_requested_at'] = now()->toISOString();

                    $llamada->update([
                        'call_metadata' => $callMetadata,
                        'internal_notes' => trim(($llamada->internal_notes ?? '') . "\n" . '[Agent] Reintento solicitado: ' . $defaultNotes),
                        'call_notes' => $defaultNotes,
                    ]);
                }

                Log::info('ElevenLabs Tool: save_driver_decision marcado para reintento', [
                    'conversation_id' => $conversationId,
                    'conductor_id' => $conductor?->id,
                    'retry_after_minutes' => $retryAfterMinutes,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Llamada marcada para reintento',
                    'decision' => 'retry',
                ]);
            }

            $callResponse = DriverCallResponse::updateOrCreate(
                ['elevenlabs_conversation_id' => $conversationId],
                [
                    'cotizacion_id' => $resolvedCotizacionId,
                    'driver_id' => $resolvedDriverId,
                    'driver_name' => $conductor?->nombre_conductor ?? 'Desconocido',
                    'driver_phone' => $conductor?->telefono,
                    'vehicle_type' => $conductor?->tipo_vehiculo ?? $conductor?->clase_vehiculo,
                    'vehicle_plate' => $conductor?->placa,
                    'call_status' => 'completed',
                    'response_status' => $responseStatus,
                    'response_time' => now(),
                    'notes' => $defaultNotes,
                ]
            );

            if ($conductor) {
                $conductor->update([
                    'estado_llamada' => 'completada',
                    'respuesta_llamada' => $responseStatus,
                    'notas' => $defaultNotes,
                    'driver_call_response_id' => $callResponse->id,
                ]);

                Log::info('Conductor actualizado en llamadas_conductores', [
                    'conductor_id' => $conductor->id,
                    'nombre' => $conductor->nombre_conductor,
                    'decision' => $responseStatus
                ]);
            }

            // 2. Actualizar la tabla llamadas si existe
            if ($llamada) {
                $llamada->update([
                    'status' => $responseStatus === 'accepted'
                        ? \App\Models\Llamada::STATUS_ACEPTADA
                        : ($responseStatus === 'rejected'
                            ? \App\Models\Llamada::STATUS_RECHAZADA
                            : \App\Models\Llamada::STATUS_FINALIZADA),
                    'call_status' => 'completed',
                    'call_completed_at' => now(),
                    'call_notes' => $defaultNotes,
                ]);
            }

            // 3. Si aceptó, marcar en la cotización
            if ($responseStatus === 'accepted' && $resolvedCotizacionId) {
                $cotId = $resolvedCotizacionId;
                $cotizacion = CotizacionModel::find($cotId);
                if ($cotizacion && !$cotizacion->selected_driver_id && $legacyDriverId) {
                    $cotizacion->update([
                        'selected_driver_id' => $legacyDriverId
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
                'message' => match ($responseStatus) {
                    'accepted' => 'Decisión registrada: Conductor ACEPTÓ el viaje',
                    'rejected' => 'Decisión registrada: Conductor RECHAZÓ el viaje',
                    default => 'Decisión registrada: Pendiente de seguimiento',
                },
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

    private function normalizePhone(?string $telefono): ?string
    {
        if (!$telefono) {
            return null;
        }

        $telefonoLimpio = preg_replace('/[^0-9]/', '', $telefono);

        if (strlen($telefonoLimpio) > 10 && str_starts_with($telefonoLimpio, '57')) {
            return substr($telefonoLimpio, 2);
        }

        return $telefonoLimpio;
    }

    /**
     * Devuelve las variantes de un teléfono limpio que pueden coexistir en
     * la base de datos (con/sin prefijo país, con/sin '+'). Se usa para
     * matchear teléfonos exactos en vez de un LIKE laxo que mete falsos
     * positivos.
     */
    private function phoneVariants(string $telefonoLimpio): array
    {
        $variantes = array_unique(array_filter([
            $telefonoLimpio,
            '57' . $telefonoLimpio,
            '+57' . $telefonoLimpio,
            substr($telefonoLimpio, -10),
        ]));

        return array_values($variantes);
    }

    private function findLatestConductorByPhone(string $telefonoLimpio): ?LlamadaConductor
    {
        $variantes = $this->phoneVariants($telefonoLimpio);

        // Primero: conductor con llamada en progreso o pendiente reciente
        // (creada en la última hora). Esto evita que un teléfono de prueba
        // reusado agarre una cotización vieja de otro grupo.
        $conductor = LlamadaConductor::whereIn('telefono', $variantes)
            ->whereIn('estado_llamada', ['en_progreso', 'pendiente'])
            ->where('created_at', '>=', now()->subHour())
            ->orderBy('created_at', 'desc')
            ->first();

        if ($conductor) {
            return $conductor;
        }

        // Segundo: el más reciente con match exacto a alguna variante,
        // priorizando estados activos.
        return LlamadaConductor::whereIn('telefono', $variantes)
            ->orderByRaw("CASE WHEN estado_llamada IN ('en_progreso', 'pendiente') THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->first();
    }

    private function findConductorByConversationId(?string $conversationId): ?LlamadaConductor
    {
        if (!$conversationId) {
            return null;
        }

        $conductor = LlamadaConductor::where(function ($query) use ($conversationId) {
                $query->where('elevenlabs_conversation_id', $conversationId)
                    ->orWhere('call_id', $conversationId);
            })
            ->with('cotizacion')
            ->first();

        if ($conductor) {
            return $conductor;
        }

        $llamada = \App\Models\Llamada::where('elevenlabs_conversation_id', $conversationId)->first();

        if (!$llamada || !$llamada->conductor_id) {
            return null;
        }

        return LlamadaConductor::where('id', $llamada->conductor_id)
            ->with('cotizacion')
            ->first();
    }

    private function phonesMatch($left, $right): bool
    {
        $leftClean = $this->normalizePhone($left) ?? '';
        $rightClean = $this->normalizePhone($right) ?? '';

        if ($leftClean === '' || $rightClean === '') {
            return false;
        }

        if ($leftClean === $rightClean) {
            return true;
        }

        if (strlen($leftClean) >= 10 && strlen($rightClean) >= 10) {
            return substr($leftClean, -10) === substr($rightClean, -10);
        }

        return str_ends_with($leftClean, $rightClean) || str_ends_with($rightClean, $leftClean);
    }

    private function buildCotizacionPayload(?CotizacionModel $cotizacion): ?array
    {
        if (!$cotizacion) {
            return null;
        }

        return [
            'id' => $cotizacion->id,
            'group_cotization_id' => $cotizacion->group_cotization_id,
            'ciudad_origen' => $cotizacion->ciudad_origen,
            'ciudad_destino' => $cotizacion->ciudad_destino,
            'peso_mercancia' => $cotizacion->peso_mercancia,
            'tipo_mercancia' => $cotizacion->tipo_mercancia,
            'tipo_embajale' => $cotizacion->tipo_embajale,
            'vehiculo_requerido' => $cotizacion->vehiculo_requerido,
            'tipo_carroceria' => $cotizacion->tipo_carroceria,
            'fecha_cargue' => $this->formatQuoteDate($cotizacion->fecha_hora_descargue_cargue),
            'hora_cargue'   => $this->formatLoadingTime($cotizacion->fecha_hora_descargue_cargue),
            'fecha_descargue' => null,
        ];
    }

    private function buildPrecioPayload(?CotizacionModel $cotizacion): ?array
    {
        if (!$cotizacion) {
            return null;
        }

        $valorFlete = is_numeric($cotizacion->flete) ? (float) $cotizacion->flete : null;

        return [
            'valor_flete' => $valorFlete,
            'mensaje_precio' => $this->formatCurrency($valorFlete),
        ];
    }

    private function formatCurrency(?float $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        return '$' . number_format($valor, 0, ',', '.');
    }

    private function formatQuoteDate($fecha): ?string
    {
        if (!$fecha) {
            return null;
        }

        try {
            $fechaCargue = \Carbon\Carbon::parse($fecha, 'America/Bogota')->startOfDay();
            $hoy = \Carbon\Carbon::now('America/Bogota')->startOfDay();
            $delta = $hoy->diffInDays($fechaCargue, false);
            $dias = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
            $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            $nombreDia = $dias[$fechaCargue->dayOfWeekIso - 1];

            if ($delta === 0) {
                return 'hoy';
            }

            if ($delta === 1) {
                return 'mañana';
            }

            if ($delta >= 2) {
                return "el próximo {$nombreDia} {$fechaCargue->day}";
            }

            if ($delta === -1) {
                return 'ayer';
            }

            return "el {$nombreDia} {$fechaCargue->day} de {$meses[$fechaCargue->month - 1]}";
        } catch (\Throwable $e) {
            return (string) $fecha;
        }
    }

    private function formatLoadingTime($fecha): ?string
    {
        if (!$fecha) {
            return null;
        }

        try {
            $carbon = \Carbon\Carbon::parse($fecha);
            // Only return time if it is not midnight (00:00) — midnight means no time was specified
            if ($carbon->hour === 0 && $carbon->minute === 0) {
                return null;
            }
            return $carbon->format('g:i A');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function resolveDriverDecision($decision): array
    {
        if (is_bool($decision) || is_numeric($decision)) {
            return ((int) $decision) === 1
                ? [
                    'response_status' => 'accepted',
                    'requires_retry' => false,
                    'default_notes' => 'Aceptó el viaje',
                ]
                : [
                    'response_status' => 'rejected',
                    'requires_retry' => false,
                    'default_notes' => 'Rechazó la oferta',
                ];
        }

        $normalized = strtolower(trim((string) $decision));

        return match ($normalized) {
            '1', 'si', 'sí', 'accept', 'accepted', 'aceptado', 'acepta' => [
                'response_status' => 'accepted',
                'requires_retry' => false,
                'default_notes' => 'Aceptó el viaje',
            ],
            'retry', 'reintento', 'voicemail', 'buzon', 'buzón' => [
                'response_status' => 'pending',
                'requires_retry' => true,
                'default_notes' => 'Buzón de voz o contacto no logrado, reintentar',
            ],
            'maybe', 'tal_vez', 'talvez', 'indeciso', 'supervisor', 'pending' => [
                'response_status' => 'pending',
                'requires_retry' => false,
                'default_notes' => 'Pendiente de seguimiento por supervisor',
            ],
            default => [
                'response_status' => 'rejected',
                'requires_retry' => false,
                'default_notes' => 'Rechazó la oferta',
            ],
        };
    }
}
