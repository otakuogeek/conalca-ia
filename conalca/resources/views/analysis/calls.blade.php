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
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($groupAnalysis as $group)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <span class="font-semibold text-blue-600">#{{ $group->group_cotization_id }}</span>
                            @if($group->group_ref)
                                <div class="text-xs text-gray-400 truncate max-w-[150px]" title="{{ $group->group_ref }}">{{ $group->group_ref }}</div>
                            @endif
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
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="px-4 py-8 text-center text-gray-400">No hay datos de llamadas por grupo</td>
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
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-800">Detalle de Conductores Llamados</h2>
                <p class="text-sm text-gray-500 mt-1">Todos los conductores contactados, cantidad de veces y si respondieron</p>
            </div>
            <div class="flex items-center gap-2">
                <input type="text" id="searchDriver" placeholder="Buscar conductor..."
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FF7C32] focus:border-transparent outline-none">
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
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            document.querySelectorAll('.driver-row').forEach(row => {
                const name = row.querySelector('.driver-name')?.textContent?.toLowerCase() || '';
                row.style.display = name.includes(term) ? '' : 'none';
            });
        });
    }
});
</script>
@endpush
