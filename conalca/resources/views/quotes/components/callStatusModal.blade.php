<!-- Modal de Estado de Llamadas -->
<dialog id="call_status_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
        </form>
        
        <h3 class="font-bold text-lg mb-4">📞 Estado de las Llamadas</h3>
        
        <!-- Header con resumen -->
        <div class="bg-base-200 p-4 rounded-lg mb-4">
            <div class="grid grid-cols-3 gap-4 text-center">
                <div class="stat">
                    <div class="stat-title">Total Enviadas</div>
                    <div class="stat-value text-primary" id="total-calls">0</div>
                    <div class="stat-desc">llamadas realizadas</div>
                </div>
                <div class="stat">
                    <div class="stat-title">Exitosas</div>
                    <div class="stat-value text-success" id="successful-calls">0</div>
                    <div class="stat-desc">enviadas correctamente</div>
                </div>
                <div class="stat">
                    <div class="stat-title">Atendidas</div>
                    <div class="stat-value text-info" id="answered-calls">0</div>
                    <div class="stat-desc">respondieron</div>
                </div>
            </div>
        </div>

        <!-- Lista de llamadas individuales -->
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Conductor</th>
                        <th>Teléfono</th>
                        <th>Estado</th>
                        <th>Resultado</th>
                        <th>Duración</th>
                    </tr>
                </thead>
                <tbody id="calls-table-body">
                    <!-- Las filas se llenarán dinámicamente -->
                </tbody>
            </table>
        </div>

        <!-- Progreso en tiempo real -->
        <div class="mt-4">
            <div class="flex justify-between text-sm mb-2">
                <span>Progreso de llamadas</span>
                <span id="progress-text">0%</span>
            </div>
            <progress class="progress progress-primary w-full" value="0" max="100" id="calls-progress"></progress>
        </div>

        <!-- Acciones -->
        <div class="modal-action">
            <button class="btn btn-secondary" onclick="retryFailedCalls()" id="retry-btn">
                🔄 Reintentar Fallidas
            </button>
            <button class="btn btn-primary" onclick="refreshCallStatus()">
                🔄 Actualizar Estado
            </button>
            <button class="btn" onclick="call_status_modal.close()">Cerrar</button>
        </div>
    </div>
</dialog>

<script>
// Variable global para almacenar el ID de la llamada actual
let currentCallId = null;
let callStatusInterval = null;

// Función para mostrar el modal con datos iniciales
function showCallStatusModal(callData) {
    currentCallId = callData.call_id;
    
    // Actualizar estadísticas iniciales
    document.getElementById('total-calls').textContent = callData.total_drivers || 0;
    document.getElementById('successful-calls').textContent = callData.successful_calls || 0;
    document.getElementById('answered-calls').textContent = 0; // Se actualizará con el seguimiento
    
    // Limpiar tabla
    const tableBody = document.getElementById('calls-table-body');
    tableBody.innerHTML = '';
    
    // Agregar filas iniciales si hay resultados
    if (callData.results) {
        callData.results.forEach(result => {
            addCallRow(result);
        });
    }
    
    // Actualizar progreso
    updateProgress(callData.successful_calls, callData.total_drivers);
    
    // Mostrar modal
    document.getElementById('call_status_modal').showModal();
    
    // Iniciar actualización automática cada 5 segundos
    if (callStatusInterval) {
        clearInterval(callStatusInterval);
    }
    callStatusInterval = setInterval(refreshCallStatus, 5000);
}

// Función para agregar una fila de llamada a la tabla
function addCallRow(callResult) {
    const tableBody = document.getElementById('calls-table-body');
    const row = document.createElement('tr');
    
    const statusBadge = getStatusBadge(callResult.call_status || callResult.status);
    const outcomeBadge = getOutcomeBadge(callResult.response_status || callResult.outcome);
    const retryInfo = callResult.retry_count > 0 ? ` (Intento #${callResult.retry_count + 1})` : '';
    
    row.innerHTML = `
        <td>${(callResult.driver_name || callResult.driver || 'N/A') + retryInfo}</td>
        <td>${callResult.driver_phone || callResult.phone || 'N/A'}</td>
        <td>${statusBadge}</td>
        <td>${outcomeBadge}</td>
        <td>${callResult.call_duration || callResult.duration || '-'}</td>
    `;
    
    tableBody.appendChild(row);
}

