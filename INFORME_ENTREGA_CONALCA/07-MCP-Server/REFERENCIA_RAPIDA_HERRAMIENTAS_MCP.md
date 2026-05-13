# 📚 REFERENCIA RÁPIDA: HERRAMIENTAS MCP CONALCA

**Documento de Referencia para Desarrolladores**  
**39 Campos de Cotización + 42 del Vehículo**  
**11 Herramientas Principales + Búsquedas Avanzadas**

---

## ⚡ RESUMEN EJECUTIVO

```
Total de Herramientas: 15
├─ Llamadas: 6 herramientas
├─ Cotizaciones: 4 herramientas
├─ Vehículos: 4 herramientas
└─ Precios/Grupos: 5 herramientas

Tiempo Promedio de Respuesta: 50-100ms por query
Pool de Conexiones: 10 conexiones máximo
Base de Datos: MySQL ai_transport
Protocolo: JSON-RPC 2.0
```

---

## 📞 HERRAMIENTAS DE LLAMADAS

### 1️⃣ `get_llamadas` - Listar todas las llamadas

```json
{
  "method": "tools/call",
  "params": {
    "name": "get_llamadas",
    "arguments": {
      "limit": 100,
      "offset": 0
    }
  }
}
```

**Respuesta:**
```json
{
  "id_llamada": 46,
  "id_cotizacion": 45,
  "chofer_id": 67,
  "status": "en_curso",
  "elevenlabs_conversation_id": "conv_4301k697vrn3e47b1rre6r6e3rzb",
  "elevenlabs_sip_call_id": "SCL_XaBPFTPBv8TH",
  "call_started_at": "2025-10-05T10:30:00",
  "call_ended_at": null,
  "call_notes": "Teléfono: +584263774021",
  "created_at": "2025-10-05T10:25:00",
  "updated_at": "2025-10-05T10:30:00"
}
```

**Casos de Uso:**
- Dashboard de llamadas activas
- Reporte diario de actividad
- Monitoreo de operaciones

---

### 2️⃣ `get_llamada_by_id` - Obtener llamada específica

```json
{
  "method": "tools/call",
  "params": {
    "name": "get_llamada_by_id",
    "arguments": {
      "id_llamada": 46
    }
  }
}
```

**Uso:** Cuando tienes el ID de la llamada

---

### 3️⃣ `get_llamada_by_conversation_id` - Sincronizar con ElevenLabs

```json
{
  "method": "tools/call",
  "params": {
    "name": "get_llamada_by_conversation_id",
    "arguments": {
      "conversation_id": "conv_4301k697vrn3e47b1rre6r6e3rzb"
    }
  }
}
```

**Uso:** Desde ElevenLabs, para sincronizar una llamada activa  
**Tiempo:** ~1-5ms con índice

---

### 4️⃣ `get_llamadas_by_telefono` - Buscar por teléfono

```json
{
  "method": "tools/call",
  "params": {
    "name": "get_llamadas_by_telefono",
    "arguments": {
      "telefono": "+584263774021",
      "tipo_busqueda": "ambos"
    }
  }
}
```

**Parámetros:**
- `tipo_busqueda`: "origen" | "destino" | "ambos"

**Uso:** Historial de llamadas de un conductor

---

### 5️⃣ `update_llamada_status` - Actualizar en tiempo real

```json
{
  "method": "tools/call",
  "params": {
    "name": "update_llamada_status",
    "arguments": {
      "id_llamada": 46,
      "status": "completada",
      "call_notes": "Conductor aceptó. Ruta: Bogotá-Cali"
    }
  }
}
```

**Estados Válidos:**
- `pendiente` - Programada
- `en_curso` - En progreso
- `completada` - Finalizada
- `fallida` - Error
- `reagendada` - Reprogramada

**Uso:** Registrar decisión del conductor

---

### 6️⃣ `create_llamada` - Crear nueva llamada

```json
{
  "method": "tools/call",
  "params": {
    "name": "create_llamada",
    "arguments": {
      "id_cotizacion": 45,
      "chofer_id": 67,
      "status": "pendiente"
    }
  }
}
```

**Respuesta:** `{"success": true, "id_llamada": 47}`

---

## 📦 HERRAMIENTAS DE COTIZACIONES

### 1️⃣ `get_cotizaciones` - Listar todas

```json
{
  "method": "tools/call",
  "params": {
    "name": "get_cotizaciones",
    "arguments": {
      "limit": 100,
      "offset": 0
    }
  }
}
```

**Campos Devueltos (vista simplificada):**
- id, pricing_id, ciudad_origen, ciudad_destino
- peso_mercancia, vehiculo_requerido, ruta
- valor, tipo_mercancia, silogtran_status

---

### 2️⃣ `get_cotizacion_by_id` - Obtener todos los 39 campos

```json
{
  "method": "tools/call",
  "params": {
    "name": "get_cotizacion_by_id",
    "arguments": {
      "id_cotizacion": 45
    }
  }
}
```

**Retorna Todos los Campos:**

