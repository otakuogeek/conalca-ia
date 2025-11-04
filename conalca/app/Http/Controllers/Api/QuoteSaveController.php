<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GroupCotization;
use App\Models\CotizacionModel;
use App\Models\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class QuoteSaveController extends Controller
{
    public function saveQuoteFromChat(Request $request)
    {
        $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'quote_data' => 'required|array|min:1',
            'quote_data.*.ciudad_origen' => 'required|string',
            'quote_data.*.ciudad_destino' => 'required|string',
            'quote_data.*.peso_mercancia' => 'nullable|string',
            'quote_data.*.tipo_producto' => 'nullable|string',
            'quote_data.*.vehiculo_requerido' => 'nullable|string',
            'quote_data.*.valor_declarado' => 'nullable|string',
            'thread_id' => 'nullable|string',
            'type_business' => 'required|string'
        ]);

        DB::beginTransaction();
        
        try {
            $userId = auth()->id();
            $client = Client::findOrFail($request->client_id);
            
            Log::info('Guardando cotización desde chat', [
                'user_id' => $userId,
                'client_id' => $request->client_id,
                'routes_count' => count($request->quote_data),
                'thread_id' => $request->thread_id
            ]);

            // Crear el grupo de cotización
            $group = GroupCotization::create([
                'user_id' => $userId,
                'client_id' => $request->client_id,
                'type' => $request->type_business,
                'status' => 'Pre-Solicitud', // Estado inicial para la columna
                'openai_thread_id' => $request->thread_id,
                'created_from_chat' => true
            ]);

            // Crear las cotizaciones individuales para cada ruta
            foreach ($request->quote_data as $index => $routeData) {
                // Obtener un pricing por defecto para las cotizaciones del chat
                $defaultPricing = \App\Models\Pricing::first();
                
                // Calcular precio final si se envió desde el frontend
                $finalValue = $routeData['finalValue'] ?? 0;
                $porcentaje = $routeData['porcentaje'] ?? 0;
                
                $cotizacion = CotizacionModel::create([
                    'pricing_id' => $defaultPricing ? $defaultPricing->id : 61,
                    'group_cotization_id' => $group->id,
                    'client_id' => $request->client_id,
                    'ciudad_origen' => $routeData['ciudad_origen'],
                    'ciudad_destino' => $routeData['ciudad_destino'],
                    'peso_mercancia' => $this->extractNumericValue($routeData['peso_mercancia'] ?? ''),
                    'tipo_producto' => $routeData['tipo_producto'] ?? '',
                    'vehiculo_requerido' => $routeData['vehiculo_requerido'] ?? 'Camión sencillo',
                    'valor_declarado' => $this->extractNumericValue($routeData['valor_declarado'] ?? '0'),
                    'valor' => $finalValue, // Guardar el precio final calculado
                    'porcentaje' => $porcentaje, // Guardar el porcentaje aplicado
                    'cantidad' => $routeData['cantidad'] ?? '1',
                    'tipo_embajale' => $routeData['tipo_embajale'] ?? 'Bultos',
                    'dimensiones_exactas' => $routeData['dimensiones_exactas'] ?? 'No especificado',
                    'registro_fotografico' => $routeData['registro_fotografico'] ?? 'No requerido',
                    'regimen_nacionalizado' => '1',
                    'descargue_cargue' => '1',
                    'consolidado_expreso' => '0',
                    'fcl_lcl' => 'LCL',
                    'sitio_devolucion_contenedor' => 'No aplica',
                    'fecha_hora_descargue_cargue' => now()->format('d-m-Y'),
                    'cantidad_vh' => '1',
                    'un' => 'N/A',
                    'ruta' => strtolower($routeData['ciudad_origen']) . '-' . strtolower($routeData['ciudad_destino']),
                    'frecuencia' => 'Única vez',
                    'esquema_seguridad' => 'Básico',
                    'tipo_carroceria' => 'Estacas',
                    'tipo_mercancia' => 'Carga seca',
                    'seguro' => '1',
                    'decision_cliente' => 'pendiente',
                    'active' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info('Cotización creada desde chat', [
                    'cotizacion_id' => $cotizacion->id,
                    'group_id' => $group->id,
                    'ruta' => $routeData['ciudad_origen'] . ' → ' . $routeData['ciudad_destino']
                ]);
            }

            // Actualizar el thread_id en el cliente si se proporcionó
            if ($request->thread_id) {
                $client->update(['openai_thread_id' => $request->thread_id]);
            }

            DB::commit();

            Log::info('Grupo de cotización guardado exitosamente', [
                'group_id' => $group->id,
                'total_routes' => count($request->quote_data)
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'group_id' => $group->id,
                    'total_routes' => count($request->quote_data),
                    'status' => $group->status,
                    'message' => 'Cotización guardada exitosamente'
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error guardando cotización desde chat', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id() ?? 'no_auth',
                'client_id' => $request->client_id ?? 'no_client'
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extrae valores numéricos de strings que pueden contener formato
     */
    private function extractNumericValue($value)
    {
        if (empty($value)) {
            return 0;
        }
        
        // Remover caracteres no numéricos excepto puntos y comas
        $cleaned = preg_replace('/[^\d.,]/', '', $value);
        
        // Convertir comas a puntos para decimales
        $cleaned = str_replace(',', '.', $cleaned);
        
        // Si hay múltiples puntos, tomar solo el último como decimal
        $parts = explode('.', $cleaned);
        if (count($parts) > 2) {
            $integer = implode('', array_slice($parts, 0, -1));
            $decimal = end($parts);
            $cleaned = $integer . '.' . $decimal;
        }
        
        return (float) $cleaned;
    }
}