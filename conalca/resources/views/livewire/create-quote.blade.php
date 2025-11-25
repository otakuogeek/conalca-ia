<div>
    @switch($step)
    @case(0)
    <div class="flex flex-row flex-wrap relative bg-white w-full md:w-[74.8125rem] h-full md:h-[38.5625rem] 2xl:h-[48.5625rem] rounded-[1.125rem] gap-[6.69rem]
        items-center justify-center overflow-auto px-2 md:px-0">
        <form method="dialog">
            <button class="absolute top-[1.69rem] right-[2.13rem]">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M13.4142 12L17.7072 7.70701C18.0982 7.31601 18.0982 6.68401 17.7072 6.29301C17.3162 5.90201 16.6842 5.90201 16.2933 6.29301L12.0002 10.586L7.70725 6.29301C7.31625 5.90201 6.68425 5.90201 6.29325 6.29301C5.90225 6.68401 5.90225 7.31601 6.29325 7.70701L10.5862 12L6.29325 16.293C5.90225 16.684 5.90225 17.316 6.29325 17.707C6.48825 17.902 6.74425 18 7.00025 18C7.25625 18 7.51225 17.902 7.70725 17.707L12.0002 13.414L16.2933 17.707C16.4882 17.902 16.7443 18 17.0002 18C17.2562 18 17.5122 17.902 17.7072 17.707C18.0982 17.316 18.0982 16.684 17.7072 16.293L13.4142 12Z"
                        fill="#18203A" />
                    <mask id="mask0_135_2594" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="6" y="5"
                        width="13" height="13">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M13.4142 12L17.7072 7.70701C18.0982 7.31601 18.0982 6.68401 17.7072 6.29301C17.3162 5.90201 16.6842 5.90201 16.2933 6.29301L12.0002 10.586L7.70725 6.29301C7.31625 5.90201 6.68425 5.90201 6.29325 6.29301C5.90225 6.68401 5.90225 7.31601 6.29325 7.70701L10.5862 12L6.29325 16.293C5.90225 16.684 5.90225 17.316 6.29325 17.707C6.48825 17.902 6.74425 18 7.00025 18C7.25625 18 7.51225 17.902 7.70725 17.707L12.0002 13.414L16.2933 17.707C16.4882 17.902 16.7443 18 17.0002 18C17.2562 18 17.5122 17.902 17.7072 17.707C18.0982 17.316 18.0982 16.684 17.7072 16.293L13.4142 12Z"
                            fill="white" />
                    </mask>
                    <g mask="url(#mask0_135_2594)">
                    </g>
                </svg>
            </button>
        </form>
        <img class="w-auto md:w-[27.5625rem] h-[23.9375rem] object-cover" src="{{ asset('img/quotes_logo.gif') }}" alt="conalca-ai-logo">
        <form wire:submit='submitClient'>
            <div class="flex flex-col justify-start">
                <h1 class="w-full md:w-[30.625rem] text-[#FF7C32] text-[2.625rem] font-bold leading-normal">
                    HOLA!,
                    HOY TE AYUDARE A
                    CREAR TU COTIZACIÓN
                </h1>
                <h6 class="text-[#898989] text-sm font-normal leading-normal mt-[2.44rem]">Buscar la empresa en tus
                    registros u omite esta opción.</h6>
                <div class="relative w-full md:w-[21.5rem] h-[3.625rem]">
                    @csrf
                    <input type="search" 
                           id="company-search" 
                           wire:model='search' 
                           placeholder="Buscar empresa" 
                           name="search_company" 
                           autocomplete="off"
                           class="w-full md:w-[21.5rem] h-[3.625rem] rounded-lg bg-white border border-solid border-[#dcdcdc] mt-1 pl-3 pr-8 text-[#898989] text-xl font-normal leading-normal" 
                           onkeyup="console.log('🔤 Tecla presionada:', this.value)" />
                    
                    <!-- Dropdown de resultados -->
                    <div id="search-results" 
                         class="absolute top-full left-0 w-full bg-white border border-gray-300 rounded-lg shadow-lg z-50 max-h-60 overflow-y-auto hidden">
                    </div>
                    
                    <!-- Loading indicator -->
                    <div id="search-loading" 
                         class="absolute top-full left-0 w-full bg-white border border-gray-300 rounded-lg shadow-lg z-50 p-3 hidden">
                        <div class="flex items-center justify-center">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-orange-500"></div>
                            <span class="ml-2 text-gray-600">Buscando...</span>
                        </div>
                    </div>
                    
                    @error('search')
                    <p class="text-red-500">
                        {{ $message }}
                    </p>
                    @enderror
                </div>
                <h6 class="text-[#898989] text-sm font-normal leading-normal mt-[1.06rem]">Seleciona el tipo de cliente
                    que
                    va ser dirigida tu cotización</h6>
                <select name="client_type" id="client_type" class="w-full md:w-[21.5rem] h-[3.625rem] rounded-lg bg-white border border-solid border-[#dcdcdc] mt-1
                    text-[#898989] text-xl font-normal leading-normal px-3">
                    <option value="" disabled>Seleccione el tipo de cliente</option>
                    <option value="credit">Crédito</option>
                    <option value="cash">Contado</option>
                </select>

                <button type="submit" class="w-full md:w-[21.5rem] h-[3.625rem] rounded-lg bg-[#FF7C32] text-white text-xl font-medium leading-normal
                    mt-[2.81rem] mb-4 md:mb-0">Crear
                    cotización</button>
            </div>
        </form>
    </div>
    @break

    @case(1)
    <div class="flex flex-row flex-wrap bg-[#F3F2F2] w-full md:w-[87.68rem] h-full md:h-[44.12rem] 2xl:h-[52.12rem]
    items-center justify-center overflow-auto mx-2 md:mx-0 rounded-[0.9375rem] shadow-md relative">
        <form method="dialog">
            <button class="absolute top-[1.69rem] right-[2.13rem]">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M13.4142 12L17.7072 7.70701C18.0982 7.31601 18.0982 6.68401 17.7072 6.29301C17.3162 5.90201 16.6842 5.90201 16.2933 6.29301L12.0002 10.586L7.70725 6.29301C7.31625 5.90201 6.68425 5.90201 6.29325 6.29301C5.90225 6.68401 5.90225 7.31601 6.29325 7.70701L10.5862 12L6.29325 16.293C5.90225 16.684 5.90225 17.316 6.29325 17.707C6.48825 17.902 6.74425 18 7.00025 18C7.25625 18 7.51225 17.902 7.70725 17.707L12.0002 13.414L16.2933 17.707C16.4882 17.902 16.7443 18 17.0002 18C17.2562 18 17.5122 17.902 17.7072 17.707C18.0982 17.316 18.0982 16.684 17.7072 16.293L13.4142 12Z"
                        fill="#18203A" />
                    <mask id="mask0_135_2594" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="6" y="5"
                        width="13" height="13">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M13.4142 12L17.7072 7.70701C18.0982 7.31601 18.0982 6.68401 17.7072 6.29301C17.3162 5.90201 16.6842 5.90201 16.2933 6.29301L12.0002 10.586L7.70725 6.29301C7.31625 5.90201 6.68425 5.90201 6.29325 6.29301C5.90225 6.68401 5.90225 7.31601 6.29325 7.70701L10.5862 12L6.29325 16.293C5.90225 16.684 5.90225 17.316 6.29325 17.707C6.48825 17.902 6.74425 18 7.00025 18C7.25625 18 7.51225 17.902 7.70725 17.707L12.0002 13.414L16.2933 17.707C16.4882 17.902 16.7443 18 17.0002 18C17.2562 18 17.5122 17.902 17.7072 17.707C18.0982 17.316 18.0982 16.684 17.7072 16.293L13.4142 12Z"
                            fill="white" />
                    </mask>
                    <g mask="url(#mask0_135_2594)">
                    </g>
                </svg>
            </button>
        </form>
        <div class="w-full md:w-[37.875rem] h-full md:h-[36.187rem] 2xl:h-[46.187rem] rounded-[0.94rem] bg-[#F3F2F2] shadow-xl flex flex-col
    mt-[3.31rem] ml-3 md:ml-[4.13rem] mb-3 md:mb-[2.62rem] md:p-0 md:pt-8 md:pl-[2.62rem] md:pr-[2.31rem] md:pb-4">
            <div class="flex flex-row flex-wrap items-start justify-start md:justify-between gap-3 md:gap-0">
                <div class="flex flex-col justify-start text-black text-base font-normal leading-normal">
                    <h5 class="font-norma">Creación de cotización</h5>
                    <h5 class="font-normal">{{ $search }} | <span class="font-bold uppercase">{{ $client_name }}</span>
                    </h5>
                    <h5 class="font-bold uppercase">Tipo cliente activo</h5>
                    <h5 class="font-norma">{{ date('F d, H:i A') }} | RolAdmin02</h5>
                </div>
                <select name="quote_type" id="quote_type" class="w-[8.94rem] h-[2.63rem] rounded-lg
            bg-white border border-solid border-[#dcdcdc]">
                    <option value="etiqueta" disabled>Etiqueta</option>
                </select>
            </div>
            <h5 class="text-[#00000081] text-base font-normal leading-normal mt-[3.19rem] mb-[0.44rem]">Validando
                información
                ingresada...</h5>
            <form class="flex flex-col text-black text-sm font-normal leading-normal">
                <label for="origin">Origen: {{ $quote_data['ciudad_origen'] ?? '-' }}</label>
                <label for="destination">Destino: {{ $quote_data['ciudad_destino'] ?? '-' }}</label>
                <label for="value">Valor del Flete: {{ $quote_data['valor_flete'] ?? '-' }}</label>
                <label for="peso_mercancia">Peso de la mercancía: {{ $quote_data['peso_mercancia'] ?? '0' }} kg</label>
                <label for="value">Fecha cargue: {{ $quote_data['fecha_cargue'] ?? '-' }}</label>
                <label for="value">Fecha tentativa descargue: {{ $quote_data['fecha_descargue'] ?? '-' }}</label>
                <label for="value">Producto: {{ $quote_data['producto'] ?? '-' }}</label>
                <label for="value">Empaque: {{ $quote_data['empaque'] ?? '-' }}</label>
                <label for="value">Cantidad de vehiculos: {{ $quote_data['cantidad_vehiculos'] ?? '-' }}</label>
                <label for="value">Clase de vehiculos: {{ $quote_data['clase_vehiculo'] ?? '-' }}</label>
                <label for="value">Carroceria: {{ $quote_data['carroceria'] ?? '-' }}</label>
                <label for="value">Modelo minimo: {{ $quote_data['minimo_modelo'] ?? '-' }}</label>
                <label for="value">Tipo flete: {{ $quote_data['fecha_descargue'] ?? '-' }}</label>
                <label for="value">Conductor: {{ $quote_data['conductor'] ?? '-' }}</label>
                <label for="value">Flete ministerio: {{ $quote_data['flete_ministerio'] ?? '-' }}</label>
                <label for="value">Tipo de tarifa: {{ $quote_data['tipo_de_tarifa'] ?? '-' }}</label>
                <label for="value">Tarifa de cliente: {{ $quote_data['tarifa_cliente'] ?? '-' }}</label>
                <label for="value">Valor mercancia: {{ $quote_data['valor_mercancia'] ?? '-' }}</label>
                <label for="value">Descripcion de la mercancia: {{ $quote_data['descripcion_mercancia'] ?? '-' }}</label>
                <label for="value">Sub-cliente: {{ $quote_data['sub_cliente'] ?? '-' }}</label>
                <label for="value">Acompañante: {{ $quote_data['partner'] ?? '-' }}</label>
                <label for="value">Tipo de viaje: {{ $quote_data['tipo_viaje'] ?? '-' }}</label>
            </form>
        </div>
        <div class="">
            <div wire:poll.5s.visible id="conversation"
                class="w-full md:w-[40.775rem] h-full md:h-[31.5rem] 2xl:h-[40.187rem] bg-white flex flex-col mt-3 md:mt-[3.31rem] ml-3 md:ml-[4.13rem]
        mb-3 md:mb-4 md:p-0 md:pt-8 md:pl-[2.62rem] md:pr-[1.53rem]  gap-6 font-normal leading-normal text-black relative overflow-y-auto scrollbar-default">
                {{-- destination message --}}

                @foreach ($messages as $message)
                <div
                    class="flex flex-col pr-[1.52rem] pt-[0.52rem] pb-[1.52rem] pl-4 rounded-tl-[0.94rem] rounded-tr-[0.94rem] rounded-br-[0.94rem]
            rounded-bl-none shadow-md mr-5 {{ $message['role'] == 'user' ? 'self-end justify-end bg-[#F0F0F0] ml-12' : 'self-start justify-start bg-white' }}">
                    <span class="text-[0.5rem] text-right">{{ $message['created_at'] }}</span>
                    <h6 class="text-sm text-right">
                        {{ $message['text'] }}
                    </h6>
                </div>
                @endforeach

            </div>
            <div class="md:pb-4 md:pl-12">
                <form class=" " wire:submit='sendMessage'>
                    @csrf
                    <input type="text" placeholder="Escribe tu mensaje" name="message" wire:model='input_message'
                        class="w-full h-8 rounded-lg bg-white mb-1" />
                    <button>
                        <svg width="10" height="19" viewBox="0 0 10 19" fill="none" xmlns="http://www.w3.org/2000/svg"
                            xmlns:xlink="http://www.w3.org/1999/xlink">
                            <rect width="9.07445" height="19" fill="url(#pattern0)" fill-opacity="0.5" />
                            <defs>
                                <pattern id="pattern0" patternContentUnits="objectBoundingBox" width="1" height="1">
                                    <use xlink:href="#image0_12_608"
                                        transform="matrix(0.0163577 0 0 0.0078125 -0.546896 0)" />
                                </pattern>
                                <image id="image0_12_608" width="128" height="128"
                                    xlink:href="data:image/png;base64,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" />
                            </defs>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
        @isset($quote_data['ciudad_origen'])
            <button onclick="quotes_modal_p3.showModal(); quotes_modal_p2.close()" wire:click="saveCotizacion()" class="w-full md:w-[16.58rem] h-[2.69rem] rounded-lg bg-[#FF7C32] text-center relative sm:mb-4 md:mb-0 md:absolute md:bottom-[1.06rem] md:right-[12.67rem]
            text-white text-sm font-medium leading-normal">Crear
            cotización</button>
        @endisset


    </div>
    @break

    @default
    @endswitch

    {{-- <script>
        https://youtu.be/fGg5_4E8lu8?t=654
        window.addEventListener('openModal', event => {
            quotes_modal_p2.showModal();
        });
    </script> --}}

    <style>
        /* Estilos adicionales para empresas inactivas */
        .company-inactive {
            position: relative;
        }
        
        .company-inactive::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: repeating-linear-gradient(
                45deg,
                rgba(239, 68, 68, 0.1),
                rgba(239, 68, 68, 0.1) 10px,
                transparent 10px,
                transparent 20px
            );
            pointer-events: none;
            border-radius: 0.375rem;
        }
        
        .company-active:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .pulse-red {
            animation: pulse-red 2s infinite;
        }

        @keyframes pulse-red {
            0%, 100% { border-color: #ef4444; }
            50% { border-color: #dc2626; }
        }
    </style>

    <script>
        function setupCompanySearch() {
            console.log('🔍 Configurando búsqueda de empresas (versión simple)...');
            
            let retryCount = 0;
            const maxRetries = 10;
            
            function trySetup() {
                retryCount++;
                console.log(`📝 Intento ${retryCount}/${maxRetries} de configurar búsqueda...`);
                
                // Buscar elementos de múltiples formas
                const searchInput = document.getElementById('company-search') || 
                                 document.querySelector('#company-search') ||
                                 document.querySelector('input[id="company-search"]');
                
                const resultsDiv = document.getElementById('search-results') ||
                                 document.querySelector('#search-results') ||
                                 document.querySelector('.search-results');
                
                if (!searchInput) {
                    console.warn(`⚠️ Campo de búsqueda no encontrado en intento ${retryCount}`);
                    if (retryCount < maxRetries) {
                        setTimeout(trySetup, 500);
                        return;
                    } else {
                        console.error('❌ No se pudo encontrar el campo de búsqueda después de todos los intentos');
                        return;
                    }
                }
                
                if (!resultsDiv) {
                    console.warn(`⚠️ Div de resultados no encontrado en intento ${retryCount}`);
                    if (retryCount < maxRetries) {
                        setTimeout(trySetup, 500);
                        return;
                    } else {
                        console.error('❌ No se pudo encontrar el div de resultados después de todos los intentos');
                        return;
                    }
                }
                
                console.log('✅ Elementos encontrados, configurando eventos...');
                
                let searchTimeout;
                
                // Configurar evento de búsqueda
                searchInput.addEventListener('input', function() {
                    const query = this.value.trim();
                    console.log('🔤 Texto ingresado:', query);
                    
                    clearTimeout(searchTimeout);
                    
                    if (query.length < 2) {
                        resultsDiv.style.display = 'none';
                        return;
                    }
                    
                    searchTimeout = setTimeout(() => {
                        searchCompanies(query, resultsDiv);
                    }, 300);
                });
                
                // Ocultar resultados al perder foco
                searchInput.addEventListener('blur', function() {
                    setTimeout(() => {
                        resultsDiv.style.display = 'none';
                    }, 150);
                });
                
                console.log('🎉 Búsqueda de empresas configurada exitosamente!');
            }
            
            trySetup();
        }
        
        function searchCompanies(query, resultsDiv) {
            console.log('🔍 Buscando:', query);
            
            fetch(`/api/search/companies?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(companies => {
                    console.log('📊 Empresas encontradas:', companies.length);
                    
                    if (companies.length === 0) {
                        resultsDiv.innerHTML = '<div class="p-3 text-gray-500">No se encontraron empresas</div>';
                    } else {
                        resultsDiv.innerHTML = companies.map(company => {
                            const isInactive = company.estado === 'INACTIVO';
                            const bgClass = isInactive ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-green-50';
                            const textClass = isInactive ? 'text-red-700' : 'text-gray-800';
                            const statusText = isInactive ? '🔒 INACTIVA' : '✅ ACTIVA';
                            const statusClass = isInactive ? 'text-red-500' : 'text-green-600';
                            
                            return `
                                <div class="p-3 cursor-pointer border-b ${bgClass}" onclick="selectCompany('${company.name}', '${company.estado}')">
                                    <div class="font-semibold ${textClass}">
                                        ${company.name} 
                                        <span class="${statusClass} text-sm font-normal">(${statusText})</span>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        Doc: ${company.documento || 'N/A'} | Tel: ${company.telefono || 'N/A'}
                                    </div>
                                    ${isInactive ? '<div class="text-xs text-red-600 mt-1">⚠️ Empresa inactiva - Contacte al administrador</div>' : ''}
                                </div>
                            `;
                        }).join('');
                    }
                    
                    resultsDiv.style.display = 'block';
                })
                .catch(error => {
                    console.error('❌ Error:', error);
                    resultsDiv.innerHTML = '<div class="p-3 text-red-500">Error al buscar empresas</div>';
                    resultsDiv.style.display = 'block';
                });
        }
        
        function selectCompany(name, estado) {
            console.log('🏢 Empresa seleccionada:', name, estado);
            
            const searchInput = document.getElementById('company-search');
            if (searchInput) {
                searchInput.value = name;
                
                if (estado === 'INACTIVO') {
                    alert('⚠️ EMPRESA INACTIVA\n\nEsta empresa no puede gestionar órdenes hasta estar activa.\nContacte al administrador para activar la empresa.');
                }
            }
            
            const resultsDiv = document.getElementById('search-results');
            if (resultsDiv) {
                resultsDiv.style.display = 'none';
            }
        }
                    <div class="bg-white rounded-lg p-6 max-w-md mx-4 shadow-xl">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.732 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-red-700">Empresa Inactiva</h3>
                        </div>
                        <div class="mb-4">
                            <p class="text-gray-700 mb-2">
                                <strong>${company.name}</strong> está marcada como <span class="text-red-600 font-semibold">INACTIVA</span>.
                            </p>
                            <p class="text-gray-600 text-sm mb-3">
                                Las empresas inactivas no pueden gestionar nuevas órdenes o cotizaciones.
                            </p>
                            <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                                <p class="text-red-700 text-sm">
                                    <strong>¿Qué hacer?</strong><br>
                                    • Contacte al administrador del sistema<br>
        
        // Inicializar cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', setupCompanySearch);
        
        // Reinicializar después de actualizaciones de Livewire
        document.addEventListener('livewire:load', function() {
            setTimeout(setupCompanySearch, 100);
        });
        
        document.addEventListener('livewire:initialized', function() {
            setTimeout(setupCompanySearch, 100);
        });
        
        // Ejecutar inmediatamente también
        setupCompanySearch();
    </script>

</div>
