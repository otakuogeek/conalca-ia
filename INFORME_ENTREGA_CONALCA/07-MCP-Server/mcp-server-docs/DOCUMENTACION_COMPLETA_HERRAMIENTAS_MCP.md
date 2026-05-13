# 📚 Documentación Completa de Herramientas MCP - CONALCA

**Servidor**: Conalca MCP Server  
**URL**: https://my-kontrol.online/mcp/elevenlabs  
**Puerto Local**: 18840  
**Total de Herramientas**: 23  
**Fecha de Actualización**: 11 de Octubre de 2025

---

## 📑 Índice de Herramientas

1. [get_llamadas](#1-get_llamadas) - Listar llamadas con paginación
2. [get_llamada_by_id](#2-get_llamada_by_id) - Obtener llamada específica
3. [create_llamada](#3-create_llamada) - Crear nueva llamada
4. [update_llamada_status](#4-update_llamada_status) - Actualizar estado de llamada
5. [get_cotizaciones](#5-get_cotizaciones) - Listar cotizaciones
6. [get_vehicle_by_telefono_conductor](#6-get_vehicle_by_telefono_conductor) - Buscar vehículo por teléfono
7. [process_elevenlabs_event](#7-process_elevenlabs_event) - Procesar eventos de ElevenLabs
8. [get_chofer_by_placa](#8-get_chofer_by_placa) - Obtener chofer por placa
9. [get_llamadas_by_telefono](#9-get_llamadas_by_telefono) - Buscar llamadas por teléfono
10. [activate_conversation](#10-activate_conversation) - Activar conversación
11. [generate_transport_offer](#11-generate_transport_offer) - Obtener información de viaje
12. [save_driver_decision](#12-save_driver_decision) - Guardar decisión del chofer
13. [get_group_cotizations](#13-get_group_cotizations) - Listar grupos de cotizaciones
14. [get_group_cotizations_by_user](#14-get_group_cotizations_by_user) - Grupos por usuario
15. [get_cotizacion_with_group_info](#15-get_cotizacion_with_group_info) - Cotización con info de grupo
16. [get_cotizations_by_group](#16-get_cotizations_by_group) - Cotizaciones de un grupo
17. [get_pricings](#17-get_pricings) - Listar precios
18. [get_pricing_by_vehicle_type](#18-get_pricing_by_vehicle_type) - Precios por tipo de vehículo
19. [search_pricings_by_route](#19-search_pricings_by_route) - Buscar precios por ruta
20. [get_cotizacion_with_pricing_info](#20-get_cotizacion_with_pricing_info) - Cotización con precios
21. [precioviaje](#21-precioviaje) - Obtener precio del viaje
22. [zinformacion](#22-zinformacion) - Información operativa de órdenes
23. [llenar_formulario](#23-llenar_formulario) - Auto-llenar formularios

---

## 1. get_llamadas

### 📝 Descripción
Obtiene todas las llamadas telefónicas con paginación opcional. Permite filtrar por estado y obtener información detallada de cada llamada.

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción | Valor por Defecto |
|-----------|------|-----------|-------------|-------------------|
| `page` | integer | No | Número de página para paginación | 1 |
| `limit` | integer | No | Cantidad máxima de resultados por página | 20 |

**Restricciones**:
- `page`: Mínimo 1
- `limit`: Mínimo 1, Máximo 100

### 📤 Salida Esperada
```json
{
  "success": true,
  "total_encontradas": 50,
  "page": 1,
  "limit": 20,
  "llamadas": [
    {
      "id_llamada": 152,
      "telefono": "3123456789",
      "numero_destino": "+573001234567",
      "estado": "completada",
      "fecha_llamada": "2025-10-10 15:30:00",
      "observaciones": "Cliente interesado",
      "elevenlabs_conversation_id": "conv_abc123",
      "chofer_id": 973,
      "id_cotizacion": 59
    }
  ]
}
```

### 💡 Casos de Uso
- Listar todas las llamadas del día
- Revisar historial de llamadas
- Monitorear estado de llamadas pendientes

---

## 2. get_llamada_by_id

### 📝 Descripción
Obtiene información detallada de una llamada específica usando su ID único. Incluye estado, fecha, observaciones y datos del contacto.

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `llamada_id` | integer | ✅ Sí | ID único de la llamada a consultar |

**Restricciones**:
- `llamada_id`: Mínimo 1

### 📤 Salida Esperada
```json
{
  "success": true,
  "llamada": {
    "id_llamada": 152,
    "telefono": "3123456789",
    "numero_destino": "+573001234567",
    "estado": "completada",
    "fecha_llamada": "2025-10-10 15:30:00",
    "observaciones": "Viaje aceptado",
    "elevenlabs_conversation_id": "conv_0901k7790gj3erytxfvc8d1tynjg",
    "chofer_id": 973,
    "id_cotizacion": 59,
    "sip_call_id": "sip_123456"
  }
}
```

### 💡 Casos de Uso
- Consultar detalles de una llamada específica
- Verificar estado de seguimiento
- Obtener conversation_id para otras operaciones

---

## 3. create_llamada

### 📝 Descripción
Crea una nueva llamada telefónica en el sistema. Registra el número de teléfono del contacto, número de destino, estado inicial y observaciones opcionales.

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción | Valor por Defecto |
|-----------|------|-----------|-------------|-------------------|
| `telefono` | string | ✅ Sí | Número de teléfono del contacto | - |
| `numero_destino` | string | No | Número de teléfono al que se está llamando | - |
| `estado` | string | No | Estado inicial de la llamada | "pendiente" |
| `observaciones` | string | No | Observaciones o notas adicionales | - |

**Restricciones**:
- `telefono`: Patrón `^[+]?[0-9\s\-\(\)]+$`
- `numero_destino`: Patrón `^[+]?[0-9\s\-\(\)]+$`
- `estado`: Valores permitidos: "pendiente", "en_curso", "completada", "fallida", "reagendada"

### 📤 Salida Esperada
```json
{
  "success": true,
  "llamada_id": 154,
  "message": "Llamada creada exitosamente"
}
```

### 💡 Casos de Uso
- Registrar nueva llamada antes de marcar
- Programar llamadas pendientes
- Crear registro manual de llamada

---

## 4. update_llamada_status

### 📝 Descripción
Actualiza el estado de una llamada existente. Útil para hacer seguimiento del progreso de las llamadas.

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `llamada_id` | integer | ✅ Sí | ID de la llamada a actualizar |
| `new_status` | string | ✅ Sí | Nuevo estado de la llamada |

**Restricciones**:
- `llamada_id`: Mínimo 1
- `new_status`: Valores: "pendiente", "en_curso", "completada", "fallida", "reagendada"

### 📤 Salida Esperada
```json
{
  "success": true,
  "llamada_id": 152,
  "old_status": "en_curso",
  "new_status": "completada",
  "message": "Estado actualizado exitosamente"
}
```

### 💡 Casos de Uso
- Marcar llamada como completada
- Cambiar a reagendada si no contestan
- Actualizar a fallida si hay problemas

---

## 5. get_cotizaciones

### 📝 Descripción
Obtiene lista de cotizaciones de vehículos disponibles. Incluye modelos, precios y especificaciones técnicas.

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción | Valor por Defecto |
|-----------|------|-----------|-------------|-------------------|
| `page` | integer | No | Número de página para paginación | 1 |
| `limit` | integer | No | Cantidad máxima de cotizaciones por página | 20 |

**Restricciones**:
- `page`: Mínimo 1
- `limit`: Mínimo 1, Máximo 100

### 📤 Salida Esperada
```json
{
  "success": true,
  "total_encontradas": 15,
  "page": 1,
  "limit": 20,
  "cotizaciones": [
    {
      "id": 59,
      "ciudad_origen": "funza",
      "ciudad_destino": "bogota",
      "peso_mercancia": "2000",
      "tipo_embajale": "5",
      "tipo_producto": "93",
      "vehiculo_requerido": "Tracto Mula S3",
      "fecha_hora_descargue_cargue": "12-10-2025 11:00",
      "valor": "1500000"
    }
  ]
}
```

### 💡 Casos de Uso
- Listar cotizaciones disponibles
- Buscar transportes pendientes
- Revisar precios y rutas

---

## 6. get_vehicle_by_telefono_conductor

### 📝 Descripción
Busca vehículos asociados a un conductor específico usando su número de teléfono. Devuelve información del vehículo, conductor y propietario.

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `telefono` | string | ✅ Sí | Número de teléfono del conductor |

**Restricciones**:
- `telefono`: Patrón `^[+]?[0-9\s\-\(\)]+$`

### 📤 Salida Esperada
```json
{
  "success": true,
  "vehiculo": {
    "id": 12345,
    "placa": "ABC123",
    "conductor": "Felipe Acevedo",
    "telefono_conductor": "3123456789",
    "propietario": "Juan Pérez",
    "poseedor": "María González",
    "tipo_vehiculo": "Tracto Mula",
    "marca": "Freightliner",
    "modelo": "2018"
  }
}
```

### 💡 Casos de Uso
- Identificar chofer al iniciar llamada
- Verificar vehículo disponible
- Obtener datos completos del conductor

---

## 7. process_elevenlabs_event

### 📝 Descripción
Procesa eventos de webhook de ElevenLabs como inicio/fin de conversación, cambios de estado de llamada, etc.

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `event_type` | string | ✅ Sí | Tipo de evento de ElevenLabs |
| `conversation_id` | string | No | ID único de la conversación |
| `phone_number` | string | No | Número de teléfono asociado al evento |
| `status` | string | No | Estado de la llamada |
| `duration` | integer | No | Duración en segundos |
| `metadata` | object | No | Metadatos adicionales del evento |

**Restricciones**:
- `event_type`: Valores: "conversation_started", "conversation_ended", "call_status_change", "user_input", "agent_response"

### 📤 Salida Esperada
```json
{
  "success": true,
  "event_processed": true,
  "event_type": "conversation_ended",
  "conversation_id": "conv_abc123",
  "actions_taken": ["updated_call_status", "saved_duration"]
}
```

### 💡 Casos de Uso
- Sincronizar eventos de ElevenLabs
- Actualizar estados automáticamente
- Registrar duración de llamadas

---

## 8. get_chofer_by_placa

### 📝 Descripción
Obtiene información del chofer y vehículo mediante el número de placa. Devuelve datos completos del conductor, propietario, poseedor, especificaciones del vehículo y el ID único del registro.

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `placa` | string | ✅ Sí | Número de placa del vehículo (ej: ABC123) |

**Restricciones**:
- `placa`: Patrón `^[A-Z0-9]{3,8}$`

### 📤 Salida Esperada
```json
{
  "success": true,
  "chofer": {
    "id": 973,
    "placa": "ABC123",
    "conductor": "Felipe Acevedo",
    "telefono_conductor": "3123456789",
    "cedula_conductor": "1234567890",
    "propietario": "Juan Pérez",
    "poseedor": "María González",
    "tipo_vehiculo": "Tracto Mula S3",
    "marca": "Freightliner",
    "modelo": "2018",
    "capacidad_kg": "35000"
  }
}
```

### 💡 Casos de Uso
- Verificar chofer antes de asignar carga
- Consultar capacidad del vehículo
- Obtener datos de contacto del conductor

---

## 9. get_llamadas_by_telefono

### 📝 Descripción
Busca todas las llamadas asociadas a un número de teléfono específico. Puede buscar tanto en el campo telefono (origen) como en numero_destino (destino).

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción | Valor por Defecto |
|-----------|------|-----------|-------------|-------------------|
| `telefono` | string | ✅ Sí | Número de teléfono a buscar | - |
| `tipo_busqueda` | string | No | Tipo de búsqueda a realizar | "ambos" |

**Restricciones**:
- `telefono`: Patrón `^[+]?[0-9\s\-\(\)]+$`
- `tipo_busqueda`: Valores: "origen", "destino", "ambos"

### 📤 Salida Esperada
```json
{
  "success": true,
  "telefono_buscado": "3123456789",
  "tipo_busqueda": "ambos",
  "total_llamadas": 5,
  "llamadas": [
    {
      "id_llamada": 152,
      "telefono": "3123456789",
      "numero_destino": "+573001234567",
      "estado": "completada",
      "fecha_llamada": "2025-10-10 15:30:00",
      "tipo_match": "origen"
    }
  ]
}
```

### 💡 Casos de Uso
- Historial de llamadas de un chofer
- Verificar contactos previos
- Análisis de frecuencia de llamadas

---

## 10. activate_conversation

### 📝 Descripción
Activa o actualiza el estado de una llamada usando el conversation_id de ElevenLabs. Permite cambiar el estado y agregar notas a la conversación.

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción | Valor por Defecto |
|-----------|------|-----------|-------------|-------------------|
| `conversation_id` | string | ✅ Sí | ID de conversación de ElevenLabs | - |
| `new_status` | string | No | Nuevo estado de la conversación | "en_curso" |
| `call_notes` | string | No | Notas adicionales sobre la conversación | - |
| `sip_call_id` | string | No | ID de llamada SIP de ElevenLabs | - |

**Restricciones**:
- `conversation_id`: Patrón `^conv_[a-zA-Z0-9]+$`
- `new_status`: Valores: "pendiente", "en_curso", "completada", "fallida", "reagendada"

### 📤 Salida Esperada
```json
{
  "success": true,
  "conversation_id": "conv_0901k7790gj3erytxfvc8d1tynjg",
  "llamada_id": 152,
  "status_updated": true,
  "new_status": "en_curso",
  "notes_added": true
}
```

### 💡 Casos de Uso
- Activar conversación al iniciar llamada
- Actualizar estado durante la llamada
- Agregar notas de seguimiento

---

## 11. generate_transport_offer

### 📝 Descripción
Obtiene información detallada del viaje (chofer, origen, destino, producto, embalaje, fecha) usando el conversation_id. Retorna solo los datos estructurados sin mensajes predeterminados para que la IA los use libremente.

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `conversation_id` | string | ✅ Sí | ID de conversación de ElevenLabs |

**Restricciones**:
- `conversation_id`: Patrón `^conv_[a-zA-Z0-9]+$`

### 📤 Salida Esperada
```json
{
  "success": true,
  "conversation_id": "conv_0901k7790gj3erytxfvc8d1tynjg",
  "llamada_info": {
    "id_llamada": 152,
    "id_cotizacion": 59,
    "chofer_id": 973
  },
  "chofer": {
    "nombre": "Felipe Acevedo",
    "chofer_id": 973
  },
  "viaje": {
    "origen": "funza",
    "destino": "bogota",
    "peso_kg": "2000",
    "tipo_embalaje": "BULTOS",
    "tipo_producto": "MAIZ",
    "fecha_hora": "12-10-2025 11:00",
    "cantidad": "1",
    "vehiculo_requerido": "Tracto Mula S3"
  }
}
```

### 💡 Casos de Uso
- Obtener datos del viaje para ofrecerlo al chofer
- Generar propuesta de transporte personalizada
- Consultar detalles de la carga

**Nota Importante**: Esta herramienta NO genera mensajes predeterminados. Retorna solo datos para que la IA los use libremente en la conversación.

---

## 12. save_driver_decision

### 📝 Descripción
Guarda la decisión del chofer sobre la oferta de transporte. Registra si acepta (1) o rechaza (0) la propuesta junto con el ID del chofer y cotización.

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `cotizacion_model_id` | integer | ✅ Sí | ID de la cotización/modelo asociado |
| `driver_id` | integer | ✅ Sí | ID del chofer/conductor |
| `decision` | integer | ✅ Sí | Decisión del chofer: 1=aceptar, 0=rechazar |

**Restricciones**:
- `cotizacion_model_id`: Mínimo 1
- `driver_id`: Mínimo 1
- `decision`: Valores: 0 o 1

### 📤 Salida Esperada
```json
{
  "success": true,
  "cotizacion_id": 59,
  "driver_id": 973,
  "decision": 1,
  "decision_text": "aceptada",
  "message": "Decisión guardada exitosamente",
  "timestamp": "2025-10-11 10:30:00"
}
```

### 💡 Casos de Uso
- Registrar aceptación del viaje
- Guardar rechazo con motivo
- Seguimiento de decisiones de choferes

---

## 13. get_group_cotizations

### 📝 Descripción
Obtiene una lista de grupos de cotizaciones con paginación.

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción | Valor por Defecto |
|-----------|------|-----------|-------------|-------------------|
| `limit` | integer | No | Cantidad máxima de resultados por página | 100 |
| `offset` | integer | No | Número de registros a omitir | 0 |

**Restricciones**:
- `limit`: Mínimo 1
- `offset`: Mínimo 0

### 📤 Salida Esperada
```json
{
  "success": true,
  "total_groups": 25,
  "limit": 100,
  "offset": 0,
  "groups": [
    {
      "id": 1,
      "user_id": 101,
      "client_id": 202,
      "type": "export",
      "reference": "GRP-2025-001",
      "status": "active",
      "created_at": "2025-10-01 08:00:00",
      "updated_at": "2025-10-10 15:30:00"
    }
  ]
}
```

### 💡 Casos de Uso
- Listar grupos de cotizaciones activas
- Gestión de grupos por referencia
- Seguimiento de cotizaciones grupales

---

## 14. get_group_cotizations_by_user

### 📝 Descripción
Obtiene grupos de cotizaciones asociados a un usuario específico.

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `user_id` | integer | ✅ Sí | ID del usuario |

**Restricciones**:
- `user_id`: Mínimo 1

### 📤 Salida Esperada
```json
{
  "success": true,
  "user_id": 101,
  "total_groups": 5,
  "groups": [
    {
      "id": 1,
      "client_id": 202,
      "type": "export",
      "reference": "GRP-2025-001",
      "status": "active",
      "created_at": "2025-10-01 08:00:00"
    }
  ]
}
```

### 💡 Casos de Uso
- Ver cotizaciones de un usuario específico
- Filtrar por responsable
- Gestión de cartera por usuario

---

## 15. get_cotizacion_with_group_info

### 📝 Descripción
Obtiene una cotización específica con información completa del grupo asociado.

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `cotizacion_id` | integer | ✅ Sí | ID de la cotización |

**Restricciones**:
- `cotizacion_id`: Mínimo 1

### 📤 Salida Esperada
```json
{
  "success": true,
  "cotizacion": {
    "id": 59,
    "ciudad_origen": "funza",
    "ciudad_destino": "bogota",
    "peso_mercancia": "2000",
    "tipo_producto": "MAIZ",
    "group_cotizations_id": 1
  },
  "group_info": {
    "id": 1,
    "user_id": 101,
    "client_id": 202,
    "type": "export",
    "reference": "GRP-2025-001",
    "status": "active"
  }
}
```

### 💡 Casos de Uso
- Obtener contexto completo de una cotización
- Verificar grupo asociado
- Seguimiento de cotización en grupo

---

## 16. get_cotizations_by_group

### 📝 Descripción
Obtiene todas las cotizaciones que pertenecen a un grupo específico.

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `group_id` | integer | ✅ Sí | ID del grupo de cotizaciones |

**Restricciones**:
- `group_id`: Mínimo 1

### 📤 Salida Esperada
```json
{
  "success": true,
  "group_id": 1,
  "total_cotizaciones": 8,
  "cotizaciones": [
    {
      "id": 59,
      "ciudad_origen": "funza",
      "ciudad_destino": "bogota",
      "peso_mercancia": "2000",
      "tipo_producto": "MAIZ",
      "vehiculo_requerido": "Tracto Mula S3"
    }
  ]
}
```

### 💡 Casos de Uso
- Ver todas las cotizaciones de un grupo
- Análisis de grupo completo
- Gestión de cotizaciones agrupadas

---

## 17. get_pricings

### 📝 Descripción
Obtiene una lista de precios con paginación.

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción | Valor por Defecto |
|-----------|------|-----------|-------------|-------------------|
| `limit` | integer | No | Cantidad máxima de resultados por página | 100 |
| `offset` | integer | No | Número de registros a omitir | 0 |

**Restricciones**:
- `limit`: Mínimo 1
- `offset`: Mínimo 0

### 📤 Salida Esperada
```json
{
  "success": true,
  "total_prices": 150,
  "limit": 100,
  "offset": 0,
  "pricings": [
    {
      "id": 1,
      "origen": "Funza",
      "destino": "Bogotá",
      "tipo_vehiculo": "Tracto Mula S3",
      "precio_base": "1500000",
      "precio_por_kg": "50",
      "distancia_km": "35"
    }
  ]
}
```

### 💡 Casos de Uso
- Consultar tabla de precios
- Revisar tarifas disponibles
- Análisis de costos

---

## 18. get_pricing_by_vehicle_type

### 📝 Descripción
Obtiene precios filtrados por tipo de vehículo.

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `vehicle_type` | string | ✅ Sí | Tipo de vehículo para filtrar precios |

### 📤 Salida Esperada
```json
{
  "success": true,
  "vehicle_type": "Tracto Mula S3",
  "total_prices": 12,
  "pricings": [
    {
      "id": 1,
      "origen": "Funza",
      "destino": "Bogotá",
      "precio_base": "1500000",
      "distancia_km": "35"
    }
  ]
}
```

### 💡 Casos de Uso
- Buscar precios para un tipo de vehículo específico
- Comparar tarifas por vehículo
- Cotización rápida por tipo

---

## 19. search_pricings_by_route

### 📝 Descripción
Busca precios por ruta específica (origen y destino).

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `origin` | string | No | Ciudad o lugar de origen |
| `destination` | string | No | Ciudad o lugar de destino |

### 📤 Salida Esperada
```json
{
  "success": true,
  "origin": "Funza",
  "destination": "Bogotá",
  "total_results": 3,
  "pricings": [
    {
      "id": 1,
      "tipo_vehiculo": "Tracto Mula S3",
      "precio_base": "1500000",
      "precio_por_kg": "50",
      "distancia_km": "35"
    }
  ]
}
```

### 💡 Casos de Uso
- Buscar precio de ruta específica
- Comparar opciones de transporte
- Cotización por ruta

---

## 20. get_cotizacion_with_pricing_info

### 📝 Descripción
Obtiene una cotización específica con información completa de precios.

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `cotizacion_id` | integer | ✅ Sí | ID de la cotización |

**Restricciones**:
- `cotizacion_id`: Mínimo 1

### 📤 Salida Esperada
```json
{
  "success": true,
  "cotizacion": {
    "id": 59,
    "ciudad_origen": "funza",
    "ciudad_destino": "bogota",
    "peso_mercancia": "2000",
    "pricing_id": 1
  },
  "pricing_info": {
    "id": 1,
    "precio_base": "1500000",
    "precio_por_kg": "50",
    "distancia_km": "35",
    "tipo_vehiculo": "Tracto Mula S3"
  }
}
```

### 💡 Casos de Uso
- Obtener cotización con precio calculado
- Ver desglose de costos
- Información completa para facturación

---

## 21. precioviaje

### 📝 Descripción
Obtiene el precio específico de un viaje consultando la cotización y su precio asociado. Ideal para responder cuando el chofer pregunta el valor del viaje.

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `cotizacion_id` | integer | ✅ Sí | ID de la cotización para consultar el precio |

**Restricciones**:
- `cotizacion_id`: Mínimo 1

### 📤 Salida Esperada
```json
{
  "success": true,
  "cotizacion_id": 59,
  "precio_viaje": "1500000",
  "moneda": "COP",
  "detalles": {
    "precio_base": "1200000",
    "precio_por_peso": "300000",
    "peso_kg": "2000",
    "tarifa_por_kg": "50"
  }
}
```

### 💡 Casos de Uso
- Responder pregunta del chofer sobre precio
- Informar valor del flete
- Negociación de tarifa

---

## 22. zinformacion

### 📝 Descripción
Consultar información operativa completa de órdenes con campos estáticos de cotizacion_models. Excluye información sensible como datos del cliente, porcentajes de ganancia y decisiones posteriores a llamadas.

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción | Valor por Defecto |
|-----------|------|-----------|-------------|-------------------|
| `orden_id` | integer | No | ID específico de la orden a consultar | - |
| `search` | string | No | Texto para buscar en ciudades, productos o mercancías | - |
| `show_all` | boolean | No | Mostrar todas las órdenes (limitadas por limit) | false |
| `show_stats` | boolean | No | Mostrar estadísticas de completitud de campos | false |
| `limit` | integer | No | Límite de resultados para búsquedas | 10 |

**Restricciones**:
- `orden_id`: Mínimo 1
- `limit`: Mínimo 1, Máximo 50

### 📤 Salida Esperada
```json
{
  "success": true,
  "orden_id": 59,
  "orden": {
    "id": 59,
    "ciudad_origen": "funza",
    "ciudad_destino": "bogota",
    "peso_mercancia": "2000",
    "tipo_producto": "MAIZ",
    "tipo_embalaje": "BULTOS",
    "vehiculo_requerido": "Tracto Mula S3",
    "fecha_hora_descargue_cargue": "12-10-2025 11:00",
    "temperatura_mercancia": "ambiente",
    "humedad": "normal"
  },
  "completitud": "85%"
}
```

### 💡 Casos de Uso
- Consultar información operativa de orden
- Buscar órdenes por ciudad o producto
- Ver estadísticas de completitud de datos

---

## 23. llenar_formulario

### 📝 Descripción
Llena automáticamente un formulario de cotización usando datos de una orden existente. Convierte la información técnica en valores de formulario listos para usar por el agente de IA.

### 📥 Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción | Valor por Defecto |
|-----------|------|-----------|-------------|-------------------|
| `orden_id` | integer | ✅ Sí | ID de la orden para obtener datos del formulario | - |
| `tipo_formulario` | string | No | Tipo de formulario a llenar | "cotizacion" |

**Restricciones**:
- `orden_id`: Mínimo 1
- `tipo_formulario`: Valores: "cotizacion", "pre_solicitud", "despacho"

### 📤 Salida Esperada
```json
{
  "success": true,
  "orden_id": 59,
  "tipo_formulario": "cotizacion",
  "formulario": {
    "ciudad_origen": "funza",
    "ciudad_destino": "bogota",
    "peso_mercancia": "2000",
    "tipo_embalaje": "BULTOS",
    "tipo_producto": "MAIZ",
    "vehiculo_requerido": "Tracto Mula S3",
    "fecha_cargue": "12-10-2025",
    "hora_cargue": "11:00"
  },
  "campos_listos": 12,
  "campos_faltantes": 3
}
```

### 💡 Casos de Uso
- Auto-llenar formularios web
- Preparar datos para ingreso rápido
- Facilitar captura de información

---

## 📊 Resumen de Categorías

### 🔹 Gestión de Llamadas (5)
1. get_llamadas
2. get_llamada_by_id
3. create_llamada
4. update_llamada_status
9. get_llamadas_by_telefono

### 🔹 Conversaciones ElevenLabs (3)
7. process_elevenlabs_event
10. activate_conversation
11. generate_transport_offer

### 🔹 Vehículos y Choferes (2)
6. get_vehicle_by_telefono_conductor
8. get_chofer_by_placa

### 🔹 Cotizaciones (6)
5. get_cotizaciones
13. get_group_cotizations
14. get_group_cotizations_by_user
15. get_cotizacion_with_group_info
16. get_cotizations_by_group
20. get_cotizacion_with_pricing_info

### 🔹 Precios (4)
17. get_pricings
18. get_pricing_by_vehicle_type
19. search_pricings_by_route
21. precioviaje

### 🔹 Decisiones y Órdenes (3)
12. save_driver_decision
22. zinformacion
23. llenar_formulario

---

## 🔧 Información Técnica

### Endpoint Base
```
https://my-kontrol.online/mcp/elevenlabs
```

### Formato de Request
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "tools/call",
  "params": {
    "name": "nombre_herramienta",
    "arguments": {
      "parametro1": "valor1",
      "parametro2": "valor2"
    }
  }
}
```

### Headers Requeridos
```
Content-Type: application/json
```

### Códigos de Estado
- `200 OK`: Operación exitosa
- `400 Bad Request`: Parámetros inválidos
- `404 Not Found`: Recurso no encontrado
- `500 Internal Server Error`: Error del servidor

---

## 📝 Notas Importantes

1. **Paginación**: La mayoría de las herramientas que retornan listas soportan `page` y `limit`
2. **IDs**: Todos los IDs son enteros positivos (mínimo 1)
3. **Teléfonos**: Aceptan formato internacional con `+` y formato local
4. **Fechas**: Formato `DD-MM-YYYY HH:MM`
5. **Estados**: Valores estandarizados: "pendiente", "en_curso", "completada", "fallida", "reagendada"
6. **conversation_id**: Siempre con prefijo `conv_`
7. **Conversión automática**: Los IDs de productos y embalajes se convierten a nombres legibles

---

**Última actualización**: 11 de Octubre de 2025  
**Versión del servidor**: MCP 1.0  
**Base de datos**: MySQL ai_transport
