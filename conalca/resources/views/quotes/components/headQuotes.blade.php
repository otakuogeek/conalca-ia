<div class="flex flex-row flex-wrap w-full items-center justify-center gap-3 md:gap-0 md:justify-between">
    <h3 class="text-[#202020] text-[1.9375rem] font-semibold leading-[2.875rem]">Cotización</h3>
    <div
        class="flex flex-row items-center text-[#84818A] w-[25rem] h-10 rounded-lg bg-white px-[0.56rem]
        z-20 relative">
        <div class="flex flex-row items-center justify-between w-60 shadow-sm">
            <div class="flex flex-col justify-start">
                <span class="text-[0.6875rem] font-medium leading-tight">Valor facturado</span>
                <span class="text-sm font-medium leading-tight">$200.000.000 COP</span>
            </div>
            <div class="relative h-10 flex items-end shadow-custom1 rounded-r-lg bg-white">
                <div class="flex flex-row items-center gap-1 absolute top-0 left-3 pointer-events-none z-10">
                    <span class="text-[0.6875rem] font-medium leading-tight">Mes</span>
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M7 10L12 15L17 10H7Z" fill="#FF7C32" />
                        </svg>
                    </span>
                </div>
                <select class="bg-transparent appearance-none">
                    <option>Setiembre</option>
                    <option>Octubre</option>
                    <option>Noviembre</option>
                </select>
            </div>
        </div>
        <div class="w-40 h-10 rounded-lg flex flex-col items-center justify-center shadow-sm relative">
            <span class="bg-[#04D000] h-full w-[50%] absolute -left-3 -z-10"></span>
            <span class="text-[#077D04] text-[0.5625rem] font-bold leading-5 -mt-[1.2rem]">!Ya casi cumples la
                meta¡</span>
            <span class="text-[#565656] text-[0.5625rem] font-bold leading-5 -mb-2">La meta del mes facturado</span>
            <span class="text-[#565656] text-xs font-bold leading-5">$400.000.000 COP</span>
        </div>
    </div>
    <button onclick="quotes_modal_p1.showModal()"
        class="text-white w-[10.5rem] h-10 rounded-lg bg-[#FF7C32] inline-flex items-center font-bold leading-normal gap-2 justify-center">
        <span class="text-[1.375rem]">+</span>
        <span class="text-sm">Crear cotización</span></button>
    <form class="inline-flex relative items-center">
        <span class="absolute left-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M15.5 14H14.71L14.43 13.73C15.41 12.59 16 11.11 16 9.5C16 5.91 13.09 3 9.5 3C5.91 3 3 5.91 3 9.5C3 13.09 5.91 16 9.5 16C11.11 16 12.59 15.41 13.73 14.43L14 14.71V15.5L19 20.49L20.49 19L15.5 14ZM9.5 14C7.01 14 5 11.99 5 9.5C5 7.01 7.01 5 9.5 5C11.99 5 14 7.01 14 9.5C14 11.99 11.99 14 9.5 14Z"
                    fill="#84818A" />
            </svg>
        </span>
        <label for="search-quote">
            <input id="search-quote" name="search-quote" type="search" placeholder="Buscar..."
                class="w-60 h-10 rounded-lg bg-white border border-solid border-[#DCDCDC] pl-[1.9rem] pr-1 text-[#84818A] text-sm
                font-medium leading-5" />
        </label>
    </form>
    <div class="inline-flex relative w-[11.875rem] h-10 rounded-lg border border-solid border-[#DCDCDC] bg-white">
        <span
            class="text-[#898989] text-sm font-normal leading-normal absolute left-3 pointer-events-none inset-y-0 pr-6
            flex items-center">Filtrar:</span>
        <select name="filter-quote" id="filter-quote" class="h-full w-full appearance-none bg-transparent pl-14">
            <option value="accepted">Aceptada</option>
            <option value="pending">Pendiente</option>
        </select>
        <span class="absolute inset-y-0 right-3 flex items-center pr-3 pointer-events-none">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="17" viewBox="0 0 16 17" fill="none">
                <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M10.1166 7.33442C10.2071 7.25701 10.332 7.23377 10.4443 7.27345C10.5566 7.31312 10.6392 7.40969 10.661 7.52678C10.6828 7.64386 10.6404 7.76368 10.5499 7.84108L8.21661 9.84108C8.09187 9.94778 7.90802 9.94778 7.78327 9.84108L5.44994 7.84108C5.31003 7.72142 5.29361 7.511 5.41327 7.37108C5.53294 7.23117 5.74336 7.21476 5.88327 7.33442L7.99994 9.14842L10.1166 7.33508V7.33442Z"
                    fill="#404040" stroke="black" />
            </svg>
        </span>
    </div>
</div>
