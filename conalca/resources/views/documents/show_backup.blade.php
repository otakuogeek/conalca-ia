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
            console.log('Documento cargado, inicializando funcionalidad de documentos...');
            
            // Initialize right away
            initializeDocumentModule();
            
            function initializeDocumentModule() {
                console.log('Inicializando módulo de documentos');
                setupClientSelection();
                setupUploadModals();
            }
            
            function setupClientSelection() {
                console.log('Configurando selección de clientes...');
                
                // Handle radio button changes
                document.addEventListener('change', function(e) {
                    if (e.target.classList.contains('client-selector')) {
                        handleClientSelected(e.target);
                    }
                });
                
                // Handle "Seleccionar" button clicks
                document.addEventListener('click', function(e) {
                    if (e.target.textContent.includes('Seleccionar')) {
                        const row = e.target.closest('tr');
                        const radio = row.querySelector('.client-selector');
                        if (radio) {
                            radio.checked = true;
                            handleClientSelected(radio);
                        }
                    }
                });
            }
            
            function handleClientSelected(radioElement) {
                const clientId = radioElement.getAttribute('data-client-id');
                console.log('Cliente seleccionado:', clientId);
                
                if (!clientId) {
                    console.error('No se encontró ID del cliente');
                    return;
                }
                
                // Show panel immediately
                showClientPanel();
                
                // Get basic info from table row
                const row = radioElement.closest('tr');
                const nameElement = row.querySelector('td:nth-child(2) .font-semibold');
                const docElement = row.querySelector('td:nth-child(3) .font-medium');
                
                const clientName = nameElement ? nameElement.textContent.trim() : 'Cliente';
                const clientDoc = docElement ? docElement.textContent.trim() : 'Sin documento';
                
                // Update basic info immediately
                updateClientField('client_company', clientName);
                updateClientField('client_nit', clientDoc);
                
                // Load full details from server
                loadClientDetailsFromServer(clientId);
                
                // Load documents
                loadClientDocuments(clientId);
            }
            
            function showClientPanel() {
                const panel = document.getElementById('client-details');
                if (panel) {
                    panel.style.display = 'flex';
                    panel.classList.remove('hidden');
                    panel.classList.add('flex', 'flex-col');
                    console.log('Panel de cliente mostrado');
                } else {
                    console.error('Panel client-details no encontrado');
                }
            }
            
            function updateClientField(fieldId, value) {
                const element = document.getElementById(fieldId);
                if (element) {
                    element.textContent = value || '-';
                    element.style.backgroundColor = '#f0fdf4';
                    setTimeout(() => {
                        element.style.backgroundColor = '';
                    }, 1000);
                    console.log(`Campo ${fieldId} actualizado: ${value}`);
                } else {
                    console.error(`Campo ${fieldId} no encontrado`);
                }
            }
            
            function loadClientDetailsFromServer(clientId) {
                console.log('Cargando detalles del cliente desde servidor:', clientId);
                
                fetch(`/documents/clients/${clientId}/details`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Error ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(client => {
                        console.log('Datos del cliente recibidos:', client);
                        
                        // Update all fields
                        updateClientField('client_company', client.cliente || client.name || 'Sin nombre');
                        updateClientField('client_nit', client.documento || client.nit || 'Sin documento');
                        updateClientField('client_sector', client.actividad || 'Sin especificar');
                        updateClientField('client_address', client.direccion || 'Sin dirección');
                        updateClientField('client_email', client.email || 'Sin email');
                        updateClientField('client_position', client.cargo || 'Sin especificar');
                        updateClientField('client_contact', client.contacto || 'Sin contacto');
                        updateClientField('client_phone', client.telefono || 'Sin teléfono');
                        updateClientField('client_city', client.ciudad || 'Sin ciudad');
                        
                        // Store client data globally
                        window.selectedClientData = client;
                        
                        console.log('Detalles del cliente actualizados correctamente');
                    })
                    .catch(error => {
                        console.error('Error cargando detalles del cliente:', error);
                        updateClientField('client_company', 'Error cargando datos');
                    });
            }
            
            function loadClientDocuments(clientId) {
                console.log('Cargando documentos del cliente:', clientId);
                
                // Load documents for each category
                loadDocumentsByCategory(clientId, 'general', 'general-documents');
                loadDocumentsByCategory(clientId, 'facturas', 'facturas-documents');
                loadDocumentsByCategory(clientId, 'transito', 'transito-documents');
            }
            
            function setupUploadModals() {
                // Setup upload modal functionality
                window.openUploadModal = function(category) {
                    if (!window.selectedClientData) {
                        alert('Por favor selecciona un cliente primero');
                        return;
                    }
                    
                    const modal = document.getElementById(`uploadModal-${category}`);
                    if (modal) {
                        modal.style.display = 'flex';
                        console.log(`Modal ${category} abierto`);
                    }
                };
                
                window.closeUploadModal = function(category) {
                    const modal = document.getElementById(`uploadModal-${category}`);
                    if (modal) {
                        modal.style.display = 'none';
                    }
                };
            }
            
            // Function to load documents by category - Simple version
            function loadDocumentsByCategory(clientId, category, containerId) {
                console.log(`Cargando documentos ${category} para cliente ${clientId}`);
                
                const container = document.getElementById(containerId);
                if (!container) {
                    console.error(`Contenedor ${containerId} no encontrado`);
                    return;
                }
                
                // Show loading
                container.innerHTML = `
                    <div class="text-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-500 mx-auto"></div>
                        <p class="mt-2 text-gray-600">Cargando documentos...</p>
                    </div>
                `;
                
                fetch(`/documents/clients/${clientId}/files/${category}`)
                    .then(response => response.json())
                    .then(files => {
                        console.log(`Documentos ${category} cargados:`, files);
                        
                        if (files.length === 0) {
                            container.innerHTML = `
                                <div class="text-center py-8">
                                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="text-gray-600">Sin documentos de ${category}</p>
                                    <button onclick="openUploadModal('${category}')" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                        Subir documentos
                                    </button>
                                </div>
                            `;
                        } else {
                            // Show documents
                            let documentsHtml = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">';
                            files.forEach(file => {
                                documentsHtml += `
                                    <div class="border rounded-lg p-4 bg-white shadow">
                                        <h4 class="font-medium text-gray-900 truncate">${file.name}</h4>
                                        <p class="text-sm text-gray-500 mt-1">${formatFileSize(file.size)}</p>
                                        <div class="mt-3 flex gap-2">
                                            <a href="/storage/${file.file_path}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">
                                                Ver
                                            </a>
                                            <a href="/storage/${file.file_path}" download class="text-green-600 hover:text-green-800 text-sm">
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
                        console.error(`Error cargando documentos ${category}:`, error);
                        container.innerHTML = `
                            <div class="text-center py-8">
                                <p class="text-red-600">Error cargando documentos</p>
                            </div>
                        `;
                    });
            }
            
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
            
            // Test function
            window.testClientSelection = function() {
                const firstRadio = document.querySelector('.client-selector');
                if (firstRadio) {
                    firstRadio.checked = true;
                    handleClientSelected(firstRadio);
                    console.log('Cliente de prueba seleccionado');
                } else {
                    console.log('No se encontraron clientes');
                }
            };
            
            console.log('Módulo de documentos inicializado correctamente');
            
            // Initialize client selection after DOM is ready
            setTimeout(() => {
                console.log('=== INITIALIZING CLIENT SELECTION ===');
                window.checkElements();
                initializeClientSelection();
            }, 500);
        });
            
            // Function to load documents by category - Enhanced version
            window.loadDocumentsByCategory = function(clientId, category, containerId) {
                console.log(`Loading ${category} documents for client ${clientId}`);
                
                const container = document.getElementById(containerId);
                if (!container) {
                    console.error(`Container ${containerId} not found`);
                    return;
                }
                
                // Show enhanced loading state
                container.innerHTML = `
                    <div class="text-center py-12 w-full flex flex-col items-center">
                        <div class="relative">
                            <div class="w-16 h-16 bg-gradient-to-r from-orange-400 to-orange-600 rounded-full flex items-center justify-center mb-4 animate-spin">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </div>
                            <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2">
                                <div class="flex space-x-1">
                                    <div class="w-2 h-2 bg-orange-400 rounded-full animate-bounce"></div>
                                    <div class="w-2 h-2 bg-orange-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                                    <div class="w-2 h-2 bg-orange-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                                </div>
                            </div>
                        </div>
                        <span class="text-lg font-medium text-gray-600">Cargando ${category}...</span>
                        <span class="text-sm text-gray-400 mt-2">Por favor espera un momento</span>
                    </div>
                `;
                
                // Use the correct endpoint for client files by category
                fetch(`/clients/${clientId}/files/${category}`)
                    .then(response => response.json())
                    .then(files => {
                        console.log(`${category} files loaded:`, files);
                        
                        if (files.length === 0) {
                            // Enhanced empty state with category-specific colors
                            const categoryStyles = {
                                general: { 
                                    bgColor: 'bg-orange-100', 
                                    textColor: 'text-orange-400', 
                                    buttonColor: 'bg-orange-500 hover:bg-orange-600',
                                    description: 'documentos generales' 
                                },
                                facturas: { 
                                    bgColor: 'bg-blue-100', 
                                    textColor: 'text-blue-400', 
                                    buttonColor: 'bg-blue-500 hover:bg-blue-600',
                                    description: 'facturas' 
                                },
                                transito: { 
                                    bgColor: 'bg-green-100', 
                                    textColor: 'text-green-400', 
                                    buttonColor: 'bg-green-500 hover:bg-green-600',
                                    description: 'órdenes de tránsito' 
                                }
                            };
                            
                            const style = categoryStyles[category] || categoryStyles.general;
                            
                            container.innerHTML = `
                                <div class="text-center py-12 w-full flex flex-col items-center">
                                    <div class="${style.bgColor} rounded-full p-6 mb-4">
                                        <svg class="w-12 h-12 ${style.textColor}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <span class="text-lg font-medium text-gray-600">Sin ${style.description}</span>
                                    <span class="text-sm text-gray-400 mt-2">No hay ${style.description} cargados para este cliente</span>
                                    <button onclick="openUploadModal('${category}')" class="mt-4 px-6 py-2 ${style.buttonColor} text-white rounded-xl text-sm font-medium transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                        Subir ${style.description}
                                    </button>
                                </div>
                            `;
                        } else {
                            // Enhanced documents display
                            container.innerHTML = '';
                            const documentsContainer = document.createElement('div');
                            documentsContainer.className = 'flex flex-row items-start justify-start gap-6 overflow-x-auto scrollbar-thin scrollbar-thumb-orange-300 scrollbar-track-orange-100 py-4';
                            
                            files.forEach(file => {
                                const fileElement = window.createEnhancedFileElement(file, category);
                                documentsContainer.appendChild(fileElement);
                            });
                            
                            container.appendChild(documentsContainer);
                        }
                    })
                    .catch(error => {
                        console.error(`Error loading ${category} documents:`, error);
                        container.innerHTML = `
                            <div class="text-center py-12 w-full flex flex-col items-center">
                                <div class="bg-red-100 rounded-full p-6 mb-4">
                                    <svg class="w-12 h-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <span class="text-lg font-medium text-red-600">Error al cargar documentos</span>
                                <span class="text-sm text-gray-400 mt-2">Hubo un problema al cargar los documentos</span>
                                <button onclick="window.loadDocumentsByCategory(${clientId}, '${category}', '${containerId}')" class="mt-4 px-6 py-2 bg-red-500 hover:bg-red-600 text-white rounded-xl text-sm font-medium transition-all duration-200">
                                    Reintentar
                                </button>
                            </div>
                        `;
                    });
            }

            // Enhanced function to create file element with better design
            window.createEnhancedFileElement = function(file, category) {
                const fileDiv = document.createElement('div');
                fileDiv.className = 'flex-shrink-0 w-72 bg-white rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-200 hover:border-' + getCategoryColorName(category) + '-300';
                
                // Determine file type and styling
                const extension = file.name ? file.name.split('.').pop().toLowerCase() : 'unknown';
                const fileIcon = getFileIconHtml(extension);
                const fileSize = formatFileSizeDisplay(file.size || 0);
                const uploadDate = file.created_at ? new Date(file.created_at).toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                }) : 'Fecha desconocida';
                
                const categoryColor = getCategoryColorName(category);
                
                fileDiv.innerHTML = `
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-${categoryColor}-100 to-${categoryColor}-200 rounded-xl flex items-center justify-center mr-4">
                                ${fileIcon}
                            </div>
                            <div class="flex-1">
                                <h3 class="text-sm font-bold text-gray-900 mb-1 line-clamp-2" title="${file.name || 'Sin nombre'}">${truncateFileName(file.name || 'Sin nombre', 30)}</h3>
                                <div class="flex items-center text-xs text-gray-500">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    ${uploadDate}
                                </div>
                            </div>
                        </div>
                        <div class="relative group">
                            <button class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-all duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs text-gray-500 font-medium">Tamaño del archivo</span>
                            <span class="text-xs font-bold text-${categoryColor}-600 bg-${categoryColor}-100 px-2 py-1 rounded-lg">${fileSize}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-${categoryColor}-400 to-${categoryColor}-600 h-2 rounded-full transition-all duration-500" style="width: 100%"></div>
                        </div>
                    </div>
                    
                    <div class="flex space-x-2">
                        <a href="/storage/${file.file_path}" target="_blank" 
                           class="flex-1 bg-gradient-to-r from-${categoryColor}-500 to-${categoryColor}-600 hover:from-${categoryColor}-600 hover:to-${categoryColor}-700 text-white px-4 py-2 rounded-xl text-xs font-medium transition-all duration-200 text-center transform hover:scale-105">
                            <svg class="w-3 h-3 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Ver
                        </a>
                        <a href="/storage/${file.file_path}" download 
                           class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-xl text-xs font-medium transition-all duration-200 text-center transform hover:scale-105">
                            <svg class="w-3 h-3 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Descargar
                        </a>
                    </div>
                `;
                
                return fileDiv;
            }

            // Helper functions for enhanced display
            function getFileIconHtml(extension) {
                const icons = {
                    pdf: '<svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path d="M4 18h12V6l-4-4H4v16z"></path></svg>',
                    doc: '<svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path d="M4 18h12V6l-4-4H4v16z"></path></svg>',
                    docx: '<svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path d="M4 18h12V6l-4-4H4v16z"></path></svg>',
                    jpg: '<svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"></path></svg>',
                    jpeg: '<svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"></path></svg>',
                    png: '<svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"></path></svg>'
                };
                return icons[extension] || '<svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 20 20"><path d="M4 18h12V6l-4-4H4v16z"></path></svg>';
            }

            function formatFileSizeDisplay(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            function getCategoryColorName(category) {
                const colors = {
                    general: 'orange',
                    facturas: 'blue',
                    transito: 'green'
                };
                return colors[category] || 'orange';
            }

            function truncateFileName(fileName, maxLength) {
                if (fileName.length <= maxLength) return fileName;
                const extension = fileName.split('.').pop();
                const nameWithoutExt = fileName.substring(0, fileName.lastIndexOf('.'));
                const truncatedName = nameWithoutExt.substring(0, maxLength - extension.length - 4) + '...';
                        // Client selection functionality
            function handleClientSelection() {
                console.log('Setting up client selection handlers...');
                const clientRadios = document.querySelectorAll('.client-selector');
                console.log('Found client radios:', clientRadios.length);
                
                clientRadios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        if (this.checked) {
                            const clientId = this.getAttribute('data-client-id');
                            const clientName = this.getAttribute('data-client-name');
                            const clientDocument = this.getAttribute('data-client-document');
                            
                            console.log('=== CLIENT SELECTED ===');
                            console.log('Client ID:', clientId);
                            console.log('Client Name:', clientName);
                            console.log('Client Document:', clientDocument);
                            
                            // Show the client details panel immediately
                            const clientDetailPanel = document.getElementById('client-details');
                            if (clientDetailPanel) {
                                clientDetailPanel.style.display = 'flex';
                                clientDetailPanel.classList.remove('hidden');
                                console.log('Panel is now visible');
                            }
                            
                            // Load client details
                            loadClientDetails(clientId);
                            
                            // Load documents for each category
                            console.log('Loading documents for client:', clientId);
                            loadDocumentsByCategory(clientId, 'general', 'general-documents');
                            loadDocumentsByCategory(clientId, 'facturas', 'facturas-documents');
                            loadDocumentsByCategory(clientId, 'transito', 'transito-documents');
                            
                            console.log('=== END CLIENT SELECTION DEBUG ===');
                        }
                    });
                });

                // Handle "Seleccionar" button clicks
                document.addEventListener('click', function(event) {
                    if (event.target.closest('button') && event.target.closest('button').textContent.trim().includes('Seleccionar')) {
                        console.log('Seleccionar button clicked');
                        const button = event.target.closest('button');
                        const clientRow = button.closest('tr');
                        const radioButton = clientRow.querySelector('.client-selector');
                        
                        if (radioButton) {
                            radioButton.checked = true;
                            radioButton.dispatchEvent(new Event('change'));
                        }
                    }
                });
            }

            // Function to load client details from server
            function loadClientDetails(clientId) {
                console.log('=== LOADING CLIENT DETAILS ===');
                console.log('Client ID:', clientId);
                
                // Show the client details panel first
                const clientDetailPanel = document.getElementById('client-details');
                if (clientDetailPanel) {
                    clientDetailPanel.style.display = 'flex';
                    clientDetailPanel.classList.remove('hidden');
                    console.log('Panel is now visible');
                }
                
                fetch(`/documents/clients/${clientId}/details`)
                    .then(response => {
                        console.log('Response status:', response.status);
                        console.log('Response headers:', [...response.headers.entries()]);
                        
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                        return response.json();
                    })
                    .then(client => {
                        console.log('=== CLIENT DATA RECEIVED ===');
                        console.log('Raw client data:', client);
                        console.log('Available fields:', Object.keys(client));
                        
                        // Simple direct field mapping first
                        const updates = [
                            { id: 'client_company', value: client.cliente || client.name || 'Sin nombre' },
                            { id: 'client_nit', value: client.documento || client.nit || 'Sin documento' },
                            { id: 'client_sector', value: client.actividad || client.sector || 'Sin especificar' },
                            { id: 'client_address', value: client.direccion || client.address || 'Sin dirección' },
                            { id: 'client_email', value: client.email || client.correo || 'Sin email' },
                            { id: 'client_position', value: client.cargo || client.position || 'Sin especificar' },
                            { id: 'client_contact', value: client.contacto || client.contact || 'Sin contacto' },
                            { id: 'client_phone', value: client.telefono || client.phone || 'Sin teléfono' },
                            { id: 'client_city', value: client.ciudad || client.city || 'Sin ciudad' }
                        ];
                        
                        console.log('=== UPDATING FIELDS ===');
                        let updatedCount = 0;
                        
                        updates.forEach(update => {
                            const element = document.getElementById(update.id);
                            if (element) {
                                const oldValue = element.textContent;
                                element.textContent = update.value;
                                console.log(`✓ ${update.id}: "${oldValue}" → "${update.value}"`);
                                
                                // Visual feedback
                                element.style.backgroundColor = '#dcfce7';
                                element.style.transition = 'background-color 0.5s ease';
                                setTimeout(() => {
                                    element.style.backgroundColor = '';
                                }, 2000);
                                
                                updatedCount++;
                            } else {
                                console.error(`✗ Element ${update.id} not found!`);
                            }
                        });
                        
                        console.log(`=== UPDATE COMPLETE: ${updatedCount}/${updates.length} fields updated ===`);
                        
                        // Store the complete client data
                        window.selectedClientData = client;
                        
                        // Test if elements are actually visible
                        setTimeout(() => {
                            console.log('=== POST-UPDATE VERIFICATION ===');
                            updates.forEach(update => {
                                const element = document.getElementById(update.id);
                                if (element) {
                                    console.log(`${update.id} current value: "${element.textContent}"`);
                                }
                            });
                        }, 100);
                        
                    })
                    .catch(error => {
                        console.error('=== ERROR LOADING CLIENT DETAILS ===');
                        console.error('Error:', error);
                        console.error('Stack:', error.stack);
                        
                        // Show error in the main field
                        const companyElement = document.getElementById('client_company');
                        if (companyElement) {
                            companyElement.textContent = `Error: ${error.message}`;
                            companyElement.style.color = '#ef4444';
                        }
                        
                        // Show basic info if available
                        if (window.selectedClientData) {
                            console.log('Using fallback data:', window.selectedClientData);
                            const nitElement = document.getElementById('client_nit');
                            if (nitElement) {
                                nitElement.textContent = window.selectedClientData.document || 'Ver detalles en tabla';
                            }
                        }
                    });
            }

            // Initialize client selection when page loads or content updates
            function initializeClientSelection() {
                console.log('Initializing client selection...');
                handleClientSelection();
            }
            
            // Debug functions for testing
            window.testRadioClick = function() {
                const firstRadio = document.querySelector('.client-selector');
                if (firstRadio) {
                    firstRadio.checked = true;
                    firstRadio.dispatchEvent(new Event('change'));
                    console.log('Test radio clicked');
                } else {
                    console.log('No radio buttons found');
                }
            };
            
            // Test panel visibility function (for debugging)
            window.testPanelVisibility = function() {
                const panel = document.getElementById('client-details');
                if (panel) {
                    panel.style.display = 'flex';
                    panel.classList.remove('hidden');
                    console.log('Panel should now be visible for testing');
                    return true;
                } else {
                    console.error('Panel not found');
                    return false;
                }
            };
            
            // Test loading client details function
            window.testLoadClientDetails = function(clientId = 1) {
                console.log(`Testing loadClientDetails with ID: ${clientId}`);
                
                // First, let's test the API endpoint directly
                fetch(`/documents/clients/${clientId}/details`)
                    .then(response => {
                        console.log('API Response Status:', response.status);
                        console.log('API Response Headers:', [...response.headers.entries()]);
                        return response.text();
                    })
                    .then(text => {
                        console.log('Raw API Response:', text);
                        try {
                            const data = JSON.parse(text);
                            console.log('Parsed API Data:', data);
                            console.log('Available fields in response:', Object.keys(data));
                        } catch (e) {
                            console.error('Failed to parse JSON:', e);
                        }
                    })
                    .catch(error => {
                        console.error('API Error:', error);
                    });
                
                // Also test the actual function
                loadClientDetails(clientId);
            };
            
            // Function to test with a specific client from the table
            window.testWithFirstClient = function() {
                const firstRow = document.querySelector('tbody tr');
                if (firstRow) {
                    const radioButton = firstRow.querySelector('.client-selector');
                    if (radioButton) {
                        const clientId = radioButton.getAttribute('data-client-id');
                        console.log(`Testing with first client ID: ${clientId}`);
                        window.testLoadClientDetails(clientId);
                    } else {
                        console.error('No radio button found in first row');
                    }
                } else {
                    console.error('No client rows found in table');
                }
            };
            
            // Debug: Check if all required elements exist
            window.checkElements = function() {
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
                    'client_city'
                ];
                
                console.log('=== Element Check ===');
                requiredElements.forEach(id => {
                    const element = document.getElementById(id);
                    console.log(`${id}: ${element ? '✓ Found' : '✗ Missing'}`);
                    if (element && id === 'client-details') {
                        console.log(`Panel current display: ${element.style.display}`);
                        console.log(`Panel classes: ${element.className}`);
                    }
                });
                
                // Check client selectors
                const selectors = document.querySelectorAll('.client-selector');
                console.log(`client selectors found: ${selectors.length}`);
                
                // Check table structure
                const firstRow = document.querySelector('tbody tr');
                if (firstRow) {
                    const clientNameElement = firstRow.querySelector('td:nth-child(2) .text-sm.font-semibold');
                    const clientDocElement = firstRow.querySelector('td:nth-child(3) .text-sm.font-medium');
                    console.log('Table structure check:');
                    console.log('- client name element found:', !!clientNameElement);
                    console.log('- client document element found:', !!clientDocElement);
                    if (clientNameElement) console.log('- client name:', clientNameElement.textContent.trim());
                    if (clientDocElement) console.log('- client document:', clientDocElement.textContent.trim());
                }
            };
            
            // Quick test function to show panel
            window.testShowPanel = function() {
                const panel = document.getElementById('client-details');
                if (panel) {
                    panel.style.display = 'flex';
                    panel.classList.remove('hidden');
                    console.log('Panel should now be visible');
                    
                    // Update company name for visual feedback
                    const companyElement = document.getElementById('client_company');
                    if (companyElement) {
                        companyElement.textContent = 'Panel de prueba visible';
                        companyElement.style.color = '#10b981';
                    }
                    return true;
                } else {
                    console.error('Panel not found');
                    return false;
                }
            };
            
            // Test function to simulate client selection
            window.testClientSelection = function() {
                const firstRadio = document.querySelector('.client-selector');
                if (firstRadio) {
                    console.log('Testing client selection...');
                    firstRadio.checked = true;
                    firstRadio.dispatchEvent(new Event('change'));
                    console.log('Client selection test completed');
                    return true;
                } else {
                    console.error('No client selectors found');
                    return false;
                }
            };
            
            // New debug functions
            window.testWithSelectedClient = function() {
                const checkedRadio = document.querySelector('input[type="radio"][name="selected_client"]:checked');
                if (checkedRadio) {
                    const clientId = checkedRadio.getAttribute('data-client-id');
                    console.log('=== TESTING WITH CURRENTLY SELECTED CLIENT ===');
                    console.log('Selected client ID:', clientId);
                    testLoadClientDetails(clientId);
                } else {
                    console.log('No client currently selected. Please select a client first.');
                }
            };
            
            window.testDirectAPI = function(clientId = null) {
                const testId = clientId || '1';
                console.log('=== TESTING DIRECT API CALL ===');
                console.log('Testing API with client ID:', testId);
                
                fetch(`/documents/clients/${testId}/details`)
                    .then(response => {
                        console.log('API Response status:', response.status);
                        console.log('API Response headers:', response.headers);
                        return response.text(); // Get as text first
                    })
                    .then(text => {
                        console.log('Raw API response:', text);
                        try {
                            const data = JSON.parse(text);
                            console.log('Parsed API response:', data);
                            return data;
                        } catch (e) {
                            console.error('Failed to parse JSON:', e);
                            throw new Error('Invalid JSON response');
                        }
                    })
                    .catch(error => {
                        console.error('API Error:', error);
                    });
            };
            
            window.debugFieldMapping = function() {
                console.log('=== FIELD MAPPING DEBUG ===');
                const elements = [
                    'client_company',
                    'client_nit', 
                    'client_contact',
                    'client_email',
                    'client_address'
                ];
                
                elements.forEach(id => {
                    const element = document.getElementById(id);
                    if (element) {
                        console.log(`${id}:`, {
                            element: element,
                            tagName: element.tagName,
                            currentText: element.textContent,
                            currentValue: element.value || 'N/A',
                            innerHTML: element.innerHTML
                        });
                    } else {
                        console.error(`Element ${id} NOT FOUND!`);
                    }
                });
            };
            
            // New function to test database structure
            window.testDatabaseFields = function(clientId = null) {
                const testId = clientId || '1';
                console.log('=== TESTING DATABASE FIELDS ===');
                console.log('Testing with client ID:', testId);
                
                fetch(`/documents/clients/${testId}/details`)
                    .then(response => response.json())
                    .then(client => {
                        console.log('=== DATABASE FIELDS ANALYSIS ===');
                        console.log('All fields in client object:');
                        
                        Object.keys(client).forEach(key => {
                            console.log(`- ${key}: "${client[key]}" (${typeof client[key]})`);
                        });
                        
                        // Check which fields we're looking for
                        const expectedFields = ['cliente', 'documento', 'email', 'telefono', 'direccion', 'ciudad', 'contacto', 'actividad', 'cargo'];
                        console.log('\n=== FIELD MAPPING CHECK ===');
                        expectedFields.forEach(field => {
                            if (client.hasOwnProperty(field)) {
                                console.log(`✓ ${field}: "${client[field]}"`);
                            } else {
                                console.log(`✗ ${field}: NOT FOUND`);
                            }
                        });
                    })
                    .catch(error => {
                        console.error('Error testing database fields:', error);
                    });
            };
            
            // Auto-run element check
            window.checkElements();
            
            // Observer for AJAX table updates
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.target.id === 'clientsTableContainer') {
                        console.log('Table updated, reinitializing client selection');
                        setTimeout(() => {
                            initializeClientSelection();
                        }, 200);
                    }
                });
            });
            
            const tableContainer = document.getElementById('clientsTableContainer');
            if (tableContainer) {
                observer.observe(tableContainer, { childList: true, subtree: true });
                console.log('Table observer set up successfully');
            } else {
                console.warn('Table container not found for observer');
            }
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
                const clientIdInput = form.querySelector('input[name="client_id"]');
                
                if (clientIdInput) {
                    clientIdInput.value = client.id;
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
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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
