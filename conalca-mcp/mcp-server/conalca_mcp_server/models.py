from typing import Optional, Dict, Any, List
from datetime import datetime
from pydantic import BaseModel, Field
from .database import db_connection
import logging

logger = logging.getLogger(__name__)

class LlamadaModel(BaseModel):
    """Modelo para la tabla llamadas"""
    id_llamada: int
    id_cotizacion: int
    chofer_id: int
    numero_destino: Optional[str] = None
    status: str
    elevenlabs_conversation_id: Optional[str] = None
    elevenlabs_sip_call_id: Optional[str] = None
    call_started_at: Optional[datetime] = None
    call_ended_at: Optional[datetime] = None
    call_notes: Optional[str] = None
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None

    class Config:
        from_attributes = True

class CotizacionModel(BaseModel):
    """Modelo para la tabla cotizacion_models"""
    id: int
    pricing_id: int
    porcentaje: Optional[str] = None
    ciudad_origen: Optional[str] = None
    ciudad_destino: Optional[str] = None
    ciudad_origen_dane: Optional[str] = None
    ciudad_destino_dane: Optional[str] = None
    peso_mercancia: Optional[str] = None
    cantidad: Optional[str] = None
    tipo_embajale: Optional[str] = None
    dimensiones_exactas: Optional[str] = None
    registro_fotografico: Optional[str] = None
    planos: Optional[str] = None
    tipo_producto: Optional[str] = None
    temperatura_mercancia: Optional[str] = None
    humedad: Optional[str] = None
    vehiculo_requerido: Optional[str] = None
    regimen_nacionalizado: Optional[str] = None
    agente_aduanas: Optional[str] = None
    descargue_cargue: Optional[str] = None
    consolidado_expreso: Optional[str] = None
    fcl_lcl: Optional[str] = None
    sitio_devolucion_contenedor: Optional[str] = None
    numero_documento_bl: Optional[str] = None
    fecha_hora_descargue_cargue: Optional[str] = None
    cantidad_vh: Optional[str] = None
    un: Optional[str] = None
    ruta: Optional[str] = None
    frecuencia: Optional[str] = None
    esquema_seguridad: Optional[str] = None
    tipo_carroceria: Optional[str] = None
    valor: Optional[str] = None
    valor_declarado: Optional[str] = None
    tipo_mercancia: Optional[str] = None
    ventanas_horarios_recibidos: Optional[str] = None
    seguro: Optional[str] = None
    silogtran_status: Optional[str] = None
    group_cotizations_id: Optional[int] = None

    class Config:
        from_attributes = True

class GroupCotizationsModel(BaseModel):
    """Modelo para la tabla group_cotizations"""
    id: int
    user_id: Optional[int] = None
    client_id: Optional[int] = None
    type: Optional[str] = None
    reference: Optional[str] = None
    status: Optional[str] = None
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None

    class Config:
        from_attributes = True

class PricingModel(BaseModel):
    """Modelo para la tabla pricings"""
    id: int
    documents: Optional[str] = None
    vehicle_type: Optional[str] = None
    extra: Optional[str] = None
    price_extra: Optional[str] = None
    price_extra2: Optional[str] = None
    download_target: Optional[str] = None
    load_target: Optional[str] = None
    price_person: Optional[str] = None
    event: Optional[str] = None
    type_send: Optional[str] = None
    save_box: Optional[str] = None
    time_day: Optional[str] = None
    return_: Optional[str] = Field(None, alias="return")
    container: Optional[str] = None
    complements: Optional[str] = None
    download_price: Optional[str] = None
    iva: Optional[str] = None
    person_download: Optional[str] = None
    rent: Optional[str] = None
    download: Optional[str] = None
    load: Optional[str] = None
    store: Optional[str] = None
    scales: Optional[str] = None
    time: Optional[str] = None
    weight: Optional[str] = None
    vehicle_extra: Optional[str] = None
    download_destiny: Optional[str] = None
    download_destiny_iva: Optional[str] = None
    price_complements: Optional[str] = None
    price_documents: Optional[str] = None
    load_tulan: Optional[str] = None
    volume: Optional[str] = None
    price_month: Optional[float] = None
    price_aux_month: Optional[float] = None
    price_week: Optional[float] = None
    price_aux_week: Optional[float] = None
    price_day: Optional[float] = None
    price_aux_day: Optional[float] = None
    condition: Optional[str] = None
    weight_from: Optional[str] = None
    weight_to: Optional[str] = None
    type_pricing: Optional[str] = None
    origin: Optional[str] = None
    destination: Optional[str] = None
    price: Optional[str] = None
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None

    class Config:
        from_attributes = True
        populate_by_name = True

