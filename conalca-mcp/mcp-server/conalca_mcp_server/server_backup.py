import asyncio
import logging
import sys
from typing import Any, Dict, List, Optional
import uvloop
import json
from datetime import datetime
from fastapi import FastAPI, Request, HTTPException
from fastapi.responses import JSONResponse
from fastapi.middleware.cors import CORSMiddleware
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

class ConalcaMCPServer:
    """Servidor MCP principal para Conalca con capacidades de streaming"""
    
    def __init__(self, root_path: str = ""):
        self.server = Server("conalca-mcp-server")
        self.streaming_clients = set()
        self.root_path = root_path
        
        # Crear FastAPI app para webhooks y endpoints HTTP
        self.app = FastAPI(
            title="Conalca MCP Server",
            description="Servidor MCP streameable para ElevenLabs y base de datos Conalca",
            version="1.0.0",
            root_path=root_path
        )
        
        # Configurar CORS
        self.app.add_middleware(
            CORSMiddleware,
            allow_origins=["*"],
            allow_credentials=True,
            allow_methods=["*"],
            allow_headers=["*"],
        )
        
        # Registrar endpoints HTTP
        self._register_http_endpoints()
        
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
                    "version": "1.0.0"
                }
            except Exception as e:
                logger.error(f"Health check failed: {e}")
                raise HTTPException(status_code=503, detail="Service unhealthy")
        
        @self.app.post("/webhook/elevenlabs")
        async def elevenlabs_webhook(request: Request):
            """Maneja webhooks de ElevenLabs"""
            try:
                data = await request.json()
                logger.info(f"Webhook recibido de ElevenLabs: {data}")
                
                # Procesar el webhook según el tipo de evento
                event_type = data.get("event_type", "unknown")
                
                if event_type == "conversation_started":
                    await self._handle_conversation_started(data)
                elif event_type == "conversation_ended":
                    await self._handle_conversation_ended(data)
                elif event_type == "call_status_change":
                    await self._handle_call_status_change(data)
                
                return {"status": "success", "processed": True}
                
            except Exception as e:
                logger.error(f"Error procesando webhook de ElevenLabs: {e}")
                raise HTTPException(status_code=500, detail=str(e))
        
        @self.app.get("/")
        async def root():
            """Endpoint raíz con información del servidor"""
            return {
                "service": "Conalca MCP Server",
                "version": "1.0.0",
                "status": "running",
                "endpoints": {
                    "health": f"{self.root_path}/health",
                    "webhook": f"{self.root_path}/webhook/elevenlabs",
                    "tools": "Available via MCP protocol"
                }
            }
    
    def _register_tools(self):
        """Registra todas las herramientas MCP"""
        
        @self.server.call_tool()
        async def get_llamadas(page: int = 1, limit: int = 20, status: Optional[str] = None) -> List[TextContent]:
            """Obtiene todas las llamadas con paginación opcional"""
            try:
                llamadas = await repository.get_llamadas(page=page, limit=limit, status=status)
                return [TextContent(
                    type="text",
                    text=json.dumps({
                        "total": len(llamadas),
                        "page": page,
                        "limit": limit,
                        "llamadas": [llamada.model_dump() for llamada in llamadas]
                    }, indent=2, ensure_ascii=False)
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
                if llamada:
                    return [TextContent(
                        type="text",
                        text=json.dumps(llamada.model_dump(), indent=2, ensure_ascii=False)
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
                cotizaciones = await repository.get_cotizaciones(page=page, limit=limit, status=status)
                return [TextContent(
                    type="text",
                    text=json.dumps({
                        "total": len(cotizaciones),
                        "page": page,
                        "limit": limit,
                        "cotizaciones": [cotizacion.model_dump() for cotizacion in cotizaciones]
                    }, indent=2, ensure_ascii=False)
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
                return [TextContent(
                    type="text",
                    text=json.dumps({
                        "total": len(vehicles),
                        "telefono_buscado": telefono,
                        "vehiculos": [vehicle.model_dump() for vehicle in vehicles]
                    }, indent=2, ensure_ascii=False)
                )]
            except Exception as e:
                logger.error(f"Error al buscar vehículos por teléfono {telefono}: {e}")
                return [TextContent(
                    type="text",
                    text=f"Error al buscar vehículos: {str(e)}"
                )]

        @self.server.call_tool()
        async def elevenlabs_webhook_handler(event_data: Dict[str, Any]) -> List[TextContent]:
            """Maneja eventos de webhooks de ElevenLabs"""
            try:
                event_type = event_data.get("event_type", "unknown")
                
                # Log del evento
                logger.info(f"Procesando evento de ElevenLabs: {event_type}")
                
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
                    }, indent=2, ensure_ascii=False)
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
                    description="Acceso a la tabla de llamadas telefónicas",
                    mimeType="application/json"
                ),
                Resource(
                    uri="conalca://database/cotizaciones",
                    name="Cotizaciones Database",
                    description="Acceso a la tabla de modelos de cotización",
                    mimeType="application/json"
                ),
                Resource(
                    uri="conalca://database/vehicles",
                    name="Vehicles Database",
                    description="Acceso a la tabla de vehículos, propietarios y conductores",
                    mimeType="application/json"
                )
            ]
    
    async def _handle_conversation_started(self, data: Dict[str, Any]) -> str:
        """Maneja el inicio de una conversación"""
        try:
            # Extraer información relevante
            conversation_id = data.get("conversation_id")
            phone_number = data.get("phone_number", "unknown")
            
            # Crear una nueva llamada en la base de datos
            if phone_number != "unknown":
                await repository.create_llamada(
                    telefono=phone_number,
                    estado="en_curso",
                    observaciones=f"Conversación iniciada - ID: {conversation_id}"
                )
            
            return f"Conversación iniciada para {phone_number}"
            
        except Exception as e:
            logger.error(f"Error manejando inicio de conversación: {e}")
            return f"Error: {str(e)}"
    
    async def _handle_conversation_ended(self, data: Dict[str, Any]) -> str:
        """Maneja el fin de una conversación"""
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
            
            return f"Conversación terminada para {phone_number} - Duración: {duration}s"
            
        except Exception as e:
            logger.error(f"Error manejando fin de conversación: {e}")
            return f"Error: {str(e)}"
    
    async def _handle_call_status_change(self, data: Dict[str, Any]) -> str:
        """Maneja cambios de estado de llamada"""
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
            
            return f"Estado actualizado a {new_status} para {phone_number}"
            
        except Exception as e:
            logger.error(f"Error manejando cambio de estado: {e}")
            return f"Error: {str(e)}"
    
    async def close(self):
        """Cierra las conexiones del servidor"""
        try:
            await db_connection.close()
            logger.info("Servidor cerrado correctamente")
        except Exception as e:
            logger.error(f"Error al cerrar servidor: {e}")

# Función para iniciar el servidor con FastAPI
async def run_http_server(host: str = "127.0.0.1", port: int = 8000, root_path: str = ""):
    """Inicia el servidor HTTP con FastAPI"""
    try:
        # Usar uvloop para mejor rendimiento en Linux
        if sys.platform != 'win32':
            uvloop.install()
        
        # Crear e inicializar el servidor
        mcp_server = ConalcaMCPServer(root_path=root_path)
        await mcp_server.initialize()
        
        # Configurar uvicorn
        config = uvicorn.Config(
            app=mcp_server.app,
            host=host,
            port=port,
            log_level="info",
            access_log=True,
            loop="uvloop" if sys.platform != 'win32' else "asyncio"
        )
        
        server = uvicorn.Server(config)
        
        logger.info(f"Iniciando servidor HTTP en {host}:{port}")
        if root_path:
            logger.info(f"Root path configurado: {root_path}")
        
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
        port = int(sys.argv[2]) if len(sys.argv) > 2 else 8000
        root_path = sys.argv[3] if len(sys.argv) > 3 else ""
        asyncio.run(run_http_server(port=port, root_path=root_path))
    else:
        # Modo STDIO estándar MCP
        asyncio.run(main())