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

        /* 2. Total de conductores registrados para esta cotización en llamadas_conductores */
        $totalToCall = \App\Models\LlamadaConductor::where('cotizacion_id', $cotizacionId)
            ->count();

        /* 3. Conductores que aceptaron desde driver_call_responses
            Relacionando con llamadas_conductores para obtener sus datos */
        $acceptedDrivers = \App\Models\LlamadaConductor::query()
            ->where('cotizacion_id', $cotizacionId)
            ->whereHas('driverCallResponse', function ($query) {
                $query->where('response_status', 'accepted');
            })
            ->with('driverCallResponse')
            ->select([
                'id',
                'nombre_conductor',
                'telefono',
                'placa',
                'tipo_vehiculo',
                'ciudad_origen',
                'ciudad_destino',
                'cotizacion_id',
                'driver_call_response_id'  // IMPORTANTE: necesario para cargar la relación
            ])
            ->get();

        /* 4. Respuesta para el front */
        return response()->json([
            'prompt'              => $this->buildPrompt($cot),
            'vehicle_type'        => $cot->vehiculo_requerido,
            'ciudad_origen'       => $cot->ciudad_origen,
            'ciudad_destino'      => $cot->ciudad_destino,
            'total_to_call'       => $totalToCall,
            'accepted'            => $acceptedDrivers->map(function ($d) {
                                        $decisionDate = null;
                                        if ($d->driverCallResponse) {
                                            if ($d->driverCallResponse->response_time) {
                                                $decisionDate = \Carbon\Carbon::parse($d->driverCallResponse->response_time)->format('Y-m-d H:i:s');
                                            } elseif ($d->driverCallResponse->created_at) {
                                                $decisionDate = $d->driverCallResponse->created_at->format('Y-m-d H:i:s');
                                            }
                                        }
                                        
                                        return [
                                            'id'    => $d->id,
                                            'name'  => $d->nombre_conductor ?: 'Sin nombre',
                                            'phone' => $d->telefono ?: 'Sin teléfono',
                                            'placa' => $d->placa ?: 'Sin placa',
                                            'tipo_vehiculo' => $d->tipo_vehiculo,
                                            'ciudad_origen' => $d->ciudad_origen,
                                            'ciudad_destino' => $d->ciudad_destino,
                                            'decision_date' => $decisionDate,
                                        ];
                                    }),
            'percentage'          => $totalToCall > 0
                                    ? round(($acceptedDrivers->count() / $totalToCall) * 100, 2)
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
        $conductor = \App\Models\LlamadaConductor::where('id', $validated['driver_id'])
            ->where('cotizacion_id', $cotizacionId)
            ->whereHas('driverCallResponse', function ($query) {
                $query->where('response_status', 'accepted');
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

    private function buildPrompt($cot)
    {
        return "Este flete consiste en transportar {$cot->tipo_producto} desde "
             . "{$cot->ciudad_origen} hasta {$cot->ciudad_destino} con peso "
             . "{$cot->peso_mercancia} kg.";
    }
}
