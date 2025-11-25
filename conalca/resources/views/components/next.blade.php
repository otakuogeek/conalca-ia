@props(['num' => ''])   {{-- $num = "", "2", "3" … --}}

@php
    // id igual a como lo esperaba tu JS (#next-btn, #next-btn-2 …)
    $id = $num ? "next-btn-{$num}" : 'next-btn';
@endphp

<div class="mt-6 w-full text-center cursor-pointer">
    <button type="button" wire:click="nextStep" id="{{ $id }}" 
            {{ $attributes->merge(['class' => 'px-6 py-3 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors']) }}>
        Siguiente
    </button>
</div>