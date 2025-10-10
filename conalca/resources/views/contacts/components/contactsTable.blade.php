<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" id="clientsTableContainer">
    <div class="overflow-x-auto">
        <table class="table table-xs w-full">
            <!-- head -->
            <thead class="bg-gray-50">
                <tr class="text-[#92929D] text-sm font-medium leading-normal tracking-[0.00625rem] h-12 border-b border-gray-200">
                    <th class="md:align-middle px-4 py-3">
                        <label>
                            <input type="checkbox" class="checkbox checkbox-primary" />
                        </label>
                    </th>
                    <th class="align-middle md:text-start px-4 py-3 font-medium">Documento (NIT)</th>
                    <th class="align-middle md:text-start px-4 py-3 font-medium">Cliente</th>
                    <th class="align-middle md:text-center px-4 py-3 font-medium">Código</th>
                    <th class="align-middle md:text-center px-4 py-3 font-medium">Estado</th>
                    <th class="align-middle md:text-center px-4 py-3 font-medium">Ubicación</th>
                    <th class="align-middle md:text-center px-4 py-3 font-medium">Asignados</th>
                    @if(auth()->user()->hasRole(['SUPER ADMIN', 'JEFE COMERCIAL']))
                    <th class="align-middle md:text-center px-4 py-3 font-medium">Acciones</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <!-- row -->
                @if ($clients->count())
                    @foreach ($clients as $client)
                        <tr class="hover:bg-gray-50/50 transition-colors duration-150">
                            <th class="align-middle px-4 py-4">
                                <label>
                                    <input type="radio" name="radio-1" class="radio radio-primary client-selector"
                                        data-client-id="{{ $client->id }}" />
                                </label>
                            </th>
                            <td class="px-4 py-4 align-middle">
                                <div class="leading-normal tracking-[0.00625rem] font-normal">
                                    <h5 class="text-[#374151] text-sm font-semibold">{{ $client->documento ?? 'Sin documento' }}</h5>
                                </div>
                            </td>
                            <td class="px-4 py-4 align-middle">
                                <div class="leading-normal tracking-[0.00625rem] font-normal">
                                    <h5 class="text-[#374151] text-sm font-medium">{{ $client->cliente ?? 'Sin nombre' }}</h5>
                                </div>
                            </td>
                            <td class="align-middle text-center px-4 py-4">
                                <h5 class="leading-normal tracking-[0.00625rem] font-normal text-[#374151] text-sm font-medium">
                                    {{ $client->codigo ?? 'Sin código' }}</h5>
                            </td>
                            <td class="align-middle text-center px-4 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium 
                                    @if($client->estado == 'Activo') bg-green-100 text-green-700 border border-green-200
                                    @elseif($client->estado == 'Inactivo') bg-red-100 text-red-700 border border-red-200
                                    @else bg-gray-100 text-gray-700 border border-gray-200 @endif">
                                    {{ $client->estado ?? 'Sin estado' }}
                                </span>
                            </td>
                            <td class="align-middle text-center px-4 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border">
                                    {{ $client->ciudad ?? 'Sin ubicación' }}
                                </span>
                            </td>
                            <td class="align-middle text-center px-4 py-4">
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
                            @if(auth()->user()->hasRole(['SUPER ADMIN', 'JEFE COMERCIAL']))
                            <td class="align-middle text-center px-4 py-4">
                                <button onclick="openAssignModal({{ $client->id }})" 
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-[#82c43c] hover:bg-[#6fa832] rounded-md transition-colors duration-200 shadow-sm">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Asignar
                                </button>
                            </td>
                            @endif
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="@if(auth()->user()->hasRole(['SUPER ADMIN', 'JEFE COMERCIAL'])) 7 @else 6 @endif" class="text-center py-12">
                            <div class="flex flex-col items-center justify-center text-gray-400">
                                <svg class="w-16 h-16 mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <p class="text-lg font-medium text-gray-500 mb-1">No hay clientes disponibles</p>
                                <p class="text-sm text-gray-400">Intenta ajustar tu búsqueda o agregar nuevos clientes</p>
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
                    <a href="{{ $clients->previousPageUrl() }}" class="flex items-center justify-center w-9 h-9 text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-[#82c43c] hover:text-[#82c43c] transition-all duration-200">
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
                    <a href="{{ $clients->url(1) }}" class="flex items-center justify-center w-9 h-9 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-[#82c43c] hover:text-[#82c43c] transition-all duration-200">
                        1
                    </a>
                    @if($start > 2)
                        <span class="flex items-center justify-center w-9 h-9 text-gray-400">...</span>
                    @endif
                @endif

                @for($page = $start; $page <= $end; $page++)
                    @if ($page == $clients->currentPage())
                        <button class="flex items-center justify-center w-9 h-9 text-sm font-medium text-white bg-[#82c43c] border border-[#82c43c] rounded-lg shadow-sm">
                            {{ $page }}
                        </button>
                    @else
                        <a href="{{ $clients->url($page) }}" class="flex items-center justify-center w-9 h-9 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-[#82c43c] hover:text-[#82c43c] transition-all duration-200">
                            {{ $page }}
                        </a>
                    @endif
                @endfor

                @if($end < $clients->lastPage())
                    @if($end < $clients->lastPage() - 1)
                        <span class="flex items-center justify-center w-9 h-9 text-gray-400">...</span>
                    @endif
                    <a href="{{ $clients->url($clients->lastPage()) }}" class="flex items-center justify-center w-9 h-9 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-[#82c43c] hover:text-[#82c43c] transition-all duration-200">
                        {{ $clients->lastPage() }}
                    </a>
                @endif

                {{-- Next Page Link --}}
                @if ($clients->hasMorePages())
                    <a href="{{ $clients->nextPageUrl() }}" class="flex items-center justify-center w-9 h-9 text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-[#82c43c] hover:text-[#82c43c] transition-all duration-200">
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
