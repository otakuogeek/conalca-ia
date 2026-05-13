# 🔧 GUÍA COMPLETA - HERRAMIENTAS MCP CONALCA
## Sistema de Gestión de Transporte y Llamadas Automatizadas

---

**Servidor de Producción:** https://conalcaia.conalca.com.co/mcp/  
**Protocolo:** JSON-RPC 2.0 compatible con ElevenLabs  
**Base de Datos:** MySQL (ai_transport)  
**Total de Herramientas:** 20 especializadas  
**Estado:** 12 activas en producción + 8 nuevas desarrolladas  
**Fecha de Actualización:** Octubre 5, 2025

---

## 📋 ÍNDICE DE CONTENIDO

1. [Herramientas de Gestión de Llamadas](#-gestión-de-llamadas-5-herramientas)
2. [Herramientas de Gestión de Cotizaciones](#-gestión-de-cotizaciones-3-herramientas)
3. [Herramientas de Gestión de Vehículos](#-gestión-de-vehículos-4-herramientas)
4. [Herramientas de Integración ElevenLabs](#-integración-elevenlabs-4-herramientas)
5. [Herramientas de Gestión de Grupos](#-gestión-de-grupos-4-herramientas-nuevas)
6. [Herramientas de Gestión de Precios](#-gestión-de-precios-5-herramientas-nuevas)
7. [Casos de Uso Detallados](#-casos-de-uso-detallados)
8. [Estructura de Base de Datos](#-estructura-de-base-de-datos)
9. [Ejemplos de Implementación](#-ejemplos-de-implementación)

---

## 📞 **GESTIÓN DE LLAMADAS (5 herramientas)**

### **1. `get_llamadas`** ✅ *Activa en Producción*

**Descripción:** Lista todas las llamadas telefónicas registradas con soporte de paginación

**Parámetros:**
- `limit` (int, opcional): Número máximo de resultados (default: 100, máx: 1000)
- `offset` (int, opcional): Número de registros a omitir (default: 0)

**Tabla de Base de Datos:** `llamadas`

**Campos Utilizados:**
```sql
SELECT id_llamada, id_cotizacion, chofer_id, status, 
       elevenlabs_conversation_id, elevenlabs_sip_call_id,
       call_started_at, call_ended_at, call_notes,
       created_at, updated_at
FROM llamadas 
ORDER BY created_at DESC
```

**Estados de Llamada:**
- `pendiente`: Llamada programada pero no realizada
- `en_curso`: Llamada actualmente en progreso
- `completada`: Llamada finalizada exitosamente
- `fallida`: Llamada que no se pudo completar
- `reagendada`: Llamada reprogramada para otro momento

**Casos de Uso:**
- Monitoreo diario de actividad telefónica
- Generación de reportes de gestión
- Dashboard de operaciones en tiempo real
- Análisis de volumen de llamadas por período

**Ejemplo de Respuesta:**
```json
{
  "id_llamada": 123,
  "id_cotizacion": 45,
  "chofer_id": 67,
  "status": "completada",
  "elevenlabs_conversation_id": "conv_abc123",
  "call_started_at": "2025-10-05T10:30:00",
  "call_ended_at": "2025-10-05T10:35:00",
  "call_notes": "Conductor acepta transporte",
  "created_at": "2025-10-05T10:25:00"
}
```

---

### **2. `get_llamada_by_id`** ✅ *Activa en Producción*

**Descripción:** Obtiene información detallada de una llamada específica por su ID único

**Parámetros:**
- `id_llamada` (int, requerido): ID único de la llamada (mínimo: 1)

**Tabla de Base de Datos:** `llamadas`

**Campos Utilizados:**
```sql
SELECT id_llamada, id_cotizacion, chofer_id, status, 
       elevenlabs_conversation_id, elevenlabs_sip_call_id,
       call_started_at, call_ended_at, call_notes,
       created_at, updated_at
FROM llamadas 
WHERE id_llamada = ?
```

**Casos de Uso:**
- Auditoría de llamadas específicas
- Revisión de incidencias reportadas
- Seguimiento de casos particulares
- Análisis post-llamada detallado

**Validaciones:**
- Verifica que el ID de llamada exista
- Retorna error si no se encuentra el registro

---

### **3. `get_llamadas_by_telefono`** ✅ *Activa en Producción*

**Descripción:** Busca llamadas asociadas a un número de teléfono específico

**Parámetros:**
- `telefono` (string, requerido): Número de teléfono a buscar
- `tipo_busqueda` (string, opcional): Tipo de búsqueda
  - `"origen"`: Busca en teléfono de origen
  - `"destino"`: Busca en número destino
  - `"ambos"`: Busca en ambos campos (default)

**Tabla de Base de Datos:** `llamadas`

**Consulta SQL:**
```sql
-- Para búsqueda completa
SELECT * FROM llamadas 
WHERE call_notes LIKE '%Teléfono: {telefono}%' 
   OR numero_destino LIKE '%{telefono}%'
ORDER BY created_at DESC
```

**Casos de Uso:**
- Historial completo de cliente por teléfono
- Seguimiento de conductores específicos
- Análisis de frecuencia de contacto
- Identificación de patrones de comunicación

---

### **4. `create_llamada`** ✅ *Activa en Producción*

**Descripción:** Crea un nuevo registro de llamada en el sistema

**Parámetros:**
- `id_cotizacion` (int, requerido): ID de la cotización relacionada
- `chofer_id` (int, requerido): ID del conductor/chofer
- `status` (string, opcional): Estado inicial (default: "pendiente")
- `numero_destino` (string, opcional): Número de teléfono destino

**Tabla de Base de Datos:** `llamadas`

**Operación SQL:**
```sql
INSERT INTO llamadas (id_cotizacion, chofer_id, numero_destino, status, created_at, updated_at)
VALUES (?, ?, ?, ?, NOW(), NOW())
```

**Casos de Uso:**
- Programación de llamadas salientes
- Registro de llamadas entrantes
- Creación de agenda telefónica
- Integración con sistema CRM

**Validaciones:**
- Verifica que id_cotizacion exista en tabla cotizacion_models
- Verifica que chofer_id exista en tabla vehicle_owner_holder_driver
- Valida formato de teléfono si se proporciona

---

### **5. `update_llamada_status`** ✅ *Activa en Producción*

**Descripción:** Actualiza el estado y notas de una llamada existente

**Parámetros:**
- `id_llamada` (int, requerido): ID de la llamada a actualizar
- `status` (string, requerido): Nuevo estado de la llamada
- `call_notes` (string, opcional): Notas adicionales sobre la llamada

**Tabla de Base de Datos:** `llamadas`

**Operación SQL:**
```sql
UPDATE llamadas 
SET status = ?, call_notes = ?, updated_at = NOW()
WHERE id_llamada = ?
```

**Casos de Uso:**
- Actualización durante conversaciones activas
- Registro de resultados post-llamada
- Gestión de workflow telefónico
- Seguimiento de estado en tiempo real

---

## 💰 **GESTIÓN DE COTIZACIONES (3 herramientas)**

### **6. `get_cotizaciones`** ✅ *Activa en Producción*

**Descripción:** Obtiene lista completa de cotizaciones de transporte con paginación

**Parámetros:**
- `limit` (int, opcional): Cotizaciones por página (default: 100, máx: 1000)
- `offset` (int, opcional): Registro inicial (default: 0)

**Tabla de Base de Datos:** `cotizacion_models`

**Campos Principales:**
```sql
SELECT id, pricing_id, ciudad_origen, ciudad_destino, peso_mercancia,
       vehiculo_requerido, ruta, valor, tipo_mercancia, silogtran_status,
       group_cotizations_id
FROM cotizacion_models 
ORDER BY id DESC
```

**Casos de Uso:**
- Consulta de ofertas disponibles
- Generación de presupuestos
- Análisis de mercado de transporte
- Dashboard de cotizaciones activas

**Información Incluida:**
- Detalles de origen y destino
- Especificaciones de carga
- Tipo de vehículo requerido
- Valor de la cotización
- Estado en sistema SILOGTRAN

---

### **7. `get_cotizacion_by_id`** ✅ *Activa en Producción*

**Descripción:** Obtiene información completa de una cotización específica

**Parámetros:**
- `id_cotizacion` (int, requerido): ID único de la cotización

**Tabla de Base de Datos:** `cotizacion_models`

**Consulta Completa:**
```sql
SELECT * FROM cotizacion_models WHERE id = ?
```

**Casos de Uso:**
- Detalles para crear ofertas específicas
- Verificación de especificaciones técnicas
- Análisis de rentabilidad individual
- Generación de contratos

**Información Detallada:**
- Todas las especificaciones técnicas
- Requerimientos especiales
- Documentación necesaria
- Condiciones de transporte

---

### **8. `search_cotizaciones_by_ruta`** ✅ *Activa en Producción*

**Descripción:** Busca cotizaciones que coincidan con una ruta específica

**Parámetros:**
- `ruta` (string, requerido): Texto de la ruta a buscar

**Tabla de Base de Datos:** `cotizacion_models`

**Consulta SQL:**
```sql
SELECT id, ruta, ciudad_origen, ciudad_destino, valor, vehiculo_requerido
FROM cotizacion_models 
WHERE ruta LIKE '%{ruta}%'
```

**Casos de Uso:**
- Búsqueda de precios para rutas específicas
- Optimización de rutas frecuentes
- Análisis de corredores de transporte
- Identificación de oportunidades comerciales

---

## 🚛 **GESTIÓN DE VEHÍCULOS (4 herramientas)**

### **9. `get_vehicles`** ✅ *Activa en Producción*

**Descripción:** Lista todos los vehículos registrados con información completa

**Parámetros:**
- `limit` (int, opcional): Vehículos por página (default: 100)
- `offset` (int, opcional): Registro inicial (default: 0)

**Tabla de Base de Datos:** `vehicle_owner_holder_driver`

**Campos Principales:**
```sql
SELECT placa, propietario, conductor, telefonoconductor, telefonopropietario,
       marca, modelo, carroceria, ciudad_conductor
FROM vehicle_owner_holder_driver
```

**Casos de Uso:**
- Consulta de flota disponible
- Búsqueda de vehículos para asignación
- Inventario de capacidades de transporte
- Gestión de recursos vehiculares

---

### **10. `get_vehicle_by_placa`** ✅ *Activa en Producción*

**Descripción:** Busca información completa de un vehículo por su placa

**Parámetros:**
- `placa` (string, requerido): Número de placa del vehículo

**Tabla de Base de Datos:** `vehicle_owner_holder_driver`

**Consulta SQL:**
```sql
SELECT * FROM vehicle_owner_holder_driver WHERE Placa = ?
```

**Casos de Uso:**
- Verificación de datos vehiculares
- Validación de documentación
- Identificación rápida durante operaciones
- Control de cumplimiento regulatorio

---

### **11. `search_vehicles_by_conductor`** ✅ *Activa en Producción*

**Descripción:** Busca vehículos asociados a un conductor específico

**Parámetros:**
- `conductor_name` (string, requerido): Nombre del conductor

**Tabla de Base de Datos:** `vehicle_owner_holder_driver`

**Consulta SQL:**
```sql
SELECT placa, conductor, telefonoconductor, propietario, marca, modelo
FROM vehicle_owner_holder_driver 
WHERE Conductor LIKE '%{conductor_name}%'
```

**Casos de Uso:**
- Localización de vehículos por conductor
- Gestión de asignaciones personales
- Análisis de productividad por conductor
- Distribución de carga de trabajo

---

### **12. `get_vehicle_by_telefono_conductor`** ✅ *Activa en Producción*

**Descripción:** Identifica vehículo y conductor por número de teléfono

**Parámetros:**
- `telefono` (string, requerido): Número de teléfono del conductor

**Tabla de Base de Datos:** `vehicle_owner_holder_driver`

**Consulta SQL:**
```sql
SELECT * FROM vehicle_owner_holder_driver WHERE Telefonoconductor = ?
```

**Casos de Uso:**
- Identificación durante llamadas entrantes
- Verificación automática de identidad
- Asignación rápida de trabajos
- Gestión de comunicaciones

---

## 🤖 **INTEGRACIÓN ELEVENLABS (4 herramientas)**

### **13. `activate_conversation`** ✅ *Activa en Producción*

**Descripción:** Gestiona conversaciones de IA por conversation_id de ElevenLabs

**Parámetros:**
- `conversation_id` (string, requerido): ID de conversación de ElevenLabs
- `new_status` (string, opcional): Nuevo estado (default: "en_curso")
- `call_notes` (string, opcional): Notas sobre la conversación
- `sip_call_id` (string, opcional): ID de llamada SIP

**Tabla de Base de Datos:** `llamadas`

**Operación SQL:**
```sql
UPDATE llamadas 
SET status = ?, call_notes = ?, elevenlabs_sip_call_id = ?, updated_at = NOW()
WHERE elevenlabs_conversation_id = ?
```

**Casos de Uso:**
- Control de conversaciones automatizadas
- Finalización de llamadas de IA
- Gestión de workflow de ElevenLabs
- Sincronización de estados

---

### **14. `generate_transport_offer`** ✅ *Activa en Producción*

**Descripción:** Genera ofertas personalizadas de transporte para conductores

**Parámetros:**
- `conversation_id` (string, requerido): ID de conversación activa

**Tablas Utilizadas:** 
- `llamadas` (para obtener contexto)
- `cotizacion_models` (para datos de la oferta)
- `vehicle_owner_holder_driver` (para datos del conductor)

**Proceso:**
1. Busca llamada por conversation_id
2. Obtiene datos de cotización
3. Obtiene datos del conductor
4. Genera oferta personalizada

**Casos de Uso:**
- Generación automática de propuestas
- Comunicación profesional con transportistas
- Integración con sistemas de IA conversacional
- Optimización de procesos comerciales

---

### **15. `get_chofer_by_placa`** ✅ *Activa en Producción*

**Descripción:** Obtiene información completa del conductor por placa del vehículo

**Parámetros:**
- `placa` (string, requerido): Número de placa del vehículo

**Tabla de Base de Datos:** `vehicle_owner_holder_driver`

**Consulta SQL:**
```sql
SELECT * FROM vehicle_owner_holder_driver 
WHERE UPPER(TRIM(Placa)) = UPPER(TRIM(?))
```

**Casos de Uso:**
- Identificación de conductor durante llamadas
- Verificación de datos para ofertas
- Validación de elegibilidad
- Gestión de comunicaciones personalizadas

---

### **16. `save_driver_decision`** ✅ *Activa en Producción*

**Descripción:** Registra la decisión del conductor sobre una cotización

**Parámetros:**
- `cotizacion_model_id` (int, requerido): ID de la cotización
- `driver_id` (int, requerido): ID del conductor
- `decision` (int, requerido): Decisión (1 = acepta, 0 = rechaza)

**Tabla de Base de Datos:** `call_driver_decisions`

**Operación SQL:**
```sql
INSERT INTO call_driver_decisions (cotizacion_model_id, driver_id, decision, created_at, updated_at)
VALUES (?, ?, ?, NOW(), NOW())
```

**Casos de Uso:**
- Registro de respuestas de conductores
- Análisis de tasas de aceptación
- Seguimiento de decisiones comerciales
- Métricas de efectividad

---

## 🏷️ **GESTIÓN DE GRUPOS (4 herramientas)** ⭐ *NUEVAS*

### **17. `get_group_cotizations`** 🆕 *Nueva Funcionalidad*

**Descripción:** Lista grupos de cotizaciones organizadas por categorías

**Parámetros:**
- `limit` (int, opcional): Grupos por página (default: 100)
- `offset` (int, opcional): Registro inicial (default: 0)

**Tabla de Base de Datos:** `group_cotizations`

**Consulta SQL:**
```sql
SELECT id, user_id, client_id, type, reference, status, created_at, updated_at
FROM group_cotizations 
ORDER BY id DESC
```

**Relación de Base de Datos:**
```sql
-- Clave foránea establecida:
ALTER TABLE cotizacion_models 
ADD CONSTRAINT fk_cotizacion_group 
FOREIGN KEY (group_cotizations_id) REFERENCES group_cotizations (id)
```

**Casos de Uso:**
- Organización de cotizaciones por tipo
- Análisis por categorías de negocio
- Gestión de proyectos específicos
- Segmentación de mercado

**Tipos de Grupos Disponibles:**
- `otm`: One-to-Many (Un origen, múltiples destinos)
- `dta`: Direct Transport Agreement (Acuerdo directo)
- Personalizados por cliente o proyecto

---

### **18. `get_group_cotization_by_id`** 🆕 *Nueva Funcionalidad*

**Descripción:** Obtiene información detallada de un grupo específico

**Parámetros:**
- `group_id` (int, requerido): ID único del grupo

**Tabla de Base de Datos:** `group_cotizations`

**Consulta SQL:**
```sql
SELECT * FROM group_cotizations WHERE id = ?
```

**Casos de Uso:**
- Detalles de proyectos específicos
- Información de clientes por grupo
- Análisis de rentabilidad por categoría
- Gestión de contratos grupales

---

### **19. `get_cotizaciones_by_group`** 🆕 *Nueva Funcionalidad*

**Descripción:** Obtiene todas las cotizaciones pertenecientes a un grupo

**Parámetros:**
- `group_id` (int, requerido): ID del grupo

**Tabla de Base de Datos:** `cotizacion_models`

**Consulta SQL:**
```sql
SELECT * FROM cotizacion_models 
WHERE group_cotizations_id = ? 
ORDER BY id DESC
```

**Casos de Uso:**
- Análisis de cotizaciones por proyecto
- Gestión de ofertas agrupadas
- Reportes por categoría de negocio
- Seguimiento de contratos específicos

---

### **20. `get_cotizacion_with_group_info`** 🆕 *Nueva Funcionalidad*

**Descripción:** Obtiene cotización con información completa de su grupo

**Parámetros:**
- `cotizacion_id` (int, requerido): ID de la cotización

**Tablas Utilizadas:** `cotizacion_models` JOIN `group_cotizations`

**Consulta SQL:**
```sql
SELECT c.*, g.type as group_type, g.reference as group_reference, g.status as group_status
FROM cotizacion_models c
LEFT JOIN group_cotizations g ON c.group_cotizations_id = g.id
WHERE c.id = ?
```

**Casos de Uso:**
- Contexto completo para ofertas
- Información integrada para reportes
- Análisis de relaciones comerciales
- Gestión integral de proyectos

---

## 💲 **GESTIÓN DE PRECIOS (5 herramientas)** ⭐ *NUEVAS*

### **21. `get_pricings`** 🆕 *Nueva Funcionalidad*

**Descripción:** Lista completa de precios y tarifas disponibles

**Parámetros:**
- `limit` (int, opcional): Precios por página (default: 100)
- `offset` (int, opcional): Registro inicial (default: 0)

**Tabla de Base de Datos:** `pricings` (11,866 registros)

**Consulta SQL:**
```sql
SELECT id, vehicle_type, type_pricing, origin, destination, price,
       weight_from, weight_to, condition, created_at, updated_at
FROM pricings 
ORDER BY id DESC
```

**Relación de Base de Datos:**
```sql
-- Clave foránea establecida:
ALTER TABLE cotizacion_models 
ADD CONSTRAINT fk_cotizacion_pricing 
FOREIGN KEY (pricing_id) REFERENCES pricings (id)
```

**Casos de Uso:**
- Consulta de tarifario completo
- Análisis de precios de mercado
- Configuración de ofertas automáticas
- Gestión de políticas de precios

**Campos de Precio Disponibles:**
- Precios por día/semana/mes
- Precios auxiliares
- Costos adicionales por servicios
- Tarifas por peso y volumen

---

### **22. `get_pricing_by_id`** 🆕 *Nueva Funcionalidad*

**Descripción:** Obtiene información completa de un precio específico

**Parámetros:**
- `pricing_id` (int, requerido): ID único del precio

**Tabla de Base de Datos:** `pricings`

**Consulta SQL:**
```sql
SELECT * FROM pricings WHERE id = ?
```

**Información Detallada:**
- Precios base y auxiliares
- Condiciones especiales
- Rangos de peso
- Servicios adicionales
- Documentación requerida

**Casos de Uso:**
- Detalles para cotizaciones específicas
- Verificación de tarifas aplicables
- Análisis de componentes de precio
- Configuración de ofertas personalizadas

---

### **23. `get_cotizaciones_by_pricing`** 🆕 *Nueva Funcionalidad*

**Descripción:** Encuentra cotizaciones que utilizan un precio específico

**Parámetros:**
- `pricing_id` (int, requerido): ID del precio a buscar

**Tabla de Base de Datos:** `cotizacion_models`

**Consulta SQL:**
```sql
SELECT * FROM cotizacion_models 
WHERE pricing_id = ? 
ORDER BY id DESC
```

**Casos de Uso:**
- Análisis de uso de tarifas
- Identificación de cotizaciones populares
- Optimización de precios frecuentes
- Gestión de políticas comerciales

---

### **24. `get_cotizacion_with_pricing_info`** 🆕 *Nueva Funcionalidad*

**Descripción:** Obtiene cotización con información completa del precio asociado

**Parámetros:**
- `cotizacion_id` (int, requerido): ID de la cotización

**Tablas Utilizadas:** `cotizacion_models` JOIN `pricings`

**Consulta SQL:**
```sql
SELECT c.*, p.vehicle_type, p.origin as pricing_origin, p.destination as pricing_destination, 
       p.price as pricing_price, p.type_pricing, p.condition as pricing_condition
FROM cotizacion_models c
LEFT JOIN pricings p ON c.pricing_id = p.id
WHERE c.id = ?
```

**Casos de Uso:**
- Información completa para ofertas
- Análisis integrado de precio-cotización
- Generación de propuestas detalladas
- Validación de coherencia comercial

---

### **25. `search_pricings_by_route`** 🆕 *Nueva Funcionalidad*

**Descripción:** Busca precios por origen y/o destino específicos

**Parámetros:**
- `origin` (string, opcional): Ciudad/región de origen
- `destination` (string, opcional): Ciudad/región de destino

**Tabla de Base de Datos:** `pricings`

**Consulta SQL:**
```sql
SELECT * FROM pricings 
WHERE origin LIKE '%{origin}%' 
  AND destination LIKE '%{destination}%'
ORDER BY id DESC
```

**Casos de Uso:**
- Búsqueda de tarifas por ruta
- Análisis de corredores comerciales
- Optimización de precios regionales
- Identificación de oportunidades de mercado

**Validaciones:**
- Requiere al menos un parámetro (origen o destino)
- Búsqueda flexible con LIKE para coincidencias parciales

---

## 🎯 **CASOS DE USO DETALLADOS**

### **📞 Escenario 1: Llamada Entrante de Conductor**

**Flujo Completo:**
1. **Identificación Automática**
   ```json
   GET: get_vehicle_by_telefono_conductor(telefono: "3001234567")
   ```
   - Identifica conductor y vehículo
   - Obtiene datos de contacto y especificaciones

2. **Búsqueda de Ofertas Relevantes**
   ```json
   GET: search_cotizaciones_by_ruta(ruta: "Medellín-Bogotá")
   GET: get_cotizaciones_by_group(group_id: 42)
   ```
   - Encuentra cotizaciones para la ruta del conductor
   - Filtra por grupos relevantes

3. **Generación de Propuesta**
   ```json
   POST: generate_transport_offer(conversation_id: "conv_abc123")
   ```
   - Crea oferta personalizada
   - Incluye datos del conductor y cotización

4. **Registro de Decisión**
   ```json
   POST: save_driver_decision(cotizacion_model_id: 45, driver_id: 67, decision: 1)
   ```
   - Guarda respuesta del conductor (acepta/rechaza)

5. **Actualización de Estado**
   ```json
   PUT: update_llamada_status(id_llamada: 123, status: "completada", call_notes: "Conductor acepta trabajo")
   ```

**Tablas Involucradas:**
- `vehicle_owner_holder_driver` → Identificación
- `cotizacion_models` → Ofertas disponibles
- `pricings` → Tarifas aplicables
- `llamadas` → Registro de la conversación
- `call_driver_decisions` → Decisión final

---

### **📊 Escenario 2: Análisis de Rendimiento Comercial**

**Flujo de Análisis:**
1. **Obtener Actividad Diaria**
   ```json
   GET: get_llamadas(limit: 1000, offset: 0)
   ```
   - Lista todas las llamadas del período

2. **Análizar por Grupos**
   ```json
   GET: get_group_cotizations()
   GET: get_cotizaciones_by_group(group_id: X) // Para cada grupo
   ```
   - Segmenta análisis por tipo de negocio

3. **Evaluar Precios por Ruta**
   ```json
   GET: search_pricings_by_route(origin: "Medellín", destination: "Bogotá")
   ```
   - Identifica tarifas más utilizadas

4. **Cruzar Información**
   ```json
   GET: get_cotizacion_with_pricing_info(cotizacion_id: X) // Para cada cotización
   GET: get_cotizacion_with_group_info(cotizacion_id: X)
   ```
   - Obtiene contexto completo para análisis

**Métricas Obtenidas:**
- Volumen de llamadas por estado
- Distribución por grupos de cotizaciones
- Tarifas más populares por ruta
- Tasas de aceptación por conductor
- Rentabilidad por tipo de transporte

---

### **🤖 Escenario 3: Integración con ElevenLabs AI**

**Workflow Automatizado:**
1. **Inicio de Conversación**
   ```json
   POST: create_llamada(id_cotizacion: 45, chofer_id: 67, status: "pendiente")
   ```
   - Sistema programa llamada automática

2. **Activación de IA**
   ```json
   POST: activate_conversation(conversation_id: "conv_xyz789", new_status: "en_curso")
   ```
   - IA de ElevenLabs inicia conversación

3. **Generación Dinámica de Contenido**
   ```json
   GET: get_chofer_by_placa(placa: "ABC123")
   GET: get_cotizacion_with_pricing_info(cotizacion_id: 45)
   POST: generate_transport_offer(conversation_id: "conv_xyz789")
   ```
   - IA obtiene contexto y genera oferta personalizada

4. **Procesamiento de Respuesta**
   ```json
   POST: save_driver_decision(cotizacion_model_id: 45, driver_id: 67, decision: 1)
   ```
   - Registra decisión del conductor

5. **Finalización**
   ```json
   POST: activate_conversation(conversation_id: "conv_xyz789", new_status: "completada", call_notes: "Oferta aceptada")
   ```

**Ventajas de la Integración:**
- Conversaciones naturales y contextuales
- Información en tiempo real
- Registro automático de decisiones
- Escalabilidad de operaciones

---

## 🗄️ **ESTRUCTURA DE BASE DE DATOS**

### **Tabla: `llamadas`**
```sql
CREATE TABLE llamadas (
    id_llamada INT PRIMARY KEY AUTO_INCREMENT,
    id_cotizacion INT NOT NULL,
    chofer_id INT NOT NULL,
    numero_destino VARCHAR(50),
    status ENUM('pendiente', 'en_curso', 'completada', 'fallida', 'reagendada'),
    elevenlabs_conversation_id VARCHAR(255),
    elevenlabs_sip_call_id VARCHAR(255),
    call_started_at TIMESTAMP NULL,
    call_ended_at TIMESTAMP NULL,
    call_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_cotizacion) REFERENCES cotizacion_models(id),
    FOREIGN KEY (chofer_id) REFERENCES vehicle_owner_holder_driver(id)
);
```

### **Tabla: `cotizacion_models`**
```sql
CREATE TABLE cotizacion_models (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    pricing_id BIGINT UNSIGNED NOT NULL,
    group_cotizations_id BIGINT UNSIGNED NULL,
    porcentaje VARCHAR(191),
    ciudad_origen VARCHAR(191),
    ciudad_destino VARCHAR(191),
    peso_mercancia VARCHAR(191),
    vehiculo_requerido VARCHAR(191),
    ruta VARCHAR(191),
    valor VARCHAR(191),
    tipo_mercancia VARCHAR(191),
    silogtran_status VARCHAR(191),
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (pricing_id) REFERENCES pricings(id) ON UPDATE CASCADE,
    FOREIGN KEY (group_cotizations_id) REFERENCES group_cotizations(id) ON DELETE SET NULL ON UPDATE CASCADE,
    
    INDEX idx_cotizacion_pricing (pricing_id),
    INDEX idx_cotizacion_group (group_cotizations_id)
);
```

### **Tabla: `pricings`**
```sql
CREATE TABLE pricings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    vehicle_type VARCHAR(191),
    type_pricing VARCHAR(191),
    origin VARCHAR(191),
    destination VARCHAR(191),
    price VARCHAR(191),
    weight_from VARCHAR(191),
    weight_to VARCHAR(191),
    condition VARCHAR(191),
    price_month DOUBLE,
    price_week DOUBLE,
    price_day DOUBLE,
    extra VARCHAR(191),
    price_extra VARCHAR(191),
    -- ... más campos de configuración de precios
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### **Tabla: `group_cotizations`**
```sql
CREATE TABLE group_cotizations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(255), -- 'otm', 'dta', etc.
    reference VARCHAR(255),
    status VARCHAR(255) DEFAULT 'borrador',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### **Tabla: `vehicle_owner_holder_driver`**
```sql
CREATE TABLE vehicle_owner_holder_driver (
    id INT PRIMARY KEY AUTO_INCREMENT,
    Placa VARCHAR(10) UNIQUE,
    Propietario VARCHAR(255),
    Conductor VARCHAR(255),
    Telefonoconductor VARCHAR(20),
    Telefonopropietario VARCHAR(20),
    Marca VARCHAR(100),
    Modelo INT,
    Carroceria VARCHAR(100),
    Ciudad_conductor VARCHAR(100),
    -- ... más campos de información vehicular
);
```

### **Tabla: `call_driver_decisions`**
```sql
CREATE TABLE call_driver_decisions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cotizacion_model_id BIGINT UNSIGNED NOT NULL,
    driver_id INT NOT NULL,
    decision TINYINT NOT NULL, -- 1 = acepta, 0 = rechaza
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (cotizacion_model_id) REFERENCES cotizacion_models(id),
    FOREIGN KEY (driver_id) REFERENCES vehicle_owner_holder_driver(id)
);
```

### **Relaciones Principales:**
```sql
-- Relación cotizaciones con precios
cotizacion_models.pricing_id → pricings.id

-- Relación cotizaciones con grupos
cotizacion_models.group_cotizations_id → group_cotizations.id

-- Relación llamadas con cotizaciones
llamadas.id_cotizacion → cotizacion_models.id

-- Relación llamadas con conductores
llamadas.chofer_id → vehicle_owner_holder_driver.id

-- Relación decisiones con cotizaciones y conductores
call_driver_decisions.cotizacion_model_id → cotizacion_models.id
call_driver_decisions.driver_id → vehicle_owner_holder_driver.id
```

---

## 🛠️ **EJEMPLOS DE IMPLEMENTACIÓN**

### **Ejemplo 1: Consulta con cURL**
```bash
# Obtener lista de cotizaciones
curl -X POST https://conalcaia.conalca.com.co/mcp/ \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": "1",
    "method": "tools/call",
    "params": {
      "name": "get_cotizaciones",
      "arguments": {
        "limit": 10,
        "offset": 0
      }
    }
  }'
```

### **Ejemplo 2: Búsqueda por Ruta**
```bash
# Buscar precios para ruta específica
curl -X POST https://conalcaia.conalca.com.co/mcp/ \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": "2",
    "method": "tools/call",
    "params": {
      "name": "search_pricings_by_route",
      "arguments": {
        "origin": "Medellín",
        "destination": "Bogotá"
      }
    }
  }'
```

### **Ejemplo 3: Información Integrada**
```bash
# Obtener cotización con información de precio y grupo
curl -X POST https://conalcaia.conalca.com.co/mcp/ \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": "3",
    "method": "tools/call",
    "params": {
      "name": "get_cotizacion_with_pricing_info",
      "arguments": {
        "cotizacion_id": 32
      }
    }
  }'
```

---

## 📈 **MÉTRICAS Y ESTADÍSTICAS**

### **Estado Actual del Sistema:**
- **Herramientas Activas:** 12/20
- **Base de Datos:** 
  - Cotizaciones: ~66 registros
  - Precios: 11,866 registros
  - Grupos: 17 registros
  - Vehículos: Base amplia de transportistas
- **Integración:** ElevenLabs AI completamente funcional
- **Uptime:** 24/7 con reinicio automático

### **Nuevas Funcionalidades Desarrolladas:**
- ✅ Relación cotizaciones ↔ grupos
- ✅ Relación cotizaciones ↔ precios
- ✅ 8 herramientas adicionales implementadas
- ✅ Índices de base de datos optimizados
- ✅ Validaciones y claves foráneas

### **Próximos Pasos:**
1. Activar 8 herramientas nuevas en producción
2. Migrar cambios al servidor https://conalcaia.conalca.com.co/mcp/
3. Probar integración completa con ElevenLabs
4. Implementar dashboard de métricas
5. Configurar alertas y monitoreo

---

## 🚀 **CONCLUSIÓN**

El sistema MCP CONALCA cuenta con **20 herramientas especializadas** que cubren todas las operaciones críticas del negocio de transporte:

- **Gestión Completa:** Llamadas, cotizaciones, vehículos, precios y grupos
- **Integración IA:** Compatible con ElevenLabs para conversaciones automatizadas
- **Base de Datos:** Relaciones optimizadas y consistentes
- **Escalabilidad:** Arquitectura preparada para crecimiento
- **Flexibilidad:** APIs JSON-RPC estándar para fácil integración

El sistema está listo para operación completa y puede escalar para manejar volúmenes comerciales significativos con alta disponibilidad y rendimiento.

---

*Documento generado el: Octubre 5, 2025*  
*Versión: 2.0 - Guía Completa*  
*© 2025 CONALCA AI - Sistema MCP Especializado*