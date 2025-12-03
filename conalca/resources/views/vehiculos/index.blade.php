@extends('layout.app')

@section('title', 'Vehículos')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 inline-block mr-2 text-[#FF7C32]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 17h8M8 17a2 2 0 11-4 0 2 2 0 014 0zm8 0a2 2 0 104 0 2 2 0 00-4 0zm-8 0H5a2 2 0 01-2-2V9a2 2 0 012-2h2.5M17 17h2a2 2 0 002-2v-5a2 2 0 00-2-2h-1.5l-2-3H10a2 2 0 00-2 2v5" />
                    </svg>
                    Gestión de Vehículos
                </h1>
                <p class="text-gray-600 dark:text-gray-300 mt-1">
                    Tipos de vehículos registrados en Arcangel
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button id="btnSincronizar" 
                        onclick="sincronizarVehiculos()"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 transition-colors">
                    <svg id="iconSync" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span id="textSync">Sincronizar</span>
                </button>
                <span class="bg-[#FF7C32] text-white px-4 py-2 rounded-full text-sm font-semibold">
                    {{ count($tiposVehiculos ?? []) }} tipos registrados
                </span>
            </div>
        </div>
    </div>

    {{-- Panel de Progreso (oculto por defecto) --}}
    <div id="panelProgreso" class="hidden bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center">
                <svg class="animate-spin w-5 h-5 mr-2 text-[#FF7C32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Sincronización en progreso
            </h3>
            <span id="tiempoRestante" class="text-sm bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-medium">
                Calculando tiempo...
            </span>
        </div>

        {{-- Barra de progreso --}}
        <div class="mb-4">
            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                <span id="ciudadActual">Preparando...</span>
                <span id="porcentajeTexto">0%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700 overflow-hidden">
                <div id="barraProgreso" 
                     class="bg-gradient-to-r from-[#FF7C32] to-orange-500 h-4 rounded-full transition-all duration-300 flex items-center justify-center"
                     style="width: 0%">
                </div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                <span><span id="ciudadesProcesadas">0</span> de <span id="ciudadesTotal">0</span> ciudades</span>
                <span id="erroresCount" class="text-red-500 hidden">0 errores</span>
            </div>
        </div>

        {{-- Estadísticas en tiempo real --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 text-center">
                <div id="statCiudades" class="text-2xl font-bold text-[#FF7C32]">0</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Ciudades</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 text-center">
                <div id="statVehiculos" class="text-2xl font-bold text-green-600">0</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Vehículos</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 text-center">
                <div id="statTipos" class="text-2xl font-bold text-blue-600">0</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Tipos únicos</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 text-center">
                <div id="statErrores" class="text-2xl font-bold text-red-600">0</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Errores</div>
            </div>
        </div>

        {{-- Log de ciudades escaneadas --}}
        <div class="mt-4">
            <details class="cursor-pointer">
                <summary class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                    Ver log de escaneo
                </summary>
                <div id="logEscaneo" class="mt-2 max-h-40 overflow-y-auto bg-gray-900 text-green-400 rounded-lg p-3 font-mono text-xs">
                    <div>Esperando inicio...</div>
                </div>
            </details>
        </div>
    </div>

    {{-- Alert de resultado --}}
    <div id="alertaResultado" class="hidden mb-6"></div>

    {{-- Error Alert --}}
    @if(isset($error) && $error)
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg" role="alert">
        <div class="flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="font-bold">Error</p>
        </div>
        <p class="mt-2">{{ $error }}</p>
    </div>
    @endif

    {{-- Tabla de Tipos de Vehículos --}}
    @if(isset($tiposVehiculos) && count($tiposVehiculos) > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2 text-[#FF7C32]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
            </svg>
            Tipos de Vehículos Arcangel
        </h2>
        
        {{-- Search Filter --}}
        <div class="mb-4">
            <input type="text" 
                   id="searchVehiculos" 
                   placeholder="Buscar tipo de vehículo..." 
                   class="w-full md:w-1/3 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF7C32] focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ID
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Tipo de Vehículo
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Fecha Registro
                        </th>
                    </tr>
                </thead>
                <tbody id="tablaVehiculos" class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                    @foreach($tiposVehiculos as $tipo)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 vehiculo-row">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $tipo->id }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-[#FBEBE2] rounded-full flex items-center justify-center">
                                    @if(str_contains(strtoupper($tipo->nombre), 'TRACTOMULA'))
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#FF7C32]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 17h8M8 17a2 2 0 11-4 0 2 2 0 014 0zm8 0a2 2 0 104 0 2 2 0 00-4 0z" />
                                        </svg>
                                    @elseif(str_contains(strtoupper($tipo->nombre), 'CAMIONETA'))
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#FF7C32]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    @elseif(str_contains(strtoupper($tipo->nombre), 'TURBO'))
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#FF7C32]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#FF7C32]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white vehiculo-nombre">
                                        {{ $tipo->nombre }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $tipo->created_at ? \Carbon\Carbon::parse($tipo->created_at)->format('d/m/Y H:i') : 'N/A' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Contador de resultados --}}
        <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
            Mostrando <span id="contadorResultados">{{ count($tiposVehiculos) }}</span> de {{ count($tiposVehiculos) }} tipos de vehículos
        </div>
    </div>
    @else
    <div class="bg-yellow-50 dark:bg-yellow-900 border-l-4 border-yellow-400 p-4 rounded-lg">
        <div class="flex items-center">
            <svg class="w-6 h-6 text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <p class="text-yellow-700 dark:text-yellow-200 font-medium">No hay tipos de vehículos registrados. Haz clic en "Sincronizar" para obtenerlos de Arcangel.</p>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    let eventSource = null;

    // Filtro de búsqueda
    document.getElementById('searchVehiculos')?.addEventListener('input', function(e) {
        const busqueda = e.target.value.toLowerCase();
        const filas = document.querySelectorAll('.vehiculo-row');
        let contador = 0;

        filas.forEach(fila => {
            const nombre = fila.querySelector('.vehiculo-nombre').textContent.toLowerCase();
            if (nombre.includes(busqueda)) {
                fila.style.display = '';
                contador++;
            } else {
                fila.style.display = 'none';
            }
        });

        document.getElementById('contadorResultados').textContent = contador;
    });

    // Función para sincronizar vehículos con Server-Sent Events
    function sincronizarVehiculos() {
        const btn = document.getElementById('btnSincronizar');
        const icon = document.getElementById('iconSync');
        const text = document.getElementById('textSync');
        const panelProgreso = document.getElementById('panelProgreso');
        const logEscaneo = document.getElementById('logEscaneo');
        
        // Deshabilitar botón
        btn.disabled = true;
        btn.classList.remove('bg-green-600', 'hover:bg-green-700');
        btn.classList.add('bg-gray-400', 'cursor-not-allowed');
        icon.classList.add('animate-spin');
        text.textContent = 'Sincronizando...';
        
        // Mostrar panel de progreso
        panelProgreso.classList.remove('hidden');
        logEscaneo.innerHTML = '<div class="text-yellow-400">▶ Iniciando conexión con Arcangel...</div>';
        
        // Ocultar alerta anterior
        document.getElementById('alertaResultado').classList.add('hidden');
        
        // Crear conexión SSE
        eventSource = new EventSource('/vehiculos/sincronizar');
        
        eventSource.addEventListener('init', function(e) {
            const data = JSON.parse(e.data);
            addLog('info', data.message);
            document.getElementById('ciudadActual').textContent = data.step;
        });
        
        eventSource.addEventListener('ciudades', function(e) {
            const data = JSON.parse(e.data);
            document.getElementById('ciudadesTotal').textContent = data.total;
            document.getElementById('tiempoRestante').textContent = '⏱ Estimado: ' + data.estimatedTime;
            addLog('success', data.message);
            addLog('info', 'Tiempo estimado: ' + data.estimatedTime);
        });
        
        eventSource.addEventListener('progress', function(e) {
            const data = JSON.parse(e.data);
            
            // Actualizar barra de progreso
            document.getElementById('barraProgreso').style.width = data.percent + '%';
            document.getElementById('porcentajeTexto').textContent = data.percent + '%';
            document.getElementById('ciudadesProcesadas').textContent = data.current;
            document.getElementById('ciudadActual').textContent = 'Escaneando: ' + data.ciudad;
            
            // Actualizar estadísticas
            document.getElementById('statCiudades').textContent = data.current;
            document.getElementById('statVehiculos').textContent = data.vehiculosTotal;
            document.getElementById('statTipos').textContent = data.tiposEncontrados;
            document.getElementById('statErrores').textContent = data.errores;
            
            // Actualizar tiempo restante
            document.getElementById('tiempoRestante').textContent = '⏱ Restante: ' + data.tiempoRestante;
            
            // Mostrar errores si hay
            if (data.errores > 0) {
                document.getElementById('erroresCount').classList.remove('hidden');
                document.getElementById('erroresCount').textContent = data.errores + ' errores';
            }
            
            // Agregar al log (cada 10 ciudades para no saturar)
            if (data.current % 10 === 0 || data.current === 1) {
                addLog('info', `[${data.percent}%] ${data.ciudad}: ${data.vehiculosEnCiudad} vehículos`);
            }
            
            // Si hay error en esta ciudad
            if (data.error) {
                addLog('error', data.error);
            }
        });
        
        eventSource.addEventListener('saving', function(e) {
            const data = JSON.parse(e.data);
            document.getElementById('ciudadActual').textContent = data.message;
            addLog('info', data.message);
        });
        
        eventSource.addEventListener('complete', function(e) {
            const data = JSON.parse(e.data);
            eventSource.close();
            
            // Actualizar UI final
            document.getElementById('barraProgreso').style.width = '100%';
            document.getElementById('porcentajeTexto').textContent = '100%';
            document.getElementById('ciudadActual').textContent = '✓ Completado';
            document.getElementById('tiempoRestante').textContent = '✓ ' + data.tiempoTotal;
            
            addLog('success', '═══════════════════════════════════');
            addLog('success', 'SINCRONIZACIÓN COMPLETADA');
            addLog('success', `Ciudades: ${data.ciudadesConsultadas}`);
            addLog('success', `Vehículos: ${data.vehiculosEncontrados}`);
            addLog('success', `Tipos únicos: ${data.tiposUnicos}`);
            addLog('success', `Nuevos agregados: ${data.tiposNuevos}`);
            addLog('success', `Tiempo total: ${data.tiempoTotal}`);
            
            // Mostrar alerta de éxito
            mostrarResultado('success', data);
            
            // Restaurar botón
            restaurarBoton();
            
            // Recargar si hay nuevos tipos
            if (data.tiposNuevos > 0) {
                setTimeout(() => {
                    window.location.reload();
                }, 5000);
            }
        });
        
        eventSource.addEventListener('error', function(e) {
            if (e.data) {
                const data = JSON.parse(e.data);
                addLog('error', 'ERROR: ' + data.message);
                mostrarResultado('error', data);
            }
            eventSource.close();
            restaurarBoton();
        });
        
        eventSource.onerror = function(e) {
            if (eventSource.readyState === EventSource.CLOSED) {
                return; // Ya se cerró normalmente
            }
            addLog('error', 'Error de conexión con el servidor');
            eventSource.close();
            restaurarBoton();
        };
    }
    
    function addLog(type, message) {
        const log = document.getElementById('logEscaneo');
        const colors = {
            info: 'text-green-400',
            success: 'text-cyan-400',
            error: 'text-red-400',
            warning: 'text-yellow-400'
        };
        const time = new Date().toLocaleTimeString();
        log.innerHTML += `<div class="${colors[type]}">[${time}] ${message}</div>`;
        log.scrollTop = log.scrollHeight;
    }
    
    function restaurarBoton() {
        const btn = document.getElementById('btnSincronizar');
        const icon = document.getElementById('iconSync');
        const text = document.getElementById('textSync');
        
        btn.disabled = false;
        btn.classList.remove('bg-gray-400', 'cursor-not-allowed');
        btn.classList.add('bg-green-600', 'hover:bg-green-700');
        icon.classList.remove('animate-spin');
        text.textContent = 'Sincronizar';
    }
    
    function mostrarResultado(tipo, data) {
        const alerta = document.getElementById('alertaResultado');
        
        if (tipo === 'success') {
            let nuevosHtml = '';
            if (data.tiposAgregados && data.tiposAgregados.length > 0) {
                nuevosHtml = '<div class="mt-2"><strong>Nuevos tipos agregados:</strong><ul class="list-disc list-inside mt-1">';
                data.tiposAgregados.forEach(t => {
                    nuevosHtml += `<li>${t}</li>`;
                });
                nuevosHtml += '</ul></div>';
            }
            
            alerta.innerHTML = `
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <p class="font-bold">¡Sincronización completada!</p>
                    </div>
                    <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-2 text-sm">
                        <div><strong>Ciudades:</strong> ${data.ciudadesConsultadas}</div>
                        <div><strong>Vehículos:</strong> ${data.vehiculosEncontrados}</div>
                        <div><strong>Tipos únicos:</strong> ${data.tiposUnicos}</div>
                        <div><strong>Nuevos:</strong> ${data.tiposNuevos}</div>
                    </div>
                    ${nuevosHtml}
                    ${data.tiposNuevos > 0 ? '<p class="mt-2 text-sm italic">La página se recargará en 5 segundos...</p>' : ''}
                </div>
            `;
        } else {
            alerta.innerHTML = `
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <p class="font-bold">Error en sincronización</p>
                    </div>
                    <p class="mt-2">${data.message}</p>
                </div>
            `;
        }
        
        alerta.classList.remove('hidden');
    }
</script>
@endpush
@endsection
