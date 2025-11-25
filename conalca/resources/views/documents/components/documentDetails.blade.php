<div class="bg-gradient-to-r from-gray-50 to-white rounded-xl p-6 mx-[1.38rem] mt-[1.88rem] mb-8 shadow-sm border border-gray-100">
    <span class="text-[#6B7280] text-[0.75rem] font-medium leading-[1.03125rem] -mb-2 uppercase tracking-wider">Información del Cliente</span>
    <h4 id="client_company" class="text-[#1F2937] text-[1.8rem] font-bold leading-normal mb-6">Selecciona un cliente</h4>

    {{-- data from DB --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
            <div class="bg-white rounded-lg p-4 border border-gray-100">
                <label for="client_nit" class="text-[#6B7280] text-[0.75rem] font-semibold leading-[1.03125rem] mb-1 block uppercase tracking-wide">NIT</label>
                <span id="client_nit" class="text-[#1F2937] text-[1rem] font-medium leading-[1.2rem] block">-</span>
            </div>
            
            <div class="bg-white rounded-lg p-4 border border-gray-100">
                <label for="client_sector" class="text-[#6B7280] text-[0.75rem] font-semibold leading-[1.03125rem] mb-1 block uppercase tracking-wide">Sector Económico</label>
                <span id="client_sector" class="text-[#1F2937] text-[1rem] font-medium leading-[1.2rem] block">-</span>
            </div>
            
            <div class="bg-white rounded-lg p-4 border border-gray-100">
                <label for="client_address" class="text-[#6B7280] text-[0.75rem] font-semibold leading-[1.03125rem] mb-1 block uppercase tracking-wide">Dirección</label>
                <span id="client_address" class="text-[#1F2937] text-[1rem] font-medium leading-[1.2rem] block">-</span>
            </div>
            
            <div class="bg-white rounded-lg p-4 border border-gray-100">
                <label for="client_email" class="text-[#6B7280] text-[0.75rem] font-semibold leading-[1.03125rem] mb-1 block uppercase tracking-wide">Correo</label>
                <span id="client_email" class="text-[#1F2937] text-[1rem] font-medium leading-[1.2rem] block">-</span>
            </div>
        </div>
        
        <div class="space-y-4">
            <div class="bg-white rounded-lg p-4 border border-gray-100">
                <label for="client_position" class="text-[#6B7280] text-[0.75rem] font-semibold leading-[1.03125rem] mb-1 block uppercase tracking-wide">Cargo</label>
                <span id="client_position" class="text-[#1F2937] text-[1rem] font-medium leading-[1.2rem] block">-</span>
            </div>
            
            <div class="bg-white rounded-lg p-4 border border-gray-100">
                <label for="client_contact" class="text-[#6B7280] text-[0.75rem] font-semibold leading-[1.03125rem] mb-1 block uppercase tracking-wide">Persona de Contacto</label>
                <span id="client_contact" class="text-[#1F2937] text-[1rem] font-medium leading-[1.2rem] block">-</span>
            </div>
            
            <div class="bg-white rounded-lg p-4 border border-gray-100">
                <label for="client_phone" class="text-[#6B7280] text-[0.75rem] font-semibold leading-[1.03125rem] mb-1 block uppercase tracking-wide">Teléfono</label>
                <span id="client_phone" class="text-[#1F2937] text-[1rem] font-medium leading-[1.2rem] block">-</span>
            </div>
            
            <div class="bg-white rounded-lg p-4 border border-gray-100">
                <label for="client_city" class="text-[#6B7280] text-[0.75rem] font-semibold leading-[1.03125rem] mb-1 block uppercase tracking-wide">Ciudad</label>
                <span id="client_city" class="text-[#1F2937] text-[1rem] font-medium leading-[1.2rem] block">-</span>
            </div>
        </div>
    </div>
</div>

{{-- Documentos Generales --}}
<div class="document-section bg-gradient-to-br from-orange-50 via-white to-orange-100 rounded-2xl p-8 mx-[1.38rem] mt-[1.94rem] mb-8 transition-all duration-300 hover:shadow-lg border border-orange-200">
    <div class="flex flex-row flex-wrap gap-4 md:gap-0 items-start md:items-center justify-between mb-8">
        <div class="flex flex-col items-start justify-start">
            <div class="flex items-center mb-2">
                <div class="w-3 h-3 bg-orange-500 rounded-full mr-2 animate-pulse"></div>
                <span class="text-orange-600 text-[0.75rem] font-bold leading-[1.03125rem] uppercase tracking-wider">Documentos Generales</span>
            </div>
            <h4 class="text-orange-900 text-[1.625rem] font-bold leading-normal flex items-center">
                <svg class="w-6 h-6 mr-3 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                DOCUMENTOS CORPORATIVOS
            </h4>
            <p class="text-orange-700 text-sm mt-1">Documentos oficiales y corporativos del cliente</p>
        </div>
        <button onclick="openUploadModal('general')" class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white px-8 py-4 rounded-2xl text-sm font-bold transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 hover:scale-105 mt-4 md:mt-0 border border-orange-400">
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Subir Documentos
        </button>
    </div>
    <div id="general-documents" class="min-h-[180px] bg-gradient-to-br from-white to-orange-50 rounded-2xl p-6 shadow-inner border-2 border-dashed border-orange-200 hover:border-orange-300 transition-all duration-300">
        <div class="text-gray-500 text-center py-12 w-full flex flex-col items-center">
            <div class="bg-orange-100 rounded-full p-6 mb-4 animate-bounce">
                <svg class="w-12 h-12 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <span class="text-lg font-medium text-gray-600">Selecciona un cliente para ver sus documentos</span>
            <span class="text-sm text-gray-400 mt-2">Los documentos aparecerán aquí una vez selecciones un cliente</span>
        </div>
    </div>
</div>

{{-- Facturas --}}
<div class="document-section bg-gradient-to-br from-blue-50 via-white to-blue-100 rounded-2xl p-8 mx-[1.38rem] mt-8 mb-8 transition-all duration-300 hover:shadow-lg border border-blue-200">
    <div class="flex flex-row flex-wrap gap-4 md:gap-0 items-start md:items-center justify-between mb-8">
        <div class="flex flex-col items-start justify-start">
            <div class="flex items-center mb-2">
                <div class="w-3 h-3 bg-blue-500 rounded-full mr-2 animate-pulse"></div>
                <span class="text-blue-600 text-[0.75rem] font-bold leading-[1.03125rem] uppercase tracking-wider">Documentos Financieros</span>
            </div>
            <h4 class="text-blue-900 text-[1.625rem] font-bold leading-normal flex items-center">
                <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                FACTURAS Y COBROS
            </h4>
            <p class="text-blue-700 text-sm mt-1">Facturas, recibos y documentos de facturación</p>
        </div>
        <button onclick="openUploadModal('facturas')" class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-8 py-4 rounded-2xl text-sm font-bold transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 hover:scale-105 mt-4 md:mt-0 border border-blue-400">
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Subir Facturas
        </button>
    </div>
    <div id="facturas-documents" class="min-h-[180px] bg-gradient-to-br from-white to-blue-50 rounded-2xl p-6 shadow-inner border-2 border-dashed border-blue-200 hover:border-blue-300 transition-all duration-300">
        <div class="text-gray-500 text-center py-12 w-full flex flex-col items-center">
            <div class="bg-blue-100 rounded-full p-6 mb-4 animate-bounce">
                <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <span class="text-lg font-medium text-gray-600">Selecciona un cliente para ver sus facturas</span>
            <span class="text-sm text-gray-400 mt-2">Las facturas aparecerán aquí una vez selecciones un cliente</span>
        </div>
    </div>
</div>

{{-- Órdenes de Tránsito --}}
<div class="document-section bg-gradient-to-br from-green-50 via-white to-green-100 rounded-2xl p-8 mx-[1.38rem] mt-8 mb-8 transition-all duration-300 hover:shadow-lg border border-green-200">
    <div class="flex flex-row flex-wrap gap-4 md:gap-0 items-start md:items-center justify-between mb-8">
        <div class="flex flex-col items-start justify-start">
            <div class="flex items-center mb-2">
                <div class="w-3 h-3 bg-green-500 rounded-full mr-2 animate-pulse"></div>
                <span class="text-green-600 text-[0.75rem] font-bold leading-[1.03125rem] uppercase tracking-wider">Documentos Logísticos</span>
            </div>
            <h4 class="text-green-900 text-[1.625rem] font-bold leading-normal flex items-center">
                <svg class="w-6 h-6 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2v0M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                </svg>
                ÓRDENES DE TRÁNSITO
            </h4>
            <p class="text-green-700 text-sm mt-1">Documentos de transporte, logística y envíos</p>
        </div>
        <button onclick="openUploadModal('transito')" class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-8 py-4 rounded-2xl text-sm font-bold transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 hover:scale-105 mt-4 md:mt-0 border border-green-400">
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Subir Órdenes
        </button>
    </div>
    <div id="transito-documents" class="min-h-[180px] bg-gradient-to-br from-white to-green-50 rounded-2xl p-6 shadow-inner border-2 border-dashed border-green-200 hover:border-green-300 transition-all duration-300">
        <div class="text-gray-500 text-center py-12 w-full flex flex-col items-center">
            <div class="bg-green-100 rounded-full p-6 mb-4 animate-bounce">
                <svg class="w-12 h-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2v0M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                </svg>
            </div>
            <span class="text-lg font-medium text-gray-600">Selecciona un cliente para ver sus órdenes de tránsito</span>
            <span class="text-sm text-gray-400 mt-2">Las órdenes aparecerán aquí una vez selecciones un cliente</span>
        </div>
    </div>
</div>
