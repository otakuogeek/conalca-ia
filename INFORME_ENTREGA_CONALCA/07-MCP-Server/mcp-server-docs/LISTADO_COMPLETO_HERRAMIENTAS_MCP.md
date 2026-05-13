# 🔧 LISTADO COMPLETO DE HERRAMIENTAS MCP - CONALCA
**Servidor de Transporte Inteligente con Protocolo MCP**

---

## 📊 Información General del Servidor

| **Propiedad** | **Valor** |
|---------------|-----------|
| **URL Producción** | `https://conalcaia.conalca.com.co/mcp/` |
| **Puerto Local** | `18840` |
| **Protocolo** | `MCP JSON-RPC 2.0` |
| **Servicio SystemD** | `conalca-mcp-server.service` |
| **Base de Datos** | `MySQL - ai_transport` |
| **Estado** | ✅ **ACTIVO** |
| **Total Herramientas** | **20 herramientas** |
| **Integración** | ElevenLabs AI Agent |

---

## 🆕 Resumen de Herramientas por Categoría

### 📞 **Gestión de Llamadas** (6 herramientas)
- `get_llamadas` - Lista todas las llamadas con paginación
- `get_llamada_by_id` - Obtiene llamada específica por ID
- `create_llamada` - Crea nueva llamada telefónica
- `update_llamada_status` - Actualiza estado de llamada
- `get_llamadas_by_telefono` - Busca llamadas por teléfono
- `activate_conversation` - Activa conversación ElevenLabs

### 🚛 **Gestión de Vehículos y Cotizaciones** (3 herramientas)
- `get_cotizaciones` - Lista cotizaciones con paginación
- `get_vehicle_by_telefono_conductor` - Busca vehículo por teléfono
- `get_chofer_by_placa` - Obtiene chofer por placa

### 🤖 **Integración ElevenLabs** (3 herramientas)
- `process_elevenlabs_event` - Procesa eventos de webhook
- `generate_transport_offer` - Genera oferta personalizada
- `save_driver_decision` - Guarda decisión del chofer

### 👥 **Gestión de Grupos** (4 herramientas - NUEVAS)
- `get_group_cotizations` - Lista grupos de cotizaciones
- `get_group_cotizations_by_user` - Grupos por usuario
- `get_cotizacion_with_group_info` - Cotización con info de grupo
- `get_cotizations_by_group` - Cotizaciones por grupo

### 💰 **Gestión de Precios** (4 herramientas - NUEVAS)
- `get_pricings` - Lista precios con paginación
- `get_pricing_by_vehicle_type` - Precios por tipo de vehículo
- `search_pricings_by_route` - Búsqueda por ruta
- `get_cotizacion_with_pricing_info` - Cotización con info de precios

---

## 📋 DETALLE COMPLETO DE TODAS LAS HERRAMIENTAS

### 📞 **1. GESTIÓN DE LLAMADAS**

#### **1.1 get_llamadas**
```json
{
  "name": "get_llamadas",
  "description": "Obtiene todas las llamadas telefónicas con paginación opcional. Permite filtrar por estado y obtener información detallada de cada llamada.",
  "inputSchema": {
    "type": "object",
    "properties": {
      "page": {
        "type": "integer", 
        "description": "Número de página para paginación",
        "default": 1,
        "minimum": 1
      },
      "limit": {
        "type": "integer", 
        "description": "Cantidad máxima de resultados por página",
        "default": 20,
        "minimum": 1,
        "maximum": 100
      }
    }
  }
}
```
**Ejemplo de uso:**
```bash
curl -X POST https://conalcaia.conalca.com.co/mcp/ \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": "1",
    "method": "tools/call",
    "params": {
      "name": "get_llamadas",
      "arguments": {"page": 1, "limit": 10}
    }
  }'
```

