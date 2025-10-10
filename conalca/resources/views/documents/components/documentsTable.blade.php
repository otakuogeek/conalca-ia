<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" id="clientsTableContainer">
    <div class="overflow-x-auto">
        <table class="table table-sm w-full">
            <!-- head -->
            <thead class="bg-gray-50">
                <tr class="text-gray-600 text-sm font-medium uppercase tracking-wider h-14 border-b border-gray-200">
                    <th class="pl-6 pr-3 py-4 text-left w-12">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" class="checkbox checkbox-sm checkbox-primary border-gray-300" />
                        </label>
                    </th>
                    <th class="px-3 py-4 text-left font-semibold">Cliente</th>
                    <th class="px-3 py-4 text-left font-semibold">Documento</th>
                    <th class="px-3 py-4 text-center font-semibold">Ubicación</th>
                    <th class="px-3 py-4 text-center font-semibold">Documentos</th>
                    <th class="px-3 py-4 text-center font-semibold">Asignados</th>
                    <th class="px-3 py-4 text-center font-semibold pr-6">Acción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <!-- row -->
                @if ($clients->count())
                    @foreach ($clients as $client)
                        <tr class="hover:bg-gray-50/50 transition-colors duration-200 group">
                            <td class="pl-6 pr-3 py-4">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="selected_client" 
                                        class="radio radio-sm radio-primary border-gray-300 client-selector"
                                        data-client-id="{{ $client->id }}"
                                        data-client-name="{{ $client->cliente ?? 'Sin nombre' }}"
                                        data-client-document="{{ $client->documento ?? 'Sin documento' }}" />
                                </label>
                            </td>
                            <td class="px-3 py-4">
                                <div class="flex items-center space-x-3">
                                   
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 truncate">
                                            {{ $client->cliente ?? 'Sin nombre' }}
                                        </p>
                                     
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-gray-900">{{ $client->documento ?? 'Sin documento' }}</span>
                          
                                </div>
                            </td>
                            <td class="px-3 py-4 text-center">
                                <div class="flex flex-col items-center">
                                    <span class="text-sm font-medium text-gray-700">{{ $client->ciudad ?? 'Sin ciudad' }}</span>
                                  
                                </div>
                            </td>
                            <td class="px-3 py-4 text-center">
                                <div class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ $client->files ? $client->files->count() : 0 }} docs
                                </div>
                            </td>
                            <td class="px-3 py-4 text-center">
                                <div class="flex flex-wrap gap-1 justify-center">
                                    @if($client->assignedUsers && $client->assignedUsers->count() > 0)
                                        @foreach($client->assignedUsers->take(2) as $user)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                                {{ $user->name }}
                                            </span>
                                        @endforeach
                                        @if($client->assignedUsers->count() > 2)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200">
                                                +{{ $client->assignedUsers->count() - 2 }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400 italic">Sin asignar</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-4 text-center pr-6">
                                <button class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 hover:border-orange-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-all duration-200 group-hover:border-orange-300 group-hover:text-orange-700">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                    Seleccionar
                                </button>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-500">
                                <svg class="w-16 h-16 mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <p class="text-lg font-medium text-gray-500 mb-1">No hay clientes disponibles</p>
                                <p class="text-sm text-gray-400">Intenta ajustar tu búsqueda o verifica los permisos</p>
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
    
    @if($clients->hasPages())
    <!-- Pagination -->
    <div class="bg-white px-6 py-4 border-t border-gray-200">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <!-- Results info -->
            <div class="flex items-center text-sm text-gray-600">
                <div class="flex items-center gap-2">
                    <span class="flex items-center justify-center w-8 h-8 bg-gray-100 rounded-full text-xs font-medium text-gray-700">
                        {{ $clients->currentPage() }}
                    </span>
                    <span>de {{ $clients->lastPage() }} páginas</span>
                </div>
                <span class="mx-2 text-gray-400">•</span>
                <span>{{ $clients->total() }} clientes totales</span>
            </div>
            
            <!-- Pagination controls -->
            <div class="flex items-center gap-1">
                {{-- Previous Page Link --}}
                @if ($clients->onFirstPage())
                    <button disabled class="flex items-center justify-center w-9 h-9 text-gray-300 bg-white border border-gray-200 rounded-lg cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                @else
                    <a href="{{ $clients->previousPageUrl() }}" class="flex items-center justify-center w-9 h-9 text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-orange-400 hover:text-orange-600 transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                @endif

                {{-- Page Numbers --}}
                @php
                    $start = max(1, $clients->currentPage() - 2);
                    $end = min($clients->lastPage(), $clients->currentPage() + 2);
                @endphp

                @if($start > 1)
                    <a href="{{ $clients->url(1) }}" class="flex items-center justify-center w-9 h-9 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-orange-400 hover:text-orange-600 transition-all duration-200">
                        1
                    </a>
                    @if($start > 2)
                        <span class="flex items-center justify-center w-9 h-9 text-gray-400">...</span>
                    @endif
                @endif

                @for($page = $start; $page <= $end; $page++)
                    @if ($page == $clients->currentPage())
                        <button class="flex items-center justify-center w-9 h-9 text-sm font-medium text-white bg-orange-600 border border-orange-600 rounded-lg shadow-sm">
                            {{ $page }}
                        </button>
                    @else
                        <a href="{{ $clients->url($page) }}" class="flex items-center justify-center w-9 h-9 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-orange-400 hover:text-orange-600 transition-all duration-200">
                            {{ $page }}
                        </a>
                    @endif
                @endfor

                @if($end < $clients->lastPage())
                    @if($end < $clients->lastPage() - 1)
                        <span class="flex items-center justify-center w-9 h-9 text-gray-400">...</span>
                    @endif
                    <a href="{{ $clients->url($clients->lastPage()) }}" class="flex items-center justify-center w-9 h-9 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-orange-400 hover:text-orange-600 transition-all duration-200">
                        {{ $clients->lastPage() }}
                    </a>
                @endif

                {{-- Next Page Link --}}
                @if ($clients->hasMorePages())
                    <a href="{{ $clients->nextPageUrl() }}" class="flex items-center justify-center w-9 h-9 text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-orange-400 hover:text-orange-600 transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                @else
                    <button disabled class="flex items-center justify-center w-9 h-9 text-gray-300 bg-white border border-gray-200 rounded-lg cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                @endif
            </div>
        </div>
        
        <!-- Mobile pagination info -->
        <div class="mt-3 text-xs text-center text-gray-500 sm:hidden">
            Mostrando {{ $clients->firstItem() }}-{{ $clients->lastItem() }} de {{ $clients->total() }} resultados
        </div>
    </div>
    @endif
</div>
