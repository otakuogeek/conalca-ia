# 🔧 LISTADO DETALLADO DE 23 HERRAMIENTAS MCP - CONALCA
**Análisis Técnico Completo: Métodos, Tablas y Campos**

---

## 📊 RESUMEN EJECUTIVO

| **Estadística** | **Valor** |
|----------------|-----------|
| **Total Herramientas** | **23** |
| **Métodos HTTP** | Todas usan **POST** (JSON-RPC 2.0) |
| **Operaciones CRUD** | 18 GET, 3 POST, 1 PUT, 1 DELETE |
| **Tablas Afectadas** | 5 tablas principales |
| **Herramientas Nuevas** | 11 (grupos, precios, viajes, información y formularios) |

---

## 📋 LISTADO COMPLETO DE HERRAMIENTAS

### 📞 **CATEGORÍA: GESTIÓN DE LLAMADAS (6 herramientas)**

#### **1. get_llamadas**
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `GET` (Lectura) |
| **Descripción** | Obtiene todas las llamadas telefónicas con paginación opcional |
| **Parámetros** | `page` (int), `limit` (int) |
| **Tabla Principal** | `llamadas` |
| **Campos Afectados** | `id_llamada`, `telefono`, `numero_destino`, `estado`, `observaciones`, `fecha_llamada`, `conversation_id`, `chofer_id`, `id_cotizacion` |
| **Uso Típico** | Listar todas las llamadas para dashboard administrativo |

#### **2. get_llamada_by_id**
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `GET` (Lectura) |
| **Descripción** | Obtiene información detallada de una llamada específica |
| **Parámetros** | `llamada_id` (int, requerido) |
| **Tabla Principal** | `llamadas` |
| **Campos Afectados** | Todos los campos de la tabla `llamadas` |
| **Uso Típico** | Consultar detalles específicos de una llamada |

#### **3. create_llamada**
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `POST` (Creación) |
| **Descripción** | Crea una nueva llamada telefónica en el sistema |
| **Parámetros** | `telefono` (string, req), `estado` (string), `observaciones` (string) |
| **Tabla Principal** | `llamadas` |
| **Campos Afectados** | `telefono`, `numero_destino`, `estado`, `observaciones`, `fecha_llamada` |
| **Uso Típico** | Registrar nueva llamada desde ElevenLabs |

#### **4. update_llamada_status**
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `PUT` (Actualización) |
| **Descripción** | Actualiza el estado de una llamada existente |
| **Parámetros** | `llamada_id` (int, req), `new_status` (string, req) |
| **Tabla Principal** | `llamadas` |
| **Campos Afectados** | `estado` |
| **Uso Típico** | Cambiar estado de llamada durante conversación |

#### **5. get_llamadas_by_telefono**
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `GET` (Lectura) |
| **Descripción** | Busca llamadas asociadas a un número de teléfono |
| **Parámetros** | `telefono` (string, req), `tipo_busqueda` (string) |
| **Tabla Principal** | `llamadas` |
| **Campos Afectados** | Búsqueda en `telefono` y `numero_destino` |
| **Uso Típico** | Historial de llamadas de un cliente específico |

#### **6. activate_conversation**
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `PUT` (Actualización) |
| **Descripción** | Activa conversación usando conversation_id de ElevenLabs |
| **Parámetros** | `conversation_id` (string, req), `new_status` (string), `call_notes` (string) |
| **Tabla Principal** | `llamadas` |
| **Campos Afectados** | `conversation_id`, `estado`, `observaciones` |
| **Uso Típico** | Sincronizar estado con ElevenLabs |

---

### 🚛 **CATEGORÍA: VEHÍCULOS Y COTIZACIONES (3 herramientas)**

