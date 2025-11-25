<?php

namespace App\Livewire;

use App\Models\CotizacionModel;
use App\Models\SolicitudTransporte;
use App\Models\DataColumn;
use App\Models\Client;
use App\Models\City;
use App\Models\Seller;
use App\Models\Product;
use App\Models\Packing;
use App\Models\VehicleClass;
use App\Models\Bodywork;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class DrogZone extends Component
{
    public $showModalCreateSolicitud = false;
    public $cotizacionSeleccionada = null;
    public $CotizacionModelId = null;
    public $tiposFlete = [];
    public $productos = [];
    public $empaques = [];
    public $ciudades = [];
    public $solicitud_data = [];
    public $step = 1;
    public $totalSteps = 6;
    
    // Cliente searcher properties
    public $cliente_search = '';
    public $cliente_codigo = '';
    public $cliente_selected = null;
    public $cliente_results = [];
    public $show_cliente_dropdown = false;
    
    // Ciudad searcher properties
    public $ciudad_search = '';
    public $ciudad_codigodane = '';
    public $ciudad_selected = null;
    public $ciudad_results = [];
    public $show_ciudad_dropdown = false;
    
    // Vendedor searcher properties
    public $vendedor_search = '';
    public $vendedor_documento = '';
    public $vendedor_selected = null;
    public $vendedor_results = [];
    public $show_vendedor_dropdown = false;
    
    // Origen searcher properties
    public $origen_search = '';
    public $origen_codigodane = '';
    public $origen_selected = null;
    public $origen_results = [];
    public $show_origen_dropdown = false;
    
    // Destino searcher properties
    public $destino_search = '';
    public $destino_codigodane = '';
    public $destino_selected = null;
    public $destino_results = [];
    public $show_destino_dropdown = false;

    // Producto searcher properties
    public $producto_search = '';
    public $producto_codigo = '';
    public $producto_selected = null;
    public $producto_results = [];
    public $show_producto_dropdown = false;

    // Empaque searcher properties
    public $empaque_search = '';
    public $empaque_codigo = '';
    public $empaque_selected = null;
    public $empaque_results = [];
    public $show_empaque_dropdown = false;

    // Clase Vehículo searcher properties
    public $vehicleClassSearch = '';
    public $vehicleClassCode = '';
    public $vehicleClassSelected = null;
    public $vehicleClassResults = [];
    public $showVehicleClassDropdown = false;

    // Carrocería searcher properties
    public $bodyworkSearch = '';
    public $bodyworkCode = '';
    public $bodyworkSelected = null;
    public $bodyworkResults = [];
    public $showBodyworkDropdown = false;

    // Control de búsquedas activas para evitar múltiples peticiones
    public $searchingCliente = false;
    public $searchingCiudad = false;
    public $searchingVendedor = false;
    public $searchingOrigen = false;
    public $searchingDestino = false;
    public $searchingProducto = false;
    public $searchingEmpaque = false;
    public $searchingVehicleClass = false;
    public $searchingBodywork = false;

    public $solicitudes; //HAVE TO DELETE
    public $quotes; //HAVE TO DELETE

    protected $listeners = [
        'abrirModalSolicitudTransporte' => 'abrirModal',
    ];

    public function mount(){
        //HAVE TO DELETE
        $this->solicitudes = SolicitudTransporte::with('client','acompanamiento', 'cargue', 'condiciones_factura', 'contenedor', 'detalle', 'entrega', 'equipos', 'internacional')->get();
        $this->quotes = CotizacionModel::with('pricing')->orderBy('created_at', 'desc')->get();
    }

    public function render()
    {
        return view('livewire.drog-zone');
    }

    // public function abrirModal($id)
    // {
    //     $this->CotizacionModelId = $id;
    //     $this->cotizacionSeleccionada = CotizacionModel::with('client')->find($id);
    //     $this->showModalCreateSolicitud = true;

    //     $this->tiposFlete = DataColumn::where('column', 'tipo_flete')->orderBy('value')->get();
    //     $this->productos = DataColumn::where('column', 'producto')->orderBy('value')->get();
    //     $this->empaques = DataColumn::where('column', 'empaque')->orderBy('value')->get();
    //     $this->ciudades = DataColumn::where('column', 'ciudad')->orderBy('value')->get();

    //     $solicitud = SolicitudTransporte::with([
    //         'detalle',
    //         'cargue',
    //         'condiciones_factura',
    //         'contenedor',
    //         'entrega',
    //         'equipos',
    //         'internacional',
    //         'acompanamiento'
    //     ])->where('cotizacion_model_id', $id)->first();

    //     if ($solicitud) {
    //         $this->solicitud_data = [
    //             'solicitud'   => $solicitud->toArray(),
    //             'detalle'     => $solicitud->detalle ? $solicitud->detalle->toArray() : [],
    //             'cargue'      => $solicitud->cargue ? $solicitud->cargue->toArray() : [],
    //             'condiciones' => $solicitud->condiciones_factura ? $solicitud->condiciones_factura->toArray() : [],
    //             'contenedor'  => $solicitud->contenedor ? $solicitud->contenedor->toArray() : [],
    //             'entrega'     => $solicitud->entrega ? $solicitud->entrega->toArray() : [],
    //             'equipos'     => $solicitud->equipos ? $solicitud->equipos->toArray() : [],
    //             'internacional' => $solicitud->internacional ? $solicitud->internacional->toArray() : [],
    //             'acompanamiento' => $solicitud->acompanamiento ? $solicitud->acompanamiento->toArray() : [],
    //         ];
    //         Log::info('Datos de solicitud cargados:', $this->solicitud_data);
    //         $this->dispatch('solicitud-data-ready', [
    //             'solicitud_data' => $this->solicitud_data,
    //         ]);

    //     } else {
    //         $this->solicitud_data = [];
    //     }
    // }

    public function abrirModal($id)
    {
        $this->CotizacionModelId = $id;
        $this->cotizacionSeleccionada = CotizacionModel::with('client')->find($id);
        $this->showModalCreateSolicitud = true;
        $this->step = 1; // Reset to step 1 when opening modal

        $this->tiposFlete = DataColumn::where('column', 'tipo_flete')->orderBy('value')->get();
        $this->productos = DataColumn::where('column', 'producto')->orderBy('value')->get();
        $this->empaques = DataColumn::where('column', 'empaque')->orderBy('value')->get();
        $this->ciudades = DataColumn::where('column', 'ciudad')->orderBy('value')->get();

        $solicitud = SolicitudTransporte::with([
            'detalle',
            'cargue',
            'condiciones_factura',
            'contenedor',
            'entrega',
            'equipos',
            'internacional',
            'acompanamiento'
        ])->where('cotizacion_model_id', $id)->first();

        if ($solicitud) {
            $this->solicitud_data = [
                'solicitud'   => $solicitud->toArray(),
                'detalle'     => $solicitud->detalle ? $solicitud->detalle->toArray() : new \stdClass(),
                'cargue'      => $solicitud->cargue ? $solicitud->cargue->toArray() : new \stdClass(),
                'condiciones' => $solicitud->condiciones_factura ? $solicitud->condiciones_factura->toArray() : new \stdClass(),
                'contenedor'  => $solicitud->contenedor ? $solicitud->contenedor->toArray() : new \stdClass(),
                'entrega'     => $solicitud->entrega ? $solicitud->entrega->toArray() : new \stdClass(),
                'equipos'     => $solicitud->equipos ? $solicitud->equipos->toArray() : new \stdClass(),
                'internacional'=> $solicitud->internacional ? $solicitud->internacional->toArray() : new \stdClass(),
                'acompanamiento'=> $solicitud->acompanamiento ? $solicitud->acompanamiento->toArray() : new \stdClass(),
            ];
            Log::info('Datos de solicitud cargados:', $this->solicitud_data);
            $this->dispatch('solicitud-data-ready', [
                'solicitud_data' => $this->solicitud_data,
            ]);

        } else {
            $this->solicitud_data = [];
        }
        
        // Emit the initial step
        $this->dispatch('step-changed', ['step' => $this->step]);
    }

    public function nextStep()
    {
        // Validation for step 1 - ensure client is selected
        if ($this->step == 1 && !$this->cliente_selected) {
            session()->flash('error', 'Debe seleccionar un cliente para continuar.');
            return;
        }
        
        // Additional validations can be added here for other steps
        // For example:
        // if ($this->step == 2) {
        //     // Validate step 2 fields
        // }
        
        if ($this->step <= $this->totalSteps) {
            $this->step++;
            $this->dispatch('step-changed', ['step' => $this->step]);
        }
    }

    public function prevStep()
    {
        if ($this->step > 1) {
            $this->step--;
            $this->dispatch('step-changed', ['step' => $this->step]);
        }
    }

    // Cliente search methods
    public function updatedClienteSearch()
    {
        if ($this->searchingCliente) {
            return;
        }
        
        $this->searchingCliente = true;
        
        try {
            if (strlen($this->cliente_search) >= 2) {
                $query = Client::query();
                
                // Role-based filtering
                if (auth()->user()->hasRole(['SUPER ADMIN', 'JEFE COMERCIAL'])) {
                    // Super admin and commercial manager see all clients
                } else {
                    // Regular users see only assigned clients
                    $query->whereHas('assignedUsers', function($q) {
                        $q->where('user_id', auth()->id());
                    });
                }
                
                $this->cliente_results = $query->where(function($q) {
                    $q->where('cliente', 'LIKE', '%' . $this->cliente_search . '%')
                      ->orWhere('codigo', 'LIKE', '%' . $this->cliente_search . '%')
                      ->orWhere('documento', 'LIKE', '%' . $this->cliente_search . '%');
                })
                ->limit(10)
                ->get(['id', 'codigo', 'cliente', 'documento']);
                
                $this->show_cliente_dropdown = true;
            } else {
                $this->cliente_results = [];
                $this->show_cliente_dropdown = false;
            }
        } catch (\Exception $e) {
            Log::error('Error en búsqueda de clientes: ' . $e->getMessage());
            $this->cliente_results = [];
            $this->show_cliente_dropdown = false;
        } finally {
            $this->searchingCliente = false;
        }
    }

    public function selectCliente($clienteId)
    {
        $cliente = Client::find($clienteId);
        if ($cliente) {
            $this->cliente_selected = $cliente;
            $this->cliente_codigo = $cliente->codigo;
            $this->cliente_search = $cliente->cliente;
            $this->show_cliente_dropdown = false;
        }
    }

    public function clearClienteSearch()
    {
        $this->cliente_search = '';
        $this->cliente_codigo = '';
        $this->cliente_selected = null;
        $this->cliente_results = [];
        $this->show_cliente_dropdown = false;
    }

    // Ciudad search methods
    public function updatedCiudadSearch()
    {
        if ($this->searchingCiudad) {
            return;
        }
        
        $this->searchingCiudad = true;
        
        try {
            if (strlen($this->ciudad_search) >= 2) {
                $this->ciudad_results = City::where('ciudad_nombre', 'LIKE', '%' . $this->ciudad_search . '%')
                    ->limit(10)
                    ->get(['ciudad_codigo', 'ciudad_nombre', 'ciudad_codigodane', 'departamento_nombre']);
                
                $this->show_ciudad_dropdown = true;
            } else {
                $this->ciudad_results = [];
                $this->show_ciudad_dropdown = false;
            }
        } catch (\Exception $e) {
            Log::error('Error en búsqueda de ciudades: ' . $e->getMessage());
            $this->ciudad_results = [];
            $this->show_ciudad_dropdown = false;
        } finally {
            $this->searchingCiudad = false;
        }
    }

    public function selectCiudad($ciudadCodigo)
    {
        $ciudad = City::where('ciudad_codigo', $ciudadCodigo)->first();
        if ($ciudad) {
            $this->ciudad_selected = $ciudad;
            $this->ciudad_codigodane = $ciudad->ciudad_codigodane;
            $this->ciudad_search = $ciudad->ciudad_nombre;
            $this->show_ciudad_dropdown = false;
        }
    }

    public function clearCiudadSearch()
    {
        $this->ciudad_search = '';
        $this->ciudad_codigodane = '';
        $this->ciudad_selected = null;
        $this->ciudad_results = [];
        $this->show_ciudad_dropdown = false;
    }

    // Vendedor search methods
    public function updatedVendedorSearch()
    {
        if ($this->searchingVendedor) {
            return;
        }
        
        $this->searchingVendedor = true;
        
        try {
            if (strlen($this->vendedor_search) >= 2) {
                $this->vendedor_results = Seller::where('Nombre', 'LIKE', '%' . $this->vendedor_search . '%')
                    ->limit(10)
                    ->get(['Codigo', 'Nombre', 'Documento', 'Ciudad']);
                
                $this->show_vendedor_dropdown = true;
            } else {
                $this->vendedor_results = [];
                $this->show_vendedor_dropdown = false;
            }
        } catch (\Exception $e) {
            Log::error('Error en búsqueda de vendedores: ' . $e->getMessage());
            $this->vendedor_results = [];
            $this->show_vendedor_dropdown = false;
        } finally {
            $this->searchingVendedor = false;
        }
    }

    public function selectVendedor($vendedorDocumento)
    { 
        $vendedor = Seller::where('Documento', $vendedorDocumento)->first();
        if ($vendedor) {
            $this->vendedor_selected = $vendedor;
            $this->vendedor_documento = $vendedor->Documento;
            $this->vendedor_search = $vendedor->Nombre;
            $this->show_vendedor_dropdown = false;
        }
    }

    public function clearVendedorSearch()
    {
        $this->vendedor_search = '';
        $this->vendedor_documento = '';
        $this->vendedor_selected = null;
        $this->vendedor_results = [];
        $this->show_vendedor_dropdown = false;
    }

    // Origen search methods
    public function updatedOrigenSearch()
    {
        if ($this->searchingOrigen) {
            return;
        }
        
        $this->searchingOrigen = true;
        
        try {
            if (strlen($this->origen_search) >= 2) {
                $this->origen_results = City::where('ciudad_nombre', 'LIKE', '%' . $this->origen_search . '%')
                    ->limit(10)
                    ->get(['ciudad_codigo', 'ciudad_nombre', 'ciudad_codigodane', 'departamento_nombre']);
                
                $this->show_origen_dropdown = true;
                
                // Debug log
                Log::info('Búsqueda origen:', [
                    'search_term' => $this->origen_search,
                    'results_count' => count($this->origen_results)
                ]);
            } else {
                $this->origen_results = [];
                $this->show_origen_dropdown = false;
            }
        } catch (\Exception $e) {
            Log::error('Error en búsqueda de origen: ' . $e->getMessage());
            $this->origen_results = [];
            $this->show_origen_dropdown = false;
        } finally {
            $this->searchingOrigen = false;
        }
    }

    public function selectOrigen($ciudadCodigo)
    {
        // Intentar encontrar la ciudad usando diferentes métodos
        $ciudad = City::where('ciudad_codigo', $ciudadCodigo)->first();
        
        // Si no la encuentra, intentar por la clave primaria
        if (!$ciudad) {
            $ciudad = City::find($ciudadCodigo);
        }
        
        // Si aún no la encuentra, intentar por codigo dane
        if (!$ciudad) {
            $ciudad = City::where('ciudad_codigodane', $ciudadCodigo)->first();
        }
        
        if ($ciudad) {
            $this->origen_selected = $ciudad;
            $this->origen_codigodane = $ciudad->ciudad_codigodane;
            $this->origen_search = $ciudad->ciudad_nombre;
            $this->show_origen_dropdown = false;
            
            // Log para debug
            Log::info('Ciudad origen seleccionada:', [
                'ciudad_codigo' => $ciudad->ciudad_codigo,
                'ciudad_nombre' => $ciudad->ciudad_nombre,
                'ciudad_codigodane' => $ciudad->ciudad_codigodane,
                'origen_codigodane_property' => $this->origen_codigodane
            ]);
            
            // Forzar actualización del componente
            $this->dispatch('ciudad-origen-selected', [
                'codigo_dane' => $this->origen_codigodane,
                'nombre' => $ciudad->ciudad_nombre
            ]);
        } else {
            Log::error('No se encontró la ciudad con código: ' . $ciudadCodigo);
        }
    }

    public function clearOrigenSearch()
    {
        $this->origen_search = '';
        $this->origen_codigodane = '';
        $this->origen_selected = null;
        $this->origen_results = [];
        $this->show_origen_dropdown = false;
    }

    // Destino search methods
    public function updatedDestinoSearch()
    {
        if ($this->searchingDestino) {
            return;
        }
        
        $this->searchingDestino = true;
        
        try {
            if (strlen($this->destino_search) >= 2) {
                $this->destino_results = City::where('ciudad_nombre', 'LIKE', '%' . $this->destino_search . '%')
                    ->limit(10)
                    ->get(['ciudad_codigo', 'ciudad_nombre', 'ciudad_codigodane', 'departamento_nombre']);
                
                $this->show_destino_dropdown = true;
                
                // Debug log
                Log::info('Búsqueda destino:', [
                    'search_term' => $this->destino_search,
                    'results_count' => count($this->destino_results)
                ]);
            } else {
                $this->destino_results = [];
                $this->show_destino_dropdown = false;
            }
        } catch (\Exception $e) {
            Log::error('Error en búsqueda de destino: ' . $e->getMessage());
            $this->destino_results = [];
            $this->show_destino_dropdown = false;
        } finally {
            $this->searchingDestino = false;
        }
    }

    public function selectDestino($ciudadCodigo)
    {
        // Intentar encontrar la ciudad usando diferentes métodos
        $ciudad = City::where('ciudad_codigo', $ciudadCodigo)->first();
        
        // Si no la encuentra, intentar por la clave primaria
        if (!$ciudad) {
            $ciudad = City::find($ciudadCodigo);
        }
        
        // Si aún no la encuentra, intentar por codigo dane
        if (!$ciudad) {
            $ciudad = City::where('ciudad_codigodane', $ciudadCodigo)->first();
        }
        
        if ($ciudad) {
            $this->destino_selected = $ciudad;
            $this->destino_codigodane = $ciudad->ciudad_codigodane;
            $this->destino_search = $ciudad->ciudad_nombre;
            $this->show_destino_dropdown = false;
            
            // Log para debug
            Log::info('Ciudad destino seleccionada:', [
                'ciudad_codigo' => $ciudad->ciudad_codigo,
                'ciudad_nombre' => $ciudad->ciudad_nombre,
                'ciudad_codigodane' => $ciudad->ciudad_codigodane,
                'destino_codigodane_property' => $this->destino_codigodane
            ]);
            
            // Forzar actualización del componente
            $this->dispatch('ciudad-destino-selected', [
                'codigo_dane' => $this->destino_codigodane,
                'nombre' => $ciudad->ciudad_nombre
            ]);
        } else {
            Log::error('No se encontró la ciudad destino con código: ' . $ciudadCodigo);
        }
    }

    public function clearDestinoSearch()
    {
        $this->destino_search = '';
        $this->destino_codigodane = '';
        $this->destino_selected = null;
        $this->destino_results = [];
        $this->show_destino_dropdown = false;
    }

        // Producto search methods
    public function updatedProductoSearch()
    {
        if ($this->searchingProducto) {
            return;
        }
        
        $this->searchingProducto = true;
        
        try {
            if (strlen($this->producto_search) >= 2) {
                $this->producto_results = Product::where('producto_nombre', 'LIKE', '%' . $this->producto_search . '%')
                    ->limit(10)
                    ->get(['producto_codigo', 'producto_nombre']);
                
                $this->show_producto_dropdown = true;
            } else {
                $this->producto_results = [];
                $this->show_producto_dropdown = false;
            }
        } catch (\Exception $e) {
            Log::error('Error en búsqueda de productos: ' . $e->getMessage());
            $this->producto_results = [];
            $this->show_producto_dropdown = false;
        } finally {
            $this->searchingProducto = false;
        }
    }

    public function selectProducto($productoCodigo)
    {
        $producto = Product::where('producto_codigo', $productoCodigo)->first();
        if ($producto) {
            $this->producto_selected = $producto;
            $this->producto_codigo = $producto->producto_codigo;
            $this->producto_search = $producto->producto_nombre;
            $this->show_producto_dropdown = false;
        }
    }

    public function clearProductoSearch()
    {
        $this->producto_search = '';
        $this->producto_codigo = '';
        $this->producto_selected = null;
        $this->producto_results = [];
        $this->show_producto_dropdown = false;
    }

    // Empaque search methods
    public function updatedEmpaqueSearch()
    {
        if ($this->searchingEmpaque) {
            return; // Evitar múltiples búsquedas simultáneas
        }

        if (strlen($this->empaque_search) >= 2) {
            $this->searchingEmpaque = true;
            
            try {
                $this->empaque_results = Packing::where('Nombre', 'LIKE', '%' . $this->empaque_search . '%')
                    ->limit(10)
                    ->get(['Codigo', 'Nombre']);
                
                $this->show_empaque_dropdown = true;
                
                // Debug log
                Log::info('Búsqueda empaque:', [
                    'search_term' => $this->empaque_search,
                    'results_count' => count($this->empaque_results),
                    'results' => $this->empaque_results->map(function($item) {
                        return ['codigo' => $item->Codigo, 'nombre' => $item->Nombre];
                    })->toArray()
                ]);
            } catch (\Exception $e) {
                Log::error('Error en búsqueda empaque:', [
                    'search_term' => $this->empaque_search,
                    'error' => $e->getMessage()
                ]);
                $this->empaque_results = [];
                $this->show_empaque_dropdown = false;
            }
            
            $this->searchingEmpaque = false;
        } else {
            $this->empaque_results = [];
            $this->show_empaque_dropdown = false;
        }
    }

    public function selectEmpaque($empaqueCodigo)
    {
        try {
            // Buscar el empaque por código (que ahora es la clave primaria)
            $empaque = Packing::find($empaqueCodigo);
            
            // Si no lo encuentra, intentar por el campo Codigo explícitamente
            if (!$empaque) {
                $empaque = Packing::where('Codigo', $empaqueCodigo)->first();
            }
            
            if ($empaque) {
                $this->empaque_selected = $empaque;
                $this->empaque_codigo = $empaque->Codigo;
                $this->empaque_search = $empaque->Nombre;
                $this->show_empaque_dropdown = false;
                
                // Log para debug
                Log::info('Empaque seleccionado:', [
                    'codigo' => $empaque->Codigo,
                    'nombre' => $empaque->Nombre,
                    'empaque_codigo_property' => $this->empaque_codigo
                ]);
                
                // Forzar actualización del componente
                $this->dispatch('empaque-selected', [
                    'codigo' => $this->empaque_codigo,
                    'nombre' => $empaque->Nombre
                ]);
            } else {
                Log::error('No se encontró el empaque con código: ' . $empaqueCodigo);
            }
        } catch (\Exception $e) {
            Log::error('Error seleccionando empaque:', [
                'codigo' => $empaqueCodigo,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function clearEmpaqueSearch()
    {
        $this->empaque_search = '';
        $this->empaque_codigo = '';
        $this->empaque_selected = null;
        $this->empaque_results = [];
        $this->show_empaque_dropdown = false;
    }

    // Clase Vehículo search methods
    public function updatedVehicleClassSearch()
    {
        if ($this->searchingVehicleClass) {
            return; // Evitar múltiples búsquedas simultáneas
        }

        if (strlen($this->vehicleClassSearch) >= 2) {
            $this->searchingVehicleClass = true;
            
            try {
                $this->vehicleClassResults = VehicleClass::where('Nombre', 'LIKE', '%' . $this->vehicleClassSearch . '%')
                    ->limit(10)
                    ->get(['Codigo', 'Nombre']);
                
                $this->showVehicleClassDropdown = true;
                
                // Debug log
                Log::info('Búsqueda clase vehículo:', [
                    'search_term' => $this->vehicleClassSearch,
                    'results_count' => count($this->vehicleClassResults),
                    'results' => $this->vehicleClassResults->toArray()
                ]);
            } catch (\Exception $e) {
                Log::error('Error en búsqueda clase vehículo:', [
                    'search_term' => $this->vehicleClassSearch,
                    'error' => $e->getMessage()
                ]);
                $this->vehicleClassResults = [];
                $this->showVehicleClassDropdown = false;
            }
            
            $this->searchingVehicleClass = false;
        } else {
            $this->vehicleClassResults = [];
            $this->showVehicleClassDropdown = false;
        }
    }

    public function selectClaseVehiculo($claseVehiculoCodigo)
    {
        // Buscar la clase de vehículo por código (que ahora es la clave primaria)
        $claseVehiculo = VehicleClass::find($claseVehiculoCodigo);
        
        // Si no lo encuentra, intentar por el campo Codigo explícitamente
        if (!$claseVehiculo) {
            $claseVehiculo = VehicleClass::where('Codigo', $claseVehiculoCodigo)->first();
        }
        
        if ($claseVehiculo) {
            $this->vehicleClassSelected = $claseVehiculo;
            $this->vehicleClassCode = $claseVehiculo->Codigo;
            $this->vehicleClassSearch = $claseVehiculo->Nombre;
            $this->showVehicleClassDropdown = false;
            
            // Log para debug
            Log::info('Clase vehículo seleccionada:', [
                'codigo' => $claseVehiculo->Codigo,
                'nombre' => $claseVehiculo->Nombre,
                'clase_vehiculo_codigo_property' => $this->vehicleClassCode
            ]);
            
            // Forzar actualización del componente
            $this->dispatch('clase-vehiculo-selected', [
                'codigo' => $this->vehicleClassCode,
                'nombre' => $claseVehiculo->Nombre
            ]);
        } else {
            Log::error('No se encontró la clase de vehículo con código: ' . $claseVehiculoCodigo);
        }
    }

    public function clearClaseVehiculoSearch()
    {
        $this->vehicleClassSearch = '';
        $this->vehicleClassCode = '';
        $this->vehicleClassSelected = null;
        $this->vehicleClassResults = [];
        $this->showVehicleClassDropdown = false;
    }

    // Carrocería search methods
    public function updatedBodyworkSearch()
    {
        if ($this->searchingBodywork) {
            return; // Evitar múltiples búsquedas simultáneas
        }

        if (strlen($this->bodyworkSearch) >= 2) {
            $this->searchingBodywork = true;
            
            try {
                $this->bodyworkResults = Bodywork::where('Nombre', 'LIKE', '%' . $this->bodyworkSearch . '%')
                    ->limit(10)
                    ->get(['Codigo', 'Nombre']);
                
                $this->showBodyworkDropdown = true;
                
                // Debug log
                Log::info('Búsqueda carrocería:', [
                    'search_term' => $this->bodyworkSearch,
                    'results_count' => count($this->bodyworkResults),
                    'results' => $this->bodyworkResults->toArray()
                ]);
            } catch (\Exception $e) {
                Log::error('Error en búsqueda carrocería:', [
                    'search_term' => $this->bodyworkSearch,
                    'error' => $e->getMessage()
                ]);
                $this->bodyworkResults = [];
                $this->showBodyworkDropdown = false;
            }
            
            $this->searchingBodywork = false;
        } else {
            $this->bodyworkResults = [];
            $this->showBodyworkDropdown = false;
        }
    }

    public function selectCarroceria($carroceriaCodigo)
    {
        // Buscar la carrocería por código (que ahora es la clave primaria)
        $carroceria = Bodywork::find($carroceriaCodigo);
        
        // Si no lo encuentra, intentar por el campo Codigo explícitamente
        if (!$carroceria) {
            $carroceria = Bodywork::where('Codigo', $carroceriaCodigo)->first();
        }
        
        if ($carroceria) {
            $this->bodyworkSelected = $carroceria;
            $this->bodyworkCode = $carroceria->Codigo;
            $this->bodyworkSearch = $carroceria->Nombre;
            $this->showBodyworkDropdown = false;
            
            // Log para debug
            Log::info('Carrocería seleccionada:', [
                'codigo' => $carroceria->Codigo,
                'nombre' => $carroceria->Nombre,
                'carroceria_codigo_property' => $this->bodyworkCode
            ]);
            
            // Forzar actualización del componente
            $this->dispatch('carroceria-selected', [
                'codigo' => $this->bodyworkCode,
                'nombre' => $carroceria->Nombre
            ]);
        } else {
            Log::error('No se encontró la carrocería con código: ' . $carroceriaCodigo);
        }
    }

    public function clearCarroceriaSearch()
    {
        $this->bodyworkSearch = '';
        $this->bodyworkCode = '';
        $this->bodyworkSelected = null;
        $this->bodyworkResults = [];
        $this->showBodyworkDropdown = false;
    }

    public function closeModal()
    {
        $this->showModalCreateSolicitud = false;
        $this->clearClienteSearch();
        $this->clearCiudadSearch();
        $this->clearVendedorSearch();
        $this->clearOrigenSearch();
        $this->clearDestinoSearch();
        $this->clearProductoSearch();
        $this->clearEmpaqueSearch();
        $this->clearClaseVehiculoSearch();
        $this->clearCarroceriaSearch();
        $this->step = 1;
        $this->CotizacionModelId = null;
        $this->cotizacionSeleccionada = null;
        
        // Emit step reset
        $this->dispatch('step-changed', ['step' => $this->step]);
    }

    public function submitSolicitud()
    {
        // This will trigger the final submission
        // For now, just go to final step to maintain compatibility with existing AJAX
        if ($this->step <= $this->totalSteps) {
            $this->step = $this->totalSteps;
            $this->dispatch('step-changed', ['step' => $this->step]);
            $this->dispatch('submit-solicitud');
        }
    }
}
