{{-- Modal para Documentos Generales --}}
<div id="uploadModal_general" class="upload-modal fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm overflow-y-auto h-full w-full z-50 hidden flex items-center justify-center p-4" style="display: none;">
    <div class="relative mx-auto border-0 w-full max-w-lg shadow-2xl rounded-3xl bg-white transform transition-all duration-500 scale-95 hover:scale-100 overflow-hidden">
        <!-- Header con gradiente -->
        <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-8 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="bg-white bg-opacity-20 rounded-full p-3 mr-4 backdrop-blur-sm">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Subir Documentos</h3>
                        <p class="text-orange-100 text-sm">Documentos generales del cliente</p>
                    </div>
                </div>
                <button onclick="closeUploadModal('general')" class="text-white hover:text-orange-200 p-2 rounded-full hover:bg-white hover:bg-opacity-20 transition-all duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Contenido del modal -->
        <div class="p-8">
            <form action="{{ route('document.store') }}" method="POST" enctype="multipart/form-data" onsubmit="handleFormSubmit(event, 'general')">
                @csrf
                <input type="hidden" name="client_id" value="">
                <input type="hidden" name="category" value="general">
                
                <div class="mb-8">
                    <label class="block text-sm font-bold text-gray-700 mb-4">Selecciona archivos para subir</label>
                    
                    <!-- Zona de drag & drop mejorada -->
                    <div class="relative group">
                        <input type="file" name="files[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" 
                               id="fileInput_general"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                               required>
                        
                        <div class="border-3 border-dashed border-orange-300 rounded-2xl p-8 text-center bg-gradient-to-br from-orange-50 to-orange-100 hover:from-orange-100 hover:to-orange-200 transition-all duration-300 group-hover:border-orange-400 group-hover:scale-105 min-h-[160px] flex flex-col justify-center">
                            <div class="space-y-4">
                                <div class="mx-auto w-16 h-16 bg-orange-200 rounded-full flex items-center justify-center group-hover:bg-orange-300 transition-all duration-300">
                                    <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-orange-700 font-semibold text-lg">Arrastra y suelta tus archivos aquí</p>
                                    <p class="text-orange-600 text-sm mt-2">o <span class="font-medium underline">haz clic para seleccionar</span></p>
                                </div>
                                <div class="bg-white bg-opacity-60 rounded-xl p-3 mx-4">
                                    <p class="text-xs text-orange-700 font-medium">Formatos soportados: PDF, DOC, DOCX, JPG, PNG</p>
                                    <p class="text-xs text-orange-600">Tamaño máximo: 10MB por archivo</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Preview de archivos seleccionados -->
                        <div id="filePreview_general" class="mt-4 hidden">
                            <div class="bg-gray-50 rounded-xl p-4">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3">Archivos seleccionados:</h4>
                                <div id="fileList_general" class="space-y-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <button type="button" onclick="closeUploadModal('general')" 
                            class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all duration-200 border border-gray-300">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-8 py-3 text-sm font-bold text-white bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5">
                        <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        Subir Documentos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal para Facturas --}}
