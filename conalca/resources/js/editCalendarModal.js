// Verificar que el DOM esté completamente cargado antes de añadir event listeners
if (document.readyState === 'loading') {
    document.addEventListener("DOMContentLoaded", function () {
        // Código de inicialización del modal aquí
        console.log('Modal calendar script loaded');
    });
} else {
    // DOM ya está cargado
    console.log('Modal calendar script loaded (DOM ready)');
}
