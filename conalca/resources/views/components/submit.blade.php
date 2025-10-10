{{-- Componente de botón Enviar --}}
<div class="mt-6 w-full text-center cursor-pointer">
    {{--  Botón que funcionará con el sistema jQuery/AJAX existente  --}}
    <button type="button" id="submitButton"
            {{ $attributes->merge(['class' => 'px-6 py-3 bg-[#ff7c32] text-white rounded-md hover:bg-[#e6672b] transition-colors']) }}>
        Enviar
    </button>
</div>