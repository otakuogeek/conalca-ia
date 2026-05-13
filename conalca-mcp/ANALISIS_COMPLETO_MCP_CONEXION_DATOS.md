# 📊 ANÁLISIS COMPLETO: HERRAMIENTA MCP - CONEXIÓN DE DATOS Y COTIZACIONES

**Proyecto:** Conalca MCP Server  
**Versión:** 2.1.0  
**Protocolo:** JSON-RPC 2.0 (Compatible con ElevenLabs)  
**Base de Datos:** MySQL (`ai_transport`)  
**Última Actualización:** 2026-01-05

---

## 🎯 RESUMEN EJECUTIVO

El servidor MCP de Conalca actúa como intermediario entre ElevenLabs y la base de datos de transporte. Permite:

1. **Gestión de Llamadas** - Control del ciclo de vida de conversaciones de IA
2. **Consulta de Cotizaciones** - Acceso a ofertas de transporte con detalles completos
3. **Gestión de Vehículos** - Información de conductores, propietarios y capacidades
4. **Gestión de Precios** - Consulta de tarifas y esquemas de pricing
5. **Agrupación de Cotizaciones** - Organización de múltiples cotizaciones en grupos

---

## 🔌 ARQUITECTURA DE CONEXIÓN

### Flujo General de Datos

```
ElevenLabs (Llamada Entrante)
    ↓
MCP Server (FastAPI + JSON-RPC 2.0)
    ↓
DatabaseConnection Pool (aiomysql)
    ↓
MySQL Database (ai_transport)
    ↓
Respuesta Estructurada → ElevenLabs
```

### Componentes Principales

| Componente | Ubicación | Función |
|-----------|-----------|---------|
| **Server** | `server.py` | Gestor de endpoints MCP y WebSocket |
| **Tools** | `tools.py` | Definición de herramientas disponibles |
| **Models** | `models.py` | Modelos Pydantic y Repository |
| **Database** | `database.py` | Pool de conexiones MySQL async |

### Configuración de Conexión

**Archivo:** `.env`

```properties
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ai_transport
DB_USERNAME=biosanar_user
DB_PASSWORD=/6Tx0eXqFQONTFuoc7aqPicNlPhmuINU
```

**Pool de Conexiones:**
- Mínimo: 1 conexión
- Máximo: 10 conexiones
- Charset: utf8mb4
- Autocommit: Enabled

---

## 📞 TABLA: LLAMADAS

### Estructura de Datos

```sql
CREATE TABLE llamadas (
    id_llamada INT PRIMARY KEY,
    id_cotizacion INT,
    chofer_id INT,
    numero_destino VARCHAR(20),
    status VARCHAR(50),
    elevenlabs_conversation_id VARCHAR(255),
    elevenlabs_sip_call_id VARCHAR(255),
    call_started_at DATETIME,
    call_ended_at DATETIME,
    call_notes TEXT,
    created_at DATETIME,
    updated_at DATETIME
);
```

### Estados de Llamada

| Estado | Descripción |
|--------|------------|
| `pendiente` | Llamada programada pero no realizada |
| `en_curso` | Llamada actualmente en progreso |
| `completada` | Llamada finalizada exitosamente |
| `fallida` | Llamada que no se pudo completar |
| `reagendada` | Llamada reprogramada para otro momento |

### Herramientas MCP Disponibles

#### 1. **get_llamadas**
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

**Retorna:** Lista paginada de todas las llamadas

**Campos Devueltos:**
- `id_llamada` - ID único
- `id_cotizacion` - ID de la cotización asociada
- `chofer_id` - ID del conductor
- `status` - Estado actual
- `elevenlabs_conversation_id` - ID de conversación de ElevenLabs
- `elevenlabs_sip_call_id` - ID de SIP call de ElevenLabs
- `call_started_at` - Fecha/hora de inicio
- `call_ended_at` - Fecha/hora de fin
- `call_notes` - Notas de la llamada
- `created_at` - Fecha de creación
- `updated_at` - Fecha de última actualización

#### 2. **get_llamada_by_id**
```json
{
  "method": "tools/call",
  "params": {
    "name": "get_llamada_by_id",
    "arguments": {
      "id_llamada": 123
    }
  }
}
```

