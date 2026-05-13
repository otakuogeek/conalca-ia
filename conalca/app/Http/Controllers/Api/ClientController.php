<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    /**
     * Crear un nuevo cliente automáticamente
     */
    public function store(Request $request)
    {
        $cliente = $request->input('cliente');
        $documento = $request->input('documento');
        
        if (empty($cliente) || empty($documento)) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente y documento son requeridos'
            ], 422);
        }

        // Verificar si ya existe
        $existe = Client::where('documento', $documento)->first();
        if ($existe) {
            return response()->json([
                'success' => true,
                'id' => $existe->id,
                'cliente' => $existe->cliente,
                'documento' => $existe->documento,
                'message' => 'Cliente ya existe'
            ]);
        }

        try {
            $client = Client::create([
                'cliente' => $cliente,
                'documento' => $documento,
                'telefono' => $request->input('telefono'),
                'celular' => $request->input('celular'),
                'email' => $request->input('email'),
                'direccion' => $request->input('direccion'),
                'ciudad' => $request->input('ciudad'),
                'branch_office' => $request->input('branch_office'),
            ]);

            return response()->json([
                'success' => true,
                'id' => $client->id,
                'cliente' => $client->cliente,
                'documento' => $client->documento,
                'message' => 'Cliente creado exitosamente'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear cliente: ' . $e->getMessage()
            ], 500);
        }
    }
}