#### **7. get_cotizaciones**
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `GET` (Lectura) |
| **Descripción** | Lista cotizaciones de vehículos con paginación |
| **Parámetros** | `page` (int), `limit` (int) |
| **Tabla Principal** | `cotizacion_models` |
| **Campos Afectados** | `id`, `group_cotizations_id`, `pricing_id`, `ciudad_origen`, `ciudad_destino`, `modelo`, `marca`, `precio` |
| **Uso Típico** | Mostrar catálogo de cotizaciones disponibles |

#### **8. get_vehicle_by_telefono_conductor**
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `GET` (Lectura) |
| **Descripción** | Busca vehículos por teléfono del conductor |
| **Parámetros** | `telefono` (string, req) |
| **Tabla Principal** | `vehicle_owner_holder_driver` |
| **Campos Afectados** | `placa`, `conductor`, `cedula`, `telefono`, `propietario`, `poseedor` |
| **Uso Típico** | Identificar vehículo durante llamada telefónica |

#### **9. get_chofer_by_placa**
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `GET` (Lectura) |
| **Descripción** | Obtiene información completa del chofer por placa |
| **Parámetros** | `placa` (string, req) |
| **Tabla Principal** | `vehicle_owner_holder_driver` |
| **Campos Afectados** | Todos los campos de la tabla |
| **Uso Típico** | Verificar datos del conductor y vehículo |

---

### 🤖 **CATEGORÍA: INTEGRACIÓN ELEVENLABS (3 herramientas)**

#### **10. process_elevenlabs_event**
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `POST/PUT` (Creación/Actualización) |
| **Descripción** | Procesa eventos de webhook de ElevenLabs |
| **Parámetros** | `event_type` (string, req), `conversation_id` (string), `phone_number` (string) |
| **Tabla Principal** | `llamadas` |
| **Campos Afectados** | `conversation_id`, `telefono`, `estado`, `observaciones` |
| **Uso Típico** | Sincronizar eventos de IA con base de datos |

#### **11. generate_transport_offer**
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `GET` (Lectura compleja) |
| **Descripción** | Genera oferta personalizada usando conversation_id |
| **Parámetros** | `conversation_id` (string, req) |
| **Tablas Principales** | `llamadas`, `cotizacion_models`, `vehicle_owner_holder_driver` |
| **Campos Afectados** | JOIN entre múltiples tablas para generar oferta |
| **Uso Típico** | Crear mensaje comercial personalizado para cliente |

#### **12. save_driver_decision**
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `POST` (Creación) |
| **Descripción** | Guarda decisión del chofer sobre oferta de transporte |
| **Parámetros** | `cotizacion_model_id` (int, req), `driver_id` (int, req), `decision` (int, req) |
| **Tabla Principal** | `driver_decisions` (tabla de decisiones) |
| **Campos Afectados** | `cotizacion_model_id`, `driver_id`, `decision`, `created_at` |
| **Uso Típico** | Registrar aceptación/rechazo de ofertas |

---

### 👥 **CATEGORÍA: GESTIÓN DE GRUPOS (4 herramientas - NUEVAS) ⭐**

#### **13. get_group_cotizations** ⭐ **NUEVA**
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `GET` (Lectura) |
| **Descripción** | Lista grupos de cotizaciones con paginación |
| **Parámetros** | `limit` (int), `offset` (int) |
| **Tabla Principal** | `group_cotizations` |
| **Campos Afectados** | `id`, `user_id`, `client_id`, `type`, `reference`, `status`, `created_at`, `updated_at` |
| **Uso Típico** | Administrar grupos organizacionales de cotizaciones |

#### **14. get_group_cotizations_by_user** ⭐ **NUEVA**
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `GET` (Lectura) |
| **Descripción** | Obtiene grupos de cotizaciones de un usuario específico |
| **Parámetros** | `user_id` (int, req) |
| **Tabla Principal** | `group_cotizations` |
| **Campos Afectados** | Filtro por `user_id`, retorna todos los campos |
| **Uso Típico** | Dashboard personalizado por usuario |

