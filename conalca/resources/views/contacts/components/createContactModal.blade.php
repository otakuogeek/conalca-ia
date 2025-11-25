{{-- Include Product Sans font --}}
<link href="https://fonts.googleapis.com/css2?family=Product+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

{{-- Create Client Modal --}}
<div id="createContactModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.6)); z-index: 9999; align-items: center; justify-content: center; padding: 1rem; backdrop-filter: blur(4px);">
    <div style="background: white; border-radius: 20px; width: 100%; max-width: 42rem; max-height: 90vh; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1); font-family: 'Product Sans', sans-serif; transform: scale(0.95); animation: modalSlideIn 0.3s ease-out forwards;">
        
        <!-- Header con gradiente -->
        <div style="background: linear-gradient(135deg, #FF7C32, #FF9A56); padding: 2rem; border-radius: 20px 20px 0 0; position: relative; overflow: hidden;">
            <div style='position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: url("data:image/svg+xml,<svg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27><defs><pattern id=%27grain%27 width=%27100%27 height=%27100%27 patternUnits=%27userSpaceOnUse%27><circle cx=%2750%27 cy=%2750%27 r=%271%27 fill=%27%23ffffff%27 opacity=%270.05%27/></pattern></defs><rect width=%27100%27 height=%27100%27 fill=%27url(%23grain)%27/></svg>"); opacity: 0.3;'></div>
            <div style="display: flex; align-items: center; justify-content: space-between; position: relative; z-index: 1;">
                <div>
                    <h2 style="font-size: 1.75rem; font-weight: 700; color: white; margin: 0; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">Crear Nuevo Cliente</h2>
                    <p style="color: rgba(255, 255, 255, 0.9); margin: 0.5rem 0 0 0; font-size: 0.95rem;">Complete la información para registrar un nuevo cliente</p>
                </div>
                <button onclick="closeCreateClientModal()" style="color: white; background: rgba(255, 255, 255, 0.2); border: none; cursor: pointer; font-size: 1.5rem; line-height: 1; width: 3rem; height: 3rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.2s; backdrop-filter: blur(10px);" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Contenido del formulario -->
        <div style="padding: 2rem; max-height: 60vh; overflow-y: auto;">
            <form id="create-client-form" action="{{ route('contacts.store') }}" method="POST" style="display: flex; flex-direction: column; gap: 1.5rem;">
                @csrf
                
                <!-- Información básica del cliente -->
                <div style="background: linear-gradient(135deg, #f8fafc, #f1f5f9); padding: 1.5rem; border-radius: 16px; border: 1px solid #e2e8f0;">
                    <h3 style="font-size: 1.1rem; font-weight: 600; color: #1e293b; margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                        <svg style="width: 1.25rem; height: 1.25rem; color: #FF7C32;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        Información Básica
                    </h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label for="name" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Nombre del Cliente *</label>
                            <input type="text" id="name" name="name" 
                                   style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                                   placeholder="Ej: Juan Pérez"
                                   onfocus="this.style.borderColor='#FF7C32'; this.style.boxShadow='0 0 0 3px rgba(255, 124, 50, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'"
                                   required>
                        </div>
                        <div>
                            <label for="document" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Documento *</label>
                            <input type="text" id="document" name="document" 
                                   style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                                   placeholder="12345678 o 900.123.456-7"
                                   onfocus="this.style.borderColor='#FF7C32'; this.style.boxShadow='0 0 0 3px rgba(255, 124, 50, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'"
                                   required>
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <label for="company_name" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Nombre de la Empresa *</label>
                            <input type="text" id="company_name" name="company_name" 
                                   style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                                   placeholder="Ej: Transportes Colombia S.A.S"
                                   onfocus="this.style.borderColor='#FF7C32'; this.style.boxShadow='0 0 0 3px rgba(255, 124, 50, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'"
                                   required>
                        </div>
                    </div>
                </div>

                <!-- Información de contacto -->
                <div style="background: linear-gradient(135deg, #f0f9ff, #e0f2fe); padding: 1.5rem; border-radius: 16px; border: 1px solid #bae6fd;">
                    <h3 style="font-size: 1.1rem; font-weight: 600; color: #1e293b; margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                        <svg style="width: 1.25rem; height: 1.25rem; color: #0ea5e9;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        Información de Contacto
                    </h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label for="phone_numbers" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Teléfonos</label>
                            <input type="text" id="phone_numbers" name="phone_numbers" 
                                   style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                                   placeholder="300 123 4567, 601 234 5678"
                                   onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        </div>
                        <div>
                            <label for="personal_cell" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Celular Personal</label>
                            <input type="text" id="personal_cell" name="personal_cell" 
                                   style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                                   placeholder="300 123 4567"
                                   onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <label for="address" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Dirección</label>
                            <input type="text" id="address" name="address" 
                                   style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                                   placeholder="Calle 123 # 45-67, Bogotá"
                                   onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        </div>
                    </div>
                </div>

                <!-- Información adicional -->
                <div style="background: linear-gradient(135deg, #f0fdf4, #dcfce7); padding: 1.5rem; border-radius: 16px; border: 1px solid #bbf7d0;">
                    <h3 style="font-size: 1.1rem; font-weight: 600; color: #1e293b; margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                        <svg style="width: 1.25rem; height: 1.25rem; color: #22c55e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Información Adicional
                    </h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label for="location" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Ubicación</label>
                            <input type="text" id="location" name="location" 
                                   style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                                   placeholder="Bogotá, Cundinamarca"
                                   onfocus="this.style.borderColor='#22c55e'; this.style.boxShadow='0 0 0 3px rgba(34, 197, 94, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        </div>
                        <div>
                            <label for="branch_office" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Sucursal</label>
                            <input type="text" id="branch_office" name="branch_office" 
                                   style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                                   placeholder="Sucursal principal"
                                   onfocus="this.style.borderColor='#22c55e'; this.style.boxShadow='0 0 0 3px rgba(34, 197, 94, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div style="display: flex; justify-content: flex-end; gap: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                    <button type="button" onclick="closeCreateClientModal()" 
                            style="padding: 0.75rem 1.5rem; color: #6b7280; background: #f9fafb; border: 2px solid #e5e7eb; border-radius: 12px; cursor: pointer; font-size: 0.95rem; font-weight: 600; transition: all 0.2s; display: flex; align-items: center; gap: 0.5rem;"
                            onmouseover="this.style.background='#f3f4f6'; this.style.borderColor='#d1d5db'"
                            onmouseout="this.style.background='#f9fafb'; this.style.borderColor='#e5e7eb'">
                        <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancelar
                    </button>
                    <button type="submit" 
                            style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #FF7C32, #FF9A56); color: white; border: none; border-radius: 12px; cursor: pointer; font-size: 0.95rem; font-weight: 600; transition: all 0.2s; display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 12px rgba(255, 124, 50, 0.3);"
                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(255, 124, 50, 0.4)'"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(255, 124, 50, 0.3)'">
                        <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Crear Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Asignación de Clientes -->
