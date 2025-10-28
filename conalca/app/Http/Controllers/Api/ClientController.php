<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Buscar clientes por documento o nombre
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (empty($query) || strlen($query) < 2) {
            return response()->json([]);
        }

        $clients = Client::where(function($q) use ($query) {
            $q->where('documento', 'LIKE', "%{$query}%")
              ->orWhere('cliente', 'LIKE', "%{$query}%");
        })
        ->limit(10)
        ->get([
            'id',
            'documento', 
            'cliente',
            'ciudad',
            'telefono',
            'celular',
            'email',
            'direccion',
            'branch_office',
            'vendedor_nombre'
        ]);

        return response()->json($clients);
    }

    /**
     * Obtener cliente por documento específico
     */
    public function getByDocument(Request $request)
    {
        $documento = $request->get('documento');
        
        if (empty($documento)) {
            return response()->json(['error' => 'Documento requerido'], 400);
        }

        $client = Client::where('documento', $documento)->first([
            'id',
            'documento', 
            'cliente',
            'ciudad',
            'telefono',
            'celular',
            'email',
            'direccion',
            'branch_office',
            'vendedor_nombre'
        ]);

        if (!$client) {
            return response()->json(['error' => 'Cliente no encontrado'], 404);
        }

        return response()->json($client);
    }
}