class VehicleOwnerHolderDriverModel(BaseModel):
    """Modelo para la tabla vehicle_owner_holder_driver"""
    id: Optional[int] = Field(None, alias="id")
    telefonopropietario: Optional[str] = Field(None, alias="Telefonopropietario")
    telefonoposeedor: Optional[str] = Field(None, alias="Telefonoposeedor")
    telefonoconductor: Optional[str] = Field(None, alias="Telefonoconductor")
    codigo: Optional[int] = Field(None, alias="Codigo")
    placa: Optional[str] = Field(None, alias="Placa")
    propietario: Optional[str] = Field(None, alias="Propietario")
    tipodocumentopropietario: Optional[str] = Field(None, alias="Tipodocumentopropietario")
    documentopropietario: Optional[int] = Field(None, alias="Documentopropietario")
    direccion_propietario: Optional[str] = Field(None, alias="Direccion propietario")
    ciudad_propietario: Optional[str] = Field(None, alias="Ciudad propietario")
    ciudad_codigodane_propietario: Optional[int] = Field(None, alias="Ciudad codigodane propietario")
    municipio_nombre: Optional[str] = Field(None, alias="Municipio nombre")
    municipio_dane: Optional[str] = Field(None, alias="Municipio dane")
    departamento_nombre: Optional[str] = Field(None, alias="Departamento nombre")
    departamento_dane: Optional[str] = Field(None, alias="Departamento dane")
    poseedor: Optional[str] = Field(None, alias="Poseedor")
    tipodocumentoposeedor: Optional[str] = Field(None, alias="Tipodocumentoposeedor")
    documentoposeedor: Optional[int] = Field(None, alias="Documentoposeedor")
    direccion_poseedor: Optional[str] = Field(None, alias="Direccion poseedor")
    ciudad_poseedor: Optional[str] = Field(None, alias="Ciudad poseedor")
    ciudad_codigodane_poseedor: Optional[int] = Field(None, alias="Ciudad codigodane poseedor")
    conductor: Optional[str] = Field(None, alias="Conductor")
    tipodocumentoconducotor: Optional[str] = Field(None, alias="Tipodocumentoconducotor")
    cedula: Optional[int] = Field(None, alias="Cedula")
    direccion_conductor: Optional[str] = Field(None, alias="Direccion conductor")
    ciudad_conductor: Optional[str] = Field(None, alias="Ciudad conductor")
    ciudad_codigodane_conductor: Optional[int] = Field(None, alias="Ciudad codigodane conductor")
    vehiculo_ejes: Optional[int] = Field(None, alias="Vehiculo ejes")
    clasevehiculo: Optional[str] = Field(None, alias="Clasevehiculo")
    marca: Optional[str] = Field(None, alias="Marca")
    clase_linea: Optional[str] = Field(None, alias="Clase linea")
    modelo: Optional[int] = Field(None, alias="Modelo")
    vehiculo_chasis: Optional[str] = Field(None, alias="Vehiculo chasis")
    pais: Optional[str] = Field(None, alias="Pais")
    fecha: Optional[str] = Field(None, alias="Fecha")
    tipafi_codigo: Optional[int] = Field(None, alias="Tipafi codigo")
    tipafi_nombre: Optional[str] = Field(None, alias="Tipafi nombre")
    carroceria: Optional[str] = Field(None, alias="Carroceria")
    capacidad: Optional[int] = Field(None, alias="Capacidad")
    estado: Optional[str] = Field(None, alias="Estado")

    class Config:
        from_attributes = True
        populate_by_name = True