```json
{
  "id": 45,
  "pricing_id": 123,
  "porcentaje": "10%",
  "ciudad_origen": "Bogotá",
  "ciudad_destino": "Cali",
  "ciudad_origen_dane": "11001",
  "ciudad_destino_dane": "76110",
  "peso_mercancia": "2500",
  "cantidad": "100",
  "tipo_embajale": "Caja",
  "dimensiones_exactas": "100x50x50cm",
  "registro_fotografico": "url_foto",
  "planos": "url_plano",
  "tipo_producto": "Electrónico",
  "temperatura_mercancia": "-5°C a 5°C",
  "humedad": "40-60%",
  "vehiculo_requerido": "Furgón",
  "regimen_nacionalizado": "Importación",
  "agente_aduanas": "Empresa XYZ",
  "descargue_cargue": "Bogotá",
  "consolidado_expreso": "Consolidado",
  "fcl_lcl": "LCL",
  "sitio_devolucion_contenedor": "Terminal Bogotá",
  "numero_documento_bl": "BL123456",
  "fecha_hora_descargue_cargue": "2025-10-05 10:00",
  "cantidad_vh": "1",
  "un": "KG",
  "ruta": "Bogotá → Cali",
  "frecuencia": "Diaria",
  "esquema_seguridad": "Custodia",
  "tipo_carroceria": "Furgón isotérmico",
  "valor": "250000",
  "valor_declarado": "300000",
  "flete": "200000",
  "tipo_mercancia": "Frágil",
  "ventanas_horarios_recibidos": "Mañana",
  "seguro": "15000",
  "silogtran_status": "Confirmado",
  "group_cotizations_id": 10,
  "created_at": "2025-10-01T14:30:00",
  "updated_at": "2025-10-05T10:00:00"
}
```

---

### 3️⃣ `search_cotizaciones_by_ruta` - Buscar por ruta

```json
{
  "method": "tools/call",
  "params": {
    "name": "search_cotizaciones_by_ruta",
    "arguments": {
      "ruta": "Bogotá → Cali"
    }
  }
}
```

**Uso:** Encontrar cotizaciones en una ruta específica

---

### 4️⃣ `get_cotizacion_with_group_info` - Cotización + Grupo

```json
{
  "method": "tools/call",
  "params": {
    "name": "get_cotizacion_with_group_info",
    "arguments": {
      "cotizacion_id": 45
    }
  }
}
```

**Respuesta:**
```json
{
  "cotizacion": {
    "id": 45,
    "precio": "250000",
    ...39 campos...
  },
  "grupo": {
    "type": "transporte_terrestre",
    "reference": "REF-001-2026",
    "status": "activo"
  }
}
```

---

## 🚗 HERRAMIENTAS DE VEHÍCULOS

### 1️⃣ `get_vehicles` - Listar todos

```json
{
  "method": "tools/call",
  "params": {
    "name": "get_vehicles",
    "arguments": {
      "limit": 100,
      "offset": 0
    }
  }
}
```

---

### 2️⃣ `get_vehicle_by_placa` - Obtener por placa

```json
{
  "method": "tools/call",
  "params": {
    "name": "get_vehicle_by_placa",
    "arguments": {
      "placa": "ABC-1234"
    }
  }
}
```

**Retorna 42 Campos:**
```json
{
  "id": 1,
  "placa": "ABC-1234",
  "propietario": "Empresa XYZ",
  "conductor": "Juan Pérez",
  "telefono_conductor": "+584263774021",
  "marca": "Hino",
  "modelo": 2022,
  "carroceria": "Furgón isotérmico",
  "capacidad": 8000,
  "ciudad_conductor": "Bogotá",
  ...42 campos totales...
}
```

---

### 3️⃣ `get_vehicle_by_telefono_conductor` - Buscar por teléfono

```json
{
  "method": "tools/call",
  "params": {
    "name": "get_vehicle_by_telefono_conductor",
    "arguments": {
      "telefono": "+584263774021"
    }
  }
}
```

**Tiempo:** ~3-8ms con índice  
**Uso:** Sincronizar vehículo cuando tienes teléfono de conductor

---

### 4️⃣ `search_vehicles_by_conductor` - Buscar por nombre

```json
{
  "method": "tools/call",
  "params": {
    "name": "search_vehicles_by_conductor",
    "arguments": {
      "conductor_name": "Juan Pérez"
    }
}
```

---

## 💳 HERRAMIENTAS DE PRECIOS

### 1️⃣ `get_pricings` - Listar todas las tarifas

```json
{
  "method": "tools/call",
  "params": {
    "name": "get_pricings",
    "arguments": {
      "limit": 100,
      "offset": 0
    }
  }
}
```

---

### 2️⃣ `get_pricing_by_id` - Obtener tarifa específica

```json
{
  "method": "tools/call",
  "params": {
    "name": "get_pricing_by_id",
    "arguments": {
      "pricing_id": 123
    }
  }
}
```

