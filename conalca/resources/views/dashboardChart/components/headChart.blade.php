<!-- Modern Header with Glass Morphism -->
<div class="glass-morphism rounded-2xl p-6 mb-8 backdrop-blur-lg shadow-2xl">
    <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
        
        <!-- Title Section -->
        <div class="flex items-center space-x-4">
            <div class="p-3 bg-gradient-to-r from-orange-500 to-red-600 rounded-xl">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-800 tracking-tight">Panel de Control</h2>
        </div>

        <!-- Navigation Modules
         <div class="flex flex-wrap items-center justify-center gap-2 bg-white/60 p-2 rounded-xl backdrop-blur-sm border border-white/30 shadow-lg">
            <a href="#" id="module-1" 
               class="group relative flex items-center justify-center px-4 py-2 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-lg font-semibold text-sm transition-all duration-300 hover:from-orange-600 hover:to-red-500 hover:shadow-lg hover:scale-105 active:scale-95">
                <span class="relative z-10">Análisis</span>
                <div class="absolute inset-0 rounded-lg bg-gradient-to-r from-orange-400 to-red-400 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            </a>
            <a href="#" id="module-2" 
               class="group flex items-center justify-center px-4 py-2 bg-white text-gray-700 rounded-lg font-semibold text-sm transition-all duration-300 hover:bg-orange-50 hover:text-orange-700 hover:shadow-md hover:scale-105 active:scale-95">
                Finanzas
            </a>
            <a href="#" id="module-3" 
               class="group flex items-center justify-center px-4 py-2 bg-white text-gray-700 rounded-lg font-semibold text-sm transition-all duration-300 hover:bg-orange-50 hover:text-orange-700 hover:shadow-md hover:scale-105 active:scale-95">
                Clientes
            </a>
            <a href="#" id="module-4" 
               class="group flex items-center justify-center px-4 py-2 bg-white text-gray-700 rounded-lg font-semibold text-sm transition-all duration-300 hover:bg-orange-50 hover:text-orange-700 hover:shadow-md hover:scale-105 active:scale-95">
                Reportes
            </a>
            <a href="#" id="module-5" 
               class="group flex items-center justify-center px-4 py-2 bg-white text-gray-700 rounded-lg font-semibold text-sm transition-all duration-300 hover:bg-orange-50 hover:text-orange-700 hover:shadow-md hover:scale-105 active:scale-95">
                Configuración
            </a>
        </div> 
        -->
       

        <!-- Action Buttons -->
        <div class="flex items-center gap-4 flex-wrap">
            <!-- Date Selector
             <div class="relative">
                <select name="months" id="months" 
                        class="appearance-none bg-white/80 border border-gray-200 rounded-xl px-4 py-3 pr-10 text-sm font-medium text-gray-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-300 hover:bg-white">
                    @for ($monthNumber = 1; $monthNumber <= 12; $monthNumber++)
                        @php(setlocale(LC_TIME, 'es_ES.utf8'))
                        <option value="{{ $monthNumber }}">
                            {{ strftime('%B', mktime(0, 0, 0, $monthNumber, 1)) }}
                        </option>
                    @endfor
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div> 
            -->
           

            <!-- Add Activity Button -->
            <a href="{{ route('calendar.show') }}" 
               class="group relative flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-xl font-bold text-sm transition-all duration-300 hover:from-orange-600 hover:to-orange-700 hover:shadow-lg hover:scale-105 active:scale-95 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-orange-400 to-orange-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <svg class="w-5 h-5 relative z-10 transition-transform group-hover:rotate-90 duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span class="relative z-10">Nueva Actividad</span>
            </a>

           
        </div>
    </div>
</div>

<style>
    /* Custom animations */
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animate-slide-up {
        animation: slideInUp 0.6s ease-out;
    }
    
    /* Custom select arrow */
    select {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 0.5rem center;
        background-repeat: no-repeat;
        background-size: 1.5em 1.5em;
    }
</style>
