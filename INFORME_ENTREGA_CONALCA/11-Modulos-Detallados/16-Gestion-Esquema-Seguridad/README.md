# 🛡️ MÓDULO: GESTIÓN › ESQUEMA DE SEGURIDAD ⭐

## 1. ¿Para qué es este módulo?
Es el **motor de reglas de seguridad** del sistema. Define qué **medidas de seguridad** son obligatorias para cada tipo de carga, según el producto y el rango de precio. Cuando un comercial cotiza una carga peligrosa o valiosa, este módulo determina automáticamente si requiere candado satelital, escolta, GPS, etc. — y esos costos se suman al pricing.

Es **uno de los módulos más críticos y más recientes** (migraciones de abril 2026).

## 2. ¿Qué hace?
- **Catálogo de productos** que requieren reglas especiales de seguridad.
- **Catálogo de medidas** de seguridad: candado satelital, jen-set, kit de derrames, escolta, GPS, pictogramas, combustible adicional.
- **Rangos de precio** del producto → desencadenan medidas obligatorias (productos de alto valor exigen escolta).
- **Asignación por cliente** — algunos clientes tienen esquemas customizados.
- **Override por usuario** — un comercial puede tener reglas personalizadas dentro del rango asignado a su cliente.
- El chat IA consulta este esquema **antes** de cerrar la cotización para activar campos en `group_cotizations`:
  - `candado_satelital`
  - `jen_set`
  - `combustible`
  - `kit_derrames`
  - `pictogramas`

## 3. ¿Cómo funciona?

### Estructura de decisión
```
Producto + Rango de precio + Cliente
       ↓
Esquema de Seguridad determina
       ↓
{medidas_obligatorias: ["candado_satelital", "escolta", "gps"]}
       ↓
Chat IA suma costos al pricing
       ↓
Group_cotization se crea con los flags activos
```

### Flujo cuando el chat IA cotiza
```
Usuario: "Necesito flete para 30 toneladas de combustible, $200,000,000"
    ↓
MCPAssistantService identifica:
  - producto: "combustible"
  - valor: 200,000,000
    ↓
Llama a SecuritySchemaController.getSchemaForPricing()
    ↓
SecuritySchema lookup:
  - Producto "combustible" → categoría "peligroso"
  - Rango de precio: 100M-500M
  - Cliente X tiene override? → sí: requiere escolta
    ↓
Devuelve:
  {
    candado_satelital: true,
    jen_set: false,
    combustible: true,
    kit_derrames: true,
    pictogramas: true,
    escolta_required: true,
    cost_extra: 1500000
  }
    ↓
Chat IA suma cost_extra al precio base
    ↓
Al crear el GroupCotization, los flags quedan registrados
```

### Flujo del admin configurando
```
1. SUPER ADMIN entra a /security-schema
2. Crea producto (productos especiales)
3. Define rangos de precio asociados al producto
4. Define qué medidas se activan en cada rango
5. (Opcional) Asigna esquemas customizados a clientes específicos
6. (Cualquier user) Puede crear sus propios overrides personales
```

## 4. ¿Cómo actúa en el sistema?
Es **fuente crítica de configuración** que afecta:
- **Chat IA al cotizar** — activa flags y suma costos
- **Group_cotizations** — guarda los flags
- **Cliente al ver cotización** — ve los costos detallados
- **Conductor durante llamada** — si necesita escolta, el agente IA lo menciona
- **Solicitud de Transporte** — los campos de seguridad se prellenan

Es un módulo **defensivo** — evita ofrecer cotizaciones sin seguridad adecuada para cargas peligrosas/valiosas.

## 5. Componentes del código

### Rutas
**Sin permiso especial (cualquier usuario autenticado):**
| Método | URL | Función |
|--------|-----|---------|
| GET | `/security-schema/` | Vista principal |
| GET | `/security-schema/data` | Datos JSON |
| GET | `/security-schema/for-pricing` | Datos optimizados para chat IA |
| POST | `/security-schema/user-override` | Crear override personal |
| DELETE | `/security-schema/user-override/{baseRangeId}` | Eliminar override |

**Sólo SUPER ADMIN (CRUD master):**
| Método | URL | Función |
|--------|-----|---------|
| GET | `/security-schema/search-products` | Buscar productos |
| POST | `/security-schema/products` | Crear producto |
| DELETE | `/security-schema/products/{id}` | Eliminar producto |
| POST | `/security-schema/price-ranges` | Crear rango |
| PUT | `/security-schema/price-ranges/{id}` | Actualizar rango |
| DELETE | `/security-schema/price-ranges/{id}` | Eliminar rango |
| GET | `/security-schema/search-clients` | Buscar clientes |
| GET | `/security-schema/assigned-clients` | Clientes asignados |
| POST | `/security-schema/assign-client` | Asignar esquema a cliente |
| DELETE | `/security-schema/unassign-client/{id}` | Quitar asignación |

### Controlador
**`app/Http/Controllers/SecuritySchemaController.php`**

**Métodos (15):**
- `index()` — vista
- `getData()` — datos generales
- `searchProducts(Request)`, `storeProduct(Request)`, `destroyProduct($id)`
- `storePriceRange(Request)`, `updatePriceRange(Request, $id)`, `destroyPriceRange($id)`
- `searchClients(Request)`, `getAssignedClients()`, `assignClient(Request)`, `unassignClient($id)`
- `getSchemaForPricing()` — endpoint principal usado por el chat IA
- `storeUserOverride(Request)` — override personal
- `deleteUserOverride($baseRangeId)`

