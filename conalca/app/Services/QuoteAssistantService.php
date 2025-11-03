<?php

namespace App\Services;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuoteAssistantService
{
    private static $openai_uri = 'https://api.openai.com/v1';

    private static $private_token = null;
    private static $token = null; // Añadir esta propiedad

    private static $assistant_id_dta_otm = 'asst_s94P42GEEEnXEp2rufhJM0sx';

    private static $assistant_id_refri = 'asst_kvaDaC4TnAEKUrggofkHIquF';

    private static $assistant_id_impo_expo = 'asst_H1Aox8nK3G9fF5E7TtplZxiE';

    private static $assistant_id_distri = 'asst_tf9AwrBKbP7GzTOuIhtiFZMi';

    private static $assistant_id_ce_cg_mp = 'asst_NRyScHbWS5rBZlW3LZ7BjpBx';


    private static $assistant_id = 'asst_MnJ08tJG6NKOjbqFLvsYMqEp';

    private static function initToken()
    {
        if (!self::$private_token) {
            self::$private_token = env('OPENAI_API_KEY');
            self::$token = self::$private_token; // Asignar también a $token
            Log::info('Initializing OpenAI token', [
                'token_length' => strlen(self::$private_token),
                'token_prefix' => substr(self::$private_token, 0, 10)
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

        $request = Http::withHeaders([
            'OpenAI-Beta' => 'assistants=v2'
        ])->withToken(self::$private_token)->post(self::$openai_uri . '/threads');

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
    }

    public static function getMessages($thread_id)
    {
        self::initToken();
        $request = Http::withHeaders([
            'OpenAI-Beta' => 'assistants=v2'
        ])->withToken(self::$private_token)->get(self::$openai_uri . '/threads/' . $thread_id . '/messages?order=asc&limit=50');

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
    }

    public static function createMessage($thread_id, $text)
    {
        self::initToken();
        
        Log::info('Creando mensaje en OpenAI:', [
            'thread_id' => $thread_id,
            'message_length' => strlen($text),
            'token_available' => !empty(self::$private_token)
        ]);
        
        $request = Http::withHeaders([
            'OpenAI-Beta' => 'assistants=v2'
        ])->withToken(self::$private_token)->post(self::$openai_uri . '/threads/' . $thread_id . '/messages', [
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
    }

    public static function runAssistant($thread_id, $type_business)
    {
        self::initToken();
        
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

        $request = Http::withHeaders([
            'OpenAI-Beta' => 'assistants=v2'
        ])->withToken(self::$private_token)->post(self::$openai_uri . '/threads/' . $thread_id . '/runs', [
            'assistant_id' => $assistant_id
        ]);

        if ($request->status() == 200) {
            $message = $request->json();
            Log::info("Run assistant creado:", ['run_id' => $message['id'] ?? 'no_id']);
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
            Log::error('Error al ejecutar asistente:', [
                'status' => $request->status(),
                'response' => $request->json()
            ]);
        }

        return null;
    }



    public static function checkRunStatus($thread_id, $run_id)
    {
        self::initToken();
        $request = Http::withHeaders([
            'OpenAI-Beta' => 'assistants=v2'
        ])->withToken(self::$private_token)->get(self::$openai_uri . '/threads/' . $thread_id . '/runs/' . $run_id);

        if ($request->status() == 200) {
            $message = $request->json();
            Log::info("Current run status: ", [$message]);
            if (isset($message['status']) && $message['status'] == 'requires_action' && isset($message['required_action']['type']) && $message['required_action']['type'] == 'submit_tool_outputs') {
                // $json = $message['required_action']['submit_tool_outputs']['tool_calls'][0]['function']['arguments'];
                // Log::info("Tool call: ", [$json]);
                // // submit tool outputs
                // $request = Http::withHeaders([
                //     'OpenAI-Beta' => 'assistants=v2'
                // ])->withToken(self::$private_token)->post(self::$openai_uri . '/threads/' . $thread_id . '/runs/' . $run_id . '/submit_tool_outputs', [
                //             'tool_outputs' => [
                //                 [
                //                     'tool_call_id' => $message['required_action']['submit_tool_outputs']['tool_calls'][0]['id'],
                //                     'output' => $json
                //                 ]
                //             ]
                //         ]);

                // Log::info("Submit tool outputs: ", $request->json());

                // return json_decode($json, true);
                Log::info("Tool calls: ", $message['required_action']['submit_tool_outputs']['tool_calls']);
                $tool_calls = $message['required_action']['submit_tool_outputs']['tool_calls'];
                $tool_outputs = [];
                $all_data = [];
                foreach ($tool_calls as $tool_call) {
                    $call_id = $tool_call['id'];
                    $arguments = json_decode($tool_call['function']['arguments'], true);
                    $tool_outputs[] = [
                        'tool_call_id' => $call_id,
                        'output' => $arguments // O pon tu procesamiento aquí si hace falta
                    ];
                    $all_data[] = $arguments;
                }
                $request = Http::withHeaders([
                    'OpenAI-Beta' => 'assistants=v2'
                ])->withToken(self::$private_token)
                ->post(self::$openai_uri . '/threads/' . $thread_id . '/runs/' . $run_id . '/submit_tool_outputs', [
                    'tool_outputs' => $tool_outputs
                ]);
                Log::info("Submit tool outputs: ", $request->json());

                return $all_data; // array de rutas
            } else if (isset($message['status']) && $message['status'] == 'completed') {
                return 'finished';
            }


        }

        return null;
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
4. cantidad: Cantidad de unidades/bultos
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
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => $messages,
                    'functions' => $functions,
                    'function_call' => 'auto',
                    'max_tokens' => 800,
                    'temperature' => 0.1, // Más determinista para respuestas propositivas
                    'presence_penalty' => 0.1,
                    'frequency_penalty' => 0.1
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
            
            throw $e;
        }
    }
}
