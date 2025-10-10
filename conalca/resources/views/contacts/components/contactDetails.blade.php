{{-- Include Product Sans font --}}
<link href="https://fonts.googleapis.com/css2?family=Product+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<div style="font-family: 'Product Sans', sans-serif; padding: 1.5rem; background: white; border-radius: 19px; height: 100%; overflow-y: auto;">
    <!-- Header del contacto -->
    <div style="background: linear-gradient(135deg, #FF7C32, #FF9A56); padding: 1.5rem; border-radius: 16px; margin-bottom: 1.5rem; position: relative; overflow: hidden;">
        <div style='position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: url("data:image/svg+xml,<svg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27><defs><pattern id=%27grain%27 width=%27100%27 height=%27100%27 patternUnits=%27userSpaceOnUse%27><circle cx=%2750%27 cy=%2750%27 r=%271%27 fill=%27%23ffffff%27 opacity=%270.05%27/></pattern></defs><rect width=%27100%27 height=%27100%27 fill=%27url(%23grain)%27/></svg>"); opacity: 0.3;'></div>
        <div style="position: relative; z-index: 1;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <svg style="width: 1.5rem; height: 1.5rem; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <span style="color: rgba(255, 255, 255, 0.8); font-size: 0.875rem; font-weight: 500;">INFORMACIÓN DEL CLIENTE</span>
            </div>
            <h2 id="client_company" style="color: white; font-size: 1.5rem; font-weight: 700; margin: 0; text-shadow: 0 2px 4px rgba(0,0,0,0.1);"></h2>
        </div>
    </div>

    <form class="flex flex-col w-full" action="{{ route('contacts.update') }}" method="POST">
        @csrf
        <!-- Sección: Información de la Empresa -->
        <div style="background: linear-gradient(135deg, #f8fafc, #f1f5f9); padding: 1.5rem; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.1rem; font-weight: 600; color: #1e293b; margin: 0 0 1.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <svg style="width: 1.25rem; height: 1.25rem; color: #FF7C32;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                Información de la Empresa
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div>
                    <label for="nit_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        NIT @error('nit')<span style="color: #ef4444;">{{ $message }}</span>@enderror
                    </label>
                    <input type="text" id="nit_update" name="nit" placeholder="Ingrese el NIT"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white; @error('nit') border-color: #ef4444; @enderror"
                           value="{{ old('nit') }}"
                           onfocus="this.style.borderColor='#FF7C32'; this.style.boxShadow='0 0 0 3px rgba(255, 124, 50, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" />
                </div>
                <div>
                    <label for="sector_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Sector Económico @error('sector')<span style="color: #ef4444;">{{ $message }}</span>@enderror
                    </label>
                    <input type="text" id="sector_update" name="sector" placeholder="Ingrese el sector económico"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white; @error('sector') border-color: #ef4444; @enderror"
                           value="{{ old('sector') }}"
                           onfocus="this.style.borderColor='#FF7C32'; this.style.boxShadow='0 0 0 3px rgba(255, 124, 50, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" />
                </div>
                <div>
                    <label for="address_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Dirección @error('address')<span style="color: #ef4444;">{{ $message }}</span>@enderror
                    </label>
                    <input type="text" id="address_update" name="address" placeholder="Ingrese la dirección"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white; @error('address') border-color: #ef4444; @enderror"
                           value="{{ old('address') }}"
                           onfocus="this.style.borderColor='#FF7C32'; this.style.boxShadow='0 0 0 3px rgba(255, 124, 50, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" />
                </div>
                <div>
                    <label for="email_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Email @error('email')<span style="color: #ef4444;">{{ $message }}</span>@enderror
                    </label>
                    <input type="email" id="email_update" name="email" placeholder="email@empresa.com"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white; @error('email') border-color: #ef4444; @enderror"
                           value="{{ old('email') }}"
                           onfocus="this.style.borderColor='#FF7C32'; this.style.boxShadow='0 0 0 3px rgba(255, 124, 50, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" />
                </div>
                <div>
                    <label for="city_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Ciudad @error('city')<span style="color: #ef4444;">{{ $message }}</span>@enderror
                    </label>
                    <input type="text" id="city_update" name="city" placeholder="Ingrese la ciudad"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white; @error('city') border-color: #ef4444; @enderror"
                           value="{{ old('city') }}"
                           onfocus="this.style.borderColor='#FF7C32'; this.style.boxShadow='0 0 0 3px rgba(255, 124, 50, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" />
                </div>
                <div>
                    <label for="company_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Empresa @error('company')<span style="color: #ef4444;">{{ $message }}</span>@enderror
                    </label>
                    <input type="text" id="company_update" name="company" placeholder="Nombre de la empresa"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white; @error('company') border-color: #ef4444; @enderror"
                           value="{{ old('company') }}"
                           onfocus="this.style.borderColor='#FF7C32'; this.style.boxShadow='0 0 0 3px rgba(255, 124, 50, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" />
                </div>
            </div>
        </div>
        <!-- Sección: Información de Contacto -->
        <div style="background: linear-gradient(135deg, #f0f9ff, #e0f2fe); padding: 1.5rem; border-radius: 16px; border: 1px solid #bae6fd; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.1rem; font-weight: 600; color: #1e293b; margin: 0 0 1.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <svg style="width: 1.25rem; height: 1.25rem; color: #0ea5e9;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Información de Contacto
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div>
                    <label for="main_contact_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Contacto Principal @error('main_contact')<span style="color: #ef4444;">{{ $message }}</span>@enderror
                    </label>
                    <input type="text" id="main_contact_update" name="main_contact" placeholder="Nombre del contacto principal"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white; @error('main_contact') border-color: #ef4444; @enderror"
                           value="{{ old('main_contact') }}" required
                           onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" />
                </div>
                <div>
                    <label for="position_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Cargo @error('position')<span style="color: #ef4444;">{{ $message }}</span>@enderror
                    </label>
                    <input type="text" id="position_update" name="position" placeholder="Cargo en la empresa"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white; @error('position') border-color: #ef4444; @enderror"
                           value="{{ old('position') }}"
                           onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" />
                </div>
                <div>
                    <label for="contact_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Persona de Contacto @error('contact')<span style="color: #ef4444;">{{ $message }}</span>@enderror
                    </label>
                    <input type="text" id="contact_update" name="contact" placeholder="Nombre del contacto"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white; @error('contact') border-color: #ef4444; @enderror"
                           value="{{ old('contact') }}"
                           onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" />
                </div>
                <div>
                    <label for="phone_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Teléfono @error('phone')<span style="color: #ef4444;">{{ $message }}</span>@enderror
                    </label>
                    <input type="tel" id="phone_update" name="phone" placeholder="+57 300 123 4567"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white; @error('phone') border-color: #ef4444; @enderror"
                           value="{{ old('phone') }}"
                           onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" />
                </div>
                <div>
                    <label for="contact_title_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Título del Contacto @error('contact_title')<span style="color: #ef4444;">{{ $message }}</span>@enderror
                    </label>
                    <input type="text" id="contact_title_update" name="contact_title" placeholder="Sr., Sra., Dr., Ing."
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white; @error('contact_title') border-color: #ef4444; @enderror"
                           value="{{ old('contact_title') }}"
                           onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" />
                </div>
                <div>
                    <label for="document_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Documento @error('document')<span style="color: #ef4444;">{{ $message }}</span>@enderror
                    </label>
                    <input type="text" id="document_update" name="document" placeholder="Documento del contacto"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white; @error('document') border-color: #ef4444; @enderror"
                           value="{{ old('document') }}"
                           onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" />
                </div>
                <div>
                    <label for="address_2_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Dirección Secundaria @error('address_2')<span style="color: #ef4444;">{{ $message }}</span>@enderror
                    </label>
                    <input type="text" id="address_2_update" name="address_2" placeholder="Dirección secundaria"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white; @error('address_2') border-color: #ef4444; @enderror"
                           value="{{ old('address_2') }}"
                           onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" />
                </div>
                <div>
                    <label for="email_2_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Email Secundario @error('email_2')<span style="color: #ef4444;">{{ $message }}</span>@enderror
                    </label>
                    <input type="email" id="email_2_update" name="email_2" placeholder="email2@empresa.com"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white; @error('email_2') border-color: #ef4444; @enderror"
                           value="{{ old('email_2') }}"
                           onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" />
                </div>
                <div>
                    <label for="projected_value_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Valor Proyectado @error('projected_value')<span style="color: #ef4444;">{{ $message }}</span>@enderror
                    </label>
                    <input type="text" id="projected_value_update" name="projected_value" placeholder="$3.000.000 COP"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white; @error('projected_value') border-color: #ef4444; @enderror"
                           value="{{ old('projected_value') }}"
                           onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" />
                </div>
                <div>
                    <label for="date_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Fecha de Ejecución @error('date')<span style="color: #ef4444;">{{ $message }}</span>@enderror
                    </label>
                    <input type="date" id="date_update" name="date"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white; @error('date') border-color: #ef4444; @enderror"
                           value="{{ old('date') }}"
                           onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" />
                </div>
                <div>
                    <label for="city_2_update" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Ciudad Secundaria @error('city_2')<span style="color: #ef4444;">{{ $message }}</span>@enderror
                    </label>
                    <input type="text" id="city_2_update" name="city_2" placeholder="Segunda ciudad"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-size: 0.95rem; transition: all 0.2s; background: white; @error('city_2') border-color: #ef4444; @enderror"
                           value="{{ old('city_2') }}"
                           onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'" />
                </div>
            </div>
        </div>
        <input type="hidden" id="id_update" name="id_contact" value="" />
        
        <!-- Botón de actualización -->
        <button type="submit" 
                style="width: 100%; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #FF7C32, #FF9A56); color: white; border: none; border-radius: 12px; cursor: pointer; font-size: 0.95rem; font-weight: 600; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem; box-shadow: 0 4px 12px rgba(255, 124, 50, 0.3); margin-bottom: 1.5rem;"
                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(255, 124, 50, 0.4)'"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(255, 124, 50, 0.3)'">
            <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Actualizar Contacto
        </button>
    </form>

    <!-- Sección: Documentos -->
    <div style="background: linear-gradient(135deg, #fef3c7, #fde68a); padding: 1.5rem; border-radius: 16px; border: 1px solid #f59e0b; margin-bottom: 1.5rem;">
        <h3 style="font-size: 1.1rem; font-weight: 600; color: #1e293b; margin: 0 0 1.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
            <svg style="width: 1.25rem; height: 1.25rem; color: #f59e0b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Documentos
        </h3>
        
        <!-- Mensaje de éxito -->
        @if(session('success'))
            <div style="background: #d1fae5; border: 1px solid #10b981; border-radius: 8px; padding: 0.75rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <svg style="width: 1rem; height: 1rem; color: #10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span style="color: #065f46; font-size: 0.875rem; font-weight: 500;">{{ session('success') }}</span>
            </div>
        @endif
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: start;">
            <div>
                <form action="{{ route('document.store') }}" method="POST" enctype="multipart/form-data"
                      style="background: white; padding: 1rem; border-radius: 12px; border: 2px dashed #d1d5db; display: flex; flex-direction: column; gap: 1rem;"
                      id="pdf-upload-form">
                    @csrf
                    <input type="text" id="contact_id_document" name="contact_id" style="display: none;"/>
                    <label for="pdf-input" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        Seleccionar Documentos (PDF)
                    </label>
                    <div style="position: relative;">
                        <input type="file" id="pdf-input" name="files[]" accept="application/pdf" multiple required
                               style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem; cursor: pointer;"
                               onchange="showFileNamesForm()" />
                        <div style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); pointer-events: none;">
                            <svg style="width: 1.25rem; height: 1.25rem; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                        </div>
                    </div>
                    <p style="font-size: 0.75rem; color: #6b7280; margin-top: 0.5rem; margin-bottom: 0;">
                        💡 Después de seleccionar los archivos, podrás asignar un nombre personalizado a cada documento.
                    </p>
                    
                    <!-- Container para los nombres de archivos -->
                    <div id="file-names-container" style="display: none;"></div>
                    
                    <button type="submit" id="upload-btn" style="display: none; padding: 0.75rem 1rem; background: linear-gradient(135deg, #f59e0b, #f97316); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 0.875rem; font-weight: 600; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem;"
                            onclick="return validateFileNames()"
                            onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(245, 158, 11, 0.3)'"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                        <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        Subir Documentos
                    </button>
                </form>
                
                <div style="margin-top: 1rem;">
                    <h4 style="font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.75rem;">Documentos Subidos</h4>
                    <div id="uploaded-documents" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 0.75rem; max-height: 200px; overflow-y: auto; padding: 0.5rem; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;"></div>
                </div>
            </div>
            
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.75rem;">Documentos por Completar</h4>
                <div id="pending-documents" style="background: #f3f4f6; padding: 1rem; border-radius: 12px; border: 1px solid #d1d5db; max-height: 300px; overflow-y: auto;">
                    <ul id="document-list" style="list-style: none; padding: 0; margin: 0; color: #6b7280; font-size: 0.875rem;"></ul>
                </div>
            </div>
        </div>
    </div>
    <!-- Sección: Historial -->
    <div style="background: linear-gradient(135deg, #f0f4f8, #e2e8f0); padding: 1.5rem; border-radius: 16px; border: 1px solid #cbd5e1;">
        <h3 style="font-size: 1.1rem; font-weight: 600; color: #1e293b; margin: 0 0 1.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
            <svg style="width: 1.25rem; height: 1.25rem; color: #64748b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Historial
        </h3>
        <div style="background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 2.5rem; height: 2.5rem; background: linear-gradient(135deg, #FF7C32, #FF9A56); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 1.25rem; height: 1.25rem; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <h5 style="font-size: 0.875rem; font-weight: 600; color: #6b7280; margin: 0; text-transform: uppercase;">COTIZACIÓN</h5>
                    <h5 style="font-size: 0.875rem; font-weight: 600; color: #1f2937; margin: 0.25rem 0; text-transform: uppercase;">| EMPRESA</h5>
                    <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">Jun 8, 8:30am | Roladmin</p>
                </div>
            </div>
            <button style="padding: 0.5rem 1rem; background: linear-gradient(135deg, #FF7C32, #FF9A56); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 0.875rem; font-weight: 600; transition: all 0.2s; display: flex; align-items: center; gap: 0.5rem;"
                    onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(255, 124, 50, 0.3)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                Ver más
            </button>
        </div>
    </div>
