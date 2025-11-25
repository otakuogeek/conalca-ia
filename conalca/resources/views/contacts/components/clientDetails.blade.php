{{-- Include Product Sans font --}}
<link href="https://fonts.googleapis.com/css2?family=Product+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<div style="font-family: 'Product Sans', sans-serif; padding: 1.5rem; background: white; border-radius: 19px; height: 100%; overflow-y: auto;">
    <!-- Header del cliente -->
    <div style="background: linear-gradient(135deg, #FF7C32, #FF9A56); padding: 1.5rem; border-radius: 16px; margin-bottom: 1.5rem; position: relative; overflow: hidden;">
        <div style='position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: url("data:image/svg+xml,<svg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27><defs><pattern id=%27grain%27 width=%27100%27 height=%27100%27 patternUnits=%27userSpaceOnUse%27><circle cx=%2750%27 cy=%2750%27 r=%271%27 fill=%27%23ffffff%27 opacity=%270.05%27/></pattern></defs><rect width=%27100%27 height=%27100%27 fill=%27url(%23grain)%27/></svg>"); opacity: 0.3;'></div>
        <div style="position: relative; z-index: 1;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <svg style="width: 1.5rem; height: 1.5rem; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <span style="color: rgba(255, 255, 255, 0.8); font-size: 0.875rem; font-weight: 500;">INFORMACIÓN DEL CLIENTE</span>
            </div>
            <h2 id="client_company_name" style="color: white; font-size: 1.5rem; font-weight: 700; margin: 0; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">Selecciona un cliente</h2>
        </div>
    </div>

    <form class="flex flex-col w-full" action="{{ route('contacts.update') }}" method="POST">
        @csrf
        <!-- Sección: Información Básica -->
        <div style="background: linear-gradient(135deg, #f8fafc, #f1f5f9); padding: 1.5rem; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.1rem; font-weight: 600; color: #1e293b; margin: 0 0 1.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <svg style="width: 1.25rem; height: 1.25rem; color: #FF7C32;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                Información Básica
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div>
                    <label for="name_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Nombre del Cliente
                    </label>
                    <input type="text" id="name_update" name="name" placeholder="Nombre del cliente"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                           onfocus="this.style.borderColor='#FF7C32'; this.style.boxShadow='0 0 0 3px rgba(255, 124, 50, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" 
                           onchange="enableUpdateButton()" />
                </div>
                <div>
                    <label for="document_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Documento (NIT)
                    </label>
                    <input type="text" id="document_update" name="document" placeholder="Documento del cliente"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                           onfocus="this.style.borderColor='#FF7C32'; this.style.boxShadow='0 0 0 3px rgba(255, 124, 50, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" 
                           onchange="enableUpdateButton()" />
                </div>
                <div style="grid-column: 1 / -1;">
                    <label for="company_name_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Nombre de la Empresa
                    </label>
                    <input type="text" id="company_name_update" name="company_name" placeholder="Nombre de la empresa"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                           onfocus="this.style.borderColor='#FF7C32'; this.style.boxShadow='0 0 0 3px rgba(255, 124, 50, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" 
                           onchange="enableUpdateButton()" />
                </div>
            </div>
        </div>

        <!-- Sección: Información de Contacto -->
        <div style="background: linear-gradient(135deg, #f0f9ff, #e0f2fe); padding: 1.5rem; border-radius: 16px; border: 1px solid #bae6fd; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.1rem; font-weight: 600; color: #1e293b; margin: 0 0 1.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <svg style="width: 1.25rem; height: 1.25rem; color: #0ea5e9;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                </svg>
                Información de Contacto
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div>
                    <label for="phone_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Teléfonos
                    </label>
                    <input type="text" id="phone_update" name="phone_numbers" placeholder="300 123 4567"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                           onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" 
                           onchange="enableUpdateButton()" />
                </div>
                <div>
                    <label for="personal_cell_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Celular Personal
                    </label>
                    <input type="text" id="personal_cell_update" name="personal_cell" placeholder="300 123 4567"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                           onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" 
                           onchange="enableUpdateButton()" />
                </div>
                <div style="grid-column: 1 / -1;">
                    <label for="address_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Dirección
                    </label>
                    <input type="text" id="address_update" name="address" placeholder="Calle 123 # 45-67"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                           onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" 
                           onchange="enableUpdateButton()" />
                </div>
            </div>
        </div>

        <!-- Sección: Información Adicional -->
        <div style="background: linear-gradient(135deg, #f0fdf4, #dcfce7); padding: 1.5rem; border-radius: 16px; border: 1px solid #bbf7d0; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.1rem; font-weight: 600; color: #1e293b; margin: 0 0 1.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <svg style="width: 1.25rem; height: 1.25rem; color: #22c55e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Información Adicional
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div>
                    <label for="city_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Ciudad
                    </label>
                    <input type="text" id="city_update" name="location" placeholder="Bogotá"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                           onfocus="this.style.borderColor='#22c55e'; this.style.boxShadow='0 0 0 3px rgba(34, 197, 94, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" 
                           onchange="enableUpdateButton()" />
                </div>
                <div>
                    <label for="email_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Email
                    </label>
                    <input type="email" id="email_update" name="email" placeholder="contacto@empresa.com"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                           onfocus="this.style.borderColor='#22c55e'; this.style.boxShadow='0 0 0 3px rgba(34, 197, 94, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" 
                           onchange="enableUpdateButton()" />
                </div>
            </div>
        </div>

        <!-- Sección: Usuarios Asignados -->
        <div style="background: linear-gradient(135deg, #fef3c7, #fde68a); padding: 1.5rem; border-radius: 16px; border: 1px solid #f59e0b; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.1rem; font-weight: 600; color: #1e293b; margin: 0 0 1.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <svg style="width: 1.25rem; height: 1.25rem; color: #f59e0b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Usuarios Asignados
            </h3>
            <div id="assigned_users_display" style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                <span style="color: #6b7280; font-size: 0.875rem;">Selecciona un cliente para ver los usuarios asignados</span>
            </div>
        </div>

        <input type="hidden" id="id_update" name="id" value="" />
        
        <!-- Botón de actualización -->
        <button type="submit" id="update_client_btn" style="width: 100%; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #FF7C32, #FF9A56); color: white; border: none; border-radius: 12px; cursor: pointer; font-size: 0.95rem; font-weight: 600; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem; box-shadow: 0 4px 12px rgba(255, 124, 50, 0.3); margin-bottom: 1.5rem; opacity: 0.5;" disabled
                onmouseover="if(!this.disabled) { this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(255, 124, 50, 0.4)'; }"
                onmouseout="if(!this.disabled) { this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(255, 124, 50, 0.3)'; }">
            <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Actualizar Cliente
        </button>
    </form>

    <!-- Mensaje informativo -->
    <div style="background: #f3f4f6; padding: 1.5rem; border-radius: 16px; border: 1px solid #d1d5db; text-align: center;">
        <svg style="width: 3rem; height: 3rem; color: #9ca3af; margin: 0 auto 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <h4 style="font-size: 1rem; font-weight: 600; color: #374151; margin: 0 0 0.5rem 0;">Información del Cliente</h4>
        <p style="color: #6b7280; font-size: 0.875rem; margin: 0; line-height: 1.5;">
            Selecciona un cliente de la tabla para ver y editar su información detallada. Los usuarios con permisos administrativos pueden asignar clientes a comerciales desde aquí.
        </p>
    </div>
</div>

<script>
    function enableUpdateButton() {
        const updateBtn = document.getElementById('update_client_btn');
        const idField = document.getElementById('id_update');
        
        if (idField && idField.value) {
            updateBtn.disabled = false;
            updateBtn.style.opacity = '1';
            updateBtn.style.cursor = 'pointer';
        }
    }

    // Add change event listeners to all form inputs to enable the update button
    document.addEventListener('DOMContentLoaded', function() {
        const formInputs = [
            'name_update', 'document_update', 'company_name_update', 
            'phone_update', 'personal_cell_update', 'address_update', 
            'city_update', 'email_update'
        ];
        
        formInputs.forEach(function(inputId) {
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('change', enableUpdateButton);
                input.addEventListener('input', enableUpdateButton);
            }
        });
    });
</script>

<style>
/* Animaciones para inputs */
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
