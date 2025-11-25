@extends('layout.app')

@section('title')
    {{ 'Análisis' }}
@endsection

@push('scripts')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.min.css') }}" />
    <script src="{{ asset('vendor/leaflet/leaflet.min.js') }}"></script>
    <script src="{{ asset('vendor/leaflet-routing-machine/leaflet-routing-machine.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('vendor/leaflet-routing-machine/leaflet-routing-machine.css') }}" />
    @vite(['resources/css/analysis.css', 'resources/js/analysis.js'])
    
    {{-- Pasar datos del servidor al JavaScript --}}
    <script>
        // Cargar datos desde la API en lugar de usar variables PHP
        window.analysisServerData = null; // Forzar carga desde API
    </script>
@endpush


@section('content')
    <section class="w-full bg-[#F9F9F9] text-[#232323] min-h-screen p-6">
        <!-- Header Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Análisis de Rutas de Transporte</h1>
            <p class="text-gray-600">Dashboard de métricas y análisis en tiempo real del sistema de transporte</p>
        </div>

        <!-- Main Content Grid -->
        <section class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <!-- Left Column - Map and Route Analysis -->
            <div class="xl:col-span-2 space-y-6">
                <!-- Interactive Map Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden chart-container">
                    <div class="flex h-80">
                        <!-- Info Panel -->
                        <div class="w-80 p-6 bg-gradient-to-br from-blue-50 to-indigo-50 border-r border-gray-200">
                            <div class="flex items-center mb-4">
                                <div class="w-2 h-8 bg-blue-600 rounded-full mr-3"></div>
                                <h2 class="text-xl font-bold text-gray-800">Ubicaciones principales</h2>
                            </div>
                            
                            <div class="flex items-center gap-4 mb-6 p-4 bg-white rounded-lg shadow-sm">
                                <div>
                                    <h3 class="text-3xl font-bold text-blue-600" id="totalClients">0</h3>
                                    <p class="text-gray-600 text-sm">Clientes registrados</p>
                                </div>
                                <div class="w-12 h-12 bg-gradient-to-r from-yellow-400 to-red-500 rounded-full flex items-center justify-center">
                                    <span class="text-white font-bold text-xs">CO</span>
                                </div>
                            </div>

                            <div class="space-y-3" id="statusDistribution">
                                <div class="flex justify-between items-center p-2 rounded-lg hover:bg-white transition-colors">
                                    <div class="flex gap-3 items-center">
                                        <div class="rounded-full bg-blue-600 w-3 h-3"></div>
                                        <span class="text-gray-700 font-medium">Activa</span>
                                    </div>
                                    <span class="font-bold text-gray-800">0</span>
                                </div>
                                <div class="flex justify-between items-center p-2 rounded-lg hover:bg-white transition-colors">
                                    <div class="flex gap-3 items-center">
                                        <div class="rounded-full bg-green-600 w-3 h-3"></div>
                                        <span class="text-gray-700 font-medium">Completada</span>
                                    </div>
                                    <span class="font-bold text-gray-800">0</span>
                                </div>
                                <div class="flex justify-between items-center p-2 rounded-lg hover:bg-white transition-colors">
                                    <div class="flex gap-3 items-center">
                                        <div class="rounded-full bg-yellow-500 w-3 h-3"></div>
                                        <span class="text-gray-700 font-medium">Borrador</span>
                                    </div>
                                    <span class="font-bold text-gray-800">0</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Interactive Map -->
                        <div class="flex-1 relative">
                            <div id="colombia-map" class="w-full h-full"></div>
                        </div>
                    </div>
                </div>

                <!-- Route Analysis Chart -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 chart-container">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Análisis de cotizaciones mensuales</h3>
                            <p class="text-gray-600 text-sm">Seguimiento de cotizaciones y eficiencia operativa</p>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-blue-600 rounded"></div>
                                <span class="text-sm text-gray-600">Cotizaciones</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-green-500 rounded"></div>
                                <span class="text-sm text-gray-600">Eficiencia (%)</span>
                            </div>
                        </div>
                    </div>
                    <div class="h-80">
                        <canvas id="lineChartSell" class="w-full h-full"></canvas>
                    </div>
                </div>

                <!-- Top Cities Analysis -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Análisis de rutas principales</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Top Origin Cities -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-700 mb-3">Ciudades de origen más frecuentes</h4>
                            <div class="space-y-2" id="topOriginCities">
                                <div class="text-center py-4">
                                    <p class="text-sm text-gray-500">Cargando datos...</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Top Destination Cities -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-700 mb-3">Ciudades de destino más frecuentes</h4>
                            <div class="space-y-2" id="topDestinationCities">
                                <div class="text-center py-4">
                                    <p class="text-sm text-gray-500">Cargando datos...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Economic Analysis -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Análisis económico</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <p class="text-2xl font-bold text-blue-600" id="avgValue">$0</p>
                            <p class="text-sm text-gray-600">Valor promedio</p>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <p class="text-2xl font-bold text-green-600" id="totalValue">$0</p>
                            <p class="text-sm text-gray-600">Valor total</p>
                        </div>
                        <div class="text-center p-4 bg-orange-50 rounded-lg">
                            <p class="text-2xl font-bold text-orange-600" id="maxValue">$0</p>
                            <p class="text-sm text-gray-600">Valor máximo</p>
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-lg">
                            <p class="text-2xl font-bold text-purple-600" id="avgWeight">0kg</p>
                            <p class="text-sm text-gray-600">Peso promedio</p>
                        </div>
                    </div>
                </div>

                <!-- Vehicle and Product Analysis -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Análisis de vehículos y productos</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Vehicle Types -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-700 mb-3">Tipos de vehículo más solicitados</h4>
                            <div class="space-y-2" id="vehicleTypes">
                                <div class="text-center py-4">
                                    <p class="text-sm text-gray-500">Cargando datos...</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Product Types -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-700 mb-3">Tipos de producto más frecuentes</h4>
                            <div class="space-y-2" id="productTypes">
                                <div class="text-center py-4">
                                    <p class="text-sm text-gray-500">Cargando datos...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Analytics and Metrics -->
            <div class="space-y-6">
                <!-- Annual Distribution -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 chart-container">
                    <div class="text-center mb-4">
                        <h3 class="text-lg font-bold text-gray-800 mb-1">Distribución por tipo de transporte</h3>
                        <p class="text-gray-600 text-sm">Tipos de transporte por volumen</p>
                    </div>

                    <div class="h-64 flex items-center justify-center">
                        <canvas id="doughnutYear" class="max-w-full max-h-full"></canvas>
                    </div>

                    <div class="flex justify-around mt-4 pt-4 border-t border-gray-100" id="transportTypeDistribution">
                        <div class="text-center">
                            <p class="text-sm text-gray-500">Cargando datos...</p>
                        </div>
                    </div>
                </div>

                <!-- Performance Metrics -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 metric-card">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Métricas de rendimiento</h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-center gap-4 p-3 bg-green-50 rounded-lg">
                            <div class="w-12 h-12 flex items-center justify-center">
                                <canvas id="miniDonut1" class="w-full h-full"></canvas>
                            </div>
                            <div class="flex-1">
                                <p class="font-bold text-2xl text-green-600" id="acceptanceRate">0%</p>
                                <p class="text-gray-600 text-sm">Tasa de aceptación</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 p-3 bg-orange-50 rounded-lg">
                            <div class="w-12 h-12 flex items-center justify-center">
                                <canvas id="miniDonut2" class="w-full h-full"></canvas>
                            </div>
                            <div class="flex-1">
                                <p class="font-bold text-2xl text-orange-500" id="averageResponseTime">0h</p>
                                <p class="text-gray-600 text-sm">Tiempo promedio de respuesta</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Growth Trend -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 metric-card">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Tendencia de crecimiento</h3>
                    <div class="h-20 mb-4">
                        <canvas id="lineYearNoGrid" class="w-full h-full"></canvas>
                    </div>
                    <div class="flex justify-between items-end">
                        <div>
                            <p class="font-bold text-3xl text-blue-600" id="totalCotizations">0</p>
                            <p class="text-gray-600 text-sm">Cotizaciones totales</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-500" id="growth">0%</p>
                            <p class="text-xs text-gray-400">vs mes anterior</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </section>
@endsection
