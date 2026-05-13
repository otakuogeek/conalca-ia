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

            // 🔧 FIX CRÍTICO: En modo edición con multi-rutas, fusionar datos extraídos con datos existentes
            $selectedRouteIndex = $validated['selected_route_index'] ?? null;
            $groupId = $validated['group_id'] ?? null;
            $isEditMode = isset($result['extracted']['is_edit']) || (!empty($validated['current_data']) && $selectedRouteIndex !== null);
            
            if ($isEditMode && $groupId !== null && $selectedRouteIndex !== null && !isset($result['multi_ruta'])) {
                Log::info('🔧 MODO EDICIÓN detectado - fusionando datos', [
                    'selected_route_index' => $selectedRouteIndex,
                    'group_id' => $groupId,
                    'extracted_keys' => array_keys($result['extracted'] ?? [])
                ]);
                
                $group = \App\Models\GroupCotization::find($groupId);
                if ($group && $group->extracted_data) {
                    $existingData = json_decode($group->extracted_data, true);
                    
                    if (isset($existingData['multi_ruta']) && $existingData['multi_ruta'] === true && isset($existingData['rutas'])) {
                        // Fusionar SOLO campos no-null del resultado extraído
                        $extractedFields = $result['extracted'] ?? [];
                        $rutaIdx = (int) $selectedRouteIndex;
                        
                        if (isset($existingData['rutas'][$rutaIdx])) {
                            $rutaExistente = $existingData['rutas'][$rutaIdx];
                            
                            // Solo actualizar campos que OpenAI extrajo (no null)
                            foreach ($extractedFields as $key => $value) {
                                if ($value !== null && $key !== 'is_edit') {
                                    $rutaExistente[$key] = $value;
                                    Log::info("✏️ Campo '$key' actualizado a: " . json_encode($value));
                                }
                            }
                            
                            // Actualizar la ruta en el array
                            $existingData['rutas'][$rutaIdx] = $rutaExistente;
                            
                            // Guardar en BD
                            $group->extracted_data = json_encode($existingData);
                            $group->save();
                            
                            Log::info('💾 Datos fusionados guardados en grupo', [
                                'group_id' => $groupId,
                                'ruta_editada' => $rutaIdx,
                                'campos_actualizados' => array_keys(array_filter($extractedFields, fn($v) => $v !== null))
                            ]);
                            
                            // 🔧 FIX: Si el peso ya tiene tara incluida (por DataExtractionService),
                            // NO volver a aplicarla. Solo sincronizar campos.
                            $rutasConTara = [];
                            foreach ($existingData['rutas'] as $idx => $ruta) {
                                // 🔧 FIX: Buscar peso en ambos campos posibles
                                $pesoBase = $ruta['peso'] ?? $ruta['peso_kg'] ?? 0;
                                $incluyeTara = $ruta['incluye_tara'] ?? false;
                                
                                // 🆕 FIX CRÍTICO: Si la ruta que se está editando ya tiene el peso
                                // actualizado por DataExtractionService (con tara incluida), NO duplicar
                                if ($idx === $rutaIdx && isset($extractedFields['incluye_tara']) && $extractedFields['incluye_tara'] === true) {
                                    // DataExtractionService ya aplicó la tara, solo sincronizar
                                    $pesoConTara = $extractedFields['peso'] ?? $extractedFields['peso_kg'] ?? $pesoBase;
                                    $ruta['peso'] = $pesoConTara;
                                    $ruta['peso_kg'] = $pesoConTara;
                                    $ruta['incluye_tara'] = true;
                                    Log::info('✅ Tara ya aplicada por DataExtractionService, solo sincronizando campos', [
                                        'ruta' => $idx + 1,
                                        'peso' => $pesoConTara
                                    ]);
                                } else if (!$incluyeTara && is_numeric($pesoBase) && $pesoBase > 0) {
                                    // 🔧 FIX v3: Solo aplicar tara si hay contenedores
                                    $textoContEdicion = $ruta['empaque'] ?? $ruta['contenedor'] ?? $ruta['tamano_contenedor'] ?? '';
                                    $hayContEdicion = preg_match('/contenedor|container|\d+[xX]\d+|\d+\s*(?:pies|ft|pie)/ui', $textoContEdicion);
                                    if ($hayContEdicion) {
                                        $taraEdicion = 3400; // Default
                                        if (isset($ruta['tamano_contenedor']) && $ruta['tamano_contenedor'] == 20) {
                                            $taraEdicion = 2300;
                                        } elseif (isset($ruta['empaque']) && preg_match('/CONTENEDOR\s*20/i', $ruta['empaque'])) {
                                            $taraEdicion = 2300;
                                        }
                                        $ruta['peso'] = $pesoBase + $taraEdicion;
                                        $ruta['peso_kg'] = $pesoBase + $taraEdicion;
                                        $ruta['tara'] = $taraEdicion;
                                        $ruta['incluye_tara'] = true;
                                        Log::info('🏋️ TARA aplicada en edición fusionada, ruta ' . ($idx + 1), [
                                            'tara' => $taraEdicion,
                                            'peso_con_tara' => $ruta['peso']
                                        ]);
                                    } else {
                                        // Sin contenedor → NO aplicar tara
                                        $ruta['peso'] = $pesoBase;
                                        $ruta['peso_kg'] = $pesoBase;
                                        $ruta['incluye_tara'] = false;
                                    }
                                } else if ($incluyeTara) {
                                    // Ya tiene tara, sincronizar campos
                                    $ruta['peso'] = $pesoBase;
                                    $ruta['peso_kg'] = $pesoBase;
                                }
                                $rutasConTara[] = $ruta;
                            }
                            
                            // Devolver las rutas fusionadas completas
                            return response()->json([
                                'success' => true,
                                'data' => [
                                    'multi_ruta' => true,
                                    'rutas' => $rutasConTara,
                                    'total_rutas' => count($rutasConTara),
                                    'message' => $this->buildEditResponseMessage($extractedFields, $rutaIdx + 1),
                                    'edited_route_index' => $rutaIdx,
                                    'metadata' => [
                                        'confidence' => $result['confidence'] ?? 0.9,
                                        'is_edit' => true
                                    ]
                                ]
                            ]);
                        }
                    }
                }
            }

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
                    
                    // 🔧 FIX: Solo aplicar tara si NO está marcada como incluida
                    $rutasConTara = [];
                    foreach ($extractedData['rutas'] ?? [] as $idx => $ruta) {
                        // 🔧 FIX: Buscar peso en ambos campos posibles
                        $pesoBase = $ruta['peso'] ?? $ruta['peso_kg'] ?? 0;
                        $incluyeTara = $ruta['incluye_tara'] ?? false;
                        
                        if (!$incluyeTara && is_numeric($pesoBase) && $pesoBase > 0) {
                            // 🔧 FIX v3: Solo aplicar tara si hay contenedores
                            $textoContEd2 = $ruta['empaque'] ?? $ruta['contenedor'] ?? $ruta['tamano_contenedor'] ?? '';
                            $hayContEd2 = preg_match('/contenedor|container|\d+[xX]\d+|\d+\s*(?:pies|ft|pie)/ui', $textoContEd2);
                            if ($hayContEd2) {
                                $taraEdicion2 = 3400; // Default
                                if (isset($ruta['tamano_contenedor']) && $ruta['tamano_contenedor'] == 20) {
                                    $taraEdicion2 = 2300;
                                } elseif (isset($ruta['empaque']) && preg_match('/CONTENEDOR\s*20/i', $ruta['empaque'])) {
                                    $taraEdicion2 = 2300;
                                }
                                $ruta['peso'] = $pesoBase + $taraEdicion2;
                                $ruta['peso_kg'] = $pesoBase + $taraEdicion2;
                                $ruta['tara'] = $taraEdicion2;
                                $ruta['incluye_tara'] = true;
                                Log::info('🏋️ TARA aplicada para frontend (edición) en ruta ' . ($idx + 1), [
                                    'peso_original' => $pesoBase,
                                    'peso_con_tara' => $ruta['peso']
                                ]);
                            } else {
                                // Sin contenedor → NO aplicar tara
                                $ruta['peso'] = $pesoBase;
                                $ruta['peso_kg'] = $pesoBase;
                                $ruta['incluye_tara'] = false;
                            }
                        } else if ($incluyeTara) {
                            // Ya tiene tara, sincronizar campos
                            $ruta['peso'] = $pesoBase;
                            $ruta['peso_kg'] = $pesoBase;
                            Log::info('✅ TARA ya incluida (edición) en ruta ' . ($idx + 1) . ', peso: ' . $pesoBase . ' kg');
                        }
                        $rutasConTara[] = $ruta;
                    }
                    
                    // Normalizar respuesta para que el frontend la entienda
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'multi_ruta' => true,
                            'rutas' => $rutasConTara,
                            'total_rutas' => $extractedData['total_rutas'] ?? count($rutasConTara),
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
                
                // 🔧 FIX CRÍTICO: Cada ruta tiene su propio flag incluye_tara
                // NO usar detección global - usar el flag de cada ruta individual
                // La IA (DataExtractionService) ya analizó cada segmento y estableció el flag correctamente
                
                $rutasConTara = [];
                foreach ($result['rutas'] ?? [] as $idx => $ruta) {
                    $pesoBase = $ruta['peso'] ?? $ruta['peso_kg'] ?? 0;
                    $incluyeTaraFlag = $ruta['incluye_tara'] ?? false;
                    
                    // 🔴 LÓGICA SIMPLE Y CORRECTA:
                    // - Si incluye_tara = true → el peso YA tiene tara, NO sumar
                    // - Si incluye_tara = false → el peso NO tiene tara, SUMAR tara según contenedor
                    // 🔧 FIX: Contenedor 20 pies = 2300 kg, otros = 3400 kg
                    
                    if ($incluyeTaraFlag) {
                        // El peso YA incluye tara - NO sumar nada
                        $ruta['peso'] = $pesoBase;
                        $ruta['peso_kg'] = $pesoBase;
                        Log::info('✅ TARA YA INCLUIDA en ruta ' . ($idx + 1) . ', peso se mantiene: ' . $pesoBase . ' kg');
                    } elseif (is_numeric($pesoBase) && $pesoBase > 0) {
                        // 🔧 FIX v3: Solo aplicar tara si hay CONTENEDORES
                        $textoContRuta = $ruta['empaque'] ?? $ruta['contenedor'] ?? $ruta['tamano_contenedor'] ?? '';
                        $hayContRuta = preg_match('/contenedor|container|\d+[xX]\d+|\d+\s*(?:pies|ft|pie)/ui', $textoContRuta);
                        
                        if ($hayContRuta) {
                            // 🔧 FIX CRÍTICO: Determinar tara según tamaño de contenedor
                            $taraRuta = 3400; // Default
                            if (isset($ruta['tamano_contenedor']) && $ruta['tamano_contenedor'] == 20) {
                                $taraRuta = 2300;
                            } elseif (isset($ruta['empaque']) && preg_match('/CONTENEDOR\s*20/i', $ruta['empaque'])) {
                                $taraRuta = 2300;
                            } elseif (isset($ruta['contenedor']) && preg_match('/\d+[xX]20/i', $ruta['contenedor'])) {
                                $taraRuta = 2300;
                            }
                            $ruta['peso'] = $pesoBase + $taraRuta;
                            $ruta['peso_kg'] = $pesoBase + $taraRuta;
                            $ruta['tara'] = $taraRuta;
                            $ruta['incluye_tara'] = true;
                            Log::info('🏋️ TARA SUMADA en ruta ' . ($idx + 1), [
                                'peso_original' => $pesoBase,
                                'tara_aplicada' => $taraRuta,
                                'peso_con_tara' => $ruta['peso'],
                                'empaque' => $ruta['empaque'] ?? 'N/A',
                                'contenedor' => $ruta['contenedor'] ?? 'N/A',
                                'razon' => 'Flag incluye_tara=false CON contenedor'
                            ]);
                        } else {
                            // Sin contenedor → NO aplicar tara (carga suelta)
                            $ruta['peso'] = $pesoBase;
                            $ruta['peso_kg'] = $pesoBase;
                            $ruta['incluye_tara'] = false;
                            Log::info('📦 Sin contenedor en ruta ' . ($idx + 1) . ' → peso se mantiene: ' . $pesoBase . ' kg');
                        }
                    }
                    $rutasConTara[] = $ruta;
                }
                $result['rutas'] = $rutasConTara;
                
                // Generar respuesta para multi-ruta
                $multiRutaResponse = $this->buildMultiRutaResponse($result);
                
                // Guardar en grupo si hay group_id
                if (isset($validated['group_id'])) {
                    $this->saveMultiRutaToGroup($validated['group_id'], $result, $validated['message'] ?? null);
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
                        // 💵 NO guardar valor_declarado si está en USD
                        if ($extractedKey === 'valor' && isset($extracted['valor_en_usd']) && $extracted['valor_en_usd']) {
                            Log::info('💵 Valor en USD detectado - NO se guarda valor_declarado en BD');
                            continue;
                        }
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

            // 💵 Si hay valor en USD, limpiar el valor de los datos extraídos antes de enviar al frontend
            $extractedForFrontend = $result['extracted'] ?? [];
            if (isset($extractedForFrontend['valor_en_usd']) && $extractedForFrontend['valor_en_usd']) {
                unset($extractedForFrontend['valor']);
                unset($extractedForFrontend['valor_declarado']);
                unset($extractedForFrontend['valorMercancia']);
                // Agregar 'valor' a los campos faltantes si no está ya
                if (!in_array('valor', $result['missing'] ?? [])) {
                    $result['missing'][] = 'valor';
                }
                Log::info('💵 Valor en USD: limpiado de extracted, agregado a missing');
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'extracted' => $extractedForFrontend,
                    'missing' => $result['missing'] ?? [],
                    'questions' => $result['questions'] ?? [],
                    'message' => $assistantResponse,
                    'metadata' => [
                        'confidence' => $result['confidence'] ?? 0,
                        'fields_found' => count($extractedForFrontend),
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
     * 🔧 FIX: Construye mensaje para respuesta de edición
     */
    private function buildEditResponseMessage(array $extractedFields, int $rutaNumero): string
    {
        $fieldsUpdated = array_filter($extractedFields, fn($v) => $v !== null && $v !== 'is_edit');
        
        if (empty($fieldsUpdated)) {
            return "✅ No detecté campos para actualizar en la Ruta $rutaNumero.";
        }
        
        $fieldLabels = [
            'origen' => 'Origen',
            'destino' => 'Destino',
            'peso' => 'Peso',
            'cantidad' => 'Cantidad',
            'empaque' => 'Empaque',
            'producto' => 'Producto',
            'valor' => 'Valor declarado',
            'vehiculo' => 'Vehículo',
            'contenedor' => 'Contenedor'
        ];
        
        $response = "✅ **¡Entendido! He actualizado los siguientes campos en la Ruta $rutaNumero:**\n\n";
        
        foreach ($fieldsUpdated as $key => $value) {
            if ($key === 'is_edit') continue;
            $label = $fieldLabels[$key] ?? ucfirst($key);
            $formattedValue = $this->formatFieldValue($key, $value);
            $response .= "- **$label**: $formattedValue\n";
        }
        
        return $response;
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

            // 💵 Detectar si hay valor en USD
            $valorEnUSD = isset($extracted['valor_en_usd']) && $extracted['valor_en_usd'];
            
            // Campos internos que NO se deben mostrar al usuario
            $camposOcultos = ['valor_en_usd', 'empaque_id', 'is_edit', 'producto_codigo'];

            foreach ($extracted as $key => $value) {
                // Saltar campos internos/ocultos
                if (in_array($key, $camposOcultos)) continue;
                
                // 💵 Si hay valor en USD, NO mostrar el valor numérico
                if ($valorEnUSD && in_array($key, ['valor', 'valor_declarado', 'valorMercancia'])) continue;
                
                $label = $fieldLabels[$key] ?? ucfirst(str_replace('_', ' ', $key));
                $formattedValue = $this->formatFieldValue($key, $value);
                $response .= "- $label: `$formattedValue`\n";
            }
            
            // 💵 Agregar contra-pregunta por valor en USD
            if ($valorEnUSD) {
                $response .= "\n⚠️ **El valor de la carga fue indicado en dólares (USD).**\n";
                $response .= "\n📋 Para continuar, necesito que me indiques:\n";
                $response .= "\n- ¿Cuál es el **valor declarado en pesos colombianos (COP)**?";
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
            
            // 🔧 FIX: Calcular peso CON TARA si no está incluida
            // 🔧 FIX v2: Usar tara según contenedor (20 pies = 2300, otros = 3400)
            $pesoBase = $ruta['peso'] ?? 0;
            $incluyeTara = $ruta['incluye_tara'] ?? false;
            $peso = $pesoBase;
            // 🔧 FIX: Solo aplicar tara si hay contenedores (NO para cajas, bultos, pallets, etc.)
            $textoContenedor = $ruta['empaque'] ?? $ruta['contenedor'] ?? $ruta['tamano_contenedor'] ?? '';
            $hayContenedorEnRuta = preg_match('/contenedor|container|\d+[xX]\d+|\d+\s*(?:pies|ft|pie)/ui', $textoContenedor);
            if (!$incluyeTara && is_numeric($pesoBase) && $pesoBase > 0 && $hayContenedorEnRuta) {
                $taraAplicar = \App\Services\MCPAssistantService::getTaraByContenedorPublic($textoContenedor);
                $peso = $pesoBase + $taraAplicar; // Sumar tara según contenedor
            }
            
            $producto = $ruta['producto'] ?? '?';
            $cantidad = $ruta['cantidad'] ?? '?';
            $valor = isset($ruta['valor']) ? number_format($ruta['valor'], 0, ',', '.') : '?';
            $vehiculo = $ruta['vehiculo'] ?? '?';
            
            $response .= "{$numero}. Ruta {$origen} → {$destino}:\n";
            $response .= "   - Peso: {$peso} kg" . ($incluyeTara ? " (tara incluida)" : " (incluye tara)") . "\n";
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
     * @param string|null $mensajeOriginal Mensaje original del usuario para detectar comandos de tara
     */
    private function saveMultiRutaToGroup(int $groupId, array $result, ?string $mensajeOriginal = null): void
    {
        try {
            $group = \App\Models\GroupCotization::find($groupId);
            if (!$group) {
                Log::warning('⚠️ Grupo no encontrado para guardar multi-ruta', ['group_id' => $groupId]);
                return;
            }
            
            // 🔧 FIX: Detectar si el mensaje original tiene "+ tara" o "más tara"
            $forzarSumaTara = false;
            if ($mensajeOriginal) {
                $forzarSumaTara = preg_match('/\+\s*tara|m[aá]s\s+tara|sin\s+tara|no\s+incluye\s+tara/ui', strtolower($mensajeOriginal));
            }
            
            // 🆔 Agregar ID único a cada ruta y CALCULAR TARA si no está incluida
            $rutasConId = [];
            foreach ($result['rutas'] ?? [] as $idx => $ruta) {
                if (!isset($ruta['ruta_id'])) {
                    $ruta['ruta_id'] = 'ruta_' . uniqid() . '_' . ($idx + 1);
                    Log::info('🆔 ID generado para nueva ruta', [
                        'ruta_id' => $ruta['ruta_id'],
                        'index' => $idx
                    ]);
                }
                
                // 🔧 FIX CRÍTICO: Calcular peso CON TARA si no está incluida ANTES de guardar
                // 🔧 FIX v3: SOLO aplicar tara si hay CONTENEDORES, NO para carga suelta (cajas, bultos, etc.)
                $pesoBase = $ruta['peso'] ?? 0;
                $incluyeTara = $ruta['incluye_tara'] ?? false;
                
                // 🔧 Verificar si la ruta tiene contenedores
                $textoContenedorSave = $ruta['empaque'] ?? $ruta['contenedor'] ?? $ruta['tamano_contenedor'] ?? '';
                $hayContenedorSave = preg_match('/contenedor|container|\d+[xX]\d+|\d+\s*(?:pies|ft|pie)/ui', $textoContenedorSave);
                
                // 🔧 FIX: Si hay comando de tara y el peso parece sin tara, forzar suma (SOLO con contenedores)
                $pesoPareceSinTara = $forzarSumaTara && is_numeric($pesoBase) && $pesoBase > 0 && ($pesoBase % 1000 == 0);
                
                if ((!$incluyeTara || $pesoPareceSinTara) && is_numeric($pesoBase) && $pesoBase > 0 && $hayContenedorSave) {
                    // 🔧 FIX v2: Usar tara según contenedor (20 pies = 2300, otros = 3400)
                    $taraAplicar = \App\Services\MCPAssistantService::getTaraByContenedorPublic($textoContenedorSave);
                    $ruta['peso'] = $pesoBase + $taraAplicar; // Sumar tara según contenedor
                    $ruta['peso_kg'] = $pesoBase + $taraAplicar; // También actualizar peso_kg
                    $ruta['tara'] = $taraAplicar; // Guardar tara aplicada
                    $ruta['incluye_tara'] = true; // Marcar que ahora incluye tara
                    Log::info('🏋️ TARA agregada a ruta ' . ($idx + 1), [
                        'peso_original' => $pesoBase,
                        'tara_aplicada' => $taraAplicar,
                        'peso_con_tara' => $ruta['peso'],
                        'razon' => $pesoPareceSinTara ? 'Forzado por mensaje original' : 'Flag incluye_tara=false'
                    ]);
                } else if (!$hayContenedorSave && is_numeric($pesoBase) && $pesoBase > 0) {
                    // Sin contenedor → NO aplicar tara (carga suelta)
                    $ruta['peso_kg'] = $pesoBase;
                    $ruta['incluye_tara'] = false;
                    Log::info('📦 Sin contenedor en ruta ' . ($idx + 1) . ' → peso se mantiene: ' . $pesoBase . ' kg (sin tara)');
                } else if ($incluyeTara && !$pesoPareceSinTara) {
                    Log::info('✅ Ruta ' . ($idx + 1) . ' ya incluye tara, peso se mantiene: ' . $pesoBase);
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
                    ? '$' . number_format($value, 0, ',', '.')
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