#### **15. get_cotizacion_with_group_info** ⭐ **NUEVA**
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `GET` (Lectura con JOIN) |
| **Descripción** | Cotización específica con información completa del grupo |
| **Parámetros** | `cotizacion_id` (int, req) |
| **Tablas Principales** | `cotizacion_models` JOIN `group_cotizations` |
| **Campos Afectados** | Relación FK: `cotizacion_models.group_cotizations_id → group_cotizations.id` |
| **Uso Típico** | Vista detallada de cotización con contexto de grupo |

#### **16. get_cotizations_by_group** ⭐ **NUEVA**
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `GET` (Lectura) |
| **Descripción** | Todas las cotizaciones que pertenecen a un grupo |
| **Parámetros** | `group_id` (int, req) |
| **Tabla Principal** | `cotizacion_models` |
| **Campos Afectados** | Filtro por `group_cotizations_id` |
| **Uso Típico** | Listar cotizaciones agrupadas por proyecto/cliente |

---

### 💰 **CATEGORÍA: GESTIÓN DE PRECIOS (4 herramientas - NUEVAS) ⭐**

#### **17. get_pricings** ⭐ **NUEVA**
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `GET` (Lectura) |
| **Descripción** | Lista precios con paginación |
| **Parámetros** | `limit` (int), `offset` (int) |
| **Tabla Principal** | `pricings` |
| **Campos Afectados** | `id`, `vehicle_type`, `origin`, `destination`, `price`, `type_pricing`, `weight_from`, `weight_to` |
| **Uso Típico** | Catálogo completo de precios disponibles |

#### **18. get_pricing_by_vehicle_type** ⭐ **NUEVA**
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `GET` (Lectura) |
| **Descripción** | Precios filtrados por tipo de vehículo |
| **Parámetros** | `vehicle_type` (string, req) |
| **Tabla Principal** | `pricings` |
| **Campos Afectados** | Filtro por `vehicle_type`, retorna todos los campos |
| **Uso Típico** | Precios específicos para camión, turbo, sencillo, etc. |

#### **19. search_pricings_by_route** ⭐ **NUEVA**
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `GET` (Lectura) |
| **Descripción** | Busca precios por ruta específica (origen y destino) |
| **Parámetros** | `origin` (string), `destination` (string) |
| **Tabla Principal** | `pricings` |
| **Campos Afectados** | Filtro por `origin` y `destination` |
| **Uso Típico** | Cotización automática Bogotá → Medellín |

#### **20. get_cotizacion_with_pricing_info** ⭐ **NUEVA**
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `GET` (Lectura con JOIN) |
| **Descripción** | Cotización específica con información completa de precios |
| **Parámetros** | `cotizacion_id` (int, req) |
| **Tablas Principales** | `cotizacion_models` JOIN `pricings` |
| **Campos Afectados** | Relación FK: `cotizacion_models.pricing_id → pricings.id` |
| **Uso Típico** | Vista completa de cotización con detalles de precios |

#### **21. precioviaje** ⭐ **NUEVA - ESPECIALIZADA**
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `GET` (Lectura con JOIN) |
| **Descripción** | **Obtiene el precio específico de un viaje para responder cuando el chofer pregunta el valor** |
| **Parámetros** | `cotizacion_id` (int, req) |
| **Tablas Principales** | `cotizacion_models` → `pricings` (vía pricing_id) |
| **Campos Afectados** | `pricing_id`, `price`, `origin`, `destination`, `vehicle_type` |
| **Proceso** | 1. Busca `pricing_id` en cotización → 2. Consulta precio en tabla `pricings` |
| **Uso Típico** | **Respuesta directa al chofer: "El precio del viaje es $X COP"** |

---

## 🗄️ MAPEO DE TABLAS Y RELACIONES

