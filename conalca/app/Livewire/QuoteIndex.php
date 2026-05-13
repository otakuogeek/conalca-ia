<?php

namespace App\Livewire;

use App\Imports\PricingImport;
use App\Imports\PricingSMImport;
use App\Imports\PricingUpImport;
use Livewire\Component;
use App\Models\Client;
use App\Models\Email;
use App\Models\Pricing;
use App\Models\CotizacionModel;
use App\Models\GroupCotization;
use App\Models\SolicitudTransporte;
use App\Services\QuoteAssistantService;
use App\Services\SilogtranService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use App\Models\Solicitation;
use Illuminate\Support\Facades\Session;  
use Illuminate\Support\Facades\Redirect; 
use OpenAI;
use App\Helpers\CityHelper;
use App\Services\MCPAssistantService;

class QuoteIndex extends Component
{
    public $quotes;

    use WithFileUploads;
    public $quote = [];

    public $showModal = false;

    public $loading_prompt = false;

    public $showModal2 = false;

    public $showModal3 = false;

    public $showModal4 = false;

    public $showModal9 = false;

    public $showModalSuccess = false;

    public $step = 0;

    public $search = '';

    public $client_name = '';

    public $document_client = '';

    public $messages = [];

    public ?Client $client;

    public $openai_thread = "";

    public $openai_current_run = "";

    public $input_message = "";

    public $quote_data = [];

    public $porcentaje = 17;

    public $price_aux = 0;

    public $type_business;

    public $cargo_type;

    public $file;

    public $selectedPricings = [];
    public $pricings = [];
    public $active_button = false;
    public $porcentajes1 = []; 
    public $porcentajes2 = [];
    public $porcentajes3 = [];

    public $selected_data_cotizacion = [];

    public $load_button = false;

    public $accept_quote = false;

    public $send_email = '';

    public $active_button_2 = false;

    public $select_value;

    public $title_email = "Cotización";
    public $greeting = "Cordialmente";
    public $acceptOfferDecision = "A continuación puedes aceptar nuestra cotización como aprobado o declinar para generar una negociación nueva.";
    public $asesor_name_email = "Asigna el nombre del asesor...";
    public $asesor_phone_email = "Asigna el telefono del asesor...";
    public $asesor_pbx_email = "Asigna el pbx...";
    public $asesor_ubicacion_email = "Asigna la ubicacion...";
    public $asesor_email_email = "Asigna el correo...";
    public $advisor_signature = "";
    public $cliente_email = ""; // Email destino para envío de cotización

    public $solicitudes = [];

    public $id_last_created;



    public $porcentaje_global = null; // 17, 24, 32
    public $porcentaje_modificado = []; // para marcar si se modificó manualmente
    public $errores_porcentaje = [];

    // Acompañamientos
    public $acompañamientos = [];

    
    public $client_id = null;
    public $client_document_client = null;
    public $client_company_name = null;
    public $client_location = null;
    public $client_phone_numbers = null;
    public $client_personal_cell = null;
    public $client_email = null;
    public $client_email_registered = null; // Email registrado del cliente en la BD
    public $client_emails_available = []; // Array de emails disponibles del cliente
    public $cliente_email_manually_set = false; // Flag para saber si el usuario modificó manualmente el email
    public $client_address = null;
    public $client_branch_office = null;
    public $client_sales_representative = null;

    public string $prompt_response = '';

    public string $accept_url = '';
    public string $reject_url = '';

    public string $total_price = '';

    public $isEditing = false;

    public $group_cotization_id = null;
    
    public string $save_draft_notification = '';

    // Propiedades
    public $ciudadFaltante   = false;
    public $ciudadOrigenSel  = '';
    public $ciudadDestinoSel = '';

    public $vehicleSuggestions = []; 

    public $operation_type;
    
    protected $listeners = [
        'openModal' => 'openModal',
        'ciudad-no-encontrada' => 'pedirCiudadManualmente',
    ];

    public function render()
    {
        $this->syncChat();
        return view('livewire.quote-index');
    }

    public function mount()
    {
        $this->loading_prompt = false;
        // Tipo de negocio
        $this->type_business = 'dta';

        // Solicitudes relacionadas con transporte y cotizaciones
        // $this->solicitudes = SolicitudTransporte::with('acompanamiento', 'cargue', 'condiciones_factura', 'contenedor', 'detalle', 'entrega', 'equipos', 'internacional')->get();
        // $this->quotes = CotizacionModel::with('pricing')->orderBy('created_at', 'desc')->where('silogtran_status', 'cancel')->get();

        // Datos del usuario logueado (asesor comercial)
        $user = auth()->user();
        if ($user) {
            $this->asesor_name_email  = $user->name;
            $this->asesor_email_email = $user->email;
            $this->asesor_phone_email = $user->phone ?? '';
            $this->advisor_signature = $user->signature ?? '';
        }

        // // Recuperar cliente por documento (NIT)
        $client = Client::where('documento', $this->search)->first();
        $client = Client::where('documento', $this->search)->first();
        if ($client) {
            $this->client_id = $client->id;
            $this->client_document_client = $this->search;
            $this->client_name = $client->cliente;                // Nombre del cliente
            $this->client_company_name = $client->cliente;       // Nombre de la empresa
            $this->client_location = $client->ciudad;               // Ubicación del cliente
            $this->client_phone_numbers = $client->telefono;     // Teléfonos de contacto
            $this->client_personal_cell = $client->celular;     // Celular personal
            $this->client_email = $client->email;     
            $this->client_email_registered = $client->email;    // Email registrado en BD
            $this->cliente_email = $client->email; // Inicializar email destino automáticamente
            $this->client_address = $client->direccion;                 // Dirección física
            $this->client_branch_office = $client->branch_office;     // Sucursal (si aplica)
            $this->client_sales_representative = $client->vendedor_nombre; // Representante de ventas
            
            // CRÍTICO: Inicializar thread de MCP para el chat
            $this->client = $client;
            $this->openai_thread = MCPAssistantService::getThread($client);
            
            // NUEVO: Guardar thread_id en el cliente si no existe
            if ($this->openai_thread && !$client->openai_thread_id) {
                $client->openai_thread_id = $this->openai_thread;
                $client->save();
                Log::info('Thread ID guardado en cliente', [
                    'client_id' => $client->id,
                    'thread_id' => $this->openai_thread
                ]);
            }
            
            // Obtener run_id desde el cliente o la sesión
            $this->openai_current_run = $client->openai_current_run;
            
            // Si no hay run en el cliente, intentar obtenerlo de la última sesión activa
            if (!$this->openai_current_run) {
                $session = ConversationSession::where('client_id', $client->id)
                    ->where('status', 'active')
                    ->latest()
                    ->first();
                    
                if ($session && $session->metadata) {
                    $metadata = json_decode($session->metadata, true);
                    if (isset($metadata['current_run_id'])) {
                        $this->openai_current_run = $metadata['current_run_id'];
                        Log::info('Run ID recuperado de sesión', [
                            'run_id' => $this->openai_current_run
                        ]);
                    }
                }
            }
            
            // Cargar mensajes existentes del thread
            if ($this->openai_thread) {
                $this->messages = MCPAssistantService::getMessages($this->openai_thread);
            }
        } 

        if (empty($this->acompañamientos)) {
            $this->acompañamientos[] = [
                'id' => uniqid(),
                'tipo' => '',
                'cuenta_de' => '',
                'cantidad_vehiculos' => 1,
                'valor' => 0
            ];
        }
    }

    /* -----------------------------------------------------------------
    | Calcula valor_final para una ruta concreta
    |-----------------------------------------------------------------*/
    private function calcularValorFinal(int $index)
    {
        $pricing   = $this->selectedPricings[$index] ?? null;
        $ruta      = $this->quote_data[$index];

        if (!$pricing) {                     // aún sin vehículo
            unset($this->quote_data[$index]['valor_final']);
            return;
        }

        // 1. precio base + rentabilidad
        $base        = floatval($pricing->price);
        $rentab      = floatval($ruta['porcentaje'] ?? 0);
        $clienteBase = $base + ($base * $rentab / 100);

        // 2. acompañamiento
        $cantAcomp   = intval($ruta['itesoltra_vehiculoacompanamiento'] ?? 0);
        $valorAcomp  = floatval($ruta['itesoltra_acompanamientovalor']  ?? 0);
        $subtotalA   = $cantAcomp * $valorAcomp;

        // 3. total
        $this->quote_data[$index]['valor_final']         = round($clienteBase + $subtotalA);
        $this->quote_data[$index]['subtotal_acompanante']= $subtotalA;   // por si lo necesitas mostrar
    }

    public function pedirCiudadManualmente($nombres)
    {
        $this->ciudadFaltante  = true;
        $this->nombreOrigenRaw = $nombres['origen'];
        $this->nombreDestinoRaw= $nombres['destino'];
    }

    public function confirmarCiudades()
    {
        if(!$this->ciudadOrigenSel || !$this->ciudadDestinoSel){
            $this->dispatchBrowserEvent('mostrar-alerta', [
                'tipo'    => 'error',
                'mensaje' => 'Debes escoger ambos códigos.'
            ]);
            return;
        }

        // Sobrescribe los códigos en la ruta que estaba pendiente
        $this->quote_data[0]['ciudad_origen_dane']  = $this->ciudadOrigenSel;
        $this->quote_data[0]['ciudad_destino_dane'] = $this->ciudadDestinoSel;

        $this->ciudadFaltante = false;

        // Continúa con el flujo habitual, por ejemplo:
        $this->saveCotizacion();
    }

