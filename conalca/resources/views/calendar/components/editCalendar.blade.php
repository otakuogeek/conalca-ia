@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* Aplicar Product Sans al modal de edición */
        #myModal, #myModal *,
        #deleteModal, #deleteModal *,
        #modalContent, #modalContent *,
        form, form *,
        input, textarea, select, button, label {
            font-family: 'Product Sans' !important;
            font-weight: 600 !important;
        }
        
        /* Títulos en bold */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Product Sans' !important;
            font-weight: 700 !important;
        }
        
        /* Botones en medium weight */
        button, .btn {
            font-family: 'Product Sans' !important;
            font-weight: 600 !important;
        }
        
        /* Flatpickr styling */
        .flatpickr-day:hover, .flatpickr-day.selected, .flatpickr-time input:focus {
            font-family: 'Product Sans' !important;
        }
        
        .flatpickr-calendar * {
            font-family: 'Product Sans' !important;
        }
        
        /* Alert overrides */
        .alert, .alert * {
            font-family: 'Product Sans' !important;
        }
        
        /* Radio button styling */
        .radio {
            width: 20px;
            height: 20px;
        }
        
        .radio:checked {
            background-color: currentColor;
        }
        
        .radio-info:checked {
            background-color: #3b82f6;
        }
        
        .radio-success:checked {
            background-color: #10b981;
        }
        
        .radio-warning:checked {
            background-color: #f59e0b;
        }
        
        .radio-error:checked {
            background-color: #ef4444;
        }
        
        select:focus, input:focus, textarea:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.1);
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endpush
<div id="myModal" class="hidden overflow-auto fixed top-0 left-0 w-full h-full bg-[rgba(0,0,0,0.4)] z-50" style="font-family: 'Product Sans' !important;">
    <form id="modalContent" class="absolute bg-white w-[32rem] rounded-2xl shadow-lg text-[#222B45] p-8"
        action="{{ route('calendar.update') }}" method="POST" style="font-family: 'Product Sans' !important;">
        @csrf
        @method('PUT')
        <input type="hidden" id="edit_event_id" name="edit_event_id" />
        <div class="flex w-full items-center justify-between mb-4" style="font-family: 'Product Sans' !important;">
            <div class="flex flex-row gap-3" style="font-family: 'Product Sans' !important;">
                <label><input type="radio" id="edit_blue" name="edit_appointment_type" class="radio radio-info"
                    value="blue" style="font-family: 'Product Sans' !important;" /></label>
                <label><input type="radio" id="edit_green" name="edit_appointment_type" class="radio radio-success"
                    value="green" style="font-family: 'Product Sans' !important;" /></label>
                <label><input type="radio" id="edit_yellow" name="edit_appointment_type" class="radio radio-warning"
                    value="yellow" style="font-family: 'Product Sans' !important;" /></label>
                <label><input type="radio" id="edit_red" name="edit_appointment_type" class="radio radio-error"
                    value="red" style="font-family: 'Product Sans' !important;" /></label>
                <h5 class="text-lg font-semibold ml-4" style="font-family: 'Product Sans' !important; font-weight: 700;">Editar evento</h5>
            </div>
            <button id="close_modal" type="button" onclick="closeModal()"
                class="hover:bg-gray-100 rounded-full p-1" style="font-family: 'Product Sans' !important;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-7 h-7">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button>
        </div>
        <hr class="mb-6" />
        <div class="flex flex-col gap-4" style="font-family: 'Product Sans' !important;">
            <div class="flex gap-4">
                <div class="flex flex-col flex-1">
                    <label class="text-xs text-gray-500 mb-1" style="font-family: 'Product Sans' !important;">Título</label>
                    <input type="text" id="edit_title" name="edit_title"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" style="font-family: 'Product Sans' !important;" required />
                </div>
            </div>
            <div class="flex gap-4">
                <div class="flex flex-col flex-1">
                    <label class="text-xs text-gray-500 mb-1" style="font-family: 'Product Sans' !important;">Fecha inicio</label>
                    <input type="date" id="edit_start_date" name="edit_start_date"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" style="font-family: 'Product Sans' !important;" required />
                </div>
                <div class="flex flex-col flex-1">
                    <label class="text-xs text-gray-500 mb-1" style="font-family: 'Product Sans' !important;">Hora inicio</label>
                    <input type="time" id="edit_start_time" name="edit_start_time"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" style="font-family: 'Product Sans' !important;" required />
                </div>
            </div>
            <div class="flex gap-4">
                <div class="flex flex-col flex-1">
                    <label class="text-xs text-gray-500 mb-1" style="font-family: 'Product Sans' !important;">Fecha fin</label>
                    <input type="date" id="edit_end_date" name="edit_end_date"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" style="font-family: 'Product Sans' !important;" />
                </div>
                <div class="flex flex-col flex-1">
                    <label class="text-xs text-gray-500 mb-1" style="font-family: 'Product Sans' !important;">Hora fin</label>
                    <input type="time" id="edit_end_time" name="edit_end_time"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" style="font-family: 'Product Sans' !important;" />
                </div>
            </div>
            <div class="flex flex-col">
                <label class="text-xs text-gray-500 mb-1" style="font-family: 'Product Sans' !important;">Notas</label>
                <textarea id="edit_notes" name="edit_notes"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                    style="font-family: 'Product Sans' !important;" rows="2"></textarea>
            </div>
        </div>
        <div class="flex justify-between items-center mt-6" style="font-family: 'Product Sans' !important;">
            <div class="flex gap-2">
                <button type="button" onclick="showDeleteConfirmation()" 
                    class="bg-red-500 hover:bg-red-600 text-white rounded-lg px-4 py-2 font-semibold flex items-center gap-2"
                    style="font-family: 'Product Sans' !important; font-weight: 500;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                    Eliminar
                </button>
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="closeModal()"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg px-6 py-2 font-semibold"
                    style="font-family: 'Product Sans' !important; font-weight: 500;">Cancelar</button>
                <button type="submit"
                    class="bg-orange-500 hover:bg-orange-600 text-white rounded-lg px-6 py-2 font-semibold"
                    style="font-family: 'Product Sans' !important; font-weight: 500;">Guardar</button>
            </div>
        </div>
    </form>