class DatabaseRepository:
    """Repositorio para interactuar con las tablas de la base de datos"""
    
    def __init__(self):
        self.db = db_connection
    
    # Métodos para tabla llamadas
    async def get_llamadas(self, limit: int = 100, offset: int = 0) -> List[LlamadaModel]:
        """Obtiene las llamadas con paginación"""
        query = """
        SELECT id_llamada, id_cotizacion, chofer_id, status, 
               elevenlabs_conversation_id, elevenlabs_sip_call_id,
               call_started_at, call_ended_at, call_notes,
               created_at, updated_at
        FROM llamadas 
        ORDER BY created_at DESC 
        LIMIT %s OFFSET %s
        """
        results = await self.db.execute_query(query, (limit, offset))
        return [LlamadaModel(**row) for row in results]
    
    async def get_llamada_by_id(self, id_llamada: int) -> Optional[LlamadaModel]:
        """Obtiene una llamada por ID"""
        query = """
        SELECT id_llamada, id_cotizacion, chofer_id, status, 
               elevenlabs_conversation_id, elevenlabs_sip_call_id,
               call_started_at, call_ended_at, call_notes,
               created_at, updated_at
        FROM llamadas WHERE id_llamada = %s
        """
        results = await self.db.execute_query(query, (id_llamada,))
        return LlamadaModel(**results[0]) if results else None
    
    async def update_llamada_status(self, id_llamada: int, status: str, call_notes: str = None) -> bool:
        """Actualiza el status y notas de una llamada"""
        query = """
        UPDATE llamadas 
        SET status = %s, call_notes = %s, updated_at = NOW()
        WHERE id_llamada = %s
        """
        affected_rows = await self.db.execute_update(query, (status, call_notes, id_llamada))
        return affected_rows > 0
    
    async def get_llamadas_by_telefono(self, telefono: str, tipo_busqueda: str = "ambos") -> List[LlamadaModel]:
        """Busca llamadas por número de teléfono"""
        
        # Construir la consulta según el tipo de búsqueda
        if tipo_busqueda == "origen":
            where_clause = "WHERE call_notes LIKE %s"
            params = (f"%Teléfono: {telefono}%",)
        elif tipo_busqueda == "destino":
            where_clause = "WHERE numero_destino LIKE %s"
            params = (f"%{telefono}%",)
        else:  # ambos
            where_clause = "WHERE call_notes LIKE %s OR numero_destino LIKE %s"
            params = (f"%Teléfono: {telefono}%", f"%{telefono}%")
            
        query = f"""
        SELECT id_llamada, id_cotizacion, chofer_id, numero_destino, status,
               elevenlabs_conversation_id, elevenlabs_sip_call_id,
               call_started_at, call_ended_at, call_notes,
               created_at, updated_at
        FROM llamadas 
        {where_clause}
        ORDER BY created_at DESC
        """
        
        results = await self.db.execute_query(query, params)
        return [LlamadaModel(**row) for row in results]
    
    async def get_llamada_by_conversation_id(self, conversation_id: str) -> Optional[LlamadaModel]:
        """Busca una llamada por su conversation_id de ElevenLabs"""
        query = """
        SELECT id_llamada, id_cotizacion, chofer_id, numero_destino, status,
               elevenlabs_conversation_id, elevenlabs_sip_call_id,
               call_started_at, call_ended_at, call_notes,
               created_at, updated_at
        FROM llamadas 
        WHERE elevenlabs_conversation_id = %s
        """
        results = await self.db.execute_query(query, (conversation_id,))
        return LlamadaModel(**results[0]) if results else None
    
    async def activate_conversation(self, conversation_id: str, new_status: str, call_notes: str = None, sip_call_id: str = None) -> bool:
        """Activa/actualiza una conversación usando el conversation_id"""
        
        # Construir la consulta dinámicamente según los parámetros proporcionados
        set_clauses = ["status = %s", "updated_at = NOW()"]
        params = [new_status]
        
        if call_notes is not None:
            set_clauses.append("call_notes = %s")
            params.append(call_notes)
            
        if sip_call_id is not None:
            set_clauses.append("elevenlabs_sip_call_id = %s")
            params.append(sip_call_id)
            
        # Agregar conversation_id para la condición WHERE
        params.append(conversation_id)
        
        query = f"""
        UPDATE llamadas 
        SET {', '.join(set_clauses)}
        WHERE elevenlabs_conversation_id = %s
        """
        
        affected_rows = await self.db.execute_update(query, params)
        return affected_rows > 0
    
    async def create_llamada(self, telefono: str = None, status: str = 'pendiente', observaciones: str = None, numero_destino: str = None, id_cotizacion: int = None, chofer_id: int = None) -> int:
        """Crea una nueva llamada (telefónica general o específica de cotización)"""
        
        # Si es una llamada específica de cotización (llamada antigua)
        if id_cotizacion is not None and chofer_id is not None:
            query = """
            INSERT INTO llamadas (id_cotizacion, chofer_id, numero_destino, status, created_at, updated_at)
            VALUES (%s, %s, %s, %s, NOW(), NOW())
            """
            affected_rows = await self.db.execute_update(query, (id_cotizacion, chofer_id, numero_destino, status))
        else:
            # Para llamadas telefónicas generales, necesitamos valores por defecto para campos requeridos
            # Usar valores dummy para id_cotizacion y chofer_id si no se proporcionan
            if id_cotizacion is None:
                id_cotizacion = 0  # Valor dummy
            if chofer_id is None:
                chofer_id = 0  # Valor dummy
                
            query = """
            INSERT INTO llamadas (id_cotizacion, chofer_id, numero_destino, status, call_notes, created_at, updated_at)
            VALUES (%s, %s, %s, %s, %s, NOW(), NOW())
            """
            notes = f"Teléfono: {telefono}"
            if observaciones:
                notes += f" | Observaciones: {observaciones}"
                
            affected_rows = await self.db.execute_update(query, (id_cotizacion, chofer_id, numero_destino, status, notes))
            
        if affected_rows > 0:
            # Obtener el ID del registro insertado
            results = await self.db.execute_query("SELECT LAST_INSERT_ID() as id")
            return results[0]['id']
        return 0
    
    # Métodos para tabla cotizacion_models
    async def get_cotizaciones(self, limit: int = 100, offset: int = 0) -> List[CotizacionModel]:
        """Obtiene las cotizaciones con paginación"""
        query = "SELECT * FROM cotizacion_models ORDER BY id DESC LIMIT %s OFFSET %s"
        results = await self.db.execute_query(query, (limit, offset))
        return [CotizacionModel(**row) for row in results]
    
    async def get_cotizacion_by_id(self, id_cotizacion: int) -> Optional[CotizacionModel]:
        """Obtiene una cotización por ID"""
        query = "SELECT * FROM cotizacion_models WHERE id = %s"
        results = await self.db.execute_query(query, (id_cotizacion,))
        return CotizacionModel(**results[0]) if results else None
    
    async def search_cotizaciones_by_ruta(self, ruta: str) -> List[CotizacionModel]:
        """Busca cotizaciones por ruta"""
        query = "SELECT * FROM cotizacion_models WHERE ruta LIKE %s"
        results = await self.db.execute_query(query, (f"%{ruta}%",))
        return [CotizacionModel(**row) for row in results]
    
    # Métodos para tabla vehicle_owner_holder_driver
    async def get_vehicles(self, limit: int = 100, offset: int = 0) -> List[VehicleOwnerHolderDriverModel]:
        """Obtiene los vehículos con paginación"""
        query = "SELECT * FROM vehicle_owner_holder_driver LIMIT %s OFFSET %s"
        results = await self.db.execute_query(query, (limit, offset))
        return [VehicleOwnerHolderDriverModel(**row) for row in results]
    
    async def get_vehicle_by_placa(self, placa: str) -> Optional[VehicleOwnerHolderDriverModel]:
        """Obtiene un vehículo por placa"""
        query = "SELECT * FROM vehicle_owner_holder_driver WHERE Placa = %s"
        results = await self.db.execute_query(query, (placa,))
        return VehicleOwnerHolderDriverModel(**results[0]) if results else None
    
    async def search_vehicles_by_conductor(self, conductor_name: str) -> List[VehicleOwnerHolderDriverModel]:
        """Busca vehículos por nombre del conductor"""
        query = "SELECT * FROM vehicle_owner_holder_driver WHERE Conductor LIKE %s"
        results = await self.db.execute_query(query, (f"%{conductor_name}%",))
        return [VehicleOwnerHolderDriverModel(**row) for row in results]
    
    async def get_vehicle_by_telefono_conductor(self, telefono: str) -> Optional[VehicleOwnerHolderDriverModel]:
        """Obtiene un vehículo por teléfono del conductor"""
        query = "SELECT * FROM vehicle_owner_holder_driver WHERE Telefonoconductor = %s"
        results = await self.db.execute_query(query, (telefono,))
        return VehicleOwnerHolderDriverModel(**results[0]) if results else None

    async def get_chofer_by_placa(self, placa: str) -> Optional[VehicleOwnerHolderDriverModel]:
        """Obtiene información del chofer por número de placa del vehículo"""
        query = """
        SELECT * FROM vehicle_owner_holder_driver 
        WHERE UPPER(TRIM(Placa)) = UPPER(TRIM(%s))
        LIMIT 1
        """
        results = await self.db.execute_query(query, (placa,))
        return VehicleOwnerHolderDriverModel(**results[0]) if results else None

    async def get_chofer_by_id(self, chofer_id: int) -> Optional[VehicleOwnerHolderDriverModel]:
        """Obtiene información del chofer por ID"""
        query = "SELECT * FROM vehicle_owner_holder_driver WHERE id = %s"
        results = await self.db.execute_query(query, (chofer_id,))
        return VehicleOwnerHolderDriverModel(**results[0]) if results else None

    async def save_driver_decision(self, cotizacion_model_id: int, driver_id: int, decision: int) -> bool:
        """Guarda la decisión del conductor sobre una cotización (1 = acepta, 0 = rechaza)"""
        query = """
        INSERT INTO call_driver_decisions (cotizacion_model_id, driver_id, decision, created_at, updated_at)
        VALUES (%s, %s, %s, NOW(), NOW())
        """
        affected_rows = await self.db.execute_update(query, (cotizacion_model_id, driver_id, decision))
        return affected_rows > 0

    # Métodos para tabla group_cotizations
    async def get_group_cotizations(self, limit: int = 100, offset: int = 0) -> List[GroupCotizationsModel]:
        """Obtiene grupos de cotizaciones con paginación"""
        query = "SELECT * FROM group_cotizations ORDER BY id DESC LIMIT %s OFFSET %s"
        results = await self.db.execute_query(query, (limit, offset))
        return [GroupCotizationsModel(**row) for row in results]
    
    async def get_group_cotization_by_id(self, group_id: int) -> Optional[GroupCotizationsModel]:
        """Obtiene un grupo de cotizaciones por ID"""
        query = "SELECT * FROM group_cotizations WHERE id = %s"
        results = await self.db.execute_query(query, (group_id,))
        return GroupCotizationsModel(**results[0]) if results else None
    
    async def get_cotizaciones_by_group_id(self, group_id: int) -> List[CotizacionModel]:
        """Obtiene todas las cotizaciones de un grupo específico"""
        query = "SELECT * FROM cotizacion_models WHERE group_cotizations_id = %s ORDER BY id DESC"
        results = await self.db.execute_query(query, (group_id,))
        return [CotizacionModel(**row) for row in results]
    
    async def get_cotizacion_with_group(self, cotizacion_id: int) -> Optional[Dict[str, Any]]:
        """Obtiene una cotización con información de su grupo"""
        query = """
        SELECT c.*, g.type as group_type, g.reference as group_reference, g.status as group_status
        FROM cotizacion_models c
        LEFT JOIN group_cotizations g ON c.group_cotizations_id = g.id
        WHERE c.id = %s
        """
        results = await self.db.execute_query(query, (cotizacion_id,))
        return results[0] if results else None

    # Métodos para tabla pricings
    async def get_pricings(self, limit: int = 100, offset: int = 0) -> List[PricingModel]:
        """Obtiene precios con paginación"""
        query = "SELECT * FROM pricings ORDER BY id DESC LIMIT %s OFFSET %s"
        results = await self.db.execute_query(query, (limit, offset))
        return [PricingModel(**row) for row in results]
    
    async def get_pricing_by_id(self, pricing_id: int) -> Optional[PricingModel]:
        """Obtiene un precio por ID"""
        query = "SELECT * FROM pricings WHERE id = %s"
        results = await self.db.execute_query(query, (pricing_id,))
        return PricingModel(**results[0]) if results else None
    
    async def get_cotizacion_with_pricing(self, cotizacion_id: int) -> Optional[Dict[str, Any]]:
        """Obtiene una cotización con información de su precio"""
        query = """
        SELECT c.*, p.vehicle_type, p.origin as pricing_origin, p.destination as pricing_destination, 
               p.price as pricing_price, p.type_pricing, p.condition as pricing_condition
        FROM cotizacion_models c
        LEFT JOIN pricings p ON c.pricing_id = p.id
        WHERE c.id = %s
        """
        results = await self.db.execute_query(query, (cotizacion_id,))
        return results[0] if results else None
    
    async def get_cotizaciones_by_pricing_id(self, pricing_id: int) -> List[CotizacionModel]:
        """Obtiene todas las cotizaciones que usan un precio específico"""
        query = "SELECT * FROM cotizacion_models WHERE pricing_id = %s ORDER BY id DESC"
        results = await self.db.execute_query(query, (pricing_id,))
        return [CotizacionModel(**row) for row in results]
    
    async def search_pricings_by_route(self, origin: str = None, destination: str = None) -> List[PricingModel]:
        """Busca precios por origen y/o destino"""
        conditions = []
        params = []
        
        if origin:
            conditions.append("origin LIKE %s")
            params.append(f"%{origin}%")
        
        if destination:
            conditions.append("destination LIKE %s")
            params.append(f"%{destination}%")
        
        if not conditions:
            return []
        
        where_clause = " AND ".join(conditions)
        query = f"SELECT * FROM pricings WHERE {where_clause} ORDER BY id DESC"
        results = await self.db.execute_query(query, params)
        return [PricingModel(**row) for row in results]

    async def search_cotizaciones_by_multiple_fields(self, search_text: str, limit: int = 100) -> List[CotizacionModel]:
        """Busca cotizaciones por múltiples campos (ciudades, productos, mercancías, vehículos)"""
        query = """
        SELECT * FROM cotizacion_models 
        WHERE ciudad_origen LIKE %s 
        OR ciudad_destino LIKE %s 
        OR tipo_mercancia LIKE %s 
        OR tipo_producto LIKE %s 
        OR vehiculo_requerido LIKE %s
        ORDER BY id DESC 
        LIMIT %s
        """
        search_param = f"%{search_text}%"
        params = (search_param, search_param, search_param, search_param, search_param, limit)
        results = await self.db.execute_query(query, params)
        return [CotizacionModel(**row) for row in results]
    
    async def get_packing_name(self, codigo: int) -> Optional[str]:
        """Obtiene el nombre del tipo de embalaje por su código"""
        query = "SELECT Nombre FROM packing WHERE Codigo = %s LIMIT 1"
        results = await self.db.execute_query(query, (codigo,))
        if results and len(results) > 0:
            return results[0].get('Nombre')
        return None
    
    async def get_product_name(self, producto_codigo: int) -> Optional[str]:
        """Obtiene el nombre del producto por su código"""
        query = "SELECT producto_nombre FROM products WHERE producto_codigo = %s LIMIT 1"
        results = await self.db.execute_query(query, (producto_codigo,))
        if results and len(results) > 0:
            return results[0].get('producto_nombre')
        return None

    # ========================================================================
    # Métodos para tabla llamadas_conductores (nuevo flujo con Arcangel)
    # ========================================================================
    
    async def get_conductor_by_conversation_id(self, conversation_id: str) -> Optional[Dict[str, Any]]:
        """
        Obtiene información completa del conductor y cotización mediante conversation_id.
        Busca primero en la tabla llamadas y luego en llamadas_conductores.
        """
        # Primero buscar en llamadas para obtener cotizacion_id
        query_llamada = """
        SELECT id_llamada, id_cotizacion, chofer_id, elevenlabs_conversation_id, status
        FROM llamadas 
        WHERE elevenlabs_conversation_id = %s 
        LIMIT 1
        """
        llamada_results = await self.db.execute_query(query_llamada, (conversation_id,))
        
        if not llamada_results or len(llamada_results) == 0:
            return None
            
        llamada = llamada_results[0]
        cotizacion_id = llamada.get('id_cotizacion')
        
        if not cotizacion_id:
            return None
        
        # Ahora buscar el conductor en llamadas_conductores que esté pendiente
        query_conductor = """
        SELECT lc.*, cm.ciudad_origen, cm.ciudad_destino, cm.tipo_producto, cm.tipo_embajale,
               cm.peso_carga, cm.vehiculo_requerido
        FROM llamadas_conductores lc
        LEFT JOIN cotizacion_models cm ON lc.cotizacion_id = cm.id
        WHERE lc.cotizacion_id = %s 
        AND lc.estado_llamada = 'pendiente'
        AND lc.disponible = 1
        ORDER BY lc.score DESC
        LIMIT 1
        """
        conductor_results = await self.db.execute_query(query_conductor, (cotizacion_id,))
        
        if not conductor_results or len(conductor_results) == 0:
            return None
            
        return conductor_results[0]
    
    async def update_estado_llamada_conductor(
        self, 
        identificador_unico: str, 
        estado_llamada: str,
        call_id: str = None,
        respuesta_llamada: str = None,
        notas: str = None
    ) -> bool:
        """
        Actualiza el estado de una llamada a un conductor en la tabla llamadas_conductores.
        Estados válidos: pendiente, en_progreso, completada, fallida, cancelada
        """
        estados_validos = ['pendiente', 'en_progreso', 'completada', 'fallida', 'cancelada']
        
        if estado_llamada not in estados_validos:
            return False
        
        # Construir query dinámicamente según parámetros disponibles
        update_fields = ["estado_llamada = %s", "fecha_llamada = NOW()", "updated_at = NOW()"]
        params = [estado_llamada]
        
        if call_id:
            update_fields.append("call_id = %s")
            params.append(call_id)
        
        if respuesta_llamada:
            update_fields.append("respuesta_llamada = %s")
            params.append(respuesta_llamada)
            
        if notas:
            update_fields.append("notas = %s")
            params.append(notas)
        
        # Agregar identificador_unico al final
        params.append(identificador_unico)
        
        query = f"""
        UPDATE llamadas_conductores 
        SET {', '.join(update_fields)}
        WHERE identificador_unico = %s
        """
        
        affected_rows = await self.db.execute_update(query, tuple(params))
        return affected_rows > 0
    
    async def save_driver_decision_new(
        self, 
        identificador_unico: str,
        conversation_id: str,
        decision: int,
        notas: str = None
    ) -> bool:
        """
        Guarda la decisión del conductor en:
        1. llamadas_conductores (actualiza estado y respuesta)
        2. call_driver_decisions (registro histórico)
        
        decision: 1 = acepta, 0 = rechaza
        
        También actualiza el estado_llamada según la decisión:
        - Si acepta (1): estado_llamada = 'completada'
        - Si rechaza (0): estado_llamada = 'fallida'
        """
        try:
            # Primero, obtener la información del conductor
            query_conductor = """
            SELECT id, cotizacion_id 
            FROM llamadas_conductores 
            WHERE identificador_unico = %s AND deleted_at IS NULL
            """
            result = await self.db.execute_query(query_conductor, (identificador_unico,))
            
            if not result:
                logger.error(f"No se encontró conductor con identificador_unico: {identificador_unico}")
                return False
            
            conductor_data = result[0]
            conductor_id = conductor_data['id']
            cotizacion_id = conductor_data.get('cotizacion_id')
            
            # 1. Actualizar llamadas_conductores
            estado_nuevo = 'completada' if decision == 1 else 'fallida'
            respuesta = 'Acepta el viaje' if decision == 1 else 'Rechaza el viaje'
            
            update_success = await self.update_estado_llamada_conductor(
                identificador_unico=identificador_unico,
                estado_llamada=estado_nuevo,
                call_id=conversation_id,
                respuesta_llamada=respuesta,
                notas=notas
            )
            
            if not update_success:
                logger.error(f"Error actualizando llamadas_conductores para {identificador_unico}")
                return False
            
            # 2. Guardar en call_driver_decisions (registro histórico)
            if cotizacion_id:
                query_decision = """
                INSERT INTO call_driver_decisions 
                (cotizacion_model_id, driver_id, decision, created_at, updated_at)
                VALUES (%s, %s, %s, NOW(), NOW())
                """
                await self.db.execute_query(
                    query_decision, 
                    (cotizacion_id, conductor_id, decision)
                )
                logger.info(f"Decisión guardada en call_driver_decisions: cotizacion_id={cotizacion_id}, driver_id={conductor_id}, decision={decision}")
            else:
                logger.warning(f"No se guardó en call_driver_decisions: cotizacion_id es NULL para {identificador_unico}")
            
            return True
            
        except Exception as e:
            logger.error(f"Error en save_driver_decision_new: {e}")
            return False

# Instancia global del repositorio
repository = DatabaseRepository()