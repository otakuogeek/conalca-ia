<div class="space-y-6">
    <!-- Header con estadísticas -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h3 class="text-xl font-bold text-gray-900">Gestión de Llamadas</h3>
                <p class="text-gray-600">
                    @if($groupCotizationId)
                        Grupo de cotización #{{ $groupCotizationId }}
                    @elseif($cotizacionId)
                        Cotización #{{ $cotizacionId }}
                    @endif
                </p>
            </div>
            
            @if($groupCotizationId)
                <button 
                    wire:click="registrarLlamadasParaGrupo"
                    wire:loading.attr="disabled"
                    class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-lg transition-colors duration-200 flex items-center gap-2"
                    @if($isLoading) disabled @endif
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none" class="inline">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M4.41333 7.19333C5.37333 9.08 6.92 10.62 8.80667 11.5867L10.2733 10.12C10.4533 9.94 10.72 9.88 10.9533 9.96C11.7 10.2067 12.5067 10.34 13.3333 10.34C13.7 10.34 14 10.64 14 11.0067V13.3333C14 13.7 13.7 14 13.3333 14C7.07333 14 2 8.92667 2 2.66667C2 2.3 2.3 2 2.66667 2H5C5.36667 2 5.66667 2.3 5.66667 2.66667C5.66667 3.5 5.8 4.3 6.04667 5.04667C6.12 5.28 6.06667 5.54 5.88 5.72667L4.41333 7.19333Z"
                            fill="currentColor" />
                    </svg>
                    <span wire:loading.remove>Registrar Llamadas</span>
                    <span wire:loading>Registrando...</span>
                </button>
            @endif
        </div>

        <!-- Estadísticas -->
        @if(!empty($statistics) && $statistics['total'] > 0)
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-6">
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ $statistics['total'] }}</div>
                    <div class="text-sm text-gray-600">Total</div>
                </div>
                <div class="bg-yellow-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-800">{{ $statistics['pendientes'] }}</div>
                    <div class="text-sm text-yellow-600">Pendientes</div>
                </div>
                <div class="bg-blue-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-blue-800">{{ $statistics['en_curso'] }}</div>
                    <div class="text-sm text-blue-600">En Curso</div>
                </div>
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-green-800">{{ $statistics['aceptadas'] }}</div>
                    <div class="text-sm text-green-600">Aceptadas</div>
                </div>
                <div class="bg-red-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-red-800">{{ $statistics['rechazadas'] }}</div>
                    <div class="text-sm text-red-600">Rechazadas</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-gray-800">{{ $statistics['finalizadas'] }}</div>
                    <div class="text-sm text-gray-600">Finalizadas</div>
                </div>
            </div>
        @endif
    </div>

    <!-- Mensajes de flash -->
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Loading state -->
    <div wire:loading class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 flex items-center gap-3">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-red-500"></div>
            <span>Cargando...</span>
        </div>
    </div>

    <!-- Lista de llamadas -->
    @if(!empty($llamadas))
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Conductor
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Teléfono
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Vehículo
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ruta
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estado
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fecha
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($llamadas as $llamada)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #{{ $llamada['id_llamada'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $llamada['chofer']['nombre'] }}</div>
                                    <div class="text-sm text-gray-500">Placa: {{ $llamada['chofer']['placa'] }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $llamada['chofer']['telefono'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $llamada['chofer']['clase_vehiculo'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $llamada['cotizacion']['origen'] }} → {{ $llamada['cotizacion']['destino'] }}</div>
                                    <div class="text-sm text-gray-500">{{ $llamada['cotizacion']['vehiculo'] }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $llamada['status_color'] }}">
                                        {{ $llamada['status_label'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $llamada['created_at'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex gap-2">
                                        @if($llamada['status'] === 'pendiente')
                                            <button 
                                                wire:click="updateStatus({{ $llamada['id_llamada'] }}, 'en_curso')"
                                                class="text-blue-600 hover:text-blue-900 text-xs bg-blue-100 px-2 py-1 rounded"
                                            >
                                                Iniciar
                                            </button>
                                        @endif
                                        
                                        @if($llamada['status'] === 'en_curso')
                                            <button 
                                                wire:click="updateStatus({{ $llamada['id_llamada'] }}, 'aceptada')"
                                                class="text-green-600 hover:text-green-900 text-xs bg-green-100 px-2 py-1 rounded"
                                            >
                                                Aceptar
                                            </button>
                                            <button 
                                                wire:click="updateStatus({{ $llamada['id_llamada'] }}, 'rechazada')"
                                                class="text-red-600 hover:text-red-900 text-xs bg-red-100 px-2 py-1 rounded"
                                            >
                                                Rechazar
                                            </button>
                                        @endif
                                        
                                        @if(in_array($llamada['status'], ['pendiente', 'en_curso']))
                                            <button 
                                                wire:click="updateStatus({{ $llamada['id_llamada'] }}, 'finalizada')"
                                                class="text-gray-600 hover:text-gray-900 text-xs bg-gray-100 px-2 py-1 rounded"
                                            >
                                                Finalizar
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="bg-white rounded-lg shadow-md p-12 text-center">
            <div class="text-gray-500 mb-4">
                <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No hay llamadas registradas</h3>
            <p class="text-gray-500 mb-6">
                @if($groupCotizationId)
                    Haz clic en "Registrar Llamadas" para comenzar con el proceso de llamadas para este grupo de cotizaciones.
                @else
                    No se han registrado llamadas para esta cotización.
                @endif
            </p>
            
            @if($groupCotizationId)
                <button 
                    wire:click="registrarLlamadasParaGrupo"
                    class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-lg transition-colors duration-200"
                >
                    Registrar Llamadas
                </button>
            @endif
        </div>
    @endif
</div>
