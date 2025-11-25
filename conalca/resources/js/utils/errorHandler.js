/**
 * Sistema Global de Manejo de Errores
 * Captura y muestra errores en un modal para debugging
 */

class ErrorHandler {
    constructor() {
        this.errors = [];
        this.initModal();
        this.setupGlobalErrorHandler();
    }

    initModal() {
        // Crear modal si no existe
        if (!document.getElementById('global-error-modal')) {
            const modalHTML = `
                <div id="global-error-modal" class="fixed inset-0 z-[9999] hidden bg-black bg-opacity-50 flex items-center justify-center p-4">
                    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                        <div class="bg-red-600 text-white px-6 py-4 flex justify-between items-center">
                            <h3 class="text-xl font-bold flex items-center">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Error Detectado
                            </h3>
                            <button onclick="window.errorHandler.closeModal()" class="text-white hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
                            <div id="error-content" class="space-y-4"></div>
                        </div>
                        <div class="bg-gray-100 px-6 py-4 flex justify-between items-center border-t">
                            <button onclick="window.errorHandler.copyErrors()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                                📋 Copiar Errores
                            </button>
                            <div class="flex gap-2">
                                <button onclick="window.errorHandler.clearErrors()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
                                    🗑️ Limpiar
                                </button>
                                <button onclick="window.errorHandler.closeModal()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }
    }

    setupGlobalErrorHandler() {
        // Capturar errores JavaScript globales
        window.addEventListener('error', (event) => {
            this.logError({
                type: 'JavaScript Error',
                message: event.message,
                file: event.filename,
                line: event.lineno,
                column: event.colno,
                stack: event.error?.stack,
                timestamp: new Date().toISOString()
            });
        });

        // Capturar promesas rechazadas no manejadas
        window.addEventListener('unhandledrejection', (event) => {
            this.logError({
                type: 'Unhandled Promise Rejection',
                message: event.reason?.message || event.reason,
                stack: event.reason?.stack,
                timestamp: new Date().toISOString()
            });
        });

        // Capturar errores de recursos (imágenes, scripts, etc)
        window.addEventListener('error', (event) => {
            if (event.target !== window) {
                this.logError({
                    type: 'Resource Load Error',
                    message: `Failed to load: ${event.target.src || event.target.href}`,
                    element: event.target.tagName,
                    timestamp: new Date().toISOString()
                }, false); // No mostrar modal automáticamente para recursos
            }
        }, true);

        // Interceptar errores de console.error
        const originalConsoleError = console.error;
        console.error = (...args) => {
            this.logError({
                type: 'Console Error',
                message: args.map(arg => 
                    typeof arg === 'object' ? JSON.stringify(arg, null, 2) : String(arg)
                ).join(' '),
                timestamp: new Date().toISOString()
            }, false); // No mostrar modal automáticamente
            originalConsoleError.apply(console, args);
        };
    }

    logError(error, showModal = true) {
        this.errors.push(error);
        console.group(`🔴 ${error.type}`);
        console.error('Message:', error.message);
        if (error.file) console.log('File:', `${error.file}:${error.line}:${error.column}`);
        if (error.stack) console.log('Stack:', error.stack);
        console.log('Time:', error.timestamp);
        console.groupEnd();

        if (showModal) {
            this.showModal();
        }
        
        this.updateModalContent();
    }

    showModal() {
        const modal = document.getElementById('global-error-modal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    }

    closeModal() {
        const modal = document.getElementById('global-error-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    updateModalContent() {
        const content = document.getElementById('error-content');
        if (!content) return;

        content.innerHTML = this.errors.map((error, index) => `
            <div class="border border-red-300 rounded-lg p-4 bg-red-50">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex items-center">
                        <span class="bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-2">
                            ${index + 1}
                        </span>
                        <span class="font-bold text-red-800">${error.type}</span>
                    </div>
                    <span class="text-sm text-gray-600">${new Date(error.timestamp).toLocaleTimeString()}</span>
                </div>
                <div class="ml-8 space-y-2">
                    <div class="bg-white p-3 rounded border border-red-200">
                        <p class="font-semibold text-sm text-red-700 mb-1">Mensaje:</p>
                        <pre class="text-sm text-gray-800 whitespace-pre-wrap break-words">${this.escapeHtml(error.message)}</pre>
                    </div>
                    ${error.file ? `
                        <div class="bg-white p-3 rounded border border-red-200">
                            <p class="font-semibold text-sm text-red-700 mb-1">Ubicación:</p>
                            <p class="text-sm text-gray-800">${error.file}:${error.line}:${error.column}</p>
                        </div>
                    ` : ''}
                    ${error.stack ? `
                        <details class="bg-white p-3 rounded border border-red-200">
                            <summary class="font-semibold text-sm text-red-700 cursor-pointer">Stack Trace</summary>
                            <pre class="text-xs text-gray-700 mt-2 whitespace-pre-wrap break-words max-h-40 overflow-y-auto">${this.escapeHtml(error.stack)}</pre>
                        </details>
                    ` : ''}
                    ${error.element ? `
                        <div class="bg-white p-3 rounded border border-red-200">
                            <p class="font-semibold text-sm text-red-700 mb-1">Elemento:</p>
                            <p class="text-sm text-gray-800">${error.element}</p>
                        </div>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    clearErrors() {
        this.errors = [];
        this.updateModalContent();
        this.closeModal();
    }

    copyErrors() {
        const errorText = this.errors.map((error, index) => {
            let text = `Error #${index + 1} - ${error.type}\n`;
            text += `Timestamp: ${error.timestamp}\n`;
            text += `Message: ${error.message}\n`;
            if (error.file) text += `Location: ${error.file}:${error.line}:${error.column}\n`;
            if (error.stack) text += `Stack: ${error.stack}\n`;
            text += '\n' + '='.repeat(80) + '\n\n';
            return text;
        }).join('');

        navigator.clipboard.writeText(errorText).then(() => {
            alert('✅ Errores copiados al portapapeles');
        }).catch(err => {
            console.error('Error al copiar:', err);
        });
    }

    // Método para reportar errores manualmente
    reportError(message, details = {}) {
        this.logError({
            type: 'Manual Report',
            message,
            ...details,
            timestamp: new Date().toISOString()
        });
    }
}

// Inicializar el manejador de errores cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.errorHandler = new ErrorHandler();
    });
} else {
    window.errorHandler = new ErrorHandler();
}

// Exportar para usar en módulos
export default ErrorHandler;
