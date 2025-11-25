<dialog id="successModal" class="modal">
    <div class="w-full md:w-[30.88rem] h-full md:h-[37rem] rounded-[1.125rem] bg-white flex flex-col">
        <form method="dialog" class="mt-[1.125rem] mr-5 self-end">
            <button>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
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
        <span class="self-center mt-[2.81rem]">
            <?xml version="1.0" ?><svg class="bi bi-patch-check-fill" fill="#04eb84" height="180" viewBox="0 0 16 16"
                width="180" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M10.067.87a2.89 2.89 0 0 0-4.134 0l-.622.638-.89-.011a2.89 2.89 0 0 0-2.924 2.924l.01.89-.636.622a2.89 2.89 0 0 0 0 4.134l.637.622-.011.89a2.89 2.89 0 0 0 2.924 2.924l.89-.01.622.636a2.89 2.89 0 0 0 4.134 0l.622-.637.89.011a2.89 2.89 0 0 0 2.924-2.924l-.01-.89.636-.622a2.89 2.89 0 0 0 0-4.134l-.637-.622.011-.89a2.89 2.89 0 0 0-2.924-2.924l-.89.01-.622-.636zm.287 5.984-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7 8.793l2.646-2.647a.5.5 0 0 1 .708.708z" />
            </svg>
        </span>
        <div class="self-center text-center text-black text-lg font-normal leading-normal mt-11">
            <h5 class="-mb-1">Envío de cotización exitoso</h5>
            <h5 class="-mb-1">IDC003 | <span class="font-bold">Nombre Empresa</span></h5>
            <h5 class="-mb-1">{{ date('F d, H:i A') }} | RolAdmin02</h5>
            <h5 class="-mb-1">Valor $5.000.000</h5>
            <h5>Estado en <span class="text-[#FF7C32] font-bold uppercase">Espera de aceptación</span></h5>
        </div>
    </div>
</dialog>