**Retorna:** Detalles completos de una llamada específica

#### 3. **update_llamada_status**
```json
{
  "method": "tools/call",
  "params": {
    "name": "update_llamada_status",
    "arguments": {
      "id_llamada": 123,
      "status": "completada",
      "call_notes": "Conductor aceptó el transporte"
    }
  }
}
```

**Función:** Actualiza estado y notas de una llamada en tiempo real

#### 4. **create_llamada**
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

**Función:** Crea un nuevo registro de llamada

#### 5. **get_llamadas_by_telefono**
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
- `tipo_busqueda`: "origen", "destino", "ambos"

**Retorna:** Llamadas que coinciden con el teléfono

#### 6. **get_llamada_by_conversation_id**
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

**Función:** Busca llamada por ID de conversación de ElevenLabs

---

## 📦 TABLA: COTIZACIONES (cotizacion_models)

### Estructura Completa de Datos

```sql
CREATE TABLE cotizacion_models (
    id INT PRIMARY KEY,
    pricing_id INT,
    porcentaje VARCHAR(255),
    ciudad_origen VARCHAR(255),
    ciudad_destino VARCHAR(255),
    ciudad_origen_dane VARCHAR(10),
    ciudad_destino_dane VARCHAR(10),
    peso_mercancia VARCHAR(255),
    cantidad VARCHAR(255),
    tipo_embajale VARCHAR(255),
    dimensiones_exactas VARCHAR(255),
    registro_fotografico VARCHAR(255),
    planos VARCHAR(255),
    tipo_producto VARCHAR(255),
    temperatura_mercancia VARCHAR(255),
    humedad VARCHAR(255),
    vehiculo_requerido VARCHAR(255),
    regimen_nacionalizado VARCHAR(255),
    agente_aduanas VARCHAR(255),
    descargue_cargue VARCHAR(255),
    consolidado_expreso VARCHAR(255),
    fcl_lcl VARCHAR(255),
    sitio_devolucion_contenedor VARCHAR(255),
    numero_documento_bl VARCHAR(255),
    fecha_hora_descargue_cargue VARCHAR(255),
    cantidad_vh VARCHAR(255),
    un VARCHAR(255),
    ruta VARCHAR(255),
    frecuencia VARCHAR(255),
    esquema_seguridad VARCHAR(255),
    tipo_carroceria VARCHAR(255),
    valor VARCHAR(255),
    valor_declarado VARCHAR(255),
    flete VARCHAR(255),
    tipo_mercancia VARCHAR(255),
    ventanas_horarios_recibidos VARCHAR(255),
    seguro VARCHAR(255),
    silogtran_status VARCHAR(255),
    group_cotizations_id INT,
    created_at DATETIME,
    updated_at DATETIME
);
```

### Campos Principales en cada Cotización

#### 📍 Información de Rutas

| Campo | Descripción | Ejemplo |
|-------|------------|---------|
| `ciudad_origen` | Ciudad de salida | Bogotá |
| `ciudad_destino` | Ciudad de destino | Cali |
| `ciudad_origen_dane` | Código DANE origen | 11001 |
| `ciudad_destino_dane` | Código DANE destino | 76110 |
| `ruta` | Descripción de la ruta | Bogotá → Cali |
| `frecuencia` | Periodicidad del viaje | Diaria, Semanal |

#### 🚚 Información de Vehículos

| Campo | Descripción | Ejemplo |
|-------|------------|---------|
| `vehiculo_requerido` | Tipo de vehículo necesario | Furgón, Plataforma |
| `tipo_carroceria` | Carrocería específica | Furgón isotérmico |
| `cantidad_vh` | Cantidad de vehículos | 1, 2 |
| `esquema_seguridad` | Seguridad requerida | Custodia, Cepo |

#### 📦 Información de Mercancía

