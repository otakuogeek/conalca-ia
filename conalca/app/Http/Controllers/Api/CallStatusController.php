<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VehicleOwnerHolderDriver;
use App\Models\CotizacionModel;
use App\Models\CallDriverDecision;

class CallStatusController extends Controller
{
    // public function show($cotizacionId)
    // {
    //     // nº total de conductores que se podrían llamar
    //     $cot = CotizacionModel::findOrFail($cotizacionId);
    //     $totalToCall = VehicleOwnerHolderDriver::whereRaw(
    //         'LOWER(clasevehiculo)=?',
    //         [strtolower($cot->vehiculo_requerido)]
    //     )->count();

    //     // conductores que YA aceptaron (tabla call_driver_decisions)
    //     $acceptedIds = CallDriverDecision::where([
    //                         'cotizacion_model_id' => $cotizacionId,
    //                         'decision'            => 'aceptado'
    //                     ])->pluck('driver_id');

    //     $acceptedDrivers = VehicleOwnerHolderDriver::whereIn('id',$acceptedIds)
    //                         ->get(['id','conductor as name',
    //                             \DB::raw("COALESCE(telefonoconductor,
    //                                         telefonopropietario,
    //                                         telefonoposeedor) as phone_number")]);

    //     return response()->json([
    //         'prompt'        => $this->buildPrompt($cot),
    //         'vehicle_type'  => $cot->vehiculo_requerido,
    //         'total_to_call' => $totalToCall,
    //         'accepted'      => $acceptedDrivers->map(fn($d)=>[
    //                             'id'   => $d->id,
    //                             'name' => $d->name,
    //                             'phone'=> $d->phone_number
    //                         ]),
    //         'percentage'    => $totalToCall
    //                         ? $acceptedDrivers->count() / $totalToCall * 100
    //                         : 0
    //     ]);
    // }

    // public function show($cotizacionId)
    // {
    //     $cot = CotizacionModel::findOrFail($cotizacionId);

    //     $totalToCall = \App\Models\VehicleOwnerHolderDriver::whereRaw(
    //         'LOWER(clasevehiculo)=?',
    //         [strtolower($cot->vehiculo_requerido)]
    //     )->count();

    //     $acceptedIds = \App\Models\CallDriverDecision::where([
    //                         'cotizacion_model_id' => $cotizacionId,
    //                         'decision'            => 'aceptado'
    //                     ])->pluck('driver_id');

    //     $acceptedDrivers = \App\Models\VehicleOwnerHolderDriver::whereIn('id', $acceptedIds)
    //                         ->get([
    //                             'id',
    //                             'conductor as name',
    //                             \DB::raw("COALESCE(telefonoconductor,
    //                                                 telefonopropietario,
    //                                                 telefonoposeedor) as phone_number")
    //                         ]);

    //     return response()->json([
    //         'prompt'              => $this->buildPrompt($cot),
    //         'vehicle_type'        => $cot->vehiculo_requerido,
    //         'total_to_call'       => $totalToCall,
    //         'accepted'            => $acceptedDrivers->map(fn($d)=>[
    //                                 'id'    => $d->id,
    //                                 'name'  => $d->name,
    //                                 'phone' => $d->phone_number
    //                                 ]),
    //         'percentage'          => $totalToCall
    //                                 ? $acceptedDrivers->count() / $totalToCall * 100
    //                                 : 0,
    //         'selected_driver_id'  => $cot->selected_driver_id, // <- agregado
    //     ]);
    // }