#### **1.2 get_llamada_by_id**
```json
{
  "name": "get_llamada_by_id",
  "description": "Obtiene información detallada de una llamada específica usando su ID único. Incluye estado, fecha, observaciones y datos del contacto.",
  "inputSchema": {
    "type": "object",
    "properties": {
      "llamada_id": {
        "type": "integer",
        "description": "ID único de la llamada a consultar",
        "minimum": 1
      }
    },
    "required": ["llamada_id"]
  }
}
```

#### **1.3 create_llamada**
```json
{
  "name": "create_llamada",
  "description": "Crea una nueva llamada telefónica en el sistema. Registra el número de teléfono, estado inicial y observaciones opcionales.",
  "inputSchema": {
    "type": "object",
    "properties": {
      "telefono": {
        "type": "string",
        "description": "Número de teléfono del contacto",
        "pattern": "^[+]?[0-9\\s\\-\\(\\)]+$"
      },
      "estado": {
        "type": "string",
        "description": "Estado inicial de la llamada",
        "default": "pendiente",
        "enum": ["pendiente", "en_curso", "completada", "fallida", "reagendada"]
      },
      "observaciones": {
        "type": "string",
        "description": "Observaciones o notas adicionales sobre la llamada"
      }
    },
    "required": ["telefono"]
  }
}
```

#### **1.4 update_llamada_status**
```json
{
  "name": "update_llamada_status",
  "description": "Actualiza el estado de una llamada existente. Útil para hacer seguimiento del progreso de las llamadas.",
  "inputSchema": {
    "type": "object",
    "properties": {
      "llamada_id": {
        "type": "integer",
        "description": "ID de la llamada a actualizar",
        "minimum": 1
      },
      "new_status": {
        "type": "string",
        "description": "Nuevo estado de la llamada",
        "enum": ["pendiente", "en_curso", "completada", "fallida", "reagendada"]
      }
    },
    "required": ["llamada_id", "new_status"]
  }
}
```

#### **1.5 get_llamadas_by_telefono**
```json
{
  "name": "get_llamadas_by_telefono",
  "description": "Busca todas las llamadas asociadas a un número de teléfono específico. Puede buscar tanto en el campo telefono (origen) como en numero_destino (destino).",
  "inputSchema": {
    "type": "object",
    "properties": {
      "telefono": {
        "type": "string",
        "description": "Número de teléfono a buscar (formato: 3123456789, +573123456789, etc.)",
        "pattern": "^[+]?[0-9\\s\\-\\(\\)]+$"
      },
      "tipo_busqueda": {
        "type": "string",
        "description": "Tipo de búsqueda a realizar",
        "enum": ["origen", "destino", "ambos"],
        "default": "ambos"
      }
    },
    "required": ["telefono"]
  }
}
```

#### **1.6 activate_conversation**
```json
{
  "name": "activate_conversation",
  "description": "Activa o actualiza el estado de una llamada usando el conversation_id de ElevenLabs. Permite cambiar el estado y agregar notas a la conversación.",
  "inputSchema": {
    "type": "object",
    "properties": {
      "conversation_id": {
        "type": "string",
        "description": "ID de conversación de ElevenLabs (ej: conv_430tk697vm3e47b1rre6r6e3rzb)",
        "pattern": "^conv_[a-zA-Z0-9]+$"
      },
      "new_status": {
        "type": "string",
        "description": "Nuevo estado de la conversación",
        "enum": ["pendiente", "en_curso", "completada", "fallida", "reagendada"],
        "default": "en_curso"
      },
      "call_notes": {
        "type": "string",
        "description": "Notas adicionales sobre la conversación o llamada"
      },
      "sip_call_id": {
        "type": "string",
        "description": "ID de llamada SIP de ElevenLabs (opcional)"
      }
    },
    "required": ["conversation_id"]
  }
}
```

---

### 🚛 **2. GESTIÓN DE VEHÍCULOS Y COTIZACIONES**

