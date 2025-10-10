<div class="w-full h-full bg-gradient-to-br from-gray-50 to-gray-100 py-6 px-4">
    <style>
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 4xl;
            max-height: 90vh;
            overflow: auto;
            position: relative;
        }

        .pricing-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 2rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .pricing-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .form-input {
            transition: all 0.3s ease;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 14px;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF7C32 0%, #FF5722 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 124, 50, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 124, 50, 0.4);
        }

        .btn-secondary {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .table-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .animate-fadeIn {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: linear-gradient(135deg, #FF7C32 0%, #FF5722 100%);
            border-radius: 2px;
        }
    </style>

    <!-- Header Section -->
    <div class="pricing-card mb-8 animate-fadeIn">
        <div class="flex flex-col md:flex-row items-center justify-between">
            <div class="flex items-center gap-4 mb-4 md:mb-0">
                <div class="p-3 bg-white/20 backdrop-blur-sm rounded-full">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">Gestión de Precios</h1>
                    <p class="text-white/80 text-lg">Administra y controla todos tus precios de transporte</p>
                </div>
            </div>
            <button wire:click="openModal()" class="btn-primary flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Agregar Pricing
            </button>
        </div>
    </div>

    <!-- File Upload Section -->
    <div class="form-section animate-fadeIn">
        <h2 class="section-title">
            <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
            </svg>
            Importar / Exportar Datos
        </h2>
        
        <!-- Export Section -->
        <div class="bg-blue-50 rounded-lg p-4 mb-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Exportar Template
            </h3>
            <p class="text-sm text-blue-700 mb-4">
                Descarga una plantilla con las columnas correspondientes al tipo de pricing seleccionado. Esto te permitirá editar los datos de manera más rápida y luego importarlos.
            </p>
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <select wire:model="exportPricingType" class="form-input w-full">
                        <option value="" selected>Selecciona el tipo de pricing para exportar</option>
                        <option value="up">Urbanos Ipiales</option>
                        <option value="sm">San Miguel</option>
                        <option value="p">Perú</option>
                        <option value="tt">Trueca Tulcán</option>
                        <option value="bd">Bogotá Dedicados</option>
                        <option value="i">Importación</option>
                        <option value="ebmcc">Exportación Bogotá-Medellín-Cali-Cartagena</option>
                        <option value="uc">Urbanos Colombia</option>
                        <option value="vn">Viajes Nacionales</option>
                        <option value="ivco">Impo Viajes Circulares Origen</option>
                        <option value="cs">Carga Suelta</option>
                        <option value="dv">Devolución Vacíos</option>
                    </select>
                </div>
                <button wire:click="exportTemplate" class="btn-secondary px-6 py-3 flex items-center gap-2" wire:loading.attr="disabled" wire:target="exportTemplate">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="exportTemplate">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" wire:loading wire:target="exportTemplate">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="exportTemplate">Descargar Template</span>
                    <span wire:loading wire:target="exportTemplate">Generando...</span>
                </button>
            </div>
        </div>

        <!-- Import Section -->
        <div class="bg-green-50 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-green-900 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                </svg>
                Importar Datos
            </h3>
            <p class="text-sm text-green-700 mb-4">
                Selecciona el tipo de pricing y luego sube el archivo Excel/CSV con los datos a importar.
            </p>
            <div class="flex flex-col gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <select wire:model="importPricingType" class="form-input w-full">
                            <option value="" selected>Selecciona el tipo de pricing para importar</option>
                            <option value="up">Urbanos Ipiales</option>
                            <option value="sm">San Miguel</option>
                            <option value="p">Perú</option>
                            <option value="tt">Trueca Tulcán</option>
                            <option value="bd">Bogotá Dedicados</option>
                            <option value="i">Importación</option>
                            <option value="ebmcc">Exportación Bogotá-Medellín-Cali-Cartagena</option>
                            <option value="uc">Urbanos Colombia</option>
                            <option value="vn">Viajes Nacionales</option>
                            <option value="ivco">Impo Viajes Circulares Origen</option>
                            <option value="cs">Carga Suelta</option>
                            <option value="dv">Devolución Vacíos</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <input type="file" wire:model="importFile" class="form-input w-full" accept=".csv,.xlsx,.xls">
                    </div>
                    <button wire:click="importTemplate" class="btn-primary px-6 py-3 flex items-center gap-2" wire:loading.attr="disabled" wire:target="importTemplate">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="importTemplate">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" wire:loading wire:target="importTemplate">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="importTemplate">Subir Archivo</span>
                        <span wire:loading wire:target="importTemplate">Importando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Adding Pricing -->
    @if ($showModal)
        <div class="modal-overlay animate-fadeIn">
            <div class="modal-content">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                            <div class="p-2 bg-orange-100 rounded-full">
                                @if($pricingId)
                                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                @else
                                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                @endif
                            </div>
                            {{ $pricingId ? 'Editar Pricing' : 'Agregar Nuevo Pricing' }}
                        </h2>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="p-6 space-y-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label for="type_pricing" class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Pricing</label>
                        <select id="type_pricing" class="form-input w-full" wire:model="type_pricing" wire:change="setModal" @if($pricingId) disabled @endif>
                            <option value="" selected>Selecciona una opción</option>
                            <option value="up">Urbanos Ipiales</option>
                            <option value="sm">San Miguel</option>
                            <option value="p">Perú</option>
                            <option value="tt">Trueca Tulcán</option>
                            <option value="bd">Bogotá Dedicados</option>
                            <option value="i">Importación</option>
                            <option value="ebmcc">Exportación Bogotá-Medellín-Cali-Cartagena</option>
                            <option value="uc">Urbanos Colombia</option>
                            <option value="vn">Viajes Nacionales</option>
                            <option value="ivco">Impo Viajes Circulares Origen</option>
                            <option value="cs">Carga Suelta</option>
                            <option value="dv">Devolución Vacíos</option>
                        </select>
                        @if($pricingId)
                            <p class="text-xs text-gray-500 mt-1">El tipo de pricing no se puede cambiar al editar</p>
                        @endif
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @if ($showModalType == 'up')
                            <div class="space-y-4">
                                <div>
                                    <label for="documents" class="block text-sm font-semibold text-gray-700 mb-2">Documentos</label>
                                    <input type="text" id="documents" wire:model="documents" class="form-input w-full" placeholder="Ingrese documentos requeridos">
                                </div>
                                <div>
                                    <label for="origin" class="block text-sm font-semibold text-gray-700 mb-2">Origen</label>
                                    <input type="text" id="origin" wire:model="origin" class="form-input w-full" placeholder="Ciudad de origen">
                                </div>
                                <div>
                                    <label for="destination" class="block text-sm font-semibold text-gray-700 mb-2">Destino</label>
                                    <input type="text" id="destination" wire:model="destination" class="form-input w-full" placeholder="Ciudad de destino">
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label for="vehicle_type" class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Vehículo</label>
                                    <input type="text" id="vehicle_type" wire:model="vehicle_type" class="form-input w-full" placeholder="Tipo de vehículo">
                                </div>
                                <div>
                                    <label for="price" class="block text-sm font-semibold text-gray-700 mb-2">Precio ($)</label>
                                    <input type="number" id="price" wire:model="price" class="form-input w-full" placeholder="0.00" step="0.01">
                                </div>
                                <div>
                                    <label for="extra" class="block text-sm font-semibold text-gray-700 mb-2">Extra</label>
                                    <input type="text" id="extra" wire:model="extra" class="form-input w-full" placeholder="Servicios adicionales">
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label for="price_extra" class="block text-sm font-semibold text-gray-700 mb-2">Precio Extra ($)</label>
                                    <input type="number" id="price_extra" wire:model="price_extra" class="form-input w-full" placeholder="0.00" step="0.01">
                                </div>
                                <div>
                                    <label for="price_extra2" class="block text-sm font-semibold text-gray-700 mb-2">Precio Extra 2 ($)</label>
                                    <input type="number" id="price_extra2" wire:model="price_extra2" class="form-input w-full" placeholder="0.00" step="0.01">
                                </div>
                                <div>
                                    <label for="download_target" class="block text-sm font-semibold text-gray-700 mb-2">Descargue en Destino</label>
                                    <input type="text" id="download_target" wire:model="download_target" class="form-input w-full" placeholder="Información de descarga">
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label for="iva" class="block text-sm font-semibold text-gray-700 mb-2">IVA (%)</label>
                                    <input type="number" id="iva" wire:model="iva" class="form-input w-full" placeholder="0.00" step="0.01">
                                </div>
                                <div>
                                    <label for="price_person" class="block text-sm font-semibold text-gray-700 mb-2">Precio Personal ($)</label>
                                    <input type="number" id="price_person" wire:model="price_person" class="form-input w-full" placeholder="0.00" step="0.01">
                                </div>
                            </div>
                        @endif

                        @if ($showModalType == 'sm')
                            <div class="space-y-4">
                                <div>
                                    <label for="event" class="block text-sm font-semibold text-gray-700 mb-2">Evento</label>
                                    <input type="text" id="event" wire:model="event" class="form-input w-full" placeholder="Tipo de evento">
                                </div>
                                <div>
                                    <label for="type_send" class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Envío</label>
                                    <input type="text" id="type_send" wire:model="type_send" class="form-input w-full" placeholder="Tipo de envío">
                                </div>
                                <div>
                                    <label for="origin_sm" class="block text-sm font-semibold text-gray-700 mb-2">Origen</label>
                                    <input type="text" id="origin_sm" wire:model="origin" class="form-input w-full" placeholder="Ciudad de origen">
                                </div>
                                <div>
                                    <label for="destination_sm" class="block text-sm font-semibold text-gray-700 mb-2">Destino</label>
                                    <input type="text" id="destination_sm" wire:model="destination" class="form-input w-full" placeholder="Ciudad de destino">
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label for="save_box" class="block text-sm font-semibold text-gray-700 mb-2">Guardado de Carga</label>
                                    <input type="text" id="save_box" wire:model="save_box" class="form-input w-full" placeholder="Información de guardado">
                                </div>
                                <div>
                                    <label for="time_day" class="block text-sm font-semibold text-gray-700 mb-2">Tiempo por Día</label>
                                    <input type="text" id="time_day" wire:model="time_day" class="form-input w-full" placeholder="Tiempo estimado">
                                </div>
                                <div>
                                    <label for="load_target" class="block text-sm font-semibold text-gray-700 mb-2">Carga</label>
                                    <input type="text" id="load_target" wire:model="load_target" class="form-input w-full" placeholder="Información de carga">
                                </div>
                                <div>
                                    <label for="return_sm" class="block text-sm font-semibold text-gray-700 mb-2">Devolución</label>
                                    <input type="text" id="return_sm" wire:model="return" class="form-input w-full" placeholder="Información de devolución">
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label for="container_sm" class="block text-sm font-semibold text-gray-700 mb-2">Contenedor</label>
                                    <input type="text" id="container_sm" wire:model="container" class="form-input w-full" placeholder="Tipo de contenedor">
                                </div>
                                <div>
                                    <label for="documents_sm" class="block text-sm font-semibold text-gray-700 mb-2">Documentos</label>
                                    <input type="text" id="documents_sm" wire:model="documents" class="form-input w-full" placeholder="Documentos requeridos">
                                </div>
                                <div>
                                    <label for="vehicle_type_sm" class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Vehículo</label>
                                    <input type="text" id="vehicle_type_sm" wire:model="vehicle_type" class="form-input w-full" placeholder="Tipo de vehículo">
                                </div>
                                <div>
                                    <label for="price_sm" class="block text-sm font-semibold text-gray-700 mb-2">Precio ($)</label>
                                    <input type="number" id="price_sm" wire:model="price" class="form-input w-full" placeholder="0.00" step="0.01">
                                </div>
                            </div>
                        @endif

                        @if ($showModalType && $showModalType != 'up' && $showModalType != 'sm')
                            <div class="space-y-4">
                                <div>
                                    <label for="documents_generic" class="block text-sm font-semibold text-gray-700 mb-2">Documentos</label>
                                    <input type="text" id="documents_generic" wire:model="documents" class="form-input w-full" placeholder="Documentos requeridos">
                                </div>
                                <div>
                                    <label for="origin_generic" class="block text-sm font-semibold text-gray-700 mb-2">Origen</label>
                                    <input type="text" id="origin_generic" wire:model="origin" class="form-input w-full" placeholder="Ciudad de origen">
                                </div>
                                <div>
                                    <label for="destination_generic" class="block text-sm font-semibold text-gray-700 mb-2">Destino</label>
                                    <input type="text" id="destination_generic" wire:model="destination" class="form-input w-full" placeholder="Ciudad de destino">
                                </div>
                                <div>
                                    <label for="vehicle_type_generic" class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Vehículo</label>
                                    <input type="text" id="vehicle_type_generic" wire:model="vehicle_type" class="form-input w-full" placeholder="Tipo de vehículo">
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label for="price_generic" class="block text-sm font-semibold text-gray-700 mb-2">Precio ($)</label>
                                    <input type="number" id="price_generic" wire:model="price" class="form-input w-full" placeholder="0.00" step="0.01">
                                </div>
                                <div>
                                    <label for="extra_generic" class="block text-sm font-semibold text-gray-700 mb-2">Extra</label>
                                    <input type="text" id="extra_generic" wire:model="extra" class="form-input w-full" placeholder="Servicios adicionales">
                                </div>
                                <div>
                                    <label for="price_extra_generic" class="block text-sm font-semibold text-gray-700 mb-2">Precio Extra ($)</label>
                                    <input type="number" id="price_extra_generic" wire:model="price_extra" class="form-input w-full" placeholder="0.00" step="0.01">
                                </div>
                                <div>
                                    <label for="load_target_generic" class="block text-sm font-semibold text-gray-700 mb-2">Carga</label>
                                    <input type="text" id="load_target_generic" wire:model="load_target" class="form-input w-full" placeholder="Información de carga">
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label for="download_target_generic" class="block text-sm font-semibold text-gray-700 mb-2">Descargue en Destino</label>
                                    <input type="text" id="download_target_generic" wire:model="download_target" class="form-input w-full" placeholder="Información de descarga">
                                </div>
                                <div>
                                    <label for="iva_generic" class="block text-sm font-semibold text-gray-700 mb-2">IVA (%)</label>
                                    <input type="number" id="iva_generic" wire:model="iva" class="form-input w-full" placeholder="0.00" step="0.01">
                                </div>
                                <div>
                                    <label for="weight_generic" class="block text-sm font-semibold text-gray-700 mb-2">Peso</label>
                                    <input type="text" id="weight_generic" wire:model="weight" class="form-input w-full" placeholder="Información de peso">
                                </div>
                                <div>
                                    <label for="condition_generic" class="block text-sm font-semibold text-gray-700 mb-2">Condición</label>
                                    <input type="text" id="condition_generic" wire:model="condition" class="form-input w-full" placeholder="Condiciones especiales">
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="p-6 bg-gray-50 border-t border-gray-200 flex justify-end space-x-4">
                    <button wire:click="closeModal" class="btn-secondary px-6 py-3">
                        Cancelar
                    </button>
                    <button wire:click="savePricing" class="btn-primary px-6 py-3" wire:loading.attr="disabled" wire:target="savePricing">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="savePricing">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24" wire:loading wire:target="savePricing">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="savePricing">{{ $pricingId ? 'Actualizar Pricing' : 'Guardar Pricing' }}</span>
                        <span wire:loading wire:target="savePricing">{{ $pricingId ? 'Actualizando...' : 'Guardando...' }}</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Filter Section -->
    @if (!$showModal)
        <div class="form-section animate-fadeIn">
            <h2 class="section-title">
                <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                </svg>
                Filtros de Búsqueda
            </h2>
            
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-800">
                    <strong>Instrucciones:</strong> Seleccione un "Tipo de Pricing" para ver todos los datos. Use los filtros adicionales de Origen y Destino para afinar su búsqueda.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="space-y-2">
                    <label for="type_pricing_filter" class="block text-sm font-semibold text-gray-700">Tipo de Pricing</label>
                    <select id="type_pricing_filter" class="form-input w-full" wire:model="type_pricing">
                        <option value="" selected>Selecciona una opción</option>
                        <option value="up">Urbanos Ipiales</option>
                        <option value="sm">San Miguel</option>
                        <option value="p">Perú</option>
                        <option value="tt">Trueca Tulcán</option>
                        <option value="bd">Bogotá Dedicados</option>
                        <option value="i">Importación</option>
                        <option value="ebmcc">Exportación Bogotá-Medellín-Cali-Cartagena</option>
                        <option value="uc">Urbanos Colombia</option>
                        <option value="vn">Viajes Nacionales</option>
                        <option value="ivco">Impo Viajes Circulares Origen</option>
                        <option value="cs">Carga Suelta</option>
                        <option value="dv">Devolución Vacíos</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label for="origin_filter" class="block text-sm font-semibold text-gray-700">Origen</label>
                    <input id="origin_filter" type="text" class="form-input w-full" wire:model="filter_origin" placeholder="Ciudad de origen">
                </div>

                <div class="space-y-2">
                    <label for="destination_filter" class="block text-sm font-semibold text-gray-700">Destino</label>
                    <input id="destination_filter" type="text" class="form-input w-full" wire:model="filter_destination" placeholder="Ciudad de destino">
                </div>
            </div>
            
            <div class="mt-6 flex justify-end gap-3">
                <button wire:click="clearFilters" class="btn-secondary flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Limpiar Filtros
                </button>
                <button wire:click="searchPricings" class="btn-primary flex items-center gap-2" wire:loading.attr="disabled" wire:target="searchPricings">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="searchPricings">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" wire:loading wire:target="searchPricings">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="searchPricings">Buscar Pricing</span>
                    <span wire:loading wire:target="searchPricings">Buscando...</span>
                </button>
            </div>
        </div>

        @if ($showTable)
            <!-- Results Section -->
            <div class="table-container animate-fadeIn">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="section-title">
                            <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Resultados de Búsqueda
                        </h2>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800">
                                {{ $pricings ? $pricings->count() : 0 }} resultado(s) encontrado(s)
                            </span>
                        </div>
                    </div>
                </div>
                
                @if ($type_pricing == 'up')
                    @if($pricings && $pricings->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Documentos</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Origen</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Destino</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo de Vehículo</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Precio</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Extra</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Precio Extra</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Precio Extra 2</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Descargue en Destino</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">IVA</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Precio Personal</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($pricings as $pricing)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->documents ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->origin ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->destination ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->vehicle_type ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    ${{ number_format(is_numeric($pricing->price) ? (float)$pricing->price : 0, 2) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->extra ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    ${{ number_format(is_numeric($pricing->price_extra) ? (float)$pricing->price_extra : 0, 2) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    ${{ number_format(is_numeric($pricing->price_extra2) ? (float)$pricing->price_extra2 : 0, 2) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->download_target ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                    {{ is_numeric($pricing->iva) ? $pricing->iva : 0 }}%
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                    ${{ number_format(is_numeric($pricing->price_person) ? (float)$pricing->price_person : 0, 2) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <button wire:click="editPricing({{ $pricing->id }})" class="text-blue-600 hover:text-blue-900 transition-colors p-1 rounded-md hover:bg-blue-50">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                    </button>
                                                    <button data-delete-pricing="{{ $pricing->id }}" class="text-red-600 hover:text-red-900 transition-colors p-1 rounded-md hover:bg-red-50">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-12 text-center">
                            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">No se encontraron resultados</h3>
                            <p class="text-gray-600 mb-4">No hay datos que coincidan con los filtros seleccionados.</p>
                            <button wire:click="clearFilters" class="btn-secondary">
                                Limpiar Filtros
                            </button>
                        </div>
                    @endif
                @endif
                
                @if ($type_pricing == 'sm')
                    @if($pricings && $pricings->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Evento</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo de Envío</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Origen</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Destino</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Guardado de Carga</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tiempo por Día</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Carga</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Devolución</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Contenedor</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Documentos</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo de Vehículo</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Precio</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($pricings as $pricing)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->event ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->type_send ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->origin ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->destination ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->save_box ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->time_day ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->load_target ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->return ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->container ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->documents ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->vehicle_type ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    ${{ number_format(is_numeric($pricing->price) ? (float)$pricing->price : 0, 2) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <button wire:click="editPricing({{ $pricing->id }})" class="text-blue-600 hover:text-blue-900 transition-colors p-1 rounded-md hover:bg-blue-50">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                    </button>
                                                    <button data-delete-pricing="{{ $pricing->id }}" class="text-red-600 hover:text-red-900 transition-colors p-1 rounded-md hover:bg-red-50">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-12 text-center">
                            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">No se encontraron resultados</h3>
                            <p class="text-gray-600 mb-4">No hay datos que coincidan con los filtros seleccionados.</p>
                            <button wire:click="clearFilters" class="btn-secondary">
                                Limpiar Filtros
                            </button>
                        </div>
                    @endif
                @endif
                
                @if ($type_pricing && $type_pricing != 'up' && $type_pricing != 'sm')
                    @if($pricings && $pricings->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Origen</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Destino</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo de Vehículo</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Precio</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Documentos</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($pricings as $pricing)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->type_pricing ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->origin ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->destination ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->vehicle_type ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    ${{ number_format(is_numeric($pricing->price) ? (float)$pricing->price : 0, 2) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pricing->documents ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <button wire:click="editPricing({{ $pricing->id }})" class="text-blue-600 hover:text-blue-900 transition-colors p-1 rounded-md hover:bg-blue-50">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                    </button>
                                                    <button data-delete-pricing="{{ $pricing->id }}" class="text-red-600 hover:text-red-900 transition-colors p-1 rounded-md hover:bg-red-50">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-12 text-center">
                            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">No se encontraron resultados</h3>
                            <p class="text-gray-600 mb-4">No hay datos que coincidan con los filtros seleccionados.</p>
                            <button wire:click="clearFilters" class="btn-secondary">
                                Limpiar Filtros
                            </button>
                        </div>
                    @endif
                @endif
            </div>
        @endif
    @endif
</div>

<script>
    document.addEventListener('livewire:initialized', function() {
        Livewire.on('alert', (data) => {
            if (data.type === 'warning') {
                alert(data.message);
            } else if (data.type === 'success') {
                // Create success notification
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-lg z-50';
                notification.innerHTML = `
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>${data.message}</span>
                    </div>
                `;
                document.body.appendChild(notification);
                
                // Remove notification after 3 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 3000);
            } else if (data.type === 'error') {
                // Create error notification
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded shadow-lg z-50';
                notification.innerHTML = `
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span>${data.message}</span>
                    </div>
                `;
                document.body.appendChild(notification);
                
                // Remove notification after 4 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 4000);
            }
        });

        // Handle download trigger
        Livewire.on('trigger-download', (data) => {
            const link = document.createElement('a');
            link.href = data.url;
            link.download = '';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        // Add confirmation dialog for delete buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('[data-delete-pricing]')) {
                e.preventDefault();
                e.stopPropagation();
                
                if (confirm('¿Estás seguro de que quieres eliminar este pricing? Esta acción no se puede deshacer.')) {
                    const button = e.target.closest('[data-delete-pricing]');
                    const pricingId = button.getAttribute('data-delete-pricing');
                    Livewire.first().call('deletePricing', pricingId);
                }
            }
        });

        // Handle download trigger
        Livewire.on('trigger-download', (data) => {
            const url = data[0].url;
            const link = document.createElement('a');
            link.href = url;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        // Handle template download
        Livewire.on('downloadTemplate', (data) => {
            const type = data[0].type;
            const url = `/pricing/export/template?type=${type}`;
            const link = document.createElement('a');
            link.href = url;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    });
</script>
