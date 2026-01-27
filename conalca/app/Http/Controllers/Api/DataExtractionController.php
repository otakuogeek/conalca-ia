<?php

namespace App\Http\Controllers\Api;

use App\Services\DataExtractionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class DataExtractionController extends Controller
{
    protected DataExtractionService $extractionService;

    public function __construct(DataExtractionService $extractionService)
    {
        $this->extractionService = $extractionService;
    }

    /**
     * Procesa un mensaje y extrae datos de cotización
     * POST /api/extract-quote-data
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function extractData(Request $request)
    {
        try {
            $validated = $request->validate([
                'message' => 'required|string|min:5',
                'current_data' => 'nullable|array'
            ]);

            Log::info('📨 DataExtractionController: Solicitud recibida', [
                'message_length' => strlen($validated['message']),
                'has_current_data' => !empty($validated['current_data'])
            ]);

            // Procesar el mensaje
            $result = $this->extractionService->extractDataFromMessage(
                $validated['message'],
                $validated['current_data'] ?? []
            );

            // Si hay datos extraídos pero faltantes, generar respuesta contextualizada
            if (!empty($result['extracted']) || !empty($result['missing'])) {
                $contextualResponse = $this->extractionService->generateContextualResponse(
                    $result['extracted'] ?? [],
                    $result['missing'] ?? []
                );
                $result['assistant_message'] = $contextualResponse;
            }

            Log::info('✅ DataExtractionController: Respuesta generada', [
                'extracted_count' => count($result['extracted'] ?? []),
                'missing_count' => count($result['missing'] ?? []),
                'questions_count' => count($result['questions'] ?? [])
            ]);

            return response()->json([
                'success' => $result['success'] ?? true,
                'data' => [
                    'extracted' => $result['extracted'] ?? [],
                    'missing' => $result['missing'] ?? [],
                    'questions' => $result['questions'] ?? [],
                    'confidence' => $result['confidence'] ?? 0,
                    'summary' => $result['summary'] ?? '',
                    'assistant_message' => $result['assistant_message'] ?? ''
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validación fallida',
                'messages' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ DataExtractionController: Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al procesar el mensaje',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesa un mensaje y retorna respuesta del asistente
     * POST /api/chat/extract-quote-data
     * Similar a extractData pero con respuesta más conversacional
     */
    public function chatExtractData(Request $request)
    {
        try {
            $validated = $request->validate([
                'message' => 'required|string|min:3',
                'current_data' => 'nullable|array',
                'thread_id' => 'nullable|string',
                'client_id' => 'nullable|numeric',
                'selected_route_index' => 'nullable|numeric', // 🆕 Nuevo parámetro
                'group_id' => 'nullable|numeric' // 🆕 FIX #557: Permitir guardar en grupo
            ]);

            // Convertir client_id a string si existe
            if (isset($validated['client_id'])) {
                $validated['client_id'] = (string) $validated['client_id'];
            }

            Log::info('💬 DataExtractionController: Chat extraction', [
                'message_length' => strlen($validated['message']),
                'thread_id' => $validated['thread_id'] ?? 'none',
                'client_id' => $validated['client_id'] ?? 'none',
                'group_id' => $validated['group_id'] ?? 'none'
            ]);

            $result = $this->extractionService->extractDataFromMessage(
                $validated['message'],
                $validated['current_data'] ?? [],
                $validated['selected_route_index'] ?? null // 🆕 Pasar al servicio
            );

            // Generar respuesta conversacional
            $assistantResponse = $this->buildAssistantResponse($result);

            // Log detallado de lo que se extrajo
            Log::info('🎯 Datos extraídos del mensaje:', [
                'extracted' => $result['extracted'] ?? [],
                'missing' => $result['missing'] ?? [],
                'confidence' => $result['confidence'] ?? 0,
                'multi_ruta' => $result['multi_ruta'] ?? false,
                'has_extracted_data' => isset($result['extracted_data']),
                'extracted_data_preview' => isset($result['extracted_data']) ? array_keys($result['extracted_data']) : null
            ]);
            
            // 🆕 FIX: Si MCPAssistantService retornó extracted_data, normalizarlo
            if (isset($result['extracted_data'])) {
                $extractedData = $result['extracted_data'];
                
                Log::info('🔍 Verificando extracted_data recibido', [
                    'type' => gettype($extractedData),
                    'has_multi_ruta' => isset($extractedData['multi_ruta']),
                    'multi_ruta_value' => $extractedData['multi_ruta'] ?? null,
                    'has_rutas' => isset($extractedData['rutas']),
                    'rutas_count' => isset($extractedData['rutas']) ? count($extractedData['rutas']) : 0
                ]);
                
                // Verificar si es formato multi-ruta
                if (isset($extractedData['multi_ruta']) && $extractedData['multi_ruta'] === true && isset($extractedData['rutas'])) {
                    Log::info('🚛 Multi-ruta detectada en extracted_data (después de edición)', [
                        'total_rutas' => count($extractedData['rutas']),
                        'edited_route_index' => $result['edited_route_index'] ?? null
                    ]);
                    
                    // Normalizar respuesta para que el frontend la entienda
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'multi_ruta' => true,
                            'rutas' => $extractedData['rutas'],
                            'total_rutas' => $extractedData['total_rutas'] ?? count($extractedData['rutas']),
                            'message' => $assistantResponse,
                            'edited_route_index' => $result['edited_route_index'] ?? null,
                            'metadata' => [
                                'confidence' => $result['confidence'] ?? 0.9
                            ]
                        ]
                    ]);
                }
            }
            
            // 🚛 MANEJAR MULTI-RUTA (detección directa)
            if (isset($result['multi_ruta']) && $result['multi_ruta'] === true) {
                Log::info('🚛 Multi-ruta detectada en controlador', [
                    'total_rutas' => $result['total_rutas'] ?? 0,
                    'rutas' => $result['rutas'] ?? []
                ]);
                
                // Generar respuesta para multi-ruta
                $multiRutaResponse = $this->buildMultiRutaResponse($result);
                
                // Guardar en grupo si hay group_id
                if (isset($validated['group_id'])) {
                    $this->saveMultiRutaToGroup($validated['group_id'], $result);
                }
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'multi_ruta' => true,
                        'rutas' => $result['rutas'] ?? [],
                        'total_rutas' => $result['total_rutas'] ?? 0,
                        'message' => $multiRutaResponse,
                        'metadata' => [
                            'confidence' => $result['confidence'] ?? 0.9
                        ]
                    ]
                ]);
            }
            
            // 🆕 FIX #557: Guardar datos extraídos en group_cotizations.extracted_data (RUTA ÚNICA)
            if (isset($validated['group_id']) && !empty($result['extracted'])) {
                $groupId = $validated['group_id'];
                $extracted = $result['extracted'];
                
                // Normalizar nombres de campos
                $normalizedData = [];
                $fieldMapping = [
                    'origen' => 'origen',
                    'destino' => 'destino',
                    'peso' => 'peso_kg',
                    'producto' => 'producto',
                    'valor' => 'valor_declarado',
                    'cantidad' => 'cantidad',
                    'vehiculo' => 'vehiculo',
                    'empaque' => 'empaque'
                ];
                
                foreach ($fieldMapping as $extractedKey => $dbKey) {
                    if (isset($extracted[$extractedKey]) && !empty($extracted[$extractedKey])) {
                        $normalizedData[$dbKey] = $extracted[$extractedKey];
                    }
                }
                
                if (!empty($normalizedData)) {
                    $group = \App\Models\GroupCotization::find($groupId);
                    if ($group) {
                        $existingData = json_decode($group->extracted_data ?? '{}', true) ?? [];
                        
                        // 🚛 CRÍTICO: NO sobrescribir si es formato multi-ruta
                        // MCPAssistantService ya guardó los datos correctamente dentro de rutas[]
                        $isMultiRuta = isset($existingData['multi_ruta']) && $existingData['multi_ruta'] === true && isset($existingData['rutas']);
                        
                        if ($isMultiRuta) {
                            Log::info('⚠️ Formato multi-ruta detectado - NO sobrescribir con campos globales', [
                                'group_id' => $groupId,
                                'campos_ignorados' => array_keys($normalizedData),
                                'razon' => 'MCPAssistantService ya guardó dentro de rutas[]'
                            ]);
                        } else {
                            // Ruta única: mergear normalmente
                            $mergedData = array_merge($existingData, $normalizedData);
                            $group->extracted_data = json_encode($mergedData);
                            $group->save();
                            
                            Log::info('💾 Datos de DataExtractionService guardados en grupo', [
                                'group_id' => $groupId,
                                'campos_nuevos' => array_keys($normalizedData),
                                'total_campos' => count($mergedData)
                            ]);
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'extracted' => $result['extracted'] ?? [],
                    'missing' => $result['missing'] ?? [],
                    'questions' => $result['questions'] ?? [],
                    'message' => $assistantResponse,
                    'metadata' => [
                        'confidence' => $result['confidence'] ?? 0,
                        'fields_found' => count($result['extracted'] ?? []),
                        'fields_missing' => count($result['missing'] ?? []),
                        'summary' => $result['summary'] ?? ''
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ ChatExtractData error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Construye una respuesta conversacional del asistente
     */
    private function buildAssistantResponse(array $extractionResult): string
    {
        $extracted = $extractionResult['extracted'] ?? [];
        $missing = $extractionResult['missing'] ?? [];
        $questions = $extractionResult['questions'] ?? [];
        $confidence = $extractionResult['confidence'] ?? 0;

        // Base: reconocer lo que se entendió
        $response = '';
        
        // Mostrar confianza si es baja
        if ($confidence < 0.7) {
            $response .= "⚠️ Extracción con confianza media (puede haber errores)\n\n";
        }

        if (!empty($extracted)) {
            $response .= "✅ **He identificado los siguientes detalles:**\n\n";
            
            $fieldLabels = [
                'origen' => '📍 **Origen**',
                'destino' => '📍 **Destino**',
                'peso' => '⚖️ **Peso**',
                'contenedor' => '📦 **Contenedor**',
                'cantidad' => '🔢 **Cantidad**',
                'producto' => '📦 **Producto/Mercancía**',
                'valor' => '💰 **Valor**',
                'incoterm' => '📋 **Incoterm**',
                'observaciones' => '📝 **Observaciones**',
                'mercancia' => '📦 **Mercancía**'
            ];

            foreach ($extracted as $key => $value) {
                $label = $fieldLabels[$key] ?? ucfirst(str_replace('_', ' ', $key));
                $formattedValue = $this->formatFieldValue($key, $value);
                $response .= "- $label: `$formattedValue`\n";
            }
        }

        // Si hay campos faltantes críticos, preguntar
        if (!empty($missing)) {
            $response .= "\n📋 **Para completar tu cotización, necesito:**\n";
            
            $fieldPrompts = [
                'origen' => '¿De cuál **ciudad o puerto**?',
                'destino' => '¿A cuál **ciudad o puerto**?',
                'peso' => '¿Cuál es el **peso total**? (en kg o toneladas)',
                'cantidad' => '¿Cuántas **unidades o bultos**?',
                'contenedor' => '¿Qué tipo de **contenedor/empaque**? (ej: 1X20, 1X40 HQ, caja, pallet)',
                'producto' => '¿Cuál es el **producto o mercancía** específica?',
                'valor' => '¿Cuál es el **valor declarado**? (moneda y cantidad)',
                'mercancia' => '¿Cuál es la **mercancía** a transportar?'
            ];

            foreach ($missing as $field) {
                $prompt = $fieldPrompts[$field] ?? "¿Información sobre " . str_replace('_', ' ', $field) . "?";
                $response .= "\n- $prompt";
            }
        }

        // Si se generaron preguntas adicionales del sistema, incluirlas
        if (!empty($questions)) {
            if (!empty($extracted)) {
                $response .= "\n\n🤔 **Preguntas adicionales:**\n";
            } else {
                $response = "Tengo algunas preguntas:\n";
            }
            
            foreach ($questions as $question) {
                $response .= "\n- $question";
            }
        }

        // Mensaje amable si falta información
        if (empty($extracted) && empty($questions)) {
            $response = "👋 Cuéntame sobre tu cotización.\n\n";
            $response .= "**Necesito saber:**\n";
            $response .= "- De qué ciudad a cuál ciudad\n";
            $response .= "- Cuánto pesa la mercancía\n";
            $response .= "- Qué tipo de producto\n";
            $response .= "- Tipo de contenedor (si aplica)\n";
            $response .= "- Valor declarado (si aplica)\n\n";
            $response .= "**Ejemplo de formato:**\n";
            $response .= "\"Necesito transportar 5 toneladas de maíz de Bogotá a Medellín en contenedor 1X20, valor 50.000 USD\"";
        }

        return trim($response);
    }

    /**
     * Genera respuesta para multi-ruta
     */
    private function buildMultiRutaResponse(array $result): string
    {
        $totalRutas = $result['total_rutas'] ?? 0;
        $rutas = $result['rutas'] ?? [];
        
        $response = "Perfecto, he registrado las {$totalRutas} rutas solicitadas:\n\n";
        
        foreach ($rutas as $idx => $ruta) {
            $numero = $idx + 1;
            $origen = $ruta['origen'] ?? '?';
            $destino = $ruta['destino'] ?? '?';
            $peso = $ruta['peso'] ?? '?';
            $producto = $ruta['producto'] ?? '?';
            $cantidad = $ruta['cantidad'] ?? '?';
            $valor = isset($ruta['valor']) ? number_format($ruta['valor'], 0, ',', '.') : '?';
            $vehiculo = $ruta['vehiculo'] ?? '?';
            
            $response .= "{$numero}. Ruta {$origen} → {$destino}:\n";
            $response .= "   - Peso: {$peso} kg\n";
            $response .= "   - Producto: {$producto}\n";
            if ($cantidad !== '?') {
                $empaqueInfo = isset($ruta['empaque']) ? " {$ruta['empaque']}" : ' unidades';
                $response .= "   - Cantidad: {$cantidad}{$empaqueInfo}\n";
            }
            if ($vehiculo !== '?') {
                $response .= "   - Vehículo: {$vehiculo}\n";
            }
            if ($valor !== '?') {
                $response .= "   - Valor declarado: \${$valor} COP\n";
            }
            $response .= "\n";
        }
        
        $response .= "¿Deseas proceder con la creación de las cotizaciones para estas rutas?";
        
        return $response;
    }
    
    /**
     * Guarda multi-ruta en el grupo
     */
    private function saveMultiRutaToGroup(int $groupId, array $result): void
    {
        try {
            $group = \App\Models\GroupCotization::find($groupId);
            if (!$group) {
                Log::warning('⚠️ Grupo no encontrado para guardar multi-ruta', ['group_id' => $groupId]);
                return;
            }
            
            // 🆔 Agregar ID único a cada ruta si no lo tiene
            $rutasConId = [];
            foreach ($result['rutas'] ?? [] as $idx => $ruta) {
                if (!isset($ruta['ruta_id'])) {
                    $ruta['ruta_id'] = 'ruta_' . uniqid() . '_' . ($idx + 1);
                    Log::info('🆔 ID generado para nueva ruta', [
                        'ruta_id' => $ruta['ruta_id'],
                        'index' => $idx
                    ]);
                }
                $rutasConId[] = $ruta;
            }
            
            // Guardar todas las rutas en extracted_data como array
            $group->extracted_data = json_encode([
                'multi_ruta' => true,
                'total_rutas' => $result['total_rutas'] ?? count($rutasConId),
                'rutas' => $rutasConId
            ]);
            $group->save();
            
            Log::info('💾 Multi-ruta guardada en grupo', [
                'group_id' => $groupId,
                'total_rutas' => $result['total_rutas'] ?? 0
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error guardando multi-ruta', [
                'group_id' => $groupId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Formatea un valor de campo para presentación
     */
    private function formatFieldValue(string $fieldName, $value): string
    {
        if (is_null($value)) {
            return 'No especificado';
        }

        switch ($fieldName) {
            case 'peso':
                return is_numeric($value) 
                    ? number_format($value, 0, ',', '.') . ' kg'
                    : $value;
            
            case 'valor':
                return is_numeric($value)
                    ? 'USD ' . number_format($value, 2, ',', '.')
                    : $value;
            
            case 'cantidad':
                return is_numeric($value)
                    ? (int)$value . ' unidades'
                    : $value;
            
            default:
                return (string)$value;
        }
    }
}
