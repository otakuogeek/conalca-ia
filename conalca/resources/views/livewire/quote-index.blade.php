<div class="w-fit max-h-fit">
    <style>
        .button_voice:hover {
            opacity: 1 !important;
        }

        .close2 {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 20px;
            cursor: pointer;
        }
    </style>
    <section
        class="flex flex-col pt-8 pl-2 md:pl-7 pr-2 md:pr-8 pb-[1.38rem] items-start">


        <div class="">
            <!-- Botón de creación de cotización -->
            <button wire:click="openModalCreate(2)"
                class="text-white w-[30%] md:w-[15rem] h-11 rounded-lg bg-[#FF7C32] flex items-center text-center text-white text-sm font-medium gap-2 justify-center mx-4">
                <span class="text-sm sm:text-base">+</span>
                <span class="text-sm  leading-[1rem]">Crear cotización</span>
            </button>

        </div>

        @if ($showModal)
            <div class="relative z-20" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                    <div
                        class="flex min-h-full w-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                        <div
                            class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 ">
                            <div class="grid grid-cols-1">
                                <div
                                    class="w-full h-full rounded-[1.57rem] bg-white flex flex-col overflow-y-scroll scrollbar-default">
                                    <form method="dialog" class="self-end mt-3 mr-3">
                                        <button wire:click="closeModal(1)">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                    d="M13.4142 12L17.7072 7.70701C18.0982 7.31601 18.0982 6.68401 17.7072 6.29301C17.3162 5.90201 16.6842 5.90201 16.2933 6.29301L12.0002 10.586L7.70725 6.29301C7.31625 5.90201 6.68425 5.90201 6.29325 6.29301C5.90225 6.68401 5.90225 7.31601 6.29325 7.70701L10.5862 12L6.29325 16.293C5.90225 16.684 5.90225 17.316 6.29325 17.707C6.48825 17.902 6.74425 18 7.00025 18C7.25625 18 7.51225 17.902 7.70725 17.707L12.0002 13.414L16.2933 17.707C16.4882 17.902 16.7443 18 17.0002 18C17.2562 18 17.5122 17.902 17.7072 17.707C18.0982 17.316 18.0982 16.684 17.7072 16.293L13.4142 12Z"
                                                    fill="#18203A" />
                                                <mask id="mask0_135_2594" style="mask-type:luminance"
                                                    maskUnits="userSpaceOnUse" x="6" y="5" width="13"
                                                    height="13">
                                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                                        d="M13.4142 12L17.7072 7.70701C18.0982 7.31601 18.0982 6.68401 17.7072 6.29301C17.3162 5.90201 16.6842 5.90201 16.2933 6.29301L12.0002 10.586L7.70725 6.29301C7.31625 5.90201 6.68425 5.90201 6.29325 6.29301C5.90225 6.68401 5.90225 7.31601 6.29325 7.70701L10.5862 12L6.29325 16.293C5.90225 16.684 5.90225 17.316 6.29325 17.707C6.48825 17.902 6.74425 18 7.00025 18C7.25625 18 7.51225 17.902 7.70725 17.707L12.0002 13.414L16.2933 17.707C16.4882 17.902 16.7443 18 17.0002 18C17.2562 18 17.5122 17.902 17.7072 17.707C18.0982 17.316 18.0982 16.684 17.7072 16.293L13.4142 12Z"
                                                        fill="white" />
                                                </mask>
                                                <g mask="url(#mask0_135_2594)">
                                                </g>
                                            </svg>
                                        </button>
                                    </form>
                                    <div
                                        class="overflow-x-hidden scrollbar-default w-full md:pl-4 h-[2.13rem] flex flex-row items-center">
                                        <span class="relative">
                                            <span
                                                class="absolute left-16 top-1 text-[#20202080] text-base font-medium leading-normal">Primer
                                                contacto</span>
                                            <svg width="241" height="34" viewBox="0 0 241 34" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M2.58072 8.70132C-0.80328 5.62794 1.37095 0 5.9423 0H221.258C222.426 0 223.558 0.40951 224.457 1.15737L238.493 12.8432C240.858 14.8118 240.9 18.4286 238.583 20.4522L224.479 32.7664C223.568 33.5617 222.4 34 221.191 34H5.49873C0.985344 34 -1.2179 28.4925 2.05019 25.3796L7.37997 20.3029C9.48261 18.3001 9.44263 14.9334 7.29302 12.9811L2.58072 8.70132Z"
                                                    fill="#04D000" />
                                            </svg>
                                        </span>
                                        <span class="relative">
                                            <span
                                                class="absolute left-[6.06rem] top-1 text-[#20202080] text-base font-medium leading-normal">Reunión</span>
                                            <svg width="241" height="34" viewBox="0 0 241 34" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M2.58072 8.70132C-0.80328 5.62794 1.37095 0 5.9423 0H221.258C222.426 0 223.558 0.40951 224.457 1.15737L238.493 12.8432C240.858 14.8118 240.9 18.4286 238.583 20.4522L224.479 32.7664C223.568 33.5617 222.4 34 221.191 34H5.49873C0.985344 34 -1.2179 28.4925 2.05019 25.3796L7.37997 20.3029C9.48261 18.3001 9.44263 14.9334 7.29302 12.9811L2.58072 8.70132Z"
                                                    fill="#04D000" />
                                            </svg>
                                        </span>
                                        <span class="relative">
                                            <span
                                                class="absolute left-[5.25rem] top-1 text-[#20202080] text-base font-medium leading-normal">Cotización</span>
                                            <svg width="241" height="34" viewBox="0 0 241 34" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M2.58072 8.70132C-0.80328 5.62794 1.37095 0 5.9423 0H221.258C222.426 0 223.558 0.40951 224.457 1.15737L238.493 12.8432C240.858 14.8118 240.9 18.4286 238.583 20.4522L224.479 32.7664C223.568 33.5617 222.4 34 221.191 34H5.49873C0.985344 34 -1.2179 28.4925 2.05019 25.3796L7.37997 20.3029C9.48261 18.3001 9.44263 14.9334 7.29302 12.9811L2.58072 8.70132Z"
                                                    fill="#04D000" />
                                            </svg>
                                        </span>
                                        <span class="relative">
                                            <span
                                                class="absolute left-[6.69rem] top-1 text-[#20202080] text-base font-medium leading-normal">Orden</span>
                                            <svg width="241" height="34" viewBox="0 0 241 34" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M2.58072 8.70132C-0.803281 5.62794 1.37096 0 5.9423 0H221.258C222.426 0 223.558 0.40951 224.457 1.15737L238.493 12.8432C240.858 14.8118 240.9 18.4286 238.583 20.4522L224.479 32.7664C223.568 33.5617 222.4 34 221.191 34H5.49873C0.985345 34 -1.2179 28.4925 2.05019 25.3796L7.37997 20.3029C9.48261 18.3001 9.44263 14.9334 7.29302 12.9811L2.58072 8.70132Z"
                                                    fill="#D9D9D9" />
                                            </svg>
                                        </span>
                                        <span class="relative">
                                            <span
                                                class="absolute left-[5.63rem] top-1 text-[#20202080] text-base font-medium leading-normal">Prefactura</span>
                                            <svg width="241" height="34" viewBox="0 0 241 34" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M2.58072 8.70132C-0.803281 5.62794 1.37096 0 5.9423 0H221.258C222.426 0 223.558 0.40951 224.457 1.15737L238.493 12.8432C240.858 14.8118 240.9 18.4286 238.583 20.4522L224.479 32.7664C223.568 33.5617 222.4 34 221.191 34H5.49873C0.985345 34 -1.2179 28.4925 2.05019 25.3796L7.37997 20.3029C9.48261 18.3001 9.44263 14.9334 7.29302 12.9811L2.58072 8.70132Z"
                                                    fill="#D9D9D9" />
                                            </svg>
                                        </span>
                                    </div>
                                    <div
                                        class="flex flex-col md:flex-row items-start justify-center gap-[1.32rem] md:pl-[1.88rem] mt-4 md:mt-[3.13rem]">
                                        {{-- col-1 --}}
                                        <div class="flex flex-col items-start justify-center gap-[1.31rem]">
                                            {{-- showQuoteModal --}}

                                            {{-- row 1 --}}
                                            <div
                                                class="flex flex-row flex-wrap bg-white shadow-md w-full  md:h-[10.88rem] rounded-[0.82rem] pl-[1.81rem]
                                            pt-[1.25rem] pr-[1.12rem] items-start justify-between">
                                                <div class="flex flex-col justify-start">
                                                    <div
                                                        class="flex flex-row items-center text-[#898989] text-[0.625rem] font-normal leading-normal gap-[14%]">
                                                        <span>N° Cotización</span>
                                                        <span>Nombre de la empresa</span>
                                                    </div>
                                                    <h4
                                                        class="text-[#898989] text-[1.1rem] font-normal leading-normal uppercase">
                                                        IDC001 | <span
                                                            class="text-[#202020]
                                                font-bold uppercase">
                                                            A&A CONSULTORIA E INGENIERIA S A S</span>
                                                    </h4>
                                                    <button
                                                        class="bg-[#cacaca] w-[5.88rem] h-[1.07rem] rounded text-center text-white text-[0.57rem] font-medium
                                            leading-normal mt-2">Alerta</button>
                                                    <span
                                                        class="mt-3 w-full md:w-[15.63rem] text-[#898989] text-[0.625rem] font-normal leading-[0.875rem]">
                                                        {{ $selected_data_cotizacion->silogtran_status }}
                                                    </span>
                                                </div>
                                                {{-- <div class="flex flex-col items-end md:justify-between h-full">
                                                <span
                                                    class="flex flex-col items-center justify-center text-[#898989] text-[0.625rem] font-normal leading-[1.03125rem]">

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
                                            </div> --}}
                                            </div>
                                            {{-- row 2 --}}
                                            <div
                                                class="w-full min-h-[16rem] rounded-[0.82rem] bg-white shadow-md py-8 px-8 flex flex-col">
                                                <span class="w-full text-[0.625rem] mb-2 text-gray-600">Datos empresa</span>
                                                <h4 class="text-[#202020] text-[1.44rem] font-bold uppercase mb-6">A&A
                                                    CONSULTORIA E INGENIERIA S A S</h4>
                                                <div
                                                    class="flex flex-col md:flex-row justify-center md:justify-between gap-6">
                                                    <div class="flex flex-col items-start justify-start space-y-4">
                                                        <div class="w-full">
                                                            <label for="nit" class="block text-[0.625rem] text-gray-600 mb-1">
                                                                Nit</label>
                                                            <input type="text" id="nit" placeholder="000000000-0"
                                                                name="nit"
                                                                class="w-full text-[0.94rem] px-3 py-2 border border-gray-200 rounded-md" />
                                                        </div>
                                                        <div class="w-full">
                                                            <label for="sector" class="block text-[0.625rem] text-gray-600 mb-1">
                                                                Sector económico</label>
                                                            <input type="text" id="sector" placeholder="Agroindustria"
                                                                name="sector"
                                                                class="w-full text-[0.94rem] px-3 py-2 border border-gray-200 rounded-md" />
                                                        </div>
                                                        <div class="w-full">
                                                            <label for="address" class="block text-[0.625rem] text-gray-600 mb-1">
                                                                Dirección</label>
                                                            <input type="text" id="address" placeholder="Cra 9 # 0 -0"
                                                                name="address"
                                                                class="w-full text-[0.94rem] px-3 py-2 border border-gray-200 rounded-md" />
                                                        </div>
                                                        <div class="w-full">
                                                            <label for="email" class="block text-[0.625rem] text-gray-600 mb-1">
                                                                Correo</label>
                                                            <input type="email" id="email" placeholder="correo@correo.com"
                                                                name="email"
                                                                class="w-full text-[0.94rem] px-3 py-2 border border-gray-200 rounded-md" />
                                                        </div>
                                                    </div>
                                                    <div class="flex flex-col items-start justify-start space-y-4">
                                                        <div class="w-full">
                                                            <label for="employee_type" class="block text-[0.625rem] text-gray-600 mb-1">
                                                                Cargo</label>
                                                            <input type="text" id="employee_type" placeholder="Gerente"
                                                                name="employee_type"
                                                                class="w-full text-[0.94rem] px-3 py-2 border border-gray-200 rounded-md" />
                                                        </div>
                                                        <div class="w-full">
                                                            <label for="contact_name" class="block text-[0.625rem] text-gray-600 mb-1">
                                                                Nombre persona contacto</label>
                                                            <input type="text" id="contact_name" placeholder="Nombre persona contacto"
                                                                name="contact_name"
                                                                class="w-full text-[0.94rem] px-3 py-2 border border-gray-200 rounded-md" />
                                                        </div>
                                                        <div class="w-full">
                                                            <label for="phone" class="block text-[0.625rem] text-gray-600 mb-1">
                                                                Teléfono</label>
                                                            <input type="tel" id="phone" placeholder="Número de teléfono"
                                                                name="phone"
                                                                class="w-full text-[0.94rem] px-3 py-2 border border-gray-200 rounded-md" />
                                                        </div>
                                                        <div class="w-full">
                                                            <label for="city" class="block text-[0.625rem] text-gray-600 mb-1">
                                                                Ciudad</label>
                                                            <input type="text" id="city" placeholder="Bogota" name="city"
                                                                class="w-full text-[0.94rem] px-3 py-2 border border-gray-200 rounded-md" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- row 3 --}}
                                            <div
                                                class="w-full min-h-[16rem] rounded-[0.82rem] bg-white shadow-md py-8 px-8 flex flex-col mb-6">
                                                <h4
                                                    class="text-[#202020] text-[1.44rem] font-bold uppercase leading-normal mb-8">
                                                    Rutas creadas</h4>
                                                <div
                                                    class="flex flex-row items-center justify-start w-full rounded-[0.82rem] bg-[#F9F0DF] p-8 gap-8 min-h-[6rem]">
                                                    {{-- ToDo: change icon --}}
                                                    <div class="flex-shrink-0 self-center">
                                                        <svg width="40" height="39" viewBox="0 0 32 31"
                                                            fill="none" xmlns="http://www.w3.org/2000/svg"
                                                            xmlns:xlink="http://www.w3.org/1999/xlink">
                                                            <rect width="32" height="31" fill="url(#pattern0)"
                                                                fill-opacity="0.6" />
                                                            <defs>
                                                                <pattern id="pattern0"
                                                                    patternContentUnits="objectBoundingBox"
                                                                    width="1" height="1">
                                                                    <use xlink:href="#image0_114_1364"
                                                                        transform="matrix(0.0078125 0 0 0.00806452 0 -0.016129)" />
                                                                </pattern>
                                                                <image id="image0_114_1364" width="128"
                                                                    height="128"
                                                                    xlink:href="data:image/png;base64,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" />
                                                            </defs>
                                                        </svg>
                                                    </div>
                                                    <div class="flex flex-col flex-1 min-w-0 justify-center space-y-3">
                                                        <div>
                                                            <span
                                                                class="text-[#898989] text-base font-normal leading-relaxed">RUTA
                                                                UNO | <span
                                                                    class="text-[#202020] font-bold text-lg">{{ $selected_data_cotizacion->ciudad_origen }}
                                                                    -
                                                                    {{ $selected_data_cotizacion->ciudad_destino }}</span></span>
                                                        </div>
                                                        <div>
                                                            <small
                                                                class="text-[0.75rem] text-[#898989] font-normal leading-relaxed bg-white px-3 py-1 rounded-full">{{ $selected_data_cotizacion->created_at }},
                                                                2023</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        {{-- end col-1 --}}
                                        {{-- col-2 --}}
                                        <div class="flex flex-col items-start justify-center gap-6">
                                            {{-- shoQuoteModal --}}

                                            {{-- row 1 --}}
                                            <div
                                                class="w-full min-h-[16rem] bg-white rounded-[0.82rem] shadow-md py-8 px-8 flex flex-col items-start justify-start">
                                                <h4
                                                    class="text-[#202020] text-[1.44rem] font-bold uppercase leading-normal mb-6">
                                                    Carga</h4>
                                                <div class="space-y-3 w-full">
                                                    <div class="flex items-center">
                                                        <span class="text-[0.625rem] text-gray-600 font-medium w-24">Mercancía:</span>
                                                        <span class="text-[#898989] text-[0.625rem] font-normal">{{ $selected_data_cotizacion->tipo_mercancia }}</span>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <span class="text-[0.625rem] text-gray-600 font-medium w-24">Producto:</span>
                                                        <span class="text-[#898989] text-[0.625rem] font-normal">{{ $selected_data_cotizacion->tipo_producto }}</span>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <span class="text-[0.625rem] text-gray-600 font-medium w-24">Seguro:</span>
                                                        <span class="text-[#898989] text-[0.625rem] font-normal">Valor seguro</span>
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- end row 1 --}}
                                            {{-- row 2 --}}
                                            <div
                                                class="w-full  min-h-[8.125rem] rounded-[0.82rem] bg-white shadow-md pt-[0.81rem] pr-6 pb-4 pl-[1.81rem] flex flex-col
                                            md:flex-row items-start justify-center md:justify-between">
                                                <div class="flex flex-col">
                                                    <h4
                                                        class="text-[#202020] text-[1.44rem] font-bold uppercase leading-normal mb-2">
                                                        Conductor</h4>
                                                    <label for="driver_contact_person"
                                                        class="text-[0.625rem] text-[#89898992] leading-[1.03125rem] font-normal">
                                                        Nombre persona contacto</label>
                                                    <input type="text" id="driver_contact_person" placeholder="Nombre persona contacto"
                                                        name="contact_person" value=""
                                                        class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989] font-normal leading-[1.03125rem]" />
                                                    <label for="driver_phone"
                                                        class="text-[0.625rem] text-[#89898992] leading-[1.03125rem] font-normal">
                                                        Teléfono</label>
                                                    <input type="text" id="driver_phone" placeholder="Número de teléfono"
                                                        name="phone"
                                                        class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989] font-normal leading-[1.03125rem]" />
                                                </div>
                                                <div class="flex flex-col">
                                                    <button
                                                        class="flex flex-row items-center justify-center text-white bg-[#FF7C32] w-[5.38rem] h-6 text-xs font-medium leading-normal
                                                gap-[0.37rem] self-end mb-[0.63rem] rounded-[0.38rem]">
                                                        <span>
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="12"
                                                                height="12" viewBox="0 0 12 12" fill="none">
                                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                                    d="M3.31 5.395C4.03 6.81 5.19 7.965 6.605 8.69L7.705 7.59C7.84 7.455 8.04 7.41 8.215 7.47C8.775 7.655 9.38 7.755 10 7.755C10.275 7.755 10.5 7.98 10.5 8.255V10C10.5 10.275 10.275 10.5 10 10.5C5.305 10.5 1.5 6.695 1.5 2C1.5 1.725 1.725 1.5 2 1.5H3.75C4.025 1.5 4.25 1.725 4.25 2C4.25 2.625 4.35 3.225 4.535 3.785C4.59 3.96 4.55 4.155 4.41 4.295L3.31 5.395Z"
                                                                    fill="white" />
                                                            </svg>
                                                        </span>
                                                        Lamar
                                                    </button>
                                                    <label for="driver_vehicle"
                                                        class="text-[0.625rem] text-[#89898992] leading-[1.03125rem] font-normal self-start">
                                                        Teléfono</label>
                                                    <input type="text" id="driver_vehicle" placeholder="Placa ZTC33A" name="vehicle"
                                                        class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989] font-normal leading-[1.03125rem] self-start" />
                                                </div>
                                            </div>
                                            {{-- end row 2 --}}
                                            {{-- row 3 --}}
                                            <div
                                                class="w-full min-h-[23.19rem] rounded-[0.82rem] bg-white pt-[1.69rem] flex flex-col mb-4 md:mb-0">
                                                <div
                                                    class="flex flex-row flex-wrap w-full items-center justify-between pl-[1.81rem] pr-7 mb-[0.94rem]">
                                                    <h4
                                                        class="text-[#202020] text-[1.44rem] font-bold uppercase leading-normal mb-2">
                                                        En transito</h4>
                                                    <div class="flex gap-4 items-center justify-center">
                                                        <span>
                                                            <svg width="24" height="24" viewBox="0 0 24 24"
                                                                fill="none" xmlns="http://www.w3.org/2000/svg"
                                                                xmlns:xlink="http://www.w3.org/1999/xlink">
                                                                <rect width="24" height="24"
                                                                    fill="url(#pattern0)" />
                                                                <defs>
                                                                    <pattern id="pattern0"
                                                                        patternContentUnits="objectBoundingBox"
                                                                        width="1" height="1">
                                                                        <use xlink:href="#image0_109_1280"
                                                                            transform="scale(0.0078125)" />
                                                                    </pattern>
                                                                    <image id="image0_109_1280" width="128"
                                                                        height="128"
                                                                        xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAACACAYAAADDPmHLAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAADsQAAA7EB9YPtSQAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAA3QSURBVHic7Z1psB1FFYC/97InkA1iCBBlCSFljGEREAlYJgEiGgGVFCiLgEC5sAgqi1KAiqQoUAFlkSVCSbGIFAiKIKCJEQswLEkAhYQ1CVsSQlaS8N71x7lXXq7v9unT0zNzl/mquiqVue+c0z3TM92nT59uo7kZCIwFPg6MA3YCtgAGlcvA8u9WAu+WyzLgBWAuMA+YD6zK1OqCYHoA+wLTkZvXCZQSls6yrOnAhLKOgjpjH+AmpPcmveFaWQrcCOydSc0KatIHOAx4hPRveq3yBHAi0D/luhZ0oRdwCvA2+d346vIWcHLZtoIUmYwMyvK+4bXKf5C3UkFktgf+Sv432Lc8CHwklZaITFveBnhwBHAVMm1LQgewEHieD6Z9K8vXKlPCQchUcUeSj/ZXACcBtyeU07JsBswgvBeuBO4Avg7sDvQz6O5X/psTgN+XZYXacX25LgUGtkHm3tbGXgfcAEwCeke0pzcy/phR1mG1ay6wdUR7mpoxwCvYGvhV4CxgywzsGwacXdZpsfFlYOcM7Gto9kCmVL6Nugo4H+ibg629gVORb72vvcuAT+Vga0OwB7Aav4bsBK4kmx6vMQy4Gn+38ypkfFHQhe2A1/FrwDeBg3Kx0s1kYDF+dXgLmWkUAEOAZ/FruD8iPa5eGQ7ch19dnkHq3tL0wt/BcznQno+ZJtqBX+JXp4eBnvmYWR9chF9DTc/LwASciV/dLszLwLyZhHjntAb6dl4GRuAU9Pp1AJ/Jy8C86I+4ZbXGuSgvAyNyMXo9XwIG5GFcXmsBlwBnKL+5DVkHKEXUOwYJDxuNDNgqjb4GmV08DzyNrOrFoh24BZim/O5i5LPR9IwGNuDuEU8Tx7nTBuwHXAcsUXR2LYuBa5HwshidpC9SJ5fO9cCoCLrqnjvRG2J8Qh09gCOJEzswD/gKyVcHd0Hq5tLV9CuH49E9Zmcn1LEXMEfREVIeR7yVSfihoqMT2DWhjrrmFvRGDu1pbYhvXvu8JCnvI2sPof6InugP582BsuuekUgDuiq/f6Ds/sA9iuyY5S5s8QVdmaLI3ogshzcd5+Ou+OxAuZsBf1dkp1FmET51m6XI/kGg3LqlDVkPd1U6xBnSG7hfket6nS8pF+3NVKvcR1gk8GRF7sIAmXXNHrgrPCdQ7hWK3K5lDRIidhSwLZuONXqU/+9oJARsrUHuLwJtf0qRu1ug3Lrkp7gre3qAzEMVmZWyAYkd2MogewSyxr/RQ34n8IUA+7+vyP1xgMy65XFqV7QD+6BnIH5r7y8inr9QxiNuWk3Pa9gDP0fiXgt5JIHddcUg3N/YBwNk+qwiziJOxNAw/AaZIT12pkPeBpokmnh/3A13jlHeUPQw7edIvo+gK5ujRym/iz3A4zxF5qcj2O4kiwAL7RX8mFHe8cgNqcVy4HPIDYnFKmTM8Y7jNwOBY41yH1Wu72KUV5dcj3sANdgob65DXglZg0+L7yi6nzTKG4rbNX5tFKtz5iFqV/B5o6ydHbJKyPw55oaQavqgDwqtwZ4LHLLuj2K1gyw+AR92XFtslKU5i36FDJ7SYn1Zh4tJRpmuNnC1XRSyeABcI/E3jbL2Uq7fZZQXgqZDs7GapY5rQ42yzGTxALimMm8bZbm2Vj2LzPvTZgHwb8d16/YvVxsMdFyLQtoPQC/cYc8rjPJcr8Qs/ecLHNesr23XzKIvKYftpf0AlJTr1gGba/pnHU8kYYnjmrXXutqgMhhMjbQfgIqrsxbW5dQ+jmurjbKSsNJxzWVjd7jaoMMoy0wWbwBXY1kfgDWOax8yykqCa2HJ+iC62iCmM6tbshgELndcs7prXd/LEUZZSXDpctnYHa5PhlWWmSwegEWOa6ONslyDr13IJotnD9xRyy4bu2Mnx7U3jLLMZPEAPOe4thO2zZEuWcPIJvnCBNy+DZeN1fTCvRcg9ZlN3g9Ab2ybIWYp17PI0afpmGmQNRp3SFkWfo3UOQC379xy04biDvteiwRapMWHcSeJWo9tXHO4Q1YJOCSW4XkyEnclrzbK+4Mib0YUq7vnJkW31RV9rSIvzYc5M9pwh2+9YpSnxQJ2IptKY/NVRW8JONgo05Vp7LUoVtcJN+BuuLEGWe3oKWXWAntGsp2yLC0/4HxsY6pxirymiAWocBjuyn7PKG+aIq+EOFE+H8H2qfhlCv2SUa4WFWyVV9cMwj14mxcg8y8OeZXSAVxAWD7//kigp08Wkz8HyHftC9iIPVKq7tEia607b7dDvIzazSkhzqgT8Iuy3Qw5BMI35dsy7JnBP6nItEwlG4bTcFf6mgCZB+PXQytlHXAvshFlGuLUmVD+9+lIGjpLLuAO5BNh5TpF7mkBMuueLYD3cN8cy+6dCt9wyEy7nBpg73DcW8/WU9+5EBNxB+4GDd0O9V3inBDmWzrRcxzVQtvUcmug3IbgQNyVf4fwMKijsG3qDC1rkJQxIQxCTy49OVB2w/Ak6bwFQObWaZ4pNA/4WAL7tN7/FI1xiksijsTdCGuRrdqh9ELSrb2r6LGUFYivIsmpYFsjbw+XnsMTyG8YeqEni/h1BD1DgHMRl2rojX8VSewUY07u2iFVQlb+WiZv8HG4G6MDe3x9LdqRzRqXIp8fl0NqPXIY5CXAROItl38Cfbp6TCRdJvL63vRAEie61gDmIA9B7MDInsgxdMP5wDG0Ctmk8jKylT0m7cA/ca9NzEcimlIPAq0nDkF/BX8zN+vi8S30eoY4kxqeNvSzAlYhvbVR2QGpg6uOIQkymoaPoid2nE1jHBRRTTvwN9x124hMXVuaS9FfkWnu+U8LLZdACckQ3vJsjj4tXIM9hDxPdkb3Sr5Ek+QAisFEdF/+I2QT95+UdvSl707C0+I2LVeivzIbIYWqlhG8hBwqVVDFAOAF9EHTPnkZ6MGe6IPaFyle/TXZB91j9ioZZM4IYDB6/qAOMkj91uhchv4KvSM362qjnYVQAn6Wm3UNRH/8ThI9MS8Du+EkdHufIfyMgZZjPHps3hps+wnSYiz6Mu86CoePmZPRe9V88u1VfdHTvpdojjWNzGlD9tppjXtFXgYiOQM1++6lBaJ80mIIsndQa2TrnrwYHITuvFqEREMXJGBf9CNdlpNBVs0ubIPk99OmfBMztKmp+RH6W2Am2biK23HnP66UCzKwpWXoCfwDvdGzcBWf62HHoyQLIC3ohu3RY+o3EndbeDV7oZ8jtALZt1iQAto28xKSpSsNX/sA5FRxTX/oppECT25EvwlXpaD3Gg+9M1LQW1CFb0+MkRyiwhT0Kd8C3LmMCyKyO/pR7G8hod9J2RJ4XdG1gXj7GAo8OQv9LRDjAInfeeixprgpiEA7elh5CdmFFMrxHvIfojGjlpuCbdHTxKzGnY+3FtujJ4daQbYeyIJu0HYblxAnksVL2I77VM9iyldn+ETjWE4o9QnsrMeopJZlMPqqoa+XcDf0GcYi6jMusaWZjD5Xfw53AElf9DOCO4HPplKDgv9jJPA1ZCTvkzXEJ6D0csffX+Hx9z/3tPu4su1Jsp20LP2AC9n0VbwBWRZ20Qf9bOFOJJijmv3R3yDPooeg/YRNF4zWl+tSBIR6MhH3BpEvKn+/K/o3fDGbRuoMwZ25u/IAatlMv+z4+xcoAkScbAH8Br0X3u0h6xxFRgm4s8vvb/P4/Vkeeu9VZHSW61iEiVVxGOK7125CCUkSreGzN7+E5BY82uN3s/HzIzzsWYflyL6Glg8W3RF4AL9Gq5RLPGXvgJ8nTwsyWVmW5YNProOu5QHsx803BZVcftbMnsuwHaOiZSPzKcca9I3EP4N5pawtt0XLhJDtiWQIs96IJ5CUMlZuD9BVKSGriWPLtlp1PU264Wx1wTT07dPVZTWSmDk0iaLPen535U3C4wl6lm1ebdS5HplJNCWbY0/f+ifiBFgeiC2beCdxIoq2Q+pgqfMKmjR/wBT8G+EN4p/8dZVB/5WRdR+B1MlX/5TI+muSZSCDZTn2PdynjodwBhJLqLEQOdApJiuROvnSlNPDQdi/ibcSdopILXbHPQbZSNzYvq2QOljqvAr7qeoNwzHo+/yqy3LkwKdYveJ8h67zIuloQ2y2TgnfRxxTTY3m669VZgFjIujvibiTq+XfTZx07WMQW631a6m1gr5IT9QWbarLBmA6suqXhHbEDXxzuRxJ8vFQxbnlOhir1mfnMmS/Q8sxDkkAGdJbJuVgby0mIDmArPWYg4xLWpo2ZGHE6iPoRE7z3jJ7k//HYKT3Ws4uLCGD4TNpjMynmTEC/Xj27sob5DNwmorEBlrtvYcilNzJVPzSwlSXWF5DjW2QWAKrfUuQZe8CDwYggz3rlHENMrhMY2WtJ3JSqM9J4tWfqmsIPw+xpdkN+Bf23vYUcVfWxgOPBdgxF9g7oh0tSaXnWb2IHUjPS7Jluz9hb6K1yJuodwLdBVXsANyPvRcuAg4N0HcQevLn7spM4jisCmpgiR/sWu7BL0Z/OGGzkSLOL0OGIK9364nh7yCfk+68fm3IdHKpUWYJiTpq2iPf65n9kO1e1hs2m03Dy0Yhx7dZ5SwEDkixfgUe9ENO5baGmr2HDNTOx+6/31DWWez2qSPGIUe0WnuxtRT++zomdF3BpxT++wZiBMnCwatL4b9vUKaib/p0lSW0QIROsxOyrlD475sQ33WFwn/fxLjWFdZR+O9bhlHAbxGv39Lyv0flalFO/Bcw8XMGcK8zoQAAAABJRU5ErkJggg==" />
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

                                        </div>
                                        {{-- end col-2 --}}
                                        {{-- col-3 --}}
                                        <div class="flex flex-col items-start justify-center gap-[1.06rem]">
                                            {{-- showQuoteModal --}}

                                            {{-- row 1 --}}
                                            <div
                                                class="w-full min-h-[8.5rem] rounded-[0.82rem] bg-white pt-[1.06rem] pl-4 pr-[1.13rem] pb-[1.19rem] flex flex-col">
                                                <div
                                                    class="flex flex-row flex-wrap items-center justify-between w-full mb-[0.81rem]">
                                                    <h4
                                                        class="text-[#202020] text-[1.44rem] font-bold uppercase leading-normal">
                                                        PENDIENTES</h4>
                                                    <span
                                                        class="text-[#FF7C32] text-3xl font-bold leading-normal -mt-1">+</span>
                                                </div>
                                                {{-- <div class="w-full mr-[0.63rem] min-h-[3.69rem] bg-[#FFEDED] pt-[0.56rem] pb-2 pr-3 pl-[0.94rem] rounded-[0.82rem]">
                                                <small class="text-[0.625rem] text-[#898989] font-normal leading-[0.875rem]">
                                                    Lorem ipsum es el texto que se usa habitualmente en diseño gráfico en demostraciones de tipografías o de
                                                    borradores de diseño para probar el diseño visual antes de insertar el texto final.
                                                </small>
                                            </div> --}}
                                            </div>
                                            {{-- end row 1 --}}
                                            {{-- row 2 --}}
                                            <div
                                                class="w-full  min-h-[29.75rem] rounded-[0.82rem] bg-white pt-[1.88rem] pl-[0.87rem] flex flex-col items-start justify-start
                                            shadow-md">
                                                <h4
                                                    class="text-[#202020] text-[1.44rem] font-bold uppercase leading-normal ml-[1.1rem] mb-4">
                                                    ORDEN <span class="font-normal">|</span>
                                                    <span class="text-[#898989] font-normal">IDO001</span>
                                                </h4>
                                                <div
                                                    class="flex flex-col md:flex-row justify-center md:justify-between w-full md:w-[23.20rem] ml-[1.1rem] font-normal leading-[1.03125rem]">
                                                    <div class="flex flex-col items-start justify-start">
                                                        <label for="origin"
                                                            class="w-full text-[0.625rem] text-[#89898983]">
                                                            Origen</label>
                                                        <input type="text" id="origin" placeholder="Bogota D.C."
                                                            name="origin"
                                                            value="{{ $selected_data_cotizacion->ciudad_origen }}"
                                                            class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989]" />
                                                        <label for="charge_type"
                                                            class="w-full text-[0.625rem] text-[#89898983]">
                                                            Tipo de carga</label>
                                                        <input type="text" id="charge_type" placeholder="Maquinaría"
                                                            name="charge_type"
                                                            class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989]" />
                                                        <label for="product"
                                                            class="w-full text-[0.625rem] text-[#89898983]">
                                                            Producto empaque</label>
                                                        <input type="text" id="product" placeholder="N/A" name="product"
                                                            value="{{ $selected_data_cotizacion->tipo_embajale }}"
                                                            class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989]" />
                                                    </div>
                                                    <div class="flex flex-col items-start justify-start">
                                                        <label for="destinatation"
                                                            class="w-full text-[0.625rem] text-[#89898983]">
                                                            Destino</label>
                                                        <input type="text" id="destinatation" placeholder="Barranquilla"
                                                            name="destinatation"
                                                            value="{{ $selected_data_cotizacion->ciudad_destino }}"
                                                            class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989]" />
                                                        <label for="weight"
                                                            class="w-full text-[0.625rem] text-[#89898983]">
                                                            Peso</label>
                                                        <input type="text" id="weight" placeholder="12 Toneladas"
                                                            name="weight"
                                                            value="{{ $selected_data_cotizacion->peso_mercancia }}"
                                                            class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989]" />
                                                        <label for="quantity"
                                                            class="w-full text-[0.625rem] text-[#89898983]">
                                                            Cantidad de vehículos</label>
                                                        <input type="number" id="quantity" placeholder="1" name="quantity"
                                                            value="{{ $selected_data_cotizacion->cantidad_vh }}"
                                                            class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989]" />
                                                    </div>
                                                </div>
                                                <div
                                                    class="w-full min-h-[6.19rem] rounded-xl pl-4 border border-solid border-[#dcdcdc]">
                                                    <div
                                                        class="flex flex-col md:flex-row justify-center md:justify-between w-full font-normal leading-[1.03125rem]">
                                                        <div class="flex flex-col items-start justify-start">
                                                            <label for="vehicle_type"
                                                                class="w-full text-[0.625rem] text-[#89898983]">
                                                                Tipo de vehículo</label>
                                                            <input type="text" id="vehicle_type" placeholder="Cama baja"
                                                                name="vehicle_type"
                                                                value="{{ $selected_data_cotizacion->clase_vehiculo }}"
                                                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.12rem] text-[#898989]" />
                                                            <label for="vehicle_type_2"
                                                                class="w-full text-[0.625rem] text-[#89898983]">
                                                                Tipo de vehículo</label>
                                                            <input type="text" id="vehicle_type_2" placeholder="Cama baja"
                                                                name="vehicle_type_2"
                                                                value="{{ $selected_data_cotizacion->clase_vehiculo }}"
                                                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.12rem] text-[#898989]" />
                                                            <label for="car_id"
                                                                class="w-full text-[0.625rem] text-[#89898983]">
                                                                Placas</label>
                                                            <input type="text" id="car_id" placeholder="AOS43R" name="car_id"
                                                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.12rem] text-[#898989]" />
                                                        </div>
                                                        <div class="flex flex-col items-start justify-start">
                                                            <label for="body_work"
                                                                class="w-full text-[0.625rem] text-[#89898983]">
                                                                Carrocería</label>
                                                            <input type="text" id="body_work" placeholder="N/A" name="body_work"
                                                                value="{{ $selected_data_cotizacion->tipo_carroceria }}"
                                                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.12rem] text-[#898989]" />
                                                            <label for="car_model"
                                                                class="w-full text-[0.625rem] text-[#89898983]">
                                                                Modelo</label>
                                                            <input type="text" id="car_model" placeholder="2005" name="car_model"
                                                                value="{{ $selected_data_cotizacion->tipo_carroceria }}"
                                                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.12rem] text-[#898989]" />
                                                            <label for="cargo_driver"
                                                                class="w-full text-[0.625rem] text-[#89898983]">
                                                                Conductor</label>
                                                            <input type="text" id="cargo_driver" name="driver" value=""
                                                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.12rem] md:mb-0 text-[#898989]" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div
                                                    class="flex flex-col md:flex-row justify-center md:justify-between w-full font-normal leading-[1.03125rem]
                                                    ml-[1.1rem] mt-[0.56rem]">
                                                    <div class="flex flex-col items-start justify-start">
                                                        <label for="rate"
                                                            class="w-full text-[0.625rem] text-[#89898983]">
                                                            Tipo de tarifa</label>
                                                        <input type="text" id="rate" placeholder="N/A" name="rate"
                                                            class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989]" />
                                                        <label for="merchandise_value"
                                                            class="w-full text-[0.625rem] text-[#89898983]">
                                                            Valor de la mercancía</label>
                                                        <input type="text" id="merchandise_value" placeholder="$300.000.000"
                                                            name="merchandise_value"
                                                            value="{{ $selected_data_cotizacion->valor_declarado }}"
                                                            class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989]" />
                                                    </div>
                                                    <div class="flex flex-col items-start justify-start">
                                                        <label for="client_rate"
                                                            class="w-full text-[0.625rem] text-[#89898983]">
                                                            Tarifa cliente</label>
                                                        <input type="text" id="client_rate" placeholder="N/A" name="client_rate"
                                                            value="{{ $selected_data_cotizacion->tipo_mercancia }}"
                                                            class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989]" />
                                                        <label for="insurance"
                                                            class="w-full text-[0.625rem] text-[#89898983]">
                                                            Seguro mercancía</label>
                                                        <input type="text" id="insurance" placeholder="$300.000.000"
                                                            name="insurance"
                                                            value="{{ $selected_data_cotizacion->seguro }}"
                                                            class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] text-[#898989]" />
                                                    </div>
                                                </div>
                                                <small
                                                    class="text-[0.625rem] text-[#89898984] font-normal leading-[1.03125rem] ml-[1.01rem]">Descripción
                                                    de
                                                    mercancia</small>
                                                <div
                                                    class="w-full  min-h-[4.44rem] rounded-xl border border-solid border-[#dcdcdc] pt-[0.38rem] pr-[0.87rem] pl-4 mb-4">
                                                    <small
                                                        class="text-[0.69rem] text-[#898989] font-normal leading-[1.03125rem] -mt-2">
                                                        {{ $selected_data_cotizacion->tipo_mercancia }}
                                                    </small>
                                                </div>
                                            </div>
                                            {{-- end row 2 --}}
                                            {{-- row 3 --}}
                                            <div
                                                class="flex flex-col w-full  min-h-[7.94rem] rounded-[0.82rem] bg-white shadow-md pt-3 pl-[2.37rem] pr-9 mb-5">
                                                <div
                                                    class="flex flex-row flex-wrap items-start justify-between w-full">
                                                    <h4
                                                        class="text-[#202020] text-[1.44rem] font-bold uppercase leading-normal">
                                                        FACTURA</h4>
                                                    <span class="mt-3">
                                                        <svg width="33" height="33" viewBox="0 0 33 33"
                                                            fill="none" xmlns="http://www.w3.org/2000/svg"
                                                            xmlns:xlink="http://www.w3.org/1999/xlink">
                                                            <rect width="33" height="33" fill="url(#pattern0)"
                                                                fill-opacity="0.5" />
                                                            <defs>
                                                                <pattern id="pattern0"
                                                                    patternContentUnits="objectBoundingBox"
                                                                    width="1" height="1">
                                                                    <use xlink:href="#image0_114_1369"
                                                                        transform="scale(0.0078125)" />
                                                                </pattern>
                                                                <image id="image0_114_1369" width="128"
                                                                    height="128"
                                                                    xlink:href="data:image/png;base64,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" />
                                                            </defs>
                                                        </svg>
                                                    </span>
                                                </div>
                                            </div>
                                            {{-- end row 3 --}}

                                        </div>
                                        {{-- end col-3 --}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($showModal2)
            @switch($step)
                @case(0)
                    <div class="relative z-20" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                            <div
                                class="flex min-h-full w-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                                <div
                                    class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-6xl">
                                    <div class="bg-white p-12 my-10">
                                        <div class="sm:flex sm:items-start">
                                            <form method="dialog">
                                                <button class="absolute top-[1.69rem] right-[2.13rem]"
                                                    wire:click="closeModal(2)">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                            d="M13.4142 12L17.7072 7.70701C18.0982 7.31601 18.0982 6.68401 17.7072 6.29301C17.3162 5.90201 16.6842 5.90201 16.2933 6.29301L12.0002 10.586L7.70725 6.29301C7.31625 5.90201 6.68425 5.90201 6.29325 6.29301C5.90225 6.68401 5.90225 7.31601 6.29325 7.70701L10.5862 12L6.29325 16.293C5.90225 16.684 5.90225 17.316 6.29325 17.707C6.48825 17.902 6.74425 18 7.00025 18C7.25625 18 7.51225 17.902 7.70725 17.707L12.0002 13.414L16.2933 17.707C16.4882 17.902 16.7443 18 17.0002 18C17.2562 18 17.5122 17.902 17.7072 17.707C18.0982 17.316 18.0982 16.684 17.7072 16.293L13.4142 12Z"
                                                            fill="#18203A" />
                                                        <mask id="mask0_135_2594" style="mask-type:luminance"
                                                            maskUnits="userSpaceOnUse" x="6" y="5" width="13"
                                                            height="13">
                                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                                d="M13.4142 12L17.7072 7.70701C18.0982 7.31601 18.0982 6.68401 17.7072 6.29301C17.3162 5.90201 16.6842 5.90201 16.2933 6.29301L12.0002 10.586L7.70725 6.29301C7.31625 5.90201 6.68425 5.90201 6.29325 6.29301C5.90225 6.68401 5.90225 7.31601 6.29325 7.70701L10.5862 12L6.29325 16.293C5.90225 16.684 5.90225 17.316 6.29325 17.707C6.48825 17.902 6.74425 18 7.00025 18C7.25625 18 7.51225 17.902 7.70725 17.707L12.0002 13.414L16.2933 17.707C16.4882 17.902 16.7443 18 17.0002 18C17.2562 18 17.5122 17.902 17.7072 17.707C18.0982 17.316 18.0982 16.684 17.7072 16.293L13.4142 12Z"
                                                                fill="white" />
                                                        </mask>
                                                        <g mask="url(#mask0_135_2594)">
                                                        </g>
                                                    </svg>
                                                </button>
                                            </form>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div class="flex items-center justify-center h-full">
                                                    <img class="w-auto md:w-full h-[23.9375rem] object-cover"
                                                        src="{{ asset('img/quotes_logo.gif') }}" alt="conalca-ai-logo">
                                                </div>
                                                <div class="flex items-center justify-center h-full">
                                                    <form wire:submit='submitClient'>
                                                        <div class="flex flex-col">
                                                            <h1
                                                                class="w-full md:w-[30.625rem] text-[#FF7C32] text-[2.225rem] font-bold leading-tight">
                                                                HOLA!, HOY TE AYUDARE A CREAR TU COTIZACIÓN
                                                            </h1>
                                                            <h6
                                                                class="text-[#898989] text-sm font-normal leading-normal mt-[2.44rem]">
                                                                Buscar la empresa en tus registros u omite esta opción.
                                                            </h6>
                                                            <form class="relative w-full md:w-[21.5rem] h-[3.625rem]">
                                                                @csrf
                                                                <input type="search" wire:model='search'
                                                                    placeholder="Buscar empresa" name="search_company"
                                                                    class="w-full md:w-[21.5rem] h-[3.625rem] rounded-lg bg-white border border-solid border-[#dcdcdc] mt-1 pl-3 pr-8
                                                                           text-[#898989] text-xl font-normal leading-normal" />
                                                                @error('search')
                                                                    <p class="text-red-500">{{ $message }}</p>
                                                                @enderror
                                                            </form>
                                                            <h6
                                                                class="text-[#898989] text-sm font-normal leading-normal mt-[1.06rem]">
                                                                Seleciona el tipo de cliente que va ser dirigida tu cotización
                                                            </h6>
                                                            <select name="client_type" id="client_type"
                                                                class="w-full md:w-[21.5rem] h-[3.625rem] rounded-lg bg-white border border-solid border-[#dcdcdc] mt-1
                                                                       text-[#898989] text-xl font-normal leading-normal px-3">
                                                                <option value="" disabled>Seleccione el tipo de cliente
                                                                </option>
                                                                <option value="credit">Crédito</option>
                                                                <option value="cash">Contado</option>
                                                            </select>
                                                            <h6
                                                                class="text-[#898989] text-sm font-normal leading-normal mt-[1.06rem]">
                                                                Tipo de operacion</h6>
                                                            <select wire:model="operation_type"
                                                                    class="w-full md:w-[21.5rem] h-[3.625rem] rounded-lg bg-white border border-[#dcdcdc] mt-1
                                                                        text-[#898989] text-xl leading-normal px-3">
                                                                <option value="" disabled>Selecciona el tipo de operación</option>
                                                                <option value="DISTRIBUCION">DISTRIBUCIÓN</option>
                                                                <option value="EXPORTACION">EXPORTACIÓN</option>
                                                                <option value="IMPORTACION">IMPORTACIÓN</option>
                                                            </select>
                                                            <h6
                                                                class="text-[#898989] text-sm font-normal leading-normal mt-[1.06rem]">
                                                                Selecciona el tipo de modalidad para la cotización
                                                            </h6>
                                                            <select wire:model="type_business" name="business_type"
                                                                id="business_type"
                                                                class="w-full md:w-[21.5rem] h-[3.625rem] rounded-lg bg-white border border-solid border-[#dcdcdc] mt-1
                                                                       text-[#898989] text-xl font-normal leading-normal px-3">
                                                                <option value="" disabled>Seleccione el tipo de negocio
                                                                </option>
                                                                <option value="dta">DTA</option>
                                                                <option value="otm">OTM</option>
                                                                <option value="refri">REFRI</option>
                                                            </select>
                                                            <h6
                                                                class="text-[#898989] text-sm font-normal leading-normal mt-[1.06rem]">
                                                                Selecciona el tipo de carga</h6>
                                                            <select
                                                                class="w-full md:w-[21.5rem] h-[3.625rem] rounded-lg bg-white border border-solid border-[#dcdcdc] mt-1
                                                                       text-[#898989] text-xl font-normal leading-normal px-3">
                                                                <option value="" disabled>Seleccione el tipo de negocio
                                                                </option>
                                                                <option value="dta">CARGA EXTRADIMENCIONADA</option>
                                                                <option value="otm">MERCANCIA PELIGROSA</option>
                                                                <option value="refri">CARGA GENERAL</option>
                                                            </select>
                                                            <button type="submit"
                                                                class="w-full md:w-[21.5rem] h-[3.625rem] rounded-lg bg-[#FF7C32] text-white text-xl font-medium leading-normal
                                                                       mt-[2.81rem] mb-4 md:mb-0">Crear
                                                                cotización
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @break

                @case(1)
                    <style>
                        @import url('https://fonts.googleapis.com/css2?family=Product+Sans:wght@300;400;500;600;700&display=swap');
                        .product-sans {
                            font-family: 'Product Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        }
                    </style>
                    
                    <div id="voiceElement" class="relative z-20 product-sans" aria-labelledby="modal-title" role="dialog"
                        aria-modal="true">
                        <div class="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity backdrop-blur-sm"></div>
                        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                            <div class="flex min-h-full w-full items-center justify-center p-6">
                                <div class="relative w-full max-w-[95vw] lg:max-w-7xl transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all">
                                    <!-- Header con botón de cerrar mejorado -->
                                    <div class="relative bg-white border-b border-gray-200 px-8 py-6">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3">
                                                <h2 class="text-xl font-600 text-gray-700 product-sans">Creación de Cotización</h2>
                                            </div>
                                            <button wire:click="closeModal(2)" 
                                                class="group flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 hover:bg-red-100 transition-colors duration-200">
                                                <svg class="w-5 h-5 text-gray-400 group-hover:text-red-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Contenido principal -->
                                    <div class="grid grid-cols-1 lg:grid-cols-2 min-h-[80vh] product-sans">
                                        <!-- Columna Izquierda - Panel de Información -->
                                        <div class="bg-gray-50 p-8 border-r border-gray-200">
                                            <!-- Header del Panel -->
                                            <div class="mb-8">
                                                <div class="flex items-center justify-between mb-6 p-6 bg-white rounded-xl shadow-sm border border-gray-200">
                                                    <div class="flex-1">
                                                        <div class="flex items-center space-x-2 mb-2">
                                                            <div class="w-2 h-2 bg-green-300 rounded-full animate-pulse"></div>
                                                            <span class="text-sm font-500 text-green-500 product-sans">En Proceso</span>
                                                        </div>
                                                        <h3 class="text-lg font-600 text-gray-700 product-sans mb-1">{{ $search }}</h3>
                                                        <p class="text-sm font-500 text-gray-500 product-sans uppercase tracking-wide">{{ $client_name }}</p>
                                                        <p class="text-xs text-gray-400 product-sans mt-1">{{ date('F d, Y • H:i A') }}</p>
                                                    </div>
                                                    @php
                                                        $user = Auth::user();
                                                        $roleName = $user->getRoleNames()->first();
                                                    @endphp
                                                    <div class="bg-orange-400 text-white px-4 py-2 rounded-lg shadow-sm">
                                                        <span class="text-sm font-500 product-sans">{{ $roleName ?? 'Usuario' }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Status de Validación -->
                                            <div class="mb-6">
                                                <div class="flex items-center space-x-3 mb-4">
                                                    <div class="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center">
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                    </div>
                                                    <h4 class="text-base font-600 text-gray-600 product-sans">Validando Información</h4>
                                                </div>
                                                <div class="w-full  bg-gray-100 rounded-full h-2 mb-4">
                                                    <div class="bg-orange-400 h-2 rounded-full animate-pulse" style="width: 45%"></div>
                                                </div>
                                            </div>

                                            <!-- Información de Rutas Mejorada -->
                                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                                                <div class="bg-gray-100 px-6 py-4 border-b border-gray-200">
                                                    <h4 class="text-base font-600 text-gray-600 product-sans">Detalles de la Cotización</h4>
                                                </div>
                                                <div class="max-h-96 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                                                    @foreach ($quote_data as $index => $route)
                                                        <div class="p-6 {{ $index > 0 ? 'border-t border-gray-200' : '' }}">
                                                            <div class="flex items-center space-x-2 mb-4">
                                                                <span class="bg-gray-200 text-gray-600 text-xs font-600 px-2 py-1 rounded-full product-sans">
                                                                    Ruta {{ $index + 1 }}
                                                                </span>
                                                            </div>
                                                            <div class="grid grid-cols-1 gap-3">
                                                                <div class="flex justify-between items-center py-1">
                                                                    <span class="text-sm font-500 text-gray-600 product-sans">Origen:</span>
                                                                    <span class="text-sm font-600 text-gray-900 product-sans">{{ $route['ciudad_origen'] ?? '-' }}</span>
                                                                </div>
                                                                <div class="flex justify-between items-center py-1">
                                                                    <span class="text-sm font-500 text-gray-600 product-sans">Destino:</span>
                                                                    <span class="text-sm font-600 text-gray-900 product-sans">{{ $route['ciudad_destino'] ?? '-' }}</span>
                                                                </div>
                                                                <div class="flex justify-between items-center py-1">
                                                                    <span class="text-sm font-500 text-gray-600 product-sans">Peso:</span>
                                                                    <span class="text-sm font-600 text-gray-900 product-sans">{{ $route['peso_mercancia'] ?? '0' }} kg</span>
                                                                </div>
                                                                <div class="flex justify-between items-center py-1">
                                                                    <span class="text-sm font-500 text-gray-600 product-sans">Cantidad:</span>
                                                                    <span class="text-sm font-600 text-gray-900 product-sans">{{ $route['cantidad'] ?? '0' }}</span>
                                                                </div>
                                                                <div class="flex justify-between items-center py-1">
                                                                    <span class="text-sm font-500 text-gray-600 product-sans">Tipo de embalaje:</span>
                                                                    <span class="text-sm font-600 text-gray-900 product-sans">{{ $route['tipo_embajale'] ?? '-' }}</span>
                                                                </div>
                                                                @if ($type_business == 'ce')
                                                                <div class="flex justify-between items-center py-1">
                                                                    <span class="text-sm font-500 text-gray-600 product-sans">Planos:</span>
                                                                    <span class="text-sm font-600 text-gray-900 product-sans">{{ $route['planos'] ?? '-' }}</span>
                                                                </div>
                                                                @endif
                                                                <div class="flex justify-between items-center py-1">
                                                                    <span class="text-sm font-500 text-gray-600 product-sans">Tipo producto:</span>
                                                                    <span class="text-sm font-600 text-gray-900 product-sans">{{ $route['tipo_producto'] ?? '-' }}</span>
                                                                </div>
                                                                @if ($type_business == 'refri')
                                                                <div class="flex justify-between items-center py-1">
                                                                    <span class="text-sm font-500 text-gray-600 product-sans">Temperatura:</span>
                                                                    <span class="text-sm font-600 text-gray-900 product-sans">{{ $route['temperatura_mercancia'] ?? '-' }}</span>
                                                                </div>
                                                                <div class="flex justify-between items-center py-1">
                                                                    <span class="text-sm font-500 text-gray-600 product-sans">Humedad:</span>
                                                                    <span class="text-sm font-600 text-gray-900 product-sans">{{ $route['humedad'] ?? '-' }}</span>
                                                                </div>
                                                                @endif
                                                                <div class="flex justify-between items-center py-1">
                                                                    <span class="text-sm font-500 text-gray-600 product-sans">Vehículo requerido:</span>
                                                                    <span class="text-sm font-600 text-gray-900 product-sans">{{ $route['vehiculo_requerido'] ?? '-' }}</span>
                                                                </div>
                                                                <div class="flex justify-between items-center py-1">
                                                                    <span class="text-sm font-500 text-gray-600 product-sans">Valor declarado:</span>
                                                                    <span class="text-sm font-600 text-green-600 product-sans">{{ $route['valor_declarado'] ?? '-' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Columna Derecha - Chat y Acciones -->
                                        <div class="bg-white flex flex-col">
                                            <!-- Header del Chat -->
                                            <div class="bg-orange-400 text-white p-6">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <h3 class="text-lg font-600 product-sans">Asistente IA</h3>
                                                        <p class="text-sm text-orange-100 product-sans">Configura tu cotización</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Área de Conversación -->
                                            <div wire:poll.5s.visible id="conversation" 
                                                class="flex-1 p-6 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100 bg-gray-50" 
                                                style="max-height: 500px;">
                                                @foreach ($messages as $message)
                                                    <div class="mb-4 {{ $message['role'] == 'user' ? 'flex justify-end' : 'flex justify-start' }} animate-fade-in">
                                                        <div class="max-w-xs lg:max-w-md px-4 py-3 rounded-2xl shadow-soft chat-message {{ $message['role'] == 'user' ? 'user' : '' }}
                                                            {{ $message['role'] == 'user' 
                                                                ? 'bg-orange-400 text-white rounded-br-sm' 
                                                                : 'bg-white text-gray-800 border border-gray-200 rounded-bl-sm' }}">
                                                            <div class="flex items-center justify-between mb-1">
                                                                <span class="text-xs opacity-75 product-sans">
                                                                    {{ $message['role'] == 'user' ? 'Tú' : 'Asistente' }}
                                                                </span>
                                                                <span class="text-xs opacity-75 product-sans">
                                                                    {{ $message['created_at'] }}
                                                                </span>
                                                            </div>
                                                            <p class="text-sm product-sans leading-relaxed">
                                                                {!! nl2br(e($message['text'])) !!}
                                                            </p>
                                                        </div>
                                                    </div>
                                                @endforeach
                                                
                                                @if(empty($messages))
                                                    <div class="text-center py-12 animate-fade-in">
                                                        <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                                            </svg>
                                                        </div>
                                                        <p class="text-gray-500 product-sans">¡Hola! Estoy aquí para ayudarte a crear tu cotización. Comparte los detalles de tu envío.</p>
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Área de Input Mejorada -->
                                            @if (!isset($quote_data['ciudad_origen']))
                                                <div class="border-t border-gray-200 p-6 bg-white">
                                                    <div class="space-y-4">
                                                        <div class="relative">
                                                            <!-- Indicador de grabación -->
                                                            <div id="recording-indicator" class="hidden absolute top-2 left-2 flex items-center space-x-2 bg-red-500 text-white px-3 py-1 rounded-full text-xs z-10">
                                                                <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
                                                                <span class="product-sans">Grabando...</span>
                                                            </div>
                                                            
                                                            <textarea 
                                                                id="chat-textarea"
                                                                wire:model='input_message'
                                                                wire:keydown.enter.prevent="sendMessage"
                                                                placeholder="Describe tu envío: origen, destino, peso, tipo de carga..."
                                                                class="w-full px-4 py-3 pr-20 text-gray-800 placeholder-gray-400 bg-gray-50 border border-gray-200 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-all duration-200 product-sans"
                                                                rows="3"></textarea>
                                                            
                                                            <!-- Botones de Acción -->
                                                            <div class="absolute bottom-3 right-3 flex items-center space-x-2">
                                                                <button type="button" id="startButton"
                                                                    onclick="startRecording(); return false;"
                                                                    class="w-8 h-8 flex items-center justify-center rounded-full bg-green-400 hover:bg-green-500 transition-colors duration-200 {{ $load_button ? 'hidden' : '' }}"
                                                                    title="Grabar audio">
                                                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4z"></path>
                                                                        <path d="M5.5 9.643a.75.75 0 00-1.5 0V10c0 3.06 2.29 5.585 5.25 5.954V17.5h-1.5a.75.75 0 000 1.5h4.5a.75.75 0 000-1.5H10.5v-1.546A6.001 6.001 0 0016 10v-.357a.75.75 0 00-1.5 0V10a4.5 4.5 0 01-9 0v-.357z"></path>
                                                                    </svg>
                                                                </button>

                                                                <button type="button" id="stopButton"
                                                                    onclick="stopRecording(); return false;"
                                                                    class="w-8 h-8 flex items-center justify-center rounded-full bg-red-400 hover:bg-red-500 transition-colors duration-200 {{ !$load_button ? 'hidden' : '' }}"
                                                                    title="Detener grabación">
                                                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd"></path>
                                                                    </svg>
                                                                </button>

                                                                <button type="button"
                                                                    wire:click="sendMessage"
                                                                    class="w-8 h-8 flex items-center justify-center rounded-full bg-orange-400 hover:bg-orange-500 transition-all duration-200 shadow-md"
                                                                    title="Enviar mensaje">
                                                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- Botón de Crear Cotización -->
                                            @isset($quote_data)
                                                @if(count($quote_data) > 0)
                                                    <div class="border-t border-gray-200 p-6 bg-gray-50 animate-fade-in">
                                                        <button wire:click="saveCotizacion()"
                                                            class="w-full py-4 px-6 bg-orange-400 hover:bg-orange-500 text-white font-600 rounded-xl shadow-elegant hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 hover:scale-105 product-sans group">
                                                            <div class="flex items-center justify-center space-x-2">
                                                                <svg class="w-5 h-5 group-hover:rotate-12 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                </svg>
                                                                <span class="text-base">Crear Cotización</span>
                                                                <div class="w-2 h-2 bg-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                                            </div>
                                                        </button>
                                                    </div>
                                                @endif
                                            @endisset
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @break

                @default
            @endswitch
        @endif
        
        <!-- Script de Speech Recognition - GLOBAL -->
        <script>
            // Variables globales para el reconocimiento de voz
            let recognition = null;
            let isRecording = false;
            let finalTranscript = '';
            let interimTranscript = '';

            // Función para verificar que el DOM esté cargado
            function waitForElement(selector, callback) {
                const element = document.querySelector(selector);
                if (element) {
                    callback(element);
                } else {
                    setTimeout(() => waitForElement(selector, callback), 100);
                }
            }

            // Función para iniciar grabación - GLOBAL
            function startRecording() {
                console.log('=== FUNCIÓN STARTRECORDING LLAMADA ===');
                console.log('Estado actual:', {
                    recognition: !!recognition,
                    isRecording: isRecording,
                    speechApiAvailable: 'webkitSpeechRecognition' in window || 'SpeechRecognition' in window
                });
                
                // Verificar que el textarea existe antes de iniciar
                const textarea = document.getElementById('chat-textarea');
                if (!textarea) {
                    console.error('❌ Textarea no disponible, no se puede iniciar grabación');
                    return;
                }
                console.log('✅ Textarea verificado y disponible');
                
                if (!recognition) {
                    console.error('❌ Recognition no está inicializado');
                    initSpeechRecognition(); // Intentar inicializar
                    return;
                }
                
                if (isRecording) {
                    console.log('⚠️ Ya se está grabando');
                    return;
                }
                
                try {
                    console.log('🎤 Intentando iniciar grabación...');
                    finalTranscript = '';
                    interimTranscript = '';
                    recognition.start();
                    console.log('✅ recognition.start() ejecutado sin errores');
                } catch (error) {
                    console.error('❌ Error al iniciar reconocimiento:', error);
                }
            }

            // Función para detener grabación - GLOBAL
            function stopRecording() {
                console.log('=== FUNCIÓN STOPRECORDING LLAMADA ===');
                if (recognition && isRecording) {
                    recognition.stop();
                    console.log('✓ Grabación detenida');
                } else {
                    console.log('No se está grabando actualmente');
                }
            }

            // Función de prueba directa - GLOBAL
            function testDirectUpdate() {
                const testText = 'PRUEBA DIRECTA: ' + new Date().toLocaleTimeString();
                console.log('=== FUNCIÓN TESTDIRECTUPDATE LLAMADA ===');
                console.log('Texto a insertar:', testText);
                
                const textarea = document.getElementById('chat-textarea');
                if (!textarea) {
                    console.error('❌ Textarea no encontrado');
                    return;
                }
                
                textarea.value = testText;
                textarea.dispatchEvent(new Event('input', { bubbles: true }));
                textarea.focus();
                console.log('✓ Textarea actualizado con:', testText);
            }

            // Función para actualizar el textarea
            function updateTextarea(text) {
                console.log('🔄 updateTextarea llamada con:', text);
                
                const textarea = document.getElementById('chat-textarea');
                if (!textarea) {
                    console.error('❌ Textarea no encontrado en updateTextarea');
                    console.log('🔍 Intentando encontrar textarea por otros métodos...');
                    
                    // Buscar por wire:model
                    const textareaByWire = document.querySelector('textarea[wire\\:model="input_message"]');
                    if (textareaByWire) {
                        console.log('✓ Encontrado por wire:model');
                        textareaByWire.value = text;
                        textareaByWire.dispatchEvent(new Event('input', { bubbles: true }));
                        textareaByWire.focus();
                        return;
                    }
                    
                    // Buscar cualquier textarea en el modal
                    const anyTextarea = document.querySelector('textarea');
                    if (anyTextarea) {
                        console.log('✓ Encontrado textarea genérico');
                        anyTextarea.value = text;
                        anyTextarea.dispatchEvent(new Event('input', { bubbles: true }));
                        anyTextarea.focus();
                        return;
                    }
                    
                    console.error('❌ No se pudo encontrar ningún textarea');
                    return;
                }
                
                textarea.value = text;
                textarea.dispatchEvent(new Event('input', { bubbles: true }));
                textarea.focus();
                console.log('✅ Textarea actualizado correctamente con:', text);
            }

            // Inicializar reconocimiento de voz
            function initSpeechRecognition() {
                console.log('🔧 Inicializando Speech Recognition...');
                
                // Verificar compatibilidad
                if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
                    console.error('❌ Web Speech API no es compatible con este navegador');
                    return;
                }
                
                try {
                    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                    recognition = new SpeechRecognition();
                    
                    recognition.continuous = true;
                    recognition.interimResults = true;
                    recognition.lang = 'es-ES';
                    
                    console.log('⚙️ Configuración aplicada:', {
                        continuous: recognition.continuous,
                        interimResults: recognition.interimResults,
                        lang: recognition.lang
                    });
                    
                    recognition.onstart = () => {
                        console.log('🎤 EVENTO: onstart - Reconocimiento iniciado');
                        isRecording = true;
                        finalTranscript = '';
                        interimTranscript = '';
                        
                        const indicator = document.getElementById('recording-indicator');
                        if (indicator) {
                            indicator.classList.remove('hidden');
                            console.log('👁️ Indicador de grabación mostrado');
                        } else {
                            console.warn('⚠️ Indicador de grabación no encontrado');
                        }
                    };
                    
                    recognition.onresult = (event) => {
                        console.log('🔊 EVENTO: onresult - Resultado recibido');
                        console.log('Número de resultados:', event.results.length);
                        
                        // Verificar que el textarea existe antes de proceder
                        const textarea = document.getElementById('chat-textarea');
                        if (!textarea) {
                            console.error('❌ Textarea no disponible durante onresult');
                            // Intentar detener el reconocimiento si no hay textarea
                            if (recognition && isRecording) {
                                recognition.stop();
                            }
                            return;
                        }
                        
                        interimTranscript = '';
                        
                        for (let i = event.resultIndex; i < event.results.length; i++) {
                            const transcript = event.results[i][0].transcript;
                            const confidence = event.results[i][0].confidence;
                            console.log(`Resultado ${i}:`, {
                                transcript: transcript,
                                confidence: confidence,
                                isFinal: event.results[i].isFinal
                            });
                            
                            if (event.results[i].isFinal) {
                                finalTranscript += transcript + ' ';
                                console.log('📝 Transcript final acumulado:', finalTranscript);
                            } else {
                                interimTranscript += transcript;
                                console.log('🔄 Transcript interim:', interimTranscript);
                            }
                        }
                        
                        const fullText = finalTranscript + interimTranscript;
                        console.log('📄 Texto completo a mostrar:', fullText);
                        
                        if (fullText.trim()) {
                            updateTextarea(fullText.trim());
                        }
                    };
                    
                    recognition.onerror = (event) => {
                        console.error('❌ EVENTO: onerror - Error en speech recognition');
                        console.error('Tipo de error:', event.error);
                        console.error('Mensaje:', event.message);
                        
                        isRecording = false;
                        
                        const indicator = document.getElementById('recording-indicator');
                        if (indicator) {
                            indicator.classList.add('hidden');
                        }
                        
                        // Errores específicos
                        switch (event.error) {
                            case 'not-allowed':
                                console.error('❌ Permiso denegado para el micrófono');
                                break;
                            case 'no-speech':
                                console.log('⚠️ No se detectó voz');
                                break;
                            case 'audio-capture':
                                console.error('❌ No se pudo acceder al micrófono');
                                break;
                            case 'network':
                                console.error('❌ Error de red durante el reconocimiento');
                                break;
                            default:
                                console.error('❌ Error en reconocimiento de voz:', event.error);
                        }
                    };
                    
                    recognition.onend = () => {
                        console.log('🛑 EVENTO: onend - Reconocimiento terminado');
                        console.log('Transcript final al terminar:', finalTranscript);
                        
                        isRecording = false;
                        
                        const indicator = document.getElementById('recording-indicator');
                        if (indicator) {
                            indicator.classList.add('hidden');
                        }
                        
                        if (finalTranscript.trim()) {
                            console.log('💾 Actualizando textarea con transcript final');
                            updateTextarea(finalTranscript.trim());
                        } else {
                            console.log('⚠️ No hay transcript final para guardar');
                        }
                    };
                    
                    console.log('✅ Speech Recognition inicializado correctamente');
                    
                } catch (error) {
                    console.error('❌ Error al crear Speech Recognition:', error);
                }
            }

            // Esperar a que el DOM esté listo
            document.addEventListener('DOMContentLoaded', () => {
                console.log('📄 DOM Content Loaded');
                initSpeechRecognition();
                
                // Agregar funciones al window para debugging
                window.testDirect = testDirectUpdate;
                window.testSpeech = () => {
                    console.log('Estado del Speech Recognition:', {
                        recognition: !!recognition,
                        isRecording: isRecording,
                        webkitSpeechRecognition: 'webkitSpeechRecognition' in window,
                        SpeechRecognition: 'SpeechRecognition' in window
                    });
                };
                
                console.log('🔧 Funciones globales configuradas');
            });

            // También inicializar cuando Livewire esté listo
            document.addEventListener('livewire:initialized', () => {
                console.log('⚡ Livewire inicializado');
                setTimeout(() => {
                    initSpeechRecognition();
                }, 500);
            });
        </script>
        
        @php
            // ¿Hay por lo menos un pricing real en alguna ruta?
            $existenPricings = collect($pricings)->flatten(1)->count() > 0;
        @endphp

        @if ($showModal3)
            @if($existenPricings)
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=Product+Sans:wght@300;400;500;600;700&display=swap');
                    .product-sans {
                        font-family: 'Product Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    }
                    
                    /* Estilos para inputs deshabilitados por switch */
                    .input-disabled-by-switch {
                        background-color: #f3f4f6 !important;
                        color: #9ca3af !important;
                        cursor: not-allowed !important;
                        opacity: 0.6 !important;
                        border: 1px solid #d1d5db !important;
                        position: relative;
                    }
                    
                    .input-disabled-by-switch::before {
                        content: "Controlado por switch";
                        position: absolute;
                        top: -20px;
                        left: 0;
                        font-size: 10px;
                        color: #fb923c;
                        font-weight: 500;
                        z-index: 10;
                    }
                </style>

                <div class="relative z-20 product-sans" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity backdrop-blur-sm"></div>
                    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                        <div class="flex min-h-full w-full items-center justify-center p-6">
                            <div class="relative w-full max-w-[95vw] lg:max-w-7xl transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all">
                                <!-- Header con botón de cerrar mejorado -->
                                <div class="relative bg-white border-b border-gray-200 px-8 py-6">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <h2 class="text-xl font-600 text-gray-700 product-sans">Configuración de Pricing</h2>
                                        </div>
                                        <button wire:click="closeModal(3)" 
                                            class="group flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 hover:bg-red-100 transition-colors duration-200">
                                            <svg class="w-5 h-5 text-gray-400 group-hover:text-red-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Contenido principal -->
                                <div class="p-8 bg-gray-50">
                                    @push('scripts')
                                        <script>
                                            // Función para mostrar mensaje cuando el botón está deshabilitado
                                            function mostrarMensajeValidacion() {
                                                // Validar vehículos seleccionados
                                                const selectsVehiculo = document.querySelectorAll('select[wire\\:model*="select_value"]');
                                                let vehiculosFaltantes = false;
                                                
                                                selectsVehiculo.forEach(function(select) {
                                                    if (!select.value || select.value === '') {
                                                        vehiculosFaltantes = true;
                                                    }
                                                });

                                                // Validar porcentajes >= 17
                                                const inputsPorcentaje = document.querySelectorAll('input[wire\\:model*="porcentaje"]');
                                                let porcentajesInvalidos = false;
                                                
                                                inputsPorcentaje.forEach(function(input) {
                                                    const valor = parseFloat(input.value);
                                                    if (isNaN(valor) || valor < 17) {
                                                        porcentajesInvalidos = true;
                                                    }
                                                });

                                                // Mostrar alerta específica
                                                if (vehiculosFaltantes) {
                                                    alert('Debes seleccionar un vehículo para todas las rutas antes de continuar.');
                                                } else if (porcentajesInvalidos) {
                                                    alert('Todos los porcentajes de rentabilidad deben ser igual o mayor al 17%.');
                                                }
                                            }

                                            // Validación en tiempo real de porcentajes
                                            document.addEventListener('DOMContentLoaded', function() {
                                                // Validación al escribir en campos de porcentaje
                                                document.addEventListener('input', function(e) {
                                                    if (e.target.type === 'number' && e.target.placeholder === '%') {
                                                        const valor = parseFloat(e.target.value);
                                                        if (valor < 17 && valor !== 0 && !isNaN(valor)) {
                                                            alert('No se puede ingresar una rentabilidad menor al 17%');
                                                            e.target.value = 17;
                                                            e.target.dispatchEvent(new Event('change', { bubbles: true }));
                                                        }
                                                    }
                                                    
                                                    // Feedback visual para campos de acompañamiento
                                                    if (e.target.type === 'number' && e.target.wire && e.target.wire.model && e.target.wire.model.includes('acompanamientovalor')) {
                                                        const valor = parseFloat(e.target.value);
                                                        if (!isNaN(valor) && valor > 0) {
                                                            e.target.classList.add('border-green-400', 'bg-green-50');
                                                            e.target.classList.remove('border-gray-300');
                                                            
                                                            // Agregar efecto de highlight temporal
                                                            e.target.style.boxShadow = '0 0 0 3px rgba(34, 197, 94, 0.2)';
                                                            setTimeout(() => {
                                                                e.target.style.boxShadow = '';
                                                            }, 1000);
                                                        } else {
                                                            e.target.classList.remove('border-green-400', 'bg-green-50');
                                                            e.target.classList.add('border-gray-300');
                                                        }
                                                    }
                                                });

                                                // Validación cuando el campo pierde el foco
                                                document.addEventListener('blur', function(e) {
                                                    if (e.target.type === 'number' && e.target.placeholder === '%') {
                                                        const valor = parseFloat(e.target.value);
                                                        if (valor < 17 && valor !== 0 && !isNaN(valor)) {
                                                            alert('No se puede ingresar una rentabilidad menor al 17%');
                                                            e.target.value = 17;
                                                            e.target.dispatchEvent(new Event('change', { bubbles: true }));
                                                        }
                                                    }
                                                }, true);
                                            });
                                        </script>
                                    @endpush

                                        @if($ciudadFaltante)
                                            <div class="bg-yellow-50 p-4 rounded-lg mb-6">
                                                <p class="text-sm mb-2 font-semibold">No encontramos una o ambas ciudades:</p>

                                                <div class="mb-3">
                                                    <label class="text-xs font-semibold">Origen («{{ $nombreOrigenRaw }}»)</label>
                                                    <select wire:model="ciudadOrigenSel" class="border rounded w-full text-xs mt-1">
                                                        <option value="">-- Selecciona --</option>
                                                        @foreach(\App\Helpers\CityHelper::list() as $cod => $nom)
                                                            <option value="{{ $cod }}">{{ $nom }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="mb-4">
                                                    <label class="text-xs font-semibold">Destino («{{ $nombreDestinoRaw }}»)</label>
                                                    <select wire:model="ciudadDestinoSel" class="border rounded w-full text-xs mt-1">
                                                        <option value="">-- Selecciona --</option>
                                                        @foreach(\App\Helpers\CityHelper::list() as $cod => $nom)
                                                            <option value="{{ $cod }}">{{ $nom }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <button wire:click="confirmarCiudades"
                                                        class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-xs rounded">
                                                    Confirmar y continuar
                                                </button>
                                            </div>
                                        @endif

                                    <!-- Header de información -->
                                    <div class="mb-8">
                                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1">
                                                    <div class="flex items-center space-x-2 mb-2">
                                                        <div class="w-2 h-2 bg-orange-400 rounded-full animate-pulse"></div>
                                                        <span class="text-sm font-500 text-orange-500 product-sans">Configurando Pricing</span>
                                                    </div>
                                                    <h3 class="text-lg font-600 text-gray-700 product-sans mb-1">Creación de cotización</h3>
                                                    <p class="text-sm font-500 text-gray-500 product-sans">{{ $client_document_client ?? '-' }} | <span class="font-bold uppercase">{{ $client_name ?? '-' }}</span></p>
                                                    <p class="text-xs text-gray-400 product-sans mt-1">{{ date('F d, Y • H:i A') }}</p>
                                                </div>
                                                @php
                                                    $user = Auth::user();
                                                    $roleName = $user->getRoleNames()->first();
                                                @endphp
                                                <div class="bg-orange-400 text-white px-4 py-2 rounded-lg shadow-sm">
                                                    <span class="text-sm font-500 product-sans">{{ $roleName ?? 'Usuario' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Primera Fila: Tabla de rutas y Tarjetas de rentabilidad -->
                                    <div class="flex flex-row gap-6 h-[580px]">
                                        <!-- Columna Izquierda: Tabla de rutas -->
                                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col h-full w-2/4">
                                            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex-shrink-0">
                                                <h4 class="text-sm font-600 text-gray-700 product-sans">Configuración de Rutas y Precios</h4>
                                            </div>
                                            <div class="overflow-x-auto flex-1 min-h-0">
                                                <table class="w-full text-sm">
                                                    <thead>
                                                        <tr class="text-gray-700 text-xs font-600 border-b border-gray-200 bg-gray-50">
                                                            <th class="text-left px-3 py-2 product-sans">Origen</th>
                                                            <th class="text-left px-3 py-2 product-sans">Destino</th>
                                                            <th class="text-left px-3 py-2 product-sans">Vehículo</th>
                                                            <th class="text-center px-3 py-2 product-sans">Precio Base</th>
                                                            <th class="text-center px-3 py-2 product-sans">Rent.(%)</th>
                                                            <th class="text-center px-3 py-2 product-sans">Valor Cliente</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($quote_data as $index => $route)
                                                            <!-- Bloque de ruta {{ $index + 1 }} -->
                                                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors duration-150">
                                                                <td class="px-3 py-2 text-xs font-500 text-gray-700 product-sans">{{ $route['ciudad_origen'] ?? '-' }}</td>
                                                                <td class="px-3 py-2 text-xs font-500 text-gray-700 product-sans">{{ $route['ciudad_destino'] ?? '-' }}</td>
                                                                <td class="px-3 py-2">
                                                                    {{-- Sugerencia de la IA --------------------------------------------------}}
                                                                    @if(isset($vehicleSuggestions[$index]))
                                                                        <div class="text-[10px] mb-1 rounded bg-blue-50 text-blue-600 px-1.5 py-0.5">
                                                                            IA sugiere:
                                                                            <strong>{{ $vehicleSuggestions[$index]['vehicle'] }}</strong>
                                                                            <span class="text-gray-500">/ {{ $vehicleSuggestions[$index]['bodywork'] }}</span>
                                                                        </div>
                                                                    @endif

                                                                    {{-- Selector real que el usuario puede cambiar ------------------------- --}}
                                                                    <select wire:model="quote_data.{{ $index }}.select_value"
                                                                            wire:change="handleClickPricing({{ $index }})"
                                                                            class="w-full px-2 py-2 text-xs border border-gray-300 rounded h-10
                                                                                focus:outline-none focus:ring-1 focus:ring-orange-400">
                                                                        <option value="">Selecciona vehículo</option>
                                                                        @foreach ($pricings[$index] ?? [] as $pricing)
                                                                            <option value="{{ (string) $pricing->id }}">{{ $pricing->vehicle_type ?? 'N/A' }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </td>
                                                                <td class="px-3 py-2 text-center">
                                                                    <span class="text-xs font-600 text-gray-700 product-sans">
                                                                      ${{ isset($selectedPricings[$index])
                                                                            ? number_format($selectedPricings[$index]->price)
                                                                            : '0' }}
                                                                    </span>
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <div class="flex flex-col items-center space-y-1">
                                                                        <input type="number"
                                                                            wire:model.live="quote_data.{{ $index }}.porcentaje"
                                                                            wire:change="editarPorcentajeIndividual({{ $index }})"
                                                                            class="w-20 px-3 py-2 text-sm text-center border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent product-sans bg-white font-medium h-10
                                                                                {{ !empty($porcentaje_modificado[$index]) ? 'border-orange-400 bg-orange-50' : 'border-gray-300' }}
                                                                                {{ isset($errores_porcentaje[$index]) ? 'border-red-500 bg-red-50' : '' }}" 
                                                                            placeholder="%" 
                                                                            min="17" 
                                                                            max="100" />
                                                                        
                                                                        @if (isset($errores_porcentaje[$index]))
                                                                            <span class="text-red-500 text-xs text-center product-sans">
                                                                                {{ $errores_porcentaje[$index] }}
                                                                            </span>
                                                                        @endif

                                                                        @if (!empty($porcentaje_modificado[$index]) && !isset($errores_porcentaje[$index]))
                                                                            <button wire:click="restaurarPorcentajeGlobal({{ $index }})"
                                                                                    class="text-xs text-orange-500 hover:text-orange-600 underline product-sans">
                                                                                Restaurar
                                                                            </button>
                                                                        @endif
                                                                    </div>
                                                                </td>
                                                                <td class="px-3 py-2 text-center">
                                                                    @php
                                                                        // Obtener valores directos
                                                                        $precioBase = isset($selectedPricings[$index]) ? $selectedPricings[$index]->price : 0;
                                                                        $porcentajeRentabilidad = floatval($route['porcentaje'] ?? 0);
                                                                        $valorAcompanamiento = floatval($route['itesoltra_acompanamientovalor'] ?? 0);
                                                                        
                                                                        // Calcular valor con rentabilidad
                                                                        $valorRentabilidad = $precioBase * ($porcentajeRentabilidad / 100);
                                                                        $valorBaseConRentabilidad = $precioBase + $valorRentabilidad;
                                                                        
                                                                        // Valor final = Base + Rentabilidad + Acompañamiento
                                                                        $valorFinalCompleto = $valorBaseConRentabilidad + $valorAcompanamiento;
                                                                    @endphp
                                                                    
                                                                    @if($valorAcompanamiento > 0)
                                                                        <div class="text-[9px] text-gray-500 mb-1">
                                                                            Base+Rent: ${{ number_format($valorBaseConRentabilidad) }} + ACC: ${{ number_format($valorAcompanamiento) }}
                                                                        </div>
                                                                    @endif
                                                                    
                                                                    <span class="text-xs font-700 text-orange-600">
                                                                        ${{ number_format($valorFinalCompleto) }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            
                                                            {{-- Fila de acompañamiento (opcional) --}}
                                                            <tr class="bg-gray-50 border-b-4 border-orange-200">
                                                                <td colspan="6" class="px-3 py-3">
                                                                    <div class="bg-white rounded-lg border-2 border-gray-100 shadow-sm p-3">
                                                                        <div class="flex items-center justify-between mb-3">
                                                                            <div class="text-sm text-gray-700 font-medium flex items-center">
                                                                                <span class="w-2 h-2 bg-orange-400 rounded-full mr-2"></span>
                                                                                Acompañamiento (opcional)
                                                                            </div>
                                                                            <label class="relative inline-flex items-center cursor-pointer">
                                                                                <input type="checkbox" class="sr-only peer" 
                                                                                       wire:model.live="quote_data.{{ $index }}.acompanamiento_enabled"
                                                                                       wire:change="toggleAcompanamiento({{ $index }})">
                                                                                <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
                                                                            </label>
                                                                        </div>
                                                                        
                                                                        <!-- Grid horizontal para campos de acompañamiento - Solo visible cuando el switch está activo -->
                                                                        @if(!empty($quote_data[$index]['acompanamiento_enabled']))
                                                                            <div class="grid grid-cols-4 gap-3 bg-gray-50 p-3 rounded-lg border border-gray-200">
                                                                                <!-- Cantidad de vehículos -->
                                                                                <div>
                                                                                    <label class="block text-xs text-gray-600 mb-2 font-medium">N° vehículos</label>
                                                                                    <input type="number" min="0" placeholder="1"
                                                                                          class="w-full px-2 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent h-10"
                                                                                        wire:model.live="quote_data.{{ $index }}.itesoltra_vehiculoacompanamiento">
                                                                                </div>

                                                                                <!-- Tipo de acompañamiento -->
                                                                                <div>
                                                                                    <label class="block text-xs text-gray-600 mb-2 font-medium">Tipo ACC</label>
                                                                                    <select class="w-full px-2 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent h-10"
                                                                                            wire:model.live="quote_data.{{ $index }}.tipaco_codigo">
                                                                                        <option value="">Seleccionar</option>
                                                                                        <option value="MOTORIZADO">MOTORIZADO</option>
                                                                                        <option value="VEHICULAR">VEHICULAR</option>
                                                                                        <option value="CABINA">CABINA</option>
                                                                                    </select>
                                                                                </div>

                                                                                <!-- ¿Quién lo paga? -->
                                                                                <div>
                                                                                    <label class="block text-xs text-gray-600 mb-2 font-medium">¿Quién lo asume?</label>
                                                                                    <select class="w-full px-2 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent h-10"
                                                                                            wire:model.live="quote_data.{{ $index }}.itesoltra_acompanamientocuentade">
                                                                                        <option value="">Seleccionar</option>
                                                                                        <option value="CLIENTE">CLIENTE</option>
                                                                                        <option value="EMPRESA">EMPRESA</option>
                                                                                    </select>
                                                                                </div>

                                                                                <!-- Valor -->
                                                                                <div>
                                                                                    <label class="block text-xs text-gray-600 mb-2 font-medium">Valor COP</label>
                                                                                    <input type="number" min="0" step="0.01" 
                                                                                        placeholder="0"
                                                                                        value="{{ $route['itesoltra_acompanamientovalor'] ?? 0 }}"
                                                                                        class="w-full px-2 py-2 text-sm border rounded-md focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent h-10
                                                                                               {{ !empty($route['itesoltra_acompanamientovalor']) && $route['itesoltra_acompanamientovalor'] > 0 ? 'border-green-400 bg-green-50' : 'border-gray-300' }}"
                                                                                        wire:model.live="quote_data.{{ $index }}.itesoltra_acompanamientovalor"
                                                                                        {{ $route['acompanamiento_enabled'] ?? false ? '' : 'disabled' }}>
                                                                                    @if(!empty($route['itesoltra_acompanamientovalor']) && $route['itesoltra_acompanamientovalor'] > 0)
                                                                                        <div class="text-[9px] text-green-600 mt-1 font-medium">
                                                                                            ✓ Se sumará al valor final
                                                                                        </div>
                                                                                    @endif
                                                                                </div>
                                                                                
                                                                               
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            
                                                            @if(!$loop->last)
                                                            <!-- Separador entre bloques -->
                                                            <tr>
                                                                <td colspan="6" class="py-2">
                                                                    <div class="border-t-2 border-gray-300"></div>
                                                                </td>
                                                            </tr>
                                                            @endif
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <!-- Columna Derecha: Tarjetas de rentabilidad con switches en fila horizontal -->
                                        <div class="flex flex-row justify-between h-full space-x-3 w-2/4">
                                            <!-- Propuesta 1: Rentabilidad Mínima -->
                                            <div class="rentabilidad-card bg-white rounded-lg border-2 transition-all duration-300 overflow-hidden w-1/3 h-full
                                                {{ $porcentaje_global === 17 ? 'border-orange-400 shadow-md shadow-orange-100' : 'border-gray-200' }}">
                                                <div class="p-3 h-full flex flex-col items-center justify-center">
                                                    <div class="flex flex-col items-center justify-center mb-2 w-full">
                                                        <div class="flex flex-col items-center justify-center space-y-1 w-full">
                                                            <div class="text-xs font-500 text-gray-400 product-sans text-center">PROPUESTA #1</div>
                                                            <div class="text-xs font-600 text-gray-700 product-sans text-center">RENTABILIDAD MÍNIMA</div>
                                                            <div class="text-xl font-700 product-sans {{ $porcentaje_global === 17 ? 'text-orange-500' : 'text-gray-900' }} text-center">17%</div>
                                                            <label class="relative inline-flex items-center cursor-pointer justify-center mt-2" onclick="toggleRentabilidad(17); return false;"> 
                                                                <input type="radio" 
                                                                    id="switch-17"
                                                                    name="rentabilidad_option"
                                                                    value="17"
                                                                    class="rentabilidad-switch sr-only peer" 
                                                                    {{ $porcentaje_global === 17 ? 'checked' : '' }}>
                                                                <div class="relative w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-orange-400"></div>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="border-t border-gray-200 pt-2 flex-1 w-full flex flex-col items-center justify-center">
                                                        <!-- Lista vertical de precios por ruta -->
                                                        <div class="flex flex-col space-y-3 mb-2 w-full items-center justify-center">
                                                            @foreach ($quote_data as $index => $route)
                                                                <div class="flex flex-col items-center w-full">
                                                                    <div class="text-xs text-gray-500 product-sans mb-1 text-center">
                                                                        {{ substr($route['ciudad_origen'] ?? '-', 0, 3) }}-{{ substr($route['ciudad_destino'] ?? '-', 0, 3) }}
                                                                    </div>
                                                                    <div class="text-xs font-600 text-orange-600 product-sans text-center">
                                                                        ${{ isset($selectedPricings[$index]) ? number_format($selectedPricings[$index]->price + $selectedPricings[$index]->price * 0.17) : '0' }}
                                                                    </div>
                                                                </div>
                                                                @if(!$loop->last)
                                                                    <div class="w-full h-px bg-gray-200 my-2"></div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                        <div class="border-t border-gray-200 pt-2 mt-auto w-full flex flex-col items-center justify-center">
                                                            <span class="text-xs font-500 text-gray-600 product-sans text-center">VALOR FINAL</span>
                                                            <span class="text-sm font-700 text-orange-600 product-sans text-center">
                                                                ${{ number_format(
                                                                    collect($selectedPricings)->keys()->sum(function($i) use ($selectedPricings,$quote_data){
                                                                        $p      = $selectedPricings[$i] ?? null;
                                                                        $acomp  = floatval($quote_data[$i]['subtotal_acompanante'] ?? 0);
                                                                        return $p ? ($p->price + $p->price * 0.17) + $acomp : 0;
                                                                    })
                                                                ) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Propuesta 2: Rentabilidad Promedio -->
                                            <div class="rentabilidad-card bg-white rounded-lg border-2 transition-all duration-300 overflow-hidden w-1/3 h-full
                                                {{ $porcentaje_global === 24 ? 'border-orange-400 shadow-md shadow-orange-100' : 'border-gray-200' }}">
                                                <div class="p-3 h-full flex flex-col items-center justify-center">
                                                    <div class="flex flex-col items-center justify-center mb-2 w-full">
                                                        <div class="flex flex-col items-center justify-center space-y-1 w-full">
                                                            <div class="text-xs font-500 text-gray-400 product-sans text-center">PROPUESTA #2</div>
                                                            <div class="text-xs font-600 text-gray-700 product-sans text-center">RENTABILIDAD PROMEDIO</div>
                                                            <div class="text-xl font-700 product-sans {{ $porcentaje_global === 24 ? 'text-orange-500' : 'text-gray-900' }} text-center">24%</div>
                                                            <label class="relative inline-flex items-center cursor-pointer justify-center mt-2" onclick="toggleRentabilidad(24); return false;">
                                                                <input type="radio" 
                                                                       id="switch-24"
                                                                       name="rentabilidad_option"
                                                                       value="24"
                                                                       class="rentabilidad-switch sr-only peer" 
                                                                       {{ $porcentaje_global === 24 ? 'checked' : '' }}>
                                                                <div class="relative w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-orange-400"></div>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="border-t border-gray-200 pt-2 flex-1 w-full flex flex-col items-center justify-center">
                                                        <!-- Lista vertical de precios por ruta -->
                                                        <div class="flex flex-col space-y-3 mb-2 w-full items-center justify-center">
                                                            @foreach ($quote_data as $index => $route)
                                                                <div class="flex flex-col items-center w-full">
                                                                    <div class="text-xs text-gray-500 product-sans mb-1 text-center">
                                                                        {{ substr($route['ciudad_origen'] ?? '-', 0, 3) }}-{{ substr($route['ciudad_destino'] ?? '-', 0, 3) }}
                                                                    </div>
                                                                    <div class="text-xs font-600 text-orange-600 product-sans text-center">
                                                                        ${{ isset($selectedPricings[$index]) ? number_format($selectedPricings[$index]->price + $selectedPricings[$index]->price * 0.24) : '0' }}
                                                                    </div>
                                                                </div>
                                                                @if(!$loop->last)
                                                                    <div class="w-full h-px bg-gray-200 my-2"></div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                        <div class="border-t border-gray-200 pt-2 mt-auto w-full flex flex-col items-center justify-center">
                                                            <span class="text-xs font-500 text-gray-600 product-sans text-center">VALOR FINAL</span>
                                                            <span class="text-sm font-700 text-orange-600 product-sans text-center">
                                                                ${{ number_format(
                                                                    collect($selectedPricings)->keys()->sum(function ($i) use ($selectedPricings,$quote_data) {
                                                                        $p     = $selectedPricings[$i] ?? null;
                                                                        $acomp = floatval($quote_data[$i]['subtotal_acompanante'] ?? 0);
                                                                        return $p ? ($p->price + $p->price * 0.24) + $acomp : 0;
                                                                    })
                                                                ) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Propuesta 3: Rentabilidad Mayor Vendida -->
                                            <div class="rentabilidad-card bg-white rounded-lg border-2 transition-all duration-300 overflow-hidden w-1/3 h-full
                                                {{ $porcentaje_global === 32 ? 'border-orange-400 shadow-md shadow-orange-100' : 'border-gray-200' }}">
                                                <div class="p-3 h-full flex flex-col items-center justify-center">
                                                    <div class="flex flex-col items-center justify-center mb-2 w-full">
                                                        <div class="flex flex-col items-center justify-center space-y-1 w-full">
                                                            <div class="text-xs font-500 text-gray-400 product-sans text-center">PROPUESTA #3</div>
                                                            <div class="text-xs font-600 text-gray-700 product-sans text-center">RENTABILIDAD MAYOR VENDIDA</div>
                                                            <div class="text-xl font-700 product-sans {{ $porcentaje_global === 32 ? 'text-orange-500' : 'text-gray-900' }} text-center">32%</div>
                                                            <label class="relative inline-flex items-center cursor-pointer justify-center mt-2" onclick="toggleRentabilidad(32); return false;">
                                                                <input type="radio" 
                                                                       id="switch-32"
                                                                       name="rentabilidad_option"
                                                                       value="32"
                                                                       class="rentabilidad-switch sr-only peer" 
                                                                       {{ $porcentaje_global === 32 ? 'checked' : '' }}>
                                                                <div class="relative w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-orange-400"></div>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="border-t border-gray-200 pt-2 flex-1 w-full flex flex-col items-center justify-center">
                                                        <!-- Lista vertical de precios por ruta -->
                                                        <div class="flex flex-col space-y-3 mb-2 w-full items-center justify-center">
                                                            @foreach ($quote_data as $index => $route)
                                                                <div class="flex flex-col items-center w-full">
                                                                    <div class="text-xs text-gray-500 product-sans mb-1 text-center">
                                                                        {{ substr($route['ciudad_origen'] ?? '-', 0, 3) }}-{{ substr($route['ciudad_destino'] ?? '-', 0, 3) }}
                                                                    </div>
                                                                    <div class="text-xs font-600 text-orange-600 product-sans text-center">
                                                                        ${{ isset($selectedPricings[$index]) ? number_format($selectedPricings[$index]->price + $selectedPricings[$index]->price * 0.32) : '0' }}
                                                                    </div>
                                                                </div>
                                                                @if(!$loop->last)
                                                                    <div class="w-full h-px bg-gray-200 my-2"></div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                        <div class="border-t border-gray-200 pt-2 mt-auto w-full flex flex-col items-center justify-center">
                                                            <span class="text-xs font-500 text-gray-600 product-sans text-center">VALOR FINAL</span>
                                                            <span class="text-sm font-700 text-orange-600 product-sans text-center">
                                                                ${{ number_format(
                                                                    collect($selectedPricings)->keys()->sum(function ($i) use ($selectedPricings,$quote_data) {
                                                                        $p     = $selectedPricings[$i] ?? null;
                                                                        $acomp = floatval($quote_data[$i]['subtotal_acompanante'] ?? 0);
                                                                        return $p ? ($p->price + $p->price * 0.32) + $acomp : 0;
                                                                    })
                                                                ) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                      
                                   

                                 

                                    <!-- Botón de continuar -->
                                    <div class="flex justify-center">
                                        @php
                                            // Verificar si todas las rutas tienen un vehículo seleccionado
                                            $todasRutasConVehiculo = true;
                                            foreach ($quote_data as $index => $route) {
                                                if (empty($route['select_value'])) {
                                                    $todasRutasConVehiculo = false;
                                                    break;
                                                }
                                            }

                                            // Verificar si todos los porcentajes son >= 17
                                            $todosPorcentajesValidos = true;
                                            foreach ($quote_data as $index => $route) {
                                                $porcentaje = $route['porcentaje'] ?? 0;
                                                if ($porcentaje < 17) {
                                                    $todosPorcentajesValidos = false;
                                                    break;
                                                }
                                            }

                                            // El botón solo está habilitado si ambas condiciones se cumplen
                                            $botonHabilitado = $todasRutasConVehiculo && $todosPorcentajesValidos;
                                        @endphp
                                        
                                        <button 
                                            id="btn-continuar"
                                            class="px-8 py-4 {{ $botonHabilitado ? 'bg-orange-400 hover:bg-orange-500' : 'bg-gray-300 cursor-not-allowed' }} text-white font-600 rounded-xl shadow-lg {{ $botonHabilitado ? 'hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1' : '' }} product-sans group min-w-[200px]"
                                            style="margin-top:30px;"
                                            @if($botonHabilitado)
                                                wire:click="nextStep(1)"
                                            @else
                                                onclick="mostrarMensajeValidacion()"
                                            @endif
                                            wire:loading.attr="disabled"
                                            {{ !$botonHabilitado ? 'disabled' : '' }}>
                                            <div class="flex items-center justify-center space-x-2">
                                                <span wire:loading.remove class="text-base">
                                                    @if(!$todasRutasConVehiculo)
                                                        Selecciona vehículos
                                                    @elseif(!$todosPorcentajesValidos)
                                                        Ajusta rentabilidad (min 17%)
                                                    @else
                                                        Continuar
                                                    @endif
                                                </span>
                                                <span wire:loading class="text-sm">Generando documento...</span>
                                                @if($botonHabilitado)
                                                    <svg wire:loading.remove class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                                    </svg>
                                                @else
                                                    <svg wire:loading.remove class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                    </svg>
                                                @endif
                                                <svg wire:loading class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                                </svg>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        @if($loading_prompt)
                            <div class="fixed inset-0 bg-gray-900 bg-opacity-70 flex items-center justify-center z-50 backdrop-blur-sm">
                                <div class="bg-white px-8 py-6 rounded-2xl shadow-2xl flex flex-col items-center max-w-sm mx-4">
                                    <div class="relative">
                                        <svg class="animate-spin w-16 h-16 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                        </svg>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <h3 class="mt-6 text-lg font-600 text-gray-700 text-center product-sans">Generando Documento</h3>
                                    <p class="mt-2 text-sm text-gray-500 text-center product-sans">
                                        La IA está procesando tu cotización,<br>por favor espera...
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                    <div class="relative z-20" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                            <div
                                class="flex min-h-full w-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                                <div
                                    class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8">
                                    <div class="grid grid-cols-1 p-12" style="background: rgba(255, 207, 102, 0.1)">
                                        <div class="w-full h-full flex flex-col">
                                            <form method="dialog" class="mr-5 self-end">
                                                <button wire:click="closeModalSuccess2()">
                                                    <svg width="24" height="24" viewBox="0 0 24 24"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                            d="M13.4142 12L17.7072 7.70701C18.0982 7.31601 18.0982 6.68401 17.7072 6.29301C17.3162 5.90201 16.6842 5.90201 16.2933 6.29301L12.0002 10.586L7.70725 6.29301C7.31625 5.90201 6.68425 5.90201 6.29325 6.29301C5.90225 6.68401 5.90225 7.31601 6.29325 7.70701L10.5862 12L6.29325 16.293C5.90225 16.684 5.90225 17.316 6.29325 17.707C6.48825 17.902 6.74425 18 7.00025 18C7.25625 18 7.51225 17.902 7.70725 17.707L12.0002 13.414L16.2933 17.707C16.4882 17.902 16.7443 18 17.0002 18C17.2562 18 17.5122 17.902 17.7072 17.707C18.0982 17.316 18.0982 16.684 17.7072 16.293L13.4142 12Z"
                                                            fill="#18203A" />
                                                        <mask id="mask0_135_2594" style="mask-type:luminance"
                                                            maskUnits="userSpaceOnUse" x="6" y="5" width="13"
                                                            height="13">
                                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                                d="M13.4142 12L17.7072 7.70701C18.0982 7.31601 18.0982 6.68401 17.7072 6.29301C17.3162 5.90201 16.6842 5.90201 16.2933 6.29301L12.0002 10.586L7.70725 6.29301C7.31625 5.90201 6.68425 5.90201 6.29325 6.29301C5.90225 6.68401 5.90225 7.31601 6.29325 7.70701L10.5862 12L6.29325 16.293C5.90225 16.684 5.90225 17.316 6.29325 17.707C6.48825 17.902 6.74425 18 7.00025 18C7.25625 18 7.51225 17.902 7.70725 17.707L12.0002 13.414L16.2933 17.707C16.4882 17.902 16.7443 18 17.0002 18C17.2562 18 17.5122 17.902 17.7072 17.707C18.0982 17.316 18.0982 16.684 17.7072 16.293L13.4142 12Z"
                                                                fill="white" />
                                                        </mask>
                                                        <g mask="url(#mask0_135_2594)">
                                                        </g>
                                                    </svg>
                                                </button>
                                            </form>
                                            <span class="self-center">
                                                <svg fill="#ffcf66" height="180" viewBox="0 0 512 512"
                                                    xmlns="http://www.w3.org/2000/svg" stroke="#ffcf66">
                                                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round"
                                                        stroke-linejoin="round"></g>
                                                    <g id="SVGRepo_iconCarrier">
                                                        <title>ionicons-v5-a</title>
                                                        <path
                                                            d="M256,48C141.31,48,48,141.31,48,256s93.31,208,208,208,208-93.31,208-208S370.69,48,256,48Zm0,319.91a20,20,0,1,1,20-20A20,20,0,0,1,256,367.91Zm21.72-201.15-5.74,122a16,16,0,0,1-32,0l-5.74-121.94v-.05a21.74,21.74,0,1,1,43.44,0Z">
                                                        </path>
                                                    </g>
                                                </svg>
                                            </span>

                                            <div class="text-center mt-5" style="width: 350px">
                                                <h5 class="-mb-1">
                                                <!-- {{ date('F d, H:i A') }} |
                                                {{ $quote_data['ciudad_origen'] ?? 'Origen desconocido' }} - 
                                                {{ $quote_data['ciudad_destino'] ?? 'Destino desconocido' }} -->
                                                </h5>
                                                <br>
                                                <p>
                                                    Informamos que la cotización no puede ser elaborada en este momento debido a la insuficiencia de información necesaria para definir un precio adecuado. Por tal motivo, se requiere la creación de una solicitud formal para que el Supervisor correspondiente pueda evaluar las variables implicadas y establecer los parámetros que permitan generar una cotización precisa. Agradecemos su comprensión.
                                                </p>
                                                <a href="{{ route('requests.show') }}"
                                                    class="mt-4 inline-block px-6 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg shadow transition">
                                                    Ir a solicitudes
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            @endif

        @endif

        @if ($showModal4)
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
                .inter-font { font-family: 'Inter', sans-serif; }
                .input-field {
                    @apply border border-gray-200 focus:border-orange-400 focus:bg-white outline-none rounded-lg px-4 py-3 transition-all duration-200 text-xs;
                    background-color: #f9f9f9;
                    padding-left: 0.75rem;
                    padding-right: 0.75rem;
                    padding-top: 0.75rem;
                    padding-bottom: 0.75rem;
                }
                .input-field:focus {
                    box-shadow: 0 0 0 2px rgba(255, 124, 50, 0.1);
                }
                .section-card {
                    @apply bg-white rounded-lg shadow-sm border border-gray-100 p-4 mb-4;
                }
                .label-text {
                    @apply text-gray-600 font-medium text-xs mb-1 block;
                }
                .scrollbar-custom::-webkit-scrollbar {
                    width: 4px;
                }
                .scrollbar-custom::-webkit-scrollbar-track {
                    background: #f1f1f1;
                    border-radius: 2px;
                }
                .scrollbar-custom::-webkit-scrollbar-thumb {
                    background: #c1c1c1;
                    border-radius: 2px;
                }
                .scrollbar-custom::-webkit-scrollbar-thumb:hover {
                    background: #a8a8a8;
                }
                .preview-text {
                    @apply text-xs leading-relaxed;
                }
                /* Estilos personalizados para asegurar proporciones exactas */
                .modal-layout {
                    display: flex;
                    width: 100%;
                    height: 85vh;
                    overflow: hidden;
                }
                .preview-column {
                    width: 40% !important;
                    min-width: 40%;
                    max-width: 40%;
                    flex: 0 0 40%;
                }
                .config-column {
                    width: 60% !important;
                    min-width: 60%;
                    max-width: 60%;
                    flex: 0 0 60%;
                }
            </style>
          
            
            <div class="relative z-20 inter-font" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm"></div>
                <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                    <div class="flex min-h-full w-full items-center justify-center p-3">
                        <div class="relative w-full max-w-7xl transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all">
                            
                            <!-- Header del Modal -->
                            <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4 text-white">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <h2 class="text-lg font-semibold">Cotización - Vista Previa y Envío</h2>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button 
                                            wire:click="saveDraft" 
                                            type="button" 
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-orange-600 bg-white hover:bg-gray-50 transition-colors duration-200 shadow-sm"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Guardar
                                        </button>
                                        
                                    </div>
                                </div>
                            </div>

                            <!-- Contenido Principal - 40/60 layout -->
                            <div class="flex bg-gray-50 overflow-hidden modal-layout">
                                
                                <!-- Columna Izquierda: Vista Previa (40%) -->
                                <div class="preview-column bg-white border-r border-gray-200 overflow-hidden flex flex-col">
                                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex-shrink-0">
                                        <h3 class="text-sm font-semibold text-gray-800 flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 616 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            Vista Previa del Documento
                                        </h3>
                                    </div>
                                    
                                    <div class="p-6 overflow-y-auto scrollbar-custom bg-white">
                                        <!-- Logo y Header -->
                                        <div class="flex justify-end mb-4">
                                            <img src="{{ asset('img/logo-conalca.png') }}" alt="logo-conalca" class="w-24 h-6 object-cover" />
                                        </div>
                                        
                                        <div class="space-y-3 preview-text">
                                            <h1 class="uppercase text-sm font-bold text-gray-800 mb-4">Presentación y cotización conalca</h1>
                                            
                                            <!-- Información del Cliente -->
                                            <div class="space-y-2 bg-gray-50 p-3 rounded-lg">
                                                <div class="grid grid-cols-2 gap-2 text-xs">
                                                    <div>
                                                        <span class="text-gray-500">Estimado(a):</span>
                                                        <span class="font-medium text-gray-800 block">{{ $client_name ?: '[Nombre del cliente]' }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-500">NIT:</span>
                                                        <span class="font-medium text-gray-800 block">{{ $client_document_client ?: '[NIT del cliente]' }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-500">Ubicación:</span>
                                                        <span class="font-medium text-gray-800 block">{{ $client_location ?: '[Ubicación del cliente]' }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-500">Teléfonos:</span>
                                                        <span class="font-medium text-gray-800 block">{{ $client_phone_numbers ?: '[Teléfonos del cliente]' }}</span>
                                                    </div>
                                                </div>
                                                <p class="text-xs text-gray-700 mt-2">
                                                    De parte de <span class="font-medium">{{ $asesor_name_email ?: '[Nombre del asesor]' }}</span> 
                                                    de Conalca (NIT: 900416879), le extendemos un cordial saludo.
                                                </p>
                                            </div>

                                            <!-- Título y Fecha -->
                                            <div class="my-4 p-3 bg-orange-50 rounded-lg border-l-3 border-orange-400">
                                                <h2 class="uppercase font-bold text-orange-800 text-sm mb-1">{{ $title_email ?: '[Título de la cotización]' }}</h2>
                                                <p class="text-xs text-gray-600">{{ date('F d, H:i A') }}</p>
                                            </div>

                                            <!-- Descripción -->
                                            @if($prompt_response)
                                                <div class="my-4 p-3 bg-gray-50 rounded-lg">
                                                    <p class="text-xs text-gray-700 leading-relaxed">{!! nl2br(e($prompt_response)) !!}</p>
                                                </div>
                                            @endif

                                            <!-- Tabla de Rutas Compacta -->
                                            <div class="my-4">
                                                <div class="overflow-x-auto">
                                                    <table class="w-full border-collapse border border-gray-200 rounded-lg overflow-hidden shadow-sm text-xs">
                                                        <thead>
                                                            <tr class="bg-orange-100 text-gray-800 font-semibold">
                                                                <th class="border border-gray-200 px-2 py-2 text-center">#</th>
                                                                <th class="border border-gray-200 px-2 py-2 text-center">Origen</th>
                                                                <th class="border border-gray-200 px-2 py-2 text-center">Destino</th>
                                                                <th class="border border-gray-200 px-2 py-2 text-center">Vehículo</th>
                                                                <th class="border border-gray-200 px-2 py-2 text-center">Valor</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($quote_data as $i => $route)
                                                                <tr class="bg-orange-50 hover:bg-orange-100 transition-colors duration-150">
                                                                    <td class="border border-gray-200 px-2 py-2 text-center font-medium">{{ $i + 1 }}</td>
                                                                    <td class="border border-gray-200 px-2 py-2 text-center">{{ $route['ciudad_origen'] ?? '-' }}</td>
                                                                    <td class="border border-gray-200 px-2 py-2 text-center">{{ $route['ciudad_destino'] ?? '-' }}</td>
                                                                    <td class="border border-gray-200 px-2 py-2 text-center">{{ $route['vehiculo_requerido'] ?? '-' }}</td>
                                                                    <td class="border border-gray-200 px-2 py-2 text-center font-bold text-green-700">
                                                                        ${{ number_format($route['valor_final'] ?? 0) }}

                                                                        @php
                                                                            // Datos del acompañamiento para ESTA ruta
                                                                            $tipo   = $route['tipaco_codigo']                 ?? '';
                                                                            $cant   = intval($route['itesoltra_vehiculoacompanamiento'] ?? 0);
                                                                            $valorU = floatval($route['itesoltra_acompanamientovalor']  ?? 0);
                                                                            $totalA = $cant > 0 ? $valorU * $cant : 0;
                                                                        @endphp

                                                                        @if($tipo && $totalA > 0)
                                                                            <table style="width:100%;margin-top:4px;font-size:10px;border-collapse:collapse;">
                                                                                <thead>
                                                                                    <tr style="background:#fff7ed;">
                                                                                        <th style="border:1px solid #e5e7eb;text-align:center;">N°</th>
                                                                                        <th style="border:1px solid #e5e7eb;text-align:center;">Acompañamiento</th>
                                                                                        <th style="border:1px solid #e5e7eb;text-align:center;">Valor</th>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                    <tr>
                                                                                        <td style="border:1px solid #e5e7eb;text-align:center;">1</td>
                                                                                        <td style="border:1px solid #e5e7eb;text-align:center;">
                                                                                            {{ ucfirst(strtolower($tipo)) }}
                                                                                        </td>
                                                                                        <td style="border:1px solid #e5e7eb;text-align:center;">
                                                                                            ${{ number_format($totalA,0,',','.') }}
                                                                                        </td>
                                                                                    </tr>
                                                                                </tbody>
                                                                            </table>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                
                                                @php
                                                    /*  Total = valor_final (que ya DEBERÍA incluir rentabilidad) 
                                                    +   subtotal_acompanante (por si hubo acompañamiento)        */

                                                    $total = collect($quote_data)->sum(function($ruta){
                                                        $base   = floatval($ruta['valor_final']           ?? 0);   // base + rentab
                                                        $acomp  = floatval($ruta['subtotal_acompanante']  ?? 0);   // puede ser 0
                                                        // Si valor_final ya incluye el acompañante, deja sólo $base
                                                        // Si NO lo incluye, usa $base + $acomp
                                                        return $base;                      // ⟵ cámbialo a $base + $acomp si lo necesitas
                                                    });
                                                @endphp
                                                <div class="mt-3 flex justify-end">
                                                    <div class="bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                                                        <span class="text-sm font-bold text-green-700">Total: ${{ number_format($total) }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Saludo Final -->
                                            <div class="my-4 text-xs text-gray-700 bg-gray-50 p-3 rounded-lg">
                                                {{ $greeting ?: '[Mensaje de saludo personalizable]' }}
                                            </div>

                                            <!-- Información del Asesor -->
                                            <div class="mt-4 pt-3 border-t border-gray-200">
                                                @if (!empty($advisor_signature))
                                                    <div class="mb-3">
                                                        <img src="{{ asset($advisor_signature) }}" alt="Firma del asesor" class="h-8 w-auto object-contain" />
                                                    </div>
                                                @endif
                                                
                                                <div class="text-xs text-gray-700 space-y-1 bg-gray-50 p-3 rounded-lg">
                                                    <p><span class="font-medium">Asesor:</span> {{ $asesor_name_email ?: '[Nombre del asesor]' }}</p>
                                                    <p><span class="font-medium">Tel:</span> {{ $asesor_phone_email ?: '[Teléfono del asesor]' }}</p>
                                                    <p><span class="font-medium">Email:</span> {{ $asesor_email_email ?: '[Email del asesor]' }}</p>
                                                </div>
                                            </div>

                                            @if($acceptOfferDecision)
                                                <div class="mt-4 p-3 bg-blue-50 rounded-lg border-l-3 border-blue-400">
                                                    <p class="text-xs text-blue-800">{{ $acceptOfferDecision }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                    <!-- Columna Derecha: Panel de Configuración (60%) -->
                                    <div class="config-column bg-gray-50 overflow-y-auto scrollbar-custom flex flex-col">
                                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex-shrink-0">
                                            <h3 class="text-xs font-semibold text-gray-800 flex items-center">
                                                <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 616 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                Edición del Documento
                                            </h3>
                                        </div>
                                        <div class="p-6 overflow-y-auto scrollbar-custom bg-white">
                                            
                                            <!-- Información del Cliente -->
                                            <div class="section-card">
                                                <h3 class="text-xs font-semibold text-gray-800 mb-3 flex items-center">
                                                    <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                    </svg>
                                                    Cliente
                                                </h3>
                                                <div class="space-y-2">
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                        <div>
                                                            <label class="label-text text-xs">Nombre</label>
                                                            <input type="text" wire:model.live="client_name" class="input-field w-full text-xs" placeholder="Nombre completo">
                                                        </div>
                                                        <div>
                                                            <label class="label-text text-xs">NIT/Doc</label>
                                                            <input type="text" wire:model.live="client_document_client" class="input-field w-full text-xs" placeholder="NIT o documento">
                                                        </div>
                                                        <div>
                                                            <label class="label-text text-xs">Ubicación</label>
                                                            <input type="text" wire:model.live="client_location" class="input-field w-full text-xs" placeholder="Ciudad/Dirección">
                                                        </div>
                                                        <div>
                                                            <label class="label-text text-xs">Teléfonos</label>
                                                            <input type="text" wire:model.live="client_phone_numbers" class="input-field w-full text-xs" placeholder="Contactos">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Información de la Cotización -->
                                            <div class="section-card">
                                                <h3 class="text-xs font-semibold text-gray-800 mb-3 flex items-center" style="margin-top:30px;">
                                                    <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                    Cotización
                                                </h3>
                                                <div class="space-y-2">
                                                    <div>
                                                        <label class="label-text text-xs">Título</label>
                                                        <input type="text" wire:model.live="title_email" class="input-field w-full text-xs" placeholder="Título de la cotización">
                                                    </div>
                                                    <div>
                                                        <label class="label-text text-xs">Descripción</label>
                                                        <textarea 
                                                            wire:model.live="prompt_response"
                                                            x-data
                                                            x-init="$nextTick(() => { $el.style.height = ''; $el.style.height = $el.scrollHeight + 'px' })"
                                                            class="input-field w-full min-h-[60px] resize-none text-xs"
                                                            rows="3"
                                                            placeholder="Descripción de servicios..."
                                                            oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"
                                                        ></textarea>
                                                    </div>
                                                   
                                                </div>
                                            </div>

                                            <!-- Información del Asesor -->
                                            <div class="section-card">
                                                <h3 class="text-xs font-semibold text-gray-800 mb-3 flex items-center">
                                                    <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                                                    </svg>
                                                    Confirmar correo del cliente para envío
                                                    <span class="text-red-500 ml-1">*</span>
                                                </h3>
                                                <div class="space-y-3">
                                                    
                                                 
                                                    
                                                    <!-- Mostrar correos registrados del cliente -->
                                                    @if(!empty($client_emails_available) && count($client_emails_available) > 0)
                                                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                                            <div class="flex items-center space-x-2 mb-2">
                                                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                </svg>
                                                                <span class="text-xs text-blue-700 font-medium">
                                                                    Correos registrados del cliente ({{ count($client_emails_available) }}):
                                                                </span>
                                                            </div>
                                                            <div class="space-y-2">
                                                                @foreach($client_emails_available as $index => $email)
                                                                    <div class="flex items-center justify-between bg-white p-2 rounded border">
                                                                        <span class="text-xs text-gray-700 font-mono">{{ $email }}</span>
                                                                        <button 
                                                                            type="button"
                                                                            wire:click="selectClientEmail('{{ $email }}')"
                                                                            class="text-xs px-2 py-1 {{ $cliente_email === $email ? 'bg-green-100 text-green-700 border-green-300' : 'bg-blue-100 text-blue-600 hover:bg-blue-200' }} border rounded transition-colors"
                                                                        >
                                                                            {{ $cliente_email === $email ? 'Seleccionado' : 'Usar este' }}
                                                                        </button>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @elseif(!empty($client_email_registered))
                                                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                                            <div class="flex items-center justify-between">
                                                                <div class="flex items-center space-x-2">
                                                                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                    </svg>
                                                                    <span class="text-xs text-blue-700 font-medium">Correo registrado:</span>
                                                                </div>
                                                                <button 
                                                                    type="button"
                                                                    wire:click="selectClientEmail('{{ $client_email_registered }}')"
                                                                    class="text-xs text-blue-600 hover:text-blue-800 underline"
                                                                >
                                                                    Usar este correo
                                                                </button>
                                                            </div>
                                                            <p class="text-xs text-blue-800 font-medium mt-1">{{ $client_email_registered }}</p>
                                                        </div>
                                                    @elseif(!empty($client_name) || !empty($client_document_client))
                                                        <!-- Mostrar mensaje si hay datos del cliente pero no email -->
                                                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                                            <div class="flex items-center space-x-2">
                                                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                                </svg>
                                                                <span class="text-xs text-amber-700 font-medium">Cliente encontrado sin email registrado</span>
                                                            </div>
                                                            <p class="text-xs text-amber-800 mt-1">
                                                                Cliente: {{ $client_name ?? 'N/A' }} ({{ $client_document_client ?? 'N/A' }})
                                                                <br>Debes ingresar manualmente el correo electrónico.
                                                            </p>
                                                        </div>
                                                    @endif
                                                   
                                                    <div>
                                                        <label class="label-text text-xs">
                                                            Email de destino 
                                                            <span class="text-red-500">*</span>
                                                            @if(!empty($client_email_registered))
                                                                <span class="text-gray-500">(puedes modificarlo si es necesario)</span>
                                                            @endif
                                                            @if($cliente_email_manually_set)
                                                                <span class="inline-flex items-center text-xs text-green-600 ml-2">
                                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                                    </svg>
                                                                    Email personalizado
                                                                </span>
                                                            @endif
                                                        </label>
                                                        <input 
                                                            type="email" 
                                                            wire:model.live="cliente_email" 
                                                            class="input-field w-full text-xs 
                                                                @if(empty($cliente_email)) 
                                                                    border-red-300 bg-red-50
                                                                @elseif(filter_var($cliente_email, FILTER_VALIDATE_EMAIL)) 
                                                                    border-green-300 bg-green-50 
                                                                @else 
                                                                    border-orange-300 bg-orange-50
                                                                @endif" 
                                                            placeholder="correo@ejemplo.com"
                                                            required
                                                        >
                                                        @if(empty($cliente_email))
                                                            <p class="text-red-500 text-xs mt-1 flex items-center">
                                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                El correo electrónico es obligatorio para enviar la cotización
                                                            </p>
                                                        @elseif(!filter_var($cliente_email, FILTER_VALIDATE_EMAIL))
                                                            <p class="text-orange-500 text-xs mt-1 flex items-center">
                                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                El formato del correo electrónico no es válido
                                                            </p>
                                                        @else
                                                            <p class="text-green-600 text-xs mt-1 flex items-center">
                                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                La cotización se enviará a: <strong>{{ $cliente_email }}</strong>
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        

                                        <!-- Botón de Envío -->
                                        <div class="p-4 bg-white border-t border-gray-200 space-y-3">
                                            <!-- Botón Principal -->
                                            <button 
                                                wire:click="closeModal(4)"
                                                @if ($active_button || empty($cliente_email) || !filter_var($cliente_email, FILTER_VALIDATE_EMAIL)) disabled @endif
                                                class="w-full py-3 rounded-lg font-semibold text-white transition-all duration-200 text-xs
                                                    {{ ($active_button || empty($cliente_email) || !filter_var($cliente_email, FILTER_VALIDATE_EMAIL)) ? 'bg-gray-400 cursor-not-allowed' : 'bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 shadow-md hover:shadow-lg' }}"
                                            >
                                                <div class="flex items-center justify-center space-x-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                                    </svg>
                                                    <span class="text-xs">
                                                        @if(empty($cliente_email))
                                                            Ingrese el correo para enviar
                                                        @elseif(!filter_var($cliente_email, FILTER_VALIDATE_EMAIL))
                                                            Correo inválido
                                                        @else
                                                            Enviar Cotización
                                                        @endif
                                                    </span>
                                                </div>
                                            </button>
                                            @if(empty($cliente_email))
                                                <p class="text-red-500 text-xs mt-2 text-center">
                                                    <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Debe ingresar el correo del cliente antes de enviar la cotización
                                                </p>
                                            @elseif(!filter_var($cliente_email, FILTER_VALIDATE_EMAIL))
                                                <p class="text-orange-500 text-xs mt-2 text-center">
                                                    <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Por favor, ingrese un correo electrónico válido
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                     </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($showModalSuccess)
            <div class="relative z-20" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                    <div
                        class="flex min-h-full w-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                        <div
                            class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8">
                            <div class="grid grid-cols-1 p-12">
                                <div class="w-full h-full flex flex-col">
                                    <form method="dialog" class="mr-5 self-end">
                                        <button wire:click="closeModalSuccess()">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                    d="M13.4142 12L17.7072 7.70701C18.0982 7.31601 18.0982 6.68401 17.7072 6.29301C17.3162 5.90201 16.6842 5.90201 16.2933 6.29301L12.0002 10.586L7.70725 6.29301C7.31625 5.90201 6.68425 5.90201 6.29325 6.29301C5.90225 6.68401 5.90225 7.31601 6.29325 7.70701L10.5862 12L6.29325 16.293C5.90225 16.684 5.90225 17.316 6.29325 17.707C6.48825 17.902 6.74425 18 7.00025 18C7.25625 18 7.51225 17.902 7.70725 17.707L12.0002 13.414L16.2933 17.707C16.4882 17.902 16.7443 18 17.0002 18C17.2562 18 17.5122 17.902 17.7072 17.707C18.0982 17.316 18.0982 16.684 17.7072 16.293L13.4142 12Z"
                                                    fill="#18203A" />
                                                <mask id="mask0_135_2594" style="mask-type:luminance"
                                                    maskUnits="userSpaceOnUse" x="6" y="5" width="13"
                                                    height="13">
                                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                                        d="M13.4142 12L17.7072 7.70701C18.0982 7.31601 18.0982 6.68401 17.7072 6.29301C17.3162 5.90201 16.6842 5.90201 16.2933 6.29301L12.0002 10.586L7.70725 6.29301C7.31625 5.90201 6.68425 5.90201 6.29325 6.29301C5.90225 6.68401 5.90225 7.31601 6.29325 7.70701L10.5862 12L6.29325 16.293C5.90225 16.684 5.90225 17.316 6.29325 17.707C6.48825 17.902 6.74425 18 7.00025 18C7.25625 18 7.51225 17.902 7.70725 17.707L12.0002 13.414L16.2933 17.707C16.4882 17.902 16.7443 18 17.0002 18C17.2562 18 17.5122 17.902 17.7072 17.707C18.0982 17.316 18.0982 16.684 17.7072 16.293L13.4142 12Z"
                                                        fill="white" />
                                                </mask>
                                                <g mask="url(#mask0_135_2594)">
                                                </g>
                                            </svg>
                                        </button>
                                    </form>
                                    <span class="self-center mt-[2.81rem]">
                                        <svg class="bi bi-patch-check-fill" fill="#04eb84" height="180"
                                            viewBox="0 0 16 16" width="180" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M10.067.87a2.89 2.89 0 0 0-4.134 0l-.622.638-.89-.011a2.89 2.89 0 0 0-2.924 2.924l.01.89-.636.622a2.89 2.89 0 0 0 0 4.134l.637.622-.011.89a2.89 2.89 0 0 0 2.924 2.924l.89-.01.622.636a2.89 2.89 0 0 0 4.134 0l.622-.637.89.011a2.89 2.89 0 0 0 2.924-2.924l-.01-.89.636-.622a2.89 2.89 0 0 0 0-4.134l-.637-.622.011-.89a2.89 2.89 0 0 0-2.924-2.924l-.89.01-.622-.636zm.287 5.984-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7 8.793l2.646-2.647a.5.5 0 0 1 .708.708z" />
                                        </svg>
                                    </span>
                                    <div
                                        class="self-center text-center text-black text-lg font-normal leading-normal mt-11">
                                        <h5 class="-mb-1">Envío de cotización exitoso</h5>
                                        <h5 class="-mb-1">{{ $client_document_client ?? '-' }} | <span
                                                class="font-bold">{{ $client_name }}</span></h5>
                                        <h5 class="-mb-1">
                                            {{ date('F d, H:i A') }} | 
                                            @php
                                                $user = Auth::user();
                                                $roleName = $user->getRoleNames()->first(); // Asumiendo un solo rol por usuario
                                            @endphp
                                        </h5>
                                        <h5 class="-mb-1">Valor $
                                            {{ number_format($selectedPricing->price + ($selectedPricing->price * $porcentaje) / 100) }}
                                        </h5>
                                        <h5>Estado en <span class="text-[#FF7C32] font-bold uppercase">Espera de
                                                aceptación</span></h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </section>
    @if ($save_draft_notification)
        <div 
            x-data="{ visible: @entangle('save_draft_notification').defer !== '' }"
            x-show="visible"
            class="bg-green-100 text-green-800 px-4 py-2 rounded"
        >
            {{ $save_draft_notification }}
        </div>
    @endif
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function listenWhenLivewireIsReady() {
            if (typeof Livewire === 'undefined' || typeof Livewire.dispatch !== 'function') {
                setTimeout(listenWhenLivewireIsReady, 100);
                return;
            }
            window.addEventListener('openChannelModal', function (e) {
                // Lo ves en la consola, lo mandas por dispatch:
                Livewire.dispatch('openModal', { id: e.detail });
            });
        }
        listenWhenLivewireIsReady();
    });
</script>
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('refreshPage', () => window.location.reload());
    });
</script>
<script>
    window.addEventListener('cliente-no-existe', e => {
        // 1. mensaje al usuario (usa alert, sweet-alert, toast, etc.)
        alert(e.detail.mensaje);

        // 2. luego lo llevamos al formulario de creación de contactos
        window.location.href = e.detail.url;
    });
</script>

<!-- Script global para switches de rentabilidad -->
<script>
    // Función principal de toggle para switches
    function toggleRentabilidad(selectedMargen) {
        console.log('=== TOGGLE RENTABILIDAD EJECUTADO ===');
        console.log('Margen seleccionado:', selectedMargen);
        
        const selectedSwitch = document.getElementById('switch-' + selectedMargen);
        
        if (!selectedSwitch) {
            console.error('Switch no encontrado:', 'switch-' + selectedMargen);
            return false;
        }
        
        // Verificar el estado ACTUAL del switch
        const isCurrentlyChecked = selectedSwitch.checked;
        console.log('Estado actual del switch:', isCurrentlyChecked);
        
        if (isCurrentlyChecked) {
            // DESACTIVAR: El switch está activado, lo desactivamos
            console.log('➤ DESACTIVANDO switch');
            
            selectedSwitch.checked = false;
            
            // Restaurar estilos del card
            const card = selectedSwitch.closest('.rentabilidad-card');
            if (card) {
                card.style.borderColor = '#e5e7eb';
                card.style.boxShadow = '';
            }
            
            // Habilitar inputs
            enableAllPorcentajeInputs();
            
            // Resetear en Livewire
            if (window.Livewire) {
                const component = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
                if (component) {
                    console.log('📤 Reseteando porcentaje global');
                    component.resetPorcentajeGlobal();
                    
                    // Forzar limpieza múltiple con diferentes delays
                    setTimeout(() => clearInputValues(), 100);
                    setTimeout(() => clearInputValues(), 300);
                    setTimeout(() => clearInputValues(), 500);
                }
            }
            
        } else {
            // ACTIVAR: El switch está desactivado, lo activamos
            console.log('➤ ACTIVANDO switch');
            
            // Primero desactivar TODOS los otros switches
            document.querySelectorAll('.rentabilidad-switch').forEach(function(otherSwitch) {
                if (otherSwitch !== selectedSwitch) {
                    otherSwitch.checked = false;
                    const otherCard = otherSwitch.closest('.rentabilidad-card');
                    if (otherCard) {
                        otherCard.style.borderColor = '#e5e7eb';
                        otherCard.style.boxShadow = '';
                    }
                }
            });
            
            // Activar el switch seleccionado
            selectedSwitch.checked = true;
            
            // Aplicar estilos al card
            const card = selectedSwitch.closest('.rentabilidad-card');
            if (card) {
                card.style.borderColor = '#fb923c';
                card.style.boxShadow = '0 10px 25px -3px rgba(251, 146, 60, 0.3)';
            }
            
            // Aplicar porcentaje en Livewire PRIMERO
            if (window.Livewire) {
                const component = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
                if (component) {
                    console.log('📤 Aplicando porcentaje global:', selectedMargen);
                    component.aplicarPorcentajeGlobal(selectedMargen);
                    
                    // Forzar actualización múltiple con diferentes delays
                    setTimeout(() => updateInputValues(selectedMargen), 100);
                    setTimeout(() => updateInputValues(selectedMargen), 300);
                    setTimeout(() => updateInputValues(selectedMargen), 500);
                    setTimeout(() => updateInputValues(selectedMargen), 1000);
                    
                    // También forzar sincronización directa
                    setTimeout(() => forceSync(), 1500);
                }
            }
            
            // Luego deshabilitar inputs
            setTimeout(() => {
                disableAllPorcentajeInputs();
            }, 1200);
        }
        
        console.log('Estado final del switch:', selectedSwitch.checked);
        console.log('=== FIN TOGGLE ===');
        return false; // Prevenir comportamiento por defecto
    }

    function disableAllPorcentajeInputs() {
        console.log('🔒 Deshabilitando inputs de porcentaje...');
        const inputs = document.querySelectorAll('input[wire\\:model*="quote_data"][wire\\:model*="porcentaje"]');
        console.log('Inputs encontrados:', inputs.length);
        inputs.forEach(function(input) {
            // Usar readonly en lugar de disabled para que el valor se muestre
            input.readOnly = true;
            input.style.backgroundColor = '#f3f4f6';
            input.style.color = '#9ca3af';
            input.style.cursor = 'not-allowed';
            input.style.opacity = '0.6';
            input.style.border = '1px solid #d1d5db';
            // Agregar clase visual adicional
            input.classList.add('input-disabled-by-switch');
        });
    }

    function enableAllPorcentajeInputs() {
        console.log('🔓 Habilitando inputs de porcentaje...');
        const inputs = document.querySelectorAll('input[wire\\:model*="quote_data"][wire\\:model*="porcentaje"]');
        console.log('Inputs encontrados:', inputs.length);
        inputs.forEach(function(input) {
            input.readOnly = false;
            input.style.backgroundColor = '';
            input.style.color = '';
            input.style.cursor = '';
            input.style.opacity = '';
            input.style.border = '';
            // Remover clase visual
            input.classList.remove('input-disabled-by-switch');
        });
    }

    // Función para forzar actualización de valores en inputs
    function updateInputValues(porcentaje) {
        console.log('🔄 Forzando actualización de inputs con valor:', porcentaje);
        
        const inputs = document.querySelectorAll('input[wire\\:model*="quote_data"][wire\\:model*="porcentaje"]');
        inputs.forEach((input, index) => {
            const oldValue = input.value;
            console.log(`Actualizando input ${index} de "${oldValue}" a "${porcentaje}"`);
            
            // Forzar el valor directamente
            input.value = porcentaje;
            
            // También actualizar el atributo
            input.setAttribute('value', porcentaje);
            
            // Disparar múltiples eventos para asegurar que Livewire detecte
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            input.dispatchEvent(new Event('blur', { bubbles: true }));
            
            // Verificar que el valor se haya aplicado
            setTimeout(() => {
                if (input.value !== porcentaje.toString()) {
                    console.warn(`⚠️ Input ${index} no se actualizó correctamente. Intentando de nuevo...`);
                    input.value = porcentaje;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }, 50);
        });
    }

    // Función para limpiar valores en inputs
    function clearInputValues() {
        console.log('🧹 Limpiando valores de inputs');
        
        const inputs = document.querySelectorAll('input[wire\\:model*="quote_data"][wire\\:model*="porcentaje"]');
        inputs.forEach((input, index) => {
            const oldValue = input.value;
            console.log(`Limpiando input ${index} de "${oldValue}" a ""`);
            
            // Forzar limpieza
            input.value = '';
            
            // También limpiar el atributo
            input.removeAttribute('value');
            
            // Disparar múltiples eventos
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            input.dispatchEvent(new Event('blur', { bubbles: true }));
            
            // Verificar que se haya limpiado
            setTimeout(() => {
                if (input.value !== '') {
                    console.warn(`⚠️ Input ${index} no se limpió correctamente. Intentando de nuevo...`);
                    input.value = '';
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }, 50);
        });
    }

    function onInputPorcentajeChange() {
        // Desactivar todos los switches cuando se modifica un input
        document.querySelectorAll('.rentabilidad-switch').forEach(function(switch_elem) {
            switch_elem.checked = false;
            // Restaurar estilos por defecto
            const card = switch_elem.closest('.rentabilidad-card');
            if (card) {
                card.style.borderColor = '#e5e7eb';
                card.style.boxShadow = '';
            }
        });
        
        // Habilitar todos los inputs
        enableAllPorcentajeInputs();
        
        // Resetear el porcentaje global en Livewire
        if (window.Livewire) {
            const component = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
            if (component) {
                component.resetPorcentajeGlobal();
            }
        }
    }

    // Función de prueba para verificar funcionalidad
    function testSwitchFunctionality() {
        console.log('🧪 PROBANDO FUNCIONALIDAD DE SWITCHES');
        
        // Activar switch del 24%
        console.log('1. Activando switch del 24%');
        toggleRentabilidad(24);
        
        setTimeout(() => {
            console.log('2. Estado después de activar:');
            const inputs = document.querySelectorAll('input[wire\\:model*="quote_data"][wire\\:model*="porcentaje"]');
            inputs.forEach((input, index) => {
                console.log(`Input ${index}: disabled=${input.disabled}, backgroundColor=${input.style.backgroundColor}`);
            });
            
            // Desactivar switch
            console.log('3. Desactivando switch del 24%');
            toggleRentabilidad(24);
            
            setTimeout(() => {
                console.log('4. Estado después de desactivar:');
                inputs.forEach((input, index) => {
                    console.log(`Input ${index}: disabled=${input.disabled}, backgroundColor=${input.style.backgroundColor}`);
                });
            }, 500);
        }, 500);
    }

    // Hacer función disponible globalmente para debugging
    window.testSwitchFunctionality = testSwitchFunctionality;
    
    // Función para probar funcionalidad de restaurar
    function testRestoreFunctionality() {
        console.log('🧪 PROBANDO FUNCIONALIDAD DE RESTAURAR');
        
        const inputs = document.querySelectorAll('input[wire\\:model*="quote_data"][wire\\:model*="porcentaje"]');
        if (inputs.length > 0) {
            const firstInput = inputs[0];
            console.log('1. Ingresando valor 25 en primer input');
            firstInput.value = '25';
            firstInput.dispatchEvent(new Event('input', { bubbles: true }));
            firstInput.dispatchEvent(new Event('change', { bubbles: true }));
            
            setTimeout(() => {
                console.log('2. Buscando botón Restaurar...');
                const restoreButton = document.querySelector('button[wire\\:click*="restaurarPorcentajeGlobal"]');
                if (restoreButton) {
                    console.log('3. Haciendo clic en Restaurar');
                    restoreButton.click();
                    
                    setTimeout(() => {
                        console.log('4. Valor después de restaurar:', firstInput.value);
                    }, 500);
                } else {
                    console.log('❌ No se encontró botón Restaurar');
                }
            }, 1000);
        }
    }
    
    // Función para probar cálculo automático
    function testCalculation() {
        console.log('🧪 PROBANDO CÁLCULO AUTOMÁTICO');
        
        const inputs = document.querySelectorAll('input[wire\\:model*="quote_data"][wire\\:model*="porcentaje"]');
        if (inputs.length > 0) {
            const firstInput = inputs[0];
            console.log('1. Ingresando valor 20% en primer input');
            firstInput.value = '20';
            firstInput.dispatchEvent(new Event('input', { bubbles: true }));
            firstInput.dispatchEvent(new Event('change', { bubbles: true }));
            
            setTimeout(() => {
                const valueCell = firstInput.closest('tr').querySelector('td:last-child span');
                if (valueCell) {
                    console.log('2. Valor calculado mostrado:', valueCell.textContent);
                } else {
                    console.log('❌ No se pudo encontrar celda de valor calculado');
                }
            }, 1000);
        }
    }
    
    window.testRestoreFunctionality = testRestoreFunctionality;
    window.testCalculation = testCalculation;
    
    // Función específica para probar el cálculo con 80%
    function test80PercentCalculation() {
        console.log('🧪 PROBANDO CÁLCULO CON 80%');
        
        const inputs = document.querySelectorAll('input[wire\\:model*="quote_data"][wire\\:model*="porcentaje"]');
        if (inputs.length > 0) {
            const firstInput = inputs[0];
            console.log('1. Limpiando input...');
            firstInput.value = '';
            firstInput.dispatchEvent(new Event('input', { bubbles: true }));
            firstInput.dispatchEvent(new Event('change', { bubbles: true }));
            
            setTimeout(() => {
                console.log('2. Ingresando valor 80%');
                firstInput.value = '80';
                firstInput.dispatchEvent(new Event('input', { bubbles: true }));
                firstInput.dispatchEvent(new Event('change', { bubbles: true }));
                
                setTimeout(() => {
                    const row = firstInput.closest('tr');
                    const costoCell = row.querySelector('td:nth-child(4) span');
                    const valorClienteCell = row.querySelector('td:nth-child(6) span');
                    
                    if (costoCell && valorClienteCell) {
                        const costoBase = costoCell.textContent.replace(/[^0-9]/g, '');
                        const valorFinal = valorClienteCell.textContent.replace(/[^0-9]/g, '');
                        
                        console.log('3. Resultados:');
                        console.log('   - Costo base:', costoBase);
                        console.log('   - Valor con 80%:', valorFinal);
                        console.log('   - Cálculo esperado:', parseInt(costoBase) + (parseInt(costoBase) * 0.8));
                        console.log('   - ¿Coincide?', parseInt(valorFinal) === parseInt(costoBase) + (parseInt(costoBase) * 0.8));
                    }
                }, 1500);
            }, 500);
        }
    }
    
    window.test80PercentCalculation = test80PercentCalculation;
    
    // Función para verificar cálculos manualmente
    function verifyCalculation(basePrice, percentage) {
        const result = basePrice + (basePrice * percentage / 100);
        console.log(`💰 VERIFICACIÓN MANUAL:`);
        console.log(`   Precio base: $${basePrice.toLocaleString()}`);
        console.log(`   Porcentaje: ${percentage}%`);
        console.log(`   Cálculo: ${basePrice} + (${basePrice} * ${percentage} / 100)`);
        console.log(`   Resultado: $${result.toLocaleString()}`);
        return result;
    }
    
    window.verifyCalculation = verifyCalculation;
    
    // Ejemplos predefinidos
    window.testExamples = function() {
        console.log('🔢 EJEMPLOS DE CÁLCULO:');
        verifyCalculation(330000, 80);  // Funza-Bogotá con 80%
        verifyCalculation(1200000, 80); // Medellín-Bogotá con 80%
    };
    
    // Función simple para debugging de inputs
    function debugInputValues() {
        console.log('🔍 DEBUGGING VALORES DE INPUTS:');
        
        const inputs = document.querySelectorAll('input[wire\\:model*="quote_data"][wire\\:model*="porcentaje"]');
        inputs.forEach((input, index) => {
            console.log(`Input ${index}:`, {
                value: input.value,
                wireModel: input.getAttribute('wire:model'),
                disabled: input.disabled,
                classes: input.className
            });
        });
        
        // También verificar el estado en Livewire
        if (window.Livewire) {
            const component = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
            if (component) {
                console.log('📊 Estado en Livewire Component:');
                console.log('quote_data:', component.quote_data);
                console.log('porcentaje_modificado:', component.porcentaje_modificado);
                console.log('errores_porcentaje:', component.errores_porcentaje);
            }
        }
    }
    
    window.debugInputValues = debugInputValues;
    
    // Función para probar directamente desde el servidor
    function testServerSide() {
        console.log('🔧 PROBANDO DESDE EL SERVIDOR...');
        
        if (window.Livewire) {
            const component = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
            if (component) {
                console.log('Llamando testSetPercentage(0, 80)...');
                component.testSetPercentage(0, 80);
                
                setTimeout(() => {
                    console.log('Estado después de la prueba:');
                    console.log('quote_data[0]:', component.quote_data[0]);
                }, 1000);
            }
        }
    }
    
    window.testServerSide = testServerSide;
    
    // Función específica para probar que el valor aparece en los inputs
    function testSwitchValueInInputs() {
        console.log('🧪 PROBANDO QUE EL VALOR DEL SWITCH APAREZCA EN LOS INPUTS');
        
        console.log('1. Estado inicial de inputs:');
        debugInputValues();
        
        console.log('2. Activando switch del 24%...');
        toggleRentabilidad(24);
        
        setTimeout(() => {
            console.log('3. Verificando valores en inputs después de 1 segundo...');
            const inputs = document.querySelectorAll('input[wire\\:model*="quote_data"][wire\\:model*="porcentaje"]');
            inputs.forEach((input, index) => {
                const expected = '24';
                const actual = input.value;
                const isCorrect = actual === expected;
                
                console.log(`Input ${index}:`, {
                    expected: expected,
                    actual: actual,
                    isCorrect: isCorrect ? '✅' : '❌',
                    readOnly: input.readOnly,
                    disabled: input.disabled,
                    hasClass: input.classList.contains('input-disabled-by-switch')
                });
            });
            
            // Verificar estado en Livewire
            if (window.Livewire) {
                const component = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
                if (component) {
                    console.log('4. Estado en Livewire:');
                    console.log('porcentaje_global:', component.porcentaje_global);
                    console.log('quote_data porcentajes:', component.quote_data.map(route => route.porcentaje));
                }
            }
            
            // Probar desactivación
            console.log('5. Desactivando switch...');
            setTimeout(() => {
                toggleRentabilidad(24); // Desactivar
                
                setTimeout(() => {
                    console.log('6. Estado después de desactivar:');
                    debugInputValues();
                }, 1000);
            }, 2000);
        }, 1000);
    }
    
    window.testSwitchValueInInputs = testSwitchValueInInputs;
    
    // Función para forzar sincronización desde Livewire
    function forceSync() {
        console.log('🔧 FORZANDO SINCRONIZACIÓN DESDE LIVEWIRE');
        
        if (window.Livewire) {
            const component = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
            if (component) {
                const quoteData = component.quote_data;
                console.log('📊 Datos desde Livewire:', quoteData);
                
                const inputs = document.querySelectorAll('input[wire\\:model*="quote_data"][wire\\:model*="porcentaje"]');
                inputs.forEach((input, index) => {
                    const livewireValue = quoteData[index]?.porcentaje;
                    const inputValue = input.value;
                    
                    console.log(`Input ${index}:`, {
                        livewireValue: livewireValue,
                        inputValue: inputValue,
                        sync: livewireValue == inputValue ? '✅' : '❌'
                    });
                    
                    // Si no están sincronizados, forzar el valor
                    if (livewireValue != inputValue) {
                        console.log(`🔄 Forzando sincronización input ${index}: ${inputValue} → ${livewireValue}`);
                        input.value = livewireValue || '';
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });
            }
        }
    }
    
    window.forceSync = forceSync;
    
    // Función simple para probar switch del 24%
    function testSwitch24() {
        console.log('🧪 PROBANDO SWITCH 24% - VERSIÓN SIMPLE');
        
        console.log('1. Activando switch 24%...');
        toggleRentabilidad(24);
        
        setTimeout(() => {
            console.log('2. Forzando sincronización...');
            forceSync();
            
            setTimeout(() => {
                console.log('3. Estado final:');
                debugInputValues();
            }, 500);
        }, 2000);
    }
    
    window.testSwitch24 = testSwitch24;

    // Inicializar el estado correcto cuando se carga la página
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM cargado, inicializando estado de switches...');
        setTimeout(function() {
            const activeSwitch = document.querySelector('.rentabilidad-switch:checked');
            if (activeSwitch) {
                console.log('Switch activo encontrado:', activeSwitch.id);
                disableAllPorcentajeInputs();
            } else {
                console.log('No hay switches activos');
                enableAllPorcentajeInputs();
            }
        }, 500);
    });

    // También ejecutar cuando Livewire actualice la página
    document.addEventListener('livewire:updated', function() {
        setTimeout(function() {
            const activeSwitch = document.querySelector('.rentabilidad-switch:checked');
            if (activeSwitch) {
                disableAllPorcentajeInputs();
            } else {
                enableAllPorcentajeInputs();
            }
        }, 100);
    });

    // Función de prueba para debugging
    window.testToggle = function(margen) {
        console.log('Test toggle ejecutado para margen:', margen);
        toggleRentabilidad(margen);
    };
</script>

<!-- Script para manejar alertas -->
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('show-alert', (data) => {
            const type = data[0].type;
            const message = data[0].message;
            
            // Crear y mostrar una alerta
            const alertDiv = document.createElement('div');
            alertDiv.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
                type === 'error' ? 'bg-red-100 border border-red-300 text-red-800' : 
                type === 'success' ? 'bg-green-100 border border-green-300 text-green-800' :
                'bg-blue-100 border border-blue-300 text-blue-800'
            }`;
            
            alertDiv.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        ${type === 'error' ? 
                            '<svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>' :
                            '<svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>'
                        }
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button class="text-gray-400 hover:text-gray-600" onclick="this.parentElement.parentElement.parentElement.remove()">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Auto-remover después de 5 segundos
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        });
    });
</script>


