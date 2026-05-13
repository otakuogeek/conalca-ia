# 🚛 MÓDULO: GESTIÓN › CONDUCTORES

## 1. ¿Para qué es este módulo?
Es la **gestión maestra de conductores** del sistema. Mantiene el padrón de conductores con sus vehículos, datos de contacto, estado de actividad, historial de aceptaciones y permite **bloquear** conductores problemáticos.

## 2. ¿Qué hace?
- Lista **todos los conductores** con búsqueda y filtros (nombre, cédula, placa, teléfono).
- Permite **crear, editar, eliminar** conductores manualmente.
- **Sincroniza** automáticamente con la API de Arcángel (fuente externa de conductores).
- Permite **bloquear** conductores (los excluye de futuras llamadas).
- Muestra **historial** de cotizaciones y llamadas por conductor.
- Provee **estadísticas** (% aceptación, total de llamadas, vehículos asociados).
- Categoriza por **clase de vehículo** (SENCILLO, TURBO, TRACTOMULA, etc.).

## 3. ¿Cómo funciona?

### Flujo de búsqueda de conductor en cotización
```
Cotización aceptada → Sistema busca conductores
    ↓
ArcangelDriversController@buscarConductores
    ↓
Filtra: ciudad origen, peso compatible, vehículo apropiado
    ↓
Excluye conductores bloqueados (BlockedDriver)
    ↓
Devuelve N conductores ordenados por score
    ↓
Comercial confirma llamadas
    ↓
Sistema actualiza VehicleOwnerHolderDriver con timestamps
```

### Flujo de bloqueo
```
SAC detecta conductor problemático (no contesta, ofensivo, etc.)
    ↓
/conductores → busca → click "Bloquear"
    ↓
DELETE / PATCH → ConductorController@changeStatus
    ↓
Se crea BlockedDriver con razón
    ↓
Conductor queda fuera de futuras búsquedas
```

### Sincronización con Arcángel
```
php artisan arcangel:sync-drivers
    o
Comercial entra a /vehiculos/sincronizar
    ↓
ArcangelService.get('listarConductores')
    ↓
Iterar conductores Arcángel
    ↓
Por cada uno: upsert en vehicle_owner_holder_driver
    ↓
Actualiza nombre, placa, vehículo, teléfono
```

## 4. ¿Cómo actúa en el sistema?
Es **el padrón maestro** del sistema. Sus datos alimentan:
- **Búsqueda en Arcángel** (los conductores devueltos se comparan con este padrón local para enriquecer datos)
- **Sistema de llamadas IA** (el teléfono se toma de aquí o de Arcángel)
- **Auditoría** (histórico de qué conductor aceptó qué viaje)
- **Estadísticas** (top conductores por aceptación)

## 5. Componentes del código

### Rutas
| Método | URL | Función |
|--------|-----|---------|
| GET | `/conductores` | Panel principal |
| GET | `/api/conductores` | Listar JSON (con búsqueda/filtros) |
| POST | `/api/conductores` | Crear |
| GET | `/api/conductores/{id}` | Detalle |
| PUT | `/api/conductores/{id}` | Actualizar |
| DELETE | `/api/conductores/{id}` | Eliminar |
| PATCH | `/api/conductores/{id}/status` | Cambiar estado |
| GET | `/api/conductores/stats` | Estadísticas |
| GET | `/api/conductores/vehicle-classes` | Clases disponibles |

### Controlador
**`app/Http/Controllers/ConductorController.php`** (protegido para SUPER ADMIN + SAC)

**Métodos:**
- `index()` — vista del panel
- `getConductores(Request)` — listar con paginación y filtros
- `store(Request)` — crear conductor
- `show($id)` — detalle individual
- `update(Request, $id)` — actualizar
- `destroy($id)` — eliminar
- `changeStatus(Request, $id)` — cambiar estado (activo/inactivo/bloqueado)
- `getStats()` — estadísticas
- `getVehicleClasses()` — clases de vehículo disponibles

### Vista
**`resources/views/conductores/index.blade.php`** + `resources/views/conductores/modals/`

**Vista adicional:** `resources/views/conductor-details.blade.php`

### Modelos
- **`VehicleOwnerHolderDriver`** (`vehicle_owner_holder_driver`):
  - `nombre`, `cedula`
  - `telefono_conductor`, `telefono_propietario`, `telefono_tenedor`
  - `placa`, `vehiculo_tipo`, `bodywork`
  - `ciudad`, `direccion`
  - `estado` (activo/inactivo)
  - `score` (calculado por Arcángel)
  - **Métodos:**
    - `scopeByCedula`, `scopeByEstado`, `scopeByVehicleType`, `scopeByCity`, `scopeWithValidPhone`, `scopeAvailable`
    - `getMainPhoneAttribute()`, `getNameAttribute()`, `getPhoneNumberAttribute()`, `getFormattedPhoneAttribute()`
    - `markAsCalled()`, `markAsAccepted()`
    - Relaciones: `cotizacionesAceptadas`, `callResponses`, `llamadas`, `callDecisions`, `driverCallResponses`, `cotizaciones`

