{{-- Modal para asignar usuarios a clientes --}}
<div id="assignModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto;">
        {{-- Header --}}
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 1rem;">
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600; color: #1f2937;">Asignar Usuarios al Cliente</h3>
            <button type="button" onclick="closeAssignModal()" style="background: none; border: none; font-size: 1.5rem; color: #6b7280; cursor: pointer; padding: 0; line-height: 1;">
                ×
            </button>
        </div>

        {{-- Form para asignar usuario --}}
        @if(auth()->user()->hasRole(['SUPER ADMIN', 'JEFE COMERCIAL']))
        <div style="margin-bottom: 2rem;">
            <h4 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 500; color: #374151;">Asignar Nuevo Usuario</h4>
            
            <div style="display: flex; gap: 0.75rem; align-items: end;">
                <div style="flex: 1;">
                    <label for="userSelect" style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #374151;">
                        Usuario Comercial
                    </label>
                    <select id="userSelect" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; background: white;">
                        <option value="">Seleccionar usuario...</option>
                        @foreach(\App\Models\User::role(['GERENTE DE CUENTA', 'ASISTENTE COMERCIAL'])->get() as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" onclick="assignUser()" style="padding: 0.75rem 1.5rem; background: #059669; color: white; border: none; border-radius: 6px; font-size: 0.875rem; font-weight: 500; cursor: pointer; white-space: nowrap;">
                    Asignar
                </button>
            </div>
        </div>
        @endif

        {{-- Lista de usuarios asignados --}}
        <div>
            <h4 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 500; color: #374151;">Usuarios Asignados</h4>
            <div id="assignedUsersContainer" style="min-height: 100px; padding: 1rem; background: #f9fafb; border-radius: 6px; border: 1px solid #e5e7eb;">
                <p style="color: #6b7280; font-size: 0.875rem; margin: 0; text-align: center;">Cargando...</p>
            </div>
        </div>

        {{-- Footer --}}
        <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; text-align: right;">
            <button type="button" onclick="closeAssignModal()" style="padding: 0.75rem 1.5rem; background: #6b7280; color: white; border: none; border-radius: 6px; font-size: 0.875rem; font-weight: 500; cursor: pointer;">
                Cerrar
            </button>
        </div>
    </div>
</div>

<style>
/* Estilos para el modal */
#assignModal button:hover {
    opacity: 0.9;
    transform: translateY(-1px);
    transition: all 0.2s ease;
}

#assignModal select:focus {
    outline: none;
    border-color: #059669;
    box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}

/* Animación de entrada */
#assignModal {
    animation: fadeIn 0.2s ease-out;
}

#assignModal > div {
    animation: slideIn 0.2s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { 
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
    to { 
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
</style>
