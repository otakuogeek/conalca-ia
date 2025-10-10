/**
 * Función para llamar conductores por grupo de cotización
 * Esta función puede ser llamada desde cualquier parte del frontend
 */

/**
 * Inicia llamadas conversacionales para todas las cotizaciones de un grupo
 * @param {number} groupCotizationId - ID del grupo de cotización
 * @param {string} prompt - Prompt opcional para las llamadas
 * @returns {Promise<Object>} - Resultado de las llamadas
 */
async function callDriversGroup(groupCotizationId, prompt = 'Llamada conversacional automática') {
    try {
        // Mostrar loading
        const loadingElement = document.getElementById('loading-calls');
        if (loadingElement) {
            loadingElement.style.display = 'block';
        }

        // Obtener token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        console.log('🚀 Iniciando llamadas asíncronas para grupo:', groupCotizationId);

        // Usar endpoint asíncrono para evitar timeout
        const response = await fetch('/api/call-drivers-group-async', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                group_cotization_id: groupCotizationId,
                prompt: prompt
            })
        });

        const result = await response.json();

        // Ocultar loading
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }

        if (result.success) {
            console.log('✅ Llamadas programadas asíncronamente:', result);
            
            // Mostrar notificación de éxito
            showNotification(`Llamadas programadas para ${result.total_cotizaciones} cotizaciones. Procesando en segundo plano...`, 'success');
            
            // Iniciar polling para verificar estado
            startStatusPolling(groupCotizationId);
            
            return result;
        } else {
            console.error('❌ Error en llamadas:', result);
            showNotification(result.error || 'Error al iniciar las llamadas', 'error');
            throw new Error(result.error || 'Error desconocido');
        }

    } catch (error) {
        console.error('❌ Error ejecutando llamadas:', error);
        
        // Ocultar loading en caso de error
        const loadingElement = document.getElementById('loading-calls');
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }

        // Mostrar notificación de error
        showNotification('Error de conexión o timeout. Intenta nuevamente.', 'error');
        
        throw error;
    }
}

/**
 * Hacer polling del estado de las llamadas
 */
