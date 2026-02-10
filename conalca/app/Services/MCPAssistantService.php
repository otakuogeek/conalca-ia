<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ConversationSession;
use App\Models\ConversationMessage;
use App\Models\GroupCotization;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio de Asistente IA con MCP (Model Context Protocol)
 * 
 * Este servicio usa OpenAI ChatGPT API directamente
 * y se comunica con el servidor MCP en https://conalcaia.conalca.com.co/mcp/
 * 
 * Funcionalidades:
 * - Chat completions con OpenAI ChatGPT
 * - Integración con herramientas MCP
 * - Gestión de conversaciones persistentes
 * - Tool calling para create_cotizacion, search_products, get_empaques
 */
class MCPAssistantService
{
    // Groq Configuration (para chat agéntico)
    private static $groq_api_key = null;
    private static $groq_model = null;
    
    // OpenAI Configuration (para otros servicios)
    private static $openai_api_key = null;
    private static $openai_model = null;
    private static $reasoning_effort = 'medium'; // GPT-5 reasoning: none, low, medium, high
    
    // Proveedor activo para chat
    private static $chat_provider = 'groq'; // 'groq' o 'openai'
    private static $mcp_base_url = null;

    /**
     * Inicializar configuraciones
     */
    private static function initConfig()
    {
        if (!self::$groq_api_key) {
            // Groq Configuration (proveedor principal para chat agéntico)
            self::$groq_api_key = config('services.groq.api_key') ?: env('GROQ_API_KEY');
            self::$groq_model = config('services.groq.model') ?: env('GROQ_MODEL', 'compound-beta');
            
            // OpenAI Configuration (respaldo y otros servicios)
            self::$openai_api_key = config('services.openai.api_key') ?: env('OPENAI_API_KEY');
            self::$openai_model = config('services.openai.model') ?: env('OPENAI_MODEL', 'gpt-4o-mini');
            self::$reasoning_effort = env('OPENAI_REASONING_EFFORT', 'medium');
            
            // Proveedor de chat activo
            self::$chat_provider = config('services.chat.provider') ?: env('CHAT_PROVIDER', 'groq');
            self::$mcp_base_url = config('services.mcp.base_url') ?: env('MCP_BASE_URL', 'https://conalcaia.conalca.com.co/mcp/');
            
            Log::info('MCPAssistantService inicializado', [
                'chat_provider' => self::$chat_provider,
                'groq_model' => self::$groq_model,
                'openai_model' => self::$openai_model,
                'mcp_url' => self::$mcp_base_url
            ]);
        }
    }

    /**
     * 🆕 Detecta si hay mención de contenedores en el texto
     * La tara SOLO aplica cuando hay contenedores. Para carga suelta → NO hay tara.
     * 
     * @param string|null $texto Texto donde buscar contenedores
     * @return bool true si hay contenedor mencionado
     */
    private static function hayContenedorEnTexto($texto): bool
    {
        if (!$texto) return false;
        // Detectar: "contenedor", "contenedores", "1x20", "2x40", "20 pies", "40'", "CONTENEDOR 20", etc.
        return (bool)preg_match('/contenedor(?:es)?|\b\d+[xX](20|40|45)\b|\b(20|40|45)\s*(?:pies|\')/ui', $texto);
    }

    /**
     * Obtener la tara según el tipo de contenedor
     * - Contenedor 20 pies: 2300 kg
     * - Todos los demás (40 pies, etc.): 3400 kg
     * 
     * @param string|null $texto Texto donde buscar el tipo de contenedor
     * @return int Tara en kg (2300 o 3400)
     */
    private static function getTaraByContenedor($texto = null)
    {
        if (!$texto) {
            return 3400;
        }
        
        // 🔧 FIX: Usar word boundary y patrones más específicos para no confundir 40 con 20
        // Patrones que detectan específicamente contenedor de 20 pies:
        // - "contenedor de 20" / "contenedor 20"
        // - "20 pies" / "20'" (pero NO "40 pies" que contiene "0 pies")
        // - "1x20" / "2x20" / etc.
        // - "(1) 20 PIES"
        
        // Primero verificar si es contenedor de 40 para evitar falsos positivos
        if (preg_match('/(?:contenedor|cont).*?\b40\b|40\s*(?:pies|\')|(?:\'|x)40|\b\d+x40\b|\(\d+\)\s*40\s*pies/ui', $texto)) {
            return 3400;
        }
        
        // Ahora verificar si es contenedor de 20
        if (preg_match('/(?:contenedor|cont).*?\b20\b|(?<![04])20\s*(?:pies|\')|(?:\'|x)20|\b\d+x20\b|\(\d+\)\s*20\s*pies/ui', $texto)) {
            Log::info('📦 Tara detectada para contenedor 20 pies', ['tara' => 2300]);
            return 2300;
        }
        
        // Para todos los demás casos (sin especificar, etc.), usar 3400 kg
        return 3400;
    }

    /**
     * Wrapper público de getTaraByContenedor para uso desde el Controller
     * 
     * @param string|null $texto Texto donde buscar el tipo de contenedor
     * @return int Tara en kg (2300 o 3400)
     */
    public static function getTaraByContenedorPublic($texto = null)
    {
        return self::getTaraByContenedor($texto);
    }

    /**
     * Obtener o crear thread (conversación) para el cliente
     * En este caso, usamos la tabla conversation_sessions
     */
    public static function getThread(Client $client)
    {
        self::initConfig();
        
        // Buscar o crear sesión de conversación
        $session = ConversationSession::firstOrCreate(
            ['client_id' => $client->id],
            [
                'session_id' => 'mcp_' . $client->id . '_' . time(),
                'status' => 'active',
                'metadata' => json_encode([
                    'client_name' => $client->nombre_cliente,
                    'client_document' => $client->documento_cliente,
                    'created_at' => now()->toIso8601String()
                ])
            ]
        );

        Log::info('Thread obtenido/creado', [
            'client_id' => $client->id,
            'session_id' => $session->session_id
        ]);

        return $session->session_id;
    }

    /**
     * Crear un mensaje en la conversación
     */
    public static function createMessage($threadId, $message, $groupId = null)
    {
        self::initConfig();

        $session = ConversationSession::where('session_id', $threadId)->first();
        
        if (!$session) {
            Log::warning('Thread no encontrado en BD, retornando THREAD_NOT_FOUND', [
                'thread_id' => $threadId
            ]);
            return 'THREAD_NOT_FOUND';
        }

        // Guardar mensaje del usuario CON group_cotization_id
        $conversationMessage = ConversationMessage::create([
            'session_id' => $session->id,
            'group_cotization_id' => $groupId, // 🆕 Guardar group_id
            'role' => 'user',
            'content' => $message,
            'timestamp' => now()
        ]);

        Log::info('Mensaje creado', [
            'thread_id' => $threadId,
            'group_id' => $groupId,
            'message_id' => $conversationMessage->id,
            'message_length' => strlen($message)
        ]);

        return $conversationMessage;
    }

    /**
     * 🆕 Encontrar la primera ruta que no tiene producto validado
     * @param array $extractedData Datos de las rutas
     * @return int|null Índice de la ruta sin producto, o null si todas tienen
     */
    private static function findNextRouteWithoutProduct($extractedData)
    {
        if (!is_array($extractedData) || empty($extractedData)) {
            return null;
        }
        
        // Verificar si es multi-ruta (array indexado numéricamente)
        $isMultiRoute = isset($extractedData[0]) && is_array($extractedData[0]);
        
        if (!$isMultiRoute) {
            // Ruta única - verificar si tiene producto validado
            $hasProduct = !empty($extractedData['producto_codigo']) && 
                         !empty($extractedData['producto']);
            return $hasProduct ? null : 0;
        }

        // 🆕 FIX: Asegurar orden secuencial de rutas (0, 1, 2...)
        ksort($extractedData);
        
        // Multi-ruta - buscar primera sin producto validado
        foreach ($extractedData as $idx => $ruta) {
            if (!is_numeric($idx) || !is_array($ruta)) continue;
            
            $hasProduct = !empty($ruta['producto_codigo']) && 
                         !empty($ruta['producto']) &&
                         strtoupper($ruta['producto']) !== 'PERSONALIZADO';
            
            if (!$hasProduct) {
                return $idx;
            }
        }
        
        return null; // Todas las rutas tienen producto validado
    }
    
    /**
     * 🆕 Generar resumen definitivo cuando todas las rutas están completas
     * @param array $extractedData Datos de las rutas
     * @return string Mensaje de resumen formateado
     */
    private static function generateFinalSummary($extractedData)
    {
        $isMultiRoute = isset($extractedData[0]) && is_array($extractedData[0]);
        
        if (!$isMultiRoute) {
            // Ruta única
            $ruta = $extractedData;
            $respuesta = "✅ **¡Cotización lista!**\n\n";
            $respuesta .= "**Resumen:**\n";
            if (!empty($ruta['origen'])) $respuesta .= "- Origen: " . strtoupper($ruta['origen']) . "\n";
            if (!empty($ruta['destino'])) $respuesta .= "- Destino: " . strtoupper($ruta['destino']) . "\n";
            if (!empty($ruta['producto'])) $respuesta .= "- Producto: " . $ruta['producto'] . "\n";
            if (!empty($ruta['peso_kg'])) $respuesta .= "- Peso: " . number_format($ruta['peso_kg'], 0, ',', '.') . " kg\n";
            if (!empty($ruta['vehiculo'])) $respuesta .= "- Vehículo: " . strtoupper($ruta['vehiculo']) . "\n";
            $respuesta .= "\nPuedes **crear la cotización** cuando estés listo.";
            return $respuesta;
        }
        
        // Multi-ruta
        $respuesta = "✅ **¡Todas las rutas están completas!**\n\n";
        $respuesta .= "**RESUMEN DEFINITIVO:**\n\n";
        
        foreach ($extractedData as $idx => $ruta) {
            if (!is_numeric($idx) || !is_array($ruta)) continue;
            
            $num = $idx + 1;
            $origen = strtoupper($ruta['origen'] ?? 'N/A');
            $destino = strtoupper($ruta['destino'] ?? 'N/A');
            
            $respuesta .= "**Ruta {$num}:** {$origen} → {$destino}\n";
            if (!empty($ruta['producto'])) $respuesta .= "- Producto: " . $ruta['producto'] . "\n";
            if (!empty($ruta['peso_kg'])) $respuesta .= "- Peso: " . number_format($ruta['peso_kg'], 0, ',', '.') . " kg\n";
            if (!empty($ruta['vehiculo'])) $respuesta .= "- Vehículo: " . strtoupper($ruta['vehiculo']) . "\n";
            $respuesta .= "\n";
        }
        
        $respuesta .= "Puedes **crear la cotización** cuando estés listo, o solicitar cambios en cualquier ruta.";
        
        return $respuesta;
    }
    
    /**
     * 🆕 Buscar productos para una ruta específica y mostrar opciones
     * @param ConversationSession $session Sesión de conversación
     * @param int $groupId ID del grupo de cotización
     * @param array $extractedData Datos extraídos
     * @param int $routeIndex Índice de la ruta a buscar
     * @param bool $isExplicitProductChange Si TRUE, es un cambio explícito de producto (buscar opciones). Si FALSE, es análisis inicial (NO buscar opciones)
     * @return array|null Resultado de la búsqueda o null
     */
    private static function searchProductForRoute($session, $groupId, &$extractedData, $routeIndex, $isExplicitProductChange = false)
    {
        $isMultiRoute = isset($extractedData[0]) && is_array($extractedData[0]);
        $ruta = $isMultiRoute ? ($extractedData[$routeIndex] ?? null) : $extractedData;
        
        if (!$ruta) return null;
        
        // Obtener el término de búsqueda (producto mencionado)
        $searchTerm = $ruta['producto'] ?? $ruta['producto_mencionado'] ?? null;
        
        if (empty($searchTerm) || strtoupper($searchTerm) === 'PERSONALIZADO') {
            return null; // No hay producto para buscar
        }
        
        $origen = strtoupper($ruta['origen'] ?? 'N/A');
        $destino = strtoupper($ruta['destino'] ?? 'N/A');
        $routeNum = $routeIndex + 1;
        
        // 🆕 CRÍTICO: En multi-ruta, NO buscar opciones en análisis inicial para evitar bug de re-búsqueda infinita
        // Solo buscar opciones cuando el usuario EXPLÍCITAMENTE cambie el producto
        if ($isMultiRoute && !$isExplicitProductChange) {
            Log::info("⏭️ SKIP búsqueda automática en multi-ruta (análisis inicial)", [
                'search_term' => $searchTerm,
                'ruta' => "{$origen} → {$destino}",
                'route_index' => $routeIndex,
                'reason' => 'Evitar bug de re-búsqueda infinita en ediciones'
            ]);
            
            // Guardar el producto tal cual sin validar ni buscar opciones
            // El sistema lo usará como está hasta que el usuario lo cambie explícitamente
            return ['skipped' => true, 'producto_guardado' => $searchTerm];
        }
        
        Log::info("🔍 Buscando producto para Ruta {$routeNum}", [
            'search_term' => $searchTerm,
            'ruta' => "{$origen} → {$destino}",
            'is_explicit_change' => $isExplicitProductChange
        ]);
        
        // Buscar en la tabla products
        // 🔧 FIX: Usar nombres correctos de columas (producto_codigo, producto_nombre, etc)
        // y usar alias para mantener compatibilidad con el resto del código
        $productos = \DB::table('products')
            ->where('producto_nombre', 'LIKE', "%{$searchTerm}%")
            ->limit(5)
            ->get([
                'producto_codigo as codigo', 
                'producto_nombre as nombre', 
                'producto_codigo_ministerio as codigo_ministerio', 
                'tippro_nombre as tipo_producto', 
                'natcar_nombre as naturaleza_carga'
            ])
            ->toArray();
        
        if (count($productos) === 0) {
            // 🆕 FIX: Si no se encuentra, NO retornar null (rompe el flujo).
            // Retornar petición manual y guardar estado para que el próximo mensaje sea la búsqueda.
            
            $mensaje = "🔍 No encontré productos exactos para '**{$searchTerm}**' en la **Ruta {$routeNum}**.\n\n";
            $mensaje .= "Por favor, escribe el nombre del producto nuevamente (ej: 'Cemento gris') o selecciona una categoría general.";
            
            // Guardar índice de ruta pendiente para que el próximo mensaje se aplique a esta ruta
            $metadata = json_decode($session->metadata ?? '{}', true);
            $metadata['producto_pendiente_ruta_index'] = $routeIndex;
            // Limpiar search term anterior para forzar nueva búsqueda con lo que escriba el usuario
            unset($metadata['producto_search_term']);
            unset($metadata['productos_pendientes']);
            
            $session->metadata = json_encode($metadata);
            $session->save();
            
            return [
                'needs_selection' => true, // Tratamos como que necesita selección (acción del usuario)
                'message' => $mensaje,
                'productos' => [], // Lista vacía
                'route_index' => $routeIndex
            ];
        }
        
        // 🆕 FIX: En multi-ruta, SIEMPRE mostrar opciones aunque haya solo 1 producto
        // Esto evita que se auto-aplique el mismo producto a todas las rutas
        // El usuario debe confirmar producto para CADA ruta
        if (count($productos) === 1 && !$isMultiRoute) {
            // Solo auto-seleccionar si es ruta ÚNICA
            $prod = (array)$productos[0];
            
            $extractedData['producto'] = $prod['nombre'];
            $extractedData['producto_codigo'] = $prod['codigo'];
            $extractedData['producto_nombre'] = $prod['nombre'];
            $extractedData['tipo_producto'] = $prod['nombre'];
            
            Log::info("✅ Producto único auto-seleccionado (ruta única)", [
                'producto' => $prod['nombre'],
                'codigo' => $prod['codigo']
            ]);
            
            return ['auto_selected' => true, 'producto' => $prod];
        }
        
        // Múltiples resultados - mostrar opciones
        $productosArray = [];
        $opcionesTxt = "📦 **Ruta {$routeNum}** ({$origen} → {$destino})\n\n";
        $opcionesTxt .= "Selecciona el producto para esta ruta:\n\n";
        
        foreach ($productos as $idx => $prod) {
            $p = (array)$prod;
            $opcionesTxt .= ($idx + 1) . ". **{$p['nombre']}**\n";
            $opcionesTxt .= "   - Código: {$p['codigo']}\n";
            if (!empty($p['naturaleza_carga'])) {
                $opcionesTxt .= "   - Naturaleza: {$p['naturaleza_carga']}\n";
            }
            $opcionesTxt .= "\n";
            $productosArray[] = $p;
        }
        
        $opcionesTxt .= "Indica cuál opción deseas (ej: 'opción 1').";
        
        // Guardar productos pendientes y el índice de ruta en metadata
        $metadata = json_decode($session->metadata ?? '{}', true);
        $metadata['productos_pendientes'] = $productosArray;
        $metadata['producto_search_term'] = $searchTerm;
        $metadata['producto_pendiente_ruta_index'] = $routeIndex;
        $session->metadata = json_encode($metadata);
        $session->save();
        
        Log::info("📋 Opciones de producto guardadas para Ruta {$routeNum}", [
            'count' => count($productosArray),
            'ruta_index' => $routeIndex
        ]);
        
        return [
            'needs_selection' => true,
            'message' => $opcionesTxt,
            'productos' => $productosArray,
            'route_index' => $routeIndex
        ];
    }

    /**
     * Ejecutar el asistente - procesar mensajes y obtener respuesta
     * @param string $threadId ID del thread de conversación
     * @param string $typeBusiness Tipo de negocio (dta, etc.)
     * @param int|null $groupId ID del grupo de cotización
     * @param int|null $selectedRouteIndex Índice de la ruta seleccionada para edición (null = todas)
     * @param int $existingRoutesCount Cantidad de rutas que ya existen en el frontend
     */
    public static function runAssistant($threadId, $typeBusiness = 'dta', $groupId = null, $selectedRouteIndex = null, $existingRoutesCount = 0)
    {
        self::initConfig();
        
        Log::info('🚀 runAssistant iniciado', [
            'thread_id' => $threadId,
            'group_id' => $groupId,
            'selected_route_index' => $selectedRouteIndex,
            'existing_routes_count' => $existingRoutesCount
        ]);

        $session = ConversationSession::where('session_id', $threadId)->first();
        
        if (!$session) {
            Log::warning('Thread no encontrado en runAssistant, retornando THREAD_NOT_FOUND', [
                'thread_id' => $threadId
            ]);
            return 'THREAD_NOT_FOUND';
        }
        
        // 🆕 Guardar group_id en la sesión para usar en mensajes posteriores
        if ($groupId) {
            $metadata = json_decode($session->metadata ?? '{}', true);
            $metadata['current_group_id'] = $groupId;
            $session->metadata = json_encode($metadata);
            $session->save();
            
            Log::info('Group ID guardado en sesión', [
                'thread_id' => $threadId,
                'group_id' => $groupId
            ]);
        }

        // Obtener historial de mensajes FILTRADO POR GRUPO
        $messages = self::getMessagesArray($threadId, $groupId);

        // Detectar empaque mencionado en el último mensaje del usuario
        $detectedEmpaque = self::detectEmpaqueInMessage($messages);
        
        // 🆕 PASO 1: Obtener datos extraídos ANTERIORES DEL GRUPO ESPECÍFICO (no de la sesión compartida)
        $previousExtractedData = [];
        
        if ($groupId) {
            // Primero intentar obtener del grupo directamente
            $group = GroupCotization::find($groupId);
            if ($group && $group->extracted_data) {
                $previousExtractedData = json_decode($group->extracted_data, true) ?? [];
                Log::info('📦 Datos extraídos cargados desde GRUPO', [
                    'group_id' => $groupId,
                    'fields' => array_keys($previousExtractedData)
                ]);
            }
        }
        
        // Fallback: si no hay datos en el grupo, verificar metadata de sesión (compatibilidad)
        if (empty($previousExtractedData)) {
            $existingMetadata = json_decode($session->metadata ?? '{}', true);
            
            // Usar datos indexados por grupo si existen
            if (isset($existingMetadata['extracted_data_by_group'][$groupId])) {
                $previousExtractedData = $existingMetadata['extracted_data_by_group'][$groupId];
            }
        }
        
        Log::info('📊 Datos extraídos anteriores recuperados', [
            'thread_id' => $threadId,
            'group_id' => $groupId,
            'has_previous_data' => !empty($previousExtractedData),
            'previous_fields' => !empty($previousExtractedData) ? array_keys($previousExtractedData) : []
        ]);
        
        // 🆕 DETECCIÓN TEMPRANA DE EDICIÓN SIMPLE - Antes de extractAllDataFromMessage
        // Esto evita que mensajes como "origen ponga medellín" se procesen incorrectamente
        $lastUserMessageForEdit = '';
        foreach (array_reverse($messages) as $msg) {
            if (isset($msg['role']) && $msg['role'] === 'user') {
                $lastUserMessageForEdit = trim($msg['content'] ?? ''); // 🆕 TRIM para limpiar espacios/newlines
                break;
            }
        }
        
        // 🆕 LIMPIAR texto añadido por ChatController ("NOTA IMPORTANTE: Ignora...")
        // Esto evita que el mensaje del usuario se considere largo cuando no lo es
        $lastUserMessageForEdit = preg_replace('/\s*NOTA\s+IMPORTANTE:.*$/us', '', $lastUserMessageForEdit);
        $lastUserMessageForEdit = trim($lastUserMessageForEdit);
        
        // 🔧 FIX: DETECTAR NÚMERO DE RUTA EN EL MENSAJE ANTES DE PROCESAR
        // Ejemplo: "Cambia el origen de la ruta 3 a Medellín" → detectar ruta 3 (índice 2)
        if ($selectedRouteIndex === null && preg_match('/(?:ruta|opci[oó]n)\s*(\d+)/ui', $lastUserMessageForEdit, $rutaMatch)) {
            $rutaMencionada = intval($rutaMatch[1]) - 1; // Convertir a 0-based
            // Verificar si existe esa ruta en los datos anteriores
            if (!empty($previousExtractedData) && isset($previousExtractedData[$rutaMencionada])) {
                $selectedRouteIndex = $rutaMencionada;
                Log::info('📍 Ruta detectada explícitamente en mensaje (antes de merge)', [
                    'ruta_numero' => $rutaMatch[1],
                    'ruta_index' => $selectedRouteIndex,
                    'mensaje' => substr($lastUserMessageForEdit, 0, 60)
                ]);
            }
        }
        
        // 🆕 LIMPIAR PRODUCTOS PENDIENTES si el mensaje NO es sobre productos
        // Esto evita que aparezcan opciones de producto cuando el usuario edita otro campo
        $mensajeEsProducto = preg_match('/(?:producto|opci[oó]n\s*\d|selecciono?\s+\d|la\s+\d|el\s+\d|dame\s+(?:la\s+)?opci[oó]n)/ui', $lastUserMessageForEdit);
        $mensajeEsSeleccionOpcion = preg_match('/(?:opci[oó]n\s*\d|selecciono?\s+\d|la\s+\d|el\s+\d|dame\s+(?:la\s+)?opci[oó]n)/ui', $lastUserMessageForEdit);
        $mensajeEsNuevaBusquedaProducto = $mensajeEsProducto && !$mensajeEsSeleccionOpcion;
        
        $metadata = json_decode($session->metadata ?? '{}', true);
        
        if (!$mensajeEsProducto) {
            // Si el mensaje NO es sobre productos, limpiar todo
            if (isset($metadata['productos_pendientes'])) {
                unset($metadata['productos_pendientes']);
                unset($metadata['producto_search_term']);
                unset($metadata['producto_pendiente_ruta_index']);
                $session->metadata = json_encode($metadata);
                $session->save();
                Log::info('🧹 Productos pendientes limpiados al inicio (mensaje NO es sobre productos)', [
                    'mensaje' => substr($lastUserMessageForEdit, 0, 50)
                ]);
            }
        } elseif ($mensajeEsNuevaBusquedaProducto) {
            // 🆕 Si es una NUEVA búsqueda de producto (no selección), limpiar productos anteriores
            // Esto evita que búsquedas de rutas diferentes se mezclen
            if (isset($metadata['productos_pendientes'])) {
                $rutaAnterior = $metadata['producto_pendiente_ruta_index'] ?? null;
                // Solo limpiar si la ruta cambió
                if ($rutaAnterior !== $selectedRouteIndex) {
                    unset($metadata['productos_pendientes']);
                    unset($metadata['producto_search_term']);
                    unset($metadata['producto_pendiente_ruta_index']);
                    $session->metadata = json_encode($metadata);
                    $session->save();
                    Log::info('🧹 Productos pendientes limpiados (nueva búsqueda en ruta diferente)', [
                        'ruta_anterior' => $rutaAnterior,
                        'ruta_actual' => $selectedRouteIndex,
                        'mensaje' => substr($lastUserMessageForEdit, 0, 50)
                    ]);
                }
            }
        }
        
        // 🆕 PRE-PROCESAMIENTO: Separar palabras pegadas comunes del speech-to-text
        // Ejemplo: "pesoes900kg" → "peso es 900kg", "destinomedellin" → "destino medellin"
        $lastUserMessageForEdit = preg_replace('/(\w)(es)(\d)/ui', '$1 $2 $3', $lastUserMessageForEdit); // "pesoes900" → "peso es 900"
        $lastUserMessageForEdit = preg_replace('/(peso|origen|destino|producto|cantidad|valor|vehiculo)(es|ponga|cambia|modifica)/ui', '$1 $2', $lastUserMessageForEdit);
        $lastUserMessageForEdit = preg_replace('/(es|ponga|cambia)(\d+)/ui', '$1 $2', $lastUserMessageForEdit); // "es900" → "es 900"
        $lastUserMessageForEdit = preg_replace('/(\d)(kg|ton|kilos?|millones?)/ui', '$1 $2', $lastUserMessageForEdit); // "900kg" → "900 kg"
        
        // 🆕 DEBUG: Log del mensaje a procesar para edición simple
        Log::info('🔍 VERIFICANDO EDICIÓN SIMPLE', [
            'mensaje' => $lastUserMessageForEdit,
            'longitud' => strlen($lastUserMessageForEdit),
            'selected_route_index' => $selectedRouteIndex
        ]);
        
        // 🌍🆕 DETECCIÓN DE MÚLTIPLES ORÍGENES/DESTINOS
        // Si el usuario especifica múltiples orígenes y/o destinos, expandir a rutas individuales
        // Ejemplo: "origen cali y cartagena a destino medellin" → 2 rutas
        // 
        // 🚫 EXCEPCIÓN: Si el mensaje contiene múltiples "cotización de X a Y", 
        // NO usar lógica de multi-rutas porque cada cotización es independiente
        $multipleCotizaciones = preg_match_all('/cotizaci[oó]n\s+de\s+\w+\s+a\s+\w+/ui', $lastUserMessageForEdit, $cotizacionesMatches);
        
        if ($multipleCotizaciones >= 2) {
            Log::info('🚫 Múltiples cotizaciones independientes detectadas - delegando a OpenAI', [
                'cantidad_cotizaciones' => $multipleCotizaciones,
                'mensaje' => substr($lastUserMessageForEdit, 0, 100)
            ]);
            // NO procesar como multi-ruta, dejar que OpenAI separe cada cotización
            $multipleRoutes = null;
        } else {
            $multipleRoutes = self::detectMultipleRouteCities($lastUserMessageForEdit);
        }
        
        if ($multipleRoutes && empty($previousExtractedData)) {
            // SOLO crear múltiples rutas si NO hay datos previos (mensaje inicial)
            Log::info('🌍 Creando múltiples rutas desde mensaje inicial', [
                'origenes' => $multipleRoutes['origenes'],
                'destinos' => $multipleRoutes['destinos'],
                'total_rutas' => count($multipleRoutes['origenes']) * count($multipleRoutes['destinos'])
            ]);
            
            // 🆕 EXTRAER DATOS COMUNES O ESPECÍFICOS según el patrón detectado
            $fullText = $lastUserMessageForEdit;
            $datosComunes = [];
            
            // 🔧 FIX BUG #545: Si es patrón "Todo llega a DESTINO: desde X... desde Y...", 
            // dividir texto en segmentos y extraer datos específicos por ruta
            $esPatronDestinoComun = isset($multipleRoutes['_patron']) && $multipleRoutes['_patron'] === 'destino_comun';
            $datosEspecificosPorRuta = [];
            
            if ($esPatronDestinoComun && isset($multipleRoutes['_segmentos'])) {
                // Procesar cada segmento individualmente
                Log::info('🔧 Procesando segmentos específicos por origen', [
                    'total_segmentos' => count($multipleRoutes['_segmentos'])
                ]);
                
                foreach ($multipleRoutes['_segmentos'] as $origen => $segmento) {
                    $datos = [];
                    
                    // Extraer datos específicos de este segmento
                    $peso = self::extractPeso($segmento);
                    if ($peso) {
                        $datos['peso_mercancia'] = $peso;
                        $datos['pesoMercancia'] = $peso;
                        $datos['peso_kg'] = $peso; // 🔧 FIX: Agregar peso_kg también
                    }
                    
                    $producto = self::extractProducto($segmento);
                    if ($producto) {
                        $datos['producto'] = $producto;
                        $datos['tipo_producto'] = $producto;
                        $datos['producto_mencionado'] = $producto;
                    }
                    
                    // 🆕 FIX #595: Extraer empaque específico de cada segmento
                    $empaque = self::extractEmpaque($segmento);
                    if ($empaque) {
                        if (is_array($empaque)) {
                            $datos['empaque'] = $empaque['empaque'];
                            $datos['tipo_embalaje'] = $empaque['empaque'];
                            $datos['empaque_id'] = $empaque['empaque_id'];
                            
                            // 🆕 Si viene cantidad_contenedor del formato NxTAMAÑO, usarla como cantidad
                            if (isset($empaque['cantidad_contenedor']) && $empaque['cantidad_contenedor'] > 0) {
                                $datos['cantidad'] = $empaque['cantidad_contenedor'];
                                $datos['cantidadMercancia'] = $empaque['cantidad_contenedor'];
                                Log::info('📦 Cantidad extraída del formato contenedor', [
                                    'cantidad' => $empaque['cantidad_contenedor'],
                                    'empaque' => $empaque['empaque']
                                ]);
                            }
                        } else {
                            $datos['empaque'] = $empaque;
                            $datos['tipo_embalaje'] = $empaque;
                        }
                    }
                    
                    $valor = self::extractValorDeclarado($segmento);
                    if ($valor) {
                        $datos['valor_declarado'] = $valor;
                        $datos['valorMercancia'] = $valor;
                    }
                    
                    $cantidad = self::extractCantidad($segmento);
                    if ($cantidad) {
                        $datos['cantidad'] = $cantidad;
                        $datos['cantidadMercancia'] = $cantidad;
                    }
                    
                    // Procesar tara si está mencionada
                    if ($peso) {
                        $mencionaTara = preg_match('/\btara\b/ui', $segmento);
                        
                        // 🆕 FIX CRÍTICO: Mejorar detección de "tara incluida" / "con tara incluida"
                        // Patrones que indican que el peso YA INCLUYE la tara (NO agregar):
                        // - "con tara incluida" / "con la tara incluida"
                        // - "tara incluida" / "tara ya incluida"
                        // - "con tara" (pero NO "sin tara")
                        // - "peso bruto"
                        // - "ya incluye tara" / "ya tiene tara"
                        // - "kilogramos con tara" / "kg con tara"
                        $incluyeTara = preg_match('/(?:con\s+(?:la\s+)?tara\s+incluida|tara\s+(?:ya\s+)?incluida|(?:ya\s+)?(?:incluye|tiene)\s+(?:la\s+)?tara|con\s+tara(?!\s+(?:no|sin))|peso\s+bruto|(?:kilos?|kilogramos?|kg)\s+con\s+tara)/ui', $segmento);
                        
                        // 🔧 FIX: Agregar detección de "+ tara" y "más tara" (significa que hay que SUMAR la tara)
                        // También "sin tara" y "no incluye tara"
                        $noIncluyeTara = preg_match('/(?:no\s+incluye|sin)\s*(?:la\s+)?tara|\+\s*tara|m[aá]s\s+tara|peso\s+neto/ui', $segmento);
                        
                        Log::info('🔍 MCPAssistantService - Análisis de tara', [
                            'segmento' => substr($segmento, 0, 100),
                            'peso_extraido' => $peso,
                            'menciona_tara' => (bool)$mencionaTara,
                            'incluye_tara' => (bool)$incluyeTara,
                            'no_incluye_tara' => (bool)$noIncluyeTara
                        ]);
                        
                        // 🆕 REGLA: Tara SOLO aplica cuando hay CONTENEDORES
                        $hayContenedorSeg = self::hayContenedorEnTexto($segmento) || self::hayContenedorEnTexto($fullText);
                        
                        if ($incluyeTara) {
                            // El peso YA incluye la tara, NO sumar nada
                            $datos['peso_mercancia'] = $peso;
                            $datos['pesoMercancia'] = $peso;
                            $datos['peso_kg'] = $peso;
                            $datos['incluye_tara'] = true;
                            Log::info('✅ MCPAssistantService - Tara YA incluida, peso se mantiene', [
                                'peso' => $peso
                            ]);
                        } elseif ($noIncluyeTara && $hayContenedorSeg) {
                            // "sin tara" CON contenedor → sumar tara
                            $taraEstandar = self::getTaraByContenedor($segmento);
                            $pesoConTara = $peso + $taraEstandar;
                            $datos['peso_mercancia'] = $pesoConTara;
                            $datos['pesoMercancia'] = $pesoConTara;
                            $datos['peso_kg'] = $pesoConTara;
                            $datos['peso_bruto'] = $peso;
                            $datos['tara'] = $taraEstandar;
                            $datos['incluye_tara'] = true;
                            Log::info('🏋️ MCPAssistantService - Tara agregada (sin tara, con contenedor)', [
                                'peso_original' => $peso,
                                'tara_aplicada' => $taraEstandar,
                                'peso_con_tara' => $pesoConTara
                            ]);
                        } elseif ($noIncluyeTara && !$hayContenedorSeg) {
                            // 🆕 "sin tara" pero NO hay contenedor → NO aplicar tara (carga suelta)
                            $datos['peso_mercancia'] = $peso;
                            $datos['pesoMercancia'] = $peso;
                            $datos['peso_kg'] = $peso;
                            $datos['incluye_tara'] = false;
                            Log::info('📦 MCPAssistantService - "sin tara" pero sin contenedor → peso se mantiene (carga suelta)', [
                                'peso' => $peso
                            ]);
                        } elseif (!$mencionaTara && $hayContenedorSeg) {
                            // Contenedor pero NO menciona tara → agregar tara por defecto
                            $taraBase = self::getTaraByContenedor($segmento);
                            $taraCalculada = $taraBase;
                            $pesoConTara = $peso + $taraCalculada;
                            $datos['peso_mercancia'] = $pesoConTara;
                            $datos['pesoMercancia'] = $pesoConTara;
                            $datos['peso_kg'] = $pesoConTara;
                            $datos['peso_bruto'] = $peso;
                            $datos['tara'] = $taraCalculada;
                            $datos['incluye_tara'] = true;
                            Log::info('📦 MCPAssistantService - Tara agregada por defecto (contenedor)', [
                                'peso_original' => $peso,
                                'tara_base' => $taraBase,
                                'tara_calculada' => $taraCalculada,
                                'peso_con_tara' => $pesoConTara
                            ]);
                        } else {
                            // 🆕 Sin contenedor, sin mención de tara → NO aplicar tara (carga suelta)
                            $datos['peso_mercancia'] = $peso;
                            $datos['pesoMercancia'] = $peso;
                            $datos['peso_kg'] = $peso;
                            $datos['incluye_tara'] = false;
                            Log::info('📦 MCPAssistantService - Sin contenedor → peso se mantiene (carga suelta)', [
                                'peso' => $peso
                            ]);
                        }
                    }
                    
                    $datosEspecificosPorRuta[$origen] = $datos;
                    
                    Log::info('📋 Datos específicos extraídos para origen', [
                        'origen' => $origen,
                        'peso' => $datos['peso_mercancia'] ?? 'N/A',
                        'producto' => $datos['producto'] ?? 'N/A',
                        'empaque' => $datos['empaque'] ?? 'N/A',
                        'valor' => $datos['valor_declarado'] ?? 'N/A',
                        'cantidad' => $datos['cantidad'] ?? 'N/A'
                    ]);
                }
                
                // Extraer datos que SÍ son comunes (vehículo, empaque si no está en segmentos)
                $vehiculo = self::extractVehiculo($fullText);
                if ($vehiculo) {
                    $datosComunes['vehiculo'] = $vehiculo;
                    $datosComunes['claseVehiculo'] = $vehiculo;
                    Log::info('🚛 Vehículo común detectado', ['vehiculo' => $vehiculo]);
                }
                
                $empaque = self::extractEmpaque($fullText);
                if ($empaque) {
                    if (is_array($empaque)) {
                        $datosComunes['empaque'] = $empaque['empaque'];
                        $datosComunes['tipo_embalaje'] = $empaque['empaque'];
                        $datosComunes['empaque_id'] = $empaque['empaque_id'];
                        
                        // 🆕 Si viene cantidad_contenedor del formato NxTAMAÑO
                        if (isset($empaque['cantidad_contenedor']) && $empaque['cantidad_contenedor'] > 0) {
                            $datosComunes['cantidad'] = $empaque['cantidad_contenedor'];
                            $datosComunes['cantidadMercancia'] = $empaque['cantidad_contenedor'];
                        }
                    } else {
                        $datosComunes['empaque'] = $empaque;
                        $datosComunes['tipo_embalaje'] = $empaque;
                    }
                    Log::info('📦 Empaque común detectado', ['empaque' => $datosComunes['empaque']]);
                }
                
            } else {
                // Lógica original: extraer datos comunes del texto completo
            
            // Extraer peso
            $peso = self::extractPeso($fullText);
            if ($peso) {
                $datosComunes['peso_mercancia'] = $peso;
                $datosComunes['pesoMercancia'] = $peso;
                $datosComunes['peso_kg'] = $peso; // 🔧 FIX: Agregar peso_kg también
                Log::info('📊 Peso común para todas las rutas', ['peso' => $peso]);
            }
            
            // Extraer cantidad
            $cantidad = self::extractCantidad($fullText);
            if ($cantidad) {
                $datosComunes['cantidad'] = $cantidad;
                $datosComunes['cantidadMercancia'] = $cantidad;
                Log::info('📦 Cantidad común para todas las rutas', ['cantidad' => $cantidad]);
            }
            
            // Extraer producto
            $producto = self::extractProducto($fullText);
            if ($producto) {
                $datosComunes['producto'] = $producto;
                $datosComunes['producto_mencionado'] = $producto;
                $datosComunes['tipo_producto'] = $producto;
                Log::info('📦 Producto común para todas las rutas', ['producto' => $producto]);
            }
            
            // Extraer vehículo
            $vehiculo = self::extractVehiculo($fullText);
            if ($vehiculo) {
                $datosComunes['vehiculo'] = $vehiculo;
                $datosComunes['claseVehiculo'] = $vehiculo;
                $datosComunes['vehiculo_requerido'] = $vehiculo;
                Log::info('🚛 Vehículo común para todas las rutas', ['vehiculo' => $vehiculo]);
            }
            
            // Extraer empaque
            $empaque = self::extractEmpaque($fullText);
            if ($empaque) {
                // extractEmpaque puede retornar array con ['empaque' => X, 'empaque_id' => Y, 'cantidad_contenedor' => Z] o solo string
                if (is_array($empaque)) {
                    $datosComunes['empaque'] = $empaque['empaque'];
                    $datosComunes['tipo_embalaje'] = $empaque['empaque'];
                    $datosComunes['empaque_id'] = $empaque['empaque_id'];
                    
                    // 🆕 Si viene cantidad_contenedor del formato NxTAMAÑO, usarla como cantidad
                    if (isset($empaque['cantidad_contenedor']) && $empaque['cantidad_contenedor'] > 0) {
                        $datosComunes['cantidad'] = $empaque['cantidad_contenedor'];
                        $datosComunes['cantidadMercancia'] = $empaque['cantidad_contenedor'];
                        Log::info('📦 Cantidad extraída del formato contenedor (común)', [
                            'cantidad' => $empaque['cantidad_contenedor'],
                            'empaque' => $empaque['empaque']
                        ]);
                    }
                } else {
                    $datosComunes['empaque'] = $empaque;
                    $datosComunes['tipo_embalaje'] = $empaque;
                }
                Log::info('📦 Empaque común para todas las rutas', ['empaque' => $datosComunes['empaque']]);
            }
            
            // 🆕 CALCULAR TARA AUTOMÁTICAMENTE
            // Lógica mejorada: Si el usuario dice "sin tara" o "no incluye tara", significa que DEBE sumarse la tara
            // 🔧 FIX: NO recalcular tara si estamos editando campos de una ruta existente
            $esEdicionCampo = !empty($previousExtractedData) && count($previousExtractedData) > 0;
            $mencionaTara = preg_match('/\btara\b/ui', $fullText);
            
            // 🆕 FIX CRÍTICO: Mejorar detección de "tara incluida" / "con tara incluida"
            $incluyeTara = preg_match('/(?:con\s+(?:la\s+)?tara\s+incluida|tara\s+(?:ya\s+)?incluida|(?:ya\s+)?(?:incluye|tiene)\s+(?:la\s+)?tara|con\s+tara(?!\s+(?:no|sin))|peso\s+bruto|(?:kilos?|kilogramos?|kg)\s+con\s+tara)/ui', $fullText);
            
            // 🔧 FIX: Agregar detección de "+ tara" y "más tara" (significa que hay que SUMAR la tara)
            // Ejemplo: "20 toneladas + tara" = 20000 + 3400 = 23400 kg
            $noIncluyeTara = preg_match('/(?:no\s+incluye|sin)\s*(?:la\s+)?tara|\+\s*tara|m[aá]s\s+tara|peso\s+neto/ui', $fullText);
            
            // 🆕 REGLA: Tara SOLO aplica cuando hay CONTENEDORES
            $hayContenedorGlobal = self::hayContenedorEnTexto($fullText);
            
            Log::info('🔍 MCPAssistantService - Análisis de tara global', [
                'full_text_preview' => substr($fullText, 0, 150),
                'peso_extraido' => $peso ?? null,
                'menciona_tara' => (bool)$mencionaTara,
                'incluye_tara' => (bool)$incluyeTara,
                'no_incluye_tara' => (bool)$noIncluyeTara,
                'es_edicion' => $esEdicionCampo,
                'hay_contenedor' => $hayContenedorGlobal
            ]);
            
            // 🆕 CASO 1: Usuario dice que YA INCLUYE tara → NO SUMAR NADA
            if ($peso && $incluyeTara && !$esEdicionCampo) {
                $datosComunes['peso_mercancia'] = $peso;
                $datosComunes['pesoMercancia'] = $peso;
                $datosComunes['peso_kg'] = $peso;
                $datosComunes['incluye_tara'] = true;
                Log::info('✅ MCPAssistantService - Tara YA incluida en texto global - peso se mantiene', [
                    'peso' => $peso
                ]);
            } elseif ($peso && $noIncluyeTara && $hayContenedorGlobal && !$esEdicionCampo) {
                // CASO 2: "sin tara" CON contenedor → SUMAR TARA
                $taraEstandar = self::getTaraByContenedor($fullText);
                $pesoTotal = $peso + $taraEstandar;
                
                $datosComunes['peso_mercancia'] = $pesoTotal;
                $datosComunes['pesoMercancia'] = $pesoTotal;
                $datosComunes['peso_kg'] = $pesoTotal;
                $datosComunes['peso_bruto'] = $peso;
                $datosComunes['tara'] = $taraEstandar;
                $datosComunes['incluye_tara'] = true;
                
                Log::info('🏋️ MCPAssistantService - "sin tara" con contenedor - Tara sumada', [
                    'peso_original_sin_tara' => $peso,
                    'tara_agregada' => $taraEstandar,
                    'peso_total_con_tara' => $pesoTotal
                ]);
            } elseif ($peso && $noIncluyeTara && !$hayContenedorGlobal && !$esEdicionCampo) {
                // 🆕 CASO 2b: "sin tara" pero NO hay contenedor → NO aplicar tara (carga suelta)
                $datosComunes['peso_mercancia'] = $peso;
                $datosComunes['pesoMercancia'] = $peso;
                $datosComunes['peso_kg'] = $peso;
                $datosComunes['incluye_tara'] = false;
                Log::info('📦 MCPAssistantService - "sin tara" pero sin contenedor → peso se mantiene (carga suelta)', [
                    'peso' => $peso
                ]);
            } elseif ($peso && !$mencionaTara && $hayContenedorGlobal && !$esEdicionCampo) {
                // CASO 3: Contenedor presente, NO menciona tara → Agregar tara por defecto
                $taraBase = self::getTaraByContenedor($fullText);
                $taraCalculada = $taraBase;
                $pesoTotal = $peso + $taraCalculada;
                
                $datosComunes['peso_mercancia'] = $pesoTotal;
                $datosComunes['pesoMercancia'] = $pesoTotal;
                $datosComunes['peso_kg'] = $pesoTotal;
                $datosComunes['peso_bruto'] = $peso;
                $datosComunes['tara'] = $taraCalculada;
                $datosComunes['incluye_tara'] = true;
                
                Log::info('📦 MCPAssistantService - Tara por defecto (contenedor, no mencionó tara)', [
                    'peso_original' => $peso,
                    'tara_calculada' => $taraCalculada,
                    'peso_total_con_tara' => $pesoTotal
                ]);
            } elseif ($peso && !$mencionaTara && !$hayContenedorGlobal && !$esEdicionCampo) {
                // 🆕 CASO 3b: Sin contenedor, sin mención de tara → NO aplicar tara (carga suelta)
                $datosComunes['peso_mercancia'] = $peso;
                $datosComunes['pesoMercancia'] = $peso;
                $datosComunes['peso_kg'] = $peso;
                $datosComunes['incluye_tara'] = false;
                Log::info('📦 MCPAssistantService - Sin contenedor, sin mención tara → peso se mantiene (carga suelta)', [
                    'peso' => $peso
                ]);
            } elseif ($esEdicionCampo && $peso) {
                // 🆕 CASO 4: Estamos editando campos - mantener el peso sin modificar
                $datosComunes['peso_mercancia'] = $peso;
                $datosComunes['pesoMercancia'] = $peso;
                Log::info('🔧 Modo edición detectado - Peso mantenido sin recalcular tara', [
                    'peso' => $peso,
                    'es_edicion' => $esEdicionCampo
                ]);
            }
            // Extraer valor declarado
            $valor = self::extractValorDeclarado($fullText);
            if ($valor) {
                $datosComunes['valor_declarado'] = $valor;
                $datosComunes['valorMercancia'] = $valor;
                Log::info('💰 Valor común para todas las rutas', ['valor' => $valor]);
            }
            
            } // Fin del else (lógica original)
            
            // Generar todas las combinaciones de rutas (producto cartesiano)
            $rutasGeneradas = [];
            foreach ($multipleRoutes['origenes'] as $origen) {
                foreach ($multipleRoutes['destinos'] as $destino) {
                    // 🔧 FIX BUG #545: Usar datos específicos si están disponibles
                    $datosRuta = $datosComunes;
                    if (!empty($datosEspecificosPorRuta) && isset($datosEspecificosPorRuta[$origen])) {
                        // Mergear datos específicos sobre los comunes
                        $datosRuta = array_merge($datosComunes, $datosEspecificosPorRuta[$origen]);
                    }
                    
                    $rutasGeneradas[] = array_merge([
                        'origen' => $origen,
                        'ciudad_origen' => $origen,
                        'ciudadOrigen' => $origen,
                        'destino' => $destino,
                        'ciudad_destino' => $destino,
                        'ciudadDestino' => $destino,
                        'ruta_numero' => count($rutasGeneradas) + 1
                    ], $datosRuta); // 🔥 Aplicar datos específicos o comunes
                }
            }
            
            Log::info('✅ Rutas generadas con datos específicos por origen', [
                'total_rutas' => count($rutasGeneradas),
                'usa_datos_especificos' => !empty($datosEspecificosPorRuta),
                'datos_comunes' => array_keys($datosComunes),
                'primera_ruta' => $rutasGeneradas[0] ?? null
            ]);
            
            // Guardar las rutas generadas en el grupo
            if ($groupId && !empty($rutasGeneradas)) {
                $group = GroupCotization::find($groupId);
                if ($group) {
                    $group->extracted_data = json_encode($rutasGeneradas);
                    $group->save();
                    
                    Log::info('💾 Múltiples rutas guardadas en grupo', [
                        'group_id' => $groupId,
                        'rutas_count' => count($rutasGeneradas)
                    ]);
                    
                    // 🆕 CREAR FILAS EN cotizacion_models para cada ruta
                    // Esto permite que QuoteDetailsPanel muestre las rutas correctamente
                    foreach ($rutasGeneradas as $index => $ruta) {
                        \App\Models\CotizacionModel::create([
                            'group_cotization_id' => $groupId,
                            'user_id' => $group->user_id,
                            'client_id' => $group->client_id,
                            'ciudad_origen' => $ruta['origen'] ?? $ruta['ciudad_origen'],
                            'ciudad_destino' => $ruta['destino'] ?? $ruta['ciudad_destino'],
                            'peso_mercancia' => $ruta['peso_mercancia'] ?? $ruta['pesoMercancia'] ?? 0,
                            'cantidad' => $ruta['cantidad'] ?? $ruta['cantidadMercancia'] ?? 1,
                            'tipo_embajale' => $ruta['empaque'] ?? $ruta['tipo_embalaje'] ?? 'Caja',
                            'tipo_producto' => $ruta['producto'] ?? $ruta['tipo_producto'] ?? $ruta['producto_mencionado'] ?? 'Mercancía general',
                            'vehiculo_requerido' => $ruta['vehiculo'] ?? $ruta['claseVehiculo'] ?? $ruta['vehiculo_requerido'] ?? 'Sencillo',
                            'valor_declarado' => $ruta['valor_declarado'] ?? $ruta['valorMercancia'] ?? 0,
                            'active' => true
                        ]);
                    }
                    
                    Log::info('✅ Filas en cotizacion_models creadas', [
                        'group_id' => $groupId,
                        'filas_creadas' => count($rutasGeneradas)
                    ]);
                }
                
                // Actualizar previousExtractedData para que el resto del flujo use estas rutas
                $previousExtractedData = $rutasGeneradas;
                
                // Crear mensaje de confirmación
                $confirmacionRutas = "¡Perfecto! He creado " . count($rutasGeneradas) . " ruta(s):\n\n";
                foreach ($rutasGeneradas as $i => $ruta) {
                    $confirmacionRutas .= "**Ruta " . ($i + 1) . ":** {$ruta['origen']} → {$ruta['destino']}\n";
                }
                
                // 🆕 Agregar información de datos comunes detectados
                if (!empty($datosComunes)) {
                    $confirmacionRutas .= "\n**Datos aplicados a todas las rutas:**\n";
                    if (isset($datosComunes['producto'])) {
                        $confirmacionRutas .= "- 📦 Producto: {$datosComunes['producto']}\n";
                    }
                    if (isset($datosComunes['peso_bruto'])) {
                        // Mostrar peso bruto y peso con tara
                        $confirmacionRutas .= "- ⚖️ Peso Bruto: " . number_format($datosComunes['peso_bruto'], 0, ',', '.') . " kg\n";
                        $confirmacionRutas .= "- ⚖️ Tara: " . number_format($datosComunes['tara'], 0, ',', '.') . " kg\n";
                        $confirmacionRutas .= "- ⚖️ **Peso Total (con tara):** " . number_format($datosComunes['peso_mercancia'], 0, ',', '.') . " kg\n";
                    } elseif (isset($datosComunes['peso_mercancia'])) {
                        // Solo peso sin tara calculada
                        $confirmacionRutas .= "- ⚖️ Peso: " . number_format($datosComunes['peso_mercancia'], 0, ',', '.') . " kg\n";
                    }
                    if (isset($datosComunes['cantidad'])) {
                        $confirmacionRutas .= "- 📊 Cantidad: {$datosComunes['cantidad']} unidades\n";
                    }
                    if (isset($datosComunes['vehiculo'])) {
                        $confirmacionRutas .= "- 🚛 Vehículo: {$datosComunes['vehiculo']}\n";
                    }
                    if (isset($datosComunes['empaque'])) {
                        $confirmacionRutas .= "- 📦 Empaque: {$datosComunes['empaque']}\n";
                    }
                }
                
                $confirmacionRutas .= "\n¿Necesitas agregar o modificar algo más?";
                
                ConversationMessage::create([
                    'session_id' => $session->id,
                    'group_cotization_id' => $groupId,
                    'role' => 'assistant',
                    'content' => $confirmacionRutas,
                    'timestamp' => now()
                ]);
                
                // Retornar inmediatamente con las rutas creadas
                $runId = 'run_multiple_routes_' . time();
                $metadata = json_decode($session->metadata ?? '{}', true);
                $metadata['last_run_id'] = $runId;
                $metadata['last_run_status'] = 'completed';
                $metadata['quote_data'] = $rutasGeneradas;
                $metadata['extracted_data'] = $rutasGeneradas;
                $session->metadata = json_encode($metadata);
                $session->save();
                
                return [
                    'id' => $runId,
                    'status' => 'completed_with_data',
                    'extracted_data' => $rutasGeneradas
                ];
            }
        }
        
        // Verificar si es una edición simple que podemos procesar rápidamente
        $esEdicionSimpleTemprana = false;
        $campoEditadoTemprano = null;
        $valorEditadoTemprano = null;
        $esEliminacion = false;
        
        // 🆕 PALABRAS CLAVE PARA EDICIÓN:
        // AGREGAR/CAMBIAR: ponga, pon, agrega, es, será, sea, cambia, modifica, ajusta, coloca, actualiza, son, de, va
        // QUITAR/ELIMINAR: quita, elimina, borra, remueve, deja vacío, sin, no, mejor deja
        $palabrasAgregar = 'es|son|ser[áa]?|sea|va|de|cambia(?:\s*(?:a|por))?|modifica(?:\s*(?:a|por))?|ajusta(?:\s*(?:a|por))?|ponga?|pon|agrega|coloca|actualiza(?:\s*(?:a|por))?|:|=';
        $palabrasEliminar = 'quita|elimina|borra|remueve|sin|deja\s*(?:vacío|vacio|en\s*blanco)|mejor\s*deja\s*(?:vacío|vacio|sin)';
        
        // 🆕 IMPORTANTE: Solo detectar edición simple si el mensaje es CORTO (menos de 150 caracteres)
        // Mensajes largos como correos de solicitud NO deben procesarse como edición simple
        $esEdicionSimpleHabilitada = strlen($lastUserMessageForEdit) < 150;
        
        // 🆕🆕 DETECCIÓN DE MÚLTIPLES CAMPOS: Si el mensaje contiene comas O múltiples palabras clave de campo
        $camposMultiples = [];
        $esEdicionMultiple = false;
        
        // 🔧 FIX BUG #537: Detectar múltiples campos incluso SIN comas
        // Contar cuántas palabras clave de campo aparecen en el mensaje
        $camposClave = ['origen', 'destino', 'producto', 'peso', 'cantidad', 'valor', 'empaque', 'embalaje', 'veh[ií]culo'];
        $cantidadCamposEnMensaje = 0;
        foreach ($camposClave as $clave) {
            if (preg_match('/\b' . $clave . '\b/ui', $lastUserMessageForEdit)) {
                $cantidadCamposEnMensaje++;
            }
        }
        
        // Si hay 2+ campos en el mensaje (con o sin comas), procesar como edición múltiple
        if ($esEdicionSimpleHabilitada && ($cantidadCamposEnMensaje >= 2 || strpos($lastUserMessageForEdit, ',') !== false)) {
            Log::info('🔍 Detectando MÚLTIPLES CAMPOS', [
                'tiene_comas' => strpos($lastUserMessageForEdit, ',') !== false,
                'cantidad_campos' => $cantidadCamposEnMensaje
            ]);
            
            // Detectar ORIGEN (con o sin conectores)
            // 🔧 FIX BUG #537: Detener captura al encontrar otro campo
            if (preg_match('/(?:el\s+)?origen\s+(?:' . $palabrasAgregar . ')\s+([a-záéíóúñ\s]+?)(?:\s+(?:el|la)\s+(?:producto|peso|cantidad|valor|emba|destino|veh)|[,;]|$)/ui', $lastUserMessageForEdit, $m)) {
                $camposMultiples['origen'] = self::normalizeCityName(trim($m[1]));
            } elseif (preg_match('/(?:el\s+)?orig\s+en\s+([a-záéíóúñ\s]+?)(?:\s+(?:el|la)\s+(?:producto|peso|cantidad|valor|emba|destino|veh)|[,;]|$)/ui', $lastUserMessageForEdit, $m)) {
                $camposMultiples['origen'] = self::normalizeCityName(trim($m[1]));
            } elseif (preg_match('/(?:el\s+)?origen\s+([a-záéíóúñ\s]+?)(?:\s+(?:el|la)\s+(?:producto|peso|cantidad|valor|emba|destino|veh)|[,;]|$)/ui', $lastUserMessageForEdit, $m)) {
                // Sin conector: "origen cali,"
                $camposMultiples['origen'] = self::normalizeCityName(trim($m[1]));
            }
            
            // Detectar DESTINO (con o sin conectores)
            if (preg_match('/(?:el\s+)?destino\s+(?:' . $palabrasAgregar . ')\s+([a-záéíóúñ\s]+?)(?:\s+(?:el|la)\s+(?:producto|peso|cantidad|valor|emba|origen|veh)|[,;]|$)/ui', $lastUserMessageForEdit, $m)) {
                $camposMultiples['destino'] = self::normalizeCityName(trim($m[1]));
            } elseif (preg_match('/(?:el\s+)?dest\s+en\s+([a-záéíóúñ\s]+?)(?:\s+(?:el|la)\s+(?:producto|peso|cantidad|valor|emba|origen|veh)|[,;]|$)/ui', $lastUserMessageForEdit, $m)) {
                $camposMultiples['destino'] = self::normalizeCityName(trim($m[1]));
            } elseif (preg_match('/(?:el\s+)?destino\s+([a-záéíóúñ\s]+?)(?:\s+(?:el|la)\s+(?:producto|peso|cantidad|valor|emba|origen|veh)|[,;]|$)/ui', $lastUserMessageForEdit, $m)) {
                // Sin conector: "destino medellin,"
                $camposMultiples['destino'] = self::normalizeCityName(trim($m[1]));
            }
            
            // Detectar VEHÍCULO (con o sin conectores)
            // 🔧 FIX BUG #568: Mejorar regex para no capturar frases completas con conectores
            // Capturar UNA palabra solamente, detenerse en "y" o conectores
            if (preg_match('/(?:el\s+)?veh[ií]culo\s*(?:' . $palabrasAgregar . ')\s+([a-záéíóúñ]+)(?:\s+y\s+|\s+el\s+|\s+la\s+|,|;|$)/ui', $lastUserMessageForEdit, $m)) {
                $vehiculoRaw = trim($m[1]);
                $vehiculo = strtoupper(preg_replace('/[^a-záéíóúñ]/ui', '', $vehiculoRaw));
                // Filtrar palabras de conexión
                $palabrasExcluir = ['ES', 'SON', 'SERA', 'EL', 'LA', 'DE', 'DEL', 'Y'];
                if (!in_array($vehiculo, $palabrasExcluir)) {
                    $camposMultiples['vehiculo'] = $vehiculo;
                }
            } elseif (preg_match('/(?:el\s+)?veh[ií]culo\s+([a-záéíóúñ]+)(?:\s+y\s+|\s+el\s+|\s+la\s+|,|;|$)/ui', $lastUserMessageForEdit, $m)) {
                // Sin conector: "vehiculo turbo,"
                $vehiculoRaw = trim($m[1]);
                $vehiculo = strtoupper(preg_replace('/[^a-záéíóúñ]/ui', '', $vehiculoRaw));
                $palabrasExcluir = ['ES', 'SON', 'SERA', 'EL', 'LA', 'DE', 'DEL', 'Y'];
                if (!in_array($vehiculo, $palabrasExcluir)) {
                    $camposMultiples['vehiculo'] = $vehiculo;
                }
            }
            
            // Detectar PRODUCTO (con o sin conectores)
            // 🔧 FIX BUG #537: Detener captura al encontrar otro campo
            if (preg_match('/(?:el\s+)?producto\s*(?:' . $palabrasAgregar . ')\s+([a-záéíóúñ\s]+?)(?:\s+(?:el|la)\s+(?:origen|destino|peso|cantidad|valor|emba|veh)|[,;]|$)/ui', $lastUserMessageForEdit, $m)) {
                $camposMultiples['producto'] = strtoupper(trim($m[1]));
            } elseif (preg_match('/(?:el\s+)?producto\s+([a-záéíóúñ\s]+?)(?:\s+(?:el|la)\s+(?:origen|destino|peso|cantidad|valor|emba|veh)|[,;]|$)/ui', $lastUserMessageForEdit, $m)) {
                // Sin conector: "producto tomate,"
                $camposMultiples['producto'] = strtoupper(trim($m[1]));
            }
            
            // Detectar PESO (con o sin conectores)
            // 🆕 FIX: Usar normalizeWeight para manejar formato miles español (7.600 kg = 7600 kg)
            if (preg_match('/(?:el\s+)?peso\s*(?:' . $palabrasAgregar . ')\s*([\d.,]+)\s*(?:kg|kilos?|toneladas?|ton)?/ui', $lastUserMessageForEdit, $m)) {
                $peso = self::normalizeWeight($m[1]);
                if (preg_match('/toneladas?|ton\b/ui', $lastUserMessageForEdit)) {
                    $peso = $peso * 1000;
                }
                $camposMultiples['peso_kg'] = $peso;
            } elseif (preg_match('/(?:el\s+)?peso\s+([\d.,]+)\s*(?:kg|kilos?|toneladas?|ton)?(?:\s*[,;]|$)/ui', $lastUserMessageForEdit, $m)) {
                // Sin conector: "peso 900kg,"
                $peso = self::normalizeWeight($m[1]);
                if (preg_match('/toneladas?|ton\b/ui', $lastUserMessageForEdit)) {
                    $peso = $peso * 1000;
                }
                $camposMultiples['peso_kg'] = $peso;
            }
            
            // Detectar CANTIDAD (con o sin conectores)
            if (preg_match('/(?:la\s+)?cantidad\s*(?:' . $palabrasAgregar . ')\s*(\d+)/ui', $lastUserMessageForEdit, $m)) {
                $camposMultiples['cantidad'] = intval($m[1]);
            } elseif (preg_match('/(?:la\s+)?cantidad\s+(\d+)(?:\s*[,;]|$)/ui', $lastUserMessageForEdit, $m)) {
                // Sin conector: "cantidad 4545,"
                $camposMultiples['cantidad'] = intval($m[1]);
            }
            
            // Detectar VALOR DECLARADO (con o sin conectores)
            if (preg_match('/(?:el\s+)?valor(?:\s+declarado)?\s*(?:' . $palabrasAgregar . ')\s*([\d.,]+)\s*(?:millones?)?/ui', $lastUserMessageForEdit, $m)) {
                $valor = floatval(str_replace([',', '.'], ['', ''], $m[1]));
                if (preg_match('/millones?/ui', $lastUserMessageForEdit)) {
                    $valor = $valor * 1000000;
                }
                $camposMultiples['valor_declarado'] = $valor;
            } elseif (preg_match('/(?:el\s+)?valor(?:\s+declarado)?\s+([\d.,]+)\s*(?:millones?)?(?:\s*[,;]|$)/ui', $lastUserMessageForEdit, $m)) {
                // Sin conector: "valor 5000000,"
                $valor = floatval(str_replace([',', '.'], ['', ''], $m[1]));
                if (preg_match('/millones?/ui', $lastUserMessageForEdit)) {
                    $valor = $valor * 1000000;
                }
                $camposMultiples['valor_declarado'] = $valor;
            }
            
            // Detectar EMBALAJE/EMPAQUE (con o sin conectores)
            // 🔧 FIX BUG #563: Mejorar regex para no capturar frases completas con conectores
            // Detectar: "embalaje tonel", "empaque es tonel", "embalaje: tonel"
            // NO capturar: "embalaje es tonel y vehículo es patineta"
            if (preg_match('/(?:el\s+)?(?:embalaje|empaque)\s*(?:es|son|será|:)?\s*([a-záéíóúñ]+)(?:\s+y\s+|\s+el\s+|\s+la\s+|,|;|$)/ui', $lastUserMessageForEdit, $m)) {
                $embalaje = strtoupper(trim($m[1]));
                // Filtrar palabras de conexión comunes
                $palabrasExcluir = ['ES', 'SON', 'SERA', 'EL', 'LA', 'DE', 'DEL', 'Y'];
                if (!in_array($embalaje, $palabrasExcluir)) {
                    $camposMultiples['empaque'] = $embalaje;
                }
            }
            
            // Si detectamos 2 o más campos, es edición múltiple
            if (count($camposMultiples) >= 2) {
                $esEdicionMultiple = true;
                Log::info('✅ EDICIÓN MÚLTIPLE detectada', ['campos' => array_keys($camposMultiples)]);
            }
        }
        
        // 🆕🆕 PROCESAR EDICIÓN MÚLTIPLE (antes de edición simple individual)
        if ($esEdicionMultiple && !empty($previousExtractedData)) {
            Log::info('🚀 Procesando EDICIÓN MÚLTIPLE', ['campos' => $camposMultiples]);
            
            $extractedData = $previousExtractedData;
            
            // 🚛 Detectar formato multi-ruta
            $isMultiRutaFormat = isset($extractedData['multi_ruta']) && $extractedData['multi_ruta'] === true && isset($extractedData['rutas']);
            $isMultiRouteData = $isMultiRutaFormat || (isset($extractedData[0]) && is_array($extractedData[0]));
            
            // Aplicar TODOS los cambios a la ruta seleccionada (o primera ruta si no hay selección)
            if ($isMultiRouteData) {
                $targetIndex = $selectedRouteIndex !== null ? $selectedRouteIndex : 0;
                
                // Determinar dónde está el array de rutas
                if ($isMultiRutaFormat) {
                    // Formato {multi_ruta: true, rutas: [...]}
                    $rutasArray = &$extractedData['rutas'];
                } else {
                    // Formato [ruta1, ruta2, ruta3]
                    $rutasArray = &$extractedData;
                }
                
                if (isset($rutasArray[$targetIndex])) {
                    foreach ($camposMultiples as $campo => $valor) {
                        if ($campo === 'origen') {
                            $rutasArray[$targetIndex]['origen'] = $valor;
                            $rutasArray[$targetIndex]['ciudad_origen'] = $valor;
                        } elseif ($campo === 'destino') {
                            $rutasArray[$targetIndex]['destino'] = $valor;
                            $rutasArray[$targetIndex]['ciudad_destino'] = $valor;
                        } elseif ($campo === 'vehiculo') {
                            $rutasArray[$targetIndex]['vehiculo'] = $valor;
                            $rutasArray[$targetIndex]['vehiculo_requerido'] = $valor;
                            $rutasArray[$targetIndex]['claseVehiculo'] = $valor;
                        } elseif ($campo === 'producto') {
                            $rutasArray[$targetIndex]['producto'] = $valor;
                            $rutasArray[$targetIndex]['producto_mencionado'] = $valor;
                            // Nota: NO buscamos en BD durante edición múltiple para no bloquear
                        } elseif ($campo === 'peso_kg') {
                            $rutasArray[$targetIndex]['peso_kg'] = $valor;
                            $rutasArray[$targetIndex]['peso'] = $valor; // Sincronizar alias
                        } elseif ($campo === 'cantidad') {
                            $rutasArray[$targetIndex]['cantidad'] = $valor;
                        } elseif ($campo === 'valor_declarado') {
                            $rutasArray[$targetIndex]['valor_declarado'] = $valor;
                            $rutasArray[$targetIndex]['valor'] = $valor; // Sincronizar alias
                        } elseif ($campo === 'empaque') {
                            $rutasArray[$targetIndex]['empaque'] = $valor;
                            $rutasArray[$targetIndex]['tipo_embajale'] = $valor;
                        }
                    }
                    
                    Log::info('✅ Cambios múltiples aplicados a ruta en formato multi-ruta', [
                        'ruta_index' => $targetIndex,
                        'formato' => $isMultiRutaFormat ? 'multi_ruta' : 'array_indexado',
                        'datos_actualizados' => $rutasArray[$targetIndex]
                    ]);
                }
            } else {
                // Ruta única
                foreach ($camposMultiples as $campo => $valor) {
                    if ($campo === 'origen') {
                        $extractedData['origen'] = $valor;
                        $extractedData['ciudad_origen'] = $valor;
                    } elseif ($campo === 'destino') {
                        $extractedData['destino'] = $valor;
                        $extractedData['ciudad_destino'] = $valor;
                    } elseif ($campo === 'vehiculo') {
                        $extractedData['vehiculo'] = $valor;
                        $extractedData['vehiculo_requerido'] = $valor;
                        $extractedData['claseVehiculo'] = $valor;
                    } elseif ($campo === 'producto') {
                        $extractedData['producto'] = $valor;
                        $extractedData['producto_mencionado'] = $valor;
                        // Nota: NO buscamos en BD durante edición múltiple para no bloquear
                    } elseif ($campo === 'peso_kg') {
                        $extractedData['peso_kg'] = $valor;
                    } elseif ($campo === 'cantidad') {
                        $extractedData['cantidad'] = $valor;
                    } elseif ($campo === 'valor_declarado') {
                        $extractedData['valor_declarado'] = $valor;
                    } elseif ($campo === 'empaque') {
                        $extractedData['empaque'] = $valor;
                        $extractedData['tipo_embajale'] = $valor;
                    }
                }
            }
            
            // Guardar en BD
            if ($groupId) {
                $group = GroupCotization::find($groupId);
                if ($group) {
                    $group->extracted_data = json_encode($extractedData);
                    $group->save();
                    Log::info('💾 extracted_data guardado tras edición múltiple', ['group_id' => $groupId]);
                }
            }
            
            // Generar respuesta con todos los cambios
            $rutaInfo = $isMultiRouteData && $selectedRouteIndex !== null 
                ? " en la **Ruta " . ($selectedRouteIndex + 1) . "**" 
                : "";
            
            $cambiosTxt = "¡Entendido! He actualizado los siguientes campos{$rutaInfo}:\n\n";
            foreach ($camposMultiples as $campo => $valor) {
                $nombreCampo = ucfirst(str_replace('_', ' ', $campo));
                if ($campo === 'peso_kg') {
                    $cambiosTxt .= "- **{$nombreCampo}:** " . number_format($valor, 0, ',', '.') . " kg\n";
                } elseif ($campo === 'cantidad') {
                    $cambiosTxt .= "- **{$nombreCampo}:** " . number_format($valor, 0, ',', '.') . "\n";
                } elseif ($campo === 'valor_declarado') {
                    $cambiosTxt .= "- **{$nombreCampo}:** $" . number_format($valor, 0, ',', '.') . "\n";
                } else {
                    $cambiosTxt .= "- **{$nombreCampo}:** {$valor}\n";
                }
            }
            
            ConversationMessage::create([
                'session_id' => $session->id,
                'group_cotization_id' => $groupId,
                'role' => 'assistant',
                'content' => $cambiosTxt,
                'timestamp' => now()
            ]);
            
            $runId = 'run_multi_edit_' . time();
            $metadata = json_decode($session->metadata ?? '{}', true);
            $metadata['last_run_id'] = $runId;
            $metadata['last_run_status'] = 'completed';
            $metadata['quote_data'] = $extractedData;
            $metadata['extracted_data'] = $extractedData;
            $session->metadata = json_encode($metadata);
            $session->save();
            
            return [
                'id' => $runId,
                'status' => 'completed_with_data',
                'extracted_data' => $extractedData
            ];
        }
        
        // Primero verificar si es una ELIMINACIÓN de campo (solo en mensajes cortos)
        if ($esEdicionSimpleHabilitada && preg_match('/(?:el\s+)?origen\s*(?:' . $palabrasEliminar . ')/ui', $lastUserMessageForEdit)) {
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'origen';
            $valorEditadoTemprano = null;
            $esEliminacion = true;
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:el\s+)?destino\s*(?:' . $palabrasEliminar . ')/ui', $lastUserMessageForEdit)) {
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'destino';
            $valorEditadoTemprano = null;
            $esEliminacion = true;
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:el\s+)?veh[ií]culo\s*(?:' . $palabrasEliminar . ')/ui', $lastUserMessageForEdit)) {
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'vehiculo';
            $valorEditadoTemprano = null;
            $esEliminacion = true;
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:el\s+)?peso\s*(?:' . $palabrasEliminar . ')/ui', $lastUserMessageForEdit)) {
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'peso_kg';
            $valorEditadoTemprano = null;
            $esEliminacion = true;
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:la\s+)?cantidad\s*(?:' . $palabrasEliminar . ')/ui', $lastUserMessageForEdit)) {
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'cantidad';
            $valorEditadoTemprano = null;
            $esEliminacion = true;
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:el\s+)?valor(?:\s+declarado)?\s*(?:' . $palabrasEliminar . ')/ui', $lastUserMessageForEdit)) {
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'valor_declarado';
            $valorEditadoTemprano = null;
            $esEliminacion = true;
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:el\s+)?producto\s*(?:' . $palabrasEliminar . ')/ui', $lastUserMessageForEdit)) {
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'producto';
            $valorEditadoTemprano = null;
            $esEliminacion = true;
        }
        // Patrones para AGREGAR/CAMBIAR valores (solo en mensajes cortos)
        // 🔧 FIX: Priorizar "origen" completo sobre "orig" para evitar que "origen es" se divida como "orig en es"
        // 🔧 FIX CRÍTICO: El conector debe ser NO-CAPTURADO para que "origen es barranquilla" capture SOLO "barranquilla"
        // 🔧 FIX BUG #537: Detener captura al encontrar otro campo (el producto, el peso, el empaque, etc.)
        elseif ($esEdicionSimpleHabilitada && preg_match('/(?:el\s+)?origen\s+(?:' . $palabrasAgregar . ')\s+([a-záéíóúñ\s]+?)(?:\s+(?:el|la)\s+(?:producto|peso|cantidad|valor|emba|destino)|[.,;]|$)/ui', $lastUserMessageForEdit, $matchOrigen)) {
            // 🆕 PRIORIDAD: "origen es barranquilla" → captura "barranquilla" (NO incluye "es")
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'origen';
            $valorEditadoTemprano = self::normalizeCityName(trim($matchOrigen[1]));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:el\s+)?orig\s+en\s+([a-záéíóúñ\s]+?)(?:\s+(?:el|la)\s+(?:producto|peso|cantidad|valor|emba|destino)|[.,;]|$)/ui', $lastUserMessageForEdit, $matchOrigen)) {
            // 🆕 SECUNDARIO: "orig en barranquilla" (abreviación + "en" como conector)
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'origen';
            $valorEditadoTemprano = self::normalizeCityName(trim($matchOrigen[1]));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:cambia|modifica|ajusta|actualiza)\s+(?:el\s+)?origen\s*(?:a|por)?\s+([a-záéíóúñ\s]+?)(?:\s+(?:el|la)\s+(?:producto|peso|cantidad|valor|emba|destino)|[.,;]|$)/ui', $lastUserMessageForEdit, $matchOrigen)) {
            // 🆕 "cambia origen a cali" (palabra completa)
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'origen';
            $valorEditadoTemprano = self::normalizeCityName(trim($matchOrigen[1]));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:cambia|modifica|ajusta|actualiza)\s+(?:el\s+)?orig\s*(?:a|por)?\s+([a-záéíóúñ\s]+?)(?:\s+(?:el|la)\s+(?:producto|peso|cantidad|valor|emba|destino)|[.,;]|$)/ui', $lastUserMessageForEdit, $matchOrigen)) {
            // 🆕 NUEVO: "cambia origen a cali" o "modifica el origen a medellín"
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'origen';
            $valorEditadoTemprano = self::normalizeCityName(trim($matchOrigen[1]));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:el\s+)?destino\s+(?:' . $palabrasAgregar . ')\s+([a-záéíóúñ\s]+?)(?:\s+(?:el|la)\s+(?:producto|peso|cantidad|valor|emba|origen)|[.,;]|$)/ui', $lastUserMessageForEdit, $matchDestino)) {
            // 🆕 PRIORIDAD: "destino es cali" → captura "cali" (NO incluye "es")
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'destino';
            $valorEditadoTemprano = self::normalizeCityName(trim($matchDestino[1]));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:el\s+)?dest\s+en\s+([a-záéíóúñ\s]+?)(?:\s+(?:el|la)\s+(?:producto|peso|cantidad|valor|emba|origen)|[.,;]|$)/ui', $lastUserMessageForEdit, $matchDestino)) {
            // 🆕 SECUNDARIO: "dest en barranquilla" (abreviación + "en" como conector)
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'destino';
            $valorEditadoTemprano = self::normalizeCityName(trim($matchDestino[1]));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:cambia|modifica|ajusta|actualiza)\s+(?:el\s+)?destino\s*(?:a|por)?\s+([a-záéíóúñ\s]+?)(?:\s+(?:el|la)\s+(?:producto|peso|cantidad|valor|emba|origen)|[.,;]|$)/ui', $lastUserMessageForEdit, $matchDestino)) {
            // 🆕 "cambia destino a cali" (palabra completa)
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'destino';
            $valorEditadoTemprano = self::normalizeCityName(trim($matchDestino[1]));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:cambia|modifica|ajusta|actualiza)\s+(?:el\s+)?dest\s*(?:a|por)?\s+([a-záéíóúñ\s]+?)(?:\s*[.,;]|$)/ui', $lastUserMessageForEdit, $matchDestino)) {
            // 🆕 NUEVO: "cambia destino a cali" o "modifica el destino a medellín"
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'destino';
            $valorEditadoTemprano = self::normalizeCityName(trim($matchDestino[1]));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/^(?:el\s+)?origen\s+([a-záéíóúñ\s]+)$/ui', $lastUserMessageForEdit, $matchOrigenSimple)) {
            // 🆕 "origen manizales" (palabra completa, sin conector)
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'origen';
            $valorEditadoTemprano = self::normalizeCityName(trim($matchOrigenSimple[1]));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/^(?:el\s+)?orig\s+([a-záéíóúñ\s]+)$/ui', $lastUserMessageForEdit, $matchOrigenSimple)) {
            // 🆕 "orig bogota" (abreviación, sin conector)
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'origen';
            $valorEditadoTemprano = self::normalizeCityName(trim($matchOrigenSimple[1]));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/^(?:el\s+)?destino\s+([a-záéíóúñ\s]+)$/ui', $lastUserMessageForEdit, $matchDestinoSimple)) {
            // 🆕 "destino pereira" (palabra completa, sin conector)
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'destino';
            $valorEditadoTemprano = self::normalizeCityName(trim($matchDestinoSimple[1]));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/^(?:el\s+)?dest\s+([a-záéíóúñ\s]+)$/ui', $lastUserMessageForEdit, $matchDestinoSimple)) {
            // 🆕 NUEVO: "destino manizales" o "dest pereira" (sin conector)
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'destino';
            $valorEditadoTemprano = self::normalizeCityName(trim($matchDestinoSimple[1]));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:el\s+)?veh[ií]culo\s*(?:' . $palabrasAgregar . ')\s+([a-záéíóúñ\s]+?)(?:\s*[.,;]|$)/ui', $lastUserMessageForEdit, $matchVehiculo)) {
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'vehiculo';
            // FIX: Usar vehículo EXACTO que dice el usuario (no mapear)
            $vehiculoRaw = trim($matchVehiculo[1]);
            $valorEditadoTemprano = strtoupper(preg_replace('/[^a-záéíóúñ\s]/ui', '', $vehiculoRaw));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:cambia|modifica|ajusta|actualiza)\s+(?:el\s+)?veh[ií]culo\s*(?:a|por)?\s+([a-záéíóúñ\s]+?)(?:\s*[.,;]|$)/ui', $lastUserMessageForEdit, $matchVehiculo)) {
            // 🆕 NUEVO: "cambia vehículo a tractomula" o "modifica el vehículo a dobletroque"
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'vehiculo';
            // FIX: Usar vehículo EXACTO que dice el usuario (no mapear)
            $vehiculoRaw = trim($matchVehiculo[1]);
            $valorEditadoTemprano = strtoupper(preg_replace('/[^a-záéíóúñ\s]/ui', '', $vehiculoRaw));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/^(?:el\s+)?veh[ií]culo\s+([a-záéíóúñ\s]+)$/ui', $lastUserMessageForEdit, $matchVehiculoSimple)) {
            // 🆕 NUEVO: "vehículo turbo" o "el vehículo tractomula" (sin conector)
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'vehiculo';
            $vehiculoRaw = trim($matchVehiculoSimple[1]);
            $valorEditadoTemprano = strtoupper(preg_replace('/[^a-záéíóúñ\s]/ui', '', $vehiculoRaw));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:el\s+)?peso\s*(?:' . $palabrasAgregar . ')\s*([\d.,]+)\s*(?:kg|kilos?|toneladas?|ton)?/ui', $lastUserMessageForEdit, $matchPeso)) {
            // 🆕 MEJORADO: Permite "peso es 900kg" sin espacio
            // 🆕 FIX: Usar normalizeWeight para formato miles español (7.600 kg = 7600 kg)
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'peso_kg';
            $peso = self::normalizeWeight($matchPeso[1]);
            if (preg_match('/toneladas?|ton\b/ui', $lastUserMessageForEdit)) {
                $peso = $peso * 1000;
            }
            // 🔧 FIX: Detectar si el usuario quiere sumar tara junto con el peso
            // Patrones: "peso 15000 suma tara", "peso 15000kg agrega tara", "peso 15000 ponga tara"
            if (preg_match('/(?:suma|agrega|añade|pon(?:ga|er)?|incluye|agregar|sumar)\s+(?:la\s+)?tara/ui', $lastUserMessageForEdit)) {
                $taraAplicar = self::getTaraByContenedor($lastUserMessageForEdit);
                $peso = $peso + $taraAplicar;
                Log::info('⚖️ Tara sumada al editar peso', ['peso_sin_tara' => $peso - $taraAplicar, 'tara' => $taraAplicar, 'peso_con_tara' => $peso]);
            }
            $valorEditadoTemprano = $peso;
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:cambia|modifica|ajusta|actualiza)\s+(?:el\s+)?peso\s*(?:a|por)?\s*([\d.,]+)\s*(?:kg|kilos?|toneladas?|ton)?/ui', $lastUserMessageForEdit, $matchPeso)) {
            // 🆕 NUEVO: "cambia peso a 900" o "modifica el peso a 5000kg"
            // 🆕 FIX: Usar normalizeWeight para formato miles español (7.600 kg = 7600 kg)
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'peso_kg';
            $peso = self::normalizeWeight($matchPeso[1]);
            if (preg_match('/toneladas?|ton\b/ui', $lastUserMessageForEdit)) {
                $peso = $peso * 1000;
            }
            // 🔧 FIX: Detectar si el usuario quiere sumar tara junto con el peso
            if (preg_match('/(?:suma|agrega|añade|pon(?:ga|er)?|incluye|agregar|sumar)\s+(?:la\s+)?tara/ui', $lastUserMessageForEdit)) {
                $taraAplicar = self::getTaraByContenedor($lastUserMessageForEdit);
                $peso = $peso + $taraAplicar;
                Log::info('⚖️ Tara sumada al editar peso (cambia/modifica)', ['peso_sin_tara' => $peso - $taraAplicar, 'tara' => $taraAplicar, 'peso_con_tara' => $peso]);
            }
            $valorEditadoTemprano = $peso;
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:la\s+)?cantidad\s*(?:' . $palabrasAgregar . ')\s*(\d+)/ui', $lastUserMessageForEdit, $matchCantidad)) {
            // 🆕 MEJORADO: Permite "cantidad es 900" sin espacio
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'cantidad';
            $valorEditadoTemprano = intval($matchCantidad[1]);
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:cambia|modifica|ajusta|actualiza)\s+(?:la\s+)?cantidad\s*(?:a|por)?\s*(\d+)/ui', $lastUserMessageForEdit, $matchCantidad)) {
            // 🆕 NUEVO: "cambia cantidad a 50" o "modifica la cantidad a 100"
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'cantidad';
            $valorEditadoTemprano = intval($matchCantidad[1]);
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:el\s+)?valor(?:\s+declarado)?\s*(?:' . $palabrasAgregar . ')\s*([\d.,]+)\s*(?:millones?)?/ui', $lastUserMessageForEdit, $matchValor)) {
            // 🆕 MEJORADO: Permite "valor es 80000000" sin espacio
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'valor_declarado';
            $valor = floatval(str_replace([',', '.'], ['', ''], $matchValor[1]));
            if (preg_match('/millones?/ui', $lastUserMessageForEdit)) {
                $valor = $valor * 1000000;
            }
            $valorEditadoTemprano = $valor;
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:cambia|modifica|ajusta|actualiza)\s+(?:el\s+)?valor(?:\s+declarado)?\s*(?:a|por)?\s*([\d.,]+)\s*(?:millones?)?/ui', $lastUserMessageForEdit, $matchValor)) {
            // 🆕 NUEVO: "cambia valor a 50000000" o "modifica el valor declarado a 80 millones"
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'valor_declarado';
            $valor = floatval(str_replace([',', '.'], ['', ''], $matchValor[1]));
            if (preg_match('/millones?/ui', $lastUserMessageForEdit)) {
                $valor = $valor * 1000000;
            }
            $valorEditadoTemprano = $valor;
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:el\s+)?producto\s*(?:' . $palabrasAgregar . ')\s+([a-záéíóúñ\s]+?)(?:\s*[.,;]|$)/ui', $lastUserMessageForEdit, $matchProducto)) {
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'producto';
            $valorEditadoTemprano = strtoupper(trim($matchProducto[1]));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:cambia|modifica|ajusta|actualiza)\s+(?:el\s+)?producto\s*(?:a|por)?\s+([a-záéíóúñ\s]+?)(?:\s*[.,;]|$)/ui', $lastUserMessageForEdit, $matchProducto)) {
            // 🆕 NUEVO: "cambia producto a maíz" o "modifica el producto a neumáticos"
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'producto';
            $valorEditadoTemprano = strtoupper(trim($matchProducto[1]));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/^(?:el\s+)?producto\s+(?:cambia\s+(?:a|para|deja)\s+)?([a-záéíóúñ\s]+?)(?:\s*[.,;]|$)/ui', $lastUserMessageForEdit, $matchProductoSimple)) {
            // 🆕 NUEVO: "producto tomate", "producto es tomate", "producto cambia a tomate"
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'producto';
            $valorEditadoTemprano = strtoupper(trim($matchProductoSimple[1]));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:el\s+)?peso\s+([\d.,]+)\s*(?:kg|kilos?)?\s+(?:suma|agrega|añade|pon(?:ga|er)?|incluye|agregar|sumar)\s+(?:la\s+)?tara/ui', $lastUserMessageForEdit, $matchPesoTara)) {
            // 🔧 FIX: Nuevo patrón para "peso 15000 suma tara" o "peso 30000kg agrega tara"
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'peso_kg';
            $peso = self::normalizeWeight($matchPesoTara[1]);
            $taraAplicar = self::getTaraByContenedor($lastUserMessageForEdit);
            $peso = $peso + $taraAplicar; // Sumar tara
            $valorEditadoTemprano = $peso;
            Log::info('⚖️ Peso con tara detectado (patrón directo)', ['peso_sin_tara' => $peso - $taraAplicar, 'tara' => $taraAplicar, 'peso_con_tara' => $peso]);
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:agrega|pon|sumar?|inclu(?:ye|ir))\s+(?:la\s+)?tara/ui', $lastUserMessageForEdit)) {
            // 🆕 NUEVO: "Agrega Tara" detectado tempranamente
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'tara';
            $valorEditadoTemprano = true; // Flag indicador
        }
        
        // 🔥 IMPORTANTE: Si ya se detectó edición múltiple, NO ejecutar edición simple temprana
        // Esto evita que "producto tomate, cantidad 4545, vehiculo turbo" solo procese el producto
        if ($esEdicionMultiple) {
            $esEdicionSimpleTemprana = false;
            Log::info('🚫 Edición simple temprana DESHABILITADA porque se detectó edición múltiple', [
                'campos_multiples' => array_keys($camposMultiples)
            ]);
        }
        
        // Si es edición simple temprana (agregar/cambiar O eliminar), aplicar directamente y retornar
        if ($esEdicionSimpleTemprana && ($valorEditadoTemprano !== null || $esEliminacion) && !empty($previousExtractedData)) {
            Log::info('🚀 EDICIÓN SIMPLE TEMPRANA detectada', [
                'campo' => $campoEditadoTemprano,
                'valor' => $valorEditadoTemprano,
                'es_eliminacion' => $esEliminacion
            ]);
            
            // 🆕 LIMPIAR productos pendientes de la sesión cuando se hace edición simple
            $metadata = json_decode($session->metadata ?? '{}', true);
            if (isset($metadata['productos_pendientes'])) {
                unset($metadata['productos_pendientes']);
                unset($metadata['producto_search_term']);
                $session->metadata = json_encode($metadata);
                $session->save();
                Log::info('🧹 Productos pendientes limpiados durante edición simple');
            }
            
            // 🆕 CASO ESPECIAL: Si es producto, buscar en la BD primero
            if ($campoEditadoTemprano === 'producto' && !$esEliminacion) {
                $searchTerm = $valorEditadoTemprano;
                
                Log::info('🔍 Buscando producto en BD (edición temprana)', ['search_term' => $searchTerm]);
                
                // Buscar en la tabla products
                // 🔧 FIX: Usar nombres correctos de columnas (producto_codigo, producto_nombre, etc)
                $productos = \DB::table('products')
                    ->where('producto_nombre', 'LIKE', "%{$searchTerm}%")
                    ->limit(5)
                    ->get([
                        'producto_codigo as codigo', 
                        'producto_nombre as nombre', 
                        'producto_codigo_ministerio as codigo_ministerio', 
                        'tippro_nombre as tipo_producto', 
                        'natcar_nombre as naturaleza_carga'
                    ])
                    ->toArray();
                
                if (count($productos) === 0) {
                    // No encontrado - guardar como personalizado
                    Log::info('🔍 Producto no encontrado en BD, guardando como PERSONALIZADO');
                    $valorEditadoTemprano = strtoupper($searchTerm);
                } elseif (count($productos) === 1) {
                    // Único resultado - usar directamente
                    $prod = (array)$productos[0];
                    $valorEditadoTemprano = $prod['nombre'];
                    
                    // Actualizar datos con info de BD
                    $extractedData = $previousExtractedData;
                    $isMultiRouteData = isset($extractedData[0]) && is_array($extractedData[0]);
                    
                    if ($isMultiRouteData) {
                        // 🔧 FIX: Si hay ruta seleccionada, aplicar SOLO a esa ruta
                        if ($selectedRouteIndex !== null && isset($extractedData[$selectedRouteIndex])) {
                            $extractedData[$selectedRouteIndex]['producto'] = $prod['nombre'];
                            $extractedData[$selectedRouteIndex]['producto_codigo'] = $prod['codigo'];
                            $extractedData[$selectedRouteIndex]['tipo_producto'] = $prod['nombre'];
                            $extractedData[$selectedRouteIndex]['producto_nombre'] = $prod['nombre'];
                            
                            Log::info('🎯 Producto aplicado SOLO a ruta seleccionada (único resultado)', [
                                'ruta_index' => $selectedRouteIndex,
                                'producto' => $prod['nombre']
                            ]);
                        } else {
                            // Si no hay ruta seleccionada, aplicar solo a primera ruta sin producto
                            $rutaSinProducto = null;
                            foreach ($extractedData as $idx => $ruta) {
                                if (is_numeric($idx) && is_array($ruta)) {
                                    $tieneProducto = !empty($ruta['producto']) && 
                                                     strtoupper($ruta['producto']) !== 'PERSONALIZADO' &&
                                                     !empty($ruta['producto_codigo']);
                                    if (!$tieneProducto) {
                                        $rutaSinProducto = $idx;
                                        break;
                                    }
                                }
                            }
                            
                            if ($rutaSinProducto !== null) {
                                $extractedData[$rutaSinProducto]['producto'] = $prod['nombre'];
                                $extractedData[$rutaSinProducto]['producto_codigo'] = $prod['codigo'];
                                $extractedData[$rutaSinProducto]['tipo_producto'] = $prod['nombre'];
                                $extractedData[$rutaSinProducto]['producto_nombre'] = $prod['nombre'];
                                
                                Log::info('🎯 Producto aplicado a primera ruta sin producto', [
                                    'ruta_index' => $rutaSinProducto,
                                    'producto' => $prod['nombre']
                                ]);
                            }
                        }
                    } else {
                        $extractedData['producto'] = $prod['nombre'];
                        $extractedData['producto_codigo'] = $prod['codigo'];
                        $extractedData['tipo_producto'] = $prod['nombre'];
                        $extractedData['producto_nombre'] = $prod['nombre'];
                    }
                    
                    // Guardar en grupo
                    if ($groupId) {
                        $group = GroupCotization::find($groupId);
                        if ($group) {
                            Log::info('💾 GUARDANDO extracted_data tras producto único', [
                                'group_id' => $groupId,
                                'antes' => json_decode($group->extracted_data ?? '{}', true),
                                'despues' => $extractedData,
                                'es_multi_ruta' => $isMultiRouteData,
                                'selected_route_index' => $selectedRouteIndex
                            ]);
                            
                            $group->extracted_data = json_encode($extractedData);
                            $group->save();
                            
                            Log::info('✅ extracted_data GUARDADO en BD', [
                                'group_id' => $groupId,
                                'datos_guardados' => $extractedData
                            ]);
                        }
                    }
                    
                    // 🔧 FIX: Incluir información de ruta en el mensaje
                    $rutaInfo = '';
                    if ($isMultiRouteData && $selectedRouteIndex !== null) {
                        $rutaInfo = " en la **Ruta " . ($selectedRouteIndex + 1) . "**";
                    }
                    $respuesta = "¡Perfecto! He seleccionado el producto **{$prod['nombre']}** (Código: {$prod['codigo']}){$rutaInfo}.";
                    
                    ConversationMessage::create([
                        'session_id' => $session->id,
                        'group_cotization_id' => $groupId,
                        'role' => 'assistant',
                        'content' => $respuesta,
                        'timestamp' => now()
                    ]);
                    
                    $runId = 'run_product_edit_' . time();
                    $metadata = json_decode($session->metadata ?? '{}', true);
                    $metadata['last_run_id'] = $runId;
                    $metadata['last_run_status'] = 'completed';
                    $metadata['quote_data'] = $extractedData;
                    $metadata['extracted_data'] = $extractedData;
                    $session->metadata = json_encode($metadata);
                    $session->save();
                    
                    return [
                        'id' => $runId,
                        'status' => 'completed_with_data',
                        'extracted_data' => $extractedData
                    ];
                    
                } else {
                    // Múltiples resultados - mostrar opciones
                    Log::info('🔍 Múltiples productos encontrados, mostrando opciones', ['count' => count($productos)]);
                    
                    // 🔧 FIX: Agregar número de ruta en multi-ruta
                    $isMultiRouteData = isset($extractedData[0]) && is_array($extractedData[0]);
                    $rutaInfo = '';
                    if ($isMultiRouteData && $selectedRouteIndex !== null) {
                        $rutaNum = $selectedRouteIndex + 1;
                        $rutaInfo = " para la **Ruta {$rutaNum}**";
                    }
                    
                    $opcionesTxt = "He encontrado varios productos relacionados con \"{$searchTerm}\"{$rutaInfo}. A continuación, te presento las opciones disponibles:\n\n";
                    $productosArray = [];
                    foreach ($productos as $idx => $prod) {
                        $p = (array)$prod;
                        $opcionesTxt .= ($idx + 1) . ". **{$p['nombre']}**\n";
                        $opcionesTxt .= "   - Código: {$p['codigo']}\n";
                        if (!empty($p['codigo_ministerio'])) {
                            $opcionesTxt .= "   - Código Ministerio: {$p['codigo_ministerio']}\n";
                        }
                        if (!empty($p['tipo_producto'])) {
                            $opcionesTxt .= "   - Tipo Producto: {$p['tipo_producto']}\n";
                        }
                        if (!empty($p['naturaleza_carga'])) {
                            $opcionesTxt .= "   - Naturaleza de Carga: {$p['naturaleza_carga']}\n";
                        }
                        $opcionesTxt .= "\n";
                        $productosArray[] = $p;
                    }
                    $opcionesTxt .= "Por favor, indica cuál opción deseas (ejemplo: 'opción 1' o el nombre del producto).";
                    
                    // Guardar productos pendientes en metadata
                    // 🔧 FIX: SIEMPRE guardar el índice de ruta junto con los productos pendientes
                    $metadata = json_decode($session->metadata ?? '{}', true);
                    $metadata['productos_pendientes'] = $productosArray;
                    $metadata['producto_search_term'] = $searchTerm;
                    $metadata['producto_pendiente_ruta_index'] = $selectedRouteIndex; // 🆕 CRÍTICO: Guardar ruta activa
                    $session->metadata = json_encode($metadata);
                    $session->save();
                    
                    Log::info('🎯🎯 PRODUCTOS PENDIENTES GUARDADOS - RUTA BLOQUEADA', [
                        'ruta_index' => $selectedRouteIndex,
                        'search_term' => $searchTerm,
                        'opciones' => count($productosArray),
                        '⚠️ CRÍTICO' => 'Esta ruta (' . $selectedRouteIndex . ') está BLOQUEADA para selección de producto'
                    ]);
                    
                    ConversationMessage::create([
                        'session_id' => $session->id,
                        'group_cotization_id' => $groupId,
                        'role' => 'assistant',
                        'content' => $opcionesTxt,
                        'timestamp' => now()
                    ]);
                    
                    $runId = 'run_product_options_' . time();
                    $metadata['last_run_id'] = $runId;
                    $metadata['last_run_status'] = 'completed';
                    $session->metadata = json_encode($metadata);
                    $session->save();

                    // 🆕 FIX: Retornar los datos CON el término de búsqueda aplicado
                    // Esto permite que el Frontend mantenga "TOMATE" y muestre las opciones,
                    // en lugar de revertir al producto anterior.
                    $extractedData = $previousExtractedData;
                    // Aplicar término de búsqueda temporamente para que el frontend lo refleje
                    if ($isMultiRouteData && $selectedRouteIndex !== null && isset($extractedData[$selectedRouteIndex])) {
                        $extractedData[$selectedRouteIndex]['producto'] = $searchTerm;
                        $extractedData[$selectedRouteIndex]['producto_nombre'] = $searchTerm;
                    } elseif (!$isMultiRouteData) {
                        $extractedData['producto'] = $searchTerm;
                        $extractedData['producto_nombre'] = $searchTerm;
                    }
                    
                    return [
                        'id' => $runId,
                        'status' => 'completed_with_data',
                        'extracted_data' => $extractedData, // Enviamos datos actualizados con el término de búsqueda
                        'productos_pendientes' => $productosArray
                    ];
                }
            }
            
            $extractedData = $previousExtractedData;
            
            // 🔧 DETECCIÓN CORRECTA: Soportar AMBOS formatos de multi-ruta
            // Formato 1: Array indexado [0, 1, 2]
            // Formato 2: Objeto {multi_ruta: true, rutas: [...]}
            $isMultiRouteData = isset($extractedData[0]) && is_array($extractedData[0]);
            $isMultiRutaFormat = isset($extractedData['multi_ruta']) && $extractedData['multi_ruta'] === true && isset($extractedData['rutas']);
            
            if ($isMultiRouteData) {
                // � Asegurar que todas las rutas tengan ID único
                foreach ($extractedData as $idx => $ruta) {
                    if (is_array($ruta) && is_numeric($idx) && !isset($ruta['ruta_id'])) {
                        $extractedData[$idx]['ruta_id'] = 'ruta_' . uniqid() . '_' . ($idx + 1);
                        Log::info('🆔 ID generado para ruta indexada', [
                            'ruta_id' => $extractedData[$idx]['ruta_id'],
                            'index' => $idx
                        ]);
                    }
                }
                
                // 🆕 EDICIÓN DE RUTA ESPECÍFICA: Si hay una ruta seleccionada, editar SOLO esa
                if ($selectedRouteIndex !== null && isset($extractedData[$selectedRouteIndex])) {
                    // Editar solo la ruta seleccionada
                    Log::info('✏️ Editando SOLO ruta seleccionada (edición temprana)', [
                        'ruta_index' => $selectedRouteIndex,
                        'ruta_id' => $extractedData[$selectedRouteIndex]['ruta_id'] ?? 'sin_id',
                        'campo' => $campoEditadoTemprano,
                        'valor' => $esEliminacion ? '(eliminar)' : $valorEditadoTemprano
                    ]);
                    
                    // 🆕 MAPEAR CAMPOS DUPLICADOS para consistencia
                    $camposAActualizar = [$campoEditadoTemprano];
                    if ($campoEditadoTemprano === 'origen') {
                        $camposAActualizar = ['origen', 'ciudad_origen'];
                    } elseif ($campoEditadoTemprano === 'destino') {
                        $camposAActualizar = ['destino', 'ciudad_destino'];
                    } elseif ($campoEditadoTemprano === 'producto') {
                        $camposAActualizar = ['producto', 'tipo_producto'];
                    } elseif ($campoEditadoTemprano === 'vehiculo') {
                        $camposAActualizar = ['vehiculo', 'claseVehiculo', 'vehiculo_requerido'];
                    } elseif ($campoEditadoTemprano === 'peso' || $campoEditadoTemprano === 'peso_kg') {
                        $camposAActualizar = ['peso_kg', 'peso_mercancia', 'pesoMercancia'];
                    }
                    
                    if ($campoEditadoTemprano === 'tara') {
                        // Lógica especial para Tara
                        // 🆕 COMANDO EXPLÍCITO: Si dice "agrega tara" o "suma tara", SIEMPRE sumar
                        $comandoExplicitoSumar = preg_match('/(?:agrega|añade|suma|pon|coloca)\s+(?:la\s+)?tara/ui', $lastUserMessageForEdit);
                        $yaConTara = !empty($extractedData[$selectedRouteIndex]['incluye_tara']);
                        
                        if ($comandoExplicitoSumar || !$yaConTara) {
                            $peso = floatval(str_replace(',', '', (string)($extractedData[$selectedRouteIndex]['peso_kg'] ?? 0)));
                            // 🔧 FIX: Usar tara según tipo de contenedor (20 pies = 2300, otros = 3400)
                            $textoContenedor = $extractedData[$selectedRouteIndex]['empaque'] ?? $extractedData[$selectedRouteIndex]['contenedor'] ?? $lastUserMessageForEdit;
                            $taraAplicar = self::getTaraByContenedor($textoContenedor);
                            $extractedData[$selectedRouteIndex]['peso_kg'] = $peso + $taraAplicar;
                            $extractedData[$selectedRouteIndex]['incluye_tara'] = true;
                            $extractedData[$selectedRouteIndex]['tara'] = $taraAplicar;
                            $valorEditadoTemprano = $comandoExplicitoSumar ? "Sumado (+{$taraAplicar}kg)" : "Sí (+{$taraAplicar}kg)";
                            Log::info('✅ Tara agregada', ['ruta' => $selectedRouteIndex, 'peso_anterior' => $peso, 'tara' => $taraAplicar, 'peso_nuevo' => $peso + $taraAplicar]);
                        } else {
                            $valorEditadoTemprano = "Ya incluido";
                        }
                    } else {
                        foreach ($camposAActualizar as $campo) {
                            if ($esEliminacion) {
                                unset($extractedData[$selectedRouteIndex][$campo]);
                            } else {
                                $extractedData[$selectedRouteIndex][$campo] = $valorEditadoTemprano;
                            }
                        }
                        
                        // 🔧 FIX BUG #1: Sincronizar peso_kg con peso_mercancia para frontend
                        if ($campoEditadoTemprano === 'peso' || $campoEditadoTemprano === 'peso_kg') {
                            $extractedData[$selectedRouteIndex]['peso_kg'] = $valorEditadoTemprano;
                            $extractedData[$selectedRouteIndex]['peso_mercancia'] = $valorEditadoTemprano;
                            $extractedData[$selectedRouteIndex]['pesoMercancia'] = $valorEditadoTemprano;
                        }
                    }
                } else {
                    // Sin ruta seleccionada - aplicar a todas las rutas
                    Log::info('⚠️ Sin ruta seleccionada - aplicando a TODAS las rutas');
                    
                    // 🆕 MAPEAR CAMPOS DUPLICADOS
                    $camposAActualizar = [$campoEditadoTemprano];
                    if ($campoEditadoTemprano === 'origen') {
                        $camposAActualizar = ['origen', 'ciudad_origen'];
                    } elseif ($campoEditadoTemprano === 'destino') {
                        $camposAActualizar = ['destino', 'ciudad_destino'];
                    } elseif ($campoEditadoTemprano === 'producto') {
                        $camposAActualizar = ['producto', 'tipo_producto'];
                    } elseif ($campoEditadoTemprano === 'vehiculo') {
                        $camposAActualizar = ['vehiculo', 'claseVehiculo', 'vehiculo_requerido'];
                    } elseif ($campoEditadoTemprano === 'peso' || $campoEditadoTemprano === 'peso_kg') {
                        $camposAActualizar = ['peso_kg', 'peso_mercancia', 'pesoMercancia'];
                    }
                    
                    if ($campoEditadoTemprano === 'tara') {
                         // 🆕 COMANDO EXPLÍCITO: Si dice "agrega tara" o "suma tara", SIEMPRE sumar
                         $comandoExplicitoSumar = preg_match('/(?:agrega|añade|suma|pon|coloca)\s+(?:la\s+)?tara/ui', $lastUserMessageForEdit);
                         $tarasAplicadas = [];
                         
                         foreach ($extractedData as $idx => &$ruta) {
                            if (is_array($ruta) && is_numeric($idx)) {
                                $yaConTara = !empty($ruta['incluye_tara']);
                                if ($comandoExplicitoSumar || !$yaConTara) {
                                    $peso = floatval(str_replace(',', '', (string)($ruta['peso_kg'] ?? 0)));
                                    // 🔧 FIX: Usar tara según tipo de contenedor (20 pies = 2300, otros = 3400)
                                    $textoContenedor = $ruta['empaque'] ?? $ruta['contenedor'] ?? $lastUserMessageForEdit;
                                    $taraAplicar = self::getTaraByContenedor($textoContenedor);
                                    $ruta['peso_kg'] = $peso + $taraAplicar;
                                    $ruta['incluye_tara'] = true;
                                    $ruta['tara'] = $taraAplicar;
                                    $tarasAplicadas[] = $taraAplicar;
                                    Log::info('✅ Tara agregada a ruta', ['ruta' => $idx, 'peso_anterior' => $peso, 'tara' => $taraAplicar, 'peso_nuevo' => $peso + $taraAplicar]);
                                }
                            }
                         }
                         unset($ruta);
                         // 🔧 FIX: Fallback usa getTaraByContenedor en vez de hardcoded 3400
                         $taraPromedio = count($tarasAplicadas) > 0 ? round(array_sum($tarasAplicadas) / count($tarasAplicadas)) : self::getTaraByContenedor($lastUserMessageForEdit);
                         $valorEditadoTemprano = $comandoExplicitoSumar ? "Sumado (tara aplicada)" : "Sí (tara aplicada)";
                    } else {
                        // 🔧 FIX: Mapear campos duplicados también en múltiples rutas
                        $camposAActualizarMulti = $camposAActualizar;
                        if ($campoEditadoTemprano === 'origen') {
                            $camposAActualizarMulti = ['origen', 'ciudad_origen'];
                        } elseif ($campoEditadoTemprano === 'destino') {
                            $camposAActualizarMulti = ['destino', 'ciudad_destino'];
                        } elseif ($campoEditadoTemprano === 'producto') {
                            $camposAActualizarMulti = ['producto', 'tipo_producto'];
                        } elseif ($campoEditadoTemprano === 'vehiculo') {
                            $camposAActualizarMulti = ['vehiculo', 'claseVehiculo', 'vehiculo_requerido'];
                        }
                        
                        foreach ($extractedData as $idx => &$ruta) {
                            if (is_array($ruta) && is_numeric($idx)) {
                                foreach ($camposAActualizarMulti as $campo) {
                                    if ($esEliminacion) {
                                        unset($ruta[$campo]);
                                    } else {
                                        $ruta[$campo] = $valorEditadoTemprano;
                                    }
                                }
                                
                                // 🔧 FIX BUG #1: Sincronizar peso_kg con peso_mercancia
                                if ($campoEditadoTemprano === 'peso' || $campoEditadoTemprano === 'peso_kg') {
                                    $ruta['peso_kg'] = $valorEditadoTemprano;
                                    $ruta['peso_mercancia'] = $valorEditadoTemprano;
                                    $ruta['pesoMercancia'] = $valorEditadoTemprano;
                                }
                            }
                        }
                        unset($ruta);
                    }

                }
            } elseif ($isMultiRutaFormat) {
                // 🚛 FORMATO {multi_ruta: true, rutas: [...]} - Editar dentro del array 'rutas'
                Log::info('🔍 Detectado formato multi_ruta con array rutas', [
                    'total_rutas' => count($extractedData['rutas']),
                    'selected_route_index' => $selectedRouteIndex,
                    'campo' => $campoEditadoTemprano
                ]);
                
                // 🔴 CRÍTICO: Si no hay ruta seleccionada, usar la primera por defecto
                if ($selectedRouteIndex === null) {
                    Log::warning('⚠️ Edición multi-ruta SIN ruta seleccionada', [
                        'campo' => $campoEditadoTemprano,
                        'valor' => $valorEditadoTemprano,
                        'total_rutas' => count($extractedData['rutas'])
                    ]);
                    $selectedRouteIndex = 0;
                    Log::info('📍 Usando ruta 0 por defecto');
                }
                
                // 🆕 MAPEAR CAMPOS DUPLICADOS
                $camposAActualizar = [$campoEditadoTemprano];
                if ($campoEditadoTemprano === 'origen') {
                    $camposAActualizar = ['origen', 'ciudad_origen'];
                } elseif ($campoEditadoTemprano === 'destino') {
                    $camposAActualizar = ['destino', 'ciudad_destino'];
                } elseif ($campoEditadoTemprano === 'producto') {
                    $camposAActualizar = ['producto', 'tipo_producto'];
                } elseif ($campoEditadoTemprano === 'vehiculo') {
                    $camposAActualizar = ['vehiculo', 'claseVehiculo', 'vehiculo_requerido'];
                } elseif ($campoEditadoTemprano === 'peso' || $campoEditadoTemprano === 'peso_kg') {
                    $camposAActualizar = ['peso_kg', 'peso_mercancia', 'pesoMercancia'];
                }
                
                // Editar SOLO dentro de rutas[$selectedRouteIndex], NUNCA a nivel global
                if (isset($extractedData['rutas'][$selectedRouteIndex])) {
                    if ($campoEditadoTemprano === 'tara') {
                        // Lógica especial para tara
                        $comandoExplicitoSumar = preg_match('/(?:agrega|añade|suma|pon|coloca)\s+(?:la\s+)?tara/ui', $lastUserMessageForEdit);
                        $yaConTara = !empty($extractedData['rutas'][$selectedRouteIndex]['incluye_tara']);
                        
                        if ($comandoExplicitoSumar || !$yaConTara) {
                            $peso = floatval(str_replace(',', '', (string)($extractedData['rutas'][$selectedRouteIndex]['peso_kg'] ?? 0)));
                            // 🔧 FIX: Usar tara según tipo de contenedor (20 pies = 2300, otros = 3400)
                            $textoContenedor = $extractedData['rutas'][$selectedRouteIndex]['empaque'] ?? $extractedData['rutas'][$selectedRouteIndex]['contenedor'] ?? $lastUserMessageForEdit;
                            $taraAplicar = self::getTaraByContenedor($textoContenedor);
                            $extractedData['rutas'][$selectedRouteIndex]['peso_kg'] = $peso + $taraAplicar;
                            $extractedData['rutas'][$selectedRouteIndex]['incluye_tara'] = true;
                            $extractedData['rutas'][$selectedRouteIndex]['tara'] = $taraAplicar;
                            $valorEditadoTemprano = $comandoExplicitoSumar ? "Sumado (+{$taraAplicar}kg)" : "Sí (+{$taraAplicar}kg)";
                            Log::info('✅ Tara agregada a ruta', [
                                'ruta' => $selectedRouteIndex,
                                'peso_anterior' => $peso,
                                'tara' => $taraAplicar,
                                'peso_nuevo' => $peso + $taraAplicar
                            ]);
                        } else {
                            $valorEditadoTemprano = "Ya incluido";
                        }
                    } else {
                        // Edición normal de campo
                        foreach ($camposAActualizar as $campo) {
                            if ($esEliminacion) {
                                unset($extractedData['rutas'][$selectedRouteIndex][$campo]);
                            } else {
                                $extractedData['rutas'][$selectedRouteIndex][$campo] = $valorEditadoTemprano;
                            }
                        }
                        
                        // Sincronizar peso_kg con peso_mercancia
                        if ($campoEditadoTemprano === 'peso' || $campoEditadoTemprano === 'peso_kg') {
                            $extractedData['rutas'][$selectedRouteIndex]['peso_kg'] = $valorEditadoTemprano;
                            $extractedData['rutas'][$selectedRouteIndex]['peso_mercancia'] = $valorEditadoTemprano;
                            $extractedData['rutas'][$selectedRouteIndex]['pesoMercancia'] = $valorEditadoTemprano;
                        }
                    }
                    
                    Log::info('✏️ Editada ruta individual en formato multi_ruta', [
                        'ruta_index' => $selectedRouteIndex,
                        'campo' => $campoEditadoTemprano,
                        'valor' => $valorEditadoTemprano,
                        'es_eliminacion' => $esEliminacion
                    ]);
                } else {
                    Log::error('❌ Ruta no encontrada en formato multi_ruta', [
                        'selected_index' => $selectedRouteIndex,
                        'total_rutas' => count($extractedData['rutas'])
                    ]);
                }
            } else {
                // 🆕 Ruta única - también mapear campos duplicados
                $camposAActualizar = [$campoEditadoTemprano];
                if ($campoEditadoTemprano === 'origen') {
                    $camposAActualizar = ['origen', 'ciudad_origen'];
                } elseif ($campoEditadoTemprano === 'destino') {
                    $camposAActualizar = ['destino', 'ciudad_destino'];
                } elseif ($campoEditadoTemprano === 'producto') {
                    $camposAActualizar = ['producto', 'tipo_producto'];
                } elseif ($campoEditadoTemprano === 'vehiculo') {
                    $camposAActualizar = ['vehiculo', 'claseVehiculo', 'vehiculo_requerido'];
                } elseif ($campoEditadoTemprano === 'peso' || $campoEditadoTemprano === 'peso_kg') {
                    $camposAActualizar = ['peso_kg', 'peso_mercancia', 'pesoMercancia'];
                }
                
                // Ruta única: editar a nivel global (comportamiento original)
                if (true) {
                    // Ruta única: editar a nivel global (comportamiento original)
                    foreach ($camposAActualizar as $campo) {
                        if ($esEliminacion) {
                            unset($extractedData[$campo]);
                        } else {
                            $extractedData[$campo] = $valorEditadoTemprano;
                        }
                    }
                    
                    // 🔧 FIX BUG #1: Sincronizar peso_kg con peso_mercancia para ruta única
                    if ($campoEditadoTemprano === 'peso' || $campoEditadoTemprano === 'peso_kg') {
                        $extractedData['peso_kg'] = $valorEditadoTemprano;
                        $extractedData['peso_mercancia'] = $valorEditadoTemprano;
                        $extractedData['pesoMercancia'] = $valorEditadoTemprano;
                    }
                }
            }
            
            // Guardar en grupo
            if ($groupId) {
                $group = GroupCotization::find($groupId);
                if ($group) {
                    Log::info('💾 GUARDANDO extracted_data tras edición simple', [
                        'group_id' => $groupId,
                        'campo_editado' => $campoEditadoTemprano,
                        'valor_nuevo' => $valorEditadoTemprano,
                        'es_eliminacion' => $esEliminacion,
                        'selected_route_index' => $selectedRouteIndex,
                        'antes' => json_decode($group->extracted_data ?? '{}', true),
                        'despues' => $extractedData
                    ]);
                    
                    $group->extracted_data = json_encode($extractedData);
                    $group->save();
                    
                    Log::info('✅ extracted_data GUARDADO en BD (edición simple)', [
                        'group_id' => $groupId,
                        'datos_guardados' => $extractedData
                    ]);
                    
                    // 🆕 FIX: También actualizar cotizacion_models si existe
                    if ($isMultiRouteData && $selectedRouteIndex !== null && isset($extractedData[$selectedRouteIndex])) {
                        // Es multi-ruta, actualizar solo la ruta seleccionada
                        $cotizaciones = \App\Models\CotizacionModel::where('group_cotization_id', $groupId)
                            ->orderBy('id')
                            ->get();
                        
                        if (isset($cotizaciones[$selectedRouteIndex])) {
                            $cotizacion = $cotizaciones[$selectedRouteIndex];
                            $rutaData = $extractedData[$selectedRouteIndex];
                            
                            // Actualizar campos según el tipo de edición
                            if ($campoEditadoTemprano === 'vehiculo' || in_array($campoEditadoTemprano, ['vehiculo', 'claseVehiculo', 'vehiculo_requerido'])) {
                                // 🔧 FIX BUG #2: Solo actualizar vehículo, NUNCA tocar producto
                                $cotizacion->vehiculo_requerido = $esEliminacion ? null : $valorEditadoTemprano;
                                Log::info('✅ Vehículo actualizado en cotizacion_models (SIN tocar producto)', [
                                    'vehiculo' => $valorEditadoTemprano,
                                    'producto_antes' => $cotizacion->tipo_producto,
                                    'producto_despues' => $cotizacion->tipo_producto
                                ]);
                            } elseif ($campoEditadoTemprano === 'producto' || $campoEditadoTemprano === 'tipo_producto') {
                                // 🔧 FIX BUG #2: Solo actualizar producto, NUNCA tocar vehículo
                                $cotizacion->tipo_producto = $esEliminacion ? null : $valorEditadoTemprano;
                                Log::info('✅ Producto actualizado en cotizacion_models (SIN tocar vehículo)', [
                                    'producto' => $valorEditadoTemprano,
                                    'vehiculo_antes' => $cotizacion->vehiculo_requerido,
                                    'vehiculo_despues' => $cotizacion->vehiculo_requerido
                                ]);
                            } elseif ($campoEditadoTemprano === 'peso' || $campoEditadoTemprano === 'peso_kg' || $campoEditadoTemprano === 'peso_mercancia') {
                                // 🔧 FIX BUG #1: Actualizar peso_mercancia en cotizacion_models
                                $cotizacion->peso_mercancia = $esEliminacion ? 0 : $valorEditadoTemprano;
                                Log::info('✅ Peso actualizado en cotizacion_models', [
                                    'peso_nuevo' => $valorEditadoTemprano
                                ]);
                            } elseif ($campoEditadoTemprano === 'cantidad') {
                                $cotizacion->cantidad = $esEliminacion ? 0 : $valorEditadoTemprano;
                            } elseif ($campoEditadoTemprano === 'valor' || $campoEditadoTemprano === 'valor_declarado') {
                                $cotizacion->valor_declarado = $esEliminacion ? 0 : $valorEditadoTemprano;
                            } elseif ($campoEditadoTemprano === 'origen' || $campoEditadoTemprano === 'ciudad_origen') {
                                $cotizacion->ciudad_origen = $esEliminacion ? null : $valorEditadoTemprano;
                            } elseif ($campoEditadoTemprano === 'destino' || $campoEditadoTemprano === 'ciudad_destino') {
                                $cotizacion->ciudad_destino = $esEliminacion ? null : $valorEditadoTemprano;
                            } elseif ($campoEditadoTemprano === 'peso' || $campoEditadoTemprano === 'peso_kg') {
                                // 🔧 FIX BUG #1: Actualizar peso_mercancia en cotizacion_models
                                $cotizacion->peso_mercancia = $esEliminacion ? 0 : $valorEditadoTemprano;
                            } elseif ($campoEditadoTemprano === 'valor' || $campoEditadoTemprano === 'valor_declarado') {
                                $cotizacion->valor_declarado = $esEliminacion ? 0 : $valorEditadoTemprano;
                            } elseif ($campoEditadoTemprano === 'cantidad') {
                                $cotizacion->cantidad = $esEliminacion ? 0 : $valorEditadoTemprano;
                            }
                            
                            $cotizacion->save();
                            
                            Log::info('✅ cotizacion_models ACTUALIZADA (edición simple multi-ruta)', [
                                'cotizacion_id' => $cotizacion->id,
                                'campo' => $campoEditadoTemprano,
                                'valor' => $esEliminacion ? '(eliminado)' : $valorEditadoTemprano
                            ]);
                        }
                    } else {
                        // 🔧 FIX BUG #1: También actualizar cotizacion_models para RUTA ÚNICA
                        $cotizacion = \App\Models\CotizacionModel::where('group_cotization_id', $groupId)
                            ->first();
                        
                        if ($cotizacion) {
                            // Actualizar campos según el tipo de edición
                            if ($campoEditadoTemprano === 'vehiculo' || in_array($campoEditadoTemprano, ['vehiculo', 'claseVehiculo', 'vehiculo_requerido'])) {
                                $cotizacion->vehiculo_requerido = $esEliminacion ? null : $valorEditadoTemprano;
                                Log::info('✅ Vehículo actualizado en cotizacion_models - RUTA ÚNICA', [
                                    'vehiculo' => $valorEditadoTemprano
                                ]);
                            } elseif ($campoEditadoTemprano === 'producto' || $campoEditadoTemprano === 'tipo_producto') {
                                $cotizacion->tipo_producto = $esEliminacion ? null : $valorEditadoTemprano;
                                Log::info('✅ Producto actualizado en cotizacion_models - RUTA ÚNICA', [
                                    'producto' => $valorEditadoTemprano
                                ]);
                            } elseif ($campoEditadoTemprano === 'peso' || $campoEditadoTemprano === 'peso_kg' || $campoEditadoTemprano === 'peso_mercancia') {
                                $cotizacion->peso_mercancia = $esEliminacion ? 0 : $valorEditadoTemprano;
                                Log::info('✅ Peso actualizado en cotizacion_models - RUTA ÚNICA', [
                                    'peso_nuevo' => $valorEditadoTemprano
                                ]);
                            } elseif ($campoEditadoTemprano === 'cantidad') {
                                $cotizacion->cantidad = $esEliminacion ? 0 : $valorEditadoTemprano;
                            } elseif ($campoEditadoTemprano === 'valor' || $campoEditadoTemprano === 'valor_declarado') {
                                $cotizacion->valor_declarado = $esEliminacion ? 0 : $valorEditadoTemprano;
                            } elseif ($campoEditadoTemprano === 'origen' || $campoEditadoTemprano === 'ciudad_origen') {
                                $cotizacion->ciudad_origen = $esEliminacion ? null : $valorEditadoTemprano;
                            } elseif ($campoEditadoTemprano === 'destino' || $campoEditadoTemprano === 'ciudad_destino') {
                                $cotizacion->ciudad_destino = $esEliminacion ? null : $valorEditadoTemprano;
                            }
                            
                            $cotizacion->save();
                            
                            Log::info('✅ cotizacion_models ACTUALIZADA (edición simple - RUTA ÚNICA)', [
                                'cotizacion_id' => $cotizacion->id,
                                'campo' => $campoEditadoTemprano,
                                'valor' => $esEliminacion ? '(eliminado)' : $valorEditadoTemprano
                            ]);
                        } else {
                            Log::warning('⚠️ No se encontró cotizacion_models para actualizar (ruta única)', [
                                'group_id' => $groupId
                            ]);
                        }
                    }
                    
                    Log::info('📦 Datos actualizados en GRUPO (edición simple temprana)', [
                        'group_id' => $groupId,
                        'campo' => $campoEditadoTemprano,
                        'valor' => $esEliminacion ? '(eliminado)' : $valorEditadoTemprano
                    ]);
                }
            }
            
            // Guardar mensaje de respuesta
            $nombreCampo = str_replace(['ciudad_', 'peso_kg', 'valor_declarado'], ['', 'peso', 'valor declarado'], $campoEditadoTemprano);
            $rutaInfo = '';
            if ($isMultiRouteData && $selectedRouteIndex !== null) {
                $rutaInfo = " en la Ruta " . ($selectedRouteIndex + 1);
            }
            
            if ($esEliminacion) {
                $respuesta = "¡Entendido! He eliminado el {$nombreCampo}{$rutaInfo}.";
            } else {
                $respuesta = "¡Entendido! He actualizado el {$nombreCampo} a \"{$valorEditadoTemprano}\"{$rutaInfo}.";
            }
            
            ConversationMessage::create([
                'session_id' => $session->id,
                'group_cotization_id' => $groupId,
                'role' => 'assistant',
                'content' => $respuesta,
                'timestamp' => now()
            ]);
            
            $runId = 'run_quick_edit_early_' . time();
            $metadata = json_decode($session->metadata ?? '{}', true);
            $metadata['last_run_id'] = $runId;
            $metadata['last_run_status'] = 'completed';
            // 🆕 SINCRONIZAR quote_data y extracted_data para evitar inconsistencias
            $metadata['quote_data'] = $extractedData;
            $metadata['extracted_data'] = $extractedData;
            $session->metadata = json_encode($metadata);
            $session->save();
            
            return [
                'id' => $runId,
                'status' => 'completed_with_data',
                'extracted_data' => $extractedData,
                'is_single_route_edit' => $isMultiRouteData && $selectedRouteIndex !== null, // 🆕 Indicar al frontend
                'edited_route_index' => $selectedRouteIndex // 🆕 Qué ruta se editó
            ];
        }
        
        // PASO 2: Extraer datos del último mensaje
        $newExtractedData = self::extractAllDataFromMessage($messages);
        
        // PASO 3: MERGEAR datos - los NUEVOS SOBRESCRIBEN los anteriores
        // Esto permite que el usuario corrija datos diciendo "producto es miel" etc.
        $extractedData = $previousExtractedData;
        if (!empty($newExtractedData)) {
            // Detectar si es multi-ruta (AMBOS FORMATOS)
            // Formato 1: Array indexado [0, 1, 2]
            // Formato 2: Objeto {multi_ruta: true, rutas: [...]}
            $isNewMultiRoute = isset($newExtractedData[0]) && is_array($newExtractedData[0]);
            $isPreviousMultiRoute = (isset($previousExtractedData[0]) && is_array($previousExtractedData[0])) ||
                                   (isset($previousExtractedData['multi_ruta']) && $previousExtractedData['multi_ruta'] === true && isset($previousExtractedData['rutas']));
            
            // 🆕 EDICIÓN DE RUTA ESPECÍFICA: Si hay una ruta seleccionada y los nuevos datos
            // son para una sola ruta (no multi-ruta), aplicar solo a esa ruta
            
            // 🔧 FIX: Recalcular existingRoutesCount automáticamente si es necesario
            if ($isPreviousMultiRoute && $existingRoutesCount === 0) {
                // Contar rutas según el formato
                if (isset($previousExtractedData['rutas'])) {
                    $existingRoutesCount = count($previousExtractedData['rutas']);
                } else {
                    $existingRoutesCount = count(array_filter(array_keys($extractedData), 'is_numeric'));
                }
                Log::info('📊 Rutas existentes contadas automáticamente', ['count' => $existingRoutesCount, 'formato' => isset($previousExtractedData['rutas']) ? 'objeto' : 'array']);
            }
            
            $isSingleRouteEdit = $selectedRouteIndex !== null && 
                                 !$isNewMultiRoute && 
                                 $isPreviousMultiRoute && 
                                 $existingRoutesCount > 1;
            
            if ($isSingleRouteEdit) {
                // Edición de ruta individual - aplicar cambios SOLO a la ruta seleccionada
                Log::info('✏️ Modo EDICIÓN DE RUTA INDIVIDUAL', [
                    'selected_route_index' => $selectedRouteIndex,
                    'existing_routes_count' => $existingRoutesCount,
                    'new_fields' => array_keys($newExtractedData),
                    'formato' => isset($previousExtractedData['rutas']) ? 'objeto {multi_ruta, rutas}' : 'array indexado'
                ]);
                
                // Determinar dónde está el array de rutas según el formato
                if (isset($extractedData['rutas'])) {
                    // Formato: {multi_ruta: true, rutas: [...]}
                    $targetRuta = &$extractedData['rutas'][$selectedRouteIndex];
                } else {
                    // Formato: [0, 1, 2]
                    $targetRuta = &$extractedData[$selectedRouteIndex];
                }
                
                // Verificar que la ruta seleccionada existe
                if (isset($targetRuta)) {
                    // Aplicar cambios solo a la ruta seleccionada
                    foreach ($newExtractedData as $key => $value) {
                        if (!empty($value) && !is_numeric($key)) {
                            // Solo aplicar campos de datos (no índices numéricos)
                            $oldValue = $targetRuta[$key] ?? null;
                            $targetRuta[$key] = $value;
                            
                            // 🔧 FIX: Sincronizar campos duplicados
                            if ($key === 'ciudad_origen' || $key === 'origen') {
                                $targetRuta['origen'] = $value;
                                $targetRuta['ciudad_origen'] = $value;
                            } elseif ($key === 'ciudad_destino' || $key === 'destino') {
                                $targetRuta['destino'] = $value;
                                $targetRuta['ciudad_destino'] = $value;
                            }
                            
                            Log::info("✏️ Ruta {$selectedRouteIndex}: {$key} = {$value}" . ($oldValue ? " (antes: {$oldValue})" : " (nuevo)"));
                        }
                    }
                } else {
                    Log::warning('⚠️ Ruta seleccionada no existe', [
                        'selected_route_index' => $selectedRouteIndex,
                        'available_routes' => array_keys($extractedData)
                    ]);
                }
            } elseif ($isNewMultiRoute && $isPreviousMultiRoute) {
                // Ambos son multi-ruta, mergear cada ruta
                foreach ($newExtractedData as $idx => $newRoute) {
                    if (isset($extractedData[$idx])) {
                        foreach ($newRoute as $key => $value) {
                            // 🆕 NUEVO sobrescribe ANTERIOR (permite correcciones)
                            if (!empty($value)) {
                                $extractedData[$idx][$key] = $value;
                            }
                        }
                    } else {
                        $extractedData[$idx] = $newRoute;
                    }
                }
            } elseif (!$isNewMultiRoute && !$isPreviousMultiRoute) {
                // Ambos son ruta única, mergear campos
                foreach ($newExtractedData as $key => $value) {
                    // 🆕 NUEVO sobrescribe ANTERIOR si tiene valor (permite correcciones)
                    if (!empty($value)) {
                        if (isset($extractedData[$key]) && $extractedData[$key] !== $value) {
                            Log::info("🔄 Campo ACTUALIZADO: {$key} = {$value} (antes: {$extractedData[$key]})");
                        } else {
                            Log::info("🆕 Campo agregado: {$key} = {$value}");
                        }
                        $extractedData[$key] = $value;
                    }
                }
            } elseif (!$isNewMultiRoute && $isPreviousMultiRoute && $existingRoutesCount <= 1) {
                // Nuevos datos de una ruta, pero antes había multi-ruta con solo 1 ruta real
                // Esto puede pasar si el usuario edita la única ruta que existe
                // Aplicar a la primera ruta (índice 0)
                Log::info('🔄 Aplicando datos de ruta única a primera ruta existente');
                foreach ($newExtractedData as $key => $value) {
                    if (!empty($value) && !is_numeric($key)) {
                        $extractedData[0][$key] = $value;
                        
                        // 🔧 FIX: Sincronizar campos duplicados
                        if ($key === 'ciudad_origen' || $key === 'origen') {
                            $extractedData[0]['origen'] = $value;
                            $extractedData[0]['ciudad_origen'] = $value;
                        } elseif ($key === 'ciudad_destino' || $key === 'destino') {
                            $extractedData[0]['destino'] = $value;
                            $extractedData[0]['ciudad_destino'] = $value;
                        }
                    }
                }
            } elseif (!$isNewMultiRoute && $isPreviousMultiRoute) {
                // 🔧 FIX: Nuevos datos de ruta única + datos anteriores multi-ruta
                // Esto es una CORRECCIÓN de campo - aplicar a todas las rutas o a la primera
                Log::info('🔄 Aplicando corrección de campo a rutas existentes (multi-ruta)', [
                    'new_fields' => array_keys($newExtractedData),
                    'existing_routes' => $existingRoutesCount
                ]);
                
                // Si hay ruta seleccionada, aplicar solo a esa
                if ($selectedRouteIndex !== null && isset($extractedData[$selectedRouteIndex])) {
                    foreach ($newExtractedData as $key => $value) {
                        if (!empty($value) && !is_numeric($key)) {
                            $extractedData[$selectedRouteIndex][$key] = $value;
                            
                            // 🔧 FIX: Sincronizar campos duplicados
                            if ($key === 'ciudad_origen' || $key === 'origen') {
                                $extractedData[$selectedRouteIndex]['origen'] = $value;
                                $extractedData[$selectedRouteIndex]['ciudad_origen'] = $value;
                            } elseif ($key === 'ciudad_destino' || $key === 'destino') {
                                $extractedData[$selectedRouteIndex]['destino'] = $value;
                                $extractedData[$selectedRouteIndex]['ciudad_destino'] = $value;
                            }
                        }
                    }
                } else {
                    // Sin ruta seleccionada, aplicar a la primera ruta (índice 0)
                    if (isset($extractedData[0])) {
                        foreach ($newExtractedData as $key => $value) {
                            if (!empty($value) && !is_numeric($key)) {
                                $extractedData[0][$key] = $value;
                                
                                // 🔧 FIX: Sincronizar campos duplicados
                                if ($key === 'ciudad_origen' || $key === 'origen') {
                                    $extractedData[0]['origen'] = $value;
                                    $extractedData[0]['ciudad_origen'] = $value;
                                } elseif ($key === 'ciudad_destino' || $key === 'destino') {
                                    $extractedData[0]['destino'] = $value;
                                    $extractedData[0]['ciudad_destino'] = $value;
                                }
                            }
                        }
                    }
                }
            } else {
                // Caso mixto - usar los nuevos datos
                $extractedData = $newExtractedData;
            }
            
            Log::info('📊 Datos extraídos MERGEADOS', [
                'thread_id' => $threadId,
                'previous_count' => count($previousExtractedData),
                'new_count' => count($newExtractedData),
                'merged_count' => count($extractedData),
                'merged_fields' => array_keys($extractedData)
            ]);
        }

        // 🆕 DETECTAR "agrega/incluye la tara" en el último mensaje y actualizar peso
        $lastUserMessage = '';
        foreach (array_reverse($messages) as $msg) {
            if (isset($msg['role']) && $msg['role'] === 'user') {
                $lastUserMessage = strtolower($msg['content'] ?? '');
                break;
            }
        }
        
        // Detectar si el usuario pide agregar tara (PRIMERO verificar si NO la incluye)
        // 🔧 FIX: Agregar detección de "+ tara" y "más tara" (significa que hay que SUMAR la tara)
        $noIncluyeTara = preg_match('/no\s+incluye\s+tara|sin\s+tara|peso\s+neto|el\s+peso\s+no\s+incluye\s+tara|\+\s*tara|m[aá]s\s+tara/ui', $lastUserMessage);
        $yaIncluyeTara = preg_match('/(?:ya\s+(?:incluye|tiene)|peso\s+(?:ya\s+)?(?:con|incluye)\s+tara|tara\s+(?:ya\s+)?incluida|(?:el\s+)?peso\s+es\s+con\s+tara)/ui', $lastUserMessage);
        $agregarTara = preg_match('/(?:agrega|añade|suma|pon|incluye|incluir|agregar)\s+(?:la\s+)?tara/ui', $lastUserMessage);
        $quitarTara = preg_match('/(?:quita|elimina|remueve|resta|saca|sin)\s+(?:la\s+)?tara/ui', $lastUserMessage);
        
        // 🆕 Si el usuario dice "quita la tara", restar 3400 kg
        // FIX: Respetar selectedRouteIndex para aplicar solo a la ruta seleccionada
        if ($quitarTara && !$noIncluyeTara) {
            $isMultiRouteData = isset($extractedData[0]) && is_array($extractedData[0]);
            
            if ($isMultiRouteData) {
                // 🔧 FIX: Si hay ruta seleccionada, aplicar SOLO a esa
                if ($selectedRouteIndex !== null && isset($extractedData[$selectedRouteIndex])) {
                    $route = $extractedData[$selectedRouteIndex];
                    if (isset($route['peso_kg']) && isset($route['incluye_tara']) && $route['incluye_tara']) {
                        $pesoAnterior = $route['peso_kg'];
                        // 🔧 FIX: Usar tara almacenada o calcular según contenedor (no hardcoded 3400)
                        $taraTexto = $route['empaque'] ?? $route['contenedor'] ?? $route['tamano_contenedor'] ?? '';
                        $taraQuitar = isset($route['tara']) ? $route['tara'] : self::getTaraByContenedor($taraTexto);
                        $extractedData[$selectedRouteIndex]['peso_kg'] = max(0, $pesoAnterior - $taraQuitar);
                        $extractedData[$selectedRouteIndex]['incluye_tara'] = false;
                        Log::info('📦 TARA removida de ruta ESPECÍFICA', [
                            'ruta_seleccionada' => $selectedRouteIndex,
                            'peso_anterior' => $pesoAnterior,
                            'tara_quitada' => $taraQuitar,
                            'peso_sin_tara' => $extractedData[$selectedRouteIndex]['peso_kg']
                        ]);
                    }
                } else {
                    // Sin ruta seleccionada - aplicar a todas
                    foreach ($extractedData as $idx => $route) {
                        if (isset($route['peso_kg']) && isset($route['incluye_tara']) && $route['incluye_tara']) {
                            $pesoAnterior = $route['peso_kg'];
                            // 🔧 FIX: Usar tara almacenada o calcular según contenedor (no hardcoded 3400)
                            $taraTexto = $route['empaque'] ?? $route['contenedor'] ?? $route['tamano_contenedor'] ?? '';
                            $taraQuitar = isset($route['tara']) ? $route['tara'] : self::getTaraByContenedor($taraTexto);
                            $extractedData[$idx]['peso_kg'] = max(0, $pesoAnterior - $taraQuitar);
                            $extractedData[$idx]['incluye_tara'] = false;
                            Log::info('📦 TARA removida de ruta (multi)', [
                                'ruta' => $idx,
                                'peso_anterior' => $pesoAnterior,
                                'tara_quitada' => $taraQuitar,
                                'peso_sin_tara' => $extractedData[$idx]['peso_kg']
                            ]);
                        }
                    }
                }
            } else {
                if (isset($extractedData['peso_kg']) && isset($extractedData['incluye_tara']) && $extractedData['incluye_tara']) {
                    $pesoAnterior = $extractedData['peso_kg'];
                    // 🔧 FIX: Usar tara almacenada o calcular según contenedor (no hardcoded 3400)
                    $taraTexto = $extractedData['empaque'] ?? $extractedData['contenedor'] ?? $extractedData['tamano_contenedor'] ?? '';
                    $taraQuitar = isset($extractedData['tara']) ? $extractedData['tara'] : self::getTaraByContenedor($taraTexto);
                    $extractedData['peso_kg'] = max(0, $pesoAnterior - $taraQuitar);
                    $extractedData['incluye_tara'] = false;
                    Log::info('📦 TARA removida del peso', [
                        'peso_anterior' => $pesoAnterior,
                        'tara' => $taraQuitar,
                        'peso_sin_tara' => $extractedData['peso_kg']
                    ]);
                }
            }
        }
        
        // 🆕 Agregar tara solo si se pide explícitamente Y no está ya incluida
        // FIX: Respetar selectedRouteIndex para aplicar solo a la ruta seleccionada
        if ($agregarTara) {
            // Verificar si hay peso en los datos y sumar tara (3400 kg fijo)
            $isMultiRouteData = isset($extractedData[0]) && is_array($extractedData[0]);
            
            if ($isMultiRouteData) {
                // 🔧 FIX: Si hay ruta seleccionada, aplicar SOLO a esa
                if ($selectedRouteIndex !== null && isset($extractedData[$selectedRouteIndex])) {
                    $route = $extractedData[$selectedRouteIndex];
                    // SIEMPRE SUMAR SI EL USUARIO LO PIDE, verificando lógica inteligente para no duplicar excesivamente
                    // Pero si el usuario dice "agrega tara", asumimos que el peso actual NO la tiene o quiere corregirlo
                    $yaConTara = isset($route['incluye_tara']) && $route['incluye_tara'] === true;
                    
                    if (isset($route['peso_kg'])) {
                        // Limpiar posible formato previo si viene de un string sucio
                         $pesoAnterior = floatval(str_replace(',', '', (string)$route['peso_kg']));
                         
                         // Si la diferencia con el peso anterior es exactamente 3400, probalemente ya se sumó
                         // Pero como estamos en el flujo FINAL del Run, es seguro sumarlo si el flag no está
                         // O forzarlo si el usuario lo pide explícitamente
                         
                         if (!$yaConTara) {
                            // 🔧 FIX: Usar tara según tamaño de contenedor
                            $taraRuta = isset($route['tamano_contenedor']) && $route['tamano_contenedor'] == 20 ? 2300 : 3400;
                            $extractedData[$selectedRouteIndex]['peso_kg'] = $pesoAnterior + $taraRuta;
                            $extractedData[$selectedRouteIndex]['tara'] = $taraRuta;
                            $extractedData[$selectedRouteIndex]['incluye_tara'] = true;
                            Log::info('📦 TARA agregada a ruta ESPECÍFICA', [
                                'ruta_seleccionada' => $selectedRouteIndex,
                                'peso_anterior' => $pesoAnterior,
                                'tara' => $taraRuta,
                                'peso_con_tara' => $extractedData[$selectedRouteIndex]['peso_kg']
                            ]);
                         }
                    }
                } else {
                    // Sin ruta seleccionada - aplicar a todas
                    foreach ($extractedData as $idx => $route) {
                        // 🔧 Verificar que NO tenga ya la tara incluida en los datos
                        $yaConTara = isset($route['incluye_tara']) && $route['incluye_tara'] === true;
                        
                        if (isset($route['peso_kg']) && !$yaConTara) {
                             $pesoAnterior = floatval(str_replace(',', '', (string)$route['peso_kg']));
                             
                            // 🔧 FIX: Usar tara según tamaño de contenedor
                            $taraRuta = isset($route['tamano_contenedor']) && $route['tamano_contenedor'] == 20 ? 2300 : 3400;
                            
                            // 🔧 HEURÍSTICA: Si peso-tara es múltiplo exacto de 1000, OpenAI probablemente ya sumó tara
                            $pesoSinPosibleTara = $pesoAnterior - $taraRuta;
                            $esProbablementeDuplicado = ($pesoSinPosibleTara > 0 && $pesoSinPosibleTara % 1000 == 0);
                            
                            if ($esProbablementeDuplicado) {
                                // No agregar tara, OpenAI ya la sumó
                                $extractedData[$idx]['incluye_tara'] = true;
                                Log::info('📦 TARA NO agregada - OpenAI ya la sumó (heurística)', [
                                    'ruta' => $idx,
                                    'peso_actual' => $pesoAnterior,
                                    'peso_base_detectado' => $pesoSinPosibleTara
                                ]);
                            } else {
                                $extractedData[$idx]['peso_kg'] = $pesoAnterior + $taraRuta;
                                $extractedData[$idx]['tara'] = $taraRuta;
                                $extractedData[$idx]['incluye_tara'] = true;
                                Log::info('📦 TARA agregada a ruta (multi)', [
                                    'ruta' => $idx,
                                    'peso_anterior' => $pesoAnterior,
                                    'tara' => $taraRuta,
                                    'peso_con_tara' => $extractedData[$idx]['peso_kg']
                                ]);
                            }
                        }
                    }
                }
            } else {
                // 🔧 Verificar que NO tenga ya la tara incluida en los datos
                $yaConTara = isset($extractedData['incluye_tara']) && $extractedData['incluye_tara'] === true;
                
                if (isset($extractedData['peso_kg']) && !$yaConTara) {
                    $pesoAnterior = floatval(str_replace(',', '', (string)$extractedData['peso_kg']));
                    
                    // 🔧 REGEX MEJORADO: Detectar si el peso ya tiene tara incluida
                    $pareceYaTenerTara = ($pesoAnterior >= 3400 && preg_match('/(?:con\s+(?:la\s+)?tara\s+incluida|tara\s+(?:ya\s+)?incluida|(?:ya\s+)?(?:incluye|tiene)\s+(?:la\s+)?tara|con\s+tara(?!\s+(?:no|sin))|peso\s+bruto|(?:kilos?|kilogramos?|kg)\s+con\s+tara)/ui', $lastUserMessage));
                    
                    if (!$pareceYaTenerTara) {
                        // 🔧 FIX: Usar tara según tamaño de contenedor
                        $taraRuta = isset($extractedData['tamano_contenedor']) && $extractedData['tamano_contenedor'] == 20 ? 2300 : 3400;
                        $extractedData['peso_kg'] = $pesoAnterior + $taraRuta;
                        $extractedData['tara'] = $taraRuta;
                        $extractedData['incluye_tara'] = true;
                        Log::info('📦 TARA agregada al peso', [
                            'peso_anterior' => $pesoAnterior,
                            'tara' => $taraRuta,
                            'peso_con_tara' => $extractedData['peso_kg']
                        ]);
                    } else {
                        $extractedData['incluye_tara'] = true;
                        Log::info('📦 TARA no sumada - parece ya estar incluida', [
                            'peso_kg' => $pesoAnterior
                        ]);
                    }
                }
            }
        }

        // 🆕 DETECTAR MÚLTIPLES RUTAS y guardar en metadata
        $isMultiRoute = isset($extractedData[0]) && is_array($extractedData[0]);
        if ($isMultiRoute) {
            $metadata = json_decode($session->metadata ?? '{}', true);
            $metadata['multi_route_data'] = $extractedData;
            $metadata['is_multi_route'] = true;
            $metadata['route_count'] = count($extractedData);
            $session->metadata = json_encode($metadata);
            $session->save();
            
            Log::info('🚚 MÚLTIPLES RUTAS detectadas y guardadas en metadata', [
                'thread_id' => $threadId,
                'route_count' => count($extractedData),
                'routes' => $extractedData
            ]);
        }

        // NUEVO: Si se detectó empaque, obtener su ID de la base de datos
        $dataToCheck = $isMultiRoute ? $extractedData[0] : $extractedData;
        if (!empty($dataToCheck['empaque'])) {
            $empaqueId = self::getEmpaqueIdFromDB($dataToCheck['empaque']);
            if ($empaqueId) {
                if ($isMultiRoute) {
                    // Aplicar empaque_id a todas las rutas (SOLO claves numéricas)
                    foreach ($extractedData as $key => &$ruta) {
                        if (is_numeric($key) && is_array($ruta)) {
                            $ruta['empaque_id'] = $empaqueId;
                        }
                    }
                    unset($ruta);
                } else {
                    $extractedData['empaque_id'] = $empaqueId;
                }
                Log::info('Embalaje detectado y consultado en BD', [
                    'empaque' => $dataToCheck['empaque'],
                    'empaque_id' => $empaqueId,
                    'is_multi_route' => $isMultiRoute
                ]);
            }
        }

        // 🆕 NUEVO: Si se detectó producto, buscar su código en la base de datos
        // 🔴 IMPORTANTE: Para multi-ruta, buscar el producto de CADA ruta individualmente
        if ($isMultiRoute) {
            // Procesar cada ruta individualmente para obtener su producto
            foreach ($extractedData as $key => &$ruta) {
                if (is_numeric($key) && is_array($ruta) && !empty($ruta['producto'])) {
                    $productoInfo = self::getProductoFromDB($ruta['producto']);
                    if ($productoInfo) {
                        $ruta['producto_codigo'] = $productoInfo['codigo'];
                        $ruta['producto_nombre'] = $productoInfo['nombre'];
                        if ($productoInfo['tipo']) {
                            $ruta['tipo_producto'] = $productoInfo['tipo'];
                        }
                        Log::info("✅ Producto de Ruta {$key} consultado en BD", [
                            'ruta_index' => $key,
                            'producto_original' => $ruta['producto'],
                            'producto_codigo' => $productoInfo['codigo'],
                            'producto_nombre' => $productoInfo['nombre']
                        ]);
                    }
                }
            }
            unset($ruta);
        } elseif (!empty($dataToCheck['producto'])) {
            // Ruta única - comportamiento normal
            $productoInfo = self::getProductoFromDB($dataToCheck['producto']);
            if ($productoInfo) {
                $extractedData['producto_codigo'] = $productoInfo['codigo'];
                $extractedData['producto_nombre'] = $productoInfo['nombre'];
                if ($productoInfo['tipo']) {
                    $extractedData['tipo_producto'] = $productoInfo['tipo'];
                }
                Log::info('✅ Producto detectado y consultado en BD', [
                    'producto' => $dataToCheck['producto'],
                    'producto_codigo' => $productoInfo['codigo'],
                    'producto_nombre' => $productoInfo['nombre']
                ]);
            }
        }

        // 🆕 DETECTAR SELECCIÓN DE OPCIÓN DE PRODUCTO: "dame la opción 1", "opción 2", "la 1", etc.
        $esSeleccionOpcion = false;
        $opcionSeleccionada = null;
        
        if (preg_match('/(?:dame\s+)?(?:la\s+)?opci[oó]n\s*(\d+)|(?:quiero|selecciono?|escojo)\s+(?:la\s+)?(?:opci[oó]n\s*)?(\d+)|(?:la|el)\s+(\d+)/ui', $lastUserMessage, $opcionMatch)) {
            $opcionSeleccionada = intval($opcionMatch[1] ?: ($opcionMatch[2] ?: $opcionMatch[3]));
            
            // Verificar si hay productos pendientes en metadata
            $metadata = json_decode($session->metadata ?? '{}', true);
            if (isset($metadata['productos_pendientes']) && is_array($metadata['productos_pendientes'])) {
                $productosPendientes = $metadata['productos_pendientes'];
                $indice = $opcionSeleccionada - 1; // Convertir a índice 0-based
                
                // 🔧 FIX CRÍTICO: SIEMPRE usar la ruta guardada en metadata cuando hay productos pendientes
                // La ruta en metadata es la que HIZO la búsqueda, no la que está seleccionada ahora
                $rutaParaProducto = isset($metadata['producto_pendiente_ruta_index']) 
                    ? $metadata['producto_pendiente_ruta_index'] 
                    : $selectedRouteIndex;
                
                Log::info('🎯🎯 SELECCIÓN DE PRODUCTO - RUTA DETERMINADA', [
                    'ruta_en_metadata' => $metadata['producto_pendiente_ruta_index'] ?? 'NO DEFINIDA',
                    'ruta_del_request' => $selectedRouteIndex,
                    'ruta_final_a_usar' => $rutaParaProducto,
                    'opcion_seleccionada' => $opcionSeleccionada,
                    '⚠️ CRÍTICO' => $rutaParaProducto !== null 
                        ? 'Aplicando producto SOLO a ruta ' . $rutaParaProducto 
                        : 'SIN RUTA DEFINIDA - aplicará a primera sin producto'
                ]);
                
                if (isset($productosPendientes[$indice])) {
                    $productoSeleccionado = $productosPendientes[$indice];
                    $esSeleccionOpcion = true;
                    
                    Log::info('🎯 SELECCIÓN DE OPCIÓN detectada', [
                        'opcion' => $opcionSeleccionada,
                        'producto' => $productoSeleccionado['nombre'] ?? 'N/A',
                        'codigo' => $productoSeleccionado['codigo'] ?? 'N/A',
                        'selectedRouteIndex_request' => $selectedRouteIndex,
                        'selectedRouteIndex_metadata' => $metadata['producto_pendiente_ruta_index'] ?? null,
                        'rutaParaProducto_final' => $rutaParaProducto,
                        'isMultiRouteData' => isset($extractedData[0]) && is_array($extractedData[0])
                    ]);
                    
                    // Aplicar el producto seleccionado a extractedData
                    // 🆕 RESPETAR ruta seleccionada (del request O de metadata)
                    $isMultiRouteData = isset($extractedData[0]) && is_array($extractedData[0]);
                    
                    if ($isMultiRouteData && $rutaParaProducto !== null && isset($extractedData[$rutaParaProducto])) {
                        // Aplicar SOLO a la ruta seleccionada
                        $extractedData[$rutaParaProducto]['producto'] = $productoSeleccionado['nombre'] ?? '';
                        $extractedData[$rutaParaProducto]['producto_codigo'] = $productoSeleccionado['codigo'] ?? null;
                        $extractedData[$rutaParaProducto]['producto_nombre'] = $productoSeleccionado['nombre'] ?? '';
                        $extractedData[$rutaParaProducto]['tipo_producto'] = $productoSeleccionado['tipo'] ?? 'MERCANCIAS VARIAS';
                        
                        // 🆕 VALIDACIÓN: Verificar que NO se cambiaron otras rutas
                        foreach ($extractedData as $idx => $ruta) {
                            if (is_numeric($idx) && $idx !== $rutaParaProducto && is_array($ruta)) {
                                if (isset($ruta['producto']) && $ruta['producto'] === $productoSeleccionado['nombre']) {
                                    Log::error('🚨🚨 ERROR CRÍTICO: Producto se aplicó a RUTA INCORRECTA', [
                                        'ruta_target' => $rutaParaProducto,
                                        'ruta_afectada' => $idx,
                                        'producto' => $productoSeleccionado['nombre']
                                    ]);
                                }
                            }
                        }
                        
                        Log::info('🎯🎯 PRODUCTO APLICADO - RUTA CONFIRMADA', [
                            'ruta_index' => $rutaParaProducto,
                            'producto' => $productoSeleccionado['nombre'],
                            'codigo' => $productoSeleccionado['codigo'] ?? 'N/A',
                            '✅ VERIFICADO' => 'Producto aplicado SOLO a ruta ' . $rutaParaProducto . ', otras rutas NO modificadas'
                        ]);
                    } elseif ($isMultiRouteData) {
                        // 🆕 Si NO hay ruta seleccionada, aplicar SOLO a la primera ruta SIN producto
                        // NO aplicar a todas las rutas - eso causa el bug de cambiar rutas incorrectas
                        $rutaSinProducto = null;
                        foreach ($extractedData as $idx => $ruta) {
                            if (is_numeric($idx) && is_array($ruta)) {
                                // Verificar si esta ruta NO tiene producto definido
                                $tieneProducto = !empty($ruta['producto']) && 
                                                 strtoupper($ruta['producto']) !== 'PERSONALIZADO' &&
                                                 !empty($ruta['producto_codigo']);
                                if (!$tieneProducto) {
                                    $rutaSinProducto = $idx;
                                    break;
                                }
                            }
                        }
                        
                        if ($rutaSinProducto !== null) {
                            // Aplicar solo a la primera ruta sin producto
                            $extractedData[$rutaSinProducto]['producto'] = $productoSeleccionado['nombre'] ?? '';
                            $extractedData[$rutaSinProducto]['producto_codigo'] = $productoSeleccionado['codigo'] ?? null;
                            $extractedData[$rutaSinProducto]['producto_nombre'] = $productoSeleccionado['nombre'] ?? '';
                            $extractedData[$rutaSinProducto]['tipo_producto'] = $productoSeleccionado['tipo'] ?? 'MERCANCIAS VARIAS';
                            
                            Log::info('🎯 Producto de opción aplicado a primera ruta sin producto', [
                                'ruta_index' => $rutaSinProducto,
                                'producto' => $productoSeleccionado['nombre'],
                                'codigo' => $productoSeleccionado['codigo'] ?? 'N/A'
                            ]);
                        } else {
                            // Si todas las rutas ya tienen producto, no hacer nada
                            Log::warning('⚠️ Todas las rutas ya tienen producto - selección ignorada', [
                                'producto_seleccionado' => $productoSeleccionado['nombre']
                            ]);
                        }
                    } else {
                        $extractedData['producto'] = $productoSeleccionado['nombre'] ?? '';
                        $extractedData['producto_codigo'] = $productoSeleccionado['codigo'] ?? null;
                        $extractedData['tipo_producto'] = $productoSeleccionado['nombre'] ?? '';
                    }
                    
                    // Limpiar productos pendientes de metadata
                    unset($metadata['productos_pendientes']);
                    unset($metadata['producto_search_term']);
                    unset($metadata['producto_pendiente_ruta_index']); // 🆕 Limpiar también la ruta pendiente
                    // 🆕 SINCRONIZAR quote_data con extracted_data para evitar inconsistencias
                    $metadata['quote_data'] = $extractedData;
                    $metadata['extracted_data'] = $extractedData;
                    $session->metadata = json_encode($metadata);
                    $session->save();
                    
                    // Guardar en el grupo
                    if ($groupId) {
                        $group = GroupCotization::find($groupId);
                        if ($group) {
                            $group->extracted_data = json_encode($extractedData);
                            $group->save();
                            
                            Log::info('📦 Producto seleccionado guardado en GRUPO', [
                                'group_id' => $groupId,
                                'producto' => $productoSeleccionado['nombre'],
                                'codigo' => $productoSeleccionado['codigo']
                            ]);
                        }
                    }
                    
                    // 🆕 FLUJO SECUENCIAL: Verificar si hay más rutas sin producto
                    $isMultiRouteData = isset($extractedData[0]) && is_array($extractedData[0]);
                    $nextRouteIndex = self::findNextRouteWithoutProduct($extractedData);
                    
                    $rutaInfo = '';
                    if ($isMultiRouteData && $rutaParaProducto !== null) {
                        $rutaNum = $rutaParaProducto + 1;
                        $rutaInfo = " para la **Ruta {$rutaNum}**";
                    }
                    
                    // Mensaje de confirmación
                    $respuestaSeleccion = "✅ Producto seleccionado{$rutaInfo}: **{$productoSeleccionado['nombre']}** (Código: {$productoSeleccionado['codigo']})";
                    
                    ConversationMessage::create([
                        'session_id' => $session->id,
                        'group_cotization_id' => $groupId,
                        'role' => 'assistant',
                        'content' => $respuestaSeleccion,
                        'timestamp' => now()
                    ]);
                    
                    // 🚫 DESACTIVADO: NO buscar productos automáticamente para otras rutas
                    // Solo debe buscar productos cuando el usuario EXPLÍCITAMENTE lo pida
                    // Esto previene búsquedas automáticas no deseadas
                    /*
                    if ($nextRouteIndex !== null && $isMultiRouteData) {
                        $searchResult = self::searchProductForRoute($session, $groupId, $extractedData, $nextRouteIndex, false);
                        // ...código de búsqueda automática comentado...
                    }
                    */
                    
                    Log::info('✅ Producto seleccionado - NO se buscará automáticamente para otras rutas', [
                        'productos_pendientes_limpiados' => true,
                        'siguiente_accion' => 'Usuario debe solicitar explícitamente cambio de producto'
                    ]);
                    
                    // Verificar si todas las rutas tienen producto (después de posibles auto-selecciones)
                    $allRoutesComplete = self::findNextRouteWithoutProduct($extractedData) === null;
                    
                    if ($allRoutesComplete && $isMultiRouteData) {
                        // 🎉 Mostrar resumen definitivo
                        $resumen = self::generateFinalSummary($extractedData);
                        ConversationMessage::create([
                            'session_id' => $session->id,
                            'group_cotization_id' => $groupId,
                            'role' => 'assistant',
                            'content' => $resumen,
                            'timestamp' => now()
                        ]);
                        
                        Log::info('✅ Flujo secuencial completado - mostrando resumen final');
                    }
                    
                    $runId = 'run_product_selection_' . time();
                    
                    $metadata['last_run_id'] = $runId;
                    $metadata['last_run_status'] = 'completed';
                    $metadata['quote_data'] = $extractedData;
                    $metadata['extracted_data'] = $extractedData;
                    $session->metadata = json_encode($metadata);
                    $session->save();
                    
                    return [
                        'id' => $runId,
                        'status' => 'completed_with_data',
                        'extracted_data' => $extractedData
                    ];
                }
            }
        }

        // 🚀 OPTIMIZACIÓN: Si se detectaron múltiples rutas completas con Regex, retornar inmediatamente
        // Esto evita llamar a OpenAI cuando ya tenemos la estructura clara
        // 🔧 FIX: Verificar directamente si extractedData tiene múltiples rutas con array indexado
        $hasMultipleRoutes = isset($extractedData[0]) && is_array($extractedData[0]) && count($extractedData) >= 2;
        $isFirstTimeMultiRoute = empty($previousExtractedData) || !isset($previousExtractedData[0]);
        
        if ($hasMultipleRoutes && $isFirstTimeMultiRoute) {
            Log::info('🚀 Optimización Multi-Ruta: Iniciando validación secuencial de productos', [
                'count' => count($extractedData),
                'rutas' => array_map(fn($r) => ($r['origen'] ?? '?') . ' → ' . ($r['destino'] ?? '?'), $extractedData)
            ]);
            
            // 🆕 PASO 1: Confirmar las rutas detectadas
            $respuesta = "¡Entendido! He identificado " . count($extractedData) . " rutas para tu cotización:\n\n";
            
            foreach ($extractedData as $idx => $ruta) {
                $num = $idx + 1;
                $respuesta .= "**Ruta {$num}:**\n";
                if (!empty($ruta['origen'])) $respuesta .= "- Origen: " . strtoupper($ruta['origen']) . "\n";
                if (!empty($ruta['destino'])) $respuesta .= "- Destino: " . strtoupper($ruta['destino']) . "\n";
                if (!empty($ruta['vehiculo'])) $respuesta .= "- Vehículo: " . strtoupper($ruta['vehiculo']) . "\n";
                if (!empty($ruta['peso_kg'])) $respuesta .= "- Peso: " . number_format($ruta['peso_kg'], 0, ',', '.') . " kg\n";
                if (!empty($ruta['producto'])) $respuesta .= "- Producto: " . strtoupper($ruta['producto']) . "\n";
                $respuesta .= "\n";
            }
            
            // Guardar mensaje de confirmación de rutas
            ConversationMessage::create([
                'session_id' => $session->id,
                'group_cotization_id' => $groupId,
                'role' => 'assistant',
                'content' => $respuesta,
                'timestamp' => now()
            ]);
            
            // 🆕 PASO 2: Buscar primera ruta sin producto validado e iniciar flujo secuencial
            $nextRouteIndex = self::findNextRouteWithoutProduct($extractedData);
            
            // 🚫 DESACTIVADO COMPLETAMENTE: NO buscar productos automáticamente
            // Los productos SOLO deben buscarse cuando el usuario EXPLÍCITAMENTE lo solicita
            // Esto previene el bug de productos actualizándose solos en todas las rutas
            /*
            if ($nextRouteIndex !== null) {
                $searchResult = self::searchProductForRoute($session, $groupId, $extractedData, $nextRouteIndex, false);
                // ...todo el código de búsqueda automática comentado...
            }
            */
            
            Log::info('🚫 Búsqueda automática de productos DESACTIVADA', [
                'rutas_sin_producto' => $nextRouteIndex,
                'razon' => 'Usuario debe solicitar explícitamente búsqueda de productos'
            ]);
            
            // Verificar si todas las rutas ya tienen producto (después de auto-selección)
            $allRoutesComplete = self::findNextRouteWithoutProduct($extractedData) === null;
            
            if ($allRoutesComplete) {
                // Mostrar resumen definitivo
                $resumen = self::generateFinalSummary($extractedData);
                ConversationMessage::create([
                    'session_id' => $session->id,
                    'group_cotization_id' => $groupId,
                    'role' => 'assistant',
                    'content' => $resumen,
                    'timestamp' => now()
                ]);
            }
            
            $runId = 'run_multi_route_' . time();
            
            // Guardar metadata
            $metadata = json_decode($session->metadata ?? '{}', true);
            $metadata['last_run_id'] = $runId;
            $metadata['last_run_status'] = 'completed';
            $metadata['extracted_data'] = $extractedData;
            $metadata['quote_data'] = $extractedData;
            $session->metadata = json_encode($metadata);
            $session->save();
            
            // Guardar en grupo
            if ($groupId) {
                $group = GroupCotization::find($groupId);
                if ($group) {
                    $group->extracted_data = json_encode($extractedData);
                    $group->save();
                }
            }

            return [
                'id' => $runId,
                'status' => 'completed_with_data',
                'extracted_data' => $extractedData
            ];
        }

        // 🚀 OPTIMIZACIÓN: Detectar ediciones simples y responder sin llamar al API
        // Si el mensaje es una corrección simple de un campo, no necesitamos IA
        $esEdicionSimple = false;
        $campoEditado = null;
        $valorEditado = null;
        
        // Detectar corrección de producto
        if (preg_match('/(?:el\s+)?producto\s*(?:es|será|sea|:)\s+([a-záéíóúñ\s]+?)(?:\s*[.,;]|\s+y\s+|$)/ui', $lastUserMessage, $matches)) {
            $esEdicionSimple = true;
            $campoEditado = 'producto';
            $valorEditado = strtoupper(trim($matches[1]));
        }
        // Detectar corrección de origen - MEJORADO con más variantes
        elseif (preg_match('/(?:el\s+)?origen\s*(?:es|será|sea|cambia\s*(?:a|por)|ponga?|pon|:)\s+([a-záéíóúñ]+)/ui', $lastUserMessage, $matches)) {
            $esEdicionSimple = true;
            $campoEditado = 'origen';
            $valorEditado = self::normalizeCityName(trim($matches[1]));
        }
        // Detectar corrección de destino - MEJORADO con más variantes
        elseif (preg_match('/(?:el\s+)?destino\s*(?:es|será|sea|cambia\s*(?:a|por)|ponga?|pon|:)\s+([a-záéíóúñ]+)/ui', $lastUserMessage, $matches)) {
            $esEdicionSimple = true;
            $campoEditado = 'destino';
            $valorEditado = self::normalizeCityName(trim($matches[1]));
        }
        // Detectar corrección de peso
        // 🆕 FIX: Usar normalizeWeight para manejar formato miles español (7.600 kg = 7600 kg)
        elseif (preg_match('/(?:el\s+)?peso\s*(?:es|será|sea|cambia\s*a|:)\s+([\d.,]+)\s*(?:kg|kilos?|toneladas?)?/ui', $lastUserMessage, $matches)) {
            $esEdicionSimple = true;
            $campoEditado = 'peso';
            $peso = self::normalizeWeight($matches[1]);
            // Convertir toneladas a kg si es necesario
            if (preg_match('/toneladas?/ui', $lastUserMessage)) {
                $peso = $peso * 1000;
            }
            $valorEditado = $peso;
        }
        // Detectar corrección de cantidad
        elseif (preg_match('/(?:la\s+)?cantidad\s*(?:es|será|sea|cambia\s*a|:)\s+(\d+)/ui', $lastUserMessage, $matches)) {
            $esEdicionSimple = true;
            $campoEditado = 'cantidad';
            $valorEditado = intval($matches[1]);
        }
        // Detectar corrección de valor declarado
        elseif (preg_match('/(?:el\s+)?valor(?:\s+declarado)?\s*(?:es|será|sea|cambia\s*a|:)\s+([\d.,]+)\s*(?:millones?)?/ui', $lastUserMessage, $matches)) {
            $esEdicionSimple = true;
            $campoEditado = 'valor';
            $valor = floatval(str_replace([',', '.'], ['', ''], $matches[1]));
            // Convertir millones si se menciona
            if (preg_match('/millones?/ui', $lastUserMessage)) {
                $valor = $valor * 1000000;
            }
            $valorEditado = $valor;
        }
        // 🚛 Detectar corrección de vehículo
        elseif (preg_match('/(?:el\s+)?veh[ií]culo\s*(?:es|será|sea|cambia\s*a|:)\s+([a-záéíóúñ\s]+?)(?:\s*[.,;]|$)/ui', $lastUserMessage, $matches)) {
            $esEdicionSimple = true;
            $campoEditado = 'vehiculo';
            $vehiculoRaw = strtolower(trim($matches[1]));
            // Normalizar nombre de vehículo
            $vehiculosMap = [
                'patineta' => 'PATINETA', 'camioneta' => 'CAMIONETA', 'sencillo' => 'SENCILLO',
                'turbo' => 'TURBO', 'dobletroque' => 'DOBLETROQUE', 'doble troque' => 'DOBLETROQUE',
                'minimula' => 'MINIMULA', 'mini mula' => 'MINIMULA', 'tractomula' => 'TRACTOMULA',
                'tracto mula' => 'TRACTOMULA', 'cuatro manos' => 'CUATRO MANOS'
            ];
            $valorEditado = $vehiculosMap[$vehiculoRaw] ?? strtoupper($vehiculoRaw);
        }
        
        if ($esEdicionSimple && $valorEditado !== null) {
            Log::info('🚀 EDICIÓN SIMPLE detectada - Sin llamar API', [
                'campo' => $campoEditado,
                'valor' => $valorEditado,
                'selected_route_index' => $selectedRouteIndex,
                'existing_routes_count' => $existingRoutesCount
            ]);
            
        // 🆕 DETECCIÓN EXPLÍCITA DE RUTA EN EL MENSAJE
        // Ejemplo: "cambiar producto de la ruta 2 a cemento"
        // Esto debe tener prioridad sobre la ruta seleccionada en el UI
        if (preg_match('/(?:ruta|opci[oó]n)\s*(\d+)/ui', $lastUserMessage, $rutaMatch)) {
            $rutaMencionada = intval($rutaMatch[1]) - 1; // Convertir a 0-based
            if (isset($extractedData[$rutaMencionada])) {
                $selectedRouteIndex = $rutaMencionada;
                Log::info('📍 Ruta detectada explícitamente en mensaje', ['ruta_index' => $selectedRouteIndex]);
            }
        }


            
            // Aplicar el cambio a los datos extraídos
            // 🆕 IMPORTANTE: Actualizar AMBOS nombres de campo para consistencia
            $fieldMappings = [
                'producto' => ['producto', 'tipo_producto'],
                'origen' => ['origen', 'ciudad_origen'],
                'destino' => ['destino', 'ciudad_destino'],
                'peso' => ['peso_kg', 'peso_mercancia', 'pesoMercancia'], // 🔧 FIX BUG #1: Sincronizar los 3 campos de peso
                'cantidad' => ['cantidad'],
                'valor' => ['valor_declarado'],
                'vehiculo' => ['vehiculo', 'claseVehiculo', 'vehiculo_requerido']
            ];
            
            $fieldNames = $fieldMappings[$campoEditado] ?? [$campoEditado];
            
            // Si hay una ruta seleccionada y es multi-ruta, aplicar solo a esa ruta
            $isMultiRouteData = isset($extractedData[0]) && is_array($extractedData[0]);
            
            // 🆕 PROTECCIÓN MULTI-RUTA:
            // Si es multi-ruta pero NO hay ruta seleccionada explícita (ni por UI ni por texto),
            // verificar si hay una ruta PENDIENTE en metadata.
            if ($isMultiRouteData && $selectedRouteIndex === null) {
                $metadata = json_decode($session->metadata ?? '{}', true);
                if (isset($metadata['producto_pendiente_ruta_index'])) {
                    $selectedRouteIndex = $metadata['producto_pendiente_ruta_index'];
                    Log::info('📍 Usando ruta pendiente de metadata para edición', ['ruta_index' => $selectedRouteIndex]);
                }
            }
            
            if ($isMultiRouteData && $selectedRouteIndex !== null && isset($extractedData[$selectedRouteIndex])) {
                foreach ($fieldNames as $fieldName) {
                    $extractedData[$selectedRouteIndex][$fieldName] = $valorEditado;
                }
                
                // 🆕 LIMPIEZA ESTRICTA: Si se edita producto, limpiar código SIEMPRE para forzar nueva validación
                if ($campoEditado === 'producto') {
                    $extractedData[$selectedRouteIndex]['producto_codigo'] = null;
                    $extractedData[$selectedRouteIndex]['producto_nombre'] = null; // Limpiar nombre oficial
                    $extractedData[$selectedRouteIndex]['tipo_producto'] = null;
                }
                
                Log::info("✏️ Campos actualizados en ruta {$selectedRouteIndex}", [
                    'campos' => $fieldNames,
                    'valor' => $valorEditado
                ]);
            } elseif ($isMultiRouteData) {
                // 🆕 PROTECCIÓN: Si es multi-ruta y NO se especificó ruta, NO aplicar a todas ciegamente.
                // Preguntar al usuario a cuál se refiere, O aplicar solo a la primera (decisión segura: preguntar).
                // Por ahora, aplicaremos a la primera ruta para evitar bloqueo, pero SIN aplicar a todas.
                
                // ⚠️ SOLO si es edición de producto, evitar aplicar a todas para no sobreescribir confirmados
                if ($campoEditado === 'producto') {
                     Log::warning('⚠️ Edición de producto en multi-ruta sin especificar ruta - Se aplicará a la PRIMERA ruta incompleta o la 0');
                     $targetIdx = self::findNextRouteWithoutProduct($extractedData) ?? 0;
                     
                     foreach ($fieldNames as $fieldName) {
                        $extractedData[$targetIdx][$fieldName] = $valorEditado;
                     }
                     // Limpiar códigos
                     $extractedData[$targetIdx]['producto_codigo'] = null;
                     $extractedData[$targetIdx]['producto_nombre'] = null;
                     $extractedData[$targetIdx]['tipo_producto'] = null;
                     
                     $selectedRouteIndex = $targetIdx; // Marcar para búsqueda posterior
                } else {
                    // Para otros campos (origen, destino, etc) mantenemos comportamiento "bulk" si el usuario no especifica,
                    // O podríamos restringirlo también. Por seguridad, restringimos también.
                    Log::warning('⚠️ Edición en multi-ruta sin especificar ruta - Se aplicará a la ruta 0 por defecto');
                    $targetIdx = 0;
                    foreach ($fieldNames as $fieldName) {
                        $extractedData[$targetIdx][$fieldName] = $valorEditado;
                    }
                }
            } else {
                // Ruta única
                foreach ($fieldNames as $fieldName) {
                    $extractedData[$fieldName] = $valorEditado;
                }
                
                // Limpiar códigos en ruta única
                if ($campoEditado === 'producto') {
                    $extractedData['producto_codigo'] = null;
                    $extractedData['producto_nombre'] = null;
                    $extractedData['tipo_producto'] = null;
                }
            }
            
            // 🆕 Si el campo editado es PRODUCTO, buscar en BD para obtener código
            if ($campoEditado === 'producto') {
                $productoInfo = self::getProductoFromDB($valorEditado);
                if ($productoInfo) {
                    if ($isMultiRouteData && $selectedRouteIndex !== null && isset($extractedData[$selectedRouteIndex])) {
                        // Aplicar solo a la ruta seleccionada
                        $extractedData[$selectedRouteIndex]['producto_codigo'] = $productoInfo['codigo'];
                        $extractedData[$selectedRouteIndex]['producto_nombre'] = $productoInfo['nombre'];
                        if ($productoInfo['tipo']) {
                            $extractedData[$selectedRouteIndex]['tipo_producto'] = $productoInfo['tipo'];
                        }
                    } elseif ($isMultiRouteData) {
                        // Este bloque ya no debería alcanzarse con la lógica de protección arriba
                        // pero lo mantenemos por seguridad defensiva
                    } else {
                        // Ruta única
                        $extractedData['producto_codigo'] = $productoInfo['codigo'];
                        $extractedData['producto_nombre'] = $productoInfo['nombre'];
                        if ($productoInfo['tipo']) {
                            $extractedData['tipo_producto'] = $productoInfo['tipo'];
                        }
                    }
                    Log::info('✅ Producto buscado en BD (edición simple)', [
                        'producto' => $valorEditado,
                        'producto_codigo' => $productoInfo['codigo'],
                        'ruta_editada' => $selectedRouteIndex !== null ? $selectedRouteIndex : 'TODAS'
                    ]);
                }
            }
            
            // Guardar datos en el grupo
            if ($groupId) {
                $group = GroupCotization::find($groupId);
                if ($group) {
                    $group->extracted_data = json_encode($extractedData);
                    $group->save();
                    
                    Log::info('📦 Datos actualizados en GRUPO (edición simple)', [
                        'group_id' => $groupId,
                        'campo' => $campoEditado,
                        'valor' => $valorEditado
                    ]);
                    
                    // 🔧 FIX: También actualizar cotizacion_models si existe
                    if ($isMultiRouteData && $selectedRouteIndex !== null && isset($extractedData[$selectedRouteIndex])) {
                        // Es multi-ruta, actualizar solo la ruta seleccionada
                        $cotizaciones = \App\Models\CotizacionModel::where('group_cotization_id', $groupId)
                            ->orderBy('id')
                            ->get();
                        
                        if (isset($cotizaciones[$selectedRouteIndex])) {
                            $cotizacion = $cotizaciones[$selectedRouteIndex];
                            
                            // Actualizar campos según el tipo de edición
                            if ($campoEditado === 'vehiculo') {
                                // 🔧 FIX BUG #2: Solo actualizar vehículo, NUNCA tocar producto
                                $cotizacion->vehiculo_requerido = $valorEditado;
                                Log::info('✅ Vehículo actualizado en cotizacion_models (edición simple, SIN tocar producto)', [
                                    'vehiculo' => $valorEditado,
                                    'producto_mantiene' => $cotizacion->tipo_producto
                                ]);
                            } elseif ($campoEditado === 'producto') {
                                // 🔧 FIX BUG #2: Solo actualizar producto, NUNCA tocar vehículo
                                $cotizacion->tipo_producto = $valorEditado;
                                Log::info('✅ Producto actualizado en cotizacion_models (edición simple, SIN tocar vehículo)', [
                                    'producto' => $valorEditado,
                                    'vehiculo_mantiene' => $cotizacion->vehiculo_requerido
                                ]);
                            } elseif ($campoEditado === 'peso') {
                                // 🔧 FIX BUG #1: Actualizar peso_mercancia en cotizacion_models
                                $cotizacion->peso_mercancia = $valorEditado;
                                Log::info('✅ Peso actualizado en cotizacion_models (edición simple)', [
                                    'peso_nuevo' => $valorEditado
                                ]);
                            } elseif ($campoEditado === 'cantidad') {
                                $cotizacion->cantidad = $valorEditado;
                            } elseif ($campoEditado === 'valor') {
                                $cotizacion->valor_declarado = $valorEditado;
                            } elseif ($campoEditado === 'origen') {
                                $cotizacion->ciudad_origen = $valorEditado;
                            } elseif ($campoEditado === 'destino') {
                                $cotizacion->ciudad_destino = $valorEditado;
                            }
                            
                            $cotizacion->save();
                            
                            Log::info('✅ cotizacion_models ACTUALIZADA (edición simple)', [
                                'cotizacion_id' => $cotizacion->id,
                                'campo' => $campoEditado,
                                'valor' => $valorEditado
                            ]);
                        }
                    }
                }
            }
            
            // Guardar mensaje de respuesta
            $respuestaSimple = "¡Entendido! He actualizado el {$campoEditado} a \"{$valorEditado}\".";
            if ($selectedRouteIndex !== null) {
                $respuestaSimple = "¡Entendido! He actualizado el {$campoEditado} de la Ruta " . ($selectedRouteIndex + 1) . " a \"{$valorEditado}\".";
            }
            
            ConversationMessage::create([
                'session_id' => $session->id,
                'group_cotization_id' => $groupId,
                'role' => 'assistant',
                'content' => $respuestaSimple,
                'timestamp' => now()
            ]);
            
            $runId = 'run_quick_edit_' . time();
            
            // Guardar metadata
            $metadata = json_decode($session->metadata ?? '{}', true);
            $metadata['last_run_id'] = $runId;
            $metadata['last_run_status'] = 'completed';
            // 🆕 SINCRONIZAR quote_data y extracted_data para evitar inconsistencias
            $metadata['quote_data'] = $extractedData;
            $metadata['extracted_data'] = $extractedData;
            $session->metadata = json_encode($metadata);
            $session->save();
            
            return [
                'id' => $runId,
                'status' => 'completed_with_data',
                'extracted_data' => $extractedData,
                'is_single_route_edit' => $isMultiRouteData && $selectedRouteIndex !== null, // 🆕 Indicar al frontend
                'edited_route_index' => $selectedRouteIndex // 🆕 Qué ruta se editó
            ];
        }

        // Construir system prompt según tipo de negocio
        $systemPrompt = self::getSystemPrompt($typeBusiness, $detectedEmpaque, $extractedData);

        // Agregar system prompt al inicio
        array_unshift($messages, [
            'role' => 'system',
            'content' => $systemPrompt
        ]);

        // Definir las herramientas MCP disponibles
        $tools = self::getMCPTools();

        Log::info('Ejecutando asistente MCP', [
            'thread_id' => $threadId,
            'type_business' => $typeBusiness,
            'messages_count' => count($messages),
            'tools_count' => count($tools),
            'detected_empaque' => $detectedEmpaque,
            'extracted_fields' => count($extractedData)
        ]);

        try {
            // Seleccionar proveedor de IA según configuración
            if (self::$chat_provider === 'groq') {
                // 🚀 GROQ API con modelo agéntico compound-beta
                $payload = [
                    'model' => self::$groq_model,
                    'messages' => $messages,
                    'temperature' => 0.3,
                    'max_tokens' => 4096, // Groq soporta más tokens
                    'tools' => $tools,
                    'tool_choice' => 'auto',
                ];
                
                Log::info('Groq API Request (agéntico)', [
                    'model' => self::$groq_model,
                    'provider' => 'groq',
                    'messages_count' => count($messages)
                ]);

                $response = Http::timeout(60) // Más tiempo para compound-beta
                    ->retry(3, 2000, function ($exception, $request) {
                        // Retry en errores de conexión, timeout, o 5xx
                        if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
                            Log::warning('Groq API: Error de conexión, reintentando...', [
                                'error' => $exception->getMessage()
                            ]);
                            return true;
                        }
                        
                        if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                            $response = $exception->response;
                            if ($response && $response->status() >= 500) {
                                Log::warning('Groq API: Error 5xx, reintentando...', [
                                    'status' => $response->status()
                                ]);
                                return true;
                            }
                        }
                        
                        return false;
                    }, true) // throw = true para que lance excepción después de reintentos
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . self::$groq_api_key,
                        'Content-Type' => 'application/json',
                    ])
                    ->post('https://api.groq.com/openai/v1/chat/completions', $payload)
                    ->json();
            } else {
                // OpenAI GPT-5 API (respaldo)
                // GPT-5 no acepta temperature ni max_tokens personalizados
                $payload = [
                    'model' => self::$openai_model,
                    'messages' => $messages,
                    // 'temperature' => 0.3, // GPT-5 no acepta este parámetro
                    'max_completion_tokens' => 2000,
                    'tools' => $tools,
                    'tool_choice' => 'auto',
                    'parallel_tool_calls' => true,
                ];
                
                Log::info('OpenAI GPT-5 API Request', [
                    'model' => self::$openai_model,
                    'provider' => 'openai'
                ]);

                $response = Http::timeout(30)
                    ->retry(3, 2000, function ($exception, $request) {
                        // Retry en errores de conexión, timeout, o 5xx
                        if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
                            Log::warning('OpenAI API: Error de conexión, reintentando...', [
                                'error' => $exception->getMessage()
                            ]);
                            return true;
                        }
                        
                        if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                            $response = $exception->response;
                            if ($response && $response->status() >= 500) {
                                Log::warning('OpenAI API: Error 5xx, reintentando...', [
                                    'status' => $response->status()
                                ]);
                                return true;
                            }
                        }
                        
                        return false;
                    }, true)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . self::$openai_api_key,
                        'Content-Type' => 'application/json',
                    ])
                    ->post('https://api.openai.com/v1/chat/completions', $payload)
                    ->json();
            }
            
            $assistantMessage = $response['choices'][0]['message'] ?? null;

            Log::info('Mensaje del asistente ChatGPT recibido', [
                'has_tool_calls' => isset($assistantMessage['tool_calls']) && !empty($assistantMessage['tool_calls']),
                'has_content' => !empty($assistantMessage['content'])
            ]);

            // Verificar si hay tool calls
            if (isset($assistantMessage['tool_calls']) && !empty($assistantMessage['tool_calls'])) {
                return self::processToolCalls($threadId, $assistantMessage, $response, $extractedData, $groupId, $selectedRouteIndex);
            }

            // Si no hay tool calls, guardar respuesta normal
            if (isset($assistantMessage['content'])) {
                // 🆕 PRIORIDAD: usar $groupId del request, luego metadata
                $metadata = json_decode($session->metadata ?? '{}', true);
                $currentGroupId = $groupId ?? $metadata['current_group_id'] ?? null;
                
                // 🆕 CRÍTICO FIX #554 & #557: Si el contenido es JSON, parsearlo y actualizar extractedData
                // OpenAI puede responder con datos estructurados que deben guardarse en BD
                $contenido = $assistantMessage['content'];
                if (preg_match('/^\s*\{/', $contenido) && preg_match('/\}\s*$/', $contenido)) {
                    try {
                        $jsonData = json_decode($contenido, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                            // Extraer campos relevantes del JSON de OpenAI
                            $camposRelevantes = ['origen', 'destino', 'peso', 'producto', 'valor', 'cantidad', 'contenedor', 'observaciones', 'vehiculo', 'empaque'];
                            $datosAI = [];
                            foreach ($camposRelevantes as $campo) {
                                if (isset($jsonData[$campo]) && $jsonData[$campo] !== null && $jsonData[$campo] !== 'null') {
                                    $datosAI[$campo] = $jsonData[$campo];
                                }
                            }
                            
                            if (!empty($datosAI)) {
                                // Normalizar nombres de campos para BD
                                if (isset($datosAI['valor'])) {
                                    $datosAI['valor_declarado'] = $datosAI['valor'];
                                    unset($datosAI['valor']);
                                }
                                
                                // 🔧 FIX CRÍTICO: NO sobrescribir peso si ya existe uno correcto
                                // OpenAI convierte "12.400" (español: 12400) a 12.4 incorrectamente
                                if (isset($datosAI['peso'])) {
                                    $pesoOpenAI = self::normalizeWeight($datosAI['peso']);
                                    $pesoExistente = isset($extractedData['peso_kg']) ? (int)$extractedData['peso_kg'] : 0;
                                    
                                    // Solo usar peso de OpenAI si:
                                    // 1. No hay peso existente, O
                                    // 2. El peso de OpenAI es mayor o similar al existente (no 100x menor)
                                    if ($pesoExistente == 0 || $pesoOpenAI >= $pesoExistente * 0.9) {
                                        $datosAI['peso_kg'] = $pesoOpenAI;
                                        Log::info('📊 Peso de OpenAI aceptado', [
                                            'peso_openai' => $pesoOpenAI,
                                            'peso_existente' => $pesoExistente
                                        ]);
                                    } else {
                                        // OpenAI envió un peso sospechosamente bajo, ignorarlo
                                        Log::warning('⚠️ Peso de OpenAI rechazado (sospechosamente bajo)', [
                                            'peso_openai_malo' => $pesoOpenAI,
                                            'peso_existente_bueno' => $pesoExistente
                                        ]);
                                        unset($datosAI['peso']);
                                    }
                                }
                                
                                // Mergear con extractedData existente 
                                // PERO no sobrescribir peso_kg si ya existe un valor válido
                                $pesoBackup = $extractedData['peso_kg'] ?? null;
                                $extractedData = array_merge($extractedData, $datosAI);
                                
                                // Restaurar peso si fue sobrescrito con valor malo
                                if ($pesoBackup !== null && isset($datosAI['peso_kg']) && $datosAI['peso_kg'] < $pesoBackup * 0.9) {
                                    $extractedData['peso_kg'] = $pesoBackup;
                                    $extractedData['pesoMercancia'] = $pesoBackup;
                                    $extractedData['peso_mercancia'] = $pesoBackup;
                                }
                                
                                Log::info('📊 Datos adicionales extraídos de respuesta JSON de OpenAI', [
                                    'campos_agregados' => array_keys($datosAI),
                                    'total_campos' => count($extractedData)
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('Error parseando JSON de OpenAI', ['error' => $e->getMessage()]);
                    }
                }
                
                // 🆕 FIX #557: Complementar extracción con análisis de texto descriptivo
                // Si REGEX no detectó vehículo pero el asistente lo mencionó, extraerlo
                if (empty($extractedData['vehiculo']) && !empty($contenido)) {
                    $vehiculosPatrones = [
                        'SENCILLO' => '/\bsencillo\b/iu',
                        'DOBLETROQUE' => '/\bdoble\s*troque\b/iu',
                        'TRACTOMULA' => '/\btractomula\b/iu',
                        'TURBO' => '/\bturbo\b/iu',
                        'PATINETA' => '/\bpatineta\b/iu',
                        'MINIMULA' => '/\bminimula\b/iu'
                    ];
                    
                    foreach ($vehiculosPatrones as $vehiculo => $patron) {
                        if (preg_match($patron, $contenido)) {
                            $extractedData['vehiculo'] = $vehiculo;
                            Log::info('🚚 Vehículo extraído de respuesta descriptiva', ['vehiculo' => $vehiculo]);
                            break;
                        }
                    }
                }
                
                // 🆕 FILTRAR mensajes técnicos que confunden al usuario
                $contenido = $assistantMessage['content'];
                $mensajesTecnicos = [
                    '/pricing_id/i',
                    '/pricing id/i',
                    '/pricingid/i',
                    '/ID de precios/i',
                    '/identificador de precios/i',
                    '/tarifa.*pricing/i',
                    '/error.*validation/i',
                    '/SQLSTATE/i',
                    '/Exception/i',
                    '/no tengo.*capacidad/i',
                    '/IA desarrollada por OpenAI/i',
                    '/como.*IA.*no.*acceso/i',
                    '/no puedo conectar.*sistemas/i',
                    '/no tengo acceso.*bases de datos/i',
                    '/simulación.*límites/i',
                    '/requisito.*pricing/i',
                    '/plataforma simulada/i',
                    '/sistema simulado/i',
                    '/entorno de prueba/i',
                    '/no puedo generar.*IDs/i',
                    '/no puedo generar.*ids/i',
                    '/necesitarías contar con acceso/i',
                    '/parámetros adecuados/i',
                    '/información sobre el ID/i',
                    '/no proporcione información/i',
                    '/no puedo acceder ni generar/i',
                    '/completar transacciones sin/i',
                    // 🆕 NUEVOS PATRONES - Bloquear mensajes de "no puedo"
                    '/no puedo generar cotizaciones/i',
                    '/no incluye la ejecución de funcionalidades/i',
                    '/sistemas externos reales/i',
                    '/capacidad actual no incluye/i',
                    '/mi capacidad.*no incluye/i',
                    '/en un contexto real/i',
                    '/contactar.*proveedores/i',
                    '/plataforma.*real/i',
                    '/malentendido en la solicitud/i',
                    '/parece que hay un malentendido/i',
                    '/no tengo la capacidad/i',
                    '/funcionalidades específicas/i',
                    '/no estoy conectado/i',
                    '/no tengo conexión/i',
                    '/no puedo realizar.*transacciones/i',
                    '/fuera de mis capacidades/i',
                    '/más allá de mis capacidades/i',
                ];
                
                $esMessageTecnico = false;
                foreach ($mensajesTecnicos as $patron) {
                    if (preg_match($patron, $contenido)) {
                        $esMessageTecnico = true;
                        Log::warning('🚫 Mensaje técnico bloqueado', [
                            'patron' => $patron,
                            'contenido_preview' => substr($contenido, 0, 100)
                        ]);
                        break;
                    }
                }
                
                // Si es mensaje técnico, reemplazarlo con uno amigable
                if ($esMessageTecnico) {
                    Log::warning('⚠️ Mensaje técnico detectado, reemplazando con mensaje amigable', [
                        'original' => substr($contenido, 0, 200)
                    ]);
                    $contenido = "Perfecto, tengo todos los datos. ¿Deseas crear la cotización ahora?";
                }
                
                ConversationMessage::create([
                    'session_id' => $session->id,
                    'group_cotization_id' => $currentGroupId, // 🆕 Usar group_id del request
                    'role' => 'assistant',
                    'content' => $contenido,
                    'timestamp' => now()
                ]);
            }

            // Crear run ID ficticio para compatibilidad
            $runId = 'run_mcp_' . time();
            
            Log::info('Creando run_id para respuesta con datos extraídos', [
                'run_id' => $runId,
                'thread_id' => $threadId,
                'extracted_data_count' => count($extractedData)
            ]);
            
            // Guardar run_id en metadata de sesión
            $metadata = json_decode($session->metadata ?? '{}', true);
            $metadata['last_run_id'] = $runId;
            $metadata['last_run_status'] = 'completed';
            $metadata['last_run_at'] = now()->toIso8601String();
            
            // 🆕 GUARDAR extracted_data EN EL GRUPO, no en la sesión compartida
            // PRIORIDAD: $groupId del request > metadata
            $currentGroupId = $groupId ?? $metadata['current_group_id'];
            if ($currentGroupId && !empty($extractedData)) {
                $group = GroupCotization::find($currentGroupId);
                if ($group) {
                    // 🔧 FIX #562: MERGE con datos existentes en lugar de sobrescribir
                    // 🔧 FIX: Usar + para preservar índices numéricos en lugar de array_merge que los re-indexa
                    $existingData = json_decode($group->extracted_data ?? '{}', true) ?? [];
                    
                    // Separar datos por tipo: índices numéricos (rutas) vs campos planos
                    $existingRoutes = [];
                    $existingFlat = [];
                    foreach ($existingData as $key => $value) {
                        if (is_numeric($key)) {
                            $existingRoutes[$key] = $value;
                        } else {
                            $existingFlat[$key] = $value;
                        }
                    }
                    
                    $newRoutes = [];
                    $newFlat = [];
                    foreach ($extractedData as $key => $value) {
                        if (is_numeric($key)) {
                            $newRoutes[$key] = $value;
                        } else {
                            $newFlat[$key] = $value;
                        }
                    }
                    
                    // 🔧 CRÍTICO: Si hay múltiples rutas (2+), NO guardar campos planos
                    // Los campos planos son solo para ruta única
                    $isMultiRoute = count($newRoutes) >= 2 || count($existingRoutes) >= 2;
                    
                    // 🔧 Merge inteligente: PRESERVAR campos importantes como incluye_tara
                    $mergedRoutes = $existingRoutes; // Empezar con rutas existentes
                    foreach ($newRoutes as $idx => $routeData) {
                        if (isset($mergedRoutes[$idx]) && is_array($mergedRoutes[$idx])) {
                            // 🔴 CRÍTICO: Preservar incluye_tara si ya existe y es true
                            $existingRoute = $mergedRoutes[$idx];
                            if (isset($existingRoute['incluye_tara']) && $existingRoute['incluye_tara'] === true) {
                                $routeData['incluye_tara'] = true;
                                Log::info('✅ Preservando incluye_tara=true en merge de rutas', ['ruta' => $idx]);
                            }
                            // Merge: existente + nuevo (nuevo tiene prioridad excepto incluye_tara)
                            $mergedRoutes[$idx] = array_merge($existingRoute, $routeData);
                        } else {
                            $mergedRoutes[$idx] = $routeData; // Nueva ruta
                        }
                    }
                    
                    // Merge: campos planos SOLO si es ruta única
                    $mergedFlat = [];
                    if (!$isMultiRoute) {
                        $mergedFlat = array_merge($existingFlat, $newFlat);
                    } else {
                        Log::info('🚫 Multi-ruta detectada: NO guardando campos planos', [
                            'rutas_totales' => count($mergedRoutes),
                            'campos_planos_rechazados' => array_keys($newFlat)
                        ]);
                    }
                    
                    // Combinar rutas + campos planos (si aplica)
                    $mergedData = $mergedRoutes + $mergedFlat;
                    
                    $group->extracted_data = json_encode($mergedData);
                    $group->save();
                    
                    Log::info('📦 Datos extraídos guardados en GRUPO (con merge inteligente)', [
                        'group_id' => $currentGroupId,
                        'rutas_previas' => count($existingRoutes),
                        'rutas_nuevas' => count($newRoutes),
                        'rutas_finales' => count($mergedRoutes),
                        'campos_planos' => array_keys($mergedFlat)
                    ]);
                }
            }
            
            // También guardar en metadata de sesión para compatibilidad (pero indexado por grupo)
            if (!isset($metadata['extracted_data_by_group'])) {
                $metadata['extracted_data_by_group'] = [];
            }
            if ($currentGroupId) {
                $metadata['extracted_data_by_group'][$currentGroupId] = $extractedData;
            }
            
            $session->metadata = json_encode($metadata);
            $session->save();

            return [
                'id' => $runId,
                'status' => 'completed',
                'extracted_data' => $extractedData  // NUEVO: enviar datos al frontend
            ];

        } catch (\Exception $e) {
            Log::error('Error ejecutando asistente MCP', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'thread_id' => $threadId,
                'error_type' => get_class($e)
            ]);
            
            // Detectar tipo de error para mensajes más específicos
            $errorMessage = $e->getMessage();
            
            if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
                throw new \Exception('No se pudo conectar con el servicio de IA. Verifica tu conexión a internet e intenta nuevamente.');
            }
            
            if ($e instanceof \Illuminate\Http\Client\RequestException) {
                $response = $e->response;
                if ($response && $response->status() === 429) {
                    throw new \Exception('El servicio de IA está recibiendo muchas solicitudes. Espera unos segundos e intenta nuevamente.');
                }
                if ($response && $response->status() >= 500) {
                    throw new \Exception('El servicio de IA está experimentando problemas temporales. Intenta nuevamente en unos momentos.');
                }
            }
            
            if (str_contains($errorMessage, 'timeout') || str_contains($errorMessage, 'timed out')) {
                throw new \Exception('La solicitud tardó demasiado tiempo. Intenta con un mensaje más corto o espera un momento.');
            }
            
            if (str_contains($errorMessage, 'Could not resolve host') || str_contains($errorMessage, 'DNS')) {
                throw new \Exception('No se pudo resolver el servidor. Verifica tu conexión a internet.');
            }
            
            // Error genérico
            throw new \Exception('Error procesando tu mensaje: ' . $errorMessage);
        }
    }

    /**
     * Procesar tool calls de OpenAI
     * @param int|null $selectedRouteIndex Índice de ruta seleccionada para aplicar productos
     */
    private static function processToolCalls(
        $threadId, 
        $assistantMessage, 
        $openaiResponse, 
        $extractedData = [], 
        $groupId = null, 
        $selectedRouteIndex = null
    )
    {
        $session = ConversationSession::where('session_id', $threadId)->first();
        $toolCalls = $assistantMessage['tool_calls'];
        
        // 🆕 CONVERTIR tool_calls a arrays si vienen como objetos
        $toolCalls = array_map(function($tc) {
            if (is_object($tc)) {
                return json_decode(json_encode($tc), true);
            }
            return $tc;
        }, $toolCalls);
        
        // 🆕 Usar $groupId del parámetro, con fallback a metadata
        $metadata = json_decode($session->metadata ?? '{}', true);
        $currentGroupId = $groupId ?? $metadata['current_group_id'] ?? null;

        Log::info('Procesando tool calls', [
            'thread_id' => $threadId,
            'tool_calls_count' => count($toolCalls)
        ]);

        $toolResults = [];
        $hasMultipleProducts = false;
        $searchProductsFound = false;
        
        // 🆕 DETECTAR si el mensaje del usuario es una CORRECCIÓN de otro campo (no producto)
        // Si es así, NO permitir llamadas a search_products
        // Obtener el último mensaje del usuario desde la sesión
        $lastUserMessage = '';
        $conversationMessages = ConversationMessage::where('session_id', $session->id)
            ->where('role', 'user')
            ->orderBy('id', 'desc')
            ->first();
        
        if ($conversationMessages) {
            $lastUserMessage = strtolower($conversationMessages->content ?? '');
        }
        
        // Patrones que indican corrección de campos NO-producto
        $isNonProductCorrection = preg_match('/\b(?:
            empaque\s*(?:es|son|sea|será|cambialo|cambia)|
            (?:el\s+)?empaque\s*(?:es|son)|
            origen\s*(?:es|sea|será|cambialo|cambia)|
            destino\s*(?:es|sea|será|cambialo|cambia)|
            (?:cambia|cambiar)\s*(?:el\s+)?(?:origen|destino|empaque)|
            (?:el\s+)?peso\s*(?:es|son|sea|será)|
            (?:el\s+)?valor\s*(?:es|son|sea|será)|
            (?:la\s+)?cantidad\s*(?:es|son|sea|será)|
            (?:agrega|incluye|incluir|agregar)\s*(?:la\s+)?tara
        )\b/uix', $lastUserMessage);
        
        $blockSearchProducts = false;
        if ($isNonProductCorrection) {
            Log::info('🚫 Mensaje detectado como CORRECCIÓN de campo no-producto, se bloquearán llamadas a search_products', [
                'mensaje' => $lastUserMessage
            ]);
            $blockSearchProducts = true;
        }

        // PRIMERA PASADA: Detectar si hay búsqueda de productos con múltiples resultados
        foreach ($toolCalls as $toolCall) {
            $functionName = $toolCall['function']['name'];
            $arguments = json_decode($toolCall['function']['arguments'], true);
            
            // 🆕 SIEMPRE mantener como array, nunca stdClass
            if (empty($arguments) || !is_array($arguments)) {
                $arguments = [];
            }

            Log::info('Pre-validación herramienta MCP', [
                'function' => $functionName,
                'arguments' => $arguments
            ]);

            // 🆕 BLOQUEAR search_products si el mensaje es una corrección de campo no-producto
            if ($functionName === 'search_products' && $blockSearchProducts) {
                Log::info('🚫 BLOQUEADO: search_products durante corrección de campo no-producto');
                continue; // Saltar esta herramienta en la primera pasada
            }

            // Ejecutar search_products para validar
            if ($functionName === 'search_products') {
                $tempResult = self::callMCPTool($functionName, $arguments);
                $searchProductsFound = true;
                
                // Verificar si devolvió múltiples productos
                if (isset($tempResult['productos']) && is_array($tempResult['productos'])) {
                    $productCount = count($tempResult['productos']);
                    if ($productCount > 1) {
                        $hasMultipleProducts = true;
                        Log::warning('⚠️ VALIDACIÓN: Se encontraron múltiples productos', [
                            'cantidad' => $productCount,
                            'bloqueando_create_cotizacion' => true
                        ]);
                    }
                }
            }
        }

        // SEGUNDA PASADA: Ejecutar herramientas con validación
        foreach ($toolCalls as $toolCall) {
            $functionName = $toolCall['function']['name'];
            $arguments = json_decode($toolCall['function']['arguments'], true);
            
            // 🆕 SIEMPRE mantener como array, nunca stdClass
            if (empty($arguments) || !is_array($arguments)) {
                $arguments = [];
            }

            // ⛔ BLOQUEAR create_cotizacion si hay múltiples productos sin seleccionar
            if ($functionName === 'create_cotizacion' && $hasMultipleProducts) {
                Log::error('⛔ BLOQUEADO: Intento de crear cotización con múltiples productos sin seleccionar', [
                    'productos_encontrados' => 'múltiples',
                    'accion' => 'bloqueado'
                ]);
                
                $result = [
                    'error' => 'VALIDACIÓN REQUERIDA',
                    'message' => 'Hay múltiples opciones de productos disponibles. El usuario debe seleccionar una opción específica antes de crear la cotización.',
                    'blocked' => true
                ];
                
                $toolResults[] = [
                    'tool_call_id' => $toolCall['id'],
                    'role' => 'tool',
                    'name' => $functionName,
                    'content' => json_encode($result)
                ];
                
                // $currentGroupId ya está definido al inicio de processToolCalls
                ConversationMessage::create([
                    'session_id' => $session->id,
                    'group_cotization_id' => $currentGroupId, // 🆕 Agregar group_id
                    'role' => 'tool',
                    'content' => json_encode($result),
                    'metadata' => json_encode([
                        'tool_call_id' => $toolCall['id'],
                        'function' => $functionName,
                        'arguments' => $arguments,
                        'blocked' => true,
                        'reason' => 'multiple_products_pending_selection'
                    ]),
                    'timestamp' => now()
                ]);
                
                continue; // Saltar al siguiente tool call
            }

            // 🆕 BLOQUEAR search_products si el mensaje es una corrección de campo no-producto
            if ($functionName === 'search_products' && $blockSearchProducts) {
                Log::info('🚫 BLOQUEADO: search_products ignorado durante corrección de campo no-producto');
                
                $result = [
                    'message' => 'Búsqueda de productos omitida porque el mensaje es una corrección de otro campo.',
                    'skipped' => true
                ];
                
                $toolResults[] = [
                    'tool_call_id' => $toolCall['id'],
                    'role' => 'tool',
                    'name' => $functionName,
                    'content' => json_encode($result)
                ];
                
                continue; // Saltar al siguiente tool call
            }

            Log::info('Ejecutando herramienta MCP', [
                'function' => $functionName,
                'arguments' => $arguments
            ]);

            // 🔧 FIX: Agregar pricing_id por defecto si es create_cotizacion y no está presente
            if ($functionName === 'create_cotizacion' && !isset($arguments['pricing_id'])) {
                $arguments['pricing_id'] = 43214; // Default pricing ID
                Log::info('📝 pricing_id agregado por defecto', ['pricing_id' => 43214]);
            }

            // 🔧 FIX CRÍTICO: Aplicar TARA al peso antes de crear cotización
            // OpenAI extrae el peso del mensaje del usuario sin aplicar tara
            // Debemos interceptar y aplicar la tara aquí
            if ($functionName === 'create_cotizacion' && isset($arguments['peso_mercancia'])) {
                // 🔧 FIX CRÍTICO: Verificar PRIMERO si ya tenemos datos extraídos con peso_kg
                // El peso_kg en extractedData YA tiene la tara calculada correctamente
                $taraYaDecidida = false;
                $taraYaIncluidaEnDatos = false;
                $pesoConTaraYaCalculado = null;
                
                // Verificar en extractedData si ya se decidió sobre la tara
                if (!empty($extractedData)) {
                    $isMultiRoute = isset($extractedData[0]) && is_array($extractedData[0]);
                    if ($isMultiRoute) {
                        // Para múltiples rutas, verificar la ruta correspondiente
                        foreach ($extractedData as $idx => $ruta) {
                            if (isset($ruta['incluye_tara'])) {
                                $taraYaDecidida = true;
                                $taraYaIncluidaEnDatos = $ruta['incluye_tara'] === true;
                                // 🆕 FIX: Obtener el peso_kg que ya tiene la tara calculada
                                $pesoConTaraYaCalculado = $ruta['peso_kg'] ?? $ruta['peso'] ?? null;
                                Log::info('✅ Tara YA DECIDIDA en extractedData (multi-ruta)', [
                                    'ruta' => $idx,
                                    'incluye_tara' => $taraYaIncluidaEnDatos,
                                    'peso_kg_con_tara' => $pesoConTaraYaCalculado
                                ]);
                                break;
                            }
                        }
                    } else {
                        if (isset($extractedData['incluye_tara'])) {
                            $taraYaDecidida = true;
                            $taraYaIncluidaEnDatos = $extractedData['incluye_tara'] === true;
                            // 🆕 FIX: Obtener el peso_kg que ya tiene la tara calculada
                            $pesoConTaraYaCalculado = $extractedData['peso_kg'] ?? $extractedData['peso'] ?? null;
                            Log::info('✅ Tara YA DECIDIDA en extractedData (ruta única)', [
                                'incluye_tara' => $taraYaIncluidaEnDatos,
                                'peso_kg_con_tara' => $pesoConTaraYaCalculado
                            ]);
                        }
                    }
                }
                
                // Si ya se decidió sobre la tara, usar el peso que YA tiene la tara calculada
                if ($taraYaDecidida && $taraYaIncluidaEnDatos && $pesoConTaraYaCalculado) {
                    // 🆕 FIX CRÍTICO: Usar el peso_kg de extractedData que YA tiene la tara sumada
                    // NO usar el peso de OpenAI porque OpenAI envía el peso sin tara
                    $arguments['peso_mercancia'] = (string) intval($pesoConTaraYaCalculado);
                    Log::info('✅ TARA ya incluida según extractedData - usando peso_kg calculado', [
                        'peso_openai_sin_tara' => $arguments['peso_mercancia'] ?? 'N/A',
                        'peso_extractedData_con_tara' => $pesoConTaraYaCalculado
                    ]);
                } else {
                    // Buscar en TODOS los mensajes del usuario para detectar "con tara incluida"
                    $allUserMsgs = '';
                    if (isset($session)) {
                        $allUserMessages = ConversationMessage::where('session_id', $session->id)
                            ->where('role', 'user')
                            ->orderBy('timestamp', 'asc')
                            ->get();
                        foreach ($allUserMessages as $msg) {
                            $allUserMsgs .= ' ' . ($msg->content ?? '');
                        }
                    }
                    
                    // 🆕 FIX CRÍTICO: Re-extraer peso del mensaje original del usuario
                    // Porque OpenAI convierte "12.400" (español: 12400) a "12.4" (decimal) incorrectamente
                    $pesoExtraidoDelMensaje = self::extractPeso($allUserMsgs);
                    $pesoDeOpenAI = self::normalizeWeight($arguments['peso_mercancia']);
                    
                    // Usar el peso extraído del mensaje si es mayor que el de OpenAI
                    if ($pesoExtraidoDelMensaje !== null && $pesoExtraidoDelMensaje > $pesoDeOpenAI * 100) {
                        $pesoOriginal = $pesoExtraidoDelMensaje;
                        Log::info('🔧 Peso re-extraído del mensaje original (OpenAI interpretó mal formato español)', [
                            'peso_openai_mal' => $pesoDeOpenAI,
                            'peso_extraido_correcto' => $pesoExtraidoDelMensaje
                        ]);
                    } else {
                        $pesoOriginal = $pesoDeOpenAI;
                    }
                    
                    // 🔧 FIX CRÍTICO: Detectar "sin tara" PRIMERO (tiene MAYOR prioridad)
                    // Si dice "sin tara", SIEMPRE sumar tara, no importa otros patrones
                    $sinTara = preg_match('/(?:no\s+incluye|sin)\s*(?:la\s+)?tara|\+\s*tara|m[aá]s\s+tara|peso\s+neto/ui', $allUserMsgs);
                    
                    // 🔧 REGEX para detectar "con tara incluida" (SOLO si NO dice "sin tara")
                    $taraYaIncluida = !$sinTara && preg_match('/(?:con\s+(?:la\s+)?tara\s+incluida|tara\s+(?:ya\s+)?incluida|(?:ya\s+)?(?:incluye|tiene)\s+(?:la\s+)?tara|con\s+tara(?!\s+(?:no|sin))|peso\s+bruto|(?:kilos?|kilogramos?|kg)\s+con\s+tara)/ui', $allUserMsgs);
                    
                    Log::info('🔍 Análisis de tara en create_cotizacion', [
                        'sin_tara_detectado' => (bool) $sinTara,
                        'tara_ya_incluida_detectado' => (bool) $taraYaIncluida,
                        'peso_original' => $pesoOriginal,
                        'mensaje_preview' => substr($allUserMsgs, 0, 150)
                    ]);
                    
                    // 🔧 PRIORIDAD CORREGIDA: "sin tara" tiene prioridad sobre "con tara incluida"
                    // 🔧 FIX v2: Determinar tara según tipo de contenedor
                    // PRIMERO verificar en el tipo_embalaje (más confiable)
                    // LUEGO verificar en el mensaje del usuario
                    $taraAplicar = 3400; // Default
                    
                    // Verificar tipo_embalaje en los argumentos
                    $tipoEmbalaje = $arguments['tipo_embajale'] ?? $arguments['tipo_embalaje'] ?? '';
                    if (preg_match('/CONTENEDOR\s*20|20\s*pies/i', $tipoEmbalaje)) {
                        $taraAplicar = 2300;
                        Log::info('📦 Tara 2300 detectada por tipo_embalaje', ['tipo_embalaje' => $tipoEmbalaje]);
                    } elseif (preg_match('/contenedor\s+de\s+20|1x20|2x20|\b20\s*pies/ui', $allUserMsgs)) {
                        $taraAplicar = 2300;
                        Log::info('📦 Tara 2300 detectada en mensaje del usuario');
                    } else {
                        // Fallback a la función getTaraByContenedor
                        $taraAplicar = self::getTaraByContenedor($allUserMsgs);
                    }
                    
                    if ($sinTara) {
                        // Si dice "sin tara", SUMAR tara según contenedor
                        $pesoConTara = $pesoOriginal + $taraAplicar;
                        $arguments['peso_mercancia'] = (string) $pesoConTara;
                        Log::info('🏋️ TARA aplicada en create_cotizacion (detectado "sin tara")', [
                            'peso_original' => $pesoOriginal,
                            'tara' => $taraAplicar,
                            'tipo_embalaje' => $tipoEmbalaje,
                            'peso_con_tara' => $pesoConTara
                        ]);
                    } elseif ($taraYaIncluida) {
                        // Si dice "con tara incluida", NO agregar
                        $arguments['peso_mercancia'] = (string) $pesoOriginal;
                        Log::info('✅ TARA ya incluida (detectada "con tara incluida"), peso se mantiene', [
                            'peso' => $pesoOriginal
                        ]);
                    } elseif (!preg_match('/\btara\b/ui', $allUserMsgs)) {
                        // Si NO menciona tara en absoluto, agregar tara según contenedor por defecto
                        $pesoConTara = $pesoOriginal + $taraAplicar;
                        $arguments['peso_mercancia'] = (string) $pesoConTara;
                        Log::info('🏋️ TARA aplicada en create_cotizacion (no mencionó tara)', [
                            'peso_original' => $pesoOriginal,
                            'tara' => $taraAplicar,
                            'peso_con_tara' => $pesoConTara
                        ]);
                    } else {
                        $arguments['peso_mercancia'] = (string) $pesoOriginal;
                    }
                }
            }

            // Llamar a la herramienta MCP
            $result = self::callMCPTool($functionName, $arguments);

            // 🆕 Si es search_products, manejar resultado
            if ($functionName === 'search_products') {
                $searchTerm = $arguments['search_term'] ?? '';
                
                if (isset($result['productos']) && count($result['productos']) > 0) {
                    $productos = $result['productos'];
                    
                    // Si encontró 1 solo producto, o el search_term coincide exactamente con alguno
                    $productoSeleccionado = null;
                    
                    if (count($productos) === 1) {
                        $productoSeleccionado = $productos[0];
                    } else {
                        // Buscar coincidencia exacta
                        foreach ($productos as $prod) {
                            if (strtolower($prod['nombre'] ?? '') === strtolower($searchTerm)) {
                                $productoSeleccionado = $prod;
                                break;
                            }
                        }
                    }
                    
                    if ($productoSeleccionado) {
                        // 🆕 IMPORTANTE: Si hay multi-rutas y una ruta seleccionada, aplicar solo a esa ruta
                        $isMultiRouteData = isset($extractedData[0]) && is_array($extractedData[0]);
                        
                        if ($isMultiRouteData && $selectedRouteIndex !== null && isset($extractedData[$selectedRouteIndex])) {
                            // Aplicar solo a la ruta seleccionada
                            $extractedData[$selectedRouteIndex]['producto'] = $productoSeleccionado['nombre'] ?? $searchTerm;
                            $extractedData[$selectedRouteIndex]['producto_codigo'] = $productoSeleccionado['codigo'] ?? null;
                            $extractedData[$selectedRouteIndex]['producto_nombre'] = $productoSeleccionado['nombre'] ?? $searchTerm;
                            $extractedData[$selectedRouteIndex]['tipo_producto'] = $productoSeleccionado['tipo'] ?? 'MERCANCIAS VARIAS';
                            
                            Log::info('🔄 Producto aplicado SOLO a ruta seleccionada', [
                                'ruta_index' => $selectedRouteIndex,
                                'producto' => $productoSeleccionado['nombre'],
                                'codigo' => $productoSeleccionado['codigo'] ?? 'N/A'
                            ]);
                        } elseif ($isMultiRouteData) {
                            // Si es multi-ruta pero NO hay ruta seleccionada
                            // 🆕 FIX: Verificar si las rutas YA tienen productos - si es así, NO sobrescribir
                            $rutasSinProducto = [];
                            foreach ($extractedData as $idx => $ruta) {
                                if (is_numeric($idx) && is_array($ruta)) {
                                    $tieneProducto = !empty($ruta['producto_codigo']) || 
                                                    (!empty($ruta['producto']) && $ruta['producto'] !== strtoupper($searchTerm));
                                    if (!$tieneProducto) {
                                        $rutasSinProducto[] = $idx;
                                    }
                                }
                            }
                            
                            if (count($rutasSinProducto) > 0) {
                                // Solo aplicar a rutas que NO tienen producto
                                foreach ($rutasSinProducto as $idx) {
                                    $extractedData[$idx]['producto'] = $productoSeleccionado['nombre'] ?? $searchTerm;
                                    $extractedData[$idx]['producto_codigo'] = $productoSeleccionado['codigo'] ?? null;
                                    $extractedData[$idx]['producto_nombre'] = $productoSeleccionado['nombre'] ?? $searchTerm;
                                    $extractedData[$idx]['tipo_producto'] = $productoSeleccionado['tipo'] ?? 'MERCANCIAS VARIAS';
                                }
                                
                                Log::info('🔄 Producto aplicado SOLO a rutas sin producto', [
                                    'rutas_actualizadas' => $rutasSinProducto,
                                    'producto' => $productoSeleccionado['nombre'],
                                    'codigo' => $productoSeleccionado['codigo'] ?? 'N/A'
                                ]);
                            } else {
                                Log::info('⚠️ Todas las rutas ya tienen producto - NO se sobrescribe', [
                                    'search_term' => $searchTerm,
                                    'producto_encontrado' => $productoSeleccionado['nombre']
                                ]);
                            }
                            unset($ruta);
                            
                            Log::info('🔄 Producto aplicado a TODAS las rutas (sin ruta seleccionada)', [
                                'producto' => $productoSeleccionado['nombre'],
                                'codigo' => $productoSeleccionado['codigo'] ?? 'N/A'
                            ]);
                        } else {
                            // Ruta única - comportamiento normal
                            $extractedData['producto'] = $productoSeleccionado['nombre'] ?? $searchTerm;
                            $extractedData['producto_codigo'] = $productoSeleccionado['codigo'] ?? null;
                            $extractedData['tipo_producto'] = $productoSeleccionado['nombre'] ?? $searchTerm;
                            
                            Log::info('🔄 Producto agregado a extracted_data (ruta única)', [
                                'producto' => $productoSeleccionado['nombre'],
                                'codigo' => $productoSeleccionado['codigo'] ?? 'N/A'
                            ]);
                        }
                    } else {
                        // Si no hay coincidencia exacta, guardar opciones para selección posterior
                        // 🔴 NO sobrescribir productos de rutas existentes si es multi-ruta
                        $isMultiRouteData = isset($extractedData[0]) && is_array($extractedData[0]);
                        
                        if (!$isMultiRouteData) {
                            // Solo para ruta única guardar el término temporalmente
                            $extractedData['producto'] = strtoupper($searchTerm);
                            $extractedData['tipo_producto'] = strtoupper($searchTerm);
                        }
                        
                        // 🆕 Guardar productos disponibles en metadata para selección posterior
                        $metadata = json_decode($session->metadata ?? '{}', true);
                        $metadata['productos_pendientes'] = $productos;
                        $metadata['producto_search_term'] = $searchTerm;
                        // 🆕 Guardar también la ruta que necesita el producto
                        $metadata['producto_pendiente_ruta_index'] = $selectedRouteIndex;
                        $session->metadata = json_encode($metadata);
                        $session->save();
                        
                        Log::info('🔄 Múltiples opciones de producto - NO se sobrescriben rutas existentes', [
                            'producto_buscado' => $searchTerm,
                            'opciones_disponibles' => count($productos),
                            'is_multi_route' => $isMultiRouteData,
                            'ruta_index_pendiente' => $selectedRouteIndex,
                            'productos_guardados_en_metadata' => true
                        ]);
                    }
                } else {
                    // 🆕 PRODUCTO NO ENCONTRADO EN BD - Guardarlo como PERSONALIZADO
                    // 🔴 Solo para ruta única o si hay ruta seleccionada
                    $isMultiRouteData = isset($extractedData[0]) && is_array($extractedData[0]);
                    
                    if ($isMultiRouteData && $selectedRouteIndex !== null && isset($extractedData[$selectedRouteIndex])) {
                        // Aplicar solo a la ruta seleccionada
                        $extractedData[$selectedRouteIndex]['producto'] = strtoupper($searchTerm);
                        $extractedData[$selectedRouteIndex]['tipo_producto'] = strtoupper($searchTerm);
                        $extractedData[$selectedRouteIndex]['producto_codigo'] = 'PERSONALIZADO';
                        
                        Log::info('🔄 Producto PERSONALIZADO aplicado a ruta seleccionada', [
                            'ruta_index' => $selectedRouteIndex,
                            'producto' => strtoupper($searchTerm)
                        ]);
                    } elseif (!$isMultiRouteData) {
                        // Ruta única
                        $extractedData['producto'] = strtoupper($searchTerm);
                        $extractedData['tipo_producto'] = strtoupper($searchTerm);
                        $extractedData['producto_codigo'] = 'PERSONALIZADO';
                        
                        Log::info('🔄 Producto NO encontrado en BD - guardado como PERSONALIZADO', [
                            'producto' => strtoupper($searchTerm),
                            'codigo' => 'PERSONALIZADO'
                        ]);
                    } else {
                        Log::info('🔄 Producto no encontrado - NO se sobrescriben rutas existentes', [
                            'producto_buscado' => strtoupper($searchTerm),
                            'is_multi_route' => $isMultiRouteData,
                            'selected_route_index' => $selectedRouteIndex
                        ]);
                    }
                }
            }
            
            // 🆕 Si es get_empaques y se llamó con un embalaje específico, actualizarlo
            if ($functionName === 'get_empaques' && isset($result['empaques']) && count($result['empaques']) > 0) {
                // Si solo hay un empaque o se buscó uno específico
                $empaqueNombre = $arguments['nombre'] ?? null;
                if ($empaqueNombre && count($result['empaques']) === 1) {
                    $empaque = $result['empaques'][0];
                    $extractedData['empaque'] = $empaque['nome'] ?? $empaqueNombre;
                    $extractedData['empaque_id'] = $empaque['id'] ?? null;
                    
                    Log::info('🔄 Empaque agregado a extracted_data', [
                        'empaque' => $empaque['nome'],
                        'id' => $empaque['id'] ?? 'N/A'
                    ]);
                }
            }

            $toolResults[] = [
                'tool_call_id' => $toolCall['id'],
                'role' => 'tool',
                'name' => $functionName,
                'content' => json_encode($result)
            ];

            // Guardar la ejecución de la herramienta CON tool_call_id
            // $currentGroupId ya está definido al inicio de processToolCalls
            ConversationMessage::create([
                'session_id' => $session->id,
                'group_cotization_id' => $currentGroupId, // 🆕 Agregar group_id
                'role' => 'tool',
                'content' => json_encode($result),
                'metadata' => json_encode([
                    'tool_call_id' => $toolCall['id'],
                    'function' => $functionName,
                    'arguments' => $arguments
                ]),
                'timestamp' => now()
            ]);
        }

        // Guardar el mensaje del asistente con tool calls
        // $currentGroupId ya está definido al inicio de processToolCalls
        ConversationMessage::create([
            'session_id' => $session->id,
            'group_cotization_id' => $currentGroupId, // 🆕 Agregar group_id
            'role' => 'assistant',
            'content' => json_encode([
                'tool_calls' => $toolCalls
            ]),
            'timestamp' => now()
        ]);

        // Crear run ID
        $runId = 'run_mcp_tools_' . time();
        
        // Combinar datos extraídos del mensaje con datos de tool calls
        // 🔧 FIX #556: Pasar también toolCalls para extraer argumentos
        $quoteDataFromTools = self::extractQuoteData($toolResults, $toolCalls);
        $mergedData = array_merge($extractedData, $quoteDataFromTools);
        
        // Determinar si hay datos disponibles (mergedData O extracted_data)
        $hasData = !empty($mergedData) || !empty($extractedData);
        
        $metadata = json_decode($session->metadata ?? '{}', true);
        $metadata['last_run_id'] = $runId;
        $metadata['last_run_status'] = $hasData ? 'completed_with_data' : 'completed';
        $metadata['last_run_at'] = now()->toIso8601String();
        $metadata['tool_results'] = $toolResults;
        
        // CRÍTICO: Guardar tanto quote_data como extracted_data
        if (!empty($mergedData)) {
            $metadata['quote_data'] = $mergedData;
        }
        
        // 🆕 GUARDAR extracted_data EN EL GRUPO ESPECÍFICO (no en sesión compartida)
        // 🔧 FIX #556: Usar mergedData (que incluye datos de tool calls) en lugar de extractedData
        $dataToSave = !empty($mergedData) ? $mergedData : $extractedData;
        if (!empty($dataToSave) && $currentGroupId) {
            $group = GroupCotization::find($currentGroupId);
            if ($group) {
                // Mergear con datos existentes del grupo
                $existingGroupData = json_decode($group->extracted_data ?? '{}', true) ?? [];
                
                // 🔴 CRÍTICO: Detectar si son multi-rutas (arrays indexados)
                // Si ambos tienen claves numéricas, es multi-ruta y debemos REEMPLAZAR, no mergear
                $isNewMultiRoute = isset($dataToSave[0]) && is_array($dataToSave[0]);
                $isExistingMultiRoute = isset($existingGroupData[0]) && is_array($existingGroupData[0]);
                
                if ($isNewMultiRoute) {
                    // Las nuevas rutas ACTUALIZAN las existentes (merge por ruta)
                    // Pero conservamos campos importantes como incluye_tara
                    $mergedGroupData = [];
                    
                    // Primero, copiar las rutas EXISTENTES como base
                    foreach ($existingGroupData as $key => $value) {
                        if (is_numeric($key)) {
                            $mergedGroupData[$key] = $value;
                        }
                    }
                    
                    // Luego, MERGEAR las nuevas rutas sobre las existentes
                    // Esto preserva campos como incluye_tara si no vienen en la nueva
                    foreach ($dataToSave as $key => $value) {
                        if (is_numeric($key) && is_array($value)) {
                            if (isset($mergedGroupData[$key]) && is_array($mergedGroupData[$key])) {
                                // 🔧 MERGE: campos existentes + nuevos (nuevos tienen prioridad EXCEPTO incluye_tara)
                                $existingRoute = $mergedGroupData[$key];
                                $newRoute = $value;
                                
                                // 🔴 CRÍTICO: Preservar incluye_tara si ya existe y es true
                                if (isset($existingRoute['incluye_tara']) && $existingRoute['incluye_tara'] === true) {
                                    $newRoute['incluye_tara'] = true;
                                    Log::info('✅ Preservando incluye_tara=true de ruta existente', ['ruta' => $key]);
                                }
                                
                                $mergedGroupData[$key] = array_merge($existingRoute, $newRoute);
                            } else {
                                $mergedGroupData[$key] = $value;
                            }
                        } else {
                            $mergedGroupData[$key] = $value;
                        }
                    }
                    
                    // Copiar campos NO numéricos del existente que no estén en el nuevo
                    foreach ($existingGroupData as $key => $value) {
                        if (!is_numeric($key) && !isset($mergedGroupData[$key])) {
                            $mergedGroupData[$key] = $value;
                        }
                    }
                    
                    Log::info('🔄 Multi-ruta: MERGEANDO rutas (preservando incluye_tara)', [
                        'rutas_nuevas' => count(array_filter(array_keys($dataToSave), 'is_numeric')),
                        'rutas_finales' => count(array_filter(array_keys($mergedGroupData), 'is_numeric'))
                    ]);
                } else {
                    // Ruta única o campos sueltos: mergear normalmente
                    $mergedGroupData = array_merge($existingGroupData, $dataToSave);
                }
                
                $group->extracted_data = json_encode($mergedGroupData);
                $group->save();
                
                Log::info('📦 extracted_data guardado en GRUPO (processToolCalls)', [
                    'group_id' => $currentGroupId,
                    'fields' => array_keys($mergedGroupData),
                    'usando_merged_data' => !empty($mergedData)
                ]);
            }
            
            // También guardar indexado por grupo en metadata para compatibilidad
            if (!isset($metadata['extracted_data_by_group'])) {
                $metadata['extracted_data_by_group'] = [];
            }
            $metadata['extracted_data_by_group'][$currentGroupId] = $dataToSave;
        }
        
        $session->metadata = json_encode($metadata);
        $session->save();

        Log::info('Tool calls procesados exitosamente', [
            'run_id' => $runId,
            'has_data' => $hasData,
            'has_quote_data' => !empty($mergedData),
            'has_extracted_data' => !empty($extractedData),
            'tools_executed' => count($toolResults),
            'extracted_count' => count($extractedData),
            'merged_count' => count($mergedData)
        ]);

        // NUEVO: Hacer segunda llamada al API para obtener respuesta de texto
        // basada en los resultados de las herramientas
        try {
            $followUpResponse = self::getFollowUpResponse($threadId, $toolResults, $openaiResponse);
            if ($followUpResponse) {
                // Guardar la respuesta del asistente
                ConversationMessage::create([
                    'session_id' => $session->id,
                    'group_cotization_id' => $currentGroupId,
                    'role' => 'assistant',
                    'content' => $followUpResponse,
                    'timestamp' => now()
                ]);
                
                Log::info('Respuesta de seguimiento guardada', [
                    'run_id' => $runId,
                    'response_length' => strlen($followUpResponse)
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Error obteniendo respuesta de seguimiento', [
                'error' => $e->getMessage()
            ]);
        }

        return [
            'id' => $runId,
            'status' => $hasData ? 'completed_with_data' : 'completed',
            'quote_data' => $mergedData,
            'extracted_data' => $extractedData  // NUEVO: incluir datos extraídos por regex
        ];
    }

    /**
     * Llamar a una herramienta del servidor MCP
     */
    private static function callMCPTool($toolName, $arguments)
    {
        try {
            Log::info('Llamando herramienta MCP', [
                'tool' => $toolName,
                'mcp_url' => self::$mcp_base_url
            ]);

            // JSON-RPC 2.0 format según protocolo MCP
            $payload = [
                'jsonrpc' => '2.0',
                'id' => uniqid(),
                'method' => 'tools/call',
                'params' => [
                    'name' => $toolName,
                    'arguments' => $arguments
                ]
            ];

            Log::info('Payload MCP', ['payload' => $payload]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post(self::$mcp_base_url, $payload);

            if (!$response->successful()) {
                Log::warning('Error llamando herramienta MCP', [
                    'tool' => $toolName,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return ['error' => 'Error al llamar herramienta MCP'];
            }

            $data = $response->json();
            
            Log::info('Respuesta MCP recibida', [
                'tool' => $toolName,
                'response' => $data
            ]);

            // Respuesta JSON-RPC 2.0: extraer el texto del content
            if (isset($data['result']['content'][0]['text'])) {
                $textResult = $data['result']['content'][0]['text'];
                return json_decode($textResult, true) ?? $textResult;
            }
            
            return $data['result'] ?? $data;

        } catch (\Exception $e) {
            Log::error('Excepción llamando herramienta MCP', [
                'tool' => $toolName,
                'error' => $e->getMessage()
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Obtener respuesta de seguimiento del asistente después de ejecutar herramientas
     */
    private static function getFollowUpResponse($threadId, $toolResults, $originalResponse)
    {
        self::initConfig();
        
        $session = ConversationSession::where('session_id', $threadId)->first();
        if (!$session) {
            return null;
        }
        
        // Construir mensajes incluyendo los resultados de las herramientas
        $messages = [];
        
        // Agregar el mensaje original del asistente con tool_calls
        $assistantMessage = $originalResponse['choices'][0]['message'];
        $messages[] = $assistantMessage;
        
        // Agregar los resultados de las herramientas
        foreach ($toolResults as $toolResult) {
            $messages[] = [
                'role' => 'tool',
                'tool_call_id' => $toolResult['tool_call_id'],
                'content' => $toolResult['content']
            ];
        }
        
        // Obtener historial previo de la conversación
        $metadata = json_decode($session->metadata ?? '{}', true);
        $groupId = $metadata['current_group_id'] ?? null;
        $previousMessages = self::getMessagesArray($threadId, $groupId);
        
        // Combinar: historial + mensaje asistente con tools + resultados
        $fullMessages = array_merge($previousMessages, $messages);
        
        Log::info('Preparando llamada de seguimiento al API', [
            'thread_id' => $threadId,
            'tool_results_count' => count($toolResults),
            'total_messages' => count($fullMessages)
        ]);
        
        // Llamar al API para obtener respuesta final
        $payload = [
            'model' => self::$openai_model,
            'messages' => $fullMessages,
            'max_completion_tokens' => 1500
        ];
        
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . self::$openai_api_key,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', $payload)
                ->json();
            
            $content = $response['choices'][0]['message']['content'] ?? null;
            
            Log::info('Respuesta de seguimiento recibida', [
                'has_content' => !empty($content),
                'content_preview' => $content ? substr($content, 0, 100) : null
            ]);
            
            return $content;
            
        } catch (\Exception $e) {
            Log::error('Error en llamada de seguimiento', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Extraer datos de cotización de los resultados de las herramientas
     */
    private static function extractQuoteData($toolResults, $toolCalls = [])
    {
        $quoteData = [];
        
        // 🆕 FIX #556: Crear índice de tool calls por ID para acceso rápido
        $toolCallsById = [];
        foreach ($toolCalls as $tc) {
            if (isset($tc['id'])) {
                $toolCallsById[$tc['id']] = $tc;
            }
        }

        foreach ($toolResults as $result) {
            $content = json_decode($result['content'], true);
            
            // Si la herramienta es create_cotizacion, extraer los datos del resultado Y argumentos
            if ($result['name'] === 'create_cotizacion') {
                $extractedFields = [];
                
                // 🆕 FIX #556: Extraer argumentos originales de la tool call
                $toolCallId = $result['tool_call_id'] ?? null;
                // 🔧 FIX CRÍTICO: Priorizar datos del RESULTADO de la cotización creada
                // Estos datos YA tienen la tara aplicada (desde processToolCalls)
                if (isset($content['cotizacion'])) {
                    $cotizacion = $content['cotizacion'];
                    
                    // Mapear campos de la cotización creada a extracted_data
                    $extractedFields = [
                        'origen' => $cotizacion['ciudad_origen'] ?? null,
                        'destino' => $cotizacion['ciudad_destino'] ?? null,
                        'peso_kg' => $cotizacion['peso_mercancia'] ?? null,
                        'producto' => $cotizacion['producto'] ?? $cotizacion['tipo_producto'] ?? null,
                        'valor_declarado' => $cotizacion['valor_declarado'] ?? null,
                        'cantidad' => $cotizacion['cantidad'] ?? null,
                        'empaque' => $cotizacion['tipo_embalaje'] ?? $cotizacion['empaque'] ?? null,
                        'vehiculo' => $cotizacion['vehiculo'] ?? $cotizacion['vehiculo_requerido'] ?? null,
                        'incluye_tara' => true, // Siempre true porque ya se aplicó en processToolCalls
                    ];
                    
                    // Filtrar campos null
                    $extractedFields = array_filter($extractedFields, fn($v) => $v !== null);
                    
                    Log::info('📊 Datos extraídos del RESULTADO de create_cotizacion', [
                        'campos' => array_keys($extractedFields),
                        'peso_kg' => $extractedFields['peso_kg'] ?? 'N/A'
                    ]);
                    
                    if (!empty($extractedFields)) {
                        return $extractedFields;
                    }
                }
                
                // FALLBACK: Si no hay datos en el resultado, extraer de argumentos
                // (pero aplicar tara ya que los argumentos originales no la tienen)
                $toolCallId = $result['tool_call_id'] ?? null;
                if ($toolCallId && isset($toolCallsById[$toolCallId])) {
                    $toolCall = $toolCallsById[$toolCallId];
                    $argsJson = $toolCall['function']['arguments'] ?? '{}';
                    $args = json_decode($argsJson, true);
                    
                    if (is_array($args)) {
                        // Mapear campos de create_cotizacion a nombres de extracted_data
                        $fieldMapping = [
                            'ciudad_origen' => 'origen',
                            'ciudad_destino' => 'destino',
                            'peso_mercancia' => 'peso_kg',
                            'tipo_producto' => 'producto',
                            'valor_declarado' => 'valor_declarado',
                            'cantidad' => 'cantidad',
                            'tipo_embajale' => 'empaque',
                            'vehiculo_requerido' => 'vehiculo'
                        ];
                        
                        foreach ($fieldMapping as $argKey => $dataKey) {
                            if (isset($args[$argKey]) && !empty($args[$argKey])) {
                                $extractedFields[$dataKey] = $args[$argKey];
                            }
                        }
                        
                        // 🔧 FIX CRÍTICO: Usar normalizeWeight() y aplicar TARA si corresponde
                        // 🆕 PERO verificar si el usuario ya indicó que incluye tara
                        if (isset($extractedFields['peso_kg'])) {
                            $pesoOriginal = self::normalizeWeight($extractedFields['peso_kg']);
                            
                            // 🔧 Verificar si ya incluye tara en el contexto (mensaje original)
                            // Necesitamos acceso al mensaje original - usar variable estática o buscar en contexto
                            // Por ahora, NO aplicar tara automática aquí ya que se aplica en otros lugares
                            // Solo normalizar el peso
                            $extractedFields['peso_kg'] = $pesoOriginal;
                            Log::info('📊 Peso normalizado en extractQuoteData (fallback) - SIN tara automática', [
                                'peso' => $pesoOriginal,
                                'nota' => 'La tara se aplica en processToolCalls si corresponde'
                            ]);
                        }
                        
                        Log::info('📊 Datos extraídos de argumentos de create_cotizacion (fallback)', [
                            'campos' => array_keys($extractedFields)
                        ]);
                    }
                }
                
                // Retornar extractedFields si se encontraron
                if (!empty($extractedFields)) {
                    return $extractedFields; // Para ruta única
                }
            }
        }

        return $quoteData;
    }

    /**
     * Obtener mensajes de una conversación
     */
    public static function getMessages($threadId, $groupId = null)
    {
        self::initConfig();

        $session = ConversationSession::where('session_id', $threadId)->first();
        
        if (!$session) {
            \Log::warning('Session no encontrada para threadId', ['thread_id' => $threadId]);
            return [];
        }

        // Filtrar mensajes por session_id y opcionalmente por group_id
        $query = ConversationMessage::where('session_id', $session->id);
        
        if ($groupId) {
            $query->where('group_cotization_id', $groupId);
            \Log::info('Filtrando mensajes por group_id', [
                'session_id' => $session->id,
                'group_id' => $groupId
            ]);
        }
        
        $messages = $query->orderBy('timestamp', 'asc')->get();
        
        \Log::info('Mensajes recuperados', [
            'thread_id' => $threadId,
            'group_id' => $groupId,
            'count' => $messages->count()
        ]);

        return $messages->map(function($msg) {
            // Si es un mensaje de función, no mostrarlo al usuario
            if ($msg->role === 'function') {
                return null;
            }
            
            // Si es un mensaje de tool, NO mostrarlo al usuario (son resultados internos)
            if ($msg->role === 'tool') {
                return null;
            }

            // Si el contenido es JSON de tool_calls, NO mostrarlo (es mensaje interno)
            $content = $msg->content;
            if (is_string($content) && str_starts_with($content, '{')) {
                $decoded = json_decode($content, true);
                if (isset($decoded['tool_calls'])) {
                    return null; // Ocultar mensajes de tool_calls
                }
            }

            return [
                'role' => $msg->role,
                'text' => $content,
                'created_at' => $msg->timestamp->format('H:i'),
                'status' => 'sent'
            ];
        })->filter()->values()->toArray();
    }

    /**
     * Obtener mensajes como array para OpenAI
     * @param string $threadId ID del thread
     * @param int|null $groupId ID del grupo de cotización para filtrar mensajes
     */
    private static function getMessagesArray($threadId, $groupId = null)
    {
        $session = ConversationSession::where('session_id', $threadId)->first();
        
        if (!$session) {
            return [];
        }

        // 🔴 FILTRAR POR group_cotization_id si se proporciona
        $query = ConversationMessage::where('session_id', $session->id)
            ->where('role', '!=', 'function'); // Excluir mensajes de función
        
        if ($groupId) {
            $query->where('group_cotization_id', $groupId);
            Log::info('Filtrando mensajes por grupo', [
                'thread_id' => $threadId,
                'group_id' => $groupId
            ]);
        }
        
        $messages = $query->orderBy('timestamp', 'asc')->get();

        $result = [];
        $pendingToolCalls = [];
        $tempAssistantWithTools = null;

        foreach ($messages as $msg) {
            $content = $msg->content;
            
            // Si tenemos un assistant con tool_calls pendiente y llega un mensaje que NO es tool
            if ($tempAssistantWithTools && $msg->role !== 'tool') {
                // Descartar el assistant incompleto
                Log::warning('Descartando assistant con tool_calls incompletos', [
                    'expected_calls' => count($pendingToolCalls),
                    'session_id' => $session->id
                ]);
                $tempAssistantWithTools = null;
                $pendingToolCalls = [];
            }
            
            // Si es un mensaje de tool
            if ($msg->role === 'tool') {
                if (empty($pendingToolCalls)) {
                    // No hay tool_calls pendientes, saltar mensaje huérfano
                    Log::warning('Mensaje tool huérfano omitido', [
                        'message_id' => $msg->id,
                        'session_id' => $session->id
                    ]);
                    continue;
                }
                
                $metadata = json_decode($msg->metadata ?? '{}', true);
                $toolCallId = $metadata['tool_call_id'] ?? '';
                
                // Verificar si este tool_call_id está en los pendientes
                $key = array_search($toolCallId, $pendingToolCalls);
                if ($key !== false) {
                    unset($pendingToolCalls[$key]);
                }
                
                // Agregar el mensaje tool (temporal)
                if ($tempAssistantWithTools) {
                    $tempAssistantWithTools['tool_responses'][] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCallId,
                        'content' => $content
                    ];
                    
                    // Si ya tenemos todas las respuestas, agregar todo
                    if (empty($pendingToolCalls)) {
                        $result[] = $tempAssistantWithTools['assistant'];
                        foreach ($tempAssistantWithTools['tool_responses'] as $toolResp) {
                            $result[] = $toolResp;
                        }
                        $tempAssistantWithTools = null;
                    }
                }
                continue;
            }
            
            // Si es JSON de tool_calls
            if (is_string($content) && str_starts_with($content, '{')) {
                $decoded = json_decode($content, true);
                if (isset($decoded['tool_calls']) && is_array($decoded['tool_calls'])) {
                    // Guardar temporalmente y esperar las respuestas
                    $tempAssistantWithTools = [
                        'assistant' => [
                            'role' => 'assistant',
                            'content' => null,
                            'tool_calls' => $decoded['tool_calls']
                        ],
                        'tool_responses' => []
                    ];
                    
                    // Extraer los IDs de tool_calls esperados
                    $pendingToolCalls = array_column($decoded['tool_calls'], 'id');
                    continue;
                }
            }

            // Mensaje normal (user o assistant sin tool_calls)
            $result[] = [
                'role' => $msg->role,
                'content' => $content
            ];
        }

        return $result;
    }

    /**
     * Verificar estado de un run
     */
    public static function checkRunStatus($threadId, $runId)
    {
        self::initConfig();

        $session = ConversationSession::where('session_id', $threadId)->first();
        
        if (!$session) {
            return [
                'status' => 'failed',
                'error' => 'Thread no encontrado'
            ];
        }

        $metadata = json_decode($session->metadata ?? '{}', true);
        
        if (isset($metadata['last_run_id']) && $metadata['last_run_id'] === $runId) {
            $status = $metadata['last_run_status'] ?? 'completed';
            
            // 🆕 OBTENER extracted_data DEL GRUPO ACTUAL, no de la sesión compartida
            $currentGroupId = $metadata['current_group_id'] ?? null;
            $groupExtractedData = null;
            
            if ($currentGroupId) {
                $group = GroupCotization::find($currentGroupId);
                if ($group && $group->extracted_data) {
                    $groupExtractedData = json_decode($group->extracted_data, true);
                    Log::info('📦 extracted_data obtenido del GRUPO', [
                        'group_id' => $currentGroupId,
                        'fields' => is_array($groupExtractedData) ? array_keys($groupExtractedData) : []
                    ]);
                }
            }
            
            // Determinar si hay datos (quote_data O extracted_data)
            $hasQuoteData = isset($metadata['quote_data']) && !empty($metadata['quote_data']);
            // 🆕 Priorizar datos del GRUPO sobre los de sesión
            $hasExtractedData = !empty($groupExtractedData) || (isset($metadata['extracted_data']) && !empty($metadata['extracted_data']));
            $hasAnyData = $hasQuoteData || $hasExtractedData;
            
            // Actualizar status basado en disponibilidad de datos
            if ($status === 'completed' && $hasAnyData) {
                $status = 'completed_with_data';
                Log::info('checkRunStatus: Actualizando status a completed_with_data', [
                    'run_id' => $runId,
                    'has_quote_data' => $hasQuoteData,
                    'has_extracted_data' => $hasExtractedData
                ]);
            }
            
            $result = [
                'status' => $status,
                'thread_id' => $threadId,
                'run_id' => $runId
            ];

            // Si hay datos de cotización, incluirlos
            if ($hasQuoteData) {
                $result['quote_data'] = $metadata['quote_data'];
            }

            // NUEVO: Incluir datos extraídos automáticamente si existen
            // 🆕 PRIORIZAR datos del GRUPO sobre los de sesión
            if ($hasExtractedData) {
                // NORMALIZAR datos antes de devolver
                // 🆕 Usar datos del grupo primero, luego fallback a metadata
                $extractedData = $groupExtractedData ?? $metadata['extracted_data'] ?? [];
                
                if (empty($extractedData)) {
                    Log::warning('checkRunStatus: extracted_data está vacío');
                } else {
                    // 🔧 MEJORAR DETECCIÓN: Verificar si es array de rutas o una ruta única
                    // Un array de rutas tiene claves numéricas (0, 1, 2...)
                    // Una ruta única tiene claves con nombres ('origen', 'destino', 'peso_kg'...)
                    $keys = array_keys($extractedData);
                    $numericKeys = array_filter($keys, 'is_numeric');
                    $allNumericKeys = !empty($keys) && count($numericKeys) === count($keys);
                    
                    // 🆕 NUEVO: Detectar si hay rutas mezcladas con campos sueltos
                    // Ej: { "0": {ruta1}, "1": {ruta2}, "producto": "MAIZ" }
                    $hasNumericRoutes = false;
                    $numericRouteCount = 0;
                    foreach ($numericKeys as $key) {
                        if (is_array($extractedData[$key]) && 
                            (isset($extractedData[$key]['origen']) || isset($extractedData[$key]['destino']) || 
                             isset($extractedData[$key]['peso_kg']) || isset($extractedData[$key]['ruta_numero']))) {
                            $hasNumericRoutes = true;
                            $numericRouteCount++;
                        }
                    }
                    
                    // Si hay rutas con índices numéricos mezcladas con campos sueltos, extraer solo las rutas
                    if ($hasNumericRoutes && $numericRouteCount >= 1 && !$allNumericKeys) {
                        Log::info('checkRunStatus: Detectadas rutas mezcladas con campos sueltos', [
                            'numeric_routes' => $numericRouteCount,
                            'total_keys' => count($keys)
                        ]);
                        
                        $cleanRoutes = [];
                        foreach ($numericKeys as $key) {
                            if (is_array($extractedData[$key])) {
                                $cleanRoutes[] = $extractedData[$key];
                            }
                        }
                        $extractedData = $cleanRoutes;
                        $isMultiRoute = count($cleanRoutes) > 1;
                        
                        Log::info('checkRunStatus: Rutas extraídas y limpiadas', [
                            'routes_count' => count($extractedData),
                            'is_multi_route' => $isMultiRoute
                        ]);
                    } else {
                        $firstElement = reset($extractedData);
                        
                        // Es multi-ruta si:
                        // 1. Todas las claves son numéricas (0, 1, 2...)
                        // 2. Y el primer elemento es un array con datos de ruta
                        $isMultiRoute = $allNumericKeys && is_array($firstElement) && 
                            (isset($firstElement['origen']) || isset($firstElement['destino']) || 
                             isset($firstElement['peso_kg']) || isset($firstElement['producto']));
                        
                        if (!$isMultiRoute) {
                            // Es una ruta única - convertir a array de una ruta
                            $extractedData = [$extractedData];
                            Log::info('checkRunStatus: Convertido a array de 1 ruta');
                        }
                    }
                    
                    Log::info('checkRunStatus: Detectando tipo de datos', [
                        'from_group' => !empty($groupExtractedData),
                        'keys_sample' => array_slice(array_keys($extractedData), 0, 5),
                        'is_multi_route' => $isMultiRoute ?? false,
                        'total_elements' => count($extractedData)
                    ]);
                    
                    // 🔧 FIX BUG #1: Obtener IDs de cotizacion_models para incluir en respuesta
                    $cotizacionIds = [];
                    if ($currentGroupId) {
                        $cotizaciones = \App\Models\CotizacionModel::where('group_cotization_id', $currentGroupId)
                            ->orderBy('id')
                            ->pluck('id')
                            ->toArray();
                        $cotizacionIds = $cotizaciones;
                        Log::info('checkRunStatus: IDs de cotizacion_models obtenidos', [
                            'group_id' => $currentGroupId,
                            'cotizacion_ids' => $cotizacionIds
                        ]);
                    }
                    
                    foreach ($extractedData as $index => $ruta) {
                        if (!is_array($ruta)) continue; // Saltar si no es un array válido
                        
                        // 🔧 FIX BUG #1: Incluir ID de cotizacion_models si existe
                        if (isset($cotizacionIds[$index]) && !isset($ruta['id'])) {
                            $extractedData[$index]['id'] = $cotizacionIds[$index];
                        }
                        
                        // Normalizar valor_mercancia -> valor_declarado
                        if (isset($ruta['valor_mercancia']) && !isset($ruta['valor_declarado'])) {
                            $extractedData[$index]['valor_declarado'] = $ruta['valor_mercancia'];
                        }
                        // Normalizar producto -> tipo_producto
                        if (isset($ruta['producto']) && !isset($ruta['tipo_producto'])) {
                            $extractedData[$index]['tipo_producto'] = $ruta['producto'];
                        }
                    }
                    
                    $result['extracted_data'] = $extractedData;
                    Log::info('checkRunStatus: Incluyendo extracted_data normalizado', [
                        'run_id' => $runId,
                        'from_group' => !empty($groupExtractedData),
                        'extracted_count' => count($extractedData),
                        'is_multi_route' => $isMultiRoute,
                        'primer_ruta' => $extractedData[0] ?? null
                    ]);
                }
            }
            
            if ($status === 'completed_with_data') {
                Log::info('checkRunStatus: Run completado CON datos', [
                    'run_id' => $runId,
                    'quote_data_count' => $hasQuoteData ? count($metadata['quote_data']) : 0,
                    'extracted_data_count' => $hasExtractedData ? (is_array($groupExtractedData ?? $metadata['extracted_data'] ?? []) ? count($groupExtractedData ?? $metadata['extracted_data'] ?? []) : 0) : 0
                ]);
            }

            return $result;
        }

        return [
            'status' => 'completed',
            'thread_id' => $threadId,
            'run_id' => $runId
        ];
    }

    /**
     * Limpiar/resetear una conversación
     */
    public static function clearThread($threadId, $clientId = null)
    {
        self::initConfig();

        $session = ConversationSession::where('session_id', $threadId)->first();
        
        if ($session) {
            // Eliminar mensajes
            ConversationMessage::where('session_id', $session->id)->delete();
            
            // Resetear metadata
            $session->metadata = json_encode([
                'cleared_at' => now()->toIso8601String()
            ]);
            $session->save();

            Log::info('Thread limpiado', [
                'thread_id' => $threadId,
                'session_id' => $session->id
            ]);
        }

        return true;
    }

    /**
     * Definir herramientas MCP disponibles en formato OpenAI
     */
    private static function getMCPTools()
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_cotizacion',
                    'description' => 'Crea una nueva cotización de transporte con todos los datos recopilados del usuario',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'pricing_id' => [
                                'type' => 'integer',
                                'description' => 'ID del pricing a usar (por defecto: 43214)',
                                'default' => 43214
                            ],
                            'ciudad_origen' => [
                                'type' => 'string',
                                'description' => 'Ciudad de origen del envío (ej: Bogotá, Medellín)'
                            ],
                            'ciudad_destino' => [
                                'type' => 'string',
                                'description' => 'Ciudad de destino del envío'
                            ],
                            'peso_mercancia' => [
                                'type' => 'string',
                                'description' => 'Peso de la mercancía en kilogramos (ej: "500", "1500")'
                            ],
                            'cantidad' => [
                                'type' => 'string',
                                'description' => 'Cantidad de unidades o bultos'
                            ],
                            'tipo_embajale' => [
                                'type' => 'string',
                                'description' => 'Tipo de embalaje (ej: CAJA, PALLET, ESTIBA, SACO)'
                            ],
                            'tipo_producto' => [
                                'type' => 'string',
                                'description' => 'Tipo o nombre del producto a transportar'
                            ],
                            'vehiculo_requerido' => [
                                'type' => 'string',
                                'description' => 'Tipo de vehículo requerido (ej: Turbo, Sencillo, Camión)'
                            ],
                            'valor_declarado' => [
                                'type' => 'string',
                                'description' => 'Valor declarado de la mercancía en pesos colombianos'
                            ]
                        ],
                        'required' => ['ciudad_origen', 'ciudad_destino', 'peso_mercancia']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_products',
                    'description' => 'Busca productos en el catálogo para ayudar al usuario a especificar el tipo de mercancía',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'search_term' => [
                                'type' => 'string',
                                'description' => 'Término de búsqueda para encontrar productos (ej: "neumaticos", "alimentos", "electrodomésticos")'
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Número máximo de resultados a devolver',
                                'default' => 10
                            ]
                        ],
                        'required' => ['search_term']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_empaques',
                    'description' => 'Obtiene la lista de tipos de empaque disponibles en el sistema',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'filter' => [
                                'type' => 'string',
                                'description' => 'Filtro opcional para buscar empaques específicos'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Obtener system prompt según tipo de negocio
     */
    /**
     * Detectar si el usuario mencionó un tipo de empaque en su mensaje
     */
    private static function detectEmpaqueInMessage($messages)
    {
        // Mapeo de palabras clave a nombres de empaques
        $empaqueKeywords = [
            'caja' => 'CAJAS',
            'cajas' => 'CAJAS',
            'paquete' => 'PAQUETES',
            'paquetes' => 'PAQUETES',
            'bulto' => 'BULTOS',
            'bultos' => 'BULTOS',
            'estiba' => 'ESTIBAS',
            'estibas' => 'ESTIBAS',
            'pallet' => 'PALLET',
            'pallets' => 'PALLET',
            'saco' => 'SACOS',
            'sacos' => 'SACOS',
            'tonel' => 'TONEL',
            'toneles' => 'TONEL',
            'contenedor' => 'CONTENEDOR',
            'contenedores' => 'CONTENEDOR',
            'granel' => 'GRANEL',
            'cilindro' => 'CILINDROS',
            'cilindros' => 'CILINDROS',
            'rollo' => 'ROLLOS',
            'rollos' => 'ROLLOS',
            'bolsa' => 'BOLSAS',
            'bolsas' => 'BOLSAS',
            'guacal' => 'GUACALES',
            'guacales' => 'GUACALES',
        ];

        // Buscar en los últimos 3 mensajes del usuario
        $userMessages = array_filter($messages, function($msg) {
            return isset($msg['role']) && $msg['role'] === 'user';
        });

        // Tomar los últimos 3 mensajes del usuario
        $recentUserMessages = array_slice($userMessages, -3);

        foreach ($recentUserMessages as $message) {
            $text = strtolower($message['content']);
            
            // Buscar palabras clave de empaque con word boundaries para evitar falsos positivos
            // Ejemplo: "tonel" no debe detectarse en "toneladas"
            foreach ($empaqueKeywords as $keyword => $empaqueName) {
                // Usar regex con word boundaries (\b) para match exacto de palabras
                if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/u', $text)) {
                    Log::info('Empaque detectado automáticamente', [
                        'keyword' => $keyword,
                        'empaque' => $empaqueName,
                        'message' => substr($text, 0, 100)
                    ]);
                    return $empaqueName;
                }
            }
        }

        return null;
    }

    /**
     * Obtener empaque desde la base de datos con búsqueda fuzzy mejorada
     * Retorna array con id, nome o null si no encuentra
     */
    private static function getEmpaqueFromDB($empaqueName)
    {
        try {
            // Normalizar nombre
            $nombreNormalizado = strtoupper(trim($empaqueName));
            
            // 1️⃣ Intentar coincidencia exacta primero
            $empaque = \DB::table('tb_empaque')
                ->whereRaw('UPPER(nome) = ?', [$nombreNormalizado])
                ->first();

            // 2️⃣ Si no hay coincidencia exacta, buscar por LIKE
            if (!$empaque) {
                $empaque = \DB::table('tb_empaque')
                    ->whereRaw('UPPER(nome) LIKE ?', ['%' . $nombreNormalizado . '%'])
                    ->first();
            }

            // 3️⃣ Si aún no hay coincidencia, buscar por palabras individuales
            if (!$empaque) {
                $palabras = explode(' ', $nombreNormalizado);
                $query = \DB::table('tb_empaque');
                
                foreach ($palabras as $palabra) {
                    if (strlen($palabra) >= 3) { // Solo palabras de 3+ caracteres
                        $query->where('nome', 'LIKE', '%' . $palabra . '%');
                    }
                }
                
                $empaque = $query->first();
            }
            
            // 4️⃣ Búsqueda fuzzy por similitud (levenshtein)
            if (!$empaque) {
                $todosEmpaques = \DB::table('tb_empaque')->get();
                $mejorMatch = null;
                $mejorSimilitud = 0;
                
                foreach ($todosEmpaques as $emp) {
                    similar_text(strtoupper($emp->nome), $nombreNormalizado, $percent);
                    if ($percent > $mejorSimilitud && $percent >= 60) { // Mínimo 60% similitud
                        $mejorSimilitud = $percent;
                        $mejorMatch = $emp;
                    }
                }
                
                if ($mejorMatch) {
                    $empaque = $mejorMatch;
                    Log::info('📦 Empaque encontrado por similitud', [
                        'buscado' => $empaqueName,
                        'encontrado' => $mejorMatch->nome,
                        'similitud' => round($mejorSimilitud, 2) . '%'
                    ]);
                }
            }

            if ($empaque) {
                Log::info('✅ Empaque encontrado en BD', [
                    'nombre_buscado' => $empaqueName,
                    'id' => $empaque->id,
                    'nome' => $empaque->nome
                ]);
                
                return [
                    'id' => $empaque->id,
                    'nome' => $empaque->nome
                ];
            }

            Log::warning('⚠️ Empaque NO encontrado en BD', [
                'nombre_buscado' => $empaqueName
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('❌ Error consultando empaque en BD', [
                'empaque' => $empaqueName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * LEGACY: Mantener compatibilidad con código existente
     * Obtener solo ID de empaque desde la base de datos
     */
    private static function getEmpaqueIdFromDB($empaqueName)
    {
        $result = self::getEmpaqueFromDB($empaqueName);
        return $result ? $result['id'] : null;
    }

    /**
     * Buscar producto en base de datos
     * @param string $productoName Nombre del producto detectado
     * @return array|null Array con código y nombre del producto, o null si no se encuentra
     */
    private static function getProductoFromDB($productoName)
    {
        try {
            Log::info('Buscando producto en BD', [
                'producto_buscado' => $productoName
            ]);

            // 🔧 FIX: Si el producto es muy genérico, no buscar en BD
            // Evita mapeos incorrectos como "productos de consumo masivo" → "LACTOSUERO..."
            $productosGenericos = [
                'productos de consumo masivo', 'consumo masivo', 'productos varios',
                'mercancia variada', 'mercancias varias', 'carga general',
                'productos industriales', 'productos alimenticios', 'alimentos',
                'productos', 'mercancia', 'carga', 'material'
            ];
            
            $productoLower = strtolower(trim($productoName));
            if (in_array($productoLower, $productosGenericos)) {
                Log::info('⚠️ Producto genérico detectado - NO buscar en BD', [
                    'producto' => $productoName,
                    'razon' => 'Evitar mapeo incorrecto'
                ]);
                return null; // Dejar que el usuario lo especifique mejor
            }

            // Intentar búsqueda exacta primero
            $producto = \DB::table('products')
                ->where('producto_nombre', 'LIKE', '%' . $productoName . '%')
                ->first();

            // 🔧 FIX: NO buscar por palabras individuales si el nombre es corto o genérico
            // Esto evita que "productos de consumo" mapee a cualquier cosa con "productos" en el nombre
            if (!$producto && strlen($productoName) > 10) {
                // Solo buscar por la primera palabra si es específica (> 5 caracteres, no genérica)
                $palabras = explode(' ', $productoName);
                $primerasPalabras = array_slice($palabras, 0, 2); // Solo primeras 2 palabras
                
                $palabrasGenericas = ['productos', 'producto', 'mercancia', 'mercancias', 'carga', 'material', 'consumo', 'masivo'];
                
                foreach ($primerasPalabras as $palabra) {
                    if (strlen($palabra) > 5 && !in_array(strtolower($palabra), $palabrasGenericas)) {
                        $producto = \DB::table('products')
                            ->where('producto_nombre', 'LIKE', '%' . $palabra . '%')
                            ->first();
                        if ($producto) break;
                    }
                }
            }

            if ($producto) {
                Log::info('✅ Producto encontrado en BD', [
                    'nombre_buscado' => $productoName,
                    'codigo' => $producto->producto_codigo,
                    'nombre' => $producto->producto_nombre
                ]);
                return [
                    'codigo' => $producto->producto_codigo,
                    'nombre' => $producto->producto_nombre,
                    'tipo' => $producto->tippro_nombre ?? null
                ];
            }

            Log::warning('⚠️ Producto NO encontrado en BD', [
                'nombre_buscado' => $productoName
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('❌ Error consultando producto en BD', [
                'producto' => $productoName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * � APLICAR MODIFICACIONES A RUTAS EXISTENTES
     * Detecta comandos de modificación como "la ruta de X cambia..." y actualiza campos específicos
     * 
     * @param array $rutasExistentes Rutas ya detectadas previamente
     * @param string $textoModificacion Texto con instrucciones de modificación
     * @return array Rutas con modificaciones aplicadas
     */
    private static function applyRouteModifications($rutasExistentes, $textoModificacion)
    {
        if (empty($rutasExistentes) || empty($textoModificacion)) {
            return $rutasExistentes;
        }
        
        $texto = strtolower($textoModificacion);
        
        // Detectar patrones de modificación: "la ruta de [ciudad]..."
        if (!preg_match('/la\s+ruta\s+de\s+([a-záéíóúñ]+)/ui', $texto, $matchCiudad)) {
            // No es un comando de modificación, retornar rutas sin cambios
            return $rutasExistentes;
        }
        
        $ciudadObjetivo = self::normalizeCityName($matchCiudad[1]);
        
        Log::info('🔧 Detectado comando de modificación', [
            'ciudad_objetivo' => $ciudadObjetivo,
            'texto' => substr($textoModificacion, 0, 100)
        ]);
        
        // Buscar qué ruta corresponde a esa ciudad (origen o destino)
        $rutaIdx = null;
        foreach ($rutasExistentes as $idx => $ruta) {
            if (($ruta['origen'] ?? '') === $ciudadObjetivo || ($ruta['destino'] ?? '') === $ciudadObjetivo) {
                $rutaIdx = $idx;
                break;
            }
        }
        
        if ($rutaIdx === null) {
            Log::warning('⚠️ No se encontró ruta con ciudad', ['ciudad' => $ciudadObjetivo]);
            return $rutasExistentes;
        }
        
        Log::info('✓ Ruta encontrada para modificar', ['ruta_numero' => $rutaIdx + 1, 'origen' => $rutasExistentes[$rutaIdx]['origen']]);
        
        // Extraer modificaciones del texto
        $modificaciones = [];
        
        // 🔧 FIX CRÍTICO: Usar extractPeso() para manejar formato español (12.400 = 12400)
        $pesoExtraido = self::extractPeso($texto);
        if ($pesoExtraido) {
            // 🔧 REGEX MEJORADO: Detectar si ya incluye tara
            $taraYaIncluida = preg_match('/(?:con\s+(?:la\s+)?tara\s+incluida|tara\s+(?:ya\s+)?incluida|(?:ya\s+)?(?:incluye|tiene)\s+(?:la\s+)?tara|con\s+tara(?!\s+(?:no|sin))|peso\s+bruto|(?:kilos?|kilogramos?|kg)\s+con\s+tara)/ui', $texto);
            $sinTara = preg_match('/(?:no\s+incluye|sin)\s*(?:la\s+)?tara|\+\s*tara|m[aá]s\s+tara|peso\s+neto/ui', $texto);
            
            // 🔧 PRIORIDAD: "con tara incluida" > "sin tara" > sin mención
            if ($taraYaIncluida) {
                // El peso YA INCLUYE tara, NO sumar nada
                $modificaciones['peso_kg'] = $pesoExtraido;
                Log::info('✅ TARA YA INCLUIDA en modificación - peso se mantiene', [
                    'peso' => $pesoExtraido
                ]);
            } elseif ($sinTara) {
                // El peso NO incluye tara, SUMAR tara según contenedor
                // 🔧 FIX: Usar tara según tamaño de contenedor (20'=2300, otros=3400)
                $taraRuta = isset($rutasExistentes[$rutaIdx]['tamano_contenedor']) && $rutasExistentes[$rutaIdx]['tamano_contenedor'] == 20 ? 2300 : self::getTaraByContenedor($texto);
                $modificaciones['peso_kg'] = $pesoExtraido + $taraRuta;
                $modificaciones['tara'] = $taraRuta;
                Log::info('⚖️ TARA APLICADA en modificación (sin tara detectado)', [
                    'peso_original' => $pesoExtraido,
                    'tara' => $taraRuta,
                    'peso_con_tara' => $modificaciones['peso_kg']
                ]);
            } else {
                // No menciona tara - mantener el peso sin modificar aquí
                // (la tara se aplica en otros lugares si es necesario)
                $modificaciones['peso_kg'] = $pesoExtraido;
            }
        }
        
        // Producto: "el producto es X" o "producto X"
        if (preg_match('/(?:el\s+)?producto\s+(?:es|sera|:)?\s+([a-záéíóúñ]+)(?:\s+y\s+|\s+,|\s|$)/ui', $texto, $matchProd)) {
            $producto = trim($matchProd[1]);
            $producto = self::removeAccents($producto);
            $modificaciones['producto'] = strtoupper($producto);
        }
        
        // Cantidad: "X unidades/bultos/sacos/cajas" o "cantidad X"
        if (preg_match('/(\d+)\s*(?:unidades?|bultos?|sacos?|cajas?)/ui', $texto, $matchCant)) {
            $modificaciones['cantidad'] = (int)$matchCant[1];
        } elseif (preg_match('/cantidad\s+(?:es\s+)?(\d+)/ui', $texto, $matchCant)) {
            $modificaciones['cantidad'] = (int)$matchCant[1];
        }
        
        // Valor declarado: "valor X millones" o "valor declarado X"
        if (preg_match('/valor(?:\s+declarado)?\s+(?:de\s+)?(?:\$\s*)?(\d+(?:[.,]\d+)?)\s*millones?/ui', $texto, $matchValor)) {
            $modificaciones['valor_declarado'] = (float)str_replace(',', '.', $matchValor[1]) * 1000000;
        }
        
        // Vehículo: "vehículo X" o simplemente nombre del vehículo
        if (preg_match('/veh[íi]culo\s+([a-záéíóúñ]+)/ui', $texto, $matchVeh)) {
            $modificaciones['vehiculo'] = strtoupper($matchVeh[1]);
        } elseif (preg_match('/\b(patineta|tractomula|turbo|sencillo|dobletroque|camioneta)\b/ui', $texto, $matchVeh)) {
            $modificaciones['vehiculo'] = strtoupper($matchVeh[1]);
        }
        
        // Destino: "ahora va a X" o "destino X"
        if (preg_match('/(?:ahora\s+)?va\s+a\s+([a-záéíóúñ]+)/ui', $texto, $matchDest)) {
            $modificaciones['destino'] = self::normalizeCityName($matchDest[1]);
        } elseif (preg_match('/destino\s+(?:es\s+)?([a-záéíóúñ]+)/ui', $texto, $matchDest)) {
            $modificaciones['destino'] = self::normalizeCityName($matchDest[1]);
        }
        
        // Origen: "origen X" o "sale de X"
        if (preg_match('/origen\s+(?:es\s+)?([a-záéíóúñ]+)/ui', $texto, $matchOrig)) {
            $modificaciones['origen'] = self::normalizeCityName($matchOrig[1]);
        } elseif (preg_match('/sale\s+de\s+([a-záéíóúñ]+)/ui', $texto, $matchOrig)) {
            $modificaciones['origen'] = self::normalizeCityName($matchOrig[1]);
        }
        
        if (empty($modificaciones)) {
            Log::warning('⚠️ No se detectaron modificaciones específicas en el texto');
            return $rutasExistentes;
        }
        
        Log::info('✓ Modificaciones detectadas', $modificaciones);
        
        // Aplicar modificaciones a la ruta específica
        foreach ($modificaciones as $campo => $valor) {
            $rutasExistentes[$rutaIdx][$campo] = $valor;
        }
        
        Log::info('✅ Modificaciones aplicadas a ruta', [
            'ruta_numero' => $rutaIdx + 1,
            'campos_modificados' => array_keys($modificaciones)
        ]);
        
        return $rutasExistentes;
    }

    /**
     * �🚚 DETECTAR MÚLTIPLES RUTAS EN EL TEXTO
     * Retorna array de rutas con origen, destino, peso, cantidad y valor EXPLÍCITOS por cada ruta
     */
    private static function detectMultipleRoutes($text)
    {
        $routes = [];
        
        // 🆕 PREPROCESAR TEXTO: Separar palabras pegadas antes de analizar
        $text = TextPreprocessorService::preprocess($text);
        
        Log::info('🔍 Detectando múltiples rutas en texto', [
            'text_preview' => substr($text, 0, 500),
            'text_length' => strlen($text)
        ]);
        
        // 🆕 FIX #569: Detectar patrón "de X a Y y Z" (múltiples destinos desde mismo origen)
        // Ejemplo: "De Cartagena a Bogotá y Medellín" → 2 rutas
        // 🔧 FIX #571: Agregar validaciones para excluir productos y números
        // 🔧 FIX: NO usar este patrón si hay separadores explícitos como "también", "adicional", etc.
        $tieneSeparadorExplicito = preg_match('/\b(también|adicional|ademas|aparte|segunda|tercera)\b/ui', $text);
        
        Log::info('🔍 Verificando patrón "de X a Y y Z"', [
            'tiene_separador_explicito' => $tieneSeparadorExplicito,
            'matchea_patron' => preg_match('/(?:de|desde)\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+)?)\s+a\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+)?)\s+y\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+)?)/ui', $text)
        ]);
        
        if (!$tieneSeparadorExplicito && preg_match('/(?:de|desde)\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+)?)\s+a\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+)?)\s+y\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+)?)/ui', $text, $m)) {
            $origen = trim($m[1]);
            $destino1 = trim($m[2]);
            $destino2 = trim($m[3]);
            
            // Validar que NO sean productos o números
            $palabrasInvalidas = [
                'cafe', 'arroz', 'maiz', 'avena', 'trigo', 'cebada', 'frijol', 'lenteja',
                'banano', 'platano', 'mango', 'papaya', 'naranja', 'limon',
                'papa', 'yuca', 'zanahoria', 'tomate', 'cebolla',
                'carne', 'pollo', 'pescado', 'leche', 'queso', 'huevos',
                'son', 'es', 'hay', 'tiene', 'medio', 'mitad'
            ];
            
            $origenLower = strtolower($origen);
            $destino1Lower = strtolower($destino1);
            $destino2Lower = strtolower($destino2);
            
            // Validar que no sean números o palabras inválidas
            $esValidoOrigen = !in_array($origenLower, $palabrasInvalidas) && !preg_match('/^\d/', $origen);
            $esValidoDestino1 = !in_array($destino1Lower, $palabrasInvalidas) && !preg_match('/^\d/', $destino1);
            $esValidoDestino2 = !in_array($destino2Lower, $palabrasInvalidas) && !preg_match('/^\d/', $destino2);
            
            Log::info('🔍 Validando patrón "de X a Y y Z"', [
                'origen' => $origen,
                'destino1' => $destino1,
                'destino2' => $destino2,
                'valido_origen' => $esValidoOrigen,
                'valido_destino1' => $esValidoDestino1,
                'valido_destino2' => $esValidoDestino2
            ]);
            
            // Solo procesar si todas las ciudades son válidas
            if ($esValidoOrigen && $esValidoDestino1 && $esValidoDestino2) {
                $origenNorm = self::normalizeCityName($origen);
                $destino1Norm = self::normalizeCityName($destino1);
                $destino2Norm = self::normalizeCityName($destino2);
                
                Log::info('🔥 Patrón "de X a Y y Z" VÁLIDO detectado', [
                    'origen' => $origenNorm,
                    'destino1' => $destino1Norm,
                    'destino2' => $destino2Norm
                ]);
                
                // Crear 2 rutas con el mismo origen y datos comunes
                $contextoCompleto = $text;
                
                // Ruta 1: Origen → Destino1
                $ruta1 = self::extractRouteDataFromText($contextoCompleto, 1);
                if ($ruta1) {
                    $ruta1['origen'] = $origenNorm;
                    $ruta1['destino'] = $destino1Norm;
                    $ruta1['ciudad_origen'] = $origenNorm;
                    $ruta1['ciudad_destino'] = $destino1Norm;
                    $ruta1['ruta_numero'] = 1;
                    $routes[] = $ruta1;
                    
                    Log::info('✅ Ruta #1 creada', [
                        'origen' => $origenNorm,
                        'destino' => $destino1Norm,
                        'peso_kg' => $ruta1['peso_kg'] ?? 'N/A'
                    ]);
                }
                
                // Ruta 2: Origen → Destino2 (con los mismos datos)
                $ruta2 = self::extractRouteDataFromText($contextoCompleto, 2);
                if ($ruta2) {
                    $ruta2['origen'] = $origenNorm;
                    $ruta2['destino'] = $destino2Norm;
                    $ruta2['ciudad_origen'] = $origenNorm;
                    $ruta2['ciudad_destino'] = $destino2Norm;
                    $ruta2['ruta_numero'] = 2;
                    $routes[] = $ruta2;
                    
                    Log::info('✅ Ruta #2 creada', [
                        'origen' => $origenNorm,
                        'destino' => $destino2Norm,
                        'peso_kg' => $ruta2['peso_kg'] ?? 'N/A'
                    ]);
                }
                
                if (count($routes) >= 2) {
                    Log::info('✅ Múltiples destinos detectados (Total: ' . count($routes) . ')');
                    return $routes;
                }
            } else {
                Log::info('⏭️ Patrón "de X a Y y Z" detectado pero invalidado por productos/números');
            }
        }
        
        // 🆕 CRÍTICO: Detectar múltiples "cotización de X a Y" en el mismo texto
        // Ej: "cotización de bogotá a Bucaramanga 6 toneladas... cotización de Cali a riohacha 12 toneladas..."
        $patronMultiplesCotizaciones = '/cotizaci[oó]n\s+de\s+/ui';
        if (preg_match_all($patronMultiplesCotizaciones, $text, $matchesCotizacion) && count($matchesCotizacion[0]) >= 2) {
            Log::info('🔥 MÚLTIPLES "cotización de" detectadas', [
                'cantidad' => count($matchesCotizacion[0])
            ]);
            
            // Dividir por "cotización de" manteniendo el delimitador
            $partesCotizacion = preg_split('/(?=cotizaci[oó]n\s+de\s+)/ui', $text, -1, PREG_SPLIT_NO_EMPTY);
            
            Log::info('📦 Texto dividido por "cotización de"', [
                'partes' => count($partesCotizacion),
                'previews' => array_map(function($p) { return substr(trim($p), 0, 100); }, $partesCotizacion)
            ]);
            
            $validRouteCount = 0;
            foreach ($partesCotizacion as $parte) {
                $parte = trim($parte);
                if (empty($parte)) continue;
                
                $routeData = self::extractRouteDataFromText($parte, $validRouteCount + 1);
                
                if ($routeData && (($routeData['origen'] ?? null) || ($routeData['destino'] ?? null))) {
                    $validRouteCount++;
                    $routeData['ruta_numero'] = $validRouteCount;
                    $routes[] = $routeData;
                    
                    Log::info("✅ Ruta #$validRouteCount extraída de 'cotización de'", [
                        'origen' => $routeData['origen'] ?? 'N/A',
                        'destino' => $routeData['destino'] ?? 'N/A',
                        'peso_kg' => $routeData['peso_kg'] ?? 'N/A',
                        'incluye_tara' => $routeData['incluye_tara'] ?? 'no especificado'
                    ]);
                }
            }
            
            if (count($routes) >= 2) {
                Log::info('✅ Múltiples rutas detectadas por "cotización de" (Total: ' . count($routes) . ')');
                return $routes;
            }
        }
        
        // 🆕 CRÍTICO: Detectar MÚLTIPLES PARES "ciudad a ciudad" en el mismo texto
        // Ejemplo: "cali a bucaramanga, 14 toneladas de alimentos... bogotá a ipiales, 4 toneladas de pescado"
        // Este patrón detecta TODOS los pares origen→destino en el texto
        // 🔧 FIX 7: REQUERIR contexto claro "de X a Y" para evitar falsos positivos como "Hola" → "OLA"
        // El patrón ahora DEBE tener "de" antes de la ciudad origen para considerarse válido
        // 🆕 CRÍTICO: PRIMERO detectar si hay separadores explícitos ("también", "adicional", etc.)
        // Si hay separadores → dividir por separadores y procesar cada parte
        // Si NO hay separadores → usar lógica de pares de ciudades
        $separadores = [
            'adicional', 'también', 'ademas', 'aparte', 'y también', 'y ademas', 
            'segunda cotización', 'otra cotización', 'la segunda', 'la tercera',
            'segunda ruta', 'tercera ruta'
        ];
        $tieneSeparador = false;
        $tieneSaltoLinea = false;
        $textLower = mb_strtolower($text);
        
        // Detectar separadores ordinales (primera, segunda, tercera...)
        if (preg_match('/\b(primera|segunda|tercera|cuarta|quinta)\s+(?:ruta|cotizaci|viaje|opci)/ui', $text) || 
            preg_match('/\b(la\s+)?(primera|segunda|tercera)\s+(?:vamos|haremos|es|son)/ui', $text)) {
            $tieneSeparador = true;
            Log::info('🔄 Separador ordinal detectado (primera, segunda, etc)');
        }
        
        // Detectar si hay saltos de línea que separan múltiples cotizaciones
        if (preg_match('/\n\s*cotizaci[oó]n/ui', $text)) {
            $tieneSaltoLinea = true;
            Log::info('🔄 Salto de línea con "cotización" detectado como separador');
        }
        
        foreach ($separadores as $sep) {
            if (strpos($textLower, $sep) !== false) {
                $tieneSeparador = true;
                Log::info('🔄 Separador de múltiples rutas detectado', ['separador' => $sep]);
                break;
            }
        }
        
        // 🆕 SI HAY SEPARADORES → dividir por separadores y procesar cada parte
        if ($tieneSeparador || $tieneSaltoLinea) {
            Log::info('✅ Hay separadores explícitos - usando división por separadores');
            
            // 🔧 FIX: Detectar configuraciones GLOBALES antes de dividir
            // Patrones como "ambas sin tara", "ambas en contenedor", "todas con tara", etc.
            $configGlobal = [
                'incluye_tara' => null,  // null = no especificado, true = incluye, false = no incluye
                'empaque' => null
            ];
            
            // Detectar tara global
            if (preg_match('/(?:ambas?|todas?|los dos|las dos)\s+(?:son\s+)?sin\s+tara/ui', $text)) {
                $configGlobal['incluye_tara'] = false;
                Log::info('🌍 Config GLOBAL detectada: AMBAS sin tara');
            } elseif (preg_match('/(?:ambas?|todas?|los dos|las dos)\s+(?:son\s+)?(?:con|incluye[n]?)\s+tara/ui', $text)) {
                $configGlobal['incluye_tara'] = true;
                Log::info('🌍 Config GLOBAL detectada: AMBAS con tara');
            }
            
            // Detectar empaque global
            if (preg_match('/(?:ambas?|todas?|los dos|las dos)\s+en\s+([a-záéíóúñ\s]+?)(?=\s|$)/ui', $text, $empaqueMatch)) {
                $configGlobal['empaque'] = trim($empaqueMatch[1]);
                Log::info('🌍 Config GLOBAL detectada: AMBAS en ' . $configGlobal['empaque']);
            }
            
            // Dividir por los separadores
            $regexSplit = '/(?:\s*(?:adicional(?:mente)?|también|ademas|aparte|y\s+también|y\s+ademas|(?:\b(?:la\s+|el\s+)?(?:primer[ao]|segund[ao]|tercer[ao]|cuart[ao]|quint[ao])(?:\s+(?:ruta|cotizaci[oó]n|viaje|opci[oó]n))?))\s*(?:requiero?|necesito|solicito|pido|vamos\s+a\s+hacer|es|son|de|desde)?\s*|\n\s*(?=cotizaci[oó]n))/ui';
            
            $partes = preg_split($regexSplit, $text, -1, PREG_SPLIT_NO_EMPTY);
            
            Log::info('📦 Texto dividido por separadores', [
                'partes' => count($partes),
                'previews' => array_map(function($p) { return substr(trim($p), 0, 100); }, $partes)
            ]);
            
            if (count($partes) >= 2) {
                $validRouteCount = 0;
                foreach ($partes as $parte) {
                    $routeData = self::extractRouteDataFromText($parte, 999);
                    
                    if ($routeData && (($routeData['origen'] ?? null) || ($routeData['destino'] ?? null))) {
                        $validRouteCount++;
                        $routeData['ruta_numero'] = $validRouteCount;
                        
                        // 🔧 FIX: Aplicar configuraciones GLOBALES si están definidas
                        if ($configGlobal['incluye_tara'] !== null) {
                            // Recalcular peso según config global
                            if ($configGlobal['incluye_tara'] === false && isset($routeData['peso_kg'])) {
                                // Si config global dice "sin tara" pero ya se sumó tara, restar
                                if ($routeData['incluye_tara'] === false) {
                                    // Ya se sumó tara en extractRouteDataFromText, no hacer nada
                                    Log::info('✅ Tara ya sumada por extractRouteDataFromText');
                                } else {
                                    // No se había sumado, sumar ahora con tara correcta
                                    $pesoOriginal = $routeData['peso_kg'];
                                    // 🔧 FIX: Usar tara según tamaño de contenedor
                                    $taraGlobal = isset($routeData['tamano_contenedor']) && $routeData['tamano_contenedor'] == 20 ? 2300 : 3400;
                                    $routeData['peso_kg'] = $pesoOriginal + $taraGlobal;
                                    $routeData['tara'] = $taraGlobal;
                                    Log::info('📦 TARA SUMADA por config global', [
                                        'peso_original' => $pesoOriginal,
                                        'tara' => $taraGlobal,
                                        'peso_con_tara' => $routeData['peso_kg']
                                    ]);
                                }
                            } elseif ($configGlobal['incluye_tara'] === true && isset($routeData['peso_kg'])) {
                                // Si config global dice "con tara" pero se sumó tara, restar
                                if ($routeData['incluye_tara'] === false) {
                                    // Se sumó tara en extractRouteDataFromText, restar con tara correcta
                                    // 🔧 FIX: Usar tara que se había sumado (guardada en $routeData['tara'])
                                    $taraARestar = $routeData['tara'] ?? (isset($routeData['tamano_contenedor']) && $routeData['tamano_contenedor'] == 20 ? 2300 : 3400);
                                    $routeData['peso_kg'] = $routeData['peso_kg'] - $taraARestar;
                                    Log::info('📦 TARA RESTADA por config global', [
                                        'peso_con_tara' => $routeData['peso_kg'] + $taraARestar,
                                        'tara_restada' => $taraARestar,
                                        'peso_sin_tara' => $routeData['peso_kg']
                                    ]);
                                }
                            }
                            $routeData['incluye_tara'] = $configGlobal['incluye_tara'];
                        }
                        
                        if ($configGlobal['empaque'] !== null && empty($routeData['empaque'])) {
                            $routeData['empaque'] = strtoupper($configGlobal['empaque']);
                        }
                        
                        $routes[] = $routeData;
                        
                        Log::info("✅ Ruta #$validRouteCount extraída de parte separada", [
                            'origen' => $routeData['origen'] ?? 'N/A',
                            'destino' => $routeData['destino'] ?? 'N/A',
                            'peso' => $routeData['peso_kg'] ?? 'N/A'
                        ]);
                    }
                }
                
                if (count($routes) >= 2) {
                    Log::info('✅ Múltiples rutas detectadas con separador (Total: ' . count($routes) . ')');
                    return $routes;
                }
            }
        }
        
        // 🆕 SI NO HAY SEPARADORES → usar lógica de pares de ciudades
        Log::info('⏭️ No hay separadores explícitos - usando detección de pares de ciudades');
        
        $palabrasExcluidas = '(?:millones?|mil|cientos?|miles|toneladas?|bultos?|sacos?|unidades?|cajas?|vehiculos?|veh[íi]culo|turbo|patineta|camioneta?|tractomula|del|con|sin|y|para|desde|' .
            'importaci[oó]n|exportaci[oó]n|cotizaci[oó]n|' .
            'alimentos?|pesca|pescados?|bananos?|cafe|cafés?|arroz|ma[íi]z|cemento|arena|carbon|ganado|lacteos?|frutas?|verduras?|granos?|legumbres?|carne|pollos?|huevos?|azucar|sal|' .
            'neumaticos?|neum[áa]ticos?|llantas?|repuestos?|equipos?|electrodomesticos?|electr[óo]nicos?|maquinaria|herramientas?|materiales?|productos?|mercanc[íi]a|mercanc[íi]as|carga|cargas|' .
            'hola|ayudar|tengo|necesito|podr[íi]as?|servicios?|primero|segundo)';
        
        // 🔧 FIX 8: Patrón más restrictivo - REQUIERE "de" antes de la ciudad origen
        // Esto evita capturar "Hola" como "OLA", "podrías a" como ruta, etc.
        // Formato válido: "de Bogotá a Medellín" o "servicio de Cali a Barranquilla"
        // 🔧 FIX 9: Agregar word boundary después del destino para no capturar "con", "y", etc.
        $patronCiudadACiudad = '/(?:de|desde)\s+(?!' . $palabrasExcluidas . '\b)([a-záéíóúñ]+(?:\s+(?!' . $palabrasExcluidas . '\b)[a-záéíóúñ]+){0,2})\s+(?:a|hacia)\s+(?!' . $palabrasExcluidas . '\b)([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})(?=\s*(?:[,.]|\s+con\s+|\s+y\s+|$))/ui';
        
        $matchesCiudades = [];
        
        // 🆕 FIX 10: TAMBIÉN detectar formato "Origen: X, Destino: Y" (común en prompts estructurados)
        // Ejemplo: "Origen: bogotá, Destino: Buenaventura, Peso: 2,500..."
        $patronOrigenDestino = '/\bOrigen:\s*([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})\s*,\s*Destino:\s*([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})/ui';
        
        if (preg_match_all($patronOrigenDestino, $text, $matchesOD, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            Log::info('🔍 Formato "Origen: X, Destino: Y" detectado', [
                'cantidad' => count($matchesOD),
                'pares' => array_map(function($m) { 
                    return $m[1][0] . ' → ' . $m[2][0]; 
                }, $matchesOD)
            ]);
            $matchesCiudades = $matchesOD;
        } elseif (preg_match_all($patronCiudadACiudad, $text, $matchesCA, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            Log::info('🔍 Formato "de X a Y" detectado', [
                'cantidad' => count($matchesCA),
                'pares' => array_map(function($m) { 
                    return $m[1][0] . ' → ' . $m[2][0]; 
                }, $matchesCA)
            ]);
            $matchesCiudades = $matchesCA;
        }
        
        if (!empty($matchesCiudades)) {
            // Si encontramos 1 o más pares de ciudades
            if (count($matchesCiudades) >= 1) {
                $rutasDetectadas = [];
                
                // 🆕 DETECCIÓN DE TARA GLOBAL: Si "agrega tara" está al INICIO del prompt (antes de especificar rutas),
                // debe aplicarse a TODAS las rutas, no solo a una específica
                $contextoInicial = substr($text, 0, $matchesCiudades[0][0][1]);
                $taraGlobal = false;
                
                if (preg_match('/(?:agrega|agregar|con|incluye|incluir|añade|añadir)\s+(?:la\s+)?tara/ui', $contextoInicial)) {
                    $taraGlobal = true;
                    Log::info('🔧 Tara GLOBAL detectada al inicio del prompt - se aplicará a TODAS las rutas');
                }
                
                // 🔧 FIX #572: Validar que origen y destino NO sean productos, números o palabras de conexión
                $palabrasInvalidas = [
                    // Productos comunes
                    'cafe', 'cafes', 'arroz', 'maiz', 'avena', 'cebada', 'trigo', 'frijol', 'frijoles', 'lenteja', 'lentejas',
                    'carne', 'pollo', 'pescado', 'camarones', 'langostinos', 'lacteos', 'leche', 'queso', 'yogurt',
                    'banano', 'bananos', 'platano', 'platanos', 'frutas', 'verduras', 'legumbres', 'hortalizas',
                    'cemento', 'arena', 'grava', 'carbon', 'madera', 'hierro', 'acero', 'aluminio',
                    'neumaticos', 'llantas', 'repuestos', 'electrodomesticos', 'maquinaria', 'herramientas',
                    // Palabras de conexión/números que pueden capturarse por error
                    'son', 'es', 'hay', 'tiene', 'medio', 'un', 'una', 'dos', 'tres', 'cuatro', 'cinco',
                    'mil', 'miles', 'millon', 'millones', 'kg', 'kilos', 'toneladas', 'ton'
                ];
                
                foreach ($matchesCiudades as $idx => $match) {
                    $origen = self::normalizeCityName(trim($match[1][0]));
                    $destinoRaw = trim($match[2][0]);
                    
                    // 🔧 FIX: Limpiar destino - remover palabras comunes que se peguen
                    $destinoRaw = preg_replace('/\s+(son|es|hay|tiene|kilos?|kgs?|kg|toneladas?|ton)\b.*/ui', '', $destinoRaw);
                    $destino = self::normalizeCityName($destinoRaw);
                    
                    // 🔧 FIX #572: VALIDAR origen y destino antes de crear la ruta
                    $origenLower = mb_strtolower($origen, 'UTF-8');
                    $destinoLower = mb_strtolower($destino, 'UTF-8');
                    
                    $esValidoOrigen = !in_array($origenLower, $palabrasInvalidas) 
                        && !preg_match('/^\d/', $origen) // No empieza con número
                        && strlen($origen) > 2; // Mínimo 3 caracteres
                        
                    $esValidoDestino = !in_array($destinoLower, $palabrasInvalidas) 
                        && !preg_match('/^\d/', $destino)
                        && strlen($destino) > 2;
                    
                    if (!$esValidoOrigen || !$esValidoDestino) {
                        Log::warning("🚫 FIX#572 ACTIVO - Ruta invalidada por producto/número", [
                            'origen' => $origen,
                            'destino' => $destino,
                            'origen_valido' => $esValidoOrigen,
                            'destino_valido' => $esValidoDestino,
                            'timestamp' => now()->toIso8601String()
                        ]);
                        continue; // Saltar esta ruta inválida
                    }
                    
                    // Log de ruta válida para debugging
                    Log::info("✅ FIX#572 - Ruta válida detectada", [
                        'origen' => $origen,
                        'destino' => $destino
                    ]);
                    
                    // Obtener el contexto después de este par de ciudades (hasta el siguiente par o fin de texto)
                    $matchStart = $match[0][1];
                    $matchEnd = $matchStart + strlen($match[0][0]);
                    
                    // 🆕 MEJORA: Incluir también el contexto ANTES del par de ciudades
                    // para capturar datos como "valor declarado de 13.5 millones" que aparecen antes del segundo par
                    $contextoAntes = '';
                    if ($idx > 0) {
                        // Para rutas después de la primera, obtener texto desde el final de la ruta anterior
                        $prevMatchEnd = $matchesCiudades[$idx - 1][0][1] + strlen($matchesCiudades[$idx - 1][0][0]);
                        $contextoAntes = substr($text, $prevMatchEnd, $matchStart - $prevMatchEnd);
                    } else {
                        // Para la primera ruta, obtener texto desde el inicio
                        $contextoAntes = substr($text, 0, $matchStart);
                    }
                    
                    // Buscar el siguiente par de ciudades para delimitar el contexto DESPUÉS
                    $nextMatchStart = isset($matchesCiudades[$idx + 1]) 
                        ? $matchesCiudades[$idx + 1][0][1] 
                        : strlen($text);
                    
                    $contextoDespues = substr($text, $matchEnd, $nextMatchStart - $matchEnd);
                    
                    // Extraer datos de esta ruta desde el contexto
                    $routeData = [
                        'ruta_numero' => $idx + 1,
                        'origen' => $origen,
                        'destino' => $destino
                    ];
                    
                    // 🔧 CRÍTICO: Extraer datos específicos SOLO del contextoDespues (datos que vienen DESPUÉS del par de ciudades)
                    // Esto evita que se mezclen datos de la ruta anterior
                    
                    // 🔧 FIX CRÍTICO: Usar extractPeso() para manejar correctamente formato español (12.400 = 12400)
                    // Además, detectar y aplicar tara si corresponde
                    $pesoExtraido = self::extractPeso($contextoDespues);
                    $sinTaraDetectado = preg_match('/sin\s+tara/ui', $contextoDespues);
                    
                    if ($pesoExtraido) {
                        // Si dice "sin tara", agregar tara según contenedor (20'=2300, otros=3400)
                        if ($sinTaraDetectado) {
                            // 🔧 FIX: Detectar tamaño de contenedor para esta ruta específica
                            $taraRuta = 3400; // Default
                            if (preg_match('/(\d+)\s*[×xX]\s*(20|40|45)/ui', $contextoDespues, $contM)) {
                                $taraRuta = ($contM[2] == '20') ? 2300 : 3400;
                            } elseif (isset($routeData['tamano_contenedor'])) {
                                $taraRuta = ($routeData['tamano_contenedor'] == 20) ? 2300 : 3400;
                            } else {
                                $taraRuta = self::getTaraByContenedor($text);
                            }
                            $routeData['peso_kg'] = $pesoExtraido + $taraRuta;
                            $routeData['peso_bruto'] = $pesoExtraido;
                            $routeData['tara'] = $taraRuta;
                            $routeData['incluye_tara'] = true;
                            Log::info("⚖️ TARA aplicada en extracción de ruta #{$routeData['ruta_numero']}", [
                                'peso_sin_tara' => $pesoExtraido,
                                'tara' => $taraRuta,
                                'peso_con_tara' => $routeData['peso_kg']
                            ]);
                        } else {
                            $routeData['peso_kg'] = $pesoExtraido;
                        }
                    }
                    // Fallback: buscar en contextoAntes
                    elseif ($contextoAntes) {
                        $pesoExtraido = self::extractPeso($contextoAntes);
                        $sinTaraDetectado = preg_match('/sin\s+tara/ui', $contextoAntes);
                        
                        if ($pesoExtraido) {
                            if ($sinTaraDetectado) {
                                // 🔧 FIX: Detectar tamaño de contenedor para tara correcta
                                $taraRuta = 3400; // Default
                                if (preg_match('/(\d+)\s*[×xX]\s*(20|40|45)/ui', $contextoAntes, $contM)) {
                                    $taraRuta = ($contM[2] == '20') ? 2300 : 3400;
                                } elseif (isset($routeData['tamano_contenedor'])) {
                                    $taraRuta = ($routeData['tamano_contenedor'] == 20) ? 2300 : 3400;
                                } else {
                                    $taraRuta = self::getTaraByContenedor($text);
                                }
                                $routeData['peso_kg'] = $pesoExtraido + $taraRuta;
                                $routeData['peso_bruto'] = $pesoExtraido;
                                $routeData['tara'] = $taraRuta;
                                $routeData['incluye_tara'] = true;
                            } else {
                                $routeData['peso_kg'] = $pesoExtraido;
                            }
                        }
                    }
                    
                    // 🔧 CRÍTICO: Usar extractProducto() para distinguir correctamente producto de embalaje
                    // Buscar en el contextoDespues PRIMERO (datos específicos de esta ruta)
                    $productoExtraido = self::extractProducto($contextoDespues);
                    if ($productoExtraido) {
                        $routeData['producto'] = $productoExtraido;
                        Log::info("📦 Producto extraído de contextoDespues (Ruta #{$routeData['ruta_numero']})", [
                            'producto' => $productoExtraido,
                            'contexto' => substr($contextoDespues, 0, 150)
                        ]);
                    }
                    // Si no se encuentra producto después, buscar antes (fallback)
                    elseif ($contextoAntes) {
                        $productoExtraido = self::extractProducto($contextoAntes);
                        if ($productoExtraido) {
                            $routeData['producto'] = $productoExtraido;
                            Log::info("📦 Producto extraído de contextoAntes (Ruta #{$routeData['ruta_numero']})", [
                                'producto' => $productoExtraido,
                                'contexto' => substr($contextoAntes, 0, 150)
                            ]);
                        }
                    }
                    
                    // Extraer valor declarado del contextoDespues
                    $valorExtraido = self::extractValorDeclarado($contextoDespues);
                    if ($valorExtraido) {
                        $routeData['valor_declarado'] = $valorExtraido;
                        Log::info("💰 Valor declarado extraído (Ruta #{$routeData['ruta_numero']})", [
                            'valor' => $valorExtraido,
                            'contexto' => substr($contextoDespues, 0, 150)
                        ]);
                    }
                    // Si no se encuentra valor después, buscar antes (fallback)
                    elseif ($contextoAntes) {
                        $valorExtraido = self::extractValorDeclarado($contextoAntes);
                        if ($valorExtraido) {
                            $routeData['valor_declarado'] = $valorExtraido;
                            Log::info("💰 Valor declarado extraído del contextoAntes (Ruta #{$routeData['ruta_numero']})", [
                                'valor' => $valorExtraido
                            ]);
                        }
                    }
                    
                    // Extraer cantidad del contextoDespues PRIMERO
                    // 🔧 MEJORADO: Buscar diferentes tipos de unidades (unidades, bultos, sacos, cajas)
                    if (preg_match('/(\d+)\s*(?:unidades?|bultos?|sacos?|cajas?)/ui', $contextoDespues, $cantMatch)) {
                        $routeData['cantidad'] = (int)$cantMatch[1];
                        Log::info("📊 Cantidad extraída (Ruta #{$routeData['ruta_numero']})", [
                            'cantidad' => $routeData['cantidad'],
                            'tipo' => $cantMatch[0],
                            'contexto' => substr($contextoDespues, 0, 100)
                        ]);
                    }
                    // 🔧 FIX: NO usar fallback de contextoAntes para cantidad
                    // La cantidad es específica de cada ruta, no se debe compartir
                    
                    // 🆕 FIX CRÍTICO: Valor declarado en múltiples rutas
                    // Para PRIMERA RUTA (idx=0): buscar en contextoAntes O contextoDespues
                    // Para RUTAS SIGUIENTES: SOLO buscar en contextoDespues (su propio contexto)
                    $valorDeclaradoEncontrado = false;
                    
                    if ($idx === 0) {
                        // Primera ruta: puede tener valor antes o después
                        if (preg_match('/valor(?:\s+declarado)?\s+(?:de\s+)?(?:\$\s*)?(\d+(?:[.,]\d+)?)\s*millones?/ui', $contextoDespues, $valorMatch)) {
                            $routeData['valor_declarado'] = (float)str_replace(',', '.', $valorMatch[1]) * 1000000;
                            $valorDeclaradoEncontrado = true;
                        } elseif (preg_match('/valor(?:\s+declarado)?\s+(?:de\s+)?(?:\$\s*)?(\d+(?:[.,]\d+)?)\s*millones?/ui', $contextoAntes, $valorMatch)) {
                            $routeData['valor_declarado'] = (float)str_replace(',', '.', $valorMatch[1]) * 1000000;
                            $valorDeclaradoEncontrado = true;
                        }
                    } else {
                        // Rutas siguientes: SOLO buscar en contextoDespues
                        if (preg_match('/valor(?:\s+declarado)?\s+(?:de\s+)?(?:\$\s*)?(\d+(?:[.,]\d+)?)\s*millones?/ui', $contextoDespues, $valorMatch)) {
                            $routeData['valor_declarado'] = (float)str_replace(',', '.', $valorMatch[1]) * 1000000;
                            $valorDeclaradoEncontrado = true;
                            Log::info("💰 Valor declarado encontrado en contextoDespues para ruta #{$routeData['ruta_numero']}", [
                                'valor' => $routeData['valor_declarado'],
                                'contexto_despues' => substr($contextoDespues, 0, 100)
                            ]);
                        }
                        // 🔧 NO buscar en contextoAntes para rutas siguientes
                        // El valor en contextoAntes pertenece a la ruta anterior
                    }
                    
                    // Extraer vehículo (priorizar contextoDespues)
                    // 🔧 MEJORADO: Buscar tipos de vehículo válidos, ignorando palabras como "sería", "seria", etc.
                    // PRIORIDAD 1: Buscar tipos de vehículo específicos (más confiable)
                    if (preg_match('/\b(patineta|tractomula|turbo|sencillo|dobletroque|camioneta|minimula|tractocamión)\b/ui', $contextoDespues, $vehMatch)) {
                        $routeData['vehiculo'] = strtoupper($vehMatch[1]);
                        Log::info("🚛 Vehículo extraído (patrón específico - Ruta #{$routeData['ruta_numero']})", ['vehiculo' => $routeData['vehiculo']]);
                    }
                    // PRIORIDAD 2: Buscar "vehículo TIPO" pero validar que NO sea palabra genérica
                    elseif (preg_match('/veh[ií]culo(?:\s+(?:requerido|necesario))?\s+(?:sería\s+)?([a-záéíóúñ]+)/ui', $contextoDespues, $vehMatch)) {
                        $vehTipo = strtolower($vehMatch[1]);
                        // Validar que NO sea palabra genérica (sería, seria, etc.)
                        if (!in_array($vehTipo, ['seria', 'sería', 'ser', 'es', 'son', 'un', 'una', 'el', 'la'])) {
                            $routeData['vehiculo'] = strtoupper($vehMatch[1]);
                            Log::info("🚛 Vehículo extraído (patrón 'vehículo X' - Ruta #{$routeData['ruta_numero']})", ['vehiculo' => $routeData['vehiculo']]);
                        }
                    }
                    // Fallback: buscar en contextoAntes solo para primera ruta
                    elseif ($idx === 0) {
                        if (preg_match('/\b(patineta|tractomula|turbo|sencillo|dobletroque|camioneta|minimula|tractocamión)\b/ui', $contextoAntes, $vehMatch)) {
                            $routeData['vehiculo'] = strtoupper($vehMatch[1]);
                            Log::info("🚛 Vehículo extraído de contextoAntes (Ruta #{$routeData['ruta_numero']})", ['vehiculo' => $routeData['vehiculo']]);
                        }
                    }
                    
                    // Detectar tara
                    // 🔧 FIX: Primero verificar si hay tara GLOBAL (mencionada al inicio del prompt)
                    if ($taraGlobal) {
                        $routeData['incluye_tara'] = true;
                        Log::info("🔧 Tara GLOBAL aplicada a ruta #{$routeData['ruta_numero']}");
                    }
                    // Si no hay tara global, buscar tara específica SOLO en contextoDespues para evitar duplicación
                    // 🔧 FIX: También detectar "agrega tara", "con tara", "incluye tara"
                    elseif (preg_match('/(?:agrega|agregar|con|incluye|incluir|añade|añadir)\s+(?:la\s+)?tara/ui', $contextoDespues, $taraMatch)) {
                        $routeData['incluye_tara'] = true;
                    } elseif (preg_match('/(?:el\s+)?peso\s+incluy(?:e|a)\s+(?:la\s+)?tara/ui', $contextoDespues, $taraMatch)) {
                        $routeData['incluye_tara'] = true;
                    } elseif (preg_match('/(?:el\s+)?peso\s+no\s+incluy(?:e|a)\s+(?:la\s+)?tara/ui', $contextoDespues, $taraMatch)) {
                        $routeData['incluye_tara'] = false;
                    }
                    
                    // Extraer empaque (priorizar contextoDespues)
                    if (preg_match('/en\s+(bolsas?|cajas?|sacos?|bultos?|toneles?|canecas?)/ui', $contextoDespues, $empMatch)) {
                        $routeData['empaque'] = strtoupper($empMatch[1]);
                    }
                    // Fallback: buscar en contextoAntes
                    elseif (preg_match('/en\s+(bolsas?|cajas?|sacos?|bultos?|toneles?|canecas?)/ui', $contextoAntes, $empMatch)) {
                        $routeData['empaque'] = strtoupper($empMatch[1]);
                    }
                    
                    $rutasDetectadas[] = $routeData;
                    
                    Log::info("✅ Ruta #{$routeData['ruta_numero']} extraída de par ciudad-ciudad", [
                        'origen' => $origen,
                        'destino' => $destino,
                        'peso_kg' => $routeData['peso_kg'] ?? 'N/A',
                        'producto' => $routeData['producto'] ?? 'N/A',
                        'valor_declarado' => $routeData['valor_declarado'] ?? 'N/A',
                        'contexto_antes_preview' => substr($contextoAntes, -50),
                        'contexto_despues_preview' => substr($contextoDespues, 0, 50)
                    ]);
                }
                
                if (count($rutasDetectadas) >= 1) {
                    Log::info('✅ Ruta(s) detectada(s) por pares ciudad-ciudad', ['total' => count($rutasDetectadas)]);
                    return $rutasDetectadas;
                }
            }
        }
        
        // 🆕 CRÍTICO: DETECTAR PARES DE CIUDADES "de X a Y y Z a W"
        // Patrón: "créame una ruta de bogotá a Medellín y Cartagena a San Andrés"
        // Este patrón detecta múltiples pares origen→destino incluso si dice "una ruta"
        $patronParesCiudades = '/(?:de|desde)\s+([a-záéíóúñ\s]+?)\s+(?:a|hasta)\s+([a-záéíóúñ\s]+?)\s+y\s+(?:de\s+)?([a-záéíóúñ\s]+?)\s+(?:a|hasta)\s+([a-záéíóúñ\s]+?)(?:\s+con|\s+de|\s+para|,|\.|\s+y\s+|$)/ui';
        
        if (preg_match($patronParesCiudades, $text, $matchPares)) {
            Log::info('🔍 Patrón de PARES DE CIUDADES detectado', [
                'match' => $matchPares[0],
                'origen1' => $matchPares[1],
                'destino1' => $matchPares[2],
                'origen2' => $matchPares[3],
                'destino2' => $matchPares[4]
            ]);
            
            // Extraer las dos rutas
            $ruta1 = [
                'ruta_numero' => 1,
                'origen' => self::normalizeCityName(trim($matchPares[1])),
                'destino' => self::normalizeCityName(trim($matchPares[2]))
            ];
            
            $ruta2 = [
                'ruta_numero' => 2,
                'origen' => self::normalizeCityName(trim($matchPares[3])),
                'destino' => self::normalizeCityName(trim($matchPares[4]))
            ];
            
            // Extraer datos comunes del resto del texto (después del match)
            $matchEnd = strpos($text, $matchPares[0]) + strlen($matchPares[0]);
            $contextAfter = substr($text, $matchEnd);
            
            // Buscar producto común
            $productoComun = null;
            if (preg_match('/(?:con\s+un?\s+)?producto(?:\s+de)?\s+([a-záéíóúñ\s]+?)(?:\s+para\s+los\s+dos|\s+de\s+\d+|\s+con|,|\.|\s+y\s+|$)/ui', $contextAfter, $prodMatch)) {
                $productoComun = strtoupper(trim($prodMatch[1]));
                Log::info('✅ Producto común detectado', ['producto' => $productoComun]);
            }
            
            // Buscar peso común
            $pesoComun = null;
            if (preg_match('/(?:de|para\s+los\s+dos\s+de)\s+(\d+)\s*(?:toneladas?|ton|kilogramos?|kg)/ui', $contextAfter, $pesoMatch)) {
                $pesoRaw = (int)$pesoMatch[1];
                // Detectar si es toneladas
                if (stripos($pesoMatch[0], 'ton') !== false) {
                    $pesoComun = $pesoRaw * 1000; // Convertir a kg
                } else {
                    $pesoComun = $pesoRaw;
                }
                Log::info('✅ Peso común detectado', ['peso_kg' => $pesoComun]);
            }
            
            // Buscar valor común
            $valorComun = null;
            if (preg_match('/(?:con\s+un?\s+)?valor(?:\s+de)?\s+(\d+)\s*(?:millones?|mill?)/ui', $contextAfter, $valorMatch)) {
                $valorComun = (int)$valorMatch[1] * 1000000;
                Log::info('✅ Valor común detectado', ['valor' => $valorComun]);
            }
            
            // Aplicar datos comunes a ambas rutas
            if ($productoComun) {
                $ruta1['producto'] = $productoComun;
                $ruta2['producto'] = $productoComun;
            }
            if ($pesoComun) {
                $ruta1['peso_kg'] = $pesoComun;
                $ruta2['peso_kg'] = $pesoComun;
            }
            if ($valorComun) {
                $ruta1['valor_declarado'] = $valorComun;
                $ruta2['valor_declarado'] = $valorComun;
            }
            
            $routes = [$ruta1, $ruta2];
            
            Log::info('✅ 2 RUTAS detectadas con patrón de PARES DE CIUDADES', [
                'ruta1' => $ruta1,
                'ruta2' => $ruta2
            ]);
            
            return $routes;
        }
        
        // 🆕 NUEVO PATRÓN: "Una cotización de X a Y, son N kg/toneladas de PRODUCTO..."
        // Formato: "Necesito una cotización de distribución nacionalizada de Medellín a Bogota, son 7 mil kilogramos de vacas..."
        // IMPORTANTE: Usa lookahead (?=\s+por\s+un\s+valor) para capturar correctamente el nombre del producto
        // NOTA: El patrón NO captura "distribución nacionalizada" - estas palabras van antes de "de [CIUDAD]"
        $patronCotizacion = '/(?:una\s+)?cotizaci[oó]n(?:\s+de\s+distribuci[oó]n(?:\s+nacionalizada)?)?\s+(?:de|desde)\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})\s+(?:a|hasta|hacia)\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,1}),?\s+(?:son\s+)?(\d+(?:\s*mil)?)\s*(?:kilogramos?|kg|toneladas?|ton)\s+(?:sin\s+tara\s+de\s+|con\s+tara\s+de\s+|de\s+)?([a-záéíóúñ\s]+?)(?=\s+por\s+un\s+valor)/ui';
        
        if (preg_match_all($patronCotizacion, $text, $matchesCotizacion, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            Log::info('✅ Patrón COTIZACIÓN encontró rutas', [
                'count' => count($matchesCotizacion),
                'matches_preview' => array_map(function($m) { return $m[0][0] ?? ''; }, array_slice($matchesCotizacion, 0, 3))
            ]);
            
            foreach ($matchesCotizacion as $idx => $match) {
                // Limpiar prefijos comunes de las ciudades capturadas
                $origenRaw = trim($match[1][0]);
                $destinoRaw = trim($match[2][0]);
                
                // Remover palabras clave que no son parte del nombre de la ciudad
                $prefijosARemover = ['distribución', 'distribucion', 'nacionalizada', 'nacionalizada de', 'importación', 'importacion', 'exportación', 'exportacion'];
                foreach ($prefijosARemover as $prefijo) {
                    $origenRaw = preg_replace('/^' . preg_quote($prefijo, '/') . '\s+(?:de\s+)?/ui', '', $origenRaw);
                    $destinoRaw = preg_replace('/^' . preg_quote($prefijo, '/') . '\s+(?:de\s+)?/ui', '', $destinoRaw);
                }
                
                $origen = self::normalizeCityName(trim($origenRaw));
                $destino = self::normalizeCityName(trim($destinoRaw));
                
                // Parsear peso (puede tener "mil")
                $pesoRaw = trim($match[3][0]);
                $peso = 0;
                if (preg_match('/(\d+)\s*mil/i', $pesoRaw, $pesoMil)) {
                    $peso = (int)$pesoMil[1] * 1000;
                } else {
                    $peso = (int)preg_replace('/[^\d]/', '', $pesoRaw);
                }
                
                // Detectar si es toneladas (buscar en el match completo)
                $matchFullText = $match[0][0];
                if (stripos($matchFullText, 'tonelada') !== false || stripos($matchFullText, 'ton ') !== false) {
                    $peso = $peso * 1000;
                }
                
                // Detectar tara
                $sinTara = stripos($matchFullText, 'sin tara') !== false;
                $conTara = stripos($matchFullText, 'con tara') !== false;
                
                // Producto - ya viene limpio con el lookahead
                $productoRaw = trim($match[4][0]);
                $productoClean = trim(preg_replace('/\s+/', ' ', $productoRaw));
                
                $route = [
                    'ruta_numero' => $idx + 1,
                    'origen' => $origen,
                    'destino' => $destino,
                    'peso_kg' => $peso,
                    'producto' => strtoupper($productoClean),
                ];
                
                if ($conTara) {
                    $route['incluye_tara'] = true;
                }
                
                // Buscar valor y cantidad en el contexto DESPUÉS del match
                // El lookahead no consume el texto, así que buscamos a partir del offset + length
                $matchEnd = $match[0][1] + strlen($match[0][0]);
                $contextAfter = substr($text, $matchEnd, 200); // Siguientes 200 chars
                
                // Buscar valor declarado: "por un valor (declarado) (de) N millones"
                if (preg_match('/por\s+un\s+valor(?:\s+declarado)?\s+(?:de\s+)?(\d+)\s*(?:millones?|mill?)/ui', $contextAfter, $valorMatch)) {
                    $route['valor_declarado'] = (int)$valorMatch[1] * 1000000;
                }
                
                // Buscar cantidad/unidades: "N unidades"
                if (preg_match('/(\d+)\s*(?:unidades?|uds?)/ui', $contextAfter, $cantidadMatch)) {
                    $route['cantidad'] = (int)$cantidadMatch[1];
                }
                
                // Detectar vehículo único
                if (stripos($contextAfter, 'único vehículo') !== false || 
                    stripos($contextAfter, 'unico vehiculo') !== false ||
                    stripos($contextAfter, 'un solo vehículo') !== false) {
                    $route['vehiculo'] = 'SENCILLO';
                }
                
                $routes[] = $route;
                
                Log::info("✅ Ruta #{$route['ruta_numero']} detectada (patrón COTIZACIÓN)", [
                    'origen' => $origen,
                    'destino' => $destino,
                    'peso_kg' => $peso,
                    'producto' => $route['producto'],
                    'valor' => $route['valor_declarado'] ?? 'N/A',
                    'cantidad' => $route['cantidad'] ?? 'N/A'
                ]);
            }
            
            if (count($routes) >= 2) {
                Log::info('✅ Múltiples rutas detectadas con patrón COTIZACIÓN', ['total' => count($routes)]);
                return $routes;
            }
        }
        
        // 🆕 NUEVO: Detectar patrón "la primera ... la segunda ..." sin números
        // Ej: "la primera Bogotá a Medellín, 10 toneladas de maíz, 60 sacos, valor 30 millones"
        // Patrón mejorado para capturar producto, cantidad/empaque y valor correctamente
        $patronPrimeraSegunda = '/(?:la\s+)?(primera|segunda|tercera|cuarta|quinta)(?:\s+ruta)?\s+(?:es\s+)?(?:de\s+)?([a-záéíóúñ\s]+?)\s+(?:a|hasta)\s+([a-záéíóúñ\s]+?),?\s+(\d+)\s*(?:toneladas?|ton|kg|kilos?)\s+de\s+([a-záéíóúñ]+)(?:[,\s]+(\d+)\s*(sacos?|cajas?|bultos?|unidades?))?(?:[,\s]+(?:valor|por)\s*(?:de)?\s*\$?\s*(\d+)\s*(?:millones?|mill?))?/ui';
        
        if (preg_match_all($patronPrimeraSegunda, $text, $matchesPrimeraSegunda, PREG_SET_ORDER)) {
            Log::info('✅ Patrón primera/segunda encontró rutas', [
                'count' => count($matchesPrimeraSegunda),
                'matches_raw' => $matchesPrimeraSegunda
            ]);
            
            $ordinales = ['primera' => 1, 'segunda' => 2, 'tercera' => 3, 'cuarta' => 4, 'quinta' => 5];
            
            // Mapa de empaque a nombre normalizado y ID
            $empaqueMap = [
                'saco' => ['nombre' => 'SACOS', 'id' => 4],
                'sacos' => ['nombre' => 'SACOS', 'id' => 4],
                'caja' => ['nombre' => 'CAJAS', 'id' => 2],
                'cajas' => ['nombre' => 'CAJAS', 'id' => 2],
                'bulto' => ['nombre' => 'BULTOS', 'id' => 5],
                'bultos' => ['nombre' => 'BULTOS', 'id' => 5],
                'unidad' => ['nombre' => 'UNIDADES', 'id' => 1],
                'unidades' => ['nombre' => 'UNIDADES', 'id' => 1],
            ];
            
            foreach ($matchesPrimeraSegunda as $match) {
                $ordinal = strtolower(trim($match[1]));
                $numeroRuta = $ordinales[$ordinal] ?? count($routes) + 1;
                
                $origen = trim($match[2]);
                $destino = trim($match[3]);
                $pesoRaw = (float)$match[4];
                $producto = isset($match[5]) ? trim($match[5]) : null;
                $cantidad = isset($match[6]) ? (int)$match[6] : null;
                $empaqueRaw = isset($match[7]) ? strtolower(trim($match[7])) : null;
                $valorMillones = isset($match[8]) ? (float)str_replace(',', '.', $match[8]) : null;
                
                // Limpiar nombres de ciudades
                $origenParts = array_slice(explode(' ', $origen), 0, 3);
                $destinoParts = array_slice(explode(' ', $destino), 0, 3);
                
                $origenNormalized = self::normalizeCityName(implode(' ', $origenParts));
                $destinoNormalized = self::normalizeCityName(implode(' ', $destinoParts));
                
                // Detectar si el peso está en toneladas o kg
                $pesoKg = stripos($text, 'tonelada') !== false || stripos($text, 'ton') !== false
                    ? (int)($pesoRaw * 1000)
                    : (int)$pesoRaw;
                
                $route = [
                    'ruta_numero' => $numeroRuta,
                    'origen' => $origenNormalized,
                    'destino' => $destinoNormalized,
                    'peso_kg' => $pesoKg,
                ];
                
                // Limpiar producto de palabras no válidas
                if ($producto) {
                    $excluir = ['toneladas', 'ton', 'kilos', 'kg', 'para', 'llevar', 'y', 'la', 'estoy', 'sacos', 'cajas', 'bultos'];
                    $productoParts = explode(' ', $producto);
                    $productoLimpio = [];
                    foreach ($productoParts as $part) {
                        if (!in_array(strtolower($part), $excluir) && strlen($part) >= 2) {
                            $productoLimpio[] = $part;
                        }
                    }
                    if (!empty($productoLimpio)) {
                        $route['producto'] = strtoupper(implode(' ', $productoLimpio));
                    }
                }
                
                if ($cantidad) {
                    $route['cantidad'] = $cantidad;
                }
                
                // 🆕 Detectar tipo de empaque
                if ($empaqueRaw && isset($empaqueMap[$empaqueRaw])) {
                    $route['empaque'] = $empaqueMap[$empaqueRaw]['nombre'];
                    $route['empaque_id'] = $empaqueMap[$empaqueRaw]['id'];
                }
                
                if ($valorMillones) {
                    $route['valor_declarado'] = (int)($valorMillones * 1000000);
                }
                
                $routes[] = $route;
                
                Log::info("✅ Ruta #{$numeroRuta} detectada (patrón primera/segunda)", [
                    'ordinal' => $ordinal,
                    'origen' => $route['origen'],
                    'destino' => $route['destino'],
                    'peso_kg' => $route['peso_kg'],
                    'producto' => $route['producto'] ?? 'N/A',
                    'cantidad' => $route['cantidad'] ?? 'N/A',
                    'empaque' => $route['empaque'] ?? 'N/A',
                    'valor' => $route['valor_declarado'] ?? 'N/A'
                ]);
            }
            
            // Si encontramos rutas con este patrón, retornarlas
            if (count($routes) >= 2) {
                Log::info('✅ Múltiples rutas detectadas con patrón primera/segunda', ['total' => count($routes)]);
                return $routes;
            }
        }
        
        // 🆕 PRIMERO: Intentar dividir por patrones numéricos (1., 2., 3.) dentro del texto
        // Esto funciona tanto para líneas separadas como para texto en una sola línea
        $segments = preg_split('/(?=\b\d+\.\s*(?:primera|segunda|tercera|cuarta|quinta|ruta)?)/ui', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Si no hay múltiples segmentos, intentar dividir solo por "N."
        if (count($segments) <= 1) {
            $segments = preg_split('/(?=\b\d+\.)/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        }
        
        Log::info('🔍 Segmentos detectados', [
            'count' => count($segments),
            'segments_preview' => array_map(function($s) { return substr(trim($s), 0, 80); }, array_slice($segments, 0, 5))
        ]);
        
        $rutaSecuencial = 1;
        
        foreach ($segments as $line) {
            $line = trim($line);
            
            // Detectar si el segmento empieza con número (ej: "1." o "2.")
            if (!preg_match('/^\s*(\d+)\./', $line)) {
                continue;
            }
            
            // Extraer origen y destino: varios formatos soportados
            // "Primera ruta: Bogotá a Medellín" o ": Bogotá a Medellín" o "Bogotá a Medellín"
            $ciudadesMatch = null;
            
            // Patrón 1: "ruta: Ciudad a Ciudad" o ": Ciudad a Ciudad"
            if (preg_match('/(?:ruta)?:?\s*([a-záéíóúñ\s]+?)\s+(?:a|hasta)\s+([a-záéíóúñ\s]+?)[\s,]/ui', $line, $match)) {
                $ciudadesMatch = $match;
            }
            // Patrón 2: "de Ciudad a Ciudad"
            elseif (preg_match('/(?:de|desde)\s+([a-záéíóúñ\s]+?)\s+(?:a|hasta)\s+([a-záéíóúñ\s]+?)[\s,]/ui', $line, $match)) {
                $ciudadesMatch = $match;
            }
            
            if (!$ciudadesMatch) {
                Log::info('⚠️ No se pudo extraer ciudades de segmento', ['line' => substr($line, 0, 100)]);
                continue;
            }
            
            $origen = trim($ciudadesMatch[1]);
            $destino = trim($ciudadesMatch[2]);
            
            // Limpiar nombres de ciudades (máximo 3 palabras)
            $origenParts = array_slice(explode(' ', $origen), 0, 3);
            $destinoParts = array_slice(explode(' ', $destino), 0, 3);
            
            // 🆕 NORMALIZAR nombres de ciudades
            $origenNormalized = self::normalizeCityName(implode(' ', $origenParts));
            $destinoNormalized = self::normalizeCityName(implode(' ', $destinoParts));
            
            $route = [
                'ruta_numero' => $rutaSecuencial++,
                'origen' => $origenNormalized,
                'destino' => $destinoNormalized,
            ];
            
            // 🆕 Extraer PESO de esta línea específica
            if (preg_match('/(\d+)\s*(?:toneladas?|ton)/ui', $line, $pesoMatch)) {
                $route['peso_kg'] = (int)((float)$pesoMatch[1] * 1000);
            } elseif (preg_match('/(\d+)\s*(?:kg|kilos?|kilogramos?)/ui', $line, $pesoMatch)) {
                $route['peso_kg'] = (int)$pesoMatch[1];
            }
            
            // 🆕 Extraer CANTIDAD de esta línea específica
            if (preg_match('/(\d+)\s*(?:unidades?|uds?|piezas?|cajas?|bultos?|sacos?|paquetes?)/ui', $line, $cantidadMatch)) {
                $route['cantidad'] = (int)$cantidadMatch[1];
            }
            
            // 🆕 Extraer VALOR de esta línea específica
            if (preg_match('/(?:valor|por)\s*(?:de)?\s*\$?\s*(\d+(?:[.,]\d+)?)\s*(?:millones?|mill?)/ui', $line, $valorMatch)) {
                $millones = (float)str_replace(',', '.', $valorMatch[1]);
                $route['valor_declarado'] = (int)($millones * 1000000);
            } elseif (preg_match('/\$\s*(\d{1,3}(?:[.,]\d{3})+)/ui', $line, $valorMatch)) {
                $route['valor_declarado'] = (int)str_replace(['.', ','], '', $valorMatch[1]);
            }
            
            // 🆕 Extraer PRODUCTO de esta línea específica
            if (preg_match('/(?:de|con)\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+)?)/ui', $line, $productoMatch)) {
                $productoCandidate = trim($productoMatch[1]);
                // Excluir palabras comunes que no son productos
                $excluir = ['toneladas', 'ton', 'kilos', 'kg', 'unidades', 'cajas', 'bultos', 'millones', 'pesos', 'valor'];
                if (!in_array(strtolower($productoCandidate), $excluir)) {
                    $route['producto'] = ucwords(strtolower($productoCandidate));
                }
            }
            
            $routes[] = $route;
            
            Log::info("✅ Ruta #{$route['ruta_numero']} detectada con datos explícitos", [
                'linea' => substr($line, 0, 100),
                'origen' => $route['origen'],
                'destino' => $route['destino'],
                'peso_kg' => $route['peso_kg'] ?? 'N/A',
                'cantidad' => $route['cantidad'] ?? 'N/A',
                'valor' => $route['valor_declarado'] ?? 'N/A',
                'producto' => $route['producto'] ?? 'N/A'
            ]);
        }
        
        // Si no se detectaron rutas por líneas, intentar patrón alternativo
        if (count($routes) === 0) {
            Log::info('🔄 Intentando patrones alternativos para múltiples rutas...');
            
            // 🆕 PATRÓN 1: "cotización de X a Y N toneladas de PRODUCTO sin tara son N unidades en EMPAQUE tipo de vehículo VEHICULO valor declarado N millones"
            // Ejemplo: "cotización de Bogotá a cali 15 toneladas de maíz sin tara son 30 unidades en cajas tipo de vehículo patineta valor declarado 15 millones"
            $patronCotizacionCompleto = '/cotizaci[oó]n\s+de\s+([a-záéíóúñ]+)\s+a\s+([a-záéíóúñ]+)\s+(\d+)\s*(toneladas?|kg|kilos?)\s+de\s+([a-záéíóúñ]+)\s+(?:sin|con)\s+tara\s+son\s+(\d+)\s+unidades?\s+en\s+([a-záéíóúñ]+)\s+tipo\s+de\s+veh[ií]culo\s+([a-záéíóúñ]+)\s+valor\s+declarado\s+(\d+)\s*millones?/ui';
            
            if (preg_match_all($patronCotizacionCompleto, $text, $matchesCotCompleto, PREG_SET_ORDER)) {
                Log::info('✅ Patrón COTIZACIÓN COMPLETO encontró rutas', ['count' => count($matchesCotCompleto)]);
                
                foreach ($matchesCotCompleto as $idx => $match) {
                    $origen = self::normalizeCityName(trim($match[1]));
                    $destino = self::normalizeCityName(trim($match[2]));
                    $peso = (int)$match[3];
                    $unidad = strtolower($match[4]);
                    
                    // Convertir a kg si es toneladas
                    if (strpos($unidad, 'ton') !== false) {
                        $peso = $peso * 1000;
                    }
                    
                    $route = [
                        'ruta_numero' => $idx + 1,
                        'origen' => $origen,
                        'destino' => $destino,
                        'peso_kg' => $peso,
                        'producto' => strtoupper(trim($match[5])),
                        'tipo_producto' => strtoupper(trim($match[5])),
                        'cantidad' => (int)$match[6],
                        'empaque' => strtoupper(trim($match[7])),
                        'vehiculo' => strtoupper(trim($match[8])),
                        'claseVehiculo' => strtoupper(trim($match[8])),
                        'vehiculo_requerido' => strtoupper(trim($match[8])),
                        'valor_declarado' => (int)$match[9] * 1000000,
                    ];
                    
                    $routes[] = $route;
                    
                    Log::info("✅ Ruta #{$route['ruta_numero']} detectada (patrón COTIZACIÓN COMPLETO)", [
                        'origen' => $origen,
                        'destino' => $destino,
                        'peso_kg' => $peso,
                        'producto' => $route['producto'],
                        'cantidad' => $route['cantidad'],
                        'empaque' => $route['empaque'],
                        'vehiculo' => $route['vehiculo'],
                        'valor' => $route['valor_declarado']
                    ]);
                }
                
                if (count($routes) >= 1) {
                    Log::info('✅ Rutas detectadas con patrón COTIZACIÓN COMPLETO', ['total' => count($routes)]);
                    return $routes;
                }
            }
            
            // 🆕 PATRÓN ANTIGUO (más flexible): "cotización de X a Y N toneladas de PRODUCTO..."
            // Ejemplo: "cotización de Bogotá a Buenaventura 15 toneladas de maíz sin tara son 30 unidades..."
            $patronCotizacionDeA = '/cotizaci[oó]n\s+de\s+([a-záéíóúñ]+)\s+a\s+([a-záéíóúñ]+)\s+(\d+)\s*(toneladas?|kg|kilos?)(?:\s+de\s+([a-záéíóúñ]+))?(?:\s+sin\s+tara)?(?:\s+son\s+(\d+)\s+unidades?)?(?:\s+en\s+([a-záéíóúñ]+))?(?:\s+tipo\s+de\s+veh[ií]culo\s+([a-záéíóúñ]+))?(?:\s+valor\s+declarado\s+(\d+)\s*millones?)?/ui';
            
            if (preg_match_all($patronCotizacionDeA, $text, $matchesCotDeA, PREG_SET_ORDER)) {
                Log::info('✅ Patrón COTIZACIÓN DE-A encontró rutas', ['count' => count($matchesCotDeA)]);
                
                foreach ($matchesCotDeA as $idx => $match) {
                    $origen = self::normalizeCityName(trim($match[1]));
                    $destino = self::normalizeCityName(trim($match[2]));
                    $peso = (int)$match[3];
                    $unidad = strtolower($match[4]);
                    
                    // Convertir a kg si es toneladas
                    if (strpos($unidad, 'ton') !== false) {
                        $peso = $peso * 1000;
                    }
                    
                    $route = [
                        'ruta_numero' => $idx + 1,
                        'origen' => $origen,
                        'destino' => $destino,
                        'peso_kg' => $peso,
                    ];
                    
                    // Producto (grupo 5)
                    if (!empty($match[5])) {
                        $route['producto'] = strtoupper(trim($match[5]));
                    }
                    
                    // Cantidad (grupo 6)
                    if (!empty($match[6])) {
                        $route['cantidad'] = (int)$match[6];
                    }
                    
                    // Empaque (grupo 7)
                    if (!empty($match[7])) {
                        $route['empaque'] = strtoupper(trim($match[7]));
                    }
                    
                    // Vehículo (grupo 8)
                    if (!empty($match[8])) {
                        $route['vehiculo'] = strtoupper(trim($match[8]));
                    }
                    
                    // Valor declarado (grupo 9) - en millones
                    if (!empty($match[9])) {
                        $route['valor_declarado'] = (int)$match[9] * 1000000;
                    }
                    
                    $routes[] = $route;
                    
                    Log::info("✅ Ruta #{$route['ruta_numero']} detectada (patrón COTIZACIÓN DE-A)", [
                        'origen' => $origen,
                        'destino' => $destino,
                        'peso_kg' => $peso,
                        'producto' => $route['producto'] ?? '-',
                        'cantidad' => $route['cantidad'] ?? '-',
                        'empaque' => $route['empaque'] ?? '-',
                        'vehiculo' => $route['vehiculo'] ?? '-',
                        'valor' => $route['valor_declarado'] ?? '-'
                    ]);
                }
                
                if (count($routes) >= 1) {
                    Log::info('✅ Rutas detectadas con patrón COTIZACIÓN DE-A', ['total' => count($routes)]);
                    return $routes;
                }
            }
            
            // 🆕 PATRÓN 2: "cotización ORIGEN DESTINO de N toneladas/kg..."
            // Ejemplo: "cotización Buenaventura Bogotá de 16 toneladas sin tara transportando neumáticos..."
            $patronCotizacionSimple = '/cotizaci[oó]n\s+([a-záéíóúñ]+)\s+([a-záéíóúñ]+)\s+de\s+(\d+)\s*(toneladas?|kg|kilos?)(?:\s+sin\s+tara)?(?:\s+transportando\s+([a-záéíóúñ]+))?(?:\s+en\s+(\d+)\s+unidades?)?(?:\s+empaquetad[ao]s?\s+en\s+([a-záéíóúñ]+))?(?:\s+tipo\s+de\s+veh[ií]culo\s+requerido\s+([a-záéíóúñ]+))?/ui';
            
            if (preg_match_all($patronCotizacionSimple, $text, $matchesCotSimple, PREG_SET_ORDER)) {
                Log::info('✅ Patrón COTIZACIÓN SIMPLE encontró rutas', ['count' => count($matchesCotSimple)]);
                
                foreach ($matchesCotSimple as $idx => $match) {
                    $origen = self::normalizeCityName(trim($match[1]));
                    $destino = self::normalizeCityName(trim($match[2]));
                    $peso = (int)$match[3];
                    $unidad = strtolower($match[4]);
                    
                    // Convertir a kg si es toneladas
                    if (strpos($unidad, 'ton') !== false) {
                        $peso = $peso * 1000;
                    }
                    
                    $route = [
                        'ruta_numero' => $idx + 1,
                        'origen' => $origen,
                        'destino' => $destino,
                        'peso_kg' => $peso,
                    ];
                    
                    // Producto (grupo 5)
                    if (!empty($match[5])) {
                        $route['producto'] = strtoupper(trim($match[5]));
                    }
                    
                    // Cantidad (grupo 6)
                    if (!empty($match[6])) {
                        $route['cantidad'] = (int)$match[6];
                    }
                    
                    // Empaque (grupo 7)
                    if (!empty($match[7])) {
                        $route['empaque'] = strtoupper(trim($match[7]));
                    }
                    
                    // Vehículo (grupo 8)
                    if (!empty($match[8])) {
                        $route['vehiculo'] = strtoupper(trim($match[8]));
                    }
                    
                    $routes[] = $route;
                    
                    Log::info("✅ Ruta #{$route['ruta_numero']} detectada (patrón COTIZACIÓN SIMPLE)", [
                        'origen' => $origen,
                        'destino' => $destino,
                        'peso_kg' => $peso,
                        'producto' => $route['producto'] ?? '-',
                        'cantidad' => $route['cantidad'] ?? '-',
                        'empaque' => $route['empaque'] ?? '-',
                        'vehiculo' => $route['vehiculo'] ?? '-'
                    ]);
                }
                
                if (count($routes) >= 1) {
                    Log::info('✅ Rutas detectadas con patrón COTIZACIÓN SIMPLE', ['total' => count($routes)]);
                    return $routes;
                }
            }
            
            // Patrón 1: "la primera/segunda es de X a Y para llevar N toneladas de PRODUCTO"
            $altPattern1 = '/(?:la\s+)?(?:primera|segunda|tercera|cuarta|quinta)(?:\s+ruta)?\s+es\s+(?:de|desde)\s+([a-záéíóúñ\s]+?)\s+(?:a|hasta)\s+([a-záéíóúñ\s]+?)(?:\s+para\s+llevar)?\s+(\d+)\s*(?:toneladas?|ton)(?:\s+de\s+([a-záéíóúñ\s]+?))?(?:\s+y\s+la|\s+y|\.|,|$|\s+estoy)/ui';
            
            // Patrón 2: "primera de X a Y, N toneladas de PRODUCTO"
            $altPattern2 = '/(?:primera|segunda|tercera|cuarta|quinta)\s+(?:de|desde)\s+([a-záéíóúñ\s]+?)\s+(?:a|hasta)\s+([a-záéíóúñ\s]+?)[\s,]+(\d+)\s*(?:toneladas?|ton)(?:\s+de\s+([a-záéíóúñ\s]+?))?(?:\s+y|\.|,|$)/ui';
            
            $numeroRuta = 1;
            $allMatches = [];
            
            // Intentar ambos patrones
            if (preg_match_all($altPattern1, $text, $matches1, PREG_SET_ORDER)) {
                $allMatches = array_merge($allMatches, $matches1);
                Log::info('✅ Patrón 1 encontró rutas', ['count' => count($matches1), 'matches' => $matches1]);
            }
            
            if (preg_match_all($altPattern2, $text, $matches2, PREG_SET_ORDER)) {
                $allMatches = array_merge($allMatches, $matches2);
                Log::info('✅ Patrón 2 encontró rutas', ['count' => count($matches2), 'matches' => $matches2]);
            }
            
            if (!empty($allMatches)) {
                foreach ($allMatches as $match) {
                    $origen = trim($match[1]);
                    $destino = trim($match[2]);
                    $toneladas = (float)$match[3];
                    $productoRaw = isset($match[4]) ? trim($match[4]) : null;
                    
                    $origenParts = array_slice(explode(' ', $origen), 0, 3);
                    $destinoParts = array_slice(explode(' ', $destino), 0, 3);
                    
                    // 🆕 NORMALIZAR nombres de ciudades
                    $origenNormalized = self::normalizeCityName(implode(' ', $origenParts));
                    $destinoNormalized = self::normalizeCityName(implode(' ', $destinoParts));
                    
                    // 🆕 Procesar producto capturado en el regex
                    $producto = null;
                    if ($productoRaw) {
                        $productoCandidate = trim($productoRaw);
                        $excluir = ['toneladas', 'ton', 'kilos', 'kg', 'para', 'llevar', 'y', 'la', 'estoy'];
                        if (!in_array(strtolower($productoCandidate), $excluir) && strlen($productoCandidate) >= 3) {
                            $producto = strtoupper($productoCandidate);
                            Log::info('✅ Producto capturado del regex', ['producto' => $producto]);
                        }
                    }
                    
                    // 🆕 Extraer cantidad (unidades, cajas, bultos, etc.)
                    $cantidad = null;
                    if (preg_match('/(\d+)\s*(?:unidades?|uds?|cajas?|bultos?|sacos?|paquetes?)/ui', $match[0], $cantMatch)) {
                        $cantidad = (int)$cantMatch[1];
                    } elseif ($productoRaw) {
                        // Si no se especifica cantidad pero sí hay producto y peso, asumir cantidad = 1
                        $cantidad = 1;
                        Log::info('⚠️ Cantidad no especificada, asumiendo 1 unidad por defecto');
                    }
                    
                    $route = [
                        'ruta_numero' => $numeroRuta++,
                        'origen' => $origenNormalized,
                        'destino' => $destinoNormalized,
                        'peso_kg' => (int)($toneladas * 1000)
                    ];
                    
                    if ($producto) {
                        $route['producto'] = $producto;
                    }
                    
                    if ($cantidad) {
                        $route['cantidad'] = $cantidad;
                    }
                    
                    $routes[] = $route;
                    
                    Log::info("✅ Ruta #{$route['ruta_numero']} detectada (patrón alt)", [
                        'origen' => $route['origen'],
                        'destino' => $route['destino'],
                        'peso_kg' => $route['peso_kg'],
                        'producto' => $producto ?? 'N/A',
                        'cantidad' => $cantidad ?? 'N/A',
                        'match_completo' => $match[0]
                    ]);
                }
            }
        }
        
        Log::info('📊 Total rutas detectadas', ['count' => count($routes)]);

        return $routes;
    }

    /**
     * 📦 PROCESAR MÚLTIPLES RUTAS CON DATOS COMUNES
     * Aplica datos comunes (producto, empaque) a rutas que no los tengan explícitos
     * 🆕 RESPETA valores explícitos por ruta (cantidad, valor_declarado)
     * 🆕 FIX: Detectar "c/u" o "cada uno" para propagar peso a todas las rutas
     */
    private static function processMultipleRoutes($routes, $fullText, $lowerText)
    {
        Log::info('🔄 Procesando múltiples rutas (respetando datos explícitos)', [
            'total_rutas' => count($routes)
        ]);

        // 🆕 FIX CRÍTICO: Detectar si hay "c/u" o "cada uno" que indica peso igual para todas las rutas
        // Patrón: "15 toneladas sin tara c/u" o "20 kg cada uno" o "X toneladas por contenedor"
        $pesoGlobalCadaUno = null;
        $sinTaraCadaUno = false;
        
        // Patrones para detectar "cada uno" / "c/u" / "por contenedor"
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:toneladas?|ton|kg|kilos?)\s+(?:sin\s+tara\s+)?(?:c\/u|c\/U|cada\s+un[oa]?|por\s+(?:cada\s+)?(?:contenedor|ruta))/ui', $fullText, $matchCadaUno)) {
            $pesoRaw = str_replace(['.', ','], ['', '.'], $matchCadaUno[1]);
            $pesoGlobalCadaUno = (float)$pesoRaw;
            // Convertir toneladas a kg si aplica
            if (preg_match('/toneladas?|ton/ui', $matchCadaUno[0])) {
                $pesoGlobalCadaUno *= 1000;
            }
            $sinTaraCadaUno = preg_match('/sin\s+tara/ui', $fullText);
            
            Log::info('🔔 PESO "C/U" DETECTADO - Se aplicará a TODAS las rutas', [
                'peso_kg' => $pesoGlobalCadaUno,
                'sin_tara' => $sinTaraCadaUno,
                'match' => $matchCadaUno[0]
            ]);
        }
        
        // Si hay peso "c/u", aplicarlo a todas las rutas que no tengan peso explícito
        if ($pesoGlobalCadaUno !== null && count($routes) > 0) {
            foreach ($routes as &$route) {
                // Aplicar peso "c/u" si la ruta no tiene peso o tiene peso 0
                if (!isset($route['peso_kg']) || $route['peso_kg'] <= 0) {
                    $route['peso_kg'] = $pesoGlobalCadaUno;
                    Log::info("📦 Peso c/u aplicado a ruta #{$route['ruta_numero']}", [
                        'peso_kg' => $pesoGlobalCadaUno
                    ]);
                }
            }
            unset($route); // Romper referencia
        }

        // Extraer datos comunes que aplican SOLO a rutas sin datos explícitos
        $commonData = [];

        // 1️⃣ EMPAQUE COMÚN
        $empaque = self::extractEmpaque($lowerText);
        if ($empaque) {
            if (is_array($empaque)) {
                $commonData = array_merge($commonData, $empaque);
                // 🆕 Si hay cantidad_contenedor, agregarla
                if (isset($empaque['cantidad_contenedor'])) {
                    $cantidadContenedores = $empaque['cantidad_contenedor'];
                    $numRutas = count($routes);
                    
                    // 🔧 FIX: Si los N contenedores se dividieron en N rutas separadas
                    // (por tener pesos/características diferentes), cada ruta = 1 contenedor
                    if ($numRutas > 1 && $cantidadContenedores == $numRutas) {
                        $commonData['cantidad'] = 1;
                        Log::info('📦 FIX cantidad: ' . $cantidadContenedores . ' contenedores divididos en ' . $numRutas . ' rutas → cantidad=1 por ruta');
                    } else {
                        $commonData['cantidad'] = $cantidadContenedores;
                    }
                }
            } else {
                $commonData['empaque'] = $empaque;
            }
        }

        // 2️⃣ TIPO DE MERCANCÍA
        $tipoMercancia = self::extractTipoMercancia($lowerText);
        if ($tipoMercancia) {
            $commonData['tipo_mercancia'] = $tipoMercancia;
        }

        // Aplicar datos comunes SOLO si la ruta no tiene ese dato explícito
        $processedRoutes = [];
        foreach ($routes as $route) {
            $completeRoute = $route;
            
            // Aplicar empaque común si no tiene explícito
            if (!isset($completeRoute['empaque']) && isset($commonData['empaque'])) {
                $completeRoute['empaque'] = $commonData['empaque'];
                $completeRoute['empaque_id'] = $commonData['empaque_id'] ?? null;
            }
            
            // Aplicar tipo_mercancia común si no tiene explícito
            if (!isset($completeRoute['tipo_mercancia']) && isset($commonData['tipo_mercancia'])) {
                $completeRoute['tipo_mercancia'] = $commonData['tipo_mercancia'];
            }
            
            // 🆕 NO sobrescribir valor_declarado si ya viene explícito de la ruta
            // 🆕 NO sobrescribir cantidad si ya viene explícito de la ruta
            // 🆕 NO sobrescribir producto si ya viene explícito de la ruta
            
            // Sugerir vehículo según peso si no existe
            if (isset($completeRoute['peso_kg']) && !isset($completeRoute['vehiculo'])) {
                $vehiculo = self::suggestVehicleByWeight($completeRoute['peso_kg']);
                if ($vehiculo) {
                    $completeRoute['vehiculo'] = $vehiculo;
                }
            }
            
            $processedRoutes[] = $completeRoute;
            
            Log::info("✅ Ruta #{$completeRoute['ruta_numero']} procesada", [
                'origen' => $completeRoute['origen'],
                'destino' => $completeRoute['destino'],
                'peso_kg' => $completeRoute['peso_kg'] ?? '-',
                'cantidad' => $completeRoute['cantidad'] ?? '-',
                'valor' => $completeRoute['valor_declarado'] ?? '-',
                'producto' => $completeRoute['producto'] ?? '-',
                'empaque' => $completeRoute['empaque'] ?? '-',
                'vehiculo' => $completeRoute['vehiculo'] ?? '-'
            ]);
        }

        Log::info('✅ Rutas completas procesadas', [
            'total' => count($processedRoutes),
            'preview' => array_map(function($r) {
                return "{$r['origen']} → {$r['destino']} (" . 
                       ($r['peso_kg'] ?? 0) . "kg, " . 
                       ($r['cantidad'] ?? 0) . " uds, $" . 
                       number_format($r['valor_declarado'] ?? 0) . ")";
            }, $processedRoutes)
        ]);

        return $processedRoutes;
    }

    /**
     * 📍 EXTRAER DATOS DE UNA RUTA ÚNICA
     */
    private static function extractSingleRouteData($fullText, $lowerText)
    {
        $data = [];

        // Ciudades (origen/destino)
        $ciudades = self::extractCiudades($fullText);
        Log::info('🏙️ extractSingleRouteData: extractCiudades result', [
            'text' => substr($fullText, 0, 100),
            'ciudades_result' => $ciudades
        ]);
        if ($ciudades) {
            $data = array_merge($data, $ciudades);
        }

        // Peso
        $peso = self::extractPeso($fullText);
        if ($peso) {
            $data['peso_kg'] = $peso;
        }

        // Producto - 🆕 Guardar como "producto_mencionado" (sin validar en BD)
        // El usuario deberá solicitar explícitamente la búsqueda para validar
        $producto = self::extractProducto($fullText);
        if ($producto) {
            $data['producto'] = $producto;
            $data['producto_mencionado'] = $producto; // 🆕 Marcar como no validado
            $data['producto_validado'] = false; // 🆕 Flag para saber si ya se buscó
            Log::info('📦 Producto guardado SIN VALIDAR (usuario debe pedir búsqueda)', [
                'producto_mencionado' => $producto,
                'validado' => false
            ]);
        }

        // Cantidad
        $cantidad = self::extractCantidad($fullText);
        if ($cantidad) {
            $data['cantidad'] = $cantidad;
        }

        // Valor declarado
        $valorDeclarado = self::extractValorDeclarado($fullText);
        if ($valorDeclarado) {
            $data['valor_declarado'] = $valorDeclarado;
        }

        // Empaque
        $empaque = self::extractEmpaque($lowerText);
        if ($empaque) {
            if (is_array($empaque)) {
                $data = array_merge($data, $empaque);
                // 🆕 Si hay cantidad_contenedor pero no cantidad general, usarla
                if (isset($empaque['cantidad_contenedor']) && !isset($data['cantidad'])) {
                    $data['cantidad'] = $empaque['cantidad_contenedor'];
                }
            } else {
                $data['empaque'] = $empaque;
            }
        }

        // 🆕 Volumen (metros cúbicos)
        $volumen = self::extractVolumen($fullText);
        if ($volumen) {
            $data['volumen_m3'] = $volumen;
        }

        // 🆕 Tipo de contenedor (20', 40', 40HC, etc.)
        $tipoContenedor = self::extractTipoContenedor($fullText);
        if ($tipoContenedor) {
            $data['tipo_contenedor'] = $tipoContenedor;
        }

        // Tipo de mercancía
        $tipoMercancia = self::extractTipoMercancia($lowerText);
        if ($tipoMercancia) {
            $data['tipo_mercancia'] = $tipoMercancia;
        }

        // 🆕 EXTRAER VEHÍCULO EXPLÍCITO del texto PRIMERO
        $vehiculoExplicito = self::extractVehiculo($fullText);
        if ($vehiculoExplicito) {
            $data['vehiculo'] = $vehiculoExplicito;
            Log::info('🚛 Vehículo detectado explícitamente', ['vehiculo' => $vehiculoExplicito]);
        }
        // Solo sugerir vehículo por peso si NO se detectó uno explícito
        elseif (isset($data['peso_kg'])) {
            $vehiculo = self::suggestVehicleByWeight($data['peso_kg']);
            if ($vehiculo) {
                $data['vehiculo'] = $vehiculo;
                Log::info('🚛 Vehículo sugerido por peso', ['vehiculo' => $vehiculo, 'peso' => $data['peso_kg']]);
            }
        }
        
        // 🚛 FIX: Contenedores SIEMPRE requieren TRACTOCAMION
        if (isset($data['empaque']) && preg_match('/CONTENEDOR/i', $data['empaque'])) {
            if (($data['vehiculo'] ?? '') !== 'TRACTOCAMION') {
                Log::info('🚛 Vehículo forzado a TRACTOCAMION por contenedor', [
                    'vehiculo_anterior' => $data['vehiculo'] ?? 'null',
                    'empaque' => $data['empaque']
                ]);
                $data['vehiculo'] = 'TRACTOCAMION';
            }
        }
        
        // 🔧 TARA - Por defecto debe agregarse si no se especifica
        $noIncluyeTaraPatterns = [
            'no incluye tara', 'no incluye la tara', 'sin tara', 'peso neto',
            'el peso no incluye tara', 'el peso no incluye la tara', 
            'peso no incluye tara', 'peso no incluye la tara',
            '+ tara', 'más tara', 'mas tara'
        ];
        $yaIncluyeTaraPatterns = [
            'ya incluye tara', 'ya incluye la tara', 'incluye la tara',
            'con tara', 'peso con tara', 'peso bruto', 'tara incluida',
            'el peso ya incluye la tara', 'el peso ya incluye tara',
            'peso ya incluye la tara', 'peso ya incluye tara'
        ];
        
        $detectoNoIncluyeTara = false;
        $detectoYaIncluyeTara = false;
        
        foreach ($noIncluyeTaraPatterns as $pattern) {
            if (strpos($lowerText, $pattern) !== false) {
                $detectoNoIncluyeTara = true;
                break;
            }
        }
        
        if (!$detectoNoIncluyeTara) {
            foreach ($yaIncluyeTaraPatterns as $pattern) {
                if (strpos($lowerText, $pattern) !== false) {
                    $detectoYaIncluyeTara = true;
                    break;
                }
            }
        }
        
        // 🆕 REGLA: Tara SOLO aplica cuando hay CONTENEDORES
        $hayContenedor = self::hayContenedorEnTexto($fullText) || preg_match('/CONTENEDOR/i', $data['empaque'] ?? '') || isset($data['tamano_contenedor']);
        
        if ($detectoNoIncluyeTara && $hayContenedor) {
            $data['incluye_tara'] = false;
            if (isset($data['peso_kg']) && $data['peso_kg'] > 0) {
                $pesoOriginal = $data['peso_kg'];
                if (isset($data['tamano_contenedor'])) {
                    $taraAplicar = ($data['tamano_contenedor'] == 20) ? 2300 : 3400;
                } else {
                    $taraAplicar = self::getTaraByContenedor($fullText);
                }
                $data['peso_kg'] = $pesoOriginal + $taraAplicar;
                $data['tara'] = $taraAplicar;
                Log::info('📦 TARA SUMADA (no incluye tara, con contenedor)', [
                    'peso_original' => $pesoOriginal,
                    'tara' => $taraAplicar,
                    'peso_con_tara' => $data['peso_kg'],
                    'tamano_contenedor' => $data['tamano_contenedor'] ?? 'no detectado'
                ]);
            }
        } elseif ($detectoNoIncluyeTara && !$hayContenedor) {
            // 🆕 "sin tara" pero NO hay contenedor → NO aplicar tara (carga suelta)
            $data['incluye_tara'] = false;
            Log::info('📦 "sin tara" detectado pero NO hay contenedor → peso se mantiene (carga suelta)', [
                'peso_kg' => $data['peso_kg'] ?? 'N/A'
            ]);
        } elseif ($detectoYaIncluyeTara) {
            $data['incluye_tara'] = true;
            Log::info('📦 TARA ya incluida, peso se mantiene', ['peso_kg' => $data['peso_kg'] ?? 'N/A']);
        } elseif ($hayContenedor) {
            // 🆕 Contenedor detectado y NO se menciona tara → agregar tara por defecto
            $data['incluye_tara'] = false;
            if (isset($data['peso_kg']) && $data['peso_kg'] > 0) {
                $pesoOriginal = $data['peso_kg'];
                if (isset($data['tamano_contenedor'])) {
                    $taraAplicar = ($data['tamano_contenedor'] == 20) ? 2300 : 3400;
                } else {
                    $taraAplicar = self::getTaraByContenedor($fullText);
                }
                $data['peso_kg'] = $pesoOriginal + $taraAplicar;
                $data['tara'] = $taraAplicar;
                Log::info('📦 TARA SUMADA (default - contenedor sin mención de tara)', [
                    'peso_original' => $pesoOriginal,
                    'tara' => $taraAplicar,
                    'peso_con_tara' => $data['peso_kg'],
                    'tamano_contenedor' => $data['tamano_contenedor'] ?? 'no detectado'
                ]);
            }
        } else {
            // 🆕 NO hay contenedor y NO menciona tara → NO aplicar tara (carga suelta)
            $data['incluye_tara'] = false;
            Log::info('📦 Sin contenedor, sin mención de tara → peso se mantiene (carga suelta)', [
                'peso_kg' => $data['peso_kg'] ?? 'N/A'
            ]);
        }

        Log::info('📦 Datos de ruta única extraídos', [
            'campos_detectados' => array_keys($data)
        ]);

        return $data;
    }

    /**
     * 🏙️ EXTRAER CIUDADES (origen y destino)
     */
    private static function extractCiudades($text)
    {
        Log::info('🏙️ extractCiudades INICIO', ['text_length' => strlen($text), 'text_full' => $text]);
        
        // 🚫 IGNORAR si el texto es una corrección de otro campo (producto, peso, valor, empaque, vehículo)
        // Esto evita que "producto cambia a tomates" se interprete como "de producto a tomates"
        $esCorreccionOtroCampo = preg_match('/(?:cambia|cambiar|cambialo)(?:\s+el)?\s+(?:producto|peso|valor|empaque|embalaje|veh[ií]culo)\s+(?:a|por)/ui', $text) ||
                                 preg_match('/(?:producto|peso|valor|empaque|embalaje|veh[ií]culo)\s*(?:es|será|sea|queda|:)\s+/ui', $text) ||
                                 preg_match('/(?:producto|peso|valor|empaque|veh[ií]culo).*?(?:cambia|deja)\s+/ui', $text);
        
        Log::info('🏙️ extractCiudades filtro campo', ['esCorreccionOtroCampo' => $esCorreccionOtroCampo]);
        
        if ($esCorreccionOtroCampo) {
            Log::info('🏙️ Ignorando extracción de ciudades - es corrección de otro campo', ['text' => substr($text, 0, 100)]);
            return null;
        }
        
        // 🆕 Patrón PRIORITARIO: Corrección de origen
        // "el origen es Cali" o "origen: Bogota" o "cambia el origen a Medellin" o "origen cambialo a X"
        $origenCorreccion = null;
        
        Log::info('🏙️ Testeando patterns de ORIGEN');
        
        // 🔧 Terminador común para todos los patrones de ciudades
        // Acepta: punto, coma, salto de línea, "NOTA", "y", "destino", espacios múltiples o fin de string
        $terminadorOrigen = '(?:\s*[.,;\n]|\s+y\s+|\s+destino|\s+NOTA|\s{2,}|$)';
        
        // Patrón: "cambia el origen a X" o "origen cambialo a X" o "origen cambia y ponga X" o "origen cambia a X"
        // 🔧 FIX: Permitir texto intermedio como "de la ruta 1" o "de la ruta 2" entre "origen" y "a/por"
        if (preg_match('/(?:cambia|cambiar)(?:\s+el)?\s+origen(?:\s+de\s+la\s+ruta\s+\d+)?\s+(?:a|por)\s+([a-záéíóúñ]+)' . $terminadorOrigen . '/ui', $text, $matches)) {
            $origenCorreccion = self::normalizeCityName(trim($matches[1]));
            Log::info('🏙️ Origen detectado (patrón 1: cambia el origen)', ['origen' => $origenCorreccion, 'raw' => $matches[1]]);
        }
        elseif (preg_match('/origen\s+cambialo\s+(?:a|por)\s+([a-záéíóúñ]+)' . $terminadorOrigen . '/ui', $text, $matches)) {
            $origenCorreccion = self::normalizeCityName(trim($matches[1]));
            Log::info('🏙️ Origen detectado (patrón 2: origen cambialo)', ['origen' => $origenCorreccion, 'raw' => $matches[1]]);
        }
        elseif (preg_match('/origen\s+cambia\s+(?:y\s+)?(?:ponga?|pon)\s+([a-záéíóúñ]+)' . $terminadorOrigen . '/ui', $text, $matches)) {
            $origenCorreccion = self::normalizeCityName(trim($matches[1]));
            Log::info('🏙️ Origen detectado (patrón 3: origen cambia y ponga)', ['origen' => $origenCorreccion, 'raw' => $matches[1]]);
        }
        elseif (preg_match('/origen\s+cambia\s+(?:a|por)\s+([a-záéíóúñ]+)' . $terminadorOrigen . '/ui', $text, $matches)) {
            $origenCorreccion = self::normalizeCityName(trim($matches[1]));
            Log::info('🏙️ Origen detectado (patrón 4: origen cambia a)', ['origen' => $origenCorreccion, 'raw' => $matches[1]]);
        }
        // Patrón: "origen es X" o "origen: X"  
        // 🔧 FIX: Permitir "el origen de la ruta X es/: Y"
        elseif (preg_match('/(?:el\s+)?origen(?:\s+de\s+la\s+ruta\s+\d+)?\s*(?:es|será|sea|queda|:)\s*([a-záéíóúñ]+)' . $terminadorOrigen . '/ui', $text, $matches)) {
            $origenCorreccion = self::normalizeCityName(trim($matches[1]));
            Log::info('🏙️ Origen detectado (patrón 5: origen es)', ['origen' => $origenCorreccion, 'raw' => $matches[1]]);
        }
        
        Log::info('🏙️ Resultado patterns ORIGEN', ['origenCorreccion' => $origenCorreccion]);
        
        // 🆕 Patrón PRIORITARIO: Corrección de destino
        // "el destino es Cali" o "destino: Bogota" o "cambia el destino a Medellin" o "destino cambialo a X"
        $destinoCorreccion = null;
        
        Log::info('🏙️ Testeando patterns de DESTINO');
        
        // 🔧 Terminador común para patrones de destino
        $terminadorDestino = '(?:\s*[.,;\n]|\s+y\s+|\s+origen|\s+NOTA|\s{2,}|$)';
        
        // Patrón: "cambia el destino a X" o "destino cambialo a X" o "destino cambia y ponga X" o "destino cambia a X"
        // 🔧 FIX: Permitir texto intermedio como "de la ruta 1" o "de la ruta 2" entre "destino" y "a/por"
        if (preg_match('/(?:cambia|cambiar)(?:\s+el)?\s+destino(?:\s+de\s+la\s+ruta\s+\d+)?\s+(?:a|por)\s+([a-záéíóúñ]+)' . $terminadorDestino . '/ui', $text, $matches)) {
            $destinoCorreccion = self::normalizeCityName(trim($matches[1]));
            Log::info('🏙️ Destino detectado (patrón 1: cambia el destino)', ['destino' => $destinoCorreccion, 'raw' => $matches[1]]);
        }
        elseif (preg_match('/destino\s+cambialo\s+(?:a|por)\s+([a-záéíóúñ]+)' . $terminadorDestino . '/ui', $text, $matches)) {
            $destinoCorreccion = self::normalizeCityName(trim($matches[1]));
            Log::info('🏙️ Destino detectado (patrón 2: destino cambialo)', ['destino' => $destinoCorreccion, 'raw' => $matches[1]]);
        }
        elseif (preg_match('/destino\s+cambia\s+(?:y\s+)?(?:ponga?|pon)\s+([a-záéíóúñ]+)' . $terminadorDestino . '/ui', $text, $matches)) {
            $destinoCorreccion = self::normalizeCityName(trim($matches[1]));
            Log::info('🏙️ Destino detectado (patrón 3: destino cambia y ponga)', ['destino' => $destinoCorreccion, 'raw' => $matches[1]]);
        }
        elseif (preg_match('/destino\s+cambia\s+(?:a|por)\s+([a-záéíóúñ]+)' . $terminadorDestino . '/ui', $text, $matches)) {
            $destinoCorreccion = self::normalizeCityName(trim($matches[1]));
            Log::info('🏙️ Destino detectado (patrón 4: destino cambia a)', ['destino' => $destinoCorreccion, 'raw' => $matches[1]]);
        }
        // Patrón: "destino es X" o "destino: X"
        // 🔧 FIX: Permitir "el destino de la ruta X es/: Y"
        elseif (preg_match('/(?:el\s+)?destino(?:\s+de\s+la\s+ruta\s+\d+)?\s*(?:es|será|sea|queda|:)\s*([a-záéíóúñ]+)' . $terminadorDestino . '/ui', $text, $matches)) {
            $destinoCorreccion = self::normalizeCityName(trim($matches[1]));
            Log::info('🏙️ Destino detectado (patrón 5: destino es)', ['destino' => $destinoCorreccion, 'raw' => $matches[1]]);
        }
        
        Log::info('🏙️ Resultado patterns DESTINO', ['destinoCorreccion' => $destinoCorreccion]);
        
        // Si hay correcciones específicas, retornar solo esas
        if ($origenCorreccion || $destinoCorreccion) {
            $result = [];
            if ($origenCorreccion) $result['ciudad_origen'] = $origenCorreccion;
            if ($destinoCorreccion) $result['ciudad_destino'] = $destinoCorreccion;
            return $result;
        }
        
        // 🆕 Patrón PRIORITARIO: "Origen: X" y "Destino: X" (formato simple)
        // NOTA: Incluir soporte para emojis (📍, 🏠, etc.) que pueden preceder el texto
        $origenSimple = null;
        $destinoSimple = null;
        
        // Patrón: "📍 Origen: Puerto de Cartagena" o "Origen: Bogotá" - con soporte para emojis
        if (preg_match('/Origen\s*:\s*([A-Za-záéíóúñÁÉÍÓÚÑ\s]+?)(?:\s*\n|$)/ui', $text, $origenMatch)) {
            $origenSimple = self::normalizeCityName(trim($origenMatch[1]));
            Log::info('📍 Origen detectado (formato simple)', ['origen' => $origenSimple, 'raw' => $origenMatch[1]]);
        }
        
        // Patrón: "📍 Destino: Medellín" - con soporte para emojis
        if (preg_match('/Destino\s*:\s*([A-Za-záéíóúñÁÉÍÓÚÑ\s]+?)(?:\s*\n|$)/ui', $text, $destinoMatch)) {
            $destinoSimple = self::normalizeCityName(trim($destinoMatch[1]));
            Log::info('📍 Destino detectado (formato simple)', ['destino' => $destinoSimple, 'raw' => $destinoMatch[1]]);
        }
        
        // Si encontramos Origen/Destino simple, devolverlo
        if ($origenSimple || $destinoSimple) {
            $result = [];
            if ($origenSimple) $result['ciudad_origen'] = $origenSimple;
            if ($destinoSimple) $result['ciudad_destino'] = $destinoSimple;
            Log::info('📍 Usando Origen/Destino formato simple', $result);
            return $result;
        }
        
        // 🆕 NUEVO PATRÓN PRIORITARIO: "RUTA: X destino: Y" (puede tener salto de línea después)
        // Ejemplo: "RUTA: Cartagena destino: Medellín"
        if (preg_match('/RUTA\s*:\s*([a-záéíóúñ]+)\s+destino\s*:\s*([a-záéíóúñ]+)/ui', $text, $rutaMatch)) {
            $origenNormalized = self::normalizeCityName(trim($rutaMatch[1]));
            $destinoNormalized = self::normalizeCityName(trim($rutaMatch[2]));
            
            Log::info('🏙️ Ciudades detectadas por formato RUTA: X destino: Y', [
                'origen_raw' => $rutaMatch[1],
                'origen_normalized' => $origenNormalized,
                'destino_raw' => $rutaMatch[2],
                'destino_normalized' => $destinoNormalized
            ]);
            
            return [
                'ciudad_origen' => $origenNormalized,
                'ciudad_destino' => $destinoNormalized
            ];
        }
        
        // 🆕 PATRÓN: "RUTA: X - Y" o "Ruta: X a Y" sin la palabra destino
        // Ejemplo: "RUTA: Cartagena - Medellín" o "Ruta: Cali a Bogotá"
        if (preg_match('/RUTA\s*:\s*([a-záéíóúñ]+)\s*(?:-|–|a)\s*([a-záéíóúñ]+)/ui', $text, $rutaMatch)) {
            $origenNormalized = self::normalizeCityName(trim($rutaMatch[1]));
            $destinoNormalized = self::normalizeCityName(trim($rutaMatch[2]));
            
            Log::info('🏙️ Ciudades detectadas por formato RUTA: X - Y', [
                'origen_raw' => $rutaMatch[1],
                'origen_normalized' => $origenNormalized,
                'destino_raw' => $rutaMatch[2],
                'destino_normalized' => $destinoNormalized
            ]);
            
            return [
                'ciudad_origen' => $origenNormalized,
                'ciudad_destino' => $destinoNormalized
            ];
        }
        
        // 🆕 PALABRAS CLAVE DE ORIGEN: recogida, inicio, salida, partida, cargue
        // Ejemplo: "Recogida: Puerto de Cartagena" o "Lugar de inicio: Bogotá"
        $origenKeywords = null;
        
        // Patrón: "DIRECCIÓN DE RECOGIDA: LUGAR" o "Lugar de recogida: LUGAR"
        if (preg_match('/(?:DIRECCI[OÓ]N\s+DE\s+)?(?:RECOGIDA|CARGUE|INICIO|SALIDA|PARTIDA)\s*:\s*([A-Za-záéíóúñÁÉÍÓÚÑ\s\-\.]+?)(?:\s*\n|\s*\d+:\d+|$)/ui', $text, $keywordMatch)) {
            $origenKeywords = self::normalizeCityName(trim($keywordMatch[1]));
            Log::info('📍 Origen detectado por palabra clave (recogida/inicio/salida)', ['origen' => $origenKeywords]);
        }
        // Patrón: "Lugar de recogida: LUGAR" (formato formal)
        elseif (preg_match('/Lugar\s+de\s+(?:recogida|inicio|salida|cargue)\s*:\s*([A-Za-záéíóúñÁÉÍÓÚÑ\s]+?)(?:\s*\n|$)/ui', $text, $keywordMatch)) {
            $origenKeywords = self::normalizeCityName(trim($keywordMatch[1]));
            Log::info('📍 Origen detectado por Lugar de recogida/inicio', ['origen' => $origenKeywords]);
        }
        
        // 🆕 PALABRAS CLAVE DE DESTINO: entrega, llegada, final, descargue, puesta
        // Ejemplo: "Dirección de entrega: Medellín" o "Lugar de llegada: Cali"
        $destinoKeywords = null;
        
        // Patrón: "DIRECCIÓN DE ENTREGA: LUGAR" como destino (si no es coordenadas)
        if (preg_match('/(?:DIRECCI[OÓ]N\s+DE\s+)?(?:ENTREGA|LLEGADA|DESCARGUE|FINAL|PUESTA)\s*:\s*([A-Za-záéíóúñÁÉÍÓÚÑ\s\-\.]+?)(?:\s*\n|\s*-\s*Google|$)/ui', $text, $keywordMatch)) {
            $posibleDestino = trim($keywordMatch[1]);
            // Verificar que no sea coordenadas (números con grados)
            if (!preg_match('/\d+°/', $posibleDestino)) {
                $destinoKeywords = self::normalizeCityName($posibleDestino);
                Log::info('📍 Destino detectado por palabra clave (entrega/llegada/final)', ['destino' => $destinoKeywords]);
            }
        }
        // Patrón: "Lugar de entrega: LUGAR"
        elseif (preg_match('/Lugar\s+de\s+(?:entrega|llegada|descargue|final)\s*:\s*([A-Za-záéíóúñÁÉÍÓÚÑ\s]+?)(?:\s*\n|$)/ui', $text, $keywordMatch)) {
            $destinoKeywords = self::normalizeCityName(trim($keywordMatch[1]));
            Log::info('📍 Destino detectado por Lugar de entrega/llegada', ['destino' => $destinoKeywords]);
        }
        
        // Si encontramos origen/destino por palabras clave, usarlo
        if ($origenKeywords || $destinoKeywords) {
            $result = [];
            if ($origenKeywords) $result['ciudad_origen'] = $origenKeywords;
            if ($destinoKeywords) $result['ciudad_destino'] = $destinoKeywords;
            
            // Si falta destino pero hay patrón RUTA, obtener destino de ahí
            if (!$destinoKeywords && preg_match('/RUTA\s*:.*?destino\s*:\s*([a-záéíóúñ]+)/ui', $text, $rutaDestinoMatch)) {
                $result['ciudad_destino'] = self::normalizeCityName(trim($rutaDestinoMatch[1]));
                Log::info('📍 Destino complementado de RUTA:', ['destino' => $result['ciudad_destino']]);
            }
            // Si falta origen pero hay patrón RUTA, obtener origen de ahí
            if (!$origenKeywords && preg_match('/RUTA\s*:\s*([a-záéíóúñ]+)/ui', $text, $rutaOrigenMatch)) {
                $result['ciudad_origen'] = self::normalizeCityName(trim($rutaOrigenMatch[1]));
                Log::info('📍 Origen complementado de RUTA:', ['origen' => $result['ciudad_origen']]);
            }
            
            Log::info('📍 Usando Origen/Destino por palabras clave', $result);
            return $result;
        }
        
        // 🆕 Patrón especial: "Ruta: CIUDAD → CIUDAD" o "Ruta: CIUDAD – CIUDAD" (con flecha o guiones)
        // Ejemplo: "Ruta: Cartagena → Medellín" o "Ruta: Cartagena – Medellín"
        // Incluye: → (flecha unicode), – (en-dash 2013), — (em-dash 2014), >, -, etc.
        if (preg_match('/Ruta\s*:\s*([A-Za-záéíóúñÁÉÍÓÚÑ\s]+?)\s*(?:→|–|—|-|>|\x{2192}|\x{2013}|\x{2014})+\s*([A-Za-záéíóúñÁÉÍÓÚÑ\s]+?)(?:\s*\n|$)/ui', $text, $rutaMatch)) {
            $origenNormalized = self::normalizeCityName(trim($rutaMatch[1]));
            $destinoNormalized = self::normalizeCityName(trim($rutaMatch[2]));
            
            Log::info('🏙️ Ciudades detectadas por formato Ruta con flecha', [
                'origen_raw' => $rutaMatch[1],
                'origen_normalized' => $origenNormalized,
                'destino_raw' => $rutaMatch[2],
                'destino_normalized' => $destinoNormalized
            ]);
            
            return [
                'ciudad_origen' => $origenNormalized,
                'ciudad_destino' => $destinoNormalized
            ];
        }
        
        // Patrón especial: "Origen: CIUDAD ... Destino: CIUDAD" (más común en formularios formales)
        if (preg_match('/(?:origen|origin)\s*:?\s*([a-záéíóúñ\s\-\.]+?)(?:\s*-\s*[A-Z]+\s*[A-Z]+\s*:|destino|peso|unidad|modalidad|cantidad|,|\n)/ui', $text, $origenMatch)) {
            if (preg_match('/(?:destino|destination)\s*:?\s*([a-záéíóúñ\s\-]+?)(?:\s*[-–—]\s*[a-záéíóúñ]+|unidad|peso|cantidad|modalidad|,|\n|$)/ui', $text, $destinoMatch)) {
                $origen = trim($origenMatch[1]);
                $destino = trim($destinoMatch[1]);
                
                // Limpiar direcciones y extras
                $origen = preg_replace('/\s*[-:]\s*.*/u', '', $origen); // Remover direcciones después de guion
                $destino = preg_replace('/\s*[-–—]\s*.*/u', '', $destino);
                
                // Validar longitud razonable
                if (strlen($origen) >= 3 && strlen($origen) <= 30 && 
                    strlen($destino) >= 3 && strlen($destino) <= 30) {
                    
                    $origenNormalized = self::normalizeCityName($origen);
                    $destinoNormalized = self::normalizeCityName($destino);
                    
                    Log::info('🏙️ Ciudades detectadas por formato formal (Origen:/Destino:)', [
                        'origen_raw' => $origen,
                        'origen_normalized' => $origenNormalized,
                        'destino_raw' => $destino,
                        'destino_normalized' => $destinoNormalized
                    ]);
                    
                    return [
                        'ciudad_origen' => $origenNormalized,
                        'ciudad_destino' => $destinoNormalized
                    ];
                }
            }
        }
        
        // 🆕 NUEVO PATRÓN PRIORITARIO: "ruta de X a Y" o "una ruta de X a Y"
        // Ejemplo: "quiero que me hagas una ruta de Cali a Bogotá vamos a llevar..."
        // Termina en: "vamos", "con", "en", número, "toneladas", etc.
        if (preg_match('/(?:una\s+)?ruta\s+de\s+([a-záéíóúñ]+(?:\s+de\s+[a-záéíóúñ]+)?)\s+a\s+([a-záéíóúñ]+)(?:\s+(?:vamos|con|en\s+mercanc[ií]a|el\s+producto|\d)|,|\.|$)/ui', $text, $rutaMatches)) {
            $origenNormalized = self::normalizeCityName(trim($rutaMatches[1]));
            $destinoNormalized = self::normalizeCityName(trim($rutaMatches[2]));
            
            Log::info('🏙️ Ciudades detectadas por "ruta de X a Y"', [
                'origen_raw' => $rutaMatches[1],
                'origen_normalized' => $origenNormalized,
                'destino_raw' => $rutaMatches[2],
                'destino_normalized' => $destinoNormalized
            ]);
            
            return [
                'ciudad_origen' => $origenNormalized,
                'ciudad_destino' => $destinoNormalized
            ];
        }
        
        // 🆕 PATRÓN: "de X a Y vamos a llevar" - voz natural
        // Ejemplo: "de Cali a Bogotá vamos a llevar 20 toneladas de papa"
        if (preg_match('/de\s+([a-záéíóúñ]+(?:\s+de\s+[a-záéíóúñ]+)?)\s+a\s+([a-záéíóúñ]+)\s+vamos\s+a\s+llevar/ui', $text, $rutaMatches)) {
            $origenNormalized = self::normalizeCityName(trim($rutaMatches[1]));
            $destinoNormalized = self::normalizeCityName(trim($rutaMatches[2]));
            
            Log::info('🏙️ Ciudades detectadas por "de X a Y vamos a llevar"', [
                'origen_raw' => $rutaMatches[1],
                'origen_normalized' => $origenNormalized,
                'destino_raw' => $rutaMatches[2],
                'destino_normalized' => $destinoNormalized
            ]);
            
            return [
                'ciudad_origen' => $origenNormalized,
                'ciudad_destino' => $destinoNormalized
            ];
        }
        
        // Patrón 1: "de X a Y" o "desde X hasta Y" - MEJORADO con terminador
        // Terminador: número, "toneladas", "kg", "para", "con", "vamos", "en un", coma, punto, etc.
        $terminadorRuta = '(?=\s*(?:\d|toneladas?|kg|kilos?|para\s|con\s|vamos\s|en\s+un|,|\.|;|$))';
        if (preg_match('/(?:de|desde)\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})\s+(?:a|hasta)\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,1})' . $terminadorRuta . '/ui', $text, $matches)) {
            // 🆕 Palabras que NUNCA pueden ser ciudades (incluye campos de formulario, acciones, meses, tiempo)
            $forbiddenWords = [
                // Campos de formulario y acciones
                'producto', 'peso', 'valor', 'empaque', 'embalaje', 'cantidad', 'origen', 'destino', 
                'cambia', 'cambiar', 'cambialo', 'cambiale', 'modifica', 'actualiza', 'nota', 'importante',
                // 🆕 MESES DEL AÑO (evita confundir fechas con rutas)
                'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 
                'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
                // 🆕 PALABRAS DE TIEMPO/HORA (evita "de enero a las 9")
                'las', 'los', 'del', 'dia', 'dias', 'hora', 'horas', 'manana', 'mañana', 'tarde', 'noche',
                'am', 'pm', 'hoy', 'ayer', 'semana', 'mes', 'ano', 'año'
            ];
            $commonWords = ['importación', 'exportación', 'distribución', 'distribucion', 'nacionalizada', 'internacional', 'terrestre', 'marítima', 'aérea', 'carga', 'general'];
            $origen = trim($matches[1]);
            $destino = trim($matches[2]);
            
            // 🆕 Verificar que origen/destino no sean palabras prohibidas
            $origenLower = strtolower($origen);
            $destinoLower = strtolower($destino);
            $origenIsForbidden = false;
            $destinoIsForbidden = false;
            
            // 🔧 FIX: Verificar palabras completas, no subcadenas
            // Dividir en palabras y verificar si alguna palabra completa es prohibida
            $origenWords = preg_split('/\s+/', $origenLower);
            $destinoWords = preg_split('/\s+/', $destinoLower);
            
            foreach ($forbiddenWords as $forbidden) {
                if (in_array($forbidden, $origenWords)) $origenIsForbidden = true;
                if (in_array($forbidden, $destinoWords)) $destinoIsForbidden = true;
            }
            
            // Si alguno es palabra prohibida, no extraer ciudades de este patrón
            if ($origenIsForbidden || $destinoIsForbidden) {
                Log::info('🏙️ Ignorando patrón "de X a Y" - contiene palabras prohibidas', [
                    'origen' => $origen, 
                    'destino' => $destino,
                    'origenForbidden' => $origenIsForbidden,
                    'destinoForbidden' => $destinoIsForbidden
                ]);
                // Continuar con otros patrones (no hacer return aquí)
            } else {
                // Filtrar palabras comunes del inicio Y del final
                foreach ($commonWords as $word) {
                    // Remover del inicio
                    $pattern = '/^' . preg_quote($word, '/') . '\s+/ui';
                    $origen = preg_replace($pattern, '', $origen);
                    $destino = preg_replace($pattern, '', $destino);
                    
                    // Remover del final
                    $pattern = '/\s+' . preg_quote($word, '/') . '$/ui';
                    $origen = preg_replace($pattern, '', $origen);
                    $destino = preg_replace($pattern, '', $destino);
                }
                
                // Limpiar espacios múltiples
                $origen = preg_replace('/\s+/', ' ', trim($origen));
                $destino = preg_replace('/\s+/', ' ', trim($destino));
                
                // Validar que después de filtrar quede algo válido (nombre de ciudad)
                $origenWords = explode(' ', $origen);
                $destinoWords = explode(' ', $destino);
                
                // Aceptar máximo 3 palabras para nombres de ciudades
                if (count($origenWords) <= 3 && count($destinoWords) <= 3 && 
                    strlen($origen) >= 3 && strlen($destino) >= 3) {
                    
                    // 🆕 NORMALIZAR nombres: capitalizar primera letra de cada palabra
                    $origenNormalized = self::normalizeCityName($origen);
                    $destinoNormalized = self::normalizeCityName($destino);
                    
                    Log::info('🏙️ Ciudades detectadas y normalizadas (patrón 1)', [
                        'origen_raw' => $origen,
                        'origen_normalized' => $origenNormalized,
                        'destino_raw' => $destino,
                        'destino_normalized' => $destinoNormalized,
                        'texto_original' => substr($text, 0, 200)
                    ]);
                    
                    return [
                        'ciudad_origen' => $origenNormalized,
                        'ciudad_destino' => $destinoNormalized
                    ];
                }
            }
        }
        
        // Patrón 2: "X a Y" (más flexible)
        if (preg_match('/\b([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})\s+a\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})\b/ui', $text, $matches)) {
            $origen = trim($matches[1]);
            $destino = trim($matches[2]);
            
            // 🆕 LIMPIAR palabras conectoras ANTES de validar
            // Eliminar frases como "ruta de", "para llevar", "viaje de", "cotización de", etc.
            // Usar un solo patrón grande con alternativas (|) para eliminar todo de una vez
            $patronPrefijos = '/^(?:cotizaci[oó]n\s+(?:de\s+)?|distribuci[oó]n\s+nacionalizada\s+(?:de\s+)?|importaci[oó]n\s+(?:de\s+)?|exportaci[oó]n\s+(?:de\s+)?|ruta\s+(?:de\s+)?|viaje\s+(?:de\s+)?|destino\s+(?:de\s+)?|origen\s+(?:de\s+)?|de\s+la\s+|desde\s+|hacia\s+|de\s+)/ui';
            $patronSufijos = '/(?:\s+para\s+(?:llevar|cargar|descargar)|\s+a\s+las\s+|\s+por\s+|\s+con\s+).*/ui';
            
            $origen = preg_replace($patronPrefijos, '', $origen);
            $destino = preg_replace($patronPrefijos, '', $destino);
            $origen = preg_replace($patronSufijos, '', $origen);
            $destino = preg_replace($patronSufijos, '', $destino);
            
            $origen = trim($origen);
            $destino = trim($destino);
            
            // 🆕 Palabras prohibidas que NUNCA pueden ser ciudades
            // Incluye: campos de formulario, acciones, productos comunes, meses del año, palabras de tiempo/hora
            $forbiddenWords = [
                // Campos de formulario y acciones
                'producto', 'peso', 'valor', 'empaque', 'embalaje', 'cantidad', 'origen', 'destino', 
                'cambia', 'cambiar', 'cambialo', 'cambiale', 'modifica', 'actualiza', 'nota', 'importante',
                // Nombres de productos comunes
                'tomates', 'maiz', 'arroz', 'cafe', 'azucar', 'miel',
                // 🆕 MESES DEL AÑO (evita confundir fechas con rutas)
                'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 
                'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
                // 🆕 PALABRAS DE TIEMPO/HORA (evita "de enero a las 9")
                'las', 'los', 'del', 'dia', 'dias', 'hora', 'horas', 'manana', 'mañana', 'tarde', 'noche',
                'am', 'pm', 'hoy', 'ayer', 'semana', 'mes', 'ano', 'año'
            ];
            $commonWords = ['necesito', 'quiero', 'solicito', 'cotización', 'importación', 'exportación', 'distribución', 'distribucion', 'nacionalizada', 'carga'];
            
            // Verificar palabras prohibidas
            $origenLower = strtolower($origen);
            $destinoLower = strtolower($destino);
            
            // 🔧 FIX: Rechazar si el nombre contiene palabras clave descriptivas
            // Ej: "CALI Y DESTINO BGT" contiene "destino" → rechazar
            $palabrasDescriptivas = ['origen', 'destino', 'ruta', 'viaje', 'cotizacion', 'cotización'];
            $origenTienePalabraDescriptiva = false;
            $destinoTienePalabraDescriptiva = false;
            
            foreach ($palabrasDescriptivas as $palabra) {
                if (strpos($origenLower, $palabra) !== false) {
                    $origenTienePalabraDescriptiva = true;
                    break;
                }
            }
            
            foreach ($palabrasDescriptivas as $palabra) {
                if (strpos($destinoLower, $palabra) !== false) {
                    $destinoTienePalabraDescriptiva = true;
                    break;
                }
            }
            
            // 🆕 Verificar si TODA la palabra es exactamente una palabra prohibida (no subcadena)
            $origenIsForbidden = in_array($origenLower, $forbiddenWords);
            $destinoIsForbidden = in_array($destinoLower, $forbiddenWords);
            
            $isOriginCommon = in_array($origenLower, $commonWords);
            $isDestinationCommon = in_array($destinoLower, $commonWords);
            
            // Rechazar si:
            // - Es numérico
            // - Es palabra común
            // - Es palabra prohibida
            // - Contiene palabra descriptiva (origen/destino/ruta)
            // - Es muy corto (< 3 chars)
            if (!is_numeric($origen) && !is_numeric($destino) && 
                !$isOriginCommon && !$isDestinationCommon &&
                !$origenIsForbidden && !$destinoIsForbidden &&
                !$origenTienePalabraDescriptiva && !$destinoTienePalabraDescriptiva &&
                strlen($origen) >= 3 && strlen($destino) >= 3) {
                
                $origenWords = explode(' ', $origen);
                $destinoWords = explode(' ', $destino);
                
                if (count($origenWords) <= 3 && count($destinoWords) <= 3) {
                    // 🆕 NORMALIZAR nombres
                    $origenNormalized = self::normalizeCityName($origen);
                    $destinoNormalized = self::normalizeCityName($destino);
                    
                    Log::info('🏙️ Ciudades detectadas y normalizadas (patrón 2)', [
                        'origen_raw' => $origen,
                        'origen_normalized' => $origenNormalized,
                        'destino_raw' => $destino,
                        'destino_normalized' => $destinoNormalized
                    ]);
                    
                    return [
                        'ciudad_origen' => $origenNormalized,
                        'ciudad_destino' => $destinoNormalized
                    ];
                }
            }
        }

        Log::warning('⚠️ No se detectaron ciudades en el texto', [
            'texto' => substr($text, 0, 200)
        ]);
        
        return null;
    }
    
    /**
     * 🆕 EXTRAER DATOS DE UNA RUTA DESDE UN FRAGMENTO DE TEXTO
     * Usado para procesar partes separadas por "adicional", "también", etc.
     */
    private static function extractRouteDataFromText($text, $rutaNumero = 1)
    {
        $route = [
            'ruta_numero' => $rutaNumero,
            'origen' => null,
            'destino' => null,
            'peso_kg' => null,
            'cantidad' => null,
            'producto' => null,
            'valor_declarado' => null,
            'empaque' => null,
            'empaque_id' => null,
            'incluye_tara' => false
        ];
        
        // 🆕 PREPROCESAR TEXTO: Separar palabras pegadas antes de extraer
        $text = TextPreprocessorService::preprocess(trim($text));
        $textLower = mb_strtolower($text);
        
        // 🆕 PATRÓN: "de ORIGEN a DESTINO" o "desde ORIGEN hasta DESTINO"
        // IMPORTANTE: Filtrar prefijos como "distribución nacionalizada de"
        // 🔧 FIX: Terminar antes de palabras clave como "con", "y", "en", etc.
        $patronCiudades = '/(?:cotizaci[oó]n\s+)?(?:de\s+)?(?:distribuci[oó]n\s+)?(?:nacionalizada\s+)?(?:de|desde)\s+([a-záéíóúñ]+(?:\s+(?!con\b|y\b|en\b|de\b|para\b|sin\b|con\s+|y\s+|en\s+)[a-záéíóúñ]+){0,2})\s+(?:a|hasta|hacia)\s+([a-záéíóúñ]+(?:\s+(?!con\b|y\b|en\b|de\b|para\b|sin\b|con\s+|y\s+|en\s+)[a-záéíóúñ]+){0,2})(?=\s+(?:con|y|en|de|para|sin|$)|,|\.)/ui';
        if (preg_match($patronCiudades, $text, $ciudadesMatch)) {
            // Limpiar prefijos comunes que no son parte del nombre de la ciudad
            $origenRaw = trim($ciudadesMatch[1]);
            $destinoRaw = trim($ciudadesMatch[2]);
            
            // Remover palabras clave que pueden haber quedado capturadas
            $prefijosARemover = ['distribución', 'distribucion', 'nacionalizada', 'importación', 'importacion', 'exportación', 'exportacion', 'de'];
            foreach ($prefijosARemover as $prefijo) {
                $origenRaw = preg_replace('/^' . preg_quote($prefijo, '/') . '\s+/ui', '', $origenRaw);
                $destinoRaw = preg_replace('/^' . preg_quote($prefijo, '/') . '\s+/ui', '', $destinoRaw);
            }
            
            // 🔧 FIX: Validar que NO contengan palabras descriptivas
            $palabrasDescriptivas = ['origen', 'destino', 'ruta', 'viaje', 'cotizacion', 'cotización'];
            $origenValido = true;
            $destinoValido = true;
            
            foreach ($palabrasDescriptivas as $palabra) {
                if (stripos($origenRaw, $palabra) !== false) {
                    $origenValido = false;
                    Log::warning('⚠️ Origen rechazado por contener palabra descriptiva', [
                        'origen_raw' => $origenRaw,
                        'palabra' => $palabra
                    ]);
                    break;
                }
            }
            
            foreach ($palabrasDescriptivas as $palabra) {
                if (stripos($destinoRaw, $palabra) !== false) {
                    $destinoValido = false;
                    Log::warning('⚠️ Destino rechazado por contener palabra descriptiva', [
                        'destino_raw' => $destinoRaw,
                        'palabra' => $palabra
                    ]);
                    break;
                }
            }
            
            if ($origenValido && $destinoValido) {
                $route['origen'] = self::normalizeCityName(trim($origenRaw));
                $route['destino'] = self::normalizeCityName(trim($destinoRaw));
            }
        }
        
        // 🆕 PATRÓN: "ORIGEN <sep> DESTINO"
        // Corregido: Validar secuencialmente para evitar falsos positivos (como "vamos a cargar")
        if (!$route['origen'] && !$route['destino']) {
            // Caso 1: Separador con espacios obligatorios (para " a ")
            if (preg_match('/([a-záéíóúñ]+)\s+(?:a|hacia|hasta)\s+([a-záéíóúñ]+)/ui', $text, $ciudadesMatch2)) {
                $o = self::normalizeCityName(trim($ciudadesMatch2[1]));
                $d = self::normalizeCityName(trim($ciudadesMatch2[2]));
                if ($o && $d) {
                    $route['origen'] = $o;
                    $route['destino'] = $d;
                }
            }
        }
            
        if (!$route['origen'] && !$route['destino']) {
            // Caso 2: Separador guión (puede no tener espacios)
            if (preg_match('/([a-záéíóúñ]+)\s*(?:-|–)\s*([a-záéíóúñ]+)/ui', $text, $ciudadesMatch3)) {
                $o = self::normalizeCityName(trim($ciudadesMatch3[1]));
                $d = self::normalizeCityName(trim($ciudadesMatch3[2]));
                if ($o && $d) {
                    $route['origen'] = $o;
                    $route['destino'] = $d;
                }
            }
        }
            
        if (!$route['origen'] && !$route['destino']) {
            // Caso 3: Ciudades pegadas solo por espacio (ej: "Bucaramanga Bogotá vamos a cargar")
            // Muy común en speech-to-text donde el usuario no dice "a" entre ciudades
            // Lista de ciudades comunes para validación
            $ciudadesComunes = [
                'bogota', 'bogotá', 'medellin', 'medellín', 'cali', 'barranquilla', 'cartagena', 
                'bucaramanga', 'pereira', 'cucuta', 'cúcuta', 'ibague', 'ibagué', 'manizales',
                'santa marta', 'villavicencio', 'pasto', 'monteria', 'montería', 'neiva', 
                'valledupar', 'armenia', 'popayan', 'popayán', 'sincelejo', 'tunja', 'riohacha',
                'buenaventura', 'girardot', 'floridablanca', 'soacha', 'bello', 'soledad',
                'palmira', 'envigado', 'itagui', 'itagüí', 'dosquebradas', 'tulua', 'tuluá',
                'apartado', 'apartadó', 'cartago', 'barrancabermeja', 'yopal', 'florencia'
            ];
            
            // Patrón: CIUDAD CIUDAD (vamos|para|a cargar|haremos) - ciudades contiguas
            if (preg_match('/(?:(?:ruta|hacer)\s+)?([a-záéíóúñ]+)\s+([a-záéíóúñ]+)\s+(?:vamos|llevaremos|cargaremos|para|haremos)/ui', $text, $ciudadesMatch4)) {
                 $o = self::normalizeCityName(trim($ciudadesMatch4[1]));
                 $d = self::normalizeCityName(trim($ciudadesMatch4[2]));
                 if ($o && $d) {
                    $route['origen'] = $o;
                    $route['destino'] = $d;
                 }
            }
            
            // 🆕 NUEVO: Buscar dos ciudades conocidas consecutivas
            if (!$route['origen'] && !$route['destino']) {
                $textWords = preg_split('/\s+/', $textLower);
                for ($i = 0; $i < count($textWords) - 1; $i++) {
                    $word1 = trim($textWords[$i]);
                    $word2 = trim($textWords[$i + 1]);
                    
                    // Verificar si ambas palabras son ciudades conocidas
                    if (in_array($word1, $ciudadesComunes) && in_array($word2, $ciudadesComunes)) {
                        $route['origen'] = self::normalizeCityName($word1);
                        $route['destino'] = self::normalizeCityName($word2);
                        Log::info('🆕 Ciudades detectadas por cercanía', [
                            'origen' => $route['origen'],
                            'destino' => $route['destino']
                        ]);
                        break;
                    }
                }
            }
        }
        
        // 🆕 PESO: "N toneladas" o "N kg" o "N kilos" - incluir "de/por" como prefijo opcional
        // 🆕 Soporte para palabras numéricas: "dos toneladas" = 2000 kg
        $numerosEscrito = [
            'una' => 1, 'un' => 1, 'dos' => 2, 'tres' => 3, 'cuatro' => 4, 'cinco' => 5,
            'seis' => 6, 'siete' => 7, 'ocho' => 8, 'nueve' => 9, 'diez' => 10,
            'once' => 11, 'doce' => 12, 'trece' => 13, 'catorce' => 14, 'quince' => 15,
            'veinte' => 20, 'treinta' => 30, 'cuarenta' => 40, 'cincuenta' => 50
        ];
        
        // Patrón numérico primero
        // 🆕 Soporte para "7 mil kilogramos", "26 mil kg", etc.
        if (preg_match('/(?:son\s+)?(\d+)\s*mil\s*(?:kilogramos?|kg)/ui', $text, $pesoMatch)) {
            $route['peso_kg'] = (int)$pesoMatch[1] * 1000;
            Log::info('📦 Peso extraído (formato "X mil kg")', ['peso_raw' => $pesoMatch[0], 'peso_kg' => $route['peso_kg']]);
        } elseif (preg_match('/(?:de|por)?\s*(\d+)\s*(?:toneladas?|ton(?:eladas)?)/ui', $text, $pesoMatch)) {
            $route['peso_kg'] = (int)$pesoMatch[1] * 1000;
        } elseif (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:kg|kilos?|kilogramos?)/ui', $text, $pesoMatch)) {
            $route['peso_kg'] = (int)str_replace(['.', ','], '', $pesoMatch[1]);
        }
        // 🆕 Patrón con números escritos: "dos toneladas", "tres kilos"
        elseif (preg_match('/(una|un|dos|tres|cuatro|cinco|seis|siete|ocho|nueve|diez|once|doce|trece|catorce|quince|veinte|treinta|cuarenta|cincuenta)\s*(?:toneladas?|ton)/ui', $textLower, $pesoEscritoMatch)) {
            $numPalabra = strtolower($pesoEscritoMatch[1]);
            if (isset($numerosEscrito[$numPalabra])) {
                $route['peso_kg'] = $numerosEscrito[$numPalabra] * 1000;
            }
        }
        
        // 🔧 FIX CRÍTICO v4: PRE-DETECTAR tamaño de contenedor ANTES de la tara
        // Esto asegura que $route['tamano_contenedor'] esté disponible para el cálculo de tara
        if (!isset($route['tamano_contenedor'])) {
            if (preg_match_all('/(\d+)\s*[×xX]\s*(20|40|45)\s*[\'"]?\s*(?:hc|gp|rf|hq|ot)?/ui', $text, $preContMatches, PREG_SET_ORDER)) {
                $preMatchIndex = min($rutaNumero - 1, count($preContMatches) - 1);
                $route['tamano_contenedor'] = intval($preContMatches[$preMatchIndex][2]);
                Log::info('📦 PRE-DETECCIÓN contenedor (formato NxTAMAÑO)', [
                    'tamano' => $route['tamano_contenedor'],
                    'ruta' => $rutaNumero
                ]);
            } elseif (preg_match('/contenedor(?:es)?\s+(?:de\s+)?(20|40|45)\s*(?:pies|\'|")?/ui', $text, $preContMatch2)) {
                $route['tamano_contenedor'] = intval($preContMatch2[1]);
                Log::info('📦 PRE-DETECCIÓN contenedor (formato "contenedor de X pies")', [
                    'tamano' => $route['tamano_contenedor']
                ]);
            } elseif (preg_match('/\(\d+\)\s*(20|40|45)\s*pies/ui', $text, $preContMatch3)) {
                $route['tamano_contenedor'] = intval($preContMatch3[1]);
                Log::info('📦 PRE-DETECCIÓN contenedor (formato BD "(1) X PIES")', [
                    'tamano' => $route['tamano_contenedor']
                ]);
            }
        }
        
        // 🆕 TARA - Mejorado para detectar más patrones
        // Detectar "no incluye tara", "sin tara", "peso neto", etc.
        $noIncluyeTaraPatterns = [
            'no incluye tara', 'no incluye la tara', 'sin tara', 'peso neto',
            'el peso no incluye tara', 'el peso no incluye la tara', 
            'peso no incluye tara', 'peso no incluye la tara',
            'más tara', 'mas tara', '+ tara'
        ];
        // 🔧 MEJORADO: Detectar "ya incluye tara", "con tara incluida", "kilogramos con tara", etc.
        $yaIncluyeTaraPatterns = [
            'ya incluye tara', 'ya incluye la tara', 'incluye la tara',
            'con tara incluida', 'con la tara incluida', 'tara ya incluida',
            'kilogramos con tara', 'kilos con tara', 'kg con tara',
            'con tara', 'peso con tara', 'peso bruto', 'tara incluida',
            'el peso ya incluye la tara', 'el peso ya incluye tara',
            'peso ya incluye la tara', 'peso ya incluye tara'
        ];
        
        $detectoNoIncluyeTara = false;
        $detectoYaIncluyeTara = false;
        
        foreach ($noIncluyeTaraPatterns as $pattern) {
            if (strpos($textLower, $pattern) !== false) {
                $detectoNoIncluyeTara = true;
                Log::info('🔍 TARA: Detectado patrón NO incluye', ['pattern' => $pattern, 'texto' => $textLower]);
                break;
            }
        }
        
        if (!$detectoNoIncluyeTara) {
            foreach ($yaIncluyeTaraPatterns as $pattern) {
                if (strpos($textLower, $pattern) !== false) {
                    $detectoYaIncluyeTara = true;
                    Log::info('🔍 TARA: Detectado patrón YA incluye', ['pattern' => $pattern, 'texto' => $textLower]);
                    break;
                }
            }
        }
        
        // 🆕 REGLA: Tara SOLO aplica cuando hay CONTENEDORES
        $hayContenedorRuta = self::hayContenedorEnTexto($text) || isset($route['tamano_contenedor']);
        
        if ($detectoNoIncluyeTara && $hayContenedorRuta) {
            // "sin tara" CON contenedor → SUMAR tara
            if (isset($route['peso_kg']) && $route['peso_kg'] > 0) {
                $pesoOriginal = $route['peso_kg'];
                if (isset($route['tamano_contenedor'])) {
                    $taraAplicar = ($route['tamano_contenedor'] == 20) ? 2300 : 3400;
                    Log::info('📦 Usando tamaño de contenedor ya detectado para tara', [
                        'tamano' => $route['tamano_contenedor'],
                        'tara' => $taraAplicar
                    ]);
                } else {
                    $taraAplicar = self::getTaraByContenedor($text);
                }
                $route['peso_kg'] = $pesoOriginal + $taraAplicar;
                $route['tara'] = $taraAplicar;
                $route['incluye_tara'] = true;
                Log::info('📦 TARA SUMADA (no incluye tara, con contenedor)', [
                    'peso_original' => $pesoOriginal,
                    'tara' => $taraAplicar,
                    'peso_con_tara' => $route['peso_kg'],
                    'texto_contenedor' => substr($text, 0, 100)
                ]);
            } else {
                $route['incluye_tara'] = false;
            }
        } elseif ($detectoNoIncluyeTara && !$hayContenedorRuta) {
            // 🆕 "sin tara" pero NO hay contenedor → NO aplicar tara (carga suelta)
            $route['incluye_tara'] = false;
            Log::info('📦 "sin tara" pero sin contenedor → peso se mantiene (carga suelta)', [
                'peso_kg' => $route['peso_kg'] ?? 'N/A'
            ]);
        } elseif ($detectoYaIncluyeTara) {
            $route['incluye_tara'] = true;
            Log::info('📦 TARA ya incluida, peso se mantiene', [
                'peso_kg' => $route['peso_kg'] ?? 'N/A'
            ]);
        } elseif ($hayContenedorRuta) {
            // Contenedor detectado, NO menciona tara → agregar tara por defecto
            if (isset($route['peso_kg']) && $route['peso_kg'] > 0) {
                $pesoOriginal = $route['peso_kg'];
                if (isset($route['tamano_contenedor'])) {
                    $taraAplicar = ($route['tamano_contenedor'] == 20) ? 2300 : 3400;
                    Log::info('📦 Tara usando tamano_contenedor específico de ruta', [
                        'tamano_contenedor' => $route['tamano_contenedor'],
                        'tara_aplicar' => $taraAplicar
                    ]);
                } else {
                    $taraAplicar = self::getTaraByContenedor($text);
                }
                $route['peso_kg'] = $pesoOriginal + $taraAplicar;
                $route['tara'] = $taraAplicar;
                $route['incluye_tara'] = true;
                Log::info('📦 TARA SUMADA automáticamente (contenedor, default)', [
                    'peso_original' => $pesoOriginal,
                    'tara' => $taraAplicar,
                    'peso_con_tara' => $route['peso_kg'],
                    'texto_contenedor' => substr($text, 0, 100)
                ]);
            } else {
                $route['incluye_tara'] = false;
            }
        } else {
            // 🆕 NO hay contenedor, NO menciona tara → NO aplicar tara (carga suelta)
            $route['incluye_tara'] = false;
            Log::info('📦 Sin contenedor, sin mención tara → peso se mantiene (carga suelta)', [
                'peso_kg' => $route['peso_kg'] ?? 'N/A'
            ]);
        }
        
        // 🆕 PRODUCTO: múltiples patrones para mayor flexibilidad
        // 🔴 IMPORTANTE: Guardar como producto_mencionado (sin validar en BD)
        
        // Patrón PRIORITARIO: "sin/con tara de PRODUCTO" (puede ser múltiples palabras)
        // 🔧 FIX: Terminar antes de "por" (con o sin "un valor")
        if (preg_match('/(?:sin|con)\s+tara\s+de\s+([a-záéíóúñ\s]+?)(?=,|\s+por\s|\s+empaque|\s+en\s|$)/ui', $text, $taraProductoMatch)) {
            $prod = trim($taraProductoMatch[1]);
            // Limpiar y validar
            $prod = preg_replace('/\s+/', ' ', $prod); // Normalizar espacios
            if (strlen($prod) >= 3 && !preg_match('/\b(valor|peso|incluye|por|millones?)\b/ui', $prod)) {
                $route['producto'] = strtoupper($prod);
                $route['producto_mencionado'] = strtoupper($prod);
                $route['producto_validado'] = false;
                Log::info('📦 Producto extraído (patrón "sin/con tara de PRODUCTO")', ['producto' => $route['producto']]);
            }
        }
        
        // Patrón 1: "N kg/toneladas de PRODUCTO" (captura hasta 3 palabras)
        if (empty($route['producto']) && preg_match('/(?:\d+\s*(?:mil\s+)?)?(?:toneladas?|ton|kg|kilos?|kilogramos?)\s+de\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})(?=\s+(?:por|con|sin|empaque|en)\s|\s+y\s|,|$)/ui', $text, $productoMatch)) {
            $prod = trim($productoMatch[1]);
            // Excluir palabras que NO son productos
            if (!preg_match('/\b(valor|peso|incluye|tara|por|un|con|en|sin)\b/ui', $prod) && strlen($prod) >= 3) {
                $route['producto'] = strtoupper($prod);
                $route['producto_mencionado'] = strtoupper($prod);
                $route['producto_validado'] = false;
                Log::info('📦 Producto extraído (patrón "X kg de PRODUCTO")', ['producto' => $route['producto']]);
            }
        }
        
        // Patrón 2: "se transportan/transportar/llevar/cargar PRODUCTO"
        if (empty($route['producto']) && preg_match('/(?:se\s+transporta[rn]?|transportar|transportando|llevar|cargar|con)\s+([a-záéíóúñ\s]+?)(?:\s*(?:por|con|en|empaquetados?|son|\d|vamos|$))/ui', $text, $productoMatch)) {
            $producto = trim($productoMatch[1]);
            // Limpiar palabras no válidas
            $producto = preg_replace('/\b(empaquetados?|en\s+cajas?|una|un|dos|tres|ruta|viaje|cotizaci[oó]n|en\s+sacos?)\b/ui', '', $producto);
            // Validar que no sea "valor", "peso", "toneladas"
            if (!preg_match('/\b(valor|peso|toneladas?|millones?|medida|cantidad|vamos)\b/ui', $producto) && strlen(trim($producto)) >= 3) {
                $route['producto'] = strtoupper(trim($producto));
                $route['producto_mencionado'] = strtoupper(trim($producto));
                $route['producto_validado'] = false;
            }
        }
        
        // Patrón 3: Producto pegado sin espacio (ej: "neumáticospor") - extrae palabra antes de "por"
        if (empty($route['producto']) && preg_match('/([a-záéíóúñ]{4,})por\s+un?\s*valor/ui', $text, $productoMatch3)) {
            $route['producto'] = strtoupper(trim($productoMatch3[1]));
            $route['producto_mencionado'] = strtoupper(trim($productoMatch3[1])); // 🆕 Sin validar
            $route['producto_validado'] = false; // 🆕 Flag
        }
        
        // 🆕 VALOR DECLARADO: Múltiples patrones para mayor flexibilidad
        // Patrón 1: "valor declarado de N millones" o "por N millones"
        if (preg_match('/(?:valor\s+(?:declarado\s+)?(?:de\s+)?|por\s+(?:un\s+valor\s+(?:de\s+)?)?)(\d+)\s*(?:millones?|mill?)/ui', $text, $valorMatch)) {
            $route['valor_declarado'] = (int)$valorMatch[1] * 1000000;
            Log::info('💰 Valor declarado extraído (patrón 1)', ['valor' => $route['valor_declarado']]);
        }
        // Patrón 2: "un valor de N millones" o "con valor de N millones"
        elseif (preg_match('/(?:un\s+valor\s+de|con\s+valor\s+de)\s+(\d+)\s*(?:millones?|mill?)/ui', $text, $valorMatch2)) {
            $route['valor_declarado'] = (int)$valorMatch2[1] * 1000000;
            Log::info('💰 Valor declarado extraído (patrón 2)', ['valor' => $route['valor_declarado']]);
        }
        // Patrón 3: "valor N millones" simple
        elseif (preg_match('/\bvalor\s+(?:de\s+)?(\d+)\s*(?:millones?|mill?)/ui', $text, $valorMatch3)) {
            $route['valor_declarado'] = (int)$valorMatch3[1] * 1000000;
            Log::info('💰 Valor declarado extraído (patrón 3)', ['valor' => $route['valor_declarado']]);
        }
        // Patrón 4: "N millones de valor" (invertido)
        elseif (preg_match('/(\d+)\s*(?:millones?|mill?)\s+de\s+valor/ui', $text, $valorMatch4)) {
            $route['valor_declarado'] = (int)$valorMatch4[1] * 1000000;
            Log::info('💰 Valor declarado extraído (patrón 4)', ['valor' => $route['valor_declarado']]);
        }
        
        // 🆕 CANTIDAD: "N unidades" o "son N unidades"
        if (preg_match('/(?:son\s+)?(\d+)\s*(?:unidades?|uds?)/ui', $text, $cantidadMatch)) {
            $route['cantidad'] = (int)$cantidadMatch[1];
        }
        
        // 🆕 EMPAQUE Y CONTENEDOR: Detectar tipo específico para calcular tara correcta
        // 🔧 FIX CRÍTICO v3: MOVER ANTES de la lógica de TARA para que tamano_contenedor esté disponible
        $tamanioContenedor = null; // 20 o 40
        
        // Patrón prioritario: "NxTAMAÑO" (ej: "2x40", "1x20", "1x40HC")
        // 🔧 FIX: Usar preg_match_all para capturar TODOS los contenedores en el texto
        if (preg_match_all('/(\d+)\s*[×xX]\s*(20|40|45)\s*[\'"]?\s*(?:hc|gp|rf|hq|ot)?/ui', $text, $contMatches, PREG_SET_ORDER)) {
            // Determinar cuál match usar según $rutaNumero (1-based)
            $matchIndex = min($rutaNumero - 1, count($contMatches) - 1); // Usar el índice correcto o el último disponible
            $contMatch = $contMatches[$matchIndex];
            
            $tamanioContenedor = intval($contMatch[2]);
            $route['cantidad'] = intval($contMatch[1]);
            $route['empaque'] = 'CONTENEDOR ' . $tamanioContenedor;
            $route['empaque_id'] = 9; // CONTENEDOR
            $route['tamano_contenedor'] = $tamanioContenedor;
            Log::info('📦 Contenedor detectado (formato NxTAMAÑO) - Ruta #' . $rutaNumero, [
                'raw' => $contMatch[0],
                'cantidad' => $route['cantidad'],
                'tamaño' => $tamanioContenedor,
                'total_contenedores_encontrados' => count($contMatches),
                'indice_usado' => $matchIndex
            ]);
        }
        // Patrón: "contenedor de 20/40 pies"
        elseif (preg_match('/contenedor(?:es)?\s+(?:de\s+)?(20|40|45)\s*(?:pies|\'|")?/ui', $text, $contMatch2)) {
            $tamanioContenedor = intval($contMatch2[1]);
            $route['empaque'] = 'CONTENEDOR ' . $tamanioContenedor;
            $route['empaque_id'] = 9;
            $route['tamano_contenedor'] = $tamanioContenedor;
            Log::info('📦 Contenedor detectado (formato "contenedor de X pies")', [
                'tamaño' => $tamanioContenedor
            ]);
        }
        // Patrón: "(1) 20 PIES" o "(1) 40 PIES" (formato BD)
        elseif (preg_match('/\(\d+\)\s*(20|40|45)\s*pies/ui', $text, $contMatch3)) {
            $tamanioContenedor = intval($contMatch3[1]);
            $route['empaque'] = 'CONTENEDOR ' . $tamanioContenedor;
            $route['empaque_id'] = 9;
            $route['tamano_contenedor'] = $tamanioContenedor;
            Log::info('📦 Contenedor detectado (formato BD "(1) X PIES")', [
                'tamaño' => $tamanioContenedor
            ]);
        }
        // Si no detectó contenedor específico, buscar empaque genérico
        else {
            $empaqueMap = [
                'cajas' => ['nombre' => 'CAJAS', 'id' => 2],
                'caja' => ['nombre' => 'CAJAS', 'id' => 2],
                'sacos' => ['nombre' => 'SACOS', 'id' => 4],
                'saco' => ['nombre' => 'SACOS', 'id' => 4],
                'bultos' => ['nombre' => 'BULTOS', 'id' => 5],
                'bulto' => ['nombre' => 'BULTOS', 'id' => 5],
                'pallets' => ['nombre' => 'ESTIBAS / PALLET', 'id' => 7],
                'pallet' => ['nombre' => 'ESTIBAS / PALLET', 'id' => 7],
                'estibas' => ['nombre' => 'ESTIBAS / PALLET', 'id' => 7],
                'contenedor' => ['nombre' => 'CONTENEDOR', 'id' => 9],
                'contenedores' => ['nombre' => 'CONTENEDOR', 'id' => 9],
                'contenido' => ['nombre' => 'CONTENEDOR', 'id' => 9],
                'granel' => ['nombre' => 'GRANEL', 'id' => 6],
            ];
            
            foreach ($empaqueMap as $keyword => $info) {
                if (preg_match('/\b(en\s+)?' . $keyword . '\b/ui', $textLower)) {
                    $route['empaque'] = $info['nombre'];
                    $route['empaque_id'] = $info['id'];
                    break;
                }
            }
        }
        
        // 🆕 VEHÍCULO: detectar tipo - Buscar tipos específicos válidos
        // 🔧 FIX: Usar regex con word boundaries para evitar capturar "sería" en "sería tractomula"
        if (preg_match('/\btractomula\b/ui', $text)) {
            $route['vehiculo'] = 'TRACTOMULA';
        } elseif (preg_match('/\bdobletroque\b/ui', $text)) {
            $route['vehiculo'] = 'DOBLETROQUE';
        } elseif (preg_match('/\btractocamión\b/ui', $text)) {
            $route['vehiculo'] = 'TRACTOCAMION';
        } elseif (preg_match('/\bpatineta\b/ui', $text)) {
            $route['vehiculo'] = 'PATINETA';
        } elseif (preg_match('/\bsencillo\b/ui', $text)) {
            $route['vehiculo'] = 'SENCILLO';
        } elseif (preg_match('/\bminimula\b/ui', $text)) {
            $route['vehiculo'] = 'MINIMULA';
        } elseif (preg_match('/\bturbo\b/ui', $text)) {
            $route['vehiculo'] = 'TURBO';
        }
        
        // Limpiar campos vacíos
        foreach ($route as $key => $value) {
            if ($value === null || $value === '') {
                unset($route[$key]);
            }
        }
        
        // Mantener ruta_numero siempre
        $route['ruta_numero'] = $rutaNumero;
        
        Log::info('📦 extractRouteDataFromText resultado', [
            'ruta_numero' => $rutaNumero,
            'origen' => $route['origen'] ?? 'N/A',
            'destino' => $route['destino'] ?? 'N/A',
            'peso' => $route['peso_kg'] ?? 'N/A',
            'producto' => $route['producto'] ?? 'N/A'
        ]);
        
        return $route;
    }
    
    /**
     * 🆕 NORMALIZAR NOMBRE DE CIUDAD
     * Convierte a formato estándar compatible con BD
     */
    /**
     * 🔧 FIX BUG #534: Valida si un fragmento de texto es una ciudad válida
     * Filtra falsos positivos como números, verbos, palabras clave, etc.
     * 
     * @param string $fragment Fragmento a validar
     * @return bool True si parece ser una ciudad válida
     */
    private static function isValidCityFragment($fragment)
    {
        $fragment = trim($fragment);
        
        // Filtro 1: Rechazar fragmentos vacíos o muy largos
        if (empty($fragment) || strlen($fragment) > 50) {
            return false;
        }
        
        // Filtro 2: Rechazar si contiene números grandes (peso, valor, pies, etc.)
        // Permitir códigos postales o números pequeños en nombres de ciudad
        if (preg_match('/\b\d{2,}\b/', $fragment)) {
            return false;
        }
        
        // Filtro 3: Rechazar palabras clave que NO son ciudades
        // 🔧 FIX #572: Incluir productos y números que se detectan como ciudades falsas
        $keywordsNoCity = [
            'pies', 'pie', 'contenedor', 'kg', 'kilogramos', 'toneladas', 'ton',
            'valor', 'declarado', 'millones', 'millon', 'mil', 'miles',
            'sale', 'llega', 'desde', 'hasta', 'hacia',
            'empaque', 'embalaje', 'guacales', 'cajas', 'bultos',
            'exportacion', 'importacion', 'nacional', 'internacional',
            'unidades', 'cantidad', 'total', 'todo',
            'maquinaria', 'producto', 'mercancia',
            'des de', 'des', // "des de" separado por TextPreprocessor
            // 🔧 FIX #572: Productos comunes que se detectan como orígenes falsos (con y sin acentos)
            'cafe', 'cafes', 'arroz', 'maiz', 'avena', 'cebada', 'trigo', 'frijol', 'frijoles',
            'carne', 'pollo', 'pescado', 'camarones', 'lacteos', 'leche', 'queso',
            'banano', 'bananos', 'platano', 'platanos', 'frutas', 'verduras', 'legumbres',
            'cemento', 'arena', 'grava', 'carbon', 'madera', 'hierro', 'acero',
            'neumaticos', 'llantas', 'repuestos', 'electrodomesticos', 'herramientas',
            // 🔧 FIX #572: Números y palabras de conexión
            'son', 'es', 'hay', 'tiene', 'medio', 'un', 'una', 'dos', 'tres',
        ];
        
        // 🔧 FIX #572: Remover acentos para comparación (café → cafe)
        $fragmentLower = mb_strtolower($fragment, 'UTF-8');
        $fragmentLower = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
            $fragmentLower
        );
        foreach ($keywordsNoCity as $keyword) {
            // 🔧 FIX #572.1: Usar preg_match con \b para palabras completas
            // Evita rechazar "Tunja" por contener "un", "Medellín" por contener "mede", etc.
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/', $fragmentLower)) {
                // 🔧 FIX #572: Log especial para productos/números detectados
                $esFix572 = in_array($keyword, ['cafe', 'cafes', 'arroz', 'maiz', 'son', 'es', 'medio', 'carne', 'pollo', 'pescado', 'banano', 'platano', 'cemento', 'carbon']);
                if ($esFix572) {
                    Log::warning('🚫 FIX#572.1 - Ciudad falsa rechazada en isValidCityFragment (palabra completa)', [
                        'fragment' => $fragment,
                        'keyword_producto_numero' => $keyword
                    ]);
                } else {
                    Log::info('🚫 isValidCityFragment: Rechazado por keyword', [
                        'fragment' => $fragment,
                        'keyword_found' => $keyword
                    ]);
                }
                return false;
            }
        }
        
        // Filtro 4: Rechazar verbos comunes
        $verbos = ['llega', 'sale', 'va', 'viene', 'es', 'son', 'será'];
        if (in_array($fragmentLower, $verbos)) {
            return false;
        }
        
        // Filtro 5: Rechazar si tiene más de 3 palabras (probablemente es una frase, no ciudad)
        if (str_word_count($fragment) > 3) {
            Log::info('🚫 isValidCityFragment: Rechazado por muchas palabras', [
                'fragment' => $fragment,
                'word_count' => str_word_count($fragment)
            ]);
            return false;
        }
        
        // Filtro 6: Rechazar si contiene ":" (formato estructurado)
        if (strpos($fragment, ':') !== false) {
            return false;
        }
        
        // ✅ Si pasó todos los filtros, probablemente es una ciudad
        return true;
    }

    /**
     * 🆕 Normalizar peso desde formato español/latinoamericano
     * Convierte "7.600" (7600) o "7,5" (7.5) a número correcto
     * @param string $rawWeight El peso en formato string
     * @return int El peso normalizado en kg
     */
    private static function normalizeWeight($rawWeight)
    {
        if (empty($rawWeight)) {
            return 0;
        }
        
        $raw = trim($rawWeight);
        
        // Caso 1: Formato de miles español con punto (7.600 = 7600)
        // Patrón: X.XXX o X.XXX.XXX (1-3 dígitos, luego grupos de 3 dígitos separados por punto)
        if (preg_match('/^\d{1,3}(?:\.\d{3})+$/', $raw)) {
            $peso = str_replace('.', '', $raw);
            Log::info('📊 normalizeWeight: formato miles español', ['raw' => $raw, 'result' => $peso]);
            return (int)$peso;
        }
        
        // Caso 2: Formato decimal con coma (7,5 = 7.5 kg)
        if (preg_match('/^\d+,\d{1,2}$/', $raw)) {
            $peso = str_replace(',', '.', $raw);
            Log::info('📊 normalizeWeight: decimal con coma', ['raw' => $raw, 'result' => $peso]);
            return (int)round((float)$peso);
        }
        
        // Caso 3: Formato decimal con punto (7.5 = 7.5 kg) - solo 1-2 decimales
        if (preg_match('/^\d+\.\d{1,2}$/', $raw)) {
            Log::info('📊 normalizeWeight: decimal con punto', ['raw' => $raw, 'result' => $raw]);
            return (int)round((float)$raw);
        }
        
        // Caso 4: Número entero simple
        if (preg_match('/^\d+$/', $raw)) {
            Log::info('📊 normalizeWeight: entero simple', ['raw' => $raw, 'result' => $raw]);
            return (int)$raw;
        }
        
        // Caso 5: Formato mixto (intentar limpiar puntos y comas)
        $peso = str_replace(['.', ','], '', $raw);
        Log::info('📊 normalizeWeight: fallback limpieza total', ['raw' => $raw, 'result' => $peso]);
        return (int)$peso;
    }

    private static function normalizeCityName($cityName)
    {
        // 🔥 POST-PROCESAMIENTO: Eliminar prefijos "importacion", "exportacion", "cotizacion"
        // DEBE HACERSE PRIMERO, antes de cualquier otra normalización
        $cityName = preg_replace('/^(importaci[oó]n|exportaci[oó]n|cotizaci[oó]n(?:\s+de)?)\s+/ui', '', $cityName);
        $cityName = trim($cityName);
        
        // Abreviaciones comunes (Agregado solicitud usuario)
        $abbreviations = [
            // Códigos IATA de aeropuertos (oficiales)
            'BOG' => 'BOGOTA',
            'MED' => 'MEDELLIN',
            'CLO' => 'CALI',
            'BAQ' => 'BARRANQUILLA',
            'CTG' => 'CARTAGENA',
            'BGA' => 'BUCARAMANGA',
            'CUC' => 'CUCUTA',
            'PEI' => 'PEREIRA',
            'MZL' => 'MANIZALES',
            'AXM' => 'ARMENIA',
            'IBE' => 'IBAGUE',
            'NVA' => 'NEIVA',
            'VVC' => 'VILLAVICENCIO',
            'PSO' => 'PASTO',
            'PPN' => 'POPAYAN',
            'SMR' => 'SANTA MARTA',
            'CVE' => 'SINCELEJO',
            'MTR' => 'MONTERIA',
            'VUP' => 'VALLEDUPAR',
            'RCH' => 'RIOHACHA',
            'UIB' => 'QUIBDO',
            'LET' => 'LETICIA',
            'ADZ' => 'SAN ANDRES',
            'EYP' => 'YOPAL',
            'AUC' => 'ARAUCA',
            'FLA' => 'FLORENCIA',
            'MCO' => 'MOCOA',
            'TUN' => 'TUNJA',
            'DUI' => 'DUITAMA',
            'SOG' => 'SOGAMOSO',
            'GIR' => 'GIRARDOT',
            'ZIP' => 'ZIPAQUIRA',
            'FAC' => 'FACATATIVA',
            'SOA' => 'SOACHA',
            
            // Abreviaturas informales comunes
            'BGT' => 'BUCARAMANGA',
            'BQUILLA' => 'BARRANQUILLA',
            'BQLLA' => 'BARRANQUILLA',
            'VVICENCIO' => 'VILLAVICENCIO',
            'PALMIRA' => 'PALMIRA',
            'ENVIGADO' => 'ENVIGADO',
            'ITAGUI' => 'ITAGUI',
            'BELLO' => 'BELLO',
            'BUENAV' => 'BUENAVENTURA',
            'BVTURA' => 'BUENAVENTURA',
            'BUN' => 'BUENAVENTURA',
            'BTURA' => 'BUENAVENTURA',
            'STA MARTA' => 'SANTA MARTA',
            'S MARTA' => 'SANTA MARTA',
            'S ANDRES' => 'SAN ANDRES',
            'APTO' => 'APARTADO',
            'BARRANCA' => 'BARRANCABERMEJA',
            'BMEJA' => 'BARRANCABERMEJA',
            'BMANGA' => 'BUCARAMANGA',
            'FLORIDAB' => 'FLORIDABLANCA',
            'SOLEDAD' => 'SOLEDAD',
            'DOSQ' => 'DOSQUEBRADAS',
            'CGEN' => 'CARTAGENA',
            'CART' => 'CARTAGENA',
            'BQUILL' => 'BARRANQUILLA'
        ];

        // Normalizar entrada inicial
        $cityName = trim($cityName);
        $upperInput = mb_strtoupper($cityName, 'UTF-8');
        
        // Verificar si es una abreviatura exacta
        if (isset($abbreviations[$upperInput])) {
            Log::info('📍 Abreviatura ciudad expandida', ['abbr' => $upperInput, 'full' => $abbreviations[$upperInput]]);
            return $abbreviations[$upperInput];
        }

        // Eliminar acentos y convertir a mayúsculas
        
        // 🆕 LISTA NEGRA: Palabras que NO son ciudades (verbos, conectores, etc.)
        $blackList = [
            'vamos', 'hacer', 'llevar', 'cargar', 'transportar', 'viajar', 'ir', 'ruta', 'viaje', 
            'cotizacion', 'cotización', 'necesito', 'quiero', 'requiero', 'solicito', 'pido',
            'peso', 'valor', 'tara', 'toneladas', 'kilos', 'unidades', 'cajas', 'maíz', 'maiz',
            'llantas', 'contenedor', 'contenido', 'detalle', 'informacion', 'dato', 'datos',
            'estoy', 'esta', 'está', 'hola', 'como', 'estas', 'estás'
        ];
        
        if (in_array(mb_strtolower($cityName), $blackList)) {
            Log::info('🚫 Palabra bloqueada como ciudad', ['palabra' => $cityName]);
            return null;
        }
        
        // 🆕 Eliminar palabras de cortesía y frases comunes
        $cityName = preg_replace('/\b(por\s+favor|gracias|porfavor|ok|bien|nota|importante)\b/ui', '', $cityName);
        $cityName = trim($cityName);
        
        // 🆕 Extraer ciudad de "Aeropuerto de X" o "Terminal de X" (pero NO "Puerto X")
        // "Puerto Asís", "Puerto Carreño" son nombres de ciudades válidos
        if (preg_match('/(?:Aeropuerto|Terminal)\s+(?:de\s+)?([A-Za-záéíóúñÁÉÍÓÚÑ]+)/ui', $cityName, $puertoMatch)) {
            $cityName = trim($puertoMatch[1]);
            Log::info('📍 Ciudad extraída de Aeropuerto/Terminal', ['original' => $cityName, 'ciudad' => $puertoMatch[1]]);
        }
        
        // Mapeo de caracteres especiales
        $replacements = [
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'ñ' => 'N', 'Ñ' => 'N'
        ];
        
        $normalized = strtr($cityName, $replacements);
        $normalized = mb_strtoupper($normalized, 'UTF-8');
        
        // Casos especiales de ciudades conocidas
        $specialCases = [
            'BOGOTA D.C.' => 'BOGOTA',
            'BOGOTA DC' => 'BOGOTA',
            'BOGOTA D.C' => 'BOGOTA',
            'BOGOTÁ' => 'BOGOTA',
            'TULCAN' => 'TULCAN',
            'MEDELLIN' => 'MEDELLIN',
            'CALI' => 'CALI',
            'BARRANQUILLA' => 'BARRANQUILLA',
            'CARTAGENA' => 'CARTAGENA',
            'BUCARAMANGA' => 'BUCARAMANGA',
            'CUCUTA' => 'CUCUTA',
            'PEREIRA' => 'PEREIRA',
            'MANIZALES' => 'MANIZALES',
            'SANTA MARTA' => 'SANTA MARTA',
            'IBAGUE' => 'IBAGUE',
            'PASTO' => 'PASTO',
            'VILLAVICENCIO' => 'VILLAVICENCIO',
            'MONTERIA' => 'MONTERIA',
            'NEIVA' => 'NEIVA'
        ];
        
        if (isset($specialCases[$normalized])) {
            return $specialCases[$normalized];
        }
        
        return $normalized;
    }

    /**
     * 🌍 DETECTAR Y EXPANDIR MÚLTIPLES ORÍGENES/DESTINOS
     * 
     * Detecta cuando se especifican múltiples orígenes y/o destinos en un mensaje
     * y genera todas las combinaciones de rutas.
     * 
     * Ejemplos:
     * - "origen cali y cartagena a destino medellin" → 2 rutas (CALI→MEDELLIN, CARTAGENA→MEDELLIN)
     * - "origen bucaramanga a destino ipiales y cota" → 2 rutas (BUCARAMANGA→IPIALES, BUCARAMANGA→COTA)
     * - "origen bogota y cali a destino medellin y pasto" → 4 rutas (producto cartesiano)
     * 
     * @param string $mensaje El mensaje del usuario
     * @return array|null Array con ['origenes' => [...], 'destinos' => [...]] o null si no detecta múltiples
     */
    private static function detectMultipleRouteCities($mensaje)
    {
        // 🔧 FIX 11: NO procesar si detectamos formato estructurado "Origen: X, Destino: Y" con UNA sola ruta
        // Este formato debe ser manejado por detectMultipleRoutes() en lugar de esta función
        $patronOrigenDestinoEstructurado = '/\bOrigen:\s*([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})\s*,\s*Destino:\s*([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})/ui';
        if (preg_match_all($patronOrigenDestinoEstructurado, $mensaje, $matchesEstructurado)) {
            $countEstructurado = count($matchesEstructurado[0]);
            Log::info('🚫 detectMultipleRouteCities: Formato estructurado detectado, saltando procesamiento', [
                'coincidencias' => $countEstructurado,
                'formato' => 'Origen: X, Destino: Y'
            ]);
            // Retornar null para que NO se procese como múltiples ciudades
            return null;
        }
        
        $origenes = [];
        $destinos = [];
        
        // 🆕 Patrón 0: "Todo llega a DESTINO: desde/de ORIGEN1... y desde/de ORIGEN2..."
        // Ejemplo: "Todo llega a Buenaventura: desde Pereira van... y desde Manizales salen..."
        // 🔧 FIX BUG #542: Detectar múltiples orígenes con destino común al inicio
        if (preg_match('/(?:todo|todos?)\s+(?:llega|llegan|va|van)\s+(?:a|hacia)\s+([a-záéíóúñ\s]+?):\s*(.+)/ui', $mensaje, $mDestino)) {
            $destinoComun = trim($mDestino[1]);
            $restoTexto = $mDestino[2];
            
            // Buscar múltiples orígenes en el resto del texto
            // Patrón: "desde CIUDAD" o "des de CIUDAD" (NO solo "de" para evitar "de productos", "de equipos")
            // 🔧 FIX BUG #543: Cambiar patrón para NO capturar "de PRODUCTO" 
            if (preg_match_all('/(?:desde|des\s+de)\s+([a-záéíóúñ]+)/ui', $restoTexto, $mOrigenes, PREG_OFFSET_CAPTURE)) {
                $origenesDetectados = [];
                $segmentosPorOrigen = []; // 🆕 Guardar texto específico de cada origen
                
                foreach ($mOrigenes[1] as $index => $match) {
                    $ciudad = trim($match[0]);
                    $posicionInicio = $match[1];
                    
                    // Determinar fin del segmento (hasta el próximo "desde" o final del texto)
                    $posicionFin = strlen($restoTexto);
                    if (isset($mOrigenes[1][$index + 1])) {
                        // Hay otro origen después, terminar antes de ese
                        $posicionFin = $mOrigenes[0][$index + 1][1];
                    }
                    
                    // Extraer segmento de texto para este origen
                    $segmento = substr($restoTexto, $mOrigenes[0][$index][1], $posicionFin - $mOrigenes[0][$index][1]);
                    
                    if (self::isValidCityFragment($ciudad)) {
                        $ciudadNormalizada = self::normalizeCityName($ciudad);
                        $origenesDetectados[] = $ciudadNormalizada;
                        $segmentosPorOrigen[$ciudadNormalizada] = trim($segmento);
                    }
                }
                
                // Validar destino
                if (self::isValidCityFragment($destinoComun)) {
                    $destinoNormalizado = self::normalizeCityName($destinoComun);
                    
                    if (count($origenesDetectados) >= 2 && $destinoNormalizado) {
                        Log::info('🌍 Múltiples ciudades detectadas (patrón 0 - Todo llega a DESTINO)', [
                            'origenes' => $origenesDetectados,
                            'destino' => $destinoNormalizado,
                            'total_rutas' => count($origenesDetectados),
                            'segmentos_capturados' => count($segmentosPorOrigen)
                        ]);
                        
                        return [
                            'origenes' => $origenesDetectados, 
                            'destinos' => [$destinoNormalizado],
                            '_patron' => 'destino_comun', // 🆕 Marcar el patrón usado
                            '_segmentos' => $segmentosPorOrigen // 🆕 Texto específico por origen
                        ];
                    }
                }
            }
        }
        
        // Patrón 5: "Origen: CIUDAD1 y CIUDAD2" + "Ciudad: CIUDAD3" (formato email multi-línea)
        // Este patrón busca "Origen:" en una línea y "Ciudad:" en otra línea
        // 🔥 IMPORTANTE: Capturar SOLO hasta "Destino:" para evitar capturar direcciones
        if (preg_match('/Origen:\s*(.+?)(?=\s*(?:Destino:|Ciudad:|\n|$))/uis', $mensaje, $mOrigen)) {
            $origenesText = trim($mOrigen[1]);
            
            // Buscar destino en línea "Ciudad:" (prioridad) o "Destino:" o "Delivery" o "close to"
            $destinoText = null;
            if (preg_match('/Ciudad:\s*([^,\n]+)/ui', $mensaje, $mCiudad)) {
                // Extraer solo el nombre de la ciudad (antes de la coma si existe)
                $destinoText = trim($mCiudad[1]);
            } elseif (preg_match('/Destino:\s*([^,\n]+)/ui', $mensaje, $mDestino)) {
                $destinoText = trim($mDestino[1]);
            } elseif (preg_match('/Delivery\s*(?:–|-|:)?\s*(?:close\s+to|cerca\s+(?:de|a))?\s*([A-Za-záéíóúñÁÉÍÓÚÑ\s]+?)(?:\s*[,;.]|\s+Depart|\s+Coord|\s+Plan|\n|$)/ui', $mensaje, $mDelivery)) {
                // 🆕 Patrón para formato inglés: "Delivery – close to Santa Marta, Departamento..."
                $destinoText = trim($mDelivery[1]);
            } elseif (preg_match('/(?:close\s+to|cerca\s+(?:de|a)|entrega\s+en)\s+([A-Za-záéíóúñÁÉÍÓÚÑ\s]+?)(?:\s*[,;.]|\s+Depart|\n|$)/ui', $mensaje, $mCloseTo)) {
                // 🆕 Patrón para "close to CITY" o "cerca de CITY" o "entrega en CITY"
                $destinoText = trim($mCloseTo[1]);
            }
            
            if ($destinoText) {
                // Separar orígenes por "y" o comas - SOLO tomar palabras simples (nombres de ciudades)
                $origenes = preg_split('/\s*(?:y|,)\s*/ui', $origenesText);
                $origenes = array_map(function($ciudad) {
                    $ciudad = trim($ciudad);
                    // 🔥 FILTRAR: Solo tomar si parece nombre de ciudad (sin ":", sin números, máximo 3 palabras)
                    if (strpos($ciudad, ':') !== false || preg_match('/\d{2,}/', $ciudad) || str_word_count($ciudad) > 3) {
                        return null;
                    }
                    return self::normalizeCityName($ciudad);
                }, array_filter($origenes));
                
                // Eliminar valores null
                $origenes = array_values(array_filter($origenes));
                
                // Normalizar destino
                $destinos = [self::normalizeCityName($destinoText)];
                
                if (count($origenes) > 1 || count($destinos) > 1) {
                    Log::info('🌍 Múltiples ciudades detectadas (patrón 5 - formato email)', [
                        'origenes' => $origenes,
                        'destinos' => $destinos,
                        'total_rutas' => count($origenes) * count($destinos)
                    ]);
                    
                    return ['origenes' => $origenes, 'destinos' => $destinos];
                }
            }
        }
        
        // Patrón 1.5: "sale desde X y llega a Y" (UNA sola ruta con verbos)
        // 🔧 FIX BUG #534: Detectar patrón de ruta única con verbos "sale" y "llega"
        // Este caso debe procesarse ANTES del patrón 2 para evitar falsos positivos
        if (preg_match('/(?:sale|salgo|salir)\s+(?:de|desde)\s+([a-záéíóúñ\s]+?)\s+y\s+(?:llega|llegamos?|llegar)\s+(?:a|hacia)\s+([a-záéíóúñ\s]+?)(?:\s*[,;.]|$)/ui', $mensaje, $m)) {
            $origen = self::normalizeCityName(trim($m[1]));
            $destino = self::normalizeCityName(trim($m[2]));
            
            // Validar que sean ciudades válidas
            if (self::isValidCityFragment($origen) && self::isValidCityFragment($destino)) {
                Log::info('🚫 detectMultipleRouteCities: Patrón de ruta única detectado (sale/llega)', [
                    'origen' => $origen,
                    'destino' => $destino,
                    'patron' => 'sale desde X y llega a Y'
                ]);
                // Retornar null para indicar que NO es multi-ruta
                return null;
            }
        }
        
        // Patrón 1: "origen CIUDAD1 y CIUDAD2 [y CIUDAD3...] a destino CIUDAD"
        // Captura múltiples orígenes separados por "y" o comas
        if (preg_match('/origen\s+(.+?)\s+(?:a|hacia)\s+destino\s+(.+?)(?:\s*[,;.]|$)/ui', $mensaje, $m)) {
            $origenesText = trim($m[1]);
            $destinosText = trim($m[2]);
            
            // Separar ciudades por "y" o comas
            $origenes = preg_split('/\s*(?:y|,)\s*/ui', $origenesText);
            $destinos = preg_split('/\s*(?:y|,)\s*/ui', $destinosText);
            
            // Limpiar y normalizar (quitar palabras extra como "a" al final)
            $origenes = array_map(function($ciudad) {
                $ciudad = trim($ciudad);
                // Remover "a" al final si existe
                $ciudad = preg_replace('/\s+a$/ui', '', $ciudad);
                return self::normalizeCityName($ciudad);
            }, array_filter($origenes));
            
            $destinos = array_map(function($ciudad) {
                return self::normalizeCityName(trim($ciudad));
            }, array_filter($destinos));
            
            // Solo retornar si hay múltiples orígenes O múltiples destinos
            if (count($origenes) > 1 || count($destinos) > 1) {
                Log::info('🌍 Múltiples ciudades detectadas (patrón 1)', [
                    'origenes' => $origenes,
                    'destinos' => $destinos,
                    'total_rutas' => count($origenes) * count($destinos)
                ]);
                
                return ['origenes' => $origenes, 'destinos' => $destinos];
            }
        }
        
        // Patrón 1.6: "Todo va para/a CIUDAD. Des de X..., des de Y..., y des de Z..."
        // Detecta destino común al inicio, seguido de múltiples orígenes con "des de/desde"
        // 🔧 FIX #595: Capturar segmentos de texto individuales por cada origen
        if (preg_match('/(?:todo|todas?)\s+(?:va|van)\s+(?:para|a|hacia)\s+([a-záéíóúñ\s]+?)[\.,]/ui', $mensaje, $destMatch)) {
            $destinoComun = self::normalizeCityName(trim($destMatch[1]));
            
            // Buscar todos los "des de/desde CIUDAD" en el mensaje con posiciones
            // Patrón mejorado: captura cualquier "des de/desde" seguido de ciudad y verbo/número
            if (preg_match_all('/(?:des\s+de|desde)\s+([a-záéíóúñ\s]+?)\s+(?:salen?|sale|van|va|\d)/ui', $mensaje, $origenMatches, PREG_OFFSET_CAPTURE)) {
                $origenes = [];
                $segmentosPorOrigen = [];
                
                foreach ($origenMatches[1] as $index => $match) {
                    $origenRaw = $match[0];
                    $origen = self::normalizeCityName(trim($origenRaw));
                    
                    if (self::isValidCityFragment($origen)) {
                        $origenes[] = $origen;
                        
                        // Capturar el segmento de texto para este origen
                        // Desde el inicio del match hasta el próximo "des de/desde" o fin del mensaje
                        $posicionInicio = $origenMatches[0][$index][1];
                        $posicionFin = strlen($mensaje);
                        
                        // Si hay otro origen después, terminar antes de ese
                        if (isset($origenMatches[0][$index + 1])) {
                            $posicionFin = $origenMatches[0][$index + 1][1];
                        }
                        
                        // Extraer el segmento completo
                        $segmento = substr($mensaje, $posicionInicio, $posicionFin - $posicionInicio);
                        $segmentosPorOrigen[$origen] = trim($segmento);
                        
                        Log::info('📍 Segmento capturado para origen', [
                            'origen' => $origen,
                            'segmento_preview' => substr($segmento, 0, 100)
                        ]);
                    }
                }
                
                if (count($origenes) > 1 && self::isValidCityFragment($destinoComun)) {
                    Log::info('🌍 Múltiples ciudades detectadas (patrón 1.6 - destino común al inicio)', [
                        'origenes' => $origenes,
                        'destinos' => [$destinoComun],
                        'total_rutas' => count($origenes),
                        'segmentos_capturados' => count($segmentosPorOrigen)
                    ]);
                    
                    return [
                        'origenes' => $origenes, 
                        'destinos' => [$destinoComun],
                        '_patron' => 'destino_comun', // 🆕 Marcar como patrón de destino común
                        '_segmentos' => $segmentosPorOrigen // 🆕 Incluir segmentos de texto por origen
                    ];
                }
            }
        }
        
        // Patrón 2: "de/desde CIUDAD1 y CIUDAD2 a/hacia CIUDAD3 [y CIUDAD4]"
        // 🚫 NO procesar si comienza con "cotización de" (es ruta individual)
        // 🔧 FIX BUG #534: Filtrar falsos positivos (números, palabras clave no-ciudad)
        if (!preg_match('/^cotizaci[oó]n\s+de/ui', $mensaje) && 
            preg_match('/(?:de|desde)\s+(.+?)\s+(?:a|hacia)\s+(.+?)(?:\s*[,;.]|$)/ui', $mensaje, $m)) {
            $origenesText = trim($m[1]);
            $destinosText = trim($m[2]);
            
            $origenes = preg_split('/\s*(?:y|,)\s*/ui', $origenesText);
            $destinos = preg_split('/\s*(?:y|,)\s*/ui', $destinosText);
            
            // 🔧 FIX BUG #534: Filtrar fragmentos que NO son ciudades
            $origenes = array_map(function($ciudad) {
                $ciudad = trim($ciudad);
                // Filtrar falsos positivos
                if (self::isValidCityFragment($ciudad)) {
                    return self::normalizeCityName($ciudad);
                }
                return null;
            }, array_filter($origenes));
            
            $destinos = array_map(function($ciudad) {
                $ciudad = trim($ciudad);
                if (self::isValidCityFragment($ciudad)) {
                    return self::normalizeCityName($ciudad);
                }
                return null;
            }, array_filter($destinos));
            
            // Eliminar valores null
            $origenes = array_values(array_filter($origenes));
            $destinos = array_values(array_filter($destinos));
            
            // Solo retornar si hay múltiples orígenes O múltiples destinos
            if (count($origenes) > 1 || count($destinos) > 1) {
                Log::info('🌍 Múltiples ciudades detectadas (patrón 2)', [
                    'origenes' => $origenes,
                    'destinos' => $destinos,
                    'total_rutas' => count($origenes) * count($destinos)
                ]);
                
                return ['origenes' => $origenes, 'destinos' => $destinos];
            }
        }
        
        // Patrón 3: Buscar múltiples orígenes/destinos de forma independiente
        // "origen cali, cartagena y bogota, destino medellin"
        $origenesDetectados = [];
        $destinosDetectados = [];
        
        if (preg_match('/origen\s+([^d]+?)(?:\s*,\s*destino|$)/ui', $mensaje, $m)) {
            $origenesText = trim($m[1]);
            // Limpiar "a destino" al final si existe
            $origenesText = preg_replace('/\s*(?:a|hacia)\s+destino$/ui', '', $origenesText);
            
            $origenesDetectados = preg_split('/\s*(?:y|,)\s*/ui', $origenesText);
            $origenesDetectados = array_map(function($ciudad) {
                $ciudad = trim($ciudad);
                // Quitar "a" al final
                $ciudad = preg_replace('/\s+a$/ui', '', $ciudad);
                return self::normalizeCityName($ciudad);
            }, array_filter($origenesDetectados));
        }
        
        if (preg_match('/destino\s+([^,;.]+?)(?:\s*[,;.]|$)/ui', $mensaje, $m)) {
            $destinosText = trim($m[1]);
            $destinosDetectados = preg_split('/\s*(?:y|,)\s*/ui', $destinosText);
            $destinosDetectados = array_map(function($ciudad) {
                return self::normalizeCityName(trim($ciudad));
            }, array_filter($destinosDetectados));
        }
        
        if ((count($origenesDetectados) > 1 || count($destinosDetectados) > 1) && 
            (count($origenesDetectados) > 0 && count($destinosDetectados) > 0)) {
            Log::info('🌍 Múltiples ciudades detectadas (patrón 3)', [
                'origenes' => $origenesDetectados,
                'destinos' => $destinosDetectados,
                'total_rutas' => count($origenesDetectados) * count($destinosDetectados)
            ]);
            
            return ['origenes' => $origenesDetectados, 'destinos' => $destinosDetectados];
        }
        
        // Patrón 4: SIN palabras "origen/destino" - Solo ciudades separadas por comas y "a"
        // Ejemplo: "cali, medellín, ipiales a cota"
        // Formato: CIUDAD1, CIUDAD2, CIUDAD3 a/hacia CIUDAD4
        // 🔥 IMPORTANTE: Capturar solo nombres de ciudades, detener en palabras clave
        // 🚫 NO procesar si comienza con "cotización de" (es ruta individual)
        // 🔧 FIX BUG #534: Aplicar validación de fragmentos de ciudad
        if (!preg_match('/^cotizaci[oó]n\s+de/ui', $mensaje) && 
            preg_match('/^(.+?)\s+(?:a|hacia)\s+(.+?)(?:\s+(?:son|es|será|de|vehículo|peso|cantidad|valor|producto|empaque|kg|ton|toneladas?|sacos?|cajas?|pallets?)|,\s*(?:son|es|vehículo)|$)/ui', $mensaje, $m)) {
            $origenesText = trim($m[1]);
            $destinosText = trim($m[2]);
            
            // Solo procesar si hay al menos UNA coma en orígenes (indica múltiples ciudades)
            // O si hay múltiples destinos
            if (strpos($origenesText, ',') !== false || strpos($destinosText, ',') !== false || 
                preg_match('/\s+y\s+/ui', $origenesText) || preg_match('/\s+y\s+/ui', $destinosText)) {
                
                $origenes = preg_split('/\s*(?:y|,)\s*/ui', $origenesText);
                $destinos = preg_split('/\s*(?:y|,)\s*/ui', $destinosText);
                
                // 🔧 FIX BUG #534: Validar fragmentos antes de normalizar
                $origenes = array_map(function($ciudad) {
                    $ciudad = trim($ciudad);
                    if (self::isValidCityFragment($ciudad)) {
                        return self::normalizeCityName($ciudad);
                    }
                    return null;
                }, array_filter($origenes));
                
                $destinos = array_map(function($ciudad) {
                    $ciudad = trim($ciudad);
                    if (self::isValidCityFragment($ciudad)) {
                        return self::normalizeCityName($ciudad);
                    }
                    return null;
                }, array_filter($destinos));
                
                // 🔧 FIX BUG #534: Eliminar valores null y reindexar
                $origenes = array_values(array_filter($origenes));
                $destinos = array_values(array_filter($destinos));
                
                // Solo retornar si hay múltiples orígenes O múltiples destinos Y todos son válidos
                if (count($origenes) > 1 || count($destinos) > 1) {
                    Log::info('🌍 Múltiples ciudades detectadas (patrón 4 - sin "origen/destino")', [
                        'origenes' => $origenes,
                        'destinos' => $destinos,
                        'total_rutas' => count($origenes) * count($destinos)
                    ]);
                    
                    return ['origenes' => $origenes, 'destinos' => $destinos];
                }
            }
        }
        
        return null;
    }

    /**
     * ⚖️ EXTRAER PESO (en kilogramos)
     */
    private static function extractPeso($text)
    {
        // 🆕 Patrón: "Total weight: 5,647.6 kg" (formato con comas inglesas)
        if (preg_match('/Total\s+weight\s*:\s*([\d,]+(?:\.\d+)?)\s*(?:kg|kilos?)?/ui', $text, $matches)) {
            // Remover comas (formato inglés de miles)
            $peso = str_replace(',', '', $matches[1]);
            Log::info('📊 Peso detectado (patrón "Total weight: X")', ['peso' => $peso, 'raw' => $matches[1]]);
            return (float)$peso; // Usar float para mantener decimales
        }
        
        // 🆕 Patrón PRIORITARIO: "Peso bruto: 9.900 kg" (formato formal)
        if (preg_match('/Peso\s+bruto\s*:\s*([\d.,]+)\s*(?:kg|kilos?)?/ui', $text, $matches)) {
            $peso = str_replace(['.', ','], '', $matches[1]);
            Log::info('📊 Peso detectado (patrón "Peso bruto: X")', ['peso' => $peso, 'raw' => $matches[1]]);
            return (int)$peso;
        }
        
        // 🔧 FIX: Patrón para "Peso: X toneladas" PRIMERO (antes del patrón genérico "Peso: X kg")
        // Ejemplo: "Peso: 20 toneladas + tara" → 20000 kg
        if (preg_match('/Peso\s*:\s*(\d+(?:[.,]\d+)?)\s*(?:toneladas?|ton)/ui', $text, $matches)) {
            $toneladas = (float)str_replace(',', '.', $matches[1]);
            $peso = (int)($toneladas * 1000);
            Log::info('📊 Peso detectado (patrón "Peso: X toneladas")', ['toneladas' => $toneladas, 'peso_kg' => $peso]);
            return $peso;
        }
        
        // 🆕 Patrón PRIORITARIO: "Peso: 9.900 kg" (formato con puntos de miles)
        // Ejemplo: "Peso: 9.900 kg"
        if (preg_match('/Peso\s*:\s*([\d.,]+)\s*(?:kg|kilos?|kilogramos?)?/ui', $text, $matches)) {
            // Remover puntos de miles y comas
            $peso = str_replace(['.', ','], '', $matches[1]);
            Log::info('📊 Peso detectado (patrón "Peso: X kg")', ['peso' => $peso, 'raw' => $matches[1]]);
            return (int)$peso;
        }
        
        // 🆕 Patrón: "Gross Weight: NÚMERO" o "Weight: NÚMERO" (formato profesional sin unidad)
        // Ejemplo: "Gross Weight: 9900"
        if (preg_match('/(?:Gross\s+)?Weight\s*:\s*(\d+(?:[.,]\d+)?)/ui', $text, $matches)) {
            $valor = (float)str_replace(',', '.', $matches[1]);
            Log::info('📊 Peso detectado (patrón "Gross Weight: X")', ['peso' => $valor]);
            return (int)$valor;
        }
        
        // 🆕 Patrón: "cambia el peso a X"
        if (preg_match('/(?:cambia|cambiar)(?:\s+el)?\s+peso\s+(?:a|por)\s*(\d+(?:[.,]\d+)?)\s*(?:kg|kilos?|kilogramos?|toneladas?|ton|t\b)?/ui', $text, $matches)) {
            $valor = (float)str_replace(',', '.', $matches[1]);
            if (isset($matches[2]) && preg_match('/ton/i', $matches[2])) $valor *= 1000;
            Log::info('📊 Peso detectado (patrón "cambia el peso a X")', ['peso' => $valor]);
            return (int)$valor;
        }
        
        // 🆕 Patrón PRIORITARIO: Corrección de peso
        // "el peso es 5000 kg" o "peso: 3 toneladas"
        if (preg_match('/(?:el\s+)?peso\s*(?:es|será|sea|queda|:)\s*(\d+(?:[.,]\d+)?)\s*(?:kg|kilos?|kilogramos?|toneladas?|ton|t\b)?/ui', $text, $matches)) {
            $valor = (float)str_replace(',', '.', $matches[1]);
            // Si no tiene unidad o es kg
            if (!isset($matches[2]) || preg_match('/kg|kilo/i', $matches[2] ?? '')) {
                Log::info('📊 Peso detectado (patrón CORRECCIÓN)', ['peso' => $valor]);
                return (int)$valor;
            }
            // Si es toneladas
            if (preg_match('/ton/i', $matches[2] ?? '')) {
                Log::info('📊 Peso detectado (patrón CORRECCIÓN toneladas)', ['toneladas' => $valor]);
                return (int)($valor * 1000);
            }
        }
        
        // Formato profesional con puntos de miles: "26.000,000 KGS" o "26.000.000 KGS"
        if (preg_match('/(\d{1,3}(?:[.,]\d{3})+)\s*(?:kgs?|kilos?|kilogramos?)/ui', $text, $matches)) {
            // Remover todos los puntos y comas, luego convertir
            $peso = str_replace(['.', ','], '', $matches[1]);
            Log::info('Peso detectado (formato profesional con miles)', ['raw' => $matches[1], 'parsed' => $peso]);
            return (int)$peso;
        }
        
        // Toneladas
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:toneladas?|ton|t\b)/ui', $text, $matches)) {
            $toneladas = (float)str_replace(',', '.', $matches[1]);
            return (int)($toneladas * 1000);
        }
        
        // 🆕 FIX: Kilogramos con formato de miles español/latinoamericano (7.600 kg = 7600 kg)
        // Patrón: Número con punto de miles seguido de kg/kilos/kilogramos
        // Ejemplo: "7.600 kilogramos" → 7600, "12.500 kg" → 12500, "1.200.000 kg" → 1200000
        if (preg_match('/(\d{1,3}(?:\.\d{3})+)\s*(?:kg|kilos?|kilogramos?)/ui', $text, $matches)) {
            // Remover puntos de miles (formato español/latam)
            $peso = str_replace('.', '', $matches[1]);
            Log::info('📊 Peso detectado (formato miles español: X.XXX kg)', ['raw' => $matches[1], 'parsed' => $peso]);
            return (int)$peso;
        }
        
        // Kilogramos (simple) - números sin separador de miles
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:kg|kilos?|kilogramos?)/ui', $text, $matches)) {
            // Si tiene punto o coma y es formato decimal (ej: 7.5 kg)
            $valor = $matches[1];
            // Detectar si es decimal real (solo 1-2 dígitos después del punto/coma)
            if (preg_match('/^(\d+)[.,](\d{1,2})$/', $valor, $decMatch)) {
                // Es decimal: 7.5 kg → 7.5 kg, 10,5 kg → 10.5 kg
                $peso = (float)str_replace(',', '.', $valor);
                Log::info('📊 Peso detectado (decimal)', ['raw' => $valor, 'parsed' => $peso]);
                return (int)round($peso);
            }
            // Número entero simple
            return (int)str_replace([',', '.'], '', $valor);
        }

        return null;
    }

    /**
     * 📦 EXTRAER PRODUCTO
     */
    private static function extractProducto($text)
    {
        // 🔧 FIX: Lista de palabras que son VEHÍCULOS y NO deben detectarse como productos
        $vehiculosExcluidos = ['patineta', 'tractomula', 'turbo', 'sencillo', 'dobletroque', 'camioneta', 
                               'minimula', 'tractocamión', 'tractocamion', 'camion', 'camión', 'trailer',
                               'furgon', 'furgón', 'niñera', 'ninera', 'mula', 'doble troque', 'cama baja',
                               'camabaja', 'estacas', 'plataforma', 'carrotanque', 'volqueta'];
        
        // 🔧 FIX: Función auxiliar para validar y retornar producto (evita duplicar código)
        $validarYRetornarProducto = function($producto, $patron) use ($vehiculosExcluidos) {
            $productoLower = mb_strtolower(trim($producto), 'UTF-8');
            // Verificar si es un vehículo
            foreach ($vehiculosExcluidos as $vehiculo) {
                if (strpos($productoLower, $vehiculo) !== false || $productoLower === $vehiculo) {
                    Log::info("🚛 Producto '{$producto}' es un vehículo, ignorando (patrón: {$patron})");
                    return null; // Es un vehículo, no retornar como producto
                }
            }
            Log::info("📦 Producto detectado ({$patron})", ['producto' => $producto]);
            return mb_strtoupper(trim($producto), 'UTF-8');
        };
        
        // 🆕 PATRÓN NARRATIVO: "Una carga de PRODUCTO" (incluso si texto está truncado)
        // Ejemplo: "Una carga de maíz de Bogotá a Cali" → Producto: MAÍZ
        // También: "Una carga de maíz" (truncado) → Producto: MAÍZ
        // 🔧 FIX BUG #538: Detectar productos en formato narrativo (versión flexible)
        if (preg_match('/(?:una\s+)?carga\s+de\s+([a-záéíóúñ]+)(?:\s|$)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            // Excluir palabras comunes que no son productos
            $excludeWords = ['origen', 'destino', 'bogota', 'bogotá', 'cali', 'medellin', 'medellín', 'barranquilla', 'cartagena', 'bucaramanga', 'manizales', 'pereira', 'ibague', 'ibagué', 'pasto', 'cucuta', 'cúcuta', 'villavicencio', 'neiva', 'santa', 'marta', 'monteria', 'montería', 'popayan', 'popayán', 'valledupar', 'quibdo', 'quibdó', 'florencia', 'yopal', 'armenia', 'tunja', 'sincelejo', 'riohacha', 'rionegro'];
            // 🔧 FIX: También excluir vehículos
            $excludeWords = array_merge($excludeWords, $vehiculosExcluidos);
            if (strlen($producto) > 2 && strlen($producto) < 50 && !in_array(mb_strtolower($producto, 'UTF-8'), $excludeWords)) {
                $result = $validarYRetornarProducto($producto, 'Una carga de X');
                if ($result) return $result;
            }
        }
        
        // 🆕 PATRÓN MUY PRIORITARIO: "productos electrónicos (televisores)" o "X cajas de televisores"
        // Detectar productos específicos mencionados en paréntesis o como detalle principal
        if (preg_match('/(?:productos?\s+electr[óo]nicos?|electr[óo]nicos?)\s*\(([a-záéíóúñ\s]+)\)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            $result = $validarYRetornarProducto($producto, 'productos electrónicos (X)');
            if ($result) return $result;
        }
        
        // 🆕 PATRÓN: "N cajas/palets de PRODUCTO" (donde PRODUCTO es el bien, no el material del empaque)
        // Ejemplo: "10 cajas de televisores" → Producto: TELEVISORES, NO "CAJAS" o "CARTÓN"
        // Ejemplo: "5 palets de mercancía variada" → Producto: MERCANCIA VARIADA
        if (preg_match('/(?:\d+\s+)?(?:cajas?|bultos?|paquetes?|palets?|pallets?)\s+de\s+([a-záéíóúñ\s]+?)(?:\s*,|\s+embalaje|\s+valor|\s+necesito|\s+y\s+|$)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            // Eliminar materiales de empaque que se hayan capturado
            $producto = preg_replace('/\b(?:cart[oó]n|madera|pl[aá]stico|metal)\b/ui', '', $producto);
            $producto = trim($producto);
            
            if (strlen($producto) > 2 && strlen($producto) < 100) {
                $result = $validarYRetornarProducto($producto, 'X cajas/palets de PRODUCTO');
                if ($result) return $result;
            }
        }
        
        // 🆕 Patrón ALTA PRIORIDAD: "N toneladas/kg de PRODUCTO"
        // Ejemplo: "20 toneladas de papa", "500 kg de arroz", "1.200 kg de repuestos industriales"
        // 🔧 FIX #595: Terminar antes de "en" para no capturar empaque
        // Permite texto intermedio como "sin tara", "con tara", etc.
        if (preg_match('/(?:\d+(?:[.,]\d+)?\s+)?(?:toneladas?|kg|kilos?|kilogramos?)\s+(?:sin\s+tara\s+)?(?:con\s+tara\s+)?de\s+([a-záéíóúñ\s]+?)(?:\s*,|\s+(?:en|veh[ií]culo|empaque|embalaje|cantidad|valor|y\s+|con\s+)|$)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            if (strlen($producto) > 1 && strlen($producto) < 50) {
                $result = $validarYRetornarProducto($producto, 'X toneladas de Y');
                if ($result) return $result;
            }
        }
        
        // 🆕 Patrón: "Tipo de mercancía: X" (formato formal)
        // Ejemplo: "Tipo de mercancía: Aislador GY"
        if (preg_match('/Tipo\s+de\s+mercancía\s*:\s*([A-Za-záéíóúñÁÉÍÓÚÑ\s0-9]+?)(?:\s*\n|$)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            if (strlen($producto) > 1 && strlen($producto) < 100) {
                $result = $validarYRetornarProducto($producto, 'Tipo de mercancía: X');
                if ($result) return $result;
            }
        }
        
        // 🆕 Patrón PRIORITARIO: "Producto: NOMBRE" (formato simple, para en línea vacía o 'Favor')
        // Ejemplo: "Producto: Aislador GY"
        if (preg_match('/Producto\s*:\s*([A-Za-záéíóúñÁÉÍÓÚÑ\s0-9]+?)(?:\s*\n\s*\n|\s*\n\s*(?:Favor|Por\s+favor|Quedamos|Validar|NOTA)|$)/uim', $text, $matches)) {
            $producto = trim($matches[1]);
            // Limpiar espacios extra
            $producto = preg_replace('/\s+/', ' ', $producto);
            
            // Validar que no sea vacío y no sea un valor monetario
            if (strlen($producto) > 1 && strlen($producto) < 100 && !preg_match('/^[\d.,]+\s*(USD|COP)?$/ui', $producto)) {
                $result = $validarYRetornarProducto($producto, 'Producto: X');
                if ($result) return $result;
            }
        }
        
        // 🆕 Patrón: "Mercancía: NOMBRE" (formato profesional, para en línea vacía)
        // IMPORTANTE: Incluir variante con bullet (•)
        if (preg_match('/(?:^|\n)\s*(?:•\s*)?Mercancía\s*:\s*([A-Za-záéíóúñÁÉÍÓÚÑ\s\-\.0-9]+?)(?:\s*\n\s*\n|\s*\n\s*(?:Por\s+favor|Favor|Validar)|$)/uim', $text, $matches)) {
            $producto = trim($matches[1]);
            // Limpiar espacios extra y textos residuales
            $producto = preg_replace('/\s+/', ' ', $producto);
            // Remover "USD" si se capturó por error
            $producto = preg_replace('/\s*\d+[.,]?\d*\s*USD$/ui', '', $producto);
            $producto = trim($producto);
            
            // Validar que no sea un número o valor monetario
            if (strlen($producto) > 1 && strlen($producto) < 100 && !preg_match('/^\d+[.,]?\d*\s*(USD|COP)?$/ui', $producto)) {
                $result = $validarYRetornarProducto($producto, 'Mercancía: X');
                if ($result) return $result;
            }
        }
        
        // 🆕 Patrón PRIORITARIO: Corrección de producto - MÁS FLEXIBLE
        // "producto es/será miel" o "el producto es miel" o "aquí el producto es miel"
        // Aceptamos que haya texto antes de "el producto"
        if (preg_match('/(?:el\s+)?producto\s*(?:es|será|sea|cambia\s+a|deja|queda|:|ser[aá])\s+([a-záéíóúñ\s]+?)(?:\s*[.,;]|\s+y\s+|\s+para|\s+por|$)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            $producto = preg_replace('/\b(por\s+favor|gracias|ok|bien|aqui|aqu[ií])\b/ui', '', $producto);
            $producto = trim($producto);
            
            if (strlen($producto) > 1 && strlen($producto) < 50) {
                $result = $validarYRetornarProducto($producto, 'producto es X');
                if ($result) return $result;
            }
        }
        
        // 🆕 Patrón EXTRA: "aqui/ahi el producto es X" - cuando hay prefijo
        if (preg_match('/(?:aqu[ií]|ah[ií]|aqui|ahi)\s+(?:el\s+)?producto\s*(?:es|será|sea|:)\s+([a-záéíóúñ\s]+?)(?:\s*[.,;]|\s+y\s+|$)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            
            if (strlen($producto) > 1 && strlen($producto) < 50) {
                $result = $validarYRetornarProducto($producto, 'aqui el producto es X');
                if ($result) return $result;
            }
        }
        
        // 🆕 Patrón: "cambia el producto a X" (con 'el' en medio)
        if (preg_match('/(?:cambia|cambiar)(?:\s+el)?\s+producto\s+(?:a|por)\s+([a-záéíóúñ]+)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            
            if (strlen($producto) > 1 && strlen($producto) < 50) {
                $result = $validarYRetornarProducto($producto, 'cambia el producto a X');
                if ($result) return $result;
            }
        }
        
        // 🆕 Patrón: "cambialo a X" o "pon X"
        // ⚠️ EXCLUIR si el texto es corrección de origen/destino/empaque
        $esCambioDeOtroCampo = preg_match('/\b(destino|origen|empaque|embalaje)\s+(cambia|cambialo)/ui', $text);
        if (!$esCambioDeOtroCampo && preg_match('/(?:cambialo|cambiale|reemplaza|pon)\s+(?:a|por)?\s*([a-záéíóúñ]+)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            
            if (strlen($producto) > 2 && strlen($producto) < 50) {
                $result = $validarYRetornarProducto($producto, 'cambia a X');
                if ($result) return $result;
            }
        }
        
        // 🆕 Patrón: "producto ... deja X" (para frases como "en producto cambia deja miel")
        if (preg_match('/producto.*?deja\s+([a-záéíóúñ]+)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            
            if (strlen($producto) > 1 && strlen($producto) < 50) {
                $result = $validarYRetornarProducto($producto, 'producto...deja X');
                if ($result) return $result;
            }
        }
        
        // Patrón PRIORITARIO: "[cantidad] toneladas de PRODUCTO"
        if (preg_match('/\d+\s*toneladas?\s+de\s+([a-záéíóúñ\s]+?)(?:\s+(?:por|con|sin|empaquetad|empacad|en\s+(?:cajas|sacos|bultos)|y)\s+|\.|,|$)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            $producto = preg_replace('/\b(para|llevar|transportar|toneladas?|de|cada\s+una)\b/ui', '', $producto);
            $producto = trim($producto);
            
            if (strlen($producto) > 2 && strlen($producto) < 100) {
                $result = $validarYRetornarProducto($producto, 'X toneladas de PRODUCTO');
                if ($result) return $result;
            }
        }
        
        // Patrón 1: "producto NOMBRE" o "mercancía NOMBRE"
        if (preg_match('/(?:producto|mercancía|mercancia)\s+([a-záéíóúñ\s]+?)(?:\s+empaquetad|empacad|en\s+cajas|\s+y\s+|\.|,|$)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            $producto = preg_replace('/\b(para|llevar|transportar|toneladas?)\b/ui', '', $producto);
            $producto = trim($producto);
            
            if (strlen($producto) > 2 && strlen($producto) < 100) {
                $result = $validarYRetornarProducto($producto, 'producto X');
                if ($result) return $result;
            }
        }
        
        // Patrón 2 (FALLBACK): "de/llevar/transportar PRODUCTO" - menos prioritario
        if (preg_match('/(?:transportar|llevar)\s+([a-záéíóúñ\s]+?)(?:\s+de\s+\d+|\s+por\s+|\s+empaquetad|\s+y\s+|\.|,|$)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            $producto = preg_replace('/\b(para|llevar|transportar|toneladas?|de|cada\s+una)\b/ui', '', $producto);
            $producto = trim($producto);
            
            // 🔧 FIX: Verificar que no sea un vehículo
            if (strlen($producto) > 2 && strlen($producto) < 100 && !in_array(mb_strtolower($producto, 'UTF-8'), $vehiculosExcluidos)) {
                $result = $validarYRetornarProducto($producto, 'transportar X');
                if ($result) return $result;
            }
        }
        
        // 🔧 FIX FINAL: Buscar la última palabra del texto que podría ser producto
        // Ejemplo: "Mosquera - Itagui patineta" → patineta es vehículo, NO producto
        // Si la última palabra es un vehículo, NO usarla como producto
        $palabras = preg_split('/[\s\-]+/', trim($text));
        $ultimaPalabra = end($palabras);
        if ($ultimaPalabra && in_array(mb_strtolower($ultimaPalabra, 'UTF-8'), $vehiculosExcluidos)) {
            Log::info('🚛 Última palabra es un vehículo, no detectar como producto', ['palabra' => $ultimaPalabra]);
            // No retornar producto, es un vehículo
        }

        Log::warning('⚠️ No se pudo extraer producto del texto', ['text_sample' => substr($text, 0, 200)]);
        return null;
    }

    /**
     * 🔢 EXTRAER CANTIDAD
     * 
     * REGLA IMPORTANTE: Si hay contenedor mencionado, la cantidad es el número de contenedores,
     * NO el contenido interno (cajas, bultos, etc.)
     * 
     * Ejemplo: "1 contenedor de 40 pies con 850 cajas" → cantidad = 1
     * Ejemplo: "850 cajas de herramientas" (sin contenedor) → cantidad = 850
     */
    private static function extractCantidad($text)
    {
        $lowerText = mb_strtolower($text);
        
        // 🆕 PASO 1: Detectar si hay contexto de CONTENEDOR
        // Si menciona contenedor, la cantidad es el número de contenedores, NO el contenido interno
        $hayContenedor = preg_match('/contenedor(?:es)?|container/ui', $text);
        
        if ($hayContenedor) {
            Log::info('📦 Contexto de contenedor detectado - buscando número de contenedores', ['texto' => substr($text, 0, 200)]);
            
            // Patrón: "N contenedor(es) de X pies" - extrae el número de contenedores
            // Ejemplo: "1 contenedor de 40 pies", "2 contenedores de 20 pies"
            if (preg_match('/(\d+)\s*contenedor(?:es)?/ui', $text, $matches)) {
                Log::info('📦 Cantidad de contenedores detectada', ['raw' => $matches[0], 'cantidad' => $matches[1]]);
                return (int)$matches[1];
            }
            
            // Patrón: Formato "NxTAMAÑO" como "2x40hc", "1x20'"
            if (preg_match('/(\d+)\s*[×xX]\s*(?:20|40|45)\s*[\'"]?\s*(?:hc|gp|rf|hq)?/ui', $text, $matches)) {
                Log::info('📦 Cantidad de contenedores detectada (formato NxTAMAÑO)', ['raw' => $matches[0], 'cantidad' => $matches[1]]);
                return (int)$matches[1];
            }
            
            // Si solo dice "contenedor" sin número explícito, asumir 1
            if (preg_match('/(?:es|necesito|requiero|traigo|tengo)\s+(?:un\s+)?contenedor(?!\s*es)/ui', $text)) {
                Log::info('📦 Contenedor singular detectado, asumiendo cantidad 1');
                return 1;
            }
            
            // Patrón: "un contenedor" sin número
            if (preg_match('/\bun\s+contenedor\b/ui', $text)) {
                Log::info('📦 "Un contenedor" detectado, cantidad = 1');
                return 1;
            }
            
            // Si hay contexto de contenedor pero no encontramos número específico, asumir 1
            Log::info('📦 Contenedor mencionado sin cantidad específica, asumiendo 1');
            return 1;
        }
        
        // 🔽 PASO 2: Si NO hay contenedor, usar lógica normal de cantidad
        
        // Patrón 0.5: "N PALLETS" (antes del formato contenedor)
        if (preg_match('/(\d+)\s*PALLETS?/ui', $text, $matches)) {
            Log::info('Cantidad detectada (pallets)', ['raw' => $matches[0], 'cantidad' => $matches[1]]);
            return (int)$matches[1];
        }
        
        // Patrón 1 (PRIORIDAD): "cantidad: N" específicamente con la palabra "cantidad"
        if (preg_match('/cantidad\s*:?\s*(\d+)/ui', $text, $matches)) {
            Log::info('Cantidad detectada (patrón "cantidad N")', ['raw' => $matches[0], 'cantidad' => $matches[1]]);
            return (int)$matches[1];
        }
        
        // Patrón 2 (ALTA PRIORIDAD): "N cajas/bultos/sacos/estibas/paquetes" (empaques como cantidad)
        // Este debe ir ANTES de "son N" para evitar que "son 20 toneladas" se confunda con cantidad
        if (preg_match('/(\d+)\s*(?:cajas?|bultos?|sacos?|estibas?|paquetes?|bolsas?|toneles?|bidones?|canecas?|tambores?)/ui', $text, $matches)) {
            Log::info('Cantidad detectada (empaque con número)', ['raw' => $matches[0], 'cantidad' => $matches[1]]);
            return (int)$matches[1];
        }
        
        // Patrón 3: "N unidades/piezas/uds"
        if (preg_match('/(\d+)\s*(?:unidades?|uds?|piezas?)/ui', $text, $matches)) {
            return (int)$matches[1];
        }
        
        // Patrón 4: "son/hay N" (MENOR PRIORIDAD para evitar confusión con peso)
        // Solo si NO está seguido de palabras como "toneladas", "kg", etc.
        if (preg_match('/(?:son|hay)\s+(\d+)(?!\s*(?:toneladas?|kg|kilos?|t\b))/ui', $text, $matches)) {
            Log::info('Cantidad detectada (patrón "son/hay N")', ['raw' => $matches[0], 'cantidad' => $matches[1]]);
            return (int)$matches[1];
        }
        
        // Patrón 5: "cajas: N" o "paquetes: N"
        if (preg_match('/(?:cajas?|bultos?|paquetes?|unidades?)\s*:?\s*(\d+)/ui', $text, $matches)) {
            return (int)$matches[1];
        }

        return null;
    }

    /**
     * 💰 EXTRAER VALOR DECLARADO
     */
    private static function extractValorDeclarado($text)
    {
        // 🆕 PATRÓN PRIORITARIO: "$10,000,000 COP" o "$10.000.000 COP" (formato con separadores + moneda)
        // Ejemplo: "valor declarado de $10,000,000 COP" o "un valor declarado de $5,000,000 COP"
        if (preg_match('/(?:valor\s+declarado|valor)\s+(?:de\s+)?\$\s*([\d.,]+)\s*(?:cop|pesos?)/ui', $text, $matches)) {
            $valorStr = $matches[1];
            $valor = (float)str_replace(['.', ','], '', $valorStr);
            Log::info('💰 Valor en COP detectado (patrón "valor declarado de $X COP")', ['cop' => $valor, 'raw' => $valorStr]);
            return (int)$valor;
        }
        
        // 🆕 PATRÓN: "$10,000,000 COP" (sin mencionar "valor declarado")
        if (preg_match('/\$\s*([\d.,]+)\s*(?:cop|pesos?)/ui', $text, $matches)) {
            $valorStr = $matches[1];
            $valor = (float)str_replace(['.', ','], '', $valorStr);
            if ($valor >= 1000) { // Mínimo 1,000 para evitar falsos positivos
                Log::info('💰 Valor en COP detectado (patrón "$X COP")', ['cop' => $valor, 'raw' => $valorStr]);
                return (int)$valor;
            }
        }
        
        // 🆕 Patrón: "Valor mercancía: USD 72.000" (USD primero, luego número)
        // Ejemplo: "Valor mercancía: USD 72.000"
        if (preg_match('/(?:valor\s+(?:de\s+la\s+)?(?:mercancía|mercancia))\s*:\s*(?:usd|dolar(?:es)?)\s*([\d.,]+)/ui', $text, $matches)) {
            $valorStr = $matches[1];
            // Parsear formato: 72.000 = 72000
            $valorUSD = (float)str_replace(['.', ','], '', $valorStr);
            Log::info('💰 Valor en USD detectado (patrón "Valor mercancía: USD X")', ['usd' => $valorUSD, 'raw' => $valorStr]);
            return (int)$valorUSD;
        }
        
        // 🆕 Patrón: "USD 72.000" (USD primero en cualquier contexto)
        if (preg_match('/(?:usd|dolar(?:es)?)\s*([\d.,]+)/ui', $text, $matches)) {
            $valorStr = $matches[1];
            $valorUSD = (float)str_replace(['.', ','], '', $valorStr);
            if ($valorUSD > 0) {
                Log::info('💰 Valor en USD detectado (patrón "USD X")', ['usd' => $valorUSD, 'raw' => $valorStr]);
                return (int)$valorUSD;
            }
        }
        
        // 🆕 Patrón: "Valor de la mercancía: 72.000 USD" (NÚMERO + USD)
        // Ejemplo: "Valor de la mercancía: 72.000 USD"
        if (preg_match('/(?:valor\s+(?:de\s+la\s+)?(?:mercancía|mercancia))\s*:\s*([\d.,]+)\s*(?:usd|dolar(?:es)?)/ui', $text, $matches)) {
            $valorStr = $matches[1];
            // Parsear formato europeo: 72.000 = 72000, 72,000 = 72000
            $valorUSD = (float)str_replace(['.', ','], '', $valorStr);
            Log::info('💰 Valor en USD detectado (patrón "Valor de la mercancía: X USD")', ['usd' => $valorUSD, 'raw' => $valorStr]);
            return (int)$valorUSD;
        }
        
        // 🆕 Patrón: "72.000 USD" o "72,000 USD" (NÚMERO + USD en cualquier contexto)
        if (preg_match('/([\d.,]+)\s*(?:usd|dolar(?:es)?)/ui', $text, $matches)) {
            $valorStr = $matches[1];
            // Parsear formato europeo
            $valorUSD = (float)str_replace(['.', ','], '', $valorStr);
            if ($valorUSD > 0) {
                Log::info('💰 Valor en USD detectado (patrón "X USD")', ['usd' => $valorUSD, 'raw' => $valorStr]);
                return (int)$valorUSD;
            }
        }
        
        // 🆕 Patrón: "cambia el valor a X"
        if (preg_match('/(?:cambia|cambiar)(?:\s+el)?\s+valor\s+(?:a|por)\s*\$?\s*([\d.,]+)\s*(?:mill(?:ones?|ón))?/ui', $text, $matches)) {
            $valorStr = $matches[1];
            if (preg_match('/mill/ui', $text)) {
                $valor = (float)str_replace(['.', ','], ['', '.'], $valorStr);
                Log::info('💰 Valor detectado (patrón "cambia el valor a X" millones)', ['valor' => $valor * 1000000]);
                return (int)($valor * 1000000);
            }
            $valor = (float)str_replace(['.', ','], ['', ''], $valorStr);
            Log::info('💰 Valor detectado (patrón "cambia el valor a X")', ['valor' => $valor]);
            return (int)$valor;
        }
        
        // 🆕 Patrón PRIORITARIO: Corrección de valor
        // "el valor es 5000000" o "valor: 2 millones"
        if (preg_match('/(?:el\s+)?(?:valor|valor\s+declarado)\s*(?:es|será|sea|queda|:)\s*\$?\s*([\d.,]+)\s*(?:mill(?:ones?|ón))?/ui', $text, $matches)) {
            $valorStr = $matches[1];
            
            // Detectar si menciona millones
            if (preg_match('/mill/ui', $text)) {
                $valor = (float)str_replace(['.', ','], ['', '.'], $valorStr);
                Log::info('💰 Valor detectado (patrón CORRECCIÓN millones)', ['valor' => $valor * 1000000]);
                return (int)($valor * 1000000);
            }
            
            // Valor directo
            $valor = (float)str_replace(['.', ','], ['', ''], $valorStr);
            Log::info('💰 Valor detectado (patrón CORRECCIÓN)', ['valor' => $valor]);
            return (int)$valor;
        }
        
        // Patrón 1: "USD 77500" o "USD 77,500" - Guardar valor en USD SIN CONVERTIR
        // Capturamos cualquier secuencia de dígitos con separadores opcionales
        if (preg_match('/(?:usd|dolar(?:es)?|\$us)\s*:?\s*([\d.,]+)/ui', $text, $matches)) {
            $valorStr = $matches[1];
            
            // Detectar si tiene decimales (último grupo es de 2 dígitos después de punto/coma)
            if (preg_match('/[.,](\d{2})$/', $valorStr)) {
                // Tiene decimales: normalizar
                $valorStr = preg_replace('/[.,](?=\d{3})/u', '', $valorStr);
                $valorStr = str_replace(',', '.', $valorStr);
                $valorUSD = (float)$valorStr;
            } else {
                // No tiene decimales
                $valorUSD = (float)str_replace(['.', ','], '', $valorStr);
            }
            
            Log::info('Valor en USD detectado (guardando sin convertir)', [
                'usd_original' => $matches[1],
                'usd_parsed' => $valorUSD
            ]);
            
            // Retornar valor USD sin convertir (el frontend puede mostrarlo como USD)
            return (int)$valorUSD;
        }
        
        // Patrón 2: "valor declarado 1 millón" (COP)
        if (preg_match('/(?:valor\s+declarado|valor)\s*:?\s*\$?\s*(\d+(?:[.,]\d+)?)\s*mill(?:ones?|ón)/ui', $text, $matches)) {
            $millones = (float)str_replace(',', '.', $matches[1]);
            return (int)($millones * 1000000);
        }
        
        // Patrón 2: "1 millón" suelto
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*mill(?:ones?|ón)/ui', $text, $matches)) {
            $millones = (float)str_replace(',', '.', $matches[1]);
            return (int)($millones * 1000000);
        }
        
        // Patrón 3: "valor: $2,000,000"
        if (preg_match('/(?:valor|declaro?)\s*:?\s*\$?\s*(\d{1,3}(?:[.,]\d{3})+)/ui', $text, $matches)) {
            $valor = str_replace(['.', ','], '', $matches[1]);
            return (int)$valor;
        }
        
        // Patrón 4: "$2,000,000 COP"
        if (preg_match('/\$?\s*(\d{1,3}(?:[.,]\d{3})+)\s*(?:cop|pesos?)/ui', $text, $matches)) {
            $valor = str_replace(['.', ','], '', $matches[1]);
            if (strlen($valor) >= 6) {
                return (int)$valor;
            }
        }

        return null;
    }

    /**
     * EXTRAER VOLUMEN (metros cubicos)
     */
    private static function extractVolumen($text)
    {
        // Formato "50,000CB" o "50.000 CB" o "50000 m³"
        if (preg_match('/(\d{1,3}(?:[.,]\d{3})*)\s*(?:cb|m3|m³|metros?\s*c[uú]bicos?)/ui', $text, $matches)) {
            $volumen = str_replace(['.', ','], '', $matches[1]);
            Log::info('Volumen detectado', ['raw' => $matches[0], 'parsed' => $volumen]);
            return (float)$volumen;
        }
        
        return null;
    }

    /**
     *  EXTRAER TIPO DE CONTENEDOR
     */
    private static function extractTipoContenedor($text)
    {
        // 🆕 Patrón: "Contenedor: 1 x 40 HQ" (formato formal con label)
        if (preg_match('/(?:Contenedor|Container)\s*:\s*(\d+)\s*[xX]\s*(\d+)\s*(HQ|HC|GP|RF|OT|FR|STD|STANDARD)?/ui', $text, $matches)) {
            $cantidad = $matches[1];
            $tamaño = $matches[2];
            $tipo = isset($matches[3]) && !empty($matches[3]) ? strtoupper($matches[3]) : 'GP';
            if ($tipo === 'HQ' || $tipo === 'STD' || $tipo === 'STANDARD') $tipo = 'HC';
            $contenedor = $cantidad . "X" . $tamaño . "' " . $tipo;
            Log::info('📦 Tipo de contenedor detectado (formato "Contenedor: NxTAMAÑO")', ['raw' => $matches[0], 'parsed' => $contenedor]);
            return $contenedor;
        }
        
        // 🆕 Patrón PRIORITARIO: Formato "1X40 HQ" o "2X20 GP" o "1x40hc" o "1x40'HC" (NxTAMAÑO TIPO)
        // Este formato es muy común en solicitudes de logística
        // 🔧 FIX: Agregar soporte para comilla simple (') entre tamaño y tipo: "1x40'HC"
        if (preg_match('/(\d+)\s*[Xx]\s*(\d+)\s*[\'"]?\s*(HQ|HC|GP|RF|OT|FR)?/ui', $text, $matches)) {
            $cantidad = $matches[1];
            $tamaño = $matches[2];
            $tipo = isset($matches[3]) && !empty($matches[3]) ? strtoupper($matches[3]) : 'GP';
            // Si HQ, normalizar a HC (High Cube)
            if ($tipo === 'HQ') $tipo = 'HC';
            $contenedor = $cantidad . "X" . $tamaño . "' " . $tipo;
            Log::info('Tipo de contenedor detectado (formato NxTAMAÑO)', ['raw' => $matches[0], 'parsed' => $contenedor, 'cantidad' => $cantidad]);
            return $contenedor;
        }
        
        // Formato "40hc", "40HC", "40'", "20'" (sin cantidad) - MEJORADO para evitar capturar tiempos
        // Evitar capturar cosas como "06:09 p.m." o "08:25 a.m."
        // Requiere que sea un tamaño válido de contenedor (20, 40, 45, etc.)
        if (preg_match('/\b(20|40|45)\s*[\'"]?\s*(hc|gp|rf|ot|fr)?\b/ui', $text, $matches)) {
            $tamaño = $matches[1];
            $tipo = isset($matches[2]) && !empty($matches[2]) ? strtoupper($matches[2]) : 'GP';
            $contenedor = $tamaño . "' " . $tipo;
            Log::info('Tipo de contenedor detectado (formato estándar)', ['raw' => $matches[0], 'parsed' => $contenedor]);
            return $contenedor;
        }
        
        return null;
    }

    /**
     * EXTRAER EMPAQUE - Con búsqueda fuzzy en BD
     */
    private static function extractEmpaque($lowerText)
    {
        // 🔧 FIX: Lista de palabras que son VEHÍCULOS y NO deben detectarse como empaques
        $vehiculosExcluidos = ['patineta', 'tractomula', 'turbo', 'sencillo', 'dobletroque', 'camioneta', 
                               'minimula', 'tractocamión', 'tractocamion', 'camion', 'camión', 'trailer',
                               'furgon', 'furgón', 'niñera', 'ninera'];
        
        // 🔧 FIX: Si el texto SOLO menciona un vehículo (sin contexto de empaque), NO detectar empaque
        // Ejemplo: "Mosquera - Itagui patineta" → patineta es vehículo, no empaque
        $textoSinVehiculos = $lowerText;
        foreach ($vehiculosExcluidos as $vehiculo) {
            $textoSinVehiculos = preg_replace('/\b' . preg_quote($vehiculo, '/') . '\b/ui', '', $textoSinVehiculos);
        }
        $textoSinVehiculos = trim($textoSinVehiculos);
        
        // 🆕 Patrón PRIORITARIO: Corrección de empaque
        // "el empaque es cajas" o "empaque: bultos" o "cambia el empaque a sacos" o "empaque son cajas"
        $empaqueKeyword = null;
        $cantidadContenedor = null; // 🆕 Para guardar cantidad cuando viene en formato NxTAMAÑO
        
        // 🔧 FIX MEJORADO: Detectar formato "1x40'HC", "2x20GP", "6x20", "8x40", "1x20 OT" PRIMERO
        // Patrón: "1x40'HC", "1X40HC", "2x20'GP", "6x20", "1x20 ot", etc.
        // El tipo (HC, GP, OT, etc.) es OPCIONAL
        if (preg_match('/(\d+)\s*[Xx]\s*(20|40|45)\s*[\'"]?\s*(HQ|HC|GP|RF|OT|FR)?/ui', $lowerText, $formatoMatch)) {
            $cantidadContenedor = intval($formatoMatch[1]); // 🆕 Extraer cantidad
            $tamaño = $formatoMatch[2];
            $tipo = isset($formatoMatch[3]) && !empty($formatoMatch[3]) ? strtoupper($formatoMatch[3]) : '';
            if ($tipo === 'HQ') $tipo = 'HC';
            
            // Determinar el empaque según el tamaño
            if ($tamaño == '20') {
                // Si tiene tipo específico (OT, HC, RF, etc.), incluirlo en el nombre
                $empaqueKeyword = $tipo ? 'contenedor 20 ' . strtolower($tipo) : 'contenedor de 20';
            } elseif ($tamaño == '40' || $tamaño == '45') {
                $empaqueKeyword = $tipo ? 'contenedor 40 ' . strtolower($tipo) : 'contenedor de 40';
            }
            
            Log::info('📦 Empaque detectado (formato NxTAMAÑO)', [
                'raw' => $formatoMatch[0], 
                'keyword' => $empaqueKeyword, 
                'cantidad' => $cantidadContenedor,
                'tipo' => $tipo ?: 'estándar'
            ]);
        }
        // 🔧 FIX: Detectar contenedor con tamaño (contenedor de 20/40 pies)
        // Patrón: "contenedor de 20 pies", "1 contenedor de 40 pies", "contenedor 20'"
        elseif (preg_match('/(\d+)?\s*contenedor(?:es)?\s+(?:de\s+)?(\d+)\s*(?:pies|\'|")?/ui', $lowerText, $containerMatch)) {
            if (!empty($containerMatch[1])) {
                $cantidadContenedor = intval($containerMatch[1]); // Cantidad antes de "contenedor"
            }
            $tamaño = $containerMatch[2];
            if ($tamaño == '20') {
                $empaqueKeyword = 'contenedor de 20';
                Log::info('📦 Empaque detectado (contenedor de 20 pies)', ['keyword' => $empaqueKeyword, 'cantidad' => $cantidadContenedor]);
            } elseif ($tamaño == '40') {
                $empaqueKeyword = 'contenedor de 40';
                Log::info('📦 Empaque detectado (contenedor de 40 pies)', ['keyword' => $empaqueKeyword, 'cantidad' => $cantidadContenedor]);
            }
        }
        // También detectar "20 pies" o "40 pies" sin "contenedor" explícito
        elseif (preg_match('/(\d+)\s*pies/ui', $lowerText, $piesMatch)) {
            $tamaño = $piesMatch[1];
            if ($tamaño == '20' || $tamaño == '40') {
                $empaqueKeyword = $tamaño . ' pies';
                Log::info('📦 Empaque detectado (N pies)', ['keyword' => $empaqueKeyword]);
            }
        }
        
        // 🆕 NUEVO: Detectar "empacadas/empacados en X" o "embaladas en X" PRIMERO
        // "60 unidades empacadas en cajas" → cajas
        if (!$empaqueKeyword && preg_match('/(?:empacad[ao]s?|embalad[ao]s?|envuelto?s?)\s+(?:en|con)\s+([a-záéíóúñ]+)/ui', $lowerText, $matches)) {
            $empaqueKeyword = strtolower(trim($matches[1]));
            Log::info('📦 Empaque detectado (patrón "empacadas en X")', ['keyword' => $empaqueKeyword]);
        }
        // Patrón: "cambia el empaque a X" o "empaque cambialo a X"
        // 🔧 FIX BUG #530-2: Agregar soporte para plural (empaques/embalajes) y artículos (el/los)
        elseif (!$empaqueKeyword && (preg_match('/(?:cambia|cambiar)(?:\s+(?:el|los))?\s+(?:empaque|embalaje)s?\s+(?:a|por)\s+([a-záéíóúñ]+)/ui', $lowerText, $matches) ||
            preg_match('/(?:empaque|embalaje)s?\s+cambialo\s+(?:a|por)\s+([a-záéíóúñ]+)/ui', $lowerText, $matches))) {
            $empaqueKeyword = strtolower(trim($matches[1]));
            Log::info('📦 Empaque detectado (patrón "cambia empaque a X")', ['keyword' => $empaqueKeyword]);
        }
        // Patrón: "empaque es/son X" o "empaque: X"
        // 🔧 FIX BUG #530-2: Agregar soporte para plural (empaques/embalajes) y artículos (el/los)
        elseif (!$empaqueKeyword && preg_match('/(?:(?:el|los)\s+)?(?:empaque|embalaje)s?\s*(?:es|son|será|sea|queda|:)\s*([a-záéíóúñ\s]+?)(?:\s*[.,;]|\s+y\s+|$)/ui', $lowerText, $matches)) {
            $posibleEmpaque = strtolower(trim($matches[1]));
            // 🔧 FIX: Verificar que NO sea un vehículo
            if (!in_array($posibleEmpaque, $vehiculosExcluidos)) {
                $empaqueKeyword = $posibleEmpaque;
                Log::info('📦 Empaque detectado (patrón CORRECCIÓN)', ['keyword' => $empaqueKeyword]);
            } else {
                Log::info('🚛 Palabra ignorada como empaque (es vehículo)', ['palabra' => $posibleEmpaque]);
            }
        }
        
        // 🔧 FIX: Si el empaqueKeyword detectado es un vehículo, ignorarlo
        if ($empaqueKeyword && in_array($empaqueKeyword, $vehiculosExcluidos)) {
            Log::info('🚛 empaqueKeyword ignorado (es vehículo)', ['keyword' => $empaqueKeyword]);
            $empaqueKeyword = null;
        }
        
        // Mapeo de keywords a nombres de empaques en la BD
        $empaques = [
            // Básicos
            'caja' => 'CAJAS',
            'cajas' => 'CAJAS',
            'paquete' => 'PAQUETES',
            'paquetes' => 'PAQUETES',
            'bulto' => 'BULTOS',
            'bultos' => 'BULTOS',
            'bolsa' => 'BOLSAS',
            'bolsas' => 'BOLSAS',
            'saco' => 'BULTOS', // Sacos = bultos
            'sacos' => 'BULTOS',
            // Estibas/Pallets
            'estiba' => 'ESTIBA',
            'estibas' => 'ESTIBA',
            'estibada' => 'CARGA ESTIBADA',
            'pallet' => 'ESTIBA',
            'pallets' => 'ESTIBA',
            'paletizado' => 'CARGA ESTIBADA',
            // Granel
            'granel' => 'GRANEL SOLIDO',
            'granel solido' => 'GRANEL SOLIDO',
            'granel sólido' => 'GRANEL SOLIDO',
            'granel liquido' => 'GRANEL LIQUIDO',
            'granel líquido' => 'GRANEL LIQUIDO',
            'líquido' => 'GRANEL LIQUIDO',
            'liquido' => 'GRANEL LIQUIDO',
            // Contenedores - 🔧 FIX: Agregar variaciones con "de" - Formato corto para UI
            'contenedor de 20' => 'CONTENEDOR 20',
            'contenedor de 40' => 'CONTENEDOR 40',
            'contenedor 20' => 'CONTENEDOR 20',
            'contenedor 40' => 'CONTENEDOR 40',
            '20 pies' => 'CONTENEDOR 20',
            '40 pies' => 'CONTENEDOR 40',
            'contenedor' => 'CONTENEDOR',  // Default sin tamaño (el usuario deberá especificar)
            // 🆕 Contenedores con tipo específico (OT, HC, RF, etc.)
            'contenedor 20 ot' => 'CONTENEDOR 20 OT',
            'contenedor 20 hc' => 'CONTENEDOR 20 HC',
            'contenedor 20 rf' => 'CONTENEDOR 20 RF',
            'contenedor 20 gp' => 'CONTENEDOR 20',
            'contenedor 40 ot' => 'CONTENEDOR 40 OT',
            'contenedor 40 hc' => 'CONTENEDOR 40 HC',
            'contenedor 40 rf' => 'CONTENEDOR 40 RF',
            'contenedor 40 gp' => 'CONTENEDOR 40',
            // Otros
            'tonel' => 'TONEL',
            'toneles' => 'TONEL',
            'rollo' => 'ROLLOS',
            'rollos' => 'ROLLOS',
            'cilindro' => 'CILINDROS',
            'cilindros' => 'CILINDROS',
            'guacal' => 'GUACALES',
            'guacales' => 'GUACALES',
            'varios' => 'VARIOS',
        ];

        // Buscar keywords en el texto (priorizar coincidencias más largas)
        // Si hay corrección explícita, buscar solo en esa keyword
        $searchText = $empaqueKeyword ?? $lowerText;
        $found = null;
        $foundLength = 0;
        
        // 🆕 FILTRAR: Si el texto menciona "contenedor" en contexto de VEHÍCULO, no usar como empaque
        // "capacidad de transportar contenedor", "vehículo tipo contenedor"
        $esContenedorDeVehiculo = preg_match('/(?:transportar|capacidad|veh[ií]culo.*?(?:tipo|para)).*contenedor/ui', $lowerText) ||
                                  preg_match('/contenedor.*(?:sin.*devoluci[oó]n|retorno)/ui', $lowerText);
        
        // 🆕 FILTRAR: Si "varios" está en contexto de PRODUCTO, NO usar como empaque
        // "productos varios enlatados", "productos varios"
        $esVariosDeProducto = preg_match('/producto[s]?\s+varios/ui', $lowerText);
        
        // 🔧 FIX: Si el searchText es un vehículo, no buscar empaque
        if (in_array(trim($searchText), $vehiculosExcluidos)) {
            Log::info('🚛 searchText es un vehículo, no buscar empaque', ['searchText' => $searchText]);
            return null;
        }
        
        foreach ($empaques as $keyword => $empaqueType) {
            // 🆕 Si es contenedor de vehículo, NO usarlo como empaque
            if ($esContenedorDeVehiculo && strpos($keyword, 'contenedor') !== false) {
                continue;
            }
            
            // 🆕 Si "varios" está como parte de un producto, NO usarlo como empaque
            if ($esVariosDeProducto && $keyword === 'varios') {
                Log::info('📦 Empaque "VARIOS" ignorado (es parte del producto)', ['texto' => substr($lowerText, 0, 100)]);
                continue;
            }
            
            // 🔧 FIX: No detectar vehículos como empaques
            if (in_array($keyword, $vehiculosExcluidos)) {
                continue;
            }
            
            if (strpos($searchText, $keyword) !== false) {
                // Priorizar keywords más largos (más específicos)
                if (strlen($keyword) > $foundLength) {
                    $found = $empaqueType;
                    $foundLength = strlen($keyword);
                }
            }
        }
        
        if ($found) {
            // 🔧 FIX: Para contenedores con tamaño, retornar el formato corto directamente
            // sin buscar en BD (para mostrar "CONTENEDOR 20" en lugar de "CONTENEDOR (1) 20 PIES")
            // Incluye soporte para tipos: OT, HC, RF, GP
            if (preg_match('/^CONTENEDOR\s*(\d+)\s*(OT|HC|RF|GP)?$/i', $found, $containerMatch)) {
                $tamaño = $containerMatch[1];
                $tipo = isset($containerMatch[2]) ? strtoupper($containerMatch[2]) : '';
                $empaqueCorto = $tipo ? 'CONTENEDOR ' . $tamaño . ' ' . $tipo : 'CONTENEDOR ' . $tamaño;
                
                // Buscar ID en BD usando el nombre largo para compatibilidad
                $nombreLargoBD = ($tamaño == '20') ? 'CONTENEDOR (1) 20 PIES' : 'CONTENEDOR 40 PIES';
                $empaqueFromDB = self::getEmpaqueFromDB($nombreLargoBD);
                
                Log::info('📦 Empaque contenedor detectado - formato corto', [
                    'keyword_encontrado' => $found,
                    'empaque_mostrar' => $empaqueCorto,
                    'empaque_id' => $empaqueFromDB ? $empaqueFromDB['id'] : null,
                    'tipo_contenedor' => $tipo ?: 'estándar',
                    'cantidad_contenedor' => $cantidadContenedor
                ]);
                
                // 🆕 Retornar cantidad si viene del formato NxTAMAÑO
                $resultado = [
                    'empaque' => $empaqueCorto,
                    'empaque_id' => $empaqueFromDB ? $empaqueFromDB['id'] : null,
                    'tamano_contenedor' => intval($tamaño), // 🔧 FIX: Propagar tamaño para cálculo de tara
                ];
                
                if ($cantidadContenedor && $cantidadContenedor > 0) {
                    $resultado['cantidad_contenedor'] = $cantidadContenedor;
                }
                
                return $resultado;
            }
            
            // Para otros empaques, buscar normalmente en BD
            $empaqueFromDB = self::getEmpaqueFromDB($found);
            if ($empaqueFromDB) {
                Log::info('📦 Empaque detectado y validado en BD', [
                    'keyword_encontrado' => $found,
                    'empaque_bd' => $empaqueFromDB['nome'],
                    'empaque_id' => $empaqueFromDB['id']
                ]);
                return [
                    'empaque' => $empaqueFromDB['nome'],
                    'empaque_id' => $empaqueFromDB['id']
                ];
            }
        }

        return null;
    }

    /**
     * 📋 EXTRAER TIPO DE MERCANCÍA
     */
    private static function extractTipoMercancia($lowerText)
    {
        if (strpos($lowerText, 'carga general') !== false) {
            return 'CARGA GENERAL';
        } elseif (strpos($lowerText, 'refrigerada') !== false || strpos($lowerText, 'refrigerado') !== false) {
            return 'REFRIGERADA';
        } elseif (strpos($lowerText, 'peligrosa') !== false || strpos($lowerText, 'peligroso') !== false) {
            return 'PELIGROSA';
        } elseif (strpos($lowerText, 'granel') !== false) {
            return 'GRANEL';
        }

        return null;
    }

    /**
     * 🚛 SUGERIR VEHÍCULO SEGÚN PESO
     */
    private static function suggestVehicleByWeight($pesoKg)
    {
        if ($pesoKg <= 1500) {
            return 'CAMIONETA';
        } elseif ($pesoKg <= 3500) {
            return 'SENCILLO';
        } elseif ($pesoKg <= 10000) {
            return 'TURBO';
        } elseif ($pesoKg <= 17000) {
            return 'DOBLETROQUE';
        } elseif ($pesoKg <= 28000) {
            return 'MINIMULA';
        } else {
            return 'TRACTOMULA';
        }
    }

    /**
     * 🚛 NORMALIZAR TIPO DE VEHÍCULO
     * "camión sencillo" → "SENCILLO", "camión turbo" → "TURBO"
     */
    private static function normalizeVehiculo($vehiculo)
    {
        if (empty($vehiculo)) return null;
        
        $vehiculoLower = mb_strtolower(trim($vehiculo), 'UTF-8');
        
        // Mapeo de combinaciones específicas (prioridad)
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
        
        foreach ($mapeoEspecifico as $patron => $resultado) {
            if (strpos($vehiculoLower, $patron) !== false) {
                return $resultado;
            }
        }
        
        // Mapeo simple
        $mapeoSimple = [
            'sencillo' => 'SENCILLO',
            'turbo' => 'TURBO',
            'patineta' => 'PATINETA',
            'camioneta' => 'CAMIONETA',
            'dobletroque' => 'DOBLETROQUE',
            'minimula' => 'MINIMULA',
            'tractomula' => 'TRACTOMULA',
            'trailer' => 'TRACTOMULA',
            'cuatro manos' => 'CUATRO MANOS',
        ];
        
        foreach ($mapeoSimple as $patron => $resultado) {
            if (strpos($vehiculoLower, $patron) !== false) {
                return $resultado;
            }
        }
        
        // Si no matcheó, quitar "camión" y devolver en mayúsculas
        $sinCamion = preg_replace('/cami[oó]n\s*/ui', '', $vehiculoLower);
        $sinCamion = trim($sinCamion);
        
        return !empty($sinCamion) ? mb_strtoupper($sinCamion, 'UTF-8') : mb_strtoupper($vehiculoLower, 'UTF-8');
    }
    
    /**
     * 🚛 EXTRAER VEHÍCULO EXPLÍCITO DEL TEXTO
     */
    private static function extractVehiculo($text)
    {
        // 🔧 FIX: Lista de vehículos válidos - ORDEN IMPORTANTE
        // Las combinaciones más específicas PRIMERO para que tengan prioridad
        $vehiculos = [
            // 🆕 Combinaciones específicas PRIMERO (tienen prioridad)
            'camión sencillo' => 'SENCILLO',
            'camion sencillo' => 'SENCILLO',
            'carro sencillo' => 'SENCILLO',
            'vehiculo sencillo' => 'SENCILLO',
            'camión turbo' => 'TURBO',
            'camion turbo' => 'TURBO',
            'carro turbo' => 'TURBO',
            'vehiculo turbo' => 'TURBO',
            'tracto camión' => 'TRACTOCAMION',
            'tracto camion' => 'TRACTOCAMION',
            'tractocamión' => 'TRACTOCAMION',
            'tractocamion' => 'TRACTOCAMION',
            // Vehículos simples
            'patineta' => 'PATINETA',
            'camioneta' => 'CAMIONETA',
            'sencillo' => 'SENCILLO',
            'turbo' => 'TURBO',
            'dobletroque' => 'DOBLETROQUE',
            'doble troque' => 'DOBLETROQUE',
            'minimula' => 'MINIMULA',
            'mini mula' => 'MINIMULA',
            'tractomula' => 'TRACTOMULA',
            'tracto mula' => 'TRACTOMULA',
            'trailer' => 'TRACTOMULA',
            'cuatro manos' => 'CUATRO MANOS',
            'cuatromanos' => 'CUATRO MANOS',
            // 🔧 "camión" solo al FINAL - si ningún otro matcheó, usar CAMION genérico
            'camión' => 'CAMION',
            'camion' => 'CAMION',
        ];
        
        $lowerText = mb_strtolower($text);
        
        // Patrón: "vehículo patineta" o "en patineta" o "tipo patineta"
        if (preg_match('/(?:veh[ií]culo|tipo\s+de\s+veh[ií]culo|en\s+(?:un\s+)?|tipo)\s+([a-záéíóúñ\s]+?)(?:\s+que|\s+de|\s+para|\s+con|,|\.|$)/ui', $text, $matches)) {
            $vehiculoRaw = strtolower(trim($matches[1]));
            foreach ($vehiculos as $key => $value) {
                if (strpos($vehiculoRaw, $key) !== false) {
                    Log::info('🚛 Vehículo extraído del texto', ['raw' => $vehiculoRaw, 'normalized' => $value]);
                    return $value;
                }
            }
        }
        
        // Patrón: "vehículo es/será X" (corrección)
        if (preg_match('/veh[ií]culo\s*(?:es|será|sea|:)\s*([a-záéíóúñ\s]+?)(?:\s*[.,;]|$)/ui', $text, $matches)) {
            $vehiculoRaw = strtolower(trim($matches[1]));
            foreach ($vehiculos as $key => $value) {
                if (strpos($vehiculoRaw, $key) !== false) {
                    return $value;
                }
            }
        }
        
        // Búsqueda directa en el texto
        foreach ($vehiculos as $key => $value) {
            if (strpos($lowerText, $key) !== false) {
                // Verificar que no sea parte de otra palabra
                if (preg_match('/\b' . preg_quote($key, '/') . '\b/ui', $lowerText)) {
                    Log::info('🚛 Vehículo encontrado directo', ['key' => $key, 'value' => $value]);
                    return $value;
                }
            }
        }
        
        return null;
    }

    /**
     * Extraer TODOS los datos del último mensaje del usuario
     * Versión mejorada: PRIORIZA EL ÚLTIMO MENSAJE para evitar datos cacheados
     */
    private static function extractAllDataFromMessage($messages)
    {
        $userMessages = array_filter($messages, function($msg) {
            return isset($msg['role']) && $msg['role'] === 'user';
        });

        if (empty($userMessages)) {
            Log::info('extractAllDataFromMessage: No hay mensajes de usuario');
            return [];
        }

        // 🔴 IMPORTANTE: Usar SOLO el último mensaje para datos principales
        // Esto evita mezclar datos de conversaciones anteriores
        $lastMessage = end($userMessages);
        $lastMessageText = $lastMessage['content'];
        
        // 🆕 NUEVO: Si hay indicios de múltiples rutas en el contexto, combinar los últimos mensajes
        // Esto maneja el caso donde el usuario envía "la primera... \n la segunda..."
        $combinedText = $lastMessageText;
        $userMessagesIndexed = array_values($userMessages);
        $totalMessages = count($userMessagesIndexed);
        
        $lowerLast = mb_strtolower($lastMessageText);
        
        // Detectar si necesitamos combinar mensajes
        $needsCombine = false;
        
        // Caso 1: Último mensaje menciona "segunda/tercera" pero no "primera"
        $mencionaSegundaSinPrimera = (
            (strpos($lowerLast, 'segunda') !== false || strpos($lowerLast, 'tercera') !== false) &&
            strpos($lowerLast, 'primera') === false
        );
        
        // Caso 2: Último mensaje es una ruta parcial (empieza con "la segunda", etc.)
        $pareceRutaParcial = preg_match('/^\s*(?:la\s+)?(?:segunda|tercera|cuarta|quinta)(?:\s+ruta)?/ui', $lastMessageText);
        
        // Caso 3: Mensaje anterior menciona "primera" o "dos rutas" pero no "segunda"
        $mensajeAnteriorTienePrimera = false;
        if ($totalMessages >= 2) {
            $prevMessage = mb_strtolower($userMessagesIndexed[$totalMessages - 2]['content'] ?? '');
            $mensajeAnteriorTienePrimera = (
                (strpos($prevMessage, 'primera') !== false || strpos($prevMessage, 'dos rutas') !== false) &&
                strpos($prevMessage, 'segunda') === false
            );
        }
        
        $needsCombine = $mencionaSegundaSinPrimera || $pareceRutaParcial || $mensajeAnteriorTienePrimera;
        
        if ($needsCombine && $totalMessages >= 2) {
            // Combinar los últimos 2-3 mensajes de usuario para obtener el contexto completo
            $messagesToCombine = min(3, $totalMessages);
            $combinedParts = [];
            for ($i = $totalMessages - $messagesToCombine; $i < $totalMessages; $i++) {
                if (isset($userMessagesIndexed[$i])) {
                    $combinedParts[] = $userMessagesIndexed[$i]['content'];
                }
            }
            $combinedText = implode("\n", $combinedParts);
            
            Log::info('🔗 Combinando mensajes para multi-ruta', [
                'mensajes_combinados' => $messagesToCombine,
                'texto_combinado_preview' => substr($combinedText, 0, 300)
            ]);
        }
        
        $lowerLastText = mb_strtolower($combinedText);

        Log::info('📝 extractAllDataFromMessage: Priorizando ÚLTIMO mensaje', [
            'last_message' => substr($combinedText, 0, 150) . '...',
            'message_length' => strlen($combinedText),
            'total_messages' => count($userMessages)
        ]);

        // 🚚 PASO 1: DETECTAR MÚLTIPLES RUTAS (usando texto combinado si aplica)
        $detectedRoutes = self::detectMultipleRoutes($combinedText);

        // 🔧 FIX: Si detectamos rutas explícitas (1 o más), procesarlas directamente
        // Esto incluye formatos "Origen: X, Destino: Y" que ahora detectamos
        if (!empty($detectedRoutes) && count($detectedRoutes) >= 1) {
            Log::info('🎯 PROCESANDO RUTA(S) DETECTADA(S)', ['total' => count($detectedRoutes)]);
            return self::processMultipleRoutes($detectedRoutes, $combinedText, $lowerLastText);
        }

        // 🔄 FLUJO NORMAL: No se detectaron rutas explícitas - extraer con lógica genérica
        Log::info('📦 Procesando ruta única desde ÚLTIMO mensaje (sin patrón explícito)');
        return self::extractSingleRouteData($combinedText, $lowerLastText);
    }

    private static function getSystemPrompt($typeBusiness, $detectedEmpaque = null, $extractedData = [])
    {
        // Instrucción sobre embalaje
        $empaqueInstruction = '';
        $empaqueDetectado = !empty($extractedData['empaque']) && !empty($extractedData['empaque_id']);
        
        if ($empaqueDetectado) {
            $empaqueInstruction = "\n\n✅ EMBALAJE YA DETECTADO Y VALIDADO EN BD:\n";
            $empaqueInstruction .= "- Tipo: {$extractedData['empaque']}\n";
            $empaqueInstruction .= "- ID: {$extractedData['empaque_id']}\n";
            $empaqueInstruction .= "❌ NO llames get_empaques() ni muestres opciones\n";
            $empaqueInstruction .= "✓ USA este embalaje directamente en create_cotizacion\n";
        } elseif ($detectedEmpaque) {
            $empaqueInstruction = "\n\n⚠️ El usuario mencionó '{$detectedEmpaque}' pero NO se validó en BD.\n";
            $empaqueInstruction .= "✓ Llama get_empaques() para validar y obtener ID\n";
        }

        // Construir instrucción con datos pre-extraídos
        $dataInstruction = '';
        $missingFields = []; // 🆕 Track de campos faltantes
        $multiRouteInstruction = ''; // 🆕 Instrucciones específicas para múltiples rutas
        
        if (!empty($extractedData)) {
            // 🆕 DETECTAR MÚLTIPLES RUTAS
            $isMultiRoute = isset($extractedData[0]) && is_array($extractedData[0]);
            
            if ($isMultiRoute) {
                // 🔧 FIX: Filtrar solo rutas numéricas (0, 1, 2...), ignorar campos como "producto", "tipo_producto"
                $numericRoutes = [];
                foreach ($extractedData as $key => $value) {
                    if (is_numeric($key) && is_array($value)) {
                        $numericRoutes[$key] = $value;
                    }
                }
                
                $routeCount = count($numericRoutes);
                Log::info('getSystemPrompt: MÚLTIPLES RUTAS DETECTADAS', [
                    'total_rutas' => $routeCount,
                    'rutas' => $numericRoutes
                ]);
                
                $multiRouteInstruction = "\n\n🚚 ¡MÚLTIPLES RUTAS DETECTADAS!\n";
                $multiRouteInstruction .= "Total de rutas: {$routeCount}\n\n";
                
                foreach ($numericRoutes as $index => $ruta) {
                    $routeNum = intval($index) + 1;
                    $multiRouteInstruction .= "📍 RUTA #{$routeNum}:\n";
                    foreach ($ruta as $key => $value) {
                        if ($key === 'empaque_id') continue;
                        $multiRouteInstruction .= "  • " . ucfirst(str_replace('_', ' ', $key)) . ": {$value}\n";
                    }
                    $multiRouteInstruction .= "\n";
                }
                
                $multiRouteInstruction .= "⚡ INSTRUCCIONES CRÍTICAS PARA MÚLTIPLES RUTAS:\n";
                $multiRouteInstruction .= "1. CONFIRMA al usuario las {$routeCount} rutas detectadas\n";
                $multiRouteInstruction .= "2. Si faltan datos comunes (cantidad, valor, empaque), pregunta UNA VEZ\n";
                $multiRouteInstruction .= "3. ⛔ NO llames search_products() automáticamente - espera que el usuario lo pida\n";
                $multiRouteInstruction .= "4. Solo busca productos cuando el usuario EXPLÍCITAMENTE diga 'busca producto' o mencione un NUEVO producto\n";
                $multiRouteInstruction .= "5. Cuando el usuario seleccione el producto, llama create_cotizacion() {$routeCount} VECES\n";
                $multiRouteInstruction .= "6. CADA llamada a create_cotizacion debe incluir:\n";
                $multiRouteInstruction .= "   - Datos específicos de la ruta (origen, destino, peso)\n";
                $multiRouteInstruction .= "   - Datos comunes (producto, empaque, cantidad, valor)\n";
                $multiRouteInstruction .= "   - El MISMO group_cotization_id para TODAS las rutas\n";
                $multiRouteInstruction .= "7. NO pidas confirmación, EJECUTA todas las llamadas automáticamente\n";
                $multiRouteInstruction .= "8. Rellena campos faltantes con valores por defecto razonables:\n";
                $multiRouteInstruction .= "   - cantidad: 1 (si no se especifica)\n";
                $multiRouteInstruction .= "   - valor_declarado: 1000000 por tonelada\n";
                $multiRouteInstruction .= "   - empaque: GRANEL SOLIDO (según producto)\n\n";
                
                // Para múltiples rutas, usar la primera para detectar campos faltantes
                $data = $extractedData[0];
            } else {
                // Ruta única
                $data = $extractedData;
            }
            
            $dataInstruction = "\n\n🎯 DATOS YA CAPTURADOS:\n";
            
            // 🆕 Verificar qué campos FALTAN
            $requiredFields = [
                'origen' => 'Ciudad de origen',
                'destino' => 'Ciudad de destino',
                'peso_kg' => 'Peso (kg)',
                'cantidad' => 'Cantidad de unidades',
                'valor_declarado' => 'Valor declarado',
                'empaque' => 'Tipo de embalaje',
                'producto' => 'Producto'
            ];
            
            foreach ($data as $key => $value) {
                if ($key === 'empaque_id') continue; // Ya mostrado arriba
                
                // Convertir valores a string de forma segura
                if (is_array($value)) {
                    $value = json_encode($value);
                } elseif (is_bool($value)) {
                    $value = $value ? 'sí' : 'no';
                } elseif (is_null($value)) {
                    continue; // Saltar valores null
                }
                
                $dataInstruction .= "✓ " . ucfirst(str_replace('_', ' ', $key)) . ": {$value}\n";
                
                // Marcar campo como capturado
                if (isset($requiredFields[$key])) {
                    unset($requiredFields[$key]);
                }
            }
            
            // 🆕 Listar campos FALTANTES
            if (!empty($requiredFields)) {
                $dataInstruction .= "\n❌ DATOS QUE FALTAN (pregunta SOLO por estos):\n";
                foreach ($requiredFields as $field => $label) {
                    $dataInstruction .= "• $label\n";
                    $missingFields[] = $label;
                }
                $dataInstruction .= "\n💡 Pregunta de forma CONCISA: '¿Cuántas unidades y cuál es el valor declarado?'\n";
            } else {
                $dataInstruction .= "\n✅ TODOS LOS DATOS COMPLETOS - Procede a crear cotización\n";
            }
            
            // 🆕 IMPORTANTE: Si hay producto_codigo, el producto YA FUE SELECCIONADO - NO volver a preguntar
            if (isset($data['producto_codigo']) && !empty($data['producto_codigo'])) {
                $dataInstruction .= "\n🎯 PRODUCTO YA CONFIRMADO (código {$data['producto_codigo']}): NO vuelvas a mostrar opciones de productos.\n";
                $dataInstruction .= "Si el usuario pide cambios (tara, peso, cantidad), simplemente actualiza los datos.\n";
                $dataInstruction .= "⛔ NO llames a search_products si el producto ya está seleccionado.\n";
            }
            // 🆕 NUEVO: Si hay producto_mencionado pero NO producto_codigo, mostrar como pendiente de validar
            elseif (isset($data['producto']) && !empty($data['producto']) && empty($data['producto_codigo'])) {
                $dataInstruction .= "\n📦 PRODUCTO MENCIONADO: \"{$data['producto']}\" (pendiente de validar)\n";
                $dataInstruction .= "⚠️ El usuario mencionó este producto pero NO está validado en el catálogo.\n";
                $dataInstruction .= "⛔ NO llames search_products() automáticamente.\n";
                $dataInstruction .= "✅ Muestra el producto TAL CUAL lo mencionó el usuario en el resumen.\n";
                $dataInstruction .= "✅ SOLO llama search_products() cuando el usuario EXPLÍCITAMENTE diga:\n";
                $dataInstruction .= "   - 'busca el producto' o 'validar producto'\n";
                $dataInstruction .= "   - 'producto es X' (para cambiar a otro producto)\n";
                $dataInstruction .= "   - 'quiero opciones de producto'\n";
            }
            
            if (!$isMultiRoute) {
                $dataInstruction .= "\n⚡ INSTRUCCIONES CRÍTICAS:\n";
                $dataInstruction .= "1. CONFIRMA los datos capturados al usuario\n";
                $dataInstruction .= "2. PREGUNTA SOLO por lo que FALTA (máximo 1 pregunta)\n";
                $dataInstruction .= "3. SI el usuario responde con dato adicional ('cantidad 60'), ACTUALÍZALO\n";
                $dataInstruction .= "4. ⛔ NO llames search_products() automáticamente\n";
                $dataInstruction .= "5. Solo llama search_products() cuando el usuario EXPLÍCITAMENTE pida buscar o cambiar producto\n\n";
            }
        }

        $basePrompt = <<<EOT
🤖 AGENTE INTELIGENTE CONALCA - SISTEMA EN PRODUCCIÓN
Eres un agente autónomo especializado en logística de transporte de carga en Colombia.
ESTÁS CONECTADO A BASE DE DATOS REAL - PUEDES CREAR COTIZACIONES REALES.
{$empaqueInstruction}{$multiRouteInstruction}{$dataInstruction}

🚨 REGLAS CRÍTICAS - NUNCA VIOLAR:
1. ⛔ NUNCA digas "no puedo generar cotizaciones" - SÍ PUEDES, estás en producción
2. ⛔ NUNCA des listas largas de pasos o recomendaciones genéricas
3. ⛔ NUNCA menciones "en un contexto real" - ESTÁS en contexto real
4. ⛔ NUNCA sugieras "contactar proveedores" - TÚ eres el sistema de cotización
5. ✅ Respuestas CORTAS: máximo 3-4 líneas
6. ✅ Si hay error, di SOLO: "Dato faltante: [campo]. Por favor indícalo."
7. ✅ Confirma datos recibidos de forma BREVE

🧠 CAPACIDADES - ESTÁS CONECTADO A:
- Base de datos de productos reales
- Sistema de cotización real
- Herramientas: search_products, create_cotizacion, get_empaques

⚡ COMPORTAMIENTO:
- Actúa de forma PROACTIVA
- MINIMIZA preguntas: máximo 1 por interacción
- Si detectas MÚLTIPLES RUTAS, procésalas TODAS

🚚 MANEJO DE MÚLTIPLES RUTAS:
Si el usuario solicita VARIAS rutas en un solo mensaje (ej: "primera ruta de X a Y, segunda ruta de Z a W"):
1. DETECTA automáticamente todas las rutas mencionadas
2. EXTRAE datos comunes (producto, empaque, cantidad por ruta)
3. ⛔ NO llames search_products() automáticamente - ESPERA que el usuario lo pida
4. CONFIRMA las rutas detectadas al usuario
5. Cuando el usuario seleccione el producto, llama create_cotizacion() N VECES (una por ruta)
6. TODAS las rutas comparten el MISMO group_cotization_id

EJEMPLO MÚLTIPLES RUTAS:
Usuario: "quiero 2 rutas, primera FUNZA a Cali 2 ton maíz, segunda Medellín a Cali 2 ton maíz"
Tú respondes: "Perfecto, registré 2 rutas:
  1. FUNZA → Cali (2 ton) - Producto: MAÍZ
  2. Medellín → Cali (2 ton) - Producto: MAÍZ
  
¿Los datos son correctos? Cuando confirmes, puedo proceder a validar el producto en el catálogo."

⛔ ESPERA que el usuario confirme o pida explícitamente buscar producto.

📦 MANEJO DE PRODUCTOS:
1. Cuando el usuario menciona un producto (ej: "Aislador GY", "Maíz"), guárdalo TAL CUAL
2. ⛔ NO llames search_products() automáticamente al recibir el mensaje inicial
3. Muestra el producto COMO LO ESCRIBIÓ el usuario en el resumen
4. Solo llama search_products() cuando el usuario EXPLÍCITAMENTE diga:
   - "busca el producto" / "valida el producto"
   - "producto es X" (para cambiar a otro)
   - "quiero opciones de producto"
5. Si el usuario no pide buscar, simplemente muestra el resumen con el producto mencionado

📊 COMPLETADO INTELIGENTE DE DATOS:
Cuando el usuario proporciona información PARCIAL o ADICIONAL:
1. DETECTA el contexto de la conversación (mantén estado)
2. ACTUALIZA solo los campos proporcionados
3. SOLICITA amablemente SOLO lo que FALTA (ejemplo):
   ❌ Mal: "¿Cuál es el origen, destino, peso, cantidad, valor...?" (demasiado largo)
   ✅ Bien: "Perfecto. Solo necesito la cantidad de unidades para completar tu cotización."
4. NUNCA vuelvas a pedir lo que YA tienes
5. Si el usuario dice "cantidad 60", actualiza cantidad = 60
6. Si el usuario dice "valor 10 millones", actualiza valor_declarado = 10000000

💰 RECONOCIMIENTO DE VALOR DECLARADO (CRÍTICO):
El usuario puede dar el valor en MÚLTIPLES FORMATOS. DEBES reconocerlos TODOS:
✅ "$10,000,000 COP" → valor_declarado = 10000000
✅ "$10.000.000" → valor_declarado = 10000000
✅ "10 millones de pesos" → valor_declarado = 10000000
✅ "valor declarado de $5,000,000 COP" → valor_declarado = 5000000
✅ "un valor declarado de $10,000,000 COP" → valor_declarado = 10000000
✅ "5 millones COP" → valor_declarado = 5000000
✅ "valor de 10000000" → valor_declarado = 10000000

⚠️ IMPORTANTE: 
- El formato "\$X,XXX,XXX COP" o "\$X.XXX.XXX" es muy común en Colombia
- Las comas (,) y puntos (.) se usan como separadores de miles
- "COP" significa pesos colombianos
- "millones" = multiplicar por 1,000,000

EJEMPLOS DE EXTRACCIÓN:
Usuario: "con un valor declarado de $10,000,000 COP"
Tú extraes: valor_declarado = 10000000

Usuario: "valor de 5.5 millones"
Tú extraes: valor_declarado = 5500000

Usuario: "declarado $3.200.000"
Tú extraes: valor_declarado = 3200000

📦 RECONOCIMIENTO DE PRODUCTOS (CRÍTICO):
El usuario puede mencionar productos dentro de descripciones. DEBES extraer SOLO el producto:
✅ "10 cajas de productos electrónicos (televisores)" → producto = TELEVISORES
✅ "televisores embalados en cajas de cartón" → producto = TELEVISORES
✅ "30 sacos de café" → producto = CAFÉ
✅ "arroz en bultos" → producto = ARROZ
❌ "cajas de cartón" → producto NO debe ser CARTON (el cartón es el embalaje)
❌ "embalaje en madera" → producto NO debe ser MADERA (madera es el embalaje)

⚠️ REGLA: Si el texto dice "X cajas/sacos/bultos de PRODUCTO", el producto es PRODUCTO, NO el empaque.
⚠️ REGLA: Si el texto dice "productos (NOMBRE)", el producto es NOMBRE.
⚠️ REGLA: Filtrar palabras de embalaje: cartón, madera, plástico, papel, cajas, sacos, bultos.

EJEMPLOS DE EXTRACCIÓN:
Usuario: "10 cajas de productos electrónicos (televisores), embalaje en cajas de cartón"
Tú extraes: producto = TELEVISORES, empaque = CAJAS

Usuario: "mercancía variada en sacos"
Tú extraes: producto = MERCANCIA VARIADA, empaque = SACOS

Usuario: "café colombiano en bultos de 60 kg"
Tú extraes: producto = CAFÉ, empaque = BULTOS

� AJUSTES Y CORRECCIONES (CRÍTICO):
🚨 DETECTAR SI ES EDICIÓN vs CREACIÓN 🚨

**CUANDO EL USUARIO ESTÁ EDITANDO CAMPOS:**
Si el usuario dice:
- "cambia el destino a Cali"
- "origen es Barranquilla"  
- "peso 5000 kg"
- "producto neumáticos"
- "embalaje contenedor, cantidad 5645, vehículo tractomula"
- "valor 10 millones"

**LO QUE DEBES HACER:**
1. ✅ ACTUALIZAR SOLO el/los campo(s) mencionado(s)
2. ✅ PRESERVAR todos los demás campos sin cambios
3. ✅ CONFIRMAR el cambio: "✅ Actualizado: Embalaje → CONTENEDOR, Cantidad → 5645, Vehículo → TRACTOMULA"
4. ✅ MOSTRAR preview actualizado de la ruta
5. ❌ NO llamar a create_cotizacion ni create_quote
6. ❌ NO buscar productos automáticamente
7. ❌ NO preguntar por otros campos
8. ❌ NO devolver "null", "N/A" o valores vacíos para campos no mencionados

**REGLA CRÍTICA AL EDITAR:**
Cuando el usuario edita campos de una ruta:
- ✅ SI menciona "cantidad 5645" → Actualizar SOLO cantidad
- ✅ SI menciona "embalaje contenedor" → Actualizar SOLO embalaje
- ✅ SI menciona "vehículo tractomula" → Actualizar SOLO vehículo
- ✅ SI menciona "producto X" → Actualizar SOLO producto
- ❌ NO devolver origen, destino, producto, peso u otros campos como null
- ❌ NO extraer TODOS los campos de nuevo
- ❌ **NO cambiar el producto si el usuario NO menciona "producto"**
- ❌ **NO extraer producto de palabras como "sacos", "contenedor" si son embalajes**
- ✅ Los campos NO mencionados deben MANTENERSE EXACTAMENTE IGUAL

**🔥 REGLA CRÍTICA - PRODUCTOS PERSONALIZADOS:**
Si una ruta tiene un producto como "PRODUCTOS PERSONALIZADOS DE LA ANDA" u otro producto no estándar:
- ❌ **NUNCA lo cambies** si el usuario está editando otros campos
- ❌ **NO lo reemplaces** por productos de la lista estándar
- ✅ **SOLO cámbialo** si el usuario dice: "producto X" o "cambia el producto a Y"

Formato de respuesta al editar (SOLO campos modificados):
{
  "cantidad": 5645,
  "empaque": "CONTENEDOR (1) 20 PIES",
  "vehiculo": "TRACTOMULA"
}
NO incluir campos no editados en la respuesta (incluyendo producto si no fue mencionado).

**EJEMPLO CRÍTICO - NO TOCAR PRODUCTO:**
Usuario: "cantidad 5000, embalaje contenedor"
Producto actual: "PRODUCTOS PERSONALIZADOS DE LA ANDA"
✅ Respuesta: {"cantidad": 5000, "empaque": "CONTENEDOR (1) 20 PIES"}
❌ NO devolver: {"producto": "CONTENEDOR"} o cambiar el producto existente

**SOLO CREAR COTIZACIONES CUANDO:**
- ✅ Usuario EXPLÍCITAMENTE dice: "crea las cotizaciones", "genera las cotizaciones", "procede"
- ✅ Usuario confirma después de mostrar el resumen completo

Ejemplo de EDICIÓN:
Usuario: "embalaje contenedor, cantidad 5645, vehículo tractomula"
Tú: "✅ Ruta 3 actualizada:
• Embalaje: CONTENEDOR (1) 20 PIES
• Cantidad: 5,645
• Vehículo: TRACTOMULA

¿Deseas hacer más cambios o crear las cotizaciones?"
[FIN - NO crear cotizaciones]

⚠️ Reglas de ajustes:
- "agrega la tara" → DÉJALO ESTAR, el sistema lo sumará automáticamente. Solo confirma.
- "cambia el producto" → SOLO si menciona un NUEVO producto
- "modifica el peso" → Actualiza el peso con el nuevo valor
- ⚠️ NO vuelvas a preguntar por productos si ya hay producto_codigo seleccionado
- ⚠️ NO llames a search_products para ajustes que no son de producto
- ⚠️ NO llames a search_products cuando usuario edita origen, destino, peso, cantidad, valor
- ⚠️ SOLO llama search_products cuando el usuario EXPLÍCITAMENTE diga "busca producto" o mencione un NUEVO producto

📦 TARA DE CONTENEDORES (peso del empaque):
- El sistema se encarga de la matemática de la tara (3400kg).
- Tú solo extrae la INTENCIÓN del usuario: "quiere incluir tara".
- Si el usuario dice "peso 20 toneladas con tara", extrae "peso: 20000", "incluye_tara: true".
- Si el usuario dice "agrega tara", el sistema lo hará.

�� VALORES POR DEFECTO - NUEVA REGLA (CRÍTICA):
🚨 NUNCA INVENTAR DATOS QUE EL USUARIO NO PROPORCIONÓ 🚨

❌ NO asumir valores por defecto para:
- Embalaje: Si no se menciona, mostrar "-" y PREGUNTAR
- Vehículo: Si no se especifica, mostrar "-" (solo sugerir según peso, pero confirmar)
- Cantidad: Si no se especifica, mostrar "-" y PREGUNTAR (NO asumir 1)
- Valor declarado: Si no se menciona, mostrar "-" y PREGUNTAR
- Producto: Si no se detecta claramente, mostrar "-" y PREGUNTAR

✅ CORRECTO - mostrar preview con "-" para campos vacíos:
"📦 Datos capturados:
• Origen: MEDELLÍN → Destino: BOGOTÁ
• Peso: 15,000 kg (15 ton)
• Producto: CAFÉ
• Embalaje: - (no especificado)
• Vehículo: - (no especificado)
• Cantidad: - (no especificado)

Para continuar necesito:
¿Qué tipo de embalaje? (SACOS, CAJAS, GRANEL, etc.)"

❌ INCORRECTO - NO hacer esto:
"Embalaje: VARIOS (asumido)"
"Cantidad: 1 (por defecto)"
"Valor: 1,000,000 (estimado)"

REGLA: Si el campo está vacío, mostrarlo como "-" y PREGUNTAR antes de crear cotización.

�🚛 SUGERENCIA AUTOMÁTICA DE VEHÍCULO:

⚠️ REGLA PRIORITARIA DE CONTENEDORES:
- Si se mencionan CONTENEDORES (de 20, de 40, 20', 40', 1x20, 2x40, etc.) → vehículo es SIEMPRE "TRACTOCAMION"
- NO aplicar la tabla de peso cuando hay contenedores
- Contenedores SOLO se transportan en tractocamión, NUNCA en turbo, sencillo, camioneta, etc.

Si NO hay contenedores, basado en el peso detectado sugiere:
- Hasta 1.5 ton: CAMIONETA
- 1.5-3.5 ton: SENCILLO
- 3.5-10 ton: TURBO
- 10-17 ton: DOBLETROQUE
- 17-25 ton: TRACTOCAMION
- Más de 25 ton: MINIMULA

⚠️ IMPORTANTE: Solo SUGERIR el vehículo según peso (cuando NO hay contenedores), pero SIEMPRE confirmar con el usuario.
Si el usuario no especifica vehículo y NO hay contenedores, mostrar la sugerencia: "Sugiero TURBO según el peso, ¿está bien?"
Si hay contenedores, mostrar directamente: "Vehículo: TRACTOCAMION" sin preguntar.

FORMATO DE VALORES MONETARIOS:
- SIEMPRE usa "USD" para valores en dólares, NUNCA uses "$"
- Ejemplo: "USD 77,500" NO "$77,500"
- Para valores en pesos colombianos usa "$" o "COP"

🚨 SISTEMA EN PRODUCCIÓN - RESPUESTAS CONCISAS:
- MÁXIMO 4 líneas por respuesta
- NO des explicaciones largas
- NO listes pasos genéricos
- NO digas "en contexto real" o "no puedo generar cotizaciones"
- Si falta un dato, pregunta SOLO por ese dato
- CONFIRMA brevemente y ACTÚA

RESPUESTA MODELO (cuando tienes todos los datos):
"✅ Registrado:
• Ruta: Medellín → Cali | Peso: 2,000 kg
• Producto: MAÍZ | Embalaje: GRANEL
¿Confirmo cotización?"

⛔ NUNCA llames search_products() sin que el usuario lo pida.

RESPUESTA MODELO (cuando falta algo):
"Capturado: Medellín → Cali, 2 ton, MAÍZ.
Falta: cantidad y valor declarado. ¿Cuántas unidades y valor?"

⛔ NO llames search_products automáticamente.

🎯 DATOS FALTANTES - RESPUESTA BREVE:
- Si falta dato, pregunta en 1 línea
- NO hagas listas largas de pasos
- NO expliques el proceso

⚠️ HERRAMIENTAS:
- search_products: SOLO cuando usuario lo pida explícitamente
- create_cotizacion: crear orden después de confirmación
- get_empaques: SOLO si NO mencionó embalaje

📋 FORMATO DE RESPUESTA:
- MÁXIMO 4 líneas
- Confirma datos brevemente
- Pregunta 1 cosa a la vez
- NO hagas listas largas de pasos

🚨 PROHIBIDO EN PRODUCCIÓN:
- NO digas "no puedo generar cotizaciones"
- NO digas "en un contexto real"
- NO sugieras "contactar proveedores"
- NO hagas listas de recomendaciones genéricas
- NO expliques procesos internos
- SÍ estás conectado a BD real
- SÍ puedes crear cotizaciones reales
EOT;

        return $basePrompt;
    }
    
    /**
     * Eliminar acentos de una cadena
     * @param string $str
     * @return string
     */
    private static function removeAccents($str)
    {
        $replacements = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'ñ' => 'n', 'Ñ' => 'N'
        ];
        return strtr($str, $replacements);
    }
}
 
