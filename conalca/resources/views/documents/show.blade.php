@extends('layout.app')

@section('title')
    {{ 'Documentos' }}
@endsection

@push('styles')
<style>
    /* Custom scrollbar styles */
    .scrollbar-thin {
        scrollbar-width: thin;
    }
    
    .scrollbar-thin::-webkit-scrollbar {
        height: 8px;
        width: 8px;
    }
    
    .scrollbar-thumb-blue-300::-webkit-scrollbar-thumb {
        background-color: #fdba74;
        border-radius: 4px;
    }
    
    .scrollbar-thumb-green-300::-webkit-scrollbar-thumb {
        background-color: #fed7aa;
        border-radius: 4px;
    }
    
    .scrollbar-thumb-orange-300::-webkit-scrollbar-thumb {
        background-color: #fb923c;
        border-radius: 4px;
    }
    
    .scrollbar-track-blue-100::-webkit-scrollbar-track {
        background-color: #fed7aa;
        border-radius: 4px;
    }
    
    .scrollbar-track-green-100::-webkit-scrollbar-track {
        background-color: #fef3c7;
        border-radius: 4px;
    }
    
    .scrollbar-track-orange-100::-webkit-scrollbar-track {
        background-color: #fef3c7;
        border-radius: 4px;
    }
    
    /* Animation for document cards */
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .document-card {
        animation: slideInRight 0.3s ease-out;
    }
    
    /* Hover effects */
    .document-section:hover {
        transform: translateY(-2px);
        transition: transform 0.2s ease-in-out;
    }
</style>
@endpush