// Función para obtener el badge del estado
function getStatusBadge(status) {
    const badges = {
        'success': '<span class="badge badge-success">✅ Enviada</span>',
        'calling': '<span class="badge badge-warning">📞 Llamando</span>',
        'answered': '<span class="badge badge-success">✅ Atendida</span>',
        'no_answer': '<span class="badge badge-warning">📵 No Contestó</span>',
        'busy': '<span class="badge badge-warning">📞 Ocupado</span>',
        'failed': '<span class="badge badge-error">❌ Falló</span>',
        'error': '<span class="badge badge-error">❌ Error</span>',
        'completed': '<span class="badge badge-info">✅ Completada</span>'
    };
    return badges[status] || '<span class="badge badge-neutral">? Desconocido</span>';
}

// Función para obtener el badge del resultado
function getOutcomeBadge(outcome) {
    const badges = {
        'pending': '<span class="badge badge-neutral">⏳ Pendiente</span>',
        'accepted': '<span class="badge badge-success">✅ Aceptó</span>',
        'rejected': '<span class="badge badge-error">❌ Rechazó</span>',
        'answered': '<span class="badge badge-success">✅ Atendida</span>',
        'no_answer': '<span class="badge badge-warning">📵 No Contestó</span>',
        'busy': '<span class="badge badge-warning">📞 Ocupado</span>',
        'failed': '<span class="badge badge-error">❌ Falló</span>',
        'voicemail': '<span class="badge badge-info">📧 Buzón</span>'
    };
    return badges[outcome] || '<span class="badge badge-neutral">⏳ Pendiente</span>';
}

// Función para actualizar el progreso
function updateProgress(completed, total) {
    if (total > 0) {
        const percentage = Math.round((completed / total) * 100);
        document.getElementById('calls-progress').value = percentage;
        document.getElementById('progress-text').textContent = `${percentage}%`;
    }
}

// Función para refrescar el estado de las llamadas
async function refreshCallStatus() {
    if (!currentCallId) return;
    
    try {
        const response = await fetch(`/api/calls/${currentCallId}/status`);
        const data = await response.json();
        
        if (data.success) {
            // Actualizar estadísticas
            document.getElementById('total-calls').textContent = data.total_calls || 0;
            document.getElementById('successful-calls').textContent = data.successful_calls || 0;
            document.getElementById('answered-calls').textContent = data.answered_calls || 0;
            
            // Actualizar tabla
            const tableBody = document.getElementById('calls-table-body');
            tableBody.innerHTML = '';
            
            if (data.calls) {
                data.calls.forEach(call => {
                    addCallRow(call);
                });
            }
            
            // Actualizar progreso
            updateProgress(data.completed_calls, data.total_calls);
            
            // Si todas las llamadas están completadas, detener la actualización automática
            if (data.all_completed) {
                clearInterval(callStatusInterval);
                callStatusInterval = null;
            }
        }
    } catch (error) {
        console.error('Error al actualizar estado de llamadas:', error);
    }
}

// Función para reintentar llamadas fallidas
async function retryFailedCalls() {
    if (!currentCallId) {
        alert('No hay llamada activa para reintentar');
        return;
    }
    
    const retryBtn = document.getElementById('retry-btn');
    const originalText = retryBtn.innerHTML;
    
    try {
        // Cambiar botón a estado de carga
        retryBtn.innerHTML = '⏳ Reintentando...';
        retryBtn.disabled = true;
        
        // Obtener cotización ID de la llamada actual
        const statusResponse = await fetch(`/api/calls/${currentCallId}/status`);
        const statusData = await statusResponse.json();
        
        if (!statusData.success) {
            throw new Error('No se pudo obtener información de la llamada');
        }
        
        // Hacer petición de reintentos
        const retryResponse = await fetch('/api/retry-calls', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                cotizacion_model_id: statusData.cotizacion_id || currentCallId
            })
        });
        
        const retryData = await retryResponse.json();
        
        if (retryResponse.ok) {
            alert(`Reintentos procesados: ${retryData.total_retries_processed}, Exitosos: ${retryData.successful_retries}`);
            // Actualizar estado inmediatamente
            refreshCallStatus();
        } else {
            throw new Error(retryData.error || 'Error al procesar reintentos');
        }
        
    } catch (error) {
        console.error('Error al reintentar llamadas:', error);
        alert('Error al reintentar llamadas: ' + error.message);
    } finally {
        // Restaurar botón
        retryBtn.innerHTML = originalText;
        retryBtn.disabled = false;
    }
}

// Limpiar intervalo al cerrar el modal
document.getElementById('call_status_modal').addEventListener('close', function() {
    if (callStatusInterval) {
        clearInterval(callStatusInterval);
        callStatusInterval = null;
    }
});
</script>