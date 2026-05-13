# Análisis Detallado del Servidor MCP

## Ubicación y Estado

**Ruta**: `/home/ubuntu/conalca/conalca-mcp/mcp-server/`

**Estado del Servicio**: ✅ **ACTIVO Y FUNCIONANDO**
```bash
● conalca-mcp.service - Conalca MCP Server - ElevenLabs Compatible
     Active: active (running) since Wed 2026-01-07 17:40:52 UTC
     Main PID: 2052899 (uvicorn)
     Memory: 54.1M
```

## Arquitectura del Servidor

### Tecnología
- **Framework**: FastAPI + Uvicorn
- **Async**: uvloop (optimización para Linux)
- **Protocolo**: JSON-RPC 2.0 (MCP Compatible)
- **Base de Datos**: MySQL/MariaDB con aiomysql (async)
- **Puerto**: 18840 (interno)
- **Host**: 127.0.0.1 (solo acceso local)

### Exposición Pública
- **URL Pública**: https://conalcaia.conalca.com.co/mcp/
- **Proxy**: Nginx con certificado SSL (Let's Encrypt)
- **WebSocket**: wss://conalcaia.conalca.com.co/ws

## Estructura del Proyecto

```
conalca-mcp/mcp-server/
├── conalca_mcp_server/           # Módulo Python principal
│   ├── __init__.py
│   ├── database.py               # Conexión async a MySQL
│   ├── models.py                 # ORM/Modelos de datos
│   ├── server_mcp_compliant.py  # Servidor principal (116KB)
│   ├── server.py                 # Servidor alternativo (153KB)
│   ├── tools.py                  # Herramientas MCP (66KB)
│   └── __pycache__/
├── logs/                         # Logs del servicio
│   ├── mcp_server.log
│   └── mcp_server_error.log
├── venv/                         # Entorno virtual Python
├── .env                          # Configuración DB
├── conalca-mcp.service          # Servicio systemd
├── requirements.txt
├── pyproject.toml
└── README.md
```

## Configuración

### Variables de Entorno (.env)
```env
DB_CONNECTION=mysql
DB_HOST=ai-transport.czqmsk4ck839.us-west-1.rds.amazonaws.com
DB_PORT=3306
DB_DATABASE=conalca
DB_USERNAME=admin
DB_PASSWORD=1Dy81fsrX0htEBWodTJ9

MCP_SERVER_PORT=18840
MCP_SERVER_HOST=127.0.0.1
```

### Servicio Systemd
**Archivo**: `/etc/systemd/system/conalca-mcp.service`

```ini
[Unit]
Description=Conalca MCP Server - ElevenLabs Compatible
After=network.target mysql.service
Wants=mysql.service

[Service]
Type=simple
User=ubuntu
Group=ubuntu
WorkingDirectory=/home/ubuntu/conalca/conalca-mcp/mcp-server
Environment="PATH=/home/ubuntu/conalca/conalca-mcp/mcp-server/venv/bin:/usr/local/bin:/usr/bin:/bin"
ExecStart=/home/ubuntu/conalca/conalca-mcp/mcp-server/venv/bin/uvicorn conalca_mcp_server.server_mcp_compliant:app --host 127.0.0.1 --port 18840
Restart=always
RestartSec=10
StandardOutput=append:/home/ubuntu/conalca/conalca-mcp/mcp-server/logs/mcp_server.log
StandardError=append:/home/ubuntu/conalca/conalca-mcp/mcp-server/logs/mcp_server_error.log

[Install]
WantedBy=multi-user.target
```

## Protocolo JSON-RPC 2.0

### Endpoint Principal
**URL**: `POST https://conalcaia.conalca.com.co/mcp/`

### Métodos Soportados

#### 1. initialize
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "initialize",
  "params": {}
}
```

**Respuesta**:
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "result": {
    "protocolVersion": "2024-11-05",
    "capabilities": {
      "tools": { "listChanged": true },
      "resources": { "subscribe": true }
    },
    "serverInfo": {
      "name": "conalca-mcp-server",
      "version": "2.1.0"
    }
  }
}
```

#### 2. tools/list
Lista todas las herramientas MCP disponibles.

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "tools/list",
  "params": {}
}
```

**Respuesta**: Array de 19 herramientas con schemas completos

#### 3. tools/call
Ejecuta una herramienta específica.

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "tools/call",
  "params": {
    "name": "get_cotizaciones",
    "arguments": {
      "limit": 10
    }
  }
}
```

**Respuesta**:
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "result": {
    "content": [
      {
        "type": "text",
        "text": "{\"success\": true, \"cotizaciones\": [...]}"
      }
    ]
  }
}
```

#### 4. resources/list
Lista recursos disponibles del servidor.

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "resources/list",
  "params": {}
}
```

## Herramientas MCP Disponibles (19 Total)