#### **2.1 get_cotizaciones**
```json
{
  "name": "get_cotizaciones",
  "description": "Obtiene lista de cotizaciones de vehículos disponibles. Incluye modelos, precios y especificaciones técnicas.",
  "inputSchema": {
    "type": "object",
    "properties": {
      "page": {
        "type": "integer",
        "description": "Número de página para paginación",
        "default": 1,
        "minimum": 1
      },
      "limit": {
        "type": "integer",
        "description": "Cantidad máxima de cotizaciones por página",
        "default": 20,
        "minimum": 1,
        "maximum": 100
      }
    }
  }
}
```

#### **2.2 get_vehicle_by_telefono_conductor**
```json
{
  "name": "get_vehicle_by_telefono_conductor",
  "description": "Busca vehículos asociados a un conductor específico usando su número de teléfono. Devuelve información del vehículo, conductor y propietario.",
  "inputSchema": {
    "type": "object",
    "properties": {
      "telefono": {
        "type": "string",
        "description": "Número de teléfono del conductor",
        "pattern": "^[+]?[0-9\\s\\-\\(\\)]+$"
      }
    },
    "required": ["telefono"]
  }
}
```

#### **2.3 get_chofer_by_placa**
```json
{
  "name": "get_chofer_by_placa",
  "description": "Obtiene información del chofer y vehículo mediante el número de placa. Devuelve datos completos del conductor, propietario, poseedor, especificaciones del vehículo y el ID único del registro.",
  "inputSchema": {
    "type": "object",
    "properties": {
      "placa": {
        "type": "string",
        "description": "Número de placa del vehículo (ej: ABC123, XYZ789)",
        "pattern": "^[A-Z0-9]{3,8}$"
      }
    },
    "required": ["placa"]
  }
}
```

---

### 🤖 **3. INTEGRACIÓN ELEVENLABS**

#### **3.1 process_elevenlabs_event**
```json
{
  "name": "process_elevenlabs_event",
  "description": "Procesa eventos de webhook de ElevenLabs como inicio/fin de conversación, cambios de estado de llamada, etc.",
  "inputSchema": {
    "type": "object",
    "properties": {
      "event_type": {
        "type": "string",
        "description": "Tipo de evento de ElevenLabs",
        "enum": ["conversation_started", "conversation_ended", "call_status_change", "user_input", "agent_response"]
      },
      "conversation_id": {
        "type": "string",
        "description": "ID único de la conversación"
      },
      "phone_number": {
        "type": "string",
        "description": "Número de teléfono asociado al evento"
      },
      "status": {
        "type": "string",
        "description": "Estado de la llamada (para eventos de cambio de estado)"
      },
      "duration": {
        "type": "integer",
        "description": "Duración en segundos (para eventos de fin de conversación)"
      },
      "metadata": {
        "type": "object",
        "description": "Metadatos adicionales del evento"
      }
    },
    "required": ["event_type"]
  }
}
```

#### **3.2 generate_transport_offer**
```json
{
  "name": "generate_transport_offer",
  "description": "Genera una oferta personalizada de transporte usando el conversation_id. Consulta la información del chofer y cotización para crear un mensaje comercial completo para Natalia Álvarez de CONALCA.",
  "inputSchema": {
    "type": "object",
    "properties": {
      "conversation_id": {
        "type": "string",
        "description": "ID de conversación de ElevenLabs para consultar datos de la llamada",
        "pattern": "^conv_[a-zA-Z0-9]+$"
      }
    },
    "required": ["conversation_id"]
  }
}
```

#### **3.3 save_driver_decision**
```json
{
  "name": "save_driver_decision",
  "description": "Guarda la decisión del chofer sobre la oferta de transporte. Registra si acepta (1) o rechaza (0) la propuesta junto con el ID del chofer y cotización.",
  "inputSchema": {
    "type": "object",
    "properties": {
      "cotizacion_model_id": {
        "type": "integer",
        "description": "ID de la cotización/modelo asociado",
        "minimum": 1
      },
      "driver_id": {
        "type": "integer",
        "description": "ID del chofer/conductor",
        "minimum": 1
      },
      "decision": {
        "type": "integer",
        "description": "Decisión del chofer: 1 para aceptar, 0 para rechazar",
        "enum": [0, 1]
      }
    },
    "required": ["cotizacion_model_id", "driver_id", "decision"]
  }
}
```