<div id="uploadModal_facturas" class="upload-modal fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm overflow-y-auto h-full w-full z-50 hidden flex items-center justify-center p-4" style="display: none;">
    <div class="relative mx-auto border-0 w-full max-w-lg shadow-2xl rounded-3xl bg-white transform transition-all duration-500 scale-95 hover:scale-100 overflow-hidden">
        <!-- Header con gradiente -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-8 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="bg-white bg-opacity-20 rounded-full p-3 mr-4 backdrop-blur-sm">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Subir Facturas</h3>
                        <p class="text-blue-100 text-sm">Facturas del cliente</p>
                    </div>
                </div>
                <button onclick="closeUploadModal('facturas')" class="text-white hover:text-blue-200 p-2 rounded-full hover:bg-white hover:bg-opacity-20 transition-all duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Contenido del modal -->
        <div class="p-8">
            <form action="{{ route('document.store') }}" method="POST" enctype="multipart/form-data" onsubmit="handleFormSubmit(event, 'facturas')">
                @csrf
                <input type="hidden" name="client_id" value="">
                <input type="hidden" name="category" value="facturas">
                
                <div class="mb-8">
                    <label class="block text-sm font-bold text-gray-700 mb-4">Selecciona facturas para subir</label>
                    
                    <!-- Zona de drag & drop mejorada -->
                    <div class="relative group">
                        <input type="file" name="files[]" multiple accept=".pdf,.jpg,.jpeg,.png" 
                               id="fileInput_facturas"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                               required>
                        
                        <div class="border-3 border-dashed border-blue-300 rounded-2xl p-8 text-center bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 transition-all duration-300 group-hover:border-blue-400 group-hover:scale-105 min-h-[160px] flex flex-col justify-center">
                            <div class="space-y-4">
                                <div class="mx-auto w-16 h-16 bg-blue-200 rounded-full flex items-center justify-center group-hover:bg-blue-300 transition-all duration-300">
                                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-blue-700 font-semibold text-lg">Arrastra y suelta tus facturas aquí</p>
                                    <p class="text-blue-600 text-sm mt-2">o <span class="font-medium underline">haz clic para seleccionar</span></p>
                                </div>
                                <div class="bg-white bg-opacity-60 rounded-xl p-3 mx-4">
                                    <p class="text-xs text-blue-700 font-medium">Formatos soportados: PDF, JPG, PNG</p>
                                    <p class="text-xs text-blue-600">Tamaño máximo: 10MB por archivo</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Preview de archivos seleccionados -->
                        <div id="filePreview_facturas" class="mt-4 hidden">
                            <div class="bg-gray-50 rounded-xl p-4">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3">Facturas seleccionadas:</h4>
                                <div id="fileList_facturas" class="space-y-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <button type="button" onclick="closeUploadModal('facturas')" 
                            class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all duration-200 border border-gray-300">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-8 py-3 text-sm font-bold text-white bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5">
                        <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        Subir Facturas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal para Órdenes de Tránsito --}}