### **Tabla 1: `llamadas`**
```sql
-- Campos principales
id_llamada (INT PRIMARY KEY)
telefono (VARCHAR(20))
numero_destino (VARCHAR(20))
estado (ENUM)
observaciones (TEXT)
fecha_llamada (TIMESTAMP)
conversation_id (VARCHAR(255))
chofer_id (INT)
id_cotizacion (INT)

-- Herramientas que la usan
1. get_llamadas (READ)
2. get_llamada_by_id (READ)
3. create_llamada (CREATE)
4. update_llamada_status (UPDATE)
5. get_llamadas_by_telefono (READ)
6. activate_conversation (UPDATE)
10. process_elevenlabs_event (CREATE/UPDATE)
11. generate_transport_offer (READ - JOIN)
```

### **Tabla 2: `cotizacion_models`**
```sql
-- Campos principales
id (BIGINT UNSIGNED PRIMARY KEY)
group_cotizations_id (BIGINT UNSIGNED) -- FK ⭐ NUEVA
pricing_id (BIGINT UNSIGNED) -- FK ⭐ NUEVA
ciudad_origen (VARCHAR(100))
ciudad_destino (VARCHAR(100))
modelo (VARCHAR(100))
marca (VARCHAR(100))
precio (DECIMAL(10,2))

-- Herramientas que la usan
7. get_cotizaciones (READ)
11. generate_transport_offer (READ - JOIN)
12. save_driver_decision (REFERENCE)
15. get_cotizacion_with_group_info (READ - JOIN) ⭐
16. get_cotizations_by_group (READ) ⭐
20. get_cotizacion_with_pricing_info (READ - JOIN) ⭐
```

### **Tabla 3: `vehicle_owner_holder_driver`**
```sql
-- Campos principales
id (INT PRIMARY KEY)
placa (VARCHAR(10))
conductor (VARCHAR(100))
cedula (VARCHAR(20))
telefono (VARCHAR(20))
propietario (VARCHAR(100))
poseedor (VARCHAR(100))

-- Herramientas que la usan
8. get_vehicle_by_telefono_conductor (READ)
9. get_chofer_by_placa (READ)
11. generate_transport_offer (READ - JOIN)
```

### **Tabla 4: `group_cotizations` ⭐ NUEVA**
```sql
-- Campos principales
id (BIGINT UNSIGNED PRIMARY KEY)
user_id (INT)
client_id (INT)
type (VARCHAR(50))
reference (VARCHAR(100))
status (VARCHAR(50))
created_at (TIMESTAMP)
updated_at (TIMESTAMP)

-- Herramientas que la usan
13. get_group_cotizations (READ) ⭐
14. get_group_cotizations_by_user (READ) ⭐
15. get_cotizacion_with_group_info (READ - JOIN) ⭐
16. get_cotizations_by_group (READ - FILTER) ⭐
```

### **Tabla 5: `pricings` ⭐ NUEVA**
```sql
-- Campos principales
id (BIGINT UNSIGNED PRIMARY KEY)
vehicle_type (VARCHAR(100))
origin (VARCHAR(100))
destination (VARCHAR(100))
price (DECIMAL(10,2))
type_pricing (VARCHAR(50))
weight_from (DECIMAL(8,2))
weight_to (DECIMAL(8,2))

-- Herramientas que la usan
17. get_pricings (READ) ⭐
18. get_pricing_by_vehicle_type (READ) ⭐
19. search_pricings_by_route (READ) ⭐
20. get_cotizacion_with_pricing_info (READ - JOIN) ⭐
```

---

## 🔗 RELACIONES FOREIGN KEY ESTABLECIDAS ⭐

### **Relación 1: Cotizaciones ↔ Grupos**
```sql
ALTER TABLE cotizacion_models 
ADD CONSTRAINT fk_cotizacion_group 
FOREIGN KEY (group_cotizations_id) REFERENCES group_cotizations(id);

-- Herramientas que usan esta relación:
15. get_cotizacion_with_group_info
16. get_cotizations_by_group
```

