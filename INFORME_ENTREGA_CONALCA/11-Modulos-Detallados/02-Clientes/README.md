# 👥 MÓDULO: CLIENTES

## 1. ¿Para qué es este módulo?
Es el **CRM** del sistema. Centraliza toda la información de los **clientes** (empresas) y sus **contactos** (personas dentro de la empresa). Es la fuente única de verdad sobre quién es cada cliente, qué cotizaciones tiene, qué documentos, y qué comercial lo atiende.

## 2. ¿Qué hace?
- **Lista todos los clientes** con búsqueda y filtros (por NIT, nombre, ciudad, vendedor).
- Permite **crear y editar** clientes nuevos (con datos completos: NIT, nombre, dirección, ciudad, teléfonos, email, sucursales, vendedor asignado).
- Gestiona **contactos** por cliente (varios contactos por cliente: gerente, jefe de logística, asistente, etc.).
- Permite **asignar usuarios comerciales** a un cliente (1 cliente puede tener varios comerciales asignados, en distintos roles).
- Maneja **archivos del cliente** (categorizados: RUT, cámara comercio, contratos).
- Muestra **historial de cotizaciones** del cliente.
- Muestra **operaciones realizadas** y estadísticas comerciales.

## 3. ¿Cómo funciona?

### Flujo de uso típico
```
1. Comercial entra a /contacts
2. Busca cliente por NIT/nombre (búsqueda async con debounce)
3. Si existe → ve detalles, cotizaciones, archivos, contactos
4. Si NO existe → modal "Crear contacto" → guarda Client + Contact
5. Si es admin → asigna usuario comercial al cliente (1 a N)
6. Cliente queda disponible para cotizar
```

### Flujo de creación
```
Click "Nuevo Cliente"
    ↓
Modal createContactModal abre
    ↓
Comercial llena: NIT, razón social, ciudad, dirección, email, teléfono, vendedor
    ↓
POST /contacts → ContactController@store
    ↓
Crea Client + Contact principal asociado
    ↓
Vincula client_user_assignments (auto al user actual)
```

## 4. ¿Cómo actúa en el sistema?
Es **el nodo de entrada** del flujo comercial. Sin un Client, no se pueden:
- Crear cotizaciones (`GroupCotization.client_id`)
- Crear solicitudes de transporte (`SolicitudTransporte.client_id`)
- Asignar comerciales para metas

Otros módulos consultan Client:
- **Chat IA de cotizaciones:** busca cliente por NIT antes de iniciar chat
- **Pricing:** sugiere precios diferentes según cliente (algunos tienen tarifas especiales)
- **Esquema de Seguridad:** ciertos clientes tienen esquemas customizados
- **OpenAI Threads:** cada cliente tiene su propio `openai_thread_id` para mantener historial de chat

## 5. Componentes del código

### Rutas principales
| Método | URL | Controlador | Función |
|--------|-----|-------------|---------|
| GET | `/contacts` | `ContactController@index` | Panel principal |
| POST | `/contacts` | `ContactController@store` | Crear contacto/cliente |
| POST | `/contacts/update` | `ContactController@update` | Actualizar |
| POST | `/clients/assign` | `ContactController@assignUserToClient` | Asignar comercial |
| GET | `/clients/{id}/assigned-users` | `ContactController@getClientAssignedUsers` | Ver asignados |
| DELETE | `/clients/remove-assignment` | `ContactController@removeUserFromClient` | Quitar asignación |
| GET | `/clients/search-react` | `ClientController@search` | Búsqueda async (React) |
| GET | `/clients/by-document` | `ClientController@getByDocument` | Cliente por NIT |
| POST | `/clients` | `ClientController@store` | Crear cliente (API) |

### Controladores
- **`app/Http/Controllers/ContactController.php`** — gestión web + asignación de comerciales
- **`app/Http/Controllers/Api/ClientController.php`** — API REST para frontend React
- **`app/Http/Controllers/Api/ClientSearchController.php`** — búsqueda async optimizada
- **`app/Http/Controllers/Api/CompanySearchController.php`** — búsqueda de empresas

### Componente Livewire
**`app/Livewire/ClientsIndex.php`** — listado interactivo con:
- Búsqueda en tiempo real (`$search`)
- Modal de asignación (`$showAssignModal`)
- Selección de cliente y usuario
- Acciones de asignar/quitar

### Vistas
- `resources/views/contacts/show.blade.php` (vista principal)
- `resources/views/contacts/components/`:
  - `contactsTable.blade.php` — tabla de clientes
  - `clientDetails.blade.php` — panel de detalles
  - `contactDetails.blade.php` — panel del contacto
  - `headContacts.blade.php` — buscador y filtros
  - `createContactModal.blade.php` — modal de creación
  - `assignModal.blade.php` — modal de asignación

### Modelos involucrados
| Modelo | Tabla | Rol |
|--------|-------|-----|
| `Client` | clients | Empresa (NIT, ciudad, vendedor) |
| `Contact` | contacts | Personas de contacto en la empresa |
| `ContactFile` | contact_files | Archivos por contacto |
| `ClientFile` | client_files | Archivos del cliente (categorizados) |
| `ClientUserAssignment` | client_user_assignments | Pivot: cliente ↔ usuario |
| `Seller` | sellers | Vendedores |

### Servicio
**`app/Services/ClientService.php`**:
- `resolveOrCreateClient($nit, $name, $additionalData)` — busca por NIT, retorna prompt si no existe
- `createClient($data)` — creación nueva con validación

## 6. Reglas de negocio importantes
- **NIT es único** — no se permite crear duplicados
- Un cliente puede tener **N comerciales asignados** (multi-asignación)
- Cuando se crea desde el chat IA, se llama `ClientService.resolveOrCreateClient()` para evitar duplicación
- El campo `openai_thread_id` en `clients` mantiene el contexto de chat entre sesiones
- **soft delete:** `deleted_at` permite restaurar clientes eliminados

## 7. Permisos
- **Ver clientes:** todos los roles excepto PRICING
- **Crear/Editar:** todos los comerciales
- **Asignar usuarios:** SUPER ADMIN, JEFE COMERCIAL, GERENTE DE CUENTA
- **Quitar asignación:** SUPER ADMIN, JEFE COMERCIAL

## 8. Filtrado por rol
- **SUPER ADMIN / JEFE COMERCIAL / GERENTE CUENTA:** ven TODOS los clientes
- **ASISTENTE COMERCIAL:** sólo ven los clientes que tienen asignados
- **SAC:** ven todos para servicio post-venta

## 9. Archivos clave
- `app/Http/Controllers/ContactController.php`
- `app/Http/Controllers/Api/ClientController.php`
- `app/Livewire/ClientsIndex.php`
- `app/Services/ClientService.php`
- `app/Models/Client.php`, `Contact.php`, `ClientUserAssignment.php`
- `resources/views/contacts/`

## 10. Relacionado con
- [03-Documentos](../03-Documentos/) — comparte archivos del cliente
- [09-Pricing](../09-Pricing/) — pricings personalizados por cliente
- [16-Gestion-Esquema-Seguridad](../16-Gestion-Esquema-Seguridad/) — esquemas asignados a clientes
- [17-Control-Usuarios](../17-Control-Usuarios/) — origen de los usuarios comerciales

## 11. Documentación adicional
Ver carpeta `04-Cotizaciones-IA/`:
- `FLUJO_INFORMACION_CLIENTE.md` — flujo completo de info del cliente
- `CORRECCION_DUPLICACION_GROUP_COTIZATIONS.md`
