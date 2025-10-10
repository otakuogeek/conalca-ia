<div class="flex flex-row flex-wrap items-center justify-center md:justify-start gap-3 md:gap-0 mb-10">
    <h2 class="text-[#171725] text-2xl font-semibold leading-9 tracking-[0.00625rem] mr-[1%]">Todos los
        clientes</h2>
    <div class="dropdown">
        <label tabindex="0"
            class="btn m-1 w-[6.5rem] h-[2.37rem] bg-white rounded flex flex-row text-[#171725] text-sm font-medium leading-normal tracking-[0.00625rem]">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="12" viewBox="0 0 18 14" fill="none">
                <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M1 2H17C17.5523 2 18 1.55228 18 1C18 0.447715 17.5523 0 17 0H1C0.447715 0 0 0.447715 0 1C0 1.55228 0.447715 2 1 2ZM6 14H12C12.5523 14 13 13.5523 13 13C13 12.4477 12.5523 12 12 12H6C5.44772 12 5 12.4477 5 13C5 13.5523 5.44772 14 6 14ZM14 8H4C3.44772 8 3 7.55228 3 7C3 6.44772 3.44772 6 4 6H14C14.5523 6 15 6.44772 15 7C15 7.55228 14.5523 8 14 8Z"
                    fill="#696974" />
            </svg>
            Filtro</label>
        <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
            <li><a>Todos los clientes</a></li>
            <li><a>Clientes asignados</a></li>
        </ul>
    </div>
    <button onclick="openCreateClientModal()"
        class="flex flex-row items-center w-[9.125rem] h-[2.37rem] rounded-[0.625rem]  text-white text-sm font-semibold leading-normal
            tracking-[0.00625rem] gap-2 bg-[#82c43c] justify-center ml-[2%]">
        <span>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 18 18" fill="none">
                <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M10 8H17C17.5523 8 18 8.44771 18 9C18 9.55229 17.5523 10 17 10H10V17C10 17.5523 9.55229 18 9 18C8.44771 18 8 17.5523 8 17V10H1C0.447715 10 0 9.55229 0 9C0 8.44771 0.447715 8 1 8H8V1C8 0.447715 8.44771 0 9 0C9.55229 0 10 0.447715 10 1V8Z"
                    fill="#FAFAFB" />
            </svg>
        </span>
        Agregar
    </button>
    <div class="flex flex-row text-base leading-normal tracking-[0.00556rem] ml-[2%] gap-1">
        <span class="text-[#92929D] font-normal">Total:</span>
        <span class="text-[#44444F] font-medium">{{ $clients->total() }} clientes</span>
    </div>
    <div class="flex relative items-center ml-[3%]">
        <span class="absolute left-1 z-10">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M15.5 14H14.71L14.43 13.73C15.41 12.59 16 11.11 16 9.5C16 5.91 13.09 3 9.5 3C5.91 3 3 5.91 3 9.5C3 13.09 5.91 16 9.5 16C11.11 16 12.59 15.41 13.73 14.43L14 14.71V15.5L19 20.49L20.49 19L15.5 14ZM9.5 14C7.01 14 5 11.99 5 9.5C5 7.01 7.01 5 9.5 5C11.99 5 14 7.01 14 9.5C14 11.99 11.99 14 9.5 14Z"
                    fill="#84818A" />
            </svg>
        </span>
        <input type="search" name="search" id="searchInput" placeholder="Buscar..." 
            class="w-60 h-10 rounded-lg border border-solid border-[#DCDCDC] focus:ring-[#DCDCDC] focus:border-[#DCDCDC] focus:z-10 pl-8" />
    </div>
</div>
