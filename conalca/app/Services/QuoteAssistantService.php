<?php

namespace App\Services;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuoteAssistantService
{
    private static $openai_uri = 'https://api.openai.com/v1';

    private static $private_token = null;
    private static $token = null; // Añadir esta propiedad
    
    // 🆕 URL del servidor MCP para ejecutar herramientas
    private static $mcp_base_url = null;

    private static $assistant_id_dta_otm = 'asst_s94P42GEEEnXEp2rufhJM0sx';

    private static $assistant_id_refri = 'asst_kvaDaC4TnAEKUrggofkHIquF';

    private static $assistant_id_impo_expo = 'asst_H1Aox8nK3G9fF5E7TtplZxiE';

    private static $assistant_id_distri = 'asst_tf9AwrBKbP7GzTOuIhtiFZMi';

    private static $assistant_id_ce_cg_mp = 'asst_NRyScHbWS5rBZlW3LZ7BjpBx';


    private static $assistant_id = 'asst_OfFhkHs7XCVtvFHgefkRBU2p';

    private static function initToken()
    {
        if (!self::$private_token) {
            self::$private_token = config('services.openai.api_key');
            self::$token = self::$private_token; // Asignar también a $token
            self::$mcp_base_url = config('services.mcp.base_url') ?: env('MCP_BASE_URL', 'https://conalcaia.conalca.com.co/mcp/');
            
            if (!self::$private_token) {
                Log::error('OpenAI API key no configurado en services.openai.api_key');
                throw new \Exception('OpenAI API key no disponible');
            }
            
            Log::info('Initializing OpenAI token', [
                'token_length' => strlen(self::$private_token),
                'token_prefix' => substr(self::$private_token, 0, 10),
                'mcp_url' => self::$mcp_base_url
            ]);
        }
    }

