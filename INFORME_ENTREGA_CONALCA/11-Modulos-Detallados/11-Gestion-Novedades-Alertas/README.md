# 🔔 MÓDULO: GESTIÓN › NOVEDADES & ALERTAS

## 1. ¿Para qué es este módulo?
Es el **centro unificado de alertas y novedades** del sistema. Combina varios subsistemas: tareas pendientes asociadas a grupos de cotización, notificaciones del jefe comercial, alertas de cotizaciones nuevas que requieren atención, y preferencias de notificación del usuario.

## 2. ¿Qué hace?
- Muestra **tareas pendientes** asignadas al usuario asociadas a grupos de cotización.
- Lista **notificaciones del jefe** (alertas de metas, cumplimiento, evaluaciones).
- Alerta de **nuevas cotizaciones** que requieren acción.
- Permite **marcar como leído** las notificaciones.
- Permite **gestionar preferencias** de qué notificaciones recibir.
- Soporta **CRUD de pendientes** (crear, actualizar, eliminar tareas).

## 3. ¿Cómo funciona?

### Flujo de tarea pendiente en un grupo
```
1. Sistema detecta evento (cotización sin respuesta hace 3 días)
2. Crea Pending automático asociado al group_cotization_id
3. POST /pendings → PendingController@store
4. Pending aparece en la lista de "Novedades"
5. Usuario lo atiende → click marca como completado
6. PUT /pendings/{id} → cambia status
```

### Flujo de notificación del jefe
```
1. EvaluateMonthlyGoals (cron) detecta meta en riesgo
2. GoalStatusNotification se dispara
3. Se guarda en notifications (Laravel standard)
4. Jefe entra a /boss/notifications
5. GoalController@notifications lista
6. Click → POST /boss/notifications/{id}/read marca leída
```

### Flujo de alerta de cotizaciones nuevas
```
1. Cliente acepta una cotización (decision_cliente=aceptada)
2. Sistema dispara alerta para el comercial asignado
3. Aparece en /quotes-news-alerts
4. Comercial debe avanzar a Solicitud de Transporte
```

## 4. ¿Cómo actúa en el sistema?
Es un módulo **agregador** que combina información de:
- `Pending` (tareas operativas)
- `Notification` (notificaciones estándar Laravel)
- `GroupCotization` (eventos en cotizaciones)
- `Goal` (alertas de metas)
- `Solicitation` (solicitudes que requieren atención)

## 5. Componentes del código

### Rutas
| Método | URL | Función |
|--------|-----|---------|
| GET | `/groups/{group}/pendings` | Tareas del grupo |
| POST | `/pendings` | Crear pendiente |
| PUT | `/pendings/{id}` | Actualizar |
| DELETE | `/pendings/{id}` | Eliminar |
| GET | `/boss/notifications` | Notificaciones jefe |
| POST | `/boss/notifications/{id}/read` | Marcar leída |
| GET | `/quotes-news-alerts` | Alertas cotizaciones |
| POST | `/account/notifications` | Preferencias usuario |

### Controladores
- **`app/Http/Controllers/Api/PendingController.php`**:
  - `index($groupCotizationId)` — pendientes de un grupo
  - `store(Request)` — crear
  - `update(Request, $id)` — actualizar
  - `destroy($id)` — eliminar

- **`app/Http/Controllers/Api/GoalController.php`** (sección notificaciones):
  - `notifications()` — listar
  - `markNotificationRead($id)` — marcar

- **`app/Http/Controllers/AccountController.php`**:
  - `updateNotifications(Request)` — preferencias del usuario

### Modelos
- **`Pending`** (`pendings` table):
  - `title`, `description`
  - `group_cotization_id` (FK opcional)
  - `assigned_user_id`
  - `status` — `pending`, `in_progress`, `completed`
  - `due_date`

- **`Solicitation`** (`solicitations` table):
  - Tipos de solicitudes genéricas
  - Estado y grupo

- **`Notification`** (estándar Laravel):
  - `type`, `notifiable_type`, `notifiable_id`
  - `data` (JSON)
  - `read_at`

### Migraciones
- `2025_07_04_074418_create_pendings_table.php`
- `2025_07_08_144432_create_solicitations_table.php`
- `2025_07_08_144500_create_messages_table.php`
- `2025_07_10_145902_create_notifications_table.php`
- `2026_02_16_184815_add_type_and_group_id_to_solicitations_table.php`

## 6. Tipos de alertas que maneja

### Operativas
- Tareas pendientes en grupos
- Recordatorios de seguimiento
- Cotizaciones sin respuesta del cliente
- Solicitudes de transporte en pasos incompletos

### Comerciales
- Cliente aceptó cotización (alerta de seguimiento)
- Cliente rechazó (alerta de cambio de estrategia)
- Cliente respondió parcialmente

### De gestión
- Meta en riesgo
- Meta cumplida
- Evaluación mensual disponible

### Sistema
- Errores de integración (Arcángel down, Silogtran error)
- Llamadas atascadas (cuelgue manual requerido)

## 7. Reglas de negocio
- **Visibilidad:** las alertas se muestran sólo al usuario destinatario
- **Auto-creación:** algunas alertas se crean automáticamente (sistema)
- **Manual:** los comerciales pueden crear sus propios pendientes
- **Expiración:** las pendientes con `due_date` vencido se marcan visualmente
- **Marcar leída:** no elimina, sólo cambia `read_at`

## 8. Permisos
| Rol | Pendientes | Notif. Jefe | Preferencias |
|-----|:----------:|:-----------:|:------------:|
| SUPER ADMIN | ✅ Todas | ✅ | ✅ |
| JEFE COMERCIAL | ✅ Equipo | ✅ | ✅ |
| GERENTE DE CUENTA | ✅ Equipo | ✅ | ✅ |
| ASISTENTE COMERCIAL | ✅ Propias | ❌ | ✅ |
| SAC | ✅ Asignadas | ❌ | ✅ |
| PRICING | ❌ | ❌ | ✅ |

## 9. Mantenimiento
- **Limpieza:** purgar pendientes completados con > 6 meses
- **Auto-creación:** los hooks (model observers) crean pendientes automáticamente — revisar si saturan la UI
- **Email:** algunas notificaciones disparan email vía SMTP

## 10. Archivos clave
- `app/Http/Controllers/Api/PendingController.php`
- `app/Http/Controllers/Api/GoalController.php` (notificaciones)
- `app/Http/Controllers/AccountController.php`
- `app/Models/Pending.php`, `Solicitation.php`
- `app/Notifications/GoalStatusNotification.php`

## 11. Relacionado con
- [04-Calendario](../04-Calendario/) — tareas también se muestran ahí
- [05-Buzon](../05-Buzon/) — notificaciones llegan al buzón
- [10-Gestion-Metas](../10-Gestion-Metas/) — alertas de metas

## 12. Mejoras pendientes
- Push notifications al navegador (Web Push)
- Integración con WhatsApp Business (no implementada)
- Plantillas de alertas configurables por admin
- Resumen diario por email