| Campo | Descripción | Ejemplo |
|-------|------------|---------|
| `peso_mercancia` | Peso en kg | 2500 |
| `cantidad` | Cantidad de unidades | 100 |
| `tipo_producto` | Clasificación del producto | Electrónico |
| `tipo_mercancia` | Tipo general | Frágil |
| `temperatura_mercancia` | Rango de temperatura | -5°C a 5°C |
| `humedad` | Nivel de humedad requerido | 40-60% |
| `tipo_embajale` | Tipo de embalaje | Caja, Pallet |
| `dimensiones_exactas` | Dimensiones en cm | 100x50x50 |

#### 💰 Información de Precios

| Campo | Descripción | Ejemplo |
|-------|------------|---------|
| `valor` | Valor total de la cotización | 250000 |
| `valor_declarado` | Valor declarado para seguro | 300000 |
| `flete` | Costo de flete | 200000 |
| `seguro` | Costo de seguro | 15000 |
| `porcentaje` | Porcentaje aplicado | 10% |

#### 📋 Documentación y Aduanas

| Campo | Descripción | Ejemplo |
|-------|------------|---------|
| `registro_fotografico` | Evidencia fotográfica | URL/Ruta |
| `planos` | Planos de carga | URL/Ruta |
| `numero_documento_bl` | Bill of Lading | BL123456 |
| `regimen_nacionalizado` | Régimen aduanal | Importación |
| `agente_aduanas` | Agente aduanal | Empresa XYZ |
| `consolidado_expreso` | Tipo de consolidación | Consolidado |
| `fcl_lcl` | Full Container / Less Container | FCL |

#### ⏰ Información Adicional

| Campo | Descripción | Ejemplo |
|-------|------------|---------|
| `fecha_hora_descargue_cargue` | Fecha/hora de operación | 2026-01-05 10:00 |
| `ventanas_horarios_recibidos` | Horarios disponibles | Mañana, Tarde |
| `sitio_devolucion_contenedor` | Punto de devolución | Terminal Bogotá |
| `pricing_id` | ID de tarifa asociada | 123 |
| `group_cotizations_id` | ID de grupo | 45 |
| `silogtran_status` | Estado en SiloGtran | Confirmado |

### Herramientas MCP Disponibles

#### 1. **get_cotizaciones**
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

**Retorna:** Lista paginada de cotizaciones

**Campos Básicos Devueltos:**
- `id`, `pricing_id`, `ciudad_origen`, `ciudad_destino`
- `peso_mercancia`, `vehiculo_requerido`, `ruta`
- `valor`, `tipo_mercancia`, `silogtran_status`

#### 2. **get_cotizacion_by_id**
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

**Retorna:** Todos los campos de una cotización (respuesta completa)

#### 3. **search_cotizaciones_by_ruta**
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

**Retorna:** Cotizaciones que coinciden con la ruta especificada

#### 4. **get_cotizacion_with_group_info**
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

**Retorna:** Cotización + Información del grupo asociado

**Estructura de Respuesta:**
```json
{
  "cotizacion": {
    "id": 45,
    "pricing_id": 123,
    "ciudad_origen": "Bogotá",
    "ciudad_destino": "Cali",
    "peso_mercancia": "2500",
    "vehiculo_requerido": "Furgón",
    "ruta": "Bogotá → Cali",
    "valor": "250000",
    "tipo_mercancia": "Frágil",
    "group_cotizations_id": 10
  },
  "grupo": {
    "type": "transporte_terrestre",
    "reference": "REF-001-2026",
    "status": "activo"
  }
}
```

---

## 👥 TABLA: VEHÍCULOS (vehicle_owner_holder_driver)

### Estructura de Datos

