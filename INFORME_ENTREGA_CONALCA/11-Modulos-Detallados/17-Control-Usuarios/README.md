# 👨‍💼 MÓDULO: CONTROL DE USUARIOS

## 1. ¿Para qué es este módulo?
Es el **panel de administración de usuarios** del sistema. Permite gestionar a quién le da acceso, qué rol tiene, a quién reporta jerárquicamente, y cómo se personaliza su experiencia (columnas visibles en grids).

## 2. ¿Qué hace?
- **CRUD de usuarios:** crear, ver, editar, eliminar usuarios del sistema.
- **Asignación de roles** (Spatie Permission): SUPER ADMIN, JEFE COMERCIAL, GERENTE DE CUENTA, ASISTENTE COMERCIAL, SAC, PRICING, API_PRICING.
- **Jerarquía organizacional:** define quién es el supervisor (`creator_id`) de cada comercial.
- **Personalización de columnas:** cada usuario puede definir qué columnas ve en los grids (drag & drop sortable).
- **Activación/desactivación** de usuarios sin borrarlos.
- **Reset de password** (manual o auto-generado).

## 3. ¿Cómo funciona?

### Flujo de creación de usuario
```
1. Admin entra a /panel-control-usuarios
2. Click "Nuevo usuario"
3. Llena: nombre, email, password, rol, supervisor (creator)
4. POST /users → UserController@store
5. Sistema valida:
   - Email único
   - Rol válido para el rol del admin
   - Supervisor existe y tiene jerarquía superior
6. Crea User + asigna Role vía Spatie
7. Si tiene rol comercial → puede ser asignado a clientes
```

### Flujo de personalización de columnas
```
1. Usuario entra a un grid (clientes, cotizaciones, etc.)
2. Click "Personalizar columnas"
3. Drag & drop ordena/oculta columnas
4. POST/PUT /users/columns → UserColumnController
5. Se guarda en user_columns como preferencia personal
6. Próxima vez que entre al grid, ve sus columnas custom
```

### Jerarquía organizacional
```
SUPER ADMIN
    │
    ├── JEFE COMERCIAL "Ana"
    │       │
    │       ├── GERENTE DE CUENTA "Carlos"
    │       │       │
    │       │       └── ASISTENTE COMERCIAL "Luis"
    │       │
    │       └── GERENTE DE CUENTA "María"
    │               │
    │               └── ASISTENTE COMERCIAL "Pedro"
    │
    └── SAC "Sandra"
```

La jerarquía determina:
- Quién puede ver las cotizaciones de quién
- Quién recibe alertas de metas
- Quién puede asignar usuarios a clientes

## 4. ¿Cómo actúa en el sistema?
Es el **módulo de acceso y autorización**. Sin un User registrado:
- No se puede entrar al sistema
- No se pueden crear cotizaciones
- No se pueden asignar clientes
- No hay metas

Se integra con:
- **Spatie Permission** para roles
- **Cliente:** asignación de comerciales (`client_user_assignments`)
- **Goals:** metas por comercial
- **GroupCotization:** `user_id` propietario
- **Email/Notifications:** destinatario de mensajes

## 5. Componentes del código

### Rutas
| Método | URL | Función |
|--------|-----|---------|
| GET | `/panel-control-usuarios` | Vista panel |
| GET | `/users` | Listar JSON |
| POST | `/users` | Crear |
| PUT | `/users/{id}` | Actualizar |
| DELETE | `/users/{id}` | Eliminar |
| GET | `/users/assignable-roles` | Roles asignables |
| GET | `/users/me` | Usuario actual |
| GET | `/users/potential-parents` | Supervisores potenciales |
| GET | `/users/columns` | Columnas del usuario |
| POST | `/users/columns` | Crear columna |
| PUT | `/users/columns/{column}` | Actualizar |
| DELETE | `/users/columns/{column}` | Eliminar |

### Controladores
- **`app/Http/Controllers/Api/UserController.php`** — CRUD principal
- **`app/Http/Controllers/Api/UserColumnController.php`** — columnas personalizadas
- **`app/Http/Controllers/DataColumnController.php`** — columnas globales del sistema

**Métodos en UserController:**
- `index(Request)` — listar con filtros
- `store(Request)` — crear con validación de rol
- `update(Request, $id)` — actualizar
- `destroy($id)` — eliminar (soft delete)
- `assignableRoles(Request)` — roles que el user actual puede asignar
- `current(Request)` — usuario autenticado
- `potentialParents(Request)` — supervisores potenciales (jerarquía)

### Vista
**`resources/views/users/panel.blade.php`** — vista que carga React.

### Componente React
**`resources/js/components/UserControlPanel/`** — UI moderna:
- Listado paginado con búsqueda
- Modales de creación/edición
- Selector de rol con validación dinámica
- Selector de supervisor con jerarquía
- Sortable columns para personalización