<div id="uploadModal_transito" class="upload-modal fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm overflow-y-auto h-full w-full z-50 hidden flex items-center justify-center p-4" style="display: none;">
    <div class="relative mx-auto border-0 w-full max-w-lg shadow-2xl rounded-3xl bg-white transform transition-all duration-500 scale-95 hover:scale-100 overflow-hidden">
        <!-- Header con gradiente -->
        <div class="bg-gradient-to-r from-green-500 to-green-600 px-8 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="bg-white bg-opacity-20 rounded-full p-3 mr-4 backdrop-blur-sm">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2v0M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Subir Órdenes de Tránsito</h3>
                        <p class="text-green-100 text-sm">Documentos de transporte y logística</p>
                    </div>
                </div>
                <button onclick="closeUploadModal('transito')" class="text-white hover:text-green-200 p-2 rounded-full hover:bg-white hover:bg-opacity-20 transition-all duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Contenido del modal -->
        <div class="p-8">
            <form action="{{ route('document.store') }}" method="POST" enctype="multipart/form-data" onsubmit="handleFormSubmit(event, 'transito')">
                @csrf
                <input type="hidden" name="client_id" value="">
                <input type="hidden" name="category" value="transito">
                
                <div class="mb-8">
                    <label class="block text-sm font-bold text-gray-700 mb-4">Selecciona órdenes de tránsito para subir</label>
                    
                    <!-- Zona de drag & drop mejorada -->
                    <div class="relative group">
                        <input type="file" name="files[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" 
                               id="fileInput_transito"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                               required>
                        
                        <div class="border-3 border-dashed border-green-300 rounded-2xl p-8 text-center bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 transition-all duration-300 group-hover:border-green-400 group-hover:scale-105 min-h-[160px] flex flex-col justify-center">
                            <div class="space-y-4">
                                <div class="mx-auto w-16 h-16 bg-green-200 rounded-full flex items-center justify-center group-hover:bg-green-300 transition-all duration-300">
                                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2v0M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-green-700 font-semibold text-lg">Arrastra y suelta tus órdenes aquí</p>
                                    <p class="text-green-600 text-sm mt-2">o <span class="font-medium underline">haz clic para seleccionar</span></p>
                                </div>
                                <div class="bg-white bg-opacity-60 rounded-xl p-3 mx-4">
                                    <p class="text-xs text-green-700 font-medium">Formatos soportados: PDF, DOC, DOCX, JPG, PNG</p>
                                    <p class="text-xs text-green-600">Tamaño máximo: 10MB por archivo</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Preview de archivos seleccionados -->
                        <div id="filePreview_transito" class="mt-4 hidden">
                            <div class="bg-gray-50 rounded-xl p-4">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3">Órdenes seleccionadas:</h4>
                                <div id="fileList_transito" class="space-y-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <button type="button" onclick="closeUploadModal('transito')" 
                            class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all duration-200 border border-gray-300">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-8 py-3 text-sm font-bold text-white bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5">
                        <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        Subir Órdenes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // JavaScript para mejorar la experiencia de los modales
    document.addEventListener('DOMContentLoaded', function() {
        // Agregar funcionalidad de preview de archivos para cada modal
        ['general', 'facturas', 'transito'].forEach(category => {
            const fileInput = document.getElementById(`fileInput_${category}`);
            const filePreview = document.getElementById(`filePreview_${category}`);
            const fileList = document.getElementById(`fileList_${category}`);
            
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    const files = Array.from(e.target.files);
                    
                    if (files.length > 0) {
                        filePreview.classList.remove('hidden');
                        fileList.innerHTML = '';
                        
                        files.forEach((file, index) => {
                            const fileItem = document.createElement('div');
                            fileItem.className = 'flex items-center justify-between bg-white rounded-lg p-3 border border-gray-200';
                            
                            const fileInfo = document.createElement('div');
                            fileInfo.className = 'flex items-center';
                            
                            const icon = getFileIcon(file.type);
                            const fileName = file.name.length > 30 ? file.name.substring(0, 30) + '...' : file.name;
                            const fileSize = formatFileSize(file.size);
                            
                            fileInfo.innerHTML = `
                                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center mr-3">
                                    ${icon}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">${fileName}</p>
                                    <p class="text-xs text-gray-500">${fileSize}</p>
                                </div>
                            `;
                            
                            const removeBtn = document.createElement('button');
                            removeBtn.type = 'button';
                            removeBtn.className = 'text-red-500 hover:text-red-700 p-1';
                            removeBtn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
                            removeBtn.onclick = () => removeFile(index, category);
                            
                            fileItem.appendChild(fileInfo);
                            fileItem.appendChild(removeBtn);
                            fileList.appendChild(fileItem);
                        });
                    } else {
                        filePreview.classList.add('hidden');
                    }
                });
            }
        });
        
        // Drag and drop functionality
        ['general', 'facturas', 'transito'].forEach(category => {
            const dropZone = document.querySelector(`#uploadModal_${category} .group`);
            const fileInput = document.getElementById(`fileInput_${category}`);
            
            if (dropZone && fileInput) {
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, preventDefaults, false);
                });
                
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropZone.addEventListener(eventName, () => dropZone.classList.add('border-4'), false);
                });
                
                ['dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, () => dropZone.classList.remove('border-4'), false);
                });
                
                dropZone.addEventListener('drop', handleDrop, false);
                
                function handleDrop(e) {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    fileInput.files = files;
                    fileInput.dispatchEvent(new Event('change'));
                }
            }
        });
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    function getFileIcon(fileType) {
        if (fileType.includes('pdf')) {
            return '<svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path d="M4 18h12V6l-4-4H4v16z"></path></svg>';
        } else if (fileType.includes('image')) {
            return '<svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"></path></svg>';
        } else {
            return '<svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path d="M4 18h12V6l-4-4H4v16z"></path></svg>';
        }
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    function removeFile(index, category) {
        // Esta función sería para remover archivos individuales
        // Por simplicidad, vamos a resetear toda la selección
        const fileInput = document.getElementById(`fileInput_${category}`);
        const filePreview = document.getElementById(`filePreview_${category}`);
        
        if (fileInput && filePreview) {
            fileInput.value = '';
            filePreview.classList.add('hidden');
        }
    }
</script>