---

### 👥 **4. GESTIÓN DE GRUPOS (NUEVAS HERRAMIENTAS)**

#### **4.1 get_group_cotizations** ⭐ **NUEVA**
```json
{
  "name": "get_group_cotizations",
  "description": "Obtiene una lista de grupos de cotizaciones con paginación",
  "inputSchema": {
    "type": "object",
    "properties": {
      "limit": {
        "type": "integer",
        "description": "Cantidad máxima de resultados por página",
        "default": 100,
        "minimum": 1
      },
      "offset": {
        "type": "integer",
        "description": "Número de registros a omitir",
        "default": 0,
        "minimum": 0
      }
    }
  }
}
```
**Tabla asociada:** `group_cotizations`

#### **4.2 get_group_cotizations_by_user** ⭐ **NUEVA**
```json
{
  "name": "get_group_cotizations_by_user",
  "description": "Obtiene grupos de cotizaciones asociados a un usuario específico",
  "inputSchema": {
    "type": "object",
    "properties": {
      "user_id": {
        "type": "integer",
        "description": "ID del usuario",
        "minimum": 1
      }
    },
    "required": ["user_id"]
  }
}
```

#### **4.3 get_cotizacion_with_group_info** ⭐ **NUEVA**
```json
{
  "name": "get_cotizacion_with_group_info",
  "description": "Obtiene una cotización específica con información completa del grupo asociado",
  "inputSchema": {
    "type": "object",
    "properties": {
      "cotizacion_id": {
        "type": "integer",
        "description": "ID de la cotización",
        "minimum": 1
      }
    },
    "required": ["cotizacion_id"]
  }
}
```
**Relación:** `cotizacion_models.group_cotizations_id → group_cotizations.id`

#### **4.4 get_cotizations_by_group** ⭐ **NUEVA**
```json
{
  "name": "get_cotizations_by_group",
  "description": "Obtiene todas las cotizaciones que pertenecen a un grupo específico",
  "inputSchema": {
    "type": "object",
    "properties": {
      "group_id": {
        "type": "integer",
        "description": "ID del grupo de cotizaciones",
        "minimum": 1
      }
    },
    "required": ["group_id"]
  }
}
```

---

### 💰 **5. GESTIÓN DE PRECIOS (NUEVAS HERRAMIENTAS)**

#### **5.1 get_pricings** ⭐ **NUEVA**
```json
{
  "name": "get_pricings",
  "description": "Obtiene una lista de precios con paginación",
  "inputSchema": {
    "type": "object",
    "properties": {
      "limit": {
        "type": "integer",
        "description": "Cantidad máxima de resultados por página",
        "default": 100,
        "minimum": 1
      },
      "offset": {
        "type": "integer",
        "description": "Número de registros a omitir",
        "default": 0,
        "minimum": 0
      }
    }
  }
}
```
**Tabla asociada:** `pricings`

#### **5.2 get_pricing_by_vehicle_type** ⭐ **NUEVA**
```json
{
  "name": "get_pricing_by_vehicle_type",
  "description": "Obtiene precios filtrados por tipo de vehículo",
  "inputSchema": {
    "type": "object",
    "properties": {
      "vehicle_type": {
        "type": "string",
        "description": "Tipo de vehículo para filtrar precios"
      }
    },
    "required": ["vehicle_type"]
  }
}
```

#### **5.3 search_pricings_by_route** ⭐ **NUEVA**
```json
{
  "name": "search_pricings_by_route",
  "description": "Busca precios por ruta específica (origen y destino)",
  "inputSchema": {
    "type": "object",
    "properties": {
      "origin": {
        "type": "string",
        "description": "Ciudad o lugar de origen"
      },
      "destination": {
        "type": "string",
        "description": "Ciudad o lugar de destino"
      }
    }
  }
}
```