</div>

<!-- Modal de confirmación de eliminación -->
<div id="deleteModal" class="hidden overflow-auto fixed top-0 left-0 w-full h-full bg-[rgba(0,0,0,0.5)] z-50" style="font-family: 'Product Sans', sans-serif !important;">
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white w-96 rounded-2xl shadow-xl p-6" style="font-family: 'Product Sans', sans-serif !important;">
        <div class="text-center" style="font-family: 'Product Sans', sans-serif !important;">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2" style="font-family: 'Product Sans', sans-serif !important; font-weight: 700;">Confirmar eliminación</h3>
            <p class="text-sm text-gray-500 mb-6" style="font-family: 'Product Sans', sans-serif !important; font-weight: 400;">¿Estás seguro de que quieres eliminar este evento/tarea? Esta acción no se puede deshacer.</p>
            <div class="flex justify-center gap-3">
                <button type="button" onclick="closeDeleteModal()" 
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg px-4 py-2 font-semibold"
                    style="font-family: 'Product Sans', sans-serif !important; font-weight: 500;">
                    Cancelar
                </button>
                <button type="button" onclick="confirmDelete()" 
                    class="bg-red-500 hover:bg-red-600 text-white rounded-lg px-4 py-2 font-semibold"
                    style="font-family: 'Product Sans', sans-serif !important; font-weight: 500;">
                    Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Form oculto para eliminar -->
<form id="deleteForm" action="{{ route('calendar.destroy') }}" method="POST" class="hidden">
    @csrf
    @method('DELETE')
    <input type="hidden" id="delete_event_id" name="delete_event_id" />
</form>

<script>
function showDeleteConfirmation() {
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

function confirmDelete() {
    // Copiar el ID del evento del modal de edición al formulario de eliminación
    const eventId = document.getElementById('edit_event_id').value;
    document.getElementById('delete_event_id').value = eventId;
    
    // Enviar el formulario de eliminación
    document.getElementById('deleteForm').submit();
}

// Cerrar modal de confirmación al hacer clic fuera
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>
