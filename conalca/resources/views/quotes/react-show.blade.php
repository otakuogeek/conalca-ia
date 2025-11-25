@extends('layout.app')

@section('title', 'Gestión de Cotizaciones - React')

@section('content')
    <div class="p-4">
        <!-- Indicador visual de que estamos en React -->
      

        <div class="flex flex-row space-x-4">
            <div class="text-black mb-4 rounded flex-1">
                <!-- Contenedor para nuestro componente React -->
                <div id="quote-index-react-root"></div>
            </div>
            <div id="my-goal-progress" class="flex-1"></div>
        </div>
            
        <div class="text-black rounded">
            <div id="channel-with-custom-columns"></div>
        </div>
        <div class="text-black p-4 rounded">
            @livewire('drog-zone')
        </div>
    </div>

    @viteReactRefresh
    @vite(['resources/js/app.jsx', 'resources/js/quotes-react.jsx'])

    <script>
        // Test para confirmar que JavaScript está funcionando
        document.addEventListener('DOMContentLoaded', function() {
            const testBtn = document.getElementById('test-react-btn');
            if (testBtn) {
                testBtn.addEventListener('click', function() {
                    alert('¡JavaScript funcionando! React debería estar activo.');
                });
            }
        });
    </script>
@endsection