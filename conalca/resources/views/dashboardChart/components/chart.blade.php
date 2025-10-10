<!-- Modern Charts Section -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-8 mb-8">
    
    <!-- Main Chart Section -->
    <div class="xl:col-span-2">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
            <!-- Chart Header -->
            <div class="bg-gradient-to-r from-gray-50 to-orange-50 p-6 border-b border-gray-100">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <!-- Chart Title -->
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-gradient-to-r from-orange-500 to-red-600 rounded-xl shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Ventas Mensuales</h3>
                            <p class="text-sm text-gray-600">{{ now()->format('Y') }} - Datos reales por mes</p>
                        </div>
                    </div>

                    <!-- Chart Controls -->
                    <div class="flex items-center space-x-4">
                       
                    </div>
                </div>

                <!-- Key Metrics -->
                <div class="flex items-center space-x-6 mt-6">
                    <div class="flex items-center space-x-3">
                        @php
                            $totalRevenue = collect($monthlyData ?? [])->sum('revenue');
                            $totalTrips = collect($monthlyData ?? [])->sum('trips');
                            $averageMonthly = count($monthlyData ?? []) > 0 ? $totalRevenue / count($monthlyData) : 0;
                        @endphp
                        <h4 class="text-3xl font-bold text-gray-900">${{ number_format($totalRevenue, 2) }}</h4>
                        <div class="flex items-center px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 20 20">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                            {{ count($monthlyData ?? []) }} meses
                        </div>
                    </div>
                    <div class="text-sm text-gray-500">
                        <span class="block">Total Ventas {{ now()->format('Y') }}</span>
                        <span class="font-semibold text-gray-700">Promedio mensual: ${{ number_format($averageMonthly, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Chart Canvas -->
            <div class="p-6">
              
                <div class="relative h-96 bg-gradient-to-br from-gray-50 to-orange-50 rounded-xl p-4">
                    <canvas id="monthlyChart" class="w-full h-full"></canvas>
                    
                    <!-- Loading State -->
                    <div id="chart-loading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-90 rounded-xl">
                        <div class="flex flex-col items-center space-y-4">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500"></div>
                            <p class="text-gray-600 font-medium">Cargando datos mensuales...</p>
                        </div>
                    </div>
                </div>

                <!-- Chart Legend -->
                <div class="flex items-center justify-center space-x-8 mt-6">
                    <div class="flex items-center space-x-2">
                        <div class="w-4 h-4 bg-gradient-to-r from-orange-500 to-orange-600 rounded-full"></div>
                        <span class="text-sm font-medium text-gray-700">Ventas Mensuales {{ $currentYear ?? date('Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Activity Sidebar -->
    <div class="xl:col-span-1">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 h-full">
            <!-- Sidebar Header -->
            <div class="bg-gradient-to-r from-orange-50 to-red-50 p-6 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-gradient-to-r from-orange-500 to-red-600 rounded-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 uppercase tracking-wide">Actividades Pendientes</h3>
                    </div>
                    <button class="p-2 hover:bg-white hover:bg-opacity-50 rounded-lg transition-all duration-200">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- Quick Stats -->
                <div class="flex items-center space-x-4 mt-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600">{{ count($appointments) }}</div>
                        <div class="text-xs text-gray-600">Total</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600">{{ collect($appointments)->where('priority', 'high')->count() }}</div>
                        <div class="text-xs text-gray-600">Alta Prioridad</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-500">{{ collect($appointments)->where('completed', true)->count() }}</div>
                        <div class="text-xs text-gray-600">Completadas</div>
                    </div>
                </div>
            </div>

            <!-- Activity List -->
            <div class="p-6 space-y-4 max-h-96 overflow-y-auto custom-scrollbar">
                @forelse ($appointments as $index => $appointment)
                    <div class="group relative bg-gradient-to-r from-gray-50 to-white p-4 rounded-xl border border-gray-100 hover:shadow-lg transition-all duration-300 hover:scale-105">
                        <!-- Priority indicator -->
                        @php
                            $priorityColor = 'from-orange-400 to-red-400'; // default
                            if($appointment->priority === 'high') {
                                $priorityColor = 'from-red-500 to-orange-500';
                            } elseif($appointment->priority === 'medium') {
                                $priorityColor = 'from-orange-500 to-red-500';
                            } elseif($appointment->priority === 'low') {
                                $priorityColor = 'from-green-400 to-blue-400';
                            }
                            
                            // Calcular estado del evento/tarea
                            $today = \Carbon\Carbon::today();
                            $startDate = \Carbon\Carbon::parse($appointment->start_date);
                            $endDate = \Carbon\Carbon::parse($appointment->end_date);
                            
                            if($appointment->completed) {
                                $progress = 100;
                                $statusText = '✓ Completado';
                                $statusColor = 'text-green-600';
                            } elseif($endDate->isPast()) {
                                $progress = 95;
                                $statusText = '⚠ Vencido';
                                $statusColor = 'text-red-600';
                            } elseif($startDate->isToday() || ($startDate->isPast() && $endDate->isFuture())) {
                                $progress = 70;
                                $statusText = '🔄 En curso';
                                $statusColor = 'text-orange-600';
                            } else {
                                $progress = 25;
                                $statusText = '📅 Pendiente';
                                $statusColor = 'text-blue-600';
                            }
                        @endphp
                        <div class="absolute top-4 left-0 w-1 h-12 bg-gradient-to-b {{ $priorityColor }} rounded-r-full"></div>
                        
                        <div class="ml-4">
                            <!-- Date Range and Time -->
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-xs font-medium text-gray-500">
                                        {{ \Carbon\Carbon::parse($appointment->start_date)->locale('es')->isoFormat('DD MMM') }}
                                        @if($appointment->start_date !== $appointment->end_date)
                                            - {{ \Carbon\Carbon::parse($appointment->end_date)->locale('es')->isoFormat('DD MMM') }}
                                        @endif
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <!-- Type indicator -->
                                    @if($appointment->item_type === 'task')
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">Tarea</span>
                                    @else
                                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Evento</span>
                                    @endif
                                    <!-- Time -->
                                    @if($appointment->start_time)
                                        <span class="text-xs text-gray-500">
                                            {{ \Carbon\Carbon::parse($appointment->start_time)->format('H:i') }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Title and Client -->
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1">
                                    <h4 class="font-bold text-gray-800 group-hover:text-orange-600 transition-colors duration-200">
                                        {{ $appointment->title }}
                                    </h4>
                                    <p class="text-sm font-semibold text-gray-700">{{ $appointment->client }}</p>
                                </div>
                                <a href="{{ route('calendar.show') }}" 
                                   class="p-2 hover:bg-orange-100 rounded-lg transition-all duration-200 opacity-0 group-hover:opacity-100">
                                    <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            </div>

                            <!-- Notes -->
                            @if($appointment->notes)
                                <p class="text-xs text-gray-600 bg-gray-50 p-2 rounded-lg">{{ $appointment->notes }}</p>
                            @endif

                            <!-- Status and Progress indicator -->
                            <div class="mt-3 flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div class="flex-1 bg-gray-200 rounded-full h-1.5 w-20">
                                        <div class="h-1.5 {{ $priorityColor }} bg-gradient-to-r rounded-full transition-all duration-500"
                                             style="width: {{ $progress }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold {{ $statusColor }}">
                                        {{ $progress }}%
                                    </span>
                                </div>
                                <span class="text-xs font-medium {{ $statusColor }}">
                                    {{ $statusText }}
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-500 font-medium">No hay actividades pendientes</p>
                        <p class="text-sm text-gray-400">¡Excelente trabajo!</p>
                    </div>
                @endforelse
            </div>

            <!-- Action Footer -->
            <div class="p-6 border-t border-gray-100 bg-gray-50">
                <a href="{{ route('calendar.show') }}" 
                   class="w-full flex items-center justify-center space-x-2 py-3 px-4 bg-gradient-to-r from-orange-500 to-red-600 text-white rounded-xl font-semibold text-sm hover:from-orange-600 hover:to-red-700 transition-all duration-300 transform hover:scale-105">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <span>Ver todas las actividades</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Top Clients Section -->
@if(!empty($topClients) && count($topClients) > 0)
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
    <!-- Top Clientes -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-r from-orange-50 to-red-50 p-6 border-b border-gray-100">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-gradient-to-r from-orange-500 to-red-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800">Top Clientes del Mes</h3>
            </div>
        </div>
        
        <div class="p-6">
            @foreach($topClients as $index => $client)
                <div class="flex items-center justify-between p-4 rounded-xl border border-gray-100 hover:shadow-md transition-all duration-300 {{ $loop->last ? '' : 'mb-4' }}">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 bg-gradient-to-r {{ $index == 0 ? 'from-yellow-400 to-yellow-600' : ($index == 1 ? 'from-gray-400 to-gray-600' : ($index == 2 ? 'from-orange-400 to-orange-600' : 'from-blue-400 to-blue-600')) }} rounded-full flex items-center justify-center text-white font-bold">
                            {{ $index + 1 }}
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">{{ $client['name'] }}</h4>
                            <p class="text-sm text-gray-600">{{ $client['cotizations_count'] }} cotizaciones</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold text-gray-900">${{ number_format($client['value'], 2) }}</div>
                        <div class="text-sm text-gray-500">Total</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Distribución de Cotizaciones -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 p-6 border-b border-gray-100">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800">Distribución de Cotizaciones</h3>
            </div>
        </div>
        
        <div class="p-6">
            @php
                // Calcular distribución de cotizaciones por estado
                $cotizationStates = \App\Models\CotizacionModel::where('active', 1)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->selectRaw('
                        CASE 
                            WHEN decision_cliente = "aceptada" THEN "aceptada"
                            WHEN decision_cliente = "rechazada" THEN "rechazada"
                            WHEN decision_cliente = "pendiente" OR decision_cliente IS NULL THEN "pendiente"
                            ELSE "otros"
                        END as estado,
                        COUNT(*) as count
                    ')
                    ->groupBy('estado')
                    ->pluck('count', 'estado')
                    ->toArray();
                    
                $cotizationColors = [
                    'aceptada' => 'from-green-400 to-green-600',
                    'pendiente' => 'from-yellow-400 to-orange-500',
                    'rechazada' => 'from-red-400 to-red-600',
                    'otros' => 'from-gray-400 to-gray-600',
                ];
                
                $cotizationLabels = [
                    'aceptada' => 'Aceptadas',
                    'pendiente' => 'Pendientes',
                    'rechazada' => 'Rechazadas',
                    'otros' => 'Otros',
                ];
                
                $cotizationTotal = array_sum($cotizationStates);
            @endphp
            
            @if(!empty($cotizationStates) && $cotizationTotal > 0)
                @foreach($cotizationStates as $state => $count)
                    <div class="flex items-center justify-between p-4 rounded-xl border border-gray-100 hover:shadow-md transition-all duration-300 {{ $loop->last ? '' : 'mb-4' }}">
                        <div class="flex items-center space-x-4">
                            <div class="w-4 h-4 bg-gradient-to-r {{ $cotizationColors[$state] ?? 'from-gray-400 to-gray-600' }} rounded-full"></div>
                            <div>
                                <h4 class="font-semibold text-gray-800">{{ $cotizationLabels[$state] ?? ucfirst($state) }}</h4>
                                <div class="w-32 bg-gray-200 rounded-full h-2 mt-1">
                                    <div class="bg-gradient-to-r {{ $cotizationColors[$state] ?? 'from-gray-400 to-gray-600' }} h-2 rounded-full" 
                                         style="width: {{ ($count / $cotizationTotal) * 100 }}%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-gray-900">{{ $count }}</div>
                            <div class="text-sm text-gray-500">{{ round(($count / $cotizationTotal) * 100, 1) }}%</div>
                        </div>
                    </div>
                @endforeach
                
                <!-- Resumen total -->
                <div class="mt-6 pt-4 border-t border-gray-100">
                    <div class="flex items-center justify-between p-3 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg">
                        <div>
                            <h5 class="font-semibold text-gray-800">Total Cotizaciones</h5>
                            <p class="text-sm text-gray-600">Del mes actual</p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-purple-600">{{ $cotizationTotal }}</div>
                            <div class="text-sm text-gray-500">cotizaciones</div>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500 font-medium">No hay cotizaciones este mes</p>
                    <p class="text-sm text-gray-400">¡Empieza a crear cotizaciones!</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endif



<style>
    /* Custom scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: linear-gradient(45deg, #F7B267, #F25C54);
        border-radius: 10px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(45deg, #F79D65, #F27059);
    }
    
    /* Chart loading animation */
    #chart-loading {
        transition: opacity 0.5s ease-in-out;
    }
    
    /* Smooth transitions for all interactive elements */
    * {
        scroll-behavior: smooth;
    }
    
    /* Enhanced hover effects */
    .group:hover .transform {
        transform: translateY(-2px);
    }
</style>