```sql
CREATE TABLE vehicle_owner_holder_driver (
    id INT PRIMARY KEY,
    Telefonopropietario VARCHAR(20),
    Telefonoposeedor VARCHAR(20),
    Telefonoconductor VARCHAR(20),
    Codigo INT,
    Placa VARCHAR(10),
    Propietario VARCHAR(255),
    Tipodocumentopropietario VARCHAR(10),
    Documentopropietario INT,
    Direccion_propietario VARCHAR(255),
    Ciudad_propietario VARCHAR(100),
    Ciudad_codigodane_propietario INT,
    Municipio_nombre VARCHAR(100),
    Municipio_dane VARCHAR(10),
    Departamento_nombre VARCHAR(100),
    Departamento_dane VARCHAR(10),
    Poseedor VARCHAR(255),
    Tipodocumentoposeedor VARCHAR(10),
    Documentoposeedor INT,
    Direccion_poseedor VARCHAR(255),
    Ciudad_poseedor VARCHAR(100),
    Ciudad_codigodane_poseedor INT,
    Conductor VARCHAR(255),
    Tipodocumentoconducotor VARCHAR(10),
    Cedula INT,
    Direccion_conductor VARCHAR(255),
    Ciudad_conductor VARCHAR(100),
    Ciudad_codigodane_conductor INT,
    Vehiculo_ejes INT,
    Clasevehiculo VARCHAR(50),
    Marca VARCHAR(100),
    Clase_linea VARCHAR(50),
    Modelo INT,
    Vehiculo_chasis VARCHAR(100),
    Pais VARCHAR(50),
    Fecha VARCHAR(50),
    Tipafi_codigo INT,
    Tipafi_nombre VARCHAR(100),
    Carroceria VARCHAR(100),
    Capacidad INT,
    Estado VARCHAR(50)
);
```

### Herramientas MCP Disponibles

#### 1. **get_vehicles**
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

**Retorna:** Lista de vehículos con información de propietario, poseedor y conductor

#### 2. **get_vehicle_by_placa**
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

**Retorna:** Todos los datos de un vehículo específico

#### 3. **search_vehicles_by_conductor**
```json
{
  "method": "tools/call",
  "params": {
    "name": "search_vehicles_by_conductor",
    "arguments": {
      "conductor_name": "Juan Pérez"
    }
  }
}
```

**Retorna:** Vehículos asociados a un conductor

#### 4. **get_vehicle_by_telefono_conductor**
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

**Retorna:** Vehículo asociado a un número de teléfono de conductor

---

## 💳 TABLA: PRECIOS (pricings)

### Campos Disponibles

| Campo | Descripción | Tipo |
|-------|------------|------|
| `vehicle_type` | Tipo de vehículo | VARCHAR |
| `type_pricing` | Tipo de tarifa | VARCHAR |
| `origin` | Origen de ruta | VARCHAR |
| `destination` | Destino de ruta | VARCHAR |
| `price` | Precio unitario | VARCHAR |
| `weight_from` | Peso mínimo | VARCHAR |
| `weight_to` | Peso máximo | VARCHAR |
| `condition` | Condición de aplicación | VARCHAR |
| `price_month` | Precio mensual | FLOAT |
| `price_week` | Precio semanal | FLOAT |
| `price_day` | Precio diario | FLOAT |
| `extra` | Conceptos adicionales | VARCHAR |
| `price_extra` | Valor de extras | VARCHAR |
| `download_destiny_iva` | IVA sobre descarga | VARCHAR |

### Herramientas MCP Disponibles

#### 1. **get_pricings**
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

#### 2. **get_pricing_by_id**
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

---

## 🎯 TABLA: GRUPOS DE COTIZACIONES (group_cotizations)

### Estructura

```sql
CREATE TABLE group_cotizations (
    id INT PRIMARY KEY,
    user_id INT,
    client_id INT,
    type VARCHAR(50),
    reference VARCHAR(100),
    status VARCHAR(50),
    created_at DATETIME,
    updated_at DATETIME
);
```

### Herramientas MCP Disponibles

#### 1. **get_group_cotizations**
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

#### 2. **get_group_cotization_by_id**
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

#### 3. **get_cotizaciones_by_group**
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

**Retorna:** Todas las cotizaciones que pertenecen a un grupo

---

## 🔄 FLUJO TÍPICO: LLAMADA ENTRANTE Y CONSULTA DE COTIZACIÓN

### Secuencia de Eventos

