{{-- Include Product Sans font --}}
<link href="https://fonts.googleapis.com/css2?family=Product+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

{{-- Create Contact Modal --}}
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
                <button onclick="closeCreateContactModal()" style="color: white; background: rgba(255, 255, 255, 0.2); border: none; cursor: pointer; font-size: 1.5rem; line-height: 1; width: 3rem; height: 3rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.2s; backdrop-filter: blur(10px);" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Contenido del formulario -->
        <div style="padding: 2rem; max-height: 60vh; overflow-y: auto;">
            <form id="create-contact-form" action="{{ route('contacts.store') }}" method="POST" style="display: flex; flex-direction: column; gap: 1.5rem;">
                @csrf
                
                <!-- Información de la empresa -->
                <div style="background: linear-gradient(135deg, #f8fafc, #f1f5f9); padding: 1.5rem; border-radius: 16px; border: 1px solid #e2e8f0;">
                    <h3 style="font-size: 1.1rem; font-weight: 600; color: #1e293b; margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                        <svg style="width: 1.25rem; height: 1.25rem; color: #FF7C32;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        Información de la Empresa
                    </h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div style="grid-column: 1 / -1;">
                            <label for="company" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Nombre de la Empresa *</label>
                            <input type="text" id="company" name="company" 
                                   style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                                   placeholder="Ej: Transportes Colombia S.A.S"
                                   onfocus="this.style.borderColor='#FF7C32'; this.style.boxShadow='0 0 0 3px rgba(255, 124, 50, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'"
                                   required>
                        </div>
                        <div>
                            <label for="nit" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">NIT *</label>
                            <input type="text" id="nit" name="nit" 
                                   style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                                   placeholder="900.123.456-7"
                                   onfocus="this.style.borderColor='#FF7C32'; this.style.boxShadow='0 0 0 3px rgba(255, 124, 50, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'"
                                   required>
                        </div>
                        <div>
                            <label for="sector" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Sector Económico</label>
                            <input type="text" id="sector" name="sector" 
                                   style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                                   placeholder="Ej: Transporte y logística"
                                   onfocus="this.style.borderColor='#FF7C32'; this.style.boxShadow='0 0 0 3px rgba(255, 124, 50, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        </div>
                        <div>
                            <label for="address" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Dirección</label>
                            <input type="text" id="address" name="address" 
                                   style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                                   placeholder="Dirección de la empresa"
                                   onfocus="this.style.borderColor='#FF7C32'; this.style.boxShadow='0 0 0 3px rgba(255, 124, 50, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        </div>
                    </div>
                </div>

                <!-- Información de contacto -->
                <div style="background: linear-gradient(135deg, #f0f9ff, #e0f2fe); padding: 1.5rem; border-radius: 16px; border: 1px solid #bae6fd;">
                    <h3 style="font-size: 1.1rem; font-weight: 600; color: #1e293b; margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                        <svg style="width: 1.25rem; height: 1.25rem; color: #0ea5e9;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Información de Contacto
                    </h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label for="email" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Email *</label>
                            <input type="email" id="email" name="email" 
                                   style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                                   placeholder="contacto@empresa.com"
                                   onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'"
                                   required>
                        </div>
                        <div>
                            <label for="phone" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Teléfono</label>
                            <input type="tel" id="phone" name="phone" 
                                   style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                                   placeholder="+57 300 123 4567"
                                   onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        </div>
                        <div>
                            <label for="contact" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Persona de Contacto</label>
                            <input type="text" id="contact" name="contact" 
                                   style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                                   placeholder="Nombre del contacto"
                                   onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        </div>
                        <div>
                            <label for="position" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Cargo</label>
                            <input type="text" id="position" name="position" 
                                   style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                                   placeholder="Gerente Comercial"
                                   onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        </div>
                        <div>
                            <label for="city" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Ciudad</label>
                            <input type="text" id="city" name="city" 
                                   style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                                   placeholder="Bogotá, Medellín, Cali..."
                                   onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        </div>
                        <div>
                            <label for="projected_value" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Valor Proyectado</label>
                            <input type="text" id="projected_value" name="projected_value" 
                                   style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white;"
                                   placeholder="$3.000.000 COP"
                                   onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div style="display: flex; justify-content: flex-end; gap: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                    <button type="button" onclick="closeCreateContactModal()" 
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

@keyframes modalSlideOut {
    from {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
    to {
        opacity: 0;
        transform: scale(0.95) translateY(-20px);
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
