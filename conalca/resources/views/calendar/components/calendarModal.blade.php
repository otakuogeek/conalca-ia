@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* Aplicar Product Sans al modal */
        .modal, .modal *, 
        dialog, dialog *,
        #my_modal_5, #my_modal_5 *,
        form, form *,
        input, textarea, select, button, label {
            font-family: 'Product Sans', 'Segoe UI', system-ui, -apple-system, sans-serif !important;
            font-weight: 500 !important;
        }
        
        /* Títulos en bold */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Product Sans', 'Segoe UI', system-ui, -apple-system, sans-serif !important;
            font-weight: 700 !important;
        }
        
        /* Botones en medium weight */
        button, .btn {
            font-family: 'Product Sans', 'Segoe UI', system-ui, -apple-system, sans-serif !important;
            font-weight: 600 !important;
        }
        
        .modal-tab-btn {
            transition: all 0.2s ease;
            font-weight: 600 !important;
            font-family: 'Product Sans', sans-serif !important;
        }
        
        .modal-tab-btn:hover {
            background: white !important;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
        }
        
        .modal-tab-btn.active {
            background: #f97316 !important;
            color: white !important;
        }
        
        /* Flatpickr styling */
        .flatpickr-day:hover, .flatpickr-day.selected, .flatpickr-time input:focus {
            font-family: 'Product Sans', sans-serif !important;
        }
        
        .flatpickr-calendar * {
            font-family: 'Product Sans', sans-serif !important;
        }
        
        /* Alert overrides */
        .alert, .alert * {
            font-family: 'Product Sans', sans-serif !important;
        }
        
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
        
        .checkbox {
            width: 18px;
            height: 18px;
        }
        
        .checkbox:checked {
            background-color: #f97316;
            border-color: #f97316;
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
    <script>
        function switchTab(tabType) {
            // Actualizar tabs
            document.querySelectorAll('.modal-tab-btn').forEach(btn => {
                btn.classList.remove('text-white', 'bg-orange-500');
                btn.classList.add('text-gray-600', 'hover:text-gray-900', 'hover:bg-white');
            });
            
            // Mostrar/ocultar formularios
            if (tabType === 'event') {
                document.getElementById('event-tab').classList.remove('text-gray-600', 'hover:text-gray-900', 'hover:bg-white');
                document.getElementById('event-tab').classList.add('text-white', 'bg-orange-500');
                document.getElementById('event-form').classList.remove('hidden');
                document.getElementById('task-form').classList.add('hidden');
                document.getElementById('modal-title').textContent = 'Nuevo evento';
            } else {
                document.getElementById('task-tab').classList.remove('text-gray-600', 'hover:text-gray-900', 'hover:bg-white');
                document.getElementById('task-tab').classList.add('text-white', 'bg-orange-500');
                document.getElementById('task-form').classList.remove('hidden');
                document.getElementById('event-form').classList.add('hidden');
                document.getElementById('modal-title').textContent = 'Nueva tarea';
            }
        }
        
        // Validación del formulario de eventos
        function validateEventForm() {
            const title = document.getElementById('title').value.trim();
            const client = document.getElementById('client').value.trim();
            const startDate = document.getElementById('start_date').value;
            const startTime = document.getElementById('start_time').value;
            
            if (!title) {
                alert('El título del evento es requerido');
                return false;
            }
            if (!client) {
                alert('El cliente es requerido');
                return false;
            }
            if (!startDate) {
                alert('La fecha de inicio es requerida');
                return false;
            }
            if (!startTime) {
                alert('La hora de inicio es requerida');
                return false;
            }
            
            // Validar que la fecha de fin no sea anterior a la de inicio
            const endDate = document.getElementById('end_date').value;
            if (endDate && endDate < startDate) {
                alert('La fecha de fin no puede ser anterior a la fecha de inicio');
                return false;
            }
            
            return true;
        }
        
        // Validación del formulario de tareas
        function validateTaskForm() {
            const title = document.getElementById('task_title').value.trim();
            const dueDate = document.getElementById('task_due_date').value;
            
            if (!title) {
                alert('El título de la tarea es requerido');
                return false;
            }
            if (!dueDate) {
                alert('La fecha límite es requerida');
                return false;
            }
            
            return true;
        }
        
        // Agregar event listeners cuando se cargue el DOM
        document.addEventListener('DOMContentLoaded', function() {
            // Validación para formulario de eventos
            document.getElementById('event-form').addEventListener('submit', function(e) {
                if (!validateEventForm()) {
                    e.preventDefault();
                }
            });
            
            // Validación para formulario de tareas
            document.getElementById('task-form').addEventListener('submit', function(e) {
                if (!validateTaskForm()) {
                    e.preventDefault();
                }
            });
            
            // Auto-llenar fecha de fin si no se especifica
            document.getElementById('start_date').addEventListener('change', function() {
                const endDateField = document.getElementById('end_date');
                if (!endDateField.value) {
                    endDateField.value = this.value;
                }
            });
            
            // Auto-llenar hora de fin si no se especifica
            document.getElementById('start_time').addEventListener('change', function() {
                const endTimeField = document.getElementById('end_time');
                if (!endTimeField.value) {
                    // Agregar 1 hora por defecto
                    const startTime = new Date('2000-01-01 ' + this.value);
                    startTime.setHours(startTime.getHours() + 1);
                    const endTime = startTime.toTimeString().slice(0, 5);
                    endTimeField.value = endTime;
                }
            });
        });
        
        // Resetear formularios cuando se cierre el modal
        document.getElementById('my_modal_5').addEventListener('close', function() {
            // Resetear a tab de evento
            switchTab('event');
            
            // Limpiar formularios
            document.getElementById('event-form').reset();
            document.getElementById('task-form').reset();
            
            // Resetear colores seleccionados
            document.getElementById('blue').checked = true;
            document.getElementById('task_blue').checked = true;
        });
    </script>
@endpush
<dialog id="my_modal_5" class="modal" style="font-family: 'Product Sans', sans-serif !important;">
    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0" style="font-family: 'Product Sans', sans-serif !important;">
        <div class="flex flex-col w-full md:w-[44rem] bg-white border border-solid border-[#eff4fa] items-center text-[#232323] rounded-2xl shadow-lg relative p-8" style="font-family: 'Product Sans', sans-serif !important;">
            <div class="flex w-full items-center justify-between mb-4" style="font-family: 'Product Sans', sans-serif !important;">
                <h3 class="text-[#222b45] text-lg font-semibold" id="modal-title" style="font-family: 'Product Sans', sans-serif !important; font-weight: 700;">Nuevo evento</h3>
                <form method="dialog" style="font-family: 'Product Sans', sans-serif !important;">
                    <button type="submit" class="hover:bg-gray-100 rounded-full p-1" style="font-family: 'Product Sans', sans-serif !important;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M13.4142 12L17.7072 7.70701C18.0982 7.31601 18.0982 6.68401 17.7072 6.29301C17.3162 5.90201 16.6842 5.90201 16.2933 6.29301L12.0002 10.586L7.70725 6.29301C7.31625 5.90201 6.68425 5.90201 6.29325 6.29301C5.90225 6.68401 5.90225 7.31601 6.29325 7.70701L10.5862 12L6.29325 16.293C5.90225 16.684 5.90225 17.316 6.29325 17.707C6.48825 17.902 6.74425 18 7.00025 18C7.25625 18 7.51225 17.902 7.70725 17.707L12.0002 13.414L16.2933 17.707C16.4882 17.902 16.7443 18 17.0002 18C17.2562 18 17.5122 17.902 17.7072 17.707C18.0982 17.316 18.0982 16.684 17.7072 16.293L13.4142 12Z" fill="#18203A" />
                        </svg>
                    </button>
                </form>
            </div>
            
            <!-- Tabs para Evento/Tarea -->
            <div class="flex w-full mb-6">
                <div class="flex gap-1 bg-gray-100 rounded-lg p-1 w-full">
                    <button type="button" id="event-tab" class="modal-tab-btn flex-1 px-4 py-2 text-sm font-medium text-white bg-orange-500 rounded-md transition-colors" onclick="switchTab('event')">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Evento
                    </button>
                    <button type="button" id="task-tab" class="modal-tab-btn flex-1 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-white rounded-md transition-colors" onclick="switchTab('task')">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                        Tarea
                    </button>
                </div>
            </div>
            
            <hr class="mb-6 w-full" />
            <!-- Formulario para Evento -->
            <form id="event-form" action="{{ route('calendar.store') }}" method="POST" class="w-full flex flex-col gap-4">
                <div class="flex w-full items-center justify-between mb-2">
                    <h5 class="text-sm font-normal text-[#c5cee0]">Información del evento</h5>
                    <div class="flex items-center gap-3">
                        <label><input type="radio" id="blue" name="appointment_type" class="radio radio-info" value="blue" checked /></label>
                        <label><input type="radio" id="green" name="appointment_type" class="radio radio-success" value="green" /></label>
                        <label><input type="radio" id="yellow" name="appointment_type" class="radio radio-warning" value="yellow" /></label>
                        <label><input type="radio" id="red" name="appointment_type" class="radio radio-error" value="red" /></label>
                    </div>
                </div>
                @csrf
                <input type="hidden" name="item_type" value="event" />
                <div class="flex flex-col gap-4">
                    <input type="text" id="title" name="title" placeholder="Título del evento*" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm placeholder:text-[#8f9bb3]" />
                    <input type="text" id="client" name="client" placeholder="Cliente*" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm placeholder:text-[#8f9bb3]" />
                    <div class="flex gap-4">
                        <div class="flex flex-col flex-1">
                            <label class="text-xs text-gray-500 mb-1">Fecha inicio</label>
                            <input type="date" id="start_date" name="start_date" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required />
                        </div>
                        <div class="flex flex-col flex-1">
                            <label class="text-xs text-gray-500 mb-1">Hora inicio</label>
                            <input type="time" id="start_time" name="start_time" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required />
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex flex-col flex-1">
                            <label class="text-xs text-gray-500 mb-1">Fecha fin</label>
                            <input type="date" id="end_date" name="end_date" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                        </div>
                        <div class="flex flex-col flex-1">
                            <label class="text-xs text-gray-500 mb-1">Hora fin</label>
                            <input type="time" id="end_time" name="end_time" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <textarea id="notes" name="notes" placeholder="Descripción del evento" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" rows="2"></textarea>
                    <input type="hidden" name="notify" value="0" />
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white rounded-lg px-6 py-2 font-semibold">Crear evento</button>
                    <button type="button" onclick="my_modal_5.close()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg px-6 py-2 font-semibold">Cancelar</button>
                </div>
            </form>

            <!-- Formulario para Tarea -->
            <form id="task-form" action="{{ route('calendar.store') }}" method="POST" class="w-full flex flex-col gap-4 hidden">
                <div class="flex w-full items-center justify-between mb-2">
                    <h5 class="text-sm font-normal text-[#c5cee0]">Información de la tarea</h5>
                    <div class="flex items-center gap-3">
                        <label><input type="radio" id="task_blue" name="appointment_type" class="radio radio-info" value="blue" checked /></label>
                        <label><input type="radio" id="task_green" name="appointment_type" class="radio radio-success" value="green" /></label>
                        <label><input type="radio" id="task_yellow" name="appointment_type" class="radio radio-warning" value="yellow" /></label>
                        <label><input type="radio" id="task_red" name="appointment_type" class="radio radio-error" value="red" /></label>
                    </div>
                </div>
                @csrf
                <input type="hidden" name="item_type" value="task" />
                <div class="flex flex-col gap-4">
                    <input type="text" id="task_title" name="title" placeholder="Título de la tarea*" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm placeholder:text-[#8f9bb3]" />
                    <div class="flex gap-4">
                        <div class="flex flex-col flex-1">
                            <label class="text-xs text-gray-500 mb-1">Fecha límite</label>
                            <input type="date" id="task_due_date" name="start_date" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" required />
                        </div>
                        <div class="flex flex-col flex-1">
                            <label class="text-xs text-gray-500 mb-1">Hora límite</label>
                            <input type="time" id="task_due_time" name="start_time" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex flex-col flex-1">
                            <label class="text-xs text-gray-500 mb-1">Prioridad</label>
                            <select id="task_priority" name="priority" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                <option value="low">Baja</option>
                                <option value="medium" selected>Media</option>
                                <option value="high">Alta</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2 mt-4">
                            <input type="checkbox" id="task_completed" name="completed" class="checkbox checkbox-sm" />
                            <label for="task_completed" class="text-sm text-gray-700">Completada</label>
                        </div>
                    </div>
                    <textarea id="task_notes" name="notes" placeholder="Descripción de la tarea" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" rows="2"></textarea>
                    <input type="hidden" name="notify" value="0" />
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white rounded-lg px-6 py-2 font-semibold">Crear tarea</button>
                    <button type="button" onclick="my_modal_5.close()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg px-6 py-2 font-semibold">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</dialog>
