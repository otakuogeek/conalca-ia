@extends('layout.app')

@section('title')
    {{ 'Calendario' }}
@endsection

@push('styles')
    <style>
        /* Definición mejorada de Product Sans */
        @font-face {
            font-family: 'Product Sans';
            src: url('{{ asset('fonts/ProductSansRegular.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Product Sans';
            src: url('{{ asset('fonts/ProductSansRegular.ttf') }}') format('truetype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Product Sans';
            src: url('{{ asset('fonts/ProductSansRegular.ttf') }}') format('truetype');
            font-weight: 500;
            font-style: normal;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Product Sans';
            src: url('{{ asset('fonts/ProductSansBold.ttf') }}') format('truetype');
            font-weight: 600;
            font-style: normal;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Product Sans';
            src: url('{{ asset('fonts/ProductSansBold.ttf') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Product Sans';
            src: url('{{ asset('fonts/ProductSansBold.ttf') }}') format('truetype');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Product Sans';
            src: url('{{ asset('fonts/ProductSansBold.ttf') }}') format('truetype');
            font-weight: 800;
            font-style: normal;
            font-display: swap;
        }
        
        /* Aplicar Product Sans globalmente con mayor especificidad */
        * {
            font-family: 'Product Sans', 'Segoe UI', system-ui, -apple-system, sans-serif !important;
        }
        
        body, html {
            font-family: 'Product Sans', 'Segoe UI', system-ui, -apple-system, sans-serif !important;
            font-weight: 500 !important;
        }
        
        /* Títulos y encabezados en bold */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Product Sans', 'Segoe UI', system-ui, -apple-system, sans-serif !important;
            font-weight: 700 !important;
        }
        
        /* Elementos específicos en bold */
        .font-semibold, .font-bold, .font-medium {
            font-family: 'Product Sans', 'Segoe UI', system-ui, -apple-system, sans-serif !important;
            font-weight: 600 !important;
        }
        
        /* Texto normal con peso medio para mejor legibilidad */
        p, span, div, label {
            font-family: 'Product Sans', 'Segoe UI', system-ui, -apple-system, sans-serif !important;
            font-weight: 500 !important;
        }
        
        /* Inputs con peso normal */
        input, textarea, select {
            font-family: 'Product Sans', 'Segoe UI', system-ui, -apple-system, sans-serif !important;
            font-weight: 500 !important;
        }
        
        /* Botones en medium weight */
        button, .btn {
            font-family: 'Product Sans', 'Segoe UI', system-ui, -apple-system, sans-serif !important;
            font-weight: 600 !important;
        }
        
        /* Aplicar específicamente a elementos del calendario */
        .calendar-container, .calendar-container * {
            font-family: 'Product Sans', 'Segoe UI', system-ui, -apple-system, sans-serif !important;
        }
        
        /* FullCalendar específico */
        .fc, .fc * {
            font-family: 'Product Sans', 'Segoe UI', system-ui, -apple-system, sans-serif !important;
        }
        
        /* Modales del calendario */
        .modal, .modal * {
            font-family: 'Product Sans', 'Segoe UI', system-ui, -apple-system, sans-serif !important;
        }
        
        /* Componentes específicos */
        .headCalendar, .headCalendar * {
            font-family: 'Product Sans', 'Segoe UI', system-ui, -apple-system, sans-serif !important;
        }
    </style>
@endpush

@push('scripts')
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/locales-all.min.js"></script>

    <script>
        let calendar, calendar2; // Variables globales para los calendarios
        
        // Función para cambiar la vista del calendario
        function changeCalendarView(viewType) {
            calendar.changeView(viewType);
            
            // Actualizar el estado activo de los tabs
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('text-white', 'bg-orange-500');
                btn.classList.add('text-gray-600', 'hover:text-gray-900', 'hover:bg-white');
            });
            
            event.target.classList.remove('text-gray-600', 'hover:text-gray-900', 'hover:bg-white');
            event.target.classList.add('text-white', 'bg-orange-500');
            
            // Limpiar y aplicar correctamente el resaltado de domingos después del cambio de vista
            setTimeout(() => {
                cleanupSundayHighlighting();
            }, 150);
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-ocultar notificaciones después de 5 segundos
            const successAlert = document.getElementById('success-alert');
            const errorAlert = document.getElementById('error-alert');
            const noResultsAlert = document.getElementById('no-results-alert');
            
            if (successAlert) {
                setTimeout(() => {
                    successAlert.style.opacity = '0';
                    setTimeout(() => successAlert.remove(), 300);
                }, 5000);
            }
            
            if (errorAlert) {
                setTimeout(() => {
                    errorAlert.style.opacity = '0';
                    setTimeout(() => errorAlert.remove(), 300);
                }, 7000);
            }
            
            if (noResultsAlert) {
                setTimeout(() => {
                    noResultsAlert.style.opacity = '0';
                    setTimeout(() => noResultsAlert.remove(), 300);
                }, 6000);
            }
            
            const calendarEl = document.getElementById('calendar');
            const myEvents = @json($events);

            // Helper functions for safe DOM manipulation - defined globally
            const safeCheck = (id) => {
                const element = document.getElementById(id);
                if (element) {
                    element.checked = true;
                    return true;
                }
                return false;
            };
            
            const safeSetValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || '';
                    return true;
                }
                return false;
            };

            // Días festivos de Colombia 2025
            const colombianHolidays = {
                '2025-01-01': 'Año Nuevo',
                '2025-01-06': 'Día de los Reyes Magos',
                '2025-03-24': 'Día de San José',
                '2025-04-17': 'Jueves Santo',
                '2025-04-18': 'Viernes Santo',
                '2025-05-01': 'Día del Trabajo',
                '2025-05-19': 'Ascensión del Señor',
                '2025-06-09': 'Corpus Christi',
                '2025-06-16': 'Sagrado Corazón de Jesús',
                '2025-06-23': 'San Pedro y San Pablo',
                '2025-07-20': 'Día de la Independencia',
                '2025-08-07': 'Batalla de Boyacá',
                '2025-08-18': 'Asunción de la Virgen',
                '2025-10-13': 'Día de la Raza',
                '2025-11-03': 'Todos los Santos',
                '2025-11-17': 'Independencia de Cartagena',
                '2025-12-08': 'Inmaculada Concepción',
                '2025-12-25': 'Navidad'
            };

            calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'es',
                timeZone: 'America/Bogota',
                height: "100%",
                initialView: "dayGridMonth",
                selectable: true,
                dayMaxEvents: 2, // Cambiar de 3 a 2 para mostrar el "+X más" antes
                moreLinkClick: 'popover',
                eventDisplay: 'block',
                firstDay: 1, // Lunes como primer día de la semana
                weekends: true,
                headerToolbar: {
                    left: "prev,next",
                    center: "title",
                    right: "",
                },
                dayCellDidMount: function(info) {
                    const date = info.date.toISOString().split('T')[0];
                    const dayOfWeek = info.date.getDay();
                    
                    // Verificar si es día festivo
                    if (colombianHolidays[date]) {
                        info.el.classList.add('holiday-day');
                        info.el.style.backgroundColor = '#fdf2f8';
                        info.el.style.position = 'relative';
                        info.el.setAttribute('data-holiday-name', colombianHolidays[date]);
                        
                        // Agregar indicador de día festivo
                        const indicator = document.createElement('div');
                        indicator.className = 'holiday-indicator';
                        indicator.innerHTML = '🎉';
                        indicator.style.position = 'absolute';
                        indicator.style.top = '4px';
                        indicator.style.right = '4px';
                        indicator.style.fontSize = '12px';
                        indicator.style.zIndex = '10';
                        info.el.appendChild(indicator);
                    }
                    
                    // Verificar si es domingo (día 0 en JavaScript) ÚNICAMENTE
                    if (dayOfWeek === 0) {
                        console.log(`Domingo detectado: ${date}, día de la semana: ${dayOfWeek}`);
                        info.el.classList.add('sunday-day');
                        
                        // Solo aplicar estilos si no es día festivo
                        if (!colombianHolidays[date]) {
                            info.el.style.backgroundColor = '#fef7f0';
                            info.el.style.borderColor = '#fed7aa';
                            info.el.style.borderWidth = '2px';
                            
                            // Colorear el número del día específicamente para domingos
                            const dayNumber = info.el.querySelector('.fc-daygrid-day-number');
                            if (dayNumber) {
                                dayNumber.style.color = '#c2410c';
                                dayNumber.style.fontWeight = '700';
                            }
                        }
                    }
                    
                    // Asegurar que otros días NO tengan estilos de domingo
                    if (dayOfWeek !== 0) {
                        info.el.classList.remove('sunday-day');
                        if (!colombianHolidays[date]) {
                            info.el.style.backgroundColor = '';
                            info.el.style.borderColor = '';
                            info.el.style.borderWidth = '';
                        }
                    }
                    
                    // Debug: verificar si está detectando lunes incorrectamente
                    if (dayOfWeek === 1) {
                        console.log(`Lunes detectado (NO debería resaltarse): ${date}, día de la semana: ${dayOfWeek}`);
                    }
                },
                select: function(info) {
                    //Get the date selected in the first calendar
                    const selectedDate = info.start.toISOString().split('T')[0];

                    // Remove previous selection highlighting
                    document.querySelectorAll('.fc-day-selected').forEach(day => {
                        day.classList.remove('fc-day-selected');
                    });

                    // Add highlighting to selected date
                    const selectedDayElement = document.querySelector(`[data-date="${selectedDate}"]`);
                    if (selectedDayElement) {
                        selectedDayElement.classList.add('fc-day-selected');
                    }

                    // Add logic to get events for the selected day from the data source
                    const eventsForDate = myEvents?.filter(event => {
                        const eventStart = event?.start?.slice(0, 10);
                        const eventEnd = event?.end?.slice(0, 10);
                        return (eventStart <= selectedDate && eventEnd >= selectedDate);
                    });
                    // clean old events
                    calendar2.getEventSources().forEach(function(source) {
                        source.remove();
                    });
                    //add the new events
                    calendar2.addEventSource(eventsForDate);
                    //change view to selected date
                    calendar2.gotoDate(selectedDate);
                    
                    // Update sidebar header
                    const dateObj = new Date(selectedDate);
                    const options = { year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' };
                    let formattedDate = dateObj.toLocaleDateString('es-ES', options);
                    
                    // Agregar información de día festivo si aplica
                    if (colombianHolidays[selectedDate]) {
                        formattedDate += ` - ${colombianHolidays[selectedDate]}`;
                    }
                    
                    const sidebarDate = document.querySelector('.sidebar-date');
                    if (sidebarDate) {
                        sidebarDate.textContent = formattedDate;
                    }
                },
                eventClick: function(event) {
                    const [filteredEvents] = myEvents.filter(events => events.id === Number(event.event.id));

                    if (filteredEvents) {
                        // Safely set color radio buttons
                        if(filteredEvents.color == '#ff7c32'){
                            safeCheck('edit_yellow');
                        }
                        if(filteredEvents.color == '#67AAFF'){
                            safeCheck('edit_blue');
                        }
                        if(filteredEvents.color == '#FF5656'){
                            safeCheck('edit_red');
                        }
                        if(filteredEvents.color == '#04760B'){
                            safeCheck('edit_green');
                        }

                        // Safely set form values
                        safeSetValue('edit_event_id', filteredEvents?.id);
                        safeSetValue('edit_title', filteredEvents?.title);
                        safeSetValue('edit_start_date', filteredEvents?.start.slice(0, 10));
                        safeSetValue('edit_start_time', filteredEvents?.start.slice(11, filteredEvents?.start.length));
                        safeSetValue('edit_end_date', filteredEvents?.end.slice(0, 10));
                        safeSetValue('edit_end_time', filteredEvents?.end.slice(11, filteredEvents?.end.length));
                        safeSetValue('edit_notes', filteredEvents?.notes);
                        safeSetValue('delete_event_id', filteredEvents?.id);
                        
                        showModal();
                    }
                },
                slotMinTime: '7:00:00',
                slotMaxTime: '23:00:00',
                events: @json($events),
                eventContent: function(arg) {
                    const eventHtml = document.createElement('div');
                    eventHtml.style.width = '100%';
                    eventHtml.style.padding = '0px';
                    eventHtml.style.margin = '0px';

                    const getEvent = myEvents.find(event => {
                        if (event.id == arg.event._def.publicId) return event.color
                    })
                    
                    // Map colors to design colors
                    let dotColor = getEvent?.color;
                    let bgColor = '#f8fafc';
                    
                    if (dotColor === '#ff7c32') {
                        dotColor = '#ff7c32'; // Orange
                        bgColor = '#fff7ed';
                    } else if (dotColor === '#67AAFF') {
                        dotColor = '#3b82f6'; // Blue
                        bgColor = '#eff6ff';
                    } else if (dotColor === '#FF5656') {
                        dotColor = '#ef4444'; // Red
                        bgColor = '#fef2f2';
                    } else if (dotColor === '#04760B') {
                        dotColor = '#10b981'; // Green
                        bgColor = '#f0fdf4';
                    }
                    
                    eventHtml.style.background = 'transparent';
                    eventHtml.style.border = 'none';
                    
                    const startTime = new Date(arg.event.start);
                    const endTime = new Date(arg.event.end);

                    // Format time
                    const startHour = startTime.getHours();
                    const startMinutes = ('0' + startTime.getMinutes()).slice(-2);
                    const endHour = endTime.getHours();
                    const endMinutes = ('0' + endTime.getMinutes()).slice(-2);

                    // Check if event has extended properties for title
                    const eventTitle = arg.event.title || 'Evento';

                    eventHtml.innerHTML =
                        `<div class="myEvent w-full text-left cursor-pointer px-1 py-0.5 rounded border border-gray-200 mb-0" style="background-color: ${bgColor}; border-left: 2px solid ${dotColor};">
                            <div class="flex items-center justify-between">
                                <div class="flex flex-col min-w-0 flex-1">
                                    <span class="text-xs font-medium text-gray-800 truncate leading-tight">${eventTitle}</span>
                                </div>
                                <div class="flex space-x-0.5 ml-1">
                                    <div class="w-1 h-1 rounded-full" style="background-color: ${dotColor}"></div>
                                    <div class="w-1 h-1 rounded-full" style="background-color: ${dotColor}"></div>
                                    <div class="w-1 h-1 rounded-full" style="background-color: ${dotColor}"></div>
                                </div>
                            </div>
                        </div>`;

                    return {
                        domNodes: [eventHtml]
                    };
                },
                viewDidMount: function(info) {
                    // Asegurar que solo los domingos estén resaltados después del cambio de vista
                    setTimeout(() => {
                        cleanupSundayHighlighting();
                    }, 100);
                }
            });

            const calendarEl2 = document.getElementById('calendar-2');
            calendar2 = new FullCalendar.Calendar(calendarEl2, {
                locale: "es",
                timeZone: 'America/Bogota',
                height: "100%",
                initialView: "listDay",
                firstDay: 1, // Lunes como primer día de la semana
                slotMinTime: '7:00:00',
                slotMaxTime: '23:00:00',
                headerToolbar: {
                    left: '',
                    center: '',
                    right: ''
                },
                dayCellDidMount: function(info) {
                    const date = info.date.toISOString().split('T')[0];
                    const dayOfWeek = info.date.getDay();
                    
                    // Verificar si es día festivo
                    if (colombianHolidays[date]) {
                        info.el.classList.add('holiday-day');
                        info.el.style.backgroundColor = '#fdf2f8';
                    }
                    
                    // Verificar si es domingo (día 0 en JavaScript) - SOLO domingo
                    if (dayOfWeek === 0) {
                        console.log(`Domingo en calendario lateral: ${date}, día: ${dayOfWeek}`);
                        info.el.classList.add('sunday-day');
                        
                        // Solo aplicar estilos si no es día festivo
                        if (!colombianHolidays[date]) {
                            info.el.style.backgroundColor = '#fef7f0';
                            info.el.style.borderColor = '#fed7aa';
                        }
                    }
                    
                    // Asegurar que otros días NO tengan estilos de domingo
                    if (dayOfWeek !== 0) {
                        info.el.classList.remove('sunday-day');
                        if (!colombianHolidays[date]) {
                            info.el.style.backgroundColor = '';
                            info.el.style.borderColor = '';
                        }
                    }
                    
                    // Debug para verificar si se está detectando lunes incorrectamente
                    if (dayOfWeek === 1) {
                        console.log(`Lunes en calendario lateral (NO debe resaltarse): ${date}, día: ${dayOfWeek}`);
                    }
                },
                noEventsContent: function() {
                    return 'No hay eventos programados para este día';
                },
                eventClick: function(event) {
                    const [filteredEvents] = myEvents.filter(events => events.id === Number(event.event.id));

                    if (filteredEvents) {
                        if(filteredEvents.color == '#ff7c32'){
                            safeCheck('edit_yellow');
                        }
                        if(filteredEvents.color == '#67AAFF'){
                            safeCheck('edit_blue');
                        }
                        if(filteredEvents.color == '#FF5656'){
                            safeCheck('edit_red');
                        }
                        if(filteredEvents.color == '#04760B'){
                            safeCheck('edit_green');
                        }

                        safeSetValue('edit_event_id', filteredEvents?.id);
                        safeSetValue('edit_title', filteredEvents?.title);
                        safeSetValue('edit_start_date', filteredEvents?.start?.slice(0, 10));
                        safeSetValue('edit_start_time', filteredEvents?.start?.slice(11, filteredEvents?.start?.length));
                        safeSetValue('edit_end_date', filteredEvents?.end?.slice(0, 10));
                        safeSetValue('edit_end_time', filteredEvents?.end?.slice(11, filteredEvents?.end?.length));
                        safeSetValue('edit_notes', filteredEvents?.notes);
                        safeSetValue('delete_event_id', filteredEvents?.id);
                        
                        if (typeof showModal === 'function') {
                            showModal();
                        }
                    }
                },
                eventContent: function(event) {
                    const eventsHtml = document.createElement('div');
                    eventsHtml.style.width = '100%';
                    eventsHtml.style.padding = '0';
                    eventsHtml.style.margin = '0 0 24px 0';
                    eventsHtml.style.background = 'transparent';

                    // Get start and end time
                    const startTime = new Date(event.event.start);
                    const endTime = new Date(event.event.end);

                    // Format time
                    const startHour = ('0' + startTime.getHours()).slice(-2);
                    const startMinutes = ('0' + startTime.getMinutes()).slice(-2);
                    const endHour = ('0' + endTime.getHours()).slice(-2);
                    const endMinutes = ('0' + endTime.getMinutes()).slice(-2);

                    // Map colors to design colors
                    let bgColor = event.backgroundColor;
                    let dotColor = event.backgroundColor;
                    let textColor = '#1f2937';
                    
                    if (bgColor === '#ff7c32') {
                        bgColor = '#fef3c7'; // Light yellow/orange background
                        dotColor = '#ff7c32';
                        textColor = '#92400e';
                    } else if (bgColor === '#67AAFF') {
                        bgColor = '#dbeafe'; // Light blue background  
                        dotColor = '#3b82f6';
                        textColor = '#1e40af';
                    } else if (bgColor === '#FF5656') {
                        bgColor = '#fecaca'; // Light red background
                        dotColor = '#ef4444';
                        textColor = '#dc2626';
                    } else if (bgColor === '#04760B') {
                        bgColor = '#d1fae5'; // Light green background
                        dotColor = '#10b981';
                        textColor = '#047857';
                    }

                    // Get hour in 12-hour format
                    const hour12 = startTime.getHours();
                    const displayHour = hour12 === 0 ? 12 : hour12 > 12 ? hour12 - 12 : hour12;
                    const ampm = hour12 >= 12 ? 'PM' : 'AM';

                    eventsHtml.innerHTML =
                        `<div class="myEvent cursor-pointer">
                            <div class="flex items-center justify-between mb-3">
                                <div class="text-sm font-semibold text-gray-800">${displayHour} ${ampm}</div>
                                <div class="h-px flex-1 bg-gray-200 ml-4"></div>
                            </div>
                            <div class="rounded-lg p-4 relative border-0 shadow-sm" style="background-color: ${bgColor};">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-semibold pr-6" style="color: ${textColor};">${event.event.title || 'Text'}</div>
                                    <div class="w-3 h-3 rounded-full" style="background-color: ${dotColor}"></div>
                                </div>
                            </div>
                        </div>`

                    return {
                        domNodes: [eventsHtml]
                    };
                }
            });

            calendar.render();
            calendar2.render();
            
            // Función para limpiar y aplicar correctamente el resaltado de domingos
            function cleanupSundayHighlighting() {
                document.querySelectorAll('.fc-daygrid-day').forEach(dayEl => {
                    const date = dayEl.getAttribute('data-date');
                    if (date) {
                        const dayDate = new Date(date);
                        const dayOfWeek = dayDate.getDay();
                        
                        // Si NO es domingo (0), remover cualquier resaltado de domingo
                        if (dayOfWeek !== 0) {
                            dayEl.classList.remove('sunday-day');
                            if (!colombianHolidays[date]) {
                                dayEl.style.backgroundColor = '';
                                dayEl.style.borderColor = '';
                                dayEl.style.borderWidth = '';
                            }
                            
                            // Restablecer el color del número del día
                            const dayNumber = dayEl.querySelector('.fc-daygrid-day-number');
                            if (dayNumber) {
                                dayNumber.style.color = '';
                                dayNumber.style.fontWeight = '';
                            }
                        }
                        
                        // Si ES domingo (0), asegurar que tenga el resaltado correcto
                        if (dayOfWeek === 0 && !colombianHolidays[date]) {
                            dayEl.classList.add('sunday-day');
                            dayEl.style.backgroundColor = '#fef7f0';
                            dayEl.style.borderColor = '#fed7aa';
                            dayEl.style.borderWidth = '2px';
                            
                            // Colorear el número del día para domingos
                            const dayNumber = dayEl.querySelector('.fc-daygrid-day-number');
                            if (dayNumber) {
                                dayNumber.style.color = '#c2410c';
                                dayNumber.style.fontWeight = '700';
                            }
                        }
                    }
                });
            }
            
            // Función para limpiar resaltado incorrecto después de renderizar
            setTimeout(() => {
                // Remover resaltado de cualquier día que no sea domingo
                document.querySelectorAll('.fc-daygrid-day').forEach(dayEl => {
                    const date = dayEl.getAttribute('data-date');
                    if (date) {
                        const dayDate = new Date(date);
                        const dayOfWeek = dayDate.getDay();
                        
                        // Si NO es domingo (0) y tiene clase sunday-day, removerla
                        if (dayOfWeek !== 0 && dayEl.classList.contains('sunday-day')) {
                            console.log(`Removiendo resaltado incorrecto de: ${date} (día ${dayOfWeek})`);
                            dayEl.classList.remove('sunday-day');
                            if (!colombianHolidays[date]) {
                                dayEl.style.backgroundColor = '';
                                dayEl.style.borderColor = '';
                                dayEl.style.borderWidth = '';
                            }
                            
                            // Restablecer el color del número del día
                            const dayNumber = dayEl.querySelector('.fc-daygrid-day-number');
                            if (dayNumber) {
                                dayNumber.style.color = '';
                                dayNumber.style.fontWeight = '';
                            }
                        }
                        
                        // Si ES domingo (0) y NO tiene clase sunday-day, agregarla
                        if (dayOfWeek === 0 && !dayEl.classList.contains('sunday-day') && !colombianHolidays[date]) {
                            console.log(`Agregando resaltado correcto a domingo: ${date}`);
                            dayEl.classList.add('sunday-day');
                            dayEl.style.backgroundColor = '#fef7f0';
                            dayEl.style.borderColor = '#fed7aa';
                            dayEl.style.borderWidth = '2px';
                            
                            // Colorear el número del día para domingos
                            const dayNumber = dayEl.querySelector('.fc-daygrid-day-number');
                            if (dayNumber) {
                                dayNumber.style.color = '#c2410c';
                                dayNumber.style.fontWeight = '700';
                            }
                        }
                    }
                });
                
                // Limpiar también los selectores CSS que podrían estar afectando
                document.querySelectorAll('.fc-day-mon, .fc-day-tue, .fc-day-wed, .fc-day-thu, .fc-day-fri, .fc-day-sat').forEach(dayEl => {
                    dayEl.classList.remove('sunday-day');
                    if (!dayEl.classList.contains('holiday-day')) {
                        dayEl.style.backgroundColor = '';
                        dayEl.style.borderColor = '';
                        dayEl.style.borderWidth = '';
                    }
                });
            }, 200);

            // Add a click event on some item to display the modal at the click position
            document.addEventListener('click', function(e) {
                if (e.target.closest('.myEvent')) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });

        });
    </script>
    
    <!-- Script para funcionalidad de búsqueda mejorada -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search');
            const searchForm = searchInput?.closest('form');
            let searchTimeout;
            
            if (searchInput && searchForm) {
                // Mejorar la experiencia de búsqueda con debounce
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    
                    // Esperar 800ms después de que el usuario deje de escribir
                    searchTimeout = setTimeout(() => {
                        const searchTerm = this.value.trim();
                        
                        // Si hay al menos 2 caracteres, realizar búsqueda automática
                        if (searchTerm.length >= 2) {
                            searchForm.submit();
                        }
                        
                        // Si el campo está vacío, limpiar búsqueda
                        if (searchTerm.length === 0) {
                            window.location.href = '{{ route("calendar.show") }}';
                        }
                    }, 800);
                });
                
                // Búsqueda al presionar Enter
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        clearTimeout(searchTimeout);
                        searchForm.submit();
                    }
                });
                
                // Limpiar búsqueda con Escape
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        this.value = '';
                        window.location.href = '{{ route("calendar.show") }}';
                    }
                });
            }
                
            // Resaltar eventos que coinciden con la búsqueda
            @if(request('search'))
                const searchTerm = '{{ request("search") }}';
                    
                // Función para resaltar texto en eventos
                function highlightSearchResults() {
                    const eventElements = document.querySelectorAll('.myEvent');
                    
                    eventElements.forEach(element => {
                        const textContent = element.textContent.toLowerCase();
                        const searchLower = searchTerm.toLowerCase();
                        
                        if (textContent.includes(searchLower)) {
                            element.style.boxShadow = '0 0 0 2px #ff7c32';
                            element.style.borderRadius = '6px';
                            element.style.transform = 'scale(1.02)';
                            element.style.transition = 'all 0.2s ease';
                        }
                    });
                }
                
                // Aplicar resaltado después de que se rendericen los calendarios
                setTimeout(highlightSearchResults, 500);
            @endif
        });
    </script>
    @vite('resources/js/editCalendarModal.js')
    <script>
        function closeModal() {
            const modal = document.getElementById("myModal");
            if (modal) {
                modal.style.display = "none";
            }
        }
        
        function showModal(event) {
            const modal = document.getElementById("myModal");
            const modalContent = document.getElementById("modalContent");
            
            if (modal && modalContent) {
                // Set the modal position
                modalContent.style.position = "fixed";
                modalContent.style.top = "50%";
                modalContent.style.left = "50%";
                modalContent.style.transform = "translate(-50%, -50%)";
                modal.style.display = "block";
            }
        }
        
        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            window.addEventListener('click', function(event) {
                const modal = document.getElementById("myModal");
                if (modal && event.target === modal) {
                    closeModal();
                }
            });
        });
    </script>