    // Create a thread 'conversation' in open AI with this client
    public static function getThread(Client $client)
    {
        self::initToken();
        
        if ($client->openai_thread_id) {
            return $client->openai_thread_id;
        }

        try {
            $request = Http::timeout(10)
                ->withHeaders([
                    'OpenAI-Beta' => 'assistants=v2'
                ])
                ->withToken(self::$private_token)
                ->post(self::$openai_uri . '/threads');

            if ($request->status() == 200 && $request->json()['object'] == 'thread') {
                $client->openai_thread_id = $request->json()['id'];
                $client->save();

                return $client->openai_thread_id;
            }
            
            Log::error('Failed to create thread', [
                'status' => $request->status(),
                'response' => $request->json()
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('OpenAI connection error - falling back to local thread ID', [
                'error' => $e->getMessage(),
                'client_id' => $client->id
            ]);
            
            // Crear un thread ID local temporal cuando OpenAI no esté disponible
            $fallbackThreadId = 'local_thread_' . $client->id . '_' . time();
            $client->openai_thread_id = $fallbackThreadId;
            $client->save();
            
            return $fallbackThreadId;
        }
    }

    /**
     * Eliminar un thread de OpenAI
     * Se usa cuando se inicia una nueva cotización para limpiar el historial
     */
    /**
     * Cancela todos los runs activos de un thread antes de eliminarlo
     */
    public static function cancelActiveRuns($thread_id)
    {
        self::initToken();
        
        if (str_starts_with($thread_id, 'local_thread_')) {
            return true;
        }
        
        try {
            Log::info('🔍 Buscando runs activos para cancelar:', ['thread_id' => $thread_id]);
            
            $runsRequest = Http::timeout(10)
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->withToken(self::$private_token)
                ->get(self::$openai_uri . '/threads/' . $thread_id . '/runs');
            
            if ($runsRequest->status() == 404) {
                Log::info('Thread no existe, no hay runs para cancelar');
                return true;
            }
            
            if ($runsRequest->status() == 200) {
                $runs = $runsRequest->json();
                $activeRuns = array_filter($runs['data'] ?? [], function($run) {
                    return in_array($run['status'], ['queued', 'in_progress', 'requires_action']);
                });
                
                if (empty($activeRuns)) {
                    Log::info('✅ No hay runs activos para cancelar');
                    return true;
                }
                
                Log::info('⚠️ Se encontraron ' . count($activeRuns) . ' runs activos, cancelando...');
                
                foreach ($activeRuns as $run) {
                    try {
                        $cancelRequest = Http::timeout(10)
                            ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                            ->withToken(self::$private_token)
                            ->post(self::$openai_uri . '/threads/' . $thread_id . '/runs/' . $run['id'] . '/cancel');
                        
                        if ($cancelRequest->status() == 200) {
                            Log::info('✅ Run cancelado:', ['run_id' => $run['id']]);
                        } else {
                            Log::warning('⚠️ No se pudo cancelar run:', [
                                'run_id' => $run['id'],
                                'status' => $cancelRequest->status()
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Error cancelando run individual:', [
                            'run_id' => $run['id'],
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Error listando runs para cancelar:', [
                'error' => $e->getMessage(),
                'thread_id' => $thread_id
            ]);
            return false;
        }
    }
    
    public static function deleteThread($thread_id)
    {
        self::initToken();
        
        // Si es un thread local temporal, no intentar eliminarlo de OpenAI
        if (str_starts_with($thread_id, 'local_thread_')) {
            Log::info('Thread local detectado, omitiendo eliminación en OpenAI');
            return true;
        }
        
        Log::info('Eliminando thread de OpenAI:', ['thread_id' => $thread_id]);
        
        // PRIMERO cancelar todos los runs activos
        self::cancelActiveRuns($thread_id);
        
        try {
            $request = Http::timeout(10)
                ->withHeaders([
                    'OpenAI-Beta' => 'assistants=v2'
                ])
                ->withToken(self::$private_token)
                ->delete(self::$openai_uri . '/threads/' . $thread_id);

            if ($request->status() == 200) {
                Log::info('✅ Thread eliminado exitosamente', ['thread_id' => $thread_id]);
                return true;
            }
            
            Log::warning('No se pudo eliminar thread', [
                'status' => $request->status(),
                'response' => $request->json(),
                'thread_id' => $thread_id
            ]);
            
            return false;
        } catch (\Exception $e) {
            Log::error('Error eliminando thread de OpenAI', [
                'error' => $e->getMessage(),
                'thread_id' => $thread_id
            ]);
            throw $e;
        }
    }

    public static function getMessages($thread_id)
    {
        self::initToken();
        
        // Si es un thread local temporal, retornar array vacío
        if (str_starts_with($thread_id, 'local_thread_')) {
            return [];
        }
        
        try {
            $request = Http::timeout(10)
                ->withHeaders([
                    'OpenAI-Beta' => 'assistants=v2'
                ])
                ->withToken(self::$private_token)
                ->get(self::$openai_uri . '/threads/' . $thread_id . '/messages?order=asc&limit=50');

            $messages = [];
            if ($request->status() == 200) {
                $payload = $request->json('data');
                Log::info("New messages: ", $payload);

                foreach ($payload as $message) {
                    if (isset($message['content'][0]['text']['value'])) {
                        $messages[] = [
                            'id' => $message['id'],
                            'text' => $message['content'][0]['text']['value'],
                            'created_at' => Carbon::parse($message['created_at']),
                            'role' => $message['role'],
                        ];
                    }
                }
            }

            return $messages;
        } catch (\Exception $e) {
            Log::error('Error getting messages from OpenAI', [
                'error' => $e->getMessage(),
                'thread_id' => $thread_id
            ]);
            return [];
        }
    }

    public static function createMessage($thread_id, $text)
    {
        self::initToken();
        
        // Si es un thread local temporal, no intentar crear mensaje en OpenAI
        if (str_starts_with($thread_id, 'local_thread_')) {
            Log::info('Thread local detectado, omitiendo creación de mensaje en OpenAI');
            return [
                'id' => 'local_msg_' . time(),
                'text' => $text,
                'created_at' => Carbon::now(),
                'role' => 'user',
            ];
        }
        
        Log::info('Creando mensaje en OpenAI:', [
            'thread_id' => $thread_id,
            'message_length' => strlen($text),
            'token_available' => !empty(self::$private_token)
        ]);
        
        try {
            // PRIMERO: Verificar si hay runs activos y cancelarlos automáticamente
            $runsRequest = Http::timeout(20)
                ->retry(3, 1000)
                ->withHeaders([
                    'OpenAI-Beta' => 'assistants=v2'
                ])
                ->withToken(self::$private_token)
                ->get(self::$openai_uri . '/threads/' . $thread_id . '/runs');
            
            if ($runsRequest->status() == 404) {
                // El thread no existe, retornar null con código especial
                Log::warning('Thread no encontrado en OpenAI (404):', [
                    'thread_id' => $thread_id
                ]);
                return 'THREAD_NOT_FOUND';
            }
            
            if ($runsRequest->status() == 200) {
                $runs = $runsRequest->json();
                $activeRuns = array_filter($runs['data'], function($run) {
                    return in_array($run['status'], ['queued', 'in_progress', 'requires_action']);
                });
                
                if (!empty($activeRuns)) {
                    Log::warning('⚠️ Se encontraron runs activos, cancelándolos automáticamente...', [
                        'thread_id' => $thread_id,
                        'active_runs' => count($activeRuns)
                    ]);
                    
                    // Cancelar cada run activo
                    foreach ($activeRuns as $run) {
                        try {
                            $cancelRequest = Http::timeout(10)
                                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                                ->withToken(self::$private_token)
                                ->post(self::$openai_uri . '/threads/' . $thread_id . '/runs/' . $run['id'] . '/cancel');
                            
                            if ($cancelRequest->status() == 200) {
                                Log::info('✅ Run cancelado antes de crear mensaje:', ['run_id' => $run['id']]);
                            }
                        } catch (\Exception $e) {
                            Log::warning('Error cancelando run:', ['run_id' => $run['id'], 'error' => $e->getMessage()]);
                        }
                    }
                    
                    // Esperar 1 segundo para que las cancelaciones surtan efecto
                    sleep(1);
                }
            }
            
            // SEGUNDO: Ahora sí crear el mensaje
            $request = Http::timeout(20)
                ->retry(3, 1000)
                ->withHeaders([
                    'OpenAI-Beta' => 'assistants=v2'
                ])
                ->withToken(self::$private_token)
                ->post(self::$openai_uri . '/threads/' . $thread_id . '/messages', [
                    'role' => 'user',
                    'content' => $text
                ]);

            if ($request->status() == 200) {
                $message = $request->json();
                Log::info('Mensaje creado exitosamente:', ['message_id' => $message['id']]);
                return [
                    'id' => $message['id'],
                    'text' => $message['content'][0]['text']['value'],
                    'created_at' => Carbon::parse($message['created_at']),
                    'role' => $message['role'],
                ];
            } else {
                Log::error('Error al crear mensaje en OpenAI:', [
                    'status' => $request->status(),
                    'response' => $request->json()
                ]);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Exception creating message in OpenAI:', [
                'error' => $e->getMessage(),
                'thread_id' => $thread_id
            ]);
            return null;
        }
    }

    public static function runAssistant($thread_id, $type_business)
    {
        self::initToken();
        
        // Si es un thread local temporal, no intentar ejecutar asistente
        if (str_starts_with($thread_id, 'local_thread_')) {
            Log::info('Thread local detectado, omitiendo ejecución de asistente');
            return [
                'id' => 'local_run_' . time()
            ];
        }
        
        Log::info('Ejecutando asistente:', [
            'thread_id' => $thread_id,
            'type_business' => $type_business
        ]);

        if($type_business == 'dta' || $type_business == 'otm'){
            $assistant_id = self::$assistant_id_dta_otm;
        }else if($type_business == 'refri'){
            $assistant_id = self::$assistant_id_refri;
        }else if($type_business == 'nacionalizado'){
            $assistant_id = self::$assistant_id_dta_otm; // Usar el mismo asistente que DTA/OTM por ahora
        }else if($type_business == 'impo' || $type_business == 'expo'){
            $assistant_id = self::$assistant_id_impo_expo;
        }else if($type_business == 'distri'){
            $assistant_id = self::$assistant_id_distri;
        }else if($type_business == 'ce' || $type_business == 'cg' || $type_business == 'mp'){
            $assistant_id = self::$assistant_id_ce_cg_mp;
        } else {
            $assistant_id = self::$assistant_id;
        }

        Log::info('Usando asistente:', ['assistant_id' => $assistant_id]);

        try {
            $request = Http::timeout(30)  // Aumentado a 30 segundos
                ->retry(2, 2000)  // Solo 2 reintentos con 2 segundos
                ->withHeaders([
                    'OpenAI-Beta' => 'assistants=v2'
                ])
                ->withToken(self::$private_token)
                ->post(self::$openai_uri . '/threads/' . $thread_id . '/runs', [
                    'assistant_id' => $assistant_id
                ]);

            if ($request->status() == 200) {
                $message = $request->json();
                Log::info("Run assistant creado exitosamente:", [
                    'run_id' => $message['id'] ?? 'no_id',
                    'status' => $message['status'] ?? 'unknown'
                ]);
                if (isset($message['id'])) {
                    return [
                        'id' => $message['id']
                    ];
                } else if (isset($message['content'][0]['text']['value'])) {
                    return [
                        'id' => $message['id'],
                        'text' => $message['content'][0]['text']['value'],
                        'created_at' => Carbon::parse($message['created_at']),
                        'role' => $message['role'],
                    ];
                }
            } else {
                Log::error('Error al ejecutar asistente - HTTP Status:', [
                    'status' => $request->status(),
                    'response' => $request->json(),
                    'thread_id' => $thread_id,
                    'assistant_id' => $assistant_id
                ]);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Exception running assistant:', [
                'error' => $e->getMessage(),
                'thread_id' => $thread_id,
                'type_business' => $type_business,
                'error_type' => get_class($e)
            ]);
            return null;
        }
    }



    /**
     * Extrae datos de cotización del texto de respuesta del asistente
     */
    private static function extractQuoteDataFromText($messages)
    {
        Log::info('Intentando extraer datos de cotización del texto...');
        
        $extractedData = [];
        
        foreach ($messages as $message) {
            if ($message['role'] === 'assistant') {
                $text = $message['text'];
                Log::info('Analizando mensaje del asistente:', [
                    'text_length' => strlen($text),
                    'text_preview' => substr($text, 0, 200)
                ]);
                
                // Verificar si el asistente dice que las cotizaciones están completadas/creadas
                $completionIndicators = [
                    'cotizaciones.*han sido creadas exitosamente',
                    'cotizaciones.*fueron creadas',
                    'cotizaciones.*ya fueron creadas',
                    'cotizaciones.*están listas',
                    'cotizaciones.*completada.*exitosamente',
                    'solicitud.*completada exitosamente'
                ];
                
                $isCompleted = false;
                foreach ($completionIndicators as $pattern) {
                    if (preg_match('/' . $pattern . '/i', $text)) {
                        $isCompleted = true;
                        Log::info('Detectado indicador de finalización:', ['pattern' => $pattern]);
                        break;
                    }
                }
                
                // Si el asistente indica que completó, extraer los datos estructurados
                if ($isCompleted) {
                    Log::info('Extrayendo datos estructurados de cotización completada...');
                    
                    // Buscar bloques de rutas estructuradas
                    if (preg_match_all('/(\d+)\.\s*Ruta:\s*(\w+)\s*a\s*(\w+)(.+?)(?=\d+\.\s*Ruta:|$)/si', $text, $routeBlocks, PREG_SET_ORDER)) {
                        Log::info('Bloques de rutas encontrados:', ['count' => count($routeBlocks)]);
                        
                        foreach ($routeBlocks as $block) {
                            $routeNumber = $block[1];
                            $origen = trim($block[2]);
                            $destino = trim($block[3]);
                            $details = $block[4];
                            
                            Log::info('Procesando ruta ' . $routeNumber, ['origen' => $origen, 'destino' => $destino]);
                            
                            $routeData = [
                                'ciudad_origen' => ucfirst(strtolower($origen)),
                                'ciudad_destino' => ucfirst(strtolower($destino)),
                                'peso_mercancia' => '2000', // Valor por defecto basado en "2 toneladas"
                                'cantidad' => '1',
                                'tipo_embajale' => 'Bultos',
                                'tipo_producto' => 'Maiz',
                                'vehiculo_requerido' => 'Camión sencillo',
                                'valor_declarado' => '10,000,000'
                            ];
                            
                            // Extraer datos específicos del bloque de detalles
                            if (preg_match('/Peso:\s*(\d+)\s*kg/i', $details, $pesoMatch)) {
                                $routeData['peso_mercancia'] = $pesoMatch[1];
                            }
                            
                            if (preg_match('/Cantidad:\s*(\d+)/i', $details, $cantMatch)) {
                                $routeData['cantidad'] = $cantMatch[1];
                            }
                            
                            if (preg_match('/Tipo\s+embalaje:\s*([^\-\n]+)/i', $details, $embMatch)) {
                                $routeData['tipo_embajale'] = trim($embMatch[1]);
                            }
                            
                            if (preg_match('/Tipo\s+producto:\s*([^\-\n]+)/i', $details, $prodMatch)) {
                                $routeData['tipo_producto'] = trim($prodMatch[1]);
                            }
                            
                            if (preg_match('/Vehículo\s+requerido:\s*([^\-\n]+)/i', $details, $vehMatch)) {
                                $routeData['vehiculo_requerido'] = trim($vehMatch[1]);
                            }
                            
                            if (preg_match('/Valor\s+y?\s*valor\s+declarado:\s*([0-9,\.]+)/i', $details, $valMatch)) {
                                $routeData['valor_declarado'] = trim($valMatch[1]);
                            }
                            
                            $extractedData[] = $routeData;
                            Log::info('Ruta extraída de bloque estructurado:', $routeData);
                        }
                    }
                    
                    // Si no encontramos bloques estructurados, buscar patrones más simples
                    if (empty($extractedData)) {
                        Log::info('No se encontraron bloques estructurados, buscando patrones simples...');
                        
                        // Buscar las rutas básicas mencionadas en la respuesta
                        if (preg_match_all('/(\w+)\s*a\s*(\w+).*?(\d+)\s*toneladas?\s*de\s*(\w+)/i', $text, $simpleMatches, PREG_SET_ORDER)) {
                            foreach ($simpleMatches as $match) {
                                $routeData = [
                                    'ciudad_origen' => ucfirst(strtolower(trim($match[1]))),
                                    'ciudad_destino' => ucfirst(strtolower(trim($match[2]))),
                                    'peso_mercancia' => (intval($match[3]) * 1000), // convertir toneladas a kg
                                    'cantidad' => '1',
                                    'tipo_embajale' => 'Bultos',
                                    'tipo_producto' => ucfirst(strtolower(trim($match[4]))),
                                    'vehiculo_requerido' => 'Camión sencillo',
                                    'valor_declarado' => '10,000,000'
                                ];
                                $extractedData[] = $routeData;
                                Log::info('Ruta extraída con patrón simple:', $routeData);
                            }
                        }
                    }
                }
                
                // Patrón simplificado para extraer rutas (fallback para compatibilidad)
                if (empty($extractedData)) {
                    // Busca: "CIUDAD a CIUDAD, X toneladas (Y kg) de PRODUCTO"
                    if (preg_match_all('/(\w+)\s*(?:\([^)]+\))?\s*a\s*(\w+)\s*(?:\([^)]+\))?,\s*(\d+)\s*toneladas?\s*\((\d+)\s*kg\)\s*de\s*(\w+)/i', $text, $matches, PREG_SET_ORDER)) {
                        Log::info('Rutas encontradas con regex clásico:', ['count' => count($matches)]);
                        
                        foreach ($matches as $match) {
                            $routeData = [
                                'ciudad_origen' => ucfirst(strtolower(trim($match[1]))),
                                'ciudad_destino' => ucfirst(strtolower(trim($match[2]))),
                                'peso_mercancia' => trim($match[4]),
                                'cantidad' => trim($match[3]) . ' toneladas',
                                'tipo_embajale' => 'Bultos', // Valor por defecto
                                'tipo_producto' => ucfirst(strtolower(trim($match[5]))),
                                'vehiculo_requerido' => 'Camión sencillo',
                                'valor_declarado' => '10,000,000'
                            ];
                            $extractedData[] = $routeData;
                            Log::info('Ruta extraída con regex clásico:', $routeData);
                        }
                    }
                }
                
                // Si aún no encontramos datos, intentar extraer manualmente línea por línea
                if (empty($extractedData)) {
                    Log::info('Intentando extracción manual línea por línea...');
                    $lines = explode("\n", $text);
                    
                    foreach ($lines as $line) {
                        // Buscar líneas que empiecen con número (1., 2., etc.) o contengan rutas
                        if (preg_match('/^\d+\.\s*(.+)/i', trim($line), $lineMatch) || 
                            preg_match('/(\w+)\s+a\s+(\w+)/i', trim($line), $lineMatch)) {
                            
                            $routeLine = isset($lineMatch[1]) ? $lineMatch[1] : $line;
                            Log::info('Línea de ruta encontrada:', ['line' => $routeLine]);
                            
                            // Extraer datos específicos de esta línea
                            $routeData = [
                                'ciudad_origen' => '',
                                'ciudad_destino' => '',
                                'peso_mercancia' => '2000', // Valor por defecto
                                'cantidad' => '1',
                                'tipo_embajale' => 'Bultos',
                                'tipo_producto' => 'Maiz',
                                'vehiculo_requerido' => 'Camión sencillo',
                                'valor_declarado' => '10,000,000'
                            ];
                            
                            // Buscar origen y destino
                            if (preg_match('/(\w+)\s+(?:\([^)]+\))?\s+a\s+(\w+)/i', $routeLine, $cityMatch)) {
                                $routeData['ciudad_origen'] = ucfirst(strtolower(trim($cityMatch[1])));
                                $routeData['ciudad_destino'] = ucfirst(strtolower(trim($cityMatch[2])));
                            }
                            
                            // Buscar peso
                            if (preg_match('/(\d+)\s*kg/i', $routeLine, $weightMatch)) {
                                $routeData['peso_mercancia'] = $weightMatch[1];
                            }
                            
                            // Buscar toneladas
                            if (preg_match('/(\d+)\s*toneladas?/i', $routeLine, $tonMatch)) {
                                $routeData['peso_mercancia'] = (intval($tonMatch[1]) * 1000); // convertir a kg
                            }
                            
                            // Buscar producto
                            if (preg_match('/de\s+(\w+)/i', $routeLine, $productMatch)) {
                                $routeData['tipo_producto'] = ucfirst(strtolower(trim($productMatch[1])));
                            }
                            
                            // Solo agregar si al menos tenemos origen y destino
                            if (!empty($routeData['ciudad_origen']) && !empty($routeData['ciudad_destino'])) {
                                $extractedData[] = $routeData;
                                Log::info('Ruta extraída manualmente:', $routeData);
                            }
                        }
                    }
                }
            }
        }
        
        if (count($extractedData) > 0) {
            Log::info('Datos de cotización extraídos exitosamente:', ['count' => count($extractedData), 'data' => $extractedData]);
            return $extractedData;
        }
        
        Log::info('No se encontraron datos de cotización en el texto');
        return null;
    }

    public static function checkRunStatus($thread_id, $run_id)
    {
        self::initToken();
        
        // Si es un thread/run local temporal, retornar finished
        if (str_starts_with($thread_id, 'local_thread_') || str_starts_with($run_id, 'local_run_')) {
            Log::info('Thread/run local detectado, retornando finished');
            return 'finished';
        }
        
        // Verificar caché para evitar reprocesamiento
        $cacheKey = "run_status_{$thread_id}_{$run_id}";
        $cachedResult = Cache::get($cacheKey);
        if ($cachedResult && in_array($cachedResult['status'], ['completed_with_data', 'finished_with_indication'])) {
            Log::info('Resultado en caché encontrado:', ['status' => $cachedResult['status']]);
            return $cachedResult['data'] ?? $cachedResult['status'];
        }
        
        try {
            $request = Http::timeout(10)
                ->withHeaders([
                    'OpenAI-Beta' => 'assistants=v2'
                ])
                ->withToken(self::$private_token)
                ->get(self::$openai_uri . '/threads/' . $thread_id . '/runs/' . $run_id);

            if ($request->status() == 200) {
                $message = $request->json();
                Log::info("Current run status: ", [$message]);
                
                if (isset($message['status']) && $message['status'] == 'requires_action' && isset($message['required_action']['type']) && $message['required_action']['type'] == 'submit_tool_outputs') {
                    Log::info("Tool calls: ", $message['required_action']['submit_tool_outputs']['tool_calls']);
                    $tool_calls = $message['required_action']['submit_tool_outputs']['tool_calls'];
                    $tool_outputs = [];
                    $all_data = [];
                    $extractedData = []; // 🆕 Datos extraídos de herramientas
                    
                    foreach ($tool_calls as $tool_call) {
                        $call_id = $tool_call['id'];
                        $function_name = $tool_call['function']['name'] ?? 'unknown';
                        $arguments = json_decode($tool_call['function']['arguments'], true);
                        
                        // LOG DETALLADO para debug
                        Log::info('Tool call procesado:', [
                            'call_id' => $call_id,
                            'function_name' => $function_name,
                            'arguments_raw' => $tool_call['function']['arguments'],
                            'arguments_decoded' => $arguments,
                            'tiene_tipo_producto' => isset($arguments['tipo_producto']) ? 'SI' : 'NO',
                            'tiene_producto' => isset($arguments['producto']) ? 'SI' : 'NO',
                            'keys_disponibles' => is_array($arguments) ? array_keys($arguments) : []
                        ]);
                        
                        // 🆕 EJECUTAR HERRAMIENTAS REALES VÍA MCP
                        $toolResult = self::executeTool($function_name, $arguments);
                        
                        // 🆕 Procesar resultado de search_products
                        if ($function_name === 'search_products') {
                            $searchTerm = $arguments['search_term'] ?? $arguments['nombre'] ?? '';
                            if (isset($toolResult['productos']) && count($toolResult['productos']) > 0) {
                                $productos = $toolResult['productos'];
                                if (count($productos) === 1) {
                                    $extractedData['producto'] = $productos[0]['nombre'];
                                    $extractedData['producto_codigo'] = $productos[0]['codigo'] ?? null;
                                    Log::info('✅ Producto encontrado en BD:', ['producto' => $productos[0]['nombre']]);
                                } else {
                                    // Múltiples opciones, usar el término de búsqueda
                                    $extractedData['producto'] = strtoupper($searchTerm);
                                    Log::info('📋 Múltiples productos, usando término:', ['producto' => $searchTerm]);
                                }
                            } else {
                                // 🆕 Producto NO encontrado - guardarlo como PERSONALIZADO
                                $extractedData['producto'] = strtoupper($searchTerm);
                                $extractedData['producto_codigo'] = 'PERSONALIZADO';
                                Log::info('⚠️ Producto no en BD, marcado como PERSONALIZADO:', ['producto' => $searchTerm]);
                            }
                        }
                        
                        // 🆕 Procesar resultado de get_empaques
                        if ($function_name === 'get_empaques') {
                            if (isset($toolResult['empaques']) && count($toolResult['empaques']) > 0) {
                                $empaque = $toolResult['empaques'][0];
                                $extractedData['empaque'] = $empaque['nome'] ?? $empaque['nombre'] ?? null;
                                $extractedData['empaque_id'] = $empaque['id'] ?? null;
                                Log::info('✅ Empaque encontrado:', ['empaque' => $extractedData['empaque']]);
                            }
                        }
                        
                        // NORMALIZAR: Si viene 'producto' en lugar de 'tipo_producto', copiarlo
                        if (isset($arguments['producto']) && !isset($arguments['tipo_producto'])) {
                            $arguments['tipo_producto'] = $arguments['producto'];
                            Log::info('Campo producto normalizado a tipo_producto:', ['valor' => $arguments['producto']]);
                        }
                        
                        // NORMALIZAR: Si viene 'product' en lugar de 'tipo_producto', copiarlo
                        if (isset($arguments['product']) && !isset($arguments['tipo_producto'])) {
                            $arguments['tipo_producto'] = $arguments['product'];
                            Log::info('Campo product normalizado a tipo_producto:', ['valor' => $arguments['product']]);
                        }
                        
                        // Combinar argumentos con datos extraídos
                        $mergedData = array_merge($arguments, $extractedData);
                        
                        $tool_outputs[] = [
                            'tool_call_id' => $call_id,
                            'output' => json_encode($toolResult) // 🆕 Enviar resultado real de la herramienta
                        ];
                        $all_data[] = $mergedData;
                    }
                    
                    $submitRequest = Http::timeout(10)
                        ->withHeaders([
                            'OpenAI-Beta' => 'assistants=v2'
                        ])
                        ->withToken(self::$private_token)
                        ->post(self::$openai_uri . '/threads/' . $thread_id . '/runs/' . $run_id . '/submit_tool_outputs', [
                            'tool_outputs' => $tool_outputs
                        ]);
                    
                    Log::info("Submit tool outputs: ", $submitRequest->json());

                    // Guardar datos extraídos en caché
                    Cache::put($cacheKey, ['status' => 'completed_with_data', 'data' => $all_data], 3600);

                    return $all_data; // array de rutas
                } else if (isset($message['status']) && $message['status'] == 'completed') {
                    // El run está completado, intentar extraer datos del texto
                    Log::info('Run completado, intentando extraer datos del texto...');
                    $messages = self::getMessages($thread_id);
                    $extractedData = self::extractQuoteDataFromText($messages);
                    
                    if ($extractedData && count($extractedData) > 0) {
                        Log::info('Datos extraídos del texto:', $extractedData);
                        // Guardar en caché
                        Cache::put($cacheKey, ['status' => 'completed_with_data', 'data' => $extractedData], 3600);
                        return $extractedData;
                    }
                    
                    // Verificar si el asistente indica que las cotizaciones están completadas
                    $lastMessage = '';
                    if (is_array($messages) && count($messages) > 0) {
                        foreach ($messages as $msg) {
                            if (isset($msg['role']) && $msg['role'] === 'assistant') {
                                $lastMessage = $msg['text'] ?? '';
                                break;
                            }
                        }
                    }
                    
                    $completionIndicators = [
                        'cotizaciones.*han sido creadas exitosamente',
                        'cotizaciones.*fueron creadas',
                        'cotizaciones.*ya fueron creadas', 
                        'cotizaciones.*están listas',
                        'cotizaciones.*completada.*exitosamente',
                        'solicitud.*completada exitosamente',
                        'proceso.*completado.*éxito'
                    ];
                    
                    foreach ($completionIndicators as $pattern) {
                        if (preg_match('/' . $pattern . '/i', $lastMessage)) {
                            Log::info('Asistente indica que las cotizaciones están completadas:', ['pattern' => $pattern]);
                            // Guardar en caché para evitar repetir polling
                            Cache::put($cacheKey, ['status' => 'finished_with_indication'], 3600);
                            return 'finished_with_indication';
                        }
                    }
                    
                    // Guardar estado finished en caché
                    Cache::put($cacheKey, ['status' => 'finished'], 3600);
                    return 'finished';
                } else if (isset($message['status'])) {
                    // Retornar el status actual para otros estados (queued, in_progress, failed, etc.)
                    Log::info('Run en estado:', ['status' => $message['status']]);
                    return $message['status'];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Exception checking run status:', [
                'error' => $e->getMessage(),
                'thread_id' => $thread_id,
                'run_id' => $run_id
            ]);
            return null;
        }
    }
    
    /**
     * 🆕 ESPERAR RUN Y EXTRAER DATOS
     * Hace polling hasta que el run termine y retorna los datos extraídos
     */
    public static function waitForRunAndExtractData($thread_id, $run_id, $timeoutSeconds = 60)
    {
        $startTime = time();
        $pollInterval = 2; // segundos entre cada verificación
        $extractedData = [];
        
        Log::info('⏳ Iniciando polling de run', [
            'thread_id' => $thread_id,
            'run_id' => $run_id,
            'timeout' => $timeoutSeconds
        ]);
        
        while ((time() - $startTime) < $timeoutSeconds) {
            $result = self::checkRunStatus($thread_id, $run_id);
            
            Log::info('🔄 Resultado de checkRunStatus', [
                'result_type' => gettype($result),
                'is_array' => is_array($result),
                'result' => is_array($result) ? 'array with ' . count($result) . ' items' : $result
            ]);
            
            // Si el resultado es un array, son los datos extraídos
            if (is_array($result) && count($result) > 0) {
                Log::info('✅ Datos extraídos del run', [
                    'count' => count($result)
                ]);
                return $result;
            }
            
            // Si el status es completed o finished, intentar extraer de mensajes
            if ($result === 'completed' || $result === 'finished' || $result === 'finished_with_indication') {
                Log::info('🏁 Run completado, intentando extraer datos de mensajes');
                $messages = self::getMessages($thread_id);
                $extractedFromText = self::extractQuoteDataFromText($messages);
                
                if (!empty($extractedFromText)) {
                    return $extractedFromText;
                }
                
                // No hay datos pero el run terminó
                return [];
            }
            
            // Si falló, retornar vacío
            if ($result === 'failed' || $result === 'cancelled' || $result === 'expired') {
                Log::warning('⚠️ Run terminó con error', ['status' => $result]);
                return [];
            }
            
            // Esperar antes del siguiente poll
            sleep($pollInterval);
        }
        
        Log::warning('⏰ Timeout esperando run', [
            'thread_id' => $thread_id,
            'run_id' => $run_id
        ]);
        
        return [];
    }

    /**
     * Procesa mensajes directamente con OpenAI usando function calling
     */
    public function processMessages($messages)
    {
        self::initToken();
        
        Log::info('Procesando mensajes con OpenAI:', [
            'messages_count' => count($messages)
        ]);

        // Definir el prompt del sistema para cotizaciones propositivas
        $systemPrompt = 'Eres un asistente de cotizaciones PROPOSITIVO y EFICIENTE especializado en transporte en Colombia. 

PRINCIPIOS CLAVE:
1. NO hagas preguntas de confirmación ("¿está seguro?", "¿quiere que...?")
2. ASUME parámetros razonables cuando la información sea parcial
3. PROPÓN acciones automáticas basadas en la información disponible
4. SÉ RESOLUTIVO - responde con soluciones, no con dudas

INFORMACIÓN DE CONTEXTO DISPONIBLE:
- Los parámetros automáticos (Candado Satelital, Jen Set, Combustible, Kit derrames, Pictogramas) ya están configurados según el tipo de servicio
- El cliente y tipo de negocio ya están vinculados
- El sistema está optimizado para cotizaciones rápidas

COMPORTAMIENTO ESPERADO:
- Si mencionan una ciudad, asume que es origen/destino según el contexto
- Si mencionan peso sin unidad, asume kilogramos
- Si falta información secundaria, usa valores estándar de la industria
- Propón interpretaciones automáticas de información ambigua
- Evita repreguntas - mejor asumir y aclarar después si es necesario

CAMPOS DISPONIBLES:
1. ciudad_origen: Ciudad de origen del envío
2. ciudad_destino: Ciudad de destino del envío  
3. peso_mercancia: Peso en kilogramos
4. cantidad: Cantidad de unidades/bultos (IMPORTANTE: si el usuario dice "18 pallets", la cantidad es 18; si dice "210 cajas", la cantidad es 210. NUNCA poner 1 cuando el usuario especifica un número de unidades)
5. tipo_embajale: Tipo de embalaje (caja, pallet, etc.)
6. tipo_producto: Tipo de producto/mercancía
7. vehiculo_requerido: Tipo de vehículo necesario
8. valor_declarado: Valor declarado de la mercancía

RESPUESTAS INTELIGENTES:
- "Perfecto, configurando envío de [origen] a [destino]..."
- "Entendido, procesando [peso]kg desde [ciudad]..."
- "Basándome en tu solicitud, sugiero..."
- "Detecté que necesitas [servicio], aplicando configuración automática..."

USA la función "extract_quote_data" INMEDIATAMENTE cuando identifiques información de envío.';

        // Definir las funciones disponibles para extracción de datos
        $functions = [
            [
                'name' => 'extract_quote_data',
                'description' => 'Extrae y estructura datos de cotización del mensaje del usuario',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'ciudad_origen' => [
                            'type' => 'string',
                            'description' => 'Ciudad de origen del envío'
                        ],
                        'ciudad_destino' => [
                            'type' => 'string', 
                            'description' => 'Ciudad de destino del envío'
                        ],
                        'peso_mercancia' => [
                            'type' => 'string',
                            'description' => 'Peso de la mercancía (incluir unidad si está disponible)'
                        ],
                        'cantidad' => [
                            'type' => 'string',
                            'description' => 'Cantidad de unidades o bultos'
                        ],
                        'tipo_embajale' => [
                            'type' => 'string',
                            'description' => 'Tipo de embalaje (caja, pallet, bulto, etc.)'
                        ],
                        'tipo_producto' => [
                            'type' => 'string',
                            'description' => 'Tipo de producto o mercancía'
                        ],
                        'vehiculo_requerido' => [
                            'type' => 'string',
                            'description' => 'Tipo de vehículo requerido'
                        ],
                        'valor_declarado' => [
                            'type' => 'string',
                            'description' => 'Valor declarado de la mercancía'
                        ]
                    ],
                    'required' => []
                ]
            ]
        ];

        // Agregar prompt del sistema si no existe
        $hasSystemPrompt = collect($messages)->where('role', 'system')->isNotEmpty();
        if (!$hasSystemPrompt) {
            array_unshift($messages, [
                'role' => 'system',
                'content' => $systemPrompt
            ]);
        }

        try {
            $response = Http::withToken(self::$token)
                ->timeout(10)
                ->retry(2, 100)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => $messages,
                    'functions' => $functions,
                    'function_call' => 'auto',
                    'max_tokens' => 800,
                    'temperature' => 0, // Determinista para extracción precisa de datos
                    'seed' => 42, // Seed fijo para mayor consistencia entre llamadas
                    'presence_penalty' => 0,
                    'frequency_penalty' => 0
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Respuesta exitosa de OpenAI:', [
                    'has_function_call' => isset($data['choices'][0]['message']['function_call'])
                ]);

                return $data['choices'][0]['message'];
            } else {
                Log::error('Error en OpenAI API:', [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                
                throw new \Exception('Error al procesar con OpenAI: ' . $response->status());
            }

        } catch (\Exception $e) {
            Log::error('Excepción en processMessages:', [
                'message' => $e->getMessage()
            ]);
            
            // Verificar si es un error de conexión específico
            if (str_contains($e->getMessage(), 'Could not resolve host') || 
                str_contains($e->getMessage(), 'api.openai.com') ||
                str_contains($e->getMessage(), 'cURL error 6')) {
                
                throw new \Exception('Servicio de chat temporalmente no disponible. Verifica tu conexión a internet.');
            }
            
            throw $e;
        }
    }
    
    /**
     * 🆕 EJECUTAR HERRAMIENTA VÍA SERVIDOR MCP
     * Llama al servidor MCP para ejecutar herramientas como search_products, get_empaques, etc.
     */
    private static function executeTool($toolName, $arguments)
    {
        self::initToken();
        
        try {
            Log::info('🔧 Ejecutando herramienta vía MCP', [
                'tool' => $toolName,
                'arguments' => $arguments,
                'mcp_url' => self::$mcp_base_url
            ]);

            // JSON-RPC 2.0 format según protocolo MCP
            $payload = [
                'jsonrpc' => '2.0',
                'id' => uniqid(),
                'method' => 'tools/call',
                'params' => [
                    'name' => $toolName,
                    'arguments' => $arguments ?? []
                ]
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post(self::$mcp_base_url, $payload);

            if (!$response->successful()) {
                Log::warning('⚠️ Error llamando herramienta MCP', [
                    'tool' => $toolName,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return ['error' => 'Error al llamar herramienta MCP', 'status' => $response->status()];
            }

            $data = $response->json();
            
            Log::info('✅ Respuesta MCP recibida', [
                'tool' => $toolName,
                'has_result' => isset($data['result']),
                'response_keys' => array_keys($data)
            ]);

            // Respuesta JSON-RPC 2.0: extraer el texto del content
            if (isset($data['result']['content'][0]['text'])) {
                $textResult = $data['result']['content'][0]['text'];
                $decoded = json_decode($textResult, true);
                return $decoded ?? ['raw' => $textResult];
            }
            
            return $data['result'] ?? $data;

        } catch (\Exception $e) {
            Log::error('❌ Excepción ejecutando herramienta MCP', [
                'tool' => $toolName,
                'error' => $e->getMessage()
            ]);
            return ['error' => $e->getMessage()];
        }
    }
}
