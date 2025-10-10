<!-- Modal Cambiar Estado -->
<div x-show="showStatusModal" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50" @click="showStatusModal = false"></div>
    
    <!-- Modal Content -->
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full"
             @click.stop>
            
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    Cambiar Estado del Conductor
                </h3>
                <button @click="showStatusModal = false" 
                        class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Content -->
            <form @submit.prevent="updateStatus()" class="p-6">
                <div class="mb-4">
                    <p class="text-sm text-gray-600 mb-4">
                        Cambiar el estado del conductor: <strong x-text="statusConductor.Conductor"></strong>
                    </p>
                    
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Nuevo Estado
                    </label>
                    <select x-model="newStatus"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF7C32] focus:border-transparent">
                        <option value="">Seleccionar estado...</option>
                        <option value="ACTIVO">Activo</option>
                        <option value="INACTIVO">Inactivo</option>
                        <option value="SUSPENDIDO">Suspendido</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Motivo del cambio (opcional)
                    </label>
                    <textarea x-model="statusReason"
                              rows="3"
                              placeholder="Explique el motivo del cambio de estado..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF7C32] focus:border-transparent"></textarea>
                </div>
                
                <!-- Estado actual -->
                <div class="mb-6 p-3 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">Estado actual:</p>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full mt-1"
                          :class="{
                              'bg-green-100 text-green-800': statusConductor.Estado === 'ACTIVO',
                              'bg-yellow-100 text-yellow-800': statusConductor.Estado === 'INACTIVO',
                              'bg-red-100 text-red-800': statusConductor.Estado === 'SUSPENDIDO'
                          }">
                        <span x-text="statusConductor.Estado ? statusConductor.Estado.charAt(0).toUpperCase() + statusConductor.Estado.slice(1).toLowerCase() : ''"></span>
                    </span>
                </div>
                
                <!-- Buttons -->
                <div class="flex justify-end space-x-3">
                    <button type="button" 
                            @click="showStatusModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Cancelar
                    </button>
                    <button type="submit" 
                            :disabled="updatingStatus || !newStatus"
                            class="px-4 py-2 text-sm font-medium text-white bg-[#FF7C32] border border-transparent rounded-lg hover:bg-[#E86A20] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#FF7C32] disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!updatingStatus">Cambiar Estado</span>
                        <span x-show="updatingStatus" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Actualizando...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
