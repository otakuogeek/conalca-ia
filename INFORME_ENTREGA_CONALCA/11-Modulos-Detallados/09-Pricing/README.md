# 💰 MÓDULO: PRICING

## 1. ¿Para qué es este módulo?
Es el **motor de tarifas** del sistema. Contiene la base de datos maestra de **precios por ruta y tipo de vehículo**. Es la fuente de verdad sobre cuánto cuesta cada operación de transporte y se usa tanto por el chat IA de cotizaciones como por el sistema manual.

## 2. ¿Qué hace?
- Lista **todos los pricings** registrados con filtros avanzados.
- Permite **crear, editar, eliminar** soluciones de precio.
- Soporta **importación masiva** desde Excel.
- Soporta **exportación a Excel** (plantilla + datos).
- Permite **actualización masiva (bulk)** de precios.
- Sugiere **vehículos óptimos** según peso y destino.
- Calcula **estadísticas de rentabilidad** por ruta.
- Provee una **guía de capacidades** de vehículos.
- Mantiene **histórico** de cambios de precio por ruta.

## 3. ¿Cómo funciona?

### Flujo del comercial al cotizar
```
Comercial pide cotización Bogotá → Medellín, 30T, paletizado
    ↓
Chat IA (MCPAssistantService) invoca tool: search_pricings_by_route
    ↓
PricingController busca en `pricings` por origen, destino, tipo_vehiculo
    ↓
Devuelve N opciones de precio
    ↓
Sistema aplica porcentaje (PercentageSetting) y tara (TaraSetting)
    ↓
Genera propuesta de cotización
    ↓
Comercial puede modificar % final en PricingModal
```

### Flujo de actualización masiva
```
1. Admin entra a /pricing → click "Importar Excel"
2. Sube archivo .xlsx con plantilla esperada
3. PricingImport / PricingUpImport / PricingSMImport procesa cada fila
4. Valida formato, normaliza ciudades
5. Crea o actualiza Pricing
6. Reporta filas procesadas y errores
```

### Flujo de exportación
```
Click "Exportar plantilla" → /pricing/export
    ↓
PricingTemplateExport / SimplePricingTemplateExport genera .xlsx
    ↓
Descarga con headers + datos actuales (para edición masiva)
```

## 4. ¿Cómo actúa en el sistema?
Es **fuente de datos crítica** para:
- **Chat IA de cotizaciones** (sugiere precios)
- **Cotizaciones manuales** (busca pricings al crear)
- **Reportes** de rentabilidad
- **Sistema MCP** (herramientas `search_pricings_by_route`, `get_pricing_by_vehicle_type`)

Otros módulos que lo consultan:
- `QuoteAssistantService` / `MCPAssistantService`
- `PricingController` directamente
- Servidor MCP Python

## 5. Componentes del código

### Rutas
| Método | URL | Controlador | Función |
|--------|-----|-------------|---------|
| GET | `/pricing` | closure | Panel principal |
| POST | `/api/pricings-solutions` | `PricingController@store` | Crear |
| PUT | `/api/pricings-solutions/{id}` | `PricingController@update` | Actualizar |
| GET | `/api/pricings-solutions/latest-by-route` | `PricingController@latestByRoute` | Último por ruta |
| POST | `/api/pricing-suggestions` | `PricingController@suggestVehicles` | Sugerir vehículos |
| GET | `/api/pricing-rentability-stats` | `PricingController@rentabilityStats` | Stats rentabilidad |
| GET | `/api/pricing/vehicle-guide` | `PricingController@vehicleCapacityGuide` | Guía capacidades |

**API REST completa** (`PricingApiController`):
- `index`, `store`, `show`, `update`, `destroy`
- `bulkStore`, `bulkUpdate`, `bulkDestroy`

### Controladores
- **`app/Http/Controllers/Api/PricingController.php`** — operaciones específicas
- **`app/Http/Controllers/Api/PricingApiController.php`** — API REST CRUD + bulk
- **`app/Http/Controllers/PricingExportController.php`** — exportación

### Componente Livewire
**`app/Livewire/PricingIndex.php`** con propiedades:
- `$pricings` — listado
- `$showModal`, `$showModalType` — modales
- Filtros: `$filter_origin`, `$filter_destination`, `$type_pricing`, `$vehicle_type`
- Campos de pricing: `$documents`, `$extra`, `$price_extra`, `$price_extra2`, `$download_target`, `$load_target`, `$iva`, `$price_person`, `$origin`, `$destination`, `$price`, `$event`, `$type_send`, `$save_box`, `$time_day`, `$return`, `$container`

**`app/Livewire/ModalPricing.php`** — modal de edición individual.

### Vista
**`resources/views/pricing/show.blade.php`** — panel principal.

