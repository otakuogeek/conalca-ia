<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;
use Illuminate\Support\Facades\Storage;
use OpenAI\Client as OpenAIClient;
use App\Traits\VoiceGenerationTrait;
use OpenAI;
use Illuminate\Support\Facades\Cache;  

use App\Services\LocalAudioStorage;
use App\Services\CallQueueService;
use App\Services\ElevenLabsCallService;
use App\Models\DriverCallResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Call;
use App\Models\CallDriver;
use App\Models\BlockedDriver;
use App\Http\Controllers\TransportController;
use App\Models\DataColumn;
use App\Models\SolicitudTransporte;
use GuzzleHttp\Client;
use App\Models\CotizacionModel;
use App\Models\VehicleOwnerHolderDriver;
use App\Models\Pricing;
use App\Jobs\CallDriverJob;

class CallController extends Controller
{
    use VoiceGenerationTrait;

    protected $openai;
    protected $audioStorage;
    protected $elevenLabsCallService;

    public function __construct(LocalAudioStorage $audioStorage, ElevenLabsCallService $elevenLabsCallService)
    {
        $this->audioStorage = $audioStorage;
        $this->elevenLabsCallService = $elevenLabsCallService;
        
        Log::info('CallController inicializado con LocalAudioStorage y ElevenLabsCallService');

        // Solo inicializar OpenAI si la clave está configurada
        if (config('services.openai.api_key')) {
            $this->openai = OpenAI::client(config('services.openai.api_key'));
        } else {
            Log::info('OpenAI API Key no configurada');
            $this->openai = null;
        }
    }