### Vista
**`resources/views/security-schema/index.blade.php`** — panel administrativo.

### Componentes React
**`resources/js/components/SecuritySchema/`** — UI moderna con búsqueda, formularios y tablas dinámicas.

### Modelos (6)
| Modelo | Tabla | Función |
|--------|-------|---------|
| `SecuritySchemaProduct` | security_schema_products | Catálogo de productos con esquemas |
| `SecuritySchemaMeasure` | security_schema_measures | Medidas (candado, jen-set, etc.) |
| `SecuritySchemaPriceRange` | security_schema_price_ranges | Rangos de precio base |
| `SecuritySchemaClientAssignment` | security_schema_client_assignments | Asignaciones por cliente |
| `SecuritySchemaUserMeasure` | security_schema_user_measures | Overrides por usuario (medidas) |
| `SecuritySchemaUserPriceRange` | security_schema_user_price_ranges | Overrides por usuario (rangos) |

### Migraciones
- `2026_04_16_000001_create_security_schema_tables.php` (creación)
- `2026_04_16_000002_add_product_code_to_security_schema_products.php`
- `2026_04_20_000001_update_security_schema_categories_and_client_assignments.php`
- `2026_04_20_064756_add_category_to_security_schema_products_table.php`
- `2026_04_20_100001_create_security_schema_user_price_ranges_table.php`

## 6. Tipos de medidas de seguridad

| Medida | Campo en `group_cotizations` | Descripción |
|--------|-----------------------------|-------------|
| Candado satelital | `candado_satelital` | Bloqueo del contenedor monitoreado por satélite |
| Jen-set | `jen_set` | Generador de respaldo para carga refrigerada |
| Combustible | `combustible` | Combustible adicional incluido |
| Kit de derrames | `kit_derrames` | Para mercancía peligrosa |
| Pictogramas | `pictogramas` | Etiquetado de carga peligrosa |
| Escolta | (campo separado) | Acompañamiento de seguridad |
| GPS | (campo separado) | Monitoreo de tránsito |

## 7. Categorías de productos
- **Mercancía peligrosa:** químicos, combustibles, gases
- **Mercancía valiosa:** electrónica, productos de alta gama
- **Mercancía refrigerada:** alimentos perecederos, vacunas
- **Mercancía frágil:** vidrio, cerámica
- **Mercancía normal:** sin requerimientos especiales

## 8. Estructura jerárquica de aplicación
```
1. Esquema base por producto + rango de precio
   ↓ (puede ser sobrescrito por)
2. Esquema customizado para un cliente específico
   ↓ (puede ser sobrescrito por)
3. Override personal del comercial (dentro del rango de su cliente)
```

## 9. Reglas de negocio
- **Default seguro:** si no hay esquema definido, se aplica el más estricto
- **Validación:** algunos productos NUNCA pueden cotizarse sin escolta (configurable)
- **Auditoría:** los overrides quedan registrados con `user_id` y timestamp
- **Cliente asignado:** un cliente puede tener su propio esquema completo

## 10. Permisos
| Rol | Lectura | Override personal | Master CRUD |
|-----|:-------:|:-----------------:|:-----------:|
| SUPER ADMIN | ✅ | ✅ | ✅ |
| JEFE COMERCIAL | ✅ | ✅ | ❌ |
| GERENTE DE CUENTA | ✅ | ✅ | ❌ |
| ASISTENTE COMERCIAL | ✅ | ✅ | ❌ |
| SAC | ❌ | ❌ | ❌ |
| PRICING | ❌ | ❌ | ❌ |

## 11. Endpoint clave: `getSchemaForPricing()`
Este endpoint es el que **consume el chat IA**. Devuelve un JSON optimizado con todas las reglas aplicables a un producto + rango de precio + cliente:

```json
{
  "product_code": "PROD-001",
  "category": "peligroso",
  "applicable_ranges": [
    {
      "min": 0,
      "max": 100000000,
      "measures": ["candado_satelital", "pictogramas"]
    },
    {
      "min": 100000000,
      "max": 500000000,
      "measures": ["candado_satelital", "pictogramas", "kit_derrames", "escolta"]
    }
  ],
  "user_override": null,
  "client_assignment": null
}
```

## 12. Archivos clave
- `app/Http/Controllers/SecuritySchemaController.php`
- `app/Models/SecuritySchemaProduct.php`
- `app/Models/SecuritySchemaMeasure.php`
- `app/Models/SecuritySchemaPriceRange.php`
- `app/Models/SecuritySchemaClientAssignment.php`
- `app/Models/SecuritySchemaUserMeasure.php`
- `app/Models/SecuritySchemaUserPriceRange.php`
- `resources/views/security-schema/index.blade.php`
- `resources/js/components/SecuritySchema/`

## 13. Relacionado con
- [02-Clientes](../02-Clientes/) — asignación por cliente
- [09-Pricing](../09-Pricing/) — costos extra se suman al pricing
- [13-Gestion-Panel-Porcentajes](../13-Gestion-Panel-Porcentajes/) — % de seguridad
- [17-Control-Usuarios](../17-Control-Usuarios/) — overrides por usuario

## 14. Mejoras pendientes
- Log de auditoría de cambios en esquemas master
- Reportes de uso (qué medidas se aplican más, costos generados)
- Versionado de esquemas (esquema vigente en una fecha pasada)
- Notificación automática cuando se cambia una regla crítica