### **Relación 2: Cotizaciones ↔ Precios**
```sql
ALTER TABLE cotizacion_models 
ADD CONSTRAINT fk_cotizacion_pricing 
FOREIGN KEY (pricing_id) REFERENCES pricings(id);

-- Herramientas que usan esta relación:
20. get_cotizacion_with_pricing_info
```

---

## 📊 ESTADÍSTICAS DE USO POR OPERACIÓN CRUD

| **Operación** | **Cantidad** | **Herramientas** |
|---------------|--------------|------------------|
| **GET (Lectura)** | **18** | 1,2,5,7,8,9,11,13,14,15,16,17,18,19,20,21,22,23 |
| **POST (Creación)** | **3** | 3,10,12 |
| **PUT (Actualización)** | **2** | 4,6 |
| **DELETE** | **0** | Ninguna |

---

## ✅ RESUMEN TÉCNICO FINAL

| **Aspecto** | **Detalle** |
|-------------|-------------|
| **Total Herramientas** | **23 activas** |
| **Protocolo** | **JSON-RPC 2.0 sobre HTTP POST** |
| **Tablas de BD** | **5 tablas principales** |
| **Relaciones FK** | **2 nuevas relaciones establecidas** |
| **Operaciones CRUD** | **18 GET, 3 POST, 2 PUT, 0 DELETE** |
| **Herramientas Nuevas** | **11 (grupos, precios, viajes, información y formularios)** |
| **Estado** | **✅ TODAS ACTIVAS EN PRODUCCIÓN** |

---

**📍 Servidor en Producción:** `https://conalcaia.conalca.com.co/mcp/`  
**🔧 Puerto Local:** `18840`  
**📅 Última Actualización:** Octubre 9, 2025  
**🆕 Versión zinformacion:** 2.0 Ampliada (60+ campos, 12 secciones, compatible con Laravel)  
**🆕 Nueva herramienta:** #23 llenar_formulario (Llenado automático de formularios)  
**📚 Documentación Adicional:** Ver `ZINFORMACION_AMPLIADA.md` para detalles completos

---

## 🎯 CASOS DE USO POR CATEGORÍA

### **📞 Flujo de Llamadas**
1. **ElevenLabs inicia llamada** → `process_elevenlabs_event`
2. **Crear registro** → `create_llamada`
3. **Actualizar estado** → `update_llamada_status`
4. **Generar oferta** → `generate_transport_offer`
5. **Guardar decisión** → `save_driver_decision`

### **🚛 Consulta de Vehículos**
1. **Cliente llama con placa** → `get_chofer_by_placa`
2. **Buscar por teléfono** → `get_vehicle_by_telefono_conductor`
3. **Ver cotizaciones** → `get_cotizaciones`

### **👥 Gestión de Grupos ⭐**
1. **Listar grupos** → `get_group_cotizations`
2. **Grupos por usuario** → `get_group_cotizations_by_user`
3. **Cotizaciones del grupo** → `get_cotizations_by_group`
4. **Detalle con grupo** → `get_cotizacion_with_group_info`

### **💰 Gestión de Precios ⭐**
1. **Catálogo completo** → `get_pricings`
2. **Precios por vehículo** → `get_pricing_by_vehicle_type`
3. **Precios por ruta** → `search_pricings_by_route`
4. **Cotización con precios** → `get_cotizacion_with_pricing_info`

### **🚚 Consulta de Precio de Viaje ⭐ NUEVO**
1. **Chofer pregunta precio** → `precioviaje`
2. **Respuesta inmediata** → "El precio del viaje es $2,900,000 COP"
3. **Información completa** → Origen, destino, vehículo, peso

### **📋 Consulta de Información de Órdenes ⭐ NUEVO**
1. **Consulta orden específica** → `zinformacion` con `orden_id`
2. **Buscar órdenes** → `zinformacion` con `search="medellin"`
3. **Listar todas** → `zinformacion` con `show_all=true`
4. **Ver estadísticas** → `zinformacion` con `show_stats=true`

