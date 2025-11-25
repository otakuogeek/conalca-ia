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

        /* 2. Total de conductores con ese tipo de vehículo */
        $totalToCall = \App\Models\VehicleOwnerHolderDriver::whereRaw(
            'LOWER(clasevehiculo)=?',
            [ strtolower($cot->vehiculo_requerido) ]
        )->count();

        /* 3. Conductores que aceptaron
            decision puede ser:
            • 'aceptado'  (si la columna es VARCHAR)
            • 1           (si la columna es TINYINT)          */
        $acceptedIds = \App\Models\CallDriverDecision::where(
                            'cotizacion_model_id', $cotizacionId)
                        ->where(function ($q) {
                            $q->where('decision', 'aceptado')
                            ->orWhere('decision', 1);       // ← NUEVO
                        })
                        ->pluck('driver_id');

        /* 4. Datos de esos conductores */
        $acceptedDrivers = \App\Models\VehicleOwnerHolderDriver::whereIn('id', $acceptedIds)
                            ->select(['id', 'Conductor', 'Telefonoconductor', 'Telefonopropietario', 'Telefonoposeedor'])
                            ->get();

        /* 5. Respuesta para el front */
        return response()->json([
            'prompt'              => $this->buildPrompt($cot),
            'vehicle_type'        => $cot->vehiculo_requerido,
            'total_to_call'       => $totalToCall,
            'accepted'            => $acceptedDrivers->map(fn ($d) => [
                                        'id'    => $d->id,
                                        'name'  => $d->Conductor ?: 'Sin nombre',
                                        'phone' => $d->Telefonoconductor ?: $d->Telefonopropietario ?: $d->Telefonoposeedor ?: 'Sin teléfono',
                                    ]),
            'percentage'          => $totalToCall
                                    ? round(($acceptedDrivers->count() / $totalToCall) * 100, 2)
                                    : 0,
            'selected_driver_id'  => $cot->selected_driver_id,
        ]);
    }

    public function selectDriver(Request $request, $cotizacionId)
    {
        $validated = $request->validate([
            'driver_id' => ['required', 'integer', 'exists:vehicle_owner_holder_driver,id'],
        ]);

        $cot = CotizacionModel::findOrFail($cotizacionId);

        // (Opcional pero recomendado) Validar que el conductor realmente aceptó esta cotización
        $accepted = \App\Models\CallDriverDecision::where('cotizacion_model_id', $cotizacionId)
            ->where('driver_id', $validated['driver_id'])
            ->where('decision', 'aceptado')
            ->exists();

        if (!$accepted) {
            return response()->json([
                'message' => 'El conductor no ha aceptado esta oferta para esta cotización.'
            ], 422);
        }

        $cot->selected_driver_id = $validated['driver_id'];
        $cot->save();

        return response()->json([
            'ok'                 => true,
            'selected_driver_id' => $cot->selected_driver_id
        ]);
    }

    private function buildPrompt($cot)
    {
        return "Este flete consiste en transportar {$cot->tipo_producto} desde "
             . "{$cot->ciudad_origen} hasta {$cot->ciudad_destino} con peso "
             . "{$cot->peso_mercancia} kg.";
    }
}
