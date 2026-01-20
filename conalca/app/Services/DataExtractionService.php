<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Servicio para extracción inteligente de datos de cotización
 * Usa OpenAI API para identificar y extraer información relevante de mensajes naturales
 */
class DataExtractionService
{
    private $apiKey;
    private $baseUrl = 'https://api.openai.com/v1';
    
    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');
        
        if (!$this->apiKey) {
            throw new \Exception('OpenAI API key no configurada');
        }
    }

    /**
     * Procesa un mensaje de usuario y extrae datos de cotización
     * Usa OpenAI API para análisis inteligente
     */
    public function extractDataFromMessage(string $userMessage, array $currentData = [], ?int $selectedRouteIndex = null): array
    {
        Log::info('🔍 DataExtractionService: Procesando mensaje con OpenAI', [
            'message_length' => strlen($userMessage),
            'current_data_keys' => array_keys($currentData),
            'selected_route_index' => $selectedRouteIndex
        ]);

        $systemPrompt = $this->buildSystemPrompt($currentData);
        
        try {
            // Llamar a OpenAI API directamente
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . '/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'temperature' => 0,
                    'max_tokens' => 2000,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt
                        ],
                        [
                            'role' => 'user',
                            'content' => "Extrae datos de este mensaje:\n\n" . $userMessage
                        ]
                    ]
                ]);

            if (!$response->successful()) {
                Log::error('OpenAI API error:', [
                    'status' => $response->status(),
                    'body' => $response->json()
                ]);
                throw new \Exception('Error en OpenAI API: ' . $response->status());
            }

            $assistantMessage = $response->json()['choices'][0]['message']['content'] ?? '';
            
            Log::info('📨 Respuesta de OpenAI (primeros 300 chars):', [
                'response' => substr($assistantMessage, 0, 300)
            ]);
            
            // Parsear respuesta JSON
            $extractedData = $this->parseExtractionResponse($assistantMessage);

            // 🆕 LÓGICA TARA (Manual, post-procesamiento para respuesta rápida)
            // Esto asegura que la respuesta inmediata tenga el cálculo aplicado
            $lastUserMessage = strtolower($userMessage);
            
            // 🔧 FIX: Detectar si debe agregar tara
            // Casos que requieren agregar tara:
            // 1. "agrega/incluye/suma tara" (explícito)
            // 2. "el peso no incluye tara" o "sin tara" o "peso neto" (explícito negativo)
            // 3. Default: si NO dice "tara incluida/con tara/peso bruto", agregar tara
            $agregarTara = preg_match('/(?:agrega|añade|suma|pon|incluye|incluir|agregar)\s+(?:la\s+)?tara/ui', $lastUserMessage);
            $noIncluyeTara = preg_match('/(?:no\s+incluye|sin)\s+(?:la\s+)?tara|peso\s+neto|el\s+peso\s+no\s+incluye/ui', $lastUserMessage);
            $yaIncluyeTara = preg_match('/(?:tara\s+incluida|con\s+tara|peso\s+bruto|ya\s+incluye\s+tara|peso\s+ya\s+incluye)/ui', $lastUserMessage);
            
            // Determinar si se debe agregar tara
            $debeAgregarTara = $agregarTara || $noIncluyeTara || (!$yaIncluyeTara);
            
            if ($debeAgregarTara && !$yaIncluyeTara) {
                // Buscar peso en extracción actual o datos previos
                $pesoActual = $extractedData['extracted']['peso'] ?? $currentData['peso_mercancia'] ?? $currentData['peso'] ?? 0;
                
                // Limpiar peso si viene como string
                if (is_string($pesoActual)) {
                    $pesoActual = (float) preg_replace('/[^0-9.]/', '', $pesoActual);
                }
                
                // 🔥 HEURÍSTICO MEJORADO: Detectar si OpenAI ya sumó la tara
                // Si (peso - 3400) es múltiplo exacto de 1000, probablemente ya tiene tara
                // Ejemplos: 15400-3400=12000 (12 ton), 27400-3400=24000 (24 ton), 34200-3400=30800 (30.8 ton - ¡duplicado!)
                $pesoSinPosibleTara = $pesoActual - 3400;
                $esProbablementeDuplicado = ($pesoSinPosibleTara > 0 && $pesoSinPosibleTara % 1000 == 0);
                
                // También verificar si el mensaje menciona "con tara" o "ya tara"
                $mensionaTara = preg_match('/(?<!no\s)(?:con\s+tara|ya.*tara|tara\s+incluida|peso\s+bruto)/ui', $lastUserMessage);
                
                $pareceYaTenerTara = ($pesoActual >= 3400 && ($esProbablementeDuplicado || $mensionaTara));
                
                // Solo sumar si hay un peso base y NO parece tener tara ya incluida
                if ($pesoActual > 0 && !$pareceYaTenerTara) {
                     $nuevoPeso = $pesoActual + 3400;
                     $extractedData['extracted']['peso'] = $nuevoPeso;
                     // Asegurar que se incluya en la respuesta
                     $extractedData['extracted']['incluye_tara'] = true;
                     
                     Log::info('📦 TARA agregada en Quick Extraction', [
                         'peso_anterior' => $pesoActual,
                         'nuevo_peso' => $nuevoPeso,
                         'selected_route_index' => $selectedRouteIndex
                     ]);
                } else if ($pareceYaTenerTara) {
                    Log::info('⚠️ TARA NO agregada: ya parece estar incluida', [
                        'peso_actual' => $pesoActual,
                        'peso_sin_posible_tara' => $pesoSinPosibleTara,
                        'es_multiplo_1000' => $esProbablementeDuplicado,
                        'menciona_tara' => $mensionaTara,
                        'razon' => $esProbablementeDuplicado 
                            ? 'Heurístico: (peso - 3400) es múltiplo de 1000, OpenAI probablemente ya sumó tara'
                            : 'Mensaje menciona que tara ya está incluida'
                    ]);
                }
            }
            
            Log::info('✅ DataExtractionService: Extracción completada', [
                'extracted_fields' => array_keys($extractedData['extracted'] ?? []),
                'missing_fields' => $extractedData['missing'] ?? [],
                'has_questions' => !empty($extractedData['questions'] ?? []),
                'confidence' => $extractedData['confidence'] ?? 0
            ]);

            return $extractedData;
            
        } catch (\Exception $e) {
            Log::error('❌ DataExtractionService: Error en extracción', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'extracted' => [],
                'missing' => [],
                'questions' => []
            ];
        }
    }

    /**
     * Construye el prompt del sistema para extracción de datos
     * Optimizado para OpenAI gpt-4o-mini
     */
    private function buildSystemPrompt(array $currentData): string
    {
        return <<<'EOT'
Eres un experto en logística y transporte. Tu tarea es EXTRAER datos de cotizaciones de envíos.

CAMPOS A EXTRAER (EN ESTE ORDEN):
1. origen - Ciudad/lugar de recogida (busca: RUTA:, DIRECCIÓN DE RECOGIDA, FROM, DE, desde, origen)
2. destino - Ciudad/lugar de entrega (busca: destino, DIRECCIÓN DE ENTREGA, TO, HACIA, hacia)
3. peso - Peso total en kg (busca: Weight, kg, tonelada, Gross Weight, PESO, kilos)
4. contenedor - Tipo de contenedor (busca: 1X20, 1X40, HQ, contenedor, caja, bulto, pallet)
5. producto - Mercancía (busca: Mercancía, MERCANCIA, Product, artículo, carga, cargo)
6. valor - Valor declarado (busca: Valor, USD, COP, Price, Valor de la, amount)
7. cantidad - Número de unidades (busca: cantidad, units, bultos, piezas)
8. observaciones - Detalles especiales (cargue, descargue, protocolo, seguridad, etc.)

REGLAS IMPORTANTES:
✓ Convierte SIEMPRE toneladas a kg: 1 tonelada = 1000 kg
✓ Para números: 72.000 USD → 72000 (quita separadores)
✓ Para ciudades: normaliza a formato correcto (Cartagena, Medellín, Bogotá, etc.)
✓ IGNORA palabras previas como "importacion", "exportacion", "cotización de" al extraer nombres de ciudades
✓ Ejemplo: "importacion cartagena a bogotá" → origen: "Cartagena", destino: "Bogotá"
✓ Si dice "Gross Weight: 9900" → peso es 9900
✓ Si dice "1X40 HQ" → contenedor es "1X40 HQ"
✓ NUNCA inventes datos, solo extrae lo visible
✓ Responde SOLO en JSON, sin markdown, sin explicaciones

FORMATO DE RESPUESTA (JSON PURO, SIN MARKDOWN):
{
  "origen": "valor o null",
  "destino": "valor o null",
  "peso": número o null,
  "contenedor": "valor o null",
  "producto": "valor o null",
  "valor": número o null,
  "cantidad": número o null,
  "observaciones": "valor o null",
  "missing": ["lista de campos no encontrados"],
  "confidence": número entre 0 y 1,
  "summary": "resumen breve en una línea"
}

EJEMPLOS:
Input: "Necesito enviar 5 toneladas de maíz de Bogotá a Medellín"
Output: {"origen":"Bogotá","destino":"Medellín","peso":5000,"producto":"maíz","missing":[],"confidence":0.95,"summary":"5 toneladas de maíz de Bogotá a Medellín"}

Input: "RUTA: Cartagena destino: Medellín, 1X40 HQ con 9900 kg, AISLADOR GY, valor 72000 USD"
Output: {"origen":"Cartagena","destino":"Medellín","peso":9900,"contenedor":"1X40 HQ","producto":"AISLADOR GY","valor":72000,"missing":[],"confidence":0.98,"summary":"1X40 HQ con AISLADOR GY de Cartagena a Medellín"}
EOT;
    }

    /**
     * Parsea la respuesta de OpenAI para extraer datos estructurados
     */
    private function parseExtractionResponse(string $response): array
    {
        try {
            $response = trim($response);
            
            // Limpiar markdown si lo hay
            $response = preg_replace('/^```json\s*/i', '', $response);
            $response = preg_replace('/^```\s*/i', '', $response);
            $response = preg_replace('/\s*```$/i', '', $response);
            $response = trim($response);
            
            Log::info('📝 Respuesta limpia:', ['response' => substr($response, 0, 500)]);
            
            // Intentar parsear JSON
            if (preg_match('/\{[\s\S]*\}/m', $response, $matches)) {
                $jsonStr = $matches[0];
                $data = json_decode($jsonStr, true);
                
                if (is_array($data)) {
                    // Extraer datos - puede estar en "extracted" o directamente en el root
                    $extracted = $data['extracted'] ?? $data;
                    
                    // LIMPIAR: solo quedarse con campos de cotización reales
                    $quotationFields = ['origen', 'destino', 'peso', 'contenedor', 'cantidad', 'producto', 'mercancia', 'valor', 'incoterm', 'observaciones'];
                    $cleanExtracted = [];
                    foreach ($quotationFields as $field) {
                        if (isset($extracted[$field])) {
                            $cleanExtracted[$field] = $extracted[$field];
                        }
                    }
                    
                    // Si no hay "missing", calcular basado en los campos limpios
                    $missing = $data['missing'] ?? [];
                    if (empty($missing)) {
                        $missing = $this->calculateMissing($cleanExtracted);
                    }
                    
                    // Normalizar datos extraídos (SOLO los campos limpios)
                    $normalized = $this->normalizeExtractedData($cleanExtracted);
                    
                    return [
                        'success' => true,
                        'extracted' => $normalized,
                        'missing' => array_filter($missing),
                        'questions' => array_filter($data['questions'] ?? []),
                        'confidence' => $data['confidence'] ?? 0.8,
                        'summary' => $data['summary'] ?? '',
                        'raw_response' => $response
                    ];
                }
            }
            
            Log::warning('⚠️ No se pudo parsear JSON', ['response' => substr($response, 0, 200)]);
            return [
                'success' => false,
                'error' => 'No se pudo parsear la respuesta',
                'extracted' => [],
                'missing' => [],
                'questions' => [],
                'raw_response' => $response
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ Error parseando respuesta:', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'extracted' => [],
                'missing' => [],
                'questions' => []
            ];
        }
    }

    /**
     * Calcula qué campos están faltando
     */
    private function calculateMissing(array $extracted): array
    {
        $critical = ['origen', 'destino', 'peso', 'producto'];
        $missing = [];
        
        foreach ($critical as $field) {
            if (!isset($extracted[$field]) || empty($extracted[$field]) || is_null($extracted[$field])) {
                $missing[] = $field;
            }
        }
        
        return $missing;
    }

    /**
     * Normaliza datos extraídos: limpia valores, convierte tipos, etc.
     */
    private function normalizeExtractedData(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            // Saltar nulos
            if (is_null($value) || $value === '') {
                continue;
            }

            // Procesar según el tipo de campo
            switch ($key) {
                case 'peso':
                case 'weight':
                    // Convertir a número, manejar toneladas
                    $num = $this->extractNumber($value);
                    if ($num) {
                        // Si es muy pequeño, probablemente esté en toneladas
                        if ($num < 100 && strpos(strtolower($value), 'ton') !== false) {
                            $num = $num * 1000;
                        }
                        $normalized['peso'] = $num;
                    }
                    break;

                case 'valor':
                case 'value':
                case 'price':
                    // Extraer número sin símbolos
                    $num = $this->extractNumber($value);
                    if ($num) {
                        $normalized['valor'] = $num;
                    }
                    break;

                case 'cantidad':
                case 'quantity':
                case 'amount':
                    $num = $this->extractNumber($value);
                    if ($num) {
                        $normalized['cantidad'] = $num;
                    }
                    break;

                case 'origen':
                case 'destino':
                    // 🆕 NORMALIZACIÓN EXTENDIDA: Abreviaturas y Mayúsculas
                    // Usar la misma lógica de MCPAssistantService si es posible, o replicarla
                    $val = trim($value);
                    
                    // 🔥 POST-PROCESAMIENTO: Eliminar prefijos "importacion", "exportacion", "cotizacion"
                    $val = preg_replace('/^(importaci[oó]n|exportaci[oó]n|cotizaci[oó]n\s+de?)\s+/ui', '', $val);
                    $val = trim($val);
                    
                    $upperVal = mb_strtoupper($val);
                    
                    $abbreviations = [
                        'BOG' => 'BOGOTA', 'MED' => 'MEDELLIN', 'CLO' => 'CALI', 
                        'BAQ' => 'BARRANQUILLA', 'CTG' => 'CARTAGENA', 'BGA' => 'BUCARAMANGA',
                        'CUC' => 'CUCUTA', 'PEI' => 'PEREIRA', 'MZL' => 'MANIZALES',
                        'AXM' => 'ARMENIA', 'IBE' => 'IBAGUE', 'NVA' => 'NEIVA',
                        'VVC' => 'VILLAVICENCIO', 'PSO' => 'PASTO', 'PPN' => 'POPAYAN',
                        'SMR' => 'SANTA MARTA', 'CVE' => 'SINCELEJO', 'MTR' => 'MONTERIA',
                        'VUP' => 'VALLEDUPAR', 'RCH' => 'RIOHACHA', 'UIB' => 'QUIBDO',
                        'LET' => 'LETICIA', 'ADZ' => 'SAN ANDRES', 'EYP' => 'YOPAL',
                        'AUC' => 'ARAUCA', 'FLA' => 'FLORENCIA', 'MCO' => 'MOCOA',
                        'TUN' => 'TUNJA', 'DUI' => 'DUITAMA', 'SOG' => 'SOGAMOSO',
                        'GIR' => 'GIRARDOT', 'ZIP' => 'ZIPAQUIRA', 'FAC' => 'FACATATIVA',
                        'SOA' => 'SOACHA'
                    ];
                    
                    if (isset($abbreviations[$upperVal])) {
                        $normalized[$key] = $abbreviations[$upperVal];
                    } else {
                        // Eliminar acentos y convertir a mayúsculas
                        $replacements = [
                            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U',
                            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
                            'ñ' => 'N', 'Ñ' => 'N'
                        ];
                        $normalized[$key] = mb_strtoupper(strtr($val, $replacements), 'UTF-8');
                    }
                    break;
                    
                case 'contenedor':
                case 'producto':
                case 'mercancia':
                case 'incoterm':
                case 'observaciones':
                    // Capitalizar y limpiar
                    $normalized[$key] = trim($value);
                    break;

                default:
                    // Mantener tal cual si no reconocemos el campo
                    if (!empty($value)) {
                        $normalized[$key] = $value;
                    }
            }
        }

        return $normalized;
    }

    /**
     * Extrae el primer número de un string, manejando puntos y comas
     */
    private function extractNumber(string $value): ?float
    {
        // Eliminar símbolos de moneda y letras, mantener números y puntos/comas
        $cleaned = preg_replace('/[^\d.,]/', '', $value);
        
        if (empty($cleaned)) {
            return null;
        }

        // Si tiene coma como decimal (formato europeo), convertir
        // 72.000,50 → 72000.50
        if (substr_count($cleaned, '.') > 0 && substr_count($cleaned, ',') > 0) {
            // Múltiples puntos y comas - punto es separador de miles
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        } elseif (substr_count($cleaned, '.') > 1) {
            // Solo puntos múltiples - son separadores de miles
            $cleaned = str_replace('.', '', $cleaned);
        } elseif (substr_count($cleaned, ',') > 1) {
            // Solo comas múltiples - son separadores de miles
            $cleaned = str_replace(',', '', $cleaned);
        }

        $number = floatval($cleaned);
        return $number > 0 ? $number : null;
    }

    /**
     * Genera una respuesta contextuada para el usuario basada en datos extraídos
     */
    public function generateContextualResponse(array $extractedData, array $missingFields): string
    {
        $response = '';

        // Si hay datos extraídos
        if (!empty($extractedData)) {
            $response .= "✅ He identificado los siguientes detalles de tu cotización:\n\n";
            
            foreach ($extractedData as $field => $value) {
                $fieldLabel = $this->humanizeFieldName($field);
                $response .= "- **$fieldLabel**: $value\n";
            }
        }

        // Si hay campos faltantes críticos
        if (!empty($missingFields)) {
            $response .= "\n📋 Para completar tu cotización, necesito que me proporciones:\n\n";
            
            foreach ($missingFields as $field) {
                $prompt = $this->getFieldPrompt($field);
                $response .= "- $prompt\n";
            }
        } else if (!empty($extractedData)) {
            $response .= "\n✨ Perfecto, tengo toda la información que necesito para procesar tu cotización.";
        }

        return trim($response);
    }

    /**
     * Convierte nombres de campos a texto legible
     */
    private function humanizeFieldName(string $field): string
    {
        $labels = [
            'origen' => 'Origen',
            'destino' => 'Destino',
            'peso' => 'Peso',
            'cantidad' => 'Cantidad',
            'contenedor' => 'Tipo de Contenedor',
            'producto' => 'Producto/Mercancía',
            'valor' => 'Valor Declarado',
            'incoterm' => 'Incoterm',
            'observaciones' => 'Observaciones'
        ];

        return $labels[$field] ?? ucfirst($field);
    }

    /**
     * Obtiene un mensaje de solicitud apropiado para cada campo
     */
    private function getFieldPrompt(string $field): string
    {
        $prompts = [
            'origen' => '¿Cuál es la **ciudad de origen** del envío?',
            'destino' => '¿Cuál es la **ciudad de destino**?',
            'peso' => '¿Cuál es el **peso total** de la mercancía? (en kg o toneladas)',
            'cantidad' => '¿Cuántas **unidades o bultos** comprende el envío?',
            'contenedor' => '¿Qué tipo de **empaque o contenedor** se utilizará?',
            'producto' => '¿Cuál es el **tipo de producto o mercancía** que se transportará?',
            'valor' => '¿Cuál es el **valor declarado** de la mercancía?',
            'incoterm' => '¿Cuál es el **incoterm** aplicable (DDP, CIF, FOB)?',
            'observaciones' => '¿Hay alguna **observación especial** que deba considerar?'
        ];

        return $prompts[$field] ?? "Información faltante: $field";
    }
}