    public function show($cotizacionId)
    {
        /* 1. Cotización */
        $cot = \App\Models\CotizacionModel::findOrFail($cotizacionId);

        $llamadas = \App\Models\Llamada::query()
            ->byCotizacion($cotizacionId)
            ->with('conductor:id,nombre_conductor,telefono,placa,tipo_vehiculo')
            ->orderBy('batch_number')
            ->orderBy('batch_position')
            ->orderByDesc('id_llamada')
            ->get();

        /* 2. Total de conductores con llamadas realmente registradas (tabla llamadas) */
        $totalToCall = $llamadas
            ->pluck('conductor_id')
            ->filter()
            ->unique()
            ->count();

        $queueSummary = [
            'registered' => $llamadas->count(),
            'pending' => $llamadas->where('queue_status', 'pending')->count(),
            'processing' => $llamadas->where('queue_status', 'processing')->count(),
            'completed' => $llamadas->where('queue_status', 'completed')->count(),
            'failed' => $llamadas->where('queue_status', 'failed')->count(),
            'cancelled' => $llamadas->where('queue_status', 'cancelled')->count(),
        ];

        $queueSummary['finished'] = $queueSummary['completed'] + $queueSummary['failed'] + $queueSummary['cancelled'];
        $queueSummary['progress_percentage'] = $queueSummary['registered'] > 0
            ? round(($queueSummary['finished'] / $queueSummary['registered']) * 100, 2)
            : 0;
        $queueSummary['active'] = $queueSummary['processing'] > 0 || $queueSummary['pending'] > 0;

        $totalRegistered = $llamadas->count();
        $totalBatches = $llamadas->max('batch_number') ?? 1;

        $recentCalls = $llamadas->take(10)->map(function ($llamada) use ($totalRegistered, $totalBatches) {
            $conductor = $llamada->conductor;

            // Calcular posición global en la cola
            $globalPosition = null;
            if ($llamada->batch_number && $llamada->batch_position) {
                $globalPosition = (($llamada->batch_number - 1) * 2) + $llamada->batch_position;
            }

            return [
                'id_llamada' => $llamada->id_llamada,
                'driver_id' => $llamada->conductor_id,
                'driver_name' => $conductor?->nombre_conductor ?? 'Sin nombre',
                'driver_phone' => $conductor?->telefono ?? $llamada->numero_destino,
                'placa' => $conductor?->placa,
                'tipo_vehiculo' => $conductor?->tipo_vehiculo,
                'status' => $llamada->status,
                'queue_status' => $llamada->queue_status,
                'call_status' => $llamada->call_status,
                'batch_number' => $llamada->batch_number,
                'batch_position' => $llamada->batch_position,
                'total_batches' => $totalBatches,
                'global_position' => $globalPosition,
                'total_registered' => $totalRegistered,
                'queued_at' => $llamada->queued_at?->format('Y-m-d H:i:s'),
                'processing_started_at' => $llamada->processing_started_at?->format('Y-m-d H:i:s'),
                'processing_completed_at' => $llamada->processing_completed_at?->format('Y-m-d H:i:s'),
                'call_started_at' => $llamada->call_started_at?->format('Y-m-d H:i:s'),
                'call_completed_at' => $llamada->call_completed_at?->format('Y-m-d H:i:s'),
                'failure_reason' => $llamada->failure_reason,
                'call_notes' => $llamada->call_notes,
            ];
        })->values();

        /* 3. Conductores que aceptaron:
           - Primero busca vía relación driverCallResponse (response_status = 'accepted')
           - Luego busca directamente en llamadas_conductores (respuesta_llamada LIKE '%acepta%')
           - Combina ambos resultados sin duplicados, máximo 7 */
        $acceptedViaResponse = \App\Models\LlamadaConductor::query()
            ->where('cotizacion_id', $cotizacionId)
            ->whereHas('driverCallResponse', function ($query) {
                $query->where('response_status', 'accepted');
            })
            ->pluck('id')
            ->toArray();

        $acceptedViaLlamada = \App\Models\LlamadaConductor::query()
            ->where('cotizacion_id', $cotizacionId)
            ->where(function ($q) {
                $q->where('respuesta_llamada', 'LIKE', '%acepta%')
                  ->orWhere('respuesta_llamada', 'LIKE', '%Acepta%');
            })
            ->pluck('id')
            ->toArray();

        $acceptedIds = array_unique(array_merge($acceptedViaResponse, $acceptedViaLlamada));

        $acceptedDrivers = \App\Models\LlamadaConductor::query()
            ->whereIn('id', $acceptedIds)
            ->select([
                'id',
                'nombre_conductor',
                'telefono',
                'placa',
                'tipo_vehiculo',
                'ciudad_origen',
                'ciudad_destino',
                'cotizacion_id',
                'estado_llamada',
                'respuesta_llamada',
                'fecha_llamada',
            ])
            ->orderByDesc('fecha_llamada')
            ->limit(7)
            ->get();

        /* 4. Conductores en TAL VEZ / seguimiento:
           - response_status = 'pending' en driver_call_responses
           - o respuesta_llamada = 'pending' en llamadas_conductores
           - sin mezclar con los aceptados */
        $maybeViaResponse = \App\Models\LlamadaConductor::query()
            ->where('cotizacion_id', $cotizacionId)
            ->whereHas('driverCallResponse', function ($query) {
                $query->where('response_status', 'pending');
            })
            ->pluck('id')
            ->toArray();

        $maybeViaLlamada = \App\Models\LlamadaConductor::query()
            ->where('cotizacion_id', $cotizacionId)
            ->where('respuesta_llamada', 'pending')
            ->pluck('id')
            ->toArray();

        $maybeIds = array_values(array_diff(array_unique(array_merge($maybeViaResponse, $maybeViaLlamada)), $acceptedIds));

        $maybeDrivers = \App\Models\LlamadaConductor::query()
            ->whereIn('id', $maybeIds)
            ->select([
                'id',
                'nombre_conductor',
                'telefono',
                'placa',
                'tipo_vehiculo',
                'ciudad_origen',
                'ciudad_destino',
                'cotizacion_id',
                'estado_llamada',
                'respuesta_llamada',
                'fecha_llamada',
            ])
            ->orderByDesc('fecha_llamada')
            ->limit(7)
            ->get();

        /* 5. Respuesta para el front */
        return response()->json([
            'prompt'              => $this->buildPrompt($cot),
            'vehicle_type'        => $cot->vehiculo_requerido,
            'ciudad_origen'       => $cot->ciudad_origen,
            'ciudad_destino'      => $cot->ciudad_destino,
            'total_to_call'       => $totalToCall,
            'call_execution'      => [
                'summary' => $queueSummary,
                'recent_calls' => $recentCalls,
                'last_updated' => now()->format('Y-m-d H:i:s'),
            ],
            'accepted'            => $acceptedDrivers->map(function ($d) {
                                        return [
                                            'id'    => $d->id,
                                            'name'  => $d->nombre_conductor ?: 'Sin nombre',
                                            'phone' => $d->telefono ?: 'Sin teléfono',
                                            'placa' => $d->placa ?: 'Sin placa',
                                            'tipo_vehiculo' => $d->tipo_vehiculo,
                                            'ciudad_origen' => $d->ciudad_origen,
                                            'ciudad_destino' => $d->ciudad_destino,
                                            'decision_date' => $d->fecha_llamada
                                                ? $d->fecha_llamada->format('Y-m-d H:i:s')
                                                : null,
                                        ];
                                    }),
            'total_accepted'      => count($acceptedIds),
            'maybe'               => $maybeDrivers->map(function ($d) {
                                        return [
                                            'id'    => $d->id,
                                            'name'  => $d->nombre_conductor ?: 'Sin nombre',
                                            'phone' => $d->telefono ?: 'Sin teléfono',
                                            'placa' => $d->placa ?: 'Sin placa',
                                            'tipo_vehiculo' => $d->tipo_vehiculo,
                                            'ciudad_origen' => $d->ciudad_origen,
                                            'ciudad_destino' => $d->ciudad_destino,
                                            'decision_date' => $d->fecha_llamada
                                                ? $d->fecha_llamada->format('Y-m-d H:i:s')
                                                : null,
                                        ];
                                    }),
            'total_maybe'         => count($maybeIds),
            'percentage'          => $totalToCall > 0
                                    ? round((count($acceptedIds) / $totalToCall) * 100, 2)
                                    : 0,
            'selected_driver_id'  => $cot->selected_driver_id,
        ]);
    }

