# 📧 MÓDULO: BUZÓN

## 1. ¿Para qué es este módulo?
Es la **bandeja de mensajería interna** del sistema. Permite a los usuarios enviarse mensajes/emails entre sí dentro de la plataforma, recibir notificaciones del sistema, y reenviar comunicaciones a otros usuarios.

## 2. ¿Qué hace?
- Muestra una **lista de mensajes** recibidos con badge de no leídos (icono naranja en el sidebar).
- Permite **componer mensajes** dirigidos a uno o varios usuarios.
- Permite **responder** mensajes.
- Permite **reenviar** mensajes a otros usuarios (con `UserSearch` integrado).
- Filtra mensajes por tipo (enviados, recibidos, no leídos).
- Marca mensajes como leídos automáticamente.
- Recibe **notificaciones del sistema** (ej: cuando se completa un paso de Solicitud de Transporte, se notifica al comercial).

## 3. ¿Cómo funciona?

### Flujo de envío
```
1. Usuario hace click en "Nuevo mensaje" → modal EmailStore
2. Busca destinatarios con UserSearch (búsqueda async)
3. Selecciona uno o varios usuarios
4. Escribe asunto y cuerpo
5. POST /emails/store → EmailController@store
6. Crea registro en emails con sender_id, receiver_id, subject, body
7. Notifica al destinatario (badge en sidebar)
```

### Flujo de respuesta
```
1. Usuario abre mensaje → openEmailModal($emailId)
2. Lee contenido, marca como leído
3. Click "Responder" → modal con destinatario pre-llenado
4. POST /emails/reply → EmailController@reply
```

### Flujo de reenvío
```
1. En el modal abierto, click "Reenviar"
2. UserSearch aparece para elegir nuevos destinatarios
3. EmailIndex@forwardEmail envía a múltiples
4. Loop por cada user seleccionado → crea nuevo Email
```

### Notificaciones del sistema
Algunas acciones disparan emails automáticos al buzón:
- `Mail\StepCompleted` — cuando un paso de Solicitud de Transporte se completa
- Notificaciones de cambio de estado de cotizaciones
- Alertas de metas (vía `GoalStatusNotification`)

## 4. ¿Cómo actúa en el sistema?
Es el **canal de comunicación asíncrona** entre usuarios. Sustituye en parte:
- Emails externos (Outlook, Gmail) para comunicaciones internas
- Mensajería instantánea (WhatsApp interno)
- Notificaciones del sistema

Se integra con:
- **Usuarios:** mensajes entre user_id
- **Notifications:** notificaciones automáticas del sistema
- **Solicitud de Transporte:** dispara emails al completar pasos
- **Metas:** notificaciones cuando se cumple/no cumple meta

## 5. Componentes del código

### Rutas
| Método | URL | Controlador | Función |
|--------|-----|-------------|---------|
| GET | `/mail` | closure | Bandeja de entrada |
| POST | `/emails/store` | `EmailController@store` | Enviar |
| POST | `/emails/reply` | `EmailController@reply` | Responder |

### Componentes Livewire
**`app/Livewire/EmailIndex.php`** — listado principal con:
- `$emails` — array de emails
- `$selectedEmail` — email actualmente abierto
- `$forwarding` — modo reenvío activo
- `$selectedUserId` — usuario para reenviar
- `$searchTerm` — buscador de usuarios
- `$selectedUsers` — array de usuarios para reenvío
- `$emailReSend` — flag de re-envío
- **Métodos:** `selectUser`, `removeUser`, `updatedSearchTerm`, `openEmailModal`, `closeModal`, `mount`, `showForwarding`, `forwardEmail`

**`app/Livewire/EmailStore.php`** — modal de composición:
- `$users` — lista de destinatarios
- `$isOpen` — estado modal
- `openModal()`, `closeModal()`

**`app/Livewire/UserSearch.php`** — buscador reusable de usuarios:
- `$searchTerm`, `$selectedUsers`, `$forForwarding`
- `selectUser`, `removeUser`

### Vistas
- `resources/views/mailbox/show.blade.php` (vista principal)
- `resources/views/mailbox/components/headMailbox.blade.php`
- `resources/views/livewire/email-index.blade.php`
- `resources/views/livewire/email-store.blade.php`
- `resources/views/livewire/user-search.blade.php`
- `resources/views/email/template.blade.php` — template HTML de email externo
- `resources/views/emails/` — templates de emails enviados por SMTP

### Controlador
**Archivo:** `app/Http/Controllers/EmailController.php`
- `store(Request)` — crear email
- `reply(Request)` — responder

### Modelos
- **`Email`** (`emails` table):
  - `sender_id` → User (FK)
  - `receiver_id` → User (FK)
  - `subject`, `body`
  - `is_read` (boolean)
  - `parent_email_id` (para hilos de respuesta)
  - `created_at`
- Relaciones en `User`:
  - `sent_emails()` — `HasMany`
  - `received_emails()` — `HasMany`

### Mail (templates SMTP)
**`app/Mail/StepCompleted.php`** — Mailable que se envía cuando se completa un paso de Solicitud de Transporte. Envío vía SMTP configurado en `.env`.

### Notifications
**`app/Notifications/GoalStatusNotification.php`** — notificación de estado de metas.

### Migraciones
- `2024_08_25_101457_create_emails_table.php`
- `2024_08_26_081427_add_columns_to_emails_table.php`
- `2025_07_10_145902_create_notifications_table.php`

## 6. Reglas de negocio
- **Marca leído al abrir:** se actualiza `is_read = true` al hacer `openEmailModal`
- **Multi-destinatario:** un email puede tener varios `receiver_id` (loop crea registros separados)
- **Reenvío:** crea un nuevo Email con referencia al original
- **No se eliminan:** los emails se marcan como `deleted` (soft delete), no se borran físicamente

## 7. Permisos
- **Acceso:** todos los usuarios autenticados
- **Ver mensajes de otros:** sólo SUPER ADMIN (auditoría)
- **Reenviar:** todos los usuarios

## 8. Integraciones externas
- **SMTP** (`MAIL_*` en `.env`) — para emails que se envían al cliente final (no al buzón interno)
- Mailgun / Postmark / SendGrid son alternativas configurables en `config/services.php`

## 9. Mantenimiento
- **Performance:** indexar `receiver_id`, `is_read`, `created_at`
- **Limpieza:** considerar archivado de emails > 1 año
- **Spam interno:** no hay anti-spam actualmente — cualquier usuario puede enviar a cualquier otro

## 10. Archivos clave
- `app/Http/Controllers/EmailController.php`
- `app/Livewire/EmailIndex.php`, `EmailStore.php`, `UserSearch.php`
- `app/Models/Email.php`
- `app/Mail/StepCompleted.php`
- `app/Notifications/GoalStatusNotification.php`
- `resources/views/mailbox/`
- `resources/views/email/template.blade.php`

## 11. Relacionado con
- [04-Calendario](../04-Calendario/) — recordatorios
- [10-Gestion-Metas](../10-Gestion-Metas/) — notificaciones de metas
- [11-Gestion-Novedades-Alertas](../11-Gestion-Novedades-Alertas/) — centro unificado de alertas
- [17-Control-Usuarios](../17-Control-Usuarios/) — origen de los usuarios destinatarios