- **`BlockedDriver`** (`blocked_drivers`):
  - `driver_id` (FK)
  - `reason` (motivo del bloqueo)
  - `blocked_by` (user que bloqueó)
  - `blocked_at`

- **`VehicleClass`** (`vehicle_class`):
  - Clases: SENCILLO, TURBO, MINIMULA, TRACTOMULA, DOBLE TROQUE, etc.

- **`Bodywork`** (`bodyworks`):
  - Tipos de carrocería: ESTACAS, FURGON, PLATAFORMA, TANQUE, etc.

### Servicio externo
**`app/Services/ArcangelService.php`** — sincronización con API externa de conductores.

## 6. Filtros disponibles
- **Búsqueda libre:** nombre, cédula, placa, teléfono
- **Por ciudad**
- **Por tipo de vehículo**
- **Por estado:** activo / inactivo / bloqueado
- **Por capacidad de carga**
- **Conductores con teléfono válido** (scope)

## 7. Estadísticas (`getStats()`)
- Total de conductores activos
- Conductores con llamadas exitosas
- Top 10 por aceptaciones
- Distribución por tipo de vehículo
- Distribución geográfica

## 8. Reglas de negocio
- **Cédula única** — no duplicados
- **Placa única** — un vehículo, un conductor principal
- **Bloqueo permanente:** los bloqueados no aparecen en búsquedas
- **Soft delete:** eliminar no borra físicamente, marca `deleted_at`
- **Score Arcángel:** se actualiza con cada consulta a Arcángel y se cachea

## 9. Permisos
| Rol | Acceso |
|-----|:------:|
| SUPER ADMIN | ✅ Total |
| JEFE COMERCIAL | ❌ |
| GERENTE DE CUENTA | ❌ |
| ASISTENTE COMERCIAL | ❌ |
| SAC | ✅ CRUD + bloqueo |
| PRICING | ❌ |

## 10. Operación

### Comandos relacionados
```bash
# Listar pendientes
php artisan llamadas:listar-pendientes

# Buscar conductores para cotización específica
php artisan conductores:buscar {cotizacion_id}

# Actualizar conductores existentes (script bash)
./actualizar_conductores_existentes.sh

# Consultar localidad Arcángel
php artisan arcangel:consultar-localidad {ciudad}

# Listar ciudades Arcángel
php artisan arcangel:listar-ciudades
```

### Modo Arcángel
- Switch entre producción y desarrollo:
  ```bash
  ./switch_arcangel_mode.sh production
  ./switch_arcangel_mode.sh development
  ```
- Comando: `php artisan arcangel:mode-switch {modo}`

## 11. Archivos clave
- `app/Http/Controllers/ConductorController.php`
- `app/Http/Controllers/Api/ArcangelDriversController.php`
- `app/Models/VehicleOwnerHolderDriver.php`
- `app/Models/BlockedDriver.php`
- `app/Models/VehicleClass.php`
- `app/Services/ArcangelService.php`
- `app/Console/Commands/BuscarConductoresParaCotizacion.php`
- `resources/views/conductores/`

## 12. Migraciones relevantes
- `2025_03_13_163658_create_call_drivers_table.php`
- `2025_03_13_163754_create_blocked_drivers_table.php`
- `2025_08_27_105338_create_vehicle_classes_table.php`
- `2025_08_27_110019_create_bodyworks_table.php`
- `2025_09_19_222630_create_blocked_drivers_table.php`

## 13. Relacionado con
- [02-Integraciones-Externas](../../02-Integraciones-Externas/) — Arcángel API
- [07-Analisis-Llamadas-ElevenLabs](../07-Analisis-Llamadas-ElevenLabs/) — analytics
- [08-Analisis-Auditoria-Llamadas](../08-Analisis-Auditoria-Llamadas/) — auditoría
- [15-Gestion-Vehiculos](../15-Gestion-Vehiculos/) — mapeo de vehículos

## 14. Documentación adicional
Ver carpeta `05-Conductores-Vehiculos/`:
- `CORRECCION_AUTENTICACION_BUSCAR_CONDUCTORES.md`
- `CORRECCION_MAPEO_VEHICULOS_ARCANGEL.md`
- `FIX_CONDUCTORES_ACEPTADOS_NO_MOSTRABAN.md`
- `GUIA_PRUEBAS_CONDUCTORES.md`
