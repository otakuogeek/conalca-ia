@extends('layout.app')

@section('title')
    {{ 'Configuración de Cuenta' }}
@endsection

@section('content')
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Configuración de Cuenta</h1>
                <p class="mt-2 text-gray-600">Gestiona tu información personal y preferencias de cuenta</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <nav class="space-y-2">
                            <a href="#profile" onclick="showSection('profile')" 
                               class="nav-item flex items-center px-4 py-3 text-sm font-medium rounded-lg bg-[#FBEBE2] text-[#FF7C32] transition-colors">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Información Personal
                            </a>
                            <a href="#security" onclick="showSection('security')" 
                               class="nav-item flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                Seguridad
                            </a>
                            <a href="#notifications" onclick="showSection('notifications')" 
                               class="nav-item flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                Notificaciones
                            </a>
                            <a href="#preferences" onclick="showSection('preferences')" 
                               class="nav-item flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Preferencias
                            </a>
                        </nav>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="lg:col-span-2">
                    <!-- Profile Section -->
                    <div id="profile-section" class="content-section bg-white rounded-xl shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900">Información Personal</h2>
                            <p class="mt-1 text-sm text-gray-600">Actualiza tu información personal y foto de perfil</p>
                        </div>
                        
                        <form class="p-6 space-y-6" id="profileForm">
                            @csrf
                            <!-- Profile Photo Section -->
                            <div class="flex items-center space-x-6">
                                <div class="flex-shrink-0">
                                    <img id="profileImage" class="h-20 w-20 rounded-full object-cover border-4 border-white shadow-lg" 
                                         src="{{ auth()->user()->profile_photo ? asset('storage/' . auth()->user()->profile_photo) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=FF7C32&color=fff&size=200' }}" 
                                         alt="Foto de perfil">
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-medium text-gray-900">Foto de Perfil</h3>
                                    <p class="text-sm text-gray-600 mb-4">JPG, GIF o PNG. Máximo 10MB.</p>
                                    <div class="flex space-x-3">
                                        <label for="photo-upload" class="cursor-pointer inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-[#FF7C32] hover:bg-[#e56a28] transition-colors">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                            </svg>
                                            Subir Foto
                                        </label>
                                        <input id="photo-upload" name="profile_photo" type="file" class="hidden" accept="image/jpeg,image/png,image/jpg,image/gif" onchange="previewImage(this)">
                                        <button type="button" onclick="removeImage()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                            Eliminar
                                        </button>
                                    </div>
                                    <!-- File upload feedback -->
                                    <div id="upload-feedback" class="mt-2 text-sm text-gray-600 hidden"></div>
                                </div>
                            </div>

                            <!-- Personal Information Form -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="firstName" class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                                    <input type="text" id="firstName" name="first_name" 
                                           value="{{ explode(' ', auth()->user()->name)[0] ?? '' }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF7C32] focus:border-[#FF7C32] transition-colors">
                                </div>
                                <div>
                                    <label for="lastName" class="block text-sm font-medium text-gray-700 mb-2">Apellido</label>
                                    <input type="text" id="lastName" name="last_name" 
                                           value="{{ explode(' ', auth()->user()->name, 2)[1] ?? '' }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF7C32] focus:border-[#FF7C32] transition-colors">
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                    <input type="email" id="email" name="email" 
                                           value="{{ auth()->user()->email }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF7C32] focus:border-[#FF7C32] transition-colors">
                                </div>
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                                    <input type="tel" id="phone" name="phone" 
                                           value="{{ auth()->user()->phone ?? '' }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF7C32] focus:border-[#FF7C32] transition-colors">
                                </div>
                                <div>
                                    <label for="position" class="block text-sm font-medium text-gray-700 mb-2">Cargo</label>
                                    <input type="text" id="position" name="position" 
                                           value="{{ auth()->user()->position ?? '' }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF7C32] focus:border-[#FF7C32] transition-colors">
                                </div>
                                <div>
                                    <label for="department" class="block text-sm font-medium text-gray-700 mb-2">Departamento</label>
                                    <input type="text" id="department" name="department" 
                                           value="{{ auth()->user()->department ?? '' }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF7C32] focus:border-[#FF7C32] transition-colors">
                                </div>
                            </div>

                            <div>
                                <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">Biografía</label>
                                <textarea id="bio" name="bio" rows="4" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF7C32] focus:border-[#FF7C32] transition-colors"
                                          placeholder="Cuéntanos un poco sobre ti...">{{ auth()->user()->bio ?? '' }}</textarea>
                            </div>

                            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                                <button type="button" class="px-6 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                    Cancelar
                                </button>
                                <button type="submit" class="px-6 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#FF7C32] hover:bg-[#e56a28] transition-colors">
                                    Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Security Section -->
                    <div id="security-section" class="content-section bg-white rounded-xl shadow-sm border border-gray-200 hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900">Seguridad</h2>
                            <p class="mt-1 text-sm text-gray-600">Gestiona tu contraseña y configuración de seguridad</p>
                        </div>
                        
                        <form class="p-6 space-y-6" id="securityForm">
                            @csrf
                            <div>
                                <label for="currentPassword" class="block text-sm font-medium text-gray-700 mb-2">Contraseña Actual</label>
                                <input type="password" id="currentPassword" name="current_password" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF7C32] focus:border-[#FF7C32] transition-colors">
                            </div>
                            <div>
                                <label for="newPassword" class="block text-sm font-medium text-gray-700 mb-2">Nueva Contraseña</label>
                                <input type="password" id="newPassword" name="new_password" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF7C32] focus:border-[#FF7C32] transition-colors">
                            </div>
                            <div>
                                <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-2">Confirmar Nueva Contraseña</label>
                                <input type="password" id="confirmPassword" name="new_password_confirmation" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF7C32] focus:border-[#FF7C32] transition-colors">
                            </div>

                            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                                <button type="button" class="px-6 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                    Cancelar
                                </button>
                                <button type="submit" class="px-6 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#FF7C32] hover:bg-[#e56a28] transition-colors">
                                    Actualizar Contraseña
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Notifications Section -->
                    <div id="notifications-section" class="content-section bg-white rounded-xl shadow-sm border border-gray-200 hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900">Notificaciones</h2>
                            <p class="mt-1 text-sm text-gray-600">Configura tus preferencias de notificaciones</p>
                        </div>
                        
                        <div class="p-6 space-y-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-base font-medium text-gray-900">Notificaciones por Email</h3>
                                    <p class="text-sm text-gray-600">Recibe notificaciones importantes por correo electrónico</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#FF7C32]/25 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#FF7C32]"></div>
                                </label>
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-base font-medium text-gray-900">Notificaciones Push</h3>
                                    <p class="text-sm text-gray-600">Recibe notificaciones push en tu navegador</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#FF7C32]/25 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#FF7C32]"></div>
                                </label>
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-base font-medium text-gray-900">Actualizaciones de Sistema</h3>
                                    <p class="text-sm text-gray-600">Recibe notificaciones sobre actualizaciones del sistema</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#FF7C32]/25 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#FF7C32]"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Preferences Section -->
                    <div id="preferences-section" class="content-section bg-white rounded-xl shadow-sm border border-gray-200 hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900">Preferencias</h2>
                            <p class="mt-1 text-sm text-gray-600">Personaliza tu experiencia en la aplicación</p>
                        </div>
                        
                        <div class="p-6 space-y-6">
                            <div>
                                <label for="language" class="block text-sm font-medium text-gray-700 mb-2">Idioma</label>
                                <select id="language" name="language" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF7C32] focus:border-[#FF7C32] transition-colors">
                                    <option value="es">Español</option>
                                    <option value="en">English</option>
                                </select>
                            </div>

                            <div>
                                <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">Zona Horaria</label>
                                <select id="timezone" name="timezone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF7C32] focus:border-[#FF7C32] transition-colors">
                                    <option value="America/Bogota">Colombia (GMT-5)</option>
                                    <option value="America/New_York">Nueva York (GMT-5)</option>
                                    <option value="Europe/Madrid">Madrid (GMT+1)</option>
                                </select>
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-base font-medium text-gray-900">Modo Oscuro</h3>
                                    <p class="text-sm text-gray-600">Activa el tema oscuro para una mejor experiencia visual</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#FF7C32]/25 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#FF7C32]"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Navigation functionality
        function showSection(section) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(el => el.classList.add('hidden'));
            
            // Show selected section
            document.getElementById(section + '-section').classList.remove('hidden');
            
            // Update navigation styles
            document.querySelectorAll('.nav-item').forEach(el => {
                el.classList.remove('bg-[#FBEBE2]', 'text-[#FF7C32]');
                el.classList.add('text-gray-600', 'hover:bg-gray-50', 'hover:text-gray-900');
            });
            
            // Highlight active nav item
            event.target.classList.remove('text-gray-600', 'hover:bg-gray-50', 'hover:text-gray-900');
            event.target.classList.add('bg-[#FBEBE2]', 'text-[#FF7C32]');
        }

        // Image preview functionality with validation
        function previewImage(input) {
            const feedbackDiv = document.getElementById('upload-feedback');
            feedbackDiv.classList.add('hidden');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    feedbackDiv.textContent = 'Error: Solo se permiten archivos JPG, PNG o GIF';
                    feedbackDiv.classList.remove('hidden');
                    feedbackDiv.classList.add('text-red-600');
                    input.value = '';
                    return;
                }
                
                // Validate file size (10MB = 10485760 bytes)
                const maxSize = 10485760;
                if (file.size > maxSize) {
                    feedbackDiv.textContent = 'Error: El archivo es demasiado grande. Máximo 10MB';
                    feedbackDiv.classList.remove('hidden');
                    feedbackDiv.classList.add('text-red-600');
                    input.value = '';
                    return;
                }
                
                // Show success feedback
                feedbackDiv.textContent = `Archivo seleccionado: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                feedbackDiv.classList.remove('hidden', 'text-red-600');
                feedbackDiv.classList.add('text-green-600');
                
                // Preview the image
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileImage').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        // Remove image functionality
        function removeImage() {
            if (confirm('¿Estás seguro de que quieres eliminar tu foto de perfil?')) {
                fetch('{{ route("account.profile-photo.remove") }}', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('profileImage').src = 'https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=FF7C32&color=fff&size=200';
                        document.getElementById('photo-upload').value = '';
                        showNotification(data.message, 'success');
                    } else {
                        showNotification(data.message || 'Error al eliminar la foto', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error al eliminar la foto de perfil', 'error');
                });
            }
        }

        // Notification function
        function showNotification(message, type = 'success') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            notification.textContent = message;
            
            // Add to page
            document.body.appendChild(notification);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Form submission handlers
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Guardando...';
            submitBtn.disabled = true;
            
            // Create FormData to handle file upload
            const formData = new FormData(this);
            
            fetch('{{ route("account.profile.update") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Profile update response:', data); // Debug logging
                
                if (data.success) {
                    showNotification(data.message, 'success');
                    
                    // Update profile photo if new one was uploaded
                    if (data.profile_photo_url) {
                        document.getElementById('profileImage').src = data.profile_photo_url;
                        console.log('Profile photo updated to:', data.profile_photo_url);
                    }
                    
                    // Clear file input and feedback
                    const fileInput = document.getElementById('photo-upload');
                    const feedbackDiv = document.getElementById('upload-feedback');
                    if (fileInput.files.length > 0) {
                        fileInput.value = '';
                        feedbackDiv.classList.add('hidden');
                    }
                    
                    // Show user data for debugging
                    if (data.user_data) {
                        console.log('Updated user data:', data.user_data);
                    }
                } else {
                    showNotification(data.message || 'Error al actualizar el perfil', 'error');
                    console.error('Profile update failed:', data);
                }
            })
            .catch(error => {
                console.error('Profile update error:', error);
                showNotification('Error de conexión al actualizar el perfil', 'error');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });

        document.getElementById('securityForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                showNotification('Las contraseñas no coinciden', 'error');
                return;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Actualizando...';
            submitBtn.disabled = true;
            
            const formData = new FormData(this);
            
            fetch('{{ route("account.password.update") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    this.reset();
                } else {
                    showNotification(data.message || 'Error al actualizar la contraseña', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al actualizar la contraseña', 'error');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });

        // Handle notification toggles
        document.querySelectorAll('#notifications-section input[type="checkbox"]').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                
                // Get all notification preferences
                const emailNotifications = document.querySelector('#notifications-section input[type="checkbox"]:nth-of-type(1)').checked;
                const pushNotifications = document.querySelector('#notifications-section input[type="checkbox"]:nth-of-type(2)').checked;
                const systemUpdates = document.querySelector('#notifications-section input[type="checkbox"]:nth-of-type(3)').checked;
                
                formData.append('email_notifications', emailNotifications);
                formData.append('push_notifications', pushNotifications);
                formData.append('system_updates', systemUpdates);
                
                fetch('{{ route("account.notifications.update") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                    } else {
                        showNotification(data.message || 'Error al actualizar notificaciones', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error al actualizar las notificaciones', 'error');
                });
            });
        });

        // Handle preference changes
        document.querySelectorAll('#preferences-section select, #preferences-section input[type="checkbox"]').forEach(element => {
            element.addEventListener('change', function() {
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                formData.append('language', document.getElementById('language').value);
                formData.append('timezone', document.getElementById('timezone').value);
                formData.append('dark_mode', document.querySelector('#preferences-section input[type="checkbox"]').checked);
                
                fetch('{{ route("account.preferences.update") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                    } else {
                        showNotification(data.message || 'Error al actualizar preferencias', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error al actualizar las preferencias', 'error');
                });
            });
        });
    </script>
@endsection
