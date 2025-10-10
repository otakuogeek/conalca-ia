<div class="flex flex-row flex-wrap items-center justify-center md:justify-start gap-3 md:gap-0 mb-10">
    <h2 class="text-[#171725] text-2xl font-semibold leading-9 tracking-[0.00625rem] mr-[2%]">Documentos de Clientes</h2>
    
    <div class="flex flex-row text-base leading-normal tracking-[0.00556rem] mr-[3%] gap-1">
        <span class="text-[#92929D] font-normal">Total:</span>
        <span class="text-[#44444F] font-medium">{{ $clients->total() }} clientes</span>
    </div>

    {{-- Search Input --}}
    <div class="flex relative items-center">
        <span class="absolute left-3 z-10">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M15.5 14H14.71L14.43 13.73C15.41 12.59 16 11.11 16 9.5C16 5.91 13.09 3 9.5 3C5.91 3 3 5.91 3 9.5C3 13.09 5.91 16 9.5 16C11.11 16 12.59 15.41 13.73 14.43L14 14.71V15.5L19 20.49L20.49 19L15.5 14ZM9.5 14C7.01 14 5 11.99 5 9.5C5 7.01 7.01 5 9.5 5C11.99 5 14 7.01 14 9.5C14 11.99 11.99 14 9.5 14Z"
                    fill="#fb923c" />
            </svg>
        </span>
        <input type="search" name="search" id="searchInput" placeholder="Buscar clientes..." 
            class="w-64 h-10 rounded-lg border border-solid border-orange-300 focus:ring-orange-400 focus:border-orange-400 focus:z-10 pl-10 pr-4 text-sm" />
    </div>
</div>
