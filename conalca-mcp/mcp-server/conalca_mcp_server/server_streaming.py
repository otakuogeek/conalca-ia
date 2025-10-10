import asyncio
import logging
import sys
from typing import Any, Dict, List, Optional, Set
import uvloop
import json
from datetime import datetime, date
from fastapi import FastAPI, Request, HTTPException, WebSocket, WebSocketDisconnect
from fastapi.responses import JSONResponse, StreamingResponse
from fastapi.middleware.cors import CORSMiddleware
from sse_starlette.sse import EventSourceResponse
import uvicorn

# Custom JSON encoder para manejar fechas
class DateTimeEncoder(json.JSONEncoder):
    def default(self, obj):
        if isinstance(obj, (datetime, date)):
            return obj.isoformat()
        return super().default(obj)

from mcp.server import Server
from mcp.server.stdio import stdio_server
from mcp.types import Resource, Tool, TextContent

from .database import db_connection
from .models import repository

# Configuración de logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

class ConalcaMCPServer:
    """Servidor MCP principal para Conalca con capacidades de streaming completas"""
    
    def __init__(self, root_path: str = ""):
        self.server = Server("conalca-mcp-server")
        self.streaming_clients: Set[WebSocket] = set()
        self.sse_clients: Set = set()
        self.root_path = root_path
        
        # Crear FastAPI app para webhooks y endpoints HTTP
        self.app = FastAPI(
            title="Conalca MCP Server - Streameable",
            description="Servidor MCP streameable para ElevenLabs con WebSocket, SSE y streaming HTTP",
            version="2.0.0",
            root_path=root_path
        )
        
        # Configurar CORS para streaming
        self.app.add_middleware(
            CORSMiddleware,
            allow_origins=["*"],
            allow_credentials=True,
            allow_methods=["*"],
            allow_headers=["*"],
            expose_headers=["*"]
        )
        
        # Registrar endpoints HTTP y streaming
        self._register_http_endpoints()
        self._register_streaming_endpoints()
        
    async def initialize(self):
        """Inicializa el servidor y la conexión a la base de datos"""
        try:
            # Inicializar conexión a la base de datos
            await db_connection.initialize()
            logger.info("Conexión a base de datos inicializada")
            
            # Registrar herramientas MCP
            self._register_tools()
            logger.info("Herramientas MCP registradas")
            
            # Registrar recursos y capacidades de streaming
            self._register_resources()
            
        except Exception as e:
            logger.error(f"Error al inicializar el servidor: {e}")
            raise
    
    def _register_http_endpoints(self):
        """Registra endpoints HTTP para webhooks y health checks"""
        
        @self.app.get("/health")
        async def health_check():
            """Health check endpoint"""
            try:
                # Verificar conexión a base de datos
                await db_connection.execute_query("SELECT 1")
                return {
                    "status": "healthy",
                    "timestamp": datetime.now().isoformat(),
                    "database": "connected",
                    "version": "2.0.0",
                    "streaming": {
                        "websocket_clients": len(self.streaming_clients),
                        "sse_clients": len(self.sse_clients),
                        "capabilities": ["websocket", "sse", "http_streaming"]
                    }
                }
            except Exception as e:
                logger.error(f"Health check failed: {e}")
                raise HTTPException(status_code=503, detail="Service unhealthy")
        
        @self.app.post("/webhook/elevenlabs")
        async def elevenlabs_webhook(request: Request):
            """Maneja webhooks de ElevenLabs con capacidades de streaming"""
            try:
                data = await request.json()
                logger.info(f"Webhook recibido de ElevenLabs: {data}")
                
                # Procesar el webhook según el tipo de evento
                event_type = data.get("event_type", "unknown")
                
                # Streaming: notificar a todos los clientes conectados
                await self._broadcast_to_clients({
                    "type": "elevenlabs_webhook",
                    "event_type": event_type,
                    "data": data,
                    "timestamp": datetime.now().isoformat()
                })
                
                if event_type == "conversation_started":
                    result = await self._handle_conversation_started(data)
                elif event_type == "conversation_ended":
                    result = await self._handle_conversation_ended(data)
                elif event_type == "call_status_change":
                    result = await self._handle_call_status_change(data)
                else:
                    result = f"Evento procesado: {event_type}"
                
                return {"status": "success", "processed": True, "result": result}
                
            except Exception as e:
                logger.error(f"Error procesando webhook de ElevenLabs: {e}")
                raise HTTPException(status_code=500, detail=str(e))
        
        @self.app.get("/")
        async def root():
            """Endpoint raíz - Respuesta inmediata para MCP"""
            return {
                "jsonrpc": "2.0",
                "result": {
                    "protocolVersion": "2024-11-05",
                    "capabilities": {
                        "tools": {
                            "listChanged": True
                        },
                        "resources": {
                            "subscribe": True,
                            "listChanged": True
                        }
                    },
                    "serverInfo": {
                        "name": "conalca-mcp-server",
                        "version": "2.0.0"
                    },
                    "tools": [
                        {
                            "name": "get_llamadas",
                            "description": "Obtiene todas las llamadas con paginación opcional",
                            "inputSchema": {
                                "type": "object",
                                "properties": {
                                    "page": {"type": "integer", "description": "Número de página", "default": 1},
                                    "limit": {"type": "integer", "description": "Límite por página", "default": 20}
                                }
                            }
                        },
                        {
                            "name": "get_llamada_by_id",
                            "description": "Obtiene una llamada específica por ID",
                            "inputSchema": {
                                "type": "object",
                                "properties": {
                                    "llamada_id": {"type": "integer", "description": "ID de la llamada"}
                                },
                                "required": ["llamada_id"]
                            }
                        },
                        {
                            "name": "create_llamada",
                            "description": "Crea una nueva llamada telefónica",
                            "inputSchema": {
                                "type": "object",
                                "properties": {
                                    "telefono": {"type": "string", "description": "Número de teléfono"},
                                    "estado": {"type": "string", "description": "Estado inicial", "default": "pendiente"},
                                    "observaciones": {"type": "string", "description": "Observaciones adicionales"}
                                },
                                "required": ["telefono"]
                            }
                        },
                        {
                            "name": "update_llamada_status",
                            "description": "Actualiza el estado de una llamada",
                            "inputSchema": {
                                "type": "object",
                                "properties": {
                                    "llamada_id": {"type": "integer", "description": "ID de la llamada"},
                                    "new_status": {"type": "string", "description": "Nuevo estado"}
                                },
                                "required": ["llamada_id", "new_status"]
                            }
                        },
                        {
                            "name": "get_cotizaciones",
                            "description": "Obtiene cotizaciones de vehículos con filtros",
                            "inputSchema": {
                                "type": "object",
                                "properties": {
                                    "page": {"type": "integer", "description": "Número de página", "default": 1},
                                    "limit": {"type": "integer", "description": "Límite por página", "default": 20}
                                }
                            }
                        },
                        {
                            "name": "get_vehicle_by_telefono_conductor",
                            "description": "Busca vehículos por teléfono del conductor",
                            "inputSchema": {
                                "type": "object",
                                "properties": {
                                    "telefono": {"type": "string", "description": "Teléfono del conductor"}
                                },
                                "required": ["telefono"]
                            }
                        },
                        {
                            "name": "elevenlabs_webhook_handler",
                            "description": "Maneja eventos de webhooks de ElevenLabs",
                            "inputSchema": {
                                "type": "object",
                                "properties": {
                                    "event_data": {
                                        "type": "object", 
                                        "description": "Datos del evento de ElevenLabs"
                                    }
                                },
                                "required": ["event_data"]
                            }
                        }
                    ]
                }
            }
        
        @self.app.get("/mcp")
        async def mcp_info():
            """Endpoint específico para información MCP"""
            try:
                # Obtener información de las herramientas disponibles
                tools_info = []
                
                # Información de herramientas disponibles
                available_tools = [
                    {
                        "name": "get_llamadas",
                        "description": "Obtiene todas las llamadas con paginación opcional",
                        "parameters": {
                            "page": {"type": "integer", "description": "Número de página", "default": 1},
                            "limit": {"type": "integer", "description": "Límite por página", "default": 20},
                            "status": {"type": "string", "description": "Filtrar por estado", "optional": True}
                        }
                    },
                    {
                        "name": "get_llamada_by_id", 
                        "description": "Obtiene una llamada específica por ID",
                        "parameters": {
                            "llamada_id": {"type": "integer", "description": "ID de la llamada"}
                        }
                    },
                    {
                        "name": "create_llamada",
                        "description": "Crea una nueva llamada",
                        "parameters": {
                            "telefono": {"type": "string", "description": "Número de teléfono"},
                            "estado": {"type": "string", "description": "Estado inicial", "default": "pendiente"},
                            "observaciones": {"type": "string", "description": "Observaciones", "optional": True}
                        }
                    },
                    {
                        "name": "update_llamada_status",
                        "description": "Actualiza el estado de una llamada",
                        "parameters": {
                            "llamada_id": {"type": "integer", "description": "ID de la llamada"},
                            "new_status": {"type": "string", "description": "Nuevo estado"}
                        }
                    },
                    {
                        "name": "get_cotizaciones",
                        "description": "Obtiene cotizaciones con filtros opcionales",
                        "parameters": {
                            "page": {"type": "integer", "description": "Número de página", "default": 1},
                            "limit": {"type": "integer", "description": "Límite por página", "default": 20},
                            "status": {"type": "string", "description": "Filtrar por estado", "optional": True}
                        }
                    },
                    {
                        "name": "get_vehicle_by_telefono_conductor",
                        "description": "Busca vehículos por teléfono del conductor",
                        "parameters": {
                            "telefono": {"type": "string", "description": "Teléfono del conductor"}
                        }
                    },
                    {
                        "name": "elevenlabs_webhook_handler",
                        "description": "Maneja eventos de webhooks de ElevenLabs",
                        "parameters": {
                            "event_data": {"type": "object", "description": "Datos del evento de ElevenLabs"}
                        }
                    }
                ]
                
                return {
                    "protocol": "mcp",
                    "version": "1.0",
                    "server": {
                        "name": "conalca-mcp-server",
                        "version": "2.0.0"
                    },
                    "capabilities": {
                        "tools": True,
                        "resources": True,
                        "streaming": True
                    },
                    "tools": available_tools,
                    "resources": [
                        {
                            "uri": "conalca://database/llamadas",
                            "name": "Llamadas Database",
                            "description": "Acceso a la tabla de llamadas telefónicas",
                            "mimeType": "application/json"
                        },
                        {
                            "uri": "conalca://database/cotizaciones", 
                            "name": "Cotizaciones Database",
                            "description": "Acceso a la tabla de cotizaciones",
                            "mimeType": "application/json"
                        },
                        {
                            "uri": "conalca://database/vehicles",
                            "name": "Vehicles Database", 
                            "description": "Acceso a la tabla de vehículos",
                            "mimeType": "application/json"
                        }
                    ],
                    "streaming": {
                        "websocket_clients": len(self.streaming_clients),
                        "sse_clients": len(self.sse_clients),
                        "capabilities": ["websocket", "sse", "http_streaming"]
                    }
                }
            except Exception as e:
                logger.error(f"Error en endpoint MCP info: {e}")
                raise HTTPException(status_code=500, detail=str(e))
        
        @self.app.get("/tools")
        async def list_tools():
            """Endpoint para listar herramientas MCP"""
            return {
                "tools": [
                    {
                        "name": "get_llamadas",
                        "description": "Obtiene todas las llamadas con paginación opcional",
                        "inputSchema": {
                            "type": "object",
                            "properties": {
                                "page": {"type": "integer", "description": "Número de página", "default": 1},
                                "limit": {"type": "integer", "description": "Límite por página", "default": 20},
                                "status": {"type": "string", "description": "Filtrar por estado"}
                            }
                        }
                    },
                    {
                        "name": "get_llamada_by_id",
                        "description": "Obtiene una llamada específica por ID",
                        "inputSchema": {
                            "type": "object",
                            "properties": {
                                "llamada_id": {"type": "integer", "description": "ID de la llamada"}
                            },
                            "required": ["llamada_id"]
                        }
                    },
                    {
                        "name": "create_llamada",
                        "description": "Crea una nueva llamada",
                        "inputSchema": {
                            "type": "object",
                            "properties": {
                                "telefono": {"type": "string", "description": "Número de teléfono"},
                                "estado": {"type": "string", "description": "Estado inicial", "default": "pendiente"},
                                "observaciones": {"type": "string", "description": "Observaciones adicionales"}
                            },
                            "required": ["telefono"]
                        }
                    },
                    {
                        "name": "update_llamada_status",
                        "description": "Actualiza el estado de una llamada",
                        "inputSchema": {
                            "type": "object",
                            "properties": {
                                "llamada_id": {"type": "integer", "description": "ID de la llamada"},
                                "new_status": {"type": "string", "description": "Nuevo estado"}
                            },
                            "required": ["llamada_id", "new_status"]
                        }
                    },
                    {
                        "name": "get_cotizaciones",
                        "description": "Obtiene cotizaciones con filtros opcionales",
                        "inputSchema": {
                            "type": "object",
                            "properties": {
                                "page": {"type": "integer", "description": "Número de página", "default": 1},
                                "limit": {"type": "integer", "description": "Límite por página", "default": 20},
                                "status": {"type": "string", "description": "Filtrar por estado"}
                            }
                        }
                    },
                    {
                        "name": "get_vehicle_by_telefono_conductor",
                        "description": "Busca vehículos por teléfono del conductor",
                        "inputSchema": {
                            "type": "object",
                            "properties": {
                                "telefono": {"type": "string", "description": "Teléfono del conductor"}
                            },
                            "required": ["telefono"]
                        }
                    },
                    {
                        "name": "elevenlabs_webhook_handler",
                        "description": "Maneja eventos de webhooks de ElevenLabs con streaming",
                        "inputSchema": {
                            "type": "object",
                            "properties": {
                                "event_data": {
                                    "type": "object", 
                                    "description": "Datos del evento de ElevenLabs",
                                    "properties": {
                                        "event_type": {"type": "string"},
                                        "conversation_id": {"type": "string"},
                                        "phone_number": {"type": "string"}
                                    }
                                }
                            },
                            "required": ["event_data"]
                        }
                    }
                ]
            }
        
        @self.app.post("/tools/{tool_name}")
        async def call_tool(tool_name: str, request: Request):
            """Endpoint para ejecutar herramientas MCP"""
            try:
                data = await request.json()
                arguments = data.get("arguments", {})
                
                # Mapear las herramientas a sus implementaciones
                if tool_name == "get_llamadas":
                    page = arguments.get("page", 1)
                    limit = arguments.get("limit", 20)
                    # Convertir page a offset
                    offset = (page - 1) * limit
                    llamadas = await repository.get_llamadas(limit=limit, offset=offset)
                    
                    # Notificar a clientes streaming
                    await self._broadcast_to_clients({
                        "type": "tool_execution",
                        "tool": "get_llamadas",
                        "params": arguments,
                        "result_count": len(llamadas),
                        "timestamp": datetime.now().isoformat()
                    })
                    
                    return {
                        "content": [
                            {
                                "type": "text",
                                "text": json.dumps({
                                    "total": len(llamadas),
                                    "page": page,
                                    "limit": limit,
                                    "llamadas": [llamada.model_dump() for llamada in llamadas]
                                }, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
                            }
                        ]
                    }
                
                elif tool_name == "get_llamada_by_id":
                    llamada_id = arguments.get("llamada_id")
                    if not llamada_id:
                        raise HTTPException(status_code=400, detail="llamada_id es requerido")
                    
                    llamada = await repository.get_llamada_by_id(llamada_id)
                    
                    # Notificar a clientes streaming
                    await self._broadcast_to_clients({
                        "type": "tool_execution",
                        "tool": "get_llamada_by_id",
                        "params": arguments,
                        "found": llamada is not None,
                        "timestamp": datetime.now().isoformat()
                    })
                    
                    if llamada:
                        return {
                            "content": [
                                {
                                    "type": "text",
                                    "text": json.dumps(llamada.model_dump(), indent=2, ensure_ascii=False, cls=DateTimeEncoder)
                                }
                            ]
                        }
                    else:
                        return {
                            "content": [
                                {
                                    "type": "text",
                                    "text": f"No se encontró la llamada con ID: {llamada_id}"
                                }
                            ]
                        }
                
                elif tool_name == "create_llamada":
                    telefono = arguments.get("telefono")
                    if not telefono:
                        raise HTTPException(status_code=400, detail="telefono es requerido")
                    
                    estado = arguments.get("estado", "pendiente")
                    observaciones = arguments.get("observaciones")
                    
                    llamada_id = await repository.create_llamada(telefono, estado, observaciones)
                    
                    # Notificar a clientes streaming
                    await self._broadcast_to_clients({
                        "type": "data_create",
                        "action": "llamada_created",
                        "llamada_id": llamada_id,
                        "telefono": telefono,
                        "estado": estado,
                        "timestamp": datetime.now().isoformat()
                    })
                    
                    return {
                        "content": [
                            {
                                "type": "text",
                                "text": f"Nueva llamada creada con ID: {llamada_id}"
                            }
                        ]
                    }
                
                elif tool_name == "update_llamada_status":
                    llamada_id = arguments.get("llamada_id")
                    new_status = arguments.get("new_status")
                    
                    if not llamada_id or not new_status:
                        raise HTTPException(status_code=400, detail="llamada_id y new_status son requeridos")
                    
                    success = await repository.update_llamada_status(llamada_id, new_status)
                    
                    # Notificar a clientes streaming
                    await self._broadcast_to_clients({
                        "type": "data_update",
                        "action": "status_updated",
                        "llamada_id": llamada_id,
                        "new_status": new_status,
                        "success": success,
                        "timestamp": datetime.now().isoformat()
                    })
                    
                    result_text = f"Estado de llamada {llamada_id} actualizado a: {new_status}" if success else f"No se pudo actualizar la llamada {llamada_id}"
                    
                    return {
                        "content": [
                            {
                                "type": "text",
                                "text": result_text
                            }
                        ]
                    }
                
                elif tool_name == "get_cotizaciones":
                    page = arguments.get("page", 1)
                    limit = arguments.get("limit", 20)
                    # Convertir page a offset
                    offset = (page - 1) * limit
                    
                    cotizaciones = await repository.get_cotizaciones(limit=limit, offset=offset)
                    
                    # Notificar a clientes streaming
                    await self._broadcast_to_clients({
                        "type": "tool_execution",
                        "tool": "get_cotizaciones",
                        "params": arguments,
                        "result_count": len(cotizaciones),
                        "timestamp": datetime.now().isoformat()
                    })
                    
                    return {
                        "content": [
                            {
                                "type": "text",
                                "text": json.dumps({
                                    "total": len(cotizaciones),
                                    "page": page,
                                    "limit": limit,
                                    "cotizaciones": [cotizacion.model_dump() for cotizacion in cotizaciones]
                                }, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
                            }
                        ]
                    }
                
                elif tool_name == "get_vehicle_by_telefono_conductor":
                    telefono = arguments.get("telefono")
                    if not telefono:
                        raise HTTPException(status_code=400, detail="telefono es requerido")
                    
                    vehicles = await repository.get_vehicle_by_telefono_conductor(telefono)
                    
                    # Notificar a clientes streaming
                    await self._broadcast_to_clients({
                        "type": "tool_execution",
                        "tool": "get_vehicle_by_telefono_conductor",
                        "params": arguments,
                        "result_count": len(vehicles),
                        "timestamp": datetime.now().isoformat()
                    })
                    
                    return {
                        "content": [
                            {
                                "type": "text",
                                "text": json.dumps({
                                    "total": len(vehicles),
                                    "telefono_buscado": telefono,
                                    "vehiculos": [vehicle.model_dump() for vehicle in vehicles]
                                }, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
                            }
                        ]
                    }
                
                elif tool_name == "elevenlabs_webhook_handler":
                    event_data = arguments.get("event_data")
                    if not event_data:
                        raise HTTPException(status_code=400, detail="event_data es requerido")
                    
                    event_type = event_data.get("event_type", "unknown")
                    
                    # Procesar según el tipo de evento
                    if event_type == "conversation_started":
                        result = await self._handle_conversation_started(event_data)
                    elif event_type == "conversation_ended":
                        result = await self._handle_conversation_ended(event_data)
                    elif event_type == "call_status_change":
                        result = await self._handle_call_status_change(event_data)
                    else:
                        result = f"Evento no reconocido: {event_type}"
                    
                    # Notificar a clientes streaming
                    await self._broadcast_to_clients({
                        "type": "elevenlabs_event",
                        "event_type": event_type,
                        "data": event_data,
                        "timestamp": datetime.now().isoformat()
                    })
                    
                    return {
                        "content": [
                            {
                                "type": "text",
                                "text": json.dumps({
                                    "event_type": event_type,
                                    "processed": True,
                                    "result": result,
                                    "timestamp": datetime.now().isoformat()
                                }, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
                            }
                        ]
                    }
                
                else:
                    raise HTTPException(status_code=404, detail=f"Herramienta '{tool_name}' no encontrada")
                
            except Exception as e:
                logger.error(f"Error ejecutando herramienta {tool_name}: {e}")
                raise HTTPException(status_code=500, detail=str(e))
    
    def _register_streaming_endpoints(self):
        """Registra endpoints de streaming: WebSocket, SSE y HTTP Streaming"""
        
        @self.app.websocket("/ws")
        async def websocket_endpoint(websocket: WebSocket):
            """Endpoint WebSocket para streaming en tiempo real"""
            await websocket.accept()
            self.streaming_clients.add(websocket)
            logger.info(f"Cliente WebSocket conectado. Total: {len(self.streaming_clients)}")
            
            try:
                # Enviar mensaje de bienvenida
                await websocket.send_json({
                    "type": "connection_established",
                    "message": "Conectado al servidor MCP streameable",
                    "timestamp": datetime.now().isoformat()
                })
                
                while True:
                    # Mantener la conexión viva y recibir mensajes
                    try:
                        data = await websocket.receive_json()
                        logger.info(f"Mensaje WebSocket recibido: {data}")
                        
                        # Echo del mensaje con timestamp
                        await websocket.send_json({
                            "type": "echo",
                            "original": data,
                            "timestamp": datetime.now().isoformat()
                        })
                    except WebSocketDisconnect:
                        break
                    
            except WebSocketDisconnect:
                pass
            finally:
                self.streaming_clients.discard(websocket)
                logger.info(f"Cliente WebSocket desconectado. Total: {len(self.streaming_clients)}")
        
        @self.app.get("/stream")
        async def sse_endpoint(request: Request):
            """Endpoint Server-Sent Events para MCP - Envía herramientas disponibles"""
            
            async def mcp_event_publisher():
                """Generador de eventos SSE en formato MCP"""
                try:
                    # Agregar cliente SSE
                    client_id = id(request)
                    self.sse_clients.add(client_id)
                    logger.info(f"Cliente MCP SSE conectado. Total: {len(self.sse_clients)}")
                    
                    # Enviar información del servidor MCP
                    yield {
                        "event": "server_info",
                        "data": json.dumps({
                            "protocol": "mcp",
                            "version": "1.0",
                            "server": {
                                "name": "conalca-mcp-server",
                                "version": "2.0.0"
                            },
                            "capabilities": {
                                "tools": True,
                                "resources": True,
                                "streaming": True
                            }
                        })
                    }
                    
                    # Enviar lista de herramientas disponibles
                    tools = [
                        {
                            "name": "get_llamadas",
                            "description": "Obtiene todas las llamadas con paginación opcional",
                            "inputSchema": {
                                "type": "object",
                                "properties": {
                                    "page": {"type": "integer", "description": "Número de página", "default": 1},
                                    "limit": {"type": "integer", "description": "Límite por página", "default": 20}
                                }
                            }
                        },
                        {
                            "name": "get_llamada_by_id",
                            "description": "Obtiene una llamada específica por ID",
                            "inputSchema": {
                                "type": "object",
                                "properties": {
                                    "llamada_id": {"type": "integer", "description": "ID de la llamada"}
                                },
                                "required": ["llamada_id"]
                            }
                        },
                        {
                            "name": "create_llamada",
                            "description": "Crea una nueva llamada telefónica",
                            "inputSchema": {
                                "type": "object",
                                "properties": {
                                    "telefono": {"type": "string", "description": "Número de teléfono"},
                                    "estado": {"type": "string", "description": "Estado inicial", "default": "pendiente"},
                                    "observaciones": {"type": "string", "description": "Observaciones adicionales"}
                                },
                                "required": ["telefono"]
                            }
                        },
                        {
                            "name": "update_llamada_status",
                            "description": "Actualiza el estado de una llamada",
                            "inputSchema": {
                                "type": "object",
                                "properties": {
                                    "llamada_id": {"type": "integer", "description": "ID de la llamada"},
                                    "new_status": {"type": "string", "description": "Nuevo estado"}
                                },
                                "required": ["llamada_id", "new_status"]
                            }
                        },
                        {
                            "name": "get_cotizaciones",
                            "description": "Obtiene cotizaciones de vehículos con filtros",
                            "inputSchema": {
                                "type": "object",
                                "properties": {
                                    "page": {"type": "integer", "description": "Número de página", "default": 1},
                                    "limit": {"type": "integer", "description": "Límite por página", "default": 20}
                                }
                            }
                        },
                        {
                            "name": "get_vehicle_by_telefono_conductor",
                            "description": "Busca vehículos por teléfono del conductor",
                            "inputSchema": {
                                "type": "object",
                                "properties": {
                                    "telefono": {"type": "string", "description": "Teléfono del conductor"}
                                },
                                "required": ["telefono"]
                            }
                        },
                        {
                            "name": "elevenlabs_webhook_handler",
                            "description": "Maneja eventos de webhooks de ElevenLabs",
                            "inputSchema": {
                                "type": "object",
                                "properties": {
                                    "event_data": {
                                        "type": "object", 
                                        "description": "Datos del evento de ElevenLabs"
                                    }
                                },
                                "required": ["event_data"]
                            }
                        }
                    ]
                    
                    yield {
                        "event": "tools",
                        "data": json.dumps({
                            "tools": tools
                        })
                    }
                    
                    # Enviar recursos disponibles
                    resources = [
                        {
                            "uri": "conalca://database/llamadas",
                            "name": "Llamadas Database",
                            "description": "Acceso a la tabla de llamadas telefónicas",
                            "mimeType": "application/json"
                        },
                        {
                            "uri": "conalca://database/cotizaciones", 
                            "name": "Cotizaciones Database",
                            "description": "Acceso a la tabla de cotizaciones de vehículos",
                            "mimeType": "application/json"
                        },
                        {
                            "uri": "conalca://database/vehicles",
                            "name": "Vehicles Database", 
                            "description": "Acceso a la tabla de vehículos y conductores",
                            "mimeType": "application/json"
                        }
                    ]
                    
                    yield {
                        "event": "resources",
                        "data": json.dumps({
                            "resources": resources
                        })
                    }
                    
                    # Mantener la conexión viva con updates periódicos
                    counter = 0
                    while True:
                        counter += 1
                        
                        # Obtener estadísticas actuales
                        try:
                            llamadas_count = await db_connection.execute_query(
                                "SELECT COUNT(*) as count FROM llamadas"
                            )
                            count = llamadas_count[0]['count'] if llamadas_count else 0
                        except Exception as e:
                            logger.error(f"Error obteniendo estadísticas: {e}")
                            count = 0
                        
                        yield {
                            "event": "stats",
                            "data": json.dumps({
                                "timestamp": datetime.now().isoformat(),
                                "server_status": "healthy",
                                "total_llamadas": count,
                                "active_connections": {
                                    "websocket": len(self.streaming_clients),
                                    "sse": len(self.sse_clients)
                                },
                                "update_counter": counter
                            })
                        }
                        
                        await asyncio.sleep(30)  # Actualizar cada 30 segundos
                        
                except asyncio.CancelledError:
                    pass
                finally:
                    self.sse_clients.discard(client_id)
                    logger.info(f"Cliente MCP SSE desconectado. Total: {len(self.sse_clients)}")
            
            return EventSourceResponse(mcp_event_publisher())
        
        @self.app.get("/data/stream")
        async def http_streaming_endpoint():
            """Endpoint HTTP Streaming para datos en tiempo real"""
            
            async def generate_data():
                """Generador de datos streaming HTTP"""
                yield "data: " + json.dumps({
                    "type": "stream_start",
                    "message": "Iniciando stream de datos HTTP",
                    "timestamp": datetime.now().isoformat()
                }) + "\n\n"
                
                counter = 0
                while counter < 10:  # Stream limitado para demo
                    counter += 1
                    
                    # Obtener datos recientes de llamadas
                    try:
                        recent_calls = await repository.get_llamadas(limit=5)
                        calls_data = [call.model_dump() for call in recent_calls]
                    except Exception as e:
                        logger.error(f"Error obteniendo llamadas: {e}")
                        calls_data = []
                    
                    data = {
                        "type": "data_chunk",
                        "chunk_number": counter,
                        "recent_calls": calls_data,
                        "stats": {
                            "active_connections": len(self.streaming_clients),
                            "sse_clients": len(self.sse_clients)
                        },
                        "timestamp": datetime.now().isoformat()
                    }
                    
                    yield "data: " + json.dumps(data) + "\n\n"
                    await asyncio.sleep(2)  # Pausa entre chunks
                
                yield "data: " + json.dumps({
                    "type": "stream_end",
                    "message": "Stream finalizado",
                    "timestamp": datetime.now().isoformat()
                }) + "\n\n"
            
            return StreamingResponse(
                generate_data(),
                media_type="text/plain",
                headers={
                    "Cache-Control": "no-cache",
                    "Connection": "keep-alive",
                    "X-Accel-Buffering": "no"  # Disable nginx buffering
                }
            )
    
    async def _broadcast_to_clients(self, message: Dict[str, Any]):
        """Envía un mensaje a todos los clientes WebSocket conectados"""
        if not self.streaming_clients:
            return
        
        disconnected_clients = set()
        for client in self.streaming_clients:
            try:
                await client.send_json(message)
            except Exception as e:
                logger.error(f"Error enviando mensaje a cliente WebSocket: {e}")
                disconnected_clients.add(client)
        
        # Remover clientes desconectados
        for client in disconnected_clients:
            self.streaming_clients.discard(client)
    
    def _register_tools(self):
        """Registra todas las herramientas MCP"""
        
        @self.server.call_tool()
        async def get_llamadas(page: int = 1, limit: int = 20, status: Optional[str] = None) -> List[TextContent]:
            """Obtiene todas las llamadas con paginación opcional"""
            try:
                # Convertir page a offset
                offset = (page - 1) * limit
                llamadas = await repository.get_llamadas(limit=limit, offset=offset)
                
                # Notificar a clientes streaming sobre la consulta
                await self._broadcast_to_clients({
                    "type": "tool_execution",
                    "tool": "get_llamadas",
                    "params": {"page": page, "limit": limit, "status": status},
                    "result_count": len(llamadas),
                    "timestamp": datetime.now().isoformat()
                })
                
                return [TextContent(
                    type="text",
                    text=json.dumps({
                        "total": len(llamadas),
                        "page": page,
                        "limit": limit,
                        "llamadas": [llamada.model_dump() for llamada in llamadas]
                    }, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
                )]
            except Exception as e:
                logger.error(f"Error al obtener llamadas: {e}")
                return [TextContent(
                    type="text",
                    text=f"Error al obtener llamadas: {str(e)}"
                )]

        @self.server.call_tool()
        async def get_llamada_by_id(llamada_id: int) -> List[TextContent]:
            """Obtiene una llamada específica por ID"""
            try:
                llamada = await repository.get_llamada_by_id(llamada_id)
                
                # Notificar a clientes streaming
                await self._broadcast_to_clients({
                    "type": "tool_execution",
                    "tool": "get_llamada_by_id",
                    "params": {"llamada_id": llamada_id},
                    "found": llamada is not None,
                    "timestamp": datetime.now().isoformat()
                })
                
                if llamada:
                    return [TextContent(
                        type="text",
                        text=json.dumps(llamada.model_dump(), indent=2, ensure_ascii=False, cls=DateTimeEncoder)
                    )]
                else:
                    return [TextContent(
                        type="text",
                        text=f"No se encontró la llamada con ID: {llamada_id}"
                    )]
            except Exception as e:
                logger.error(f"Error al obtener llamada {llamada_id}: {e}")
                return [TextContent(
                    type="text",
                    text=f"Error al obtener llamada: {str(e)}"
                )]

        @self.server.call_tool()
        async def update_llamada_status(llamada_id: int, new_status: str) -> List[TextContent]:
            """Actualiza el estado de una llamada"""
            try:
                success = await repository.update_llamada_status(llamada_id, new_status)
                
                # Notificar a clientes streaming sobre la actualización
                await self._broadcast_to_clients({
                    "type": "data_update",
                    "action": "status_updated",
                    "llamada_id": llamada_id,
                    "new_status": new_status,
                    "success": success,
                    "timestamp": datetime.now().isoformat()
                })
                
                if success:
                    return [TextContent(
                        type="text",
                        text=f"Estado de llamada {llamada_id} actualizado a: {new_status}"
                    )]
                else:
                    return [TextContent(
                        type="text",
                        text=f"No se pudo actualizar la llamada {llamada_id}"
                    )]
            except Exception as e:
                logger.error(f"Error al actualizar llamada {llamada_id}: {e}")
                return [TextContent(
                    type="text",
                    text=f"Error al actualizar llamada: {str(e)}"
                )]

        @self.server.call_tool()
        async def create_llamada(telefono: str, estado: str = "pendiente", observaciones: Optional[str] = None) -> List[TextContent]:
            """Crea una nueva llamada"""
            try:
                llamada_id = await repository.create_llamada(telefono, estado, observaciones)
                
                # Notificar a clientes streaming sobre la nueva llamada
                await self._broadcast_to_clients({
                    "type": "data_create",
                    "action": "llamada_created",
                    "llamada_id": llamada_id,
                    "telefono": telefono,
                    "estado": estado,
                    "timestamp": datetime.now().isoformat()
                })
                
                return [TextContent(
                    type="text",
                    text=f"Nueva llamada creada con ID: {llamada_id}"
                )]
            except Exception as e:
                logger.error(f"Error al crear llamada: {e}")
                return [TextContent(
                    type="text",
                    text=f"Error al crear llamada: {str(e)}"
                )]

        @self.server.call_tool()
        async def get_cotizaciones(page: int = 1, limit: int = 20, status: Optional[str] = None) -> List[TextContent]:
            """Obtiene cotizaciones con filtros opcionales"""
            try:
                # Convertir page a offset
                offset = (page - 1) * limit
                cotizaciones = await repository.get_cotizaciones(limit=limit, offset=offset)
                
                # Notificar a clientes streaming
                await self._broadcast_to_clients({
                    "type": "tool_execution",
                    "tool": "get_cotizaciones",
                    "params": {"page": page, "limit": limit, "status": status},
                    "result_count": len(cotizaciones),
                    "timestamp": datetime.now().isoformat()
                })
                
                return [TextContent(
                    type="text",
                    text=json.dumps({
                        "total": len(cotizaciones),
                        "page": page,
                        "limit": limit,
                        "cotizaciones": [cotizacion.model_dump() for cotizacion in cotizaciones]
                    }, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
                )]
            except Exception as e:
                logger.error(f"Error al obtener cotizaciones: {e}")
                return [TextContent(
                    type="text",
                    text=f"Error al obtener cotizaciones: {str(e)}"
                )]

        @self.server.call_tool()
        async def get_vehicle_by_telefono_conductor(telefono: str) -> List[TextContent]:
            """Busca vehículos por teléfono del conductor"""
            try:
                vehicles = await repository.get_vehicle_by_telefono_conductor(telefono)
                
                # Notificar a clientes streaming
                await self._broadcast_to_clients({
                    "type": "tool_execution",
                    "tool": "get_vehicle_by_telefono_conductor",
                    "params": {"telefono": telefono},
                    "result_count": len(vehicles),
                    "timestamp": datetime.now().isoformat()
                })
                
                return [TextContent(
                    type="text",
                    text=json.dumps({
                        "total": len(vehicles),
                        "telefono_buscado": telefono,
                        "vehiculos": [vehicle.model_dump() for vehicle in vehicles]
                    }, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
                )]
            except Exception as e:
                logger.error(f"Error al buscar vehículos por teléfono {telefono}: {e}")
                return [TextContent(
                    type="text",
                    text=f"Error al buscar vehículos: {str(e)}"
                )]

        @self.server.call_tool()
        async def elevenlabs_webhook_handler(event_data: Dict[str, Any]) -> List[TextContent]:
            """Maneja eventos de webhooks de ElevenLabs con streaming"""
            try:
                event_type = event_data.get("event_type", "unknown")
                
                # Log del evento
                logger.info(f"Procesando evento de ElevenLabs: {event_type}")
                
                # Notificar a clientes streaming
                await self._broadcast_to_clients({
                    "type": "elevenlabs_event",
                    "event_type": event_type,
                    "data": event_data,
                    "timestamp": datetime.now().isoformat()
                })
                
                # Procesar según el tipo de evento
                if event_type == "conversation_started":
                    result = await self._handle_conversation_started(event_data)
                elif event_type == "conversation_ended":
                    result = await self._handle_conversation_ended(event_data)
                elif event_type == "call_status_change":
                    result = await self._handle_call_status_change(event_data)
                else:
                    result = f"Evento no reconocido: {event_type}"

                return [TextContent(
                    type="text",
                    text=json.dumps({
                        "event_type": event_type,
                        "processed": True,
                        "result": result,
                        "timestamp": datetime.now().isoformat()
                    }, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
                )]
                
            except Exception as e:
                logger.error(f"Error al procesar webhook de ElevenLabs: {e}")
                return [TextContent(
                    type="text",
                    text=f"Error al procesar webhook: {str(e)}"
                )]

    def _register_resources(self):
        """Registra recursos disponibles"""
        
        @self.server.list_resources()
        async def list_resources() -> list[Resource]:
            """Lista todos los recursos disponibles"""
            return [
                Resource(
                    uri="conalca://database/llamadas",
                    name="Llamadas Database",
                    description="Acceso a la tabla de llamadas telefónicas con streaming",
                    mimeType="application/json"
                ),
                Resource(
                    uri="conalca://database/cotizaciones",
                    name="Cotizaciones Database",
                    description="Acceso a la tabla de modelos de cotización con streaming",
                    mimeType="application/json"
                ),
                Resource(
                    uri="conalca://database/vehicles",
                    name="Vehicles Database",
                    description="Acceso a la tabla de vehículos, propietarios y conductores con streaming",
                    mimeType="application/json"
                ),
                Resource(
                    uri="conalca://streaming/websocket",
                    name="WebSocket Streaming",
                    description="Conexión WebSocket para datos en tiempo real",
                    mimeType="application/json"
                ),
                Resource(
                    uri="conalca://streaming/sse",
                    name="Server-Sent Events",
                    description="Stream SSE para actualizaciones en tiempo real",
                    mimeType="text/event-stream"
                )
            ]
    
    async def _handle_conversation_started(self, data: Dict[str, Any]) -> str:
        """Maneja el inicio de una conversación con streaming"""
        try:
            # Extraer información relevante
            conversation_id = data.get("conversation_id")
            phone_number = data.get("phone_number", "unknown")
            
            # Crear una nueva llamada en la base de datos
            if phone_number != "unknown":
                llamada_id = await repository.create_llamada(
                    telefono=phone_number,
                    estado="en_curso",
                    observaciones=f"Conversación iniciada - ID: {conversation_id}"
                )
                
                # Notificar a clientes streaming
                await self._broadcast_to_clients({
                    "type": "conversation_started",
                    "conversation_id": conversation_id,
                    "phone_number": phone_number,
                    "llamada_id": llamada_id,
                    "timestamp": datetime.now().isoformat()
                })
            
            return f"Conversación iniciada para {phone_number}"
            
        except Exception as e:
            logger.error(f"Error manejando inicio de conversación: {e}")
            return f"Error: {str(e)}"
    
    async def _handle_conversation_ended(self, data: Dict[str, Any]) -> str:
        """Maneja el fin de una conversación con streaming"""
        try:
            conversation_id = data.get("conversation_id")
            phone_number = data.get("phone_number", "unknown")
            duration = data.get("duration", 0)
            
            # Actualizar la llamada en la base de datos
            if phone_number != "unknown":
                # Buscar la llamada más reciente para este teléfono
                llamadas = await repository.get_llamadas(limit=1, status="en_curso")
                if llamadas:
                    await repository.update_llamada_status(
                        llamadas[0].id,
                        "completada"
                    )
                    
                    # Notificar a clientes streaming
                    await self._broadcast_to_clients({
                        "type": "conversation_ended",
                        "conversation_id": conversation_id,
                        "phone_number": phone_number,
                        "duration": duration,
                        "llamada_id": llamadas[0].id,
                        "timestamp": datetime.now().isoformat()
                    })
            
            return f"Conversación terminada para {phone_number} - Duración: {duration}s"
            
        except Exception as e:
            logger.error(f"Error manejando fin de conversación: {e}")
            return f"Error: {str(e)}"
    
    async def _handle_call_status_change(self, data: Dict[str, Any]) -> str:
        """Maneja cambios de estado de llamada con streaming"""
        try:
            new_status = data.get("status", "unknown")
            phone_number = data.get("phone_number", "unknown")
            
            # Actualizar estado en la base de datos
            if phone_number != "unknown":
                llamadas = await repository.get_llamadas(limit=1)
                if llamadas:
                    await repository.update_llamada_status(
                        llamadas[0].id,
                        new_status
                    )
                    
                    # Notificar a clientes streaming
                    await self._broadcast_to_clients({
                        "type": "call_status_changed",
                        "phone_number": phone_number,
                        "new_status": new_status,
                        "llamada_id": llamadas[0].id,
                        "timestamp": datetime.now().isoformat()
                    })
            
            return f"Estado actualizado a {new_status} para {phone_number}"
            
        except Exception as e:
            logger.error(f"Error manejando cambio de estado: {e}")
            return f"Error: {str(e)}"
    
    async def close(self):
        """Cierra las conexiones del servidor"""
        try:
            # Cerrar todas las conexiones WebSocket
            for client in self.streaming_clients.copy():
                try:
                    await client.close()
                except:
                    pass
            
            await db_connection.close()
            logger.info("Servidor cerrado correctamente")
        except Exception as e:
            logger.error(f"Error al cerrar servidor: {e}")

# Función para iniciar el servidor con FastAPI
async def run_http_server(host: str = "127.0.0.1", port: int = 18840, root_path: str = ""):
    """Inicia el servidor HTTP streameable con FastAPI"""
    try:
        # Usar uvloop para mejor rendimiento en Linux
        if sys.platform != 'win32':
            uvloop.install()
        
        # Crear e inicializar el servidor
        mcp_server = ConalcaMCPServer(root_path=root_path)
        await mcp_server.initialize()
        
        # Configurar uvicorn con soporte para streaming
        config = uvicorn.Config(
            app=mcp_server.app,
            host=host,
            port=port,
            log_level="info",
            access_log=True,
            loop="uvloop" if sys.platform != 'win32' else "asyncio",
            ws_ping_interval=20,
            ws_ping_timeout=20,
            timeout_keep_alive=65
        )
        
        server = uvicorn.Server(config)
        
        logger.info(f"Iniciando servidor HTTP streameable en {host}:{port}")
        if root_path:
            logger.info(f"Root path configurado: {root_path}")
        logger.info("Capacidades de streaming: WebSocket, SSE, HTTP Streaming")
        
        await server.serve()
        
    except Exception as e:
        logger.error(f"Error al iniciar servidor HTTP: {e}")
        raise
    finally:
        await mcp_server.close()

# Función principal para STDIO (para compatibilidad MCP estándar)
async def main():
    """Función principal para protocolo MCP estándar via STDIO"""
    try:
        # Usar uvloop para mejor rendimiento en Linux
        if sys.platform != 'win32':
            uvloop.install()
        
        # Crear e inicializar el servidor
        mcp_server = ConalcaMCPServer()
        await mcp_server.initialize()
        
        # Ejecutar servidor STDIO
        async with stdio_server() as streams:
            await mcp_server.server.run(
                streams[0], streams[1], mcp_server.server.create_initialization_options()
            )
            
    except Exception as e:
        logger.error(f"Error en servidor MCP: {e}")
        raise
    finally:
        if 'mcp_server' in locals():
            await mcp_server.close()

if __name__ == "__main__":
    import sys
    if len(sys.argv) > 1 and sys.argv[1] == "http":
        # Modo HTTP para desarrollo y producción
        port = int(sys.argv[2]) if len(sys.argv) > 2 else 18840
        root_path = sys.argv[3] if len(sys.argv) > 3 else ""
        asyncio.run(run_http_server(port=port, root_path=root_path))
    else:
        # Modo STDIO estándar MCP
        asyncio.run(main())