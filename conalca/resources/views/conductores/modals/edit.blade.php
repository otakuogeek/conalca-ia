<!-- Modal para EDITAR Conductor -->
<div x-show="showModal && modalMode === 'edit'" 
     x-transition:enter="transition ease-out duration-300" 
     x-transition:enter-start="opacity-0" 
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200" 
     x-transition:leave-start="opacity-100" 
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto" 
     style="display: none;">
    
    <!-- DEBUG INFO en el modal -->
    <div class="fixed top-4 right-4 bg-blue-500 text-white p-2 rounded text-xs z-[60]" x-show="showModal && modalMode === 'edit'">
        EDITAR CONDUCTOR | Modo: <span x-text="modalMode"></span> | ID: <span x-text="currentConductor?.id || 'Sin ID'"></span>
    </div>
    
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75" @click="closeModal()"></div>
        </div>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full"
             @click.stop>
            
            <!-- Header del modal -->
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">
                        <svg class="inline w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Editar Conductor
                    </h3>
                    <div class="flex items-center space-x-2">
                        <!-- Botón de actualización directa para testing -->
                        <button @click="updateConductor()"
                                type="button"
                                class="px-2 py-1 text-xs font-medium text-blue-600 bg-blue-100 border border-blue-300 rounded hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Actualizar Directo
                        </button>
                        <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Formulario para EDITAR -->
            <form @submit.prevent="updateConductor()">
                <div class="bg-white px-6 py-4 max-h-96 overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        
                        <!-- Información del Conductor -->
                        <div class="md:col-span-2">
                            <h4 class="text-md font-semibold text-gray-800 mb-3 border-b border-gray-200 pb-2">
                                👤 Información del Conductor
                            </h4>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Conductor *</label>
                            <input type="text" x-model="currentConductor.Conductor" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Nombre completo del conductor">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Documento</label>
                            <select x-model="currentConductor.Tipodocumentoconducotor"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Seleccionar tipo...</option>
                                <option value="CEDULA">Cédula de Ciudadanía</option>
                                <option value="CE">Cédula de Extranjería</option>
                                <option value="PA">Pasaporte</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cédula</label>
                            <input type="number" 
                                   x-model="currentConductor.Cedula" 
                                   min="1" 
                                   max="2147483647"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Número de cédula (máx: 2,147,483,647)">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono Conductor</label>
                            <input type="text" x-model="currentConductor.Telefonoconductor" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Teléfono del conductor">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dirección Conductor</label>
                            <input type="text" x-model="currentConductor['Direccion conductor']" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Dirección del conductor">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad Conductor</label>
                            <input type="text" x-model="currentConductor['Ciudad conductor']" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Ciudad del conductor">
                        </div>

                        <!-- Información del Vehículo -->
                        <div class="md:col-span-2 mt-4">
                            <h4 class="text-md font-semibold text-gray-800 mb-3 border-b border-gray-200 pb-2">
                                🚛 Información del Vehículo
                            </h4>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Placa</label>
                            <input type="text" x-model="currentConductor.Placa" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Placa del vehículo"
                                   style="text-transform: uppercase;">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                            <input type="text" x-model="currentConductor.Marca" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Marca del vehículo">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Modelo (Año)</label>
                            <input type="number" 
                                   x-model="currentConductor.Modelo" 
                                   min="1900" 
                                   max="2030"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Año del vehículo (ej: 2023)">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Clase de Vehículo</label>
                            <select x-model="currentConductor.Clasevehiculo" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Seleccionar clase de vehículo...</option>
                                <template x-for="vehicleClass in vehicleClasses" :key="vehicleClass.Codigo">
                                    <option :value="vehicleClass.Nombre" x-text="vehicleClass.Nombre"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Información del Propietario -->
                        <div class="md:col-span-2 mt-4">
                            <h4 class="text-md font-semibold text-gray-800 mb-3 border-b border-gray-200 pb-2">
                                🏢 Información del Propietario
                            </h4>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Propietario</label>
                            <input type="text" x-model="currentConductor.Propietario" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Nombre del propietario">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Documento Propietario</label>
                            <select x-model="currentConductor.Tipodocumentopropietario"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Seleccionar tipo...</option>
                                <option value="CEDULA">Cédula de Ciudadanía</option>
                                <option value="CE">Cédula de Extranjería</option>
                                <option value="NIT">NIT</option>
                                <option value="PA">Pasaporte</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Documento Propietario</label>
                            <input type="number" 
                                   x-model="currentConductor.Documentopropietario" 
                                   min="1" 
                                   max="2147483647"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Documento del propietario (máx: 2,147,483,647)">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono Propietario</label>
                            <input type="text" x-model="currentConductor.Telefonopropietario" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Teléfono del propietario">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dirección Propietario</label>
                            <input type="text" x-model="currentConductor['Direccion propietario']" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Dirección del propietario">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad Propietario</label>
                            <input type="text" x-model="currentConductor['Ciudad propietario']" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Ciudad del propietario">
                        </div>
                        
                        <!-- Estado -->
                        <div class="md:col-span-2 mt-4">
                            <h4 class="text-md font-semibold text-gray-800 mb-3 border-b border-gray-200 pb-2">
                                ⚡ Estado
                            </h4>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                            <select x-model="currentConductor.Estado" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="ACTIVO">ACTIVO</option>
                                <option value="INACTIVO">INACTIVO</option>
                                <option value="SUSPENDIDO">SUSPENDIDO</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Footer del modal -->
                <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3 border-t border-gray-200">
                    <!-- Debug Info (solo en desarrollo) -->
                    <div class="flex-1 text-xs text-gray-500" x-data="{showDebug: false}">
                        <button type="button" @click="showDebug = !showDebug" class="text-blue-500 underline">Debug</button>
                        <div x-show="showDebug" class="mt-1 p-2 bg-gray-100 rounded text-xs">
                            <div><strong>Modo:</strong> EDIT</div>
                            <div><strong>ID:</strong> <span x-text="currentConductor?.id || 'Sin ID (ERROR)'"></span></div>
                            <div><strong>Saving:</strong> <span x-text="savingConductor"></span></div>
                            <div><strong>Función:</strong> updateConductor()</div>
                            <div><strong>Método:</strong> PUT /api/conductores/{id}</div>
                        </div>
                    </div>
                    
                    <button type="button" @click="closeModal()" 
                            class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="savingConductor"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:opacity-50">
                        <span x-show="!savingConductor" class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Actualizar Conductor
                        </span>
                        <span x-show="savingConductor" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Actualizando Conductor...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
