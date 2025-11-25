<div>
    <style>
        .message-bubble {
            max-width: 80%;
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 15px;
            word-wrap: break-word;
            white-space: pre-wrap;
            /* Asegura el salto de línea */
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Cliente dropdown styles */
        .cliente-dropdown {
            backdrop-filter: blur(10px);
            border: 1px solid rgba(209, 213, 219, 0.8);
        }
        
        .cliente-dropdown button:hover {
            background: linear-gradient(to right, rgba(251, 146, 60, 0.05), rgba(251, 146, 60, 0.1));
        }
        
        /* Ciudad dropdown styles */
        .ciudad-dropdown {
            backdrop-filter: blur(10px);
            border: 1px solid rgba(209, 213, 219, 0.8);
        }
        
        .ciudad-dropdown button:hover {
            background: linear-gradient(to right, rgba(251, 146, 60, 0.05), rgba(251, 146, 60, 0.1));
        }
        
        /* Vendedor dropdown styles */
        .vendedor-dropdown {
            backdrop-filter: blur(10px);
            border: 1px solid rgba(209, 213, 219, 0.8);
        }
        
        .vendedor-dropdown button:hover {
            background: linear-gradient(to right, rgba(251, 146, 60, 0.05), rgba(251, 146, 60, 0.1));
        }

        /* Producto dropdown styles */
        .producto-dropdown {
            backdrop-filter: blur(10px);
            border: 1px solid rgba(209, 213, 219, 0.8);
        }
        
        .producto-dropdown button:hover {
            background: linear-gradient(to right, rgba(34, 197, 94, 0.05), rgba(34, 197, 94, 0.1));
        }
        
        /* Origen dropdown styles */
        .origen-dropdown {
            backdrop-filter: blur(10px);
            border: 1px solid rgba(209, 213, 219, 0.8);
        }
        
        .origen-dropdown button:hover {
            background: linear-gradient(to right, rgba(59, 130, 246, 0.05), rgba(59, 130, 246, 0.1));
        }
        
        /* Destino dropdown styles */
        .destino-dropdown {
            backdrop-filter: blur(10px);
            border: 1px solid rgba(209, 213, 219, 0.8);
        }
        
        .destino-dropdown button:hover {
            background: linear-gradient(to right, rgba(59, 130, 246, 0.05), rgba(59, 130, 246, 0.1));
        }
 

        .userMessage {
            background-color: rgba(255, 124, 50, 0.2);
            /* Color para los mensajes del usuario */
            color: #000;
            align-self: flex-end;
            text-align: right;
        }

        .botMessage {
            background-color: rgba(200, 200, 200, 0.2);
            /* Color para los mensajes del bot */
            color: #000;
            align-self: flex-start;
            text-align: left;
        }

        #chatbox {
            display: flex;
            flex-direction: column;
        }
    </style>

   @if($showModalCreateSolicitud)
        <script> if(!window.solicitudData){window.solicitudData=@json($solicitud_data);} </script>

        <!-- ===== Overlay ===== -->
        <div class="fixed inset-0 z-40 bg-black/60"></div>

        <!-- ===== Modal ===== -->
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-8 overflow-hidden">
            <div class="relative w-full max-w-6xl mx-auto my-8 md:my-12 bg-gradient-to-br from-white to-gray-50 rounded-2xl shadow-2xl border border-gray-100 flex flex-col md:flex-row overflow-hidden max-h-[calc(100vh-4rem)] md:max-h-[calc(100vh-6rem)]">
                
                <!-- Botón de cerrar (X) -->
                <button wire:click="$set('showModalCreateSolicitud', false)" 
                        class="absolute top-4 right-4 z-20 w-10 h-10 bg-white bg-opacity-90 hover:bg-opacity-100 rounded-full flex items-center justify-center text-gray-600 hover:text-gray-800 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>

                <!-- ═══════════  Wizard / Form  ═══════════-->
                <form id="wizardForm" class="w-full md:w-1/2 overflow-hidden bg-white flex flex-col">
                    <!-- ═════════ Header con logo y título ═════════ -->
                    <div class="bg-gradient-to-r from-orange-500 to-orange-600 text-white p-6 flex-shrink-0">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold mb-1">Solicitud de Transporte</h2>
                                <p class="text-orange-100 text-sm">Complete todos los pasos para procesar su solicitud</p>
                            </div>
                            <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                    <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1V8a1 1 0 00-1-1h-3z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- ═════════ Barra de progreso mejorada ═════════ -->
                    <div class="px-8 py-6 bg-white border-b border-gray-100 flex-shrink-0">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-gray-600">Progreso</span>
                            <span id="progressText" class="text-sm font-semibold text-orange-600">Paso {{ $step }} / {{ $totalSteps }}</span>
                        </div>
                        <div id="progressBar" class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                            <div id="progressBarFill"
                                class="bg-gradient-to-r from-orange-500 to-orange-600 h-2 rounded-full transition-all duration-500 ease-out shadow-sm"
                                style="width:{{ (($step - 1) / ($totalSteps - 1)) * 100 }}%"></div>
                        </div>
                        <div class="flex justify-between mt-2">
                            <span class="text-xs text-gray-400">Inicio</span>
                            <span class="text-xs text-gray-400">Finalización</span>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto p-8">

                    <!-- STEP 1 – DATOS BÁSICOS -->
                    <section id="step-1" class="step space-y-8 animate-fadeIn {{ $step != 1 ? 'hidden' : '' }}">
                        <div class="flex items-center space-x-3 mb-8">
                            <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-orange-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                1
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">Datos Básicos</h3>
                                <p class="text-gray-600 text-sm">Información general del cliente y tipo de servicio</p>
                            </div>
                        </div>
                        
                        <input type="hidden" id="CotizacionModelId" value="{{ $CotizacionModelId }}">

                        <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                            <h4 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                                Información del Cliente
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Cliente Searcher -->
                                <div class="space-y-2 relative">
                                    <label class="block text-sm font-medium text-gray-700">Cliente</label>
                                    <div class="relative">
                                        <input 
                                            type="text" 
                                            wire:model.live.debounce.300ms="cliente_search"
                                            placeholder="Buscar cliente por nombre, código o documento..."
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-300 hover:border-orange-300"
                                        />
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            @if($cliente_selected)
                                                <button 
                                                    type="button"
                                                    wire:click="clearClienteSearch"
                                                    class="text-gray-400 hover:text-gray-600"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            @else
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <!-- Dropdown Results -->
                                    @if($show_cliente_dropdown)
                                        <div class="absolute z-50 w-full mt-1 bg-white cliente-dropdown rounded-lg shadow-xl max-h-60 overflow-y-auto animate-fadeIn">
                                            @if(count($cliente_results) > 0)
                                                @foreach($cliente_results as $cliente)
                                                    <button 
                                                        type="button"
                                                        wire:click="selectCliente({{ $cliente->id }})"
                                                        class="w-full px-4 py-3 text-left hover:bg-orange-50 focus:bg-orange-50 focus:outline-none border-b border-gray-100 last:border-b-0 transition-all duration-200"
                                                    >
                                                        <div class="flex justify-between items-center">
                                                            <div>
                                                                <div class="font-medium text-gray-900">{{ $cliente->cliente }}</div>
                                                                <div class="text-sm text-gray-600">Doc: {{ $cliente->documento }}</div>
                                                            </div>
                                                            <div class="text-sm font-semibold text-orange-600 bg-orange-100 px-2 py-1 rounded">
                                                                {{ $cliente->codigo }}
                                                            </div>
                                                        </div>
                                                    </button>
                                                @endforeach
                                            @else
                                                <div class="px-4 py-6 text-center text-gray-500">
                                                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.49.901-6.092 2.379C7.068 18.42 8.978 19 12 19s4.932-.58 6.092-1.621z"></path>
                                                    </svg>
                                                    <p class="text-sm">No se encontraron clientes</p>
                                                    <p class="text-xs text-gray-400 mt-1">Intenta con otros términos de búsqueda</p>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                    
                                    <!-- Selected Client Info -->
                                    @if($cliente_selected)
                                        <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span class="text-sm font-medium text-green-800">Cliente seleccionado</span>
                                            </div>
                                            <div class="mt-1 text-sm text-green-700">
                                                <strong>{{ $cliente_selected->cliente }}</strong> - Código: {{ $cliente_selected->codigo }}
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <!-- Hidden input for form submission -->
                                    <input type="hidden" id="cliente_codigo" wire:model="cliente_codigo" value="{{ $cliente_codigo }}">
                                </div>

                                <!-- Tipo de viaje -->
                                <div class="space-y-2">
                                    <x-select id="tipo_viaje" label="Tipo Viaje"
                                              :options="['NACIONAL','URBANO','DEVOLUCION','EXPORTACION','CONTENEDOR VACIO','ITR','ALMACENAMIENTO','LEGALIZACION','NACIONAL ESPECIAL','INTERNACIONAL','URBANO-ITR']"
                                              class="transition-all duration-300 hover:border-orange-300 focus:ring-2 focus:ring-orange-500"/>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                            <h4 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"/>
                                </svg>
                                Configuración Comercial
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Moneda -->
                                <div class="space-y-2">
                                    <x-select id="moneda" label="Moneda"
                                              :options="['PESOS','DOLARES','BOLIVARES','BOLIVARES FUERTES']"
                                              class="transition-all duration-300 hover:border-orange-300 focus:ring-2 focus:ring-orange-500"/>
                                </div>

                                <!-- Fuente -->
                                <div class="space-y-2">
                                    <x-select id="fuente_solicitud" label="Fuente Solicitud"
                                              :options="['TELEFONO DESPACHADOR','TELEFONO ATENCION CLIENTE','MAIL','FAX','SIA','PAGINA WEB']"
                                              class="transition-all duration-300 hover:border-orange-300 focus:ring-2 focus:ring-orange-500"/>
                                </div>

                                <div class="space-y-2">
                                    <x-input  id="condicion_despacho"    label="Condición Despacho" class="transition-all duration-300 hover:border-orange-300 focus:ring-2 focus:ring-orange-500"/>
                                </div>
                                
                                <div class="space-y-2">
                                    <x-input  id="condicion_facturacion" label="Condición Facturación" class="transition-all duration-300 hover:border-orange-300 focus:ring-2 focus:ring-orange-500"/>
                                </div>
                                
                                <!-- Ciudad Facturación Searcher -->
                                <div class="space-y-2 relative">
                                    <label class="block text-sm font-medium text-gray-700">Ciudad Facturación</label>
                                    <div class="relative">
                                        <input 
                                            type="text" 
                                            wire:model.live.debounce.300ms="ciudad_search"
                                            placeholder="Buscar ciudad por nombre..."
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-300 hover:border-orange-300"
                                        />
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            @if($ciudad_selected)
                                                <button 
                                                    type="button"
                                                    wire:click="clearCiudadSearch"
                                                    class="text-gray-400 hover:text-gray-600"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            @else
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <!-- Dropdown Results -->
                                    @if($show_ciudad_dropdown)
                                        <div class="absolute z-50 w-full mt-1 bg-white ciudad-dropdown rounded-lg shadow-xl max-h-60 overflow-y-auto animate-fadeIn">
                                            @if(count($ciudad_results) > 0)
                                                @foreach($ciudad_results as $ciudad)
                                                    <button 
                                                        type="button"
                                                        wire:click="selectCiudad('{{ $ciudad->ciudad_codigo }}')"
                                                        class="w-full px-4 py-3 text-left hover:bg-orange-50 focus:bg-orange-50 focus:outline-none border-b border-gray-100 last:border-b-0 transition-all duration-200"
                                                    >
                                                        <div class="flex justify-between items-center">
                                                            <div>
                                                                <div class="font-medium text-gray-900">{{ $ciudad->ciudad_nombre }}</div>
                                                                <div class="text-sm text-gray-600">{{ $ciudad->departamento_nombre }}</div>
                                                            </div>
                                                            <div class="text-sm font-semibold text-orange-600 bg-orange-100 px-2 py-1 rounded">
                                                                {{ $ciudad->ciudad_codigodane }}
                                                            </div>
                                                        </div>
                                                    </button>
                                                @endforeach
                                            @else
                                                <div class="px-4 py-6 text-center text-gray-500">
                                                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    </svg>
                                                    <p class="text-sm">No se encontraron ciudades</p>
                                                    <p class="text-xs text-gray-400 mt-1">Intenta con otros términos de búsqueda</p>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                    
                                    <!-- Selected City Info -->
                                    @if($ciudad_selected)
                                        <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span class="text-sm font-medium text-green-800">Ciudad seleccionada</span>
                                            </div>
                                            <div class="mt-1 text-sm text-green-700">
                                                <strong>{{ $ciudad_selected->ciudad_nombre }}</strong> - Código DANE: {{ $ciudad_selected->ciudad_codigodane }}
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <!-- Hidden input for form submission -->
                                    <input type="hidden" id="ciudad_facturacion" wire:model="ciudad_codigodane" value="{{ $ciudad_codigodane }}">
                                </div>
                                
                                <!-- Vendedor Searcher -->
                                <div class="space-y-2 relative">
                                    <label class="block text-sm font-medium text-gray-700">Vendedor</label>
                                    <div class="relative">
                                        <input 
                                            type="text" 
                                            wire:model.live.debounce.300ms="vendedor_search"
                                            placeholder="Buscar vendedor por nombre..."
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-300 hover:border-orange-300"
                                        />
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            @if($vendedor_selected)
                                                <button 
                                                    type="button"
                                                    wire:click="clearVendedorSearch"
                                                    class="text-gray-400 hover:text-gray-600"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            @else
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <!-- Dropdown Results -->
                                    @if($show_vendedor_dropdown)
                                        <div class="absolute z-50 w-full mt-1 bg-white vendedor-dropdown rounded-lg shadow-xl max-h-60 overflow-y-auto animate-fadeIn">
                                            @if(count($vendedor_results) > 0)
                                                @foreach($vendedor_results as $vendedor)
                                                    <button 
                                                        type="button"
                                                        wire:click="selectVendedor('{{ $vendedor->Documento }}')"
                                                        class="w-full px-4 py-3 text-left hover:bg-orange-50 focus:bg-orange-50 focus:outline-none border-b border-gray-100 last:border-b-0 transition-all duration-200"
                                                    >
                                                        <div class="flex justify-between items-center">
                                                            <div>
                                                                <div class="font-medium text-gray-900">{{ $vendedor->Nombre }}</div>
                                                                <div class="text-sm text-gray-600">{{ $vendedor->Ciudad }}</div>
                                                            </div>
                                                            <div class="text-sm font-semibold text-orange-600 bg-orange-100 px-2 py-1 rounded">
                                                                {{ $vendedor->Documento }}
                                                            </div>
                                                        </div>
                                                    </button>
                                                @endforeach
                                            @else
                                                <div class="px-4 py-6 text-center text-gray-500">
                                                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                    </svg>
                                                    <p class="text-sm">No se encontraron vendedores</p>
                                                    <p class="text-xs text-gray-400 mt-1">Intenta con otros términos de búsqueda</p>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                    
                                    <!-- Selected Seller Info -->
                                    @if($vendedor_selected)
                                        <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span class="text-sm font-medium text-green-800">Vendedor seleccionado</span>
                                            </div>
                                            <div class="mt-1 text-sm text-green-700">
                                                <strong>{{ $vendedor_selected->Nombre }}</strong> - Documento: {{ $vendedor_selected->Documento }}
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <!-- Hidden input for form submission -->
                                    <input type="hidden" id="vendedor" wire:model="vendedor_documento" value="{{ $vendedor_documento }}">
                                </div>

                                <div class="space-y-2 md:col-span-2">
                                    <x-select id="tipo_operacion" label="Tipo Operación"
                                              :options="['DISTRIBUCION','IMPORTACION','EXPORTACION']"
                                              class="transition-all duration-300 hover:border-orange-300 focus:ring-2 focus:ring-orange-500"/>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            @if(session()->has('error'))
                                <div class="w-full mb-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded-lg">
                                    {{ session('error') }}
                                </div>
                            @endif
                            <x-next class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white px-8 py-3 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"/>
                        </div>
                    </section>

                    <!-- STEP 2 – DETALLE -->
                    <section id="step-2" class="step space-y-8 animate-fadeIn {{ $step != 2 ? 'hidden' : '' }}">
                        <div class="flex items-center space-x-3 mb-8">
                            <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-orange-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                2
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">Detalle del Servicio</h3>
                                <p class="text-gray-600 text-sm">Especificaciones técnicas y logísticas del transporte</p>
                            </div>
                        </div>

                        <!-- Ubicaciones -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                            <h4 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                </svg>
                                Ubicaciones
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Origen Ciudad Searcher -->
                                <div class="space-y-2 relative">
                                    <label class="block text-sm font-medium text-gray-700">Origen</label>
                                    <div class="relative">
                                        <input 
                                            type="text" 
                                            wire:model.live.debounce.300ms="origen_search"
                                            placeholder="Buscar ciudad de origen por nombre..."
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300 hover:border-blue-300"
                                        />
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            @if($origen_selected)
                                                <button 
                                                    type="button"
                                                    wire:click="clearOrigenSearch"
                                                    class="text-gray-400 hover:text-gray-600"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            @else
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <!-- Dropdown Results -->
                                    @if($show_origen_dropdown)
                                        <div class="absolute z-50 w-full mt-1 bg-white origen-dropdown rounded-lg shadow-xl max-h-60 overflow-y-auto animate-fadeIn">
                                            @if(count($origen_results) > 0)
                                                @foreach($origen_results as $ciudad)
                                                    <button 
                                                        type="button"
                                                        wire:click="selectOrigen('{{ $ciudad->ciudad_codigo }}')"
                                                        class="w-full px-4 py-3 text-left hover:bg-blue-50 focus:bg-blue-50 focus:outline-none border-b border-gray-100 last:border-b-0 transition-all duration-200"
                                                    >
                                                        <div class="flex justify-between items-center">
                                                            <div>
                                                                <div class="font-medium text-gray-900">{{ $ciudad->ciudad_nombre }}</div>
                                                                <div class="text-sm text-gray-500">Código DANE: {{ $ciudad->ciudad_codigodane }}</div>
                                                            </div>
                                                        </div>
                                                    </button>
                                                @endforeach
                                            @else
                                                <div class="px-4 py-6 text-center text-gray-500">
                                                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    </svg>
                                                    <p class="text-sm">No se encontraron ciudades</p>
                                                    <p class="text-xs text-gray-400 mt-1">Intenta con otros términos de búsqueda</p>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                    
                                    <!-- Selected City Info -->
                                    @if($origen_selected)
                                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span class="text-sm font-medium text-blue-800">Ciudad de origen seleccionada</span>
                                            </div>
                                            <div class="mt-1 text-sm text-blue-700">
                                                <strong>{{ $origen_selected->ciudad_nombre }}</strong> - Código DANE: <strong>{{ $origen_selected->ciudad_codigodane }}</strong>
                                            </div>
                                            <div class="mt-1 text-xs text-blue-600">
                                                Valor cargado: <code class="bg-blue-100 px-1 rounded">{{ $origen_codigodane }}</code>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <!-- Hidden input for form submission -->
                                    <input type="hidden" id="origen" wire:model="origen_codigodane" value="{{ $origen_codigodane }}">
                                </div>
                                
                                <!-- Destino Ciudad Searcher -->
                                <div class="space-y-2 relative">
                                    <label class="block text-sm font-medium text-gray-700">Destino</label>
                                    <div class="relative">
                                        <input 
                                            type="text" 
                                            wire:model.live.debounce.300ms="destino_search"
                                            placeholder="Buscar ciudad de destino por nombre..."
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300 hover:border-blue-300"
                                        />
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            @if($destino_selected)
                                                <button 
                                                    type="button"
                                                    wire:click="clearDestinoSearch"
                                                    class="text-gray-400 hover:text-gray-600"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            @else
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <!-- Dropdown Results -->
                                    @if($show_destino_dropdown)
                                        <div class="absolute z-50 w-full mt-1 bg-white destino-dropdown rounded-lg shadow-xl max-h-60 overflow-y-auto animate-fadeIn">
                                            @if(count($destino_results) > 0)
                                                @foreach($destino_results as $ciudad)
                                                    <button 
                                                        type="button"
                                                        wire:click="selectDestino('{{ $ciudad->ciudad_codigo }}')"
                                                        class="w-full px-4 py-3 text-left hover:bg-blue-50 focus:bg-blue-50 focus:outline-none border-b border-gray-100 last:border-b-0 transition-all duration-200"
                                                    >
                                                        <div class="flex justify-between items-center">
                                                            <div>
                                                                <div class="font-medium text-gray-900">{{ $ciudad->ciudad_nombre }}</div>
                                                                <div class="text-sm text-gray-500">Código DANE: {{ $ciudad->ciudad_codigodane }}</div>
                                                            </div>
                                                        </div>
                                                    </button>
                                                @endforeach
                                            @else
                                                <div class="px-4 py-6 text-center text-gray-500">
                                                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    </svg>
                                                    <p class="text-sm">No se encontraron ciudades</p>
                                                    <p class="text-xs text-gray-400 mt-1">Intenta con otros términos de búsqueda</p>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                    
                                    <!-- Selected City Info -->
                                    @if($destino_selected)
                                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span class="text-sm font-medium text-blue-800">Ciudad de destino seleccionada</span>
                                            </div>
                                            <div class="mt-1 text-sm text-blue-700">
                                                <strong>{{ $destino_selected->ciudad_nombre }}</strong> - Código DANE: <strong>{{ $destino_selected->ciudad_codigodane }}</strong>
                                            </div>
                                            <div class="mt-1 text-xs text-blue-600">
                                                Valor cargado: <code class="bg-blue-100 px-1 rounded">{{ $destino_codigodane }}</code>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <!-- Hidden input for form submission -->
                                    <input type="hidden" id="destino" wire:model="destino_codigodane" value="{{ $destino_codigodane }}">
                                </div>
                            </div>
                        </div>

                        <!-- Mercancía -->
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200">
                            <h4 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a1 1 0 00-2 0v7.268a2 2 0 000 3.464V16a1 1 0 102 0v-1.268a2 2 0 000-3.464V4zM11 4a1 1 0 10-2 0v1.268a2 2 0 000 3.464V16a1 1 0 102 0V8.732a2 2 0 000-3.464V4zM16 3a1 1 0 011 1v7.268a2 2 0 010 3.464V16a1 1 0 11-2 0v-1.268a2 2 0 010-3.464V4a1 1 0 011-1z"/>
                                </svg>
                                Información de la Mercancía
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div class="space-y-2">
                                    <x-input id="cantidad_mercancia" label="Cantidad Mercancía" type="number" class="transition-all duration-300 hover:border-green-300 focus:ring-2 focus:ring-green-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="peso"               label="Peso (kg)"          type="number" class="transition-all duration-300 hover:border-green-300 focus:ring-2 focus:ring-green-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="valor_mercancia" label="Valor Mercancía" type="number" class="transition-all duration-300 hover:border-green-300 focus:ring-2 focus:ring-green-500"/>
                                </div>
                                <!-- Producto Searcher -->
                                <div class="space-y-2 relative">
                                    <label class="block text-sm font-medium text-gray-700">Producto</label>
                                    <div class="relative">
                                        <input 
                                            type="text" 
                                            wire:model.live.debounce.300ms="producto_search"
                                            placeholder="Buscar producto por nombre..."
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-300 hover:border-green-300"
                                        />
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            @if($producto_selected)
                                                <button 
                                                    type="button"
                                                    wire:click="clearProductoSearch"
                                                    class="text-gray-400 hover:text-gray-600"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            @else
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <!-- Dropdown Results -->
                                    @if($show_producto_dropdown)
                                        <div class="absolute z-50 w-full mt-1 bg-white producto-dropdown rounded-lg shadow-xl max-h-60 overflow-y-auto animate-fadeIn">
                                            @if(count($producto_results) > 0)
                                                @foreach($producto_results as $producto)
                                                    <button 
                                                        type="button"
                                                        wire:click="selectProducto('{{ $producto->producto_codigo }}')"
                                                        class="w-full px-4 py-3 text-left hover:bg-green-50 focus:bg-green-50 focus:outline-none border-b border-gray-100 last:border-b-0 transition-all duration-200"
                                                    >
                                                        <div class="flex justify-between items-center">
                                                            <div>
                                                                <div class="font-medium text-gray-900">{{ $producto->producto_nombre }}</div>
                                                            </div>
                                                            <div class="text-sm font-semibold text-green-600 bg-green-100 px-2 py-1 rounded">
                                                                {{ $producto->producto_codigo }}
                                                            </div>
                                                        </div>
                                                    </button>
                                                @endforeach
                                            @else
                                                <div class="px-4 py-6 text-center text-gray-500">
                                                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                    </svg>
                                                    <p class="text-sm">No se encontraron productos</p>
                                                    <p class="text-xs text-gray-400 mt-1">Intenta con otros términos de búsqueda</p>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                    
                                    <!-- Selected Product Info -->
                                    @if($producto_selected)
                                        <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span class="text-sm font-medium text-green-800">Producto seleccionado</span>
                                            </div>
                                            <div class="mt-1 text-sm text-green-700">
                                                <strong>{{ $producto_selected->producto_nombre }}</strong> - Código: {{ $producto_selected->producto_codigo }}
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <!-- Hidden input for form submission -->
                                    <input type="hidden" id="producto" wire:model="producto_codigo" value="{{ $producto_codigo }}">
                                </div>
                                <!-- Empaque Searcher -->
                                <div class="space-y-2 relative">
                                    <label class="block text-sm font-medium text-gray-700">Empaque</label>
                                    <div class="relative">
                                        <input 
                                            type="text" 
                                            wire:model.live.debounce.300ms="empaque_search"
                                            placeholder="Buscar empaque por nombre..."
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-300 hover:border-green-300"
                                        />
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            @if($empaque_selected)
                                                <button 
                                                    type="button"
                                                    wire:click="clearEmpaqueSearch"
                                                    class="text-gray-400 hover:text-gray-600"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            @else
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <!-- Dropdown Results -->
                                    @if($show_empaque_dropdown)
                                        <div class="absolute z-50 w-full mt-1 bg-white empaque-dropdown rounded-lg shadow-xl max-h-60 overflow-y-auto animate-fadeIn">
                                            @if(count($empaque_results) > 0)
                                                @foreach($empaque_results as $empaque)
                                                    <button 
                                                        type="button"
                                                        wire:click="selectEmpaque('{{ $empaque->Codigo }}')"
                                                        class="w-full px-4 py-3 text-left hover:bg-green-50 focus:bg-green-50 focus:outline-none border-b border-gray-100 last:border-b-0 transition-all duration-200"
                                                    >
                                                        <div class="flex justify-between items-center">
                                                            <div>
                                                                <div class="font-medium text-gray-900">{{ $empaque->Nombre }}</div>
                                                            </div>
                                                            <div class="text-sm font-semibold text-green-600 bg-green-100 px-2 py-1 rounded">
                                                                {{ $empaque->Codigo }}
                                                            </div>
                                                        </div>
                                                    </button>
                                                @endforeach
                                            @else
                                                <div class="px-4 py-6 text-center text-gray-500">
                                                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                    </svg>
                                                    <p class="text-sm">No se encontraron empaques</p>
                                                    <p class="text-xs text-gray-400 mt-1">Intenta con otros términos de búsqueda</p>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                    
                                    <!-- Selected Packing Info -->
                                    @if($empaque_selected)
                                        <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span class="text-sm font-medium text-green-800">Empaque seleccionado</span>
                                            </div>
                                            <div class="mt-1 text-sm text-green-700">
                                                <strong>{{ $empaque_selected->nombre }}</strong> - Código: <strong>{{ $empaque_selected->codigo }}</strong>
                                            </div>
                                            <div class="mt-1 text-xs text-green-600">
                                                Valor cargado: <code class="bg-green-100 px-1 rounded">{{ $empaque_codigo }}</code>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <!-- Hidden input for form submission -->
                                    <input type="hidden" id="empaque" wire:model="empaque_codigo" value="{{ $empaque_codigo }}">
                                </div>
                                <div class="space-y-2 md:col-span-2 lg:col-span-1">
                                    <x-input  id="descripcion_mercancia" label="Descripción Mercancía" class="transition-all duration-300 hover:border-green-300 focus:ring-2 focus:ring-green-500"/>
                                </div>
                            </div>
                        </div>

                        <!-- Vehículos -->
                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-6 border border-purple-200">
                            <h4 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                    <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1V8a1 1 0 00-1-1h-3z"/>
                                </svg>
                                Especificaciones del Vehículo
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div class="space-y-2">
                                    <x-input id="cantidad_vehiculos" label="Cantidad Vehículos" type="number" class="transition-all duration-300 hover:border-purple-300 focus:ring-2 focus:ring-purple-500"/>
                                </div>
                                <!-- Clase Vehículo Searcher -->
                                <div class="space-y-2 relative">
                                    <label class="block text-sm font-medium text-gray-700">Clase Vehículo</label>
                                    <div class="relative">
                                        <input 
                                            type="text" 
                                            wire:model.live.debounce.300ms="vehicleClassSearch"
                                            placeholder="Buscar clase de vehículo por nombre..."
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-300 hover:border-purple-300"
                                        />
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            @if($vehicleClassSelected)
                                                <button 
                                                    type="button"
                                                    wire:click="clearClaseVehiculoSearch"
                                                    class="text-gray-400 hover:text-gray-600"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            @else
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <!-- Dropdown Results -->
                                    @if($showVehicleClassDropdown)
                                        <div class="absolute z-50 w-full mt-1 bg-white clase-vehiculo-dropdown rounded-lg shadow-xl max-h-60 overflow-y-auto animate-fadeIn">
                                            @if(count($vehicleClassResults) > 0)
                                                @foreach($vehicleClassResults as $claseVehiculo)
                                                    <button 
                                                        type="button"
                                                        wire:click="selectClaseVehiculo('{{ $claseVehiculo->Codigo }}')"
                                                        class="w-full px-4 py-3 text-left hover:bg-purple-50 focus:bg-purple-50 focus:outline-none border-b border-gray-100 last:border-b-0 transition-all duration-200"
                                                    >
                                                        <div class="flex justify-between items-center">
                                                            <div>
                                                                <div class="font-medium text-gray-900">{{ $claseVehiculo->Nombre }}</div>
                                                            </div>
                                                            <div class="text-sm font-semibold text-purple-600 bg-purple-100 px-2 py-1 rounded">
                                                                {{ $claseVehiculo->Codigo }}
                                                            </div>
                                                        </div>
                                                    </button>
                                                @endforeach
                                            @else
                                                <div class="px-4 py-6 text-center text-gray-500">
                                                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1-1H8a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                    <p class="text-sm">No se encontraron clases de vehículo</p>
                                                    <p class="text-xs text-gray-400 mt-1">Intenta con otros términos de búsqueda</p>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                    
                                    <!-- Selected Vehicle Class Info -->
                                    @if($vehicleClassSelected)
                                        <div class="p-3 bg-purple-50 border border-purple-200 rounded-lg">
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span class="text-sm font-medium text-purple-800">Clase de vehículo seleccionada</span>
                                            </div>
                                            <div class="mt-1 text-sm text-purple-700">
                                                <strong>{{ $vehicleClassSelected->Nombre }}</strong> - Código: <strong>{{ $vehicleClassSelected->Codigo }}</strong>
                                            </div>
                                            <div class="mt-1 text-xs text-purple-600">
                                                Valor cargado: <code class="bg-purple-100 px-1 rounded">{{ $vehicleClassCode }}</code>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <!-- Hidden input for form submission -->
                                    <input type="hidden" id="clase_vehiculo" wire:model="vehicleClassCode" value="{{ $vehicleClassCode }}">
                                </div>
                                <div class="space-y-2">
                                    <label for="carroceria_search" class="block text-sm font-medium text-gray-700">Carrocería</label>
                                    <div class="relative">
                                        <input type="text" 
                                               id="carroceria_search"
                                               wire:model.live.debounce.300ms="bodyworkSearch" 
                                               placeholder="Buscar carrocería..." 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-300 hover:border-purple-300"
                                               autocomplete="off">
                                        
                                        @if($showBodyworkDropdown && count($bodyworkResults) > 0)
                                            <div class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
                                                @foreach($bodyworkResults as $carroceria)
                                                    <div wire:click="selectCarroceria('{{ $carroceria->Codigo }}')" 
                                                         class="px-4 py-2 hover:bg-purple-50 cursor-pointer border-b border-gray-100 last:border-b-0">
                                                        <div class="font-medium text-gray-900">{{ $carroceria->Nombre }}</div>
                                                        <div class="text-sm text-gray-500">Código: {{ $carroceria->Codigo }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                        
                                        @if($showBodyworkDropdown && count($bodyworkResults) == 0 && strlen($bodyworkSearch) >= 2)
                                            <div class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg">
                                                <div class="px-4 py-2 text-gray-500 text-center">
                                                    No se encontraron carrocerías
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    @if($bodyworkSelected)
                                        <div class="mt-2 p-3 bg-purple-50 border border-purple-200 rounded-lg">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-purple-600 font-medium">✓ Carrocería seleccionada</span>
                                                <button type="button" 
                                                        wire:click="clearCarroceriaSearch" 
                                                        class="text-purple-500 hover:text-purple-700 text-sm">
                                                    Cambiar
                                                </button>
                                            </div>
                                            <div class="mt-1 text-sm text-purple-700">
                                                <strong>{{ $bodyworkSelected->Nombre }}</strong> - Código: <strong>{{ $bodyworkSelected->Codigo }}</strong>
                                            </div>
                                            <div class="mt-1 text-xs text-purple-600">
                                                Valor cargado: <code class="bg-purple-100 px-1 rounded">{{ $bodyworkCode }}</code>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <!-- Hidden input for form submission -->
                                    <input type="hidden" id="carroceria" wire:model="bodyworkCode" value="{{ $bodyworkCode }}">
                                </div>
                                <div class="space-y-2">
                                    <x-input id="minimo_modelo"      label="Mínimo Modelo"      type="number" class="transition-all duration-300 hover:border-purple-300 focus:ring-2 focus:ring-purple-500"/>
                                </div>
                            </div>
                        </div>

                        <!-- Tarifas y Flete -->
                        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl p-6 border border-yellow-200">
                            <h4 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                </svg>
                                Tarifas y Costos
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div class="space-y-2">
                                    <x-select id="tipo_flete" label="Tipo Flete"
                                              :options="['CARGA SUELTA','CUPO','CONSOLIDADO','EXPRESO','GALON','VAN','CONTENEDOR']"
                                              class="transition-all duration-300 hover:border-yellow-300 focus:ring-2 focus:ring-yellow-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="flete_conductor"  label="Flete Conductor"   type="number" class="transition-all duration-300 hover:border-yellow-300 focus:ring-2 focus:ring-yellow-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="flete_ministerio" label="Flete Ministerio"  type="number" class="transition-all duration-300 hover:border-yellow-300 focus:ring-2 focus:ring-yellow-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-select id="tipo_tarifa" label="Tipo Tarifa" :options="['GENERAL','PESO','GALON']" class="transition-all duration-300 hover:border-yellow-300 focus:ring-2 focus:ring-yellow-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="tarifa_cliente" label="Tarifa Cliente" type="number" class="transition-all duration-300 hover:border-yellow-300 focus:ring-2 focus:ring-yellow-500"/>
                                </div>
                            </div>
                        </div>

                        <!-- Responsabilidades -->
                        <div class="bg-gradient-to-r from-red-50 to-pink-50 rounded-xl p-6 border border-red-200">
                            <h4 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1V8zm8 0a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1h-6a1 1 0 01-1-1V8z" clip-rule="evenodd"/>
                                </svg>
                                Responsabilidades y Servicios Adicionales
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div class="space-y-2">
                                    <x-select id="cargue_cuenta_de"    label="Cargue por Cuenta de"    :options="['CLIENTE','EMPRESA','DESTINATARIO']" class="transition-all duration-300 hover:border-red-300 focus:ring-2 focus:ring-red-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-select id="descargue_cuenta_de" label="Descargue por Cuenta de" :options="['CLIENTE','EMPRESA','DESTINATARIO']" class="transition-all duration-300 hover:border-red-300 focus:ring-2 focus:ring-red-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-select id="seguro_cuenta_de"    label="Seguro por Cuenta de"    :options="['CLIENTE','EMPRESA']" class="transition-all duration-300 hover:border-red-300 focus:ring-2 focus:ring-red-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-select id="kit_seguridad"         label="Kit Seguridad" :options="['SI','NO']" class="transition-all duration-300 hover:border-red-300 focus:ring-2 focus:ring-red-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="sub_cliente" label="Sub Cliente" type="number" class="transition-all duration-300 hover:border-red-300 focus:ring-2 focus:ring-red-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-select id="tipo_remesa_rndc" label="Tipo Remesa RNDC"
                                              :options="['REMESA GENERAL','CONTENEDOR CARGADO','CONTENEDOR VACIO']"
                                              class="transition-all duration-300 hover:border-red-300 focus:ring-2 focus:ring-red-500"/>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <x-next num="2" class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white px-8 py-3 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"/>
                        </div>
                    </section>

                    <!-- STEP 3 – CARGUE -->
                    <section id="step-3" class="step space-y-8 animate-fadeIn {{ $step != 3 ? 'hidden' : '' }}">
                        <div class="flex items-center space-x-3 mb-8">
                            <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-orange-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                3
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">Información de Cargue</h3>
                                <p class="text-gray-600 text-sm">Detalles sobre la recolección y entrega de la mercancía</p>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-teal-50 to-cyan-50 rounded-xl p-6 border border-teal-200">
                            <h4 class="text-lg font-semibold text-gray-700 mb-6 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-teal-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                Programación y Contactos
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div class="space-y-2">
                                    <x-input id="fecha_cargue" label="Fecha Cargue" type="date" class="transition-all duration-300 hover:border-teal-300 focus:ring-2 focus:ring-teal-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="hora_cargue"  label="Hora Cargue"  type="time" class="transition-all duration-300 hover:border-teal-300 focus:ring-2 focus:ring-teal-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="promesa_servicio"     label="Promesa Servicio"     type="date" class="transition-all duration-300 hover:border-teal-300 focus:ring-2 focus:ring-teal-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="remitente"    label="Remitente"    type="number" class="transition-all duration-300 hover:border-teal-300 focus:ring-2 focus:ring-teal-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="destinatario" label="Destinatario" type="number" class="transition-all duration-300 hover:border-teal-300 focus:ring-2 focus:ring-teal-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="contacto"     label="Contacto" class="transition-all duration-300 hover:border-teal-300 focus:ring-2 focus:ring-teal-500"/>
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <x-input id="observacion_cargue" label="Observación Cargue" class="transition-all duration-300 hover:border-teal-300 focus:ring-2 focus:ring-teal-500"/>
                                </div>
                                <div class="space-y-2">
                                    <x-input id="documento_transporte" label="Documento Transporte" type="number" class="transition-all duration-300 hover:border-teal-300 focus:ring-2 focus:ring-teal-500"/>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <x-next num="3" class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white px-8 py-3 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"/>
                        </div>
                    </section>

                    <!-- STEP 4 – CONTENEDOR -->
                    <section id="step-4" class="step space-y-8 animate-fadeIn {{ $step != 4 ? 'hidden' : '' }}">
                        <div class="flex items-center space-x-3 mb-8">
                            <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-orange-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                4
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">Contenedor</h3>
                                <p class="text-gray-600 text-sm">Configuración para transporte en contenedor</p>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-8 border border-indigo-200 text-center">
                            <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                </svg>
                            </div>
                            <h4 class="text-xl font-semibold text-gray-700 mb-6">¿El transporte requiere contenedor?</h4>
                            <div class="max-w-md mx-auto">
                                <x-select id="contenedor" label="¿Contenedor?" :options="['SI','NO']" class="text-lg transition-all duration-300 hover:border-indigo-300 focus:ring-2 focus:ring-indigo-500"/>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <x-next num="4" class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white px-8 py-3 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"/>
                        </div>
                    </section>

                    <!-- STEP 5 – INTERNACIONAL -->
                    <section id="step-5" class="step space-y-8 animate-fadeIn {{ $step != 5 ? 'hidden' : '' }}">
                        <div class="flex items-center space-x-3 mb-8">
                            <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-orange-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                5
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">Modalidad Internacional</h3>
                                <p class="text-gray-600 text-sm">Configuración para comercio internacional</p>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl p-8 border border-emerald-200 text-center">
                            <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-full flex items-center justify-center shadow-lg">
                                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C6.512 5.73 6.974 6 7.5 6A1.5 1.5 0 019 7.5V8a2 2 0 004 0 2 2 0 011.523-1.943A5.977 5.977 0 0116 10c0 .34-.028.675-.083 1H15a2 2 0 00-2 2v2.197A5.973 5.973 0 0110 16v-2a2 2 0 00-2-2 2 2 0 01-2-2 2 2 0 00-1.668-1.973z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <h4 class="text-xl font-semibold text-gray-700 mb-6">Seleccione la modalidad internacional</h4>
                            <div class="max-w-md mx-auto">
                                <x-select id="modalidad_internacional" label="Modalidad"
                                          :options="['OTM','DTA','DTAI','NACIONALIZADA']"
                                          class="text-lg transition-all duration-300 hover:border-emerald-300 focus:ring-2 focus:ring-emerald-500"/>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <x-next num="5" class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white px-8 py-3 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"/>
                        </div>
                    </section>

                    <!-- STEP 6 – Acompañamiento -->
                    <section id="step-6" class="step space-y-8 animate-fadeIn {{ $step != 6 ? 'hidden' : '' }}">
                        <div class="flex items-center space-x-3 mb-8">
                            <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-orange-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                6
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">Vehículo de Acompañamiento</h3>
                                <p class="text-gray-600 text-sm">Configuración final del servicio de transporte</p>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-amber-50 to-yellow-50 rounded-xl p-8 border border-amber-200">
                            <div class="text-center mb-8">
                                <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-amber-500 to-yellow-600 rounded-full flex items-center justify-center shadow-lg">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                        <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1V8a1 1 0 00-1-1h-3z"/>
                                    </svg>
                                </div>
                                <h4 class="text-xl font-semibold text-gray-700 mb-2">¿Requiere vehículos de acompañamiento?</h4>
                                <p class="text-gray-600 text-sm">Especifique la cantidad de vehículos adicionales necesarios</p>
                            </div>
                            
                            <div class="max-w-md mx-auto">
                                <x-input id="vehiculo_acom" label="# Vehículos Acompañamiento" type="number" 
                                        class="text-lg transition-all duration-300 hover:border-amber-300 focus:ring-2 focus:ring-amber-500"/>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200">
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto mb-3 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-700 mb-2">¡Último paso!</h4>
                                <p class="text-gray-600 text-sm mb-6">Revise toda la información antes de enviar su solicitud</p>
                                <x-submit class="bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white px-10 py-4 rounded-lg font-bold text-lg transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"/>
                            </div>
                        </div>
                    </section>
                    </div>
                </form>

                <!-- ═══════════  Chat lateral mejorado  ═══════════-->
                <aside class="w-full md:w-1/2 bg-gradient-to-br from-gray-50 to-gray-100 border-t md:border-t-0 md:border-l flex flex-col">
                    <!-- Header del chat -->
                    <div class="bg-gradient-to-r from-gray-700 to-gray-800 text-white p-6 flex-shrink-0">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold">Asistente IA</h3>
                                <p class="text-gray-300 text-sm">Estoy aquí para ayudarte</p>
                            </div>
                            <div class="flex-1"></div>
                            <div class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                        </div>
                    </div>

                    <!-- Área de mensajes con scroll interno fijo -->
                    <div class="flex-1 flex flex-col min-h-0">
                        <div id="chatbox" class="flex-1 overflow-y-auto p-6 space-y-4 chat-scroll-behavior">
                            <!-- Mensaje de bienvenida -->
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div class="bg-white rounded-2xl rounded-tl-sm p-4 shadow-sm border border-gray-200 max-w-xs">
                                    <p class="text-gray-700 text-sm">¡Hola! Soy tu asistente IA. Estoy aquí para ayudarte a completar tu solicitud de transporte. ¿En qué puedo ayudarte?</p>
                                </div>
                            </div>
                        </div>

                        <!-- Input área mejorada -->
                        <div class="p-6 border-t border-gray-200 bg-white flex-shrink-0">
                        <div class="flex items-end space-x-3">
                            <div class="flex-1 relative">
                                <textarea id="userInput"
                                          rows="1"
                                          placeholder="Escribe tu mensaje aquí..."
                                          class="w-full resize-none rounded-xl border border-gray-300 px-4 py-3 pr-12 focus:ring-2 focus:ring-orange-500 focus:border-transparent outline-none transition-all duration-300 text-gray-700 placeholder-gray-400 bg-gray-50 focus:bg-white"></textarea>
                                <div class="absolute right-3 bottom-3 text-xs text-gray-400">
                                    Enter para enviar
                                </div>
                            </div>

                            <button type="button"
                                    onclick="sendMessage()"
                                    class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-r from-orange-500 to-orange-600 text-white flex items-center justify-center hover:from-orange-600 hover:to-orange-700 transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                            </button>
                        </div>
                        <div class="flex items-center justify-between mt-2 text-xs text-gray-500">
                            <span></span>
                            <span class="flex items-center space-x-1">
                                <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                                <span>En línea</span>
                            </span>
                        </div>
                        </div> <!-- Cierre del contenedor flex del área de mensajes -->
                    </div>
                </aside>

            </div>
        </div>
    @endif

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/sortablejs/Sortable.min.js') }}"></script>
    <script src="{{ asset('vendor/sortablejs/jquery-sortable.js') }}"></script>
    <script>
        const openaiUri = 'https://api.openai.com/v1';
        const privateToken = '{{ env("OPENAI_API_KEY") }}';
        const assistantStep1 = 'asst_NRyScHbWS5rBZlW3LZ7BjpBx';
        const assistantStep2 = 'asst_4OeigFNuv9712ODPBFJREi5J';
        const assistantStep3 = 'asst_svqD1PUjnJEdlCWVUXXIWcUU';
        const assistantStep4 = 'asst_MsWhUGMTnQIftOVk1oEoq4Ov';
        const assistantStep5 = 'asst_G7qUNTQKvafZvmre0i9iioEk';
        const assistantStep6 = 'asst_cySu9Zr39jiCluhmYSgmpEG6';
        const assistantStep7 = 'asst_9INijQyakJIrtVfYguey5vzF';
        const assistantStep8 = 'asst_ZQJzZV9IuPLNLJaJVFFPNudW';
        const assistantStep9 = 'asst_RogHyE0LmSdJMoORCELLsQrK';
        // const chatbox = document.getElementById('chatbox');
        // const userInput = document.getElementById('userInput');


        // Generar un ID único para el thread
        let threadID = null;
        /* ===================================================================
         * WIZARD NAVIGATION JAVASCRIPT - DISABLED for Livewire compatibility
         * This section has been commented out as it was conflicting with Livewire
         * Now using Livewire methods instead of JavaScript/AJAX
         * =================================================================== */

        /*
        let currentStep = 1;
        const totalSteps = 6;

        function updateProgressBar () {
            const percent = (currentStep-1) / (totalSteps-1) * 100;  // 0-100
            document.getElementById('progressBarFill').style.width  = percent + '%';
            document.getElementById('progressText').innerText       = `Paso ${currentStep} / ${totalSteps}`;
        }

        document.addEventListener('click', function(event) {
            // Skip if click is inside any dropdown (but not origen/destino since they're now simple inputs)
            let targetElement = event.target;
            if (targetElement.closest('.ciudad-dropdown, .cliente-dropdown, .vendedor-dropdown, .producto-dropdown')) {
                return; // Don't process this click
            }
            
            // Skip if the click is on Livewire-managed elements (wire:model, wire:click, etc.)
            if (targetElement.hasAttribute('wire:model') || 
                targetElement.hasAttribute('wire:model.live') || 
                targetElement.hasAttribute('wire:click') ||
                targetElement.closest('[wire\\:model]') ||
                targetElement.closest('[wire\\:model\\.live]') ||
                targetElement.closest('[wire\\:click]')) {
                return; // Don't process Livewire managed elements
            }
            
            // Busca si el click fue en un .next-btn (o uno de sus hijos)
            let el = event.target;
            while (el && el !== document.body) {
                if (el.id && (el.id.startsWith('next-btn') || el.id === 'submitButton')) {
                    // ... validation and navigation logic ...
                    // This has been moved to Livewire methods
                    break; // Importante para salir del while
                }
                el = el.parentElement;
            }
        });
        */


        // Crear un nuevo thread
        async function createThread() {
            if (!threadID) { // Verificar si el `threadID` ya existe
                try {
                    const response = await fetch(`${openaiUri}/threads`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${privateToken}`,
                            'OpenAI-Beta': 'assistants=v2'
                        }
                    });

                    const data = await response.json();
                    if (data && data.object === 'thread') {
                        threadID = data.id;
                        localStorage.setItem('threadID', threadID); // Guardar en localStorage
                    }
                } catch (error) {
                    console.error('Error al crear el thread:', error);
                }
            }
        }

        // Enviar un mensaje
        async function sendMessage() {
            try {
                const userInput = document.getElementById('userInput');
                if (!userInput) {
                    console.error("No se encontró el input #userInput en el DOM");
                    return;
                }
                const chatbox = document.getElementById('chatbox');
                if (!chatbox) {
                    console.error("No se encontró el chatbox en el DOM");
                    return;
                }
                const userMessage = userInput.value;

                if (!threadID) {
                    await createThread(); // Crear el thread si no existe
                }

                // Mostrar el mensaje del usuario en la interfaz
                const userMessageElement = document.createElement('div');
                userMessageElement.className = 'message-bubble userMessage';
                userMessageElement.textContent = userMessage;
                chatbox.appendChild(userMessageElement);
                
                // Auto-scroll al final
                chatbox.scrollTop = chatbox.scrollHeight;
                
                userInput.value = '';

                // Enviar el mensaje a OpenAI
                const response = await fetch(`${openaiUri}/threads/${threadID}/messages`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${privateToken}`,
                        'OpenAI-Beta': 'assistants=v2'
                    },
                    body: JSON.stringify({
                        role: 'user',
                        content: userMessage
                    })
                });

                const data = await response.json();
                if (data) {
                    // Ejecutar el asistente después de enviar el mensaje
                    await runAssistant();
                }
            } catch (error) {
                console.error('Error al enviar mensaje:', error);
            }
        }

        async function checkRunStatus(runId) {
            try {
                const response = await fetch(`${openaiUri}/threads/${threadID}/runs/${runId}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${privateToken}`,
                        'OpenAI-Beta': 'assistants=v2'
                    }
                });

                const data = await response.json();

                if (data.status === 'completed') {
                    // Llamar a getMessages y procesar los mensajes
                    const messagesResponse = await getMessages();
                    const messages = messagesResponse.data;

                    // Encontrar el primer mensaje del bot
                    const botMessage = messages.find(message => message.role === 'assistant');

                    if (botMessage) {
                        // Extraer el texto del primer mensaje del bot
                        const botMessageText = botMessage.content.find(contentItem => contentItem.type === 'text').text
                            .value;

                        // Crear el elemento para mostrar el mensaje del bot
                        const botMessageElement = document.createElement('div');
                        botMessageElement.className = 'message-bubble botMessage';
                        // botMessageElement.textContent = botMessageText;
                        botMessageElement.innerHTML = botMessageText.replace(/\n/g, '<br>');
                        chatbox.appendChild(botMessageElement);
                        
                        // Auto-scroll al final
                        chatbox.scrollTop = chatbox.scrollHeight;
                    }
                } else if (data.status === 'requires_action' && data.required_action.type === 'submit_tool_outputs') {
                    const toolCallId = data.required_action.submit_tool_outputs.tool_calls[0].id;
                    const toolArguments = data.required_action.submit_tool_outputs.tool_calls[0].function.arguments;
                    if (currentStep == 1) {
                        llenarFormulario(toolArguments)
                    }
                    if (currentStep == 2) {
                        llenarFormulario2(toolArguments)
                    }
                    if (currentStep == 3) {
                        llenarFormulario3(toolArguments)
                    }
                    if (currentStep == 4) {
                        llenarFormulario4(toolArguments)
                    }
                    if (currentStep == 5) {
                        llenarFormulario5(toolArguments)
                    }
                    if (currentStep == 6) {
                        llenarFormulario6(toolArguments)
                    }
                    if (currentStep == 7) {
                        llenarFormulario7(toolArguments)
                    }
                    if (currentStep == 8) {
                        llenarFormulario8(toolArguments)
                    }
                    if (currentStep == 9) {
                        llenarFormulario9(toolArguments)
                    }
                } else if (data.status === 'failed') {
                    console.error("El run falló:", data);
                } else {
                    // Esperar un tiempo antes de volver a verificar
                    setTimeout(() => checkRunStatus(runId), 5000); // Espera 5 segundos antes de volver a verificar
                }
            } catch (error) {
                console.error('Error al verificar el estado del run:', error);
            }
        }
        // Ejecutar el asistente y verificar el estado del run
        async function runAssistant() {
            try {
                let assistantId;
                switch (currentStep) {
                    case 1:
                        assistantId = assistantStep1;
                        break;
                    case 2:
                        assistantId = assistantStep2;
                        break;
                    case 3:
                        assistantId = assistantStep3;
                        break;
                    case 4:
                        assistantId = assistantStep4;
                        break;
                    case 5:
                        assistantId = assistantStep5;
                        break;
                    case 6:
                        assistantId = assistantStep6;
                        break;
                    case 7:
                        assistantId = assistantStep7;
                        break;
                    case 8:
                        assistantId = assistantStep8;
                        break;
                    case 9:
                        assistantId = assistantStep9;
                        break;
                    default:
                        throw new Error("Paso actual no válido.");
                }
                const response = await fetch(`${openaiUri}/threads/${threadID}/runs`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${privateToken}`,
                        'OpenAI-Beta': 'assistants=v2'
                    },
                    body: JSON.stringify({
                        assistant_id: assistantId
                    })
                });

                const data = await response.json();

                if (data && data.status === 'queued') {
                    // Verificar el estado del run
                    checkRunStatus(data.id);
                } else {
                    console.error("Error en la ejecución del asistente:", data);
                }
            } catch (error) {
                console.error('Error al ejecutar el asistente:', error);
            }
        }

        async function getMessages() {
            try {
                const response = await fetch(`${openaiUri}/threads/${threadID}/messages`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${privateToken}`,
                        'OpenAI-Beta': 'assistants=v2'
                    }
                });

                return await response.json();
            } catch (error) {
                console.error('Error al obtener los mensajes:', error);
            }
        }

        function llenarFormulario(toolArgumentsParse) {
            let toolArguments = JSON.parse(toolArgumentsParse);

            const setValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || '';
                } else {
                    console.warn(`Elemento con ID ${id} no encontrado.`);
                }
            };

            setValue('tipo_viaje', toolArguments.tipo_viaje);
            setValue('moneda', toolArguments.moneda);
            setValue('fuente_solicitud', toolArguments.fuente_solicitud);
            setValue('observacion', toolArguments.observacion);
            setValue('condicion_despacho', toolArguments.condicion_despacho);
            setValue('condicion_facturacion', toolArguments.condicion_facturacion);
            setValue('observacion_remesa', toolArguments.observacion_remesa);
            setValue('tipo_imagen', toolArguments.tipo_imagen);
            setValue('recomendacion_trafico', toolArguments.recomendado_trafico);
            setValue('ciudad_facturacion', toolArguments.ciudad_facturacion);
            setValue('vendedor', toolArguments.vendedor);
            setValue('cliente_final', toolArguments.cliente_final);
            setValue('tipo_operacion', toolArguments.tipo_operacion);
            setValue('fecha_solicitud', toolArguments.fecha_solicitud);
            setValue('instrucciones_servicio', toolArguments.instrucciones_servicio);
            setValue('maersk_numero_viaje', toolArguments.maersk_numero_viaje);
            setValue('solicitud_servicio', toolArguments.solicitud_servicio);
            setValue('mostrar_digitalizados_vehiculos', toolArguments.digitalizados_web_vehiculo);
            setValue('mostrar_digitalizados_conductor', toolArguments.digitalizados_web_conductor);
            setValue('usuario_autorizado', toolArguments.usuario_autorizado);
            setValue('empresa', toolArguments.empresa);
            setValue('usuario', toolArguments.usuario);
        }

        function llenarFormulario2(toolArgumentsParse) {
            let toolArguments = JSON.parse(toolArgumentsParse);

            const setValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || '';
                } else {
                    console.warn(`Elemento con ID ${id} no encontrado.`);
                }
            };

            setValue("ciudad_intermedia", toolArguments.ciudad_intermedia)
            setValue("peso_despachado", toolArguments.peso_despachado)
            setValue("cantidad_vehiculos_despachados", toolArguments.cantidad_vehiculos_despachados)
            setValue("clase_vehiculo", toolArguments.clase_vehiculo)
            setValue("carroceria", toolArguments.carroceria)
            setValue("minimo_modelo", toolArguments.minimo_modelo)
            setValue("tipo_flete", toolArguments.tipo_flete)
            setValue("flete_conductor", toolArguments.flete_conductor)
            setValue("flete_ministerio", toolArguments.flete_ministerio)
            setValue("tipo_tarifa", toolArguments.tipo_tarifa)
            setValue("tarifa_cliente", toolArguments.tarifa_cliente)
            setValue("tipo_documento", toolArguments.tipo_documento)
            setValue("numero_documento", toolArguments.numero_documento)
            setValue("valor_mercancia", toolArguments.valor_mercancia)
            setValue("restricciones_cliente", toolArguments.restricciones_cliente)
            setValue("cargue_cuenta_de", toolArguments.cargue_cuenta_de)
            setValue("descargue_cuenta_de", toolArguments.descargue_cuenta_de)
            setValue("descripcion_mercancia", toolArguments.descripcion_mercancia)
            setValue("servicio_escolta", toolArguments.servicio_escolta)
            setValue("kit_seguridad", toolArguments.kit_seguridad)
            setValue("kit_cinchas", toolArguments.kit_cinchas)
            setValue("guia_acompanamiento", toolArguments.guia_acompanamiento)
            setValue("observacion_detalle", toolArguments.observacion_detalle)
            setValue("sub_cliente", toolArguments.sub_cliente)
            setValue("guia_despacho", toolArguments.guia_despacho)
            setValue("numero_viaje", toolArguments.numero_viaje)
            setValue("numero_pedido", toolArguments.numero_pedido)
            setValue("nota_entrega", toolArguments.nota_entrega)
            setValue("planilla_entrega", toolArguments.planilla_entrega)
            setValue("numero_factura", toolArguments.numero_factura)
            setValue("modelo", toolArguments.modelo)
            setValue("qty", toolArguments.qty)
            setValue("t_cbm", toolArguments.t_cbm)
            setValue("order_no", toolArguments.order_no)
            setValue("o_c_cliente", toolArguments.o_c_cliente)
            setValue("bodega", toolArguments.bodega)
            setValue("fecha_expiracion", toolArguments.fecha_expiracion)
            setValue("net_amt_txn", toolArguments.net_amt_txn)
            setValue("prec_unit", toolArguments.prec_unit)
            setValue("remark", toolArguments.remark)
            setValue("addr", toolArguments.addr)
            setValue("appoint_date", toolArguments.appoint_date)
            setValue("tipo_vehiculo", toolArguments.tipo_vehiculo)
            setValue("item", toolArguments.item)
            setValue("load", toolArguments.load)
            setValue("seguro_cuenta_de", toolArguments.seguro_cuenta_de)

        }

        function llenarFormulario3(toolArgumentsParse) {
            let toolArguments = JSON.parse(toolArgumentsParse);

            const setValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || '';
                } else {
                    console.warn(`Elemento con ID ${id} no encontrado.`);
                }
            };

            setValue('fecha_cargue', toolArguments.fecha_cargue)
            setValue('hora_cargue', toolArguments.hora_cargue)
            setValue('remitente', toolArguments.remitente)
            setValue('direccion_cargue', toolArguments.direccion_cargue)
            setValue('observacion_cargue', toolArguments.observacion_cargue)
            setValue('contacto', toolArguments.contacto)
            setValue('destinario', toolArguments.destinario)
            setValue('promesa_servicio', toolArguments.promesa_servicio)
            setValue('documento_transporte', toolArguments.documento_transporte)
            setValue('manifiesto_cliente', toolArguments.manifiesto_cliente)
            setValue('remesa_cliente', toolArguments.remesa_cliente)
            setValue('remision_cliente', toolArguments.remision_cliente)
            setValue('codigo_entrega', toolArguments.codigo_entrega)
            setValue('email', toolArguments.email)
        }

        function llenarFormulario4(toolArgumentsParse) {
            let toolArguments = JSON.parse(toolArgumentsParse);

            const setValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || '';
                } else {
                    console.warn(`Elemento con ID ${id} no encontrado.`);
                }
            };
            setValue('tipo_carga_cont', toolArguments.tipo_carga_cont)
            setValue('tamano_contenedor', toolArguments.tamano_contenedor)
            setValue('sitio_entrega_cont', toolArguments.sitio_entrega_cont)
            setValue('cantidad_cont', toolArguments.cantidad_cont)
            setValue('peso_contenedor', toolArguments.peso_contenedor)
            setValue('tipo_contenedor', toolArguments.tipo_contenedor)
            setValue('fecha_entrega_cont', toolArguments.fecha_entrega_cont)
            setValue('contenedor', toolArguments.contenedor)
            setValue('numero_cont', toolArguments.numero_cont)
        }

        function llenarFormulario5(toolArgumentsParse) {
            let toolArguments = JSON.parse(toolArgumentsParse);

            const setValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || '';
                } else {
                    console.warn(`Elemento con ID ${id} no encontrado.`);
                }
            };

            setValue('modalidad_internacional', toolArguments.modalidad_internacional)
            setValue('tipo_viaje_int', toolArguments.tipo_viaje)
            setValue('numero_documento_int', toolArguments.numero_documento_int)
            setValue('fecha_llegada_int', toolArguments.fecha_llegada_int)
            setValue('aduana_int', toolArguments.aduana_int)
            setValue('nombre_cliente', toolArguments.nombre_cliente)
            setValue('nombre_exportador', toolArguments.nombre_exportador)
            setValue('datos_agente_aduana', toolArguments.datos_agente_aduana)
            setValue('datos_bodega_ingresa', toolArguments.datos_bodega_ingresa)
            setValue('ciudad_int', toolArguments.ciudad_int)
            setValue('tipo_operacion_int', toolArguments.tipo_operacion)
            setValue('quien_paga_almacenamiento', toolArguments.quien_paga_almacenamiento)
            setValue('ciudad_otra', toolArguments.ciudad_otra)
            setValue('tipo_otro', toolArguments.tipo_otro)
            setValue('nombre_importador', toolArguments.nombre_importador)
            setValue('descripcion_mercancia', toolArguments.descripcion_mercancia)
            setValue('cantidad_peso_mercancia', toolArguments.cantidad_peso_mercancia)
            setValue('fecha_vencimiento_modalidad', toolArguments.fecha_vencimiento_modalidad)
            setValue('paso_frontera', toolArguments.paso_frontera)



        }

        function llenarFormulario6(toolArgumentsParse) {
            let toolArguments = JSON.parse(toolArgumentsParse);

            const setValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || '';
                } else {
                    console.warn(`Elemento con ID ${id} no encontrado.`);
                }
            };
            setValue('articulo_1', toolArguments.articulo_1)
            setValue('cantidad_1', toolArguments.cantidad_1)
            setValue('articulo_2', toolArguments.articulo_2)
            setValue('cantidad_2', toolArguments.cantidad_2)
            setValue('articulo_3', toolArguments.articulo_3)
            setValue('cantidad_3', toolArguments.cantidad_3)
            setValue('articulo_4', toolArguments.articulo_4)
            setValue('cantidad_4', toolArguments.cantidad_4)
        }

        function llenarFormulario7(toolArgumentsParse) {
            let toolArguments = JSON.parse(toolArgumentsParse);

            const setValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || '';
                } else {
                    console.warn(`Elemento con ID ${id} no encontrado.`);
                }
            };

            setValue('condicion_factura_condition', toolArguments.condicion_factura)
            setValue('factura_remesa_hija', toolArguments.factura_remesa_hija)
            setValue('opcion_factura_remesa', toolArguments.opcion_factura_remesa)
            setValue('opcion_condicion_cumplida', toolArguments.opcion_condicion_cumplida)
        }

        function llenarFormulario8(toolArgumentsParse) {
            let toolArguments = JSON.parse(toolArgumentsParse);

            const setValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || '';
                } else {
                    console.warn(`Elemento con ID ${id} no encontrado.`);
                }
            };
            setValue('vehiculo_acom', toolArguments.vehiculo_acom)
            setValue('tipo_vehiculo_acom', toolArguments.tipo_vehiculo_acom)
            setValue('acompanamiento_cuenta_acom', toolArguments.acompanamiento_cuenta_acom)
            setValue('valor_acompanante_acom', toolArguments.valor_acompanante_acom)
        }

        function llenarFormulario9(toolArgumentsParse) {
            let toolArguments = JSON.parse(toolArgumentsParse);

            const setValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || '';
                } else {
                    console.warn(`Elemento con ID ${id} no encontrado.`);
                }
            };


            setValue('nombre_cliente_ent', toolArguments.nombre_cliente_ent)
            setValue('direccion_entrega_ent', toolArguments.direccion_entrega_ent)
            setValue('numero_documento_ent', toolArguments.numero_documento_ent)
            setValue('cantidad_ent', toolArguments.cantidad_ent)
            setValue('empaque_ent', toolArguments.empaque_ent)
            setValue('f_12_ent', toolArguments.f_12_ent)

        }
        // Asegúrate de que la función sendMessage esté accesible globalmente
        window.sendMessage = sendMessage;

        // Event listener para el input del chat
        document.addEventListener('DOMContentLoaded', function() {
            const userInput = document.getElementById('userInput');
            if (userInput) {
                userInput.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter' && !event.shiftKey) {
                        event.preventDefault();
                        sendMessage();
                    }
                });
            }
        });
    </script>

    <script>
        $(document).ready(function() {
            $(document).on('click', '#sendInfo', function(e) {

                // Mapeo de nombres de colores a valores hexadecimales
                var colores = {
                    "rojo": "#F4A6A6", // Rojo tenue
                    "verde": "#B4D7B1", // Verde tenue
                    "azul": "#A2B8D9", // Azul tenue
                    "amarillo": "#F7E5B0", // Amarillo tenue
                    "negro": "#DADADA", // Negro tenue
                    "blanco": "#FFFFFF", // Blanco
                    "gris": "#C4C4C4" // Gris tenue
                    // Añadir más colores según sea necesario
                };

                // Obtén los valores del formulario
                var nombre = $('input[name="nombre"]').val();
                var nombreColor = $('input[name="color"]').val().toLowerCase();

                // Obtén el valor hexadecimal del color usando el nombre del color
                var color = colores[nombreColor] ||
                    '#FFFFFF'; // Usa blanco como valor predeterminado si el color no está en el mapeo

                // Crea el nuevo elemento padre
                var nuevoPadre = $('<div>').addClass('flex flex-col items-start justify-center mr-2').attr(
                    'draggable', 'true');

                var boton = $('<button>').addClass(
                        'flex flex-col px-6 pt-[1.62rem] pb-6 w-full md:w-[17rem] h-[5.562rem] rounded-xl text-left relative mb-1'
                    )
                    .css('background-color', color)
                    .append($('<h6>').addClass('text-[#202020] text-base font-medium leading-normal')
                        .text(nombre))
                    .append($('<span>').addClass('text-[#898989] text-sm font-normal leading-normal').text(
                        'Canal Creado'));

                var hijos = $('<div>').addClass('sortable-group-child')
                    .append($('<hr>').addClass(
                        ' text-[#202020] flex flex-row pl-[1.81rem] pt-[0.87rem] pb-[0.94rem] w-full md:w-[17rem] h-[0.100rem] rounded-xl bg-white text-left relative shadow-sm items-center justify-start gap-[1.13rem] non-draggable'
                    ));

                // Agrega el botón y los hijos al nuevo elemento padre
                nuevoPadre.append(boton);
                nuevoPadre.append(hijos);

                // Agrega el nuevo elemento padre al contenedor principal
                $('#printCanal').append(nuevoPadre);
                makeSortable();

                // Cierra el modal
                $('#closeModalSolicitud').modal('hidden'); // Método de Bootstrap para ocultar el modal
            });



            $('.openModalCanal1').click(function() {
                if ($('#openModalCanal2').hasClass('hidden')) {
                    $('#openModalCanal2').removeClass('hidden');
                } else {
                    $('#openModalCanal2').addClass('hidden');
                }
            });

            $('#closeModalSolicitud').click(function() {
                location.reload()
            })

            $('#closeModalSolicitud2').click(function() {
                location.reload()
            })

            $('.openModalSolicitud').click(function() {
                if ($('#openModalShow').hasClass('hidden')) {
                    var movedElement = $(this);
                    var quoteData = movedElement.attr('quote-data');
                    var quoteObject = JSON.parse(quoteData);
                    var fields = [{
                            label: 'Fecha de Cargue',
                            value: quoteObject.cargue.fecha_cargue
                        },
                        {
                            label: 'Hora de Cargue',
                            value: quoteObject.cargue.hora_cargue
                        },
                        {
                            label: 'Remitente',
                            value: quoteObject.cargue.remitente
                        },
                        {
                            label: 'Dirección de Cargue',
                            value: quoteObject.cargue.direccion_cargue
                        },
                        {
                            label: 'Observación de Cargue',
                            value: quoteObject.cargue.observacion_cargue
                        },
                        {
                            label: 'Contacto',
                            value: quoteObject.cargue.contacto
                        },
                        {
                            label: 'Destinatario',
                            value: quoteObject.cargue.destinario
                        },
                        {
                            label: 'Promesa de Servicio',
                            value: quoteObject.cargue.promesa_servicio
                        },
                        {
                            label: 'Documento de Transporte',
                            value: quoteObject.cargue.documento_transporte
                        },
                        {
                            label: 'Manifiesto del Cliente',
                            value: quoteObject.cargue.manifiesto_cliente
                        },
                        {
                            label: 'Remesa del Cliente',
                            value: quoteObject.cargue.remesa_cliente
                        },
                        {
                            label: 'Remisión del Cliente',
                            value: quoteObject.cargue.remision_cliente
                        },
                        {
                            label: 'Código de Entrega',
                            value: quoteObject.cargue.codigo_entrega
                        },
                    ];
                    var cargaPrint = document.getElementById('carga_print');

                    fields.forEach(function(field) {
                        var smallElement = document.createElement('small');
                        smallElement.className =
                            'text-[#898989] text-[0.625rem] font-normal leading-[1.03125rem]';
                        smallElement.textContent = field.label + ': ' + field.value;
                        cargaPrint.appendChild(smallElement);

                        // Create a line break
                        var lineBreak = document.createElement('br');
                        cargaPrint.appendChild(lineBreak);
                    });
                    $("#client_name").html(quoteObject.client.name)
                    $("#client_name_2").html(quoteObject.client.name)
                    $("#silogtran_status").html(quoteObject.silogtran_status)
                    $('#id_de_factura').html(quoteObject.id)
                    $('#origen_print').html(quoteObject.origen)
                    $('#destino_print').html(quoteObject.destino)
                    $('#fecha_creacion_print').html(quoteObject.created_at)
                    $('#id_print').html(quoteObject.id) 

                    $('#origin_input_print').val(quoteObject.detalle.origen)
                    $('#tipo_carga_input_print').val(quoteObject.detalle.empaque)
                    $('#producto_input_print').val(quoteObject.detalle.producto)
                    $('#destino_input_print').val(quoteObject.detalle.destino)
                    $('#peso_input_print').val(quoteObject.detalle.peso)
                    $('#cantidad_vehiculos_input_print').val(quoteObject.detalle.cantidad_vehiculos)
                    $('#tipo_vehiculo_input_print').val(quoteObject.detalle.clase_vehiculo)
                    $('#carroceria_input_print').val(quoteObject.detalle.carroceria)
                    $('#modelo_input_print').val(quoteObject.detalle.minimo_modelo)
                    $('#tipo_tarifa_input_print').val(quoteObject.detalle.tipo_tarifa)
                    $('#valor_mercancia_input_print').val(quoteObject.detalle.valor_mercancia)
                    $('#descripcion_mercancia_input_print').html(quoteObject.detalle.descripcion_mercancia)
                    $('#openModalShow').removeClass('hidden');
                } else {
                    $('#openModalShow').addClass('hidden');
                }
            });

        });
    </script>
    <script>
        // Crear una instancia de un observador de mutaciones
        const observer = new MutationObserver((mutationsList, observer) => {
            // Recorrer todas las mutaciones que acaban de suceder
            for (let mutation of mutationsList) {
                // Si el nodo agregado es un elemento
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    const newNode = mutation.addedNodes[0];
                    if (newNode.id === 'voiceElement') {
                        const startButton = document.getElementById('startButton');
                        const stopButton = document.getElementById(
                            'stopButton'); // Obtener referencia al botón de detener
                        const outputDiv = document.getElementById('input_message');
                        const recognition = new(window.SpeechRecognition || window.webkitSpeechRecognition || window
                            .mozSpeechRecognition || window.msSpeechRecognition)();
                        recognition.lang = 'es-ES'; // Cambiar a español de España
                        recognition.continuous = true; // Configurar para que el reconocimiento sea continuo
                        recognition.interimResults = true;

                        recognition.onresult = (event) => {
                            outputDiv.value = "";
                            let transcript = event.results;
                            for (let index = 0; index < transcript.length; index++) {
                                if (transcript[index].isFinal) {
                                    outputDiv.value += transcript[index][0].transcript;
                                }
                            }
                            const event1 = new Event('input', {
                                bubbles: true,
                                cancelable: true,
                            });
                            outputDiv.dispatchEvent(event1);
                        };

                        startButton.addEventListener('click', () => {
                            recognition.start();
                        });

                        stopButton.addEventListener('click', () => {
                            recognition.stop();
                        });


                    }

                }
            }
        });

        // Iniciar la observación del documento con una configuración específica
        observer.observe(document, {
            childList: true,
            subtree: true
        });
    </script>
    <script>
        function redirectToAICalls() {
            // Obtener el ID de cotización desde el span
            let cotizacionId = document.getElementById('id_print').innerText;
            
            // Validar que el ID no esté vacío
            if (!cotizacionId) {
                alert("No hay un ID de cotización disponible.");
                return;
            }

            // Redirigir a la vista con el ID en la URL
            window.location.href = `/ai-calls?cotizacion_id=${cotizacionId}`;
        }
    </script>
    <script>
        /* -----------------------------  WIZARD + AJAX (debug) - ENABLED ----------------------------- */
        $(function () {

            // Qué botón pertenece a qué paso y qué campos se envían 
            const STEP_CONFIG = {
                1:{btn:'#next-btn',     step:'step_1', fields:[
                    'tipo_viaje','moneda','fuente_solicitud','condicion_despacho',
                    'condicion_facturacion','ciudad_facturacion','vendedor','tipo_operacion',
                    'centro_costo_despacho','cliente_codigo' 
                ]},
                2:{btn:'#next-btn-2',   step:'step_2', fields:[
                    'origen','destino','cantidad_mercancia','peso','producto','empaque',
                    'cantidad_vehiculos','clase_vehiculo','carroceria','minimo_modelo','tipo_flete',
                    'flete_conductor','flete_ministerio','tipo_tarifa','tarifa_cliente','valor_mercancia',
                    'cargue_cuenta_de','descargue_cuenta_de','seguro_cuenta_de','descripcion_mercancia',
                    'kit_seguridad','sub_cliente','tipo_remesa_rndc'
                ]},
                3:{btn:'#next-btn-3',   step:'step_3', fields:[
                    'fecha_cargue','hora_cargue','remitente','observacion_cargue','contacto',
                    'destinatario','promesa_servicio','documento_transporte'
                ]},
                4:{btn:'#next-btn-4',   step:'step_4', fields:['contenedor']},
                5:{btn:'#next-btn-5',   step:'step_5', fields:['modalidad_internacional']},
                6:{btn:'#submitButton', step:'step_6', fields:['vehiculo_acom']}
            };

            // función que dispara la petición AJAX 
            function enviarPaso(el, cfg)
            {
                const data = {
                    step:              cfg.step,
                    CotizacionModelId: $('#CotizacionModelId').val()
                };

                cfg.fields.forEach(f => data[f] = $('#'+f).val());


                $.ajax({
                    url:     '/solicitud/progreso',
                    method:  'POST',
                    data:    data,
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },

                    success : function (resp) {
                        // ========= DEBUG: lo que contestó el back-end ========= 

                        if (resp.estado === 'completada') {
                            alert('¡Solicitud completada!');
                            location.reload();
                        }
                        // ─── detectar error Silogtran ─── 
                        if (resp.estado === 'error') {
                            alert('⚠️  Silogtran devolvió un error:\n\n' + resp.advertencia);

                            // Volvemos visualmente al paso 6 
                            currentStep = 6;
                            document.querySelectorAll('.step')
                                    .forEach(s => s.classList.add('hidden'));
                            document.getElementById('step-6').classList.remove('hidden');
                            updateProgressBar();      // si ya añadiste la barra de progreso

                            return;                   // no continúes con flujo normal
                        }
                        // ──────────────────────────────── 
                    },
                    error   : function (xhr, textStatus) {
                        // ========= DEBUG: error completo ========= 
                        console.error('❌ Error en la petición',
                                    {status: xhr.status, statusText: xhr.statusText, body: xhr.responseText});
                        alert('Error guardando el paso.');
                    }
                });
            }

            // Un listener por cada botón definido arriba 
            Object.values(STEP_CONFIG).forEach(cfg => {
                $(document).on('click', cfg.btn, function (e) {
                    e.preventDefault();              // no recarga la página
                    enviarPaso(this, cfg);           // this = botón clicado
                });
            });

        });
    </script>
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Escuchar el evento global disparado desde React
            window.addEventListener('openSolicitudTransporteModal', function(e) {
                // e.detail contiene el id de la CotizacionModel (route/quote)
                let id = e.detail;
                // Puedes almacenar el id, o triggers más cosas si lo necesitas
                // Llama a un método Livewire que esté escuchando (drog-zone)
                Livewire.dispatch('abrirModalSolicitudTransporte', { id: id });
            });
        });


        function fillFormWithSolicitudData() {
            if(!window.solicitudData || !window.solicitudData.solicitud) return;

            // Paso 1 (datos principales, tabla principal)
            const s = window.solicitudData.solicitud;
            // $('#tipo_viaje').val(s.tipo_viaje || "");
            // $('#moneda').val(s.moneda || "");
            $('#cliente_codigo').val(s.cliente_codigo || "");
            $('#tipo_viaje').val(s.tipo_viaje || '').trigger('change');
            $('#moneda').val(s.moneda || '').trigger('change');
            // $('#fuente_solicitud').val(s.fuente_solicitud || "");
            $('#fuente_solicitud').val(s.fuente_solicitud || '').trigger('change');
            $('#observacion').val(s.observacion || "");
            $('#condicion_despacho').val(s.condicion_despacho || "");
            $('#condicion_facturacion').val(s.condicion_facturacion || "");
            $('#observacion_remesa').val(s.observacion_remesa || "");
            $('#tipo_imagen').val(s.tipo_imagen || "");
            $('#recomendacion_trafico').val(s.recomendacion_trafico || "");
            $('#ciudad_facturacion').val(s.ciudad_facturacion || "");
            $('#vendedor').val(s.vendedor || "");
            $('#cliente_final').val(s.cliente_final || "");
            $('#tipo_operacion').val(s.tipo_operacion || "");
            $('#fecha_solicitud').val(s.fecha_solicitud || "");
            $('#instrucciones_servicio').val(s.instrucciones_servicio || "");
            $('#maersk_numero_viaje').val(s.maersk_numero_viaje || "");
            $('#solicitud_servicio').val(s.solicitud_servicio || "");
            $('#mostrar_digitalizados_vehiculos').val(s.mostrar_digitalizados_vehiculos || "");
            $('#mostrar_digitalizados_conductor').val(s.mostrar_digitalizados_conductor || "");
            $('#usuario_autorizado').val(s.usuario_autorizado || "");
            $('#empresa').val(s.empresa || "");
            $('#usuario').val(s.usuario || "");

            // Paso 2: Detalle
            if(window.solicitudData.detalle) {
                const d = window.solicitudData.detalle;
                $('#origen').val(d.origen || "");
                $('#destino').val(d.destino || "");
                $('#ciudad_intermedia').val(d.ciudad_intermedia || "");
                $('#cantidad_mercancia').val(d.cantidad_mercancia || "");
                $('#peso').val(d.peso || "");
                $('#peso_despachado').val(d.peso_despachado || "");
                $('#producto').val(d.producto || "");
                $('#empaque').val(d.empaque || "");
                $('#cantidad_vehiculos').val(d.cantidad_vehiculos || "");
                $('#cantidad_vehiculos_despachados').val(d.cantidad_vehiculos_despachados || "");
                $('#clase_vehiculo').val(d.clase_vehiculo || "");
                $('#carroceria').val(d.carroceria || "");
                $('#minimo_modelo').val(d.minimo_modelo || "");
                $('#tipo_flete').val(d.tipo_flete || "");
                $('#flete_conductor').val(d.flete_conductor || "");
                $('#flete_ministerio').val(d.flete_ministerio || "");
                $('#tipo_tarifa').val(d.tipo_tarifa || "");
                $('#tarifa_cliente').val(d.tarifa_cliente || "");
                $('#tipo_documento').val(d.tipo_documento || "");
                $('#numero_documento').val(d.numero_documento || "");
                $('#valor_mercancia').val(d.valor_mercancia || "");
                $('#restricciones_cliente').val(d.restricciones_cliente || "");
                $('#cargue_cuenta_de').val(d.cargue_cuenta_de || "");
                $('#descargue_cuenta_de').val(d.descargue_cuenta_de || "");
                $('#seguro_cuenta_de').val(d.seguro_cuenta_de || "");
                $('#descripcion_mercancia').val(d.descripcion_mercancia || "");
                $('#servicio_escolta').val(d.servicio_escolta || "");
                $('#kit_seguridad').val(d.kit_seguridad || "");
                $('#kit_cinchas').val(d.kit_cinchas || "");
                $('#guia_acompanamiento').val(d.guia_acompanamiento || "");
                $('#observacion_detalle').val(d.observacion_detalle || "");
                $('#sub_cliente').val(d.sub_cliente || "");
                $('#guia_despacho').val(d.guia_despacho || "");
                $('#numero_viaje').val(d.numero_viaje || "");
                $('#numero_pedido').val(d.numero_pedido || "");
                $('#nota_entrega').val(d.nota_entrega || "");
                $('#planilla_entrega').val(d.planilla_entrega || "");
                $('#numero_factura').val(d.numero_factura || "");
                $('#modelo').val(d.modelo || "");
                $('#qty').val(d.qty || "");
                $('#t_cbm').val(d.t_cbm || "");
                $('#order_no').val(d.order_no || "");
                $('#o_c_cliente').val(d.o_c_cliente || "");
                $('#bodega').val(d.bodega || "");
                $('#fecha_expiracion').val(d.fecha_expiracion || "");
                $('#net_amt_txn').val(d.net_amt_txn || "");
                $('#prec_unit').val(d.prec_unit || "");
                $('#remark').val(d.remark || "");
                $('#addr').val(d.addr || "");
                $('#appoint_date').val(d.appoint_date || "");
                $('#tipo_vehiculo').val(d.tipo_vehiculo || "");
                $('#item').val(d.item || "");
                $('#load').val(d.load || "");
            }
            // Paso 3: Cargue
            if(window.solicitudData.cargue) {
                const c = window.solicitudData.cargue;
                $('#fecha_cargue').val(c.fecha_cargue || "");
                $('#hora_cargue').val(c.hora_cargue || "");
                $('#remitente').val(c.remitente || "");
                $('#direccion_cargue').val(c.direccion_cargue || "");
                $('#observacion_cargue').val(c.observacion_cargue || "");
                $('#contacto').val(c.contacto || "");
                $('#destinario').val(c.destinario || "");
                        $('#promesa_servicio').val(c.promesa_servicio || "");
                $('#promesa_servicio_hora').val(c.promesa_servicio_hora || "");
                $('#documento_transporte').val(c.documento_transporte || "");
                $('#manifiesto_cliente').val(c.manifiesto_cliente || "");
                $('#remesa_cliente').val(c.remesa_cliente || "");
                $('#remision_cliente').val(c.remision_cliente || "");
                $('#codigo_entrega').val(c.codigo_entrega || "");
                $('#email').val(c.email || "");
            }
            // Paso 4: Contenedor
            if(window.solicitudData.contenedor) {
                const cont = window.solicitudData.contenedor;
                $('#tipo_carga_cont').val(cont.tipo_carga_cont || "");
                $('#tamano_contenedor').val(cont.tamano_contenedor || "");
                $('#sitio_entrega_cont').val(cont.sitio_entrega_cont || "");
                $('#cantidad_cont').val(cont.cantidad_cont || "");
                $('#peso_contenedor').val(cont.peso_contenedor || "");
                $('#tipo_contenedor').val(cont.tipo_contenedor || "");
                $('#fecha_entrega_cont').val(cont.fecha_entrega_cont || "");
                $('#contenedor').val(cont.contenedor || "");
                $('#numero_cont').val(cont.numero_cont || "");
            }
            // Paso 5: Internacional
            if(window.solicitudData.internacional) {
                const i = window.solicitudData.internacional;
                $('#modalidad_internacional').val(i.modalidad_internacional || "");
                $('#tipo_viaje_int').val(i.tipo_viaje_int || "");
                $('#numero_documento_int').val(i.numero_documento_int || "");
                $('#fecha_llegada_int').val(i.fecha_llegada_int || "");
                $('#aduana_int').val(i.aduana_int || "");
                $('#nombre_cliente').val(i.nombre_cliente || "");
                $('#nombre_exportador').val(i.nombre_exportador || "");
                $('#datos_agente_aduana').val(i.datos_agente_aduana || "");
                $('#datos_bodega_ingresa').val(i.datos_bodega_ingresa || "");
                $('#ciudad_int').val(i.ciudad_int || "");
                $('#tipo_operacion_int').val(i.tipo_operacion_int || "");
                $('#quien_paga_almacenamiento').val(i.quien_paga_almacenamiento || "");
                $('#ciudad_otra').val(i.ciudad_otra || "");
                $('#tipo_otro').val(i.tipo_otro || "");
                $('#nombre_importador').val(i.nombre_importador || "");
                $('#descripcion_mercancia_int').val(i.descripcion_mercancia || "");
                $('#cantidad_peso_mercancia').val(i.cantidad_peso_mercancia || "");
                $('#fecha_vencimiento_modalidad').val(i.fecha_vencimiento_modalidad || "");
                $('#paso_frontera').val(i.paso_frontera || "");
            }
            // Paso 6: Equipos
            if(window.solicitudData.equipos) {
                const eq = window.solicitudData.equipos;
                $('#articulo_1').val(eq.articulo_1 || "");
                $('#cantidad_1').val(eq.cantidad_1 || "");
                $('#articulo_2').val(eq.articulo_2 || "");
                $('#cantidad_2').val(eq.cantidad_2 || "");
                $('#articulo_3').val(eq.articulo_3 || "");
                $('#cantidad_3').val(eq.cantidad_3 || "");
                $('#articulo_4').val(eq.articulo_4 || "");
                $('#cantidad_4').val(eq.cantidad_4 || "");
            }
            // Paso 7: Condiciones Factura
            if(window.solicitudData.condiciones) {
                const cf = window.solicitudData.condiciones;
                $('#condicion_factura_condition').val(cf.condicion_factura || "");
                $('#factura_remesa_hija').val(cf.factura_remesa_hija || "");
                $('#opcion_factura_remesa').val(cf.opcion_factura_remesa || "");
                $('#opcion_condicion_cumplida').val(cf.opcion_condicion_cumplida || "");
            }
            // Paso 8: Acompañamiento
            if(window.solicitudData.acompanamiento) {
                const ac = window.solicitudData.acompanamiento;
                $('#vehiculo_acom').val(ac.vehiculo_acom || "");
                $('#tipo_vehiculo_acom').val(ac.tipo_vehiculo_acom || "");
                $('#acompanamiento_cuenta_acom').val(ac.acompanamiento_cuenta_acom || "");
                $('#valor_acompanante_acom').val(ac.valor_acompanante_acom || "");
            }
            // Paso 9: Entrega
            if(window.solicitudData.entrega) {
                const e = window.solicitudData.entrega;
                $('#nombre_cliente_ent').val(e.nombre_cliente_ent || "");
                $('#direccion_entrega_ent').val(e.direccion_entrega_ent || "");
                $('#numero_documento_ent').val(e.numero_documento_ent || "");
                $('#cantidad_ent').val(e.cantidad_ent || "");
                $('#empaque_ent').val(e.empaque_ent || "");
                $('#f_12_ent').val(e.f_12_ent || "");
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            // Para la primera carga por si ya está renderizado de entrada
            fillFormWithSolicitudData();
        });

        document.addEventListener("livewire:load", function() {
            Livewire.hook('message.processed', (message, component) => {
                // Chequea si tu modal está abierto (seguro por el id de tu modal)
                if ($('#openModalSolicitud').is(':visible')) {
                    fillFormWithSolicitudData();
                }
            });
        });
    </script>
    <script>
        window.addEventListener('solicitud-data-ready', function(e) {
            let data = e.detail;
            // Livewire 3 puede mandarlo como array o como objeto plano.
            if (Array.isArray(data)) {
                window.solicitudData = data[0]?.solicitud_data;
            } else {
                window.solicitudData = data.solicitud_data;
            }
            setTimeout(fillFormWithSolicitudData, 100);
        });

        // Prevenir que las actualizaciones de Livewire interfieran con el wizard
        document.addEventListener('livewire:updated', function(e) {
            // No longer needed since we're using pure Livewire
            console.log('Livewire updated - using pure Livewire navigation now');
        });

        // Escuchar cambios de paso desde Livewire
        window.addEventListener('step-changed', function(e) {
            const livewireStep = e.detail.step;
            console.log('Step changed via Livewire to:', livewireStep);
            // All navigation is now handled by Livewire - no need for manual JS updates
        });
    </script>

    <!-- Estilos CSS adicionales para el diseño mejorado -->
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out;
        }
        
        /* Estilos para los componentes x-input y x-select */
        .transition-all {
            transition-property: all;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 300ms;
        }
        
        /* Efecto hover para las tarjetas de sección */
        .bg-gradient-to-r:hover {
            transform: translateY(-1px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        /* Scroll personalizado para el chat */
        #chatbox::-webkit-scrollbar {
            width: 6px;
        }
        
        #chatbox::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        #chatbox::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        #chatbox::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Estilo adicional para el área de chat */
        #chatbox {
            scrollbar-width: thin;
            scrollbar-color: #c1c1c1 #f1f1f1;
        }

        /* Auto scroll al final cuando se agregan mensajes */
        .chat-scroll-behavior {
            scroll-behavior: smooth;
        }

        /* Estilos mejorados para el modal centrado */
        .modal-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        /* Asegurar que el chat mantenga su scroll interno */
        #chatbox {
            height: 0; /* Permite que flex-1 calcule la altura correcta */
            min-height: 200px;
        }

        /* Estilos para mensajes del chat */
        .message-bubble {
            max-width: 80%;
            word-wrap: break-word;
            border-radius: 18px;
            padding: 12px 16px;
            margin: 8px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .userMessage {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }
        
        .botMessage {
            background: white;
            color: #374151;
            border: 1px solid #e5e7eb;
            border-bottom-left-radius: 4px;
        }

        /* Animaciones suaves para botones */
        .transform {
            transition: transform 0.2s ease-in-out;
        }
        
        /* Efecto de pulso para indicadores */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
        
        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Mejoras de accesibilidad y focus */
        input:focus, select:focus, textarea:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        /* Gradientes personalizados para las secciones */
        .section-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(248,250,252,0.9) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        /* Mejoras en los inputs */
        input[type="text"], input[type="number"], input[type="date"], input[type="time"], select, textarea {
            border-radius: 8px;
            border: 1.5px solid #e5e7eb;
            padding: 12px 16px;
            font-size: 14px;
            background-color: #fafafa;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus, input[type="number"]:focus, input[type="date"]:focus, input[type="time"]:focus, select:focus, textarea:focus {
            background-color: white;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        /* Mejorar etiquetas de los inputs */
        label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            display: block;
        }
    </style>

    <script>
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            // Handle cliente dropdown
            const clienteDropdown = document.querySelector('.cliente-dropdown');
            const clienteSearchInput = document.querySelector('input[wire\\:model\\.live="cliente_search"]');
            
            if (clienteDropdown && clienteSearchInput) {
                if (!clienteDropdown.contains(event.target) && !clienteSearchInput.contains(event.target)) {
                    @this.set('show_cliente_dropdown', false);
                }
            }
            
            // Handle ciudad dropdown
            const ciudadDropdown = document.querySelector('.ciudad-dropdown');
            const ciudadSearchInput = document.querySelector('input[wire\\:model\\.live="ciudad_search"]');
            
            if (ciudadDropdown && ciudadSearchInput) {
                if (!ciudadDropdown.contains(event.target) && !ciudadSearchInput.contains(event.target)) {
                    @this.set('show_ciudad_dropdown', false);
                }
            }
            
            // Handle vendedor dropdown
            const vendedorDropdown = document.querySelector('.vendedor-dropdown');
            const vendedorSearchInput = document.querySelector('input[wire\\:model\\.live="vendedor_search"]');
            
            if (vendedorDropdown && vendedorSearchInput) {
                if (!vendedorDropdown.contains(event.target) && !vendedorSearchInput.contains(event.target)) {
                    @this.set('show_vendedor_dropdown', false);
                }
            }

            // Handle producto dropdown
            const productoDropdown = document.querySelector('.producto-dropdown');
            const productoSearchInput = document.querySelector('input[wire\\:model\\.live="producto_search"]');
            
            if (productoDropdown && productoSearchInput) {
                if (!productoDropdown.contains(event.target) && !productoSearchInput.contains(event.target)) {
                    @this.set('show_producto_dropdown', false);
                }
            }
            
            // Removed origen and destino dropdown handling since they're now simple inputs
        });

        // Enhanced keyboard navigation
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                @this.set('show_cliente_dropdown', false);
                @this.set('show_ciudad_dropdown', false);
                @this.set('show_vendedor_dropdown', false);
                @this.set('show_producto_dropdown', false);
            }
        });
    </script>
</div>
