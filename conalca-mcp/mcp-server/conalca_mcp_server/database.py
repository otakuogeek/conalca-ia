import os
import asyncio
import aiomysql
from dotenv import load_dotenv
from typing import Optional, Dict, Any
import logging

# Cargar variables de entorno desde el directorio del proyecto
project_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
load_dotenv(dotenv_path=os.path.join(project_dir, ".env"))

# Configuración de logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class DatabaseConfig:
    """Configuración de la base de datos"""
    
    def __init__(self):
        self.host = os.getenv('DB_HOST', '127.0.0.1')
        self.port = int(os.getenv('DB_PORT', 3306))
        self.database = os.getenv('DB_DATABASE', 'ai_transport')
        self.username = os.getenv('DB_USERNAME', 'biosanar_user')
        self.password = os.getenv('DB_PASSWORD', '/6Tx0eXqFQONTFuoc7aqPicNlPhmuINU')
        
    def get_connection_params(self) -> Dict[str, Any]:
        """Obtiene los parámetros de conexión para aiomysql"""
        return {
            'host': self.host,
            'port': self.port,
            'user': self.username,
            'password': self.password,
            'db': self.database,
            'charset': 'utf8mb4',
            'autocommit': True
        }

class DatabaseConnection:
    """Manager de conexión a la base de datos MySQL"""
    
    def __init__(self, config: DatabaseConfig):
        self.config = config
        self.pool: Optional[aiomysql.Pool] = None
        
    async def initialize(self):
        """Inicializa el pool de conexiones"""
        try:
            self.pool = await aiomysql.create_pool(
                **self.config.get_connection_params(),
                minsize=1,
                maxsize=10,
                echo=False
            )
            logger.info("Pool de conexiones MySQL inicializado correctamente")
        except Exception as e:
            logger.error(f"Error al inicializar la conexión MySQL: {e}")
            raise
    
    async def close(self):
        """Cierra el pool de conexiones"""
        if self.pool:
            self.pool.close()
            await self.pool.wait_closed()
            logger.info("Pool de conexiones MySQL cerrado")
    
    async def execute_query(self, query: str, params: tuple = None) -> list:
        """Ejecuta una query SELECT y retorna los resultados"""
        if not self.pool:
            await self.initialize()
            
        async with self.pool.acquire() as conn:
            async with conn.cursor(aiomysql.DictCursor) as cursor:
                await cursor.execute(query, params)
                result = await cursor.fetchall()
                return result
    
    async def execute_update(self, query: str, params: tuple = None) -> int:
        """Ejecuta una query INSERT/UPDATE/DELETE y retorna el número de filas afectadas"""
        if not self.pool:
            await self.initialize()
            
        async with self.pool.acquire() as conn:
            async with conn.cursor() as cursor:
                await cursor.execute(query, params)
                await conn.commit()
                return cursor.rowcount

# Instancia global de la conexión
db_config = DatabaseConfig()
db_connection = DatabaseConnection(db_config)