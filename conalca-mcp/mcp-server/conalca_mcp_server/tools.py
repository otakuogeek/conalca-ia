from typing import Any, Dict, List, Optional
from mcp.server import Server
from mcp.types import Tool, TextContent
import json
import asyncio
from .models import repository, LlamadaModel, CotizacionModel, VehicleOwnerHolderDriverModel, GroupCotizationsModel, PricingModel

class MCPTools:
    """Herramientas MCP para interactuar con las tablas de la base de datos"""
    
    def __init__(self, server: Server):
        self.server = server
        self.repository = repository
        self._register_tools()
    
    def _register_tools(self):
        """Registra todas las herramientas MCP"""
        
        # Herramientas para tabla llamadas
        @self.server.tool(
            name="get_llamadas",
            description="Obtiene una lista de llamadas con paginación. Útil para consultar el historial de llamadas."
        )
        async def get_llamadas(limit: int = 100, offset: int = 0) -> List[TextContent]:
            """Obtiene llamadas con paginación"""
            try:
                llamadas = await self.repository.get_llamadas(limit, offset)
                result = []
                for llamada in llamadas:
                    result.append({
                        "id_llamada": llamada.id_llamada,
                        "id_cotizacion": llamada.id_cotizacion,
                        "chofer_id": llamada.chofer_id,
                        "status": llamada.status,
                        "elevenlabs_conversation_id": llamada.elevenlabs_conversation_id,
                        "elevenlabs_sip_call_id": llamada.elevenlabs_sip_call_id,
                        "call_started_at": str(llamada.call_started_at) if llamada.call_started_at else None,
                        "call_ended_at": str(llamada.call_ended_at) if llamada.call_ended_at else None,
                        "call_notes": llamada.call_notes,
                        "created_at": str(llamada.created_at) if llamada.created_at else None,
                        "updated_at": str(llamada.updated_at) if llamada.updated_at else None
                    })
                
                return [TextContent(
                    type="text",
                    text=f"Encontradas {len(result)} llamadas:\n{json.dumps(result, indent=2, ensure_ascii=False)}"
                )]
            except Exception as e:
                return [TextContent(type="text", text=f"Error al obtener llamadas: {str(e)}")]
        
        @self.server.tool(
            name="get_llamada_by_id",
            description="Obtiene una llamada específica por su ID"
        )
        async def get_llamada_by_id(id_llamada: int) -> List[TextContent]:
            """Obtiene una llamada por ID"""
            try:
                llamada = await self.repository.get_llamada_by_id(id_llamada)
                if not llamada:
                    return [TextContent(type="text", text=f"No se encontró la llamada con ID {id_llamada}")]
                
                result = {
                    "id_llamada": llamada.id_llamada,
                    "id_cotizacion": llamada.id_cotizacion,
                    "chofer_id": llamada.chofer_id,
                    "status": llamada.status,
                    "elevenlabs_conversation_id": llamada.elevenlabs_conversation_id,
                    "elevenlabs_sip_call_id": llamada.elevenlabs_sip_call_id,
                    "call_started_at": str(llamada.call_started_at) if llamada.call_started_at else None,
                    "call_ended_at": str(llamada.call_ended_at) if llamada.call_ended_at else None,
                    "call_notes": llamada.call_notes,
                    "created_at": str(llamada.created_at) if llamada.created_at else None,
                    "updated_at": str(llamada.updated_at) if llamada.updated_at else None
                }
                
                return [TextContent(
                    type="text",
                    text=f"Llamada encontrada:\n{json.dumps(result, indent=2, ensure_ascii=False)}"
                )]
            except Exception as e:
                return [TextContent(type="text", text=f"Error al obtener la llamada: {str(e)}")]
        
        @self.server.tool(
            name="update_llamada_status",
            description="Actualiza el estado y notas de una llamada"
        )
        async def update_llamada_status(id_llamada: int, status: str, call_notes: str = None) -> List[TextContent]:
            """Actualiza el status de una llamada"""
            try:
                success = await self.repository.update_llamada_status(id_llamada, status, call_notes)
                if success:
                    return [TextContent(
                        type="text",
                        text=f"Llamada {id_llamada} actualizada exitosamente. Nuevo status: {status}"
                    )]
                else:
                    return [TextContent(
                        type="text",
                        text=f"No se pudo actualizar la llamada {id_llamada}. Verifique que exista."
                    )]
            except Exception as e:
                return [TextContent(type="text", text=f"Error al actualizar la llamada: {str(e)}")]
        
        @self.server.tool(
            name="create_llamada",
            description="Crea una nueva llamada"
        )
        async def create_llamada(id_cotizacion: int, chofer_id: int, status: str = 'pendiente') -> List[TextContent]:
            """Crea una nueva llamada"""
            try:
                new_id = await self.repository.create_llamada(id_cotizacion, chofer_id, status)
                if new_id > 0:
                    return [TextContent(
                        type="text",
                        text=f"Nueva llamada creada exitosamente con ID: {new_id}"
                    )]
                else:
                    return [TextContent(type="text", text="No se pudo crear la llamada")]
            except Exception as e:
                return [TextContent(type="text", text=f"Error al crear la llamada: {str(e)}")]
        
        # Herramientas para tabla cotizacion_models
        @self.server.tool(
            name="get_cotizaciones",
            description="Obtiene una lista de cotizaciones con paginación"
        )
        async def get_cotizaciones(limit: int = 100, offset: int = 0) -> List[TextContent]:
            """Obtiene cotizaciones con paginación"""
            try:
                cotizaciones = await self.repository.get_cotizaciones(limit, offset)
                result = []
                for cotizacion in cotizaciones:
                    result.append({
                        "id": cotizacion.id,
                        "pricing_id": cotizacion.pricing_id,
                        "ciudad_origen": cotizacion.ciudad_origen,
                        "ciudad_destino": cotizacion.ciudad_destino,
                        "peso_mercancia": cotizacion.peso_mercancia,
                        "vehiculo_requerido": cotizacion.vehiculo_requerido,
                        "ruta": cotizacion.ruta,
                        "valor": cotizacion.valor,
                        "tipo_mercancia": cotizacion.tipo_mercancia,
                        "silogtran_status": cotizacion.silogtran_status
                    })
                
                return [TextContent(
                    type="text",
                    text=f"Encontradas {len(result)} cotizaciones:\n{json.dumps(result, indent=2, ensure_ascii=False)}"
                )]
            except Exception as e:
                return [TextContent(type="text", text=f"Error al obtener cotizaciones: {str(e)}")]
        
        @self.server.tool(
            name="get_cotizacion_by_id",
            description="Obtiene una cotización específica por su ID"
        )
        async def get_cotizacion_by_id(id_cotizacion: int) -> List[TextContent]:
            """Obtiene una cotización por ID"""
            try:
                cotizacion = await self.repository.get_cotizacion_by_id(id_cotizacion)
                if not cotizacion:
                    return [TextContent(type="text", text=f"No se encontró la cotización con ID {id_cotizacion}")]
                
                result = cotizacion.model_dump()
                
                return [TextContent(
                    type="text",
                    text=f"Cotización encontrada:\n{json.dumps(result, indent=2, ensure_ascii=False)}"
                )]
            except Exception as e:
                return [TextContent(type="text", text=f"Error al obtener la cotización: {str(e)}")]
        
        @self.server.tool(
            name="search_cotizaciones_by_ruta",
            description="Busca cotizaciones por ruta"
        )
        async def search_cotizaciones_by_ruta(ruta: str) -> List[TextContent]:
            """Busca cotizaciones por ruta"""
            try:
                cotizaciones = await self.repository.search_cotizaciones_by_ruta(ruta)
                result = []
                for cotizacion in cotizaciones:
                    result.append({
                        "id": cotizacion.id,
                        "ruta": cotizacion.ruta,
                        "ciudad_origen": cotizacion.ciudad_origen,
                        "ciudad_destino": cotizacion.ciudad_destino,
                        "valor": cotizacion.valor,
                        "vehiculo_requerido": cotizacion.vehiculo_requerido
                    })
                
                return [TextContent(
                    type="text",
                    text=f"Encontradas {len(result)} cotizaciones para la ruta '{ruta}':\n{json.dumps(result, indent=2, ensure_ascii=False)}"
                )]
            except Exception as e:
                return [TextContent(type="text", text=f"Error al buscar cotizaciones: {str(e)}")]
        
        # Herramientas para tabla vehicle_owner_holder_driver
        @self.server.tool(
            name="get_vehicles",
            description="Obtiene una lista de vehículos con información de propietario, poseedor y conductor"
        )
        async def get_vehicles(limit: int = 100, offset: int = 0) -> List[TextContent]:
            """Obtiene vehículos con paginación"""
            try:
                vehicles = await self.repository.get_vehicles(limit, offset)
                result = []
                for vehicle in vehicles:
                    result.append({
                        "placa": vehicle.placa,
                        "propietario": vehicle.propietario,
                        "conductor": vehicle.conductor,
                        "telefono_conductor": vehicle.telefonoconductor,
                        "telefono_propietario": vehicle.telefonopropietario,
                        "marca": vehicle.marca,
                        "modelo": vehicle.modelo,
                        "carroceria": vehicle.carroceria,
                        "ciudad_conductor": vehicle.ciudad_conductor
                    })
                
                return [TextContent(
                    type="text",
                    text=f"Encontrados {len(result)} vehículos:\n{json.dumps(result, indent=2, ensure_ascii=False)}"
                )]
            except Exception as e:
                return [TextContent(type="text", text=f"Error al obtener vehículos: {str(e)}")]
        
        @self.server.tool(
            name="get_vehicle_by_placa",
            description="Obtiene un vehículo específico por su placa"
        )
        async def get_vehicle_by_placa(placa: str) -> List[TextContent]:
            """Obtiene un vehículo por placa"""
            try:
                vehicle = await self.repository.get_vehicle_by_placa(placa)
                if not vehicle:
                    return [TextContent(type="text", text=f"No se encontró el vehículo con placa {placa}")]
                
                result = vehicle.model_dump()
                
                return [TextContent(
                    type="text",
                    text=f"Vehículo encontrado:\n{json.dumps(result, indent=2, ensure_ascii=False)}"
                )]
            except Exception as e:
                return [TextContent(type="text", text=f"Error al obtener el vehículo: {str(e)}")]
        
        @self.server.tool(
            name="search_vehicles_by_conductor",
            description="Busca vehículos por nombre del conductor"
        )
        async def search_vehicles_by_conductor(conductor_name: str) -> List[TextContent]:
            """Busca vehículos por conductor"""
            try:
                vehicles = await self.repository.search_vehicles_by_conductor(conductor_name)
                result = []
                for vehicle in vehicles:
                    result.append({
                        "placa": vehicle.placa,
                        "conductor": vehicle.conductor,
                        "telefono_conductor": vehicle.telefonoconductor,
                        "propietario": vehicle.propietario,
                        "marca": vehicle.marca,
                        "modelo": vehicle.modelo
                    })
                
                return [TextContent(
                    type="text",
                    text=f"Encontrados {len(result)} vehículos para el conductor '{conductor_name}':\n{json.dumps(result, indent=2, ensure_ascii=False)}"
                )]
            except Exception as e:
                return [TextContent(type="text", text=f"Error al buscar vehículos: {str(e)}")]
        
        @self.server.tool(
            name="get_vehicle_by_telefono_conductor",
            description="Obtiene un vehículo por el teléfono del conductor"
        )
        async def get_vehicle_by_telefono_conductor(telefono: str) -> List[TextContent]:
            """Obtiene un vehículo por teléfono del conductor"""
            try:
                vehicle = await self.repository.get_vehicle_by_telefono_conductor(telefono)
                if not vehicle:
                    return [TextContent(type="text", text=f"No se encontró el vehículo con teléfono de conductor {telefono}")]
                
                result = {
                    "placa": vehicle.placa,
                    "conductor": vehicle.conductor,
                    "telefono_conductor": vehicle.telefonoconductor,
                    "propietario": vehicle.propietario,
                    "telefono_propietario": vehicle.telefonopropietario,
                    "marca": vehicle.marca,
                    "modelo": vehicle.modelo,
                    "carroceria": vehicle.carroceria,
                    "ciudad_conductor": vehicle.ciudad_conductor
                }
                
                return [TextContent(
                    type="text",
                    text=f"Vehículo encontrado:\n{json.dumps(result, indent=2, ensure_ascii=False)}"
                )]
            except Exception as e:
                return [TextContent(type="text", text=f"Error al obtener el vehículo: {str(e)}")]
        
        # Herramientas para tabla group_cotizations y relaciones
        @self.server.tool(
            name="get_group_cotizations",
            description="Obtiene una lista de grupos de cotizaciones con paginación"
        )
        async def get_group_cotizations(limit: int = 100, offset: int = 0) -> List[TextContent]:
            """Obtiene grupos de cotizaciones con paginación"""
            try:
                groups = await self.repository.get_group_cotizations(limit, offset)
                result = []
                for group in groups:
                    result.append({
                        "id": group.id,
                        "user_id": group.user_id,
                        "client_id": group.client_id,
                        "type": group.type,
                        "reference": group.reference,
                        "status": group.status,
                        "created_at": str(group.created_at) if group.created_at else None,
                        "updated_at": str(group.updated_at) if group.updated_at else None
                    })
                
                return [TextContent(
                    type="text",
                    text=f"Encontrados {len(result)} grupos de cotizaciones:\n{json.dumps(result, indent=2, ensure_ascii=False)}"
                )]
            except Exception as e:
                return [TextContent(type="text", text=f"Error al obtener grupos de cotizaciones: {str(e)}")]
        
        @self.server.tool(
            name="get_group_cotization_by_id",
            description="Obtiene un grupo de cotizaciones específico por su ID"
        )
        async def get_group_cotization_by_id(group_id: int) -> List[TextContent]:
            """Obtiene un grupo de cotizaciones por ID"""
            try:
                group = await self.repository.get_group_cotization_by_id(group_id)
                if not group:
                    return [TextContent(type="text", text=f"No se encontró el grupo de cotizaciones con ID {group_id}")]
                
                result = {
                    "id": group.id,
                    "user_id": group.user_id,
                    "client_id": group.client_id,
                    "type": group.type,
                    "reference": group.reference,
                    "status": group.status,
                    "created_at": str(group.created_at) if group.created_at else None,
                    "updated_at": str(group.updated_at) if group.updated_at else None
                }
                
                return [TextContent(
                    type="text",
                    text=f"Grupo de cotizaciones encontrado:\n{json.dumps(result, indent=2, ensure_ascii=False)}"
                )]
            except Exception as e:
                return [TextContent(type="text", text=f"Error al obtener el grupo de cotizaciones: {str(e)}")]
        
        @self.server.tool(
            name="get_cotizaciones_by_group",
            description="Obtiene todas las cotizaciones que pertenecen a un grupo específico"
        )
        async def get_cotizaciones_by_group(group_id: int) -> List[TextContent]:
            """Obtiene cotizaciones por grupo"""
            try:
                cotizaciones = await self.repository.get_cotizaciones_by_group_id(group_id)
                result = []
                for cotizacion in cotizaciones:
                    result.append({
                        "id": cotizacion.id,
                        "pricing_id": cotizacion.pricing_id,
                        "ciudad_origen": cotizacion.ciudad_origen,
                        "ciudad_destino": cotizacion.ciudad_destino,
                        "peso_mercancia": cotizacion.peso_mercancia,
                        "vehiculo_requerido": cotizacion.vehiculo_requerido,
                        "ruta": cotizacion.ruta,
                        "valor": cotizacion.valor,
                        "tipo_mercancia": cotizacion.tipo_mercancia,
                        "group_cotizations_id": cotizacion.group_cotizations_id
                    })
                
                return [TextContent(
                    type="text",
                    text=f"Encontradas {len(result)} cotizaciones en el grupo {group_id}:\n{json.dumps(result, indent=2, ensure_ascii=False)}"
                )]
            except Exception as e:
                return [TextContent(type="text", text=f"Error al obtener cotizaciones del grupo: {str(e)}")]
        
        @self.server.tool(
            name="get_cotizacion_with_group_info",
            description="Obtiene una cotización con la información de su grupo asociado"
        )
        async def get_cotizacion_with_group_info(cotizacion_id: int) -> List[TextContent]:
            """Obtiene cotización con información del grupo"""
            try:
                cotizacion_data = await self.repository.get_cotizacion_with_group(cotizacion_id)
                if not cotizacion_data:
                    return [TextContent(type="text", text=f"No se encontró la cotización con ID {cotizacion_id}")]
                
                result = {
                    "cotizacion": {
                        "id": cotizacion_data["id"],
                        "pricing_id": cotizacion_data["pricing_id"],
                        "ciudad_origen": cotizacion_data["ciudad_origen"],
                        "ciudad_destino": cotizacion_data["ciudad_destino"],
                        "peso_mercancia": cotizacion_data["peso_mercancia"],
                        "vehiculo_requerido": cotizacion_data["vehiculo_requerido"],
                        "ruta": cotizacion_data["ruta"],
                        "valor": cotizacion_data["valor"],
                        "tipo_mercancia": cotizacion_data["tipo_mercancia"],
                        "group_cotizations_id": cotizacion_data["group_cotizations_id"]
                    },
                    "grupo": {
                        "type": cotizacion_data.get("group_type"),
                        "reference": cotizacion_data.get("group_reference"),
                        "status": cotizacion_data.get("group_status")
                    } if cotizacion_data.get("group_type") else None
                }
                
                return [TextContent(
                    type="text",
                    text=f"Cotización con información de grupo:\n{json.dumps(result, indent=2, ensure_ascii=False)}"
                )]
            except Exception as e:
                return [TextContent(type="text", text=f"Error al obtener cotización con grupo: {str(e)}")]
        
        # Herramientas para tabla pricings y relaciones
        @self.server.tool(
            name="get_pricings",
            description="Obtiene una lista de precios con paginación"
        )
        async def get_pricings(limit: int = 100, offset: int = 0) -> List[TextContent]:
            """Obtiene precios con paginación"""
            try:
                pricings = await self.repository.get_pricings(limit, offset)
                result = []
                for pricing in pricings:
                    result.append({
                        "id": pricing.id,
                        "vehicle_type": pricing.vehicle_type,
                        "type_pricing": pricing.type_pricing,
                        "origin": pricing.origin,
                        "destination": pricing.destination,
                        "price": pricing.price,
                        "weight_from": pricing.weight_from,
                        "weight_to": pricing.weight_to,
                        "condition": pricing.condition,
                        "created_at": str(pricing.created_at) if pricing.created_at else None,
                        "updated_at": str(pricing.updated_at) if pricing.updated_at else None
                    })
                
                return [TextContent(
                    type="text",
                    text=f"Encontrados {len(result)} precios:\n{json.dumps(result, indent=2, ensure_ascii=False)}"
                )]
            except Exception as e:
                return [TextContent(type="text", text=f"Error al obtener precios: {str(e)}")]
        
        @self.server.tool(
            name="get_pricing_by_id",
            description="Obtiene un precio específico por su ID"
        )
        async def get_pricing_by_id(pricing_id: int) -> List[TextContent]:
            """Obtiene un precio por ID"""
            try:
                pricing = await self.repository.get_pricing_by_id(pricing_id)
                if not pricing:
                    return [TextContent(type="text", text=f"No se encontró el precio con ID {pricing_id}")]
                
                result = {
                    "id": pricing.id,
                    "vehicle_type": pricing.vehicle_type,
                    "type_pricing": pricing.type_pricing,
                    "origin": pricing.origin,
                    "destination": pricing.destination,
                    "price": pricing.price,
                    "weight_from": pricing.weight_from,
                    "weight_to": pricing.weight_to,
                    "condition": pricing.condition,
                    "price_month": pricing.price_month,
                    "price_week": pricing.price_week,
                    "price_day": pricing.price_day,
                    "extra": pricing.extra,
                    "price_extra": pricing.price_extra,
                    "created_at": str(pricing.created_at) if pricing.created_at else None,
                    "updated_at": str(pricing.updated_at) if pricing.updated_at else None
                }
                
                return [TextContent(
                    type="text",
                    text=f"Precio encontrado:\n{json.dumps(result, indent=2, ensure_ascii=False)}"
                )]
            except Exception as e:
                return [TextContent(type="text", text=f"Error al obtener el precio: {str(e)}")]
        
        @self.server.tool(
            name="get_cotizaciones_by_pricing",
            description="Obtiene todas las cotizaciones que usan un precio específico"
        )
        async def get_cotizaciones_by_pricing(pricing_id: int) -> List[TextContent]:
            """Obtiene cotizaciones por precio"""
            try:
                cotizaciones = await self.repository.get_cotizaciones_by_pricing_id(pricing_id)
                result = []
                for cotizacion in cotizaciones:
                    result.append({
                        "id": cotizacion.id,
                        "pricing_id": cotizacion.pricing_id,
                        "ciudad_origen": cotizacion.ciudad_origen,
                        "ciudad_destino": cotizacion.ciudad_destino,
                        "peso_mercancia": cotizacion.peso_mercancia,
                        "vehiculo_requerido": cotizacion.vehiculo_requerido,
                        "ruta": cotizacion.ruta,
                        "valor": cotizacion.valor,
                        "tipo_mercancia": cotizacion.tipo_mercancia
                    })
                
                return [TextContent(
                    type="text",
                    text=f"Encontradas {len(result)} cotizaciones que usan el precio {pricing_id}:\n{json.dumps(result, indent=2, ensure_ascii=False)}"
                )]
            except Exception as e:
                return [TextContent(type="text", text=f"Error al obtener cotizaciones por precio: {str(e)}")]
        
        @self.server.tool(
            name="get_cotizacion_with_pricing_info",
            description="Obtiene una cotización con la información de su precio asociado"
        )
        async def get_cotizacion_with_pricing_info(cotizacion_id: int) -> List[TextContent]:
            """Obtiene cotización con información del precio"""
            try:
                cotizacion_data = await self.repository.get_cotizacion_with_pricing(cotizacion_id)
                if not cotizacion_data:
                    return [TextContent(type="text", text=f"No se encontró la cotización con ID {cotizacion_id}")]
                
                result = {
                    "cotizacion": {
                        "id": cotizacion_data["id"],
                        "pricing_id": cotizacion_data["pricing_id"],
                        "ciudad_origen": cotizacion_data["ciudad_origen"],
                        "ciudad_destino": cotizacion_data["ciudad_destino"],
                        "peso_mercancia": cotizacion_data["peso_mercancia"],
                        "vehiculo_requerido": cotizacion_data["vehiculo_requerido"],
                        "ruta": cotizacion_data["ruta"],
                        "valor": cotizacion_data["valor"],
                        "tipo_mercancia": cotizacion_data["tipo_mercancia"]
                    },
                    "precio": {
                        "vehicle_type": cotizacion_data.get("vehicle_type"),
                        "origin": cotizacion_data.get("pricing_origin"),
                        "destination": cotizacion_data.get("pricing_destination"),
                        "price": cotizacion_data.get("pricing_price"),
                        "type_pricing": cotizacion_data.get("type_pricing"),
                        "condition": cotizacion_data.get("pricing_condition")
                    } if cotizacion_data.get("vehicle_type") else None
                }
                
                return [TextContent(
                    type="text",
                    text=f"Cotización con información de precio:\n{json.dumps(result, indent=2, ensure_ascii=False)}"
                )]
            except Exception as e:
                return [TextContent(type="text", text=f"Error al obtener cotización con precio: {str(e)}")]
        
        @self.server.tool(
            name="search_pricings_by_route",
            description="Busca precios por origen y/o destino"
        )
        async def search_pricings_by_route(origin: str = None, destination: str = None) -> List[TextContent]:
            """Busca precios por ruta"""
            try:
                if not origin and not destination:
                    return [TextContent(type="text", text="Debe proporcionar al menos un origen o destino para buscar")]
                
                pricings = await self.repository.search_pricings_by_route(origin, destination)
                result = []
                for pricing in pricings:
                    result.append({
                        "id": pricing.id,
                        "vehicle_type": pricing.vehicle_type,
                        "origin": pricing.origin,
                        "destination": pricing.destination,
                        "price": pricing.price,
                        "type_pricing": pricing.type_pricing,
                        "weight_from": pricing.weight_from,
                        "weight_to": pricing.weight_to
                    })
                
                search_params = []
                if origin:
                    search_params.append(f"origen: '{origin}'")
                if destination:
                    search_params.append(f"destino: '{destination}'")
                
                return [TextContent(
                    type="text",
                    text=f"Encontrados {len(result)} precios para {' y '.join(search_params)}:\n{json.dumps(result, indent=2, ensure_ascii=False)}"
                )]
            except Exception as e:
                return [TextContent(type="text", text=f"Error al buscar precios por ruta: {str(e)}")]

        @self.server.tool(
            name="get_cotizacion_with_pricing_info",
            description="Obtiene una cotización específica con información completa de precios y grupos"
        )
        async def get_cotizacion_with_pricing_info(cotizacion_id: int) -> List[TextContent]:
            """Obtiene información completa de una cotización incluyendo precios y grupos"""
            try:
                info = await self.repository.get_cotizacion_with_pricing_info(cotizacion_id)
                return [TextContent(
                    type="text",
                    text=f"Información completa de cotización {cotizacion_id}:\n{json.dumps(info, indent=2, ensure_ascii=False)}"
                )]
            except Exception as e:
                return [TextContent(type="text", text=f"Error al obtener información de cotización: {str(e)}")]

        # =========================================
        # HERRAMIENTA ZINFORMACION
        # =========================================
        
        @self.server.tool(
            name="zinformacion",
            description="Consultar información operativa completa de órdenes con campos estáticos de cotizacion_models. Excluye información sensible como datos del cliente, porcentajes de ganancia y decisiones posteriores a llamadas."
        )
        async def zinformacion(
            orden_id: Optional[int] = None,
            search: Optional[str] = None,
            show_all: bool = False,
            show_stats: bool = False,
            limit: int = 10
        ) -> List[TextContent]:
            """
            Herramienta zinformacion para consultar información de órdenes
            
            Parámetros:
            - orden_id: ID específico de la orden a consultar
            - search: Texto para buscar en ciudades, productos o mercancías
            - show_all: Mostrar todas las órdenes (limitadas por limit)
            - show_stats: Mostrar estadísticas de completitud de campos obligatorios
            - limit: Límite de resultados para búsquedas (máximo 50)
            """
            try:
                # Limitar el máximo de resultados
                limit = min(limit, 50)
                
                if orden_id:
                    return await self._zinformacion_orden_detallada(orden_id)
                elif show_stats:
                    return await self._zinformacion_estadisticas()
                elif search or show_all:
                    return await self._zinformacion_buscar_ordenes(search, limit)
                else:
                    return await self._zinformacion_ayuda()
                    
            except Exception as e:
                return [TextContent(type="text", text=f"❌ Error en zinformacion: {str(e)}")]

        async def _zinformacion_orden_detallada(self, orden_id: int) -> List[TextContent]:
            """Muestra información detallada de una orden específica"""
            try:
                # Obtener la cotización con información del grupo
                cotizacion = await self.repository.get_cotizacion_by_id(orden_id)
                if not cotizacion:
                    return [TextContent(
                        type="text", 
                        text=f"❌ No se encontró la orden con ID: {orden_id}"
                    )]
                
                # Obtener información del grupo si existe
                grupo_info = None
                if cotizacion.group_cotizations_id:
                    grupo_info = await self.repository.get_group_cotization_by_id(cotizacion.group_cotizations_id)
                
                # Validar campos obligatorios
                faltantes = self._validar_campos_obligatorios(cotizacion)
                
                # Construir respuesta detallada
                response = f"""🔍 HERRAMIENTA ZINFORMACION - ORDEN #{orden_id}
{'='*60}

📋 ORDEN #{cotizacion.id}
{'-'*60}

"""
                
                # Estado de campos obligatorios
                if faltantes:
                    response += f"⚠️  CAMPOS OBLIGATORIOS FALTANTES:\n"
                    for faltante in faltantes:
                        response += f"   ❌ {faltante}\n"
                else:
                    response += "✅ TODOS LOS CAMPOS OBLIGATORIOS COMPLETOS\n"
                
                response += "\n"
                
                # Información general
                response += f"""🎯 INFORMACIÓN GENERAL
   Tipo: {cotizacion.tipo if hasattr(cotizacion, 'tipo') else 'No especificado'}
   Operación: {cotizacion.operation_type if hasattr(cotizacion, 'operation_type') else 'No especificado'}
   Creada: {cotizacion.created_at if hasattr(cotizacion, 'created_at') else 'No disponible'}

"""
                
                # Campos obligatorios destacados
                response += f"""📋 INFORMACIÓN OBLIGATORIA PARA COTIZACIÓN
   ✅ Peso Mercancía: {cotizacion.peso_mercancia or '⚠️ FALTANTE'}
   ✅ Cantidad: {cotizacion.cantidad or '⚠️ FALTANTE'}
   ✅ Tipo Embalaje: {cotizacion.tipo_embajale or '⚠️ FALTANTE'}
   ✅ Dimensiones: {cotizacion.dimensiones_exactas or '⚠️ FALTANTE'}
   ✅ Tipo Producto: {cotizacion.tipo_producto or '⚠️ FALTANTE'}
   ✅ Vehículo Requerido: {cotizacion.vehiculo_requerido or '⚠️ FALTANTE'}
   ✅ Frecuencia: {cotizacion.frecuencia or '⚠️ FALTANTE'}
   ✅ Esquema Seguridad: {cotizacion.esquema_seguridad or '⚠️ FALTANTE'}
   ✅ Tipo Carrocería: {await self._resolver_carroceria(cotizacion.tipo_carroceria)}
   ✅ Tipo Mercancía: {cotizacion.tipo_mercancia or '⚠️ FALTANTE'}

"""
                
                # Información estática adicional
                response += f"""📦 INFORMACIÓN ESTÁTICA DE MERCANCÍA
   Registro Fotográfico: {cotizacion.registro_fotografico or 'No especificado'}
   Temperatura Mercancía: {cotizacion.temperatura_mercancia or 'No requerida'}
   Humedad: {cotizacion.humedad or 'No especificada'}

"""
                
                # Información de carga y descarga
                response += f"""🚛 INFORMACIÓN DE CARGA Y DESCARGA
   Fecha y Hora Descargue/Cargue: {cotizacion.fecha_hora_descargue_cargue or 'No especificada'}
   Descargue/Cargue: {cotizacion.descargue_cargue or 'No especificado'}

"""
                
                # Información del grupo si existe
                if grupo_info:
                    response += f"""📁 GRUPO DE COTIZACIÓN
   Tipo: {grupo_info.type or 'No especificado'}
   Referencia: {grupo_info.reference or 'No especificada'}
   Estado: {grupo_info.status or 'No especificado'}

"""
                
                # Información de ruta
                response += f"""🗺️  RUTA
   Origen: {cotizacion.ciudad_origen or 'No especificado'}
   Destino: {cotizacion.ciudad_destino or 'No especificado'}
   DANE Origen: {cotizacion.ciudad_origen_dane or 'No disponible'}
   DANE Destino: {cotizacion.ciudad_destino_dane or 'No disponible'}
   Ruta: {cotizacion.ruta or 'No especificada'}

"""
                
                # Información adicional de mercancía
                response += f"""📦 INFORMACIÓN ADICIONAL DE MERCANCÍA
   Valor Declarado: {cotizacion.valor_declarado or 'No especificado'}

"""
                
                # Información del vehículo
                response += f"""🚛 INFORMACIÓN ADICIONAL DE VEHÍCULO
   Cantidad Vehículos: {cotizacion.cantidad_vh or 'No especificada'}

"""
                
                # Información logística
                response += f"""📋 INFORMACIÓN LOGÍSTICA ADICIONAL
   Seguro: {cotizacion.seguro or 'No especificado'}
   Ventanas Horarios: {cotizacion.ventanas_horarios_recibidos or 'No especificadas'}

"""
                
                # Comercio exterior si aplica
                if cotizacion.regimen_nacionalizado or cotizacion.fcl_lcl or cotizacion.numero_documento_bl:
                    response += f"""🌍 COMERCIO EXTERIOR
"""
                    if cotizacion.regimen_nacionalizado:
                        response += f"   Régimen: {cotizacion.regimen_nacionalizado}\n"
                    if cotizacion.agente_aduanas:
                        response += f"   Agente Aduanas: {cotizacion.agente_aduanas}\n"
                    if cotizacion.fcl_lcl:
                        response += f"   FCL/LCL: {cotizacion.fcl_lcl}\n"
                    if cotizacion.numero_documento_bl:
                        response += f"   Documento BL: {cotizacion.numero_documento_bl}\n"
                    if cotizacion.sitio_devolucion_contenedor:
                        response += f"   Devolución Contenedor: {cotizacion.sitio_devolucion_contenedor}\n"
                    response += "\n"
                
                # Documentación
                if cotizacion.registro_fotografico or cotizacion.un:
                    response += f"""📄 DOCUMENTACIÓN
"""
                    if cotizacion.registro_fotografico:
                        response += f"   Registro Fotográfico: {cotizacion.registro_fotografico}\n"
                    if cotizacion.un:
                        response += f"   UN: {cotizacion.un}\n"
                    response += "\n"
                
                # Estado del sistema
                if cotizacion.silogtran_status:
                    response += f"""💻 SISTEMA
   Estado Silogtran: {cotizacion.silogtran_status}
"""
                
                return [TextContent(type="text", text=response)]
                
            except Exception as e:
                return [TextContent(type="text", text=f"❌ Error al obtener orden detallada: {str(e)}")]

        async def _zinformacion_estadisticas(self) -> List[TextContent]:
            """Muestra estadísticas de completitud de campos obligatorios"""
            try:
                # Obtener todas las cotizaciones para análisis
                cotizaciones = await self.repository.get_cotizaciones(limit=1000, offset=0)
                
                total_ordenes = len(cotizaciones)
                ordenes_completas = 0
                estadisticas_campos = {
                    'peso_mercancia': 0,
                    'cantidad': 0,
                    'tipo_embajale': 0,
                    'dimensiones_exactas': 0,
                    'tipo_producto': 0,
                    'vehiculo_requerido': 0,
                    'frecuencia': 0,
                    'esquema_seguridad': 0,
                    'tipo_carroceria': 0,
                    'tipo_mercancia': 0
                }
                
                # Analizar cada cotización
                for cotizacion in cotizaciones:
                    faltantes = self._validar_campos_obligatorios(cotizacion)
                    if not faltantes:
                        ordenes_completas += 1
                    
                    # Contar campos completos
                    for campo in estadisticas_campos.keys():
                        valor = getattr(cotizacion, campo, None)
                        if valor:
                            estadisticas_campos[campo] += 1
                
                porcentaje_completas = round((ordenes_completas / total_ordenes) * 100, 1) if total_ordenes > 0 else 0
                
                response = f"""🔍 HERRAMIENTA ZINFORMACION - ESTADÍSTICAS
{'='*60}

📊 ESTADÍSTICAS DE COMPLETITUD DE CAMPOS OBLIGATORIOS
{'='*60}

📈 RESUMEN GENERAL
   Total de órdenes: {total_ordenes}
   Órdenes completas: {ordenes_completas}
   Porcentaje completas: {porcentaje_completas}%

📋 COMPLETITUD POR CAMPO OBLIGATORIO
"""
                
                nombres_campos = {
                    'peso_mercancia': 'Peso de mercancía',
                    'cantidad': 'Cantidad',
                    'tipo_embajale': 'Tipo de embalaje',
                    'dimensiones_exactas': 'Dimensiones exactas',
                    'tipo_producto': 'Tipo de producto',
                    'vehiculo_requerido': 'Vehículo requerido',
                    'frecuencia': 'Frecuencia',
                    'esquema_seguridad': 'Esquema de seguridad',
                    'tipo_carroceria': 'Tipo de carrocería',
                    'tipo_mercancia': 'Tipo de mercancía'
                }
                
                for campo, completos in estadisticas_campos.items():
                    porcentaje = round((completos / total_ordenes) * 100, 1) if total_ordenes > 0 else 0
                    faltantes = total_ordenes - completos
                    icono = '✅' if porcentaje == 100 else ('🟡' if porcentaje >= 80 else '❌')
                    nombre = nombres_campos[campo]
                    
                    response += f"   {icono} {nombre}: {completos}/{total_ordenes} ({porcentaje}%) - Faltantes: {faltantes}\n"
                
                response += f"""
💡 Use zinformacion con show_all=true para ver todas las órdenes
💡 Use zinformacion con orden_id para ver detalles de una orden específica
"""
                
                return [TextContent(type="text", text=response)]
                
            except Exception as e:
                return [TextContent(type="text", text=f"❌ Error al generar estadísticas: {str(e)}")]

        async def _zinformacion_buscar_ordenes(self, search: Optional[str], limit: int) -> List[TextContent]:
            """Busca y lista órdenes con criterios específicos"""
            try:
                if search:
                    # Buscar por texto en diferentes campos
                    cotizaciones = await self.repository.search_cotizaciones_by_multiple_fields(search, limit)
                else:
                    # Obtener todas las órdenes
                    cotizaciones = await self.repository.get_cotizaciones(limit=limit, offset=0)
                
                if not cotizaciones:
                    return [TextContent(
                        type="text",
                        text="⚠️  No se encontraron órdenes con los criterios especificados"
                    )]
                
                response = f"""🔍 HERRAMIENTA ZINFORMACION - RESULTADOS
{'='*60}

📊 RESULTADOS ENCONTRADOS: {len(cotizaciones)}
{'='*60}

"""
                
                # Crear tabla de resultados
                response += "| ID | Origen | Destino | Mercancía | Peso | Cantidad | Vehículo | Carrocería | Completa |\n"
                response += "|----+--------+---------+-----------+------+----------+----------+------------+----------|\n"
                
                for cotizacion in cotizaciones:
                    faltantes = self._validar_campos_obligatorios(cotizacion)
                    completa = '✅' if not faltantes else '❌'
                    
                    origen = (cotizacion.ciudad_origen or 'N/A')[:8]
                    destino = (cotizacion.ciudad_destino or 'N/A')[:8]
                    mercancia = (cotizacion.tipo_mercancia or 'N/A')[:10]
                    peso = (cotizacion.peso_mercancia or 'N/A')[:6]
                    cantidad = (cotizacion.cantidad or 'N/A')[:4]
                    vehiculo = (cotizacion.vehiculo_requerido or 'N/A')[:10]
                    carroceria = (cotizacion.tipo_carroceria or 'N/A')[:8]
                    
                    response += f"| {cotizacion.id} | {origen} | {destino} | {mercancia} | {peso} | {cantidad} | {vehiculo} | {carroceria} | {completa} |\n"
                
                response += f"""

💡 Para ver detalles completos de una orden, use: zinformacion con orden_id específico
💡 Para ver estadísticas de completitud, use: zinformacion con show_stats=true
"""
                
                return [TextContent(type="text", text=response)]
                
            except Exception as e:
                return [TextContent(type="text", text=f"❌ Error al buscar órdenes: {str(e)}")]

        async def _zinformacion_ayuda(self) -> List[TextContent]:
            """Muestra la ayuda de la herramienta zinformacion"""
            response = f"""🔍 HERRAMIENTA ZINFORMACION - AYUDA
{'='*60}

EJEMPLOS DE USO:

🔹 Ver orden específica:
   zinformacion(orden_id=31)

🔹 Buscar órdenes por texto:
   zinformacion(search="bogota")
   zinformacion(search="granel")

🔹 Ver todas las órdenes (limitadas):
   zinformacion(show_all=True)
   zinformacion(show_all=True, limit=20)

🔹 Ver estadísticas de completitud:
   zinformacion(show_stats=True)

🔹 Búsquedas con límite:
   zinformacion(search="medellin", limit=5)

NOTA: Esta herramienta muestra campos estáticos de cotizacion_models
excluyendo información sensible como datos del cliente, porcentajes de
ganancia, estados de decisión, conductores seleccionados y cualquier
información posterior a las llamadas con conductores.

CAMPOS ESTÁTICOS INCLUIDOS:
- cantidad, tipo_embajale, dimensiones_exactas
- registro_fotografico, tipo_producto, temperatura_mercancia
- humedad, fecha_hora_descargue_cargue, frecuencia
- Y otros campos operativos sin información comercial sensible
"""
            
            return [TextContent(type="text", text=response)]

        def _validar_campos_obligatorios(self, cotizacion) -> List[str]:
            """Valida que los campos obligatorios estén completos"""
            campos_obligatorios = {
                'peso_mercancia': 'Peso de la mercancía',
                'cantidad': 'Cantidad',
                'tipo_embajale': 'Tipo de embalaje',
                'dimensiones_exactas': 'Dimensiones exactas',
                'tipo_producto': 'Tipo de producto',
                'vehiculo_requerido': 'Vehículo requerido',
                'frecuencia': 'Frecuencia',
                'esquema_seguridad': 'Esquema de seguridad',
                'tipo_carroceria': 'Tipo de carrocería',
                'tipo_mercancia': 'Tipo de mercancía'
            }
            
            faltantes = []
            for campo, descripcion in campos_obligatorios.items():
                valor = getattr(cotizacion, campo, None)
                if not valor:
                    faltantes.append(descripcion)
            
            return faltantes

        async def _resolver_carroceria(self, tipo_carroceria: Optional[str]) -> str:
            """Resuelve el tipo de carrocería desde la tabla bodywork"""
            if not tipo_carroceria:
                return 'No especificada'
            
            try:
                # Buscar en la tabla bodywork
                query = """
                SELECT Nombre, Codigo 
                FROM bodywork 
                WHERE Nombre LIKE %s OR Codigo = %s
                LIMIT 1
                """
                results = await self.repository.db.execute_query(query, (f"%{tipo_carroceria}%", tipo_carroceria))
                
                if results:
                    bodywork = results[0]
                    return f"{bodywork['Nombre']} (Código: {bodywork['Codigo']})"
                else:
                    return f"{tipo_carroceria} (Sin mapear en bodywork)"
                    
            except Exception:
                return tipo_carroceria or 'No especificada'
        # ========================================
        # Herramientas para llamadas_conductores
        # ========================================
        
        @self.server.tool(
            name="get_conductores_filtrados",
            description="Obtiene la lista de conductores filtrados para una cotización específica. Incluye datos del conductor, vehículo, ciudad y estado de llamada."
        )
        async def get_conductores_filtrados(cotizacion_id: int, estado_llamada: Optional[str] = None) -> List[TextContent]:
            """Obtiene conductores filtrados por cotización"""
            try:
                # Construir query base CON JOIN a cotizacion_models para obtener fecha de cargue
                query = """
                SELECT 
                    lc.id, lc.identificador_unico, lc.cotizacion_id, lc.group_cotization_id,
                    lc.nombre_conductor, lc.telefono, lc.placa, lc.tipo_vehiculo, lc.vehiculo_silogtran,
                    lc.peso_maximo, lc.ciudad_actual, lc.ciudad_origen, lc.ciudad_destino,
                    lc.disponible, lc.score, lc.estado_llamada, lc.call_id, lc.fecha_llamada,
                    lc.mercancia, lc.peso_carga, lc.empaque,
                    lc.created_at, lc.updated_at,
                    cm.ciudad_origen as cot_ciudad_origen,
                    cm.ciudad_destino as cot_ciudad_destino,
                    cm.fecha_hora_descargue_cargue as fecha_cargue,
                    cm.peso_mercancia as cot_peso_mercancia,
                    cm.tipo_producto as cot_tipo_producto,
                    cm.vehiculo_requerido as cot_vehiculo_requerido
                FROM llamadas_conductores lc
                LEFT JOIN cotizacion_models cm ON lc.cotizacion_id = cm.id
                WHERE lc.cotizacion_id = %s
                    AND lc.deleted_at IS NULL
                """
                params = [cotizacion_id]
                
                # Filtrar por estado si se especifica
                if estado_llamada:
                    query += " AND estado_llamada = %s"
                    params.append(estado_llamada)
                
                query += " ORDER BY score DESC, created_at DESC"
                
                results = await self.repository.db.execute_query(query, tuple(params))
                
                conductores = []
                for row in results:
                    # Formatear fecha de cargue si existe
                    fecha_cargue = None
                    if row['fecha_cargue']:
                        try:
                            fecha_dt = row['fecha_cargue']
                            # Formato legible: "27 de abril de 2026"
                            fecha_cargue = fecha_dt.strftime('%d de %B de %Y') if fecha_dt else None
                        except:
                            fecha_cargue = str(row['fecha_cargue']) if row['fecha_cargue'] else None
                    
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
                        "mercancia": row['mercancia'],
                        "peso_carga": float(row['peso_carga']) if row['peso_carga'] else None,
                        "empaque": row['empaque'],
                        "created_at": str(row['created_at']) if row['created_at'] else None,
                        "updated_at": str(row['updated_at']) if row['updated_at'] else None,
                        # Datos de la cotización asociados
                        "info_cotizacion": {
                            "fecha_cargue": fecha_cargue,
                            "origen": row['cot_ciudad_origen'],
                            "destino": row['cot_ciudad_destino'],
                            "peso": row['cot_peso_mercancia'],
                            "producto": row['cot_tipo_producto'],
                            "vehiculo": row['cot_vehiculo_requerido']
                        } if row['fecha_cargue'] or row['cot_ciudad_origen'] else None
                    })
                
                return [TextContent(
                    type="text",
                    text=f"Encontrados {len(conductores)} conductores filtrados para cotización {cotizacion_id}:\n{json.dumps(conductores, indent=2, ensure_ascii=False)}"
                )]
                
            except Exception as e:
                return [TextContent(type="text", text=f"Error al obtener conductores filtrados: {str(e)}")]
        
        @self.server.tool(
            name="get_conductor_by_identificador",
            description="Obtiene un conductor específico por su identificador único"
        )
        async def get_conductor_by_identificador(identificador_unico: str) -> List[TextContent]:
            """Obtiene un conductor por su identificador único"""
            try:
                query = """
                SELECT 
                    id, identificador_unico, cotizacion_id, group_cotization_id,
                    nombre_conductor, telefono, placa, tipo_vehiculo, vehiculo_silogtran,
                    peso_maximo, ciudad_actual, ciudad_origen, ciudad_destino,
                    disponible, score, estado_llamada, call_id, fecha_llamada,
                    respuesta_llamada, notas, mercancia, peso_carga, empaque,
                    datos_adicionales, created_at, updated_at
                FROM llamadas_conductores
                WHERE identificador_unico = %s
                    AND deleted_at IS NULL
                LIMIT 1
                """
                
                results = await self.repository.db.execute_query(query, (identificador_unico,))
                
                if not results:
                    return [TextContent(type="text", text=f"No se encontró conductor con identificador {identificador_unico}")]
                
                row = results[0]
                conductor = {
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
                }
                
                return [TextContent(
                    type="text",
                    text=f"Conductor encontrado:\n{json.dumps(conductor, indent=2, ensure_ascii=False)}"
                )]
                
            except Exception as e:
                return [TextContent(type="text", text=f"Error al obtener conductor: {str(e)}")]
        
        @self.server.tool(
            name="update_estado_llamada_conductor",
            description="Actualiza el estado de una llamada de conductor (pendiente, en_progreso, completada, fallida, cancelada)"
        )
        async def update_estado_llamada_conductor(
            identificador_unico: str,
            estado_llamada: str,
            call_id: Optional[str] = None,
            respuesta_llamada: Optional[str] = None,
            notas: Optional[str] = None
        ) -> List[TextContent]:
            """Actualiza el estado de una llamada de conductor"""
            try:
                # Validar estado
                estados_validos = ['pendiente', 'en_progreso', 'completada', 'fallida', 'cancelada']
                if estado_llamada not in estados_validos:
                    return [TextContent(
                        type="text",
                        text=f"Estado inválido. Use uno de: {', '.join(estados_validos)}"
                    )]
                
                # Construir query UPDATE
                updates = ["estado_llamada = %s", "updated_at = NOW()"]
                params = [estado_llamada]
                
                if call_id:
                    updates.append("call_id = %s")
                    params.append(call_id)
                
                if respuesta_llamada:
                    updates.append("respuesta_llamada = %s")
                    params.append(respuesta_llamada)
                
                if notas:
                    updates.append("notas = %s")
                    params.append(notas)
                
                if estado_llamada in ['en_progreso', 'completada']:
                    updates.append("fecha_llamada = NOW()")
                
                params.append(identificador_unico)
                
                query = f"""
                UPDATE llamadas_conductores
                SET {', '.join(updates)}
                WHERE identificador_unico = %s
                    AND deleted_at IS NULL
                """
                
                await self.repository.db.execute_update(query, tuple(params))
                
                return [TextContent(
                    type="text",
                    text=f"Estado de llamada actualizado exitosamente para conductor {identificador_unico} a '{estado_llamada}'"
                )]
                
            except Exception as e:
                return [TextContent(type="text", text=f"Error al actualizar estado de llamada: {str(e)}")]
        
        @self.server.tool(
            name="get_conductor_by_telefono",
            description="Busca conductores en llamadas_conductores por número de teléfono. Devuelve información completa del conductor y su estado de llamada."
        )
        async def get_conductor_by_telefono(telefono: str) -> List[TextContent]:
            """Busca conductor por teléfono en llamadas_conductores"""
            try:
                # Limpiar teléfono (remover espacios, guiones, etc.)
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
                
                results = await self.repository.db.execute_query(query, (telefono, telefono_limpio))
                
                if not results:
                    return [TextContent(
                        type="text",
                        text=f"No se encontraron conductores con teléfono {telefono}"
                    )]
                
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
                
                return [TextContent(
                    type="text",
                    text=f"Encontrados {len(conductores)} conductores con teléfono {telefono}:\n{json.dumps(conductores, indent=2, ensure_ascii=False)}"
                )]
                
            except Exception as e:
                return [TextContent(type="text", text=f"Error al buscar conductor por teléfono: {str(e)}")]
        
        @self.server.tool(
            name="update_conversation_id_conductor",
            description="Actualiza el conversation_id (call_id) de ElevenLabs para un conductor específico cuando se inicia una llamada. También actualiza el estado a 'en_progreso'."
        )
        async def update_conversation_id_conductor(
            identificador_unico: str,
            conversation_id: str,
            estado_llamada: str = "en_progreso",
            notas: Optional[str] = None
        ) -> List[TextContent]:
            """Actualiza conversation_id y estado de llamada de conductor"""
            try:
                # Validar que conversation_id no esté vacío
                if not conversation_id or conversation_id.strip() == "":
                    return [TextContent(
                        type="text",
                        text="Error: conversation_id no puede estar vacío"
                    )]
                
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
                
                affected_rows = await self.repository.db.execute_update(query, tuple(params))
                
                if affected_rows > 0:
                    return [TextContent(
                        type="text",
                        text=f"Conversation ID actualizado exitosamente. Conductor: {identificador_unico}, Conversation ID: {conversation_id}, Estado: {estado_llamada}"
                    )]
                else:
                    return [TextContent(
                        type="text",
                        text=f"No se pudo actualizar. Verifique que el identificador_unico {identificador_unico} sea correcto"
                    )]
                
            except Exception as e:
                return [TextContent(type="text", text=f"Error al actualizar conversation_id: {str(e)}")]