@endpush

@section('content')

    <!-- Notificaciones -->
    @if(session('success'))
        <div class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50" id="success-alert">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50" id="error-alert">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <div>
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Notificación de búsqueda sin resultados -->
    @if(request('search') && empty($events))
        <div class="fixed top-4 right-4 bg-yellow-500 text-white px-6 py-3 rounded-lg shadow-lg z-50" id="no-results-alert">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <div>
                    <div class="font-semibold">No se encontraron resultados</div>
                    <div class="text-sm opacity-90">No hay eventos o tareas que coincidan con "{{ request('search') }}"</div>
                </div>
            </div>
        </div>
    @endif

    <section class="w-full flex flex-col bg-[#FAFAFA] text-gray-800 h-full overflow-y-auto scrollbar-default mb-4" style="font-family: 'Product Sans', sans-serif;">
        <!-- Header Section -->
        <div class="px-8 pt-8 pb-4">
            @component('calendar.components.headCalendar')
            @endcomponent
        </div>
        
        <!-- Calendar Section -->
        <div class="flex flex-col lg:flex-row gap-6 px-8 pb-8">
            <!-- Main Calendar -->
            <div class="flex-1 bg-white rounded-2xl shadow-sm overflow-hidden" style="min-height: 700px;">
                <!-- Navigation tabs section for main calendar -->
                <div class="flex items-center justify-between px-8 py-4 border-b border-gray-100 bg-white">
                    <div class="flex gap-1 bg-gray-100 rounded-lg p-1">
                        <button type="button" class="tab-btn px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-white rounded-md transition-colors" onclick="changeCalendarView('dayGridDay')">Día</button>
                        <button type="button" class="tab-btn px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-white rounded-md transition-colors" onclick="changeCalendarView('dayGridWeek')">Semana</button>
                        <button type="button" class="tab-btn px-4 py-2 text-sm font-medium text-white bg-orange-500 rounded-md" onclick="changeCalendarView('dayGridMonth')">Mes</button>
                        <button type="button" class="tab-btn px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-white rounded-md transition-colors" onclick="changeCalendarView('multiMonthYear')">Año</button>
                    </div>
                    
                    <button onclick="my_modal_5.showModal()" class="bg-orange-500 hover:bg-orange-600 text-white rounded-lg px-5 py-2 flex items-center gap-2 shadow transition-colors">
                        <span class="font-semibold text-sm">+ AGREGAR</span>
                    </button>
                </div>
                
                <div id="calendar" class="w-full h-full"></div>
            </div>
            
            <!-- Side Calendar (Horario) -->
            <div class="lg:w-80 bg-white rounded-2xl shadow-sm overflow-hidden" style="min-height: 700px;">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Horario</h3>
                    <p class="text-sm text-gray-500 sidebar-date">Selecciona una fecha</p>
                </div>
                <div id="calendar-2" class="w-full h-full"></div>
            </div>
        </div>
        @component('calendar.components.calendarModal')
        @endcomponent
        @component('calendar.components.editCalendar')
        @endcomponent
    </section>
    <style>
        /* Aplicar Product Sans a todo el calendario con máxima especificidad */
        * {
            font-family: 'Product Sans', 'Segoe UI', system-ui, -apple-system, sans-serif !important;
        }

        /* Estilos base para igualar el diseño */
        .fc-toolbar {
            background: white;
            padding: 20px 24px 16px 24px;
            border-radius: 0;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 0;
            font-family: 'Product Sans', sans-serif !important;
        }

        .fc-toolbar-title {
            color: #1f2937 !important;
            font-size: 24px !important;
            font-weight: 700 !important;
            font-family: 'Product Sans', sans-serif !important;
        }

        .fc-prev-button, .fc-next-button {
            background: #f8fafc !important;
            color: #64748b !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            width: 32px !important;
            height: 32px !important;
            padding: 0 !important;
            font-size: 14px !important;
            font-family: 'Product Sans', sans-serif !important;
            font-weight: 500 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .fc-prev-button:hover, .fc-next-button:hover {
            background: #f1f5f9 !important;
        }

        /* Asegurar que los iconos de navegación se muestren correctamente */
        .fc-prev-button span, .fc-next-button span {
            font-size: 16px !important;
            font-weight: 700 !important;
            line-height: 1 !important;
        }

        .fc-prev-button .fc-icon, .fc-next-button .fc-icon {
            font-size: 16px !important;
            font-weight: 700 !important;
        }

      
        /* Estilos para el grid del calendario */
        .fc-daygrid-day {
            background: white;
            border: 1px solid #f1f5f9;
            position: relative;
            min-height: 120px;
            font-family: 'Product Sans', sans-serif !important;
        }

        .fc-daygrid-day-frame {
            min-height: 120px;
            padding: 6px 4px;
            position: relative;
            font-family: 'Product Sans', sans-serif !important;
        }

        .fc-daygrid-day-number {
            color: #64748b;
            font-weight: 700;
            padding: 6px 10px;
            position: absolute;
            top: 6px;
            left: 6px;
            z-index: 2;
            font-size: 18px;
            font-family: 'Product Sans', sans-serif !important;
        }

        .fc-day-today {
            background: #f0f9ff !important;
            border-color: #3b82f6 !important;
        }

        .fc-day-today .fc-daygrid-day-number {
            background: #3b82f6;
            color: white;
            border-radius: 6px;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            border: 2px solid #3b82f6;
            font-family: 'Product Sans', sans-serif !important;
        }

        /* Estilos para fecha seleccionada */
        .fc-day-selected {
            background: #fff7ed !important;
            border: 3px solid #ff7c32 !important;
            box-shadow: 0 0 0 3px rgba(255, 124, 50, 0.2) !important;
            border-radius: 12px !important;
        }

        .fc-day-selected .fc-daygrid-day-number {
            background: #ff7c32;
            color: white;
            border-radius: 6px;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            border: 2px solid #ff7c32;
            font-family: 'Product Sans', sans-serif !important;
        }

        .fc-daygrid-event-harness {
            margin-top: 32px !important;
            margin-bottom: 1px !important;
        }

        /* Estilos para los nombres de los días */
        .fc-col-header-day {
            background: white;
            border-bottom: 1px solid #f1f5f9;
            padding: 16px 8px;
             font-weight: 800;
            font-family: 'Product Sans', sans-serif !important;
        }

        .fc-col-header-cell-cushion {
            color: #64748b;
            font-weight: 700;
            font-size: 19px;
            text-transform: capitalize;
            letter-spacing: 0.025em;
            font-family: 'Product Sans', sans-serif !important;
        }

        /* Estilos para los eventos */
        .fc-event {
            border: none !important;
            background: transparent !important;
            padding: 0 !important;
            margin: 1px 0 !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            font-family: 'Product Sans', sans-serif !important;
        }

        .fc-event .fc-event-main {
            padding: 0 !important;
            font-family: 'Product Sans', sans-serif !important;
        }

        .myEvent {
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            font-family: 'Product Sans', sans-serif !important;
        }

        .myEvent * {
            font-family: 'Product Sans', sans-serif !important;
            font-weight: 600 !important;
        }

        .myEvent:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Indicador de múltiples eventos mejorado */
        .fc-daygrid-more-link {
            background: #fff7ed !important;
            color: #f97316 !important;
            border: 1px solid #fed7aa !important;
            border-radius: 4px !important;
            padding: 2px 6px !important;
            font-size: 10px !important;
            margin: 1px 0 !important;
            text-decoration: none !important;
            font-weight: 700 !important;
            text-align: center !important;
            display: block !important;
            font-family: 'Product Sans', sans-serif !important;
        }

        .fc-daygrid-more-link:hover {
            background: #ffedd5 !important;
            border-color: #fb923c !important;
            color: #ea580c !important;
        }

        /* Estilos para el calendario lateral (Horario) */
        #calendar-2 {
            padding: 0 !important;
            font-family: 'Product Sans', sans-serif !important;
        }

        #calendar-2 * {
            font-family: 'Product Sans', sans-serif !important;
        }

        #calendar-2 .fc-toolbar {
            display: none;
        }

        #calendar-2 .fc-list-table {
            border: none;
            font-family: 'Product Sans', sans-serif !important;
        }

        #calendar-2 .fc-list-day-cushion {
            display: none;
        }

        #calendar-2 .fc-list-event-dot {
            display: none;
        }

        #calendar-2 .fc-list-event {
            cursor: pointer;
            padding: 0 20px;
            border: none;
            margin-bottom: 0;
            background: transparent !important;
            font-family: 'Product Sans', sans-serif !important;
        }

        #calendar-2 .fc-list-event:hover {
            background: transparent !important;
        }

        #calendar-2 .fc-list-event-time {
            display: none;
        }

        #calendar-2 .fc-list-event-title {
            padding: 0;
            font-family: 'Product Sans', sans-serif !important;
        }

        /* Estilos específicos para el diseño del horario */
        #calendar-2 .myEvent {
            padding: 20px;
            border-bottom: 1px solid #f1f5f9;
            font-family: 'Product Sans', sans-serif !important;
        }

        #calendar-2 .myEvent:last-child {
            border-bottom: none;
        }

        /* Línea divisoria entre hora y contenido */
        #calendar-2 .myEvent .h-px {
            height: 1px;
            background-color: #e5e7eb;
        }

        /* Estado vacío para el calendario lateral */
        .fc-list-empty {
            padding: 60px 20px !important;
            text-align: center !important;
            color: #9ca3af !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            line-height: 1.6 !important;
            font-family: 'Product Sans', sans-serif !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 300px !important;
            background: transparent !important;
        }

        .fc-list-empty-cushion {
            color: #9ca3af !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            font-family: 'Product Sans', sans-serif !important;
        }

        /* Fallback para contenedores vacíos */
        .empty-events-container {
            padding: 32px 16px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 200px;
            font-family: 'Product Sans', sans-serif !important;
        }

        .empty-events-text {
            color: #6b7280;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.5;
            max-width: 200px;
            font-family: 'Product Sans', sans-serif !important;
        }

        .empty-events {
            padding: 32px 16px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 200px;
            font-family: 'Product Sans', sans-serif !important;
        }

        /* Estilos para los tabs */
        .tab-btn {
            transition: all 0.2s ease;
            font-weight: 600 !important;
            font-family: 'Product Sans', sans-serif !important;
        }

        .tab-btn:hover {
            background: white !important;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
        }

        /* Botones del calendario */
        button, .btn {
            font-family: 'Product Sans', sans-serif !important;
            font-weight: 600 !important;
        }

        /* Inputs y formularios */
        input, textarea, select {
            font-family: 'Product Sans', sans-serif !important;
            font-weight: 500 !important;
        }

        /* Labels y texto */
        label, span, p, div {
            font-family: 'Product Sans', sans-serif !important;
            font-weight: 600 !important;
        }

        /* Headers y títulos */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Product Sans', sans-serif !important;
            font-weight: 700 !important;
        }

        /* Ajustes para días con eventos rosados */
        .fc-daygrid-day.has-pink-events {
            background: #fdf2f8 !important;
        }

        /* Ajustes responsivos */
        @media (max-width: 1024px) {
            .flex-col.lg\:flex-row {
                flex-direction: column;
            }
            
            .lg\:w-80 {
                width: 100%;
            }
            
            .fc-daygrid-day-frame {
                min-height: 100px;
            }
            
            .fc-daygrid-event-harness {
                margin-top: 35px !important;
            }
        }

        @media (max-width: 768px) {
            .fc-daygrid-day-frame {
                min-height: 80px;
            }
            
            .fc-daygrid-day-number {
                font-size: 14px;
                padding: 6px 8px;
            }
            
            .fc-toolbar-title {
                font-size: 20px !important;
            }
            
            .fc-daygrid-event-harness {
                margin-top: 30px !important;
            }
        }

        /* Estilo específico para días con fondo rosa como en el diseño */
        .fc-daygrid-day[data-date*="2023-06-09"],
        .fc-daygrid-day[data-date*="2023-06-25"],
        .fc-daygrid-day[data-date*="2023-06-26"] {
            background: #fdf2f8 !important;
        }

        /* Estilos para días festivos colombianos */
        .fc-daygrid-day.holiday-day {
            background: #fdf2f8 !important;
            border-color: #f9a8d4 !important;
            position: relative;
        }

        .fc-daygrid-day.holiday-day .fc-daygrid-day-number {
            color: #be185d !important;
            font-weight: 700 !important;
        }

        .holiday-indicator {
            position: absolute;
            top: 4px;
            right: 4px;
            font-size: 12px;
            z-index: 10;
            opacity: 0.8;
        }

        /* Estilos para domingos - SOLO domingos (día 0) */
        .fc-daygrid-day.sunday-day {
            background: #fef7f0 !important;
            border-color: #fed7aa !important;
            border-width: 2px !important;
        }

        .fc-daygrid-day.sunday-day .fc-daygrid-day-number {
            color: #c2410c !important;
            font-weight: 700 !important;
            background: #fed7aa !important;
            border-radius: 6px !important;
            width: 28px !important;
            height: 28px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        /* Domingos usando el selector nativo de FullCalendar - SOLO domingos */
        .fc-day-sun {
            background: #fef7f0 !important;
            border-color: #fed7aa !important;
        }

        .fc-day-sun .fc-daygrid-day-number {
            color: #c2410c !important;
            font-weight: 700 !important;
        }

        /* Asegurar que NO se resalten los lunes ni otros días */
        .fc-day-mon, .fc-day-tue, .fc-day-wed, .fc-day-thu, .fc-day-fri, .fc-day-sat {
            background: white !important;
        }

        .fc-day-mon .fc-daygrid-day-number, 
        .fc-day-tue .fc-daygrid-day-number, 
        .fc-day-wed .fc-daygrid-day-number, 
        .fc-day-thu .fc-daygrid-day-number, 
        .fc-day-fri .fc-daygrid-day-number, 
        .fc-day-sat .fc-daygrid-day-number {
            color: #64748b !important;
            font-weight: 600 !important;
            font-family: 'Product Sans', sans-serif !important;
        }

        /* Prevenir que los lunes tengan estilos de domingo */
        .fc-day-mon.sunday-day {
            background: white !important;
            border-color: #f1f5f9 !important;
            border-width: 1px !important;
        }

        .fc-day-mon.sunday-day .fc-daygrid-day-number {
            color: #64748b !important;
            font-weight: 600 !important;
            background: transparent !important;
            border-radius: 0 !important;
            font-family: 'Product Sans', sans-serif !important;
        }

        /* Estilos para los nombres de los días de la semana */
        .fc-col-header-day.fc-day-sun .fc-col-header-cell-cushion {
            color: #c2410c !important;
            font-weight: 700 !important;
            font-family: 'Product Sans', sans-serif !important;
        }

        .fc-col-header-day.fc-day-sat .fc-col-header-cell-cushion {
            color: #0369a1 !important;
            font-weight: 700 !important;
            font-family: 'Product Sans', sans-serif !important;
        }

        /* Tooltip para días festivos */
        .holiday-day::before {
            content: attr(data-holiday-name);
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            background: #be185d;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
            z-index: 1000;
        }

        .holiday-day:hover::before {
            opacity: 1;
        }

        /* Reduce spacing between events */
        .fc-daygrid-event {
            margin-bottom: 1px !important;
        }

        .fc-event {
            margin-bottom: 1px !important;
        }

        .myEvent {
            margin-bottom: 1px !important;
        }

        .fc-daygrid-day-events {
            margin-top: 1px !important;
        }
    </style>
    
    <script>
        // Validar que las fuentes Product Sans se carguen correctamente
        document.fonts.ready.then(function() {
            console.log('Fuentes cargadas correctamente');
            
            // Verificar si Product Sans está disponible
            if (!document.fonts.check('12px Product Sans')) {
                console.warn('Product Sans no está disponible');
            } else {
                console.log('Product Sans está disponible');
            }
        });
    </script>
@endsection
