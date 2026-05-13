# Servidor MCP Conalca

Servidor MCP (Model Context Protocol) streameable para ElevenLabs que permite interactuar con la base de datos AI Transport de Conalca.

## Características

- ✅ Conexión asíncrona a MySQL usando aiomysql
- ✅ Integración con ElevenLabs para llamadas automatizadas
- ✅ Soporte para streaming en tiempo real
- ✅ Gestión de llamadas, cotizaciones y vehículos
- ✅ Herramientas MCP para CRUD operations
- ✅ Manejo de webhooks de ElevenLabs
- ✅ Pool de conexiones optimizado
- ✅ Logging estructurado

## Tablas Soportadas

### 1. Tabla `llamadas`
- Gestión de llamadas telefónicas
- Integración con ElevenLabs (conversation_id, sip_call_id)
- Estados de llamadas (pendiente, en_curso, completada, fallida)
- Notas y timestamps de llamadas

### 2. Tabla `cotizacion_models`
- Información detallada de cotizaciones de transporte
- Rutas origen-destino
- Datos de mercancía y vehículos requeridos
- Estados de SILOGTRAN

### 3. Tabla `vehicle_owner_holder_driver`
- Información completa de vehículos
- Datos de propietarios, poseedores y conductores
- Información de contacto (teléfonos)
- Datos técnicos del vehículo

## Instalación

### Requisitos
- Python 3.9+
- MySQL/MariaDB
- Variables de entorno configuradas en `.env`

### Instalación con pip
```bash
cd mcp-server
pip install -r requirements.txt
```

### Instalación con Poetry
```bash
cd mcp-server
poetry install
```

## Configuración

El servidor lee la configuración del archivo `.env` en la raíz del proyecto:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ai_transport
DB_USERNAME=biosanar_user
DB_PASSWORD=/6Tx0eXqFQONTFuoc7aqPicNlPhmuINU
```

## Uso

### Ejecutar el servidor
```bash
python -m conalca_mcp_server.server
```

### Ejecutar con Poetry
```bash
poetry run conalca-mcp-server
```

## Herramientas MCP Disponibles

### Llamadas
- `get_llamadas(limit, offset)` - Lista llamadas con paginación
- `get_llamada_by_id(id_llamada)` - Obtiene llamada específica
- `update_llamada_status(id_llamada, status, call_notes)` - Actualiza estado
- `create_llamada(id_cotizacion, chofer_id, status)` - Crea nueva llamada
- `stream_llamada_status(id_llamada)` - Stream de actualizaciones en tiempo real

### Cotizaciones
- `get_cotizaciones(limit, offset)` - Lista cotizaciones
- `get_cotizacion_by_id(id_cotizacion)` - Obtiene cotización específica
- `search_cotizaciones_by_ruta(ruta)` - Busca por ruta

### Vehículos
- `get_vehicles(limit, offset)` - Lista vehículos
- `get_vehicle_by_placa(placa)` - Busca por placa
- `search_vehicles_by_conductor(conductor_name)` - Busca por conductor
- `get_vehicle_by_telefono_conductor(telefono)` - Busca por teléfono

### ElevenLabs Integration
- `elevenlabs_webhook_handler(conversation_id, status, call_data)` - Maneja webhooks
- `get_driver_info_for_call(chofer_id)` - Info del conductor para llamadas

## Recursos MCP

- `conalca://llamadas` - Acceso a datos de llamadas
- `conalca://cotizaciones` - Acceso a datos de cotizaciones  
- `conalca://vehiculos` - Acceso a datos de vehículos

## Integración con ElevenLabs

El servidor incluye herramientas específicas para la integración con ElevenLabs:

1. **Webhooks**: Procesa callbacks de ElevenLabs para actualizar estados
2. **Streaming**: Notificaciones en tiempo real de cambios de estado
3. **Gestión de conversaciones**: Tracking de conversation_id y sip_call_id

### Ejemplo de uso con ElevenLabs

```python
# Crear una llamada
result = await create_llamada(id_cotizacion=59, chofer_id=965, status='pendiente')

# Iniciar streaming
await stream_llamada_status(id_llamada=35)

# Procesar webhook de ElevenLabs
await elevenlabs_webhook_handler(
    conversation_id='conv_5301k67bdbqzft0anh01xc2y1qp6',
    status='completed',
    call_data={'duration': 120, 'outcome': 'success'}
)
```

## Desarrollo

### Estructura del proyecto
```
mcp-server/
├── conalca_mcp_server/
│   ├── __init__.py
│   ├── server.py          # Servidor principal
│   ├── database.py        # Conexión a BD
│   ├── models.py          # Modelos de datos
│   └── tools.py           # Herramientas MCP
├── requirements.txt
├── pyproject.toml
└── README.md
```

### Logging

El servidor incluye logging estructurado que registra:
- Conexiones a la base de datos
- Ejecución de herramientas MCP
- Webhooks de ElevenLabs
- Errores y excepciones

### Rendimiento

- Pool de conexiones MySQL optimizado (1-10 conexiones)
- uvloop en sistemas Linux para mejor rendimiento async
- Streaming no bloqueante
- Paginación en consultas grandes

## Troubleshooting

### Error de conexión a MySQL
```
Error al inicializar la conexión MySQL: (2003, "Can't connect to MySQL server")
```
- Verificar que MySQL esté ejecutándose
- Comprobar credenciales en el archivo `.env`
- Verificar conectividad de red

### Error de permisos
```
Error al obtener llamadas: (1142, "SELECT command denied")
```
- Verificar permisos del usuario de base de datos
- Asegurar que el usuario tenga acceso a las tablas requeridas

## Contribución

1. Fork el proyecto
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## Licencia

Proyecto interno de Conalca - Todos los derechos reservados