### **📝 Llenado Automático de Formularios ⭐ NUEVO**
1. **Llenar formulario cotización** → `llenar_formulario` con `orden_id`
2. **Mapeo automático** → Convierte datos técnicos en valores de formulario
3. **Instrucciones para agente** → Guía completa para completar campos
4. **Validación de campos** → Lista de campos obligatorios

---

## 🆕 HERRAMIENTA #22: zinformacion (VERSIÓN AMPLIADA 2.0)

#### **22. zinformacion** ⭐ NUEVA - VERSIÓN COMPLETA
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `GET` (Lectura multi-modal) |
| **Descripción** | Consulta información operativa COMPLETA de órdenes desde cotizacion_models con 12 secciones organizadas. 100% compatible con Laravel `php artisan zinfo`. Excluye datos sensibles del cliente. |
| **Parámetros** | `orden_id` (int), `search` (string), `show_all` (bool), `show_stats` (bool), `limit` (int, default=10) |
| **Tablas Consultadas** | `cotizacion_models`, `group_cotizations` (mediante FK) |
| **Campos Retornados** | **60+ campos** organizados en **12 secciones** |
| **Modos Operación** | 1) Orden específica, 2) Búsqueda por texto, 3) Listar todas, 4) Estadísticas |
| **Uso Típico** | Consultar información operativa completa de órdenes para agentes de IA, dashboards, validaciones |
| **Compatibilidad** | ✅ 100% compatible con Laravel `php artisan zinfo` |

**12 Secciones de Información Retornadas:**

1. 🎯 **Información General** - ID, tipo de grupo, operación
2. 📋 **Información Obligatoria Cotización** - Peso, cantidad, embalaje, dimensiones, producto, vehículo, frecuencia, seguridad, carrocería, mercancía
3. 📦 **Información Estática Mercancía** - Registro fotográfico, temperatura, humedad, planos
4. 🚛 **Información Carga/Descarga** - Fecha/hora, tipo de operación
5. 📁 **Grupo de Cotización** - Tipo, referencia, estado (obtenido de tabla `group_cotizations`)
6. 🗺️ **Ruta** - Origen, destino, códigos DANE origen/destino, nombre de ruta
7. 📦 **Información Adicional Mercancía** - Valor declarado, valor
8. 🚛 **Información Adicional Vehículo** - Cantidad de vehículos
9. 📋 **Información Logística Adicional** - Seguro, ventanas de horarios
10. 🌍 **Comercio Exterior** - FCL/LCL, devolución contenedor, régimen, agente aduanas, consolidado, documento BL
11. 📄 **Documentación** - Registro fotográfico, código UN
12. 💻 **Sistema** - Estado Silogtran, pricing_id, group_cotizations_id

**Ejemplos de Uso:**

```json
// Modo 1: Consulta orden específica (INFORMACIÓN COMPLETA)
{
  "orden_id": 31
}
// Retorna: 12 secciones con 60+ campos
// Ejemplo de respuesta:
{
  "success": true,
  "tipo": "orden_detallada",
  "orden_id": 31,
  "informacion_general": {
    "id": 31,
    "tipo": "otm",
    "operacion": "No especificado"
  },
  "informacion_obligatoria_cotizacion": {
    "peso_mercancia": "2000",
    "cantidad": "1",
    "tipo_embalaje": "5",
    "dimensiones": "1 pallet estandar por tonelada.",
    "tipo_producto": "93",
    "vehiculo_requerido": "Tracto Mula S3",
    "frecuencia": "única",
    "esquema_seguridad": "Básico",
    "tipo_carroceria": "Estacas",
    "tipo_mercancia": "Granel sólido"
  },
  "ruta": {
    "origen": "funza",
    "destino": "bogota",
    "dane_origen": "25269",
    "dane_destino": "11001",
    "ruta": "funza-bogota"
  },
  "grupo_cotizacion": {
    "tipo": "No especificado",
    "referencia": "No especificada",
    "estado": "No especificado"
  },
  "comercio_exterior": {
    "fcl_lcl": "LCL",
    "devolucion_contenedor": "No aplica",
    "regimen_nacionalizado": "0"
  },
  "sistema": {
    "estado_silogtran": "pending",
    "pricing_id": 3087,
    "group_cotizations_id": null
  }
  // ... 7 secciones más
}

// Modo 2: Búsqueda por texto
{
  "search": "medellin"
}
// Retorna: Todas las órdenes con "medellin" en origen, destino o mercancía

// Modo 3: Listar todas
{
  "show_all": true,
  "limit": 20
}
// Retorna: Lista de 20 órdenes con información resumida

// Modo 4: Estadísticas
{
  "show_stats": true
}
// Retorna: Total de órdenes en el sistema
```

