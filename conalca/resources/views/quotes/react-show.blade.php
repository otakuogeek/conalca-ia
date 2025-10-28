@extends('layout.app')

@section('title', 'Gestión de Cotizaciones - React')

@section('content')
    <div class="p-4">
        <!-- Indicador visual de que estamos en React -->
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <strong>Modo React Activo</strong> - Esta página está usando componentes React
                <div class="ml-4 flex space-x-2">
                    <a href="/quotes-livewire" class="bg-orange-500 hover:bg-orange-700 text-white font-bold py-1 px-3 rounded text-sm">
                        Ver Livewire Original
                    </a>
                    <button id="test-react-btn" class="bg-green-500 hover:bg-green-700 text-white font-bold py-1 px-3 rounded text-sm">
                        Test React
                    </button>
                </div>
            </div>
        </div>

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