```
1. LLAMADA ENTRANTE EN ELEVENLABS
   ├─ ElevenLabs recibe llamada de conductor
   ├─ Extrae conversation_id: conv_4301k697vrn3e47b1rre6r6e3rzb
   └─ Envía solicitud al MCP Server

2. BUSCAR LLAMADA EN MCP (CONEXIÓN EXISTENTE)
   ├─ Tool: get_llamada_by_conversation_id
   ├─ Query: SELECT FROM llamadas WHERE elevenlabs_conversation_id = ?
   ├─ Retorna: id_llamada, id_cotizacion, chofer_id
   └─ Extrae: id_cotizacion = 45

3. OBTENER COTIZACIÓN ASOCIADA
   ├─ Tool: get_cotizacion_by_id
   ├─ ID: 45
   ├─ Query: SELECT * FROM cotizacion_models WHERE id = 45
   └─ Retorna: TODOS los 40+ campos de la cotización

4. OBTENER INFORMACIÓN DEL VEHÍCULO
   ├─ Tool: get_vehicle_by_telefono_conductor
   ├─ Teléfono: extraído de call_notes
   ├─ Query: SELECT * FROM vehicle_owner_holder_driver WHERE telefono = ?
   └─ Retorna: Placa, Conductor, Vehículo, Capacidad

5. OBTENER INFORMACIÓN DE PRECIOS
   ├─ Tool: get_pricing_by_id
   ├─ ID: extraído de cotización
   ├─ Query: SELECT * FROM pricings WHERE id = ?
   └─ Retorna: Tarifa, Condiciones, Precios especiales

6. OBTENER INFORMACIÓN DEL GRUPO
   ├─ Tool: get_cotizacion_with_group_info
   ├─ ID de cotización: 45
   └─ Retorna: Cotización + Información del grupo

7. ACTUALIZAR ESTADO DE LLAMADA
   ├─ Tool: update_llamada_status
   ├─ Status: "en_curso" → "completada"
   ├─ Notes: Respuesta del conductor
   └─ Query: UPDATE llamadas SET status = ?, call_notes = ? WHERE id_llamada = ?

8. RESPUESTA A ELEVENLABS
   └─ Retorna: JSON con toda la información compilada
```

---

## 📊 EJEMPLO COMPLETO: ANÁLISIS DE UNA COTIZACIÓN EN LLAMADA

### Datos que Llegan en la Llamada

```json
{
  "conversation_id": "conv_4301k697vrn3e47b1rre6r6e3rzb",
  "sip_call_id": "SCL_XaBPFTPBv8TH",
  "destination_phone": "+584263774021",
  "timestamp": "2025-10-05T10:30:00"
}
```

### Consultas Ejecutadas al MCP

**1. Buscar llamada por conversation_id:**
```json
REQUEST:
{
  "method": "tools/call",
  "params": {
    "name": "get_llamada_by_conversation_id",
    "arguments": {
      "conversation_id": "conv_4301k697vrn3e47b1rre6r6e3rzb"
    }
  }
}

RESPONSE:
{
  "id_llamada": 46,
  "id_cotizacion": 45,
  "chofer_id": 67,
  "status": "en_curso",
  "elevenlabs_conversation_id": "conv_4301k697vrn3e47b1rre6r6e3rzb",
  "elevenlabs_sip_call_id": "SCL_XaBPFTPBv8TH",
  "call_notes": "Teléfono: +584263774021, Tipo: Conductor"
}
```

**2. Obtener detalles de la cotización:**
```json
REQUEST:
{
  "method": "tools/call",
  "params": {
    "name": "get_cotizacion_by_id",
    "arguments": {
      "id_cotizacion": 45
    }
  }
}

RESPONSE:
{
  "id": 45,
  "pricing_id": 123,
  "ciudad_origen": "Bogotá",
  "ciudad_destino": "Cali",
  "peso_mercancia": "2500",
  "cantidad": "100",
  "tipo_producto": "Electrónico",
  "tipo_mercancia": "Frágil",
  "vehiculo_requerido": "Furgón",
  "ruta": "Bogotá → Cali",
  "valor": "250000",
  "flete": "200000",
  "seguro": "15000",
  "temperatura_mercancia": "-5°C a 5°C",
  "humedad": "40-60%",
  "tipo_carroceria": "Furgón isotérmico",
  "esquema_seguridad": "Custodia",
  "group_cotizations_id": 10,
  "silogtran_status": "Confirmado"
}
```