#### **5.4 get_cotizacion_with_pricing_info** ⭐ **NUEVA**
```json
{
  "name": "get_cotizacion_with_pricing_info",
  "description": "Obtiene una cotización específica con información completa de precios",
  "inputSchema": {
    "type": "object",
    "properties": {
      "cotizacion_id": {
        "type": "integer",
        "description": "ID de la cotización",
        "minimum": 1
      }
    },
    "required": ["cotizacion_id"]
  }
}
```
**Relación:** `cotizacion_models.pricing_id → pricings.id`

---

## 🗄️ ESQUEMA DE BASE DE DATOS

### **Tablas Principales**
```sql
-- Tabla de llamadas telefónicas
CREATE TABLE llamadas (
  id_llamada INT PRIMARY KEY AUTO_INCREMENT,
  telefono VARCHAR(20) NOT NULL,
  numero_destino VARCHAR(20),
  estado ENUM('pendiente', 'en_curso', 'completada', 'fallida', 'reagendada'),
  observaciones TEXT,
  fecha_llamada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  conversation_id VARCHAR(255),
  chofer_id INT,
  id_cotizacion INT
);

-- Tabla de cotizaciones
CREATE TABLE cotizacion_models (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  group_cotizations_id BIGINT UNSIGNED,
  pricing_id BIGINT UNSIGNED,
  ciudad_origen VARCHAR(100),
  ciudad_destino VARCHAR(100),
  -- Otros campos...
  FOREIGN KEY (group_cotizations_id) REFERENCES group_cotizations(id),
  FOREIGN KEY (pricing_id) REFERENCES pricings(id)
);

-- Tabla de grupos de cotizaciones ⭐ NUEVA RELACIÓN
CREATE TABLE group_cotizations (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  client_id INT NOT NULL,
  type VARCHAR(50),
  reference VARCHAR(100),
  status VARCHAR(50),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);

-- Tabla de precios ⭐ NUEVA RELACIÓN
CREATE TABLE pricings (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  vehicle_type VARCHAR(100),
  origin VARCHAR(100),
  destination VARCHAR(100),
  price DECIMAL(10,2),
  type_pricing VARCHAR(50),
  weight_from DECIMAL(8,2),
  weight_to DECIMAL(8,2)
);

-- Tabla de vehículos y choferes
CREATE TABLE vehicle_owner_holder_driver (
  id INT PRIMARY KEY AUTO_INCREMENT,
  placa VARCHAR(10) NOT NULL,
  conductor VARCHAR(100),
  cedula VARCHAR(20),
  telefono VARCHAR(20),
  -- Otros campos...
);
```

### **Relaciones Establecidas** ⭐
```sql
-- 1. Relación cotizacion_models ↔ group_cotizations
ALTER TABLE cotizacion_models 
ADD CONSTRAINT fk_cotizacion_group 
FOREIGN KEY (group_cotizations_id) REFERENCES group_cotizations(id);

-- 2. Relación cotizacion_models ↔ pricings  
ALTER TABLE cotizacion_models 
ADD CONSTRAINT fk_cotizacion_pricing 
FOREIGN KEY (pricing_id) REFERENCES pricings(id);
```

---

## 🚀 EJEMPLOS DE USO PRÁCTICO

### **Ejemplo 1: Obtener grupos de cotizaciones**
```bash
curl -X POST https://conalcaia.conalca.com.co/mcp/ \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": "1",
    "method": "tools/call",
    "params": {
      "name": "get_group_cotizations",
      "arguments": {"limit": 5, "offset": 0}
    }
  }'
```

### **Ejemplo 2: Buscar precios por ruta**
```bash
curl -X POST https://conalcaia.conalca.com.co/mcp/ \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": "2",
    "method": "tools/call",
    "params": {
      "name": "search_pricings_by_route",
      "arguments": {
        "origin": "Bogotá",
        "destination": "Medellín"
      }
    }
  }'
```

