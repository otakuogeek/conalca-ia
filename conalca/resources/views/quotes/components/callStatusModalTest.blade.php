<!-- Script de prueba para el modal de llamadas -->
<script>
// Datos de prueba para simular el modal
const testCallData = {
    success: true,
    message: "Llamadas iniciadas: 3 exitosas, 0 fallidas",
    call_id: 123,
    total_drivers: 3,
    successful_calls: 3,
    failed_calls: 0,
    results: [
        {
            driver: "Juan Pérez",
            phone: "+573001234567",
            status: "success",
            call_sid: "CA123456789"
        },
        {
            driver: "María García",
            phone: "+573009876543",
            status: "success", 
            call_sid: "CA987654321"
        },
        {
            driver: "Carlos López",
            phone: "+573005555555",
            status: "success",
            call_sid: "CA555555555"
        }
    ]
};

// Función para probar el modal (será reemplazada por la integración real)
function testCallStatusModal() {
    if (typeof showCallStatusModal === 'function') {
        showCallStatusModal(testCallData);
    } else {
        console.error('showCallStatusModal function not found');
    }
}

// Evento para prueba durante desarrollo
document.addEventListener('DOMContentLoaded', function() {
    // Agregar botón de prueba si estamos en modo debug
    if (window.location.search.includes('debug=1')) {
        const testButton = document.createElement('button');
        testButton.textContent = '🧪 Probar Modal de Llamadas';
        testButton.className = 'btn btn-warning fixed top-4 right-4 z-50';
        testButton.onclick = testCallStatusModal;
        document.body.appendChild(testButton);
    }
});
</script>