{{--
Componente de botón para llamar conductores por grupo de cotización
Uso: @include('components.call-drivers-group-button', ['groupId' => 54, 'label' => 'Llamar conductores'])
--}}

@props([
    'groupId' => null,
    'label' => 'Llamar conductores',
    'class' => 'bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors duration-200',
    'loadingText' => 'Registrando...',
    'disabled' => false
])

@if($groupId)
<div class="relative inline-block">
    <button 
        data-group-call="{{ $groupId }}"
        class="{{ $class }} {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
        {{ $disabled ? 'disabled' : '' }}
        title="Registrar llamadas para todas las cotizaciones del grupo {{ $groupId }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none" class="inline mr-2">
            <path fill-rule="evenodd" clip-rule="evenodd"
                d="M4.41333 7.19333C5.37333 9.08 6.92 10.62 8.80667 11.5867L10.2733 10.12C10.4533 9.94 10.72 9.88 10.9533 9.96C11.7 10.2067 12.5067 10.34 13.3333 10.34C13.7 10.34 14 10.64 14 11.0067V13.3333C14 13.7 13.7 14 13.3333 14C7.07333 14 2 8.92667 2 2.66667C2 2.3 2.3 2 2.66667 2H5C5.36667 2 5.66667 2.3 5.66667 2.66667C5.66667 3.5 5.8 4.3 6.04667 5.04667C6.12 5.28 6.06667 5.54 5.88 5.72667L4.41333 7.19333Z"
                fill="currentColor" />
        </svg>
        {{ $label }}
    </button>
    
    <!-- Loading indicator -->
    <div id="calling-loading" class="hidden absolute inset-0 bg-black bg-opacity-50 rounded-lg flex items-center justify-center">
        <div class="text-white text-sm">{{ $loadingText }}</div>
    </div>
</div>
@else
<div class="text-red-500 text-sm">
    Error: No se proporcionó el ID del grupo de cotización
</div>
@endif