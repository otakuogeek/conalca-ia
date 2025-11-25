{{-- showQuoteModal --}}

{{-- row 1 --}}
<div
    class="flex flex-row flex-wrap bg-white shadow-md w-full md:w-[28rem] md:h-[10.88rem] rounded-[0.82rem] pl-[1.81rem]
pt-[1.25rem] pr-[1.12rem] items-start justify-between">
    <div class="flex flex-col justify-start">
        <div class="flex flex-row items-center text-[#898989] text-[0.625rem] font-normal leading-normal gap-[14%]">
            <span>N° Cotización</span>
            <span>Nombre de la empresa</span>
        </div>
        <h4 class="text-[#898989] text-[1.44rem] font-normal leading-normal uppercase">
            IDC001 | <span class="text-[#202020]
        font-bold uppercase">
                Empresa...</span>
        </h4>
        <button
            class="bg-[#cacaca] w-[5.88rem] h-[1.07rem] rounded text-center text-white text-[0.57rem] font-medium
    leading-normal mt-2">Alerta</button>
        <span class="mt-3 w-full md:w-[15.63rem] text-[#898989] text-[0.625rem] font-normal leading-[0.875rem]">
            Lorem ipsum es el texto que se usa habitualmente en diseño gráfico en demostraciones de
            tipografías o de borradores de diseño para probar el diseño visual antes de insertar el
            texto final.
        </span>
    </div>
    <div class="flex flex-col items-end md:justify-between h-full">
        <span
            class="flex flex-col items-center justify-center text-[#898989] text-[0.625rem] font-normal leading-[1.03125rem]">
            {{-- ToDo: change icon --}}
            <svg width="31" height="31" viewBox="0 0 31 31" fill="none" xmlns="http://www.w3.org/2000/svg"
                xmlns:xlink="http://www.w3.org/1999/xlink">
                <rect y="-6.10352e-05" width="31" height="31" fill="url(#pattern0)" fill-opacity="0.6" />
                <defs>
                    <pattern id="pattern0" patternContentUnits="objectBoundingBox" width="1" height="1">
                        <use xlink:href="#image0_109_1265" transform="scale(0.0078125)" />
                    </pattern>
                    <image id="image0_109_1265" width="128" height="128"
                        xlink:href="data:image/png;base64,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" />
                </defs>
            </svg>
            27 Junio
        </span>
        <button
            class="flex flex-row items-center justify-center gap-2 bg-[#FF7C32] w-[7.19rem] h-8 rounded-lg text-white
    text-sm font-medium leading-normal mb-[1.56rem]">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M4.41333 7.19333C5.37333 9.08 6.92 10.62 8.80667 11.5867L10.2733 10.12C10.4533 9.94 10.72 9.88 10.9533 9.96C11.7 10.2067 12.5067 10.34 13.3333 10.34C13.7 10.34 14 10.64 14 11.0067V13.3333C14 13.7 13.7 14 13.3333 14C7.07333 14 2 8.92667 2 2.66667C2 2.3 2.3 2 2.66667 2H5C5.36667 2 5.66667 2.3 5.66667 2.66667C5.66667 3.5 5.8 4.3 6.04667 5.04667C6.12 5.28 6.06667 5.54 5.88 5.72667L4.41333 7.19333Z"
                    fill="white" />
            </svg>
            Llamar
        </button>
    </div>
</div>
{{-- row 2 --}}
<div
    class="w-full md:w-[28rem] min-h-[14.82rem] rounded-[0.82rem] bg-white shadow-md pt-[1.12rem] pr-[0.81rem]
p-6 pl-[1.87rem] flex flex-col">
    <span class="w-full text-[0.625rem] -mb-2">Datos empresa</span>
    <h4 class="text-[#202020] text-[1.44rem] font-bold uppercase">Empresa...</h4>
    <div class="flex flex-col md:flex-row justify-center md:justify-between mt-[0.51rem]">
        <div class="flex flex-col items-start justify-start">
            <label for="nit" class="w-full text-[0.625rem]">
                Nit</label>
            <input type="text" placeholder="000000000-0" name="nit" class="text-[0.94rem] -mt-1 mb-[0.44rem]" />
            <label for="sector" class="w-full text-[0.625rem]">
                Sector económico</label>
            <input type="text" placeholder="Agroindustria" name="sector"
                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem]" />
            <label for="address" class="w-full text-[0.625rem]">
                Dirección</label>
            <input type="text" placeholder="Cra 9 # 0 -0" name="address" class="text-[0.94rem] -mt-1 mb-[0.44rem]" />
            <label for="email" class="w-full text-[0.625rem]">
                Correo</label>
            <input type="email" placeholder="correo@correo.com" name="email"
                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] md:mb-0" />
        </div>
        <div class="flex flex-col items-start justify-start">
            <label for="employee_type" class="w-full text-[0.625rem]">
                Cargo</label>
            <input type="text" placeholder="Gerente" name="employee_type"
                class="text-[0.94rem] -mt-1 mb-[0.44rem]" />
            <label for="contact_name" class="w-full text-[0.625rem]">
                Nombre persona contacto</label>
            <input type="text" placeholder="Nombre persona contacto" name="contact_name"
                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem]" />
            <label for="phone" class="w-full text-[0.625rem]">
                Teléfono</label>
            <input type="tel" placeholder="Número de teléfono" name="phone"
                class="text-[0.94rem] -mt-1 mb-[0.44rem]" />
            <label for="city" class="w-full text-[0.625rem]">
                Ciudad</label>
            <input type="text" placeholder="Bogota" name="city" class="text-[0.94rem] -mt-[0.15rem]" />
        </div>
    </div>
</div>
{{-- row 3 --}}
<div
    class="w-full md:w-[28rem] min-h-[12.32rem] rounded-[0.82rem] pt-7 pr-[1.38rem] pb-4 md:pb-0 pl-[1.31rem]
flex flex-col mb-4 md:mb-0">
    <h4 class="text-[#202020] text-[1.44rem] font-bold uppercase leading-normal mb-[0.69rem]">Rutas creadas</h4>
    <div
        class="flex flex-row flex-wrap items-center justify-start w-full md:w-[25.32rem] min-h-[3.69rem] rounded-[0.82rem]
bg-[#F9F0DF] gap-[1.63rem]">
        {{-- ToDo: change icon --}}
        <span class="ml-4">
            <svg width="32" height="31" viewBox="0 0 32 31" fill="none" xmlns="http://www.w3.org/2000/svg"
                xmlns:xlink="http://www.w3.org/1999/xlink">
                <rect width="32" height="31" fill="url(#pattern0)" fill-opacity="0.5" />
                <defs>
                    <pattern id="pattern0" patternContentUnits="objectBoundingBox" width="1" height="1">
                        <use xlink:href="#image0_114_1364" transform="matrix(0.0078125 0 0 0.00806452 0 -0.016129)" />
                    </pattern>
                    <image id="image0_114_1364" width="128" height="128"
                        xlink:href="data:image/png;base64,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" />
                </defs>
            </svg>
        </span>
        <div class="flex flex-col">
            <span class="text-[#898989] text-sm font-normal leading-normal">RUTA UNO | <span
                    class="text-[#202020] font-bold">CALI - MEDELLIN</span></span>
            <small class="text-[0.625rem] text-[#898989] font-normal leading-normal">5:00PM - Jul 8,
                2023</small>
        </div>
    </div>
</div>
