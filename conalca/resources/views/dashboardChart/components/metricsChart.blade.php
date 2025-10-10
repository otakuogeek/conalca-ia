<!-- Modern Metrics Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <!-- Métrica Uno - Ingresos Totales -->
    <div class="group relative bg-white rounded-2xl p-6 shadow-lg hover-scale border border-gray-100 overflow-hidden">
        <!-- Background gradient overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-orange-50 to-red-50 opacity-50 group-hover:opacity-70 transition-opacity duration-300"></div>
        
        <!-- Content -->
        <div class="relative z-10">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-gradient-to-r from-orange-500 to-red-600 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <h6 class="text-lg font-bold text-gray-800">Ingresos Totales del Mes</h6>
                </div>
                <button class="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-orange-500 transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>

            <!-- Main value -->
            <div class="flex items-end space-x-4 mb-4">
                <h2 class="text-3xl font-bold text-gray-900 group-hover:text-orange-600 transition-colors duration-300">
                    ${{ number_format($currentMonthRevenue ?? 0, 2) }}
                </h2>
                <div class="flex items-center px-3 py-1 {{ ($revenueChangePercent ?? 0) >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} rounded-full text-sm font-semibold">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        @if(($revenueChangePercent ?? 0) >= 0)
                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        @else
                            <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        @endif
                    </svg>
                    {{ ($revenueChangePercent ?? 0) >= 0 ? '+' : '' }}{{ number_format($revenueChangePercent ?? 0, 1) }}%
                </div>
            </div>

            <!-- Comparison -->
            <div class="flex items-center text-sm text-gray-500">
                <span>vs mes anterior:</span>
                <span class="ml-2 font-semibold text-gray-700">${{ number_format($previousMonthRevenue ?? 0, 2) }}</span>
            </div>

            <!-- Progress bar -->
            <div class="mt-4 bg-gray-200 rounded-full h-2 overflow-hidden">
                <div class="bg-gradient-to-r from-orange-500 to-red-600 h-full rounded-full transform transition-transform duration-1000 ease-out" 
                     style="width: {{ min(100, max(10, ($currentMonthRevenue ?? 1) / max(($previousMonthRevenue ?? 1), 1) * 50)) }}%"></div>
            </div>
        </div>
    </div>
    
    <!-- Métrica Dos - Gastos Operativos -->
    <div class="group relative bg-white rounded-2xl p-6 shadow-lg hover-scale border border-gray-100 overflow-hidden">
        <!-- Background gradient overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-red-50 to-orange-50 opacity-50 group-hover:opacity-70 transition-opacity duration-300"></div>
        
        <!-- Content -->
        <div class="relative z-10">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-gradient-to-r from-red-500 to-orange-500 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h6 class="text-lg font-bold text-gray-800">Solicitudes en Proceso</h6>
                </div>
                <button class="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-red-500 transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>

            <!-- Main value -->
            <div class="flex items-end space-x-4 mb-4">
                <h2 class="text-3xl font-bold text-gray-900 group-hover:text-red-600 transition-colors duration-300">
                    {{ number_format($currentMonthInProcess ?? 0) }}
                </h2>
                <div class="flex items-center px-3 py-1 {{ ($inProcessChangePercent ?? 0) >= 0 ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700' }} rounded-full text-sm font-semibold">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        @if(($inProcessChangePercent ?? 0) >= 0)
                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        @else
                            <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        @endif
                    </svg>
                    {{ ($inProcessChangePercent ?? 0) >= 0 ? '+' : '' }}{{ number_format($inProcessChangePercent ?? 0, 1) }}%
                </div>
            </div>

            <!-- Comparison -->
            <div class="flex items-center text-sm text-gray-500">
                <span>vs mes anterior:</span>
                <span class="ml-2 font-semibold text-gray-700">{{ number_format($previousMonthInProcess ?? 0) }}</span>
            </div>

            <!-- Progress bar -->
            <div class="mt-4 bg-gray-200 rounded-full h-2 overflow-hidden">
                <div class="bg-gradient-to-r from-red-500 to-orange-500 h-full rounded-full transform transition-transform duration-1000 ease-out" 
                     style="width: {{ min(100, max(10, ($currentMonthInProcess ?? 1) / max(($currentMonthInProcess ?? 1) + ($completedProjects ?? 1), 1) * 100)) }}%"></div>
            </div>
        </div>
    </div>
    
    <!-- Métrica Tres - Clientes Activos -->
    <div class="group relative bg-white rounded-2xl p-6 shadow-lg hover-scale border border-gray-100 overflow-hidden">
        <!-- Background gradient overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-orange-50 to-red-50 opacity-50 group-hover:opacity-70 transition-opacity duration-300"></div>
        
        <!-- Content -->
        <div class="relative z-10">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-gradient-to-r from-orange-500 to-red-500 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h6 class="text-lg font-bold text-gray-800">Clientes Activos</h6>
                </div>
                <button class="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-orange-500 transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>

            <!-- Main value -->
            <div class="flex items-end space-x-4 mb-4">
                <h2 class="text-3xl font-bold text-gray-900 group-hover:text-orange-600 transition-colors duration-300">
                    {{ number_format($activeClients ?? 0) }}
                </h2>
                <div class="flex items-center px-3 py-1 {{ ($clientsChangePercent ?? 0) >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} rounded-full text-sm font-semibold">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        @if(($clientsChangePercent ?? 0) >= 0)
                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        @else
                            <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        @endif
                    </svg>
                    {{ ($clientsChangePercent ?? 0) >= 0 ? '+' : '' }}{{ number_format($clientsChangePercent ?? 0, 1) }}%
                </div>
            </div>

            <!-- Comparison -->
            <div class="flex items-center text-sm text-gray-500">
                <span>vs mes anterior:</span>
                <span class="ml-2 font-semibold text-gray-700">{{ number_format($previousActiveClients ?? 0) }}</span>
            </div>

            <!-- Progress bar -->
            <div class="mt-4 bg-gray-200 rounded-full h-2 overflow-hidden">
                <div class="bg-gradient-to-r from-orange-500 to-red-500 h-full rounded-full transform transition-transform duration-1000 ease-out" 
                     style="width: {{ min(100, max(10, ($activeClients ?? 1) / max(($previousActiveClients ?? 1), 1) * 50 + 25)) }}%"></div>
            </div>
        </div>
    </div>
    
    <!-- Métrica Cuatro - Proyectos Completados -->
    <div class="group relative bg-white rounded-2xl p-6 shadow-lg hover-scale border border-gray-100 overflow-hidden">
        <!-- Background gradient overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-red-50 to-orange-50 opacity-50 group-hover:opacity-70 transition-opacity duration-300"></div>
        
        <!-- Content -->
        <div class="relative z-10">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-gradient-to-r from-red-500 to-orange-600 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h6 class="text-lg font-bold text-gray-800">Proyectos Completados</h6>
                </div>
                <button class="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-red-500 transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>

            <!-- Main value -->
            <div class="flex items-end space-x-4 mb-4">
                <h2 class="text-3xl font-bold text-gray-900 group-hover:text-red-600 transition-colors duration-300">
                    {{ number_format($completedProjects ?? 0) }}
                </h2>
                <div class="flex items-center px-3 py-1 {{ ($projectsChangePercent ?? 0) >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} rounded-full text-sm font-semibold">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        @if(($projectsChangePercent ?? 0) >= 0)
                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        @else
                            <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        @endif
                    </svg>
                    {{ ($projectsChangePercent ?? 0) >= 0 ? '+' : '' }}{{ number_format($projectsChangePercent ?? 0, 1) }}%
                </div>
            </div>

            <!-- Comparison -->
            <div class="flex items-center text-sm text-gray-500">
                <span>vs mes anterior:</span>
                <span class="ml-2 font-semibold text-gray-700">{{ number_format($previousCompletedProjects ?? 0) }}</span>
            </div>

            <!-- Progress bar -->
            <div class="mt-4 bg-gray-200 rounded-full h-2 overflow-hidden">
                <div class="bg-gradient-to-r from-red-500 to-orange-600 h-full rounded-full transform transition-transform duration-1000 ease-out" 
                     style="width: {{ min(100, max(10, ($completedProjects ?? 0) > 0 ? (($completedProjects ?? 1) / max(($completedProjects ?? 1) + ($previousCompletedProjects ?? 1), 1) * 100) : 10)) }}%"></div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Enhanced animations */
    .hover-scale {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .hover-scale:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
    }
    
    /* Progress bar animation */
    @keyframes fillProgress {
        from { width: 0%; }
    }
    
    .progress-fill {
        animation: fillProgress 2s ease-out;
    }
    
    /* Pulse effect for icons */
    .group:hover .pulse-icon {
        animation: pulse 1.5s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
</style>