**3. Obtener información del conductor/vehículo:**
```json
REQUEST:
{
  "method": "tools/call",
  "params": {
    "name": "get_vehicle_by_telefono_conductor",
    "arguments": {
      "telefono": "+584263774021"
    }
  }
}

RESPONSE:
{
  "placa": "ABC-1234",
  "conductor": "Juan Pérez López",
  "cedula": 123456789,
  "telefono_conductor": "+584263774021",
  "direccion_conductor": "Cra 10 #20-30, Bogotá",
  "ciudad_conductor": "Bogotá",
  "propietario": "Empresa Transportes XYZ",
  "telefono_propietario": "+572123456",
  "marca": "Hino",
  "modelo": 2022,
  "clase_linea": "C3",
  "carroceria": "Furgón isotérmico",
  "capacidad": 8000,
  "vehiculo_ejes": 2,
  "tipafi_nombre": "Carga",
  "estado": "Activo"
}
```

**4. Obtener información de precios:**
```json
REQUEST:
{
  "method": "tools/call",
  "params": {
    "name": "get_pricing_by_id",
    "arguments": {
      "pricing_id": 123
    }
  }
}

RESPONSE:
{
  "id": 123,
  "vehicle_type": "Furgón",
  "type_pricing": "Viaje Completo",
  "origin": "Bogotá",
  "destination": "Cali",
  "price": "200000",
  "weight_from": "2000",
  "weight_to": "5000",
  "price_extra": "15000",
  "extra": "Seguro",
  "download_destiny_iva": "19%",
  "condition": "Pago contra entrega"
}
```

### Datos Compilados para el Agente de IA

```json
{
  "llamada": {
    "id": 46,
    "estado": "en_curso",
    "conductor": {
      "nombre": "Juan Pérez López",
      "cedula": 123456789,
      "telefono": "+584263774021",
      "ciudad": "Bogotá"
    }
  },
  "vehiculo": {
    "placa": "ABC-1234",
    "marca": "Hino",
    "modelo": 2022,
    "carroceria": "Furgón isotérmico",
    "capacidad_kg": 8000,
    "estado": "Activo"
  },
  "cotizacion": {
    "id": 45,
    "ruta": "Bogotá → Cali",
    "distancia": "Unknown",
    "mercancia": {
      "tipo": "Frágil",
      "producto": "Electrónico",
      "peso_kg": 2500,
      "cantidad": 100,
      "temperatura": "-5°C a 5°C",
      "humedad": "40-60%"
    },
    "requisitos": {
      "tipo_vehiculo": "Furgón",
      "carroceria": "Furgón isotérmico",
      "esquema_seguridad": "Custodia",
      "ejes_requeridos": 2
    }
  },
  "precios": {
    "flete": 200000,
    "seguro": 15000,
    "total": 250000,
    "iva": "19%",
    "condicion_pago": "Contra entrega"
  },
  "grupo": {
    "id": 10,
    "tipo": "transporte_terrestre",
    "referencia": "REF-001-2026",
    "estado": "activo"
  },
  "validaciones": {
    "peso_ok": "2500 <= 8000 ✓",
    "ejes_ok": "2 ejes requeridos, vehículo tiene 2 ✓",
    "capacidad_ok": "Cumple ✓",
    "conductor_activo": "Sí ✓",
    "silogtran_confirmado": "Sí ✓"
  }
}
```

---

## 🔍 CAMPOS CONSULTABLES EN COTIZACIÓN POR LLAMADA

### Categoría: Información de Ruta (6 campos)
- ✅ Ciudad origen / destino
- ✅ Código DANE origen / destino
- ✅ Ruta completa
- ✅ Frecuencia

### Categoría: Información de Vehículo (5 campos)
- ✅ Tipo de vehículo requerido
- ✅ Tipo de carrocería
- ✅ Cantidad de vehículos
- ✅ Esquema de seguridad
- ✅ Cantidad de ejes

### Categoría: Información de Mercancía (10 campos)
- ✅ Peso
- ✅ Cantidad
- ✅ Tipo de producto
- ✅ Tipo de mercancía
- ✅ Temperatura requerida
- ✅ Humedad requerida
- ✅ Tipo de embalaje
- ✅ Dimensiones exactas
- ✅ Valor declarado
- ✅ Necesidad de registro fotográfico