### **Ejemplo 3: Obtener cotización con información completa**
```bash
curl -X POST https://conalcaia.conalca.com.co/mcp/ \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": "3",
    "method": "tools/call",
    "params": {
      "name": "get_cotizacion_with_group_info",
      "arguments": {"cotizacion_id": 123}
    }
  }'
```

---

## 🔧 ADMINISTRACIÓN DEL SERVIDOR

### **Comandos de SystemD**
```bash
# Ver estado del servicio
sudo systemctl status conalca-mcp-server.service

# Reiniciar el servicio
sudo systemctl restart conalca-mcp-server.service

# Ver logs en tiempo real
sudo journalctl -u conalca-mcp-server.service -f

# Detener el servicio
sudo systemctl stop conalca-mcp-server.service

# Iniciar el servicio
sudo systemctl start conalca-mcp-server.service
```

### **Archivos de Configuración**
```bash
# Servicio SystemD
/etc/systemd/system/conalca-mcp-server.service

# Configuración del servidor
/home/ubuntu/mcp/conalca-mcp/mcp-server/conalca_mcp_server/server.py

# Modelos y repositorio
/home/ubuntu/mcp/conalca-mcp/mcp-server/conalca_mcp_server/models.py

# Configuración de base de datos
/home/ubuntu/mcp/conalca-mcp/mcp-server/conalca_mcp_server/database.py
```

### **Verificación de Salud del Servidor**
```bash
# Verificar que el servidor responde
curl -X POST http://127.0.0.1:18840/ \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":"health","method":"tools/list"}'

# Contar herramientas disponibles
curl -s -X POST http://127.0.0.1:18840/ \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":"count","method":"tools/list"}' | \
  grep -o '"name"' | wc -l
```

---

## 📈 MÉTRICAS Y ESTADÍSTICAS

| **Métrica** | **Valor** |
|-------------|-----------|
| **Herramientas Originales** | 12 |
| **Herramientas Nuevas** | 8 |
| **Total Activo** | **20** |
| **Tablas de BD Relacionadas** | 5 |
| **Relaciones FK Nuevas** | 2 |
| **Endpoints Públicos** | 20 |
| **Integración ElevenLabs** | ✅ Activa |
| **Tiempo de Respuesta Promedio** | < 200ms |

---

## 🎯 CASOS DE USO PRINCIPALES

### **1. Gestión de Llamadas de Transporte**
- Registro automático de llamadas ElevenLabs
- Seguimiento de estado de conversaciones
- Activación de ofertas personalizadas

### **2. Consulta de Disponibilidad**
- Búsqueda de vehículos por conductor
- Consulta de chofer por placa
- Verificación de cotizaciones

### **3. Gestión de Grupos y Precios** ⭐ **NUEVO**
- Organización de cotizaciones por grupos
- Consulta de precios por tipo de vehículo
- Búsqueda de tarifas por rutas específicas

### **4. Integración con IA**
- Procesamiento de eventos de ElevenLabs
- Generación automática de ofertas
- Registro de decisiones de choferes

---

## ✅ ESTADO FINAL

**🎉 TODAS LAS HERRAMIENTAS ESTÁN ACTIVAS Y FUNCIONANDO**

- ✅ **Servidor en Producción**: `https://conalcaia.conalca.com.co/mcp/`
- ✅ **20 Herramientas Disponibles**: Originales + 8 Nuevas
- ✅ **Relaciones de BD**: Establecidas y funcionales
- ✅ **Integración ElevenLabs**: Activa y optimizada
- ✅ **Documentación**: Completa y actualizada

---

**Fecha de Actualización:** Octubre 5, 2025  
**Versión:** 2.0 - Con herramientas de grupos y precios  
**Mantenido por:** Equipo de Desarrollo CONALCA