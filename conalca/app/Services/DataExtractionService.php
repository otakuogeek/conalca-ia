<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\TaraSetting;

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
                    'seed' => 42, // Seed fijo para mayor consistencia
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

            // � VALIDACIÓN DE CIUDADES: Verificar que origen/destino son ciudades colombianas reales
            // La IA puede inventar ciudades basándose en nombres de empresas, aeropuertos, etc.
            $this->validarCiudadesExtraidas($extractedData, $userMessage);

            // �💵 DETECCIÓN USD EN MENSAJE ORIGINAL: Si el usuario mencionó USD/dólares en el
            // mensaje original, NO almacenar el valor numérico sin importar lo que la IA devuelva
            $tieneUSDenMensaje = preg_match('/\b(usd|dolar(?:es)?|d[oó]lar(?:es)?|us\$|u\.s\.|dollars?)\b/ui', $userMessage)
                && preg_match('/\b(valor|precio|carga|mercanc[ií]a|declarado)\b/ui', $userMessage);
            
            if ($tieneUSDenMensaje) {
                Log::info('💵 USD detectado en mensaje original del usuario - eliminando valor extraído');
                // Eliminar cualquier valor que la IA haya extraído
                if (isset($extractedData['extracted'])) {
                    unset($extractedData['extracted']['valor']);
                    unset($extractedData['extracted']['valor_declarado']);
                    unset($extractedData['extracted']['valorMercancia']);
                    $extractedData['extracted']['valor_en_usd'] = true;
                }
                // También para multi-ruta
                if (isset($extractedData['multi_ruta']) && isset($extractedData['rutas'])) {
                    foreach ($extractedData['rutas'] as &$ruta) {
                        unset($ruta['valor']);
                        unset($ruta['valor_declarado']);
                        unset($ruta['valorMercancia']);
                        $ruta['valor_en_usd'] = true;
                    }
                    unset($ruta);
                }
            }

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
            $agregarTaraExplicito = preg_match('/(?:agrega|añade|suma|pon|incluir|agregar|sumar|ponga)\s+(?:la\s+)?tara/ui', $lastUserMessage)
                && !preg_match('/(?:ya|no)\s+incluye?\s+(?:la\s+)?tara/ui', $lastUserMessage);
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
            
            // 🆕 REGLA: Tara SOLO se aplica cuando hay CONTENEDORES en la cotización
            // Si NO hay contenedor mencionado → el peso se queda tal cual (carga suelta)
            $esContenedor40EnMsg = preg_match('/(?:contenedor|cont).*?\b40\b|\b40\s*(?:pies|\')|\b\d+[xX]40\b/ui', $userMessage);
            $esContenedor20EnMsg = preg_match('/(?:contenedor|cont).*?\b20\b|(?<![04])\b20\s*(?:pies|\')|\b\d+[xX]20\b/ui', $userMessage);
            $hayContenedorEnMensaje = $esContenedor20EnMsg || $esContenedor40EnMsg;
            
            // También verificar en datos extraídos por IA (single route)
            if (!$hayContenedorEnMensaje && isset($extractedData['extracted'])) {
                $empaqueExt = $extractedData['extracted']['empaque'] ?? '';
                $contenedorExt = $extractedData['extracted']['contenedor'] ?? '';
                if (preg_match('/CONTENEDOR/i', $empaqueExt) || preg_match('/\d+[xX](20|40)/i', $contenedorExt)) {
                    $hayContenedorEnMensaje = true;
                }
            }
            
            // Para multi-ruta, verificar si ALGUNA ruta tiene contenedor
            if (!$hayContenedorEnMensaje && isset($extractedData['multi_ruta']) && $extractedData['multi_ruta'] === true && isset($extractedData['rutas'])) {
                foreach ($extractedData['rutas'] as $rutaCheck) {
                    $empaqueR = $rutaCheck['empaque'] ?? '';
                    $contenedorR = $rutaCheck['contenedor'] ?? '';
                    if (preg_match('/CONTENEDOR/i', $empaqueR) || preg_match('/\d+[xX](20|40)/i', $contenedorR)) {
                        $hayContenedorEnMensaje = true;
                        break;
                    }
                }
            }
            
            Log::info('🔍 Detección de contenedor para tara', [
                'hay_contenedor' => $hayContenedorEnMensaje,
                'es_contenedor_20' => (bool)$esContenedor20EnMsg,
                'es_contenedor_40' => (bool)$esContenedor40EnMsg,
            ]);
            
            // 🆕 LÓGICA DE TARA: Solo aplica si hay contenedores
            // Sin contenedor → NO aplicar tara nunca (carga suelta)
            // Con contenedor → aplicar reglas existentes
            $debeAgregarTara = false;
            
            if (!$hayContenedorEnMensaje) {
                // 🆕 Sin contenedor → NO aplicar tara (carga suelta, cajas, pallets, bultos, etc.)
                Log::info('📦 Sin contenedor detectado → NO se aplica tara (carga suelta)');
                $debeAgregarTara = false;
            } else if ($yaIncluyeTara) {
                // Usuario dice "con tara incluida" - NO agregar tara
                Log::info('✅ Usuario indicó que peso YA INCLUYE TARA - NO se agregará', [
                    'ya_incluye_tara' => true
                ]);
                $debeAgregarTara = false;
            } else if ($comandoExplicitoTara) {
                // Comando explícito "suma tara" con contenedor - agregar tara
                Log::info('⚡ Comando explícito para agregar tara detectado (con contenedor)');
                $debeAgregarTara = true;
            } else if (!$esEdicionCampo && $noIncluyeTara) {
                // Mensaje dice "sin tara" o "+ tara" con contenedor presente
                Log::info('🔧 Usuario indica que peso NO incluye tara (con contenedor) - se agregará');
                $debeAgregarTara = true;
            } else if (!$esEdicionCampo) {
                // Por defecto para nuevas rutas CON CONTENEDOR, agregar tara
                Log::info('📦 Nueva ruta con contenedor sin indicación de tara - se agregará por defecto');
                $debeAgregarTara = true;
            }
            
            if ($debeAgregarTara && !$yaIncluyeTara) {
                // 🚛 MULTI-RUTA: Aplicar tara a CADA ruta individualmente
                if (isset($extractedData['multi_ruta']) && $extractedData['multi_ruta'] === true && isset($extractedData['rutas'])) {
                    Log::info('🚛 Aplicando tara a multi-ruta', ['total_rutas' => count($extractedData['rutas'])]);
                    
                    foreach ($extractedData['rutas'] as $idx => &$ruta) {
                        $pesoRuta = $ruta['peso'] ?? 0;
                        if (is_string($pesoRuta)) {
                            $pesoRuta = (float) preg_replace('/[^0-9.]/', '', $pesoRuta);
                        }
                        
                        if ($pesoRuta <= 0) {
                            Log::info("⚠️ Ruta {$idx}: peso es 0, no se aplica tara");
                            continue;
                        }
                        
                        // Determinar tara según contenedor de esta ruta
                        $empaqueRuta = $ruta['empaque'] ?? '';
                        $contenedorRuta = $ruta['contenedor'] ?? '';
                        
                        // 🆕 REGLA: Tara SOLO para rutas con contenedor
                        $rutaTieneContenedor = preg_match('/CONTENEDOR/i', $empaqueRuta) || preg_match('/\d+[xX](20|40)/i', $contenedorRuta);
                        if (!$rutaTieneContenedor) {
                            Log::info("📦 Ruta {$idx}: sin contenedor → NO se aplica tara (carga suelta)", [
                                'empaque' => $empaqueRuta,
                                'contenedor' => $contenedorRuta
                            ]);
                            continue;
                        }
                        
                        $taraRuta = TaraSetting::tara40(); // Default
                        
                        if (preg_match('/CONTENEDOR\s*20|20\s*pies/i', $empaqueRuta) ||
                            preg_match('/\d+[xX]20/i', $contenedorRuta)) {
                            $taraRuta = TaraSetting::tara20();
                        } elseif (preg_match('/CONTENEDOR\s*40|40\s*pies/i', $empaqueRuta) ||
                            preg_match('/\d+[xX]40/i', $contenedorRuta)) {
                            $taraRuta = TaraSetting::tara40();
                        } else {
                            // Fallback: verificar mensaje original
                            $esContenedor20EnMsg = preg_match('/(?:contenedor|cont).*?\b20\b|(?<![04])\b20\s*(?:pies|\')|\b\d+[xX]20\b/ui', $userMessage);
                            $esContenedor40EnMsg = preg_match('/(?:contenedor|cont).*?\b40\b|\b40\s*(?:pies|\')|\b\d+[xX]40\b/ui', $userMessage);
                            if ($esContenedor20EnMsg && !$esContenedor40EnMsg) {
                                $taraRuta = TaraSetting::tara20();
                            }
                        }
                        
                        // Aplicar heurístico solo si NO hay comando explícito ni "sin tara"/"+tara"
                        $aplicarTaraRuta = true;
                        if (!$comandoExplicitoTara && !$noIncluyeTara) {
                            $pesoSinTara3400 = $pesoRuta - TaraSetting::tara40();
                            $pesoSinTara2300 = $pesoRuta - TaraSetting::tara20();
                            $esDuplicado = (
                                ($pesoSinTara3400 > 0 && $pesoSinTara3400 % 1000 == 0) ||
                                ($pesoSinTara2300 > 0 && $pesoSinTara2300 % 1000 == 0)
                            );
                            if ($pesoRuta >= TaraSetting::tara20() && $esDuplicado) {
                                $aplicarTaraRuta = false;
                                Log::info("⚠️ Ruta {$idx}: heurístico detectó que peso {$pesoRuta} ya podría incluir tara");
                            }
                        }
                        
                        if ($aplicarTaraRuta) {
                            $nuevoPesoRuta = $pesoRuta + $taraRuta;
                            $ruta['peso'] = $nuevoPesoRuta;
                            $ruta['peso_kg'] = $nuevoPesoRuta;
                            $ruta['tara'] = $taraRuta;
                            $ruta['incluye_tara'] = true;
                            
                            Log::info("📦 TARA aplicada a Ruta {$idx}", [
                                'peso_anterior' => $pesoRuta,
                                'tara' => $taraRuta,
                                'nuevo_peso' => $nuevoPesoRuta,
                                'empaque' => $empaqueRuta
                            ]);
                        }
                    }
                    unset($ruta); // romper referencia
                    
                } else {
                // RUTA ÚNICA: Lógica existente
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
                } else if ($noIncluyeTara) {
                    // 🔧 FIX CRÍTICO: Si el usuario dice EXPLÍCITAMENTE "sin tara", "peso neto", etc.
                    // CONFIAR en el usuario y NO aplicar heurísticos que puedan dar falsos positivos
                    // Ejemplo: 6400 kg sin tara → (6400-3400=3000, 3000%1000=0) daba falso positivo
                    $pareceYaTenerTara = false;
                    Log::info('⚡ Usuario dijo explícitamente "sin tara" / "peso neto" - ignorando heurísticos, se sumará tara');
                } else {
                    // 🔧 FIX: Verificar para ambas taras posibles (configurables desde admin)
                    // Si (peso - tara) es múltiplo exacto de 1000, probablemente ya tiene tara
                    $pesoSinPosibleTara3400 = $pesoActual - TaraSetting::tara40();
                    $pesoSinPosibleTara2300 = $pesoActual - TaraSetting::tara20();
                    $esProbablementeDuplicado = (
                        ($pesoSinPosibleTara3400 > 0 && $pesoSinPosibleTara3400 % 1000 == 0) ||
                        ($pesoSinPosibleTara2300 > 0 && $pesoSinPosibleTara2300 % 1000 == 0)
                    );
                    
                    // También verificar si el mensaje menciona "con tara" o "ya tara"
                    $mensionaTara = preg_match('/(?<!no\s)(?:con\s+tara|ya.*tara|tara\s+incluida|peso\s+bruto)/ui', $lastUserMessage);
                    
                    $pareceYaTenerTara = ($pesoActual >= TaraSetting::tara20() && ($esProbablementeDuplicado || $mensionaTara));
                }
                
                // Solo sumar si hay un peso base y NO parece tener tara ya incluida
                if ($pesoActual > 0 && !$pareceYaTenerTara) {
                     // 🔧 FIX CRÍTICO: Determinar tara según tamaño de contenedor
                     // Valores configurables desde admin (Gestión > Tara)
                     $taraAplicar = TaraSetting::tara40(); // Default
                     
                     // Verificar si hay información de contenedor en los datos extraídos
                     $empaque = $extractedData['extracted']['empaque'] ?? '';
                     $contenedor = $extractedData['extracted']['contenedor'] ?? '';
                     
                     // 🔧 FIX CRÍTICO v2: PRIMERO verificar el mensaje ORIGINAL del usuario
                     // Esto es más confiable que la extracción del AI que puede equivocarse
                     // Prioridad: Mensaje original > datos extraídos por IA
                     $esContenedor40EnMensaje = preg_match('/(?:contenedor|cont).*?\b40\b|\b40\s*(?:pies|\')|\b\d+[xX]40\b/ui', $userMessage);
                     $esContenedor20EnMensaje = preg_match('/(?:contenedor|cont).*?\b20\b|(?<![04])\b20\s*(?:pies|\')|\b\d+[xX]20\b/ui', $userMessage);
                     
                     if ($esContenedor40EnMensaje && !$esContenedor20EnMensaje) {
                         // Mensaje original dice contenedor 40 → tara 40'
                         $taraAplicar = TaraSetting::tara40();
                         Log::info('📦 Contenedor 40 detectado en mensaje ORIGINAL → tara ' . $taraAplicar);
                     } elseif ($esContenedor20EnMensaje && !$esContenedor40EnMensaje) {
                         // Mensaje original dice contenedor 20 → tara 20'
                         $taraAplicar = TaraSetting::tara20();
                         Log::info('📦 Contenedor 20 detectado en mensaje ORIGINAL → tara ' . $taraAplicar);
                     } elseif (preg_match('/CONTENEDOR\s*20|20\s*pies/i', $empaque) || 
                         preg_match('/\d+[xX]20/i', $contenedor) ||
                         preg_match('/contenedor\s+de\s+20/i', $lastUserMessage)) {
                         // Fallback: usar datos extraídos por IA
                         $taraAplicar = TaraSetting::tara20();
                         Log::info('📦 Contenedor 20 detectado en datos extraídos por IA → tara ' . $taraAplicar);
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
                } // fin else (ruta única)
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
- Diferentes orígenes y/o destinos EXPLÍCITAMENTE SEPARADOS como rutas distintas
- Contenedores de TIPOS DIFERENTES: "2x40 // 1x20" → 2 rutas
- Contenedores con PESOS DIFERENTES: "uno con 8.000 kg y otro con 20.000 kg" → 2 rutas
- ENTREGA DIVIDIDA: "PRIMER DESTINO: ... SEGUNDO DESTINO: ..." → Cada destino es una RUTA SEPARADA con su propia cantidad
  Ejemplo: "ORIGEN: Cartagena PRIMER DESTINO: Cartagena (5 Pallets) SEGUNDO DESTINO: Barranquilla (7 Pallets)"
  → Ruta 1: origen=Cartagena, destino=Cartagena, cantidad=5
  → Ruta 2: origen=Cartagena, destino=Barranquilla, cantidad=7
  ⚠️ IMPORTANTE: El origen y primer destino PUEDEN ser la misma ciudad (entrega local)
- MÚLTIPLES ORÍGENES SEPARADOS: Si el campo ORIGEN contiene VARIAS ciudades separadas por " - ", " / ", " Y ", " y " o enumeradas, 
  crear UNA RUTA POR CADA ORIGEN, todas con el mismo destino y mismos datos de carga.
  Patrones:
  - "ORIGEN: CTG - BAQ" → 2 orígenes: CARTAGENA y BARRANQUILLA → 2 rutas
  - "ORIGEN: BOG / MED / CLO" → 3 orígenes: BOGOTA, MEDELLIN, CALI → 3 rutas
  - "ORIGEN: CARTAGENA - BARRANQUILLA" → 2 orígenes → 2 rutas
  - "VALIDAR LOS DOS PUERTOS" o "(VALIDAR AMBOS)" son indicaciones de que AMBOS orígenes son válidos → crear ambas rutas
  ⚠️ Si dice "ORIGEN: CTG - BAQ (VALIDAR LOS DOS PUERTOS)" → SON DOS ORÍGENES SEPARADOS, NO una sola ruta CTG→BAQ
  ⚠️ Expandir las abreviaturas: CTG=CARTAGENA, BAQ=BARRANQUILLA, BOG=BOGOTA, etc.
  Ejemplo completo:
  "ORIGEN : CTG - BAQ (VALIDAR LOS DOS PUERTOS) DESTINO : Barranquilla"
  → multi_ruta: true, total_rutas: 2
  → Ruta 1: origen=CARTAGENA, destino=BARRANQUILLA
  → Ruta 2: origen=BARRANQUILLA, destino=BARRANQUILLA
- MÚLTIPLES DESTINOS SEPARADOS: Lo mismo aplica si el campo DESTINO contiene varias ciudades separadas.
  - "DESTINO: MED - CLO" → 2 destinos: MEDELLIN y CALI → 2 rutas con el mismo origen

⚠️ CUÁNDO NO ES MULTI-RUTA (CRÍTICO - LEER PRIMERO):
- Una dirección con "Ciudad, País" (ej: "Bogotá, Colombia") es UN SOLO origen, NO dos rutas
- "Colombia" es el PAÍS, NUNCA lo uses como ciudad de origen o destino
- Si solo hay UN origen y UN destino (aunque la dirección sea larga), es RUTA ÚNICA
- Ejemplo INCORRECTO: "Origen: Carrera 69p, Bogotá, Colombia / Destino: Aeropuerto de BOG" → NO crear 2 rutas
- Ejemplo CORRECTO: → Ruta única: origen=BOGOTA, destino=BOGOTA

⚠️ CUÁNDO NO ES MULTI-RUTA (IMPORTANTE):
- "2 contenedores de 20" con UN solo peso → ES RUTA ÚNICA con cantidad: 2
- "3 contenedores de 40 con 15.000 kg" → ES RUTA ÚNICA con cantidad: 3
- Varios contenedores del MISMO tipo, MISMO peso, MISMO origen/destino → RUTA ÚNICA
- La cantidad de contenedores NO significa cantidad de rutas

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
1. origen - Ciudad/lugar de recogida. NUNCA puede ser "COLOMBIA" (eso es el país, no una ciudad)
2. destino - Ciudad/lugar de entrega. NUNCA puede ser "COLOMBIA" (eso es el país, no una ciudad)
3. peso - Peso total en kg (convierte toneladas: 1 ton = 1000 kg). Si hay KVOL (peso volumétrico) y PESO (peso real), usar el MAYOR de los dos como peso
4. cantidad - Número de unidades (VER REGLA DE CONTENEDORES ABAJO)
5. empaque - Tipo de empaque. SOLO valores válidos: cajas, bultos, estibas, paquetes, bolsas, rollos, cilindros, guacales, tonel, granel, CONTENEDOR 20, CONTENEDOR 40. Si NO puedes identificar el empaque claramente → empaque: null (NO inventar)
   ⚠️ REGLA CRÍTICA: Las DIMENSIONES (Largo x Ancho x Alto) NO son empaque. "Largo 550 x 46 Ancho x 46 Alto CMS" es información de DIMENSIONES, NO un contenedor ni empaque. NUNCA interpretes medidas físicas como formato de contenedor.
   - "550 x 46 x 46 cm" → empaque: null (son dimensiones, NO contenedor)
   - "Dimensiones: Largo 550 x 46 Ancho x 46 Alto" → empaque: null
   - SOLO es contenedor si dice EXPLÍCITAMENTE "contenedor de 20", "contenedor de 40", "1x40", "2x20", etc.
6. producto - Mercancía real a transportar
7. valor - Valor declarado en PESOS COLOMBIANOS (COP). ⚠️ Si el valor está en USD, dólares o moneda extranjera, retorna "USD" como valor (NO el número). El sistema preguntará al usuario el valor en pesos colombianos.
8. vehiculo - Tipo de vehículo (NORMALIZADO: TURBO, SENCILLO, TRACTOCAMION, PATINETA, CAMIONETA, DOBLETROQUE). IMPORTANTE: "camión sencillo" = SENCILLO, "camión turbo" = TURBO
   ⚠️ REGLA DE VEHÍCULO CON CONTENEDORES: Si hay CONTENEDORES (de 20, de 40, 20', 40', etc.), vehiculo es SIEMPRE "TRACTOCAMION" aunque el usuario no lo mencione. Los contenedores SOLO se transportan en tractocamión.
   ⚠️ Si NO hay contenedores y el usuario NO menciona vehículo → vehiculo: null (no inventar)
9. contenedor - Tipo de contenedor o empaque especial
10. incluye_tara - IMPORTANTE: true si dice "tara incluida", "con tara", "peso bruto"; false si dice "sin tara", "no incluye tara", "peso neto", o NO menciona nada de tara

⚠️ REGLA CRÍTICA DE CANTIDAD CON CONTENEDORES:
- Si se menciona CONTENEDOR, la cantidad es el NÚMERO DE CONTENEDORES, no el contenido interno
- Ejemplo: "1 contenedor de 40 pies con 850 cajas" → cantidad: 1 (NO 850)
- Ejemplo: "2 contenedores con 500 bultos cada uno" → cantidad: 2 (NO 500 ni 1000)
- Si NO hay contenedor mencionado: "850 cajas de herramientas" → cantidad: 850

⚠️ REGLA CRÍTICA DE CANTIDAD SIN CONTENEDORES (PALLETS, CAJAS, BULTOS, ESTIBAS):
- Cuando NO hay contenedor y se mencionan unidades como pallets, cajas, bultos, estibas, paquetes, etc., la CANTIDAD es el NÚMERO de esas unidades
- "18 pallets en carga estibada" → cantidad: 18, empaque: "estibas"
- "210 cajas de productos farmacéuticos" → cantidad: 210, empaque: "cajas"
- "5 bultos de ropa" → cantidad: 5, empaque: "bultos"
- "carga estibada" o "carga suelta" describe el TIPO de carga, NO la cantidad. La cantidad es el número explícito de unidades
- NUNCA pongas cantidad: 1 si el usuario especifica un número de pallets/cajas/bultos/estibas

⚠️ REGLA CRÍTICA DE CANTIDAD CUANDO CONTENEDORES SE SEPARAN EN RUTAS:
- Si N contenedores del mismo tipo tienen PESOS DIFERENTES o CARACTERÍSTICAS DIFERENTES y se crean N rutas separadas, 
  CADA RUTA tiene cantidad: 1, porque cada ruta representa UN SOLO contenedor.
- Ejemplo: "2 contenedores de 20 pies: uno con 8.000 kg sin tara y el otro con 20.000 kg con tara incluida"
  → multi_ruta: true, total_rutas: 2
  → Ruta 1: peso: 8000, cantidad: 1, incluye_tara: false
  → Ruta 2: peso: 20000, cantidad: 1, incluye_tara: true
  ❌ INCORRECTO: Ruta 1: cantidad: 2, Ruta 2: cantidad: 2
- Clave: si "uno con X... el otro con Y" separa los contenedores, es 1 por ruta

⚠️ REGLA CRÍTICA: CUÁNDO CREAR MULTI-RUTA vs RUTA ÚNICA CON CONTENEDORES:
- RUTA ÚNICA (multi_ruta: false): Cuando TODOS los contenedores son del MISMO tipo, tienen el MISMO peso, y van al MISMO destino
  - "2 contenedores de 20 con 5600 kg sin tara" → multi_ruta: false, cantidad: 2, peso: 5600
  - "3 contenedores de 40 con 15.000 kg" → multi_ruta: false, cantidad: 3, peso: 15000
  - NO crear 2 o 3 rutas idénticas, eso es INCORRECTO
- MULTI-RUTA (multi_ruta: true): SOLO cuando hay DIFERENCIAS entre los contenedores:
  - Tipos diferentes: "1 contenedor de 20 y 1 contenedor de 40" → 2 rutas
  - Pesos diferentes: "uno con 8.000 kg y otro con 20.000 kg" → 2 rutas
  - Tara diferente: "uno sin tara y otro con tara incluida" → 2 rutas
  - Destinos diferentes: "uno a Cali y otro a Medellín" → 2 rutas
  - Formato "2x40 // 1x20" → 2 rutas (tipos diferentes)

⚠️ FORMATO DE CONTENEDORES (MUY IMPORTANTE):
- "1x40'HC" = 1 contenedor de 40 pies High Cube → empaque: "CONTENEDOR 40", contenedor: "1X40' HC", cantidad: 1
- "1x20'HC" = 1 contenedor de 20 pies High Cube → empaque: "CONTENEDOR 20", contenedor: "1X20' HC", cantidad: 1
- "2x40GP" = 2 contenedores de 40 pies estándar → empaque: "CONTENEDOR 40", contenedor: "2X40' GP", cantidad: 2
- "40HC", "40'HC" = contenedor de 40 pies → empaque: "CONTENEDOR 40"
- "20GP", "20'GP" = contenedor de 20 pies → empaque: "CONTENEDOR 20"

⚠️ REGLA CRÍTICA DE TARA (SOLO PARA CONTENEDORES):
- La tara SOLO aplica cuando hay CONTENEDORES (de 20, 40, 45 pies). Para carga suelta (cajas, pallets, bultos, estibas), NO hay tara.
- Si hay contenedor y dice "X kg con tara incluida" o "peso incluye tara" → incluye_tara: true
- Si hay contenedor y dice "X kg sin tara" o "peso no incluye tara" o "peso neto" → incluye_tara: false
- Si hay contenedor y dice "X kg + tara" o "peso más tara" → incluye_tara: false (significa que hay que SUMAR la tara)
- Si hay contenedor y NO menciona nada sobre tara → incluye_tara: false (default)
- Si NO hay contenedor (carga suelta, cajas, pallets, bultos) → incluye_tara: false SIEMPRE, el peso se deja tal cual
- ⚠️ NUNCA sumes tara a cajas, bultos, pallets, estibas. La tara es EXCLUSIVAMENTE para contenedores marítimos.

⚠️ REGLA CRÍTICA DE PESO TOTAL CON C/U (SIN CONTENEDORES):
Cuando hay "c/u", "cada uno", "por unidad" y NO hay contenedores:
- El peso que se reporta debe ser el PESO TOTAL = peso_unitario × cantidad
- Ejemplo: "4 cajas - Peso 23.6 kilos c/u" → peso: 94.4 (que es 4 × 23.6)
- Ejemplo: "10 bultos de 50 kg c/u" → peso: 500 (que es 10 × 50)
- NUNCA reportes solo el peso unitario como peso total
- NUNCA interpretes "23.6" como 23600 (el punto es DECIMAL)

REGLAS IMPORTANTES:
✓ Convierte SIEMPRE toneladas a kg: 1 tonelada = 1000 kg, 2.5 toneladas = 2500 kg
✓ Para valores: 45 millones = 45000000, 20 millones = 20000000
✓ Normaliza ciudades: BOGOTA, MEDELLIN, CARTAGENA, BUENAVENTURA, CALI
✓ IMPORTANTE: Si el usuario usa ABREVIATURAS de ciudades, expándelas al nombre completo:
  - BOG = BOGOTA, MED = MEDELLIN, CLO = CALI, BAQ = BARRANQUILLA
  - CTG = CARTAGENA, BGA = BUCARAMANGA, BUN = BUENAVENTURA
  - SMR = SANTA MARTA, CUC = CUCUTA, BQUILLA = BARRANQUILLA
  - Ejemplo: "ORIGEN BUN DESTINO BOG" → origen: "BUENAVENTURA", destino: "BOGOTA"
✓ Separa vehículo de producto: "turbo" es vehículo, "alimentos" es producto
✓ NUNCA inventes datos, solo extrae lo visible
✓ Responde SOLO en JSON puro, sin markdown ```json```
✓ Origen y destino deben ser SOLO el nombre de la ciudad, sin verbos ni frases extra
  - "cartagena a bucaramanga se lleva 2 contenedores" → origen: "CARTAGENA", destino: "BUCARAMANGA" (NO "BUCARAMANGA SE LLEVA")
  - "bogotá hasta cali se envían 10 cajas" → origen: "BOGOTA", destino: "CALI" (NO "CALI SE ENVIAN")

⚠️ REGLA CRÍTICA DE ORIGEN/DESTINO CON DIRECCIONES:
- Si el campo "Recoleccion:", "Origen:" o "Destino:" contiene una DIRECCIÓN (Cra., Cl., Av., #, dirección con números), extrae SOLO la ciudad mencionada en la dirección.
  - "Origen: Carrera 69p # 78 - 67, Bogotá, Colombia" → origen: "BOGOTA" (la ciudad está en la dirección)
  - "Recoleccion: Cra. 50 #134 D 31, Medellín" → origen: "MEDELLIN"
  - "Recoleccion: Cra. 50 #134 D 31" → origen: null (NO hay ciudad mencionada)
- Si contiene un AEROPUERTO con código IATA o nombre de ciudad, extraer SOLO la ciudad:
  - "Destino: Aeropuerto de BOG" → destino: "BOGOTA" (BOG = código IATA de Bogotá)
  - "Destino: Aeropuerto El Dorado" → destino: "BOGOTA"
  - "Destino: Aeropuerto zona de carga Cargo Pack" → destino: null (no hay ciudad ni código)
  - "Recoleccion: Bodega principal km 5 via Siberia" → origen: null (es una ubicación sin ciudad)
  - "Destino: Terminal marítimo SPR Cartagena" → destino: "CARTAGENA"
- ⚠️ IMPORTANTE: "Colombia" en una dirección es el PAÍS, NO una ciudad ni un segundo origen/destino. NUNCA uses "COLOMBIA" como ciudad.
  - "Carrera 69p # 78 - 67, Bogotá, Colombia" → origen: "BOGOTA" (Colombia es el país, IGNORAR)
  - NUNCA crees una ruta con origen o destino "COLOMBIA"

✓ IMPORTANTE: Si el origen o destino dice "PUERTO DE [CIUDAD]" o "PUERTO [CIUDAD]", extraer SOLO la ciudad:
  - "PUERTO BARRANQUILLA" → "BARRANQUILLA"
  - "PUERTO DE BUENAVENTURA" → "BUENAVENTURA"
  - "PUERTO CARTAGENA" → "CARTAGENA"
  - "PUERTO DE SANTA MARTA" → "SANTA MARTA"
  - Lo mismo aplica para "AEROPUERTO DE [CIUDAD]" o "TERMINAL DE [CIUDAD]" → extraer solo la ciudad

✓ IMPORTANTE: Expandir SIEMPRE los códigos IATA de aeropuertos colombianos al nombre de la ciudad:
  - "Aeropuerto de BOG" → "BOGOTA", "Aeropuerto de MDE" o "MED" → "MEDELLIN"
  - "Aeropuerto CLO" → "CALI", "Aeropuerto CTG" → "CARTAGENA"
  - "Aeropuerto BAQ" → "BARRANQUILLA", "Aeropuerto BGA" → "BUCARAMANGA"
  - "Aeropuerto SMR" → "SANTA MARTA"

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
Hay DOS contextos para "c/u" dependiendo de si hay contenedores o no:

1. CON CONTENEDORES: "Peso: X toneladas c/u" → cada RUTA tiene X toneladas de peso
   - Ejemplo: "2x40 // 1x20 ... Peso: 15 toneladas sin tara c/u" → Ruta 1: peso=15000, Ruta 2: peso=15000

2. SIN CONTENEDORES (cajas, bultos, etc.): "Peso: X kg c/u" → peso POR UNIDAD, se debe MULTIPLICAR por la cantidad
   - Ejemplo: "4 cajas - Peso 23.6 kilos c/u" → peso = 4 × 23.6 = 94.4 kg (NO 23.6, NO 23600)
   - Ejemplo: "10 bultos - Peso 15 kg cada uno" → peso = 10 × 15 = 150 kg
   - Ejemplo: "6 estibas de 500 kg c/u" → peso = 6 × 500 = 3000 kg
   - ⚠️ IMPORTANTE: "23.6 kilos" = 23.6 kg (con punto decimal), NO 23600 kg
   - ⚠️ El punto en "23.6" es separador DECIMAL, no de miles. En español, el punto puede ser decimal.
   - ⚠️ Para carga SIN contenedores, el peso total = peso_unitario × cantidad
   - ⚠️ incluye_tara SIEMPRE es false para carga sin contenedores (NO HAY TARA)

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

Input: "envíos desde Bogotá hacia Barranquilla, son 5.200 kg sin tara, distribuidos en 210 cajas de productos farmacéuticos, con un valor declarado de $54.000.000, en camión turbo para carga suelta. También desde Medellín hacia Cartagena se requieren 18 pallets en carga estibada con 8.600 kg sin tara de insumos industriales, con un valor declarado de $72.000.000, en camión sencillo."
Output: {
  "multi_ruta": true,
  "total_rutas": 2,
  "rutas": [
    {"origen":"BOGOTA","destino":"BARRANQUILLA","peso":5200,"cantidad":210,"empaque":"cajas","producto":"productos farmacéuticos","vehiculo":"TURBO","contenedor":"carga suelta","valor":54000000,"incluye_tara":false},
    {"origen":"MEDELLIN","destino":"CARTAGENA","peso":8600,"cantidad":18,"empaque":"estibas","producto":"insumos industriales","vehiculo":"SENCILLO","contenedor":"carga estibada","valor":72000000,"incluye_tara":false}
  ],
  "confidence":0.95
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
Output: {"multi_ruta":false,"origen":"BOGOTA","destino":"BUENAVENTURA","peso":8000,"cantidad":120,"empaque":"cajas","producto":"alimentos","valor":45000000,"vehiculo":"TRACTOCAMION","contenedor":"contenedor de 20 pies","incluye_tara":false,"confidence":0.95}

EJEMPLO RUTA ÚNICA CON MÚLTIPLES CONTENEDORES (MISMO TIPO, MISMO PESO):
Input: "cartagena a bucaramanga se lleva 2 contenedores de 20, con 5600 kilos sin tara"
Output: {"multi_ruta":false,"origen":"CARTAGENA","destino":"BUCARAMANGA","peso":5600,"cantidad":2,"empaque":"CONTENEDOR 20","producto":null,"vehiculo":"TRACTOCAMION","contenedor":"2X20' GP","valor":null,"incluye_tara":false,"confidence":0.9}

Input: "3 contenedores de 40 pies con 18.000 kg sin tara de Bogotá a Cartagena, maquinaria, valor $120.000.000"
Output: {"multi_ruta":false,"origen":"BOGOTA","destino":"CARTAGENA","peso":18000,"cantidad":3,"empaque":"CONTENEDOR 40","producto":"maquinaria","vehiculo":"TRACTOCAMION","contenedor":"3X40' GP","valor":120000000,"incluye_tara":false,"confidence":0.95}

EJEMPLO MULTI-RUTA POR MÚLTIPLES ORÍGENES SEPARADOS:
Input: "ORIGEN : CTG - BAQ (VALIDAR LOS DOS PUERTOS) DESTINO : Boris Urrego Bolivar Nuvia Smiles Colombia SAS ZONA FRANCA ZOFIA CL 3 MZ 13, BODEGA 92B-07 CARRT LA CORDIALIDAD- ZONA FRANCA, ZOFIA BARRANQUILLA DETALLES DE LA CARGA CNT: 1x20'ST PESO : 2000 kilogramos +TARA VOLUMEN : 11.37 cbm MERCANCIA Líquido adhesivo"
Output: {
  "multi_ruta": true,
  "total_rutas": 2,
  "rutas": [
    {"origen":"CARTAGENA","destino":"BARRANQUILLA","peso":2000,"cantidad":1,"empaque":"CONTENEDOR 20","producto":"Líquido adhesivo","vehiculo":"TRACTOCAMION","contenedor":"1X20' ST","valor":null,"incluye_tara":false},
    {"origen":"BARRANQUILLA","destino":"BARRANQUILLA","peso":2000,"cantidad":1,"empaque":"CONTENEDOR 20","producto":"Líquido adhesivo","vehiculo":"TRACTOCAMION","contenedor":"1X20' ST","valor":null,"incluye_tara":false}
  ],
  "confidence": 0.9
}

EJEMPLO MULTI-RUTA POR MÚLTIPLES ORÍGENES (OTRO FORMATO):
Input: "ORIGEN: BOG / MED DESTINO: BUENAVENTURA PESO: 5000 kg 100 cajas de textiles"
Output: {
  "multi_ruta": true,
  "total_rutas": 2,
  "rutas": [
    {"origen":"BOGOTA","destino":"BUENAVENTURA","peso":5000,"cantidad":100,"empaque":"cajas","producto":"textiles","vehiculo":null,"contenedor":null,"valor":null,"incluye_tara":false},
    {"origen":"MEDELLIN","destino":"BUENAVENTURA","peso":5000,"cantidad":100,"empaque":"cajas","producto":"textiles","vehiculo":null,"contenedor":null,"valor":null,"incluye_tara":false}
  ],
  "confidence": 0.9
}
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
        
        // 🆕 FIX: Verificar si empaque es válido - si no lo es, agregarlo como faltante
        $empaque = $extracted['empaque'] ?? null;
        if (empty($empaque) || ($empaque && !$this->isValidEmpaque($empaque))) {
            $missing[] = 'empaque';
        }
        
        return $missing;
    }

    /**
     * 🆕 Valida si un empaque es un tipo reconocido
     */
    private function isValidEmpaque(?string $empaque): bool
    {
        if (empty($empaque)) return false;
        
        $empaqueUpper = mb_strtoupper(trim($empaque), 'UTF-8');
        
        $validTypes = [
            'CAJAS', 'CAJA', 'BULTOS', 'BULTO', 'ESTIBAS', 'ESTIBA', 'CARGA ESTIBADA',
            'PAQUETES', 'PAQUETE', 'BOLSAS', 'BOLSA', 'ROLLOS', 'ROLLO',
            'CILINDROS', 'CILINDRO', 'GUACALES', 'GUACAL', 'TONEL', 'TONELES',
            'GRANEL SOLIDO', 'GRANEL LIQUIDO', 'GRANEL', 'VARIOS', 'NO APLICA',
            'CONTENEDOR 20', 'CONTENEDOR 40', 'CONTENEDOR (1) 20 PIES', 'CONTENEDOR (2) 20 PIES',
            'CONTENEDOR 40 PIES', 'CONTENEDOR 20 PIES', 'PALLETS', 'PALLET',
            'SACOS', 'SACO', 'CARGA SUELTA', 'TAMBORES', 'TAMBOR',
            'BIDONES', 'BIDON', 'CANECAS', 'CANECA', 'IBC', 'BIG BAG', 'BIGBAG',
            'SUPERSACOS', 'SUPERSACO'
        ];
        
        // Coincidencia exacta
        if (in_array($empaqueUpper, $validTypes)) {
            return true;
        }
        
        // Coincidencia parcial para variantes de contenedor
        if (preg_match('/^CONTENEDOR\s*(\d+|DE\s+\d+)/i', $empaqueUpper)) {
            return true;
        }
        
        Log::warning('📦 Empaque NO válido detectado', ['empaque' => $empaque, 'upper' => $empaqueUpper]);
        return false;
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
                    // 💵 VALIDACIÓN USD: Si el valor viene en dólares, NO almacenar - pedir en COP
                    $valStr = trim($value);
                    $esUSD = preg_match('/\b(usd|dolar|dolares|dólares|dólar|us\$|u\.s\.|dollars?)\b/ui', $valStr)
                        || mb_strtoupper($valStr) === 'USD';
                    
                    if ($esUSD) {
                        // No guardar el valor, marcar como pendiente en USD
                        $normalized['valor_en_usd'] = true;
                        Log::info('💵 Valor en USD detectado - se pedirá en COP', ['valor_original' => $valStr]);
                    } else {
                        $num = $this->extractNumber($valStr);
                        if ($num) {
                            $normalized['valor'] = $num;
                        }
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
                    
                    // 🔧 FIX: Limpiar sufijos que no son parte de la ciudad
                    // Ejemplo: "BUCARAMANGA SE LLEVA" → "BUCARAMANGA"
                    // Ejemplo: "CARTAGENA DE INDIAS" → mantener (es nombre real)
                    $val = preg_replace('/\s+(?:se\s+lleva|se\s+env[ií]a|se\s+recoge|se\s+entrega|se\s+despacha|se\s+transporta|se\s+manda|se\s+necesita|para\s+enviar|para\s+recoger|para\s+entregar|hay\s+que|donde\s+se|con\s+destino|hacia|desde)\b.*$/ui', '', $val);
                    $val = trim($val);
                    
                    // 🚢 LIMPIAR "PUERTO DE" o "PUERTO" seguido de ciudades portuarias conocidas
                    $ciudadesPortuarias = [
                        'BARRANQUILLA', 'BUENAVENTURA', 'CARTAGENA', 'SANTA MARTA', 'TUMACO',
                        'TURBO', 'COVEÑAS', 'COVENAS', 'MAMONAL', 'PALERMO', 'POZOS COLORADOS',
                        'SOCIEDAD PORTUARIA', 'SPRB'
                    ];
                    if (preg_match('/^puerto\s+(?:de\s+)?(.+)$/ui', $val, $matchPuerto)) {
                        $posibleCiudad = mb_strtoupper(trim($matchPuerto[1]), 'UTF-8');
                        $posibleCiudadNorm = strtr($posibleCiudad, [
                            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N'
                        ]);
                        foreach ($ciudadesPortuarias as $ciudadPortuaria) {
                            if ($posibleCiudadNorm === $ciudadPortuaria || 
                                strpos($posibleCiudadNorm, $ciudadPortuaria) !== false ||
                                strpos($ciudadPortuaria, $posibleCiudadNorm) !== false) {
                                Log::info("🚢 Puerto de ciudad eliminado en DataExtraction", [
                                    'original' => $val,
                                    'ciudad_extraida' => $matchPuerto[1]
                                ]);
                                $val = trim($matchPuerto[1]);
                                break;
                            }
                        }
                    }
                    // ✈️ LIMPIAR "AEROPUERTO DE" o "TERMINAL DE" seguido de ciudad
                    if (preg_match('/^(?:aeropuerto|terminal)\s+(?:de\s+)?(.+)$/ui', $val, $matchAero)) {
                        $val = trim($matchAero[1]);
                        Log::info("✈️ Aeropuerto/Terminal de ciudad eliminado", ['ciudad_extraida' => $val]);
                    }
                    
                    // 🔧 FIX: Validar contra lista de ciudades conocidas
                    // Si la ciudad extraída contiene palabras extra, intentar matchear solo la primera palabra
                    $knownCities = [
                        'BOGOTA', 'MEDELLIN', 'CALI', 'BARRANQUILLA', 'CARTAGENA', 'BUCARAMANGA',
                        'CUCUTA', 'PEREIRA', 'MANIZALES', 'ARMENIA', 'IBAGUE', 'NEIVA',
                        'VILLAVICENCIO', 'PASTO', 'POPAYAN', 'SANTA MARTA', 'SINCELEJO', 
                        'MONTERIA', 'VALLEDUPAR', 'RIOHACHA', 'QUIBDO', 'LETICIA', 'SAN ANDRES',
                        'YOPAL', 'ARAUCA', 'FLORENCIA', 'MOCOA', 'TUNJA', 'DUITAMA', 'SOGAMOSO',
                        'GIRARDOT', 'ZIPAQUIRA', 'FACATATIVA', 'SOACHA', 'BUENAVENTURA',
                        'BARRANCABERMEJA', 'PALMIRA', 'TULUA', 'BUGA', 'CARTAGO', 'DOSQUEBRADAS',
                        'ENVIGADO', 'ITAGUI', 'BELLO', 'APARTADO', 'TURBO', 'RIONEGRO',
                        'IPIALES', 'TUMACO', 'TUQUERRES', 'OCANA', 'PAMPLONA', 'AGUACHICA',
                        'MAGANGUE', 'EL CARMEN', 'FUNDACION', 'CIENAGA', 'SABANALARGA',
                        'SOLEDAD', 'MAICAO', 'URIBIA', 'LORICA', 'CERETE', 'SAHAGUN',
                        'PLANETA RICA', 'CAUCASIA', 'SEGOVIA', 'PUERTO BERRIO', 'LA DORADA',
                        'HONDA', 'ESPINAL', 'MELGAR', 'FUSAGASUGA', 'CHIA', 'CAJICA',
                        'MOSQUERA', 'FUNZA', 'MADRID', 'CARTAGENA DE INDIAS', 'SANTA FE',
                        'SAN GIL', 'SOCORRO', 'PIEDECUESTA', 'FLORIDABLANCA', 'GIRON',
                        'PUERTO BOYACA', 'CHIQUINQUIRA', 'SANTA ROSA DE CABAL', 'LA VIRGINIA',
                        'CHINCHINA', 'VILLAMARIA', 'GARZON', 'PITALITO', 'LA PLATA',
                        'SAN JOSE DEL GUAVIARE', 'MITU', 'PUERTO CARRENO', 'INIRIDA',
                        'PUERTO GAITAN', 'ACACIAS', 'GRANADA', 'SAN MARTIN'
                    ];
                    
                    // Normalizar para comparación
                    $valUpper = mb_strtoupper(strtr(trim($val), [
                        'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U',
                        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
                        'ñ' => 'N', 'Ñ' => 'N'
                    ]), 'UTF-8');
                    
                    // Si no está en la lista exacta, verificar si empieza con una ciudad conocida
                    if (!in_array($valUpper, $knownCities)) {
                        foreach ($knownCities as $city) {
                            if (strpos($valUpper, $city) === 0 && strlen($valUpper) > strlen($city)) {
                                // La ciudad extraída empieza con una ciudad conocida pero tiene texto extra
                                $charAfter = substr($valUpper, strlen($city), 1);
                                if ($charAfter === ' ' || $charAfter === ',') {
                                    // Excepciones: "CARTAGENA DE INDIAS", "SANTA MARTA", etc.
                                    $remaining = trim(substr($valUpper, strlen($city)));
                                    $isCompoundCity = preg_match('/^(DE\s+INDIAS|DE\s+CABAL|DE\s+CAUCA|DEL?\s+)$/ui', $remaining);
                                    if (!$isCompoundCity) {
                                        Log::info("🔧 Ciudad limpiada: '{$valUpper}' → '{$city}' (removido: '{$remaining}')");
                                        $val = $city;
                                    }
                                }
                                break;
                            }
                        }
                    }
                    // 🔥 POST-PROCESAMIENTO: Eliminar prefijos "importacion", "exportacion", "cotizacion"
                    $val = preg_replace('/^(importaci[oó]n|exportaci[oó]n|cotizaci[oó]n\s+de?)\s+/ui', '', $val);
                    $val = trim($val);
                    
                    $upperVal = mb_strtoupper($val);
                    
                    $abbreviations = [
                        // Códigos IATA
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
                        'SOA' => 'SOACHA',
                        // Abreviaturas informales comunes
                        'BUN' => 'BUENAVENTURA', 'BUENAV' => 'BUENAVENTURA', 'BVTURA' => 'BUENAVENTURA', 'BTURA' => 'BUENAVENTURA',
                        'BQUILLA' => 'BARRANQUILLA', 'BQLLA' => 'BARRANQUILLA', 'BQUILL' => 'BARRANQUILLA',
                        'BMANGA' => 'BUCARAMANGA', 'BGT' => 'BUCARAMANGA',
                        'CART' => 'CARTAGENA', 'CGEN' => 'CARTAGENA',
                        'BARRANCA' => 'BARRANCABERMEJA', 'BMEJA' => 'BARRANCABERMEJA',
                        'STA MARTA' => 'SANTA MARTA', 'S MARTA' => 'SANTA MARTA',
                        'S ANDRES' => 'SAN ANDRES',
                        'VVICENCIO' => 'VILLAVICENCIO',
                        'DOSQ' => 'DOSQUEBRADAS'
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
                    $empaqueUpper = mb_strtoupper($val, 'UTF-8');
                    
                    // 🆕 FIX: Rechazar dimensiones físicas confundidas como empaque/contenedor
                    // "550X46' GP" o "Largo 550 x 46" NO son empaques válidos
                    if (preg_match('/^\d{3,}\s*[Xx]\s*\d+/i', $empaqueUpper)) {
                        // Número de 3+ dígitos seguido de 'x' → es una dimensión, NO un contenedor
                        Log::warning('📦 Empaque rechazado: parece ser dimensiones, no contenedor', [
                            'empaque_recibido' => $val,
                            'razon' => 'Patrón NNNxNN detectado como dimensión física'
                        ]);
                        // NO asignar empaque - dejarlo como faltante para que el asistente pregunte
                        break;
                    }
                    
                    // 🆕 Validar contra tipos conocidos
                    if ($this->isValidEmpaque($empaqueUpper)) {
                        $normalized[$key] = $empaqueUpper;
                    } else {
                        Log::warning('📦 Empaque no reconocido, se preguntará al usuario', [
                            'empaque_recibido' => $val
                        ]);
                        // NO asignar empaque inválido - dejarlo para que el asistente pregunte
                    }
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
        // 🆕 FIX: Solo matchear si el primer número es PEQUEÑO (1-9 contenedores), NO dimensiones grandes
        if (isset($normalized['contenedor'])) {
            $contenedorUpper = mb_strtoupper($normalized['contenedor']);
            // Patrón: "1X40'HC", "2X20GP", "1X40 HC" — el primer número debe ser 1-2 dígitos (cantidad de contenedores)
            if (preg_match('/^(\d{1,2})\s*[Xx]\s*(20|40|45)\s*[\'"\s]?\s*(HQ|HC|GP|RF|OT|FR)?/i', $contenedorUpper, $matches)) {
                $cantidad = intval($matches[1]);
                $tamaño = $matches[2];
                // Solo aceptar si la cantidad es razonable (1-20 contenedores)
                if ($cantidad <= 20) {
                    if ($tamaño == '20') {
                        $normalized['empaque'] = 'CONTENEDOR 20';
                    } elseif ($tamaño == '40' || $tamaño == '45') {
                        $normalized['empaque'] = 'CONTENEDOR 40';
                    }
                    Log::info('📦 Empaque detectado de formato contenedor NxTAMAÑO', [
                        'contenedor' => $normalized['contenedor'],
                        'empaque_final' => $normalized['empaque']
                    ]);
                } else {
                    Log::warning('📦 Formato NxTAMAÑO rechazado: cantidad demasiado alta, probablemente son dimensiones', [
                        'contenedor' => $normalized['contenedor'],
                        'cantidad_parseada' => $cantidad
                    ]);
                }
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

        // 🚛 FIX: Si hay contenedores, vehículo SIEMPRE es TRACTOCAMION
        // Los contenedores solo se transportan en tractocamión, nunca en turbo/sencillo/camioneta
        if (isset($normalized['empaque'])) {
            $empaqueUpper = mb_strtoupper($normalized['empaque']);
            if (strpos($empaqueUpper, 'CONTENEDOR') !== false) {
                if (empty($normalized['vehiculo']) || $normalized['vehiculo'] !== 'TRACTOCAMION') {
                    Log::info('🚛 Vehículo forzado a TRACTOCAMION por presencia de contenedor', [
                        'empaque' => $normalized['empaque'],
                        'vehiculo_original' => $normalized['vehiculo'] ?? 'null',
                        'vehiculo_final' => 'TRACTOCAMION'
                    ]);
                    $normalized['vehiculo'] = 'TRACTOCAMION';
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
        } elseif (substr_count($cleaned, '.') === 1) {
            // 🔧 FIX: Un solo punto - determinar si es decimal o miles español
            // Si hay exactamente 3 dígitos después del punto → separador de miles español
            // Ejemplo: "6.400" → 6400 (miles), "3.5" → 3.5 (decimal)
            if (preg_match('/\.(\d{3})$/', $cleaned)) {
                $cleaned = str_replace('.', '', $cleaned);
            }
            // Si no tiene 3 dígitos después → es decimal normal (ej: "3.5")
        } elseif (substr_count($cleaned, ',') === 1) {
            // 🔧 FIX: Una sola coma - determinar si es decimal o miles
            // Si hay exactamente 3 dígitos después de la coma → separador de miles
            // Ejemplo: "6,400" → 6400 (miles), "3,5" → 3.5 (decimal)
            if (preg_match('/,(\d{3})$/', $cleaned)) {
                $cleaned = str_replace(',', '', $cleaned);
            } else {
                $cleaned = str_replace(',', '.', $cleaned);
            }
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
        $valorEnUSD = false;
        if (!empty($extractedData)) {
            $response .= "✅ He identificado los siguientes detalles de tu cotización:\n\n";
            
            foreach ($extractedData as $field => $value) {
                // Detectar flag de valor en USD
                if ($field === 'valor_en_usd' && $value) {
                    $valorEnUSD = true;
                    continue;
                }
                $fieldLabel = $this->humanizeFieldName($field);
                $response .= "- **$fieldLabel**: $value\n";
            }
        }

        // Si se detectó valor en USD, agregar como campo faltante especial
        if ($valorEnUSD) {
            $missingFields[] = 'valor_cop';
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
            'contenedor' => '¿Qué tipo de **contenedor** se utilizará? (contenedor de 20 pies, contenedor de 40 pies)',
            'empaque' => '¿Cuál es el **tipo de embalaje/empaque**? (cajas, bultos, estibas, paquetes, bolsas, rollos, etc.)',
            'producto' => '¿Cuál es el **tipo de producto o mercancía** que se transportará?',
            'valor' => '¿Cuál es el **valor declarado** de la mercancía en **pesos colombianos (COP)**?',
            'valor_cop' => 'El valor fue indicado en dólares (USD). ¿Podrías indicarme el **valor declarado en pesos colombianos (COP)**?',
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

    /**
     * 🚨 VALIDAR que las ciudades extraídas por la IA sean ciudades colombianas reales.
     * Si no lo son (ej: direcciones, aeropuertos, nombres de empresas), se eliminan.
     */
    private function validarCiudadesExtraidas(array &$extractedData, string $userMessage): void
    {
        // Verificar si el prompt contiene "Recoleccion:/Origen:" con una DIRECCIÓN (no ciudad)
        $tieneOrigenDireccion = false;
        $tieneDestinoDireccion = false;
        
        // Detectar direcciones en Recoleccion/Origen
        if (preg_match('/(?:Recoleccion|Origen)\s*:\s*([^\n]+?)(?=\n|Destino\s*:|DETALLES|$)/ui', $userMessage, $m)) {
            $textoOrigen = trim($m[1]);
            if (!empty($textoOrigen) && !$this->esCiudadColombiana($textoOrigen)) {
                // 🆕 FIX: Intentar extraer ciudad de dirección compleja
                $ciudadExtraidaOrigen = $this->extractCityFromAddress($textoOrigen);
                if ($ciudadExtraidaOrigen) {
                    Log::info('✅ DataExtractionService: Ciudad extraída de dirección origen', ['ciudad' => $ciudadExtraidaOrigen, 'texto' => $textoOrigen]);
                } elseif ($this->looksLikeSimpleName($textoOrigen)) {
                    // 🆕 FIX: Nombre simple (1-3 palabras sin números/dirección) = probablemente municipio
                    Log::info('✅ DataExtractionService: Origen aceptado como nombre simple de municipio', ['texto' => $textoOrigen]);
                } else {
                    $tieneOrigenDireccion = true;
                    Log::warning('🚨 DataExtractionService: Origen NO es ciudad colombiana válida', ['texto' => $textoOrigen]);
                }
            }
        }
        // También verificar siguiente línea
        if (!$tieneOrigenDireccion && preg_match('/(?:Recoleccion|Origen)\s*:\s*\n\s*([^\n]+?)(?=\n|Destino\s*:|DETALLES|$)/ui', $userMessage, $m)) {
            $textoOrigen = trim($m[1]);
            if (!empty($textoOrigen) && !$this->esCiudadColombiana($textoOrigen)) {
                $ciudadExtraidaOrigen = $this->extractCityFromAddress($textoOrigen);
                if ($ciudadExtraidaOrigen) {
                    Log::info('✅ DataExtractionService: Ciudad extraída de dirección origen (siguiente línea)', ['ciudad' => $ciudadExtraidaOrigen]);
                } elseif ($this->looksLikeSimpleName($textoOrigen)) {
                    Log::info('✅ DataExtractionService: Origen aceptado como nombre simple (siguiente línea)', ['texto' => $textoOrigen]);
                } else {
                    $tieneOrigenDireccion = true;
                    Log::warning('🚨 DataExtractionService: Origen (siguiente línea) NO es ciudad colombiana válida', ['texto' => $textoOrigen]);
                }
            }
        }
        
        // Detectar direcciones en Destino
        if (preg_match('/Destino\s*:\s*([^\n]+?)(?=\n|DETALLES|Recoleccion|$)/ui', $userMessage, $m)) {
            $textoDestino = trim($m[1]);
            if (!empty($textoDestino) && !$this->esCiudadColombiana($textoDestino)) {
                $ciudadExtraidaDest = $this->extractCityFromAddress($textoDestino);
                if ($ciudadExtraidaDest) {
                    Log::info('✅ DataExtractionService: Ciudad extraída de dirección destino', ['ciudad' => $ciudadExtraidaDest, 'texto' => $textoDestino]);
                } elseif ($this->looksLikeSimpleName($textoDestino)) {
                    // 🆕 FIX: Nombre simple = probablemente municipio, NO borrar
                    Log::info('✅ DataExtractionService: Destino aceptado como nombre simple de municipio', ['texto' => $textoDestino]);
                } else {
                    $tieneDestinoDireccion = true;
                    Log::warning('🚨 DataExtractionService: Destino NO es ciudad colombiana válida', ['texto' => $textoDestino]);
                }
            }
        }
        
        $requiereAclaracion = $tieneOrigenDireccion || $tieneDestinoDireccion;
        
        // TAMBIÉN validar las ciudades que la IA extrajo (puede inventar desde su conocimiento)
        if (isset($extractedData['extracted'])) {
            $origenAI = $extractedData['extracted']['origen'] ?? null;
            $destinoAI = $extractedData['extracted']['destino'] ?? null;
            
            // 🆕 FIX: Rechazar "COLOMBIA" como ciudad (es el país, no una ciudad)
            if ($origenAI && mb_strtoupper(trim($origenAI)) === 'COLOMBIA') {
                Log::warning('🚨 DataExtractionService: AI puso "COLOMBIA" como origen - es el país, no una ciudad');
                unset($extractedData['extracted']['origen']);
                $origenAI = null;
            }
            if ($destinoAI && mb_strtoupper(trim($destinoAI)) === 'COLOMBIA') {
                Log::warning('🚨 DataExtractionService: AI puso "COLOMBIA" como destino - es el país, no una ciudad');
                unset($extractedData['extracted']['destino']);
                $destinoAI = null;
            }
            
            // Si extrajimos ciudad válida de la dirección, usarla en vez de lo que la IA inventó
            if (isset($ciudadExtraidaOrigen) && $ciudadExtraidaOrigen) {
                $extractedData['extracted']['origen'] = $ciudadExtraidaOrigen;
                Log::info('✅ DataExtractionService: Reemplazando origen AI con ciudad extraída', [
                    'ai_origen' => $origenAI,
                    'ciudad_correcta' => $ciudadExtraidaOrigen
                ]);
            } elseif ($tieneOrigenDireccion && $origenAI) {
                // Si el texto original tiene dirección pero la IA puso una ciudad, fue INVENTADA
                Log::warning('🚨 DataExtractionService: AI inventó origen', [
                    'ai_origen' => $origenAI,
                    'texto_real' => $textoOrigen ?? 'N/A'
                ]);
                unset($extractedData['extracted']['origen']);
            }
            
            if (isset($ciudadExtraidaDest) && $ciudadExtraidaDest) {
                $extractedData['extracted']['destino'] = $ciudadExtraidaDest;
                Log::info('✅ DataExtractionService: Reemplazando destino AI con ciudad extraída', [
                    'ai_destino' => $destinoAI,
                    'ciudad_correcta' => $ciudadExtraidaDest
                ]);
            } elseif ($tieneDestinoDireccion && $destinoAI) {
                Log::warning('🚨 DataExtractionService: AI inventó destino', [
                    'ai_destino' => $destinoAI,
                    'texto_real' => $textoDestino ?? 'N/A'
                ]);
                unset($extractedData['extracted']['destino']);
            }
        }
        
        // Lo mismo para multi-ruta
        if (isset($extractedData['multi_ruta']) && $extractedData['multi_ruta'] === true && isset($extractedData['rutas'])) {
            foreach ($extractedData['rutas'] as &$ruta) {
                // 🆕 FIX: Rechazar "COLOMBIA" como ciudad en multi-ruta
                if (isset($ruta['origen']) && mb_strtoupper(trim($ruta['origen'])) === 'COLOMBIA') {
                    Log::warning('🚨 DataExtractionService: AI puso "COLOMBIA" como origen en multi-ruta');
                    unset($ruta['origen']);
                }
                if (isset($ruta['destino']) && mb_strtoupper(trim($ruta['destino'])) === 'COLOMBIA') {
                    Log::warning('🚨 DataExtractionService: AI puso "COLOMBIA" como destino en multi-ruta');
                    unset($ruta['destino']);
                }
                
                if (isset($ciudadExtraidaOrigen) && $ciudadExtraidaOrigen && isset($ruta['origen'])) {
                    $ruta['origen'] = $ciudadExtraidaOrigen;
                } elseif (isset($ciudadExtraidaOrigen) && $ciudadExtraidaOrigen && !isset($ruta['origen'])) {
                    // Si el origen fue rechazado (COLOMBIA), usar la ciudad extraída de la dirección
                    $ruta['origen'] = $ciudadExtraidaOrigen;
                } elseif ($tieneOrigenDireccion && isset($ruta['origen'])) {
                    Log::warning('🚨 DataExtractionService: AI inventó origen en multi-ruta', ['ai_origen' => $ruta['origen']]);
                    unset($ruta['origen']);
                }
                if (isset($ciudadExtraidaDest) && $ciudadExtraidaDest && isset($ruta['destino'])) {
                    $ruta['destino'] = $ciudadExtraidaDest;
                } elseif (isset($ciudadExtraidaDest) && $ciudadExtraidaDest && !isset($ruta['destino'])) {
                    // Si el destino fue rechazado (COLOMBIA), usar la ciudad extraída
                    $ruta['destino'] = $ciudadExtraidaDest;
                } elseif ($tieneDestinoDireccion && isset($ruta['destino'])) {
                    Log::warning('🚨 DataExtractionService: AI inventó destino en multi-ruta', ['ai_destino' => $ruta['destino']]);
                    unset($ruta['destino']);
                }
            }
            unset($ruta);
            
            // 🆕 FIX: Colapsar multi-rutas falsas (rutas con mismo origen y destino)
            // Si después de normalizar, todas las rutas tienen el mismo origen y destino, fusionarlas en una sola
            // EXCEPCIÓN: No colapsar si las rutas tienen pesos diferentes (ej: múltiples contenedores con distinto peso)
            if (count($extractedData['rutas']) > 1) {
                $allSameOriginDest = true;
                $firstOrigen = $extractedData['rutas'][0]['origen'] ?? null;
                $firstDestino = $extractedData['rutas'][0]['destino'] ?? null;
                
                foreach ($extractedData['rutas'] as $ruta) {
                    $rutaOrigen = $ruta['origen'] ?? null;
                    $rutaDestino = $ruta['destino'] ?? null;
                    if ($rutaOrigen !== $firstOrigen || $rutaDestino !== $firstDestino) {
                        $allSameOriginDest = false;
                        break;
                    }
                }
                
                // Verificar si las rutas tienen pesos diferentes → NO colapsar
                $hasDifferentWeights = false;
                if ($allSameOriginDest) {
                    $weights = [];
                    foreach ($extractedData['rutas'] as $ruta) {
                        $w = $ruta['peso'] ?? $ruta['peso_kg'] ?? null;
                        if ($w !== null) {
                            $weights[] = floatval($w);
                        }
                    }
                    // Si hay al menos 2 rutas con peso y los pesos no son todos iguales
                    if (count($weights) >= 2) {
                        $uniqueWeights = array_unique($weights);
                        if (count($uniqueWeights) > 1) {
                            $hasDifferentWeights = true;
                            Log::info('🔧 DataExtractionService: Multi-ruta con mismo origen/destino PERO pesos diferentes → NO colapsar', [
                                'origen' => $firstOrigen,
                                'destino' => $firstDestino,
                                'pesos' => $weights,
                            ]);
                        }
                    }
                }
                
                if ($allSameOriginDest && !$hasDifferentWeights) {
                    Log::info('🔧 DataExtractionService: Colapsando multi-ruta falsa (todas con mismo origen/destino)', [
                        'origen' => $firstOrigen,
                        'destino' => $firstDestino,
                        'total_rutas_antes' => count($extractedData['rutas'])
                    ]);
                    
                    // Fusionar datos: tomar los datos más completos de la primera ruta
                    $rutaMerged = $extractedData['rutas'][0];
                    // Llenar campos vacíos con datos de otras rutas
                    for ($i = 1; $i < count($extractedData['rutas']); $i++) {
                        foreach ($extractedData['rutas'][$i] as $key => $val) {
                            if ($val !== null && $val !== '' && (!isset($rutaMerged[$key]) || $rutaMerged[$key] === null || $rutaMerged[$key] === '')) {
                                $rutaMerged[$key] = $val;
                            }
                        }
                    }
                    
                    // Convertir a ruta única
                    $extractedData['multi_ruta'] = false;
                    $extractedData['total_rutas'] = 1;
                    $extractedData['extracted'] = $rutaMerged;
                    unset($extractedData['rutas']);
                    
                    Log::info('✅ Multi-ruta falsa colapsada a ruta única', $rutaMerged);
                }
            }
        }
        
        if ($requiereAclaracion) {
            Log::info('🚨 DataExtractionService: Ciudades eliminadas - se requiere aclaración del usuario');
        }
    }

    /**
     * 🏙️ Verificar si un texto es una ciudad colombiana conocida
     */
    private function esCiudadColombiana(string $texto): bool
    {
        $texto = mb_strtolower(trim($texto));
        
        // Lista de ciudades colombianas principales
        $ciudadesValidas = [
            'bogota', 'bogotá', 'medellin', 'medellín', 'cali', 'barranquilla', 'cartagena',
            'bucaramanga', 'pereira', 'cucuta', 'cúcuta', 'ibague', 'ibagué', 'manizales',
            'santa marta', 'villavicencio', 'pasto', 'monteria', 'montería', 'neiva',
            'valledupar', 'armenia', 'popayan', 'popayán', 'sincelejo', 'tunja', 'riohacha',
            'buenaventura', 'girardot', 'floridablanca', 'soacha', 'bello', 'soledad',
            'palmira', 'envigado', 'itagui', 'itagüí', 'dosquebradas', 'tulua', 'tuluá',
            'apartado', 'apartadó', 'cartago', 'barrancabermeja', 'yopal', 'florencia',
            'funza', 'zipaquira', 'zipaquirá', 'chia', 'chía', 'sogamoso', 'duitama',
            'ipiales', 'tumaco', 'quibdo', 'quibdó', 'leticia', 'mocoa', 'arauca',
            'san andres', 'san andrés', 'providencia', 'puerto asis', 'puerto asís',
            'puerto carreño', 'inírida', 'mitú', 'turbo', 'caucasia', 'rionegro',
            'la dorada', 'honda', 'mariquita', 'espinal', 'melgar', 'fusagasuga',
            'fusagasugá', 'facatativa', 'facatativá', 'madrid', 'mosquera', 'cajica',
            'cajicá', 'tocancipá', 'tocancipa', 'cota', 'tenjo', 'tabio', 'la calera',
            'sibate', 'sibaté', 'sopo', 'sopó', 'guatape', 'guatapé', 'santa rosa de cabal',
            'la virginia', 'chinchina', 'chinchiná', 'yumbo', 'jamundí', 'jamundi',
            'candelaria', 'puerto tejada', 'santander de quilichao', 'pradera',
            'buga', 'guadalajara de buga', 'sevilla', 'andalucía', 'andalucia',
            'turbaco', 'arjona', 'turbana', 'clemencia', 'san jacinto', 'carmen de bolivar',
            'magangué', 'magangue', 'mompos', 'mompós', 'el banco', 'ciénaga', 'cienaga',
            'fundación', 'fundacion', 'zona bananera', 'aracataca', 'plato',
            'curumani', 'curumaní', 'aguachica', 'codazzi', 'agustin codazzi', 'agustín codazzi',
            'la jagua de ibirico', 'chiriguana', 'chiriguaná', 'bosconia', 'san alberto',
            'gamarra', 'pelaya', 'pailitas', 'tamalameque', 'rio de oro', 'río de oro',
            'la gloria', 'gonzalez', 'gonzález', 'san martin', 'san martín',
            'puerto berrio', 'puerto berrío', 'planeta rica',
            'lorica', 'cereté', 'cerete', 'sahagún', 'sahagun', 'montelíbano', 'montelibano',
            'tierralta', 'puerto escondido',
            'ocaña', 'ocana', 'pamplona', 'los patios', 'villa del rosario',
            'la plata', 'garzon', 'garzón', 'pitalito',
            'puerto lopez', 'puerto lópez', 'acacias', 'acacías', 'granada',
            'chaparral', 'líbano', 'libano', 'el espinal',
            'cumaribo', 'puerto carreño', 'la primavera', 'santa rosalía', 'santa rosalia',
            'san jose del guaviare', 'san josé del guaviare', 'calamar', 'el retorno', 'miraflores',
            'puerto inirida', 'puerto inírida', 'mitú', 'mitu', 'caruru',
            'san vicente del caguan', 'san vicente del caguán', 'el doncello', 'el paujil',
            'sibundoy', 'villagarzon', 'villagarzón', 'orito', 'la hormiga',
            'istmina', 'istmína', 'condoto', 'nuqui', 'nuquí', 'bahia solano', 'bahía solano',
            'maicao', 'fonseca', 'san juan del cesar', 'uribia', 'manaure',
            'aguazul', 'paz de ariporo', 'tauramena', 'maní', 'mani',
            'saravena', 'tame', 'arauquita', 'fortul',
            'corozal', 'since', 'sincé', 'ovejas',
            'el carmen de viboral', 'la ceja', 'marinilla',
            'la estrella', 'copacabana', 'barbosa', 'girardota'
        ];
        
        // Coincidencia exacta
        if (in_array($texto, $ciudadesValidas)) {
            return true;
        }
        
        // Sin acentos
        $textoNorm = $this->removeAccents($texto);
        foreach ($ciudadesValidas as $ciudad) {
            if ($textoNorm === $this->removeAccents($ciudad)) {
                return true;
            }
        }
        
        // 🆕 Verificar si es una abreviatura conocida de ciudad colombiana
        $abreviaturas = [
            'bog' => 'bogota', 'med' => 'medellin', 'clo' => 'cali',
            'baq' => 'barranquilla', 'ctg' => 'cartagena', 'bga' => 'bucaramanga',
            'cuc' => 'cucuta', 'pei' => 'pereira', 'mzl' => 'manizales',
            'axm' => 'armenia', 'ibe' => 'ibague', 'nva' => 'neiva',
            'vvc' => 'villavicencio', 'pso' => 'pasto', 'ppn' => 'popayan',
            'smr' => 'santa marta', 'mtr' => 'monteria', 'vup' => 'valledupar',
            'rch' => 'riohacha', 'uib' => 'quibdo', 'let' => 'leticia',
            'adz' => 'san andres', 'eyp' => 'yopal', 'auc' => 'arauca',
            'fla' => 'florencia', 'mco' => 'mocoa', 'tun' => 'tunja',
            'dui' => 'duitama', 'sog' => 'sogamoso', 'gir' => 'girardot',
            'bun' => 'buenaventura', 'buenav' => 'buenaventura',
            'bvtura' => 'buenaventura', 'btura' => 'buenaventura',
            'bquilla' => 'barranquilla', 'bqlla' => 'barranquilla',
            'bquill' => 'barranquilla', 'bmanga' => 'bucaramanga',
            'bmeja' => 'barrancabermeja', 'barranca' => 'barrancabermeja',
            'bgt' => 'bucaramanga', 'cgen' => 'cartagena', 'cart' => 'cartagena',
            'vvicencio' => 'villavicencio', 'sta marta' => 'santa marta',
            's marta' => 'santa marta', 'apto' => 'apartado',
            'dosq' => 'dosquebradas', 'floridab' => 'floridablanca',
        ];
        
        if (isset($abreviaturas[$textoNorm])) {
            return true;
        }
        
        return false;
    }

    /**
     * 🆕 Verificar si un texto parece un nombre simple de municipio/ciudad
     * (1-4 palabras, sin números, sin keywords de dirección)
     * Ej: "Cumaribo" → true, "San José del Guaviare" → true
     * Ej: "Calle 17 #23-45" → false, "Bodega 11 Autopista" → false
     */
    private function looksLikeSimpleName(string $texto): bool
    {
        $texto = trim($texto);
        
        // Quitar "/ Departamento" al final si existe (ej: "Cumaribo / Vichada" → "Cumaribo")
        $texto = preg_replace('/\s*[\/]\s*\S+\s*$/', '', $texto);
        $texto = trim($texto);
        
        // Máximo 4 palabras (para cubrir "San José del Guaviare", "Villa del Rosario", etc.)
        $palabras = preg_split('/\s+/', $texto);
        if (count($palabras) > 4 || count($palabras) < 1) {
            return false;
        }
        
        // No debe contener números
        if (preg_match('/\d/', $texto)) {
            return false;
        }
        
        // No debe contener keywords de dirección
        if (preg_match('/calle|carrera|cra|cll|autopista|km\b|bodega|avenida|transversal|diagonal|manzana|lote|entrada|parcela|zona\s+industrial|warehouse|bloque|piso|local|oficina|apartamento|edificio/ui', $texto)) {
            return false;
        }
        
        // Mínimo 3 caracteres
        if (mb_strlen($texto) < 3) {
            return false;
        }
        
        return true;
    }

    /**
     * 🆕 Extraer ciudad colombiana de una dirección o texto complejo
     * Ej: "Lutransa Bodega 11 – Autopista Medellín Km. 2.5 – Cota – Cundinamarca" → "COTA"
     * Ej: "Aeropuerto Bogotá, Colombia" → "BOGOTA"
     */
    private function extractCityFromAddress(string $texto): ?string
    {
        $textoLower = mb_strtolower(trim($texto));
        
        // Mapa de abreviaturas/códigos IATA → ciudad completa
        $abreviaturasExpansion = [
            'bog' => 'BOGOTA', 'med' => 'MEDELLIN', 'clo' => 'CALI',
            'baq' => 'BARRANQUILLA', 'ctg' => 'CARTAGENA', 'bga' => 'BUCARAMANGA',
            'cuc' => 'CUCUTA', 'pei' => 'PEREIRA', 'mzl' => 'MANIZALES',
            'axm' => 'ARMENIA', 'ibe' => 'IBAGUE', 'nva' => 'NEIVA',
            'vvc' => 'VILLAVICENCIO', 'pso' => 'PASTO', 'ppn' => 'POPAYAN',
            'smr' => 'SANTA MARTA', 'mtr' => 'MONTERIA', 'vup' => 'VALLEDUPAR',
            'rch' => 'RIOHACHA', 'uib' => 'QUIBDO', 'let' => 'LETICIA',
            'adz' => 'SAN ANDRES', 'eyp' => 'YOPAL', 'auc' => 'ARAUCA',
            'fla' => 'FLORENCIA', 'mco' => 'MOCOA', 'tun' => 'TUNJA',
            'bun' => 'BUENAVENTURA', 'buenav' => 'BUENAVENTURA',
            'bvtura' => 'BUENAVENTURA', 'btura' => 'BUENAVENTURA',
            'bquilla' => 'BARRANQUILLA', 'bqlla' => 'BARRANQUILLA',
            'bmanga' => 'BUCARAMANGA', 'bgt' => 'BUCARAMANGA',
            'cart' => 'CARTAGENA', 'cgen' => 'CARTAGENA',
            'barranca' => 'BARRANCABERMEJA', 'bmeja' => 'BARRANCABERMEJA',
            'sta marta' => 'SANTA MARTA', 's marta' => 'SANTA MARTA',
            'vvicencio' => 'VILLAVICENCIO', 'dosq' => 'DOSQUEBRADAS',
        ];
        
        // 🆕 FIX: Detectar código IATA/abreviatura como PRIMERA palabra del texto
        // Maneja casos como "BAQ   Calle 76 # 70-35" → BARRANQUILLA
        $palabras = preg_split('/[\s,;]+/', trim($texto), -1, PREG_SPLIT_NO_EMPTY);
        if (!empty($palabras)) {
            $primeraPalabraRaw = rtrim($palabras[0], '.,;:');
            $primeraPalabraLower = mb_strtolower($primeraPalabraRaw);
            if (isset($abreviaturasExpansion[$primeraPalabraLower])) {
                $ciudadExpandida = $abreviaturasExpansion[$primeraPalabraLower];
                Log::info('✅ Código IATA/abreviatura detectado al inicio del texto', [
                    'codigo' => $primeraPalabraRaw,
                    'ciudad' => $ciudadExpandida,
                    'texto_completo' => $texto
                ]);
                return $ciudadExpandida;
            }
            
            // También verificar abreviaturas multi-palabra (ej: "sta marta")
            if (count($palabras) >= 2) {
                $dospalabras = mb_strtolower($primeraPalabraRaw . ' ' . rtrim($palabras[1], '.,;:'));
                if (isset($abreviaturasExpansion[$dospalabras])) {
                    $ciudadExpandida = $abreviaturasExpansion[$dospalabras];
                    Log::info('✅ Abreviatura multi-palabra detectada al inicio', [
                        'abreviatura' => $dospalabras,
                        'ciudad' => $ciudadExpandida
                    ]);
                    return $ciudadExpandida;
                }
            }
        }
        
        // 1. Patrón "Aeropuerto CIUDAD" / "Puerto CIUDAD"
        // 🔧 FIX: Usar \S+ para capturar solo la primera palabra, luego verificar progresivamente
        if (preg_match('/(?:aeropuerto|terminal\s+a[eé]re[oa])\s+(?:de\s+|el\s+)?(\S+)/ui', $texto, $m)) {
            $primeraPalabra = trim($m[1]);
            // Quitar comas, puntos al final
            $primeraPalabra = rtrim($primeraPalabra, '.,;:');
            
            // 🆕 FIX: Primero verificar si es un código IATA/abreviatura 
            $ppLower = mb_strtolower($primeraPalabra);
            if (isset($abreviaturasExpansion[$ppLower])) {
                $ciudadExpandida = $abreviaturasExpansion[$ppLower];
                Log::info('✈️ Código IATA expandido en aeropuerto', [
                    'codigo' => $primeraPalabra,
                    'ciudad' => $ciudadExpandida
                ]);
                return $ciudadExpandida;
            }
            
            // Luego verificar si es una ciudad conocida directamente
            if ($this->esCiudadColombiana($primeraPalabra)) {
                return mb_strtoupper($this->removeAccents($primeraPalabra));
            }
            
            // Intentar con más palabras (ej: "Aeropuerto de Santa Marta")
            if (preg_match('/(?:aeropuerto|terminal\s+a[eé]re[oa])\s+(?:de\s+|el\s+)?([a-záéíóúñ]+(?: [a-záéíóúñ]+){0,3})/ui', $texto, $m2)) {
                $candidatoMulti = trim($m2[1]);
                $candidatoMulti = preg_replace('/,?\s*(colombia|ecuador|venezuela|peru|perú|panama|panamá).*$/ui', '', $candidatoMulti);
                $candidatoMulti = trim($candidatoMulti);
                if ($this->esCiudadColombiana($candidatoMulti)) {
                    return mb_strtoupper($this->removeAccents($candidatoMulti));
                }
            }
        }
        
        // 2. Buscar ciudad antes de departamento con separadores (–, —, -, , /)
        // 🆕 FIX: Incluir TODOS los 32 departamentos de Colombia
        $departamentos = [
            'cundinamarca', 'antioquia', 'valle del cauca', 'valle', 'atlantico', 'atlántico',
            'bolivar', 'bolívar', 'santander', 'boyaca', 'boyacá', 'tolima', 'nariño', 'narino',
            'cauca', 'cordoba', 'córdoba', 'magdalena', 'cesar', 'sucre', 'meta',
            'risaralda', 'caldas', 'quindio', 'quindío', 'huila', 'norte de santander',
            'vichada', 'guainia', 'guainía', 'vaupes', 'vaupés', 'amazonas', 'putumayo',
            'arauca', 'casanare', 'guaviare', 'caqueta', 'caquetá', 'choco', 'chocó',
            'la guajira', 'san andres', 'san andrés'
        ];
        
        foreach ($departamentos as $depto) {
            // 🔧 FIX: Incluir "/" como separador ("Cumaribo / Vichada")
            $deptoPattern = preg_quote($depto, '/');
            if (preg_match('/([a-záéíóúñ\s]+)\s*[–—\-,\/]\s*' . $deptoPattern . '/ui', $texto, $m)) {
                $segmentoAntes = trim($m[1]);
                // Tomar la última palabra/ciudad del segmento
                $partes = preg_split('/\s*[–—\-,\/]\s*/', $segmentoAntes);
                for ($i = count($partes) - 1; $i >= 0; $i--) {
                    $parte = trim($partes[$i]);
                    if (!empty($parte) && $this->esCiudadColombiana($parte)) {
                        return mb_strtoupper($parte);
                    }
                }
                // Si no se encontró en partes, probar el segmento completo
                if ($this->esCiudadColombiana($segmentoAntes)) {
                    return mb_strtoupper($segmentoAntes);
                }
                // 🆕 FIX v2: Intentar extraer ciudad conocida DENTRO del texto antes de confiar ciegamente
                // Maneja casos como "ZONA INDUSTRIAL DE TENJO" → "TENJO"
                $ultimaParte = trim(end($partes));
                if (!empty($ultimaParte) && mb_strlen($ultimaParte) >= 3 && mb_strlen($ultimaParte) <= 40
                    && !preg_match('/\d|calle|carrera|cra|cll|autopista|km|bodega|avenida|transversal|diagonal|manzana|lote/ui', $ultimaParte)) {
                    
                    // Paso A: Intentar remover prefijos de ubicación conocidos
                    $prefijosUbicacion = [
                        'zona industrial de', 'zona franca de', 'parque industrial de',
                        'centro industrial de', 'zona portuaria de', 'parque empresarial de',
                        'zona industrial del', 'zona franca del', 'puerto de', 'terminal de',
                        'zona industrial', 'zona franca', 'parque industrial',
                        'centro logistico de', 'centro logístico de', 'centro logistico', 'centro logístico',
                        'complejo industrial de', 'complejo industrial',
                    ];
                    $segmentoLower = mb_strtolower($ultimaParte);
                    $ciudadPorPrefijo = null;
                    foreach ($prefijosUbicacion as $prefijo) {
                        if (mb_strpos($segmentoLower, $prefijo) === 0) {
                            $candidato = trim(mb_substr($ultimaParte, mb_strlen($prefijo)));
                            if (!empty($candidato) && $this->esCiudadColombiana($candidato)) {
                                $ciudadPorPrefijo = $candidato;
                                break;
                            }
                        }
                    }
                    if ($ciudadPorPrefijo) {
                        Log::info('✅ DataExtractionService: Ciudad extraída removiendo prefijo de ubicación', [
                            'prefijo_removido' => true, 'ciudad' => $ciudadPorPrefijo, 'texto_original' => $ultimaParte
                        ]);
                        return mb_strtoupper($this->removeAccents($ciudadPorPrefijo));
                    }
                    
                    // Paso B: Buscar alguna ciudad conocida entre las palabras
                    $palabrasSegmento = preg_split('/\s+/', trim($ultimaParte));
                    $ciudadEncontrada = null;
                    for ($j = 0; $j < count($palabrasSegmento); $j++) {
                        $palabra = trim($palabrasSegmento[$j]);
                        if (!empty($palabra) && mb_strlen($palabra) >= 3 && $this->esCiudadColombiana($palabra)) {
                            $ciudadEncontrada = $palabra;
                        }
                        // También probar combinaciones de 2-3 palabras (ej: "Santa Marta", "San José del Guaviare")
                        if ($j < count($palabrasSegmento) - 1) {
                            $dosPalabras = $palabrasSegmento[$j] . ' ' . $palabrasSegmento[$j + 1];
                            if ($this->esCiudadColombiana($dosPalabras)) {
                                $ciudadEncontrada = $dosPalabras;
                            }
                        }
                        if ($j < count($palabrasSegmento) - 2) {
                            $tresPalabras = $palabrasSegmento[$j] . ' ' . $palabrasSegmento[$j + 1] . ' ' . $palabrasSegmento[$j + 2];
                            if ($this->esCiudadColombiana($tresPalabras)) {
                                $ciudadEncontrada = $tresPalabras;
                            }
                        }
                    }
                    if ($ciudadEncontrada) {
                        Log::info('✅ DataExtractionService: Ciudad encontrada dentro del segmento antes de departamento', [
                            'ciudad' => $ciudadEncontrada, 'texto_original' => $ultimaParte, 'depto' => $depto
                        ]);
                        return mb_strtoupper($this->removeAccents($ciudadEncontrada));
                    }
                    
                    // Paso C: Solo confiar ciegamente en textos cortos (1-2 palabras) que podrían ser municipios pequeños
                    $numPalabras = count($palabrasSegmento);
                    if ($numPalabras <= 2) {
                        Log::info('✅ DataExtractionService: Ciudad aceptada por estar antes de departamento (texto corto)', [
                            'ciudad' => $ultimaParte, 'depto' => $depto, 'num_palabras' => $numPalabras
                        ]);
                        return mb_strtoupper($ultimaParte);
                    }
                    
                    // Si tiene 3+ palabras y no se encontró ciudad conocida, NO confiar → continuar al paso 3
                    Log::info('⚠️ DataExtractionService: Texto largo antes de departamento NO confiable, ignorando', [
                        'texto' => $ultimaParte, 'depto' => $depto, 'num_palabras' => $numPalabras
                    ]);
                }
            }
        }
        
        // 3. Buscar cualquier ciudad colombiana mencionada en el texto (preferir la última)
        $ciudadesValidas = [
            'bogota', 'bogotá', 'medellin', 'medellín', 'cali', 'barranquilla', 'cartagena',
            'bucaramanga', 'pereira', 'cucuta', 'cúcuta', 'ibague', 'ibagué', 'manizales',
            'santa marta', 'villavicencio', 'pasto', 'monteria', 'montería', 'neiva',
            'valledupar', 'armenia', 'popayan', 'popayán', 'sincelejo', 'tunja', 'riohacha',
            'buenaventura', 'girardot', 'floridablanca', 'soacha', 'bello', 'soledad',
            'palmira', 'envigado', 'itagui', 'itagüí', 'dosquebradas', 'tulua', 'tuluá',
            'apartado', 'apartadó', 'cartago', 'barrancabermeja', 'yopal', 'florencia',
            'funza', 'zipaquira', 'zipaquirá', 'chia', 'chía', 'sogamoso', 'duitama',
            'ipiales', 'tumaco', 'quibdo', 'quibdó', 'leticia', 'mocoa', 'arauca',
            'turbo', 'caucasia', 'rionegro', 'la dorada', 'honda', 'mariquita', 'espinal',
            'melgar', 'fusagasuga', 'fusagasugá', 'facatativa', 'facatativá', 'madrid',
            'mosquera', 'cajica', 'cajicá', 'tocancipá', 'tocancipa', 'cota', 'tenjo',
            'tabio', 'la calera', 'sibate', 'sibaté', 'sopo', 'sopó', 'yumbo',
            'jamundí', 'jamundi', 'buga', 'sevilla', 'ciénaga', 'cienaga',
            'fundación', 'fundacion', 'magangué', 'magangue', 'mompos', 'mompós',
            'curumani', 'curumaní', 'aguachica', 'codazzi', 'bosconia', 'chiriguaná', 'chiriguana',
            'san alberto', 'gamarra', 'pelaya', 'pailitas', 'la jagua de ibirico',
            'ocaña', 'ocana', 'pamplona', 'villa del rosario', 'los patios',
            'planeta rica', 'cereté', 'cerete', 'sahagún', 'sahagun', 'lorica',
            'montelíbano', 'montelibano', 'tierralta',
            'pitalito', 'garzón', 'garzon', 'la plata',
            'puerto lópez', 'puerto lopez', 'acacías', 'acacias', 'granada',
            'chaparral', 'líbano', 'libano', 'puerto berrío', 'puerto berrio',
            'curumani', 'curumaní', 'cumaribo', 'maicao', 'aguazul', 'corozal',
            'san jose del guaviare', 'san josé del guaviare',
            'puerto inirida', 'puerto inírida', 'mitú', 'mitu',
            'puerto carreño', 'saravena', 'tame'
        ];
        
        $lastMatch = null;
        foreach ($ciudadesValidas as $ciudad) {
            // Buscar como palabra completa
            $ciudadPattern = preg_quote($ciudad, '/');
            if (preg_match('/\b' . $ciudadPattern . '\b/ui', $texto)) {
                $lastMatch = mb_strtoupper($this->removeAccents($ciudad));
            }
        }
        
        if ($lastMatch) {
            return $lastMatch;
        }
        
        return null;
    }

    /**
     * Remover acentos para comparación flexible
     */
    private function removeAccents(string $str): string
    {
        $str = mb_strtolower($str);
        $map = ['á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ñ'=>'n'];
        return strtr($str, $map);
    }
}