### Categoría: Información de Precios (5 campos)
- ✅ Valor total
- ✅ Valor declarado para seguros
- ✅ Costo de flete
- ✅ Costo de seguro
- ✅ Porcentaje aplicado

### Categoría: Documentación y Aduanas (7 campos)
- ✅ Registro fotográfico
- ✅ Planos de carga
- ✅ Número de documento BL
- ✅ Régimen aduanal
- ✅ Agente aduanal
- ✅ Tipo de consolidación
- ✅ Tipo FCL/LCL

### Categoría: Información Adicional (6 campos)
- ✅ Fecha/hora de descargue/cargue
- ✅ Ventanas horarias disponibles
- ✅ Sitio de devolución de contenedor
- ✅ Status en SiloGtran
- ✅ ID del grupo de cotización
- ✅ ID de pricing asociado

### Categoría: Validación y Estado (3 campos)
- ✅ Estado actual en sistema
- ✅ Conformidad con grupo
- ✅ Integración con SiloGtran

---

## 🎬 ENDPOINTS MCP EN SERVIDOR

### WebSocket Endpoint
```
WS://localhost:8000/ws
```

Protocolo: JSON-RPC 2.0 compatible con ElevenLabs

### HTTP Endpoints (Alternativo)
```
POST /mcp/tools (listar herramientas)
POST /mcp/execute (ejecutar herramienta)
GET /mcp/llamadas (ver llamadas)
GET /mcp/cotizaciones (ver cotizaciones)
```

---

## ⚠️ LIMITACIONES Y CONSIDERACIONES

| Limitación | Impacto | Recomendación |
|-----------|--------|--------------|
| Pool máximo 10 conexiones | Podría ser cuello de botella en picos | Incrementar a 20 si hay errores |
| Timeout de conexión no especificado | Conexiones largas pueden cerrarse | Implementar heartbeat |
| Sin autenticación MCP | Cualquiera puede llamar | Implementar API key en producción |
| Búsqueda de teléfono por LIKE | Búsquedas lentas con muchos registros | Crear índice en BD |
| Sin caché de datos | Cada consulta va a BD | Implementar Redis |

---

## 📈 MEJORAS RECOMENDADAS

### Corto Plazo
1. **Indexación de BD**
   - `CREATE INDEX idx_llamadas_conversation ON llamadas(elevenlabs_conversation_id)`
   - `CREATE INDEX idx_cotizacion_grupo ON cotizacion_models(group_cotizations_id)`
   - `CREATE INDEX idx_vehiculo_telefono ON vehicle_owner_holder_driver(Telefonoconductor)`

2. **Caché en Redis**
   - Cachear cotizaciones por 1 hora
   - Cachear vehículos por 24 horas
   - Invalidar al actualizar

3. **Logging Mejorado**
   - Grabar todas las queries en archivo de log
   - Registrar tiempos de respuesta
   - Alertas si >500ms

### Mediano Plazo
1. **Búsquedas Avanzadas**
   - `search_cotizaciones_by_criteria` (ruta, peso, precio)
   - `get_available_vehicles` (filtrar por capacidad)
   - `validate_cotizacion_vehicle` (validar compatibilidad)

2. **Gestión de Estado**
   - `create_llamada_with_cotizacion` (crear atomáticamente)
   - `update_llamada_with_resultado` (actualizar con decisión)
   - `sync_silogtran_status` (sincronizar con sistema externo)

3. **Reporting**
   - `get_llamadas_estadisticas` (por período)
   - `get_tasa_aceptacion_conductores` (efectividad)
   - `get_utilidad_cotizaciones` (rentabilidad)

---

## 🎯 CONCLUSIÓN

El MCP de Conalca proporciona acceso completo a:
- **39 campos** de información en cada cotización
- **42 campos** de información de vehículos
- **Estados detallados** de llamadas
- **Información de precios** y grupos

Todos estos datos están disponibles **en tiempo real** durante una llamada de ElevenLabs, permitiendo que el agente de IA tome decisiones informadas sobre aceptación o rechazo de transportes.