    private function getVehicleSuggestion(array $route): array
    {
        /* -----------------------------------------------------------------
        $route contiene, por ejemplo:
        [ 'ciudad_origen' , 'ciudad_destino' , 'peso_mercancia' , … ]
        -----------------------------------------------------------------*/

        // 1. Construir el prompt
        $prompt = <<<EOT
            Tienes la siguiente lista de TIPOS DE VEHÍCULO:
            NC
            MOTOCICLETA
            AUTOMOVIL
            DOBLETROQUE
            NO APLICA
            CUATRO MANOS
            NO APLICA.
            TRACTOMULA 4
            NO APLICA .
            NO APLICA SENCILLO
            CAMA BAJA
            TURBO
            SENCILLO
            PATINETA2
            TRACTOMULA3
            CAMIONETA
            PATINETA3
            TRACTOMULA 2

            Y esta lista de TIPOS DE CARROCERÍA:
            ESTACAS
            FURGON
            TANQUE
            VOLCO
            TOLVA
            PLANCHON
            ESTIBAS
            HORMIGONERO
            REPARTO
            GRUA
            CERRADA
            SEDAN
            COUPE
            CABINADO
            CARPADO
            PANEL
            ABIERTA
            REMOLQUE
            TRACTOMULA
            ARTICULADO
            GRANELERO
            PLATON
            PLATON ESC
            CONTENEDOR
            CAMABAJA
            PLATAFORMA
            TRAILER
            PORTACONTENEDOR
            SIN TRAILER
            JAULA
            THERMOKING
            TANQUE THE
            CISTERNA
            VOLQUETE
            VOLTEO
            WELCO
            COMPACTAD.
            MULA
            SIN CARROCERIA
            BOTELLERO
            PALETIZADA
            GONDOLA
            MODULAR
            PIPA
            MEZCLADORA
            NINERA
            LOW BOY
            PLATAFORMA CON EQUIPO ESPECIAL
            CARBONERA
            S.R.S

            Según la siguiente información de un envío (origen, destino, peso, tipo de producto…) indica SÓLO la mejor combinación, usando estos catálogos, con este formato exacto:
            VEHÍCULO:  <nombre exacto>
            CARROCERÍA: <nombre exacto>

              INFORMACIÓN DEL ENVÍO:
            – Origen: {$route['ciudad_origen']}
            – Destino: {$route['ciudad_destino']}
            – Peso Kg: {$route['peso_mercancia']}
            – Tipo producto: {$route['tipo_producto']}
            EOT;

        Log::info('INFORMACIÓN DEL ENVÍO:');
        Log::info('– Origen: ' . $route['ciudad_origen']);
        Log::info('– Destino: ' . $route['ciudad_destino']);
        Log::info('– Peso Kg: ' . $route['peso_mercancia']);
        Log::info('– Tipo producto: ' . $route['tipo_producto']);

        // 2. Llamar a OpenAI
        try {
            $r = OpenAI::client(config('services.openai.api_key'))
                ->chat()
                ->create([
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'system',
                        'content' => 'Responde siempre sólo con las dos líneas solicitadas.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            $text = $r['choices'][0]['message']['content'] ?? '';

            // 3. Parsear “VEHÍCULO: …  CARROCERÍA: …”
            preg_match('/VEHÍCULO:\s*(.+)/i', $text, $v);
            preg_match('/CARROCERÍA:\s*(.+)/i', $text, $c);

            return [
                'vehicle'   => trim($v[1] ?? 'N/D'),
                'bodywork'  => trim($c[1] ?? 'N/D'),
            ];
        } catch (\Throwable $e) {
            \Log::warning('OpenAI sugerencia vehículo falló: '.$e->getMessage());
            return ['vehicle' => 'N/D', 'bodywork' => 'N/D'];
        }
    }

    public function resetQuoteForm()
    {
        // Variables principales del formulario de cotización/rutas
        $this->quote = [];
        $this->quote_data = [];
        $this->selectedPricings = [];
        $this->pricings = [];
        $this->selected_data_cotizacion = [];
        $this->porcentaje = 17;
        $this->porcentaje_global = null;
        $this->porcentaje_modificado = [];
        $this->errores_porcentaje = [];

        // Reset acompañamientos
        $this->acompañamientos = [
            [
                'id' => uniqid(),
                'tipo' => '',
                'cuenta_de' => '',
                'cantidad_vehiculos' => 1,
                'valor' => 0
            ]
        ];

        // Variables de control de pasos y visualización de modales
        $this->step = 0;
        $this->showModal = false;
        $this->showModal2 = false;
        $this->showModal3 = false;
        $this->showModal4 = false;
        $this->showModal9 = false;
        $this->showModalSuccess = false;
        $this->loading_prompt = false;
        $this->load_button = false;
        $this->active_button = false;
        $this->active_button_2 = false;

        // Otros campos internos relacionados
        $this->group_cotization_id = null;
        $this->file = null;
        $this->id_last_created = null;
        $this->save_draft_notification = '';
        $this->input_message = '';
        $this->prompt_response = '';
        $this->total_price = '';
    }

    public function acceptQuote()
    {
        $this->accept_quote = true;
    }

    public function changeValue($step)
    {
        if ($step == 0) {
            $this->load_button = true;
        } else {
            $this->load_button = false;
        }
    }

    public function importExcel()
    {
        Excel::import(new PricingSMImport, $this->file);
    }

    public function storeST()
    {
        $this->hydrateClientData();
        $this->active_button = true;
        
        // Intentar obtener group_id de sesión (creado por React/API)
        if (!$this->group_cotization_id && session()->has('current_group_id')) {
            $this->group_cotization_id = session('current_group_id');
            \Log::info('✅ Group ID recuperado de sesión', ['group_id' => $this->group_cotization_id]);
        }
        
        // Si no hay en sesión, buscar el último grupo borrador RECIENTE de este cliente
        if (!$this->group_cotization_id && $this->client_id) {
            $lastDraft = GroupCotization::where('client_id', $this->client_id)
                ->where('user_id', auth()->id())
                ->where('status', 'borrador')
                ->where('created_at', '>=', now()->subDay()) // Solo últimas 24 horas
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($lastDraft) {
                $this->group_cotization_id = $lastDraft->id;
                \Log::info('✅ Grupo borrador encontrado en DB', [
                    'group_id' => $lastDraft->id,
                    'client_id' => $this->client_id,
                    'created_at' => $lastDraft->created_at,
                    'age_hours' => now()->diffInHours($lastDraft->created_at)
                ]);
            } else {
                \Log::warning('⚠️ No se encontró grupo borrador reciente', [
                    'client_id' => $this->client_id,
                    'user_id' => auth()->id()
                ]);
            }
        }
        
        \Log::info('🔄 Iniciando storeST', [
            'group_cotization_id_actual' => $this->group_cotization_id,
            'client_id' => $this->client_id,
            'type_business' => $this->type_business
        ]);

        // PUNTO ÚNICO DE CREACIÓN/ACTUALIZACIÓN DEL GRUPO
        if ($this->group_cotization_id) {
            // Caso 1: Ya existe un ID (grupo borrador creado por API)
            $group = GroupCotization::find($this->group_cotization_id);
            if ($group) {
                // Actualizar status y campos adicionales
                $group->update([
                    'status' => 'pendiente',
                    'openai_thread_id' => $this->openai_thread ?? null,
                    'created_from_chat' => 1,
                ]);
                \Log::info('✅ Grupo ACTUALIZADO de borrador a pendiente', [
                    'group_id' => $group->id,
                    'thread_id' => $this->openai_thread,
                    'from_chat' => true
                ]);
                
                // Limpiar sesión
                session()->forget('current_group_id');
            } else {
                // ID inválido, limpiar y no crear nada aún
                \Log::warning('⚠️ Group ID inválido, limpiando', ['invalid_id' => $this->group_cotization_id]);
                $this->group_cotization_id = null;
                $group = null;
            }
        }
        
        // Caso 2: Si no hay grupo válido, crear uno nuevo (flujo legacy)
        if (!isset($group) || !$group) {
            \Log::warning('⚠️ Creando grupo sin borrador previo (flujo legacy)');
            
            $group = GroupCotization::create([
                'user_id' => auth()->id(),
                'client_id' => $this->client_id,
                'status' => 'pendiente',
                'type' => $this->type_business,
                'operation_type' => $this->operation_type,
                'candado_satelital' => in_array($this->type_business, ['dta', 'otm']),
                'cargo_type' => $this->cargo_type ?? null,
                'jen_set' => ($this->cargo_type === 'refrigerado'),
                'combustible' => ($this->cargo_type === 'refrigerado'),
                'kit_derrames' => ($this->cargo_type === 'dangerous'),
                'pictogramas' => ($this->cargo_type === 'dangerous'),
            ]);
            $this->group_cotization_id = $group->id;
            \Log::info('✅ Grupo CREADO (legacy)', ['group_id' => $group->id]);
        }

        // Ahora recorres las rutas:
        foreach ($this->quote_data as $ruta) {
            $ciudad_origen = strtolower($ruta['ciudad_origen']);
            $ciudad_destino = strtolower($ruta['ciudad_destino']);

            // $pricing = Pricing::where('origin', $ciudad_origen)
            //     ->where('destination', $ciudad_destino)
            //     ->first();
            $selected_pricing_id = $ruta['select_value'] ?? null; // <---  ESTA LÍNEA ES CLAVE

            $pricing = $selected_pricing_id ? Pricing::find($selected_pricing_id) : null;

            // *Si ya existe ruta en ese grupo, actualizas; sino, creas*
            $cotizacion = CotizacionModel::where('group_cotization_id', $group->id)
                ->where('client_id', $this->client_id)
                ->where('ciudad_origen', $ruta['ciudad_origen'])
                ->where('ciudad_destino', $ruta['ciudad_destino'])
                ->first();

            if ($cotizacion) {
                $cotizacion->fill([
                    'pricing_id'              => $pricing?->id,
                    'ciudad_origen_dane'      => $ruta['codigo_dane_origen'] ?? '',
                    'ciudad_destino_dane'     => $ruta['codigo_dane_destino'] ?? '',
                    'peso_mercancia'          => $ruta['peso_mercancia'] ?? '',
                    'cantidad'                => $ruta['cantidad'] ?? '',
                    'tipo_embajale'           => $ruta['tipo_embajale'] ?? '',
                    'dimensiones_exactas'     => $ruta['dimensiones_exactas'] ?? '',
                    'registro_fotografico'    => $ruta['registro_fotografico'] ?? '',
                    'planos'                  => $ruta['planos'] ?? '',
                    'tipo_producto'           => $ruta['tipo_producto'] ?? '',
                    'temperatura_mercancia'   => $ruta['temperatura_mercancia'] ?? '',
                    'humedad'                 => $ruta['humedad'] ?? '',
                    'vehiculo_requerido'      => $ruta['vehiculo_requerido'] ?? '',
                    'regimen_nacionalizado'   => $ruta['regimen_nacionalizado'] ?? '',
                    'agente_aduanas'          => $ruta['agente_aduanas'] ?? '',
                    'descargue_cargue'        => $ruta['descargue_cargue'] ?? '',
                    'consolidado_expreso'     => $ruta['consolidado_expreso'] ?? '',
                    'fcl_lcl'                 => $ruta['fcl_lcl'] ?? '',
                    'sitio_devolucion_contenedor' => $ruta['sitio_devolucion_contenedor'] ?? '',
                    'numero_documento_bl'     => $ruta['numero_documento_bl'] ?? '',
                    'fecha_hora_descargue_cargue' => $ruta['fecha_hora_descargue_cargue'] ?? '',
                    'cantidad_vh'             => $ruta['cantidad_vh'] ?? '',
                    'un'                      => $ruta['un'] ?? '',
                    'ruta'                    => $ruta['ruta'] ?? '',
                    'frecuencia'              => $ruta['frecuencia'] ?? '',
                    'esquema_seguridad'       => $ruta['esquema_seguridad'] ?? '',
                    'tipo_carroceria'         => $ruta['tipo_carroceria'] ?? '',
                    'valor'                   => $ruta['valor'] ?? '',
                    'valor_declarado'         => $ruta['valor_declarado'] ?? '',
                    'tipo_mercancia'          => $ruta['tipo_mercancia'] ?? '',
                    'ventanas_horarios_recibidos' => $ruta['ventanas_horarios_recibidos'] ?? '',
                    'seguro'                  => $ruta['seguro'] ?? '',
                    'silogtran_status'        => "pending", // Marca como enviada
                    'porcentaje'              => $ruta['porcentaje'] ?? $this->porcentaje ?? '',
                    'tipo'                    => $this->type_business ?? '',
                    'operation_type'          => $this->operation_type ?? '',
                    'itesoltra_vehiculoacompanamiento' => $ruta['itesoltra_vehiculoacompanamiento'] ?? null,
                    'tipaco_codigo'                    => $ruta['tipaco_codigo'] ?? null,
                    'itesoltra_acompanamientocuentade' => $ruta['itesoltra_acompanamientocuentade'] ?? null,
                    'itesoltra_acompanamientovalor'    => $ruta['itesoltra_acompanamientovalor'] ?? null
                ]);
                $cotizacion->save();
            } else {
                // No existe: creamos la cotización
                $cotizacion = new CotizacionModel([
                    'group_cotization_id'     => $group->id,
                    'pricing_id'              => $pricing?->id,
                    'client_id'               => $this->client_id,
                    'ciudad_origen'           => $ruta['ciudad_origen'] ?? '',
                    'ciudad_destino'          => $ruta['ciudad_destino'] ?? '',
                    'ciudad_origen_dane'      => $ruta['codigo_dane_origen'] ?? '',
                    'ciudad_destino_dane'     => $ruta['codigo_dane_destino'] ?? '',
                    'peso_mercancia'          => $ruta['peso_mercancia'] ?? '',
                    'cantidad'                => $ruta['cantidad'] ?? '',
                    'tipo_embajale'           => $ruta['tipo_embajale'] ?? '',
                    'dimensiones_exactas'     => $ruta['dimensiones_exactas'] ?? '',
                    'registro_fotografico'    => $ruta['registro_fotografico'] ?? '',
                    'planos'                  => $ruta['planos'] ?? '',
                    'tipo_producto'           => $ruta['tipo_producto'] ?? '',
                    'temperatura_mercancia'   => $ruta['temperatura_mercancia'] ?? '',
                    'humedad'                 => $ruta['humedad'] ?? '',
                    'vehiculo_requerido'      => $ruta['vehiculo_requerido'] ?? '',
                    'regimen_nacionalizado'   => $ruta['regimen_nacionalizado'] ?? '',
                    'agente_aduanas'          => $ruta['agente_aduanas'] ?? '',
                    'descargue_cargue'        => $ruta['descargue_cargue'] ?? '',
                    'consolidado_expreso'     => $ruta['consolidado_expreso'] ?? '',
                    'fcl_lcl'                 => $ruta['fcl_lcl'] ?? '',
                    'sitio_devolucion_contenedor' => $ruta['sitio_devolucion_contenedor'] ?? '',
                    'numero_documento_bl'     => $ruta['numero_documento_bl'] ?? '',
                    'fecha_hora_descargue_cargue' => $ruta['fecha_hora_descargue_cargue'] ?? '',
                    'cantidad_vh'             => $ruta['cantidad_vh'] ?? '',
                    'un'                      => $ruta['un'] ?? '',
                    'ruta'                    => $ruta['ruta'] ?? '',
                    'frecuencia'              => $ruta['frecuencia'] ?? '',
                    'esquema_seguridad'       => $ruta['esquema_seguridad'] ?? '',
                    'tipo_carroceria'         => $ruta['tipo_carroceria'] ?? '',
                    'valor'                   => $ruta['valor'] ?? '',
                    'valor_declarado'         => $ruta['valor_declarado'] ?? '',
                    'tipo_mercancia'          => $ruta['tipo_mercancia'] ?? '',
                    'ventanas_horarios_recibidos' => $ruta['ventanas_horarios_recibidos'] ?? '',
                    'seguro'                  => $ruta['seguro'] ?? '',
                    'silogtran_status'        => "pending", // Marca como enviada
                    'porcentaje'              => $ruta['porcentaje'] ?? $this->porcentaje ?? '',
                    'tipo'                    => $this->type_business ?? '',
                    'operation_type'          => $this->operation_type ?? '',
                    'itesoltra_vehiculoacompanamiento' => $ruta['itesoltra_vehiculoacompanamiento'] ?? null,
                    'tipaco_codigo'                    => $ruta['tipaco_codigo'] ?? null,
                    'itesoltra_acompanamientocuentade' => $ruta['itesoltra_acompanamientocuentade'] ?? null,
                    'itesoltra_acompanamientovalor'    => $ruta['itesoltra_acompanamientovalor'] ?? null
                ]);
                $cotizacion->save();
            }

            // Opcional: guarda id último creado
            // $this->id_last_created = $cotizacion->id;

            // E-mail y demás procesos opcionalmente aquí (si sólo quieres para 'pending' y no para 'draft')
            Email::create([
                'from_user'   => auth()->id(),
                'to_user'     => 1,
                'subject'     => 'La presolicitud con el id '. $cotizacion->id. ' ha sido enviada para su aprobación',
                'description' => 'Se ha realizado el envío de la cotización al cliente para su previa aceptación',
                'files'       => json_encode([]),
                'status'      => 'no-read',
            ]);
        }

        // $this->active_button = false;
        $this->showModal3 = false;
        $this->showModal4 = false;
    }

    public function getAllQuotes()
    {
        $this->quotes = CotizacionModel::with('pricing')->orderBy('created_at', 'desc')->where('silogtran_status', 'cancel')->get();
    }

    public function getQuote($quote)
    {
        $this->quote = CotizacionModel::find($quote);
        $this->selected_data_cotizacion = $this->quote;
        $this->showModal = true;
    }

    public function openModalCreate($step)
    {
        $this->hydrateClientData();
        // Ahora muestra el modal según el paso
        if ($step == 2) {
            $this->showModal2 = true;
        }
        if ($step == 3) {
            $this->showModal3 = true;
        }
        if ($step == 4) {
            // Debug log para verificar que llega el NIT correcto
            \Log::info('NIT que llega a openModalCreate(4):', ['nit' => $this->client_document_client]);
            $this->showModal4 = true;
            $this->loading_prompt = true;
            $this->generatePrompt();
            $this->loading_prompt = false;
        }
        if ($step == 9) {
            $this->showModal9 = true;
        }
    }

    public function openModal($id)
    {
        \Log::info('ID received, Id of the group_cotizations for update:', [$id]);
        $this->group_cotization_id = $id;
        $group = GroupCotization::find($id);

        if (!$group) {
            $this->dispatchBrowserEvent('mostrar-alerta', [
                'tipo' => 'error',
                'mensaje' => 'Grupo de cotización no encontrado.'
            ]);
            return;
        }

        $cotizaciones = CotizacionModel::where('group_cotization_id', $id)->get();

        if ($cotizaciones->count() === 0) {
            $this->quote_data = [];
            $this->pricings = [];
            $this->showModal3 = true;
            return;
        }

        // Limpia variables
        $this->quote_data = [];
        $this->selectedPricings = [];
        $this->pricings = []; // <<--- INICIALIZACIÓN PARA CADA ENTRADA

        foreach ($cotizaciones as $index => $c) {
            // === Llena quote_data (igual que antes) ===
            $pricing = $c->pricing_id ? Pricing::find($c->pricing_id) : null;

            $this->quote_data[] = [
                'ciudad_origen'                 => $c->ciudad_origen,
                'ciudad_destino'                => $c->ciudad_destino,
                'codigo_dane_origen'            => $c->ciudad_origen_dane,
                'codigo_dane_destino'           => $c->ciudad_destino_dane,
                'peso_mercancia'                => $c->peso_mercancia,
                'cantidad'                      => $c->cantidad,
                'tipo_embajale'                 => $c->tipo_embajale,
                'dimensiones_exactas'           => $c->dimensiones_exactas,
                'registro_fotografico'          => $c->registro_fotografico,
                'planos'                        => $c->planos,
                'tipo_producto'                 => $c->tipo_producto,
                'temperatura_mercancia'         => $c->temperatura_mercancia,
                'humedad'                       => $c->humedad,
                'vehiculo_requerido'            => $c->vehiculo_requerido,
                'regimen_nacionalizado'         => $c->regimen_nacionalizado,
                'agente_aduanas'                => $c->agente_aduanas,
                'descargue_cargue'              => $c->descargue_cargue,
                'consolidado_expreso'           => $c->consolidado_expreso,
                'fcl_lcl'                       => $c->fcl_lcl,
                'sitio_devolucion_contenedor'   => $c->sitio_devolucion_contenedor,
                'numero_documento_bl'           => $c->numero_documento_bl,
                'fecha_hora_descargue_cargue'   => $c->fecha_hora_descargue_cargue,
                'cantidad_vh'                   => $c->cantidad_vh,
                'un'                            => $c->un,
                'ruta'                          => $c->ruta,
                'frecuencia'                    => $c->frecuencia,
                'esquema_seguridad'             => $c->esquema_seguridad,
                'tipo_carroceria'               => $c->tipo_carroceria,
                'valor'                         => $c->valor,
                'valor_declarado'               => $c->valor_declarado,
                'tipo_mercancia'                => $c->tipo_mercancia,
                'ventanas_horarios_recibidos'   => $c->ventanas_horarios_recibidos,
                'seguro'                        => $c->seguro,
                'porcentaje'                    => $c->porcentaje,
                // Extras para el sistema:
                'calculated_price'              => $pricing ? ($pricing->price + ($pricing->price * $c->porcentaje / 100)) : null,
                'select_value'                  => $c->pricing_id, // para select imput del pricing
                'itesoltra_vehiculoacompanamiento'   => $c->itesoltra_vehiculoacompanamiento ?? '',
                'tipaco_codigo'                      => $c->tipaco_codigo ?? '',
                'itesoltra_acompanamientocuentade'   => $c->itesoltra_acompanamientocuentade ?? '',
                'itesoltra_acompanamientovalor'      => $c->itesoltra_acompanamientovalor ?? ''
            ];
            $this->selectedPricings[$index] = $pricing;

            // === Nuevo: Llenar $pricings para cada ruta ===
            // Simula la lógica de saveCotizacion
            $ciudad_origen = strtolower($c->ciudad_origen ?? '');
            $ciudad_destino = strtolower($c->ciudad_destino ?? '');
            $weightKg = $c->peso_mercancia ?? 0;
            // $weightTonnes = $weightKg / 1000;
            $weightTonnes = floatval($weightKg) / 1000;


            $subquery = DB::table('pricings')
                ->select('vehicle_type', DB::raw('MIN(id) as id'))
                ->where('origin', $ciudad_origen)
                ->where('destination', $ciudad_destino)
                ->where('weight', '>=', $weightTonnes)
                ->groupBy('vehicle_type');

            // $pricings = DB::table('pricings as p1')
            //     ->joinSub($subquery, 'p2', function ($join) {
            //         $join->on('p1.id', '=', 'p2.id');
            //     })
            //     ->where('p1.origin', $ciudad_origen)
            //     ->where('p1.destination', $ciudad_destino)
            //     ->where('p1.weight', '>=', $weightTonnes)
            //     ->orderByRaw("ABS(p1.weight - ?)", [$weightTonnes])
            //     ->limit(3)
            //     ->get();
            $pricings = Pricing::where('origin', $ciudad_origen)
                ->where('destination', $ciudad_destino)
                ->whereNotNull('vehicle_type')
                ->where('weight', '>=', $weightTonnes)
                ->orderBy('vehicle_type')
                ->get();

            $this->pricings[$index] = $pricings;
            $this->calculatePrice($index);
        }

        // Datos globales de grupo/cliente
        $this->type_business = $group->type;
        $this->client_id = $group->client_id;

        $client = Client::find($group->client_id);
        if ($client) {
            $this->client_name = $client->cliente;
            $this->client_document_client = $client->documento;
            $this->client_company_name = $client->cliente;
            $this->client_location = $client->ciudad;
            $this->client_phone_numbers = $client->telefono;
            $this->client_personal_cell = $client->celular;
            $this->client_email = $client->email;
            $this->client_address = $client->direccion;
            $this->client_branch_office = $client->branch_office;
            $this->client_sales_representative = $client->vendedor_nombre;
        }

        $this->showModal3 = true;
    }

    public function handleClickPricing($index)
    {
        $selectedId = $this->quote_data[$index]['select_value'] ?? null;
        $pricing = Pricing::find($selectedId);
        $this->selectedPricings[$index] = $pricing;

        // ACTUALIZA vehículo requerido según el pricing
        if ($pricing) {
            $this->quote_data[$index]['vehiculo_requerido'] = $pricing->vehicle_type ?? '';
        } else {
            $this->quote_data[$index]['vehiculo_requerido'] = '';
        }

        $this->calculatePrice($index);
    }

    public function handleChangeStatus($index, $selected)
    {
        $this->porcentajes1[$index] = false;
        $this->porcentajes2[$index] = false;
        $this->porcentajes3[$index] = false;

        if ($selected == '1') {
            $this->porcentajes1[$index] = true;
        } elseif ($selected == '2') {
            $this->porcentajes2[$index] = true;
        } elseif ($selected == '3') {
            $this->porcentajes3[$index] = true;
        }
    }

    // public function calculatePrice($index)
    // {
    //     $selected = $this->selectedPricings[$index] ?? null;
    //     $porcentaje = $this->quote_data[$index]['porcentaje'] ?? null;

    //     // ✅ Evitar cálculo si porcentaje está vacío o inválido
    //     if ($porcentaje === null || $porcentaje === '' || !is_numeric($porcentaje)) {
    //         // No marcar como modificado, no calcular nada
    //         unset($this->quote_data[$index]['calculated_price']);
    //         unset($this->porcentaje_modificado[$index]);
    //         return;
    //     }

    //     // ✅ Si el porcentaje es válido, continuar
    //     $this->porcentaje_modificado[$index] = true;

    //     if ($selected) {
    //         // Usar floats para cálculos más precisos
    //         $basePrice = floatval($selected->price);
    //         $percentage = floatval($porcentaje);
            
    //         // Fórmula: Precio base + (Precio base * Porcentaje / 100)
    //         $calculatedPrice = $basePrice + ($basePrice * $percentage / 100);
            
    //         // Redondear a entero para mostrar
    //         $this->quote_data[$index]['calculated_price'] = round($calculatedPrice);
            
    //         \Log::info("Cálculo de precio para ruta $index:", [
    //             'precio_base' => $basePrice,
    //             'porcentaje' => $percentage,
    //             'precio_calculado' => $calculatedPrice,
    //             'precio_final_redondeado' => round($calculatedPrice)
    //         ]);
    //     }
    // }
    /**
     *  Recalcula el valor de venta al cliente de la ruta $index
     *  – incluye rentabilidad y acompañamiento –
     */
    public function calculatePrice(int $index)
    {
        /* ----------------------------------------------
        *  1.  Validar porcentaje
        * ---------------------------------------------*/
        $porcentaje = $this->quote_data[$index]['porcentaje'] ?? null;

        if ($porcentaje === null || $porcentaje === '' || !is_numeric($porcentaje)) {
            // Si el porcentaje está vacío o no es válido limpiamos valores
            unset($this->quote_data[$index]['valor_final']);
            unset($this->quote_data[$index]['calculated_price']);
            unset($this->porcentaje_modificado[$index]);
            return;
        }

        // Marcar que este porcentaje se cambió manualmente
        $this->porcentaje_modificado[$index] = true;


        /* ----------------------------------------------
        *  2.  Datos base
        * ---------------------------------------------*/
        $pricing   = $this->selectedPricings[$index] ?? null;
        if (!$pricing) {                       // aún no hay vehículo escogido
            unset($this->quote_data[$index]['valor_final']);
            return;
        }

        $precioBase  = floatval($pricing->price);
        $rentab      = floatval($porcentaje);


        /* ----------------------------------------------
        *  3.  Calcular subtotal por acompañamiento
        * ---------------------------------------------*/
        $cantAcomp   = intval($this->quote_data[$index]['itesoltra_vehiculoacompanamiento'] ?? 0);
        $valorAcomp  = floatval($this->quote_data[$index]['itesoltra_acompanamientovalor']  ?? 0);
        $subtotalA   = $cantAcomp * $valorAcomp;   // puede ser 0


        /* ----------------------------------------------
        *  4.  Total cliente
        * ---------------------------------------------*/
        $clienteBase = $precioBase + ($precioBase * $rentab / 100);
        $valorFinal  = round($clienteBase + $subtotalA);

        // Guardar resultados en la ruta
        $this->quote_data[$index]['calculated_price']      = round($clienteBase); // sólo base + rentab
        $this->quote_data[$index]['subtotal_acompanante']  = $subtotalA;
        $this->quote_data[$index]['valor_final']           = $valorFinal;

        \Log::info("Cálculo ruta {$index}", [
            'precio_base'            => $precioBase,
            'rentabilidad_%'         => $rentab,
            'cliente_base'           => $clienteBase,
            'cant_acomp'             => $cantAcomp,
            'valor_acomp_unitario'   => $valorAcomp,
            'subtotal_acompanante'   => $subtotalA,
            'valor_final'            => $valorFinal,
        ]);
    }

    // Se ejecutará para cualquier cambio en los campos indicados
    public function updatedQuoteData($value, $key)
    {
        // $key = "quote_data.3.itesoltra_acompanamientovalor", etc.
        if (preg_match('/quote_data\.(\d+)\.(itesoltra_vehiculoacompanamiento|itesoltra_acompanamientovalor)/', $key, $m)) {
            $index = intval($m[1]);
            $this->calcularValorFinal($index);
        }

        // Manejar cambios en el switch de acompañamiento
        if (preg_match('/quote_data\.(\d+)\.acompanamiento_enabled/', $key, $m)) {
            $index = intval($m[1]);
            $this->toggleAcompanamiento($index);
        }
    }

    public function closeModal2()
    {
        $this->showModal4 = false;
        $this->quote = [];
    }

    public function closeModal($step)
    {

        $this->hydrateClientData();

        if ($step == 1) {
            $this->showModal = false;
            $this->quote = [];
        }
        if ($step == 2) {
            $this->showModal2 = false;
            $this->step = 0;
        }
        if ($step == 3) {
            $this->showModal3 = false;
        }
        if ($step == 4) {
            // Validar email antes de enviar
            if (!$this->validateEmailForSending($this->cliente_email)) {
                Log::error('Email inválido para envío', [
                    'email' => $this->cliente_email,
                    'user_id' => auth()->id()
                ]);
                
                $this->dispatch('show-alert', [
                    'type' => 'error',
                    'message' => 'El correo electrónico no es válido. Por favor, ingresa una dirección de correo válida.'
                ]);
                return;
            }

            $this->showModal4 = false;
            sleep(1);
            
            $this->storeST();

            try {
                // Usar email limpio y validado - SIEMPRE tomar el del campo cliente_email
                $cleanEmail = trim($this->cliente_email);
                
                Log::info('Enviando email a dirección validada', [
                    'email' => $cleanEmail,
                    'manually_set' => $this->cliente_email_manually_set,
                    'registered_email' => $this->client_email_registered,
                    'available_emails' => $this->client_emails_available,
                    'user_id' => auth()->id()
                ]);

                Mail::to($cleanEmail)->send(new \App\Mail\StepCompleted([
                // Cliente
                'group_cotization_id'   => $this->group_cotization_id,
                'client_name'           => $this->client_name,
                'client_document'       => $this->client_document_client,
                'client_location'       => $this->client_location,
                'client_phone_numbers'  => $this->client_phone_numbers,
                // Encabezados y saludo
                'title'                 => $this->title_email,
                'text'                  => $this->prompt_response,
                'greeting'              => $this->greeting,
                // Rutas y totales (calcular incluyendo acompañamiento)
                'routes'                => $this->prepareQuoteDataForEmail(),
                'total_price'           => $this->calculateTotalWithAccompaniment(),
                // Asesor
                'asesor_name'           => $this->asesor_name_email,
                'asesor_phone'          => $this->asesor_phone_email,
                'asesor_email'          => $this->asesor_email_email,
                'asesor_pbx'            => $this->asesor_pbx_email,
                'asesor_ubicacion'      => $this->asesor_ubicacion_email,
                'advisor_signature'     => $this->advisor_signature ?? null,
                // Otros
                'id_last_created'       => $this->id_last_created,
                'created_at'            => now(),
                'acceptOfferDecision'   => $this->acceptOfferDecision ?? '',
            ]));

            $this->getAllQuotes();
            if (is_null($this->group_cotization_id)) {
                $this->client->openai_current_run = null;
                $this->client?->save();
            }
            $this->dispatch('refreshPage');
            
            Log::info('Email enviado exitosamente', [
                'email' => $cleanEmail,
                'user_id' => auth()->id()
            ]);
            
            } catch (\Exception $e) {
                Log::error('Error al enviar email', [
                    'email' => $cleanEmail ?? $this->cliente_email,
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id()
                ]);
                
                $this->dispatch('show-alert', [
                    'type' => 'error',
                    'message' => 'Error al enviar el correo. Por favor, verifica la dirección e intenta nuevamente.'
                ]);
                return;
            }
        }

        if ($step == 9) {
            $this->showModal9 = false;
        }
    }

    public function closeModalSuccess()
    {
        $this->showModalSuccess = false;
        return redirect()->route('quotes.show');
    }

    public function nextStep($step)
    {
        if ($step == 1) {

            $hayError = false;

            foreach ($this->quote_data as $i => &$route) {

                // Si el porcentaje no está definido, se intenta aplicar el porcentaje global
                if (!isset($route['porcentaje']) || $route['porcentaje'] === '') {
                    $route['porcentaje'] = $this->porcentaje_global ?? 17;
                }

                // Validación del porcentaje
                if ($route['porcentaje'] < 17) {
                    $this->errores_porcentaje[$i] = 'El porcentaje no puede ser menor a 17%';
                    $hayError = true;
                    continue;
                } else {
                    unset($this->errores_porcentaje[$i]); // Limpiar error si ya no aplica
                }

                // Calcular valor final con rentabilidad
                // $pricing = $this->selectedPricings[$i] ?? null;
                // if ($pricing) {
                //     $valor_base = $pricing->price;
                //     $porcentaje = $route['porcentaje'];
                //     $valor_cliente = $valor_base + ($valor_base * $porcentaje / 100);
                //     $route['valor_final'] = $valor_cliente;
                // }
                $this->calculatePrice($i);  
            }

            // Si hay errores, no pasar de paso
            if ($hayError) {
                $this->dispatchBrowserEvent('mostrar-alerta', [
                    'tipo' => 'error',
                    'mensaje' => 'Revisa los porcentajes: deben ser iguales o mayores a 17%.'
                ]);
                return;
            }

            $this->closeModal(3);
            sleep(1);
            $this->openModalCreate(4);
        }
    }

    // public function submitClient()
    // {
    //     $this->validate([
    //         'search' => 'required'
    //     ]);

    //     $client_name = '';
    //     $existing_client = Client::where('document', $this->search)->first();
    //     if ($existing_client) {
    //         $client_name = $existing_client->name;
    //         $existing_client->openai_thread_id = null;
    //         $existing_client->save();
    //         $this->client = $existing_client;
    //     } 
    //     else {
    //         $client_name = SilogtranService::getClient2($this->search);
    //         if (!$client_name) {
    //             $this->addError('search', 'Cliente no encontrado');
    //             return;
    //         }

    //         $this->client = Client::firstOrCreate(
    //             [
    //                 'document' => $this->search
    //             ],
    //             [
    //                 'name' => $client_name['nombre'],
    //                 'codigo' => $client_name['Codigo']
    //             ]
    //         );
    //     }

    //     $this->client_name = $existing_client->name;

    //     $this->openai_thread = QuoteAssistantService::getThread($this->client);
    //     $this->openai_current_run = $this->client->openai_current_run;
    //     $this->step = 1;
    // }

    public function submitClient()
    {
        $this->validate([
            'search' => 'required'
        ]);

        /* ── 1.  ¿existe el cliente en nuestra BD? ───────────────────────────── */
        $cliente = Client::where('documento', $this->search)->first();
        $cliente = Client::where('documento', $this->search)->first();

        if ($cliente) {
            // flujo normal
            $cliente->openai_thread_id   = null;
            $cliente->openai_current_run = null;
            $cliente->save();

            $this->client      = $cliente;
            $this->client_name = $cliente->cliente;

            $this->openai_thread      = MCPAssistantService::getThread($cliente);
            $this->openai_current_run = $cliente->openai_current_run;
            $this->step = 1;
            return;
        }

        /* ── 2.  NO existe: avisamos y proponemos crear contacto ─────────────── */
        $this->dispatch(               //  ←  Livewire v3
            'cliente-no-existe',
            mensaje: 'El cliente no existe. Debe crearlo antes de continuar.',
            url:      url('/contacts')  //  http://127.0.0.1:8000/contacts
        );
    }

    // public function syncChat()
    // {
    //     if ($this->openai_thread && $this->step == 1) {
    //         $this->messages = QuoteAssistantService::getMessages($this->openai_thread);

    //         if ($this->openai_current_run) {
    //             $quote_data = QuoteAssistantService::checkRunStatus($this->openai_thread, $this->openai_current_run);

    //             if (is_null($quote_data)) {
    //                 Log::info('Assistant aún no responde. Polling...');
    //                 return; // simplemente espera a la próxima llamada de syncChat
    //             }

    //             if ($quote_data == 'finished') {
    //                 // Puedes limpiar run, pero probablemente nunca venga puro asi
    //                 $this->openai_current_run = null;
    //                 $this->client->openai_current_run = null;
    //                 $this->client->save();
    //                 Log::info("Asistente terminó (status finished)");
    //                 // Muestra mensaje al usuario si quieres
    //                 return;
    //             } else if (is_array($quote_data) && !empty($quote_data)) {
    //                 // Asumimos que ya vienen las rutas cotizadas
    //                 $this->quote_data = $quote_data;
    //                 $this->openai_current_run = null;
    //                 $this->client->openai_current_run = null;
    //                 $this->client->save();
    //                 Log::info("COTIZACION LISTA:", $this->quote_data);
    //             } else {
    //                 Log::info('Salida inesperada de checkRunStatus:', [$quote_data]);
    //             }
    //         }
    //     }
    // }

    public function syncChat()
    {
        if ($this->openai_thread && $this->step == 1) {

            // Verificar que tengamos thread_id antes de continuar
            if (!$this->openai_thread) {
                Log::warning('syncChat: No hay thread_id disponible');
                return;
            }
            
            $this->messages = MCPAssistantService::getMessages($this->openai_thread);

            if ($this->openai_current_run) {
                $runStatus = MCPAssistantService::checkRunStatus($this->openai_thread,
                                                                    $this->openai_current_run);

                // -- aún esperando la respuesta
                if (is_null($runStatus)) { 
                    Log::info('syncChat: Run aún en progreso', [
                        'run_id' => $this->openai_current_run
                    ]);
                    return; 
                }

                // MCP devuelve un objeto con 'status' y 'extracted_data'
                $quote_data = null;
                
                if (is_array($runStatus)) {
                    // Priorizar extracted_data si existe
                    if (isset($runStatus['extracted_data']) && !empty($runStatus['extracted_data'])) {
                        $quote_data = $runStatus['extracted_data'];
                        Log::info('syncChat - Usando extracted_data de MCP', [
                            'count' => count($quote_data)
                        ]);
                    } elseif (isset($runStatus['quote_data']) && !empty($runStatus['quote_data'])) {
                        $quote_data = $runStatus['quote_data'];
                        Log::info('syncChat - Usando quote_data de MCP', [
                            'count' => count($quote_data)
                        ]);
                    }
                }

                /* ==========  NUEVO BLOQUE ========== */
                if (is_array($quote_data) && !empty($quote_data)) {

                    // NORMALIZAR datos: copiar campos con nombres alternativos
                    foreach ($quote_data as $index => $ruta) {
                        // Si viene 'producto' pero no 'tipo_producto', normalizar
                        if (isset($ruta['producto']) && !isset($ruta['tipo_producto'])) {
                            $quote_data[$index]['tipo_producto'] = $ruta['producto'];
                            Log::info("Normalizado campo 'producto' a 'tipo_producto'", [
                                'index' => $index,
                                'valor' => $ruta['producto']
                            ]);
                        }
                        
                        // Si viene 'product' pero no 'tipo_producto', normalizar
                        if (isset($ruta['product']) && !isset($ruta['tipo_producto'])) {
                            $quote_data[$index]['tipo_producto'] = $ruta['product'];
                            Log::info("Normalizado campo 'product' a 'tipo_producto'", [
                                'index' => $index,
                                'valor' => $ruta['product']
                            ]);
                        }
                        
                        // Si viene 'valor_mercancia' pero no 'valor_declarado', normalizar
                        if (isset($ruta['valor_mercancia']) && !isset($ruta['valor_declarado'])) {
                            $quote_data[$index]['valor_declarado'] = $ruta['valor_mercancia'];
                            Log::info("Normalizado campo 'valor_mercancia' a 'valor_declarado'", [
                                'index' => $index,
                                'valor' => $ruta['valor_mercancia']
                            ]);
                        }
                    }

                    // LOG PARA DEPURACIÓN: Ver qué datos están llegando
                    Log::info('syncChat - Datos de cotización recibidos', [
                        'run_id' => $this->openai_current_run,
                        'thread_id' => $this->openai_thread,
                        'client_id' => $this->client_id,
                        'rutas_count' => count($quote_data)
                    ]);
                    
                    Log::info('syncChat - Detalle de rutas:', [
                        'quote_data' => $quote_data,
                        'tiene_tipo_producto' => isset($quote_data[0]['tipo_producto']) ? 'SI' : 'NO',
                        'valor_tipo_producto' => $quote_data[0]['tipo_producto'] ?? 'NO EXISTE'
                    ]);

                    $this->quote_data = $quote_data;
                    $this->vehicleSuggestions = [];      // limpiamos

                    foreach ($this->quote_data as $i => $ruta) {
                        // pedir sugerencia ruta por ruta
                        $this->vehicleSuggestions[$i] = $this->getVehicleSuggestion($ruta);
                    }
                /* ==========  FIN BLOQUE ========== */

                    $this->openai_current_run = null;
                    $this->client->openai_current_run = null;
                    $this->client->save();
                }
            }
        }
    }

    public function sendMessage()
    {
        // Debug temporal: verificar estado del thread
        \Log::info('sendMessage invocado', [
            'thread' => $this->openai_thread,
            'input' => $this->input_message,
            'client_id' => $this->client_id ?? 'no definido',
            'messages_count' => count($this->messages)
        ]);
        
        $this->validate([
            'input_message' => 'required|string|min:1'
        ]);

        // Guardar el mensaje antes de procesarlo
        $messageContent = $this->input_message;

        // Limpiar INMEDIATAMENTE después de obtener el contenido
        $this->input_message = '';

        $new_message = MCPAssistantService::createMessage($this->openai_thread, $messageContent);
        
        \Log::info('Resultado de createMessage', [
            'new_message' => $new_message,
            'thread_usado' => $this->openai_thread
        ]);
        
        if ($new_message) {
            $this->messages[] = $new_message;

            $run = MCPAssistantService::runAssistant($this->openai_thread, $this->type_business);
            if (isset($run['id'])) {
                $this->openai_current_run = $run['id'];
                
                // MEJORADO: Guardar tanto thread como run en el cliente
                $this->client->openai_thread_id = $this->openai_thread;
                $this->client->openai_current_run = $this->openai_current_run;
                $this->client->save();
                
                // NUEVO: También actualizar la sesión con el run_id
                $session = ConversationSession::where('session_id', $this->openai_thread)
                    ->where('client_id', $this->client->id)
                    ->first();
                    
                if ($session) {
                    $metadata = json_decode($session->metadata, true) ?? [];
                    $metadata['current_run_id'] = $this->openai_current_run;
                    $metadata['last_run_at'] = now()->toIso8601String();
                    $session->metadata = json_encode($metadata);
                    $session->save();
                }
                
                Log::info('Thread y Run guardados', [
                    'client_id' => $this->client->id,
                    'thread_id' => $this->openai_thread,
                    'run_id' => $this->openai_current_run
                ]);
            } else {
                Log::error("No se pudo crear run", [
                    'run_response' => $run,
                    'thread_id' => $this->openai_thread
                ]);
            }
        }

        // Asegurar que está limpio y forzar refresco
        $this->reset('input_message');
        
        // Emitir eventos para notificar al frontend
        $this->dispatch('input-cleared');
        
        // Forzar JavaScript directamente
        $this->js('
            setTimeout(function() {
                const textarea = document.getElementById("chat-textarea");
                if (textarea) {
                    textarea.value = "";
                    textarea.dispatchEvent(new Event("input", { bubbles: true }));
                    textarea.dispatchEvent(new Event("change", { bubbles: true }));
                }
            }, 50);
        ');
    }

    // public function saveCotizacion()
    // {
    //     $this->pricings       = [];   //  resultados de búsqueda
    //     $this->missingRoutes  = [];   //  rutas sin precio (para mensaje)

    //     foreach ($this->quote_data as $data) {

    //         /* a. Validación mínima */
    //         if (!is_array($data)) {
    //             continue;                 //  o lanza excepción si lo prefieres
    //         }

    //         /* b. Parámetros normalizados */
    //         $origin      = strtolower(trim($data['ciudad_origen']   ?? ''));
    //         $destination = strtolower(trim($data['ciudad_destino']  ?? ''));
    //         $weightKg    = (float) ($data['peso_mercancia'] ?? 0);
    //         $weightTon   = $weightKg / 1000;

    //         /* c. Consulta de precios */
    //         $pricings = Pricing::query()
    //             ->where('origin', $origin)
    //             ->where('destination', $destination)
    //             ->whereNotNull('vehicle_type')
    //             ->where('weight', '>=', $weightTon)
    //             ->orderBy('vehicle_type')
    //             ->get();

    //         $this->pricings[] = $pricings;   //  guarda para la vista

    //         /* d. Si NO hay precio -> crear Solicitation */
    //         if ($pricings->isEmpty()) {

    //             /* evita duplicar solicitudes idénticas: firstOrCreate */
    //             $solicitation = Solicitation::firstOrCreate(
    //                 [
    //                     'origin'      => $origin,
    //                     'destination' => $destination,
    //                     'created_by'  => Auth::id(),        //  usuario actual
    //                     'status'      => 'PENDING',
    //                 ],
    //                 [   //  valores por defecto al crear
    //                     'importance'  => 'MEDIUM',
    //                     'description' => 'Solicitud generada automáticamente desde cotización',
    //                 ]
    //             );

    //             // guarda ID para informar al usuario más tarde
    //             $this->missingRoutes[] = $solicitation->id;
    //         }
    //     }

    //     /* e. Mensaje de feedback opcional */
    //     if (count($this->missingRoutes)) {
    //         session()->flash(
    //             'info',
    //             count($this->missingRoutes) . ' rutas no tenían precio; se crearon solicitudes #' .
    //             implode(', ', $this->missingRoutes)
    //         );
    //     }

    //     /* f. continúa con tu flujo de UI */
    //     $this->step = 0;
    //     $this->closeModal(2);
    //     sleep(1);
    //     $this->openModalCreate(3);
    // }

    public function saveCotizacion()
    {
        $this->pricings      = [];   // resultados de búsqueda
        $this->missingRoutes = [];   // rutas sin precio o sin código

        Log::info('Iniciando saveCotizacion');
        Log::debug('quote_data recibido:', $this->quote_data);

        // Validar que todas las rutas tengan tipo_producto
        foreach ($this->quote_data as $idx => $data) {
            if (!is_array($data)) {
                continue;
            }
            
            if (empty($data['tipo_producto'])) {
                Log::warning("Ruta $idx no tiene tipo_producto, deteniendo flujo", ['data' => $data]);
                session()->flash('error', 'Por favor proporciona el tipo de producto para todas las rutas antes de crear la cotización.');
                return;
            }
        }

        foreach ($this->quote_data as $idx => $data) {
            Log::info("Iteración $idx de quote_data");

            /* a. Validación mínima */
            if (!is_array($data)) {
                Log::warning("Elemento no es array en index $idx", ['data' => $data]);
                continue;
            }

            /* b. Normalizar nombres */
            $originName      = strtolower(trim($data['ciudad_origen']  ?? ''));
            $destinationName = strtolower(trim($data['ciudad_destino'] ?? ''));
            Log::debug("Nombres normalizados", [
                'originName' => $originName,
                'destinationName' => $destinationName
            ]);

            /* c. Resolver código DANE según TU tabla cities */
            $codigoOrigen   = CityHelper::daneCode($originName);
            $codigoDestino  = CityHelper::daneCode($destinationName);
            Log::info("DANE resuelto", [
                'originName'     => $originName,
                'destinationName'=> $destinationName,
                'codigoOrigen'   => $codigoOrigen,
                'codigoDestino'  => $codigoDestino
            ]);

            /* d. Si alguna ciudad no existe, detén flujo y pide selección manual */
            if (!$codigoOrigen || !$codigoDestino) {
                Log::warning("Ciudad no encontrada", [
                    'codigoOrigen'   => $codigoOrigen,
                    'codigoDestino'  => $codigoDestino,
                    'ciudad_origen'  => $data['ciudad_origen']  ?? '',
                    'ciudad_destino' => $data['ciudad_destino'] ?? ''
                ]);
                $this->dispatch(
                    'ciudad-no-encontrada',
                    nombres: [
                        'origen'   => $data['ciudad_origen']  ?? '',
                        'destino'  => $data['ciudad_destino'] ?? ''
                    ]
                );
                Log::info("Flujo detenido por ciudad no encontrada");
                return;
            }

            /* e. Convertir peso a toneladas para búsqueda de precio */
            $weightKg  = (float) ($data['peso_mercancia'] ?? 0);
            $weightTon = $weightKg / 1000;
            Log::debug("Pesos convertidos", [
                'peso_mercancia_kg' => $weightKg,
                'peso_toneladas'    => $weightTon
            ]);

            /* f. Consulta de precios */
            $pricings = Pricing::query()
                ->where('origin',      $originName)
                ->where('destination', $destinationName)
                ->whereNotNull('vehicle_type')
                ->where('weight', '>=', $weightTon)
                ->orderBy('vehicle_type')
                ->get();

            Log::debug("Resultados de pricing", [
                'origin'      => $originName,
                'destination' => $destinationName,
                'pricings'    => $pricings->toArray()
            ]);

            $this->pricings[] = $pricings;   //  guarda para la vista

            /* g. Si NO hay precio -> generar Solicitation */
            if ($pricings->isEmpty()) {
                Log::info("No se encontraron precios, creando solicitud", [
                    'origin'      => $originName,
                    'destination' => $destinationName,
                ]);
                $solicitation = Solicitation::firstOrCreate(
                    [
                        'origin'      => $originName,
                        'destination' => $destinationName,
                        'created_by'  => Auth::id(),
                        'status'      => 'PENDING',
                    ],
                    [
                        'importance'  => 'MEDIUM',
                        'description' => 'Solicitud generada automáticamente desde cotización',
                    ]
                );
                Log::info("Solicitud creada o encontrada", [
                    'solicitation_id' => $solicitation->id,
                ]);
                $this->missingRoutes[] = $solicitation->id;
            }

            /* h. Guarda en quote_data los códigos que acabamos de resolver */
            Log::debug("Antes de overwrite", [
                'codigo_dane_origen' => $this->quote_data[$idx]['codigo_dane_origen'] ?? null,
                'codigo_dane_destino' => $this->quote_data[$idx]['codigo_dane_destino'] ?? null
            ]);
            $this->quote_data[$idx]['codigo_dane_origen'] = $codigoOrigen;
            $this->quote_data[$idx]['codigo_dane_destino'] = $codigoDestino;
            Log::debug("Después de overwrite", [
                'codigo_dane_origen' => $this->quote_data[$idx]['codigo_dane_origen'],
                'codigo_dane_destino'=> $this->quote_data[$idx]['codigo_dane_destino']
            ]);
        }

        /* i. Feedback opcional */
        if (count($this->missingRoutes)) {
            Log::info("Solicitudes creadas para rutas sin precio", [
                'missing_routes_ids' => $this->missingRoutes
            ]);
            session()->flash(
                'info',
                count($this->missingRoutes) .
                ' rutas no tenían precio; se crearon solicitudes #' .
                implode(', ', $this->missingRoutes)
            );
        }

        /* j. Continúa flujo UI */
        Log::info("Finalizando flujo de saveCotizacion");
        $this->step = 0;
        $this->closeModal(2);
        sleep(1);
        $this->openModalCreate(3);
    }

    public function closeModalSuccess2()
    {
        $this->showModal3 = false;
        $this->quote_data = [];
    }

    public function aplicarPorcentajeGlobal($porcentaje)
    {
        \Log::info("=== APLICAR PORCENTAJE GLOBAL ===", [
            'porcentaje_recibido' => $porcentaje,
            'rutas_antes' => collect($this->quote_data)->map(function($route, $index) {
                return ['index' => $index, 'porcentaje' => $route['porcentaje'] ?? 'null'];
            })->toArray()
        ]);
        
        $this->porcentaje_global = $porcentaje;

        // Limpiar las modificaciones manuales para permitir que el porcentaje global se aplique
        $this->porcentaje_modificado = [];
        $this->errores_porcentaje = [];

        foreach ($this->quote_data as $index => $item) {
            $oldValue = $this->quote_data[$index]['porcentaje'] ?? 'null';
            $this->quote_data[$index]['porcentaje'] = $porcentaje;
            
            \Log::info("Actualizando ruta $index:", [
                'valor_anterior' => $oldValue,
                'valor_nuevo' => $porcentaje,
                'ruta' => $item['ciudad_origen'] . ' -> ' . $item['ciudad_destino']
            ]);
            
            // Recalcular precio con el nuevo porcentaje
            $this->calculatePrice($index);
        }
        
        \Log::info("Estado final:", [
            'porcentaje_global' => $this->porcentaje_global,
            'rutas_despues' => collect($this->quote_data)->map(function($route, $index) {
                return ['index' => $index, 'porcentaje' => $route['porcentaje'] ?? 'null'];
            })->toArray()
        ]);
        
        // Forzar actualización de Livewire
        $this->dispatch('porcentaje-updated');
    }

    public function resetPorcentajeGlobal()
    {
        \Log::info("=== RESET PORCENTAJE GLOBAL ===");
        
        $this->porcentaje_global = null;

        // Limpiar todas las modificaciones manuales y errores
        $this->porcentaje_modificado = [];
        $this->errores_porcentaje = [];

        // Limpiar porcentajes en todas las rutas
        foreach ($this->quote_data as $index => $item) {
            $this->quote_data[$index]['porcentaje'] = null; // Limpiar en lugar de 17
            // Limpiar precio calculado
            unset($this->quote_data[$index]['calculated_price']);
        }
        
        \Log::info("Porcentajes limpiados");
        
        // Forzar actualización de Livewire
        $this->dispatch('porcentaje-reset');
    }

    public function editarPorcentajeIndividual($index)
    {
        \Log::info("=== EDITAR PORCENTAJE INDIVIDUAL ===", [
            'index' => $index,
            'valor_recibido' => $this->quote_data[$index]['porcentaje'] ?? 'null'
        ]);
        
        // Resetear el porcentaje global cuando se modifica manualmente
        $this->porcentaje_global = null;
        
        $valor = $this->quote_data[$index]['porcentaje'] ?? null;
        
        // Si el valor está vacío o es null, limpiar errores y no marcar como modificado
        if ($valor === null || $valor === '' || !is_numeric($valor)) {
            \Log::info("Valor vacío o no numérico, limpiando...");
            unset($this->errores_porcentaje[$index]);
            unset($this->porcentaje_modificado[$index]);
            unset($this->quote_data[$index]['calculated_price']);
            return;
        }
        
        // Convertir a número
        $valor = (float) $valor;
        $this->quote_data[$index]['porcentaje'] = $valor;
        
        \Log::info("Valor convertido:", ['valor' => $valor]);

        // Validación mínima de 17%
        if ($valor < 17) {
            \Log::info("Error: Valor menor a 17%");
            $this->errores_porcentaje[$index] = 'El porcentaje no puede ser menor a 17%.';
            $this->porcentaje_modificado[$index] = true;
            return;
        }

        // Si es válido, limpiamos el error y marcamos como modificado
        unset($this->errores_porcentaje[$index]);
        $this->porcentaje_modificado[$index] = true;
        
        \Log::info("Llamando a calculatePrice...");
        // Calcular precio automáticamente
        $this->calculatePrice($index);
        
        \Log::info("Precio calculado:", [
            'calculated_price' => $this->quote_data[$index]['calculated_price'] ?? 'no definido'
        ]);
    }

    public function restaurarPorcentajeGlobal($index)
    {
        $this->porcentaje_modificado[$index] = false;
        $this->quote_data[$index]['porcentaje'] = null; // Dejar el campo vacío/limpio
        
        // Limpiar cualquier error asociado
        if (isset($this->errores_porcentaje[$index])) {
            unset($this->errores_porcentaje[$index]);
        }
    }

    // Método de prueba para debugging
    public function testSetPercentage($index, $percentage)
    {
        \Log::info("=== TEST SET PERCENTAGE ===", [
            'index' => $index,
            'percentage' => $percentage,
            'antes_quote_data' => $this->quote_data[$index] ?? 'no existe'
        ]);
        
        $this->quote_data[$index]['porcentaje'] = $percentage;
        $this->editarPorcentajeIndividual($index);
        
        \Log::info("Después del test:", [
            'quote_data' => $this->quote_data[$index],
            'porcentaje_modificado' => $this->porcentaje_modificado[$index] ?? 'no definido',
            'calculated_price' => $this->quote_data[$index]['calculated_price'] ?? 'no calculado'
        ]);
    }

    /**
     * Método que se ejecuta cuando se activa/desactiva el switch de acompañamiento
     * Si se desactiva, limpia los valores de acompañamiento y recalcula el precio
     */
    public function toggleAcompanamiento($index)
    {
        \Log::info("=== TOGGLE ACOMPAÑAMIENTO ===", [
            'index' => $index,
            'estado_switch' => $this->quote_data[$index]['acompanamiento_enabled'] ?? 'undefined'
        ]);

        // Si el switch se desactiva (false), limpiar todos los valores de acompañamiento
        if (empty($this->quote_data[$index]['acompanamiento_enabled'])) {
            \Log::info("Switch desactivado - limpiando valores de acompañamiento");
            
            // Limpiar todos los campos de acompañamiento estableciendo valores específicos
            $this->quote_data[$index]['itesoltra_vehiculoacompanamiento'] = '';
            $this->quote_data[$index]['tipaco_codigo'] = '';
            $this->quote_data[$index]['itesoltra_acompanamientocuentade'] = '';
            $this->quote_data[$index]['itesoltra_acompanamientovalor'] = 0; // Establecer en 0 para limpiar el input
            $this->quote_data[$index]['subtotal_acompanante'] = 0;

            // Recalcular el precio sin acompañamiento
            $this->calculatePrice($index);
            
            // Forzar actualización del frontend
            $this->dispatch('refresh');
            
            \Log::info("Valores limpiados y precio recalculado", [
                'nuevo_valor_final' => $this->quote_data[$index]['valor_final'] ?? 'no definido',
                'valor_acompanamiento_limpiado' => $this->quote_data[$index]['itesoltra_acompanamientovalor']
            ]);
        }
    }

    public function agregarAcompañamiento()
    {
        \Log::info('=== AGREGAR ACOMPAÑAMIENTO INICIADO ===');
        \Log::info('Estado inicial:', $this->acompañamientos);
        
        // Verificar que existe al menos una fila
        if (empty($this->acompañamientos)) {
            $this->acompañamientos[] = [
                'id' => uniqid(),
                'tipo' => '',
                'cuenta_de' => '',
                'cantidad_vehiculos' => 1,
                'valor' => 0
            ];
            \Log::info('Primera fila creada porque no existía');
            return;
        }
        
        $primeraFila = $this->acompañamientos[0];
        \Log::info('Primera fila actual:', $primeraFila);
        
        // Solo duplicar si la primera fila tiene datos
        $tieneDatos = !empty($primeraFila['tipo']) || 
                     !empty($primeraFila['cuenta_de']) || 
                     $primeraFila['cantidad_vehiculos'] > 1 || 
                     $primeraFila['valor'] > 0;
        
        \Log::info('Primera fila tiene datos:', ['tiene_datos' => $tieneDatos]);
        
        if ($tieneDatos) {
            // Crear nueva fila con los datos de la primera
            $nuevaFila = [
                'id' => uniqid(),
                'tipo' => $primeraFila['tipo'] ?? '',
                'cuenta_de' => $primeraFila['cuenta_de'] ?? '',
                'cantidad_vehiculos' => $primeraFila['cantidad_vehiculos'] ?? 1,
                'valor' => $primeraFila['valor'] ?? 0
            ];
            
            // Agregar la nueva fila
            $this->acompañamientos[] = $nuevaFila;
            \Log::info('Nueva fila agregada:', $nuevaFila);
            
            // Llamar al método específico para resetear
            $this->resetearPrimeraFilaInterna();
            
            // Forzar múltiples tipos de actualización
            $this->dispatch('$refresh');
            $this->skipRender();
            
            // JavaScript agresivo para limpiar el DOM
            $idPrimeraFila = $primeraFila['id'];
            $this->js("
                setTimeout(() => {
                    console.log('Iniciando limpieza agresiva del DOM...');
                    
                    // Encontrar la primera fila por su wire:key
                    const primeraFila = document.querySelector('[wire\\:key=\"acomp-{$idPrimeraFila}\"]');
                    console.log('Primera fila encontrada:', primeraFila);
                    
                    if (primeraFila) {
                        // Limpiar todos los selects
                        const selects = primeraFila.querySelectorAll('select');
                        selects.forEach(select => {
                            select.selectedIndex = 0;
                            select.value = '';
                            select.dispatchEvent(new Event('change', { bubbles: true }));
                            console.log('Select limpiado:', select);
                        });
                        
                        // Limpiar todos los inputs
                        const inputs = primeraFila.querySelectorAll('input');
                        inputs.forEach(input => {
                            if (input.type === 'number') {
                                input.value = input.name.includes('cantidad') ? '1' : '0';
                            } else {
                                input.value = '';
                            }
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                            input.dispatchEvent(new Event('change', { bubbles: true }));
                            console.log('Input limpiado:', input);
                        });
                        
                        console.log('Limpieza DOM completada');
                    }
                    
                    // Forzar re-render completo
                    Livewire.find('" . $this->getId() . "').\$refresh();
                }, 250);
            ");
        } else {
            // Si la primera fila está vacía, solo agregar una nueva fila vacía
            $this->acompañamientos[] = [
                'id' => uniqid(),
                'tipo' => '',
                'cuenta_de' => '',
                'cantidad_vehiculos' => 1,
                'valor' => 0
            ];
            \Log::info('Nueva fila vacía agregada');
        }
        
        \Log::info('Estado final:', $this->acompañamientos);
        \Log::info('=== AGREGAR ACOMPAÑAMIENTO TERMINADO ===');
    }

    private function resetearPrimeraFilaInterna()
    {
        if (!empty($this->acompañamientos) && isset($this->acompañamientos[0])) {
            $idOriginal = $this->acompañamientos[0]['id'];
            
            // Resetear manteniendo el ID
            $this->acompañamientos[0] = [
                'id' => $idOriginal,
                'tipo' => '',
                'cuenta_de' => '',
                'cantidad_vehiculos' => 1,
                'valor' => 0
            ];
            
            \Log::info('Primera fila reseteada internamente:', $this->acompañamientos[0]);
        }
    }

    public function updatedAcompañamientos($value, $key)
    {
        // Asegurar que los valores numéricos tengan valores por defecto
        $parts = explode('.', $key);
        if (count($parts) >= 2) {
            $index = $parts[0];
            $field = $parts[1];
            
            if ($field === 'cantidad_vehiculos' && empty($this->acompañamientos[$index]['cantidad_vehiculos'])) {
                $this->acompañamientos[$index]['cantidad_vehiculos'] = 1;
            }
            
            if ($field === 'valor' && empty($this->acompañamientos[$index]['valor'])) {
                $this->acompañamientos[$index]['valor'] = 0;
            }
        }
    }

    public function debugAcompañamientos()
    {
        \Log::info('=== DEBUG ACOMPAÑAMIENTOS ===');
        \Log::info('Estado actual de acompañamientos:', $this->acompañamientos);
        \Log::info('Cantidad de filas:', ['total' => count($this->acompañamientos)]);
        
        foreach ($this->acompañamientos as $index => $acompañamiento) {
            \Log::info("Fila {$index}:", $acompañamiento);
            
            if ($index === 0) {
                $esVacia = empty($acompañamiento['tipo']) && empty($acompañamiento['cuenta_de']) && 
                          $acompañamiento['cantidad_vehiculos'] <= 1 && $acompañamiento['valor'] <= 0;
                \Log::info('Primera fila está vacía:', ['vacia' => $esVacia]);
            }
        }
        
        // Mostrar información en la sesión para que aparezca en la interfaz
        $debugInfo = [
            'total_filas' => count($this->acompañamientos),
            'primera_fila_vacia' => !empty($this->acompañamientos) ? (
                empty($this->acompañamientos[0]['tipo']) && 
                empty($this->acompañamientos[0]['cuenta_de']) && 
                $this->acompañamientos[0]['cantidad_vehiculos'] <= 1 && 
                $this->acompañamientos[0]['valor'] <= 0
            ) : false,
            'estado_completo' => $this->acompañamientos
        ];
        
        session()->flash('debug', 'DEBUG ACOMPAÑAMIENTOS:' . PHP_EOL . json_encode($debugInfo, JSON_PRETTY_PRINT));
        
        // Forzar actualización de la interfaz
        $this->dispatch('$refresh');
        $this->dispatch('debug-completed');
        
        \Log::info('=== FIN DEBUG ACOMPAÑAMIENTOS ===');
    }

    public function eliminarAcompañamiento($id)
    {
        if (count($this->acompañamientos) > 1) {
            // Filtrar el array para eliminar el elemento con el ID especificado
            $this->acompañamientos = array_values(array_filter($this->acompañamientos, function ($acompañamiento) use ($id) {
                return ($acompañamiento['id'] ?? null) !== $id;
            }));
            
            // Log para debugging
            \Log::info('Acompañamientos después de eliminar:', $this->acompañamientos);
        }
    }

    public function limpiarPrimeraFila()
    {
        if (!empty($this->acompañamientos) && isset($this->acompañamientos[0])) {
            $primeraFilaId = $this->acompañamientos[0]['id'] ?? uniqid();
            $this->acompañamientos[0] = [
                'id' => $primeraFilaId,
                'tipo' => '',
                'cuenta_de' => '',
                'cantidad_vehiculos' => 1,
                'valor' => 0
            ];
            
            \Log::info('Primera fila limpiada manualmente:', $this->acompañamientos[0]);
            
            // Solo forzar actualización simple
            $this->dispatch('$refresh');
            
            session()->flash('info', 'Primera fila limpiada exitosamente');
        }
    }

    public function forzarActualizacionUI()
    {
        \Log::info('Forzando actualización de UI - Estado actual:', $this->acompañamientos);
        
        $this->dispatch('$refresh');
        $this->dispatch('force-ui-update');
        
        // JavaScript para sincronizar forzadamente el DOM con el estado del servidor
        if (!empty($this->acompañamientos)) {
            foreach ($this->acompañamientos as $index => $acompañamiento) {
                $id = $acompañamiento['id'];
                $tipo = $acompañamiento['tipo'];
                $cuenta_de = $acompañamiento['cuenta_de'];
                $cantidad = $acompañamiento['cantidad_vehiculos'];
                $valor = $acompañamiento['valor'];
                
                $delay = ($index * 100) + 100;
                $this->js("
                    setTimeout(() => {
                        console.log('Sincronizando fila {$index} con ID {$id}');
                        const fila = document.querySelector('[wire\\:key=\"acomp-{$id}\"]');
                        if (fila) {
                            // Sincronizar select tipo
                            const selectTipo = fila.querySelector('select[wire\\:model\\.live^=\"acompañamientos.{$index}.tipo\"]');
                            if (selectTipo) {
                                selectTipo.value = '{$tipo}';
                                selectTipo.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                            
                            // Sincronizar select cuenta_de
                            const selectCuenta = fila.querySelector('select[wire\\:model\\.live^=\"acompañamientos.{$index}.cuenta_de\"]');
                            if (selectCuenta) {
                                selectCuenta.value = '{$cuenta_de}';
                                selectCuenta.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                            
                            // Sincronizar input cantidad
                            const inputCantidad = fila.querySelector('input[wire\\:model\\.live^=\"acompañamientos.{$index}.cantidad_vehiculos\"]');
                            if (inputCantidad) {
                                inputCantidad.value = '{$cantidad}';
                                inputCantidad.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                            
                            // Sincronizar input valor
                            const inputValor = fila.querySelector('input[wire\\:model\\.live^=\"acompañamientos.{$index}.valor\"]');
                            if (inputValor) {
                                inputValor.value = '{$valor}';
                                inputValor.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                            
                            console.log('Fila {$index} sincronizada');
                        }
                    }, {$delay});
                ");
            }
        }
        
        session()->flash('info', 'UI sincronizada forzadamente con el servidor');
    }

    public function resetearManualmenteFirstRow()
    {
        if (!empty($this->acompañamientos) && isset($this->acompañamientos[0])) {
            $primeraFilaId = $this->acompañamientos[0]['id'];
            
            // Resetear completamente la primera fila
            $this->acompañamientos[0] = [
                'id' => $primeraFilaId,
                'tipo' => '',
                'cuenta_de' => '',
                'cantidad_vehiculos' => 1,
                'valor' => 0
            ];
            
            \Log::info('Primera fila reseteada manualmente:', $this->acompañamientos[0]);
            
            // Reconstruir el array completo para forzar re-render
            $nuevoArray = [];
            foreach ($this->acompañamientos as $item) {
                $nuevoArray[] = $item;
            }
            $this->acompañamientos = $nuevoArray;
            
            // Forzar actualización múltiple
            $this->dispatch('$refresh');
            $this->skipRender();
            
            session()->flash('info', 'Primera fila reseteada exitosamente');
        }
    }

    public function reconstruirArrayCompleto()
    {
        \Log::info('Reconstruyendo array completo para forzar re-render');
        
        // Crear nuevo array completamente desde cero
        $nuevoArray = [];
        foreach ($this->acompañamientos as $acompañamiento) {
            $nuevoArray[] = [
                'id' => $acompañamiento['id'],
                'tipo' => $acompañamiento['tipo'] ?? '',
                'cuenta_de' => $acompañamiento['cuenta_de'] ?? '',
                'cantidad_vehiculos' => $acompañamiento['cantidad_vehiculos'] ?? 1,
                'valor' => $acompañamiento['valor'] ?? 0
            ];
        }
        
        $this->acompañamientos = $nuevoArray;
        \Log::info('Array reconstruido:', $this->acompañamientos);
        
        $this->dispatch('$refresh');
        session()->flash('info', 'Array reconstruido completamente');
    }

    public function generatePrompt()
    {
        $promptText = "Empresa: {$this->client_company_name}.\n";
        $promptText .= "Nombre de la empresa del cliente: {$this->client_name}.\n";
        $promptText .= "Ubicación: {$this->client_location}.\n";

        // // Solo agregar si existe representante de ventas
        // if (!empty($this->client_sales_representative)) {
        //     $promptText .= "Representante de ventas de la empresa del cliente: {$this->client_sales_representative}.\n";
        // }

        $promptText .= "Nombre del Asesor Comercial de Conalca: {$this->asesor_name_email}.\n";
        $promptText .= "Correo del Asesor Comercial de Conalca: {$this->asesor_email_email}.\n";
        $promptText .= "Numero de celular del Asesor Comercial de Conalca: {$this->asesor_phone_email}.\n";

        // $openai = OpenAI::client(config('services.openai.api_key'));
        $openai = OpenAI::client(config('services.openai.api_key'));

        $response = $openai->chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "Eres un asistente profesional y cordial de una empresa de logística llamada Conalca. 
                        Tu tarea es redactar un saludo profesional al cliente, mencionando que se está haciendo una cotización para transportar su mercancía 
                        según las rutas solicitadas, incluyendo el costo de los fletes.

                        El saludo debe ser claro, respetuoso, y sonar como si fuera redactado por un humano profesional en el área logística.",
                ],
                [
                    'role' => 'user',
                    'content' => "Con la siguiente información, genera el saludo profesional:\n\n$promptText",
                ],
            ],
        ]);

        $this->prompt_response = $response['choices'][0]['message']['content'];
    }

    public function toggleEditing()
    {
        $this->isEditing = !$this->isEditing;
    }

    public function openDraft($group_id)
    {
        $group = GroupCotization::find($group_id);
        if ($group) {
            $this->draft_group_id = $group->id;
            // Selecciona todas las cotizaciones del grupo borrador
            $cotizaciones = CotizacionModel::where('group_cotization_id', $group->id)
                ->where('silogtran_status', 'draft')
                ->get();
            $this->quote_data = [];
            foreach ($cotizaciones as $c) {
                $this->quote_data[] = [
                    'ciudad_origen' => $c->ciudad_origen,
                    'ciudad_destino' => $c->ciudad_destino,
                    'porcentaje' => $c->porcentaje,
                    // ... y todos los campos que necesites cargar...
                ];
            }
        }
        // Abre el modal que corresponda, ejemplo:
        $this->showModal3 = true;
    }

    public function saveDraft()
    {
        $this->hydrateClientData();

        \Log::info('💾 Iniciando saveDraft', [
            'group_cotization_id_actual' => $this->group_cotization_id,
            'client_id' => $this->client_id
        ]);

        // Intentar obtener group_id de sesión primero
        if (!$this->group_cotization_id && session()->has('current_group_id')) {
            $this->group_cotization_id = session('current_group_id');
            \Log::info('✅ Group ID recuperado de sesión en saveDraft', ['group_id' => $this->group_cotization_id]);
        }
        
        // Si no hay en sesión, buscar borrador existente RECIENTE de este cliente
        if (!$this->group_cotization_id && $this->client_id) {
            $lastDraft = GroupCotization::where('client_id', $this->client_id)
                ->where('user_id', auth()->id())
                ->where('status', 'borrador')
                ->where('created_at', '>=', now()->subDay())
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($lastDraft) {
                $this->group_cotization_id = $lastDraft->id;
                \Log::info('✅ Borrador existente encontrado en DB', [
                    'group_id' => $lastDraft->id,
                    'created_at' => $lastDraft->created_at
                ]);
            }
        }

        // PUNTO ÚNICO DE CREACIÓN/ACTUALIZACIÓN DEL BORRADOR
        if ($this->group_cotization_id) {
            // Caso 1: Ya existe un ID (edición de borrador existente)
            $group = GroupCotization::find($this->group_cotization_id);
            if ($group) {
                $group->update([
                    'status' => 'borrador',
                    'type' => $this->type_business,
                    'operation_type' => $this->operation_type,
                    'candado_satelital' => in_array($this->type_business, ['dta', 'otm']),
                    'cargo_type' => $this->cargo_type ?? null,
                    'jen_set' => ($this->cargo_type === 'refrigerado'),
                    'combustible' => ($this->cargo_type === 'refrigerado'),
                    'kit_derrames' => ($this->cargo_type === 'dangerous'),
                    'pictogramas' => ($this->cargo_type === 'dangerous'),
                ]);
                \Log::info('✅ Borrador ACTUALIZADO', ['group_id' => $group->id]);
            } else {
                // ID inválido, crear nuevo borrador
                \Log::warning('⚠️ Group ID inválido en saveDraft, creando nuevo', ['invalid_id' => $this->group_cotization_id]);
                $group = GroupCotization::create([
                    'user_id'   => auth()->id(),
                    'client_id' => $this->client_id,
                    'type'      => $this->type_business,
                    'operation_type' => $this->operation_type,
                    'status'    => 'borrador',
                    'candado_satelital' => in_array($this->type_business, ['dta', 'otm']),
                    'cargo_type' => $this->cargo_type ?? null,
                    'jen_set' => ($this->cargo_type === 'refrigerado'),
                    'combustible' => ($this->cargo_type === 'refrigerado'),
                    'kit_derrames' => ($this->cargo_type === 'dangerous'),
                    'pictogramas' => ($this->cargo_type === 'dangerous'),
                ]);
                $this->group_cotization_id = $group->id;
                \Log::info('✅ Borrador CREADO (ID inválido)', ['new_group_id' => $group->id]);
            }
        } else {
            // Caso 2: No hay ID (creación de borrador desde cero)
            \Log::info('📝 No hay group_id, creando nuevo borrador');
            $group = GroupCotization::create([
                'user_id'   => auth()->id(),
                'client_id' => $this->client_id,
                'type'      => $this->type_business,
                'operation_type' => $this->operation_type,
                'status'    => 'borrador',
                'candado_satelital' => in_array($this->type_business, ['dta', 'otm']),
                'cargo_type' => $this->cargo_type ?? null,
                'jen_set' => ($this->cargo_type === 'refrigerado'),
                'combustible' => ($this->cargo_type === 'refrigerado'),
                'kit_derrames' => ($this->cargo_type === 'dangerous'),
                'pictogramas' => ($this->cargo_type === 'dangerous'),
            ]);
            $this->group_cotization_id = $group->id;
            \Log::info('✅ Borrador CREADO desde cero', ['new_group_id' => $group->id]);
        }

        // 2. Guarda o actualiza cada cotización como "draft" asociada al grupo
        foreach ($this->quote_data as $ruta) {

            $ciudad_origen = strtolower($ruta['ciudad_origen']);
            $ciudad_destino = strtolower($ruta['ciudad_destino']);

            // $pricing = Pricing::where('origin', $ciudad_origen)
            //     ->where('destination', $ciudad_destino)
            //     ->first();
            $selected_pricing_id = $ruta['select_value'] ?? null;

            $pricing = $selected_pricing_id ? Pricing::find($selected_pricing_id) : null;

            CotizacionModel::updateOrCreate(
                [
                    'group_cotization_id' => $group->id,
                    'client_id'           => $this->client_id,
                    'ciudad_origen'       => $ruta['ciudad_origen'] ?? null,
                    'ciudad_destino'      => $ruta['ciudad_destino'] ?? null,
                ],
                [
                    'pricing_id'              => $pricing?->id,
                    'ciudad_origen_dane'      => $ruta['codigo_dane_origen'] ?? '',
                    'ciudad_destino_dane'     => $ruta['codigo_dane_destino'] ?? '',
                    'peso_mercancia'          => $ruta['peso_mercancia'] ?? '',
                    'cantidad'                => $ruta['cantidad'] ?? '',
                    'tipo_embajale'           => $ruta['tipo_embajale'] ?? '',
                    'dimensiones_exactas'     => $ruta['dimensiones_exactas'] ?? '',
                    'registro_fotografico'    => $ruta['registro_fotografico'] ?? '',
                    'planos'                  => $ruta['planos'] ?? '',
                    'tipo_producto'           => $ruta['tipo_producto'] ?? '',
                    'temperatura_mercancia'   => $ruta['temperatura_mercancia'] ?? '',
                    'humedad'                 => $ruta['humedad'] ?? '',
                    'vehiculo_requerido'      => $ruta['vehiculo_requerido'] ?? '',
                    'regimen_nacionalizado'   => $ruta['regimen_nacionalizado'] ?? '',
                    'agente_aduanas'          => $ruta['agente_aduanas'] ?? '',
                    'descargue_cargue'        => $ruta['descargue_cargue'] ?? '',
                    'consolidado_expreso'     => $ruta['consolidado_expreso'] ?? '',
                    'fcl_lcl'                 => $ruta['fcl_lcl'] ?? '',
                    'sitio_devolucion_contenedor' => $ruta['sitio_devolucion_contenedor'] ?? '',
                    'numero_documento_bl'     => $ruta['numero_documento_bl'] ?? '',
                    'fecha_hora_descargue_cargue' => $ruta['fecha_hora_descargue_cargue'] ?? '',
                    'cantidad_vh'             => $ruta['cantidad_vh'] ?? '',
                    'un'                      => $ruta['un'] ?? '',
                    'ruta'                    => $ruta['ruta'] ?? '',
                    'frecuencia'              => $ruta['frecuencia'] ?? '',
                    'esquema_seguridad'       => $ruta['esquema_seguridad'] ?? '',
                    'tipo_carroceria'         => $ruta['tipo_carroceria'] ?? '',
                    'valor'                   => $ruta['valor'] ?? '',
                    'valor_declarado'         => $ruta['valor_declarado'] ?? '',
                    'tipo_mercancia'          => $ruta['tipo_mercancia'] ?? '',
                    'ventanas_horarios_recibidos' => $ruta['ventanas_horarios_recibidos'] ?? '',
                    'seguro'                  => $ruta['seguro'] ?? '',
                    'silogtran_status'        => 'draft', // GUARDA COMO BORRADOR
                    'porcentaje'              => $ruta['porcentaje'] ?? $this->porcentaje_global ?? 17,
                    'tipo'                    => $this->type_business ?? '',
                    'operation_type'          => $this->operation_type ?? '',
                    'itesoltra_vehiculoacompanamiento' => $ruta['itesoltra_vehiculoacompanamiento'] ?? null,
                    'tipaco_codigo'                    => $ruta['tipaco_codigo'] ?? null,
                    'itesoltra_acompanamientocuentade' => $ruta['itesoltra_acompanamientocuentade'] ?? null,
                    'itesoltra_acompanamientovalor'    => $ruta['itesoltra_acompanamientovalor'] ?? null,
                    // Agrega más campos si necesitas ;)
                ]
            );
        }

        // Notifica que se guardó el borrador (ejemplo con browser event/toastr)
        $this->save_draft_notification = '¡Borrador guardado con éxito!';

        // Opcional: cerrar modales o limpiar variables
        $this->showModal3 = false;
        $this->showModal4 = false;
        $this->quote_data = [];
        $this->dispatch('refreshPage');
    }

    public function hydrateClientData()
    {
        Log::debug('Iniciando hydrateClientData');

        $client = null;

        if ($this->client_id) {
            Log::debug('Buscando cliente por ID', ['client_id' => $this->client_id]);
            $client = \App\Models\Client::find($this->client_id);

        } elseif ($this->search) {
            Log::debug('Buscando cliente por documento', ['document' => $this->search]);
            $client = \App\Models\Client::where('documento', $this->search)->first();

        } elseif ($this->group_cotization_id) {
            Log::debug('Buscando cliente por group_cotization_id', ['group_id' => $this->group_cotization_id]);
            $group = \App\Models\GroupCotization::find($this->group_cotization_id);

            if ($group) {
                // Busca al cliente relacionado al grupo
                $client = \App\Models\Client::find($group->client_id);
                Log::debug('Cliente encontrado mediante group_cotization_id', [
                    'group_id' => $this->group_cotization_id,
                    'client_id' => $group->client_id,
                ]);
            } else {
                Log::warning('No se encontró grupo con el ID dado', ['group_id' => $this->group_cotization_id]);
            }
        }

        if ($client) {
            Log::debug('Cliente encontrado', ['cliente_id' => $client->id]);
            Log::debug('Email del cliente encontrado', ['email' => $client->email, 'email_raw' => json_encode($client->email)]);

            // Procesar múltiples emails si existen
            $processedEmail = $this->processMultipleEmails($client->email);

            $this->client_id = $client->id;
            $this->client_document_client = $client->documento;
            $this->client_name = $client->cliente;
            $this->client_company_name = $client->cliente;
            $this->client_location = $client->ciudad;
            $this->client_phone_numbers = $client->telefono;
            $this->client_personal_cell = $client->celular;
            $this->client_email = $processedEmail['primary']; 
            $this->client_email_registered = $processedEmail['primary']; // Email registrado en BD
            
            // Solo sobrescribir cliente_email si el usuario NO lo ha modificado manualmente
            if (!$this->cliente_email_manually_set) {
                $this->cliente_email = $processedEmail['primary']; // Inicializar email destino automáticamente
                Log::debug('Email de destino asignado automáticamente', [
                    'email' => $processedEmail['primary']
                ]);
            } else {
                Log::debug('Email de destino mantenido (modificado por usuario)', [
                    'email' => $this->cliente_email
                ]);
            }
            
            $this->client_emails_available = $processedEmail['all']; // Todos los emails disponibles
            $this->client_address = $client->direccion;
            $this->client_branch_office = $client->branch_office;
            $this->client_sales_representative = $client->vendedor_nombre;
            
            Log::debug('Variables de email asignadas', [
                'client_email' => $this->client_email,
                'client_email_registered' => $this->client_email_registered,
                'cliente_email' => $this->cliente_email,
                'client_emails_available' => $this->client_emails_available
            ]);
        } else {
            Log::warning('Cliente no encontrado. Reiniciando propiedades relacionadas al cliente');

            $this->client_id = '';
            $this->client_document_client = '';
            $this->client_name = '';
            $this->client_company_name = '';
            $this->client_location = '';
            $this->client_phone_numbers = '';
            $this->client_personal_cell = '';
            $this->client_email = '';
            $this->client_email_registered = '';
            $this->cliente_email = '';
            $this->cliente_email_manually_set = false; // Reset del flag
            $this->client_emails_available = [];
            $this->client_address = '';
            $this->client_branch_office = '';
            $this->client_sales_representative = '';
        }

        Log::debug('Finalizando hydrateClientData');
    }

    /**
     * Listener para detectar cambios manuales en el email del cliente
     */
    public function updatedClienteEmail($value)
    {
        // Marcar que el usuario ha modificado manualmente el email
        $this->cliente_email_manually_set = true;
        
        Log::debug('Email modificado manualmente por el usuario', [
            'new_email' => $value,
            'user_id' => auth()->id()
        ]);
    }

    /**
     * Selecciona un email específico del cliente y marca como modificado manualmente
     */
    public function selectClientEmail($email)
    {
        $this->cliente_email = $email;
        $this->cliente_email_manually_set = true;
        
        Log::debug('Email seleccionado por el usuario', [
            'selected_email' => $email,
            'user_id' => auth()->id()
        ]);
    }

    /**
     * Procesa múltiples direcciones de email separadas por espacios o comas
     * 
     * @param string $rawEmail
     * @return array
     */
    private function processMultipleEmails($rawEmail)
    {
        if (empty($rawEmail)) {
            return ['primary' => '', 'all' => []];
        }

        // Limpiar espacios extra y separar por múltiples delimitadores
        $rawEmail = trim($rawEmail);
        
        // Separar por espacios múltiples, comas, puntos y comas
        $emails = preg_split('/[\s,;]+/', $rawEmail);
        
        // Filtrar y validar emails
        $validEmails = [];
        foreach ($emails as $email) {
            $email = trim($email);
            
            // Validar formato básico de email
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $validEmails[] = $email;
            }
        }

        Log::debug('Emails procesados', [
            'raw_email' => $rawEmail,
            'valid_emails' => $validEmails,
            'count' => count($validEmails)
        ]);

        // Si no hay emails válidos, devolver el original para mostrar error
        if (empty($validEmails)) {
            return ['primary' => $rawEmail, 'all' => []];
        }

        // Retornar el primer email válido como primario y todos los válidos como opciones
        return [
            'primary' => $validEmails[0],
            'all' => $validEmails
        ];
    }

    /**
     * Valida una dirección de email antes de enviar
     * 
     * @param string $email
     * @return bool
     */
    private function validateEmailForSending($email)
    {
        if (empty($email)) {
            return false;
        }

        // Validar formato RFC 2822
        return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Preparar datos de cotización para el email, asegurando que valor_final incluya acompañamiento
     * 
     * @return array
     */
    private function prepareQuoteDataForEmail()
    {
        return collect($this->quote_data)->map(function($route) {
            $valorBase = floatval($route['valor'] ?? 0);
            
            // Calcular acompañamiento si existe
            $cant = (int)($route['itesoltra_vehiculoacompanamiento'] ?? 0);
            $valorUnitario = (float)($route['itesoltra_acompanamientovalor'] ?? 0);
            $totalAcompanamiento = $cant > 0 ? $cant * $valorUnitario : 0;
            
            // Asegurar que valor_final incluya el acompañamiento
            if (!isset($route['valor_final']) || empty($route['valor_final'])) {
                $route['valor_final'] = $valorBase + $totalAcompanamiento;
            }
            
            return $route;
        })->toArray();
    }

    /**
     * Calcular el total incluyendo acompañamiento
     * 
     * @return float
     */
    private function calculateTotalWithAccompaniment()
    {
        return collect($this->quote_data)->sum(function($route) {
            $valorBase = floatval($route['valor'] ?? 0);
            
            // Calcular acompañamiento si existe
            $cant = (int)($route['itesoltra_vehiculoacompanamiento'] ?? 0);
            $valorUnitario = (float)($route['itesoltra_acompanamientovalor'] ?? 0);
            $totalAcompanamiento = $cant > 0 ? $cant * $valorUnitario : 0;
            
            // Usar valor_final si existe, o calcular la suma
            return isset($route['valor_final']) && !empty($route['valor_final']) 
                ? floatval($route['valor_final'])
                : $valorBase + $totalAcompanamiento;
        });
    }
}