    public function selectDriver(Request $request, $cotizacionId)
    {
        $validated = $request->validate([
            'driver_id' => ['required', 'integer', 'exists:llamadas_conductores,id'],
        ]);

        $cot = CotizacionModel::findOrFail($cotizacionId);

        // Validar que el conductor existe en llamadas_conductores y aceptó esta cotización
        // Busca tanto por driverCallResponse como por respuesta_llamada directa
        $conductor = \App\Models\LlamadaConductor::where('id', $validated['driver_id'])
            ->where('cotizacion_id', $cotizacionId)
            ->where(function ($q) {
                $q->whereHas('driverCallResponse', function ($query) {
                    $query->where('response_status', 'accepted');
                })
                ->orWhere('respuesta_llamada', 'LIKE', '%acepta%');
            })
            ->first();

        if (!$conductor) {
            return response()->json([
                'message' => 'El conductor no ha aceptado esta oferta para esta cotización.'
            ], 422);
        }

        $cot->selected_driver_id = $validated['driver_id'];
        $cot->save();

        return response()->json([
            'ok'                 => true,
            'selected_driver_id' => $cot->selected_driver_id,
            'conductor_nombre'   => $conductor->nombre_conductor,
            'conductor_telefono' => $conductor->telefono,
            'conductor_placa'    => $conductor->placa,
        ]);
    }

    /**
     * Obtener detalles completos de un conductor por ID
     */
    public function getDriverDetails($driverId)
    {
        $conductor = \App\Models\LlamadaConductor::find($driverId);

        if (!$conductor) {
            return response()->json([
                'message' => 'Conductor no encontrado'
            ], 404);
        }

        return response()->json([
            'id' => $conductor->id,
            'nombre_conductor' => $conductor->nombre_conductor,
            'telefono' => $conductor->telefono,
            'placa' => $conductor->placa,
            'tipo_vehiculo' => $conductor->tipo_vehiculo,
            'ciudad_origen' => $conductor->ciudad_origen,
            'ciudad_destino' => $conductor->ciudad_destino,
            'cotizacion_id' => $conductor->cotizacion_id,
            'estado_llamada' => $conductor->estado_llamada,
            'respuesta_llamada' => $conductor->respuesta_llamada,
            'fecha_llamada' => $conductor->fecha_llamada,
        ]);
    }

    private function buildPrompt($cot)
    {
        return "Este flete consiste en transportar {$cot->tipo_producto} desde "
             . "{$cot->ciudad_origen} hasta {$cot->ciudad_destino} con peso "
             . "{$cot->peso_mercancia} kg.";
    }
}
