# 🚚 MÓDULO: GESTIÓN › VEHÍCULOS

## 1. ¿Para qué es este módulo?
Es el **puente entre los vehículos que reporta la API externa Arcángel y los pricings internos de CONALCA**. Permite normalizar y mapear los tipos de vehículo entre ambos sistemas (porque pueden tener nombres distintos para el mismo vehículo).

## 2. ¿Qué hace?
- Lista los **tipos de vehículos** registrados en Arcángel.
- Lista los **vehículos del pricing local**.
- Permite **vincular** un vehículo Arcángel con un pricing local (mapeo many-to-many).
- Permite **sincronizar** automáticamente con Arcángel para traer nuevos vehículos.
- Permite **buscar** vehículos disponibles por ciudad.
- Permite **cambiar** entre modo producción / desarrollo de Arcángel desde la UI.
- Lista las **ciudades** soportadas por Arcángel.

## 3. ¿Cómo funciona?

### Problema que resuelve
Arcángel puede llamar al mismo vehículo "CAMION 5T" mientras que el sistema interno de pricings usa "SENCILLO 5T". Sin este mapeo, no se podrían cruzar datos correctamente.

### Flujo de mapeo
```
1. Admin entra a /vehiculos
2. Ve dos paneles:
   - Vehículos Arcángel (CAMION 5T, TRACTOMULA, MINIMULA, etc.)
   - Vehículos Pricing local (SENCILLO 5T, MULA, DOBLE TROQUE, etc.)
3. Click "Agregar relación"
4. Modal: selecciona uno de cada lado
5. POST /vehiculos/relaciones → VehiculoController@agregarRelacion
6. Crea registro en vehiculos_relaciones (pivot)
7. Desde ahora, al buscar conductores con vehículo Arcángel "CAMION 5T",
   el sistema sabe que corresponde a pricing local "SENCILLO 5T"
```

### Flujo de sincronización
```
Click "Sincronizar" → VehiculoController@sincronizar
    ↓
ArcangelService.getVehiculosCercanos() / getClasesDisponibles()
    ↓
Por cada vehículo Arcángel:
  - Si NO existe en vehiculos_arcangel → crea
  - Si existe → actualiza atributos
    ↓
Reporta cuántos se sincronizaron
```

### Flujo de switch de modo
```
Admin entra a /vehiculos
    ↓
Selector: Producción / Desarrollo
    ↓
POST /vehiculos/cambiar-modo-arcangel
    ↓
Actualiza ARCANGEL_MODE en config o cache
    ↓
Próximas llamadas a Arcángel usan el modo elegido
```

## 4. ¿Cómo actúa en el sistema?
Es **infraestructura de datos** que afecta:
- Búsqueda de conductores en Arcángel (al filtrar por tipo)
- Cotizaciones del chat IA (al normalizar tipo de vehículo)
- Sugerencias de vehículo (`PricingController.suggestVehicles`)

Sin un mapeo correcto, las cotizaciones podrían ofrecer un vehículo y luego no encontrar conductores compatibles.

## 5. Componentes del código

### Rutas
| Método | URL | Función |
|--------|-----|---------|
| GET | `/vehiculos` | Panel principal |
| GET | `/vehiculos/sincronizar` | Sincronizar con Arcángel |
| POST | `/vehiculos/relaciones` | Crear relación |
| DELETE | `/vehiculos/relaciones/{id}` | Eliminar relación |
| GET | `/vehiculos/{id}/relaciones` | Ver relaciones de un vehículo |
| GET | `/vehiculos/ciudades` | Listar ciudades Arcángel |
| POST | `/vehiculos/buscar-ciudad` | Buscar vehículos por ciudad |
| POST | `/vehiculos/cambiar-modo-arcangel` | Switch prod/dev |

### Controlador
**`app/Http/Controllers/VehiculoController.php`**

**Métodos:**
- `index()` — panel con tipos y relaciones
- `sincronizar()` — sync con Arcángel
- `agregarRelacion(Request)` — vincular vehículo Arcángel ↔ Pricing
- `eliminarRelacion($id)` — desvincular
- `obtenerRelaciones($id)` — ver relaciones de un vehículo
- `getCiudades()` — ciudades disponibles
- `buscarVehiculosCiudad(Request)` — búsqueda directa por ciudad
- `cambiarModoArcangel(Request)` — switch producción/desarrollo
- `obtenerModoActual()` — modo actual

