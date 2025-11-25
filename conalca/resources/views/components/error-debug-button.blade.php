<!-- Botón flotante para abrir el modal de errores -->
<div id="error-debug-button" class="fixed bottom-4 right-4 z-[9998]">
    <button 
        onclick="window.errorHandler?.showModal()" 
        class="bg-red-600 hover:bg-red-700 text-white rounded-full p-4 shadow-lg transition-all duration-200 hover:scale-110 flex items-center justify-center group"
        title="Ver errores del sistema"
    >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span id="error-count" class="absolute -top-2 -right-2 bg-yellow-500 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center hidden">0</span>
    </button>
</div>

<script>
// Actualizar contador de errores
if (window.errorHandler) {
    const originalLogError = window.errorHandler.logError.bind(window.errorHandler);
    window.errorHandler.logError = function(...args) {
        originalLogError(...args);
        updateErrorCount();
    };
    
    const originalClearErrors = window.errorHandler.clearErrors.bind(window.errorHandler);
    window.errorHandler.clearErrors = function(...args) {
        originalClearErrors(...args);
        updateErrorCount();
    };
}

function updateErrorCount() {
    const countElement = document.getElementById('error-count');
    const count = window.errorHandler?.errors?.length || 0;
    if (countElement) {
        if (count > 0) {
            countElement.textContent = count;
            countElement.classList.remove('hidden');
        } else {
            countElement.classList.add('hidden');
        }
    }
}

// Actualizar al cargar
document.addEventListener('DOMContentLoaded', updateErrorCount);
</script>

<style>
#error-debug-button button {
    animation: pulse-slow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse-slow {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.8;
    }
}

#error-debug-button button:hover {
    animation: none;
}
</style>
