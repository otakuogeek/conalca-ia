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
        elseif ($esEdicionSimpleHabilitada && preg_match('/(?:el\s+)?origen\s*(?:' . $palabrasAgregar . ')\s+([a-záéíóúñ\s]+?)(?:\s*[.,;]|$)/ui', $lastUserMessageForEdit, $matchOrigen)) {
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'origen';
            $valorEditadoTemprano = self::normalizeCityName(trim($matchOrigen[1]));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:cambia|modifica|ajusta|actualiza)\s+(?:el\s+)?origen\s*(?:a|por)?\s+([a-záéíóúñ\s]+?)(?:\s*[.,;]|$)/ui', $lastUserMessageForEdit, $matchOrigen)) {
            // 🆕 NUEVO: "cambia origen a cali" o "modifica el origen a medellín"
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'origen';
            $valorEditadoTemprano = self::normalizeCityName(trim($matchOrigen[1]));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:el\s+)?destino\s*(?:' . $palabrasAgregar . ')\s+([a-záéíóúñ\s]+?)(?:\s*[.,;]|$)/ui', $lastUserMessageForEdit, $matchDestino)) {
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'destino';
            $valorEditadoTemprano = self::normalizeCityName(trim($matchDestino[1]));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:cambia|modifica|ajusta|actualiza)\s+(?:el\s+)?destino\s*(?:a|por)?\s+([a-záéíóúñ\s]+?)(?:\s*[.,;]|$)/ui', $lastUserMessageForEdit, $matchDestino)) {
            // 🆕 NUEVO: "cambia destino a cali" o "modifica el destino a medellín"
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'destino';
            $valorEditadoTemprano = self::normalizeCityName(trim($matchDestino[1]));
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:el\s+)?veh[ií]culo\s*(?:' . $palabrasAgregar . ')\s+([a-záéíóúñ\s]+?)(?:\s*[.,;]|$)/ui', $lastUserMessageForEdit, $matchVehiculo)) {
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'vehiculo';
            $vehiculosMap = [
                'patineta' => 'PATINETA', 'camioneta' => 'CAMIONETA', 'sencillo' => 'SENCILLO',
                'turbo' => 'TURBO', 'dobletroque' => 'DOBLETROQUE', 'minimula' => 'MINIMULA',
                'tractomula' => 'TRACTOMULA', 'mula' => 'TRACTOMULA'
            ];
            $vehiculoRaw = strtolower(trim($matchVehiculo[1]));
            $valorEditadoTemprano = $vehiculosMap[$vehiculoRaw] ?? strtoupper($vehiculoRaw);
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:cambia|modifica|ajusta|actualiza)\s+(?:el\s+)?veh[ií]culo\s*(?:a|por)?\s+([a-záéíóúñ\s]+?)(?:\s*[.,;]|$)/ui', $lastUserMessageForEdit, $matchVehiculo)) {
            // 🆕 NUEVO: "cambia vehículo a tractomula" o "modifica el vehículo a dobletroque"
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'vehiculo';
            $vehiculosMap = [
                'patineta' => 'PATINETA', 'camioneta' => 'CAMIONETA', 'sencillo' => 'SENCILLO',
                'turbo' => 'TURBO', 'dobletroque' => 'DOBLETROQUE', 'minimula' => 'MINIMULA',
                'tractomula' => 'TRACTOMULA', 'mula' => 'TRACTOMULA'
            ];
            $vehiculoRaw = strtolower(trim($matchVehiculo[1]));
            $valorEditadoTemprano = $vehiculosMap[$vehiculoRaw] ?? strtoupper($vehiculoRaw);
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:el\s+)?peso\s*(?:' . $palabrasAgregar . ')\s*([\d.,]+)\s*(?:kg|kilos?|toneladas?|ton)?/ui', $lastUserMessageForEdit, $matchPeso)) {
            // 🆕 MEJORADO: Permite "peso es 900kg" sin espacio
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'peso_kg';
            $peso = floatval(str_replace(',', '.', $matchPeso[1]));
            if (preg_match('/toneladas?|ton\b/ui', $lastUserMessageForEdit)) {
                $peso = $peso * 1000;
            }
            $valorEditadoTemprano = $peso;
        } elseif ($esEdicionSimpleHabilitada && preg_match('/(?:cambia|modifica|ajusta|actualiza)\s+(?:el\s+)?peso\s*(?:a|por)?\s*([\d.,]+)\s*(?:kg|kilos?|toneladas?|ton)?/ui', $lastUserMessageForEdit, $matchPeso)) {
            // 🆕 NUEVO: "cambia peso a 900" o "modifica el peso a 5000kg"
            $esEdicionSimpleTemprana = true;
            $campoEditadoTemprano = 'peso_kg';
            $peso = floatval(str_replace(',', '.', $matchPeso[1]));
            if (preg_match('/toneladas?|ton\b/ui', $lastUserMessageForEdit)) {
                $peso = $peso * 1000;
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
                $productos = \DB::table('products')
                    ->where('nombre', 'LIKE', "%{$searchTerm}%")
                    ->limit(5)
                    ->get(['codigo', 'nombre', 'codigo_ministerio', 'tipo_producto', 'naturaleza_carga'])
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
                        foreach ($extractedData as $idx => &$ruta) {
                            if (is_array($ruta)) {
                                $ruta['producto'] = $prod['nombre'];
                                $ruta['producto_codigo'] = $prod['codigo'];
                                $ruta['tipo_producto'] = $prod['nombre'];
                                $ruta['producto_nombre'] = $prod['nombre'];
                            }
                        }
                        unset($ruta);
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
                            $group->extracted_data = json_encode($extractedData);
                            $group->save();
                        }
                    }
                    
                    $respuesta = "¡Perfecto! He seleccionado el producto **{$prod['nombre']}** (Código: {$prod['codigo']}) para tu cotización.";
                    
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
                    
                    $opcionesTxt = "He encontrado varios productos relacionados con \"{$searchTerm}\". A continuación, te presento las opciones disponibles:\n\n";
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
                    $metadata = json_decode($session->metadata ?? '{}', true);
                    $metadata['productos_pendientes'] = $productosArray;
                    $metadata['producto_search_term'] = $searchTerm;
                    $session->metadata = json_encode($metadata);
                    $session->save();
                    
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
                    
                    return [
                        'id' => $runId,
                        'status' => 'completed_with_data',
                        'extracted_data' => $previousExtractedData,
                        'productos_pendientes' => $productosArray
                    ];
                }
            }
            
            $extractedData = $previousExtractedData;
            $isMultiRouteData = isset($extractedData[0]) && is_array($extractedData[0]);
            
            if ($isMultiRouteData) {
                // 🆕 EDICIÓN DE RUTA ESPECÍFICA: Si hay una ruta seleccionada, editar SOLO esa
                if ($selectedRouteIndex !== null && isset($extractedData[$selectedRouteIndex])) {
                    // Editar solo la ruta seleccionada
                    Log::info('✏️ Editando SOLO ruta seleccionada (edición temprana)', [
                        'ruta_index' => $selectedRouteIndex,
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
                    }
                    
                    foreach ($camposAActualizar as $campo) {
                        if ($esEliminacion) {
                            unset($extractedData[$selectedRouteIndex][$campo]);
                        } else {
                            $extractedData[$selectedRouteIndex][$campo] = $valorEditadoTemprano;
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
                    }
                    
                    foreach ($extractedData as $idx => &$ruta) {
                        if (is_array($ruta) && is_numeric($idx)) {
                            foreach ($camposAActualizar as $campo) {
                                if ($esEliminacion) {
                                    unset($ruta[$campo]);
                                } else {
                                    $ruta[$campo] = $valorEditadoTemprano;
                                }
                            }
                        }
                    }
                    unset($ruta);
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
                }
                
                foreach ($camposAActualizar as $campo) {
                    if ($esEliminacion) {
                        unset($extractedData[$campo]);
                    } else {
                        $extractedData[$campo] = $valorEditadoTemprano;
                    }
                }
            }
            
            // Guardar en grupo
            if ($groupId) {
                $group = GroupCotization::find($groupId);
                if ($group) {
                    $group->extracted_data = json_encode($extractedData);
                    $group->save();
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
            // Detectar si es multi-ruta (ambos son arrays indexados)
            $isNewMultiRoute = isset($newExtractedData[0]) && is_array($newExtractedData[0]);
            $isPreviousMultiRoute = isset($previousExtractedData[0]) && is_array($previousExtractedData[0]);
            
            // 🆕 EDICIÓN DE RUTA ESPECÍFICA: Si hay una ruta seleccionada y los nuevos datos
            // son para una sola ruta (no multi-ruta), aplicar solo a esa ruta
            $isSingleRouteEdit = $selectedRouteIndex !== null && 
                                 !$isNewMultiRoute && 
                                 $isPreviousMultiRoute && 
                                 $existingRoutesCount > 1;
            
            if ($isSingleRouteEdit) {
                // Edición de ruta individual - aplicar cambios SOLO a la ruta seleccionada
                Log::info('✏️ Modo EDICIÓN DE RUTA INDIVIDUAL', [
                    'selected_route_index' => $selectedRouteIndex,
                    'existing_routes_count' => $existingRoutesCount,
                    'new_fields' => array_keys($newExtractedData)
                ]);
                
                // Verificar que la ruta seleccionada existe
                if (isset($extractedData[$selectedRouteIndex])) {
                    // Aplicar cambios solo a la ruta seleccionada
                    foreach ($newExtractedData as $key => $value) {
                        if (!empty($value) && !is_numeric($key)) {
                            // Solo aplicar campos de datos (no índices numéricos)
                            $oldValue = $extractedData[$selectedRouteIndex][$key] ?? null;
                            $extractedData[$selectedRouteIndex][$key] = $value;
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
                        }
                    }
                } else {
                    // Sin ruta seleccionada, aplicar a la primera ruta (índice 0)
                    if (isset($extractedData[0])) {
                        foreach ($newExtractedData as $key => $value) {
                            if (!empty($value) && !is_numeric($key)) {
                                $extractedData[0][$key] = $value;
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
        $noIncluyeTara = preg_match('/no\s+incluye\s+tara|sin\s+tara|peso\s+neto|el\s+peso\s+no\s+incluye\s+tara/ui', $lastUserMessage);
        $yaIncluyeTara = preg_match('/(?:ya\s+(?:incluye|tiene)|peso\s+(?:ya\s+)?(?:con|incluye)\s+tara|tara\s+(?:ya\s+)?incluida|(?:el\s+)?peso\s+es\s+con\s+tara)/ui', $lastUserMessage);
        $agregarTara = preg_match('/(?:agrega|añade|suma|pon|incluye|incluir|agregar)\s+(?:la\s+)?tara/ui', $lastUserMessage);
        $quitarTara = preg_match('/(?:quita|elimina|remueve|resta|saca|sin)\s+(?:la\s+)?tara/ui', $lastUserMessage);
        
        // 🆕 Si el usuario dice "quita la tara", restar 3400 kg
        if ($quitarTara && !$noIncluyeTara) {
            $isMultiRouteData = isset($extractedData[0]) && is_array($extractedData[0]);
            
            if ($isMultiRouteData) {
                foreach ($extractedData as $idx => $route) {
                    if (isset($route['peso_kg']) && isset($route['incluye_tara']) && $route['incluye_tara']) {
                        $pesoAnterior = $route['peso_kg'];
                        $extractedData[$idx]['peso_kg'] = max(0, $pesoAnterior - 3400);
                        $extractedData[$idx]['incluye_tara'] = false;
                        Log::info('📦 TARA removida de ruta (multi)', [
                            'ruta' => $idx,
                            'peso_anterior' => $pesoAnterior,
                            'peso_sin_tara' => $extractedData[$idx]['peso_kg']
                        ]);
                    }
                }
            } else {
                if (isset($extractedData['peso_kg']) && isset($extractedData['incluye_tara']) && $extractedData['incluye_tara']) {
                    $pesoAnterior = $extractedData['peso_kg'];
                    $extractedData['peso_kg'] = max(0, $pesoAnterior - 3400);
                    $extractedData['incluye_tara'] = false;
                    Log::info('📦 TARA removida del peso', [
                        'peso_anterior' => $pesoAnterior,
                        'tara' => 3400,
                        'peso_sin_tara' => $extractedData['peso_kg']
                    ]);
                }
            }
        }
        
        // 🆕 Agregar tara solo si se pide explícitamente Y no está ya incluida
        if ($agregarTara && !$yaIncluyeTara) {
            // Verificar si hay peso en los datos y sumar tara (3400 kg fijo)
            $isMultiRouteData = isset($extractedData[0]) && is_array($extractedData[0]);
            
            if ($isMultiRouteData) {
                foreach ($extractedData as $idx => $route) {
                    // 🔧 Verificar que NO tenga ya la tara incluida en los datos
                    $yaConTara = isset($route['incluye_tara']) && $route['incluye_tara'] === true;
                    if (isset($route['peso_kg']) && !$yaConTara) {
                        $pesoAnterior = $route['peso_kg'];
                        $extractedData[$idx]['peso_kg'] = $pesoAnterior + 3400;
                        $extractedData[$idx]['incluye_tara'] = true;
                        Log::info('📦 TARA agregada a ruta (multi)', [
                            'ruta' => $idx,
                            'peso_anterior' => $pesoAnterior,
                            'peso_con_tara' => $extractedData[$idx]['peso_kg']
                        ]);
                    } else if ($yaConTara) {
                        Log::info('📦 TARA ya incluida en ruta (multi), no se suma de nuevo', [
                            'ruta' => $idx,
                            'peso_actual' => $route['peso_kg']
                        ]);
                    }
                }
            } else {
                // 🔧 Verificar que NO tenga ya la tara incluida en los datos
                $yaConTara = isset($extractedData['incluye_tara']) && $extractedData['incluye_tara'] === true;
                if (isset($extractedData['peso_kg']) && !$yaConTara) {
                    $pesoAnterior = $extractedData['peso_kg'];
                    $extractedData['peso_kg'] = $pesoAnterior + 3400;
                    $extractedData['incluye_tara'] = true;
                    Log::info('📦 TARA agregada al peso', [
                        'peso_anterior' => $pesoAnterior,
                        'tara' => 3400,
                        'peso_con_tara' => $extractedData['peso_kg']
                    ]);
                } else if ($yaConTara) {
                    Log::info('📦 TARA ya incluida en datos, no se suma de nuevo', [
                        'peso_actual' => $extractedData['peso_kg']
                    ]);
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
                
                // 🆕 FIX: Si no viene selectedRouteIndex en el request, usar el guardado en metadata
                $rutaParaProducto = $selectedRouteIndex;
                if ($rutaParaProducto === null && isset($metadata['producto_pendiente_ruta_index'])) {
                    $rutaParaProducto = $metadata['producto_pendiente_ruta_index'];
                    Log::info('📍 Usando ruta guardada en metadata para producto', [
                        'ruta_index' => $rutaParaProducto
                    ]);
                }
                
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
                        
                        Log::info('🎯 Producto de opción aplicado SOLO a ruta seleccionada', [
                            'ruta_index' => $rutaParaProducto,
                            'producto' => $productoSeleccionado['nombre'],
                            'codigo' => $productoSeleccionado['codigo'] ?? 'N/A'
                        ]);
                    } elseif ($isMultiRouteData) {
                        // Si NO hay ruta seleccionada, aplicar a todas
                        foreach ($extractedData as $idx => &$ruta) {
                            if (is_numeric($idx) && is_array($ruta)) {
                                $ruta['producto'] = $productoSeleccionado['nombre'] ?? '';
                                $ruta['producto_codigo'] = $productoSeleccionado['codigo'] ?? null;
                                $ruta['producto_nombre'] = $productoSeleccionado['nombre'] ?? '';
                                $ruta['tipo_producto'] = $productoSeleccionado['tipo'] ?? 'MERCANCIAS VARIAS';
                            }
                        }
                        unset($ruta);
                        
                        Log::info('🎯 Producto de opción aplicado a TODAS las rutas', [
                            'producto' => $productoSeleccionado['nombre'],
                            'codigo' => $productoSeleccionado['codigo'] ?? 'N/A'
                        ]);
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
                    
                    // Guardar respuesta
                    $respuestaSeleccion = "¡Perfecto! He seleccionado el producto **{$productoSeleccionado['nombre']}** (Código: {$productoSeleccionado['codigo']}) para tu cotización.";
                    
                    ConversationMessage::create([
                        'session_id' => $session->id,
                        'group_cotization_id' => $groupId,
                        'role' => 'assistant',
                        'content' => $respuestaSeleccion,
                        'timestamp' => now()
                    ]);
                    
                    $runId = 'run_product_selection_' . time();
                    
                    $metadata['last_run_id'] = $runId;
                    $metadata['last_run_status'] = 'completed';
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
        elseif (preg_match('/(?:el\s+)?peso\s*(?:es|será|sea|cambia\s*a|:)\s+([\d.,]+)\s*(?:kg|kilos?|toneladas?)?/ui', $lastUserMessage, $matches)) {
            $esEdicionSimple = true;
            $campoEditado = 'peso';
            $peso = floatval(str_replace(',', '.', $matches[1]));
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
            
            // Aplicar el cambio a los datos extraídos
            // 🆕 IMPORTANTE: Actualizar AMBOS nombres de campo para consistencia
            $fieldMappings = [
                'producto' => ['producto', 'tipo_producto'],
                'origen' => ['origen', 'ciudad_origen'],
                'destino' => ['destino', 'ciudad_destino'],
                'peso' => ['peso_kg'],
                'cantidad' => ['cantidad'],
                'valor' => ['valor_declarado'],
                'vehiculo' => ['vehiculo', 'claseVehiculo', 'vehiculo_requerido']
            ];
            
            $fieldNames = $fieldMappings[$campoEditado] ?? [$campoEditado];
            
            // Si hay una ruta seleccionada y es multi-ruta, aplicar solo a esa ruta
            $isMultiRouteData = isset($extractedData[0]) && is_array($extractedData[0]);
            
            if ($isMultiRouteData && $selectedRouteIndex !== null && isset($extractedData[$selectedRouteIndex])) {
                foreach ($fieldNames as $fieldName) {
                    $extractedData[$selectedRouteIndex][$fieldName] = $valorEditado;
                }
                Log::info("✏️ Campos actualizados en ruta {$selectedRouteIndex}", [
                    'campos' => $fieldNames,
                    'valor' => $valorEditado
                ]);
            } elseif ($isMultiRouteData) {
                // Sin ruta seleccionada, aplicar a todas
                foreach ($extractedData as $idx => &$ruta) {
                    if (is_array($ruta) && is_numeric($idx)) {
                        foreach ($fieldNames as $fieldName) {
                            $ruta[$fieldName] = $valorEditado;
                        }
                    }
                }
                unset($ruta);
                Log::info("✏️ Campos actualizados en TODAS las rutas", [
                    'campos' => $fieldNames,
                    'valor' => $valorEditado
                ]);
            } else {
                foreach ($fieldNames as $fieldName) {
                    $extractedData[$fieldName] = $valorEditado;
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
                        foreach ($extractedData as $idx => &$ruta) {
                            if (is_array($ruta) && is_numeric($idx)) {
                                $ruta['producto_codigo'] = $productoInfo['codigo'];
                                $ruta['producto_nombre'] = $productoInfo['nombre'];
                                if ($productoInfo['tipo']) {
                                    $ruta['tipo_producto'] = $productoInfo['tipo'];
                                }
                            }
                        }
                        unset($ruta);
                    } else {
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
                
                // 🆕 FILTRAR mensajes técnicos que confunden al usuario
                $contenido = $assistantMessage['content'];
                $mensajesTecnicos = [
                    '/pricing_id/i',
                    '/identificador de precios/i',
                    '/tarifa.*pricing/i',
                    '/error.*validation/i',
                    '/SQLSTATE/i',
                    '/Exception/i',
                ];
                
                $esMessageTecnico = false;
                foreach ($mensajesTecnicos as $patron) {
                    if (preg_match($patron, $contenido)) {
                        $esMessageTecnico = true;
                        break;
                    }
                }
                
                // Si es mensaje técnico, reemplazarlo con uno amigable
                if ($esMessageTecnico) {
                    Log::warning('⚠️ Mensaje técnico detectado, reemplazando con mensaje amigable', [
                        'original' => substr($contenido, 0, 200)
                    ]);
                    $contenido = "Todos los datos están completos. ¿Deseas crear la cotización ahora?";
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
                    $group->extracted_data = json_encode($extractedData);
                    $group->save();
                    
                    Log::info('📦 Datos extraídos guardados en GRUPO', [
                        'group_id' => $currentGroupId,
                        'fields' => array_keys($extractedData)
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
    private static function processToolCalls($threadId, $assistantMessage, $openaiResponse, $extractedData = [], $groupId = null, $selectedRouteIndex = null)
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
        $quoteDataFromTools = self::extractQuoteData($toolResults);
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
        if (!empty($extractedData) && $currentGroupId) {
            $group = GroupCotization::find($currentGroupId);
            if ($group) {
                // Mergear con datos existentes del grupo
                $existingGroupData = json_decode($group->extracted_data ?? '{}', true) ?? [];
                
                // 🔴 CRÍTICO: Detectar si son multi-rutas (arrays indexados)
                // Si ambos tienen claves numéricas, es multi-ruta y debemos REEMPLAZAR, no mergear
                $isNewMultiRoute = isset($extractedData[0]) && is_array($extractedData[0]);
                $isExistingMultiRoute = isset($existingGroupData[0]) && is_array($existingGroupData[0]);
                
                if ($isNewMultiRoute) {
                    // Las nuevas rutas REEMPLAZAN las existentes
                    // Pero conservamos campos no-rutas del existente (producto_codigo, etc.)
                    $mergedGroupData = [];
                    
                    // Primero, copiar las NUEVAS rutas
                    foreach ($extractedData as $key => $value) {
                        $mergedGroupData[$key] = $value;
                    }
                    
                    // Luego, copiar campos NO numéricos del existente que no estén en el nuevo
                    foreach ($existingGroupData as $key => $value) {
                        if (!is_numeric($key) && !isset($mergedGroupData[$key])) {
                            $mergedGroupData[$key] = $value;
                        }
                    }
                    
                    Log::info('🔄 Multi-ruta: REEMPLAZANDO rutas existentes', [
                        'rutas_nuevas' => count(array_filter(array_keys($extractedData), 'is_numeric')),
                        'rutas_finales' => count(array_filter(array_keys($mergedGroupData), 'is_numeric'))
                    ]);
                } else {
                    // Ruta única o campos sueltos: mergear normalmente
                    $mergedGroupData = array_merge($existingGroupData, $extractedData);
                }
                
                $group->extracted_data = json_encode($mergedGroupData);
                $group->save();
                
                Log::info('📦 extracted_data guardado en GRUPO (processToolCalls)', [
                    'group_id' => $currentGroupId,
                    'fields' => array_keys($mergedGroupData)
                ]);
            }
            
            // También guardar indexado por grupo en metadata para compatibilidad
            if (!isset($metadata['extracted_data_by_group'])) {
                $metadata['extracted_data_by_group'] = [];
            }
            $metadata['extracted_data_by_group'][$currentGroupId] = $extractedData;
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
    private static function extractQuoteData($toolResults)
    {
        $quoteData = [];

        foreach ($toolResults as $result) {
            $content = json_decode($result['content'], true);
            
            // Si la herramienta es create_cotizacion, extraer los datos
            if ($result['name'] === 'create_cotizacion' && isset($content['cotizacion'])) {
                $quoteData[] = $content['cotizacion'];
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
                    
                    foreach ($extractedData as $index => $ruta) {
                        if (!is_array($ruta)) continue; // Saltar si no es un array válido
                        
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
            
            // Buscar palabras clave de empaque
            foreach ($empaqueKeywords as $keyword => $empaqueName) {
                if (strpos($text, $keyword) !== false) {
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

            // Intentar búsqueda exacta primero
            $producto = \DB::table('products')
                ->where('producto_nombre', 'LIKE', '%' . $productoName . '%')
                ->first();

            // Si no encuentra exacto, buscar por similitud con SOUNDEX o partes del nombre
            if (!$producto) {
                // Separar palabras y buscar por cada una
                $palabras = explode(' ', $productoName);
                foreach ($palabras as $palabra) {
                    if (strlen($palabra) > 3) { // Ignorar palabras muy cortas
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
     * 🚚 DETECTAR MÚLTIPLES RUTAS EN EL TEXTO
     * Retorna array de rutas con origen, destino, peso, cantidad y valor EXPLÍCITOS por cada ruta
     */
    private static function detectMultipleRoutes($text)
    {
        $routes = [];
        
        Log::info('🔍 Detectando múltiples rutas en texto', [
            'text_preview' => substr($text, 0, 500),
            'text_length' => strlen($text)
        ]);
        
        // 🆕 NUEVO PATRÓN: "Una cotización de X a Y, son N kg/toneladas de PRODUCTO..."
        // Formato: "Necesito una cotización de distribución nacionalizada de Medellín a Bogota, son 7 mil kilogramos de vacas..."
        // IMPORTANTE: Usa lookahead (?=\s+por\s+un\s+valor) para capturar correctamente el nombre del producto
        $patronCotizacion = '/(?:una\s+)?cotizaci[oó]n(?:\s+de\s+distribuci[oó]n(?:\s+nacionalizada)?)?\s+(?:de|desde)\s+([a-záéíóúñ\s]+?)\s+(?:a|hasta|hacia)\s+([a-záéíóúñ\s]+?),?\s+(?:son\s+)?(\d+(?:\s*mil)?)\s*(?:kilogramos?|kg|toneladas?|ton)\s+(?:sin\s+tara\s+de\s+|con\s+tara\s+de\s+|de\s+)?([a-záéíóúñ\s]+?)(?=\s+por\s+un\s+valor)/ui';
        
        if (preg_match_all($patronCotizacion, $text, $matchesCotizacion, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            Log::info('✅ Patrón COTIZACIÓN encontró rutas', [
                'count' => count($matchesCotizacion),
                'matches_preview' => array_map(function($m) { return $m[0][0] ?? ''; }, array_slice($matchesCotizacion, 0, 3))
            ]);
            
            foreach ($matchesCotizacion as $idx => $match) {
                $origen = self::normalizeCityName(trim($match[1][0]));
                $destino = self::normalizeCityName(trim($match[2][0]));
                
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
                        'producto' => $route['producto'] ?? 'N/A',
                        'cantidad' => $route['cantidad'] ?? 'N/A',
                        'empaque' => $route['empaque'] ?? 'N/A',
                        'vehiculo' => $route['vehiculo'] ?? 'N/A',
                        'valor' => $route['valor_declarado'] ?? 'N/A'
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
                        'producto' => $route['producto'] ?? 'N/A',
                        'cantidad' => $route['cantidad'] ?? 'N/A',
                        'empaque' => $route['empaque'] ?? 'N/A',
                        'vehiculo' => $route['vehiculo'] ?? 'N/A'
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
     */
    private static function processMultipleRoutes($routes, $fullText, $lowerText)
    {
        Log::info('🔄 Procesando múltiples rutas (respetando datos explícitos)', [
            'total_rutas' => count($routes)
        ]);

        // Extraer datos comunes que aplican SOLO a rutas sin datos explícitos
        $commonData = [];

        // 1️⃣ EMPAQUE COMÚN
        $empaque = self::extractEmpaque($lowerText);
        if ($empaque) {
            $commonData = array_merge($commonData, $empaque);
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
                'peso_kg' => $completeRoute['peso_kg'] ?? 'N/A',
                'cantidad' => $completeRoute['cantidad'] ?? 'N/A',
                'valor' => $completeRoute['valor_declarado'] ?? 'N/A',
                'producto' => $completeRoute['producto'] ?? 'N/A',
                'empaque' => $completeRoute['empaque'] ?? 'N/A',
                'vehiculo' => $completeRoute['vehiculo'] ?? 'N/A'
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

        // Producto
        $producto = self::extractProducto($fullText);
        if ($producto) {
            $data['producto'] = $producto;
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
            $data = array_merge($data, $empaque);
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
        if (preg_match('/(?:cambia|cambiar)(?:\s+el)?\s+origen\s+(?:a|por)\s+([a-záéíóúñ]+)' . $terminadorOrigen . '/ui', $text, $matches)) {
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
        elseif (preg_match('/(?:el\s+)?origen\s*(?:es|será|sea|queda|:)\s*([a-záéíóúñ]+)' . $terminadorOrigen . '/ui', $text, $matches)) {
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
        if (preg_match('/(?:cambia|cambiar)(?:\s+el)?\s+destino\s+(?:a|por)\s+([a-záéíóúñ]+)' . $terminadorDestino . '/ui', $text, $matches)) {
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
        elseif (preg_match('/(?:el\s+)?destino\s*(?:es|será|sea|queda|:)\s*([a-záéíóúñ]+)' . $terminadorDestino . '/ui', $text, $matches)) {
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
        // Terminador: número, "toneladas", "kg", "con", "en un", coma, punto, etc.
        $terminadorRuta = '(?=\s*(?:\d|toneladas?|kg|kilos?|con\s|en\s+un|,|\.|;|$))';
        if (preg_match('/(?:de|desde)\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})\s+(?:a|hasta)\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,1})' . $terminadorRuta . '/ui', $text, $matches)) {
            // 🆕 Palabras que NUNCA pueden ser ciudades (incluye campos de formulario y acciones)
            $forbiddenWords = ['producto', 'peso', 'valor', 'empaque', 'embalaje', 'cantidad', 'origen', 'destino', 
                               'cambia', 'cambiar', 'cambialo', 'cambiale', 'modifica', 'actualiza', 'nota', 'importante'];
            $commonWords = ['importación', 'exportación', 'nacionalizada', 'internacional', 'terrestre', 'marítima', 'aérea', 'carga', 'general'];
            $origen = trim($matches[1]);
            $destino = trim($matches[2]);
            
            // 🆕 Verificar que origen/destino no sean palabras prohibidas
            $origenLower = strtolower($origen);
            $destinoLower = strtolower($destino);
            $origenIsForbidden = false;
            $destinoIsForbidden = false;
            
            foreach ($forbiddenWords as $forbidden) {
                if (strpos($origenLower, $forbidden) !== false) $origenIsForbidden = true;
                if (strpos($destinoLower, $forbidden) !== false) $destinoIsForbidden = true;
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
            
            // 🆕 Palabras prohibidas que NUNCA pueden ser ciudades
            $forbiddenWords = ['producto', 'peso', 'valor', 'empaque', 'embalaje', 'cantidad', 'origen', 'destino', 
                               'cambia', 'cambiar', 'cambialo', 'cambiale', 'modifica', 'actualiza', 'nota', 'importante',
                               'tomates', 'maiz', 'arroz', 'cafe', 'azucar', 'miel']; // Nombres de productos comunes
            $commonWords = ['necesito', 'quiero', 'solicito', 'cotización', 'importación', 'exportación', 'nacionalizada', 'carga'];
            
            // Verificar palabras prohibidas
            $origenLower = strtolower($origen);
            $destinoLower = strtolower($destino);
            $origenIsForbidden = in_array($origenLower, $forbiddenWords) || 
                                 array_filter($forbiddenWords, fn($w) => strpos($origenLower, $w) !== false);
            $destinoIsForbidden = in_array($destinoLower, $forbiddenWords) ||
                                  array_filter($forbiddenWords, fn($w) => strpos($destinoLower, $w) !== false);
            
            $isOriginCommon = in_array($origenLower, $commonWords);
            $isDestinationCommon = in_array($destinoLower, $commonWords);
            
            if (!is_numeric($origen) && !is_numeric($destino) && 
                !$isOriginCommon && !$isDestinationCommon &&
                !$origenIsForbidden && !$destinoIsForbidden &&
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
     * 🆕 NORMALIZAR NOMBRE DE CIUDAD
     * Convierte a formato estándar compatible con BD
     */
    private static function normalizeCityName($cityName)
    {
        // Eliminar acentos y convertir a mayúsculas
        $cityName = trim($cityName);
        
        // 🆕 Eliminar palabras de cortesía y frases comunes
        $cityName = preg_replace('/\b(por\s+favor|gracias|porfavor|ok|bien|nota|importante)\b/ui', '', $cityName);
        $cityName = trim($cityName);
        
        // 🆕 Extraer ciudad de "Puerto de X" o "Aeropuerto de X"
        if (preg_match('/(?:Puerto|Aeropuerto|Terminal)\s+(?:de\s+)?([A-Za-záéíóúñÁÉÍÓÚÑ]+)/ui', $cityName, $puertoMatch)) {
            $cityName = trim($puertoMatch[1]);
            Log::info('📍 Ciudad extraída de Puerto/Aeropuerto', ['original' => $cityName, 'ciudad' => $puertoMatch[1]]);
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
     * ⚖️ EXTRAER PESO (en kilogramos)
     */
    private static function extractPeso($text)
    {
        // 🆕 Patrón: "Peso bruto: 9.900 kg" (formato formal)
        if (preg_match('/Peso\s+bruto\s*:\s*([\d.,]+)\s*(?:kg|kilos?)?/ui', $text, $matches)) {
            $peso = str_replace(['.', ','], '', $matches[1]);
            Log::info('📊 Peso detectado (patrón "Peso bruto: X")', ['peso' => $peso, 'raw' => $matches[1]]);
            return (int)$peso;
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
        
        // Kilogramos (simple)
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:kg|kilos?|kilogramos?)/ui', $text, $matches)) {
            return (int)str_replace(',', '.', $matches[1]);
        }

        return null;
    }

    /**
     * 📦 EXTRAER PRODUCTO
     */
    private static function extractProducto($text)
    {
        // 🆕 Patrón: "Tipo de mercancía: X" (formato formal)
        // Ejemplo: "Tipo de mercancía: Aislador GY"
        if (preg_match('/Tipo\s+de\s+mercancía\s*:\s*([A-Za-záéíóúñÁÉÍÓÚÑ\s0-9]+?)(?:\s*\n|$)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            if (strlen($producto) > 1 && strlen($producto) < 100) {
                Log::info('📦 Producto detectado (patrón "Tipo de mercancía: X")', ['producto' => $producto]);
                return strtoupper($producto);
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
                Log::info('📦 Producto detectado (patrón "Producto: X")', ['producto' => $producto]);
                return strtoupper($producto);
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
                Log::info('📦 Producto detectado (patrón "Mercancía: X")', ['producto' => $producto]);
                return strtoupper($producto);
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
                Log::info('📦 Producto detectado (patrón CORRECCIÓN "producto es X")', ['producto' => $producto]);
                return strtoupper($producto);
            }
        }
        
        // 🆕 Patrón EXTRA: "aqui/ahi el producto es X" - cuando hay prefijo
        if (preg_match('/(?:aqu[ií]|ah[ií]|aqui|ahi)\s+(?:el\s+)?producto\s*(?:es|será|sea|:)\s+([a-záéíóúñ\s]+?)(?:\s*[.,;]|\s+y\s+|$)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            
            if (strlen($producto) > 1 && strlen($producto) < 50) {
                Log::info('📦 Producto detectado (patrón "aqui el producto es X")', ['producto' => $producto]);
                return strtoupper($producto);
            }
        }
        
        // 🆕 Patrón: "cambia el producto a X" (con 'el' en medio)
        if (preg_match('/(?:cambia|cambiar)(?:\s+el)?\s+producto\s+(?:a|por)\s+([a-záéíóúñ]+)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            
            if (strlen($producto) > 1 && strlen($producto) < 50) {
                Log::info('📦 Producto detectado (patrón "cambia el producto a X")', ['producto' => $producto]);
                return strtoupper($producto);
            }
        }
        
        // 🆕 Patrón: "cambialo a X" o "pon X"
        // ⚠️ EXCLUIR si el texto es corrección de origen/destino/empaque
        $esCambioDeOtroCampo = preg_match('/\b(destino|origen|empaque|embalaje)\s+(cambia|cambialo)/ui', $text);
        if (!$esCambioDeOtroCampo && preg_match('/(?:cambialo|cambiale|reemplaza|pon)\s+(?:a|por)?\s*([a-záéíóúñ]+)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            
            if (strlen($producto) > 2 && strlen($producto) < 50) {
                Log::info('📦 Producto detectado (patrón "cambia a X")', ['producto' => $producto]);
                return strtoupper($producto);
            }
        }
        
        // 🆕 Patrón: "producto ... deja X" (para frases como "en producto cambia deja miel")
        if (preg_match('/producto.*?deja\s+([a-záéíóúñ]+)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            
            if (strlen($producto) > 1 && strlen($producto) < 50) {
                Log::info('📦 Producto detectado (patrón "producto...deja X")', ['producto' => $producto]);
                return strtoupper($producto);
            }
        }
        
        // Patrón PRIORITARIO: "[cantidad] toneladas de PRODUCTO"
        if (preg_match('/\d+\s*toneladas?\s+de\s+([a-záéíóúñ\s]+?)(?:\s+por\s+|\s+empaquetad|\s+empacad|en\s+(?:cajas|sacos|bultos)|\s+y\s+|\.|,|$)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            $producto = preg_replace('/\b(para|llevar|transportar|toneladas?|de|cada\s+una)\b/ui', '', $producto);
            $producto = trim($producto);
            
            if (strlen($producto) > 2 && strlen($producto) < 100) {
                Log::info('📦 Producto detectado (patrón "X toneladas de PRODUCTO")', ['producto' => $producto, 'text_sample' => substr($text, 0, 200)]);
                return strtoupper($producto);
            }
        }
        
        // Patrón 1: "producto NOMBRE" o "mercancía NOMBRE"
        if (preg_match('/(?:producto|mercancía|mercancia)\s+([a-záéíóúñ\s]+?)(?:\s+empaquetad|empacad|en\s+cajas|\s+y\s+|\.|,|$)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            $producto = preg_replace('/\b(para|llevar|transportar|toneladas?)\b/ui', '', $producto);
            $producto = trim($producto);
            
            if (strlen($producto) > 2 && strlen($producto) < 100) {
                Log::info('📦 Producto detectado (patrón "producto X")', ['producto' => $producto]);
                return strtoupper($producto);
            }
        }
        
        // Patrón 2 (FALLBACK): "de/llevar/transportar PRODUCTO" - menos prioritario
        if (preg_match('/(?:transportar|llevar)\s+([a-záéíóúñ\s]+?)(?:\s+de\s+\d+|\s+por\s+|\s+empaquetad|\s+y\s+|\.|,|$)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            $producto = preg_replace('/\b(para|llevar|transportar|toneladas?|de|cada\s+una)\b/ui', '', $producto);
            $producto = trim($producto);
            
            if (strlen($producto) > 2 && strlen($producto) < 100) {
                Log::info('📦 Producto detectado (patrón "transportar X")', ['producto' => $producto]);
                return strtoupper($producto);
            }
        }

        Log::warning('⚠️ No se pudo extraer producto del texto', ['text_sample' => substr($text, 0, 200)]);
        return null;
    }

    /**
     * 🔢 EXTRAER CANTIDAD
     */
    private static function extractCantidad($text)
    {
        // Patrón 0: Formato contenedor "2×40hc" o "2x40hc" o "2x40'"
        if (preg_match('/(\d+)\s*[×x]\s*(?:\d+[\'"]?)?(?:hc|gp|rf)?/ui', $text, $matches)) {
            Log::info('Cantidad detectada (formato contenedor)', ['raw' => $matches[0], 'cantidad' => $matches[1]]);
            return (int)$matches[1];
        }
        
        // Patrón 1: "cantidad 60" o "cantidad: 60"
        if (preg_match('/(?:cantidad|son|hay)\s*:?\s*(\d+)/ui', $text, $matches)) {
            return (int)$matches[1];
        }
        
        // Patrón 2: "N unidades/piezas/uds"
        if (preg_match('/(\d+)\s*(?:unidades?|uds?|piezas?)/ui', $text, $matches)) {
            return (int)$matches[1];
        }
        
        // Patrón 3: "N cajas/bultos/sacos/estibas/paquetes" (empaques como cantidad)
        if (preg_match('/(\d+)\s*(?:cajas?|bultos?|sacos?|estibas?|paquetes?|bolsas?|toneles?|bidones?|canecas?|tambores?)/ui', $text, $matches)) {
            return (int)$matches[1];
        }
        
        // Patrón 4: "cajas: N" o "paquetes: N"
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
        
        // 🆕 Patrón PRIORITARIO: Formato "1X40 HQ" o "2X20 GP" o "1x40hc" (NxTAMAÑO TIPO)
        // Este formato es muy común en solicitudes de logística
        if (preg_match('/(\d+)\s*[Xx]\s*(\d+)\s*(HQ|HC|GP|RF|OT|FR)?/ui', $text, $matches)) {
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
        // 🆕 Patrón PRIORITARIO: Corrección de empaque
        // "el empaque es cajas" o "empaque: bultos" o "cambia el empaque a sacos" o "empaque son cajas"
        $empaqueKeyword = null;
        
        // 🆕 NUEVO: Detectar "empacadas/empacados en X" o "embaladas en X" PRIMERO
        // "60 unidades empacadas en cajas" → cajas
        if (preg_match('/(?:empacad[ao]s?|embalad[ao]s?|envuelto?s?)\s+(?:en|con)\s+([a-záéíóúñ]+)/ui', $lowerText, $matches)) {
            $empaqueKeyword = strtolower(trim($matches[1]));
            Log::info('📦 Empaque detectado (patrón "empacadas en X")', ['keyword' => $empaqueKeyword]);
        }
        // Patrón: "cambia el empaque a X" o "empaque cambialo a X"
        elseif (preg_match('/(?:cambia|cambiar)(?:\s+el)?\s+empaque\s+(?:a|por)\s+([a-záéíóúñ]+)/ui', $lowerText, $matches) ||
            preg_match('/empaque\s+cambialo\s+(?:a|por)\s+([a-záéíóúñ]+)/ui', $lowerText, $matches)) {
            $empaqueKeyword = strtolower(trim($matches[1]));
            Log::info('📦 Empaque detectado (patrón "cambia empaque a X")', ['keyword' => $empaqueKeyword]);
        }
        // Patrón: "empaque es/son X" o "empaque: X"
        elseif (preg_match('/(?:el\s+)?(?:empaque|embalaje)\s*(?:es|son|será|sea|queda|:)\s*([a-záéíóúñ\s]+?)(?:\s*[.,;]|\s+y\s+|$)/ui', $lowerText, $matches)) {
            $empaqueKeyword = strtolower(trim($matches[1]));
            Log::info('📦 Empaque detectado (patrón CORRECCIÓN)', ['keyword' => $empaqueKeyword]);
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
            'estiba' => 'CARGA ESTIBADA',
            'estibas' => 'CARGA ESTIBADA',
            'estibada' => 'CARGA ESTIBADA',
            'pallet' => 'CARGA ESTIBADA',
            'pallets' => 'CARGA ESTIBADA',
            'paletizado' => 'CARGA ESTIBADA',
            // Granel
            'granel' => 'GRANEL SOLIDO',
            'granel solido' => 'GRANEL SOLIDO',
            'granel sólido' => 'GRANEL SOLIDO',
            'granel liquido' => 'GRANEL LIQUIDO',
            'granel líquido' => 'GRANEL LIQUIDO',
            'líquido' => 'GRANEL LIQUIDO',
            'liquido' => 'GRANEL LIQUIDO',
            // Contenedores
            'contenedor' => 'CONTENEDOR (1) 20 PIES',
            'contenedor 20' => 'CONTENEDOR (1) 20 PIES',
            'contenedor 40' => 'CONTENEDOR 40 PIES',
            '20 pies' => 'CONTENEDOR (1) 20 PIES',
            '40 pies' => 'CONTENEDOR 40 PIES',
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
        
        foreach ($empaques as $keyword => $empaqueType) {
            // 🆕 Si es contenedor de vehículo, NO usarlo como empaque
            if ($esContenedorDeVehiculo && strpos($keyword, 'contenedor') !== false) {
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
     * 🚛 EXTRAER VEHÍCULO EXPLÍCITO DEL TEXTO
     */
    private static function extractVehiculo($text)
    {
        // Lista de vehículos válidos
        $vehiculos = [
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

        if (count($detectedRoutes) >= 2) {
            // Caso especial: múltiples rutas
            Log::info('🎯 PROCESANDO MÚLTIPLES RUTAS', ['total' => count($detectedRoutes)]);
            return self::processMultipleRoutes($detectedRoutes, $combinedText, $lowerLastText);
        }

        // 🔄 FLUJO NORMAL: Una sola ruta - extraer SOLO del último mensaje
        Log::info('📦 Procesando ruta única desde ÚLTIMO mensaje');
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
                $multiRouteInstruction .= "3. Llama search_products() UNA VEZ con el producto común\n";
                $multiRouteInstruction .= "4. Cuando el usuario seleccione el producto, llama create_cotizacion() {$routeCount} VECES\n";
                $multiRouteInstruction .= "5. CADA llamada a create_cotizacion debe incluir:\n";
                $multiRouteInstruction .= "   - Datos específicos de la ruta (origen, destino, peso)\n";
                $multiRouteInstruction .= "   - Datos comunes (producto, empaque, cantidad, valor)\n";
                $multiRouteInstruction .= "   - El MISMO group_cotization_id para TODAS las rutas\n";
                $multiRouteInstruction .= "6. NO pidas confirmación, EJECUTA todas las llamadas automáticamente\n";
                $multiRouteInstruction .= "7. Rellena campos faltantes con valores por defecto razonables:\n";
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
            
            if (!$isMultiRoute) {
                $dataInstruction .= "\n⚡ INSTRUCCIONES CRÍTICAS:\n";
                $dataInstruction .= "1. CONFIRMA los datos capturados al usuario\n";
                $dataInstruction .= "2. PREGUNTA SOLO por lo que FALTA (máximo 1 pregunta)\n";
                $dataInstruction .= "3. SI el usuario responde con dato adicional ('cantidad 60'), ACTUALÍZALO\n";
                $dataInstruction .= "4. Procede a buscar productos con search_products()\n";
                $dataInstruction .= "5. Si todos los datos están completos, crea la cotización\n\n";
            }
        }

        $basePrompt = <<<EOT
🤖 AGENTE INTELIGENTE CONALCA - GPT-5 Mini
Eres un agente autónomo especializado en logística de transporte de carga en Colombia.
{$empaqueInstruction}{$multiRouteInstruction}{$dataInstruction}

🧠 CAPACIDADES DE RAZONAMIENTO ACTIVAS:
Antes de responder, SIEMPRE:
1. ANALIZA el mensaje completo del usuario
2. IDENTIFICA todos los datos proporcionados (ciudades, peso, producto, cantidad, valor, empaque)
3. PLANIFICA qué herramientas llamar y en qué orden
4. EJECUTA las herramientas necesarias de forma autónoma
5. RESUME los resultados al usuario de forma clara

⚡ COMPORTAMIENTO AGÉNTICO:
- Actúa de forma PROACTIVA: no esperes instrucciones adicionales si tienes suficiente información
- Toma DECISIONES inteligentes: usa valores por defecto cuando sea apropiado
- EJECUTA múltiples herramientas en paralelo cuando sea posible (ej: search_products + get_empaques)
- MINIMIZA preguntas: máximo 1 pregunta por interacción
- Si detectas MÚLTIPLES RUTAS, procésalas TODAS automáticamente

🚚 MANEJO DE MÚLTIPLES RUTAS:
Si el usuario solicita VARIAS rutas en un solo mensaje (ej: "primera ruta de X a Y, segunda ruta de Z a W"):
1. DETECTA automáticamente todas las rutas mencionadas
2. EXTRAE datos comunes (producto, empaque, cantidad por ruta)
3. Llama search_products() UNA VEZ para buscar el producto
4. Cuando el usuario seleccione el producto, llama create_cotizacion() N VECES (una por ruta)
5. TODAS las rutas comparten el MISMO group_cotization_id
6. NO pidas confirmación, EJECUTA todas las llamadas automáticamente

EJEMPLO MÚLTIPLES RUTAS:
Usuario: "quiero 2 rutas, primera FUNZA a Cali 2 ton maíz, segunda Medellín a Cali 2 ton maíz"
Tú respondes: "Perfecto, registré 2 rutas:
  1. FUNZA → Cali (2 ton)
  2. Medellín → Cali (2 ton)
Buscando maíz..."
Luego llamas:
  1. search_products(search_term="maiz")
  2. Usuario selecciona "MAIZ"
  3. create_cotizacion(origen="FUNZA", destino="CALI", peso=2000, producto="MAIZ", group_cotization_id="GRUPO-123", ...)
  4. create_cotizacion(origen="MEDELLIN", destino="CALI", peso=2000, producto="MAIZ", group_cotization_id="GRUPO-123", ...)

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

� AJUSTES Y CORRECCIONES:
Cuando el usuario pide AJUSTES a datos ya capturados:
- "agrega la tara" → Suma el peso del empaque/contenedor al peso neto
- "incluye la tara" → Calcula peso bruto = peso neto + peso contenedor
- "cambia el producto" → SOLO si menciona un NUEVO producto
- "modifica el peso" → Actualiza el peso con el nuevo valor
- ⚠️ NO vuelvas a preguntar por productos si ya hay producto_codigo seleccionado
- ⚠️ NO llames a search_products para ajustes que no son de producto

📦 TARA DE CONTENEDORES (peso del empaque):
- La tara es SIEMPRE 3,400 kg (fijo para todos los contenedores)
- Si el usuario dice "agrega la tara" o "incluye la tara" → suma 3,400 kg al peso neto
- Si el usuario dice "el peso ya incluye tara" o "peso con tara" → NO sumes nada

�🚛 SUGERENCIA AUTOMÁTICA DE VEHÍCULO:
Basado en el peso detectado, sugiere automáticamente:
- Hasta 1.5 ton: CAMIONETA
- 1.5-3.5 ton: SENCILLO
- 3.5-10 ton: TURBO
- 10-17 ton: DOBLETROQUE
- 17-25 ton: TRACTOCAMION
- Más de 25 ton: MINIMULA

💡 VALORES POR DEFECTO INTELIGENTES:
Si faltan datos y el usuario NO los proporciona después de 1 pregunta:
- Cantidad: 1 unidad (si no se especifica)
- Valor declarado: USD 1,000 por tonelada (SIEMPRE usar "USD" no "$")
- Empaque: GRANEL SOLIDO (deducir según producto)
- Vehículo: AUTO-CALCULADO según peso

FORMATO DE VALORES MONETARIOS:
- SIEMPRE usa "USD" para valores en dólares, NUNCA uses "$"
- Ejemplo: "USD 77,500" NO "$77,500"
- Para valores en pesos colombianos usa "$" o "COP"

AMBIENTE DE PRUEBAS - MODO RÁPIDO:
- MINIMIZA preguntas: máximo 1 pregunta por dato faltante
- Si el usuario dice "llena lo que falta", usa valores por defecto
- PRIORIZA velocidad sobre completitud perfecta

RESPUESTA MODELO (cuando tienes todos los datos):
"Perfecto, he registrado:
✓ Ruta: Medellín → Cali
✓ Peso: 2,000 kg
✓ Cantidad: 60 unidades
✓ Valor: $2,000,000 COP (o USD 500 si es en dólares)
✓ Embalaje: GRANEL SOLIDO
✓ Vehículo sugerido: SENCILLO (ideal para 2 toneladas)

Buscando maíz en catálogo..."

RESPUESTA MODELO (cuando falta algo):
"Entendido. Tengo:
✓ Medellín → Cali, 2 ton de maíz

Solo necesito:
• Cantidad de unidades
• Valor declarado (en USD o COP)

¿Cuántas unidades transportas y cuál es el valor declarado?"

Luego llama search_products().

🎯 ESTRATEGIA PARA DATOS FALTANTES:
1. SIEMPRE pregunta por campos CRÍTICOS:
   - Origen y destino (obligatorios)
   - Peso (obligatorio para calcular vehículo)
   - Producto (obligatorio para buscar en catálogo)

2. Para campos OPCIONALES, usa valores por defecto inteligentes:
   - Cantidad: 1 unidad
   - Valor declarado: USD 1,000/ton o estimado según peso
   - Empaque: GRANEL SOLIDO o según tipo de producto
   - Volumen: calculado según peso si no se especifica

3. Haz preguntas ESPECÍFICAS y CORTAS:
   ✅ "¿Cuál es el valor declarado de la mercancía?"
   ✅ "¿Cuántas unidades/cajas/contenedores son?"
   ❌ "¿Me puedes dar la información del valor declarado, cantidad y tipo de empaque?"

⚠️ IMPORTANTE SOBRE HERRAMIENTAS:
- search_products: buscar producto → ESPERA selección del usuario → NO llames create_cotizacion todavía
- create_cotizacion: crear orden → SOLO después de que usuario seleccione producto
- get_empaques: SOLO si NO mencionó embalaje
   
   - SOLO cuando el usuario NO mencionó embalaje:
     ✓ Correcto: Mostrar tarjetas "Tipos de embalaje disponibles. Selecciona uno:"
   
   - Cuando uses search_products, SIEMPRE presenta opciones para que el usuario elija:
     ✓ Correcto: "Encontré 5 tipos de neumáticos en el catálogo:
                  1. NEUMATICOS NUEVOS DE CAUCHO
                  2. NEUMATICOS RECAUCHUTADOS O USADOS
                  ¿Cuál describe mejor tu mercancía?"
     ✗ Incorrecto: Usar el término genérico "neumáticos" sin consultar opciones
   
   - ⛔ NUNCA llames create_cotizacion inmediatamente después de search_products
   - ✅ SIEMPRE espera que el usuario responda/seleccione antes de create_cotizacion
   - SIEMPRE llama a get_empaques cuando se mencione embalaje
   - SIEMPRE llama a search_products cuando se mencione un producto
   - CONFIRMA con el usuario las opciones exactas de la base de datos
   - NO uses nombres genéricos, usa los nombres EXACTOS de las tablas

6. Mantén un tono profesional pero cercano
7. Responde de forma concisa (máximo 2-3 oraciones)
8. SÉ INTELIGENTE con las variaciones del lenguaje:
   - Acepta plural y singular indistintamente
   - Normaliza errores ortográficos comunes
   - Usa coincidencia aproximada (fuzzy matching) para encontrar el término correcto
   - Si hay 90%+ de similitud, asume que es el mismo término

EJEMPLO DE FLUJO CORRECTO CON EXTRACCIÓN COMPLETA:
Usuario: "Necesito una cotización de importación nacionalizada de cartagena a funza, son 15 toneladas de neumaticos por un valor declarado de 35 millones, 60 unidades empacadas en cajas, un único vehículo con capacidad de transportar contenedor sin necesidad de devolución"

Paso 1: Extrae TODOS los datos del mensaje:
- origen: "cartagena" ✓
- destino: "funza" ✓
- peso: 15000 kg (15 toneladas convertidas) ✓
- cantidad: 60 ✓
- valor_declarado: 35000000 (35 millones) ✓
- empaque: "cajas" → detectar como "CAJAS" ✓
- producto: "neumaticos" → buscar en catálogo ✓
- vehiculo: "contenedor" ✓

Paso 2: Llama a get_empaques SOLO para obtener ID de "CAJAS" (NO muestres opciones)

Paso 3: Llama a search_products con "neumaticos"

Paso 4: Respuesta al usuario:
"Perfecto, he registrado tu solicitud:
✓ Ruta: Cartagena → Funza
✓ Peso: 15,000 kg
✓ Cantidad: 60 unidades
✓ Valor: $35,000,000 COP (o USD 8,750 si es en dólares)
✓ Embalaje: CAJAS
✓ Vehículo: Contenedor

Encontré estos tipos de neumáticos en nuestro catálogo:
1. NEUMATICOS NUEVOS DE CAUCHO
2. NEUMATICOS RECAUCHUTADOS O USADOS

¿Cuál describe mejor tu mercancía? (Responde con el número)"

Usuario: "1"

Paso 5: ⚠️ ESPERA LA RESPUESTA DEL USUARIO - NO CREES LA COTIZACIÓN TODAVÍA

Usuario selecciona producto → Ahora SÍ llama a create_cotizacion:
{
  "pricing_id": 43214,
  "ciudad_origen": "cartagena",
  "ciudad_destino": "funza",
  "peso_mercancia": 15000,
  "cantidad": 60,
  "tipo_embajale": "CAJAS",
  "tipo_producto": "NEUMATICOS NUEVOS DE CAUCHO",
  "vehiculo_requerido": "contenedor",
  "valor_declarado": 35000000
}

⛔ REGLAS CRÍTICAS PARA create_cotizacion:
1. NUNCA llames a create_cotizacion sin que el usuario haya SELECCIONADO el producto específico
2. Si search_products devuelve MÚLTIPLES opciones (>1), DEBES mostrarlas y ESPERAR que el usuario HAGA CLIC en una
3. Si search_products devuelve UNA SOLA opción (=1), DEBES confirmar con el usuario antes de crear
4. SOLO llama a create_cotizacion DESPUÉS de que el usuario confirme/seleccione el producto
5. NO asumas el producto correcto, el usuario DEBE hacer clic y elegir explícitamente
6. La selección del producto se realiza en el PANEL LATERAL de la interfaz, NO en el chat
7. Si create_cotizacion devuelve error "VALIDACIÓN REQUERIDA", significa que hay múltiples productos y DEBES esperar selección
8. NUNCA intentes llamar a create_cotizacion en la misma respuesta donde llamas a search_products

FLUJO CORRECTO:
search_products → Muestra opciones → Usuario selecciona → create_cotizacion ✅

FLUJO INCORRECTO:
search_products → create_cotizacion inmediatamente ❌

EJEMPLO INCORRECTO (NO HACER):
Usuario: "60 unidades empacadas en cajas"
Asistente: [Muestra tarjetas de PAQUETES, CAJAS, BULTOS] ← ESTO ESTÁ MAL
Lo correcto es: "Perfecto, usaremos CAJAS. ¿Qué producto transportarás?"

IMPORTANTE:
- NO inventes datos que el usuario no ha proporcionado
- NO uses términos genéricos cuando hay opciones específicas en la base de datos
- SIEMPRE valida empaques y productos con las herramientas antes de create_cotizacion
- Si falta información, pregunta específicamente por ella
- Confirma los datos importantes antes de crear la cotización
- ⚠️ NUNCA crees cotización sin confirmación explícita del producto por parte del usuario
- El usuario DEBE hacer clic en una opción de producto ANTES de que llames a create_cotizacion
EOT;

        return $basePrompt;
    }
}