### Vista
**`resources/views/vehiculos/index.blade.php`** — panel split con ambas listas.

### Tablas involucradas
| Tabla | Función |
|-------|---------|
| `vehiculos_arcangel` | Tipos base traídos de Arcángel |
| `vehiculos_pricing` | Especificaciones de precio/capacidad locales |
| `vehiculos_relaciones` | Pivot many-to-many |
| `vehicle_class` | Clases globales (SENCILLO, TURBO, etc.) |
| `bodyworks` | Tipos de carrocería |

### Servicio
**`app/Services/ArcangelService.php`** — cliente HTTP de Arcángel:
- `getVehiculosCercanos(ciudad)`
- `getClasesDisponibles()`
- `filtrarVehiculos()`
- `clearCache()`

## 6. Modos de operación de Arcángel

### Modo Producción
- URL: `https://arcangel.conalca.com.co/api/`
- API Key: `ARCANGEL_API_KEY`
- Datos: conductores reales en producción

### Modo Desarrollo
- URL: `https://dev.arcangel.conalca.com.co/api/`
- API Key: `ARCANGEL_API_KEY_DEV`
- Datos: conductores de prueba (no se llamarán números reales)

### Cambiar modo
```bash
# Vía artisan
php artisan arcangel:mode-switch production
php artisan arcangel:mode-switch development

# Vía script
./switch_arcangel_mode.sh production
./switch_arcangel_mode.sh development

# Vía UI: /vehiculos → selector
```

## 7. Reglas de negocio
- **Relación many-to-many:** un vehículo Arcángel puede mapear a varios pricings (ej: CAMION 5T → SENCILLO 5T, TURBO 5T)
- **Caché:** las llamadas a Arcángel se cachean 60 min para reducir carga
- **Health check:** `/api/arcangel/health` verifica conectividad antes de operar

## 8. Permisos
| Rol | Acceso |
|-----|:------:|
| SUPER ADMIN | ✅ |
| JEFE COMERCIAL | ❌ |
| GERENTE DE CUENTA | ❌ |
| ASISTENTE COMERCIAL | ❌ |
| SAC | ✅ |
| PRICING | ❌ |

## 9. Operación
### Comandos
```bash
# Health check
curl https://conalcaia.conalca.com.co/api/arcangel/health

# Listar ciudades de Arcángel
php artisan arcangel:listar-ciudades

# Consultar localidad
php artisan arcangel:consultar-localidad "BOGOTA"

# Limpiar caché Arcángel
curl -X POST https://conalcaia.conalca.com.co/api/arcangel/clear-cache

# Verificar status
./check_arcangel_status.sh
```

## 10. Archivos clave
- `app/Http/Controllers/VehiculoController.php`
- `app/Http/Controllers/Api/ArcangelController.php`
- `app/Services/ArcangelService.php`
- `app/Console/Commands/ArcangelModeSwitch.php`
- `app/Console/Commands/ListarCiudadesArcangel.php`
- `app/Console/Commands/ConsultarLocalidadArcangel.php`
- `resources/views/vehiculos/index.blade.php`
- `config/arcangel.php`

## 11. Relacionado con
- [02-Integraciones-Externas](../../02-Integraciones-Externas/) — Arcángel API
- [09-Pricing](../09-Pricing/) — pricings locales
- [12-Gestion-Conductores](../12-Gestion-Conductores/) — conductores asociados

## 12. Documentación adicional
- `01-Guias-Generales/ARCANGEL_MODO_DESARROLLO.md`
- `02-Integraciones-Externas/ANALISIS_BACKEND_ARCANGEL.md`
- `02-Integraciones-Externas/DOCUMENTACION_ARCANGEL_API.md`
- `05-Conductores-Vehiculos/CORRECCION_MAPEO_VEHICULOS_ARCANGEL.md`
- `09-Manuales-API/Manual de uso API conexión ARCANGEL - CONALCA IA v1.0.pdf`
