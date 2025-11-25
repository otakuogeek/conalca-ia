<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ClientSearchController extends Controller
{
    /**
     * Búsqueda de clientes con autocompletado
     */
    public function search(Request $request)
    {
        try {
            $term = trim($request->get('q', ''));
            $limit = (int) $request->get('limit', 15);
            $limit = max(1, min($limit, 50)); // proteger recursos
            $preferStartsWith = filter_var($request->get('starts_with', null), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($preferStartsWith === null) {
                // Si no viene param, preferir startsWith cuando es 1 carácter
                $preferStartsWith = mb_strlen($term) === 1;
            }
            
            Log::info('Búsqueda de cliente iniciada', ['term' => $term]);
            
            if (empty($term)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Término de búsqueda requerido',
                    'clients' => [],
                    'total' => 0
                ]);
            }

            // Aceptar búsquedas desde 1 carácter

            // Realizar búsqueda en múltiples campos
            $clients = Client::where(function($query) use ($term) {
                $query->where('documento', 'LIKE', "%{$term}%")
                      ->orWhere('cliente', 'LIKE', "%{$term}%")
                      ->orWhere('email', 'LIKE', "%{$term}%")
                      ->orWhere('telefono', 'LIKE', "%{$term}%")
                      ->orWhere('ciudad', 'LIKE', "%{$term}%")
                      ->orWhere('contacto', 'LIKE', "%{$term}%");
            })
            ->whereNotNull('cliente')
            ->where('cliente', '!=', '')
            // Priorizar prefijos cuando aplica
            ->orderByRaw(
                "CASE 
                    WHEN documento LIKE ? THEN 1
                    WHEN cliente LIKE ? THEN 2
                    WHEN email LIKE ? THEN 3
                    WHEN telefono LIKE ? THEN 4
                    WHEN ciudad LIKE ? THEN 5
                    WHEN contacto LIKE ? THEN 6
                    ELSE 7
                END",
                [
                    ($preferStartsWith ? $term . '%' : '%' . $term . '%'),
                    ($preferStartsWith ? $term . '%' : '%' . $term . '%'),
                    '%' . $term . '%',
                    '%' . $term . '%',
                    '%' . $term . '%',
                    '%' . $term . '%',
                ]
            )
            ->limit($limit)
            ->get();

            // Formatear resultados de forma simple
            $formattedClients = [];
            foreach($clients as $client) {
                $formattedClients[] = [
                    'id' => $client->id,
                    'documento' => $client->documento ?? '',
                    'cliente' => $client->cliente ?? 'Sin nombre',
                    'email' => $client->email ?? '',
                    'telefono' => $client->telefono ?? '',
                    'direccion' => $client->direccion ?? '',
                    'ciudad' => $client->ciudad ?? '',
                    'contacto' => $client->contacto ?? '',
                    'cargo' => $client->cargo ?? '',
                    'estado' => $client->estado ?? 'activo',
                    'match_field' => $this->getMatchField($client, $term)
                ];
            }

            Log::info('Búsqueda completada', [
                'term' => $term,
                'results' => count($formattedClients)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Búsqueda completada',
                'clients' => $formattedClients,
                'total' => count($formattedClients)
            ]);

        } catch (\Exception $e) {
            Log::error('Error en búsqueda de clientes', [
                'error' => $e->getMessage(),
                'search_term' => $request->get('q', ''),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'clients' => [],
                'total' => 0
            ], 500);
        }
    }

    /**
     * Determinar en qué campo se encontró la coincidencia
     */
    private function getMatchField($client, $term)
    {
        if (stripos($client->documento ?? '', $term) !== false) {
            return 'documento';
        }
        
        if (stripos($client->cliente ?? '', $term) !== false) {
            return 'cliente';
        }
        
        if (stripos($client->email ?? '', $term) !== false) {
            return 'email';
        }
        
        if (stripos($client->telefono ?? '', $term) !== false) {
            return 'telefono';
        }
        
        if (stripos($client->ciudad ?? '', $term) !== false) {
            return 'ciudad';
        }
        
        if (stripos($client->contacto ?? '', $term) !== false) {
            return 'contacto';
        }
        
        return 'other';
    }

    /**
     * Búsqueda rápida por documento específico
     */
    public function searchByDocument(Request $request)
    {
        try {
            $documento = trim($request->get('documento', ''));
            
            if (empty($documento)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento requerido',
                    'client' => null
                ]);
            }

            $client = Client::where('documento', $documento)->first();
            
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado',
                    'client' => null
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cliente encontrado',
                'client' => [
                    'id' => $client->id,
                    'documento' => $client->documento,
                    'cliente' => $client->cliente,
                    'email' => $client->email,
                    'telefono' => $client->telefono,
                    'direccion' => $client->direccion,
                    'ciudad' => $client->ciudad,
                    'contacto' => $client->contacto,
                    'cargo' => $client->cargo,
                    'estado' => $client->estado
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en búsqueda por documento', [
                'error' => $e->getMessage(),
                'documento' => $request->get('documento', ''),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'client' => null
            ], 500);
        }
    }
}