**Casos Probados (Versión 2.0 Ampliada):**
- ✅ Orden 31: Funza → Bogotá, Tracto Mula S3, Granel sólido, 2000kg - **12 secciones completas**
- ✅ Orden 32: Medellín → Bogotá, **con grupo otm**, estado "aceptada" - **Información de grupo funcionando**
- ✅ Orden 66: Medellín → Bogotá, Tracto Mula S3, Granos, 2000kg - **60+ campos retornados**
- ✅ Búsqueda "medellin": 9 órdenes encontradas
- ✅ Estadísticas: 36 órdenes totales en sistema
- ✅ **Comparación con Laravel:** 100% compatible con `php artisan zinfo`

**Mejoras Versión 2.0:**
- ✅ Ampliada de 9 campos básicos a **60+ campos completos**
- ✅ Organizada en **12 secciones** temáticas
- ✅ Integración con tabla `group_cotizations` mediante FK
- ✅ Información de comercio exterior (FCL/LCL, BL, contenedores)
- ✅ Información logística completa (seguros, horarios, carga/descarga)
- ✅ 100% compatible con comando Laravel `php artisan zinfo`
- ✅ Ideal para agentes de IA que necesitan contexto completo de órdenes

**Seguridad:**
- 🔒 NO expone datos del cliente (nombre, contacto, empresa)
- 🔒 NO expone porcentajes de ganancia o márgenes
- 🔒 NO expone decisiones de choferes
- ✅ Solo información operativa necesaria para gestión de transporte

---

## 🆕 HERRAMIENTA #23: llenar_formulario ⭐ NUEVA

#### **23. llenar_formulario** ⭐ NUEVA - LLENADO AUTOMÁTICO
| **Campo** | **Valor** |
|-----------|-----------|
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `GET` (Lectura con mapeo) |
| **Descripción** | Llena automáticamente formularios de cotización usando datos de una orden existente. Convierte información técnica en valores de formulario listos para usar por el agente de IA. |
| **Parámetros** | `orden_id` (int, requerido), `tipo_formulario` (string, default="cotizacion") |
| **Tablas Consultadas** | `cotizacion_models`, `group_cotizations` (mediante FK) |
| **Uso Típico** | Automatizar llenado de formularios web por parte del agente de IA |
| **Resultado** | Campos mapeados + valores sugeridos + instrucciones para agente |

**Campos de Formulario Mapeados:**
- `tipo_viaje`: INTERCITY, LOCAL, IMPORT/EXPORT
- `moneda`: COP (por defecto)
- `fuente_solicitud`: INDIVIDUAL, COTIZACION_GRUPAL
- `tipo_operacion`: GRANEL, LIQUIDOS, CONTENEDORES, CARGA_GENERAL
- `condicion_despacho`: ENTREGA_INMEDIATA, PROGRAMADO
- `condicion_facturacion`: CONTADO, CREDITO_30_DIAS
- `ciudad_facturacion`: Ciudad de destino
- `vendedor`: CONALCA
- `centro_costo_despacho`: BOGOTA, MEDELLIN, CALI, BARRANQUILLA, CARTAGENA, OTROS
- `cliente`: "Cliente por asignar"

