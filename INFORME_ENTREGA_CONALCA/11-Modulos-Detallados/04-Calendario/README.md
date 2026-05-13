# 📅 MÓDULO: CALENDARIO

## 1. ¿Para qué es este módulo?
Es la **agenda visual** del sistema. Permite a cada usuario gestionar sus citas, tareas pendientes, recordatorios y eventos relacionados con clientes y cotizaciones.

## 2. ¿Qué hace?
- Muestra un **calendario interactivo** (vistas: mes, semana, día).
- Permite **crear eventos** con título, descripción, fecha/hora, duración, color, asignados.
- Soporta **tareas** (eventos con tipo `task` que pueden marcarse como completadas).
- Permite **arrastrar y soltar** eventos para cambiarlos de fecha (drag & drop).
- Búsqueda de eventos por texto.
- Filtros por usuario, equipo o cliente.
- Notificaciones de recordatorio.

## 3. ¿Cómo funciona?

### Flujo de creación de evento
```
1. Usuario entra a /calendar
2. Click en una fecha o "+ Nuevo evento"
3. Modal aparece con formulario
4. Llena: título, descripción, fecha/hora inicio-fin, color, asignados, cliente (opcional)
5. POST /calendar → CalendarController@store
6. Crea registro en appointments
7. Notifica a usuarios asignados (opcional)
8. Calendario se actualiza vía Livewire/AJAX
```

### Flujo de arrastrar evento
```
1. Usuario arrastra evento desde el lunes al miércoles
2. Frontend (FullCalendar / Flatpickr) detecta drop
3. PUT /calendar con id + new_date
4. CalendarController@update guarda
5. Vista refresca
```

### Búsqueda
```
GET /calendar/search?q=cliente
    ↓
CalendarController@search busca en title, description
    ↓
Retorna lista filtrada
```

## 4. ¿Cómo actúa en el sistema?
Es principalmente **standalone** pero se integra con:
- **Clientes:** los eventos pueden estar asociados a un cliente para programar reuniones.
- **Cotizaciones:** desde una cotización se puede crear un evento "Recordatorio de seguimiento".
- **Usuarios:** los eventos pueden ser compartidos entre usuarios (multi-asignación).
- **Notificaciones:** dispara notificaciones cuando se acerca la fecha.

## 5. Componentes del código

### Rutas
| Método | URL | Controlador | Función |
|--------|-----|-------------|---------|
| GET | `/calendar` | `CalendarController@index` | Vista principal |
| POST | `/calendar` | `CalendarController@store` | Crear evento |
| PUT | `/calendar` | `CalendarController@update` | Actualizar |
| DELETE | `/calendar` | `CalendarController@destroy` | Eliminar |
| GET | `/calendar/search` | `CalendarController@search` | Buscar |

### Controlador
**Archivo:** `app/Http/Controllers/CalendarController.php`

**Métodos:**
- `index(Request)` — vista con todos los eventos del usuario
- `store(Request)` — crear evento
- `update(Request)` — actualizar (incluye drag & drop)
- `destroy(Request)` — eliminar
- `search(Request)` — búsqueda por texto

### Componente Livewire
**`app/Livewire/CalendarIndex.php`**:
- `render()` — renderiza la vista
- Maneja eventos en tiempo real

### Vistas
- `resources/views/calendar/show.blade.php` (vista principal)
- `resources/views/calendar/components/` (modales y subcomponentes)
- `resources/views/livewire/calendar-index.blade.php`

### Modelos
| Modelo | Tabla | Función |
|--------|-------|---------|
| `Appointment` | appointments | Eventos/citas (incluye campos de tarea) |
| `CalendarEvent` | calendar_events | Eventos genéricos |
| `Pending` | pendings | Tareas pendientes |
| `TransitEvent` | transit_events | Eventos del tránsito |

**Campos clave en `appointments`:**
- `title`, `description`, `start_at`, `end_at`
- `color` (hex para estilo visual)
- `user_id` (creador)
- `client_id` (cliente asociado, nullable)
- `type` (`event`, `task`, `reminder`)
- `is_completed` (para tareas)
- `assigned_users` (JSON con array de user IDs)

### Migraciones relevantes
- `2023_09_07_234328_create_appointments_table.php`
- `2024_04_05_161203_create_calendar_events_table.php`
- `2025_07_04_074418_create_pendings_table.php`
- `2025_07_10_154443_add_task_fields_to_appointments_table.php`
- `2025_07_11_101951_create_transit_events_table.php`

### Frontend
- **Flatpickr** (`flatpickr ^4.6.13`) — datepicker
- **react-beautiful-dnd 13.1.1** — drag & drop avanzado
- **sortablejs 1.15.6** — alternativa drag & drop

### JavaScript
- `editCalendarModal.js` — modal de edición
- Lógica de calendario embebida en la vista

## 6. Reglas de negocio
- **Eventos privados:** sólo el creador y los asignados los ven
- **Eventos públicos:** todos los usuarios autenticados los ven
- **Tareas (`type=task`):** tienen checkbox para marcar completado y se ocultan al cumplirse
- **Recurrencia:** no implementada actualmente — cada evento es único
- **Zona horaria:** America/Bogota (Colombia)

## 7. Permisos
- **Ver:** todos los roles excepto PRICING
- **Crear/Editar:** todos los usuarios (sus propios eventos)
- **Editar eventos de otros:** sólo si está asignado o es SUPER ADMIN
- **Eliminar:** sólo el creador o SUPER ADMIN

## 8. Mantenimiento
- **Limpieza:** considera purgar eventos > 1 año en estado `completed`
- **Performance:** indexar `start_at` y `user_id`
- **Notificaciones:** la integración con notificaciones push depende del módulo de Buzón

## 9. Archivos clave
- `app/Http/Controllers/CalendarController.php`
- `app/Livewire/CalendarIndex.php`
- `app/Models/Appointment.php`, `CalendarEvent.php`, `Pending.php`
- `resources/views/calendar/`
- `resources/js/editCalendarModal.js`

## 10. Relacionado con
- [02-Clientes](../02-Clientes/) — eventos asociados a clientes
- [05-Buzon](../05-Buzon/) — notificaciones de recordatorio
- [11-Gestion-Novedades-Alertas](../11-Gestion-Novedades-Alertas/) — tareas pendientes

## 11. Mejoras pendientes
- Recurrencia de eventos (diario, semanal, mensual)
- Sincronización con Google Calendar / Outlook (no implementada)
- Recordatorios automáticos por email/WhatsApp
