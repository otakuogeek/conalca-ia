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
        // 🔧 FIX: Detectar modo edición para cambiar comportamiento
        $isEditMode = !empty($currentData) && count($currentData) > 0;
        
        Log::info('🔍 DataExtractionService: Procesando mensaje con OpenAI', [
            'message_length' => strlen($userMessage),
            'current_data_keys' => array_keys($currentData),
            'selected_route_index' => $selectedRouteIndex,
            'is_edit_mode' => $isEditMode
        ]);

        $systemPrompt = $this->buildSystemPrompt($currentData, $isEditMode);
        
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
            
            // 🔧 FIX #2: Detectar si estamos editando una ruta existente
            // Si $currentData tiene datos, estamos en modo edición y NO debemos recalcular tara
            $esEdicionCampo = !empty($currentData) && count($currentData) > 0;
            
            // 🔧 FIX: Detectar si debe agregar tara
            // Casos que requieren agregar tara:
            // 1. "agrega/incluye/suma tara" (explícito) - SIEMPRE, incluso en edición
            // 2. "peso X suma tara" o "peso X kg suma tara" (nuevo patrón)
            // 3. "el peso no incluye tara" o "sin tara" o "peso neto" (explícito negativo)
            // 4. Default: si NO dice "tara incluida/con tara/peso bruto", agregar tara (solo para nuevas rutas)
            $agregarTaraExplicito = preg_match('/(?:agrega|añade|suma|pon|incluye|incluir|agregar|sumar|ponga)\s+(?:la\s+)?tara/ui', $lastUserMessage);
            // 🆕 Nuevo patrón: "peso 25000 suma tara" o "peso 25000 kg suma tara"
            $pesoConSumaTara = preg_match('/peso\s+([\d.,]+)\s*(?:kg|kilos?)?\s+(?:suma|agrega|añade|pon(?:ga|er)?|incluye|agregar|sumar)\s+(?:la\s+)?tara/ui', $lastUserMessage, $matchPesoTara);
            // 🔧 FIX: Agregar detección de "+ tara" y "más tara" (significa que hay que SUMAR la tara)
            // Ejemplo: "Peso: 20 toneladas + tara" significa que el peso NO incluye tara y hay que sumarla
            $noIncluyeTara = preg_match('/(?:no\s+incluye|sin)\s+(?:la\s+)?tara|peso\s+neto|el\s+peso\s+no\s+incluye|\+\s*tara|m[aá]s\s+tara/ui', $lastUserMessage);
            
            // 🆕 FIX CRÍTICO: Mejorar detección de "tara incluida" / "con tara incluida"
            // Patrones que indican que el peso YA INCLUYE la tara (NO agregar):
            // - "con tara incluida" / "con la tara incluida"
            // - "tara incluida" 
            // - "con tara" (pero NO "sin tara")
            // - "peso bruto"
            // - "ya incluye tara" / "ya tiene tara"
            // - "kilogramos con tara" / "kg con tara"
            $yaIncluyeTara = preg_match('/(?:con\s+(?:la\s+)?tara\s+incluida|tara\s+(?:ya\s+)?incluida|con\s+tara(?!\s+(?:no|sin))|peso\s+bruto|ya\s+(?:incluye|tiene)\s+(?:la\s+)?tara|(?:kilos?|kilogramos?|kg)\s+con\s+tara)/ui', $lastUserMessage);
            
            Log::info('🔍 Análisis de tara en mensaje', [
                'mensaje_original' => substr($lastUserMessage, 0, 150),
                'agrega_tara_explicito' => (bool)$agregarTaraExplicito,
                'peso_con_suma_tara' => (bool)$pesoConSumaTara,
                'no_incluye_tara' => (bool)$noIncluyeTara,
                'ya_incluye_tara' => (bool)$yaIncluyeTara,
                'es_edicion' => $esEdicionCampo
            ]);
            
            // 🔧 FIX CRÍTICO: Si el usuario EXPLÍCITAMENTE dice "suma tara" o "agrega tara" o "+ tara", 
            // SIEMPRE agregar la tara, incluso en modo edición
            // Detectar "+ tara" como comando explícito también
            $taraConSignoMas = preg_match('/\+\s*tara|m[aá]s\s+tara/ui', $lastUserMessage);
            $comandoExplicitoTara = $agregarTaraExplicito || $pesoConSumaTara || $taraConSignoMas;
            
            // 🆕 FIX CRÍTICO: Si el usuario dice "con tara incluida", NO agregar tara
            // La tara solo se agrega si:
            // 1. Hay comando explícito ("suma tara") Y NO dice "ya incluye tara"
            // 2. NO está editando Y dice explícitamente "sin tara" Y NO dice "ya incluye tara"
            // 3. NO está editando Y NO dice "ya incluye tara" Y NO dice "con tara incluida"
            $debeAgregarTara = false;
            
            if ($yaIncluyeTara) {
                // Usuario dice "con tara incluida" - NO agregar tara
                Log::info('✅ Usuario indicó que peso YA INCLUYE TARA - NO se agregará', [
                    'ya_incluye_tara' => true
                ]);
                $debeAgregarTara = false;
            } else if ($comandoExplicitoTara) {
                // Comando explícito "suma tara" - agregar tara
                Log::info('⚡ Comando explícito para agregar tara detectado');
                $debeAgregarTara = true;
            } else if (!$esEdicionCampo && $noIncluyeTara) {
                // Mensaje dice "sin tara" o "+ tara" (sin que sea "con tara")
                Log::info('🔧 Usuario indica que peso NO incluye tara - se agregará');
                $debeAgregarTara = true;
            } else if (!$esEdicionCampo) {
                // Por defecto para nuevas rutas, agregar tara (comportamiento legacy)
                // SOLO si NO dijo explícitamente "con tara"
                Log::info('📦 Nueva ruta sin indicación de tara - se agregará por defecto');
                $debeAgregarTara = true;
            }
            
            if ($debeAgregarTara && !$yaIncluyeTara) {
                // 🆕 FIX: Si se detectó patrón "peso X suma tara", usar ese peso específico
                if ($pesoConSumaTara && isset($matchPesoTara[1])) {
                    $pesoDelMensaje = $matchPesoTara[1];
                    // Normalizar formato de peso (manejar 12.400 como 12400, no 12.4)
                    $pesoDelMensaje = str_replace(['.', ','], ['', '.'], $pesoDelMensaje);
                    $pesoActual = (float) $pesoDelMensaje;
                    Log::info('🎯 Peso extraído del patrón "peso X suma tara"', [
                        'peso_raw' => $matchPesoTara[1],
                        'peso_normalizado' => $pesoActual
                    ]);
                } else {
                    // Buscar peso en extracción actual o datos previos
                    $pesoActual = $extractedData['extracted']['peso'] ?? $currentData['peso_mercancia'] ?? $currentData['peso'] ?? 0;
                }
                
                // Limpiar peso si viene como string
                if (is_string($pesoActual)) {
                    $pesoActual = (float) preg_replace('/[^0-9.]/', '', $pesoActual);
                }
                
                // 🔥 HEURÍSTICO MEJORADO: Detectar si OpenAI ya sumó la tara
                // PERO si hay comando explícito "suma tara", NO aplicar heurístico (el usuario quiere sumar)
                if ($comandoExplicitoTara) {
                    // Usuario pidió explícitamente sumar tara, NO verificar heurísticos
                    $pareceYaTenerTara = false;
                    Log::info('⚡ Comando explícito de tara detectado - ignorando heurísticos', [
                        'comando' => $pesoConSumaTara ? 'peso X suma tara' : 'agrega/suma tara'
                    ]);
                } else {
                    // 🔧 FIX: Verificar para ambas taras posibles (2300 y 3400)
                    // Si (peso - tara) es múltiplo exacto de 1000, probablemente ya tiene tara
                    $pesoSinPosibleTara3400 = $pesoActual - 3400;
                    $pesoSinPosibleTara2300 = $pesoActual - 2300;
                    $esProbablementeDuplicado = (
                        ($pesoSinPosibleTara3400 > 0 && $pesoSinPosibleTara3400 % 1000 == 0) ||
                        ($pesoSinPosibleTara2300 > 0 && $pesoSinPosibleTara2300 % 1000 == 0)
                    );
                    
                    // También verificar si el mensaje menciona "con tara" o "ya tara"
                    $mensionaTara = preg_match('/(?<!no\s)(?:con\s+tara|ya.*tara|tara\s+incluida|peso\s+bruto)/ui', $lastUserMessage);
                    
                    $pareceYaTenerTara = ($pesoActual >= 2300 && ($esProbablementeDuplicado || $mensionaTara));
                }
                
                // Solo sumar si hay un peso base y NO parece tener tara ya incluida
                if ($pesoActual > 0 && !$pareceYaTenerTara) {
                     // 🔧 FIX CRÍTICO: Determinar tara según tamaño de contenedor
                     // Contenedor de 20 pies = 2300 kg, otros (40, 45) = 3400 kg
                     $taraAplicar = 3400; // Default
                     
                     // Verificar si hay información de contenedor en los datos extraídos
                     $empaque = $extractedData['extracted']['empaque'] ?? '';
                     $contenedor = $extractedData['extracted']['contenedor'] ?? '';
                     
                     // 🔧 FIX CRÍTICO v2: PRIMERO verificar el mensaje ORIGINAL del usuario
                     // Esto es más confiable que la extracción del AI que puede equivocarse
                     // Prioridad: Mensaje original > datos extraídos por IA
                     $esContenedor40EnMensaje = preg_match('/(?:contenedor|cont).*?\b40\b|\b40\s*(?:pies|\')|\b\d+[xX]40\b/ui', $userMessage);
                     $esContenedor20EnMensaje = preg_match('/(?:contenedor|cont).*?\b20\b|(?<![04])\b20\s*(?:pies|\')|\b\d+[xX]20\b/ui', $userMessage);
                     
                     if ($esContenedor40EnMensaje && !$esContenedor20EnMensaje) {
                         // Mensaje original dice contenedor 40 → tara 3400
                         $taraAplicar = 3400;
                         Log::info('📦 Contenedor 40 detectado en mensaje ORIGINAL → tara 3400');
                     } elseif ($esContenedor20EnMensaje && !$esContenedor40EnMensaje) {
                         // Mensaje original dice contenedor 20 → tara 2300
                         $taraAplicar = 2300;
                         Log::info('📦 Contenedor 20 detectado en mensaje ORIGINAL → tara 2300');
                     } elseif (preg_match('/CONTENEDOR\s*20|20\s*pies/i', $empaque) || 
                         preg_match('/\d+[xX]20/i', $contenedor) ||
                         preg_match('/contenedor\s+de\s+20/i', $lastUserMessage)) {
                         // Fallback: usar datos extraídos por IA
                         $taraAplicar = 2300;
                         Log::info('📦 Contenedor 20 detectado en datos extraídos por IA → tara 2300');
                     }
                     
                     $nuevoPeso = $pesoActual + $taraAplicar;
                     $extractedData['extracted']['peso'] = $nuevoPeso;
                     // 🔧 FIX: peso_kg debe estar en KG, no en toneladas
                     $extractedData['extracted']['peso_kg'] = $nuevoPeso;
                     $extractedData['extracted']['tara'] = $taraAplicar;
                     // Asegurar que se incluya en la respuesta
                     $extractedData['extracted']['incluye_tara'] = true;
                     
                     Log::info('📦 TARA agregada en Quick Extraction', [
                         'peso_anterior' => $pesoActual,
                         'tara_aplicada' => $taraAplicar,
                         'nuevo_peso' => $nuevoPeso,
                         'empaque' => $empaque,
                         'contenedor' => $contenedor,
                         'comando_explicito' => $comandoExplicitoTara,
                         'es_edicion' => $esEdicionCampo,
                         'selected_route_index' => $selectedRouteIndex
                     ]);
                } else if ($pareceYaTenerTara && !$comandoExplicitoTara) {
                    Log::info('⚠️ TARA NO agregada: ya parece estar incluida', [
                        'peso_actual' => $pesoActual,
                        'razon' => 'Heurístico detectó que probablemente ya tiene tara'
                    ]);
                } else if ($pesoActual <= 0) {
                    Log::info('⚠️ TARA NO agregada: peso es 0 o negativo', [
                        'peso_actual' => $pesoActual
                    ]);
                }
            } else if ($esEdicionCampo) {
                // 🔧 FIX #2: Si estamos editando, NO recalcular tara
                Log::info('🔧 DataExtractionService - Modo edición detectado: NO recalcular tara', [
                    'es_edicion' => $esEdicionCampo,
                    'current_data_keys' => array_keys($currentData),
                    'peso_mantenido' => $currentData['peso_mercancia'] ?? $currentData['peso'] ?? null
                ]);
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
    private function buildSystemPrompt(array $currentData, bool $isEditMode = false): string
    {
        // 🔧 FIX CRÍTICO: Si estamos en modo edición, usar prompt especial
        if ($isEditMode) {
            return $this->buildEditModePrompt($currentData);
        }
        
        return <<<'EOT'
Eres un experto en logística y transporte. Tu tarea es EXTRAER datos de cotizaciones de envíos.

⚠️ DETECCIÓN DE MULTI-RUTAS (PRIORIDAD MÁXIMA):
ANTES de extraer datos, verifica si el mensaje solicita MÚLTIPLES RUTAS:
- Frases clave: "dos rutas", "tres rutas", "varias rutas", "múltiples rutas"
- Patrones: "Una es... La otra es...", "La primera... La segunda...", "Ruta 1... Ruta 2..."
- Ejemplos: "necesito dos rutas, una de Bogotá a Cali... y otra de Medellín a Cartagena"

SI DETECTAS MÚLTIPLES RUTAS:
1. Extrae CADA ruta por separado
2. Responde en este formato:
{
  "multi_ruta": true,
  "total_rutas": 2,
  "rutas": [
    {
      "origen": "Ciudad1",
      "destino": "Ciudad2",
      "peso": 1000,
      "cantidad": 50,
      "empaque": "cajas",
      "producto": "alimentos",
      "valor": 1000000,
      "vehiculo": "TURBO",
      "contenedor": "carga suelta",
      "incluye_tara": false
    },
    {
      "origen": "Ciudad3",
      "destino": "Ciudad4",
      "peso": 2000,
      "cantidad": 100,
      "empaque": "bultos",
      "producto": "textiles",
      "valor": 2000000,
      "vehiculo": "TRACTOCAMION",
      "contenedor": "contenedor 20 pies",
      "incluye_tara": true
    }
  ],
  "confidence": 0.9
}

CAMPOS A EXTRAER POR CADA RUTA (EN ESTE ORDEN):
1. origen - Ciudad/lugar de recogida
2. destino - Ciudad/lugar de entrega
3. peso - Peso total en kg (convierte toneladas: 1 ton = 1000 kg)
4. cantidad - Número de unidades (VER REGLA DE CONTENEDORES ABAJO)
5. empaque - Tipo de empaque (cajas, bultos, estibas, CONTENEDOR 20, CONTENEDOR 40)
6. producto - Mercancía real a transportar
7. valor - Valor declarado en COP
8. vehiculo - Tipo de vehículo (NORMALIZADO: TURBO, SENCILLO, TRACTOCAMION, PATINETA, CAMIONETA, DOBLETROQUE). IMPORTANTE: "camión sencillo" = SENCILLO, "camión turbo" = TURBO
9. contenedor - Tipo de contenedor o empaque especial
10. incluye_tara - IMPORTANTE: true si dice "tara incluida", "con tara", "peso bruto"; false si dice "sin tara", "no incluye tara", "peso neto", o NO menciona nada de tara

⚠️ REGLA CRÍTICA DE CANTIDAD CON CONTENEDORES:
- Si se menciona CONTENEDOR, la cantidad es el NÚMERO DE CONTENEDORES, no el contenido interno
- Ejemplo: "1 contenedor de 40 pies con 850 cajas" → cantidad: 1 (NO 850)
- Ejemplo: "2 contenedores con 500 bultos cada uno" → cantidad: 2 (NO 500 ni 1000)
- Si NO hay contenedor mencionado: "850 cajas de herramientas" → cantidad: 850

⚠️ REGLA CRÍTICA DE CANTIDAD CUANDO CONTENEDORES SE SEPARAN EN RUTAS:
- Si N contenedores del mismo tipo tienen PESOS DIFERENTES o CARACTERÍSTICAS DIFERENTES y se crean N rutas separadas, 
  CADA RUTA tiene cantidad: 1, porque cada ruta representa UN SOLO contenedor.
- Ejemplo: "2 contenedores de 20 pies: uno con 8.000 kg sin tara y el otro con 20.000 kg con tara incluida"
  → multi_ruta: true, total_rutas: 2
  → Ruta 1: peso: 8000, cantidad: 1, incluye_tara: false
  → Ruta 2: peso: 20000, cantidad: 1, incluye_tara: true
  ❌ INCORRECTO: Ruta 1: cantidad: 2, Ruta 2: cantidad: 2
- Clave: si "uno con X... el otro con Y" separa los contenedores, es 1 por ruta

⚠️ FORMATO DE CONTENEDORES (MUY IMPORTANTE):
- "1x40'HC" = 1 contenedor de 40 pies High Cube → empaque: "CONTENEDOR 40", contenedor: "1X40' HC", cantidad: 1
- "1x20'HC" = 1 contenedor de 20 pies High Cube → empaque: "CONTENEDOR 20", contenedor: "1X20' HC", cantidad: 1
- "2x40GP" = 2 contenedores de 40 pies estándar → empaque: "CONTENEDOR 40", contenedor: "2X40' GP", cantidad: 2
- "40HC", "40'HC" = contenedor de 40 pies → empaque: "CONTENEDOR 40"
- "20GP", "20'GP" = contenedor de 20 pies → empaque: "CONTENEDOR 20"

⚠️ REGLA CRÍTICA DE TARA:
- Si dice "X kg con tara incluida" o "peso incluye tara" → incluye_tara: true
- Si dice "X kg sin tara" o "peso no incluye tara" o "peso neto" → incluye_tara: false
- Si dice "X kg + tara" o "peso más tara" → incluye_tara: false (significa que hay que SUMAR la tara, el peso dado NO incluye tara)
- Si NO menciona nada sobre tara → incluye_tara: false (default)

REGLAS IMPORTANTES:
✓ Convierte SIEMPRE toneladas a kg: 1 tonelada = 1000 kg, 2.5 toneladas = 2500 kg
✓ Para valores: 45 millones = 45000000, 20 millones = 20000000
✓ Normaliza ciudades: BOGOTA, MEDELLIN, CARTAGENA, BUENAVENTURA, CALI
✓ Separa vehículo de producto: "turbo" es vehículo, "alimentos" es producto
✓ NUNCA inventes datos, solo extrae lo visible
✓ Responde SOLO en JSON puro, sin markdown ```json```

FORMATO DE RESPUESTA RUTA ÚNICA:
{
  "multi_ruta": false,
  "origen": "CIUDAD",
  "destino": "CIUDAD",
  "peso": número_en_kg,
  "cantidad": número,
  "empaque": "tipo",
  "producto": "mercancía",
  "valor": número,
  "vehiculo": "tipo",
  "contenedor": "descripción",
  "confidence": 0.9
}

⚠️ REGLA CRÍTICA DE PESO "C/U" o "CADA UNO":
- Si dice "Peso: X toneladas c/u" o "X kg cada uno" o "X toneladas por cada contenedor" → APLICA EL MISMO PESO A TODAS LAS RUTAS
- Ejemplo: "2x40 // 1x20 ... Peso: 15 toneladas sin tara c/u" → Ruta 1: peso=15000, Ruta 2: peso=15000 (AMBAS 15 toneladas)
- El peso "c/u" (cada uno) significa que CADA ruta tiene ESE peso individualmente
- NO dividas el peso entre las rutas, cada una tiene el peso completo

EJEMPLOS MULTI-RUTA:
Input: "Cotiza dos rutas: Bogotá a Cartagena, 10500 kg con tara incluida, 520 cajas. La segunda Cali a Buenaventura, 2900 kg sin tara, 75 cajas"
Output: {
  "multi_ruta": true,
  "total_rutas": 2,
  "rutas": [
    {"origen":"BOGOTA","destino":"CARTAGENA","peso":10500,"cantidad":520,"empaque":"cajas","producto":null,"vehiculo":null,"contenedor":null,"valor":null,"incluye_tara":true},
    {"origen":"CALI","destino":"BUENAVENTURA","peso":2900,"cantidad":75,"empaque":"cajas","producto":null,"vehiculo":null,"contenedor":null,"valor":null,"incluye_tara":false}
  ],
  "confidence":0.9
}

Input: "2x40 // 1x20 Retiro: Medellín Destino: Cartagena Peso: 15 toneladas sin tara c/u"
Output: {
  "multi_ruta": true,
  "total_rutas": 2,
  "rutas": [
    {"origen":"MEDELLIN","destino":"CARTAGENA","peso":15000,"cantidad":2,"empaque":"CONTENEDOR 40","producto":null,"vehiculo":"TRACTOCAMION","contenedor":"2X40' GP","valor":null,"incluye_tara":false},
    {"origen":"MEDELLIN","destino":"CARTAGENA","peso":15000,"cantidad":1,"empaque":"CONTENEDOR 20","producto":null,"vehiculo":"TRACTOCAMION","contenedor":"1X20' GP","valor":null,"incluye_tara":false}
  ],
  "confidence":0.9
}

EJEMPLO RUTA ÚNICA:
Input: "Necesito enviar 8 toneladas de alimentos de Bogotá a Buenaventura, son 120 cajas, valor 45 millones, en tracto para contenedor de 20 pies"
Output: {"multi_ruta":false,"origen":"BOGOTA","destino":"BUENAVENTURA","peso":8000,"cantidad":120,"empaque":"cajas","producto":"alimentos","valor":45000000,"vehiculo":"tractocamión","contenedor":"contenedor de 20 pies","incluye_tara":false,"confidence":0.95}
EOT;
    }

    /**
     * 🔧 FIX CRÍTICO: Prompt especial para modo EDICIÓN
     * Solo extrae campos EXPLÍCITAMENTE mencionados, NO inventa valores
     */
    private function buildEditModePrompt(array $currentData): string
    {
        $currentDataJson = json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return <<<EOT
Eres un experto en logística. ESTÁS EN MODO EDICIÓN de una ruta existente.

⚠️ REGLA CRÍTICA - MODO EDICIÓN:
El usuario quiere MODIFICAR campos específicos de una ruta existente.
SOLO extrae los campos que el usuario MENCIONA EXPLÍCITAMENTE en su mensaje.
Para campos NO mencionados, devuelve null - NO inventes ni rellenes datos.

DATOS ACTUALES DE LA RUTA:
$currentDataJson

CAMPOS POSIBLES:
- origen: Ciudad de origen (SOLO si menciona origen/recogida/desde)
- destino: Ciudad de destino (SOLO si menciona destino/entrega/hacia/a)
- peso: Peso en kg (SOLO si menciona peso/kg/toneladas)
- cantidad: Número de unidades (SOLO si menciona cantidad/unidades/cajas)
- empaque: Tipo empaque (SOLO si menciona empaque/embalaje/cajas/bultos)
- producto: Mercancía (SOLO si menciona producto/mercancía/tipo de producto)
- valor: Valor declarado (SOLO si menciona valor/precio/millones)
- vehiculo: Tipo vehículo (SOLO si menciona vehículo/camión/tracto/turbo)
- contenedor: Tipo contenedor (SOLO si menciona contenedor/20 pies/40 pies)

EJEMPLOS CORRECTOS:

Input: "origen cali, destino bogotá, cantidad 478"
Output: {"origen":"CALI","destino":"BOGOTA","cantidad":478,"peso":null,"empaque":null,"producto":null,"valor":null,"vehiculo":null,"contenedor":null,"is_edit":true}

Input: "cambia el valor a 50 millones"
Output: {"valor":50000000,"origen":null,"destino":null,"peso":null,"cantidad":null,"empaque":null,"producto":null,"vehiculo":null,"contenedor":null,"is_edit":true}

Input: "peso 5000 kg"
Output: {"peso":5000,"origen":null,"destino":null,"cantidad":null,"empaque":null,"producto":null,"valor":null,"vehiculo":null,"contenedor":null,"is_edit":true}

REGLAS:
✓ SOLO incluir campos que el usuario menciona EXPLÍCITAMENTE
✓ Devolver null para campos NO mencionados (NO inventar)
✓ Normalizar ciudades a MAYÚSCULAS sin acentos
✓ Convertir toneladas a kg (1 ton = 1000 kg)
✓ Convertir millones a número (45 millones = 45000000)
✓ Incluir "is_edit": true en la respuesta
✓ Responder SOLO JSON puro sin markdown
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
            
            Log::info('📝 Respuesta limpia de OpenAI:', ['response' => substr($response, 0, 500)]);
            
            // Intentar parsear JSON
            if (preg_match('/\{[\s\S]*\}/m', $response, $matches)) {
                $jsonStr = $matches[0];
                $data = json_decode($jsonStr, true);
                
                if (is_array($data)) {
                    // 🚛 DETECTAR MULTI-RUTA PRIMERO
                    if (isset($data['multi_ruta']) && $data['multi_ruta'] === true && isset($data['rutas'])) {
                        Log::info('🚛 Multi-ruta detectada:', ['total' => count($data['rutas'])]);
                        
                        // Normalizar cada ruta
                        $rutasNormalizadas = [];
                        foreach ($data['rutas'] as $idx => $ruta) {
                            $normalized = $this->normalizeExtractedData($ruta);
                            $rutasNormalizadas[] = $normalized;
                            Log::info("📍 Ruta " . ($idx + 1) . " normalizada:", $normalized);
                        }
                        
                        return [
                            'success' => true,
                            'multi_ruta' => true,
                            'total_rutas' => count($rutasNormalizadas),
                            'rutas' => $rutasNormalizadas,
                            'confidence' => $data['confidence'] ?? 0.9,
                            'raw_response' => $response
                        ];
                    }
                    
                    // RUTA ÚNICA (código original)
                    $extracted = $data['extracted'] ?? $data;
                    
                    // LIMPIAR: solo quedarse con campos de cotización reales
                    $quotationFields = ['origen', 'destino', 'peso', 'contenedor', 'cantidad', 'producto', 'mercancia', 'valor', 'incoterm', 'observaciones', 'vehiculo', 'empaque'];
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
                        'multi_ruta' => false,
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
                case 'observaciones':
                    // Capitalizar y limpiar
                    $normalized[$key] = trim($value);
                    break;
                
                case 'empaque':
                    // Convertir a mayúsculas para estos campos críticos
                    $val = trim($value);
                    $normalized[$key] = mb_strtoupper($val, 'UTF-8');
                    break;
                
                case 'vehiculo':
                    // 🔧 FIX: Normalizar vehículo - "camión sencillo" → "SENCILLO"
                    $val = mb_strtolower(trim($value), 'UTF-8');
                    $vehiculoNormalizado = self::normalizeVehiculo($val);
                    $normalized[$key] = $vehiculoNormalizado;
                    Log::info('🚛 Vehículo normalizado en DataExtractionService', [
                        'original' => $value,
                        'normalizado' => $vehiculoNormalizado
                    ]);
                    break;
                
                case 'incoterm':
                    // INCOTERM siempre en mayúsculas
                    $normalized[$key] = mb_strtoupper(trim($value), 'UTF-8');
                    break;
                
                case 'incluye_tara':
                    // Convertir a booleano explícitamente
                    $normalized[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;

                default:
                    // Mantener tal cual si no reconocemos el campo
                    if (!empty($value)) {
                        $normalized[$key] = $value;
                    }
            }
        }

        // 🔧 FIX: Post-procesamiento para combinar empaque CONTENEDOR con tamaño
        // Si el empaque es "CONTENEDOR" genérico, verificar el campo 'contenedor' para obtener el tamaño
        if (isset($normalized['empaque']) && isset($normalized['contenedor'])) {
            $empaqueUpper = mb_strtoupper($normalized['empaque']);
            $contenedor = mb_strtolower($normalized['contenedor']);
            
            // Si el empaque es CONTENEDOR genérico (sin tamaño)
            if ($empaqueUpper === 'CONTENEDOR' || (strpos($empaqueUpper, 'CONTENEDOR') !== false && !preg_match('/\d+/', $empaqueUpper))) {
                // Buscar tamaño en el campo contenedor: "contenedor de 20 pies", "20'", "40 pies"
                if (preg_match('/(\d+)\s*(?:pies|\'|")?/i', $contenedor, $matches)) {
                    $tamaño = $matches[1];
                    if ($tamaño == '20') {
                        $normalized['empaque'] = 'CONTENEDOR 20';
                        Log::info('📦 Empaque actualizado con tamaño de contenedor', [
                            'empaque_original' => $empaqueUpper,
                            'contenedor' => $normalized['contenedor'],
                            'empaque_final' => $normalized['empaque']
                        ]);
                    } elseif ($tamaño == '40') {
                        $normalized['empaque'] = 'CONTENEDOR 40';
                        Log::info('📦 Empaque actualizado con tamaño de contenedor', [
                            'empaque_original' => $empaqueUpper,
                            'contenedor' => $normalized['contenedor'],
                            'empaque_final' => $normalized['empaque']
                        ]);
                    }
                }
            }
        }
        
        // 🔧 FIX: También verificar si el empaque mencionado directamente tiene tamaño
        // "contenedor de 20 pies" debería resultar en "CONTENEDOR 20"
        if (isset($normalized['empaque'])) {
            $empaqueUpper = mb_strtoupper($normalized['empaque']);
            if (preg_match('/CONTENEDOR.*?(\d+)/i', $empaqueUpper, $matches)) {
                $tamaño = $matches[1];
                if ($tamaño == '20') {
                    $normalized['empaque'] = 'CONTENEDOR 20';
                } elseif ($tamaño == '40') {
                    $normalized['empaque'] = 'CONTENEDOR 40';
                }
            }
        }
        
        // 🔧 FIX: Detectar formato "1x40'HC", "2x20GP" en el campo contenedor para extraer el empaque
        // Si el contenedor tiene formato NxTAMAÑO'TIPO, extraer el tamaño para el empaque
        if (isset($normalized['contenedor'])) {
            $contenedorUpper = mb_strtoupper($normalized['contenedor']);
            // Patrón: "1X40'HC", "2X20GP", "1X40 HC", etc.
            if (preg_match('/(\d+)\s*[Xx]\s*(20|40|45)\s*[\'"]?\s*(HQ|HC|GP|RF|OT|FR)?/i', $contenedorUpper, $matches)) {
                $tamaño = $matches[2];
                if ($tamaño == '20') {
                    $normalized['empaque'] = 'CONTENEDOR 20';
                } elseif ($tamaño == '40' || $tamaño == '45') {
                    $normalized['empaque'] = 'CONTENEDOR 40';
                }
                Log::info('📦 Empaque detectado de formato contenedor NxTAMAÑO', [
                    'contenedor' => $normalized['contenedor'],
                    'empaque_final' => $normalized['empaque']
                ]);
            }
        }

        // 🔧 FIX: Validar que el "producto" NO sea un vehículo
        // Si la IA devolvió un nombre de vehículo como producto, corregirlo
        $vehiculosConocidos = ['patineta', 'tractomula', 'turbo', 'sencillo', 'dobletroque', 'camioneta', 
                               'minimula', 'tractocamión', 'tractocamion', 'camion', 'camión', 'trailer',
                               'furgon', 'furgón', 'niñera', 'ninera', 'mula', 'doble troque', 'cama baja',
                               'camabaja', 'estacas', 'plataforma', 'carrotanque', 'volqueta'];
        
        if (isset($normalized['producto']) && !empty($normalized['producto'])) {
            $productoLower = mb_strtolower(trim($normalized['producto']), 'UTF-8');
            foreach ($vehiculosConocidos as $vehiculo) {
                if ($productoLower === $vehiculo || strpos($productoLower, $vehiculo) !== false) {
                    Log::info('🚛 Corrigiendo: producto era un vehículo, moviendo al campo vehiculo', [
                        'producto_original' => $normalized['producto'],
                        'vehiculo_detectado' => $vehiculo
                    ]);
                    // Si no hay vehículo asignado, usar este
                    if (empty($normalized['vehiculo'])) {
                        $normalized['vehiculo'] = mb_strtoupper($normalized['producto'], 'UTF-8');
                    }
                    // Limpiar el producto
                    $normalized['producto'] = null;
                    break;
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

    /**
     * 🚛 Normalizar tipo de vehículo
     * "camión sencillo" → "SENCILLO", "camión turbo" → "TURBO"
     */
    private static function normalizeVehiculo(string $vehiculo): string
    {
        $vehiculoLower = mb_strtolower(trim($vehiculo), 'UTF-8');
        
        // 🔧 Mapeo de combinaciones específicas (prioridad sobre simples)
        $mapeoEspecifico = [
            'camión sencillo' => 'SENCILLO',
            'camion sencillo' => 'SENCILLO',
            'carro sencillo' => 'SENCILLO',
            'vehiculo sencillo' => 'SENCILLO',
            'vehículo sencillo' => 'SENCILLO',
            'camión turbo' => 'TURBO',
            'camion turbo' => 'TURBO',
            'carro turbo' => 'TURBO',
            'vehiculo turbo' => 'TURBO',
            'vehículo turbo' => 'TURBO',
            'tracto camión' => 'TRACTOCAMION',
            'tracto camion' => 'TRACTOCAMION',
            'tractocamión' => 'TRACTOCAMION',
            'tractocamion' => 'TRACTOCAMION',
            'camión dobletroque' => 'DOBLETROQUE',
            'camion dobletroque' => 'DOBLETROQUE',
            'doble troque' => 'DOBLETROQUE',
        ];
        
        // Verificar mapeo específico primero
        foreach ($mapeoEspecifico as $patron => $resultado) {
            if (strpos($vehiculoLower, $patron) !== false) {
                return $resultado;
            }
        }
        
        // 🔧 Mapeo simple
        $mapeoSimple = [
            'sencillo' => 'SENCILLO',
            'turbo' => 'TURBO',
            'patineta' => 'PATINETA',
            'camioneta' => 'CAMIONETA',
            'dobletroque' => 'DOBLETROQUE',
            'minimula' => 'MINIMULA',
            'mini mula' => 'MINIMULA',
            'tractomula' => 'TRACTOMULA',
            'tracto mula' => 'TRACTOMULA',
            'trailer' => 'TRACTOMULA',
            'cuatro manos' => 'CUATRO MANOS',
            'cuatromanos' => 'CUATRO MANOS',
        ];
        
        // Verificar mapeo simple
        foreach ($mapeoSimple as $patron => $resultado) {
            if (strpos($vehiculoLower, $patron) !== false) {
                return $resultado;
            }
        }
        
        // Si no matcheó nada, devolver en mayúsculas
        // Pero quitar "camión" si está presente
        $sinCamion = preg_replace('/cami[oó]n\s*/ui', '', $vehiculoLower);
        $sinCamion = trim($sinCamion);
        
        if (!empty($sinCamion)) {
            return mb_strtoupper($sinCamion, 'UTF-8');
        }
        
        return mb_strtoupper($vehiculoLower, 'UTF-8');
    }
}
