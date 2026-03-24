import asyncio
import logging
import sys
import time
from typing import Any, Dict, List, Optional, Set
import uvloop
import json
from datetime import datetime, date
from fastapi import FastAPI, Request, HTTPException, WebSocket, WebSocketDisconnect, Response
from fastapi.responses import JSONResponse, StreamingResponse
from fastapi.middleware.cors import CORSMiddleware
from sse_starlette.sse import EventSourceResponse
import uvicorn

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

# Custom JSON encoder para manejar fechas
class DateTimeEncoder(json.JSONEncoder):
    def default(self, obj):
        if isinstance(obj, (datetime, date)):
            return obj.isoformat()
        return super().default(obj)

class ConalcaMCPServer:
    """Servidor MCP compatible con ElevenLabs - Protocolo JSON-RPC 2.0"""
    
    def __init__(self, root_path: str = ""):
        self.server = Server("conalca-mcp-server")
        self.streaming_clients: Set[WebSocket] = set()
        self.sse_clients: Set = set()
        self.root_path = root_path
        
        # Crear FastAPI app
        self.app = FastAPI(
            title="Conalca MCP Server",
            description="Servidor MCP compatible con ElevenLabs para gestión de llamadas, cotizaciones y vehículos",
            version="2.1.0",
            root_path=root_path
        )
        
        # Configurar CORS
        self.app.add_middleware(
            CORSMiddleware,
            allow_origins=["*"],
            allow_credentials=True,
            allow_methods=["*"],
            allow_headers=["*"],
            expose_headers=["*"]
        )
        
        # Registrar endpoints MCP
        self._register_mcp_endpoints()
        
    async def initialize(self):
        """Inicializa el servidor y la conexión a la base de datos"""
        try:
            await db_connection.initialize()
            logger.info("Conexión a base de datos inicializada")
            self._register_tools()
            logger.info("Herramientas MCP registradas")
            
        except Exception as e:
            logger.error(f"Error al inicializar el servidor: {e}")
            raise
    
    def _register_mcp_endpoints(self):
        """Registra endpoints compatibles con protocolo MCP"""
        
        @self.app.websocket("/ws")
        async def websocket_mcp_endpoint(websocket: WebSocket):
            """Endpoint WebSocket para protocolo MCP (requerido por ElevenLabs)"""
            await websocket.accept()
            self.streaming_clients.add(websocket)
            logger.info("Cliente WebSocket conectado para MCP")
            
            try:
                while True:
                    # Recibir mensaje JSON-RPC
                    data = await websocket.receive_json()
                    method = data.get("method")
                    params = data.get("params", {})
                    request_id = data.get("id")
                    
                    logger.info(f"WebSocket recibió método: {method}")
                    
                    # Procesar según el método
                    if method == "initialize":
                        response = {
                            "jsonrpc": "2.0",
                            "id": request_id,
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
                                    "version": "2.1.0"
                                }
                            }
                        }
                        await websocket.send_json(response)
                    
                    elif method == "tools/list":
                        # Obtener lista de herramientas (usando el endpoint POST existente)
                        tools_response = await self._get_tools_list()
                        response = {
                            "jsonrpc": "2.0",
                            "id": request_id,
                            "result": {
                                "tools": tools_response
                            }
                        }
                        await websocket.send_json(response)
                    
                    elif method == "tools/call":
                        # Ejecutar herramienta
                        tool_name = params.get("name")
                        tool_args = params.get("arguments", {})
                        
                        try:
                            result = await self._execute_tool(tool_name, tool_args)
                            response = {
                                "jsonrpc": "2.0",
                                "id": request_id,
                                "result": {
                                    "content": [
                                        {
                                            "type": "text",
                                            "text": json.dumps(result, cls=DateTimeEncoder, ensure_ascii=False)
                                        }
                                    ]
                                }
                            }
                        except Exception as e:
                            logger.error(f"Error ejecutando herramienta {tool_name}: {e}")
                            response = {
                                "jsonrpc": "2.0",
                                "id": request_id,
                                "error": {
                                    "code": -32603,
                                    "message": f"Error ejecutando herramienta: {str(e)}"
                                }
                            }
                        
                        await websocket.send_json(response)
                    
                    else:
                        # Método no soportado
                        response = {
                            "jsonrpc": "2.0",
                            "id": request_id,
                            "error": {
                                "code": -32601,
                                "message": f"Método no soportado: {method}"
                            }
                        }
                        await websocket.send_json(response)
                        
            except WebSocketDisconnect:
                logger.info("Cliente WebSocket desconectado")
            except Exception as e:
                logger.error(f"Error en WebSocket: {e}")
            finally:
                self.streaming_clients.discard(websocket)
        
        @self.app.post("/")
        async def mcp_rpc_endpoint(request: Request):
            """Endpoint principal JSON-RPC 2.0 para protocolo MCP"""
            try:
                data = await request.json()
                method = data.get("method")
                params = data.get("params", {})
                request_id = data.get("id")
                
                if method == "initialize":
                    return {
                        "jsonrpc": "2.0",
                        "id": request_id,
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
                                "version": "2.1.0"
                            }
                        }
                    }
                
                elif method == "tools/list":
                    return {
                        "jsonrpc": "2.0",
                        "id": request_id,
                        "result": {
                            "tools": [
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
                                },
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
                                },
                                {
                                    "name": "create_llamada",
                                    "description": "Crea una nueva llamada telefónica en el sistema. Registra el número de teléfono del contacto, número de destino, estado inicial y observaciones opcionales.",
                                    "inputSchema": {
                                        "type": "object",
                                        "properties": {
                                            "telefono": {
                                                "type": "string",
                                                "description": "Número de teléfono del contacto",
                                                "pattern": "^[+]?[0-9\\s\\-\\(\\)]+$"
                                            },
                                            "numero_destino": {
                                                "type": "string",
                                                "description": "Número de teléfono al que se está llamando",
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
                                },
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
                                },
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
                                },
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
                                },
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
                                },
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
                                },
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
                                },
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
                                },
                                {
                                    "name": "generate_transport_offer",
                                    "description": "Obtiene información detallada del viaje (chofer, origen, destino, producto, embalaje, fecha) usando el conversation_id. Retorna solo los datos estructurados sin mensajes predeterminados para que la IA los use libremente.",
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
                                },
                                {
                                    "name": "save_driver_decision",
                                    "description": "Guarda la decisión del chofer sobre la oferta de transporte. Registra si acepta (1) o rechaza (0) la propuesta usando el identificador_unico del conductor en llamadas_conductores.",
                                    "inputSchema": {
                                        "type": "object",
                                        "properties": {
                                            "identificador_unico": {
                                                "type": "string",
                                                "description": "Identificador único del conductor en llamadas_conductores (ej: LC-3-5e05b092-1764976542)"
                                            },
                                            "conversation_id": {
                                                "type": "string",
                                                "description": "ID de conversación de ElevenLabs (opcional)"
                                            },
                                            "decision": {
                                                "type": "integer",
                                                "description": "Decisión del chofer: 1 para aceptar, 0 para rechazar",
                                                "enum": [0, 1]
                                            },
                                            "notas": {
                                                "type": "string",
                                                "description": "Notas adicionales sobre la decisión (opcional)"
                                            }
                                        },
                                        "required": ["identificador_unico", "decision"]
                                    }
                                },
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
                                },
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
                                },
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
                                },
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
                                },
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
                                },
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
                                },
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
                                },
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
                                },
                                {
                                    "name": "precioviaje",
                                    "description": "Obtiene el valor del FLETE del viaje desde la cotización. El flete es el valor que se le paga al conductor por el transporte. Usa esta herramienta cuando el chofer pregunte cuánto se paga o el valor del viaje.",
                                    "inputSchema": {
                                        "type": "object",
                                        "properties": {
                                            "cotizacion_id": {
                                                "type": "integer",
                                                "description": "ID de la cotización para consultar el precio del viaje",
                                                "minimum": 1
                                            }
                                        },
                                        "required": ["cotizacion_id"]
                                    }
                                },
                                {
                                    "name": "zinformacion",
                                    "description": "Consultar información operativa completa de órdenes con campos estáticos de cotizacion_models. Excluye información sensible como datos del cliente, porcentajes de ganancia y decisiones posteriores a llamadas.",
                                    "inputSchema": {
                                        "type": "object",
                                        "properties": {
                                            "orden_id": {
                                                "type": "integer",
                                                "description": "ID específico de la orden a consultar",
                                                "minimum": 1
                                            },
                                            "search": {
                                                "type": "string",
                                                "description": "Texto para buscar en ciudades, productos o mercancías"
                                            },
                                            "show_all": {
                                                "type": "boolean",
                                                "description": "Mostrar todas las órdenes (limitadas por limit)",
                                                "default": False
                                            },
                                            "show_stats": {
                                                "type": "boolean",
                                                "description": "Mostrar estadísticas de completitud de campos obligatorios",
                                                "default": False
                                            },
                                            "limit": {
                                                "type": "integer",
                                                "description": "Límite de resultados para búsquedas",
                                                "default": 10,
                                                "minimum": 1,
                                                "maximum": 50
                                            }
                                        }
                                    }
                                },
                                {
                                    "name": "llenar_formulario",
                                    "description": "Llena automáticamente un formulario de cotización usando datos de una orden existente. Convierte la información técnica en valores de formulario listos para usar por el agente de IA.",
                                    "inputSchema": {
                                        "type": "object",
                                        "properties": {
                                            "orden_id": {
                                                "type": "integer",
                                                "description": "ID de la orden para obtener datos del formulario",
                                                "minimum": 1
                                            },
                                            "tipo_formulario": {
                                                "type": "string",
                                                "description": "Tipo de formulario a llenar",
                                                "enum": ["cotizacion", "pre_solicitud", "despacho"],
                                                "default": "cotizacion"
                                            }
                                        },
                                        "required": ["orden_id"]
                                    }
                                },
                                {
                                    "name": "get_conductor_by_telefono",
                                    "description": "Busca conductores en llamadas_conductores por número de teléfono. Devuelve información completa del conductor incluyendo placa, tipo de vehículo, ciudad actual, estado de llamada y conversation_id si existe.",
                                    "inputSchema": {
                                        "type": "object",
                                        "properties": {
                                            "telefono": {
                                                "type": "string",
                                                "description": "Número de teléfono del conductor (ej: 3105672307, +573105672307)"
                                            }
                                        },
                                        "required": ["telefono"]
                                    }
                                },
                                {
                                    "name": "update_conversation_id_conductor",
                                    "description": "Actualiza el conversation_id (call_id) de ElevenLabs para un conductor específico cuando se inicia una llamada. También actualiza el estado a 'en_progreso' y registra la fecha/hora de la llamada.",
                                    "inputSchema": {
                                        "type": "object",
                                        "properties": {
                                            "identificador_unico": {
                                                "type": "string",
                                                "description": "Identificador único del conductor en llamadas_conductores"
                                            },
                                            "conversation_id": {
                                                "type": "string",
                                                "description": "ID de conversación de ElevenLabs (conversation_id)"
                                            },
                                            "estado_llamada": {
                                                "type": "string",
                                                "description": "Nuevo estado de la llamada",
                                                "enum": ["pendiente", "en_progreso", "completada", "fallida", "cancelada"],
                                                "default": "en_progreso"
                                            },
                                            "notas": {
                                                "type": "string",
                                                "description": "Notas adicionales sobre la llamada (opcional)"
                                            }
                                        },
                                        "required": ["identificador_unico", "conversation_id"]
                                    }
                                }
                            ]
                        }
                    }
                
                elif method == "tools/call":
                    tool_name = params.get("name")
                    arguments = params.get("arguments", {})
                    
                    result = await self._execute_tool(tool_name, arguments)
                    
                    return {
                        "jsonrpc": "2.0",
                        "id": request_id,
                        "result": {
                            "content": [
                                {
                                    "type": "text",
                                    "text": result
                                }
                            ]
                        }
                    }
                
                elif method == "resources/list":
                    return {
                        "jsonrpc": "2.0",
                        "id": request_id,
                        "result": {
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
                    }
                
                else:
                    return {
                        "jsonrpc": "2.0",
                        "id": request_id,
                        "error": {
                            "code": -32601,
                            "message": f"Método '{method}' no encontrado"
                        }
                    }
                    
            except Exception as e:
                logger.error(f"Error en endpoint MCP: {e}")
                return {
                    "jsonrpc": "2.0",
                    "id": request_id if 'request_id' in locals() else None,
                    "error": {
                        "code": -32603,
                        "message": f"Error interno del servidor: {str(e)}"
                    }
                }

        @self.app.post("/mcp-elevenlabs")
        async def elevenlabs_mcp_endpoint(request: Request, response: Response):
            """Endpoint ULTRA-OPTIMIZADO específico para ElevenLabs"""
            # Headers optimizados para ElevenLabs (igual que el servidor funcionando)
            response.headers["Content-Type"] = "application/json; charset=utf-8"
            response.headers["Access-Control-Allow-Origin"] = "*"
            response.headers["X-ElevenLabs-Optimized"] = "true"
            response.headers["X-Response-Time"] = str(int(time.time() * 1000))
            
            try:
                data = await request.json()
                method = data.get("method")
                params = data.get("params", {})
                request_id = data.get("id")
                
                # Soporte para inicialización de ElevenLabs
                if method == "initialize":
                    return {
                        "jsonrpc": "2.0",
                        "id": request_id,
                        "result": {
                            "protocolVersion": "2025-03-26",
                            "capabilities": {
                                "tools": {
                                    "listChanged": True
                                }
                            },
                            "serverInfo": {
                                "name": "Conalca MCP Server",
                                "version": "2.1.0"
                            }
                        }
                    }
                
                # Respuesta ultra-rápida para tools/list
                if method == "tools/list":
                    tools_list = [
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
                        },
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
                        },
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
                        },
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
                        },
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
                        },
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
                        },
                        {
                            "name": "get_cotizacion_info_by_conversation",
                            "description": "Obtiene información de cotización y chofer asociados a un conversation_id específico. Ideal para agentes de ElevenLabs que necesitan datos del contexto de la llamada.",
                            "inputSchema": {
                                "type": "object",
                                "properties": {
                                    "conversation_id": {
                                        "type": "string",
                                        "description": "ID de conversación de ElevenLabs (ej: conv_430tk697vm3e47b1rre6r6e3rzb)",
                                        "pattern": "^conv_[a-zA-Z0-9]+$"
                                    }
                                },
                                "required": ["conversation_id"]
                            }
                        },
                        {
                            "name": "generate_transport_offer",
                            "description": "Obtiene información detallada del viaje (chofer, origen, destino, producto, embalaje, fecha) usando el conversation_id. Retorna solo los datos estructurados sin mensajes predeterminados para que la IA los use libremente.",
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
                        },
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
                        },
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
                        },
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
                        },
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
                        },
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
                        },
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
                        },
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
                        },
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
                        },
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
                        },
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
                        },
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
                        },
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
                    ]
                    
                    return {
                        "jsonrpc": "2.0",
                        "id": request_id,
                        "result": {
                            "tools": tools_list
                        }
                    }
                
                # Ejecución de herramientas optimizada
                if method == "tools/call":
                    tool_name = params.get("name")
                    tool_args = params.get("arguments", {})
                    
                    result = await self._execute_tool(tool_name, tool_args)
                    
                    return {
                        "jsonrpc": "2.0",
                        "id": request_id,
                        "result": {
                            "content": [{"type": "text", "text": result}]
                        }
                    }
                
                return {
                    "jsonrpc": "2.0",
                    "id": request_id,
                    "error": {"code": -32601, "message": "Método no encontrado"}
                }
                
            except Exception as e:
                logger.error(f"Error en ElevenLabs MCP: {e}")
                return {
                    "jsonrpc": "2.0",
                    "id": data.get("id") if 'data' in locals() else "unknown",
                    "error": {"code": -32603, "message": str(e)}
                }
        
        @self.app.get("/")
        async def root():
            """Endpoint raíz para verificación HTTP"""
            return {
                "service": "Conalca MCP Server",
                "version": "2.1.0",
                "protocol": "MCP (Model Context Protocol)",
                "status": "active",
                "description": "Servidor MCP para gestión de llamadas, cotizaciones y vehículos con integración ElevenLabs",
                "endpoints": {
                    "mcp_rpc": f"{self.root_path}/",
                    "health": f"{self.root_path}/health",
                    "webhook": f"{self.root_path}/webhook/elevenlabs"
                },
                "tools_available": 8,
                "capabilities": ["tools", "resources", "streaming"]
            }
        
        @self.app.get("/sse")
        async def sse_endpoint(request: Request):
            """Server-Sent Events endpoint para STREAMABLE_HTTP"""
            async def event_generator():
                try:
                    # Enviar evento de conexión inicial
                    yield {
                        "event": "connected",
                        "data": json.dumps({
                            "type": "connection",
                            "status": "connected",
                            "server": "conalca-mcp-server",
                            "version": "2.1.0"
                        })
                    }
                    
                    # Mantener la conexión abierta
                    while True:
                        if await request.is_disconnected():
                            break
                        
                        # Enviar heartbeat cada 30 segundos
                        yield {
                            "event": "heartbeat",
                            "data": json.dumps({
                                "type": "heartbeat",
                                "timestamp": datetime.now().isoformat()
                            })
                        }
                        
                        await asyncio.sleep(30)
                        
                except asyncio.CancelledError:
                    logger.info("SSE connection cancelled")
                except Exception as e:
                    logger.error(f"Error in SSE stream: {e}")
            
            return EventSourceResponse(event_generator())
        
        @self.app.post("/mcp/sse")
        async def sse_post_endpoint(request: Request):
            """Endpoint POST para mensajes MCP via SSE"""
            try:
                data = await request.json()
                method = data.get("method")
                params = data.get("params", {})
                request_id = data.get("id")
                
                logger.info(f"SSE POST recibió método: {method}")
                
                if method == "initialize":
                    return {
                        "jsonrpc": "2.0",
                        "id": request_id,
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
                                "version": "2.1.0"
                            }
                        }
                    }
                
                elif method == "tools/list":
                    tools_list = await self._get_tools_list()
                    return {
                        "jsonrpc": "2.0",
                        "id": request_id,
                        "result": {
                            "tools": tools_list
                        }
                    }
                
                elif method == "tools/call":
                    tool_name = params.get("name")
                    tool_args = params.get("arguments", {})
                    
                    try:
                        result = await self._execute_tool(tool_name, tool_args)
                        return {
                            "jsonrpc": "2.0",
                            "id": request_id,
                            "result": {
                                "content": [
                                    {
                                        "type": "text",
                                        "text": json.dumps(result, cls=DateTimeEncoder, ensure_ascii=False)
                                    }
                                ]
                            }
                        }
                    except Exception as e:
                        logger.error(f"Error ejecutando herramienta {tool_name}: {e}")
                        return {
                            "jsonrpc": "2.0",
                            "id": request_id,
                            "error": {
                                "code": -32603,
                                "message": f"Error ejecutando herramienta: {str(e)}"
                            }
                        }
                
                else:
                    return {
                        "jsonrpc": "2.0",
                        "id": request_id,
                        "error": {
                            "code": -32601,
                            "message": f"Método no soportado: {method}"
                        }
                    }
                    
            except Exception as e:
                logger.error(f"Error en SSE POST: {e}")
                return {
                    "jsonrpc": "2.0",
                    "error": {
                        "code": -32603,
                        "message": f"Error interno: {str(e)}"
                    }
                }
        
        @self.app.get("/health")
        async def health_check():
            """Health check endpoint"""
            try:
                await db_connection.execute_query("SELECT 1")
                return {
                    "status": "healthy",
                    "timestamp": datetime.now().isoformat(),
                    "database": "connected",
                    "version": "2.1.0",
                    "protocol": "MCP JSON-RPC 2.0"
                }
            except Exception as e:
                logger.error(f"Health check failed: {e}")
                raise HTTPException(status_code=503, detail="Service unhealthy")
        
        @self.app.post("/webhook/elevenlabs")
        async def elevenlabs_webhook(request: Request):
            """Webhook para eventos de ElevenLabs"""
            try:
                data = await request.json()
                logger.info(f"Webhook ElevenLabs recibido: {data}")
                
                event_type = data.get("event_type", "unknown")
                result = await self._process_elevenlabs_event(data)
                
                return {
                    "status": "success",
                    "event_type": event_type,
                    "processed": True,
                    "result": result,
                    "timestamp": datetime.now().isoformat()
                }
                
            except Exception as e:
                logger.error(f"Error procesando webhook ElevenLabs: {e}")
                raise HTTPException(status_code=500, detail=str(e))
    
    async def _execute_tool(self, tool_name: str, arguments: Dict[str, Any]) -> str:
        """Ejecuta una herramienta MCP"""
        try:
            if tool_name == "get_llamadas":
                page = arguments.get("page", 1)
                limit = arguments.get("limit", 20)
                offset = (page - 1) * limit
                
                llamadas = await repository.get_llamadas(limit=limit, offset=offset)
                
                result = {
                    "success": True,
                    "total_encontradas": len(llamadas),
                    "page": page,
                    "limit": limit,
                    "llamadas": [llamada.model_dump() for llamada in llamadas]
                }
                
                return json.dumps(result, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
            
            elif tool_name == "get_llamada_by_id":
                llamada_id = arguments.get("llamada_id")
                if not llamada_id:
                    return json.dumps({"error": "llamada_id es requerido"}, ensure_ascii=False)
                
                llamada = await repository.get_llamada_by_id(llamada_id)
                
                if llamada:
                    result = {
                        "success": True,
                        "llamada": llamada.model_dump()
                    }
                else:
                    result = {
                        "success": False,
                        "error": f"No se encontró llamada con ID: {llamada_id}"
                    }
                
                return json.dumps(result, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
            
            elif tool_name == "create_llamada":
                telefono = arguments.get("telefono")
                if not telefono:
                    return json.dumps({"error": "telefono es requerido"}, ensure_ascii=False)
                
                numero_destino = arguments.get("numero_destino")
                estado = arguments.get("estado", "pendiente")
                observaciones = arguments.get("observaciones")
                
                llamada_id = await repository.create_llamada(telefono, estado, observaciones, numero_destino)
                
                result = {
                    "success": True,
                    "message": f"Nueva llamada creada exitosamente",
                    "llamada_id": llamada_id,
                    "telefono": telefono,
                    "numero_destino": numero_destino,
                    "estado": estado
                }
                
                return json.dumps(result, indent=2, ensure_ascii=False)
            
            elif tool_name == "update_llamada_status":
                llamada_id = arguments.get("llamada_id")
                new_status = arguments.get("new_status")
                
                if not llamada_id or not new_status:
                    return json.dumps({"error": "llamada_id y new_status son requeridos"}, ensure_ascii=False)
                
                success = await repository.update_llamada_status(llamada_id, new_status)
                
                result = {
                    "success": success,
                    "message": f"Estado de llamada {llamada_id} actualizado a: {new_status}" if success else f"No se pudo actualizar la llamada {llamada_id}",
                    "llamada_id": llamada_id,
                    "new_status": new_status
                }
                
                return json.dumps(result, indent=2, ensure_ascii=False)
            
            elif tool_name == "get_llamadas_by_telefono":
                telefono = arguments.get("telefono")
                tipo_busqueda = arguments.get("tipo_busqueda", "ambos")
                
                if not telefono:
                    return json.dumps({"error": "telefono es requerido"}, ensure_ascii=False)
                
                # Limpiar el número de teléfono
                telefono_limpio = telefono.strip().replace(" ", "").replace("-", "").replace("(", "").replace(")", "")
                
                llamadas = await repository.get_llamadas_by_telefono(telefono_limpio, tipo_busqueda)
                
                result = {
                    "success": True,
                    "telefono_buscado": telefono,
                    "tipo_busqueda": tipo_busqueda,
                    "total_encontradas": len(llamadas),
                    "llamadas": []
                }
                
                for llamada in llamadas:
                    llamada_data = {
                        "id_llamada": llamada.id_llamada,
                        "telefono_origen": llamada.telefono if hasattr(llamada, 'telefono') else None,
                        "numero_destino": llamada.numero_destino if hasattr(llamada, 'numero_destino') else None,
                        "estado": llamada.status,
                        "observaciones": getattr(llamada, 'observaciones', None),
                        "elevenlabs_conversation_id": llamada.elevenlabs_conversation_id,
                        "elevenlabs_sip_call_id": llamada.elevenlabs_sip_call_id,
                        "call_started_at": llamada.call_started_at.isoformat() if llamada.call_started_at else None,
                        "call_ended_at": llamada.call_ended_at.isoformat() if llamada.call_ended_at else None,
                        "call_notes": llamada.call_notes,
                        "created_at": llamada.created_at.isoformat() if llamada.created_at else None,
                        "updated_at": llamada.updated_at.isoformat() if llamada.updated_at else None
                    }
                    result["llamadas"].append(llamada_data)
                
                return json.dumps(result, indent=2, ensure_ascii=False)
            
            elif tool_name == "activate_conversation":
                conversation_id = arguments.get("conversation_id")
                new_status = arguments.get("new_status", "en_curso")
                call_notes = arguments.get("call_notes")
                sip_call_id = arguments.get("sip_call_id")
                
                if not conversation_id:
                    return json.dumps({"error": "conversation_id es requerido"}, ensure_ascii=False)
                
                # Buscar la llamada por conversation_id
                llamada = await repository.get_llamada_by_conversation_id(conversation_id)
                
                if not llamada:
                    return json.dumps({
                        "error": f"No se encontró ninguna llamada con conversation_id: {conversation_id}",
                        "conversation_id": conversation_id
                    }, ensure_ascii=False)
                
                # Actualizar el estado y notas
                success = await repository.activate_conversation(
                    conversation_id, 
                    new_status, 
                    call_notes, 
                    sip_call_id
                )
                
                if success:
                    # Obtener la llamada actualizada
                    llamada_actualizada = await repository.get_llamada_by_conversation_id(conversation_id)
                    
                    result = {
                        "success": True,
                        "message": f"Conversación {conversation_id} activada/actualizada exitosamente",
                        "conversation_id": conversation_id,
                        "previous_status": llamada.status,
                        "new_status": new_status,
                        "llamada_actualizada": {
                            "id_llamada": llamada_actualizada.id_llamada,
                            "numero_destino": llamada_actualizada.numero_destino,
                            "estado": llamada_actualizada.status,
                            "elevenlabs_conversation_id": llamada_actualizada.elevenlabs_conversation_id,
                            "elevenlabs_sip_call_id": llamada_actualizada.elevenlabs_sip_call_id,
                            "call_notes": llamada_actualizada.call_notes,
                            "updated_at": llamada_actualizada.updated_at.isoformat() if llamada_actualizada.updated_at else None
                        }
                    }
                else:
                    result = {
                        "success": False,
                        "error": f"No se pudo actualizar la conversación {conversation_id}",
                        "conversation_id": conversation_id
                    }
                
                return json.dumps(result, indent=2, ensure_ascii=False)
            
            elif tool_name == "get_cotizacion_info_by_conversation":
                conversation_id = arguments.get("conversation_id")
                
                if not conversation_id:
                    return json.dumps({"error": "conversation_id es requerido"}, ensure_ascii=False)
                
                # Buscar la llamada por conversation_id
                llamada = await repository.get_llamada_by_conversation_id(conversation_id)
                
                if not llamada:
                    return json.dumps({
                        "success": False,
                        "error": f"No se encontró ninguna llamada con conversation_id: {conversation_id}",
                        "conversation_id": conversation_id
                    }, ensure_ascii=False)
                
                # Obtener información de cotización si existe
                cotizacion_info = None
                if llamada.id_cotizacion and llamada.id_cotizacion > 0:
                    cotizacion = await repository.get_cotizacion_by_id(llamada.id_cotizacion)
                    if cotizacion:
                        cotizacion_info = cotizacion.model_dump()
                
                # Obtener información del chofer si existe
                chofer_info = None
                if llamada.chofer_id and llamada.chofer_id > 0:
                    # Buscar chofer en la tabla vehicle_owner_holder_driver
                    chofer = await repository.get_chofer_by_id(llamada.chofer_id)
                    if chofer:
                        chofer_info = {
                            "id": chofer.id,
                            "conductor": chofer.conductor,
                            "cedula": chofer.cedula,
                            "telefono": chofer.telefonoconductor,
                            "direccion": chofer.direccion_conductor,
                            "ciudad": chofer.ciudad_conductor,
                            "placa": chofer.placa
                        }
                
                result = {
                    "success": True,
                    "conversation_id": conversation_id,
                    "llamada_info": {
                        "id_llamada": llamada.id_llamada,
                        "id_cotizacion": llamada.id_cotizacion,
                        "chofer_id": llamada.chofer_id,
                        "numero_destino": llamada.numero_destino,
                        "estado": llamada.status,
                        "elevenlabs_sip_call_id": llamada.elevenlabs_sip_call_id,
                        "call_notes": llamada.call_notes,
                        "created_at": llamada.created_at.isoformat() if llamada.created_at else None,
                        "updated_at": llamada.updated_at.isoformat() if llamada.updated_at else None
                    },
                    "cotizacion_info": cotizacion_info,
                    "chofer_info": chofer_info,
                    "mensaje_para_agente": self._generar_mensaje_agente(llamada, cotizacion_info, chofer_info)
                }
                
                return json.dumps(result, indent=2, ensure_ascii=False)
            
            elif tool_name == "generate_transport_offer":
                conversation_id = arguments.get("conversation_id")
                
                if not conversation_id:
                    return json.dumps({"error": "conversation_id es requerido"}, ensure_ascii=False)
                
                # Obtener información del conductor desde la nueva tabla llamadas_conductores
                conductor_data = await repository.get_conductor_by_conversation_id(conversation_id)
                
                if not conductor_data:
                    return json.dumps({
                        "success": False,
                        "error": f"No se encontró conductor disponible para conversation_id: {conversation_id}",
                        "conversation_id": conversation_id
                    }, ensure_ascii=False)
                
                # Extraer datos del conductor
                nombre_conductor = conductor_data.get('nombre_conductor', 'estimado conductor')
                identificador_unico = conductor_data.get('identificador_unico')
                cotizacion_id = conductor_data.get('cotizacion_id')
                
                # Obtener nombres legibles de tipo_embalaje y tipo_producto
                tipo_embalaje_nombre = conductor_data.get('tipo_embajale', 'N/A')
                tipo_producto_nombre = conductor_data.get('tipo_producto', 'N/A')
                
                # Si son IDs numéricos, consultar los nombres
                if conductor_data.get('tipo_embajale') and str(conductor_data.get('tipo_embajale')).isdigit():
                    nombre_embalaje = await repository.get_packing_name(int(conductor_data.get('tipo_embajale')))
                    if nombre_embalaje:
                        tipo_embalaje_nombre = nombre_embalaje
                
                if conductor_data.get('tipo_producto') and str(conductor_data.get('tipo_producto')).isdigit():
                    nombre_producto = await repository.get_product_name(int(conductor_data.get('tipo_producto')))
                    if nombre_producto:
                        tipo_producto_nombre = nombre_producto
                
                # Construir respuesta en el mismo formato que antes
                result = {
                    "success": True,
                    "conversation_id": conversation_id,
                    "llamada_info": {
                        "identificador_unico": identificador_unico,  # Nuevo: identificador para save_driver_decision
                        "id_cotizacion": cotizacion_id,
                        "driver_id": identificador_unico  # Mantener compatibilidad con prompt antiguo
                    },
                    "chofer": {
                        "nombre": nombre_conductor,
                        "chofer_id": identificador_unico,  # Usar identificador_unico en lugar de chofer_id
                        "telefono": conductor_data.get('telefono'),
                        "placa": conductor_data.get('placa'),
                        "tipo_vehiculo": conductor_data.get('tipo_vehiculo'),
                        "peso_maximo": float(conductor_data.get('peso_maximo', 0)) if conductor_data.get('peso_maximo') else None,
                        "ciudad_actual": conductor_data.get('ciudad_actual')
                    },
                    "viaje": {
                        "origen": conductor_data.get('ciudad_origen'),
                        "destino": conductor_data.get('ciudad_destino'),
                        "peso_kg": float(conductor_data.get('peso_carga', 0)) if conductor_data.get('peso_carga') else None,
                        "tipo_embalaje": tipo_embalaje_nombre,
                        "tipo_producto": tipo_producto_nombre,
                        "mercancia": conductor_data.get('mercancia', tipo_producto_nombre),
                        "fecha_hora": conductor_data.get('fecha_hora_descargue_cargue'),
                        "vehiculo_requerido": conductor_data.get('vehiculo_requerido')
                    }
                }
                
                # Obtener el flete de la cotización para incluir el precio
                if cotizacion_id:
                    cotizacion = await repository.get_cotizacion_by_id(cotizacion_id)
                    if cotizacion and cotizacion.flete and float(cotizacion.flete) > 0:
                        flete_num = float(cotizacion.flete)
                        result["viaje"]["valor_flete"] = flete_num
                        result["viaje"]["valor_flete_formateado"] = f"${flete_num:,.0f} COP"
                        result["mensaje_para_agente"] = f"El valor del flete para este viaje es de ${flete_num:,.0f} pesos colombianos. Este es el valor que se le paga al conductor por el transporte."
                
                return json.dumps(result, indent=2, ensure_ascii=False)
            
            elif tool_name == "get_cotizaciones":
                page = arguments.get("page", 1)
                limit = arguments.get("limit", 20)
                offset = (page - 1) * limit
                
                cotizaciones = await repository.get_cotizaciones(limit=limit, offset=offset)
                
                result = {
                    "success": True,
                    "total_encontradas": len(cotizaciones),
                    "page": page,
                    "limit": limit,
                    "cotizaciones": [cotizacion.model_dump() for cotizacion in cotizaciones]
                }
                
                return json.dumps(result, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
            
            elif tool_name == "get_vehicle_by_telefono_conductor":
                telefono = arguments.get("telefono")
                if not telefono:
                    return json.dumps({"error": "telefono es requerido"}, ensure_ascii=False)
                
                vehicles = await repository.get_vehicle_by_telefono_conductor(telefono)
                
                result = {
                    "success": True,
                    "telefono_buscado": telefono,
                    "total_encontrados": len(vehicles),
                    "vehiculos": [vehicle.model_dump() for vehicle in vehicles]
                }
                
                return json.dumps(result, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
            
            elif tool_name == "get_chofer_by_placa":
                placa = arguments.get("placa")
                if not placa:
                    return json.dumps({"error": "placa es requerido"}, ensure_ascii=False)
                
                # Limpiar y normalizar la placa
                placa_limpia = placa.strip().upper()
                
                chofer_data = await repository.get_chofer_by_placa(placa_limpia)
                
                if chofer_data:
                    result = {
                        "success": True,
                        "placa_buscada": placa_limpia,
                        "vehiculo_encontrado": True,
                        "registro_id": chofer_data.id,
                        "datos_completos": {
                            "conductor": {
                                "id_registro": chofer_data.id,
                                "nombre": chofer_data.conductor,
                                "tipo_documento": chofer_data.tipodocumentoconducotor,
                                "cedula": chofer_data.cedula,
                                "telefono": chofer_data.telefonoconductor,
                                "direccion": chofer_data.direccion_conductor,
                                "ciudad": chofer_data.ciudad_conductor
                            },
                            "vehiculo": {
                                "placa": chofer_data.placa,
                                "marca": chofer_data.marca,
                                "clase_linea": chofer_data.clase_linea,
                                "modelo": chofer_data.modelo,
                                "clase_vehiculo": chofer_data.clasevehiculo,
                                "ejes": chofer_data.vehiculo_ejes,
                                "capacidad": chofer_data.capacidad,
                                "carroceria": chofer_data.carroceria,
                                "chasis": chofer_data.vehiculo_chasis,
                                "estado": chofer_data.estado
                            },
                            "propietario": {
                                "nombre": chofer_data.propietario,
                                "tipo_documento": chofer_data.tipodocumentopropietario,
                                "documento": chofer_data.documentopropietario,
                                "telefono": chofer_data.telefonopropietario,
                                "direccion": chofer_data.direccion_propietario,
                                "ciudad": chofer_data.ciudad_propietario
                            },
                            "poseedor": {
                                "nombre": chofer_data.poseedor,
                                "tipo_documento": chofer_data.tipodocumentoposeedor,
                                "documento": chofer_data.documentoposeedor,
                                "telefono": chofer_data.telefonoposeedor,
                                "direccion": chofer_data.direccion_poseedor,
                                "ciudad": chofer_data.ciudad_poseedor
                            }
                        }
                    }
                else:
                    result = {
                        "success": False,
                        "placa_buscada": placa_limpia,
                        "vehiculo_encontrado": False,
                        "error": f"No se encontró ningún vehículo con la placa: {placa_limpia}",
                        "sugerencia": "Verifique que la placa esté correctamente escrita y completa"
                    }
                
                return json.dumps(result, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
            
            elif tool_name == "save_driver_decision":
                # Obtener parámetros
                identificador_unico = arguments.get("identificador_unico")
                conversation_id = arguments.get("conversation_id")
                decision = arguments.get("decision")
                notas = arguments.get("notas")
                
                # Validación de parámetros requeridos
                if not identificador_unico or decision is None:
                    return json.dumps({
                        "success": False,
                        "error": "Se requieren los parámetros: identificador_unico y decision"
                    }, ensure_ascii=False)
                
                # Validar que decision sea 0 o 1
                if decision not in [0, 1]:
                    return json.dumps({
                        "success": False,
                        "error": "El parámetro 'decision' debe ser 0 (rechaza) o 1 (acepta)"
                    }, ensure_ascii=False)
                
                try:
                    # Primero verificamos que el conductor exista en llamadas_conductores
                    query_verify = "SELECT id, nombre_conductor, cotizacion_id FROM llamadas_conductores WHERE identificador_unico = %s AND deleted_at IS NULL"
                    result_verify = await repository.db.execute_query(query_verify, (identificador_unico,))
                    
                    if not result_verify:
                        return json.dumps({
                            "success": False,
                            "error": f"No se encontró conductor con identificador_unico: {identificador_unico}"
                        }, ensure_ascii=False)
                    
                    conductor_data = result_verify[0]
                    nombre_conductor = conductor_data.get('nombre_conductor', 'Conductor')
                    cotizacion_id = conductor_data.get('cotizacion_id')
                    
                    # Usar el método que actualiza llamadas_conductores
                    success = await repository.save_driver_decision_new(
                        identificador_unico=identificador_unico,
                        conversation_id=conversation_id or "N/A",
                        decision=decision,
                        notas=notas
                    )
                    
                    if success:
                        decision_text = "acepta" if decision == 1 else "rechaza"
                        estado_llamada = "completada" if decision == 1 else "fallida"
                        
                        result = {
                            "success": True,
                            "message": f"Decisión guardada correctamente: {nombre_conductor} {decision_text} la cotización",
                            "data": {
                                "identificador_unico": identificador_unico,
                                "nombre_conductor": nombre_conductor,
                                "cotizacion_id": cotizacion_id,
                                "decision": decision,
                                "decision_text": decision_text,
                                "estado_llamada": estado_llamada,
                                "conversation_id": conversation_id,
                                "notas": notas
                            }
                        }
                    else:
                        result = {
                            "success": False,
                            "error": "No se pudo guardar la decisión del conductor en la base de datos"
                        }
                    
                    return json.dumps(result, indent=2, ensure_ascii=False)
                    
                except Exception as e:
                    logger.error(f"Error guardando decisión del conductor: {e}")
                    return json.dumps({
                        "success": False,
                        "error": f"Error interno al guardar la decisión: {str(e)}"
                    }, ensure_ascii=False)
            
            elif tool_name == "process_elevenlabs_event":
                return await self._process_elevenlabs_event(arguments)
            
            elif tool_name == "get_group_cotizations":
                limit = arguments.get("limit", 100)
                offset = arguments.get("offset", 0)
                
                groups = await repository.get_group_cotizations(limit, offset)
                result = {
                    "success": True,
                    "total_encontrados": len(groups),
                    "groups": [group.model_dump() for group in groups]
                }
                
                return json.dumps(result, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
            
            elif tool_name == "get_group_cotizations_by_user":
                user_id = arguments.get("user_id")
                if not user_id:
                    return json.dumps({"error": "user_id es requerido"}, ensure_ascii=False)
                
                groups = await repository.get_group_cotizations_by_user(user_id)
                result = {
                    "success": True,
                    "user_id": user_id,
                    "total_encontrados": len(groups),
                    "groups": [group.model_dump() for group in groups]
                }
                
                return json.dumps(result, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
            
            elif tool_name == "get_cotizacion_with_group_info":
                cotizacion_id = arguments.get("cotizacion_id")
                if not cotizacion_id:
                    return json.dumps({"error": "cotizacion_id es requerido"}, ensure_ascii=False)
                
                info = await repository.get_cotizacion_with_group_info(cotizacion_id)
                result = {
                    "success": True,
                    "cotizacion_id": cotizacion_id,
                    "info": info
                }
                
                return json.dumps(result, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
            
            elif tool_name == "get_cotizations_by_group":
                group_id = arguments.get("group_id")
                if not group_id:
                    return json.dumps({"error": "group_id es requerido"}, ensure_ascii=False)
                
                cotizations = await repository.get_cotizations_by_group(group_id)
                result = {
                    "success": True,
                    "group_id": group_id,
                    "total_encontradas": len(cotizations),
                    "cotizations": [cot.model_dump() for cot in cotizations]
                }
                
                return json.dumps(result, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
            
            elif tool_name == "get_pricings":
                limit = arguments.get("limit", 100)
                offset = arguments.get("offset", 0)
                
                pricings = await repository.get_pricings(limit, offset)
                result = {
                    "success": True,
                    "total_encontrados": len(pricings),
                    "pricings": [pricing.model_dump() for pricing in pricings]
                }
                
                return json.dumps(result, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
            
            elif tool_name == "get_pricing_by_vehicle_type":
                vehicle_type = arguments.get("vehicle_type")
                if not vehicle_type:
                    return json.dumps({"error": "vehicle_type es requerido"}, ensure_ascii=False)
                
                pricings = await repository.get_pricing_by_vehicle_type(vehicle_type)
                result = {
                    "success": True,
                    "vehicle_type": vehicle_type,
                    "total_encontrados": len(pricings),
                    "pricings": [pricing.model_dump() for pricing in pricings]
                }
                
                return json.dumps(result, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
            
            elif tool_name == "search_pricings_by_route":
                origin = arguments.get("origin")
                destination = arguments.get("destination")
                
                pricings = await repository.search_pricings_by_route(origin, destination)
                result = {
                    "success": True,
                    "search_criteria": {"origin": origin, "destination": destination},
                    "total_encontrados": len(pricings),
                    "pricings": [pricing.model_dump() for pricing in pricings]
                }
                
                return json.dumps(result, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
            
            elif tool_name == "get_cotizacion_with_pricing_info":
                cotizacion_id = arguments.get("cotizacion_id")
                if not cotizacion_id:
                    return json.dumps({"error": "cotizacion_id es requerido"}, ensure_ascii=False)
                
                info = await repository.get_cotizacion_with_pricing_info(cotizacion_id)
                result = {
                    "success": True,
                    "cotizacion_id": cotizacion_id,
                    "info": info
                }
                
                return json.dumps(result, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
            
            elif tool_name == "precioviaje":
                cotizacion_id = arguments.get("cotizacion_id")
                if not cotizacion_id:
                    return json.dumps({"error": "cotizacion_id es requerido"}, ensure_ascii=False)
                
                # Obtenemos la cotización para usar el campo flete como precio del viaje
                cotizacion = await repository.get_cotizacion_by_id(cotizacion_id)
                if not cotizacion:
                    return json.dumps({
                        "success": False,
                        "error": f"No se encontró cotización con ID: {cotizacion_id}"
                    }, ensure_ascii=False)
                
                # El valor que se le paga al conductor es el FLETE
                flete_valor = cotizacion.flete
                if not flete_valor or float(flete_valor) == 0:
                    return json.dumps({
                        "success": False,
                        "error": f"La cotización {cotizacion_id} no tiene valor de flete asignado"
                    }, ensure_ascii=False)
                
                flete_num = float(flete_valor)
                
                # Preparamos la respuesta con el flete como precio del viaje
                result = {
                    "success": True,
                    "cotizacion_id": cotizacion_id,
                    "precio_viaje": flete_num,
                    "precio_viaje_formateado": f"${flete_num:,.0f} COP",
                    "informacion_viaje": {
                        "origen": cotizacion.ciudad_origen,
                        "destino": cotizacion.ciudad_destino,
                        "vehiculo_requerido": cotizacion.vehiculo_requerido,
                        "tipo_carroceria": cotizacion.tipo_carroceria,
                        "peso_mercancia": cotizacion.peso_mercancia
                    },
                    "informacion_cotizacion": {
                        "vehiculo_requerido": cotizacion.vehiculo_requerido,
                        "tipo_carroceria": cotizacion.tipo_carroceria,
                        "ciudad_origen": cotizacion.ciudad_origen,
                        "ciudad_destino": cotizacion.ciudad_destino,
                        "peso_mercancia": cotizacion.peso_mercancia,
                        "tipo_mercancia": cotizacion.tipo_mercancia
                    },
                    "mensaje_chofer": f"El valor del flete del viaje desde {cotizacion.ciudad_origen} hasta {cotizacion.ciudad_destino} es de ${flete_num:,.0f} pesos colombianos"
                }
                
                return json.dumps(result, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
            
            elif tool_name == "zinformacion":
                orden_id = arguments.get("orden_id")
                search = arguments.get("search")
                show_all = arguments.get("show_all", False)
                show_stats = arguments.get("show_stats", False)
                limit = arguments.get("limit", 10)
                
                # Implementación completa de zinformacion similar a Laravel
                try:
                    if orden_id:
                        # Buscar orden específica con información completa
                        cotizacion = await repository.get_cotizacion_by_id(orden_id)
                        if not cotizacion:
                            return json.dumps({
                                "error": f"No se encontró orden con ID: {orden_id}"
                            }, ensure_ascii=False)
                        
                        # Obtener información del grupo si existe
                        grupo_info = None
                        if cotizacion.group_cotizations_id:
                            try:
                                grupo = await repository.get_group_cotization_by_id(cotizacion.group_cotizations_id)
                                if grupo:
                                    grupo_info = {
                                        "tipo": grupo.type,
                                        "referencia": grupo.reference,
                                        "estado": grupo.status
                                    }
                            except:
                                pass
                        
                        # Construir respuesta completa
                        result = {
                            "success": True,
                            "tipo": "orden_detallada",
                            "orden_id": orden_id,
                            "informacion_general": {
                                "id": cotizacion.id,
                                "tipo": grupo_info["tipo"] if grupo_info else "No especificado",
                                "operacion": "No especificado"
                            },
                            "informacion_obligatoria_cotizacion": {
                                "peso_mercancia": cotizacion.peso_mercancia,
                                "cantidad": cotizacion.cantidad,
                                "tipo_embalaje": cotizacion.tipo_embajale,
                                "dimensiones": cotizacion.dimensiones_exactas,
                                "tipo_producto": cotizacion.tipo_producto,
                                "vehiculo_requerido": cotizacion.vehiculo_requerido,
                                "frecuencia": cotizacion.frecuencia,
                                "esquema_seguridad": cotizacion.esquema_seguridad,
                                "tipo_carroceria": cotizacion.tipo_carroceria,
                                "tipo_mercancia": cotizacion.tipo_mercancia
                            },
                            "informacion_estatica_mercancia": {
                                "registro_fotografico": cotizacion.registro_fotografico,
                                "temperatura_mercancia": cotizacion.temperatura_mercancia,
                                "humedad": cotizacion.humedad,
                                "planos": cotizacion.planos
                            },
                            "informacion_carga_descarga": {
                                "fecha_hora_descargue_cargue": cotizacion.fecha_hora_descargue_cargue,
                                "descargue_cargue": cotizacion.descargue_cargue
                            },
                            "grupo_cotizacion": grupo_info if grupo_info else {
                                "tipo": "No especificado",
                                "referencia": "No especificada",
                                "estado": "No especificado"
                            },
                            "ruta": {
                                "origen": cotizacion.ciudad_origen,
                                "destino": cotizacion.ciudad_destino,
                                "dane_origen": cotizacion.ciudad_origen_dane,
                                "dane_destino": cotizacion.ciudad_destino_dane,
                                "ruta": cotizacion.ruta
                            },
                            "informacion_adicional_mercancia": {
                                "valor_declarado": cotizacion.valor_declarado,
                                "valor": cotizacion.valor
                            },
                            "informacion_adicional_vehiculo": {
                                "cantidad_vehiculos": cotizacion.cantidad_vh
                            },
                            "informacion_logistica_adicional": {
                                "seguro": cotizacion.seguro,
                                "ventanas_horarios": cotizacion.ventanas_horarios_recibidos
                            },
                            "comercio_exterior": {
                                "fcl_lcl": cotizacion.fcl_lcl,
                                "devolucion_contenedor": cotizacion.sitio_devolucion_contenedor,
                                "regimen_nacionalizado": cotizacion.regimen_nacionalizado,
                                "agente_aduanas": cotizacion.agente_aduanas,
                                "consolidado_expreso": cotizacion.consolidado_expreso,
                                "numero_documento_bl": cotizacion.numero_documento_bl
                            },
                            "documentacion": {
                                "registro_fotografico": cotizacion.registro_fotografico,
                                "un": cotizacion.un
                            },
                            "sistema": {
                                "estado_silogtran": cotizacion.silogtran_status,
                                "pricing_id": cotizacion.pricing_id,
                                "group_cotizations_id": cotizacion.group_cotizations_id
                            }
                        }
                        
                    elif show_stats:
                        # Mostrar estadísticas básicas
                        cotizaciones = await repository.get_cotizaciones(limit=100, offset=0)
                        total = len(cotizaciones)
                        
                        result = {
                            "success": True,
                            "tipo": "estadisticas",
                            "total_ordenes": total,
                            "mensaje": f"Hay {total} órdenes en el sistema"
                        }
                        
                    elif show_all:
                        # Mostrar todas las órdenes (limitadas)
                        cotizaciones = await repository.get_cotizaciones(limit=limit, offset=0)
                        
                        ordenes = []
                        for cot in cotizaciones:
                            ordenes.append({
                                "id": cot.id,
                                "origen": cot.ciudad_origen,
                                "destino": cot.ciudad_destino,
                                "vehiculo": cot.vehiculo_requerido,
                                "mercancia": cot.tipo_mercancia
                            })
                        
                        result = {
                            "success": True,
                            "tipo": "listado_ordenes",
                            "total_encontradas": len(ordenes),
                            "limit": limit,
                            "ordenes": ordenes
                        }
                        
                    elif search:
                        # Búsqueda básica en cotizaciones
                        cotizaciones = await repository.get_cotizaciones(limit=50, offset=0)
                        search_lower = search.lower()
                        
                        ordenes_filtradas = []
                        for cot in cotizaciones:
                            # Buscar en campos de texto
                            texto_busqueda = f"{cot.ciudad_origen or ''} {cot.ciudad_destino or ''} {cot.tipo_mercancia or ''} {cot.vehiculo_requerido or ''}".lower()
                            if search_lower in texto_busqueda:
                                ordenes_filtradas.append({
                                    "id": cot.id,
                                    "origen": cot.ciudad_origen,
                                    "destino": cot.ciudad_destino,
                                    "vehiculo": cot.vehiculo_requerido,
                                    "mercancia": cot.tipo_mercancia
                                })
                        
                        result = {
                            "success": True,
                            "tipo": "busqueda",
                            "termino_busqueda": search,
                            "total_encontradas": len(ordenes_filtradas),
                            "ordenes": ordenes_filtradas[:limit]
                        }
                        
                    else:
                        # Ayuda por defecto
                        result = {
                            "success": True,
                            "tipo": "ayuda",
                            "mensaje": "Herramienta zinformacion - Consulta información de órdenes",
                            "ejemplos": [
                                "zinformacion(orden_id=31) - Ver orden específica",
                                "zinformacion(search='bogota') - Buscar por ciudad",
                                "zinformacion(show_all=True) - Ver todas las órdenes",
                                "zinformacion(show_stats=True) - Ver estadísticas"
                            ]
                        }
                    
                    return json.dumps(result, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
                    
                except Exception as e:
                    return json.dumps({
                        "success": False,
                        "error": f"Error en zinformacion: {str(e)}"
                    }, ensure_ascii=False)
            
            elif tool_name == "llenar_formulario":
                orden_id = arguments.get("orden_id")
                tipo_formulario = arguments.get("tipo_formulario", "cotizacion")
                
                try:
                    # Obtener información completa de la orden
                    cotizacion = await repository.get_cotizacion_by_id(orden_id)
                    if not cotizacion:
                        return json.dumps({
                            "error": f"No se encontró orden con ID: {orden_id}"
                        }, ensure_ascii=False)
                    
                    # Mapear campos técnicos a valores de formulario
                    formulario_data = {
                        "success": True,
                        "orden_id": orden_id,
                        "tipo_formulario": tipo_formulario,
                        "campos_formulario": {
                            # Paso 1 - Datos básicos
                            "tipo_viaje": self._mapear_tipo_viaje(cotizacion),
                            "moneda": "COP",  # Por defecto pesos colombianos
                            "fuente_solicitud": self._mapear_fuente_solicitud(cotizacion),
                            "tipo_operacion": self._mapear_tipo_operacion(cotizacion),
                            "condicion_despacho": self._mapear_condicion_despacho(cotizacion),
                            "condicion_facturacion": self._mapear_condicion_facturacion(cotizacion),
                            "ciudad_facturacion": cotizacion.ciudad_destino or "Bogotá",
                            "vendedor": "CONALCA",
                            "centro_costo_despacho": self._mapear_centro_costo(cotizacion.ciudad_origen),
                            "cliente": "Cliente por asignar"
                        },
                        "valores_sugeridos": {
                            # Información adicional para el agente
                            "peso_mercancia": cotizacion.peso_mercancia,
                            "cantidad": cotizacion.cantidad,
                            "tipo_embalaje": cotizacion.tipo_embajale,
                            "dimensiones": cotizacion.dimensiones_exactas,
                            "vehiculo_requerido": cotizacion.vehiculo_requerido,
                            "tipo_carroceria": cotizacion.tipo_carroceria,
                            "tipo_mercancia": cotizacion.tipo_mercancia,
                            "origen": cotizacion.ciudad_origen,
                            "destino": cotizacion.ciudad_destino,
                            "ruta": cotizacion.ruta,
                            "valor_declarado": cotizacion.valor_declarado,
                            "frecuencia": cotizacion.frecuencia,
                            "esquema_seguridad": cotizacion.esquema_seguridad
                        },
                        "instrucciones_agente": {
                            "mensaje": "Use estos valores para llenar automáticamente el formulario. Seleccione las opciones más cercanas en los dropdowns.",
                            "campos_obligatorios": [
                                "tipo_viaje", "moneda", "fuente_solicitud", "tipo_operacion",
                                "condicion_despacho", "condicion_facturacion", "ciudad_facturacion",
                                "vendedor", "centro_costo_despacho", "cliente"
                            ],
                            "ejemplo_llenado": f"Para la orden {orden_id}: Ruta {cotizacion.ciudad_origen} -> {cotizacion.ciudad_destino}, Vehículo: {cotizacion.vehiculo_requerido}, Mercancía: {cotizacion.tipo_mercancia}"
                        }
                    }
                    
                    return json.dumps(formulario_data, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
                    
                except Exception as e:
                    return json.dumps({
                        "success": False,
                        "error": f"Error en llenar_formulario: {str(e)}"
                    }, ensure_ascii=False)
            
            elif tool_name == "get_conductor_by_telefono":
                telefono = arguments.get("telefono")
                
                if not telefono:
                    return json.dumps({
                        "error": "El parámetro 'telefono' es requerido"
                    }, ensure_ascii=False)
                
                try:
                    # Limpiar teléfono
                    telefono_limpio = telefono.replace(" ", "").replace("-", "").replace("(", "").replace(")", "")
                    
                    query = """
                    SELECT 
                        id, identificador_unico, cotizacion_id, group_cotization_id,
                        nombre_conductor, telefono, placa, tipo_vehiculo, vehiculo_silogtran,
                        peso_maximo, ciudad_actual, ciudad_origen, ciudad_destino,
                        disponible, score, estado_llamada, call_id, fecha_llamada,
                        respuesta_llamada, notas, mercancia, peso_carga, empaque,
                        datos_adicionales, created_at, updated_at
                    FROM llamadas_conductores
                    WHERE (telefono = %s OR REPLACE(REPLACE(REPLACE(telefono, ' ', ''), '-', ''), '+57', '') = %s)
                        AND deleted_at IS NULL
                    ORDER BY score DESC, created_at DESC
                    """
                    
                    results = await repository.db.execute_query(query, (telefono, telefono_limpio))
                    
                    if not results:
                        return json.dumps({
                            "success": True,
                            "telefono_buscado": telefono,
                            "total_encontrados": 0,
                            "conductores": [],
                            "mensaje": f"No se encontraron conductores con el teléfono {telefono}"
                        }, ensure_ascii=False)
                    
                    conductores = []
                    for row in results:
                        conductores.append({
                            "id": row['id'],
                            "identificador_unico": row['identificador_unico'],
                            "cotizacion_id": row['cotizacion_id'],
                            "group_cotization_id": row['group_cotization_id'],
                            "nombre_conductor": row['nombre_conductor'],
                            "telefono": row['telefono'],
                            "placa": row['placa'],
                            "tipo_vehiculo": row['tipo_vehiculo'],
                            "vehiculo_silogtran": row['vehiculo_silogtran'],
                            "peso_maximo": float(row['peso_maximo']) if row['peso_maximo'] else None,
                            "ciudad_actual": row['ciudad_actual'],
                            "ciudad_origen": row['ciudad_origen'],
                            "ciudad_destino": row['ciudad_destino'],
                            "disponible": bool(row['disponible']),
                            "score": float(row['score']) if row['score'] else 0.0,
                            "estado_llamada": row['estado_llamada'],
                            "call_id": row['call_id'],
                            "fecha_llamada": str(row['fecha_llamada']) if row['fecha_llamada'] else None,
                            "respuesta_llamada": row['respuesta_llamada'],
                            "notas": row['notas'],
                            "mercancia": row['mercancia'],
                            "peso_carga": float(row['peso_carga']) if row['peso_carga'] else None,
                            "empaque": row['empaque'],
                            "datos_adicionales": row['datos_adicionales'],
                            "created_at": str(row['created_at']) if row['created_at'] else None,
                            "updated_at": str(row['updated_at']) if row['updated_at'] else None
                        })
                    
                    return json.dumps({
                        "success": True,
                        "telefono_buscado": telefono,
                        "total_encontrados": len(conductores),
                        "conductores": conductores
                    }, indent=2, ensure_ascii=False)
                    
                except Exception as e:
                    return json.dumps({
                        "success": False,
                        "error": f"Error al buscar conductor por teléfono: {str(e)}"
                    }, ensure_ascii=False)
            
            elif tool_name == "update_conversation_id_conductor":
                identificador_unico = arguments.get("identificador_unico")
                conversation_id = arguments.get("conversation_id")
                estado_llamada = arguments.get("estado_llamada", "en_progreso")
                notas = arguments.get("notas")
                
                if not identificador_unico or not conversation_id:
                    return json.dumps({
                        "error": "Los parámetros 'identificador_unico' y 'conversation_id' son requeridos"
                    }, ensure_ascii=False)
                
                try:
                    # Construir query UPDATE
                    updates = [
                        "call_id = %s",
                        "estado_llamada = %s",
                        "fecha_llamada = NOW()",
                        "updated_at = NOW()"
                    ]
                    params = [conversation_id, estado_llamada]
                    
                    if notas:
                        updates.append("notas = %s")
                        params.append(notas)
                    
                    params.append(identificador_unico)
                    
                    query = f"""
                    UPDATE llamadas_conductores
                    SET {', '.join(updates)}
                    WHERE identificador_unico = %s
                        AND deleted_at IS NULL
                    """
                    
                    affected_rows = await repository.db.execute_update(query, tuple(params))
                    
                    if affected_rows > 0:
                        return json.dumps({
                            "success": True,
                            "identificador_unico": identificador_unico,
                            "conversation_id": conversation_id,
                            "estado_llamada": estado_llamada,
                            "mensaje": f"Conversation ID actualizado exitosamente para el conductor {identificador_unico}"
                        }, ensure_ascii=False)
                    else:
                        return json.dumps({
                            "success": False,
                            "error": f"No se encontró conductor con identificador_unico: {identificador_unico}"
                        }, ensure_ascii=False)
                    
                except Exception as e:
                    return json.dumps({
                        "success": False,
                        "error": f"Error al actualizar conversation_id: {str(e)}"
                    }, ensure_ascii=False)
            
            else:
                return json.dumps({"error": f"Herramienta '{tool_name}' no encontrada"}, ensure_ascii=False)
                
        except Exception as e:
            logger.error(f"Error ejecutando herramienta {tool_name}: {e}")
            return json.dumps({"error": f"Error ejecutando herramienta: {str(e)}"}, ensure_ascii=False)
    
    async def _process_elevenlabs_event(self, event_data: Dict[str, Any]) -> str:
        """Procesa eventos de ElevenLabs"""
        try:
            event_type = event_data.get("event_type", "unknown")
            conversation_id = event_data.get("conversation_id")
            phone_number = event_data.get("phone_number")
            
            if event_type == "conversation_started":
                if phone_number:
                    llamada_id = await repository.create_llamada(
                        telefono=phone_number,
                        estado="en_curso",
                        observaciones=f"Conversación ElevenLabs iniciada - ID: {conversation_id}"
                    )
                    
                    result = {
                        "success": True,
                        "action": "conversation_started",
                        "llamada_id": llamada_id,
                        "phone_number": phone_number,
                        "conversation_id": conversation_id
                    }
                else:
                    result = {
                        "success": False,
                        "error": "phone_number requerido para conversation_started"
                    }
            
            elif event_type == "conversation_ended":
                duration = event_data.get("duration", 0)
                
                if phone_number:
                    # Buscar la llamada más reciente para este teléfono
                    llamadas = await repository.get_llamadas(limit=1, offset=0)
                    if llamadas:
                        await repository.update_llamada_status(llamadas[0].id, "completada")
                        
                        result = {
                            "success": True,
                            "action": "conversation_ended",
                            "llamada_id": llamadas[0].id,
                            "phone_number": phone_number,
                            "duration": duration
                        }
                    else:
                        result = {
                            "success": False,
                            "error": "No se encontró llamada activa para finalizar"
                        }
                else:
                    result = {
                        "success": False,
                        "error": "phone_number requerido para conversation_ended"
                    }
            
            elif event_type == "call_status_change":
                new_status = event_data.get("status", "unknown")
                
                if phone_number:
                    llamadas = await repository.get_llamadas(limit=1, offset=0)
                    if llamadas:
                        await repository.update_llamada_status(llamadas[0].id, new_status)
                        
                        result = {
                            "success": True,
                            "action": "status_updated",
                            "llamada_id": llamadas[0].id,
                            "phone_number": phone_number,
                            "new_status": new_status
                        }
                    else:
                        result = {
                            "success": False,
                            "error": "No se encontró llamada para actualizar estado"
                        }
                else:
                    result = {
                        "success": False,
                        "error": "phone_number requerido para call_status_change"
                    }
            
            else:
                result = {
                    "success": True,
                    "action": "event_logged",
                    "event_type": event_type,
                    "message": f"Evento {event_type} procesado pero sin acción específica"
                }
            
            return json.dumps(result, indent=2, ensure_ascii=False, cls=DateTimeEncoder)
            
        except Exception as e:
            logger.error(f"Error procesando evento ElevenLabs: {e}")
            return json.dumps({
                "success": False,
                "error": f"Error procesando evento: {str(e)}"
            }, ensure_ascii=False)

    def _generar_mensaje_agente(self, llamada, cotizacion_info, chofer_info) -> str:
        """Genera un mensaje personalizado para el agente basado en los datos encontrados"""
        mensaje = f"He encontrado la información de tu llamada (ID: {llamada.id_llamada})."
        
        if llamada.id_cotizacion and llamada.id_cotizacion > 0:
            mensaje += f" Tienes una cotización asociada con ID: {llamada.id_cotizacion}."
            if cotizacion_info:
                mensaje += f" La cotización es para un {cotizacion_info.get('modelo', 'vehículo')} marca {cotizacion_info.get('marca', 'N/A')}."
        
        if llamada.chofer_id and llamada.chofer_id > 0:
            mensaje += f" El chofer asignado tiene ID: {llamada.chofer_id}."
            if chofer_info and chofer_info['conductor']:
                mensaje += f" Es {chofer_info['conductor']} con cédula {chofer_info['cedula']}."
                if chofer_info.get('placa'):
                    mensaje += f" Maneja el vehículo con placa {chofer_info['placa']}."
        
        if llamada.numero_destino:
            mensaje += f" El número de destino de esta llamada es {llamada.numero_destino}."
            
        mensaje += " ¿En qué más puedo ayudarte con esta información?"
        
        return mensaje

    def _generar_mensaje_transporte(self, nombre_chofer: str, cotizacion, tipo_embalaje_nombre: str = None, tipo_producto_nombre: str = None) -> str:
        """Genera el mensaje personalizado de oferta de transporte para Natalia Álvarez"""
        
        # Formatear el nombre del chofer
        nombre_formateado = nombre_chofer.title() if nombre_chofer != "estimado cliente" else "estimado cliente"
        
        # Extraer datos de la cotización con valores por defecto
        ciudad_origen = cotizacion.ciudad_origen or "ciudad de origen"
        ciudad_destino = cotizacion.ciudad_destino or "ciudad de destino"
        peso_mercancia = cotizacion.peso_mercancia or "peso no especificado"
        
        # Usar los nombres proporcionados o valores por defecto
        tipo_embalaje = tipo_embalaje_nombre or cotizacion.tipo_embajale or "embalaje estándar"
        tipo_producto = tipo_producto_nombre or cotizacion.tipo_producto or "producto"
        fecha_original = cotizacion.fecha_hora_descargue_cargue or "fecha por coordinar"
        
        # Convertir fecha a formato completo con nombre del mes
        fecha_descargue = self._formatear_fecha_completa(fecha_original)
        
        # Construir el mensaje según el formato solicitado
        mensaje = f"""Me gustaría, señor {nombre_formateado}, ofrecerle un transporte de carga que se realizará desde {ciudad_origen.title()} hasta {ciudad_destino.title()}, con un peso total de {peso_mercancia} kg. Su tipo de embalaje es {tipo_embalaje}, el cual es {tipo_producto}.

Espero me pueda decir si le interesa realizar este transporte que se debe realizar el día {fecha_descargue}.

¿Estaría disponible para este servicio?"""
        
        return mensaje

    def _formatear_fecha_completa(self, fecha_str: str) -> str:
        """Convierte una fecha en formato DD-MM-YYYY a formato completo con nombre del mes"""
        if not fecha_str or fecha_str == "fecha por coordinar":
            return "fecha por coordinar"
        
        try:
            from datetime import datetime
            import locale
            
            # Diccionario de meses en español
            meses = {
                1: "enero", 2: "febrero", 3: "marzo", 4: "abril",
                5: "mayo", 6: "junio", 7: "julio", 8: "agosto",
                9: "septiembre", 10: "octubre", 11: "noviembre", 12: "diciembre"
            }
            
            # Intentar diferentes formatos de fecha
            formatos = ["%d-%m-%Y", "%Y-%m-%d", "%d/%m/%Y", "%Y/%m/%d"]
            
            fecha_obj = None
            for formato in formatos:
                try:
                    fecha_obj = datetime.strptime(fecha_str, formato)
                    break
                except ValueError:
                    continue
            
            if fecha_obj:
                dia = fecha_obj.day
                mes = meses[fecha_obj.month]
                año = fecha_obj.year
                return f"{dia} de {mes} de {año}"
            else:
                return fecha_str  # Devolver fecha original si no se puede parsear
                
        except Exception as e:
            return fecha_str  # En caso de error, devolver fecha original

    def _register_tools(self):
        """Registra herramientas MCP para compatibilidad STDIO"""
        pass  # Las herramientas se manejan via JSON-RPC ahora
    
    def _mapear_tipo_viaje(self, cotizacion):
        """Mapea información de cotización a tipo de viaje"""
        if cotizacion.fcl_lcl and cotizacion.fcl_lcl.upper() == "FCL":
            return "IMPORT/EXPORT"
        elif cotizacion.ciudad_origen and cotizacion.ciudad_destino:
            if cotizacion.ciudad_origen.lower() != cotizacion.ciudad_destino.lower():
                return "INTERCITY"
        return "LOCAL"
    
    def _mapear_fuente_solicitud(self, cotizacion):
        """Mapea fuente de la solicitud"""
        if cotizacion.group_cotizations_id:
            return "COTIZACION_GRUPAL"
        return "INDIVIDUAL"
    
    def _mapear_tipo_operacion(self, cotizacion):
        """Mapea tipo de operación basado en la mercancía"""
        tipo_mercancia = (cotizacion.tipo_mercancia or "").lower()
        if "granel" in tipo_mercancia:
            return "GRANEL"
        elif "liquido" in tipo_mercancia:
            return "LIQUIDOS"
        elif "contenedor" in tipo_mercancia or cotizacion.fcl_lcl:
            return "CONTENEDORES"
        else:
            return "CARGA_GENERAL"
    
    def _mapear_condicion_despacho(self, cotizacion):
        """Mapea condición de despacho"""
        if cotizacion.descargue_cargue:
            if cotizacion.descargue_cargue == "1":
                return "ENTREGA_INMEDIATA"
            else:
                return "PROGRAMADO"
        return "PROGRAMADO"
    
    def _mapear_condicion_facturacion(self, cotizacion):
        """Mapea condición de facturación"""
        if cotizacion.valor_declarado:
            try:
                valor = float(cotizacion.valor_declarado)
                if valor > 1000000:  # Mayor a 1 millón
                    return "CREDITO_30_DIAS"
                else:
                    return "CONTADO"
            except:
                pass
        return "CONTADO"
    
    def _mapear_centro_costo(self, ciudad_origen):
        """Mapea centro de costo según ciudad de origen"""
        if not ciudad_origen:
            return "BOGOTA"
        
        ciudad = ciudad_origen.lower()
        if "bogota" in ciudad or "bogotá" in ciudad:
            return "BOGOTA"
        elif "medellin" in ciudad or "medellín" in ciudad:
            return "MEDELLIN"
        elif "cali" in ciudad:
            return "CALI"
        elif "barranquilla" in ciudad:
            return "BARRANQUILLA"
        elif "cartagena" in ciudad:
            return "CARTAGENA"
        else:
            return "OTROS"
    
    async def _get_tools_list(self):
        """Retorna lista completa de herramientas disponibles"""
        return [
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
            },
            # ... (puedes agregar todas las demás herramientas aquí, o mejor aún...)
        ]
    
    # MÉTODO DUPLICADO - COMENTADO PORQUE YA EXISTE UNO FUNCIONAL EN LÍNEA 1449
    # async def _execute_tool(self, tool_name: str, arguments: dict):
    #     """Ejecuta una herramienta específica con los argumentos dados"""
    #     logger.info(f"Ejecutando herramienta: {tool_name} con args: {arguments}")
    #     
    #     # Mapeo de herramientas a métodos del repositorio
    #     tool_methods = {
    #         "get_llamadas": repository.get_llamadas,
    #         "get_llamada_by_id": repository.get_llamada_by_id,
    #         "create_llamada": repository.create_llamada,
    #         "update_llamada_status": repository.update_llamada_status,
    #         "get_cotizaciones": repository.get_cotizaciones,
    #         "get_vehicle_by_telefono_conductor": repository.get_vehicle_by_telefono_conductor,
    #         "process_elevenlabs_event": repository.process_elevenlabs_event,
    #         "get_chofer_by_placa": repository.get_chofer_by_placa,
    #         "get_llamadas_by_telefono": repository.get_llamadas_by_telefono,
    #         "activate_conversation": repository.activate_conversation,
    #         "generate_transport_offer": repository.generate_transport_offer,
    #         "save_driver_decision": repository.save_driver_decision,
    #         "get_group_cotizations": repository.get_group_cotizations,
    #         "get_group_cotizations_by_user": repository.get_group_cotizations_by_user,
    #         "get_cotizacion_with_group_info": repository.get_cotizacion_with_group_info,
    #         "get_cotizations_by_group": repository.get_cotizations_by_group,
    #         "get_pricings": repository.get_pricings,
    #         "get_pricing_by_vehicle_type": repository.get_pricing_by_vehicle_type,
    #         "search_pricings_by_route": repository.search_pricings_by_route,
    #         "get_cotizacion_with_pricing_info": repository.get_cotizacion_with_pricing_info,
    #         "precioviaje": repository.precioviaje,
    #         "zinformacion": repository.zinformacion,
    #         "llenar_formulario": repository.llenar_formulario,
    #     }
    #     
    #     if tool_name not in tool_methods:
    #         raise ValueError(f"Herramienta no encontrada: {tool_name}")
    #     
    #     # Ejecutar el método correspondiente
    #     method = tool_methods[tool_name]
    #     result = await method(**arguments)
    #     
    #     return result
    
    async def close(self):
        """Cierra las conexiones del servidor"""
        try:
            await db_connection.close()
            logger.info("Servidor cerrado correctamente")
        except Exception as e:
            logger.error(f"Error al cerrar servidor: {e}")

# Función para iniciar el servidor HTTP
async def run_http_server(host: str = "127.0.0.1", port: int = 18840, root_path: str = ""):
    """Inicia el servidor HTTP con protocolo MCP"""
    try:
        if sys.platform != 'win32':
            uvloop.install()
        
        mcp_server = ConalcaMCPServer(root_path=root_path)
        await mcp_server.initialize()
        
        config = uvicorn.Config(
            app=mcp_server.app,
            host=host,
            port=port,
            log_level="info",
            access_log=True,
            loop="uvloop" if sys.platform != 'win32' else "asyncio"
        )
        
        server = uvicorn.Server(config)
        
        logger.info(f"Iniciando servidor MCP en {host}:{port}")
        logger.info(f"Root path: {root_path}")
        logger.info("Protocolo: MCP JSON-RPC 2.0 compatible con ElevenLabs")
        
        await server.serve()
        
    except Exception as e:
        logger.error(f"Error al iniciar servidor: {e}")
        raise
    finally:
        await mcp_server.close()

# Función principal para STDIO
async def main():
    """Función principal para protocolo MCP via STDIO"""
    try:
        if sys.platform != 'win32':
            uvloop.install()
        
        mcp_server = ConalcaMCPServer()
        await mcp_server.initialize()
        
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
        port = int(sys.argv[2]) if len(sys.argv) > 2 else 18840
        root_path = sys.argv[3] if len(sys.argv) > 3 else ""
        asyncio.run(run_http_server(port=port, root_path=root_path))
    else:
        asyncio.run(main())