**Retorna:**
```json
{
  "id": 123,
  "vehicle_type": "Furgón",
  "type_pricing": "Viaje Completo",
  "origin": "Bogotá",
  "destination": "Cali",
  "price": "200000",
  "weight_from": "2000",
  "weight_to": "5000",
  "price_month": 1500000,
  "price_week": 350000,
  "price_day": 50000,
  "extra": "Seguro",
  "price_extra": "15000",
  "condition": "Pago contra entrega"
}
```

---

## 🎯 HERRAMIENTAS DE GRUPOS

### 1️⃣ `get_group_cotizations` - Listar grupos

```json
{
  "method": "tools/call",
  "params": {
    "name": "get_group_cotizations",
    "arguments": {
      "limit": 100,
      "offset": 0
    }
  }
}
```

---

### 2️⃣ `get_group_cotization_by_id` - Obtener grupo

```json
{
  "method": "tools/call",
  "params": {
    "name": "get_group_cotization_by_id",
    "arguments": {
      "group_id": 10
    }
  }
}
```

**Retorna:**
```json
{
  "id": 10,
  "user_id": 5,
  "client_id": 8,
  "type": "transporte_terrestre",
  "reference": "REF-001-2026",
  "status": "activo",
  "created_at": "2025-10-01T14:30:00",
  "updated_at": "2025-10-05T10:00:00"
}
```

---

### 3️⃣ `get_cotizaciones_by_group` - Todas las cotizaciones de un grupo

```json
{
  "method": "tools/call",
  "params": {
    "name": "get_cotizaciones_by_group",
    "arguments": {
      "group_id": 10
    }
  }
}
```

---

## 🔄 FLUJO RECOMENDADO: UNA LLAMADA COMPLETA

```javascript
// PASO 1: Sincronizar llamada
GET /mcp/elevenlabs
{
  "method": "tools/call",
  "params": {
    "name": "get_llamada_by_conversation_id",
    "arguments": {"conversation_id": "conv_..."}
  }
}
// Retorna: id_llamada=46, id_cotizacion=45, chofer_id=67

// PASO 2: Obtener cotización completa
{
  "method": "tools/call",
  "params": {
    "name": "get_cotizacion_by_id",
    "arguments": {"id_cotizacion": 45}
  }
}
// Retorna: 39 campos de la cotización

// PASO 3: Obtener vehículo del conductor
{
  "method": "tools/call",
  "params": {
    "name": "get_vehicle_by_telefono_conductor",
    "arguments": {"telefono": "+584263774021"}
  }
}
// Retorna: 42 campos del vehículo

// PASO 4: Obtener información de precios
{
  "method": "tools/call",
  "params": {
    "name": "get_pricing_by_id",
    "arguments": {"pricing_id": 123}
  }
}
// Retorna: Tarifas y condiciones

// PASO 5: Actualizar estado de llamada
{
  "method": "tools/call",
  "params": {
    "name": "update_llamada_status",
    "arguments": {
      "id_llamada": 46,
      "status": "completada",
      "call_notes": "Conductor aceptó transporte"
    }
  }
}
// Retorna: Confirmación de actualización
```

---

## ⏱️ RENDIMIENTO POR HERRAMIENTA

| Herramienta | Sin Índice | Con Índice | Recomendación |
|------------|-----------|-----------|---------------|
| get_llamada_by_conversation_id | 200-500ms | 1-5ms | **Crear índice** |
| get_cotizacion_by_id | 50-100ms | 1-2ms | ✓ Bueno |
| get_vehicle_by_telefono_conductor | 300-800ms | 3-8ms | **Crear índice** |
| search_cotizaciones_by_ruta | 100-300ms | 2-5ms | **Crear índice** |
| update_llamada_status | 50-80ms | 30-50ms | ✓ Aceptable |

---

## 🔧 INDICADORES DE ÉXITO

✅ Conecta con ElevenLabs sin lag  
✅ Obtiene 39 campos de cotización en <100ms  
✅ Busca vehículo por teléfono en <10ms  
✅ Actualiza estado de llamada en tiempo real  
✅ Valida compatibilidad vehículo-carga  

---

## 🚨 TROUBLESHOOTING RÁPIDO

**Problema:** Conexión lenta  
**Solución:** Verificar índices BD, aumentar pool a 20  

**Problema:** Llamada no sincroniza  
**Solución:** Verificar `elevenlabs_conversation_id` exacto  

**Problema:** Vehículo no se encuentra  
**Solución:** Verificar teléfono con formato correcto (+5XX...)  

**Problema:** Precio no coincide  
**Solución:** Verificar `pricing_id` en cotización  

---

## 📞 SOPORTE

- **Base de Datos:** MySQL `ai_transport`
- **Servidor:** FastAPI + Uvicorn
- **Protocolo:** JSON-RPC 2.0
- **Pool Conexiones:** 10 (máximo recomendado: 20)
- **Archivos Clave:**
  - `/mcp-server/conalca_mcp_server/server.py`
  - `/mcp-server/conalca_mcp_server/tools.py`
  - `/mcp-server/conalca_mcp_server/models.py`