### Llamadas (4 herramientas)
1. **get_llamadas** - Listar llamadas con paginación
2. **get_llamada_by_id** - Obtener llamada específica
3. **create_llamada** - Crear nueva llamada
4. **update_llamada_status** - Actualizar estado de llamada

### Cotizaciones (6 herramientas)
5. **get_cotizaciones** - Listar cotizaciones
6. **get_cotizacion_by_id** - Obtener cotización por ID
7. **create_cotizacion** - Crear cotización (⭐ PRINCIPAL)
8. **update_cotizacion** - Actualizar cotización
9. **delete_cotizacion** - Eliminar cotización
10. **search_cotizaciones** - Buscar cotizaciones

### Productos (3 herramientas)
11. **search_products** - Buscar productos
12. **get_product_by_code** - Obtener producto por código
13. **get_products_by_category** - Listar productos por categoría

### Vehículos (2 herramientas)
14. **get_vehicle_by_telefono_conductor** - Buscar vehículo por teléfono
15. **get_chofer_by_placa** - Buscar chofer por placa

### Empaques (3 herramientas)
16. **get_empaques** - Listar empaques
17. **get_empaque_by_id** - Obtener empaque por ID
18. **search_empaques** - Buscar empaques

### ElevenLabs (1 herramienta)
19. **process_elevenlabs_event** - Procesar eventos de ElevenLabs

## Herramienta create_cotizacion - Schema Completo

```json
{
  "name": "create_cotizacion",
  "description": "Crea una nueva cotización con todos los campos disponibles",
  "inputSchema": {
    "type": "object",
    "properties": {
      "pricing_id": {"type": "integer"},
      "ciudad_origen": {"type": "string"},
      "ciudad_destino": {"type": "string"},
      "peso_mercancia": {"type": "string"},
      "cantidad": {"type": "string"},
      "tipo_embajale": {"type": "string"},
      "tipo_producto": {"type": "string"},
      "vehiculo_requerido": {"type": "string"},
      "fecha_hora_descargue_cargue": {"type": "string"},
      "ruta": {"type": "string"},
      "valor": {"type": "string"},
      "valor_declarado": {"type": "string"},
      "tipo_mercancia": {"type": "string"},
      "group_cotizations_id": {"type": "integer"},
      "porcentaje": {"type": "string"},
      "ciudad_origen_dane": {"type": "string"},
      "ciudad_destino_dane": {"type": "string"},
      "dimensiones_exactas": {"type": "string"},
      "registro_fotografico": {"type": "string"},
      "planos": {"type": "string"},
      "temperatura_mercancia": {"type": "string"},
      "humedad": {"type": "string"},
      "regimen_nacionalizado": {"type": "string"},
      "agente_aduanas": {"type": "string"},
      "descargue_cargue": {"type": "string"},
      "consolidado_expreso": {"type": "string"},
      "fcl_lcl": {"type": "string"},
      "sitio_devolucion_contenedor": {"type": "string"},
      "numero_documento_bl": {"type": "string"},
      "cantidad_vh": {"type": "string"},
      "un": {"type": "string"},
      "frecuencia": {"type": "string"},
      "esquema_seguridad": {"type": "string"},
      "tipo_carroceria": {"type": "string"},
      "ventanas_horarios_recibidos": {"type": "string"},
      "seguro": {"type": "string"},
      "silogtran_status": {"type": "string"}
    },
    "required": ["pricing_id"]
  }
}
```

## Recursos MCP

El servidor expone 3 recursos principales:

```json
{
  "resources": [
    {
      "uri": "conalca://database/llamadas",
      "name": "Base de Datos de Llamadas",
      "description": "Acceso completo a la tabla de llamadas telefónicas con funciones CRUD",
      "mimeType": "application/json"
    },
    {
      "uri": "conalca://database/cotizaciones",
      "name": "Base de Datos de Cotizaciones",
      "description": "Catálogo de cotizaciones de vehículos con precios y especificaciones",
      "mimeType": "application/json"
    },
    {
      "uri": "conalca://database/vehicles",
      "name": "Base de Datos de Vehículos",
      "description": "Registro completo de vehículos, conductores y propietarios",
      "mimeType": "application/json"
    }
  ]
}
```

## Logs y Monitoreo

### Logs en Tiempo Real
```bash
# Ver logs del servidor
tail -f /home/ubuntu/conalca/conalca-mcp/mcp-server/logs/mcp_server.log

# Ver logs de errores
tail -f /home/ubuntu/conalca/conalca-mcp/mcp-server/logs/mcp_server_error.log

# Ver logs del servicio systemd
journalctl -u conalca-mcp.service -f
```

### Ejemplo de Logs Recientes
```
INFO:     13.56.4.123:0 - "POST /mcp/ HTTP/1.1" 200 OK
INFO:     13.56.4.123:0 - "GET /mcp/ HTTP/1.1" 200 OK
INFO:     34.59.11.47:0 - "POST /mcp/ HTTP/1.1" 200 OK
```