    /**
     * Debug endpoint para capturar peticiones del frontend
     */
    public function debugCallDrivers(Request $request)
    {
        Log::info('=== DEBUG CALL-DRIVERS ENDPOINT ===', [
            'timestamp' => now(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'all_data' => $request->all(),
            'raw_content' => $request->getContent(),
            'content_type' => $request->header('Content-Type'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Debug info logged',
            'received_data' => $request->all(),
            'timestamp' => now()
        ]);
    }

    /**
     * Test simple para probar funcionamiento básico
     */
    public function testSimple(Request $request)
    {
        Log::info('TEST SIMPLE METHOD CALLED');
        return response()->json([
            'success' => true,
            'message' => 'Test simple funcionando',
            'data' => $request->all()
        ]);
    }

    /**
     * Obtener el estado de las llamadas para el frontend
     */
    public function getCallStatus($callId)
    {
        try {
            Log::info("Consultando estado de llamadas para call ID: $callId");
            
            // Buscar el Call y su cotización asociada
            $call = Call::find($callId);
            if (!$call) {
                return response()->json(['error' => 'Call not found'], 404);
            }

            // Obtener todas las llamadas de esta cotización
            $callResponses = DriverCallResponse::where('cotizacion_id', $call->quotation_id)
                ->get();

            $totalCalls = $callResponses->count();
            $successfulCalls = $callResponses->where('call_status', 'completed')->count();
            $answeredCalls = $callResponses->where('response_status', 'answered')->count();
            $completedCalls = $callResponses->whereIn('call_status', ['completed', 'failed'])->count();

            // Formatear datos para el frontend con información de reintentos
            $calls = $callResponses->map(function($call) {
                return [
                    'id' => $call->id,
                    'driver_name' => $call->driver_name,
                    'driver_phone' => $call->driver_phone,
                    'call_status' => $call->call_status,
                    'response_status' => $call->response_status,
                    'call_duration' => $call->call_duration,
                    'retry_count' => $call->retry_count ?? 0,
                    // 'twilio_call_sid' => $call->twilio_call_sid, // REMOVED: Twilio disabled
                    'notes' => $call->notes,
                    'created_at' => $call->created_at->format('H:i:s')
                ];
            });

            return response()->json([
                'success' => true,
                'cotizacion_id' => $call->quotation_id,
                'total_calls' => $totalCalls,
                'successful_calls' => $successfulCalls,
                'answered_calls' => $answeredCalls,
                'completed_calls' => $completedCalls,
                'all_completed' => $totalCalls > 0 && $completedCalls === $totalCalls,
                'calls' => $calls
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getCallStatus', [
                'error' => $e->getMessage(),
                'call_id' => $callId
            ]);
            
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Método principal para llamar a conductores con Agente IA integrado
     */
    public function callDrivers(Request $request)
    {
        set_time_limit(300);
        Log::info('------ callDrivers INIT ------');

        try {
            /* ------------------------------------------------------------------
            1. Parámetros básicos
            ------------------------------------------------------------------*/
            $prompt            = $request->input('prompt', '');
            $cotizacionModelId = $request->input('cotizacion_model_id');

            Log::info('Prompt recibido',   ['prompt' => $prompt]);
            Log::info('Cotización ID',     ['cotizacion_id' => $cotizacionModelId]);

            /* ------------------------------------------------------------------
            2. Cotización y tipo de vehículo
            ------------------------------------------------------------------*/
            $cotizacion = CotizacionModel::find($cotizacionModelId);
            if (!$cotizacion) {
                Log::warning("Cotización no encontrada: $cotizacionModelId");
                return response()->json(['error' => 'Cotización no encontrada'], 404);
            }

            $typeVehicle = trim(strtolower($cotizacion->vehiculo_requerido));
            if ($typeVehicle === '') {
                Log::warning("Cotización $cotizacionModelId sin tipo de vehículo");
                return response()->json(['error' => 'Cotización sin tipo de vehículo'], 422);
            }
            Log::info("Tipo de vehículo requerido: $typeVehicle");

            /* ------------------------------------------------------------------
            3. Conductores directo desde la tabla vehicle_owner_holder_driver
            ------------------------------------------------------------------*/
                        $drivers = VehicleOwnerHolderDriver::query()
                ->whereRaw('LOWER(Clasevehiculo) = ?', [$typeVehicle])
                ->select([
                    'id',
                    'Telefonoconductor',
                    'Conductor', 
                    'Clasevehiculo'
                ])
                ->get()
                ->map(function ($d) {
                    // Solo usar Telefonoconductor
                    $phone = $d->Telefonoconductor;
                    
                    if ($phone) {
                        // Si hay múltiples números separados por - o espacios, tomar el primero
                        $phone = explode('-', $phone)[0];
                        $phone = explode(' ', $phone)[0];
                        $num = preg_replace('/[^0-9]/', '', $phone);
                        if (strlen($num) > 10) {
                            $num = substr($num, -10);
                        }
                    } else {
                        $num = null;
                    }
                    
                    return [
                        'id' => $d->id,
                        'phone_number' => $num,
                        'name' => $d->Conductor,
                        'type_vehicle' => $d->Clasevehiculo
                    ];
                })
                ->filter(function($driver) {
                    // Solo incluir conductores con teléfono válido
                    return !empty($driver['phone_number']) && !empty($driver['name']);
                })
                ->values()
                ->toArray();

            $totalDrivers = count($drivers);
            Log::info("Conductores encontrados: $totalDrivers");

            if ($totalDrivers === 0) {
                return response()->json(['message' => 'No hay conductores con ese tipo de vehículo'], 200);
            }

            /* ------------------------------------------------------------------
            4. Crear/obtener registro Call
            ------------------------------------------------------------------*/
            $transportController = new TransportController();
            $call   = $transportController->createOrRetrieveCall($cotizacionModelId, $totalDrivers);
            $callId = $call->id;
            Log::info("Call ID: $callId");

            /* ------------------------------------------------------------------
            5. Procesar texto del flete (TEMPORALMENTE DESHABILITADO)
            ------------------------------------------------------------------*/
            if (!empty($prompt)) {
                Log::info('Procesamiento de prompt temporalmente deshabilitado', ['prompt' => $prompt]);
                // TODO: Configurar SSH/SFTP antes de habilitar
                // $this->handleFreightResponse($prompt);
            }

            /* ------------------------------------------------------------------
            6. Generar prompts de voz para el agente (TEMPORALMENTE DESHABILITADO)
            ------------------------------------------------------------------*/
            Log::info('Generación de audios temporalmente deshabilitada para testing');
            // TODO: Configurar SSH/SFTP antes de habilitar
            // $this->generateVoicePrompt('Le tenemos una oferta', 'we_have_an_offer');
            
            // Nota: Los audios se generarán dinámicamente con ElevenLabs en cada llamada

            /* ------------------------------------------------------------------
            7. Crear registros de llamadas en la cola (sistema de gestión de cola 3x3)
            ------------------------------------------------------------------*/
            $queueService = app(CallQueueService::class);
            $createdCalls = 0;
            $enqueuedCalls = 0;

            foreach ($drivers as $index => $driver) {
                Log::info('Creando llamada en cola para conductor', $driver);
                
                try {
                    // Formatear número de teléfono
                    $phoneNumber = $this->formatPhoneNumber($driver['phone_number']);
                    
                    // Crear registro de llamada con estado inicial
                    $llamada = \App\Models\Llamada::create([
                        'id_cotizacion' => $cotizacionModelId,
                        'chofer_id' => $driver['id'],
                        'numero_destino' => $phoneNumber,
                        'status' => 'pending',
                        'queue_status' => 'pending', // Estado inicial de la cola
                        'call_direction' => 'outbound',
                        'call_type' => 'agent',
                        'internal_notes' => 'Llamada creada para sistema de cola. Conductor: ' . $driver['name'] . ' | Vehículo: ' . ($driver['type_vehicle'] ?? 'N/A')
                    ]);
                    
                    $createdCalls++;
                    
                    // Agregar a la cola con prioridad (menor número = mayor prioridad)
                    // Prioridad 5 por defecto, puede ajustarse según lógica de negocio
                    if ($queueService->enqueueCall($llamada, 5)) {
                        $enqueuedCalls++;
                        Log::info("Llamada #{$llamada->id_llamada} agregada a la cola", [
                            'driver_name' => $driver['name'],
                            'phone' => $phoneNumber,
                            'estimated_wait' => $llamada->estimated_wait_seconds
                        ]);
                    } else {
                        Log::warning("No se pudo agregar llamada a la cola", [
                            'llamada_id' => $llamada->id_llamada,
                            'driver_name' => $driver['name']
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("Error al crear/encolar llamada", [
                        'driver' => $driver,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Obtener estadísticas de la cola
            $queueStats = $queueService->getQueueStats();

            Log::info('------ callDrivers END (con sistema de cola) ------', [
                'created_calls' => $createdCalls,
                'enqueued_calls' => $enqueuedCalls,
                'queue_stats' => $queueStats
            ]);

            // Disparar monitor de cola automáticamente para procesar las llamadas
            if ($enqueuedCalls > 0) {
                \Illuminate\Support\Facades\Artisan::queue('calls:monitor', [
                    '--once' => true,
                    '--stuck-timeout' => 180,
                    '--max-global' => 5,
                    '--max-per-order' => 2,
                ]);
            }

            return response()->json([
                'message' => 'Llamadas agregadas al sistema de cola',
                'total_drivers' => $totalDrivers,
                'calls_created' => $createdCalls,
                'calls_enqueued' => $enqueuedCalls,
                'cotizacion_id' => $cotizacionModelId,
                'vehicle_type' => $typeVehicle,
                'agente_ia_activo' => true,
                'queue_info' => [
                    'mode' => 'batch_processing',
                    'max_concurrent' => 3,
                    'batch_delay_seconds' => 90,
                    'total_in_queue' => $queueStats['queued'],
                    'total_processing' => $queueStats['processing'],
                    'estimated_wait_time' => $queueStats['wait_time'] . ' segundos',
                    'can_process_now' => $queueStats['can_process']
                ],
                'next_steps' => 'Las llamadas se procesarán automáticamente. El monitor está activo.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error en callDrivers', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Formatear número de teléfono para Colombia
     */
    private function formatPhoneNumber($phoneNumber)
    {
        // Limpiar el número
        $cleanNumber = preg_replace('/\D/', '', $phoneNumber);
        
        // Si empieza con 57, usar tal como está
        if (substr($cleanNumber, 0, 2) === '57') {
            return '+' . $cleanNumber;
        }
        
        // Si es número de 10 dígitos de Colombia, agregar +57
        if (strlen($cleanNumber) == 10) {
            return '+57' . $cleanNumber;
        }
        
        // Si es número de 7 dígitos, agregar código de área (1 para Bogotá por defecto)
        if (strlen($cleanNumber) == 7) {
            return '+571' . $cleanNumber;
        }
        
        // Para otros casos, intentar agregar +57
        return '+57' . $cleanNumber;
    }
    
    public function makeCall($phoneNumber, $cotizacionId, $driverId)
    {
        set_time_limit(60);
        Log::info('makeCall INIT', [
            'phoneNumber' => $phoneNumber,
            'env' => [
                'ASTERISK_HOST' => env('ASTERISK_HOST'),
                'ASTERISK_PORT' => env('ASTERISK_PORT'),
                'ASTERISK_USERNAME' => env('ASTERISK_USERNAME'),
                // No logueamos el secret por seguridad
            ]
        ]);
        try {
            $host = env('ASTERISK_HOST');
            $port = env('ASTERISK_PORT');
            $username = env('ASTERISK_USERNAME');
            $secret = env('ASTERISK_SECRET');

            if (empty($host) || empty($port) || empty($username) || empty($secret)) {
                Log::error('Asterisk env vars missing', compact('host', 'port', 'username', 'secret'));
                throw new \Exception("Faltan datos de conexión a Asterisk en el .env");
            }

            $socket = fsockopen($host, $port, $errno, $errstr, 10);
            if (!$socket) {
                Log::error('No se pudo conectar al servidor Asterisk', ['errno' => $errno, 'errstr' => $errstr]);
                throw new \Exception("No se pudo conectar al servidor Asterisk: $errstr ($errno)");
            } else {
                Log::info("Conexión a Asterisk abierta", compact('host', 'port'));
            }

            // Login
            fwrite($socket, "Action: Login\r\n");
            fwrite($socket, "Username: $username\r\n");
            fwrite($socket, "Secret: $secret\r\n");
            fwrite($socket, "\r\n");

            $response = '';
            while (!feof($socket)) {
                $line = fgets($socket, 1024);
                $response .= $line;
                Log::debug('Recibiendo línea desde Asterisk', ['line' => trim($line)]);
                if (stripos($line, "Authentication accepted") !== false) {
                    Log::info('Autenticación a Asterisk aceptada');
                    break;
                }
                // Previene loops infinitos por si hay problema de conexión
                if (stripos($line, "Authentication failed") !== false) {
                    Log::error('Autenticación a Asterisk fallida');
                    fclose($socket);
                    return 'failed';
                }
            }
            if (stripos($response, "Authentication accepted") === false) {
                Log::error('No se logró autenticar a Asterisk', ['response' => $response]);
                fclose($socket);
                return 'failed';
            }

            // Originate call a contexto AGI
            Log::info("Enviando acción Originate", [
                'phoneNumber' => $phoneNumber,
                'cotizacionId' => $cotizacionId,
                'driverId' => $driverId
            ]);
            fwrite($socket, "Action: Originate\r\n");
            fwrite($socket, "Channel: SIP/Trunk_Movistar/$phoneNumber\r\n");
            fwrite($socket, "Context: custom-transport-offer-ai-interactive\r\n");
            fwrite($socket, "Exten: 1000\r\n");
            fwrite($socket, "Priority: 1\r\n");
            fwrite($socket, "CallerID: 3168339017\r\n");
            fwrite($socket, "Timeout: 30000\r\n");
            fwrite($socket, "Async: true\r\n");
            if ($cotizacionId && $driverId) {
                fwrite($socket, "Variable: COT_ID=$cotizacionId\r\n");
                fwrite($socket, "Variable: DRV_ID=$driverId\r\n");
            }
            fwrite($socket, "\r\n");

            Log::info('Llamada disparada por Originate (AGI)', [
                'phoneNumber' => $phoneNumber
            ]);

            fflush($socket);                  // vacía el buffer de salida
            usleep(200000);                   // 0,2 s para asegurar el envío (opcional)

            fclose($socket);
            Log::info('Socket cerrado correctamente');

            return 'called';
        } catch (\Exception $e) {
            Log::error('Error en makeCall', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 'failed';
        }
    }

    public function processResponseInterative($prompt)
    {
        $response = $this->openai->chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "Eres un asistente conversacional especializado en transporte de fletes. 
                    Debes analizar descripciones de fletes, extraer información relevante y responder de forma natural y fluida, como un humano.
                    
                    Devuelve la información en JSON con las claves:
                     - 'tonelaje' 
                     - 'logistica': Debe ser un resumen fluido y natural de 'origen' y 'destino'.  
                     - 'material'.
                    
                    También agrega:
                    - 'oferta': Un resumen de la oferta para el conductor.
    
                    Si algún dato falta, coloca 'No se proporcionó esa información'."
                ],
                [
                    'role' => 'user',
                    'content' => "Extrae la información del siguiente flete y genera respuestas naturales para aceptación y rechazo:
                    \"$prompt\""
                ],
            ],
        ]);
    
        $processedResponse = json_decode($response['choices'][0]['message']['content'], true);

        return $processedResponse;
    }
    
    public function handleFreightResponse($prompt)
    {
        set_time_limit(120);

        // Procesar la respuesta para obtener los datos estructurados
        $responseProcessed = $this->processResponseInterative($prompt);

        // Lista de campos a procesar para generar voz
        $fields = [
            'tonelaje' => 'Tonelaje',
            'logistica' => 'logistica',
            'material' => 'Material',
            'oferta' => 'Oferta'
        ];

        // Generar audios para cada campo
        foreach ($fields as $key => $label) {
            $this->generateVoicePrompt($responseProcessed[$key], $key);
        }
    }

    public function fetchLastTransportRequest()
    {
        // 1. Obtener el último registro de cotizacion
        $lastCotizacion = \App\Models\CotizacionModel::orderByDesc('id')->first();

        if (!$lastCotizacion) {
            return response()->json(['error' => 'No se encontró ninguna cotización.'], 404);
        }

        // 2. Preparar los datos para el prompt
        $origen = $lastCotizacion->ciudad_origen;
        $destino = $lastCotizacion->ciudad_destino;
        // Prioriza tipo_producto, si no existe toma tipo_mercancia, puedes ajustar esto si lo deseas.
        $tipoCarga = $lastCotizacion->tipo_producto ?? $lastCotizacion->tipo_mercancia ?? 'carga sin especificar';
        $peso = $lastCotizacion->peso_mercancia;

        // Por si necesitas redondear o convertir a texto el peso (opcional):
        $pesoText = $peso . ' kilos';

        // 3. Construir el prompt:
        $prompt = "Este flete consiste en transportar una carga de tipo {$tipoCarga} desde {$origen} hasta {$destino}. ";
        $prompt .= "La carga tiene un peso total de {$pesoText}.";

        // 4. Pedir a OpenAI la conversión de números a palabras (opcional, como antes):
        $promptNumberToText = $this->openai->chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Convierte todos los números del siguiente texto a su representación en palabras, en español. Solo responde con el texto transformado, sin explicaciones.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
        ]);
        $promptConNumerosConvertidos = $promptNumberToText['choices'][0]['message']['content'];

        // 5. Verificar la coherencia (igual que antes):
        $response = $this->openai->chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Verifica la coherencia de la información del flete. Si los datos no son congruentes, responde "La información de la cotización es inconsistente, le recomendamos hacer la oferta de forma manual."'
                ],
                [
                    'role' => 'user',
                    'content' => $promptConNumerosConvertidos
                ]
            ],
        ]);
        $processedResponse = $response['choices'][0]['message']['content'];

        // 6. Responder como JSON:
        return response()->json([
            'prompt' => $promptConNumerosConvertidos,
            'respuesta_modelo' => $processedResponse
        ]);
    }
    
    public function getVehicleType($cotizacionId = null)
    {
        // Si el ID es nulo, obtener el último registro de CotizacionModel, si no, buscar por ID
        $cotizacion = $cotizacionId 
            ? CotizacionModel::find($cotizacionId) 
            : CotizacionModel::latest()->first();

        // Verificar si se encontró el registro
        if (!$cotizacion) {
            return "Cotización no encontrada";
        }

        // Retornar el tipo de vehículo requerido, si existe
        return $cotizacion->vehiculo_requerido;
    }

    public function callStatus(int $cotizacionId)
    {
        // Registro principal de la llamada
        $call = Call::where('quotation_id', $cotizacionId)->first();

        if (!$call) {
            return response()->json([
                'prompt'             => null,
                'vehicle_type'       => $this->getVehicleType($cotizacionId),
                'total_to_call'      => 0,
                'accepted'           => [],
                'percentage'         => 0,
                'selected_driver_id' => null,
            ]);
        }

        /* ------------------  A C E P T A D O S  ------------------  
        decision puede venir como:
        • 1               (tinyint → aceptado)
        • 'aceptado'
        • 'accepted'      (por si luego cambias a VARCHAR)
        ------------------------------------------------------------*/
        $accepted = DB::table('call_driver_decisions as d')
            ->join('vehicle_owner_holder_driver as v', 'v.id', '=', 'd.driver_id')
            ->where('d.cotizacion_model_id', $cotizacionId)
            ->where(function ($q) {
                $q->where('d.decision', 1)                  // tinyint = 1
                ->orWhere('d.decision', 'aceptado')       // varchar (es)
                ->orWhere('d.decision', 'accepted');      // varchar (en)
            })
            ->select(
                'v.id',
                'v.conductor  as name',
                DB::raw("COALESCE(v.telefonoconductor,
                                v.telefonopropietario,
                                v.telefonoposeedor) as phone")
            )
            ->get();

        // Porcentaje de avance
        $percentage = $call->total_drivers
            ? round(($call->calls_made / $call->total_drivers) * 100)
            : 0;

        return response()->json([
            'prompt'             => $call->prompt,
            'vehicle_type'       => $this->getVehicleType($cotizacionId),
            'total_to_call'      => $call->total_drivers,
            'accepted'           => $accepted,
            'percentage'         => $percentage,
            'selected_driver_id' => $call->selected_driver_id,
        ]);
    }

    /**
     * Verificar si ya se ha llamado al conductor para esta cotización
     */
    private function hasBeenCalled($driverId, $cotizacionId)
    {
        return DriverCallResponse::where('cotizacion_id', $cotizacionId)
                                 ->where('driver_id', $driverId)
                                 ->exists();
    }

    /**
     * Generar mensaje personalizado para la llamada
     */
    private function generateCallMessage($cotizacion)
    {
        $tipoVehiculo = strtolower($cotizacion->vehiculo_requerido ?? 'vehículo');
        $origen = $cotizacion->municipio_origen ?? 'origen';
        $destino = $cotizacion->municipio_destino ?? 'destino';
        
        return "Hola, tenemos una solicitud de transporte disponible. " .
               "Se necesita un {$tipoVehiculo} para un viaje desde {$origen} hasta {$destino}. " .
               "Si está interesado y disponible, presione 1 para aceptar o 2 para rechazar. " .
               "Gracias por su atención.";
    }

    /**
     * Manejar reintentos automáticos de llamadas fallidas
     */
    public function handleCallRetries(Request $request)
    {
        Log::info('------ handleCallRetries INIT ------');
        
        try {
            $cotizacionModelId = $request->input('cotizacion_model_id');
            $maxRetries = 2; // Máximo 2 reintentos como solicitado
            
            Log::info("Procesando reintentos para cotización: $cotizacionModelId");
            
            // Buscar llamadas fallidas o no respondidas que necesiten reintento
            $failedCalls = DriverCallResponse::where('cotizacion_id', $cotizacionModelId)
                ->whereIn('call_status', ['failed', 'no_answer', 'busy'])
                ->where('retry_count', '<', $maxRetries)
                ->get();
                
            Log::info("Llamadas que necesitan reintento: " . $failedCalls->count());
            
            $retriesProcessed = 0;
            $successfulRetries = 0;
            
            foreach ($failedCalls as $failedCall) {
                Log::info("Reintentando llamada", [
                    'driver_id' => $failedCall->driver_id,
                    'driver_name' => $failedCall->driver_name,
                    'retry_count' => $failedCall->retry_count,
                    'previous_status' => $failedCall->call_status
                ]);
                
                $result = $this->retryCallToDriver($failedCall, $cotizacionModelId);
                
                if ($result['success']) {
                    $successfulRetries++;
                }
                
                $retriesProcessed++;
            }
            
            return response()->json([
                'message' => 'Reintentos procesados exitosamente',
                'cotizacion_id' => $cotizacionModelId,
                'total_retries_processed' => $retriesProcessed,
                'successful_retries' => $successfulRetries
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error en handleCallRetries: " . $e->getMessage());
            return response()->json(['error' => 'Error procesando reintentos'], 500);
        }
    }

    /**
     * Reintentar llamada a un conductor específico - DISABLED: Twilio removed
     */
    private function retryCallToDriver($previousCall, $cotizacionModelId)
    {
        Log::warning('retryCallToDriver: Funcionalidad deshabilitada - Twilio ha sido removido del sistema');
        return ['success' => false, 'error' => 'Twilio service removed'];
    }

    /**
     * Make outbound call using ElevenLabs SIP trunk
     */
    public function makeElevenLabsCall(Request $request)
    {
        try {
            $toNumber = $request->input('to_number');
            $cotizacionId = $request->input('cotizacion_id');
            $driverId = $request->input('driver_id');
            $clientData = $request->input('client_data', []);

            Log::info('Iniciando llamada con ElevenLabs SIP Trunk', [
                'to_number' => $toNumber,
                'cotizacion_id' => $cotizacionId,
                'driver_id' => $driverId
            ]);

            // Validar número de teléfono
            if (!$this->elevenLabsCallService->isValidPhoneNumber($toNumber)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid phone number format'
                ], 400);
            }

            // Buscar cotización para contexto
            $cotizacion = null;
            if ($cotizacionId) {
                $cotizacion = CotizacionModel::find($cotizacionId);
                if ($cotizacion) {
                    $clientData['cotizacion'] = [
                        'id' => $cotizacion->id,
                        'origen' => $cotizacion->ciudad_origen,
                        'destino' => $cotizacion->ciudad_destino,
                        'tipo_mercancia' => $cotizacion->tipo_mercancia,
                        'vehiculo_requerido' => $cotizacion->vehiculo_requerido
                    ];
                }
            }

            // Agregar información del conductor
            if ($driverId) {
                $clientData['driver_id'] = $driverId;
            }

            // Realizar llamada
            $result = $this->elevenLabsCallService->makeDirectSipCall($toNumber, $clientData);

            if ($result['success']) {
                // Crear registro de la llamada
                $callResponse = DriverCallResponse::create([
                    'cotizacion_id' => $cotizacionId,
                    'driver_id' => $driverId,
                    'driver_phone' => $toNumber,
                    'call_status' => 'calling',
                    'response_status' => 'pending',
                    // Usar conversation_id en lugar de twilio_call_sid
                    'elevenlabs_conversation_id' => $result['conversation_id'],
                    'notes' => 'Llamada iniciada con ElevenLabs SIP Trunk - SIP ID: ' . ($result['sip_call_id'] ?? 'N/A')
                ]);

                Log::info('Llamada ElevenLabs iniciada exitosamente', [
                    'conversation_id' => $result['conversation_id'],
                    'sip_call_id' => $result['sip_call_id'],
                    'call_response_id' => $callResponse->id
                ]);

                return response()->json([
                    'success' => true,
                    'conversation_id' => $result['conversation_id'],
                    'sip_call_id' => $result['sip_call_id'],
                    'call_response_id' => $callResponse->id,
                    'message' => 'Call initiated successfully with ElevenLabs'
                ]);
            } else {
                Log::error('Error en llamada ElevenLabs', [
                    'error' => $result['error'],
                    'to_number' => $toNumber
                ]);

                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Excepción en makeElevenLabsCall: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get call statistics for ElevenLabs calls
     */
    public function getElevenLabsCallStats()
    {
        try {
            // Estadísticas desde la base de datos
            $dbStats = [
                'total_calls' => DriverCallResponse::whereNotNull('elevenlabs_conversation_id')->count(),
                'successful_calls' => DriverCallResponse::whereNotNull('elevenlabs_conversation_id')
                    ->where('call_status', 'completed')->count(),
                'pending_calls' => DriverCallResponse::whereNotNull('elevenlabs_conversation_id')
                    ->where('call_status', 'calling')->count(),
                'failed_calls' => DriverCallResponse::whereNotNull('elevenlabs_conversation_id')
                    ->where('call_status', 'failed')->count()
            ];

            // Test de conexión a ElevenLabs
            $connectionTest = $this->elevenLabsCallService->testSipTrunkConnection();

            return response()->json([
                'success' => true,
                'stats' => $dbStats,
                'connection_status' => $connectionTest,
                'service' => 'ElevenLabs SIP Trunk'
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas ElevenLabs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to get call statistics'
            ], 500);
        }
    }
}