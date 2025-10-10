@extends('layout.app')

@section('title', 'Gestión de Conductores')

@section('content')
<div class="p-6 bg-white rounded-lg shadow-md" x-data="conductoresManager()"
     style="background-color: white !important; color: #1f2937 !important;">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Gestión de Conductores</h1>
            <p class="text-gray-600">Administra la información de conductores y vehículos</p>
        </div>
        
        <div class="flex space-x-3 mt-4 md:mt-0">
            <!-- Botón de estadísticas -->
            <button @click="showStats = !showStats" 
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Estadísticas
            </button>
            
            <!-- Botón nuevo conductor -->
            <button @click="openCreateModal()" 
                    class="inline-flex items-center px-4 py-2 bg-[#FF7C32] hover:bg-[#E86A20] text-white font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nuevo Conductor
            </button>
        </div>
    </div>

    <!-- Panel de estadísticas -->
    <div x-show="showStats" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total</p>
                        <p class="text-2xl font-semibold text-blue-600" x-text="stats.total || 0">0</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-green-50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Activos</p>
                        <p class="text-2xl font-semibold text-green-600" x-text="stats.activos || 0">0</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-yellow-50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Inactivos</p>
                        <p class="text-2xl font-semibold text-yellow-600" x-text="stats.inactivos || 0">0</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-red-50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Suspendidos</p>
                        <p class="text-2xl font-semibold text-red-600" x-text="stats.suspendidos || 0">0</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="bg-gray-50 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Búsqueda -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                <div class="relative">
                    <input type="text" 
                           x-model="filters.search" 
                           @input.debounce.500ms="loadConductores()"
                           placeholder="Buscar por nombre, cédula, placa o teléfono..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF7C32] focus:border-transparent bg-white text-gray-900">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Filtro por estado -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                <select x-model="filters.estado" 
                        @change="loadConductores()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF7C32] focus:border-transparent bg-white text-gray-900">
                    <option value="">Todos los estados</option>
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                    <option value="suspendido">Suspendido</option>
                </select>
            </div>
            
            <!-- Filtro por ciudad -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ciudad</label>
                <input type="text" 
                       x-model="filters.ciudad" 
                       @input.debounce.500ms="loadConductores()"
                       placeholder="Filtrar por ciudad..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF7C32] focus:border-transparent bg-white text-gray-900">
            </div>
        </div>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="flex justify-center items-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#FF7C32]"></div>
    </div>

    <!-- Tabla de conductores -->
    <div x-show="!loading" class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Conductor
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Vehículo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Contacto
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Estado
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="conductor in conductores.data" :key="conductor.id">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-[#FF7C32] flex items-center justify-center">
                                                                        <span class="text-sm font-medium text-white" x-text="getInitials(conductor.Conductor || 'NN')"></span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900" x-text="conductor.Conductor || 'Sin nombre'"></div>
                                        <div class="text-sm text-gray-500" x-text="conductor.Cedula || 'Sin cédula'"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <div class="font-medium" x-text="conductor.Placa || 'Sin placa'"></div>
                                    <div class="text-gray-500" x-text="(conductor.Marca || 'Sin marca') + ' ' + (conductor.Modelo || 'Sin modelo')"></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900" x-text="conductor.Telefonoconductor || 'Sin teléfono'"></div>
                                <div class="text-sm text-gray-500" x-text="conductor['Ciudad conductor'] || 'Sin ciudad'"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                      :class="{
                                          'bg-green-100 text-green-800': conductor.Estado === 'ACTIVO',
                                          'bg-yellow-100 text-yellow-800': conductor.Estado === 'INACTIVO',
                                          'bg-red-100 text-red-800': conductor.Estado === 'SUSPENDIDO'
                                      }"
                                      x-text="conductor.Estado || 'Sin estado'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button @click="viewConductor(conductor)" 
                                            class="text-blue-600 hover:text-blue-900">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                    <button @click="editConductor(conductor)" 
                                            class="text-indigo-600 hover:text-indigo-900">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    
                                    <button @click="deleteConductor(conductor)" 
                                            class="text-red-600 hover:text-red-900">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button @click="previousPage()" 
                            :disabled="!conductores.prev_page_url"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Anterior
                    </button>
                    <button @click="nextPage()" 
                            :disabled="!conductores.next_page_url"
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Siguiente
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Mostrando <span class="font-medium" x-text="conductores.from"></span> a <span class="font-medium" x-text="conductores.to"></span> de <span class="font-medium" x-text="conductores.total"></span> resultados
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <!-- Pagination buttons -->
                            <template x-for="page in getPaginationPages()" :key="page">
                                <button @click="goToPage(page)" 
                                        :class="page === conductores.current_page ? 'bg-[#FF7C32] text-white' : 'bg-white text-gray-500 hover:bg-gray-50'"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium"
                                        x-text="page"></button>
                            </template>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

 
    @include('conductores.modals.create')
    @include('conductores.modals.edit')
    @include('conductores.modals.view')
    @include('conductores.modals.change-status')