## Gestión del Servicio

```bash
# Ver estado
sudo systemctl status conalca-mcp.service

# Reiniciar
sudo systemctl restart conalca-mcp.service

# Detener
sudo systemctl stop conalca-mcp.service

# Iniciar
sudo systemctl start conalca-mcp.service

# Ver logs en tiempo real
sudo journalctl -u conalca-mcp.service -f
```

## Pruebas del Servidor

### Test 1: Listar Herramientas
```bash
curl -X POST https://conalcaia.conalca.com.co/mcp/ \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list","params":{}}'
```

### Test 2: Llamar create_cotizacion
```bash
curl -X POST https://conalcaia.conalca.com.co/mcp/ \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc":"2.0",
    "id":1,
    "method":"tools/call",
    "params":{
      "name":"create_cotizacion",
      "arguments":{
        "pricing_id": 1,
        "ciudad_origen": "Bogotá",
        "ciudad_destino": "Medellín",
        "peso_mercancia": "1000",
        "tipo_producto": "Carga general",
        "vehiculo_requerido": "Turbo"
      }
    }
  }'
```

### Test 3: Buscar Productos
```bash
curl -X POST https://conalcaia.conalca.com.co/mcp/ \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc":"2.0",
    "id":1,
    "method":"tools/call",
    "params":{
      "name":"search_products",
      "arguments":{
        "query": "caja",
        "limit": 5
      }
    }
  }'
```

### Test 4: Obtener Empaques
```bash
curl -X POST https://conalcaia.conalca.com.co/mcp/ \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc":"2.0",
    "id":1,
    "method":"tools/call",
    "params":{
      "name":"get_empaques",
      "arguments":{
        "limit": 10
      }
    }
  }'
```

## Integración con Laravel (MCPAssistantService)

### Endpoint Correcto para Tool Calling

❌ **INCORRECTO**: 
```
https://conalcaia.conalca.com.co/mcp/tools/call
```

✅ **CORRECTO**:
```
https://conalcaia.conalca.com.co/mcp/
```

### Payload JSON-RPC para Tools
```php
$payload = [
    'jsonrpc' => '2.0',
    'id' => uniqid(),
    'method' => 'tools/call',
    'params' => [
        'name' => $toolName,      // e.g., 'create_cotizacion'
        'arguments' => $arguments  // e.g., ['ciudad_origen' => 'Bogotá', ...]
    ]
];
```

### Actualización Necesaria en MCPAssistantService

**Archivo**: `/home/ubuntu/conalca/conalca/app/Services/MCPAssistantService.php`

**Línea 306**: Cambiar
```php
->post(self::$mcp_base_url . 'tools/call', [
```

Por:
```php
->post(self::$mcp_base_url, [
```

**Y el payload debe ser**:
```php
$response = Http::timeout(30)
    ->withHeaders([
        'Content-Type' => 'application/json',
    ])
    ->post(self::$mcp_base_url, [
        'jsonrpc' => '2.0',
        'id' => uniqid(),
        'method' => 'tools/call',
        'params' => [
            'name' => $toolName,
            'arguments' => $arguments
        ]
    ]);
```

## Ventajas del Servidor MCP

1. **Protocolo Estándar**: JSON-RPC 2.0 compatible con múltiples clientes
2. **Performance**: uvloop + async/await para alta concurrencia
3. **Seguridad**: SSL/TLS + autenticación DB
4. **Escalabilidad**: Pool de conexiones optimizado
5. **Monitoreo**: Logging estructurado y systemd integration
6. **Flexibilidad**: 19 herramientas listas para usar
7. **Documentación**: Schemas completos para cada herramienta

## Documentación Disponible

El servidor incluye extensa documentación:

- `README.md` - Guía de instalación y uso
- `ANALISIS_TECNICO_20_HERRAMIENTAS_MCP.md` - Análisis técnico completo
- `DOCUMENTACION_COMPLETA_HERRAMIENTAS_MCP.md` - Docs de cada herramienta
- `LISTADO_COMPLETO_HERRAMIENTAS_MCP.md` - Listado con ejemplos
- `RESUMEN_CONFIGURACION_MCP.md` - Configuración del servidor
- `CONFIGURACION_NUEVA_BD_CONDUCTORES.md` - Schema de BD

## Próximos Pasos para Integración

1. ✅ Servidor MCP funcionando correctamente
2. ✅ 19 herramientas disponibles y probadas
3. ⏳ Actualizar MCPAssistantService con endpoint correcto
4. ⏳ Probar tool calling desde Laravel
5. ⏳ Validar creación de cotizaciones end-to-end
6. ⏳ Monitorear logs durante pruebas

---

**Análisis Generado**: 7 de enero de 2026
**Servidor**: Ubuntu 22.04 LTS
**Python**: 3.x + uvicorn + FastAPI
**Estado**: ✅ Operacional
