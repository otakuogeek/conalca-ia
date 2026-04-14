@extends('layout.app')

@section('title', 'Auditoría de Llamadas')

@section('content')
<style>
    /* Audio Player */
    .audio-player{background:linear-gradient(135deg,#1e1b4b,#312e81);border-radius:16px;padding:20px 24px;color:#fff;max-width:420px;box-shadow:0 8px 32px rgba(0,0,0,.18)}
    .audio-player .ap-title{font-size:13px;font-weight:600;opacity:.8;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .audio-player .ap-subtitle{font-size:11px;opacity:.5}
    .audio-player .ap-wave{height:40px;display:flex;align-items:center;gap:2px;margin:12px 0}
    .audio-player .ap-wave .bar{width:3px;border-radius:2px;background:rgba(255,255,255,.25);transition:background .15s,height .15s}
    .audio-player .ap-wave .bar.active{background:#818cf8}
    .audio-player .ap-wave .bar.played{background:#a78bfa}
    .audio-player .ap-controls{display:flex;align-items:center;gap:16px;justify-content:center;margin:8px 0}
    .audio-player .ap-btn{background:none;border:none;color:#fff;cursor:pointer;opacity:.7;transition:opacity .15s,transform .15s;padding:4px}
    .audio-player .ap-btn:hover{opacity:1;transform:scale(1.12)}
    .audio-player .ap-btn-play{width:48px;height:48px;background:linear-gradient(135deg,#8b5cf6,#6366f1);border-radius:50%;display:flex;align-items:center;justify-content:center;opacity:1;box-shadow:0 4px 16px rgba(99,102,241,.4)}
    .audio-player .ap-btn-play:hover{transform:scale(1.08);box-shadow:0 6px 24px rgba(99,102,241,.5)}
    .audio-player .ap-progress{position:relative;height:4px;background:rgba(255,255,255,.15);border-radius:4px;cursor:pointer;margin:8px 0}
    .audio-player .ap-progress-bar{height:100%;background:linear-gradient(90deg,#8b5cf6,#a78bfa);border-radius:4px;transition:width .1s linear}
    .audio-player .ap-progress-thumb{position:absolute;top:50%;width:14px;height:14px;background:#fff;border-radius:50%;transform:translate(-50%,-50%);box-shadow:0 2px 8px rgba(0,0,0,.3);cursor:grab;transition:transform .1s}
    .audio-player .ap-progress-thumb:hover{transform:translate(-50%,-50%) scale(1.25)}
    .audio-player .ap-time{display:flex;justify-content:space-between;font-size:11px;opacity:.5;margin-top:4px}
    .audio-player .ap-speed{font-size:11px;padding:3px 8px;border-radius:12px;background:rgba(255,255,255,.12);border:none;color:#fff;cursor:pointer}
    .audio-player .ap-speed:hover{background:rgba(255,255,255,.22)}

    /* Call Cards */
    .call-card{background:#fff;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden;transition:all .2s}
    .call-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.06);border-color:#c7d2fe}
    .call-card .cc-header{padding:14px 16px;display:flex;align-items:center;gap:12px;border-bottom:1px solid #f3f4f6}
    .call-card .cc-avatar{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px;color:#fff;flex-shrink:0}
    .call-card .cc-body{padding:14px 16px}
    .call-card .cc-footer{padding:10px 16px;background:#fafafa;border-top:1px solid #f3f4f6;display:flex;align-items:center;gap:8px;flex-wrap:wrap}

    /* Flow Steps */
    .flow-step{display:flex;align-items:center;gap:6px;font-size:11px;padding:4px 10px;border-radius:999px;font-weight:500}
    .flow-step.done{background:#dcfce7;color:#166534}
    .flow-step.active{background:#dbeafe;color:#1e40af;animation:pulse-soft 2s infinite}
    .flow-step.error{background:#fee2e2;color:#991b1b}
    .flow-step.pending{background:#f3f4f6;color:#6b7280}
    .flow-connector{width:20px;height:2px;background:#d1d5db;flex-shrink:0}
    .flow-connector.done{background:#86efac}

    @keyframes pulse-soft{0%,100%{opacity:1}50%{opacity:.65}}

    /* KPI ring */
    .kpi-ring{position:relative;display:inline-flex;align-items:center;justify-content:center}
    .kpi-ring svg{transform:rotate(-90deg)}
    .kpi-ring .kpi-value{position:absolute;font-weight:700;font-size:18px}

    /* Sanity Badge */
    .sanity-score{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;font-weight:600;font-size:12px}
    .sanity-good{background:#dcfce7;color:#166534}
    .sanity-warn{background:#fef3c7;color:#92400e}
    .sanity-bad{background:#fee2e2;color:#991b1b}
</style>

<section class="w-full bg-[#F9F9F9] text-[#232323] min-h-screen p-4 md:p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-1">
            <a href="{{ route('analysis.calls') }}" class="text-gray-400 hover:text-[#FF7C32] transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Auditoría de Llamadas</h1>
                <p class="text-sm text-gray-500">Control de lotes, flujos, transcripciones, audio y resultados</p>
            </div>
            <div class="ml-auto flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold {{ $kpis['provider'] === 'twilio' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                    <span class="w-2 h-2 rounded-full {{ $kpis['provider'] === 'twilio' ? 'bg-purple-500' : 'bg-blue-500' }}"></span>
                    {{ strtoupper($kpis['provider']) }}
                </span>
                <a href="{{ route('auditoria.llamadas.export-csv', request()->only(['from', 'to'])) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition-colors shadow-sm">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    Exportar CSV
                </a>
            </div>
        </div>

        <!-- Filtros + Cola en línea -->
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <form method="GET" action="{{ route('auditoria.llamadas') }}" class="flex items-center gap-2 bg-white rounded-xl border border-gray-200 px-3 py-2 shadow-sm">
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="px-2 py-1 border-0 text-sm focus:ring-0 outline-none bg-transparent w-32">
                <span class="text-gray-300">—</span>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="px-2 py-1 border-0 text-sm focus:ring-0 outline-none bg-transparent w-32">
                <button type="submit" class="px-3 py-1 bg-[#FF7C32] text-white text-xs font-medium rounded-lg hover:bg-[#e06a28] transition-colors">Filtrar</button>
                @if(($filters['from'] ?? null) || ($filters['to'] ?? null))
                <a href="{{ route('auditoria.llamadas') }}" class="text-xs text-gray-400 hover:text-[#FF7C32]">✕</a>
                @endif
            </form>

            <!-- Mini cola en tiempo real -->
            <div class="flex items-center gap-3 bg-white rounded-xl border border-gray-200 px-4 py-2 shadow-sm">
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse" id="qs-dot-processing" style="display:{{ ($queueActivity['processing'] ?? 0) > 0 ? '' : 'none' }}"></span>
                    <span class="text-xs text-gray-500">Procesando: <strong class="text-blue-600" id="qs-processing">{{ $queueActivity['processing'] ?? 0 }}</strong></span>
                </div>
                <div class="w-px h-4 bg-gray-200"></div>
                <span class="text-xs text-gray-500">Cola: <strong class="text-yellow-600" id="qs-pending">{{ $queueActivity['pending'] ?? 0 }}</strong></span>
                <div class="w-px h-4 bg-gray-200"></div>
                <span class="text-xs text-gray-500">Jobs: <strong class="text-purple-600" id="qs-jobs">{{ $queueActivity['active_jobs'] ?? 0 }}</strong></span>
                <div class="w-px h-4 bg-gray-200"></div>
                <span class="text-xs text-gray-500">Fallidos: <strong class="text-red-600" id="qs-failed">{{ $queueActivity['failed_jobs'] ?? 0 }}</strong></span>
                <div class="w-px h-4 bg-gray-200"></div>
                <span class="text-xs text-gray-500">Max: <strong class="text-gray-600" id="qs-max">{{ \App\Services\CallQueueManager::getMaxConcurrentCalls() }}</strong></span>
                <button onclick="refreshQueueStatus()" class="ml-1 p-1 text-gray-400 hover:text-blue-600 rounded transition-colors" title="Actualizar">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                </button>
                <button onclick="toggleAutoRefresh(this)" class="p-1 rounded transition-colors bg-gray-100 text-gray-500" title="Auto-refresh desactivado">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- KPIs con anillos visuales -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center">
                <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
            </div>
            <div>
                <div class="text-xl font-bold text-gray-800">{{ $kpis['total_groups'] }}</div>
                <div class="text-[11px] text-gray-400">Lotes</div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-indigo-50 flex items-center justify-center">
                <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </div>
            <div>
                <div class="text-xl font-bold text-gray-800">{{ $kpis['total_conductores'] }}</div>
                <div class="text-[11px] text-gray-400">Conductores</div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-green-50 flex items-center justify-center">
                <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <div class="text-xl font-bold text-green-600">{{ $kpis['total_aceptaron'] }}</div>
                <div class="text-[11px] text-gray-400">Aceptaron</div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-red-50 flex items-center justify-center">
                <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </div>
            <div>
                <div class="text-xl font-bold text-red-600">{{ $kpis['total_rechazaron'] }}</div>
                <div class="text-[11px] text-gray-400">Rechazaron</div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-yellow-50 flex items-center justify-center">
                <svg class="h-5 w-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <div class="text-xl font-bold text-yellow-600">{{ $kpis['total_sin_decision'] }}</div>
                <div class="text-[11px] text-gray-400">Sin decisión</div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-purple-50 flex items-center justify-center">
                <svg class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <div class="text-xl font-bold text-purple-600">{{ $kpis['promedio_duracion_global'] }}s</div>
                <div class="text-[11px] text-gray-400">Durac. Prom.</div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
            <div class="kpi-ring">
                <svg width="48" height="48"><circle cx="24" cy="24" r="20" fill="none" stroke="#e5e7eb" stroke-width="4"/><circle cx="24" cy="24" r="20" fill="none" stroke="#3b82f6" stroke-width="4" stroke-dasharray="{{ $kpis['tasa_contacto'] * 1.256 }} 125.6" stroke-linecap="round"/></svg>
                <span class="kpi-value text-sm text-blue-600">{{ $kpis['tasa_contacto'] }}%</span>
            </div>
            <div>
                <div class="text-[11px] text-gray-400">Contacto</div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
            <div class="kpi-ring">
                <svg width="48" height="48"><circle cx="24" cy="24" r="20" fill="none" stroke="#e5e7eb" stroke-width="4"/><circle cx="24" cy="24" r="20" fill="none" stroke="#22c55e" stroke-width="4" stroke-dasharray="{{ $kpis['tasa_exito'] * 1.256 }} 125.6" stroke-linecap="round"/></svg>
                <span class="kpi-value text-sm text-green-600">{{ $kpis['tasa_exito'] }}%</span>
            </div>
            <div>
                <div class="text-[11px] text-gray-400">Éxito</div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════ GRÁFICOS GLOBALES ══════════════════════ -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <!-- Distribución de Resultados (Doughnut) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                    <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" /></svg>
                </div>
                <h3 class="text-sm font-bold text-gray-700">Distribución de Resultados</h3>
            </div>
            <div class="flex items-center justify-center" style="height:260px">
                <canvas id="chart-resultados"></canvas>
            </div>
        </div>

        <!-- Rendimiento por Lote (Bar) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                    <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                </div>
                <h3 class="text-sm font-bold text-gray-700">Rendimiento por Lote</h3>
            </div>
            <div style="height:260px">
                <canvas id="chart-lotes"></canvas>
            </div>
        </div>

        <!-- Duración Promedio por Lote (Line) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center">
                    <svg class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <h3 class="text-sm font-bold text-gray-700">Duración Promedio de Llamadas</h3>
            </div>
            <div style="height:260px">
                <canvas id="chart-duracion"></canvas>
            </div>
        </div>

        <!-- Tasa de Contacto y Éxito por Ruta (Horizontal Bar) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center">
                    <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                </div>
                <h3 class="text-sm font-bold text-gray-700">Efectividad por Ruta</h3>
            </div>
            <div style="height:260px">
                <canvas id="chart-rutas"></canvas>
            </div>
        </div>
    </div>

    <!-- Errores Recientes -->
    @if($recentErrors->count() > 0)
    <div class="mb-6 bg-red-50 rounded-xl border border-red-200 p-4" x-data="{ showErrors: false }">
        <button @click="showErrors = !showErrors" class="w-full flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                <span class="text-sm font-bold text-red-700">{{ $recentErrors->count() }} errores en las últimas 24h</span>
            </div>
            <svg class="h-4 w-4 text-red-400 transition-transform" :class="showErrors && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
        </button>
        <div x-show="showErrors" x-collapse class="mt-3">
            <div class="space-y-2 max-h-48 overflow-y-auto">
                @foreach($recentErrors as $error)
                <div class="flex items-start gap-3 bg-white rounded-lg p-3 text-xs">
                    <span class="text-red-400 whitespace-nowrap font-mono">{{ \Carbon\Carbon::parse($error->created_at)->format('H:i:s') }}</span>
                    <span class="font-mono text-gray-600">{{ $error->numero_destino }}</span>
                    <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 whitespace-nowrap">{{ $error->call_status }}</span>
                    @if($error->sip_status_code)<span class="text-gray-400">SIP {{ $error->sip_status_code }}</span>@endif
                    <span class="text-red-600 flex-1 truncate" title="{{ $error->failure_reason }}">{{ $error->failure_reason }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Lotes -->
    <div class="space-y-3">
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-lg font-bold text-gray-800">Lotes de Llamadas</h2>
            <input type="text" id="search-batches" placeholder="Buscar grupo, cliente..."
                class="px-3 py-1.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#FF7C32] focus:border-transparent outline-none w-56 shadow-sm">
        </div>

        @forelse($batches as $batch)
        @php
            $successRate = $batch->total_conductores > 0 ? min(100, round(($batch->aceptaron_viaje / $batch->total_conductores) * 100)) : 0;
            $sinDecision = $batch->sin_decision ?? 0;
            $sanityClass = $batch->con_errores > 0 ? 'sanity-bad' : ($sinDecision > $batch->aceptaron_viaje ? 'sanity-warn' : ($successRate >= 30 ? 'sanity-good' : 'sanity-warn'));
            $sanityLabel = $batch->con_errores > 0 ? 'Con errores' : ($sinDecision > $batch->aceptaron_viaje ? 'Muchas sin decisión' : ($successRate >= 30 ? 'Saludable' : 'Bajo rendimiento'));
        @endphp
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden batch-row-card" data-search-text="{{ strtolower(($batch->grupo_referencia ?? '') . ' ' . ($batch->cliente_nombre ?? '') . ' ' . $batch->group_cotization_id . ' ' . ($batch->ruta_origen ?? '') . ' ' . ($batch->ruta_destino ?? '')) }}">
            <!-- Batch header -->
            <div class="px-5 py-4 cursor-pointer hover:bg-gray-50/50 transition-colors"
                 onclick="toggleBatchCard({{ $batch->group_cotization_id }})">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <svg class="h-5 w-5 text-gray-300 transform transition-transform duration-200 batch-chevron-{{ $batch->group_cotization_id }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-800">IDC{{ str_pad($batch->group_cotization_id, 6, '0', STR_PAD_LEFT) }}</span>
                                @if($batch->ruta_origen || $batch->ruta_destino)
                                    <span class="text-sm font-semibold text-indigo-600">{{ strtoupper($batch->ruta_origen ?? '?') }} → {{ strtoupper($batch->ruta_destino ?? '?') }}</span>
                                @endif
                                <span class="sanity-score {{ $sanityClass }}">{{ $sanityLabel }}</span>
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                {{ $batch->cliente_nombre ?? 'Cliente desconocido' }}
                                · {{ $batch->grupo_tipo ?? '' }}
                                · {{ $batch->primera_llamada ? \Carbon\Carbon::parse($batch->primera_llamada)->format('d/m/Y H:i') : '' }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-5 text-center">
                        <div>
                            <div class="text-sm font-bold text-gray-700">{{ $batch->total_conductores }}</div>
                            <div class="text-[10px] text-gray-400">Conductores</div>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-green-600">{{ $batch->aceptaron_viaje }}</div>
                            <div class="text-[10px] text-gray-400">Aceptaron</div>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-orange-600">{{ $batch->rechazaron_viaje }}</div>
                            <div class="text-[10px] text-gray-400">Rechazaron</div>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-blue-600">{{ $batch->contestadas }}</div>
                            <div class="text-[10px] text-gray-400">Contestadas</div>
                        </div>
                        @if(($batch->sin_decision ?? 0) > 0)
                        <div>
                            <div class="text-sm font-bold text-yellow-600">{{ $batch->sin_decision }}</div>
                            <div class="text-[10px] text-gray-400">Sin decisión</div>
                        </div>
                        @endif
                        @if($batch->con_errores > 0)
                        <div>
                            <div class="text-sm font-bold text-red-600">{{ $batch->con_errores }}</div>
                            <div class="text-[10px] text-gray-400">Errores</div>
                        </div>
                        @endif
                        <div>
                            <div class="text-sm font-bold text-purple-600">{{ $batch->con_transcripcion }}</div>
                            <div class="text-[10px] text-gray-400">Transcripc.</div>
                        </div>
                        <div class="hidden md:block">
                            <div class="text-sm font-bold text-gray-500">{{ $batch->promedio_duracion ? round($batch->promedio_duracion) . 's' : '—' }}</div>
                            <div class="text-[10px] text-gray-400">Durac. Prom.</div>
                        </div>
                        <!-- Mini progress bar -->
                        <div class="hidden lg:block w-24">
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden flex">
                                @if($batch->total_conductores > 0)
                                <div class="h-full bg-green-400" style="width:{{ min(100, ($batch->aceptaron_viaje / $batch->total_conductores) * 100) }}%"></div>
                                <div class="h-full bg-orange-400" style="width:{{ min(100 - min(100, ($batch->aceptaron_viaje / $batch->total_conductores) * 100), ($batch->rechazaron_viaje / $batch->total_conductores) * 100) }}%"></div>
                                <div class="h-full bg-yellow-400" style="width:{{ min(100, (($batch->sin_decision ?? 0) / $batch->total_conductores) * 100) }}%"></div>
                                <div class="h-full bg-red-400" style="width:{{ min(100, ($batch->con_errores / $batch->total_conductores) * 100) }}%"></div>
                                @endif
                            </div>
                            <div class="text-[9px] text-gray-400 mt-0.5 text-center">{{ $successRate }}% éxito</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Batch detail (hidden) -->
            <div class="hidden border-t border-gray-100" id="batch-detail-{{ $batch->group_cotization_id }}">
                <div class="p-5 bg-gray-50/50">
                    <!-- Mini charts for this batch -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4 batch-charts-{{ $batch->group_cotization_id }}" style="display:none">
                        <div class="bg-white rounded-lg border border-gray-100 p-3">
                            <div class="text-xs font-semibold text-gray-500 mb-2">Resultado de Llamadas</div>
                            <div style="height:140px"><canvas id="batch-chart-result-{{ $batch->group_cotization_id }}"></canvas></div>
                        </div>
                        <div class="bg-white rounded-lg border border-gray-100 p-3">
                            <div class="text-xs font-semibold text-gray-500 mb-2">Duración por Conductor</div>
                            <div style="height:140px"><canvas id="batch-chart-duration-{{ $batch->group_cotization_id }}"></canvas></div>
                        </div>
                        <div class="bg-white rounded-lg border border-gray-100 p-3">
                            <div class="text-xs font-semibold text-gray-500 mb-2">Timeline de Llamadas</div>
                            <div style="height:140px"><canvas id="batch-chart-timeline-{{ $batch->group_cotization_id }}"></canvas></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mb-4 batch-loading-{{ $batch->group_cotization_id }}">
                        <div class="animate-spin rounded-full h-4 w-4 border-2 border-[#FF7C32] border-t-transparent"></div>
                        <span class="text-sm text-gray-400">Cargando llamadas del lote...</span>
                    </div>
                    <div class="batch-content-{{ $batch->group_cotization_id }}"></div>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <svg class="h-16 w-16 mx-auto text-gray-200 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
            <p class="text-gray-400">No hay datos de llamadas para el periodo seleccionado</p>
        </div>
        @endforelse
    </div>
</section>

<!-- Modal Transcripción -->
<div id="transcript-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4" onclick="if(event.target===this)closeModal('transcript-modal')">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[85vh] flex flex-col overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-blue-50 to-indigo-50">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                    <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <h3 class="text-base font-bold text-gray-800">Transcripción</h3>
            </div>
            <button onclick="closeModal('transcript-modal')" class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1" id="transcript-content">
            <div class="text-center text-gray-400 py-8"><div class="animate-spin rounded-full h-8 w-8 border-2 border-[#FF7C32] border-t-transparent mx-auto mb-3"></div>Cargando...</div>
        </div>
    </div>
</div>

<!-- Modal Timeline -->
<div id="timeline-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4" onclick="if(event.target===this)closeModal('timeline-modal')">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[85vh] flex flex-col overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-purple-50 to-indigo-50">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                    <svg class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <h3 class="text-base font-bold text-gray-800">Flujo de la Llamada</h3>
            </div>
            <button onclick="closeModal('timeline-modal')" class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1" id="timeline-content">
            <div class="text-center text-gray-400 py-8"><div class="animate-spin rounded-full h-8 w-8 border-2 border-[#FF7C32] border-t-transparent mx-auto mb-3"></div>Cargando...</div>
        </div>
    </div>
</div>

<!-- Modal Audio Player -->
<div id="audio-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4" onclick="if(event.target===this)closeAudioModal()">
    <div class="audio-player" id="audio-player-container" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between mb-1">
            <div class="flex-1 min-w-0">
                <div class="ap-title" id="ap-title">Cargando audio...</div>
                <div class="ap-subtitle" id="ap-subtitle">Auditoría de llamada</div>
            </div>
            <button onclick="closeAudioModal()" class="ap-btn ml-3">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <!-- Waveform visualization -->
        <div class="ap-wave" id="ap-wave"></div>

        <!-- Progress bar -->
        <div class="ap-progress" id="ap-progress" onclick="seekAudio(event)">
            <div class="ap-progress-bar" id="ap-progress-bar" style="width:0%"></div>
            <div class="ap-progress-thumb" id="ap-progress-thumb" style="left:0%"></div>
        </div>
        <div class="ap-time">
            <span id="ap-current">0:00</span>
            <span id="ap-duration">0:00</span>
        </div>

        <!-- Controls -->
        <div class="ap-controls">
            <button class="ap-btn" onclick="skipAudio(-10)" title="Retroceder 10s">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z" /></svg>
            </button>
            <button class="ap-btn" onclick="skipAudio(-5)" title="-5s">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M11.99 5V1l-5 5 5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6h-2c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/></svg>
            </button>
            <button class="ap-btn ap-btn-play" id="ap-play-btn" onclick="togglePlayAudio()">
                <svg class="h-6 w-6" id="ap-icon-play" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                <svg class="h-6 w-6 hidden" id="ap-icon-pause" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
            </button>
            <button class="ap-btn" onclick="skipAudio(5)" title="+5s">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12.01 5V1l5 5-5 5V7c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6h2c0 4.42-3.58 8-8 8s-8-3.58-8-8 3.58-8 8-8z"/></svg>
            </button>
            <button class="ap-btn" onclick="skipAudio(10)" title="Adelantar 10s">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z" /></svg>
            </button>
        </div>

        <!-- Speed + Volume -->
        <div class="flex items-center justify-between mt-2">
            <button class="ap-speed" id="ap-speed" onclick="cycleSpeed()">1x</button>
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 opacity-50" fill="currentColor" viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/></svg>
                <input type="range" id="ap-volume" min="0" max="100" value="80" class="w-16 h-1 accent-purple-400" oninput="setVolume(this.value)">
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
@php
$chartBatchesData = $batches->map(function($b) {
    return [
        'id' => $b->group_cotization_id,
        'label' => 'IDC' . str_pad($b->group_cotization_id, 6, '0', STR_PAD_LEFT),
        'ruta' => ($b->ruta_origen ?? '?') . ' → ' . ($b->ruta_destino ?? '?'),
        'total' => $b->total_conductores,
        'aceptaron' => $b->aceptaron_viaje,
        'rechazaron' => $b->rechazaron_viaje,
        'sin_decision' => $b->sin_decision ?? 0,
        'errores' => $b->con_errores,
        'contestadas' => $b->contestadas,
        'sin_respuesta' => $b->sin_respuesta,
        'duracion_prom' => round($b->promedio_duracion ?? 0),
        'ruta_origen' => $b->ruta_origen ?? '',
        'ruta_destino' => $b->ruta_destino ?? '',
    ];
})->values();
@endphp
<script>
// ── Audio Player State ──
let audioEl = null;
let audioAnimFrame = null;
const speeds = [0.5, 0.75, 1, 1.25, 1.5, 2];
let speedIdx = 2;

// ── Search ──
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-batches');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.batch-row-card').forEach(card => {
                card.style.display = card.dataset.searchText.includes(q) ? '' : 'none';
            });
        });
    }
});

// ── Batch Toggle ──
function toggleBatchCard(groupId) {
    const detail = document.getElementById('batch-detail-' + groupId);
    const chevron = document.querySelector('.batch-chevron-' + groupId);
    if (!detail) return;

    if (detail.classList.contains('hidden')) {
        document.querySelectorAll('[id^="batch-detail-"]').forEach(d => d.classList.add('hidden'));
        document.querySelectorAll('[class*="batch-chevron-"]').forEach(c => c.classList.remove('rotate-90'));
        detail.classList.remove('hidden');
        if (chevron) chevron.classList.add('rotate-90');
        loadBatchDetail(groupId);
    } else {
        detail.classList.add('hidden');
        if (chevron) chevron.classList.remove('rotate-90');
    }
}

function loadBatchDetail(groupId) {
    const container = document.querySelector('.batch-content-' + groupId);
    const loading = document.querySelector('.batch-loading-' + groupId);
    if (!container) return;
    loading.style.display = 'flex';
    container.innerHTML = '';

    fetch(`/auditoria-llamadas/batch/${groupId}`)
        .then(r => r.json())
        .then(data => {
            loading.style.display = 'none';
            if (!data.success || !data.calls || data.calls.length === 0) {
                container.innerHTML = '<p class="text-gray-400 text-sm py-4">Sin llamadas detalladas para este grupo</p>';
                return;
            }
            container.innerHTML = renderCallCards(data.calls);
            // Render per-batch charts
            try { renderBatchCharts(groupId, data.calls); } catch(e) { console.warn('Charts error:', e); }
        })
        .catch(err => {
            loading.style.display = 'none';
            container.innerHTML = '<p class="text-red-500 text-sm py-4">Error: ' + err.message + '</p>';
        });
}

function renderCallCards(calls) {
    const groups = {};
    calls.forEach(c => {
        const k = c.batch_number || 0;
        if (!groups[k]) groups[k] = [];
        groups[k].push(c);
    });

    let html = '';
    Object.keys(groups).sort((a,b) => a - b).forEach(bk => {
        const batchCalls = groups[bk];
        if (Object.keys(groups).length > 1 || bk != 0) {
            html += '<div class="flex items-center gap-2 mb-3 mt-1">' +
                '<div class="w-6 h-6 rounded-lg bg-purple-100 flex items-center justify-center"><svg class="h-3 w-3 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg></div>' +
                '<span class="text-xs font-bold text-purple-700">Lote #' + (bk == 0 ? 'Sin asignar' : bk) + '</span>' +
                '<span class="text-[10px] text-gray-400">' + batchCalls.length + ' llamadas</span></div>';
        }
        html += '<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 mb-4">';
        batchCalls.forEach((call, i) => { html += renderSingleCard(call, i); });
        html += '</div>';
    });
    return html;
}

function renderSingleCard(c, idx) {
    const avatarColors = ['#6366f1','#8b5cf6','#ec4899','#f97316','#14b8a6','#3b82f6','#ef4444','#22c55e'];
    const initials = (c.nombre_conductor || '?').split(' ').map(w => w[0]).join('').substring(0,2).toUpperCase();
    const bgColor = avatarColors[idx % avatarColors.length];

    // ── Determinar resultado REAL de la llamada ──
    const outcome = determineCallOutcome(c);

    // Flow steps
    const steps = [];
    steps.push({ label: 'Cola', done: !!c.queued_at || !!c.queue_status, active: c.queue_status === 'pending' });
    steps.push({ label: 'Procesando', done: !!c.processing_started_at || c.queue_status === 'completed', active: c.queue_status === 'processing' });
    steps.push({ label: 'Iniciada', done: !!c.call_initiated_at || c.call_status === 'initiated' || c.call_status === 'completed', active: false });
    const hasRung = !!c.call_ringing_at || c.call_status === 'answered' || c.call_status === 'completed';
    steps.push({ label: 'Timbrando', done: hasRung, active: c.call_status === 'ringing' });
    // Final step based on outcome
    if (outcome.type === 'accepted') {
        steps.push({ label: 'Aceptó viaje', done: true });
    } else if (outcome.type === 'rejected') {
        steps.push({ label: 'Rechazó', done: true, error: true });
    } else if (outcome.type === 'voicemail') {
        steps.push({ label: 'Buzón de voz', error: true });
    } else if (outcome.type === 'hangup') {
        steps.push({ label: 'Colgó/Cortó', error: true });
    } else if (outcome.type === 'no_answer') {
        steps.push({ label: 'No contestó', error: true });
    } else if (outcome.type === 'busy') {
        steps.push({ label: 'Ocupado', error: true });
    } else if (outcome.type === 'failed') {
        steps.push({ label: 'Fallida', error: true });
    } else if (outcome.type === 'in_progress') {
        steps.push({ label: 'En curso...', active: true });
    } else {
        steps.push({ label: 'Pendiente', done: false });
    }

    // Result badge
    const resultBadge = outcome.badge;

    const hasConvId = c.elevenlabs_conversation_id;
    const hasTranscript = c.transcript && c.transcript.length > 0;
    const llamadaId = c.id_llamada;
    const durSec = c.talk_duration_seconds || c.call_duration_seconds || 0;
    const duration = durSec > 0 ? formatDuration(durSec) : null;

    const errorHtml = c.failure_reason ?
        '<div class="mt-2 px-3 py-2 bg-red-50 rounded-lg text-xs text-red-600 flex items-start gap-2"><svg class="h-3.5 w-3.5 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01" /></svg><span>' + esc(c.failure_reason) + '</span></div>' : '';

    const notesHtml = (c.respuesta_llamada || c.notas || c.call_notes) ?
        '<div class="mt-2 px-3 py-2 bg-blue-50 rounded-lg text-xs text-blue-700">' +
        (c.respuesta_llamada ? '<b>Respuesta:</b> ' + esc(c.respuesta_llamada) + '<br>' : '') +
        (c.notas ? '<b>Notas:</b> ' + esc(c.notas) + '<br>' : '') +
        (c.call_notes ? '<b>Call notes:</b> ' + esc(c.call_notes) : '') + '</div>' : '';

    let flowHtml = '<div class="flex items-center flex-wrap gap-0 mt-3 mb-1">';
    steps.forEach((s, si) => {
        const cls = s.error ? 'error' : (s.active ? 'active' : (s.done ? 'done' : 'pending'));
        const dotCls = s.error ? 'bg-red-500' : (s.active ? 'bg-blue-500' : (s.done ? 'bg-green-500' : 'bg-gray-400'));
        flowHtml += '<div class="flow-step ' + cls + '"><span class="w-1.5 h-1.5 rounded-full ' + dotCls + '"></span>' + s.label + '</div>';
        if (si < steps.length - 1) flowHtml += '<div class="flow-connector ' + (s.done && !s.error ? 'done' : '') + '"></div>';
    });
    flowHtml += '</div>';

    return '<div class="call-card">' +
        '<div class="cc-header">' +
            '<div class="cc-avatar" style="background:' + bgColor + '">' + initials + '</div>' +
            '<div class="flex-1 min-w-0">' +
                '<div class="font-semibold text-sm text-gray-800 truncate">' + (c.nombre_conductor || 'Desconocido') + '</div>' +
                '<div class="text-[11px] text-gray-400">' + (c.telefono || '') + ' · ' + (c.tipo_vehiculo || '') + (c.placa ? ' · ' + c.placa : '') + '</div>' +
            '</div>' +
            resultBadge +
        '</div>' +
        '<div class="cc-body">' +
            '<div class="flex items-center gap-4 text-xs text-gray-500">' +
                '<span>' + (c.ciudad_origen || '') + ' → ' + (c.ciudad_destino || '') + '</span>' +
                (duration ? '<span class="font-medium text-gray-700">' + duration + '</span>' : '') +
                (c.score ? '<span title="Score">⭐ ' + c.score + '</span>' : '') +
            '</div>' +
            flowHtml + errorHtml + notesHtml +
        '</div>' +
        '<div class="cc-footer">' +
            (llamadaId ? '<button onclick="event.stopPropagation();showTimeline(' + llamadaId + ')" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs bg-purple-50 text-purple-600 hover:bg-purple-100 rounded-lg transition-colors font-medium"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>Flujo</button>' : '') +
            (hasConvId ? '<button onclick="event.stopPropagation();showTranscript(\'' + c.elevenlabs_conversation_id + '\')" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs ' + (hasTranscript ? 'bg-blue-50 text-blue-600 hover:bg-blue-100' : 'bg-gray-50 text-gray-500 hover:bg-gray-100') + ' rounded-lg transition-colors font-medium"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>Transcripción</button>' : '') +
            (hasConvId ? '<button onclick="event.stopPropagation();openAudioPlayer(\'' + c.elevenlabs_conversation_id + '\', \'' + esc(c.nombre_conductor || 'Llamada') + '\', \'' + esc(c.telefono || '') + '\')" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg transition-colors font-medium"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" /></svg>Audio</button>' : '') +
        '</div></div>';
}

function failLabel(s) { return { 'failed':'Fallida', 'busy':'Ocupado', 'no_answer':'Sin respuesta', 'cancelled':'Cancelada' }[s] || s || 'Error'; }
function formatDuration(s) { if (!s || s <= 0) return '0s'; var m = Math.floor(s/60), sec = s % 60; return m > 0 ? m+'m '+sec+'s' : sec+'s'; }
function esc(str) { if (!str) return ''; var d = document.createElement('div'); d.textContent = str; return d.innerHTML; }

// ── Determinar resultado REAL de la llamada ──
function determineCallOutcome(c) {
    const resp = (c.respuesta_llamada || '').toLowerCase();
    const notas = (c.notas || '').toLowerCase();
    const transcript = (c.transcript || '').toLowerCase();
    const intNotes = (c.internal_notes || '').toLowerCase();

    // 1. Aceptación EXPLÍCITA: respuesta_llamada contiene "acepta"
    if (resp.includes('acepta') || resp === 'accepted' || resp === 'aceptado') {
        return { type: 'accepted', badge: '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-green-100 text-green-700 text-xs font-bold"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>Aceptó</span>' };
    }

    // 2. Rechazo EXPLÍCITO: respuesta_llamada contiene "rechaza"
    if (resp.includes('rechaza') || resp === 'rejected' || resp === 'rechazado') {
        return { type: 'rejected', badge: '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-100 text-red-700 text-xs font-bold"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>Rechazó</span>' };
    }

    // 3. Buzón de voz: detectar en transcripción o notas
    const buzBonPatterns = ['buzón', 'buzon', 'grabe su mensaje', 'después del tono', 'despues del tono', 'voicemail', 'mailbox', 'deje su mensaje'];
    const isBuzon = buzBonPatterns.some(p => transcript.includes(p) || notas.includes(p) || intNotes.includes(p));
    if (isBuzon) {
        return { type: 'voicemail', badge: '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-purple-100 text-purple-700 text-xs font-bold"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>Buzón de voz</span>' };
    }

    // 4. No contestó
    if (c.call_status === 'no_answer' || resp === 'no_contesto') {
        return { type: 'no_answer', badge: '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-yellow-100 text-yellow-700 text-xs font-bold"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m-2.829-2.829a5 5 0 000-7.07" /></svg>No contestó</span>' };
    }

    // 5. Ocupado
    if (c.call_status === 'busy' || resp === 'ocupado') {
        return { type: 'busy', badge: '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-orange-100 text-orange-700 text-xs font-bold">Ocupado</span>' };
    }

    // 6. Fallida (error técnico)
    if (c.call_status === 'failed' || resp === 'no_disponible') {
        return { type: 'failed', badge: '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-100 text-red-700 text-xs font-bold">Fallida</span>' };
    }

    // 7. Llamada se realizó (tiene transcripción o conversation_id) pero NO hubo decisión explícita
    // Esto incluye: colgó, se cortó, no llegó a responder, monitor finalizó por timeout
    const hasConversation = !!c.elevenlabs_conversation_id;
    const hasTranscript = !!c.transcript;
    const monitorFinalized = intNotes.includes('monitor:') || intNotes.includes('finalizada') || intNotes.includes('sin respuesta');

    if (hasConversation && (hasTranscript || c.estado_llamada === 'en_progreso') && !resp) {
        // Diferenciar entre "colgó" y "en curso real"
        if (monitorFinalized || c.estado_llamada === 'en_progreso') {
            return { type: 'hangup', badge: '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-100 text-amber-700 text-xs font-bold"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.13a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z" /></svg>Colgó / Sin decisión</span>' };
        }
    }

    // 8. En curso activamente (llamada.status = en_curso, sin finalizar)
    if (c.estado_llamada === 'en_progreso' && c.llamada_status === 'en_curso') {
        return { type: 'in_progress', badge: '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-100 text-blue-700 text-xs font-bold animate-pulse">En curso...</span>' };
    }

    // 9. estado_llamada fallida pero sin respuesta explícita (error de la llamada misma)
    if (c.estado_llamada === 'fallida' && !resp) {
        return { type: 'failed', badge: '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-100 text-red-700 text-xs font-bold">Fallo técnico</span>' };
    }

    // 10. Pendiente (no se ha llamado)
    return { type: 'pending', badge: '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-gray-100 text-gray-500 text-xs">Pendiente</span>' };
}

// ── Modal helpers ──
function openModal(id) { var m = document.getElementById(id); m.classList.remove('hidden'); m.classList.add('flex'); }
function closeModal(id) { var m = document.getElementById(id); m.classList.add('hidden'); m.classList.remove('flex'); }

// ── Transcripción ──
function showTranscript(convId) {
    openModal('transcript-modal');
    var el = document.getElementById('transcript-content');
    el.innerHTML = '<div class="text-center text-gray-400 py-8"><div class="animate-spin rounded-full h-8 w-8 border-2 border-[#FF7C32] border-t-transparent mx-auto mb-3"></div>Cargando transcripción...</div>';
    fetch('/auditoria-llamadas/transcript/' + convId).then(function(r){return r.json()}).then(function(data) {
        if (data.success && data.transcript) {
            el.innerHTML = formatTranscript(data.transcript);
        } else {
            el.innerHTML = '<div class="text-center py-8"><svg class="h-12 w-12 text-gray-200 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg><p class="text-gray-400 text-sm">Transcripción no disponible</p></div>';
        }
    }).catch(function(e) { el.innerHTML = '<p class="text-red-500 text-center py-4">Error: ' + e.message + '</p>'; });
}

function formatTranscript(text) {
    if (!text) return '<p class="text-gray-400">Sin transcripción</p>';

    // Si el texto es JSON crudo (array de turns de ElevenLabs), parsearlo
    if (typeof text === 'string' && text.trim().startsWith('[{')) {
        try {
            var turns = JSON.parse(text);
            if (Array.isArray(turns) && turns.length > 0 && turns[0].role) {
                var parsed = turns.map(function(t) {
                    var role = t.role === 'agent' ? 'Agente' : 'Conductor';
                    var msg = t.message || t.text || '';
                    return msg.trim() ? role + ': ' + msg : '';
                }).filter(function(l){return l});
                text = parsed.join('\n');
            }
        } catch(e) { /* not JSON, proceed as text */ }
    }

    var lines = text.split('\n').filter(function(l){return l.trim()});
    var html = '<div class="space-y-2">';
    lines.forEach(function(line) {
        if (line.startsWith('Agente:')) {
            html += '<div class="flex items-start gap-3"><div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 mt-0.5"><svg class="h-3.5 w-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg></div><div class="bg-blue-50 rounded-xl rounded-tl-sm px-4 py-2.5 text-sm text-gray-700 flex-1">' + esc(line.replace('Agente:','').trim()) + '</div></div>';
        } else if (line.startsWith('Conductor:')) {
            html += '<div class="flex items-start gap-3 flex-row-reverse"><div class="w-7 h-7 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5"><svg class="h-3.5 w-3.5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg></div><div class="bg-green-50 rounded-xl rounded-tr-sm px-4 py-2.5 text-sm text-gray-700 flex-1 text-right">' + esc(line.replace('Conductor:','').trim()) + '</div></div>';
        } else {
            html += '<div class="text-xs text-center text-gray-400 py-1">' + esc(line) + '</div>';
        }
    });
    html += '</div>';
    return html;
}

// ── Timeline ──
function showTimeline(llamadaId) {
    openModal('timeline-modal');
    var el = document.getElementById('timeline-content');
    el.innerHTML = '<div class="text-center text-gray-400 py-8"><div class="animate-spin rounded-full h-8 w-8 border-2 border-[#FF7C32] border-t-transparent mx-auto mb-3"></div>Cargando flujo...</div>';
    fetch('/auditoria-llamadas/timeline/' + llamadaId).then(function(r){return r.json()}).then(function(data) {
        if (!data.success) { el.innerHTML = '<p class="text-gray-400 text-center py-8">Sin datos de flujo</p>'; return; }
        var m = data.metadata;
        var html = '';
        html += '<div class="bg-gradient-to-r from-gray-50 to-purple-50 rounded-xl p-4 mb-5 text-xs"><div class="grid grid-cols-2 gap-y-2 gap-x-4">';
        html += '<div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full ' + (m.status==='aceptada'?'bg-green-500':m.status==='rechazada'?'bg-red-500':'bg-gray-400') + '"></span><span class="text-gray-500">Status:</span><span class="font-semibold">' + (m.status||'—') + '</span></div>';
        html += '<div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full ' + (m.call_status==='completed'?'bg-green-500':m.call_status==='failed'?'bg-red-500':'bg-blue-500') + '"></span><span class="text-gray-500">Call:</span><span class="font-semibold">' + (m.call_status||'—') + '</span></div>';
        html += '<div><span class="text-gray-500">Batch:</span> <span class="font-semibold">#' + (m.batch_number||'—') + ' pos ' + (m.batch_position||'—') + '</span></div>';
        html += '<div><span class="text-gray-500">Cola:</span> <span class="font-semibold">' + (m.queue_status||'—') + '</span></div>';
        html += '<div><span class="text-gray-500">Duración:</span> <span class="font-semibold">' + (m.duration||0) + 's</span> <span class="text-gray-400">(habla: ' + (m.talk_time||0) + 's)</span></div>';
        html += '<div><span class="text-gray-500">Intentos:</span> <span class="font-semibold">' + (m.attempts||0) + '</span> <span class="text-gray-400">(retries: ' + (m.retry_count||0) + ')</span></div>';
        if (m.sip_code) html += '<div><span class="text-gray-500">SIP:</span> <span class="font-semibold">' + m.sip_code + ' ' + (m.sip_message||'') + '</span></div>';
        html += '</div></div>';

        html += '<div class="relative ml-4">';
        data.events.forEach(function(ev, i) {
            var colorMap = { gray:'#9ca3af', blue:'#3b82f6', yellow:'#eab308', green:'#22c55e', red:'#ef4444', orange:'#f97316' };
            var color = colorMap[ev.color] || '#9ca3af';
            var time = ev.time ? new Date(ev.time).toLocaleTimeString('es-CO', { hour:'2-digit', minute:'2-digit', second:'2-digit' }) : '';
            var isLast = i === data.events.length - 1;
            html += '<div class="relative flex items-start gap-4 ' + (isLast?'':'pb-6') + '"><div class="relative flex flex-col items-center"><div class="w-4 h-4 rounded-full border-2 bg-white z-10 flex items-center justify-center" style="border-color:' + color + '"><div class="w-2 h-2 rounded-full" style="background:' + color + '"></div></div>';
            if (!isLast) html += '<div class="w-0.5 flex-1 mt-1 bg-gray-200" style="min-height:20px"></div>';
            html += '</div><div class="flex-1 -mt-0.5"><div class="text-sm font-medium text-gray-800">' + esc(ev.event) + '</div><div class="text-[11px] text-gray-400">' + time + '</div></div></div>';
        });
        html += '</div>';
        el.innerHTML = html;
    }).catch(function(e) { el.innerHTML = '<p class="text-red-500 text-center py-4">Error: ' + e.message + '</p>'; });
}

// ── Audio Player ──
function openAudioPlayer(convId, name, phone) {
    openModal('audio-modal');
    document.getElementById('ap-title').textContent = name;
    document.getElementById('ap-subtitle').textContent = phone + ' · Cargando audio...';
    document.getElementById('ap-current').textContent = '0:00';
    document.getElementById('ap-duration').textContent = '0:00';
    document.getElementById('ap-progress-bar').style.width = '0%';
    document.getElementById('ap-progress-thumb').style.left = '0%';
    document.getElementById('ap-icon-play').classList.remove('hidden');
    document.getElementById('ap-icon-pause').classList.add('hidden');
    generateWave();

    if (audioEl) { audioEl.pause(); audioEl = null; }
    if (audioAnimFrame) cancelAnimationFrame(audioAnimFrame);

    fetch('/auditoria-llamadas/audio/' + convId).then(function(r){return r.json()}).then(function(data) {
        if (data.success && data.audio_url) {
            audioEl = new Audio(data.audio_url);
            audioEl.volume = document.getElementById('ap-volume').value / 100;
            audioEl.playbackRate = speeds[speedIdx];
            audioEl.addEventListener('loadedmetadata', function() {
                document.getElementById('ap-subtitle').textContent = phone + ' · ' + fmtTime(audioEl.duration);
                document.getElementById('ap-duration').textContent = fmtTime(audioEl.duration);
            });
            audioEl.addEventListener('ended', function() {
                document.getElementById('ap-icon-play').classList.remove('hidden');
                document.getElementById('ap-icon-pause').classList.add('hidden');
                cancelAnimationFrame(audioAnimFrame);
            });
            audioEl.play().then(function() {
                document.getElementById('ap-icon-play').classList.add('hidden');
                document.getElementById('ap-icon-pause').classList.remove('hidden');
                updateProgress();
            }).catch(function(){});
        } else {
            document.getElementById('ap-subtitle').textContent = 'Audio no disponible';
        }
    }).catch(function() {
        document.getElementById('ap-subtitle').textContent = 'Error al cargar audio';
    });
}

function togglePlayAudio() {
    if (!audioEl) return;
    if (audioEl.paused) {
        audioEl.play();
        document.getElementById('ap-icon-play').classList.add('hidden');
        document.getElementById('ap-icon-pause').classList.remove('hidden');
        updateProgress();
    } else {
        audioEl.pause();
        document.getElementById('ap-icon-play').classList.remove('hidden');
        document.getElementById('ap-icon-pause').classList.add('hidden');
        cancelAnimationFrame(audioAnimFrame);
    }
}

function skipAudio(sec) {
    if (!audioEl) return;
    audioEl.currentTime = Math.max(0, Math.min(audioEl.duration || 0, audioEl.currentTime + sec));
}

function seekAudio(e) {
    if (!audioEl) return;
    var bar = document.getElementById('ap-progress');
    var rect = bar.getBoundingClientRect();
    var pct = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
    audioEl.currentTime = pct * (audioEl.duration || 0);
}

function updateProgress() {
    if (!audioEl) return;
    var pct = audioEl.duration ? (audioEl.currentTime / audioEl.duration) * 100 : 0;
    document.getElementById('ap-progress-bar').style.width = pct + '%';
    document.getElementById('ap-progress-thumb').style.left = pct + '%';
    document.getElementById('ap-current').textContent = fmtTime(audioEl.currentTime);
    updateWaveBars(pct);
    if (!audioEl.paused) audioAnimFrame = requestAnimationFrame(updateProgress);
}

function setVolume(val) { if (audioEl) audioEl.volume = val / 100; }

function cycleSpeed() {
    speedIdx = (speedIdx + 1) % speeds.length;
    if (audioEl) audioEl.playbackRate = speeds[speedIdx];
    document.getElementById('ap-speed').textContent = speeds[speedIdx] + 'x';
}

function closeAudioModal() {
    if (audioEl) { audioEl.pause(); audioEl = null; }
    if (audioAnimFrame) cancelAnimationFrame(audioAnimFrame);
    closeModal('audio-modal');
}

function fmtTime(secs) {
    if (!secs || isNaN(secs)) return '0:00';
    var m = Math.floor(secs / 60), s = Math.floor(secs % 60);
    return m + ':' + (s < 10 ? '0' : '') + s;
}

function generateWave() {
    var container = document.getElementById('ap-wave');
    container.innerHTML = '';
    for (var i = 0; i < 60; i++) {
        var h = 8 + Math.random() * 28;
        var bar = document.createElement('div');
        bar.className = 'bar';
        bar.style.height = h + 'px';
        container.appendChild(bar);
    }
}

function updateWaveBars(pct) {
    var bars = document.querySelectorAll('#ap-wave .bar');
    var activeIdx = Math.floor((pct / 100) * bars.length);
    bars.forEach(function(bar, i) {
        bar.classList.toggle('played', i < activeIdx);
        bar.classList.toggle('active', i === activeIdx);
    });
}

// ── Queue Status ──
function refreshQueueStatus() {
    fetch('/auditoria-llamadas/queue-status').then(function(r){return r.json()}).then(function(data) {
        if (data.success) {
            document.getElementById('qs-processing').textContent = data.queue.active_calls;
            document.getElementById('qs-pending').textContent = data.queue.pending_calls;
            document.getElementById('qs-jobs').textContent = data.queue.active_jobs;
            document.getElementById('qs-failed').textContent = data.queue.failed_jobs_24h;
            var maxEl = document.getElementById('qs-max');
            if (maxEl) maxEl.textContent = data.queue.max_concurrent;
            var dot = document.getElementById('qs-dot-processing');
            if (dot) dot.style.display = data.queue.active_calls > 0 ? '' : 'none';
        }
    }).catch(function(){});
}
setInterval(refreshQueueStatus, 15000);

// Auto-refresh full page when there are active calls
let autoRefreshInterval = null;
function toggleAutoRefresh(btn) {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
        btn.classList.remove('bg-green-100', 'text-green-700');
        btn.classList.add('bg-gray-100', 'text-gray-500');
        btn.title = 'Auto-refresh desactivado';
    } else {
        autoRefreshInterval = setInterval(() => { location.reload(); }, 60000);
        btn.classList.remove('bg-gray-100', 'text-gray-500');
        btn.classList.add('bg-green-100', 'text-green-700');
        btn.title = 'Auto-refresh cada 60s (click para desactivar)';
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAudioModal();
        closeModal('transcript-modal');
        closeModal('timeline-modal');
    }
    if (audioEl && document.getElementById('audio-modal').classList.contains('flex')) {
        if (e.code === 'Space') { e.preventDefault(); togglePlayAudio(); }
        if (e.key === 'ArrowLeft') { e.preventDefault(); skipAudio(-5); }
        if (e.key === 'ArrowRight') { e.preventDefault(); skipAudio(5); }
    }
});

// ══════════════════════ GLOBAL CHARTS (Chart.js) ══════════════════════
document.addEventListener('DOMContentLoaded', function() {
    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { labels: { font: { size: 11, family: "'Inter','sans-serif'" }, padding: 12, usePointStyle: true, pointStyleWidth: 8 } } }
    };

    // ── Data from PHP ──
    const kpis = @json($kpis);
    const batchesData = @json($chartBatchesData);

    // ── 1. Doughnut: Distribución de Resultados ──
    const ctxResultados = document.getElementById('chart-resultados');
    if (ctxResultados) {
        new Chart(ctxResultados, {
            type: 'doughnut',
            data: {
                labels: ['Aceptaron', 'Rechazaron', 'Sin decisión', 'Sin respuesta', 'Con errores'],
                datasets: [{
                    data: [kpis.total_aceptaron, kpis.total_rechazaron, kpis.total_sin_decision, kpis.total_sin_respuesta, kpis.total_con_error],
                    backgroundColor: ['#22c55e', '#ef4444', '#eab308', '#f97316', '#dc2626'],
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 6
                }]
            },
            options: {
                ...chartDefaults,
                cutout: '62%',
                plugins: {
                    ...chartDefaults.plugins,
                    legend: { ...chartDefaults.plugins.legend, position: 'right' },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const total = ctx.dataset.data.reduce((a,b) => a+b, 0);
                                const pct = total > 0 ? Math.round((ctx.raw / total) * 100) : 0;
                                return ' ' + ctx.label + ': ' + ctx.raw + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // ── 2. Stacked Bar: Rendimiento por Lote ──
    const ctxLotes = document.getElementById('chart-lotes');
    if (ctxLotes && batchesData.length > 0) {
        const labels = batchesData.map(b => b.label);
        new Chart(ctxLotes, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Aceptaron', data: batchesData.map(b => b.aceptaron), backgroundColor: '#22c55e', borderRadius: 3 },
                    { label: 'Rechazaron', data: batchesData.map(b => b.rechazaron), backgroundColor: '#ef4444', borderRadius: 3 },
                    { label: 'Sin decisión', data: batchesData.map(b => b.sin_decision), backgroundColor: '#eab308', borderRadius: 3 },
                    { label: 'Errores', data: batchesData.map(b => b.errores), backgroundColor: '#dc2626', borderRadius: 3 }
                ]
            },
            options: {
                ...chartDefaults,
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 45 } },
                    y: { stacked: true, beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { font: { size: 10 }, stepSize: 1 } }
                },
                plugins: { ...chartDefaults.plugins, legend: { ...chartDefaults.plugins.legend, position: 'top' } }
            }
        });
    }

    // ── 3. Line: Duración Promedio por Lote ──
    const ctxDuracion = document.getElementById('chart-duracion');
    if (ctxDuracion && batchesData.length > 0) {
        new Chart(ctxDuracion, {
            type: 'line',
            data: {
                labels: batchesData.map(b => b.label),
                datasets: [{
                    label: 'Duración promedio (seg)',
                    data: batchesData.map(b => b.duracion_prom),
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139,92,246,0.1)',
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#8b5cf6',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                ...chartDefaults,
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 45 } },
                    y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { font: { size: 10 }, callback: v => v + 's' } }
                },
                plugins: {
                    ...chartDefaults.plugins,
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ' ' + ctx.raw + ' segundos' } }
                }
            }
        });
    }

    // ── 4. Horizontal Bar: Efectividad por Ruta ──
    const ctxRutas = document.getElementById('chart-rutas');
    if (ctxRutas && batchesData.length > 0) {
        // Agrupar por ruta
        const rutaMap = {};
        batchesData.forEach(b => {
            const r = b.ruta;
            if (!rutaMap[r]) rutaMap[r] = { total: 0, aceptaron: 0, contestadas: 0 };
            rutaMap[r].total += b.total;
            rutaMap[r].aceptaron += b.aceptaron;
            rutaMap[r].contestadas += b.contestadas;
        });
        const rutaLabels = Object.keys(rutaMap);
        const tasaExito = rutaLabels.map(r => rutaMap[r].total > 0 ? Math.round((rutaMap[r].aceptaron / rutaMap[r].total) * 100) : 0);
        const tasaContacto = rutaLabels.map(r => rutaMap[r].total > 0 ? Math.round((rutaMap[r].contestadas / rutaMap[r].total) * 100) : 0);

        new Chart(ctxRutas, {
            type: 'bar',
            data: {
                labels: rutaLabels,
                datasets: [
                    { label: 'Tasa de contacto %', data: tasaContacto, backgroundColor: 'rgba(59,130,246,0.7)', borderRadius: 3 },
                    { label: 'Tasa de éxito %', data: tasaExito, backgroundColor: 'rgba(34,197,94,0.7)', borderRadius: 3 }
                ]
            },
            options: {
                ...chartDefaults,
                indexAxis: 'y',
                scales: {
                    x: { beginAtZero: true, max: 100, grid: { color: '#f3f4f6' }, ticks: { font: { size: 10 }, callback: v => v + '%' } },
                    y: { grid: { display: false }, ticks: { font: { size: 10 } } }
                },
                plugins: {
                    ...chartDefaults.plugins,
                    legend: { ...chartDefaults.plugins.legend, position: 'top' },
                    tooltip: { callbacks: { label: ctx => ' ' + ctx.dataset.label + ': ' + ctx.raw + '%' } }
                }
            }
        });
    }
});

// ══════════════════════ PER-BATCH CHARTS ══════════════════════
const batchChartInstances = {};

function renderBatchCharts(groupId, calls) {
    const chartsContainer = document.querySelector('.batch-charts-' + groupId);
    if (!chartsContainer) return;
    chartsContainer.style.display = '';

    // Destroy previous instances
    if (batchChartInstances[groupId]) {
        batchChartInstances[groupId].forEach(c => c.destroy());
    }
    batchChartInstances[groupId] = [];

    // Classify each call by outcome
    const outcomes = { accepted: 0, rejected: 0, voicemail: 0, hangup: 0, no_answer: 0, busy: 0, failed: 0, in_progress: 0, pending: 0 };
    const outcomeLabels = { accepted:'Aceptó', rejected:'Rechazó', voicemail:'Buzón', hangup:'Colgó/Sin decisión', no_answer:'No contestó', busy:'Ocupado', failed:'Fallida', in_progress:'En curso', pending:'Pendiente' };
    const outcomeColors = { accepted:'#22c55e', rejected:'#ef4444', voicemail:'#8b5cf6', hangup:'#f59e0b', no_answer:'#f97316', busy:'#ea580c', failed:'#dc2626', in_progress:'#3b82f6', pending:'#9ca3af' };

    const durations = [];
    const durationLabels = [];
    const timelineData = [];

    calls.forEach(c => {
        const o = determineCallOutcome(c);
        if (outcomes.hasOwnProperty(o.type)) outcomes[o.type]++;

        const name = (c.nombre_conductor || 'Desc.').split(' ').slice(0,2).join(' ');
        const dur = c.talk_duration_seconds || c.call_duration_seconds || 0;
        durations.push(dur);
        durationLabels.push(name);

        if (c.fecha_llamada || c.lc_created_at) {
            timelineData.push({ x: new Date(c.fecha_llamada || c.lc_created_at), y: dur, name: name, outcome: o.type });
        }
    });

    // 1. Doughnut - Result distribution
    const filteredLabels = [], filteredData = [], filteredColors = [];
    Object.keys(outcomes).forEach(k => {
        if (outcomes[k] > 0) {
            filteredLabels.push(outcomeLabels[k]);
            filteredData.push(outcomes[k]);
            filteredColors.push(outcomeColors[k]);
        }
    });

    const ctx1 = document.getElementById('batch-chart-result-' + groupId);
    if (ctx1) {
        batchChartInstances[groupId].push(new Chart(ctx1, {
            type: 'doughnut',
            data: { labels: filteredLabels, datasets: [{ data: filteredData, backgroundColor: filteredColors, borderWidth: 1, borderColor: '#fff' }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '55%', plugins: { legend: { position: 'right', labels: { font: { size: 9 }, padding: 6, usePointStyle: true, pointStyleWidth: 6 } } } }
        }));
    }

    // 2. Bar - Duration per driver
    const ctx2 = document.getElementById('batch-chart-duration-' + groupId);
    if (ctx2) {
        const barColors = calls.map(c => {
            const o = determineCallOutcome(c).type;
            return outcomeColors[o] || '#9ca3af';
        });
        batchChartInstances[groupId].push(new Chart(ctx2, {
            type: 'bar',
            data: { labels: durationLabels, datasets: [{ label: 'Duración (seg)', data: durations, backgroundColor: barColors, borderRadius: 2 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { font: { size: 8 }, maxRotation: 45 } }, y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { font: { size: 9 }, callback: v => v + 's' } } } }
        }));
    }

    // 3. Scatter - Timeline
    const ctx3 = document.getElementById('batch-chart-timeline-' + groupId);
    if (ctx3 && timelineData.length > 0) {
        const scatterSets = {};
        timelineData.forEach(d => {
            const key = d.outcome;
            if (!scatterSets[key]) scatterSets[key] = { label: outcomeLabels[key] || key, data: [], backgroundColor: outcomeColors[key] || '#9ca3af', pointRadius: 5, pointHoverRadius: 7 };
            scatterSets[key].data.push({ x: d.x, y: d.y, name: d.name });
        });
        batchChartInstances[groupId].push(new Chart(ctx3, {
            type: 'scatter',
            data: { datasets: Object.values(scatterSets) },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    x: { type: 'time', time: { unit: 'minute', displayFormats: { minute: 'HH:mm' } }, grid: { display: false }, ticks: { font: { size: 8 } } },
                    y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { font: { size: 9 }, callback: v => v + 's' }, title: { display: true, text: 'Duración', font: { size: 9 } } }
                },
                plugins: {
                    legend: { position: 'top', labels: { font: { size: 9 }, padding: 6, usePointStyle: true, pointStyleWidth: 6 } },
                    tooltip: { callbacks: { label: ctx => ' ' + (ctx.raw.name || '') + ': ' + ctx.raw.y + 's' } }
                }
            }
        }));
    }
}
</script>
@endpush
