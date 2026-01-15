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
                'client_id' => 'nullable|string'
            ]);

            Log::info('💬 DataExtractionController: Chat extraction', [
                'message_length' => strlen($validated['message']),
                'thread_id' => $validated['thread_id'] ?? 'none',
                'client_id' => $validated['client_id'] ?? 'none'
            ]);

            $result = $this->extractionService->extractDataFromMessage(
                $validated['message'],
                $validated['current_data'] ?? []
            );

            // Generar respuesta conversacional
            $assistantResponse = $this->buildAssistantResponse($result);

            // Log detallado de lo que se extrajo
            Log::info('🎯 Datos extraídos del mensaje:', [
                'extracted' => $result['extracted'] ?? [],
                'missing' => $result['missing'] ?? [],
                'confidence' => $result['confidence'] ?? 0
            ]);

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
