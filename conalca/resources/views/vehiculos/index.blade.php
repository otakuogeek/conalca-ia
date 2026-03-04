@extends('layout.app')

@section('title', 'Vehículos')

@section('content')
<div class="container mx-auto px-4 py-6" style="background-color: #f3f4f6 !important; color: #1f2937 !important;">
    {{-- Header --}}
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 inline-block mr-2 text-[#FF7C32]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 17h8M8 17a2 2 0 11-4 0 2 2 0 014 0zm8 0a2 2 0 104 0 2 2 0 00-4 0zm-8 0H5a2 2 0 01-2-2V9a2 2 0 012-2h2.5M17 17h2a2 2 0 002-2v-5a2 2 0 00-2-2h-1.5l-2-3H10a2 2 0 00-2 2v5" />
                    </svg>
                    Gestión de Vehículos
                </h1>
                <p class="text-gray-600 mt-1">
                    Tipos de vehículos registrados en Arcangel y sus relaciones con Silogtran
                </p>
            </div>
            <div class="flex items-center gap-3">
                {{-- Switch de Modo Arcángel --}}
                <div class="flex items-center gap-2 bg-gray-100 px-4 py-2 rounded-lg">
                    <span class="text-sm font-medium text-gray-700">
                        Modo:
                    </span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="switchModoArcangel" class="sr-only peer" onchange="cambiarModoArcangel()">
                        <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-green-600"></div>
                    </label>
                    <span id="labelModoArcangel" class="text-sm font-semibold text-gray-800">
                        <span class="inline-flex items-center">
                            <svg class="animate-spin w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Cargando...
                        </span>
                    </span>
                </div>
                
                <button id="btnSincronizar" 
                        onclick="sincronizarVehiculos()"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 transition-colors">
                    <svg id="iconSync" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span id="textSync">Sincronizar</span>
                </button>
                <span class="bg-[#FF7C32] text-white px-4 py-2 rounded-full text-sm font-semibold">
                    {{ count($tiposVehiculos ?? []) }} tipos registrados
                </span>
            </div>
        </div>
    </div>

    {{-- Pestañas --}}
    <div class="bg-white rounded-lg shadow-md mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button onclick="cambiarPestana('tipos')" 
                        id="tab-tipos"
                        class="tab-btn px-6 py-4 text-sm font-medium border-b-2 border-[#FF7C32] text-[#FF7C32] focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Tipos y Relaciones
                </button>
                <button onclick="cambiarPestana('buscar')" 
                        id="tab-buscar"
                        class="tab-btn px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Buscar por Ciudad
                </button>
            </nav>
        </div>
    </div>

    {{-- Panel de Progreso (oculto por defecto) --}}
    <div id="panelProgreso" class="hidden bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <svg class="animate-spin w-5 h-5 mr-2 text-[#FF7C32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Sincronización en progreso
            </h3>
            <span id="tiempoRestante" class="text-sm bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-medium">
                Calculando tiempo...
            </span>
        </div>

        {{-- Barra de progreso --}}
        <div class="mb-4">
            <div class="flex justify-between text-sm text-gray-600 mb-1">
                <span id="ciudadActual">Preparando...</span>
                <span id="porcentajeTexto">0%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                <div id="barraProgreso" 
                     class="bg-gradient-to-r from-[#FF7C32] to-orange-500 h-4 rounded-full transition-all duration-300"
                     style="width: 0%">
                </div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 mt-1">
                <span><span id="ciudadesProcesadas">0</span> de <span id="ciudadesTotal">0</span> ciudades</span>
                <span id="erroresCount" class="text-red-500 hidden">0 errores</span>
            </div>
        </div>

        {{-- Estadísticas en tiempo real --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-gray-50 rounded-lg p-3 text-center">
                <div id="statCiudades" class="text-2xl font-bold text-[#FF7C32]">0</div>
                <div class="text-xs text-gray-500">Ciudades</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 text-center">
                <div id="statVehiculos" class="text-2xl font-bold text-green-600">0</div>
                <div class="text-xs text-gray-500">Vehículos</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 text-center">
                <div id="statTipos" class="text-2xl font-bold text-blue-600">0</div>
                <div class="text-xs text-gray-500">Tipos únicos</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 text-center">
                <div id="statErrores" class="text-2xl font-bold text-red-600">0</div>
                <div class="text-xs text-gray-500">Errores</div>
            </div>
        </div>

        {{-- Log de escaneo --}}
        <div class="mt-4">
            <details class="cursor-pointer">
                <summary class="text-sm text-gray-600 hover:text-gray-800">
                    Ver log de escaneo
                </summary>
                <div id="logEscaneo" class="mt-2 max-h-40 overflow-y-auto bg-gray-900 text-green-400 rounded-lg p-3 font-mono text-xs">
                    <div>Esperando inicio...</div>
                </div>
            </details>
        </div>
    </div>

    {{-- Alert de resultado --}}
    <div id="alertaResultado" class="hidden mb-6"></div>

    {{-- Error Alert --}}
    @if(isset($error) && $error)
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg" role="alert">
        <div class="flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="font-bold">Error</p>
        </div>
        <p class="mt-2">{{ $error }}</p>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- PESTAÑA 1: TIPOS Y RELACIONES --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div id="panel-tipos">
        {{-- Tabla de Tipos de Vehículos con Relaciones --}}
        @if(isset($tiposVehiculos) && count($tiposVehiculos) > 0)
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2 text-[#FF7C32]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                </svg>
                Tipos de Vehículos Arcangel y Relaciones Silogtran
            </h2>
            
            {{-- Search Filter --}}
            <div class="mb-4">
                <input type="text" 
                       id="searchVehiculos" 
                       placeholder="Buscar tipo de vehículo..." 
                       class="w-full md:w-1/3 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF7C32] focus:border-transparent">
        </div>

        {{-- Accordion de vehículos --}}
        <div class="space-y-3" id="listaVehiculos">
            @foreach($tiposVehiculos as $tipo)
            <div class="vehiculo-card border border-gray-200 rounded-lg overflow-hidden" data-id="{{ $tipo->id }}">
                {{-- Header del vehículo --}}
                <div class="flex items-center justify-between p-4 bg-gray-50 cursor-pointer hover:bg-gray-100 transition-colors"
                     onclick="toggleRelaciones({{ $tipo->id }})">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 bg-[#FBEBE2] rounded-full flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#FF7C32]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-bold text-gray-900 vehiculo-nombre">
                                {{ $tipo->nombre }}
                            </div>
                            <div class="text-xs text-gray-500">
                                <span class="relaciones-count" id="count-{{ $tipo->id }}">{{ count($tipo->relaciones) }}</span> vehículos relacionados
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="event.stopPropagation(); abrirModalAgregar({{ $tipo->id }}, '{{ $tipo->nombre }}')"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-semibold flex items-center gap-1 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Agregar Vehículo
                        </button>
                        <svg id="chevron-{{ $tipo->id }}" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400 transform transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
                
                {{-- Panel de relaciones (oculto por defecto) --}}
                <div id="relaciones-{{ $tipo->id }}" class="hidden border-t border-gray-200">
                    <div class="p-4 bg-white">
                        @if(count($tipo->relaciones) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Vehículo Silogtran</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tabla Pricing</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Peso Máximo</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-{{ $tipo->id }}" class="divide-y divide-gray-200">
                                    @foreach($tipo->relaciones as $rel)
                                    <tr id="rel-{{ $rel->relacion_id }}" class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ $rel->vehiculo_silogtran }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $rel->tabla_pricing }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($rel->peso_maximo) }} kg</td>
                                        <td class="px-4 py-3 text-center">
                                            <button onclick="eliminarRelacion({{ $rel->relacion_id }}, {{ $tipo->id }})"
                                                    class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs font-semibold transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div id="empty-{{ $tipo->id }}" class="text-center py-6 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            No hay vehículos relacionados. Haz clic en "Agregar Vehículo" para crear una relación.
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Contador de resultados --}}
        <div class="mt-4 text-sm text-gray-500">
            Mostrando <span id="contadorResultados">{{ count($tiposVehiculos) }}</span> de {{ count($tiposVehiculos) }} tipos de vehículos
        </div>
    </div>
    @else
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
        <div class="flex items-center">
            <svg class="w-6 h-6 text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <p class="text-yellow-700 font-medium">No hay tipos de vehículos registrados. Haz clic en "Sincronizar" para obtenerlos de Arcangel.</p>
        </div>
    </div>
    @endif
    </div>
    {{-- Fin de panel-tipos --}}

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- PESTAÑA 2: BUSCAR POR CIUDAD --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div id="panel-buscar" class="hidden">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2 text-[#FF7C32]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Buscar Vehículos Disponibles por Ciudad
            </h2>
            <p class="text-gray-600 text-sm mb-6">
                Consulta en tiempo real los vehículos disponibles en cualquier ciudad de Colombia a través de Arcangel. Los resultados no se almacenan.
            </p>

            {{-- Selector de ciudad --}}
            <div class="grid md:grid-cols-3 gap-4 mb-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ciudad</label>
                    <div class="relative">
                        <select id="selectCiudad" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF7C32] focus:border-transparent appearance-none">
                            <option value="">-- Seleccione una ciudad --</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                    <p id="ciudadesInfo" class="text-xs text-gray-500 mt-1">Cargando ciudades...</p>
                </div>
                <div class="flex items-end">
                    <button onclick="buscarVehiculosCiudad()" 
                            id="btnBuscarCiudad"
                            class="w-full bg-[#FF7C32] hover:bg-orange-600 text-white px-6 py-3 rounded-lg font-semibold flex items-center justify-center gap-2 transition-colors">
                        <svg id="iconBuscar" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span id="textBuscar">Buscar</span>
                    </button>
                </div>
            </div>

            {{-- Resultados de búsqueda --}}
            <div id="resultadosBusqueda" class="hidden">
                {{-- Header de resultados --}}
                <div id="headerResultados" class="bg-gradient-to-r from-[#FF7C32] to-orange-500 rounded-t-lg p-4 text-white">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div>
                            <h3 class="font-bold text-lg flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                                <span id="ciudadResultado">Ciudad</span>
                            </h3>
                            <p class="text-sm opacity-90">Última actualización: <span id="timestampResultado">-</span></p>
                        </div>
                        <div class="flex gap-4 text-center">
                            <div class="bg-white/20 rounded-lg px-4 py-2">
                                <div id="totalVehiculosResultado" class="text-2xl font-bold">0</div>
                                <div class="text-xs">Vehículos</div>
                            </div>
                            <div class="bg-white/20 rounded-lg px-4 py-2">
                                <div id="totalTiposResultado" class="text-2xl font-bold">0</div>
                                <div class="text-xs">Tipos</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Lista de vehículos por tipo --}}
                <div id="listaVehiculosCiudad" class="border border-t-0 border-gray-200 rounded-b-lg">
                    {{-- Se llenará dinámicamente --}}
                </div>
            </div>

            {{-- Estado vacío --}}
            <div id="estadoVacioBusqueda" class="text-center py-12">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
                <h3 class="text-lg font-medium text-gray-600">Selecciona una ciudad</h3>
                <p class="text-sm text-gray-500 mt-1">
                    Elige una ciudad del listado para ver los vehículos disponibles en tiempo real
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Modal Agregar Vehículo --}}
<div id="modalAgregar" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-hidden">
        {{-- Header del modal --}}
        <div class="bg-[#FF7C32] px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-white flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Agregar Vehículo Relacionado
                </h3>
                <button onclick="cerrarModal()" class="text-white hover:text-gray-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <p class="text-white text-sm opacity-90 mt-1">
                Vehículo Arcangel: <strong id="modalVehiculoNombre"></strong>
            </p>
        </div>
        
        {{-- Cuerpo del modal --}}
        <div class="p-6 max-h-[60vh] overflow-y-auto">
            <input type="hidden" id="modalVehiculoId">
            
            {{-- Buscador --}}
            <div class="mb-4">
                <input type="text" 
                       id="searchPricing" 
                       placeholder="Buscar vehículo Silogtran..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF7C32] focus:border-transparent">
            </div>
            
            {{-- Lista de vehículos disponibles --}}
            <div class="space-y-2" id="listaPricing">
                @if(isset($vehiculosPricing))
                @foreach($vehiculosPricing as $vp)
                <div class="pricing-item flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                     data-id="{{ $vp->id }}"
                     data-nombre="{{ strtolower($vp->vehiculo_silogtran) }} {{ strtolower($vp->tabla_pricing) }}">
                    <div>
                        <div class="font-medium text-gray-900">{{ $vp->vehiculo_silogtran }}</div>
                        <div class="text-sm text-gray-500">
                            {{ $vp->tabla_pricing }} - {{ number_format($vp->peso_maximo) }} kg
                        </div>
                    </div>
                    <button onclick="agregarRelacion({{ $vp->id }})"
                            class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm font-semibold transition-colors">
                        Agregar
                    </button>
                </div>
                @endforeach
                @endif
            </div>
        </div>
        
        {{-- Footer del modal --}}
        <div class="bg-gray-50 px-6 py-3 flex justify-end">
            <button onclick="cerrarModal()" 
                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                Cerrar
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let eventSource = null;

    // Filtro de búsqueda de vehículos
    document.getElementById('searchVehiculos')?.addEventListener('input', function(e) {
        const busqueda = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('.vehiculo-card');
        let contador = 0;

        cards.forEach(card => {
            const nombre = card.querySelector('.vehiculo-nombre').textContent.toLowerCase();
            if (nombre.includes(busqueda)) {
                card.style.display = '';
                contador++;
            } else {
                card.style.display = 'none';
            }
        });

        document.getElementById('contadorResultados').textContent = contador;
    });

    // Filtro de búsqueda en modal
    document.getElementById('searchPricing')?.addEventListener('input', function(e) {
        const busqueda = e.target.value.toLowerCase();
        const items = document.querySelectorAll('.pricing-item');

        items.forEach(item => {
            const nombre = item.dataset.nombre;
            if (nombre.includes(busqueda)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });

    // Toggle panel de relaciones
    function toggleRelaciones(id) {
        const panel = document.getElementById('relaciones-' + id);
        const chevron = document.getElementById('chevron-' + id);
        
        if (panel.classList.contains('hidden')) {
            panel.classList.remove('hidden');
            chevron.classList.add('rotate-180');
        } else {
            panel.classList.add('hidden');
            chevron.classList.remove('rotate-180');
        }
    }

    // Abrir modal para agregar vehículo
    function abrirModalAgregar(vehiculoId, vehiculoNombre) {
        document.getElementById('modalVehiculoId').value = vehiculoId;
        document.getElementById('modalVehiculoNombre').textContent = vehiculoNombre;
        document.getElementById('searchPricing').value = '';
        
        // Mostrar todos los items
        document.querySelectorAll('.pricing-item').forEach(item => {
            item.style.display = '';
        });
        
        document.getElementById('modalAgregar').classList.remove('hidden');
    }

    // Cerrar modal
    function cerrarModal() {
        document.getElementById('modalAgregar').classList.add('hidden');
    }

    // Agregar relación
    async function agregarRelacion(vehiculoPricingId) {
        const vehiculoArcangelId = document.getElementById('modalVehiculoId').value;
        
        try {
            const response = await fetch('/vehiculos/relaciones', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    vehiculo_arcangel_id: vehiculoArcangelId,
                    vehiculo_pricing_id: vehiculoPricingId
                })
            });

            const data = await response.json();

            if (data.success) {
                // Agregar fila a la tabla
                const tbody = document.getElementById('tbody-' + vehiculoArcangelId);
                const empty = document.getElementById('empty-' + vehiculoArcangelId);
                
                // Si había mensaje de vacío, crear la tabla
                if (empty) {
                    const panelRelaciones = document.getElementById('relaciones-' + vehiculoArcangelId);
                    panelRelaciones.querySelector('.p-4').innerHTML = `
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Vehículo Silogtran</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tabla Pricing</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Peso Máximo</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-${vehiculoArcangelId}" class="divide-y divide-gray-200">
                                </tbody>
                            </table>
                        </div>
                    `;
                }
                
                // Obtener el tbody actualizado
                const tbodyActual = document.getElementById('tbody-' + vehiculoArcangelId);
                
                const newRow = document.createElement('tr');
                newRow.id = 'rel-' + data.relacion.id;
                newRow.className = 'hover:bg-gray-50';
                newRow.innerHTML = `
                    <td class="px-4 py-3 text-sm text-gray-900 font-medium">${data.relacion.vehiculo_silogtran}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">${data.relacion.tabla_pricing}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">${new Intl.NumberFormat().format(data.relacion.peso_maximo)} kg</td>
                    <td class="px-4 py-3 text-center">
                        <button onclick="eliminarRelacion(${data.relacion.id}, ${vehiculoArcangelId})"
                                class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs font-semibold transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Eliminar
                        </button>
                    </td>
                `;
                tbodyActual.appendChild(newRow);

                // Actualizar contador
                const countSpan = document.getElementById('count-' + vehiculoArcangelId);
                countSpan.textContent = parseInt(countSpan.textContent) + 1;

                // Mostrar panel si estaba oculto
                const panel = document.getElementById('relaciones-' + vehiculoArcangelId);
                if (panel.classList.contains('hidden')) {
                    toggleRelaciones(vehiculoArcangelId);
                }

                // Notificación de éxito
                mostrarNotificacion('success', data.message);
            } else {
                mostrarNotificacion('error', data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarNotificacion('error', 'Error al agregar la relación');
        }
    }

    // Eliminar relación
    async function eliminarRelacion(relacionId, vehiculoArcangelId) {
        if (!confirm('¿Estás seguro de eliminar esta relación?')) return;
        
        try {
            const response = await fetch('/vehiculos/relaciones/' + relacionId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (data.success) {
                // Eliminar fila de la tabla
                document.getElementById('rel-' + relacionId).remove();

                // Actualizar contador
                const countSpan = document.getElementById('count-' + vehiculoArcangelId);
                const newCount = parseInt(countSpan.textContent) - 1;
                countSpan.textContent = newCount;

                // Si no quedan relaciones, mostrar mensaje vacío
                if (newCount === 0) {
                    const panelRelaciones = document.getElementById('relaciones-' + vehiculoArcangelId);
                    panelRelaciones.querySelector('.p-4').innerHTML = `
                        <div id="empty-${vehiculoArcangelId}" class="text-center py-6 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            No hay vehículos relacionados. Haz clic en "Agregar Vehículo" para crear una relación.
                        </div>
                    `;
                }

                mostrarNotificacion('success', data.message);
            } else {
                mostrarNotificacion('error', data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarNotificacion('error', 'Error al eliminar la relación');
        }
    }

    // Mostrar notificación
    function mostrarNotificacion(tipo, mensaje) {
        const notif = document.createElement('div');
        notif.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white font-medium transform transition-all duration-300 ${tipo === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
        notif.textContent = mensaje;
        document.body.appendChild(notif);

        setTimeout(() => {
            notif.remove();
        }, 3000);
    }

    // Función para sincronizar vehículos con Server-Sent Events
    function sincronizarVehiculos() {
        const btn = document.getElementById('btnSincronizar');
        const icon = document.getElementById('iconSync');
        const text = document.getElementById('textSync');
        const panelProgreso = document.getElementById('panelProgreso');
        const logEscaneo = document.getElementById('logEscaneo');
        
        btn.disabled = true;
        btn.classList.remove('bg-green-600', 'hover:bg-green-700');
        btn.classList.add('bg-gray-400', 'cursor-not-allowed');
        icon.classList.add('animate-spin');
        text.textContent = 'Sincronizando...';
        
        panelProgreso.classList.remove('hidden');
        logEscaneo.innerHTML = '<div class="text-yellow-400">▶ Iniciando conexión con Arcangel...</div>';
        
        document.getElementById('alertaResultado').classList.add('hidden');
        
        eventSource = new EventSource('/vehiculos/sincronizar');
        
        eventSource.addEventListener('init', function(e) {
            const data = JSON.parse(e.data);
            addLog('info', data.message);
            document.getElementById('ciudadActual').textContent = data.step;
            if (data.tiposExistentes) {
                addLog('success', `Ya tienes ${data.tiposExistentes} tipos registrados en la base de datos`);
            }
        });
        
        eventSource.addEventListener('ciudades', function(e) {
            const data = JSON.parse(e.data);
            document.getElementById('ciudadesTotal').textContent = data.total;
            document.getElementById('tiempoRestante').textContent = '⏱ Estimado: ' + data.estimatedTime;
            addLog('success', data.message);
        });
        
        eventSource.addEventListener('nuevo_tipo', function(e) {
            const data = JSON.parse(e.data);
            addLog('success', `✨ NUEVO TIPO: "${data.tipo}" encontrado en ${data.ciudad} (Total nuevos: ${data.total_nuevos})`);
        });
        
        eventSource.addEventListener('progress', function(e) {
            const data = JSON.parse(e.data);
            
            document.getElementById('barraProgreso').style.width = data.percent + '%';
            document.getElementById('porcentajeTexto').textContent = data.percent + '%';
            document.getElementById('ciudadesProcesadas').textContent = data.current;
            document.getElementById('ciudadActual').textContent = 'Escaneando: ' + data.ciudad;
            
            document.getElementById('statCiudades').textContent = data.current;
            document.getElementById('statVehiculos').textContent = data.vehiculosTotal;
            // Mostrar tipos nuevos + existentes
            document.getElementById('statTipos').textContent = (data.tiposExistentes || 0) + '+' + (data.tiposNuevos || 0);
            document.getElementById('statErrores').textContent = data.errores;
            
            document.getElementById('tiempoRestante').textContent = '⏱ Restante: ' + data.tiempoRestante;
            
            if (data.errores > 0) {
                document.getElementById('erroresCount').classList.remove('hidden');
                document.getElementById('erroresCount').textContent = data.errores + ' errores';
            }
            
            // Log cada 20 ciudades o si hay tipos omitidos significativos
            if (data.current % 20 === 0 || data.current === 1) {
                let logMsg = `[${data.percent}%] ${data.ciudad}: ${data.vehiculosEnCiudad} vehículos`;
                if (data.omitidos > 0) {
                    logMsg += ` (${data.omitidos} tipos ya existentes omitidos)`;
                }
                addLog('info', logMsg);
            }
        });
        
        eventSource.addEventListener('early_stop', function(e) {
            const data = JSON.parse(e.data);
            addLog('warning', `⚡ ${data.message}`);
            addLog('warning', `Ciudades procesadas: ${data.ciudadesProcesadas}, Tipos nuevos encontrados: ${data.tiposNuevos}`);
        });
        
        eventSource.addEventListener('saving', function(e) {
            const data = JSON.parse(e.data);
            document.getElementById('ciudadActual').textContent = data.message;
            addLog('info', data.message);
        });
        
        eventSource.addEventListener('complete', function(e) {
            const data = JSON.parse(e.data);
            eventSource.close();
            
            document.getElementById('barraProgreso').style.width = '100%';
            document.getElementById('porcentajeTexto').textContent = '100%';
            document.getElementById('ciudadActual').textContent = '✓ Completado';
            document.getElementById('tiempoRestante').textContent = '✓ ' + data.tiempoTotal;
            
            addLog('success', '═══════════════════════════════════════');
            addLog('success', 'SINCRONIZACIÓN COMPLETADA');
            if (data.detencionTemprana) {
                addLog('warning', '⚡ Detenida temprano (sin tipos nuevos)');
            }
            addLog('success', `📊 Total tipos en BD: ${data.totalTiposEnDB}`);
            addLog('success', `✨ Nuevos agregados: ${data.tiposNuevos}`);
            addLog('success', `📁 Ya existentes: ${data.tiposExistentes}`);
            addLog('success', '═══════════════════════════════════════');
            
            mostrarResultado('success', data);
            restaurarBoton();
            
            if (data.tiposNuevos > 0) {
                setTimeout(() => window.location.reload(), 3000);
            }
        });
        
        eventSource.addEventListener('error', function(e) {
            if (e.data) {
                const data = JSON.parse(e.data);
                addLog('error', 'ERROR: ' + data.message);
                mostrarResultado('error', data);
            }
            eventSource.close();
            restaurarBoton();
        });
        
        eventSource.onerror = function(e) {
            if (eventSource.readyState === EventSource.CLOSED) return;
            addLog('error', 'Error de conexión');
            eventSource.close();
            restaurarBoton();
        };
    }
    
    function addLog(type, message) {
        const log = document.getElementById('logEscaneo');
        const colors = { info: 'text-green-400', success: 'text-cyan-400', error: 'text-red-400', warning: 'text-yellow-400' };
        const time = new Date().toLocaleTimeString();
        log.innerHTML += `<div class="${colors[type] || 'text-white'}">[${time}] ${message}</div>`;
        log.scrollTop = log.scrollHeight;
    }
    
    function restaurarBoton() {
        const btn = document.getElementById('btnSincronizar');
        btn.disabled = false;
        btn.classList.remove('bg-gray-400', 'cursor-not-allowed');
        btn.classList.add('bg-green-600', 'hover:bg-green-700');
        document.getElementById('iconSync').classList.remove('animate-spin');
        document.getElementById('textSync').textContent = 'Sincronizar';
    }
    
    function mostrarResultado(tipo, data) {
        const alerta = document.getElementById('alertaResultado');
        
        if (tipo === 'success') {
            alerta.innerHTML = `
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg">
                    <p class="font-bold">¡Sincronización completada! ${data.detencionTemprana ? '⚡ (Detención temprana)' : ''}</p>
                    <div class="mt-2 grid grid-cols-2 md:grid-cols-5 gap-2 text-sm">
                        <div><strong>Ciudades:</strong> ${data.ciudadesConsultadas}/${data.ciudadesTotal}</div>
                        <div><strong>Vehículos:</strong> ${data.vehiculosEncontrados}</div>
                        <div><strong>Total en BD:</strong> ${data.totalTiposEnDB}</div>
                        <div><strong>Nuevos:</strong> <span class="text-green-600 font-bold">${data.tiposNuevos}</span></div>
                        <div><strong>Omitidos:</strong> ${data.omitidos}</div>
                    </div>
                    ${data.tiposNuevos > 0 ? `
                        <div class="mt-2 text-sm">
                            <strong>Tipos agregados:</strong> ${data.tiposAgregados.join(', ')}
                        </div>
                        <p class="mt-2 text-sm italic">La página se recargará...</p>
                    ` : '<p class="mt-2 text-sm italic text-gray-600">No se encontraron tipos nuevos. Tu base de datos está actualizada.</p>'}
                </div>
            `;
        } else {
            alerta.innerHTML = `
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg">
                    <p class="font-bold">Error en sincronización</p>
                    <p class="mt-2">${data.message}</p>
                </div>
            `;
        }
        
        alerta.classList.remove('hidden');
    }

    // Cerrar modal con Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModal();
        }
    });

    // ═══════════════════════════════════════════════════════════════
    // SISTEMA DE PESTAÑAS
    // ═══════════════════════════════════════════════════════════════
    function cambiarPestana(pestana) {
        // Ocultar todos los paneles
        document.getElementById('panel-tipos').classList.add('hidden');
        document.getElementById('panel-buscar').classList.add('hidden');
        
        // Desactivar todas las pestañas
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('border-[#FF7C32]', 'text-[#FF7C32]');
            btn.classList.add('border-transparent', 'text-gray-500');
        });
        
        // Mostrar panel y activar pestaña seleccionada
        document.getElementById('panel-' + pestana).classList.remove('hidden');
        const tab = document.getElementById('tab-' + pestana);
        tab.classList.remove('border-transparent', 'text-gray-500');
        tab.classList.add('border-[#FF7C32]', 'text-[#FF7C32]');
        
        // Cargar ciudades si es la pestaña de búsqueda
        if (pestana === 'buscar' && !ciudadesCargadas) {
            cargarCiudades();
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // BÚSQUEDA POR CIUDAD
    // ═══════════════════════════════════════════════════════════════
    let ciudadesCargadas = false;

    async function cargarCiudades() {
        try {
            document.getElementById('ciudadesInfo').textContent = 'Cargando ciudades de Arcangel...';
            
            const response = await fetch('/vehiculos/ciudades');
            const data = await response.json();
            
            if (data.success) {
                const select = document.getElementById('selectCiudad');
                select.innerHTML = '<option value="">-- Seleccione una ciudad --</option>';
                
                data.ciudades.forEach(ciudad => {
                    const option = document.createElement('option');
                    option.value = ciudad;
                    option.textContent = ciudad;
                    select.appendChild(option);
                });
                
                document.getElementById('ciudadesInfo').textContent = `${data.total} ciudades disponibles`;
                ciudadesCargadas = true;
            } else {
                document.getElementById('ciudadesInfo').textContent = 'Error: ' + data.message;
            }
        } catch (error) {
            console.error('Error cargando ciudades:', error);
            document.getElementById('ciudadesInfo').textContent = 'Error al cargar ciudades';
        }
    }

    async function buscarVehiculosCiudad() {
        const ciudad = document.getElementById('selectCiudad').value;
        
        if (!ciudad) {
            mostrarNotificacion('error', 'Por favor selecciona una ciudad');
            return;
        }
        
        const btn = document.getElementById('btnBuscarCiudad');
        const icon = document.getElementById('iconBuscar');
        const text = document.getElementById('textBuscar');
        
        // Estado de carga
        btn.disabled = true;
        btn.classList.add('opacity-75');
        icon.classList.add('animate-spin');
        text.textContent = 'Buscando...';
        
        try {
            const response = await fetch('/vehiculos/buscar-ciudad', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ ciudad })
            });
            
            const data = await response.json();
            
            if (data.success) {
                mostrarResultadosCiudad(data);
            } else {
                mostrarNotificacion('error', data.message);
            }
        } catch (error) {
            console.error('Error buscando vehículos:', error);
            mostrarNotificacion('error', 'Error al buscar vehículos');
        } finally {
            // Restaurar botón
            btn.disabled = false;
            btn.classList.remove('opacity-75');
            icon.classList.remove('animate-spin');
            text.textContent = 'Buscar';
        }
    }

    function mostrarResultadosCiudad(data) {
        document.getElementById('estadoVacioBusqueda').classList.add('hidden');
        document.getElementById('resultadosBusqueda').classList.remove('hidden');
        
        // Actualizar header
        document.getElementById('ciudadResultado').textContent = data.ciudad;
        document.getElementById('timestampResultado').textContent = data.timestamp;
        document.getElementById('totalVehiculosResultado').textContent = data.totalVehiculos;
        document.getElementById('totalTiposResultado').textContent = data.tiposUnicos;
        
        // Generar lista de vehículos por tipo
        const lista = document.getElementById('listaVehiculosCiudad');
        
        if (data.vehiculosPorTipo.length === 0) {
            lista.innerHTML = `
                <div class="p-8 text-center text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p>No se encontraron vehículos disponibles en ${data.ciudad}</p>
                </div>
            `;
            return;
        }
        
        lista.innerHTML = data.vehiculosPorTipo.map((tipo, index) => `
            <div class="border-b border-gray-200 last:border-b-0">
                <div class="flex items-center justify-between p-4 bg-gray-50 cursor-pointer hover:bg-gray-100 transition-colors"
                     onclick="toggleVehiculosTipo(${index})">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 bg-[#FBEBE2] rounded-full flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#FF7C32]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-bold text-gray-900">${tipo.tipo}</div>
                            <div class="text-xs text-gray-500">
                                ${tipo.cantidad} vehículo${tipo.cantidad !== 1 ? 's' : ''} disponible${tipo.cantidad !== 1 ? 's' : ''}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">
                            ${tipo.cantidad}
                        </span>
                        <svg id="chevron-tipo-${index}" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400 transform transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
                <div id="vehiculos-tipo-${index}" class="hidden bg-white overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Placa</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Conductor</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Teléfono</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Empresa</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            ${tipo.vehiculos.map(v => `
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">${v.placa}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">${v.conductor}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <a href="tel:${v.telefono}" class="text-blue-600 hover:underline">${v.telefono}</a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">${v.empresa}</td>
                                    <td class="px-4 py-3">
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-medium">
                                            ${v.disponibilidad}
                                        </span>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `).join('');
    }

    function toggleVehiculosTipo(index) {
        const panel = document.getElementById('vehiculos-tipo-' + index);
        const chevron = document.getElementById('chevron-tipo-' + index);
        
        if (panel.classList.contains('hidden')) {
            panel.classList.remove('hidden');
            chevron.classList.add('rotate-180');
        } else {
            panel.classList.add('hidden');
            chevron.classList.remove('rotate-180');
        }
    }

    // ============================================================================
    // SWITCH DE MODO ARCÁNGEL (PRODUCCIÓN / DESARROLLO)
    // ============================================================================
    
    // Cargar modo actual al iniciar la página
    document.addEventListener('DOMContentLoaded', function() {
        cargarModoActual();
    });

    async function cargarModoActual() {
        try {
            const response = await fetch('{{ route("vehiculos.modo-arcangel-actual") }}');
            const data = await response.json();
            
            if (data.success) {
                const switchElement = document.getElementById('switchModoArcangel');
                const labelElement = document.getElementById('labelModoArcangel');
                
                // Actualizar switch (checked = development, unchecked = production)
                switchElement.checked = data.modo === 'development';
                
                // Actualizar label
                actualizarLabelModo(data.modo, data.url);
            }
        } catch (error) {
            console.error('Error cargando modo actual:', error);
            document.getElementById('labelModoArcangel').innerHTML = `
                <span class="text-red-600">Error</span>
            `;
        }
    }

    async function cambiarModoArcangel() {
        const switchElement = document.getElementById('switchModoArcangel');
        const labelElement = document.getElementById('labelModoArcangel');
        const nuevoModo = switchElement.checked ? 'development' : 'production';
        
        // Mostrar estado de carga
        labelElement.innerHTML = `
            <span class="inline-flex items-center">
                <svg class="animate-spin w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Cambiando...
            </span>
        `;
        
        try {
            const response = await fetch('{{ route("vehiculos.cambiar-modo-arcangel") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ modo: nuevoModo })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Actualizar label
                actualizarLabelModo(data.modo, data.url);
                
                // Mostrar notificación de éxito
                mostrarNotificacion('success', 
                    `✅ Modo cambiado a ${data.modo.toUpperCase()}\n📍 URL: ${data.url}\n🔄 Cachés limpiadas automáticamente`
                );
            } else {
                throw new Error(data.message || 'Error al cambiar modo');
            }
        } catch (error) {
            console.error('Error cambiando modo:', error);
            
            // Revertir el switch
            switchElement.checked = !switchElement.checked;
            
            // Restaurar label al modo anterior
            cargarModoActual();
            
            // Mostrar error
            mostrarNotificacion('error', `❌ Error: ${error.message || 'No se pudo cambiar el modo de Arcángel'}`);
        }
    }

    function actualizarLabelModo(modo, url) {
        const labelElement = document.getElementById('labelModoArcangel');
        const isDev = modo === 'development';
        
        labelElement.innerHTML = `
            <span class="inline-flex items-center gap-1">
                ${isDev ? 
                    '<svg class="w-4 h-4 text-yellow-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>' :
                    '<svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>'
                }
                <span class="${isDev ? 'text-yellow-700' : 'text-green-700'} font-bold">
                    ${isDev ? 'DEV' : 'PROD'}
                </span>
            </span>
        `;
        
        // Actualizar tooltip
        labelElement.title = `${modo.toUpperCase()}\n${url}`;
    }
</script>
@endpush
@endsection
