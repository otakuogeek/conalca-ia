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

        // Definir el prompt del sistema para cotizaciones
        $systemPrompt = 'Eres un asistente especializado en cotizaciones de transporte en Colombia. Tu objetivo es ayudar a completar formularios de cotización extrayendo información del usuario.

        Campos disponibles para rellenar:
        1. origen_codigo: Código de ciudad de origen (número)
        2. origen: Nombre de ciudad de origen  
        3. destino_codigo: Código de ciudad de destino (número)
        4. destino: Nombre de ciudad de destino
        5. fecha_recogida: Fecha de recogida (YYYY-MM-DD)
        6. fecha_entrega: Fecha de entrega (YYYY-MM-DD)
        7. tipo_mercancia: Tipo de mercancía
        8. peso: Peso en kilogramos (número)
        9. volumen: Volumen en metros cúbicos (número)
        10. valor_mercancia: Valor de la mercancía (número)
        11. remitente_codigo: Código de remitente (número)
        12. observaciones: Observaciones adicionales

        Responde de manera clara y usa la función "rellenar_campo" cuando identifiques información específica.';

        // Agregar prompt del sistema si no existe
        $hasSystemPrompt = collect($messages)->where('role', 'system')->isNotEmpty();
        if (!$hasSystemPrompt) {
            array_unshift($messages, [
                'role' => 'system',
                'content' => $systemPrompt
            ]);
        }

        // Definir las funciones disponibles
        $functions = [
            [
                'name' => 'rellenar_campo',
                'description' => 'Rellena un campo específico del formulario de cotización',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'field' => [
                            'type' => 'string',
                            'description' => 'El nombre del campo a rellenar',
                            'enum' => [
                                'origen_codigo', 'origen', 'destino_codigo', 'destino',
                                'fecha_recogida', 'fecha_entrega', 'tipo_mercancia',
                                'peso', 'volumen', 'valor_mercancia', 'remitente_codigo', 'observaciones'
                            ]
                        ],
                        'value' => [
                            'type' => 'string',
                            'description' => 'El valor para rellenar en el campo'
                        ]
                    ],
                    'required' => ['field', 'value']
                ]
            ]
        ];

        try {
            $response = Http::withToken(self::$token)
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => $messages,
                    'functions' => $functions,
                    'function_call' => 'auto',
                    'max_tokens' => 500,
                    'temperature' => 0.3
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
