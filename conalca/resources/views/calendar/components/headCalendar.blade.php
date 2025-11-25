<!-- Calendar Header -->
<div class="bg-white rounded-2xl shadow-sm" style="font-family: 'Product Sans', sans-serif !important;">
    <!-- Top section with email and search only -->
    <div class="flex items-center justify-between px-8 py-6" style="font-family: 'Product Sans', sans-serif !important;">
        <div class="flex items-center gap-3" style="font-family: 'Product Sans', sans-serif !important;">
            <span class="text-gray-500 font-medium" style="font-family: 'Product Sans', sans-serif !important;">Tu Correo</span>
            <div class="flex items-center gap-2" style="font-family: 'Product Sans', sans-serif !important;">
                <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
                <span class="text-gray-700 font-semibold" style="font-family: 'Product Sans', sans-serif !important;">{{ auth()->user()->email ?? 'tucorreo@gmail.com' }}</span>
            </div>
        </div>
        
        <div class="flex flex-col items-end gap-2" style="font-family: 'Product Sans', sans-serif !important;">
            <form method="GET" action="{{ route('calendar.show') }}" class="relative">
                <input type="search" name="search" id="search" placeholder="Buscar eventos o tareas..." 
                       value="{{ request('search') }}" 
                       style="font-family: 'Product Sans', sans-serif !important;"
                      
                       class=" text-gray-700 font-semibold bg-gray-50 border border-gray-200 rounded-lg px-4 py-2 pl-10 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent w-64" />
                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M15.5 14H14.71L14.43 13.73C15.41 12.59 16 11.11 16 9.5C16 5.91 13.09 3 9.5 3C5.91 3 3 5.91 3 9.5C3 13.09 5.91 16 9.5 16C11.11 16 12.59 15.41 13.73 14.43L14 14.71V15.5L19 20.49L20.49 19L15.5 14ZM9.5 14C7.01 14 5 11.99 5 9.5C5 7.01 7.01 5 9.5 5C11.99 5 14 7.01 14 9.5C14 11.99 11.99 14 9.5 14Z"/>
                </svg>
                
                <!-- Botón para limpiar búsqueda -->
                @if(request('search'))
                    <a href="{{ route('calendar.show') }}" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                        </svg>
                    </a>
                @endif
            </form>
            
            <!-- Indicador de búsqueda activa -->
            @if(request('search'))
                <div class="flex items-center gap-2 text-sm text-orange-600 bg-orange-50 px-3 py-1 rounded-full" style="font-family: 'Product Sans', sans-serif !important;">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    <span style="font-family: 'Product Sans', sans-serif !important;" class="text-gray-700 font-semibold" >
                        @if(isset($events) && count($events) > 0)
                            {{ count($events) }} {{ count($events) == 1 ? 'resultado' : 'resultados' }} para: "<strong>{{ request('search') }}</strong>"
                        @else
                           Resultados para: "<strong>{{ request('search') }}</strong>"
                        @endif
                    </span>
                </div>
            @endif
        </div>
    </div>
</div>