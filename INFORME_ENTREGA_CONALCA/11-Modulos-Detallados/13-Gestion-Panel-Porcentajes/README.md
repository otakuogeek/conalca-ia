# % MÓDULO: GESTIÓN › PANEL DE PORCENTAJES

## 1. ¿Para qué es este módulo?
Es el **panel de configuración de porcentajes de comisión y márgenes** que aplica el sistema sobre los precios base. Es un módulo de **configuración administrativa** crítico porque afecta directamente el precio final que se ofrece a los clientes.

## 2. ¿Qué hace?
- Lista los **porcentajes configurados** por tipo de operación (importación, exportación, distribución, etc.).
- Permite **editar el valor** de cada porcentaje.
- Estos porcentajes los usa el **chat IA de cotizaciones** para sugerir precios al comercial.
- El comercial puede **ajustar manualmente** al cotizar (el valor de aquí es sólo la base sugerida).

## 3. ¿Cómo funciona?

### Flujo del admin configurando
```
1. Admin entra a /percentage-settings
2. Ve listado de porcentajes (con tipo, valor, descripción)
3. Click en uno → modo edición inline
4. Cambia el valor
5. PUT /percentage-settings/{id} → PercentageSettingController@update
6. Guarda en percentage_settings
7. Cambio impacta INMEDIATAMENTE las siguientes cotizaciones
```

### Flujo del chat IA usando el porcentaje
```
Usuario pide cotización con operation_type=IMPORTACION
    ↓
MCPAssistantService consulta PercentageSetting por IMPORTACION
    ↓
Obtiene porcentaje (ej: 17%)
    ↓
Calcula: precio_final = precio_base * (1 + 17/100)
    ↓
Sugiere ese precio al comercial
    ↓
Comercial puede modificar manualmente en PricingModal
```

## 4. ¿Cómo actúa en el sistema?
Es **fuente de configuración** para:
- Chat IA al cotizar (porcentaje base sugerido)
- Cálculos automáticos en `PricingController.suggestVehicles()`
- Reportes de rentabilidad

**Cambiar un valor aquí afecta inmediatamente** todas las cotizaciones nuevas. No afecta las cotizaciones ya creadas (el porcentaje queda registrado en cada `CotizacionModel`).

## 5. Componentes del código

### Rutas
| Método | URL | Función | Middleware |
|--------|-----|---------|------------|
| GET | `/percentage-settings` | Vista panel | `auth`, `role:SUPER ADMIN\|JEFE COMERCIAL` |
| PUT | `/percentage-settings/{percentageSetting}` | Actualizar | `auth`, `role:SUPER ADMIN\|JEFE COMERCIAL` |

### Controlador
**`app/Http/Controllers/PercentageSettingController.php`**

**Métodos:**
- `index()` — listar porcentajes
- `update(Request, PercentageSetting)` — actualizar valor

### Modelo
**`PercentageSetting`** (`percentage_settings` table):
- `operation_type` — `IMPORTACION`, `EXPORTACION`, `DISTRIBUCION`, etc.
- `cargo_type` — tipo de carga (refrigerado, peligroso, etc.)
- `percentage` — valor decimal (ej: 17.50)
- `description` — descripción legible
- `is_active`

### Vista
**`resources/views/percentage-settings/index.blade.php`** — tabla con edición inline.

### Migración
- `2025_12_03_070930_create_percentage_settings_table.php`

### Seeder
- `database/seeders/PercentageSettingsSeeder.php` — datos iniciales

## 6. Tipos de porcentajes típicos
- **Operación importación:** 15-20%
- **Operación exportación:** 12-18%
- **Distribución nacional:** 10-15%
- **Carga refrigerada:** + adicional
- **Carga peligrosa:** + adicional por riesgo
- **Operación con escolta:** ajuste especial

## 7. Reglas de negocio
- **Histórico no se guarda automáticamente** — el valor anterior se sobreescribe. Si se requiere auditoría, considerar implementar log de cambios.
- **Aplicación retroactiva:** NO. Las cotizaciones existentes mantienen su porcentaje original.
- **Default:** si no hay porcentaje para un tipo de operación, usa 17% por defecto (hardcoded).

## 8. Permisos
| Rol | Acceso |
|-----|:------:|
| SUPER ADMIN | ✅ Total |
| JEFE COMERCIAL | ✅ Total |
| GERENTE DE CUENTA | ❌ |
| ASISTENTE COMERCIAL | ❌ |
| SAC | ❌ |
| PRICING | ❌ |

## 9. Mantenimiento y precauciones
- **Cambios drásticos:** un cambio grande (ej: de 17% a 25%) puede romper la coherencia con cotizaciones que están en negociación. Avisar al equipo comercial antes de hacer cambios.
- **Auditoría:** considerar implementar tabla de log para registrar quién cambió qué y cuándo.
- **Backup:** antes de hacer cambios masivos, exportar la tabla.

## 10. Archivos clave
- `app/Http/Controllers/PercentageSettingController.php`
- `app/Models/PercentageSetting.php`
- `database/seeders/PercentageSettingsSeeder.php`
- `database/migrations/2025_12_03_070930_create_percentage_settings_table.php`
- `resources/views/percentage-settings/index.blade.php`

## 11. Relacionado con
- [09-Pricing](../09-Pricing/) — pricings base sobre los que se aplica %
- [14-Gestion-Tara](../14-Gestion-Tara/) — tara también afecta el cálculo
- [16-Gestion-Esquema-Seguridad](../16-Gestion-Esquema-Seguridad/) — recargos por seguridad

## 12. Mejoras pendientes
- Log de auditoría de cambios
- Vigencia temporal de porcentajes (válido desde - hasta)
- Porcentajes diferenciales por cliente (ya parcialmente cubierto por Esquema de Seguridad)
- Notificación al equipo cuando se cambia un porcentaje