function startStatusPolling(groupCotizationId) {
    let pollCount = 0;
    const maxPolls = 30; // 5 minutos máximo (30 x 10 segundos)
    
    const statusInterval = setInterval(async () => {
        try {
            pollCount++;
            
            const response = await fetch(`/api/call-drivers-group-status?group_cotization_id=${groupCotizationId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const status = await response.json();
            
            if (status.success) {
                const summary = status.summary;
                const totalCalls = summary.total_llamadas;
                const completedCalls = summary.completadas + summary.fallidas;
                const activeCalls = summary.con_conversation_id;
                
                console.log(`📊 Estado llamadas (${pollCount}/${maxPolls}):`, {
                    total: totalCalls,
                    completadas: completedCalls,
                    activas: activeCalls,
                    pendientes: summary.pendientes
                });
                
                // Actualizar UI con progreso
                updateCallsProgress(summary);
                
                // Detener polling si completado o máximo alcanzado
                if (summary.pendientes === 0 || pollCount >= maxPolls) {
                    clearInterval(statusInterval);
                    showNotification(`Llamadas completadas. Total: ${totalCalls}, Exitosas: ${activeCalls}`, 'info');
                }
            }
            
        } catch (error) {
            console.error('Error en polling de estado:', error);
            if (pollCount >= maxPolls) {
                clearInterval(statusInterval);
            }
        }
    }, 10000); // Cada 10 segundos
}

/**
 * Actualizar interfaz con progreso de llamadas
 */
function updateCallsProgress(summary) {
    // Buscar o crear elemento de progreso
    let progressElement = document.getElementById('calls-progress');
    if (!progressElement) {
        // Crear elemento de progreso si no existe
        progressElement = document.createElement('div');
        progressElement.id = 'calls-progress';
        progressElement.className = 'active';
        
        // Buscar un lugar para insertarlo (después del botón de llamadas)
        const callButton = document.querySelector('[data-group-call]');
        if (callButton && callButton.parentNode) {
            callButton.parentNode.insertBefore(progressElement, callButton.nextSibling);
        } else {
            document.body.appendChild(progressElement);
        }
    }
    
    const percentage = summary.total_llamadas > 0 
        ? Math.round(((summary.completadas + summary.fallidas) / summary.total_llamadas) * 100)
        : 0;
        
    progressElement.innerHTML = `
        <div class="progress-bar-container">
            <div class="progress-bar" style="width: ${percentage}%"></div>
        </div>
        <div class="progress-text">
            ${summary.con_conversation_id} llamadas con ElevenLabs activas de ${summary.total_llamadas} total
        </div>
        <div class="progress-details">
            <div class="progress-stat">
                <div class="progress-stat-value">${summary.total_llamadas}</div>
                <div class="progress-stat-label">Total</div>
            </div>
            <div class="progress-stat">
                <div class="progress-stat-value">${summary.con_conversation_id}</div>
                <div class="progress-stat-label">Activas</div>
            </div>
            <div class="progress-stat">
                <div class="progress-stat-value">${summary.completadas}</div>
                <div class="progress-stat-label">Completadas</div>
            </div>
            <div class="progress-stat">
                <div class="progress-stat-value">${summary.pendientes}</div>
                <div class="progress-stat-label">Pendientes</div>
            </div>
            <div class="progress-stat">
                <div class="progress-stat-value">${summary.fallidas}</div>
                <div class="progress-stat-label">Fallidas</div>
            </div>
        </div>
    `;
    
    progressElement.classList.add('active');
}

/**
 * Función de conveniencia para llamar desde botones HTML
 * @param {HTMLElement} button - El botón que disparó la acción
 * @param {number} groupCotizationId - ID del grupo de cotización
 */
async function handleCallDriversButtonClick(button, groupCotizationId) {
    try {
        // Deshabilitar botón
        const originalText = button.textContent;
        button.disabled = true;
        button.textContent = 'Registrando...';
        
        await callDriversGroup(groupCotizationId);
        
        // Cambiar texto del botón
        button.textContent = 'Llamadas Registradas';
        button.classList.add('bg-green-500');
        button.classList.remove('bg-red-500');
        
        // Revertir después de 3 segundos
        setTimeout(() => {
            button.disabled = false;
            button.textContent = originalText;
            button.classList.remove('bg-green-500');
            button.classList.add('bg-red-500');
        }, 3000);
        
    } catch (error) {
        // Revertir botón en caso de error
        button.disabled = false;
        button.textContent = originalText;
    }
}

/**
 * Actualizar la interfaz después de las llamadas
 * @param {Object} result - Resultado de las llamadas
 */
function updateCallsInterface(result) {
    // Actualizar contador de conductores si existe
    const conductorCountElement = document.querySelector('[data-conductor-count]');
    if (conductorCountElement) {
        conductorCountElement.textContent = result.summary.total_drivers_called;
    }
    
    // Actualizar lista de conductores llamados si existe
    const driversListElement = document.querySelector('[data-drivers-list]');
    if (driversListElement) {
        let driversHtml = '';
        result.results.forEach(cotizacionResult => {
            if (cotizacionResult.success) {
                cotizacionResult.drivers_called.forEach(driver => {
                    driversHtml += `
                        <div class="driver-called-item p-2 border-b">
                            <strong>${driver.driver_name}</strong><br>
                            <small>Tel: ${driver.phone} | Estado: ${driver.status}</small><br>
                            <small>Cotización: ${cotizacionResult.cotizacion_id} | ${cotizacionResult.cotizacion_details.origen} → ${cotizacionResult.cotizacion_details.destino}</small>
                        </div>
                    `;
                });
            }
        });
        driversListElement.innerHTML = driversHtml;
    }
}

/**
 * Mostrar notificación al usuario
 * @param {string} message - Mensaje a mostrar
 * @param {string} type - Tipo de notificación (success, error, info)
 */
function showNotification(message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 max-w-sm ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    notification.textContent = message;
    
    // Agregar al DOM
    document.body.appendChild(notification);
    
    // Remover después de 5 segundos
    setTimeout(() => {
        notification.remove();
    }, 5000);
    
    // También agregar a console
    console.log(`📢 ${type.toUpperCase()}: ${message}`);
}

/**
 * Función de inicialización para configurar event listeners
 */
function initializeGroupCalls() {
    // Buscar todos los botones con atributo data-group-call
    document.querySelectorAll('[data-group-call]').forEach(button => {
        button.addEventListener('click', async function() {
            const groupId = this.getAttribute('data-group-call');
            if (groupId) {
                await handleCallDriversButtonClick(this, parseInt(groupId));
            }
        });
    });
    
    console.log('✅ Sistema de llamadas por grupo inicializado');
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeGroupCalls);
} else {
    initializeGroupCalls();
}

// Exponer funciones globalmente para uso desde HTML inline
window.callDriversGroup = callDriversGroup;
window.handleCallDriversButtonClick = handleCallDriversButtonClick;