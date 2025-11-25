<div class="p-4">
    <h1 class="text-2xl font-bold mb-4">Test Componente Livewire Simple</h1>
    <div class="bg-green-100 p-4 rounded">
        <p>{{ $test_message }}</p>
        <p>Si ves este mensaje, Livewire está funcionando correctamente.</p>
    </div>
    
    <div class="mt-4">
        <button wire:click="$refresh" class="bg-blue-500 text-white px-4 py-2 rounded">
            Actualizar Componente
        </button>
    </div>
</div>