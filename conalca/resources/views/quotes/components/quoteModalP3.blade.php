@push('scripts')
    <script>
        function selectedProposal(checkboxId, divId) {
            const checkbox = document.getElementById(checkboxId);
            const div = document.getElementById(divId);
            const spans = Array.from(div.getElementsByTagName('span'));

            checkbox.addEventListener('change', function() {
                if (checkbox.checked) {
                    // If the checkbox is checked, change the div styles
                    div.style.boxShadow =
                        '-3px 6px 20px -2px #FF7C32';
                    div.style.height = '21.13rem';
                    spans.forEach(function(span) {
                        span.style.color =
                            '#FF7C32';
                    });
                } else {
                    // If the checkbox is unchecked, restore the div styles
                    div.style.boxShadow =
                        '-4px 4px 8px 0px rgba(0, 0, 0, 0.07)';
                    div.style.height = '20.25rem';
                    spans.forEach(function(span) {
                        span.style.color =
                            '';
                    });
                }
            });
        };
        selectedProposal('min-toggle', 'min-div');
        selectedProposal('prom-toggle', 'prom-div');
        selectedProposal('most-toggle', 'most-div')
    </script>
@endpush

<dialog id="quotes_modal_p3" class="modal md:py-4">
    <div
        class="flex flex-col w-full md:w-[74.82rem] h-full rounded-[1.125rem] bg-[#f9f9f9] md:pt-[2.62rem] md:pl-[3.62rem] md:pr-[2.44rem]
    pt-[1.89rem] pl-[1.33rem] pr-[1.33rem] pb-[0.74rem] overflow-auto scrollbar-default relative">
        <button
            class="absolute right-[2.31rem] top-[1.37rem] w-[11.19rem] h-[1.69rem] rounded-[0.625rem] bg-[#d9d9d9] text-center">Guardar
            Borrador y Salir</button>
        <div
            class="w-full md:w-[66.44rem] h-full rounded-[0.94rem] bg-[#f0f0f0] shadow-lg flex flex-col overflow-auto scrollbar-default">
            <div
                class="flex flex-col md:flex-row mt-[1.33rem] w-full items-center justify-center md:justify-between px-3 md:px-14 gap-3 md:gap-0">
                <div class="flex flex-col justify-start text-black text-base font-normal leading-normal">
                    <h5 class="font-norma">Creación de cotización</h5>
                    <h5 class="font-normal">IDC003 | <span class="font-bold uppercase">Empresa uno</span></h5>
                    <h5 class="font-bold uppercase">Tipo cliente activo</h5>
                    <h5 class="font-norma">{{ date('F d, H:i A') }} | RolAdmin02</h5>
                </div>
                <div
                    class="flex items-center justify-start w-full md:w-[35.62rem] min-h-[7.94rem] gap-[2.99rem] bg-white pl-[1.92rem] pt-[1.31rem] pb-[0.87rem] pr-[1.31rem]
                rounded-xl shadow">
                    <span class="bg-[#FF7C32] w-[2.79rem] h-[1.44rem] rounded-xl"></span>
                    <div class="flex flex-col text-[#898989] text-xs font-normal leading-normal">
                        <span class="text-sm text-black">ID003 | <span class="font-bold">cotización</span></span>
                        <span class="w-full md:w-[23.63rem] h-full">
                            Lorem ipsum es el texto que se usa habitualmente en diseño gráfico en demostraciones de
                            tipografías o de borradores de diseño para probar el diseño visual antes de insertar el
                            texto final.
                        </span>
                        <span>{{ date('F d, H:i A') }}</span>
                    </div>
                </div>
            </div>
            <div class="flex flex-col md:flex-row md:items-end justify-center gap-2 mt-4 md:mt-[5.52rem]">
                <div
                    class="overflow-x-auto pt-[0.81rem] pl-[0.81rem] pr-[0.63rem] pb-[0.63rem] w-full md:w-[30rem] bg-white rounded-[0.3125rem] mt-3 md:mt-0">
                    <table class="table-xs w-full">
                        {{-- head --}}
                        <thead>
                            <tr class="text-[#202020] text-sm font-bold leading-normal border-0 uppercase h-[1.2rem]">
                                <th class="align-middle md:text-start border-0">Origen</th>
                                <th class="align-middle md:text-start border-0">Destino</th>
                                <th class="align-middle md:text-center border-0">Costo</th>
                                <th class="align-middle md:text-center border-0">Rentabilidad</th>
                            </tr>
                        </thead>
                        {{-- body --}}
                        <tbody>
                            {{-- row 1 --}}
                            <tr class="border-0 text-[#898989] text-sm font-normal leading-normal h-[1.7rem]">
                                <td class="align-middle border-0">
                                    <span>Bogota DC</span>
                                </td>
                                <td class="align-middle border-0">
                                    <span>Barranquilla</span>
                                </td>
                                <td class="align-middle border-0 text-center">
                                    <span>$5.000.000</span>
                                </td>
                                <td class="align-middle border-0">
                                    <div
                                        class="flex flex-row w-[6.63rem] h-[1.63] items-center justify-end gap-[0.94rem]
                                    bg-[#ececec] rounded-[0.32rem] pr-[0.44rem] mx-auto">
                                        <span>17%</span>
                                        <button>
                                            <?xml version="1.0" ?><svg class="feather feather-edit" fill="none"
                                                height="12" stroke="#898989" stroke-linecap="round"
                                                stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24"
                                                width="12" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            {{-- end row 1 --}}
                            {{-- row 2 --}}
                            <tr class="border-0 text-[#898989] text-sm font-normal leading-normal h-[1.7rem]">
                                <td class="align-middle border-0">
                                    <span>Bogota DC</span>
                                </td>
                                <td class="align-middle border-0">
                                    <span>Barranquilla</span>
                                </td>
                                <td class="align-middle border-0 text-center">
                                    <span>$5.000.000</span>
                                </td>
                                <td class="align-middle border-0">
                                    <div
                                        class="flex flex-row w-[6.63rem] h-[1.63] items-center justify-end gap-[0.94rem]
                                    bg-[#ececec] rounded-[0.32rem] pr-[0.44rem] mx-auto">
                                        <span>17%</span>
                                        <button>
                                            <?xml version="1.0" ?><svg class="feather feather-edit" fill="none"
                                                height="12" stroke="#898989" stroke-linecap="round"
                                                stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24"
                                                width="12" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            {{-- end row 2 --}}
                            {{-- row 3 --}}
                            <tr class="border-0 text-[#898989] text-sm font-normal leading-normal h-[1.7rem]">
                                <td class="align-middle border-0">
                                    <span>Bogota DC</span>
                                </td>
                                <td class="align-middle border-0">
                                    <span>Barranquilla</span>
                                </td>
                                <td class="align-middle border-0 text-center">
                                    <span>$5.000.000</span>
                                </td>
                                <td class="align-middle border-0">
                                    <div
                                        class="flex flex-row w-[6.63rem] h-[1.63] items-center justify-end gap-[0.94rem]
                                    bg-[#ececec] rounded-[0.32rem] pr-[0.44rem] mx-auto">
                                        <span>17%</span>
                                        <button>
                                            <?xml version="1.0" ?><svg class="feather feather-edit" fill="none"
                                                height="12" stroke="#898989" stroke-linecap="round"
                                                stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24"
                                                width="12" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            {{-- end row 3 --}}
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-row flex-wrap gap-[1.06rem] md:items-end justify-center">
                    {{-- item 1 --}}
                    <div id="min-div"
                        class="flex flex-col w-[9.82rem] h-[20.25rem] rounded bg-white justify-center items-center shadow-custom2">
                        <label class="text-[#202020] text-xs font-bold leading-normal -mb-2">Rentabilidad</label>
                        <h5 class="text-[#202020] text-[1.07rem] font-bold leading-normal">Miníma</h5>
                        <h4 class="text-[#202020] text-3xl font-bold leading-normal mt-[2%]">17%</h4>
                        <label class="text-[#898989] text-[0.5rem] font-normal leading-normal mt-[1%]">Seleccione
                            propuesta</label>
                        <input type="checkbox" class="toggle toggle-success mt-[0.31rem]" id="min-toggle" />
                        <hr class="border border-dotted border-[#979797] w-[7.82rem] mt-[1.69rem]">
                        <span class="text-[#202020] text-sm font-bold leading-normal mt-[1.31rem] uppercase">Costo
                            final</span>
                        <span class="text-[#898989] text-sm font-normal leading-normal mt-[0.69rem]">$5.800.000</span>
                        <span class="text-[#898989] text-sm font-normal leading-normal mt-[0.69rem]">$5.800.000</span>
                        <span class="text-[#898989] text-sm font-normal leading-normal mt-[0.69rem]">$5.800.000</span>
                    </div>
                    {{-- end item 1 --}}
                    {{-- item 2 --}}
                    <div id="prom-div"
                        class="flex flex-col w-[9.82rem] h-[20.25rem] rounded bg-white justify-center items-center shadow-custom2">
                        <label class="text-[#202020] text-xs font-bold leading-normal -mb-2">Rentabilidad</label>
                        <h5 class="text-[#202020] text-[1.07rem] font-bold leading-normal">Promedio</h5>
                        <h4 class="text-[#202020] text-3xl font-bold leading-normal mt-[2%]">24%</h4>
                        <label class="text-[#898989] text-[0.5rem] font-normal leading-normal mt-[1%]">Seleccione
                            propuesta</label>
                        <input type="checkbox" class="toggle toggle-success mt-[0.31rem]" id="prom-toggle" />
                        <hr class="border border-dotted border-[#979797] w-[7.82rem] mt-[1.69rem]">
                        <span class="text-[#202020] text-sm font-bold leading-normal mt-[1.31rem] uppercase">Costo
                            final</span>
                        <span class="text-[#898989] text-sm font-normal leading-normal mt-[0.69rem]">$5.800.000</span>
                        <span class="text-[#898989] text-sm font-normal leading-normal mt-[0.69rem]">$5.800.000</span>
                        <span class="text-[#898989] text-sm font-normal leading-normal mt-[0.69rem]">$5.800.000</span>
                    </div>
                    {{-- end item 2 --}}
                    {{-- item 3 --}}
                    <div id="most-div"
                        class="flex flex-col w-[9.82rem] h-[20.25rem] rounded bg-white justify-center items-center shadow-custom2">
                        <label class="text-[#202020] text-xs font-bold leading-normal -mb-2">Rentabilidad</label>
                        <h5 class="text-[#202020] text-[1.07rem] font-bold leading-normal">Mayor vendida</h5>
                        <h4 class="text-[#202020] text-3xl font-bold leading-normal mt-[2%]">32%</h4>
                        <label class="text-[#898989] text-[0.5rem] font-normal leading-normal mt-[1%]">Seleccione
                            propuesta</label>
                        <input type="checkbox" class="toggle toggle-success mt-[0.31rem]" id="most-toggle" />
                        <hr class="border border-dotted border-[#979797] w-[7.82rem] mt-[1.69rem]">
                        <span class="text-[#202020] text-sm font-bold leading-normal mt-[1.31rem] uppercase">Costo
                            final</span>
                        <span class="text-[#898989] text-sm font-normal leading-normal mt-[0.69rem]">$5.800.000</span>
                        <span class="text-[#898989] text-sm font-normal leading-normal mt-[0.69rem]">$5.800.000</span>
                        <span class="text-[#898989] text-sm font-normal leading-normal mt-[0.69rem]">$5.800.000</span>
                    </div>
                    {{-- end item 3 --}}
                </div>
            </div>
            <span
                class="text-[#202020] text-sm font-bold leading-normal mt-3 text-left uppercase md:ml-[2.06rem]">Agregar
                Acompañamiento</span>
            <form
                class="flex flex-row flex-wrap gap-4 items-center justify-center md:justify-start md:-mt-1 text-[#898989] text-sm font-normal leading-normal
                md:ml-[2.06rem] md:mb-3">
                @csrf
                <div class="flex flex-col w-full md:w-[12.44rem] justify-start">
                    <label for="vehicle">#Vehículos Acompañamiento</label>
                    <input type="text" name="vehicle" placeholder="Cantidad de vehículos"
                        class="w-full h-[1.82rem] rounded-[0.32rem] bg-white px-3" />
                </div>
                <div class="flex flex-col w-full md:w-[12.44rem] justify-start">
                    <label for="accompaniment_type">Tipo Acompañamiento</label>
                    <select name="accompaniment_type" class="w-full h-[1.82rem] text-xs">
                        <option value="#">Seleccione tipo</option>
                    </select>
                </div>
                <div class="flex flex-col w-full md:w-[12.44rem] justify-start">
                    <label for="accompaniment_by">Tipo Acompañamiento</label>
                    <select name="accompaniment_by" class="w-full h-[1.82rem] text-xs">
                        <option value="#">Seleccione tipo</option>
                    </select>
                </div>
                <div class="flex flex-col w-full md:w-[12.44rem] justify-start">
                    <label for="accompaniment_value">Valor Acompañamiento</label>
                    <input type="text" name="accompaniment_value" placeholder="Valor del acompañamiento"
                        class="w-full h-[1.82rem] rounded-[0.32rem] bg-white px-3" />
                </div>
                <button type="submit" class="md:mt-6 mb-3 md:mb-0">
                    <svg class="feather feather-plus-circle" fill="none" height="26" stroke="#898989"
                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24"
                        width="26" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" x2="12" y1="8" y2="16" />
                        <line x1="8" x2="16" y1="12" y2="12" />
                    </svg>
                </button>
            </form>
        </div>
        <button onclick="quotes_modal_p4.showModal(); quotes_modal_p3.close()"
            class="w-[14.32rem] h-[2.81rem] rounded-lg bg-[#FF7C32] text-white text-center text-sm font-medium leading-normal mx-auto
            mt-4">
            Continuar</button>
    </div>
</dialog>