### Modelo
**`Pricing`** (`pricings` table):
- `origin`, `destination` — ruta
- `vehicle_type` — tipo de vehículo
- `price` — precio base
- `iva` — IVA aplicable
- `documents` — costos de documentos
- `extra`, `price_extra`, `price_extra2` — costos adicionales
- `download_target`, `load_target` — descargues/cargues
- `price_person` — precio por persona (logística)
- `event`, `type_send`, `save_box`, `time_day`, `return`, `container` — campos específicos

### Imports (Maatwebsite/Excel)
- `app/Imports/PricingImport.php` — importación estándar
- `app/Imports/PricingSMImport.php` — variante simplificada
- `app/Imports/PricingUpImport.php` — actualización masiva
- `app/Imports/DataColumnImport.php` — columnas adicionales

### Exports
- `app/Exports/PricingTemplateExport.php` — plantilla con headers
- `app/Exports/SimplePricingTemplateExport.php` — plantilla simple

### Migración
- `2024_03_16_062003_create_pricings_table.php`
- `2026_02_16_192907_add_performance_indexes_to_cotizaciones_tables.php` (índices)

## 6. Lógica de pricing

### Cálculo de precio final
```
precio_final = pricing.price                  (precio base)
             + pricing.iva                    (IVA)
             + pricing.documents              (docs)
             + pricing.extra + price_extra    (costos adicionales)
             × (1 + porcentaje/100)           (margen comercial)
```

El porcentaje viene de **`PercentageSetting`** (módulo aparte) y depende del tipo de operación.

### Reglas de selección
1. Buscar por **(origen, destino, vehicle_type)** exacto
2. Si no existe → buscar por **(origen, destino)** y ofrecer varios vehículos
3. Si no existe ruta → ofrecer creación de nuevo pricing al admin
4. **`latestByRoute`** retorna el último pricing creado para esa ruta (asume que es el vigente)

## 7. Sugerencia automática de vehículo
**`suggestVehicles(Request)`** recibe `peso, destino` y devuelve vehículos cuya **capacidad >= peso** ordenados por costo unitario. Esto ayuda al comercial a elegir el vehículo más eficiente.

## 8. Reglas de negocio importantes
- **Ciudades normalizadas:** se guardan en MAYÚSCULAS sin acentos (`BOGOTA`, `MEDELLIN`)
- **Sin duplicados:** misma combinación (origin, destination, vehicle_type) debería ser única
- **Histórico:** los pricings no se eliminan físicamente — se marca `deleted_at` para mantener trazabilidad
- **Indexación crítica:** los índices en `origin`, `destination`, `vehicle_type` son fundamentales para el rendimiento del chat IA

## 9. Permisos
| Rol | Acceso |
|-----|:------:|
| SUPER ADMIN | ✅ Todo |
| JEFE COMERCIAL | ✅ Ver/Editar |
| GERENTE DE CUENTA | ❌ |
| ASISTENTE COMERCIAL | ❌ |
| SAC | ❌ |
| PRICING | ✅ Ver/Editar (rol dedicado) |
| API_PRICING | ✅ API (sistemas externos) |

## 10. Operación
### Importación masiva paso a paso
1. Descargar plantilla: `/pricing/export-template`
2. Llenar Excel con columnas requeridas
3. Subir vía formulario en `/pricing`
4. Sistema valida y reporta errores
5. Filas correctas se importan, las incorrectas se rechazan

### Comandos
```bash
# Verificar pricing por ruta
php artisan tinker
>>> Pricing::where('origin', 'BOGOTA')->where('destination', 'MEDELLIN')->get();
```

## 11. Archivos clave
- `app/Http/Controllers/Api/PricingController.php`
- `app/Http/Controllers/Api/PricingApiController.php`
- `app/Http/Controllers/PricingExportController.php`
- `app/Livewire/PricingIndex.php`, `ModalPricing.php`
- `app/Models/Pricing.php`
- `app/Imports/Pricing*.php`
- `app/Exports/*PricingTemplateExport.php`
- `resources/views/pricing/show.blade.php`

## 12. Relacionado con
- [02-Clientes](../02-Clientes/) — clientes que cotizan
- [13-Gestion-Panel-Porcentajes](../13-Gestion-Panel-Porcentajes/) — % de comisión sobre el pricing
- [14-Gestion-Tara](../14-Gestion-Tara/) — tara para cálculo de peso neto
- [15-Gestion-Vehiculos](../15-Gestion-Vehiculos/) — mapeo vehículos Arcángel ↔ pricing

## 13. Documentación adicional
- `08-Correcciones-Fixes/DOCUMENTACION_BULK_UPDATE_PRICINGS.md`
- `02-Integraciones-Externas/ENDPOINT_FILTRADO_VEHICULOS.md`
