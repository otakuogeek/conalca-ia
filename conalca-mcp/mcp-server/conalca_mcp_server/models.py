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
    pricing_id: Optional[int] = None
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
    flete: Optional[Any] = None
    tipo_mercancia: Optional[str] = None
    ventanas_horarios_recibidos: Optional[str] = None
    seguro: Optional[str] = None
    silogtran_status: Optional[str] = None
    group_cotizations_id: Optional[int] = None

    class Config:
        from_attributes = True

class ProductModel(BaseModel):
    """Modelo para la tabla products"""
    producto_codigo: int
    producto_codigo_ministerio: int
    producto_nombre: Optional[str] = None
    tippro_nombre: Optional[str] = None
    producto_fechacreacion: Optional[int] = None
    natcar_nombre: Optional[str] = None
    usuario_nombre: Optional[str] = None

    class Config:
        from_attributes = True
class EmpaqueModel(BaseModel):
    """Modelo para la tabla tb_empaque"""
    id: int
    codigo_ministerio: int
    nome: str
    usuario: str
    data_criacao: Optional[datetime] = None
    data_modificacao: Optional[datetime] = None

    class Config:
        from_attributes = True
class EmpaqueModel(BaseModel):
    """Modelo para la tabla tb_empaque"""
    id: int
    codigo_ministerio: int
    nome: str
    usuario: str
    data_criacao: Optional[datetime] = None
    data_modificacao: Optional[datetime] = None

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

