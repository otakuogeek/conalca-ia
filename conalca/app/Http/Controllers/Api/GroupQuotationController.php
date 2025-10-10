<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GroupCotization;
use Illuminate\Support\Facades\Log;

class GroupQuotationController extends Controller
{
    public function groupsQuotes()
    {
        $user = auth()->user();

        // Si es super admin, no filtramos
        $query = GroupCotization::with([
            'client',
            'cotizaciones.client',
            'cotizaciones.pricing',
            'cotizaciones.solicitud',
            'cotizaciones.notes.author'
        ])
        ->orderBy('created_at', 'desc');
        
        // Si NO es super admin, solo le mostramos sus grupos
        if ($user->role != 'SUPER ADMIN' && $user->role != 'GERENTE DE CUENTA') {
            $query->where('user_id', $user->id);
        }

        $groups_quotes = $query->get()
            ->map(function ($group) {
                   foreach ($group->cotizaciones as $cotizacion) {
                        Log::info('Solicitud asociada a cotización:', [
                            'cotizacion_id' => $cotizacion->id,
                            'solicitud' => $cotizacion->solicitud // Esto mostrará null o el objeto cargado
                        ]);
                    }
                $group->valor_total = $group->cotizaciones->where('decision_cliente','aceptada') 
                    ->sum(function ($quote) {
                        $precioBase = floatval($quote->pricing->price ?? 0);
                        $porcentaje = floatval($quote->porcentaje ?? 0);
                        return $precioBase + ($precioBase * $porcentaje / 100);
                });
                return $group;
            });

        return response()->json([
            'data' => $groups_quotes
        ]);
    }

    public function changeStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|string']);
        $group = GroupCotization::findOrFail($id);
        $group->status = $request->status; // Puede ser 'En tránsito', 'Pre-Solicitud', etc o el nombre de una columna custom
        $group->save();
        return response()->json(['success' => true]);
    }

    public function accept($id)
    {
        // ADD NOTIFICATION  ADVISER, THE CLIENT ACCEPT THE QUOTE
        $group = GroupCotization::findOrFail($id);
        $group->status = 'aceptada'; // o 'Aprobada', como quieras
        $group->save();

        // Puedes notificar al comercial aquí si quieres

        return view('quotes.result', [
            'mensaje' => '¡Gracias por aceptar la cotización! Nuestro equipo gestionará tu transporte lo antes posible.',
            'esAceptada' => true,
        ]);
    }

    public function cancel($id)
    {
        // ADD NOTIFICATION  ADVISER, THE CLIENT REFUSE THE QUOTE

        $group = GroupCotization::findOrFail($id);
        $group->status = 'rechazada'; // o 'Cancelada'
        $group->save();

        // Puedes notificar al comercial aquí si quieres

        return view('quotes.result', [
            'mensaje' => '¡Cotización rechazada! Hemos informado al asesor comercial para gestionar una nueva propuesta o aclarar tus inquietudes.',
            'esAceptada' => false,
        ]);
    }
}