<div id="assignModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 16px; width: 100%; max-width: 32rem; max-height: 90vh; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
        <div style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); padding: 1.5rem; color: white;">
            <h3 style="font-size: 1.25rem; font-weight: 600; margin: 0;">Asignar Cliente</h3>
            <p style="margin: 0.5rem 0 0 0; opacity: 0.9; font-size: 0.875rem;">Seleccione los usuarios comerciales para asignar este cliente</p>
        </div>
        
        <div style="padding: 1.5rem;">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Seleccionar Usuario Comercial</label>
                <select id="userSelect" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem;">
                    <option value="">Seleccione un usuario...</option>
                    @foreach(\App\Models\User::role(['GERENTE DE CUENTA', 'ASISTENTE COMERCIAL'])->where('active', true)->get() as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <button onclick="assignUser()" style="width: 100%; padding: 0.75rem; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; margin-bottom: 1rem;">
                Asignar Usuario
            </button>
            
            <div id="assignedUsersList" style="border-top: 1px solid #e5e7eb; padding-top: 1rem;">
                <h4 style="font-size: 0.875rem; font-weight: 600; color: #374151; margin: 0 0 0.5rem 0;">Usuarios Asignados:</h4>
                <div id="assignedUsersContainer"></div>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <button onclick="closeAssignModal()" style="padding: 0.5rem 1rem; background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer;">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Estilos para inputs con mejor UX */
input:focus {
    transform: translateY(-1px);
}

/* Scrollbar personalizada */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #FF7C32, #FF9A56);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #FF6B1A, #FF8A3D);
}
</style>
