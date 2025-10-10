<!-- Modal Ver Conductor -->
<div x-show="showModal && modalMode === 'view'" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50" @click="showModal = false"></div>
    
    <!-- Modal Content -->
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
        <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto"
             @click.stop>
            
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    Información del Conductor
                </h3>
                <button @click="showModal = false" 
                        class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Content -->
            <div class="p-6">
                <!-- Header con imagen y estado -->
                <div class="flex items-center space-x-4 mb-8 p-4 bg-gray-50 rounded-lg">
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 rounded-full bg-[#FF7C32] flex items-center justify-center">
                            <span class="text-xl font-bold text-white" x-text="getInitials(currentConductor.conductor)"></span>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-xl font-bold text-gray-900" x-text="currentConductor.Conductor"></h2>
                        <p class="text-gray-600" x-text="currentConductor.Cedula"></p>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full mt-2"
                              :class="{
                                  'bg-green-100 text-green-800': currentConductor.Estado === 'ACTIVO',
                                  'bg-yellow-100 text-yellow-800': currentConductor.Estado === 'INACTIVO',
                                  'bg-red-100 text-red-800': currentConductor.Estado === 'SUSPENDIDO'
                              }">
                            <span x-text="currentConductor.Estado ? currentConductor.Estado.charAt(0).toUpperCase() + currentConductor.Estado.slice(1).toLowerCase() : ''"></span>
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    <!-- Información del Conductor -->
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">
                            Datos del Conductor
                        </h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Nombre Completo</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor.Conductor || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Tipo de Documento</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor.Tipodocumentoconducotor || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Número de Documento</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor.Cedula || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Teléfono</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor.Telefonoconductor || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Dirección</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor['Direccion conductor'] || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Ciudad</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor['Ciudad conductor'] || 'N/A'"></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información del Vehículo -->
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">
                            Datos del Vehículo
                        </h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Placa</label>
                                <p class="text-sm text-gray-900 font-mono" x-text="currentConductor.Placa || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Marca</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor.Marca || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Modelo</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor.Modelo || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Clase de Vehículo</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor.Clasevehiculo || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Chasis</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor['Vehiculo chasis'] || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Ejes</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor['Vehiculo ejes'] || 'N/A'"></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información del Propietario -->
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">
                            Datos del Propietario
                        </h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Nombre</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor.Propietario || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Tipo de Documento</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor.Tipodocumentopropietario || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Documento</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor.Documentopropietario || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Teléfono</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor.Telefonopropietario || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Dirección</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor['Direccion propietario'] || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Ciudad</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor['Ciudad propietario'] || 'N/A'"></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información del Poseedor (si aplica) -->
                    <div x-show="currentConductor.Poseedor">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">
                            Datos del Poseedor
                        </h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Nombre</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor.Poseedor || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Tipo de Documento</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor.Tipodocumentoposeedor || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Documento</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor.Documentoposeedor || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Teléfono</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor.Telefonoposeedor || 'N/A'"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Información Adicional del Vehículo -->
                    <div class="md:col-span-2">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">
                            Información Adicional
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Carrocería</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor.Carroceria || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Capacidad</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor.Capacidad || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Código</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor.Codigo || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">País</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor.Pais || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Fecha</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor.Fecha || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Proveedor GPS</label>
                                <p class="text-sm text-gray-900" x-text="currentConductor.Proveedorgps || 'N/A'"></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-200">
                    <button @click="editConductor(currentConductor)"
                            class="px-4 py-2 text-sm font-medium text-white bg-[#FF7C32] border border-transparent rounded-lg hover:bg-[#E86A20] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#FF7C32]">
                        Editar
                    </button>
                    <button @click="showModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
