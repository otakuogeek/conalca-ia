<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GroupCotization;
use App\Models\CotizacionModel;

class CotizacionClienteController extends Controller
{
    // Mostrar la vista pública
    public function responderVista($id)
    {
        $grupo = GroupCotization::with(['cotizaciones.pricing'])->findOrFail($id);

        // Filtrar solo cotizaciones con valor > 0
        $grupo->setRelation('cotizaciones', $grupo->cotizaciones->filter(function($cotizacion) {
            return $cotizacion->valor > 0;
        }));

        // Si ya fue respondida, puedes mostrar mensaje.
        if (
            $grupo->status === 'En facturación' || 
            $grupo->status === 'En tránsito' || 
            $grupo->status === 'Completada'
            ) {
            return view('quotes.publica_finalizada', compact('grupo'));
        }

        return view('quotes.publica_responder', compact('grupo'));
    }

    // Guardar la decisión del cliente
    public function guardarRespuestas(Request $request, $id)
    {
        $grupo = GroupCotization::with('cotizaciones')->findOrFail($id);

        // Filtrar solo cotizaciones con valor > 0
        $grupo->setRelation('cotizaciones', $grupo->cotizaciones->filter(function($cotizacion) {
            return $cotizacion->valor > 0;
        }));

        $data = $request->input('decisiones', []); // [cotizacion_id => 'aceptada'/'rechazada'/...]

        $cotizaciones = $grupo->cotizaciones;

        // Actualiza cada decisión...
        foreach ($cotizaciones as $cot) {
            $dec = $data[$cot->id] ?? 'pendiente';
            $cot->decision_cliente = $dec;
            $cot->save();
        }

        // ¿Cuántas aceptó?
        $aceptadas = $cotizaciones->where('decision_cliente', 'aceptada')->count();

        $grupo->status = $aceptadas > 0 ? 'aceptada' : 'rechazada';
        $grupo->save();

        // Puedes notificar al asesor aquí...

        return view('quotes.publica_finalizada', compact('grupo'));
    }

    // API endpoint para obtener datos del grupo
    public function getGroupData($id)
    {
        try {
            $grupo = GroupCotization::with(['cotizaciones.pricing', 'client'])->findOrFail($id);
            
            // Filtrar solo cotizaciones con valor > 0
            $grupo->setRelation('cotizaciones', $grupo->cotizaciones->filter(function($cotizacion) {
                return $cotizacion->valor > 0;
            }));
            
            return response()->json($grupo);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Grupo no encontrado'], 404);
        }
    }
}