</div>
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

/* Estilo para preview de archivos */
.file-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.5rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    transition: all 0.2s;
}

.file-preview:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}
</style>

<script>
    function showFileNamesForm() {
        const input = document.getElementById('pdf-input');
        const container = document.getElementById('file-names-container');
        const uploadBtn = document.getElementById('upload-btn');
        
        container.innerHTML = '';
        
        if (input.files.length === 0) {
            container.style.display = 'none';
            uploadBtn.style.display = 'none';
            return;
        }
        
        container.style.display = 'block';
        uploadBtn.style.display = 'flex';
        
        // Crear título para la sección
        const title = document.createElement('h4');
        title.style.cssText = 'font-size: 0.875rem; font-weight: 600; color: #374151; margin: 0 0 0.75rem 0;';
        title.textContent = 'Asignar nombres a los documentos:';
        container.appendChild(title);
        
        Array.from(input.files).forEach((file, index) => {
            if (file.type === 'application/pdf') {
                const fileItem = document.createElement('div');
                fileItem.style.cssText = 'display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 0.5rem;';
                
                // Icono del archivo
                const icon = document.createElement('div');
                icon.style.cssText = 'display: flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; background: #fef3c7; border-radius: 6px; flex-shrink: 0;';
                icon.innerHTML = `
                    <svg style="width: 1.25rem; height: 1.25rem; color: #f59e0b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                `;
                
                // Información del archivo
                const fileInfo = document.createElement('div');
                fileInfo.style.cssText = 'flex: 1; display: flex; flex-direction: column; gap: 0.25rem;';
                
                const originalName = document.createElement('span');
                originalName.style.cssText = 'font-size: 0.75rem; color: #6b7280; font-weight: 500;';
                originalName.textContent = `Archivo original: ${file.name}`;
                
                const customNameInput = document.createElement('input');
                customNameInput.type = 'text';
                customNameInput.name = `custom_names[${index}]`;
                customNameInput.placeholder = 'Nombre personalizado para el documento';
                customNameInput.value = file.name.replace('.pdf', '');
                customNameInput.style.cssText = 'width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; background: white; outline: none; transition: all 0.2s;';
                customNameInput.addEventListener('focus', function() {
                    this.style.borderColor = '#0ea5e9';
                    this.style.boxShadow = '0 0 0 2px rgba(14, 165, 233, 0.1)';
                });
                customNameInput.addEventListener('blur', function() {
                    this.style.borderColor = '#d1d5db';
                    this.style.boxShadow = 'none';
                });
                
                fileInfo.appendChild(originalName);
                fileInfo.appendChild(customNameInput);
                
                fileItem.appendChild(icon);
                fileItem.appendChild(fileInfo);
                container.appendChild(fileItem);
            }
        });
        
        // Actualizar preview
        previewFiles();
    }
    
    function previewFiles() {
        const input = document.getElementById('pdf-input');
        const previewContainer = document.getElementById('uploaded-documents');
        previewContainer.innerHTML = '';

        if (input.files.length === 0) {
            previewContainer.innerHTML = '<p style="color: #6b7280; text-align: center; grid-column: 1 / -1; padding: 1rem;">No hay archivos seleccionados</p>';
            return;
        }

        Array.from(input.files).forEach((file, index) => {
            if (file.type === 'application/pdf') {
                const filePreview = document.createElement('div');
                filePreview.className = 'file-preview';

                const icon = document.createElement('div');
                icon.style.cssText = 'display: flex; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; background: #fef3c7; border-radius: 8px; margin-bottom: 0.5rem;';
                
                icon.innerHTML = `
                    <svg style="width: 1.5rem; height: 1.5rem; color: #f59e0b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                `;

                const fileName = document.createElement('span');
                fileName.style.cssText = 'font-size: 0.75rem; color: #374151; text-align: center; line-height: 1.2; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;';
                
                // Obtener el nombre personalizado si existe
                const customNameInput = document.querySelector(`input[name="custom_names[${index}]"]`);
                const displayName = customNameInput ? customNameInput.value : file.name.replace('.pdf', '');
                
                fileName.textContent = displayName.length > 12 ? displayName.substring(0, 12) + '...' : displayName;
                fileName.title = displayName;

                filePreview.appendChild(icon);
                filePreview.appendChild(fileName);
                previewContainer.appendChild(filePreview);
            }
        });
    }
    
    function validateFileNames() {
        const nameInputs = document.querySelectorAll('input[name^="custom_names["]');
        let hasError = false;
        
        nameInputs.forEach(input => {
            if (input.value.trim() === '') {
                input.style.borderColor = '#ef4444';
                input.style.boxShadow = '0 0 0 2px rgba(239, 68, 68, 0.1)';
                hasError = true;
            } else {
                input.style.borderColor = '#d1d5db';
                input.style.boxShadow = 'none';
            }
        });
        
        if (hasError) {
            alert('Por favor, ingresa un nombre para todos los documentos antes de subirlos.');
            return false;
        }
        
        return true;
    }
    
    // Limpiar formulario después de subir archivos exitosamente
    @if(session('success'))
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const fileInput = document.getElementById('pdf-input');
                const container = document.getElementById('file-names-container');
                const uploadBtn = document.getElementById('upload-btn');
                const previewContainer = document.getElementById('uploaded-documents');
                
                fileInput.value = '';
                container.innerHTML = '';
                container.style.display = 'none';
                uploadBtn.style.display = 'none';
                previewContainer.innerHTML = '<p style="color: #6b7280; text-align: center; grid-column: 1 / -1; padding: 1rem;">No hay archivos seleccionados</p>';
            }, 2000);
        });
    @endif
    
    // Actualizar preview cuando se cambien los nombres
    document.addEventListener('input', function(e) {
        if (e.target.name && e.target.name.startsWith('custom_names[')) {
            // Remover el error visual si el usuario empieza a escribir
            if (e.target.value.trim() !== '') {
                e.target.style.borderColor = '#d1d5db';
                e.target.style.boxShadow = 'none';
            }
            previewFiles();
        }
    });
</script>