@push('scripts')
    @vite('resources/js/dropzone.js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== INICIALIZANDO MÓDULO DE DOCUMENTOS ===');
            
            // Variables globales
            window.selectedClientData = null;
            
            // Inicializar módulo
            initializeDocumentModule();
            
            function initializeDocumentModule() {
                console.log('Configurando event listeners...');
                setupEventListeners();
                setupUploadModals();
                
                // Debug: verificar elementos
                setTimeout(() => {
                    checkRequiredElements();
                }, 100);
            }
            
            function setupEventListeners() {
                // Manejar cambios en radio buttons
                document.addEventListener('change', function(e) {
                    if (e.target.classList.contains('client-selector') && e.target.checked) {
                        const clientId = e.target.getAttribute('data-client-id');
                        console.log('=== CLIENTE SELECCIONADO VIA RADIO ===');
                        console.log('Client ID:', clientId);
                        handleClientSelection(clientId, e.target);
                    }
                });
                
                // Manejar clics en botones "Seleccionar"
                document.addEventListener('click', function(e) {
                    if (e.target.textContent && e.target.textContent.includes('Seleccionar')) {
                        e.preventDefault();
                        const row = e.target.closest('tr');
                        const radio = row.querySelector('.client-selector');
                        if (radio) {
                            radio.checked = true;
                            const clientId = radio.getAttribute('data-client-id');
                            console.log('=== CLIENTE SELECCIONADO VIA BOTÓN ===');
                            console.log('Client ID:', clientId);
                            handleClientSelection(clientId, radio);
                        }
                    }
                });
            }
            
            function handleClientSelection(clientId, radioElement) {
                if (!clientId) {
                    console.error('No se encontró ID del cliente');
                    return;
                }
                
                console.log('Procesando selección de cliente:', clientId);
                
                // 1. Mostrar panel inmediatamente
                showClientPanel();
                
                // 2. Obtener datos básicos de la fila de la tabla
                const row = radioElement.closest('tr');
                const nameElement = row.querySelector('td:nth-child(2) .font-semibold');
                const docElement = row.querySelector('td:nth-child(3) .font-medium');
                
                const clientName = nameElement ? nameElement.textContent.trim() : 'Nombre no disponible';
                const clientDoc = docElement ? docElement.textContent.trim() : 'Documento no disponible';
                
                console.log('Datos básicos obtenidos:', { clientName, clientDoc });
                
                // 3. Actualizar campos básicos inmediatamente
                updateClientField('client_company', clientName);
                updateClientField('client_nit', clientDoc);
                
                // 4. Cargar detalles completos del servidor
                loadClientDetails(clientId);
                
                // 5. Cargar documentos
                loadClientDocuments(clientId);
            }
            
            function showClientPanel() {
                const panel = document.getElementById('client-details');
                if (panel) {
                    panel.style.display = 'flex';
                    panel.classList.remove('hidden');
                    console.log('✓ Panel de cliente mostrado');
                    return true;
                } else {
                    console.error('✗ Panel client-details no encontrado');
                    return false;
                }
            }
            
            function updateClientField(fieldId, value) {
                const element = document.getElementById(fieldId);
                if (element) {
                    element.textContent = value || '-';
                    // Feedback visual
                    element.style.backgroundColor = '#dcfce7';
                    element.style.transition = 'background-color 0.5s ease';
                    setTimeout(() => {
                        element.style.backgroundColor = '';
                    }, 1500);
                    console.log(`✓ Campo ${fieldId} actualizado: "${value}"`);
                    return true;
                } else {
                    console.error(`✗ Campo ${fieldId} no encontrado`);
                    return false;
                }
            }
            
            function loadClientDetails(clientId) {
                console.log('=== CARGANDO DETALLES DEL CLIENTE ===');
                console.log('Client ID:', clientId);
                
                // Mostrar estado de carga
                updateClientField('client_company', 'Cargando...');
                
                fetch(`/documents/clients/${clientId}/details`)
                    .then(response => {
                        console.log('Response status:', response.status);
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                        return response.json();
                    })
                    .then(client => {
                        console.log('=== DATOS DEL CLIENTE RECIBIDOS ===');
                        console.log('Client data:', client);
                        
                        // Mapear y actualizar todos los campos
                        const fieldMapping = {
                            'client_company': client.cliente || client.name || 'Sin nombre',
                            'client_nit': client.documento || client.nit || 'Sin documento',
                            'client_sector': client.actividad || client.sector || 'Sin especificar',
                            'client_address': client.direccion || client.address || 'Sin dirección',
                            'client_email': client.email || client.correo || 'Sin email',
                            'client_position': client.cargo || client.position || 'Sin especificar',
                            'client_contact': client.contacto || client.contact || 'Sin contacto',
                            'client_phone': client.telefono || client.phone || 'Sin teléfono',
                            'client_city': client.ciudad || client.city || 'Sin ciudad'
                        };
                        
                        let updatedCount = 0;
                        Object.entries(fieldMapping).forEach(([fieldId, value]) => {
                            if (updateClientField(fieldId, value)) {
                                updatedCount++;
                            }
                        });
                        
                        console.log(`✓ ${updatedCount}/${Object.keys(fieldMapping).length} campos actualizados`);
                        
                        // Guardar datos del cliente globalmente
                        window.selectedClientData = client;
                        
                    })
                    .catch(error => {
                        console.error('=== ERROR CARGANDO DETALLES ===');
                        console.error('Error:', error);
                        updateClientField('client_company', `Error: ${error.message}`);
                        
                        // Mostrar panel aunque haya error
                        showClientPanel();
                    });
            }
            
            function loadClientDocuments(clientId) {
                console.log('=== CARGANDO DOCUMENTOS DEL CLIENTE ===');
                console.log('Client ID:', clientId);
                
                // Cargar documentos por categoría
                loadDocumentsByCategory(clientId, 'general', 'general-documents');
                loadDocumentsByCategory(clientId, 'facturas', 'facturas-documents');
                loadDocumentsByCategory(clientId, 'transito', 'transito-documents');
            }
            
            function loadDocumentsByCategory(clientId, category, containerId) {
                console.log(`Cargando documentos ${category} para cliente ${clientId}`);
                
                const container = document.getElementById(containerId);
                if (!container) {
                    console.error(`✗ Contenedor ${containerId} no encontrado`);
                    return;
                }
                
                // Mostrar loading
                container.innerHTML = `
                    <div class="text-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-500 mx-auto"></div>
                        <p class="mt-2 text-gray-600">Cargando documentos ${category}...</p>
                    </div>
                `;
                
                fetch(`/documents/clients/${clientId}/files/${category}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(files => {
                        console.log(`✓ Documentos ${category} cargados:`, files.length, 'archivos');
                        
                        if (files.length === 0) {
                            container.innerHTML = `
                                <div class="text-center py-8">
                                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="text-gray-600 mb-4">Sin documentos de ${category}</p>
                                    <button onclick="openUploadModal('${category}')" class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600 transition-colors">
                                        Subir documentos
                                    </button>
                                </div>
                            `;
                        } else {
                            // Mostrar documentos
                            let documentsHtml = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
                            files.forEach(file => {
                                documentsHtml += `
                                    <div class="border rounded-lg p-4 bg-white shadow hover:shadow-md transition-shadow">
                                        <h4 class="font-medium text-gray-900 truncate mb-2">${file.name || 'Sin nombre'}</h4>
                                        <p class="text-sm text-gray-500 mb-3">${formatFileSize(file.size || 0)}</p>
                                        <div class="flex gap-2">
                                            <a href="/storage/${file.file_path}" target="_blank" 
                                               class="px-3 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-600 transition-colors">
                                                Ver
                                            </a>
                                            <a href="/storage/${file.file_path}" download 
                                               class="px-3 py-1 bg-green-500 text-white text-sm rounded hover:bg-green-600 transition-colors">
                                                Descargar
                                            </a>
                                        </div>
                                    </div>
                                `;
                            });
                            documentsHtml += '</div>';
                            container.innerHTML = documentsHtml;
                        }
                    })
                    .catch(error => {
                        console.error(`✗ Error cargando documentos ${category}:`, error);
                        container.innerHTML = `
                            <div class="text-center py-8">
                                <div class="text-red-600 mb-4">
                                    <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p>Error cargando documentos</p>
                                </div>
                                <button onclick="loadDocumentsByCategory(${clientId}, '${category}', '${containerId}')" 
                                        class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors">
                                    Reintentar
                                </button>
                            </div>
                        `;
                    });
            }
            
            function setupUploadModals() {
                window.openUploadModal = function(category) {
                    if (!window.selectedClientData) {
                        alert('Por favor selecciona un cliente primero');
                        return;
                    }
                    
                    const modal = document.getElementById(`uploadModal_${category}`);
                    if (modal) {
                        modal.style.display = 'flex';
                        console.log(`Modal ${category} abierto`);
                    } else {
                        console.error(`Modal uploadModal_${category} no encontrado`);
                    }
                };
                
                window.closeUploadModal = function(category) {
                    const modal = document.getElementById(`uploadModal_${category}`);
                    if (modal) {
                        modal.style.display = 'none';
                    }
                };
            }
            
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
            
            function checkRequiredElements() {
                console.log('=== VERIFICANDO ELEMENTOS REQUERIDOS ===');
                
                const requiredElements = [
                    'client-details',
                    'client_company',
                    'client_nit',
                    'client_sector',
                    'client_address',
                    'client_email',
                    'client_position',
                    'client_contact',
                    'client_phone',
                    'client_city',
                    'general-documents',
                    'facturas-documents',
                    'transito-documents'
                ];
                
                let foundCount = 0;
                requiredElements.forEach(id => {
                    const element = document.getElementById(id);
                    if (element) {
                        foundCount++;
                        console.log(`✓ ${id}: encontrado`);
                    } else {
                        console.error(`✗ ${id}: NO ENCONTRADO`);
                    }
                });
                
                console.log(`Elementos encontrados: ${foundCount}/${requiredElements.length}`);
                
                // Verificar radio buttons
                const radios = document.querySelectorAll('.client-selector');
                console.log(`Radio buttons encontrados: ${radios.length}`);
                
                return foundCount === requiredElements.length;
            }
            
            // Funciones de prueba
            window.testShowPanel = function() {
                console.log('=== PRUEBA: MOSTRAR PANEL ===');
                const success = showClientPanel();
                if (success) {
                    updateClientField('client_company', 'Panel de prueba visible');
                    updateClientField('client_nit', 'Prueba exitosa');
                }
                return success;
            };
            
            window.testClientSelection = function() {
                console.log('=== PRUEBA: SELECCIONAR PRIMER CLIENTE ===');
                const firstRadio = document.querySelector('.client-selector');
                if (firstRadio) {
                    const clientId = firstRadio.getAttribute('data-client-id');
                    firstRadio.checked = true;
                    handleClientSelection(clientId, firstRadio);
                    console.log('✓ Prueba de selección completada');
                    return true;
                } else {
                    console.error('✗ No se encontraron clientes para probar');
                    return false;
                }
            };
            
            // Exponer funciones globalmente para debugging
            window.loadDocumentsByCategory = loadDocumentsByCategory;
            window.checkRequiredElements = checkRequiredElements;
            
            console.log('=== MÓDULO DE DOCUMENTOS INICIALIZADO ===');
        });

        // Functions for file upload modals
        function openUploadModal(category) {
            // Check if a client is selected
            if (!window.selectedClientData) {
                alert('Por favor selecciona un cliente primero');
                return;
            }
            
            const client = window.selectedClientData;
            const modal = document.getElementById(`uploadModal_${category}`);
            
            if (modal) {
                // Set the client ID in the form
                const form = modal.querySelector('form');
                if (form) {
                    const clientIdInput = form.querySelector('input[name="client_id"]');
                    
                    if (clientIdInput) {
                        clientIdInput.value = client.id;
                    }
                }
                
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }

        function closeUploadModal(category) {
            const modal = document.getElementById(`uploadModal_${category}`);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                
                // Reset form
                const form = modal.querySelector('form');
                if (form) form.reset();
            }
        }

        function handleFormSubmit(event, category) {
            const form = event.target;
            const formData = new FormData(form);
            
            event.preventDefault();
            
            // Get the selected client
            if (!window.selectedClientData) {
                alert('Por favor selecciona un cliente primero');
                return;
            }
            
            const client = window.selectedClientData;

            // Make sure client_id is set in formData
            formData.set('client_id', client.id);

            // Debug: Log what we're sending
            console.log('Uploading files for client:', client.id, 'category:', category);
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (response.ok) {
                    return response.json();
                } else {
                    return response.json().then(errorData => {
                        console.error('Server error data:', errorData);
                        let errorMessage = 'Error del servidor';
                        
                        if (errorData.message) {
                            errorMessage = errorData.message;
                        }
                        
                        if (errorData.errors) {
                            const validationErrors = Object.values(errorData.errors).flat().join(', ');
                            errorMessage += ': ' + validationErrors;
                        }
                        
                        throw new Error(errorMessage);
                    });
                }
            })
            .then(data => {
                console.log('Upload successful:', data);
                closeUploadModal(category);
                // Reload documents for the selected client
                window.loadDocumentsByCategory(client.id, category, `${category}-documents`);
                alert(data.message || 'Documentos subidos exitosamente');
            })
            .catch(error => {
                console.error('Upload error:', error);
                alert('Error al subir documentos: ' + error.message);
            });
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('upload-modal')) {
                const category = event.target.id.split('_')[1];
                closeUploadModal(category);
            }
        });
    </script>
@endpush

@section('content')
    <section class="w-full pl-3 md:pl-[3.37rem] md:pt-[2.38rem] pt-3 bg-[#F9F9F9] pb-[3.81rem] flex flex-col h-full overflow-y-auto scrollbar-default">
        @component('documents.components.headDocuments', ['clients' => $clients])
        @endcomponent
        <div class="flex w-full flex-col lg:flex-row gap-4 lg:gap-[1.44rem]">
            <div class="w-full lg:w-[58%] mb-6 lg:mb-0">
                @component('documents.components.documentsTable', ['clients' => $clients])
                @endcomponent
            </div>
            {{-- client detail --}}
            <div class="w-full lg:w-[42%] min-h-[500px] h-fit max-h-[80vh] bg-white rounded-[1.1875rem] shadow-md flex flex-col items-start overflow-y-auto
            scrollbar-default pl-[1.06rem] pr-[1.69rem] py-2 border-2 border-gray-200"
                id="client-details" style="display: none;">
                @component('documents.components.documentDetails')
                @endcomponent
            </div>
        </div>

        {{-- Upload Modals --}}
        @component('documents.components.uploadModals')
        @endcomponent
    </section>
@endsection