**Mapeo Inteligente:**
- 🎯 **Tipo Viaje:** Detecta FCL/LCL para IMPORT/EXPORT, ciudades diferentes para INTERCITY
- 🎯 **Fuente Solicitud:** Detecta si tiene group_cotizations_id para COTIZACION_GRUPAL
- 🎯 **Tipo Operación:** Analiza tipo_mercancia (granel, líquido, contenedor)
- 🎯 **Condición Despacho:** Basado en campo descargue_cargue
- 🎯 **Condición Facturación:** Basado en valor_declarado (>1M = crédito)
- 🎯 **Centro Costo:** Mapeo por ciudad de origen

**Ejemplo de Uso:**

```json
// Solicitud
{
  "orden_id": 31,
  "tipo_formulario": "cotizacion"
}

// Respuesta
{
  "success": true,
  "orden_id": 31,
  "campos_formulario": {
    "tipo_viaje": "INTERCITY",
    "moneda": "COP",
    "fuente_solicitud": "INDIVIDUAL",
    "tipo_operacion": "GRANEL",
    "condicion_despacho": "ENTREGA_INMEDIATA",
    "condicion_facturacion": "CONTADO",
    "ciudad_facturacion": "bogota",
    "vendedor": "CONALCA",
    "centro_costo_despacho": "OTROS",
    "cliente": "Cliente por asignar"
  },
  "valores_sugeridos": {
    "peso_mercancia": "2000",
    "vehiculo_requerido": "Tracto Mula S3",
    "tipo_mercancia": "Granel sólido",
    "origen": "funza",
    "destino": "bogota"
  },
  "instrucciones_agente": {
    "mensaje": "Use estos valores para llenar automáticamente el formulario. Seleccione las opciones más cercanas en los dropdowns.",
    "ejemplo_llenado": "Para la orden 31: Ruta funza -> bogota, Vehículo: Tracto Mula S3, Mercancía: Granel sólido"
  }
}
```

**Casos de Uso del Agente:**
1. **Automatización de Formularios:** El agente recibe estos valores y los usa para llenar automáticamente cada campo del formulario web
2. **Selección de Dropdowns:** Usa los valores mapeados para seleccionar la opción más cercana en listas desplegables
3. **Validación:** Verifica que todos los campos obligatorios estén completos
4. **Comunicación:** Informa al usuario sobre los valores utilizados

**Casos Probados:**
- ✅ Orden 31: Sin grupo → fuente_solicitud="INDIVIDUAL", centro_costo="OTROS" (Funza)
- ✅ Orden 32: Con grupo → fuente_solicitud="COTIZACION_GRUPAL", centro_costo="MEDELLIN", facturación="CREDITO_30_DIAS"

---

## ✅ RESUMEN TÉCNICO FINAL

| **Aspecto** | **Detalle** |
|-------------|-------------|
| **Total Herramientas** | **22 activas** |
| **Protocolo** | **JSON-RPC 2.0 sobre HTTP POST** |
| **Tablas de BD** | **5 tablas principales** |
| **Relaciones FK** | **2 nuevas relaciones establecidas** |
| **Operaciones CRUD** | **17 GET, 3 POST, 2 PUT, 0 DELETE** |
| **Herramientas Nuevas** | **10 (grupos, precios, viajes e información)** |
| **Estado** | **✅ TODAS ACTIVAS EN PRODUCCIÓN** |

---

**📍 Servidor en Producción:** `https://conalcaia.conalca.com.co/mcp/`  
**🔧 Puerto Local:** `18840`  
**📅 Última Actualización:** Octubre 6, 2025  
**🆕 Versión zinformacion:** 2.0 Ampliada (60+ campos, 12 secciones, compatible con Laravel)  
**📚 Documentación Adicional:** Ver `ZINFORMACION_AMPLIADA.md` para detalles completos