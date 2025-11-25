@push('styles')
    <link href="{{ asset('vendor/dropzone/dropzone.css') }}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
@endpush

<dialog id="meetingModal" class="modal">
    <div
        class="w-full md:w-[74.82rem] h-full 2xl:h-[49.5rem] rounded-[1.125rem] bg-[#f9f9f9] flex flex-col overflow-y-scroll scrollbar-default">
        <form method="dialog" class="self-end mt-3 mr-3">
            <button>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M13.4142 12L17.7072 7.70701C18.0982 7.31601 18.0982 6.68401 17.7072 6.29301C17.3162 5.90201 16.6842 5.90201 16.2933 6.29301L12.0002 10.586L7.70725 6.29301C7.31625 5.90201 6.68425 5.90201 6.29325 6.29301C5.90225 6.68401 5.90225 7.31601 6.29325 7.70701L10.5862 12L6.29325 16.293C5.90225 16.684 5.90225 17.316 6.29325 17.707C6.48825 17.902 6.74425 18 7.00025 18C7.25625 18 7.51225 17.902 7.70725 17.707L12.0002 13.414L16.2933 17.707C16.4882 17.902 16.7443 18 17.0002 18C17.2562 18 17.5122 17.902 17.7072 17.707C18.0982 17.316 18.0982 16.684 17.7072 16.293L13.4142 12Z"
                        fill="#18203A" />
                    <mask id="mask0_135_2594" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="6"
                        y="5" width="13" height="13">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M13.4142 12L17.7072 7.70701C18.0982 7.31601 18.0982 6.68401 17.7072 6.29301C17.3162 5.90201 16.6842 5.90201 16.2933 6.29301L12.0002 10.586L7.70725 6.29301C7.31625 5.90201 6.68425 5.90201 6.29325 6.29301C5.90225 6.68401 5.90225 7.31601 6.29325 7.70701L10.5862 12L6.29325 16.293C5.90225 16.684 5.90225 17.316 6.29325 17.707C6.48825 17.902 6.74425 18 7.00025 18C7.25625 18 7.51225 17.902 7.70725 17.707L12.0002 13.414L16.2933 17.707C16.4882 17.902 16.7443 18 17.0002 18C17.2562 18 17.5122 17.902 17.7072 17.707C18.0982 17.316 18.0982 16.684 17.7072 16.293L13.4142 12Z"
                            fill="white" />
                    </mask>
                    <g mask="url(#mask0_135_2594)">
                    </g>
                </svg>
            </button>
        </form>
        <div class="overflow-x-hidden scrollbar-default w-full md:pl-4 h-[2.13rem] flex flex-row items-center">
            <span class="relative">
                <span class="absolute left-16 top-1 text-[#20202080] text-base font-medium leading-normal">Primer
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
                        d="M2.58072 8.70132C-0.803281 5.62794 1.37096 0 5.9423 0H221.258C222.426 0 223.558 0.40951 224.457 1.15737L238.493 12.8432C240.858 14.8118 240.9 18.4286 238.583 20.4522L224.479 32.7664C223.568 33.5617 222.4 34 221.191 34H5.49873C0.985345 34 -1.2179 28.4925 2.05019 25.3796L7.37997 20.3029C9.48261 18.3001 9.44263 14.9334 7.29302 12.9811L2.58072 8.70132Z"
                        fill="#D9D9D9" />
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
        <div class="flex flex-col md:flex-row items-start justify-center gap-7 mt-5 md:mt-[4.25rem]">
            <form class="flex flex-col justify-center gap-4">
                @csrf
                <div
                    class="bg-white w-full md:w-[29.5rem] min-h-[15.5rem] rounded-[1.25rem] shadow-md flex flex-col items-start justify-start
                px-[2.06rem] pt-[1.31rem] pb-[1.44rem] text-[#898989] font-normal leading-[1.03125rem]">
                    <span class="w-full text-[0.625rem] -mb-[0.15rem]">Datos empresa</span>
                    <h4 class="text-[#202020] text-[1.44rem] font-bold uppercase">Empresa...</h4>
                    <div
                        class="flex flex-row flex-wrap items-start justify-between w-full mt-[1.38rem] text-[#898989] font-normal leading-[1.03125rem]">
                        <div class="flex flex-col items-start justify-start">
                            <label for="nit" class="w-full text-[0.625rem]">
                                Nit</label>
                            <input type="text" placeholder="000000000-0" name="nit"
                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem]" />
                            <label for="sector" class="w-full text-[0.625rem]">
                                Sector económico</label>
                            <input type="text" placeholder="Agroindustria" name="sector"
                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem]" />
                            <label for="address" class="w-full text-[0.625rem]">
                                Dirección</label>
                            <input type="text" placeholder="Cra 9 # 0 -0" name="address"
                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem]" />
                            <label for="email" class="w-full text-[0.625rem]">
                                Correo</label>
                            <input type="email" placeholder="correo@correo.com" name="email"
                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] md:mb-0" />
                        </div>
                        <div class="flex flex-col items-start justify-start">
                            <label for="employee_type" class="w-full text-[0.625rem]">
                                Cargo</label>
                            <input type="text" placeholder="Gerente" name="employee_type"
                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem]" />
                            <label for="contact_name" class="w-full text-[0.625rem]">
                                Nombre persona contacto</label>
                            <input type="text" placeholder="Nombre persona contacto" name="contact_name"
                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem]" />
                            <label for="phone" class="w-full text-[0.625rem]">
                                Teléfono</label>
                            <input type="tel" placeholder="Número de teléfono" name="phone"
                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem]" />
                            <label for="city" class="w-full text-[0.625rem]">
                                Ciudad</label>
                            <input type="text" placeholder="Bogota" name="city"
                                class="text-[0.94rem] -mt-[0.15rem]" />
                        </div>
                    </div>
                </div>
                {{-- second div --}}
                <div
                    class="bg-white w-full md:w-[29.5rem] min-h-[20.19rem] rounded-[1.25rem] shadow-md flex flex-col items-start justify-start
                px-[2.06rem] pt-[1.31rem] pb-[1.44rem] text-[#898989] font-normal leading-[1.03125rem]">
                    <span class="w-full text-[0.625rem] -mb-[0.15rem]">Módulo</span>
                    <h4 class="text-[#202020] text-[1.44rem] font-bold uppercase">Módulo primer contacto</h4>
                    <div
                        class="flex flex-row flex-wrap items-start justify-between w-full mt-[1.38rem] text-[#898989] font-normal leading-[1.03125rem]">
                        <div class="flex flex-col items-start justify-start">
                            <label for="nit" class="w-full text-[0.625rem]">
                                Persona de contacto</label>
                            <input type="first_contact" placeholder="Sebastian Correa" name="first_contact"
                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem]" />
                            <label for="contract_title" class="w-full text-[0.625rem]">
                                Título del contrato</label>
                            <input type="text" placeholder="T0-212-123" name="contract_title"
                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem]" />
                            <label for="address-2" class="w-full text-[0.625rem]">
                                Dirección</label>
                            <input type="text" placeholder="Cra 9 # 0 -0" name="address-2"
                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem]" />
                            <label for="email" class="w-full text-[0.625rem]">
                                Correo</label>
                            <input type="email-2" placeholder="correo@correo.com" name="email-2"
                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] md:mb-0" />
                        </div>
                        <div class="flex flex-col items-start justify-start">
                            <label for="company" class="w-full text-[0.625rem]">
                                Empresa</label>
                            <input type="text" placeholder="Delorean studios" name="company"
                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem]" />
                            <label for="projected_value" class="w-full text-[0.625rem]">
                                Valor proyectado</label>
                            <input type="text" placeholder="$3.000.000COP" name="projected_value"
                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem]" />
                            <label for="date" class="w-full text-[0.625rem]">
                                Fecha prevista de ejecución</label>
                            <input type="date" id="date" name="date"
                                class="text-[#898989] text-[0.9375rem] font-normal leading-[1.03125rem] mb-[0.44rem] w-48" />
                            <label for="city-2" class="w-full text-[0.625rem]">
                                Ciudad</label>
                            <input type="text" placeholder="Bogota" name="city-2"
                                class="text-[0.94rem] -mt-[0.15rem]" />
                            <button type="submit"
                                class="w-[8.32rem] h-[1.57rem] rounded-lg bg-[#FF7C32] text-center text-white text-sm font-medium
                            leading-normal mt-[2.19rem] self-end">
                                Guardar</button>
                        </div>
                    </div>
                </div>
            </form>
            {{-- second form --}}
            <form
                class="w-full md:w-[37.07rem] min-h-[35.07rem] rounded-[1.25rem] bg-white shadow-md pt-[2.38rem] pl-[2.63rem] pr-[1.81rem]
            pb-5 flex flex-col relative">
                <span
                    class="absolute -top-4 -right-4 w-[3.82rem] h-[3.82rem] rounded-full bg-[#FF7C32] shadow-md text-white
            text-[3.44rem] font-extrabold leading-normal flex items-center justify-center">
                    +</span>
                @csrf
                <div class="flex flex-row flex-wrap items-center justify-between w-full">
                    <div class="flex flex-col justify-start">
                        <span
                            class="text-[0.625rem] text-[#898989] font-normal leading-[1.03125rem] -mb-1">Módulo</span>
                        <h4 class="text-[#202020] text-[1.44rem] font-bold leading-normal uppercase">Reunión</h4>
                        <span
                            class="text-[0.82rem] text-black font-normal leading-normal -mt-2">{{ date('F d, H:i A') }}
                            | RolAdmin02</span>
                    </div>
                    <h4 class="text-[#202020] text-[1.44rem] font-bold leading-normal uppercase mr-1">Nombre empresa
                    </h4>
                </div>
                <div class="flex flex-row flex-wrap items-start justify-between w-full mt-[1.38rem]">
                    <div
                        class="flex flex-row flex-wrap items-start justify-between w-full md:w-[70%] text-[#898989] font-normal leading-[1.03125rem]">
                        <div class="flex flex-col items-start justify-start">
                            <label for="nit" class="w-full text-[0.625rem]">
                                Persona de contacto</label>
                            <input type="first_contact" placeholder="Sebastian Correa" name="first_contact"
                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem]" />
                            <label for="contract_title" class="w-full text-[0.625rem]">
                                Título del contrato</label>
                            <input type="text" placeholder="T0-212-123" name="contract_title"
                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem]" />
                            <label for="address-2" class="w-full text-[0.625rem]">
                                Dirección</label>
                            <input type="text" placeholder="Cra 9 # 0 -0" name="address-2"
                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem]" />
                            <label for="email" class="w-full text-[0.625rem]">
                                Correo</label>
                            <input type="email-2" placeholder="correo@correo.com" name="email-2"
                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem] md:mb-0" />
                        </div>
                        <div class="flex flex-col items-start justify-start">
                            <label for="projected_value" class="w-full text-[0.625rem]">
                                Valor proyectado</label>
                            <input type="text" placeholder="$3.000.000COP" name="projected_value"
                                class="text-[0.94rem] -mt-[0.15rem] mb-[0.44rem]" />
                            <label for="date" class="w-full text-[0.625rem]">
                                Fecha prevista de ejecución</label>
                            <input type="date" id="date" name="date"
                                class="text-[#898989] text-[0.9375rem] font-normal leading-[1.03125rem] mb-[0.44rem] w-48" />
                            <label for="city-2" class="w-full text-[0.625rem]">
                                Ciudad</label>
                            <input type="text" placeholder="Bogota" name="city-2"
                                class="text-[0.94rem] -mt-[0.15rem]" />
                        </div>
                    </div>
                    {{-- select --}}
                    <div class="flex flex-col justify-start ml-4 mr-2 md:-mt-4">
                        <label for="status"
                            class="w-full text-[0.625rem] text-[#898989] font-normal leading-[1.03125rem]">
                            Estado</label>
                        <select name="status" id="status"
                            class="bg-[#FF7C32] w-[6.63rem] h-[1.44rem] rounded-lg text-white text-center
                        text-[0.53rem] font-medium leading-normal -mt-[0.15rem]">
                            <option value="">Selecciona estado</option>
                        </select>
                    </div>
                </div>
                <h5 class="text-[#898989] text-[0.94rem] font-bold leading-[1.03125rem] mt-4 md:mt-[1.81rem]">
                    Anotación de la reunión</h5>
                <textarea name="annotations" id="annotations" rows="10"
                    class="py-[0.87rem] px-[0.94rem] w-full h-[10.82rem] rounded-xl
                bg-[#f2f2f2] text-[0.69rem] text-[#898989] font-normal leading-[1.03125rem] resize-none mt-[0.31rem]"
                    placeholder="Escribir...">
                </textarea>
                <button type="submit"
                    class="w-[14.32rem] h-[2.69rem] rounded-lg bg-[#73DD70] text-center text-white text-sm
                font-medium leading-normal mt-[1.06rem] self-center">
                    Guardar</button>
            </form>
        </div>
    </div>
</dialog>