class ClientModel(BaseModel):
    """Modelo para la tabla clients"""
    id: Optional[int] = None
    cliente: Optional[str] = None
    documento: Optional[str] = None
    telefono: Optional[str] = None
    direccion: Optional[str] = None
    ciudad: Optional[str] = None
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None

    class Config:
        from_attributes = True


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
    
    async def create_cotizacion(self, cotizacion_data: Dict[str, Any]) -> int:
        """Crea una nueva cotización en la base de datos"""
        # 🆕 NORMALIZAR CIUDADES ANTES DE GUARDAR
        if 'ciudad_origen' in cotizacion_data and cotizacion_data['ciudad_origen']:
            city_info = await self.search_city_by_name(cotizacion_data['ciudad_origen'])
            if city_info:
                cotizacion_data['ciudad_origen'] = city_info['ciudad_nombre']
                cotizacion_data['ciudad_origen_dane'] = city_info['ciudad_codigodane']
                logger.info(f"🏙️ Ciudad origen normalizada: {city_info['ciudad_nombre']} (DANE: {city_info['ciudad_codigodane']})")
            else:
                logger.warning(f"⚠️ No se encontró ciudad origen en BD: {cotizacion_data['ciudad_origen']}")
        
        if 'ciudad_destino' in cotizacion_data and cotizacion_data['ciudad_destino']:
            city_info = await self.search_city_by_name(cotizacion_data['ciudad_destino'])
            if city_info:
                cotizacion_data['ciudad_destino'] = city_info['ciudad_nombre']
                cotizacion_data['ciudad_destino_dane'] = city_info['ciudad_codigodane']
                logger.info(f"🏙️ Ciudad destino normalizada: {city_info['ciudad_nombre']} (DANE: {city_info['ciudad_codigodane']})")
            else:
                logger.warning(f"⚠️ No se encontró ciudad destino en BD: {cotizacion_data['ciudad_destino']}")
        
        # Campos disponibles en cotizacion_models
        fields = []
        values = []
        placeholders = []
        
        # Mapear campos del diccionario a la tabla
        field_mapping = {
            'pricing_id': 'pricing_id',
            'porcentaje': 'porcentaje',
            'ciudad_origen': 'ciudad_origen',
            'ciudad_destino': 'ciudad_destino',
            'ciudad_origen_dane': 'ciudad_origen_dane',
            'ciudad_destino_dane': 'ciudad_destino_dane',
            'peso_mercancia': 'peso_mercancia',
            'cantidad': 'cantidad',
            'tipo_embajale': 'tipo_embajale',
            'dimensiones_exactas': 'dimensiones_exactas',
            'registro_fotografico': 'registro_fotografico',
            'planos': 'planos',
            'tipo_producto': 'tipo_producto',
            'temperatura_mercancia': 'temperatura_mercancia',
            'humedad': 'humedad',
            'vehiculo_requerido': 'vehiculo_requerido',
            'regimen_nacionalizado': 'regimen_nacionalizado',
            'agente_aduanas': 'agente_aduanas',
            'descargue_cargue': 'descargue_cargue',
            'consolidado_expreso': 'consolidado_expreso',
            'fcl_lcl': 'fcl_lcl',
            'sitio_devolucion_contenedor': 'sitio_devolucion_contenedor',
            'numero_documento_bl': 'numero_documento_bl',
            'fecha_hora_descargue_cargue': 'fecha_hora_descargue_cargue',
            'cantidad_vh': 'cantidad_vh',
            'un': 'un',
            'ruta': 'ruta',
            'frecuencia': 'frecuencia',
            'esquema_seguridad': 'esquema_seguridad',
            'tipo_carroceria': 'tipo_carroceria',
            'valor': 'valor',
            'valor_declarado': 'valor_declarado',
            'tipo_mercancia': 'tipo_mercancia',
            'ventanas_horarios_recibidos': 'ventanas_horarios_recibidos',
            'seguro': 'seguro',
            'silogtran_status': 'silogtran_status',
            'group_cotizations_id': 'group_cotizations_id'
        }
        
        for key, db_field in field_mapping.items():
            if key in cotizacion_data and cotizacion_data[key] is not None:
                fields.append(db_field)
                values.append(cotizacion_data[key])
                placeholders.append('%s')
        
        if not fields:
            raise ValueError("No se proporcionaron datos para crear la cotización")
        
        query = f"INSERT INTO cotizacion_models ({', '.join(fields)}) VALUES ({', '.join(placeholders)})"
        affected_rows = await self.db.execute_update(query, tuple(values))
        
        if affected_rows > 0:
            # Obtener el ID de la cotización recién creada
            result = await self.db.execute_query("SELECT LAST_INSERT_ID() as id")
            return result[0]['id'] if result else 0
        return 0
    
    async def update_cotizacion(self, cotizacion_id: int, update_data: Dict[str, Any]) -> bool:
        """Actualiza una cotización existente"""
        if not update_data:
            return False
        
        # Campos que se pueden actualizar
        field_mapping = {
            'pricing_id': 'pricing_id',
            'porcentaje': 'porcentaje',
            'ciudad_origen': 'ciudad_origen',
            'ciudad_destino': 'ciudad_destino',
            'ciudad_origen_dane': 'ciudad_origen_dane',
            'ciudad_destino_dane': 'ciudad_destino_dane',
            'peso_mercancia': 'peso_mercancia',
            'cantidad': 'cantidad',
            'tipo_embajale': 'tipo_embajale',
            'dimensiones_exactas': 'dimensiones_exactas',
            'registro_fotografico': 'registro_fotografico',
            'planos': 'planos',
            'tipo_producto': 'tipo_producto',
            'temperatura_mercancia': 'temperatura_mercancia',
            'humedad': 'humedad',
            'vehiculo_requerido': 'vehiculo_requerido',
            'regimen_nacionalizado': 'regimen_nacionalizado',
            'agente_aduanas': 'agente_aduanas',
            'descargue_cargue': 'descargue_cargue',
            'consolidado_expreso': 'consolidado_expreso',
            'fcl_lcl': 'fcl_lcl',
            'sitio_devolucion_contenedor': 'sitio_devolucion_contenedor',
            'numero_documento_bl': 'numero_documento_bl',
            'fecha_hora_descargue_cargue': 'fecha_hora_descargue_cargue',
            'cantidad_vh': 'cantidad_vh',
            'un': 'un',
            'ruta': 'ruta',
            'frecuencia': 'frecuencia',
            'esquema_seguridad': 'esquema_seguridad',
            'tipo_carroceria': 'tipo_carroceria',
            'valor': 'valor',
            'valor_declarado': 'valor_declarado',
            'tipo_mercancia': 'tipo_mercancia',
            'ventanas_horarios_recibidos': 'ventanas_horarios_recibidos',
            'seguro': 'seguro',
            'silogtran_status': 'silogtran_status',
            'group_cotizations_id': 'group_cotizations_id'
        }
        
        set_clauses = []
        values = []
        
        for key, db_field in field_mapping.items():
            if key in update_data:
                set_clauses.append(f"{db_field} = %s")
                values.append(update_data[key])
        
        if not set_clauses:
            return False
        
        values.append(cotizacion_id)
        query = f"UPDATE cotizacion_models SET {', '.join(set_clauses)} WHERE id = %s"
        affected_rows = await self.db.execute_update(query, tuple(values))
        return affected_rows > 0
    
    async def delete_cotizacion(self, cotizacion_id: int) -> bool:
        """Elimina una cotización por ID"""
        query = "DELETE FROM cotizacion_models WHERE id = %s"
        affected_rows = await self.db.execute_update(query, (cotizacion_id,))
        return affected_rows > 0
    
    async def search_cotizaciones_advanced(self, filters: Dict[str, Any]) -> List[CotizacionModel]:
        """Búsqueda avanzada de cotizaciones con múltiples filtros"""
        where_clauses = []
        values = []
        
        # Filtros de búsqueda exacta
        exact_filters = ['id', 'pricing_id', 'group_cotizations_id', 'silogtran_status']
        for field in exact_filters:
            if field in filters and filters[field] is not None:
                where_clauses.append(f"{field} = %s")
                values.append(filters[field])
        
        # Filtros de búsqueda parcial (LIKE)
        like_filters = [
            'ciudad_origen', 'ciudad_destino', 'tipo_producto', 'ruta', 
            'vehiculo_requerido', 'tipo_carroceria', 'tipo_mercancia'
        ]
        for field in like_filters:
            if field in filters and filters[field]:
                where_clauses.append(f"{field} LIKE %s")
                values.append(f"%{filters[field]}%")
        
        # Construir query
        query = "SELECT * FROM cotizacion_models"
        if where_clauses:
            query += " WHERE " + " AND ".join(where_clauses)
        query += " ORDER BY id DESC"
        
        # Aplicar límite si se especifica
        if 'limit' in filters:
            query += " LIMIT %s"
            values.append(filters['limit'])
            if 'offset' in filters:
                query += " OFFSET %s"
                values.append(filters['offset'])
        
        results = await self.db.execute_query(query, tuple(values) if values else None)
        return [CotizacionModel(**row) for row in results]
    
    # Métodos para tabla products
    async def search_products_by_name(self, search_term: str, limit: int = 20) -> List[ProductModel]:
        """Busca productos por nombre usando coincidencia parcial"""
        query = """
        SELECT producto_codigo, producto_codigo_ministerio, producto_nombre, 
               tippro_nombre, producto_fechacreacion, natcar_nombre, usuario_nombre
        FROM products 
        WHERE producto_nombre LIKE %s
        ORDER BY 
            CASE 
                WHEN producto_nombre LIKE %s THEN 1
                WHEN producto_nombre LIKE %s THEN 2
                ELSE 3
            END,
            producto_nombre
        LIMIT %s
        """
        search_pattern = f"%{search_term}%"
        start_pattern = f"{search_term}%"
        word_pattern = f"% {search_term}%"
        
        results = await self.db.execute_query(
            query, 
            (search_pattern, start_pattern, word_pattern, limit)
        )
        return [ProductModel(**row) for row in results]
    
    async def get_product_by_code(self, producto_codigo: int) -> Optional[ProductModel]:
        """Obtiene un producto por su código"""
        query = """
        SELECT producto_codigo, producto_codigo_ministerio, producto_nombre, 
               tippro_nombre, producto_fechacreacion, natcar_nombre, usuario_nombre
        FROM products 
        WHERE producto_codigo = %s
        """
        results = await self.db.execute_query(query, (producto_codigo,))
        return ProductModel(**results[0]) if results else None
    
    async def get_products_by_category(self, categoria: str, limit: int = 50) -> List[ProductModel]:
        """Obtiene productos por categoría (tippro_nombre o natcar_nombre)"""
        query = """
        SELECT producto_codigo, producto_codigo_ministerio, producto_nombre, 
               tippro_nombre, producto_fechacreacion, natcar_nombre, usuario_nombre
        FROM products 
        WHERE tippro_nombre LIKE %s OR natcar_nombre LIKE %s
        ORDER BY producto_nombre
        LIMIT %s
        """
        search_pattern = f"%{categoria}%"
        results = await self.db.execute_query(query, (search_pattern, search_pattern, limit))
        return [ProductModel(**row) for row in results]
    
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

    # Métodos para tabla tb_empaque
    async def get_empaques(self, limit: int = 50, offset: int = 0) -> List[EmpaqueModel]:
        """Obtiene lista de empaques con paginación"""
        try:
            query = """
                SELECT id, codigo_ministerio, nome, usuario, data_criacao, data_modificacao
                FROM tb_empaque
                ORDER BY id ASC
                LIMIT %s OFFSET %s
            """
            
            results = await self.db.execute_query(query, (limit, offset))
            
            empaques = []
            for row in results:
                empaque = EmpaqueModel(
                    id=row['id'],
                    codigo_ministerio=row['codigo_ministerio'],
                    nome=row['nome'],
                    usuario=row['usuario'],
                    data_criacao=row['data_criacao'],
                    data_modificacao=row['data_modificacao']
                )
                empaques.append(empaque)
            
            return empaques
        except Exception as e:
            logger.error(f"Error al obtener empaques: {e}")
            return []
    
    async def get_empaque_by_id(self, empaque_id: int) -> Optional[EmpaqueModel]:
        """Obtiene un empaque específico por su ID"""
        try:
            query = """
                SELECT id, codigo_ministerio, nome, usuario, data_criacao, data_modificacao
                FROM tb_empaque
                WHERE id = %s
            """
            
            results = await self.db.execute_query(query, (empaque_id,))
            
            if results:
                row = results[0]
                return EmpaqueModel(
                    id=row['id'],
                    codigo_ministerio=row['codigo_ministerio'],
                    nome=row['nome'],
                    usuario=row['usuario'],
                    data_criacao=row['data_criacao'],
                    data_modificacao=row['data_modificacao']
                )
            return None
        except Exception as e:
            logger.error(f"Error al obtener empaque por ID: {e}")
            return None
    
    async def search_empaques_by_name(self, search_term: str, limit: int = 20) -> List[EmpaqueModel]:
        """Busca empaques por nombre con coincidencia parcial"""
        try:
            query = """
                SELECT id, codigo_ministerio, nome, usuario, data_criacao, data_modificacao
                FROM tb_empaque
                WHERE nome LIKE %s
                ORDER BY 
                    CASE 
                        WHEN nome LIKE %s THEN 1
                        WHEN nome LIKE %s THEN 2
                        ELSE 3
                    END,
                    nome ASC
                LIMIT %s
            """
            
            search_pattern = f"%{search_term}%"
            starts_with = f"{search_term}%"
            word_starts = f"% {search_term}%"
            
            results = await self.db.execute_query(
                query, 
                (search_pattern, starts_with, word_starts, limit)
            )
            
            empaques = []
            for row in results:
                empaque = EmpaqueModel(
                    id=row['id'],
                    codigo_ministerio=row['codigo_ministerio'],
                    nome=row['nome'],
                    usuario=row['usuario'],
                    data_criacao=row['data_criacao'],
                    data_modificacao=row['data_modificacao']
                )
                empaques.append(empaque)
            
            return empaques
        except Exception as e:
            logger.error(f"Error al buscar empaques por nombre: {e}")
            return []


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
        Busca directamente en llamadas_conductores por elevenlabs_conversation_id,
        que ya fue almacenado al iniciar la llamada en ProcessElevenLabsCall.
        """
        # Buscar directamente el conductor por su elevenlabs_conversation_id
        query_conductor = """
        SELECT lc.*, cm.ciudad_origen, cm.ciudad_destino, cm.tipo_producto, cm.tipo_embajale,
               cm.peso_carga, cm.vehiculo_requerido, cm.fecha_hora_descargue_cargue
        FROM llamadas_conductores lc
        LEFT JOIN cotizacion_models cm ON lc.cotizacion_id = cm.id
        WHERE lc.elevenlabs_conversation_id = %s
        LIMIT 1
        """
        conductor_results = await self.db.execute_query(query_conductor, (conversation_id,))
        
        if not conductor_results or len(conductor_results) == 0:
            # Fallback: buscar via tabla llamadas si no se encuentra directamente
            query_llamada = """
            SELECT id_llamada, id_cotizacion, conductor_id, elevenlabs_conversation_id, status
            FROM llamadas 
            WHERE elevenlabs_conversation_id = %s 
            LIMIT 1
            """
            llamada_results = await self.db.execute_query(query_llamada, (conversation_id,))
            
            if not llamada_results or len(llamada_results) == 0:
                return None
                
            llamada = llamada_results[0]
            conductor_id = llamada.get('conductor_id')
            
            if conductor_id:
                # Buscar por conductor_id (FK directa)
                query_by_id = """
                SELECT lc.*, cm.ciudad_origen, cm.ciudad_destino, cm.tipo_producto, cm.tipo_embajale,
                       cm.peso_carga, cm.vehiculo_requerido, cm.fecha_hora_descargue_cargue
                FROM llamadas_conductores lc
                LEFT JOIN cotizacion_models cm ON lc.cotizacion_id = cm.id
                WHERE lc.id = %s
                LIMIT 1
                """
                conductor_results = await self.db.execute_query(query_by_id, (conductor_id,))
                
                if conductor_results and len(conductor_results) > 0:
                    return conductor_results[0]
            
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
            update_fields.append("elevenlabs_conversation_id = %s")
            params.append(call_id)
        
        if respuesta_llamada is not None:
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
    
    def _resolve_driver_decision_state(self, decision: Any) -> Dict[str, Any]:
        """Convierte decisiones del agente a estados de BD consistentes."""
        if isinstance(decision, bool) or isinstance(decision, int):
            decision_int = int(decision)
            return {
                'normalized_decision': 'accepted' if decision_int == 1 else 'rejected',
                'response_status': 'accepted' if decision_int == 1 else 'rejected',
                'estado_llamada': 'completada' if decision_int == 1 else 'fallida',
                'respuesta_llamada': 'accepted' if decision_int == 1 else 'rejected',
                'historical_decision': decision_int,
                'call_status': 'completed',
                'llamada_status': 'aceptada' if decision_int == 1 else 'rechazada',
                'queue_status': 'completed',
                'failure_reason': None,
                'requires_retry': False,
            }

        normalized = str(decision).strip().lower()
        if normalized in {'1', 'si', 'sí', 'accept', 'accepted', 'acepta', 'aceptado'}:
            return self._resolve_driver_decision_state(1)
        if normalized in {'0', 'no', 'reject', 'rejected', 'rechaza', 'rechazado'}:
            return self._resolve_driver_decision_state(0)
        if normalized in {'retry', 'reintento', 'reintentar', 'voicemail', 'buzon', 'buzón', 'no_answer'}:
            return {
                'normalized_decision': 'retry',
                'response_status': 'pending',
                'estado_llamada': 'pendiente',
                'respuesta_llamada': '',
                'historical_decision': None,
                'call_status': 'no_answer',
                'llamada_status': 'finalizada',
                'queue_status': 'failed',
                'failure_reason': 'Reintento solicitado por agente',
                'requires_retry': True,
            }
        if normalized in {'maybe', 'tal_vez', 'talvez', 'indeciso', 'supervisor', 'pending', 'seguimiento'}:
            return {
                'normalized_decision': 'maybe',
                'response_status': 'pending',
                'estado_llamada': 'completada',
                'respuesta_llamada': 'pending_supervisor',
                'historical_decision': None,
                'call_status': 'completed',
                'llamada_status': 'finalizada',
                'queue_status': 'completed',
                'failure_reason': None,
                'requires_retry': False,
            }

        return self._resolve_driver_decision_state(0)

    async def save_driver_decision_new(
        self,
        identificador_unico: str,
        conversation_id: str,
        decision: Any,
        notas: str = None,
    ) -> bool:
        """Guarda la decision final del conductor y sincroniza tablas de llamadas."""
        try:
            decision_state = self._resolve_driver_decision_state(decision)

            query_conductor = """
            SELECT id, cotizacion_id, nombre_conductor, telefono, placa, tipo_vehiculo,
                   clase_vehiculo, chofer_id_local, elevenlabs_conversation_id, call_id
            FROM llamadas_conductores
            WHERE identificador_unico = %s AND deleted_at IS NULL
            """
            result = await self.db.execute_query(query_conductor, (identificador_unico,))

            if not result:
                logger.error(f"No se encontro conductor con identificador_unico: {identificador_unico}")
                return False

            conductor_data = result[0]
            conductor_id = conductor_data['id']
            cotizacion_id = conductor_data.get('cotizacion_id')

            llamada_data = None
            if conversation_id and conversation_id != 'N/A':
                llamada_result = await self.db.execute_query(
                    """
                    SELECT id_llamada, conductor_id, chofer_id, numero_destino, elevenlabs_conversation_id
                    FROM llamadas
                    WHERE elevenlabs_conversation_id = %s
                    LIMIT 1
                    """,
                    (conversation_id,),
                )
                llamada_data = llamada_result[0] if llamada_result else None

                conversation_matches = conversation_id in {
                    conductor_data.get('elevenlabs_conversation_id'),
                    conductor_data.get('call_id'),
                    llamada_data.get('elevenlabs_conversation_id') if llamada_data else None,
                }
                llamada_matches = bool(llamada_data and llamada_data.get('conductor_id') == conductor_id)

                if not (conversation_matches or llamada_matches):
                    logger.error(
                        "No se guarda decision: conversation_id no coincide con identificador_unico",
                        extra={
                            'identificador_unico': identificador_unico,
                            'conversation_id': conversation_id,
                            'conductor_id': conductor_id,
                            'llamada_conductor_id': llamada_data.get('conductor_id') if llamada_data else None,
                        },
                    )
                    return False

            update_success = await self.update_estado_llamada_conductor(
                identificador_unico=identificador_unico,
                estado_llamada=decision_state['estado_llamada'],
                call_id=conversation_id,
                respuesta_llamada=decision_state['respuesta_llamada'],
                notas=notas,
            )

            if not update_success:
                logger.error(f"Error actualizando llamadas_conductores para {identificador_unico}")
                return False

            if conversation_id and conversation_id != 'N/A':
                await self.db.execute_update(
                    """
                    UPDATE llamadas
                    SET status = %s,
                        call_status = %s,
                        queue_status = %s,
                        failure_reason = %s,
                        call_completed_at = NOW(),
                        processing_completed_at = NOW(),
                        call_notes = %s,
                        updated_at = NOW()
                    WHERE elevenlabs_conversation_id = %s
                    """,
                    (
                        decision_state['llamada_status'],
                        decision_state['call_status'],
                        decision_state['queue_status'],
                        decision_state['failure_reason'],
                        notas,
                        conversation_id,
                    ),
                )

            if not decision_state['requires_retry'] and cotizacion_id and conversation_id and conversation_id != 'N/A':
                resolved_driver_id = conductor_data.get('chofer_id_local') or conductor_id
                try:
                    existing_response = await self.db.execute_query(
                        "SELECT id FROM driver_call_responses WHERE elevenlabs_conversation_id = %s LIMIT 1",
                        (conversation_id,),
                    )

                    if existing_response:
                        await self.db.execute_update(
                            """
                            UPDATE driver_call_responses
                            SET cotizacion_id = %s, driver_id = %s, driver_name = %s,
                                driver_phone = %s, vehicle_type = %s, vehicle_plate = %s,
                                call_status = %s, response_status = %s, response_time = NOW(),
                                notes = %s, updated_at = NOW()
                            WHERE id = %s
                            """,
                            (
                                cotizacion_id,
                                resolved_driver_id,
                                conductor_data.get('nombre_conductor') or 'Conductor',
                                conductor_data.get('telefono'),
                                conductor_data.get('tipo_vehiculo') or conductor_data.get('clase_vehiculo'),
                                conductor_data.get('placa'),
                                decision_state['call_status'],
                                decision_state['response_status'],
                                notas,
                                existing_response[0]['id'],
                            ),
                        )
                    else:
                        await self.db.execute_update(
                            """
                            INSERT INTO driver_call_responses
                            (cotizacion_id, driver_id, driver_name, driver_phone, vehicle_type, vehicle_plate,
                             call_status, response_status, response_time, elevenlabs_conversation_id, notes, created_at, updated_at)
                            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, NOW(), %s, %s, NOW(), NOW())
                            """,
                            (
                                cotizacion_id,
                                resolved_driver_id,
                                conductor_data.get('nombre_conductor') or 'Conductor',
                                conductor_data.get('telefono'),
                                conductor_data.get('tipo_vehiculo') or conductor_data.get('clase_vehiculo'),
                                conductor_data.get('placa'),
                                decision_state['call_status'],
                                decision_state['response_status'],
                                conversation_id,
                                notas,
                            ),
                        )
                except Exception as response_error:
                    logger.warning(f"No se pudo sincronizar driver_call_responses: {response_error}")

            historical_decision = decision_state['historical_decision']
            if cotizacion_id and historical_decision is not None:
                try:
                    resolved_driver_id = conductor_data.get('chofer_id_local') or conductor_id
                    await self.db.execute_update(
                        """
                        INSERT INTO call_driver_decisions
                        (cotizacion_model_id, driver_id, decision, created_at, updated_at)
                        VALUES (%s, %s, %s, NOW(), NOW())
                        """,
                        (cotizacion_id, resolved_driver_id, historical_decision),
                    )
                    logger.info(
                        f"Decision guardada en call_driver_decisions: cotizacion_id={cotizacion_id}, "
                        f"driver_id={resolved_driver_id}, decision={historical_decision}"
                    )
                except Exception as history_error:
                    logger.warning(f"No se pudo guardar call_driver_decisions para {identificador_unico}: {history_error}")
            elif not cotizacion_id:
                logger.warning(f"No se guardo en call_driver_decisions: cotizacion_id es NULL para {identificador_unico}")

            return True

        except Exception as e:
            logger.error(f"Error en save_driver_decision_new: {e}")
            return False
    
    async def search_city_by_name(self, city_name: str) -> Optional[Dict[str, Any]]:
        """
        Busca una ciudad en la BD por nombre (fuzzy search).
        Retorna ciudad_codigo, ciudad_nombre y ciudad_codigodane
        """
        if not city_name or len(city_name) < 3:
            return None
        
        # Normalizar nombre: capitalizar primera letra de cada palabra
        normalized_name = city_name.strip().upper()
        
        try:
            # Búsqueda 1: Coincidencia exacta
            query = """
            SELECT ciudad_codigo, ciudad_nombre, ciudad_codigodane 
            FROM cities 
            WHERE UPPER(ciudad_nombre) = %s
            LIMIT 1
            """
            result = await self.db.execute_query(query, (normalized_name,))
            
            if result:
                logger.info(f"✅ Ciudad encontrada (exacta): {result[0]['ciudad_nombre']} (código: {result[0]['ciudad_codigo']})")
                return result[0]
            
            # Búsqueda 2: Coincidencia parcial (LIKE)
            query = """
            SELECT ciudad_codigo, ciudad_nombre, ciudad_codigodane 
            FROM cities 
            WHERE ciudad_nombre LIKE %s
            ORDER BY CHAR_LENGTH(ciudad_nombre) ASC
            LIMIT 1
            """
            result = await self.db.execute_query(query, (f"%{normalized_name}%",))
            
            if result:
                logger.info(f"✅ Ciudad encontrada (parcial): {result[0]['ciudad_nombre']} (código: {result[0]['ciudad_codigo']})")
                return result[0]
            
            # Búsqueda 3: Por palabras clave (primeras 2 letras)
            if len(normalized_name) >= 3:
                prefix = normalized_name[:3]
                query = """
                SELECT ciudad_codigo, ciudad_nombre, ciudad_codigodane 
                FROM cities 
                WHERE ciudad_nombre LIKE %s
                ORDER BY CHAR_LENGTH(ciudad_nombre) ASC
                LIMIT 1
                """
                result = await self.db.execute_query(query, (f"{prefix}%",))
                
                if result:
                    logger.info(f"⚠️ Ciudad encontrada (aproximada): {result[0]['ciudad_nombre']} (código: {result[0]['ciudad_codigo']})")
                    return result[0]
            
            logger.warning(f"❌ No se encontró ciudad para: {city_name}")
            return None
            
        except Exception as e:
            logger.error(f"Error buscando ciudad '{city_name}': {e}")
            return None
     
    # Métodos para tabla clients
    async def get_client_by_id(self, client_id: int) -> Optional[ClientModel]:
        """Obtiene un cliente por ID"""
        query = "SELECT * FROM clients WHERE id = %s"
        results = await self.db.execute_query(query, (client_id,))
        return ClientModel(**results[0]) if results else None
    
    async def get_client_by_document(self, documento: str) -> Optional[ClientModel]:
        """Busca cliente por número de documento/NIT"""
        query = "SELECT * FROM clients WHERE documento = %s"
        results = await self.db.execute_query(query, (documento,))
        return ClientModel(**results[0]) if results else None
    
    async def search_clients_by_name(self, name: str) -> List[ClientModel]:
        """Busca clientes por nombre (coincidencia parcial)"""
        query = """
        SELECT * FROM clients 
        WHERE cliente LIKE %s 
        ORDER BY cliente ASC 
        LIMIT 10
        """
        results = await self.db.execute_query(query, (f"%{name}%",))
        return [ClientModel(**row) for row in results]
    
    async def create_client(self, client_data: Dict[str, Any]) -> Optional[ClientModel]:
        """Crea un nuevo cliente"""
        query = """
        INSERT INTO clients (cliente, documento, telefono, direccion, ciudad, created_at, updated_at)
        VALUES (%s, %s, %s, %s, %s, NOW(), NOW())
        """
        params = (
            client_data.get('cliente'),
            client_data.get('documento'),
            client_data.get('telefono'),
            client_data.get('direccion'),
            client_data.get('ciudad')
        )
        affected_rows = await self.db.execute_update(query, params)
        if affected_rows > 0:
            result = await self.db.execute_query("SELECT LAST_INSERT_ID() as id")
            new_id = result[0]['id']
            return await self.get_client_by_id(new_id)
        return None
    
    async def search_or_create_client(self, nit: str = None, name: str = None, 
                                      additional_data: Dict[str, Any] = None) -> Dict[str, Any]:
        """
        Busca cliente por NIT o nombre. Si no existe, ofrece crearlo.
        Retorna dict con 'found' (bool), 'client' (ClientModel|None), 'prompt' (str|None)
        """
        client = None
        
        # Buscar por NIT
        if nit:
            client = await self.get_client_by_document(nit)
        
        # Buscar por nombre si no se encontró por NIT
        if not client and name:
            clients = await self.search_clients_by_name(name)
            if clients:
                client = clients[0]
        
        if client:
            return {
                'found': True,
                'client': client,
                'message': f"Cliente encontrado: {client.cliente} (NIT: {client.documento})"
            }
        
        # Cliente no encontrado
        if additional_data and additional_data.get('create_if_not_exists'):
            # Crear automáticamente
            create_data = {
                'cliente': name or additional_data.get('cliente'),
                'documento': nit or additional_data.get('documento'),
                'telefono': additional_data.get('telefono'),
                'direccion': additional_data.get('direccion'),
                'ciudad': additional_data.get('ciudad')
            }
            new_client = await self.create_client(create_data)
            if new_client:
                return {
                    'found': True,
                    'client': new_client,
                    'message': f"Cliente creado exitosamente: {new_client.cliente} (NIT: {new_client.documento}). Continuando con el proceso de cotización..."
                }
        
        return {
            'found': False,
            'client': None,
            'message': 'Cliente no encontrado en el sistema.',
            'prompt': '¿Desea crear un nuevo cliente? Por favor proporcione: nombre/razón social (requerido), NIT/documento (requerido), teléfono, dirección y ciudad.',
            'required_fields': {
                'cliente': 'Nombre o razón social (requerido)',
                'documento': 'NIT o documento (requerido)',
                'telefono': 'Teléfono (opcional)',
                'direccion': 'Dirección (opcional)',
                'ciudad': 'Ciudad (opcional)'
            }
        }

# Instancia global del repositorio
repository = DatabaseRepository()