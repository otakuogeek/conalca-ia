@extends('layout.app')

@section('title', 'Análisis de Llamadas')

@section('content')
<section class="w-full bg-[#F9F9F9] text-[#232323] min-h-screen p-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('analysis.show') }}" class="text-gray-400 hover:text-[#FF7C32] transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-3xl font-bold text-gray-800">Análisis de Llamadas ElevenLabs</h1>
        </div>
        <p class="text-gray-600 mb-4">Data analítica de llamadas por grupo de solicitud, conductores contactados y tasas de respuesta</p>

        <!-- Export with date range -->
        <form action="{{ route('analysis.calls.export') }}" method="GET" class="flex flex-wrap items-end gap-3 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Desde</label>
                <input type="date" name="from" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FF7C32] focus:border-transparent outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Hasta</label>
                <input type="date" name="to" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FF7C32] focus:border-transparent outline-none">
            </div>
            <button type="submit"
                class="inline-flex items-center gap-2 px-5 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Exportar Excel
            </button>
            <span class="text-xs text-gray-400 self-center">Sin fechas = exportar todo</span>
        </form>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-2xl font-bold text-blue-600">{{ $totalCalls }}</div>
            <div class="text-xs text-gray-500 mt-1">Conductores Contactados</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-2xl font-bold text-indigo-600">{{ $totalLlamadas }}</div>
            <div class="text-xs text-gray-500 mt-1">Llamadas Realizadas</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-2xl font-bold text-green-600">{{ $completedCalls }}</div>
            <div class="text-xs text-gray-500 mt-1">Completadas</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-2xl font-bold text-yellow-600">{{ $inProgressCalls }}</div>
            <div class="text-xs text-gray-500 mt-1">En Progreso</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-2xl font-bold text-red-600">{{ $failedCalls }}</div>
            <div class="text-xs text-gray-500 mt-1">Fallidas</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-2xl font-bold text-purple-600">{{ $elevenlabsCalls }}</div>
            <div class="text-xs text-gray-500 mt-1">Con ElevenLabs ID</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Estado de Llamadas (Donut) -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Distribución por Estado</h2>
            <div class="flex items-center justify-center" style="height: 280px;">
                <canvas id="statusDonut"></canvas>
            </div>
        </div>

        <!-- Llamadas por Día (Line) -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Llamadas por Día (últimos 30 días)</h2>
            <div style="height: 280px;">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Análisis por Grupo de Solicitud -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8 overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-800">Llamadas por Grupo de Solicitud</h2>
            <p class="text-sm text-gray-500 mt-1">Cada grupo representa una solicitud de cotización con sus llamadas asociadas</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Grupo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Cliente</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tipo</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Total</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Conductores</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Completadas</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">En Progreso</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Fallidas</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Pendientes</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Disponibles</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Tasa</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Última</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Llamadas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($groupAnalysis as $group)
                    <tr class="hover:bg-gray-50 transition-colors cursor-pointer group-row" data-group-id="{{ $group->group_cotization_id }}" onclick="toggleGroupCalls({{ $group->group_cotization_id }}, this)">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-gray-400 transition-transform group-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                <div>
                                    <span class="font-semibold text-blue-600">#{{ $group->group_cotization_id }}</span>
                                    @if($group->group_ref)
                                        <div class="text-xs text-gray-400 truncate max-w-[150px]" title="{{ $group->group_ref }}">{{ $group->group_ref }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="truncate max-w-[180px]" title="{{ $group->client_name }}">{{ $group->client_name ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($group->group_type)
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-50 text-blue-700">{{ $group->group_type }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center font-bold">{{ $group->total_llamadas }}</td>
                        <td class="px-4 py-3 text-center">{{ $group->conductores_unicos }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 text-xs rounded-full {{ $group->completadas > 0 ? 'bg-green-50 text-green-700' : 'text-gray-400' }}">{{ $group->completadas }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 text-xs rounded-full {{ $group->en_progreso > 0 ? 'bg-yellow-50 text-yellow-700' : 'text-gray-400' }}">{{ $group->en_progreso }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 text-xs rounded-full {{ $group->fallidas > 0 ? 'bg-red-50 text-red-700' : 'text-gray-400' }}">{{ $group->fallidas }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-gray-500">{{ $group->pendientes }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-emerald-600 font-medium">{{ $group->disponibles }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $tasa = $group->total_llamadas > 0 ? round(($group->completadas / $group->total_llamadas) * 100, 1) : 0;
                            @endphp
                            <div class="flex items-center justify-center gap-1">
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ $tasa > 50 ? 'bg-green-500' : ($tasa > 20 ? 'bg-yellow-500' : 'bg-red-500') }}" style="width: {{ min($tasa, 100) }}%"></div>
                                </div>
                                <span class="text-xs font-medium">{{ $tasa }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">
                            {{ $group->ultima_llamada ? \Carbon\Carbon::parse($group->ultima_llamada)->format('d/m/Y H:i') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button onclick="event.stopPropagation(); toggleGroupCalls({{ $group->group_cotization_id }}, this.closest('tr'))"
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-xs font-medium transition-colors shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Ver
                            </button>
                        </td>
                    </tr>
                    <!-- Expandable calls detail row -->
                    <tr class="group-calls-row hidden" id="group-calls-{{ $group->group_cotization_id }}">
                        <td colspan="13" class="p-0">
                            <div class="bg-blue-50/50 border-t border-b border-blue-100 px-6 py-4">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-bold text-gray-700">
                                        Llamadas del Grupo #{{ $group->group_cotization_id }}
                                    </h4>
                                    <span class="text-xs text-gray-400 group-calls-loading">Cargando...</span>
                                </div>
                                <div class="group-calls-content" id="group-calls-content-{{ $group->group_cotization_id }}">
                                    <div class="text-center py-4">
                                        <svg class="animate-spin h-6 w-6 text-blue-500 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13" class="px-4 py-8 text-center text-gray-400">No hay datos de llamadas por grupo</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top 10 Conductores más contactados -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-800">Top 10 Conductores Más Contactados</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Conductor</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Teléfono</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Llamadas</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Respondió</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Tasa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($topDrivers as $driver)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $driver->nombre_conductor }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $driver->telefono }}</td>
                            <td class="px-4 py-3 text-center font-bold">{{ $driver->total_llamadas }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 text-xs rounded-full {{ $driver->completadas > 0 ? 'bg-green-50 text-green-700' : 'bg-gray-50 text-gray-500' }}">
                                    {{ $driver->completadas }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-xs font-bold {{ $driver->tasa_respuesta > 50 ? 'text-green-600' : ($driver->tasa_respuesta > 0 ? 'text-yellow-600' : 'text-red-500') }}">
                                    {{ $driver->tasa_respuesta }}%
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Respuestas ElevenLabs -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-800">Respuestas de Conductores (ElevenLabs)</h2>
                <p class="text-sm text-gray-500 mt-1">Detalle de llamadas con respuesta registrada</p>
            </div>
            @if($driverResponses->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Conductor</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Vehículo</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Estado</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Respuesta</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Duración</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($driverResponses as $resp)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $resp->driver_name }}</div>
                                <div class="text-xs text-gray-400">{{ $resp->driver_phone }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                {{ $resp->vehicle_type }} {{ $resp->vehicle_plate ? '('.$resp->vehicle_plate.')' : '' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $statusColors = [
                                        'answered' => 'bg-green-50 text-green-700',
                                        'no_answer' => 'bg-red-50 text-red-700',
                                        'busy' => 'bg-yellow-50 text-yellow-700',
                                        'initiated' => 'bg-blue-50 text-blue-700',
                                        'failed' => 'bg-red-50 text-red-700',
                                    ];
                                    $color = $statusColors[$resp->call_status] ?? 'bg-gray-50 text-gray-700';
                                @endphp
                                <span class="px-2 py-1 text-xs rounded-full {{ $color }}">{{ $resp->call_status ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $respColors = [
                                        'accepted' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                    ];
                                    $rColor = $respColors[$resp->response_status] ?? 'bg-gray-100 text-gray-800';
                                    $respLabels = [
                                        'accepted' => 'ACEPTÓ',
                                        'rejected' => 'RECHAZÓ',
                                        'pending' => 'PENDIENTE',
                                    ];
                                    $rLabel = $respLabels[$resp->response_status] ?? $resp->response_status;
                                @endphp
                                <span class="px-2 py-1 text-xs font-bold rounded-full {{ $rColor }}">{{ $rLabel }}</span>
                            </td>
                            <td class="px-4 py-3 text-center text-xs">
                                {{ $resp->call_duration ? $resp->call_duration.'s' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{ $resp->created_at ? \Carbon\Carbon::parse($resp->created_at)->format('d/m/Y H:i') : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="p-8 text-center text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                <p>No hay respuestas registradas aún</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Detalle completo de conductores -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-8">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="text-lg font-bold text-gray-800">Detalle de Conductores Llamados</h2>
                <p class="text-sm text-gray-500 mt-1">Todos los conductores contactados, cantidad de veces y si respondieron</p>
            </div>
            <div class="flex items-center gap-3">
                <input type="text" id="searchDriver" placeholder="Buscar conductor..."
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FF7C32] focus:border-transparent outline-none">
                <span class="text-xs text-gray-400" id="driversCount"></span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="driversTable">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Conductor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Teléfono</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Vehículo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Ciudad</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Ruta</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Veces Llamado</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Respondió</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">No Respondió</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Disponible</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Grupos</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Última</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($driverDetails as $d)
                    <tr class="hover:bg-gray-50 transition-colors driver-row">
                        <td class="px-4 py-3 font-medium driver-name">{{ $d->nombre_conductor }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $d->telefono }}</td>
                        <td class="px-4 py-3 text-xs">{{ $d->tipo_vehiculo ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $d->ciudad_actual ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs">
                            @if($d->ciudad_origen && $d->ciudad_destino)
                                {{ $d->ciudad_origen }} → {{ $d->ciudad_destino }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 text-xs font-bold rounded-full bg-blue-50 text-blue-700">{{ $d->veces_llamado }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($d->respondio > 0)
                                <span class="px-2 py-1 text-xs font-bold rounded-full bg-green-100 text-green-800">SÍ ({{ $d->respondio }})</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-500">NO</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs {{ $d->no_respondio > 0 ? 'text-red-500 font-medium' : 'text-gray-400' }}">{{ $d->no_respondio }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($d->veces_disponible > 0)
                                <span class="text-green-600 font-bold">{{ $d->veces_disponible }}</span>
                            @else
                                <span class="text-gray-400">0</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">
                            @if($d->grupos)
                                @foreach(explode(',', $d->grupos) as $gId)
                                    <span class="inline-block px-1.5 py-0.5 bg-gray-100 rounded text-gray-600 mr-1">#{{ $gId }}</span>
                                @endforeach
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">
                            {{ $d->ultima_llamada ? \Carbon\Carbon::parse($d->ultima_llamada)->format('d/m/Y H:i') : '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="px-4 py-8 text-center text-gray-400">No hay datos de conductores</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination Controls -->
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between" id="driversPagination">
            <div class="text-sm text-gray-500">
                Mostrando <span id="driversShowFrom">1</span>-<span id="driversShowTo">20</span> de <span id="driversTotal">0</span> conductores
            </div>
            <div class="flex items-center gap-1" id="driversPaginationBtns">
            </div>
        </div>
    </div>

    <!-- Transcript Modal -->
    <div id="transcriptModal" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeTranscriptModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[80vh] flex flex-col relative">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Transcripción de Llamada</h3>
                        <p class="text-sm text-gray-500" id="transcriptModalSubtitle"></p>
                    </div>
                    <button onclick="closeTranscriptModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <!-- Modal Body -->
                <div class="px-6 py-4 overflow-y-auto flex-1" id="transcriptModalBody">
                    <div class="text-center py-8">
                        <svg class="animate-spin h-8 w-8 text-blue-500 mx-auto mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-gray-400">Cargando transcripción...</p>
                    </div>
                </div>
                <!-- Modal Footer -->
                <div class="px-6 py-3 border-t border-gray-100 flex justify-end flex-shrink-0">
                    <button onclick="closeTranscriptModal()" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Status Donut Chart
    const statusData = @json($statusDistribution);
    const statusLabels = {
        'pendiente': 'Pendiente',
        'en_progreso': 'En Progreso',
        'completada': 'Completada',
        'fallida': 'Fallida',
        'cancelada': 'Cancelada'
    };
    const statusColors = {
        'pendiente': '#94a3b8',
        'en_progreso': '#f59e0b',
        'completada': '#22c55e',
        'fallida': '#ef4444',
        'cancelada': '#6b7280'
    };

    if (document.getElementById('statusDonut')) {
        new Chart(document.getElementById('statusDonut'), {
            type: 'doughnut',
            data: {
                labels: statusData.map(s => statusLabels[s.estado_llamada] || s.estado_llamada),
                datasets: [{
                    data: statusData.map(s => s.total),
                    backgroundColor: statusData.map(s => statusColors[s.estado_llamada] || '#94a3b8'),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true } }
                },
                cutout: '60%'
            }
        });
    }

    // Daily Calls Line Chart
    const dailyData = @json($dailyCalls);
    if (document.getElementById('dailyChart') && dailyData.length > 0) {
        new Chart(document.getElementById('dailyChart'), {
            type: 'line',
            data: {
                labels: dailyData.map(d => {
                    const date = new Date(d.fecha);
                    return date.toLocaleDateString('es-CO', { day: '2-digit', month: 'short' });
                }),
                datasets: [
                    {
                        label: 'Total',
                        data: dailyData.map(d => d.total),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3
                    },
                    {
                        label: 'Completadas',
                        data: dailyData.map(d => d.completadas),
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3
                    },
                    {
                        label: 'Fallidas',
                        data: dailyData.map(d => d.fallidas),
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.05)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true } }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    // Search filter for drivers table
    const searchInput = document.getElementById('searchDriver');
    const allDriverRows = Array.from(document.querySelectorAll('.driver-row'));
    let filteredRows = [...allDriverRows];
    const PAGE_SIZE = 20;
    let currentPage = 1;

    function paginateDrivers() {
        // Hide all rows first
        allDriverRows.forEach(r => r.style.display = 'none');

        const totalFiltered = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(totalFiltered / PAGE_SIZE));
        if (currentPage > totalPages) currentPage = totalPages;

        const start = (currentPage - 1) * PAGE_SIZE;
        const end = Math.min(start + PAGE_SIZE, totalFiltered);

        // Show only current page rows
        for (let i = start; i < end; i++) {
            filteredRows[i].style.display = '';
        }

        // Update info text
        document.getElementById('driversShowFrom').textContent = totalFiltered > 0 ? start + 1 : 0;
        document.getElementById('driversShowTo').textContent = end;
        document.getElementById('driversTotal').textContent = totalFiltered;
        if (document.getElementById('driversCount')) {
            document.getElementById('driversCount').textContent = totalFiltered + ' conductores';
        }

        // Render pagination buttons
        const btnContainer = document.getElementById('driversPaginationBtns');
        btnContainer.innerHTML = '';

        if (totalPages <= 1) return;

        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.innerHTML = '&laquo; Ant';
        prevBtn.className = 'px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ' + (currentPage <= 1 ? 'bg-gray-100 text-gray-300 cursor-not-allowed' : 'bg-gray-100 text-gray-600 hover:bg-gray-200');
        prevBtn.disabled = currentPage <= 1;
        prevBtn.onclick = function() { if (currentPage > 1) { currentPage--; paginateDrivers(); } };
        btnContainer.appendChild(prevBtn);

        // Page numbers (show max 7 pages)
        let startPage = Math.max(1, currentPage - 3);
        let endPage = Math.min(totalPages, startPage + 6);
        if (endPage - startPage < 6) startPage = Math.max(1, endPage - 6);

        for (let p = startPage; p <= endPage; p++) {
            const btn = document.createElement('button');
            btn.textContent = p;
            btn.className = 'px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ' + (p === currentPage ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200');
            btn.onclick = (function(page) { return function() { currentPage = page; paginateDrivers(); }; })(p);
            btnContainer.appendChild(btn);
        }

        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.innerHTML = 'Sig &raquo;';
        nextBtn.className = 'px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ' + (currentPage >= totalPages ? 'bg-gray-100 text-gray-300 cursor-not-allowed' : 'bg-gray-100 text-gray-600 hover:bg-gray-200');
        nextBtn.disabled = currentPage >= totalPages;
        nextBtn.onclick = function() { if (currentPage < totalPages) { currentPage++; paginateDrivers(); } };
        btnContainer.appendChild(nextBtn);
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            if (term === '') {
                filteredRows = [...allDriverRows];
            } else {
                filteredRows = allDriverRows.filter(row => {
                    const name = row.querySelector('.driver-name')?.textContent?.toLowerCase() || '';
                    return name.includes(term);
                });
            }
            currentPage = 1;
            paginateDrivers();
        });
    }

    // Initialize pagination
    paginateDrivers();
});

// Group calls expand/collapse
const groupCallsCache = {};

function toggleGroupCalls(groupId, rowEl) {
    const detailRow = document.getElementById('group-calls-' + groupId);
    const chevron = rowEl.querySelector('.group-chevron');

    if (!detailRow) return;

    if (detailRow.classList.contains('hidden')) {
        detailRow.classList.remove('hidden');
        if (chevron) chevron.style.transform = 'rotate(90deg)';
        loadGroupCalls(groupId);
    } else {
        detailRow.classList.add('hidden');
        if (chevron) chevron.style.transform = 'rotate(0deg)';
    }
}

function loadGroupCalls(groupId) {
    if (groupCallsCache[groupId]) {
        renderGroupCalls(groupId, groupCallsCache[groupId]);
        return;
    }

    const container = document.getElementById('group-calls-content-' + groupId);
    container.innerHTML = `
        <div class="text-center py-4">
            <svg class="animate-spin h-6 w-6 text-blue-500 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>`;

    fetch('/analysis/calls/group/' + groupId)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                groupCallsCache[groupId] = data.calls;
                renderGroupCalls(groupId, data.calls);
            } else {
                container.innerHTML = '<p class="text-center text-gray-400 py-4">No se pudieron cargar las llamadas</p>';
            }
        })
        .catch(() => {
            container.innerHTML = '<p class="text-center text-red-400 py-4">Error cargando llamadas</p>';
        });
}

function renderGroupCalls(groupId, calls) {
    const container = document.getElementById('group-calls-content-' + groupId);
    if (!calls || calls.length === 0) {
        container.innerHTML = '<p class="text-center text-gray-400 py-4">No hay llamadas registradas para este grupo</p>';
        return;
    }

    const statusColors = {
        'completed': 'bg-green-100 text-green-800',
        'answered': 'bg-green-100 text-green-800',
        'completada': 'bg-green-100 text-green-800',
        'failed': 'bg-red-100 text-red-800',
        'fallida': 'bg-red-100 text-red-800',
        'no_answer': 'bg-orange-100 text-orange-800',
        'busy': 'bg-yellow-100 text-yellow-800',
        'initiated': 'bg-blue-100 text-blue-800',
        'en_progreso': 'bg-yellow-100 text-yellow-800',
        'pendiente': 'bg-gray-100 text-gray-600',
    };

    let html = `
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr class="bg-blue-100/50">
                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Conductor</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Teléfono</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Vehículo</th>
                    <th class="px-3 py-2 text-center font-semibold text-gray-600">Estado</th>
                    <th class="px-3 py-2 text-center font-semibold text-gray-600">Disponible</th>
                    <th class="px-3 py-2 text-center font-semibold text-gray-600">Duración</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Fecha</th>
                    <th class="px-3 py-2 text-center font-semibold text-gray-600">Transcripción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-blue-100/30">`;

    calls.forEach(call => {
        const estado = call.estado_llamada || call.call_status || 'pendiente';
        const colorClass = statusColors[estado] || 'bg-gray-100 text-gray-600';
        const duracion = call.talk_duration_seconds ? call.talk_duration_seconds + 's' : '—';
        const fecha = call.created_at ? new Date(call.created_at).toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';
        const hasTranscript = call.transcript ? true : false;
        const convId = call.elevenlabs_conversation_id || '';
        const llamadaId = call.id_llamada || '';

        html += `
            <tr class="hover:bg-blue-50/50">
                <td class="px-3 py-2 font-medium">${call.nombre_conductor || '—'}</td>
                <td class="px-3 py-2 text-gray-500">${call.telefono || '—'}</td>
                <td class="px-3 py-2">${call.tipo_vehiculo || '—'} ${call.placa ? '(' + call.placa + ')' : ''}</td>
                <td class="px-3 py-2 text-center">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium ${colorClass}">${estado}</span>
                </td>
                <td class="px-3 py-2 text-center">
                    ${call.disponible == 1 ? '<span class="text-green-600 font-bold">SÍ</span>' : '<span class="text-gray-400">NO</span>'}
                </td>
                <td class="px-3 py-2 text-center">${duracion}</td>
                <td class="px-3 py-2 text-gray-500">${fecha}</td>
                <td class="px-3 py-2 text-center">`;

        if (hasTranscript) {
            html += `<button onclick="event.stopPropagation(); showTranscriptFromCache('${escapeHtml(call.nombre_conductor || 'Conductor')}', '${call.telefono || ''}', \`${escapeForTemplate(call.transcript)}\`)"
                class="inline-flex items-center gap-1 px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-md text-xs font-medium transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Ver
            </button>`;
        } else if (llamadaId) {
            html += `<button onclick="event.stopPropagation(); fetchAndShowTranscript('${llamadaId}', '${escapeHtml(call.nombre_conductor || 'Conductor')}', '${call.telefono || ''}')"
                class="inline-flex items-center gap-1 px-2 py-1 bg-gray-200 hover:bg-gray-300 text-gray-600 rounded-md text-xs font-medium transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Cargar
            </button>`;
        } else {
            html += '<span class="text-gray-300">—</span>';
        }

        html += `</td></tr>`;
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function escapeForTemplate(text) {
    if (!text) return '';
    return text.replace(/\\/g, '\\\\').replace(/`/g, '\\`').replace(/\$/g, '\\$');
}

// Transcript modal functions
function showTranscriptFromCache(conductor, telefono, transcript) {
    document.getElementById('transcriptModalSubtitle').textContent = conductor + (telefono ? ' — ' + telefono : '');
    document.getElementById('transcriptModalBody').innerHTML = formatTranscript(transcript);
    document.getElementById('transcriptModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function fetchAndShowTranscript(llamadaId, conductor, telefono) {
    document.getElementById('transcriptModalSubtitle').textContent = conductor + (telefono ? ' — ' + telefono : '');
    document.getElementById('transcriptModalBody').innerHTML = `
        <div class="text-center py-8">
            <svg class="animate-spin h-8 w-8 text-blue-500 mx-auto mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-gray-400">Obteniendo transcripción de ElevenLabs...</p>
        </div>`;
    document.getElementById('transcriptModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    fetch('/analysis/calls/transcript/' + llamadaId)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.transcript) {
                document.getElementById('transcriptModalBody').innerHTML = formatTranscript(data.transcript);
            } else {
                document.getElementById('transcriptModalBody').innerHTML = `
                    <div class="text-center py-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-gray-400">${data.message || 'No hay transcripción disponible'}</p>
                    </div>`;
            }
        })
        .catch(() => {
            document.getElementById('transcriptModalBody').innerHTML = '<p class="text-center text-red-400 py-8">Error obteniendo transcripción</p>';
        });
}

function formatTranscript(text) {
    if (!text) return '<p class="text-gray-400 text-center py-8">Sin transcripción</p>';

    const lines = text.split('\n').filter(l => l.trim());
    let html = '<div class="space-y-3">';

    lines.forEach(line => {
        const agentMatch = line.match(/^\[Agente\]:\s*(.+)/);
        const driverMatch = line.match(/^\[Conductor\]:\s*(.+)/);

        if (agentMatch) {
            html += `<div class="flex gap-3">
                <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="bg-blue-50 rounded-lg px-4 py-2 max-w-[85%]">
                    <div class="text-xs font-bold text-blue-600 mb-1">Agente IA</div>
                    <div class="text-sm text-gray-700">${escapeHtml(agentMatch[1])}</div>
                </div>
            </div>`;
        } else if (driverMatch) {
            html += `<div class="flex gap-3 justify-end">
                <div class="bg-green-50 rounded-lg px-4 py-2 max-w-[85%]">
                    <div class="text-xs font-bold text-green-600 mb-1 text-right">Conductor</div>
                    <div class="text-sm text-gray-700">${escapeHtml(driverMatch[1])}</div>
                </div>
                <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            </div>`;
        } else {
            html += `<div class="px-4 py-2 text-sm text-gray-600">${escapeHtml(line)}</div>`;
        }
    });

    html += '</div>';
    return html;
}

function closeTranscriptModal() {
    document.getElementById('transcriptModal').classList.add('hidden');
    document.body.style.overflow = '';
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeTranscriptModal();
});
</script>
@endpush