</div>

@push('scripts')
<script>
function conductoresManager() {
    return {
        // Variables de estado
        loading: false,
        showStats: false,
        savingConductor: false,
        isSaving: false, // Flag adicional para prevenir múltiples envíos
        conductores: { data: [], total: 0, current_page: 1, last_page: 1 },
        stats: { total: 0, activos: 0, inactivos: 0, suspendidos: 0 },
        vehicleClasses: [], // Lista de clases de vehículos
        
        // Filtros
        filters: {
            search: '',
            estado: '',
            ciudad: '',
            sort_by: 'id',
            sort_order: 'desc',
            per_page: 15
        },
        
        // Modal state
        showModal: false,
        modalMode: 'create', // 'create', 'edit', 'view'
        currentConductor: {},
        savingConductor: false,
        
        // Status modal state
        showStatusModal: false,
        statusConductor: {},
        newStatus: '',
        statusReason: '',
        updatingStatus: false,
        
        // Inicialización
        async init() {
            await this.loadVehicleClasses();
            await this.loadStats();
            await this.loadConductores();
        },
        
        // Cargar clases de vehículos
        async loadVehicleClasses() {
            try {
                const response = await fetch('/api/conductores/vehicle-classes');
                const data = await response.json();
                this.vehicleClasses = data;
                console.log('Clases de vehículos cargadas:', this.vehicleClasses);
            } catch (error) {
                console.error('Error loading vehicle classes:', error);
                this.vehicleClasses = [];
            }
        },
        
        // Cargar estadísticas
        async loadStats() {
            try {
                const response = await fetch('/api/conductores/stats');
                const data = await response.json();
                this.stats = data;
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        },
        
        // Cargar conductores
        async loadConductores(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    ...this.filters
                });
                
                const response = await fetch(`/api/conductores?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Datos recibidos del API:', data);
                
                // Verificar que los datos tengan la estructura esperada
                this.conductores = {
                    data: data.data || [],
                    total: data.total || 0,
                    current_page: data.current_page || 1,
                    last_page: data.last_page || 1,
                    next_page_url: data.next_page_url || null,
                    prev_page_url: data.prev_page_url || null
                };
                
                console.log('Conductores procesados:', this.conductores);
                console.log('Número de registros:', this.conductores.data.length);
                
            } catch (error) {
                console.error('Error loading conductores:', error);
                this.conductores = { data: [], total: 0, current_page: 1, last_page: 1 };
                this.showNotification('Error al cargar los conductores', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        // Navegación de páginas
        nextPage() {
            if (this.conductores.next_page_url) {
                this.loadConductores(this.conductores.current_page + 1);
            }
        },
        
        previousPage() {
            if (this.conductores.prev_page_url) {
                this.loadConductores(this.conductores.current_page - 1);
            }
        },
        
        // Utilidades
        getInitials(name) {
            if (!name) return 'NN';
            return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
        },
        
        showNotification(message, type = 'success') {
            console.log(`${type}: ${message}`);
            
            // Solo mostrar alertas para errores, no para operaciones exitosas
            if (type === 'error') {
                alert(`❌ Error: ${message}`);
            } else {
                // Para operaciones exitosas, solo log en consola
                console.log(`✅ ${message}`);
                // Opcional: Aquí podrías implementar un toast notification menos intrusivo
            }
        },
        
        // Funciones del modal
        openModal(mode, conductor = null) {
            console.log('=== ABRIENDO MODAL ===');
            console.log('Modo:', mode);
            console.log('Conductor:', conductor);
            
            this.modalMode = mode;
            this.savingConductor = false; // Reset estado de guardado
            
            if (mode === 'create') {
                this.currentConductor = {
                    Conductor: '',
                    Tipodocumentoconducotor: '',
                    Cedula: '',
                    Telefonoconductor: '',
                    'Direccion conductor': '',
                    'Ciudad conductor': '',
                    Placa: '',
                    Propietario: '',
                    Tipodocumentopropietario: '',
                    Documentopropietario: '',
                    Telefonopropietario: '',
                    'Direccion propietario': '',
                    'Ciudad propietario': '',
                    Marca: '',
                    Modelo: '',
                    Clasevehiculo: '',
                    Estado: 'ACTIVO'
                };
            } else if (mode === 'edit' && conductor) {
                this.currentConductor = { ...conductor };
            } else if (mode === 'view' && conductor) {
                this.currentConductor = { ...conductor };
            }
            
            this.showModal = true;
            console.log('=== FIN ABRIR MODAL ===');
        },
        
        // Cerrar modal con limpieza completa
        closeModal() {
            console.log('=== CERRANDO MODAL ===');
            this.showModal = false;
            this.modalMode = null;
            this.currentConductor = {};
            this.savingConductor = false;
            console.log('=== MODAL CERRADO Y LIMPIADO ===');
        },
        
        // Función de conveniencia para crear conductor
        openCreateModal() {
            this.openModal('create');
        },
        
        // Función de prueba para llenar datos básicos
        fillTestData() {
            console.log('=== LLENANDO DATOS DE PRUEBA ===');
            console.log('CurrentConductor antes:', this.currentConductor);
            console.log('CurrentConductor antes JSON:', JSON.stringify(this.currentConductor));
            
            this.currentConductor = {
                ...this.currentConductor,
                Conductor: 'Juan Pérez Test',
                Cedula: '123456789',
                Estado: 'ACTIVO',
                Tipodocumentoconducotor: 'CEDULA',
                Telefonoconductor: '3001234567',
                'Direccion conductor': 'Calle 123 # 45-67',
                'Ciudad conductor': 'Bogotá',
                Placa: 'ABC123',
                Marca: 'CHEVROLET',
                Modelo: '2023',
                Clasevehiculo: 'AUTOMOVIL',
                Propietario: 'María García Test',
                Tipodocumentopropietario: 'CEDULA',
                Documentopropietario: '987654321',
                Telefonopropietario: '3109876543',
                'Direccion propietario': 'Carrera 45 # 12-34',
                'Ciudad propietario': 'Bogotá'
            };
            
            console.log('CurrentConductor después:', this.currentConductor);
            console.log('CurrentConductor después JSON:', JSON.stringify(this.currentConductor));
            console.log('=== FIN DATOS DE PRUEBA ===');
        },
        
        editConductor(conductor) {
            this.openModal('edit', conductor);
        },
        
        viewConductor(conductor) {
            this.openModal('view', conductor);
        },
        
        // Cambiar estado del conductor
        changeStatus(conductor) {
            this.statusConductor = { ...conductor };
            this.newStatus = conductor.Estado || '';
            this.statusReason = '';
            this.showStatusModal = true;
        },
        
        goToPage(page) {
            this.loadConductores(page);
        },
        
        getPaginationPages() {
            const pages = [];
            const current = this.conductores.current_page;
            const last = this.conductores.last_page;
            
            let start = Math.max(1, current - 2);
            let end = Math.min(last, current + 2);
            
            for (let i = start; i <= end; i++) {
                pages.push(i);
            }
            
            return pages;
        },
        
        // Utilidades
        getInitials(name) {
            return name ? name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) : '';
        },
        
        // Crear nuevo conductor - función específica
        async createConductor() {
            console.log('=== INICIANDO CREATE CONDUCTOR ===');
            
            // PROTECCIÓN ESTRICTA contra ejecuciones duplicadas
            if (this.savingConductor || this.isSaving) {
                console.warn('⚠️ OPERACIÓN YA EN PROGRESO - Ignorando nueva solicitud');
                return false;
            }
            
            // Validar que estamos en modo correcto
            if (this.modalMode !== 'create') {
                console.error('❌ Función createConductor llamada pero no estamos en modo create. Modo actual:', this.modalMode);
                this.showNotification('Error: Función de crear llamada en modo incorrecto', 'error');
                return false;
            }
            
            // DOBLE PROTECCIÓN: Marcar ambos flags inmediatamente
            this.savingConductor = true;
            this.isSaving = true;
            
            // Crear timestamp único para esta operación
            const operationId = Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            console.log('🔒 Operación bloqueada con ID:', operationId);
            
            // Delay para dar tiempo a que la UI se actualice
            await new Promise(resolve => setTimeout(resolve, 300));
            
            try {
                console.log('Datos del conductor a crear:', this.currentConductor);
                
                // VALIDACIÓN CRÍTICA: No debe existir ID para crear
                if (this.currentConductor.id) {
                    throw new Error('Error: No se puede crear un conductor con ID existente');
                }
                
                // VALIDACIÓN DE CAMPOS OBLIGATORIOS
                const camposObligatorios = [
                    { campo: 'Telefonoconductor', nombre: 'Teléfono del Conductor' },
                    { campo: 'Telefonopropietario', nombre: 'Teléfono del Propietario' }
                ];
                
                const erroresValidacion = [];
                camposObligatorios.forEach(item => {
                    if (!this.currentConductor[item.campo] || this.currentConductor[item.campo].trim() === '') {
                        erroresValidacion.push(item.nombre);
                    }
                });
                
                if (erroresValidacion.length > 0) {
                    const mensaje = `Los siguientes campos son obligatorios:\n• ${erroresValidacion.join('\n• ')}`;
                    this.showNotification(mensaje, 'error');
                    return;
                }
                
                // Preparar datos para crear - EXCLUIR EXPLÍCITAMENTE el ID
                const datosACrear = {};
                for (const [key, value] of Object.entries(this.currentConductor)) {
                    if (key !== 'id' && value !== undefined && value !== null && value !== '') {
                        datosACrear[key] = value;
                    }
                }
                
                console.log('Datos filtrados para crear:', datosACrear);
                
                if (Object.keys(datosACrear).length === 0) {
                    throw new Error('No hay datos válidos para crear el conductor');
                }
                
                // Crear AbortController para cancelar requests duplicados
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000); // Timeout de 10 segundos
                
                // Realizar petición POST para crear
                const response = await fetch('/api/conductores', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify(datosACrear),
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);
                
                console.log('CREATE Response status:', response.status);
                
                const data = await response.json();
                console.log('CREATE Response data:', data);
                
                if (response.ok && data.success) {
                    console.log('✅ Conductor creado exitosamente con ID:', operationId);
                    this.closeModal();
                    await this.loadConductores();
                    await this.loadStats();
                    this.showNotification(data.message || 'Conductor creado exitosamente', 'success');
                } else {
                    console.log('❌ Error creando conductor para operación:', operationId);
                    
                    // Manejar específicamente errores de solicitud duplicada (429) - NO mostrar nada al usuario
                    if (response.status === 429) {
                        console.warn('Solicitud duplicada detectada por el servidor - operación:', operationId);
                        // Silenciosamente ignorar duplicados, el sistema los maneja correctamente
                        return;
                    }
                    
                    // Para errores reales (no duplicados), mostrar notificación
                    let errorMessage = data.message || 'Error al crear el conductor';
                    
                    // Solo procesar errores que NO sean de cédula duplicada
                    if (data.errors) {
                        const errorMessages = [];
                        for (const [field, messages] of Object.entries(data.errors)) {
                            // Ignorar completamente errores de cédula duplicada
                            if (field === 'Cedula' && messages.some(msg => msg.includes('already been taken'))) {
                                console.log('Ignorando error de cédula duplicada - el sistema lo maneja automáticamente');
                                continue; // Saltar este error
                            }
                            // Solo mostrar otros tipos de errores
                            errorMessages.push(`${field}: ${messages.join(', ')}`);
                        }
                        
                        // Si hay errores reales, mostrarlos
                        if (errorMessages.length > 0) {
                            errorMessage = errorMessages.join('\n');
                            this.showNotification(errorMessage, 'error');
                        }
                        // Si solo había errores de cédula duplicada, no mostrar nada
                    } else {
                        // Mostrar error general solo si no es relacionado con duplicados
                        this.showNotification(errorMessage, 'error');
                    }
                }
                
            } catch (error) {
                console.error('Error en createConductor:', error);
                this.showNotification('Error de conexión: ' + error.message, 'error');
            } finally {
                // LIMPIEZA COMPLETA: Restaurar todos los flags de protección
                this.savingConductor = false;
                this.isSaving = false;
                console.log('🔓 Protecciones liberadas - operación terminada');
                console.log('=== FIN CREATE CONDUCTOR ===');
            }
        },

        // Actualizar conductor existente - función específica
        async updateConductor() {
            console.log('=== INICIANDO UPDATE CONDUCTOR ===');
            
            // PROTECCIÓN contra ejecuciones duplicadas
            if (this.savingConductor) {
                console.log('⚠️ Ya hay una operación en progreso, cancelando...');
                return;
            }
            
            // Validar que estamos en modo correcto
            if (this.modalMode !== 'edit') {
                console.error('❌ Función updateConductor llamada pero no estamos en modo edit. Modo actual:', this.modalMode);
                this.showNotification('Error: Función de actualizar llamada en modo incorrecto', 'error');
                return;
            }
            
            // VALIDACIÓN CRÍTICA: Debe existir ID para actualizar
            if (!this.currentConductor.id) {
                this.showNotification('Error: ID del conductor requerido para actualizar', 'error');
                return;
            }
            
            // Marcar inmediatamente como saving
            this.savingConductor = true;
            
            try {
                console.log(`Actualizando conductor ID: ${this.currentConductor.id}`);
                console.log('Datos del conductor a actualizar:', this.currentConductor);
                
                // Preparar datos para actualizar - incluir solo campos modificables
                const datosAActualizar = {};
                for (const [key, value] of Object.entries(this.currentConductor)) {
                    if (key !== 'id' && value !== undefined && value !== null && value !== '') {
                        datosAActualizar[key] = value;
                    }
                }
                
                console.log('Datos filtrados para actualizar:', datosAActualizar);
                
                if (Object.keys(datosAActualizar).length === 0) {
                    throw new Error('No hay datos válidos para actualizar');
                }
                
                // Realizar petición PUT para actualizar
                const response = await fetch(`/api/conductores/${this.currentConductor.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify(datosAActualizar)
                });
                
                console.log('UPDATE Response status:', response.status);
                
                const data = await response.json();
                console.log('UPDATE Response data:', data);
                
                if (response.ok && data.success) {
                    console.log('✅ Conductor actualizado exitosamente');
                    this.closeModal();
                    await this.loadConductores();
                    await this.loadStats();
                    this.showNotification(data.message || 'Conductor actualizado exitosamente', 'success');
                } else {
                    console.log('❌ Error actualizando conductor');
                    let errorMessage = data.message || 'Error al actualizar el conductor';
                    
                    if (data.errors) {
                        const errorMessages = [];
                        for (const [field, messages] of Object.entries(data.errors)) {
                            errorMessages.push(`${field}: ${messages.join(', ')}`);
                        }
                        errorMessage += '\n' + errorMessages.join('\n');
                    }
                    
                    this.showNotification(errorMessage, 'error');
                }
                
            } catch (error) {
                console.error('Error en updateConductor:', error);
                this.showNotification('Error de conexión: ' + error.message, 'error');
            } finally {
                this.savingConductor = false;
                console.log('=== FIN UPDATE CONDUCTOR ===');
            }
        },

        // Función de conveniencia que delega a la función correcta
        saveConductor() {
            console.log('=== DELEGANDO SAVE CONDUCTOR ===');
            console.log('Modal mode:', this.modalMode);
            
            if (this.modalMode === 'create') {
                console.log('Delegando a createConductor()');
                return this.createConductor();
            } else if (this.modalMode === 'edit') {
                console.log('Delegando a updateConductor()');
                return this.updateConductor();
            } else {
                console.error('Modo de modal inválido:', this.modalMode);
                this.showNotification('Error: Modo de operación no válido', 'error');
            }
        },
        
        // NUEVA FUNCIÓN: Manejo protegido de submit del formulario
        handleFormSubmit(event) {
            console.log('🛡️ handleFormSubmit - Protegiendo contra submit duplicado');
            
            // Prevenir el submit del formulario en cualquier caso
            event.preventDefault();
            event.stopPropagation();
            
            // Si ya estamos guardando, ignorar completamente
            if (this.savingConductor || this.isSaving) {
                console.warn('⚠️ Submit ignorado - operación ya en progreso');
                return false;
            }
            
            // Proceder con la creación
            return this.createConductor();
        },
        
        // NUEVA FUNCIÓN: Manejo protegido de click del botón
        handleCreateClick(event) {
            console.log('🛡️ handleCreateClick - Protegiendo contra clicks duplicados');
            
            // Prevenir cualquier comportamiento por defecto
            event.preventDefault();
            event.stopPropagation();
            
            // Si ya estamos guardando, ignorar completamente
            if (this.savingConductor || this.isSaving) {
                console.warn('⚠️ Click ignorado - operación ya en progreso');
                return false;
            }
            
            // Deshabilitar el botón inmediatamente
            event.target.disabled = true;
            
            // Proceder con la creación
            return this.createConductor().finally(() => {
                // Re-habilitar el botón después de la operación
                event.target.disabled = false;
            });
        },
        
        // Actualizar estado
        async updateStatus() {
            if (this.updatingStatus) return;
            
            this.updatingStatus = true;
            
            try {
                const response = await fetch(`/api/conductores/${this.statusConductor.id}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                        estado: this.newStatus,
                        motivo: this.statusReason
                    })
                });
                
                const data = await response.json();
                
                if (response.ok && data.success) {
                    this.showStatusModal = false;
                    await this.loadConductores();
                    await this.loadStats();
                    this.showNotification(data.message || 'Estado actualizado exitosamente', 'success');
                } else {
                    this.showNotification(data.message || 'Error al actualizar el estado', 'error');
                }
            } catch (error) {
                console.error('Error updating status:', error);
                this.showNotification('Error de conexión al actualizar el estado', 'error');
            } finally {
                this.updatingStatus = false;
            }
        },
        
        // Eliminar conductor
        async deleteConductor(conductor) {
            if (!confirm('¿Estás seguro de que deseas eliminar este conductor?')) {
                return;
            }
            
            try {
                const response = await fetch(`/api/conductores/${conductor.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });
                
                if (response.ok) {
                    // ✅ ÉXITO: Recargar datos SIN mostrar alertas
                    console.log('✅ Conductor eliminado exitosamente');
                    await this.loadConductores();
                    await this.loadStats();
                } else {
                    // ❌ ERROR: Solo log, NO mostrar alertas porque puede ser confuso
                    console.error('❌ Error al eliminar conductor:', response.status, response.statusText);
                    // Intentar recargar datos de todos modos por si acaso se eliminó
                    await this.loadConductores();
                    await this.loadStats();
                }
            } catch (error) {
                // ❌ ERROR DE CONEXIÓN: Solo mostrar si es un problema de red real
                console.error('❌ Error de conexión:', error);
                // Intentar recargar de todos modos
                await this.loadConductores();
                await this.loadStats();
            }
        }
    }
}
</script>
@endpush
@endsection