### Modelos
- **`User`** (estándar Laravel + extras):
  - `name`, `email`, `password`
  - `creator_id` (FK a User — supervisor jerárquico)
  - Profile fields: `phone`, `position`, `avatar`, etc.
  - Spatie roles via `HasRoles` trait
  - Relaciones:
    - `creator()` — supervisor
    - `columns()`, `userColumns()` — columnas personalizadas
    - `sent_emails`, `received_emails`
    - `goalsAsCommercial`, `goalsAsBoss`
    - `clientAssignments`, `assignedClients`, `assignedClientsBy`

- **`UserColumn`** (`user_columns` table):
  - `user_id`, `data_column_id`
  - `position` (orden)
  - `is_visible`

- **`DataColumn`** (`data_columns` table):
  - Catálogo global de columnas disponibles
  - `name`, `label`, `default_visible`, `module`

- **`Role`** (Spatie) — definido en migración

### Migraciones
- `2014_10_12_000000_create_users_table.php`
- `2025_05_06_074351_create_data_columns_table.php`
- `2025_06_19_111143_create_permission_tables.php` (Spatie)
- `2025_06_19_112047_create_user_creators_table.php` (jerarquía)
- `2025_06_25_170237_create_user_columns_table.php`
- `2025_08_25_133517_add_profile_fields_to_users_table.php`

## 6. Roles disponibles

| Rol | Descripción |
|-----|-------------|
| **SUPER ADMIN** | Acceso total al sistema |
| **JEFE COMERCIAL** | Gestiona equipo comercial, ve metas, % y tara |
| **GERENTE DE CUENTA** | Gestiona clientes asignados, equipo bajo su línea |
| **ASISTENTE COMERCIAL** | Crea cotizaciones, atiende clientes |
| **SAC** | Servicio al cliente, gestiona conductores y auditoría |
| **PRICING** | Gestiona precios y rentabilidad |
| **API_PRICING** | Sistema externo que consume API de pricing (solo API) |

## 7. Reglas de asignación de roles
- **SUPER ADMIN** puede asignar cualquier rol
- **JEFE COMERCIAL** puede asignar:
  - GERENTE DE CUENTA
  - ASISTENTE COMERCIAL
- **GERENTE DE CUENTA** puede asignar:
  - ASISTENTE COMERCIAL (bajo su línea)
- **Otros roles** no pueden crear usuarios

Implementado en `assignableRoles()` que retorna los roles válidos según el rol del usuario actual.

## 8. Reglas de negocio
- **Email único** — no duplicados
- **Password mínimo:** 8 caracteres
- **Soft delete:** los usuarios eliminados no se borran (auditoría)
- **Reasignación:** si se elimina un comercial, sus clientes quedan sin asignación (manual reassign)
- **Creator obligatorio:** todo comercial debe tener un supervisor (excepto SUPER ADMIN)

## 9. Permisos
| Rol | Acceso al panel |
|-----|:---------------:|
| SUPER ADMIN | ✅ Total |
| JEFE COMERCIAL | ✅ Su línea |
| GERENTE DE CUENTA | ❌ |
| ASISTENTE COMERCIAL | ❌ |
| SAC | ❌ |
| PRICING | ❌ |

## 10. Mantenimiento
- **Cron de inactividad:** considerar desactivar usuarios sin actividad > 90 días (no implementado)
- **Auditoría:** Spatie Permission no registra cambios — considerar tabla de log
- **Backup de roles:** antes de cambios masivos, exportar `model_has_roles` y `roles`

## 11. Comandos
```bash
# Crear SUPER ADMIN manualmente (tinker)
php artisan tinker
>>> $u = User::create(['name'=>'Admin', 'email'=>'admin@conalca.com.co', 'password'=>Hash::make('password')]);
>>> $u->assignRole('SUPER ADMIN');

# Asignar rol
>>> User::find($id)->assignRole('JEFE COMERCIAL');

# Ver roles de un usuario
>>> User::find($id)->roles->pluck('name');
```

## 12. Archivos clave
- `app/Http/Controllers/Api/UserController.php`
- `app/Http/Controllers/Api/UserColumnController.php`
- `app/Http/Controllers/DataColumnController.php`
- `app/Models/User.php`
- `app/Models/UserColumn.php`
- `app/Models/DataColumn.php`
- `app/Http/Middleware/EnsureUserHasRole.php`
- `resources/views/users/panel.blade.php`
- `resources/js/components/UserControlPanel/`

## 13. Relacionado con
- **Todos los módulos** dependen de un User válido
- [02-Clientes](../02-Clientes/) — asignación cliente-usuario
- [10-Gestion-Metas](../10-Gestion-Metas/) — metas por comercial
- [16-Gestion-Esquema-Seguridad](../16-Gestion-Esquema-Seguridad/) — overrides por usuario

## 14. Auditoría histórica
- `01-Guias-Generales/AUDITORIA_PERMISOS.txt` — auditoría histórica de permisos
- `08-Correcciones-Fixes/CORRECCION_AUTENTICACION_BUSCAR_CONDUCTORES.md`

## 15. Mejoras pendientes
- 2FA / Autenticación de doble factor (no implementada)
- Login con SSO (no implementado)
- Log de actividad por usuario (último acceso, IP, acciones)
- Política de password (expiración, rotación)
- Sesiones múltiples controladas
