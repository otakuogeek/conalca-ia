{{-- shoQuoteModal --}}

{{-- row 1 --}}
<div
    class="w-full md:w-[28rem] min-h-[15.13rem] bg-white rounded-[0.82rem] shadow-md pt-[1.06rem] pl-6 flex flex-col items-start justify-start">
    <h4 class="text-[#202020] text-[1.44rem] font-bold uppercase leading-normal mb-[0.88rem]">Carga</h4>
    <small class="text-[#898989] text-[0.625rem] font-normal leading-[1.03125rem]">{{ $quote->descripcion_mercancia }}</small>
    <small class="text-[#898989] text-[0.625rem] font-normal leading-[1.03125rem]">{{ $quote->producto }}</small>
    <small class="text-[#898989] text-[0.625rem] font-normal leading-[1.03125rem]">Valor seguro</small>
</div>
{{-- end row 1 --}}
{{-- row 2 --}}
<div
    class="w-full md:w-[28rem] min-h-[8.125rem] rounded-[0.82rem] bg-white shadow-md pt-[0.81rem] pr-6 pb-4 pl-[1.81rem] flex flex-col
md:flex-row items-start justify-center md:justify-between">
    <div class="flex flex-col">
        <h4 class="text-[#202020] text-[1.44rem] font-bold uppercase leading-normal mb-2">Conductor</h4>
        <label for="contact_person" class="text-[0.625rem] text-[#89898992] leading-[1.03125rem] font-normal">
            Nombre persona contacto</label>
        <input type="text" placeholder="Nombre persona contacto" name="contact_person" value="{{ $quote->conductor }}"
            class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989] font-normal leading-[1.03125rem]" />
        <label for="phone" class="text-[0.625rem] text-[#89898992] leading-[1.03125rem] font-normal">
            Teléfono</label>
        <input type="text" placeholder="Número de teléfono" name="phone"
            class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989] font-normal leading-[1.03125rem]" />
    </div>
    <div class="flex flex-col">
        <button
            class="flex flex-row items-center justify-center text-white bg-[#FF7C32] w-[5.38rem] h-6 text-xs font-medium leading-normal
        gap-[0.37rem] self-end mb-[0.63rem] rounded-[0.38rem]">
            <span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"
                    fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M3.31 5.395C4.03 6.81 5.19 7.965 6.605 8.69L7.705 7.59C7.84 7.455 8.04 7.41 8.215 7.47C8.775 7.655 9.38 7.755 10 7.755C10.275 7.755 10.5 7.98 10.5 8.255V10C10.5 10.275 10.275 10.5 10 10.5C5.305 10.5 1.5 6.695 1.5 2C1.5 1.725 1.725 1.5 2 1.5H3.75C4.025 1.5 4.25 1.725 4.25 2C4.25 2.625 4.35 3.225 4.535 3.785C4.59 3.96 4.55 4.155 4.41 4.295L3.31 5.395Z"
                        fill="white" />
                </svg>
            </span>
            Lamar
        </button>
        <label for="vehicle" class="text-[0.625rem] text-[#89898992] leading-[1.03125rem] font-normal self-start">
            Teléfono</label>
        <input type="text" placeholder="Placa ZTC33A" name="vehicle"
            class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989] font-normal leading-[1.03125rem] self-start" />
    </div>
</div>
{{-- end row 2 --}}
{{-- row 3 --}}
<div class="w-full md:w-[28rem] min-h-[23.19rem] rounded-[0.82rem] bg-white pt-[1.69rem] flex flex-col mb-4 md:mb-0">
    <div class="flex flex-row flex-wrap w-full items-center justify-between pl-[1.81rem] pr-7 mb-[0.94rem]">
        <h4 class="text-[#202020] text-[1.44rem] font-bold uppercase leading-normal mb-2">En transito</h4>
        <div class="flex gap-4 items-center justify-center">
            <span>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                    xmlns:xlink="http://www.w3.org/1999/xlink">
                    <rect width="24" height="24" fill="url(#pattern0)" />
                    <defs>
                        <pattern id="pattern0" patternContentUnits="objectBoundingBox" width="1" height="1">
                            <use xlink:href="#image0_109_1280" transform="scale(0.0078125)" />
                        </pattern>
                        <image id="image0_109_1280" width="128" height="128"
                            xlink:href="data:image/png;base64,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" />
                    </defs>
                </svg>
            </span>
            <span class="w-6 h-6 rounded-full bg-[#04D000]"></span>
        </div>
    </div>
    {{-- ToDo: change for google maps --}}
    <!-- <img src="{{ asset('img/map.png') }}" alt="google-maps"
        class="w-full h-[18.82rem] rounded-[0.82rem] object-cover" /> -->
</div>
{{-- end row 3 --}}
