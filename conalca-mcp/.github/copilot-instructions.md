# Conalca MCP Server — Project Guidelines

Servidor MCP (Model Context Protocol) streameable para ElevenLabs que gestiona transporte, cotizaciones, vehículos y llamadas contra una base de datos MySQL (conalca / ai_transport).

## Idioma

- **Código nuevo y comentarios:** español (variables, docstrings, mensajes de error).
- **Identificadores técnicos** (clases, tipos, imports): inglés cuando ya existen así.
- **Comunicación con el usuario / documentación:** español.

## Arquitectura

```
mcp-server/
  conalca_mcp_server/
    server.py              # FastAPI + MCP JSON-RPC 2.0, WebSocket, SSE, ElevenLabs
    tools.py               # Herramientas MCP (funciones @server.tool)
    models.py              # Pydantic models + DatabaseRepository (SQL manual)
    database.py            # Pool aiomysql (async)
```

- **Sin ORM** — queries SQL manuales en `DatabaseRepository`. Usar parámetros `%s` (nunca f-strings con datos del usuario).
- **Async everywhere** — todo método de DB y herramienta es `async`.
- Las herramientas MCP retornan `List[TextContent]` con JSON (`ensure_ascii=False`, `indent=2`).
- `DateTimeEncoder` para serializar `datetime`/`date` a ISO 8601.

## Build & Run

```bash
# Instalar dependencias (desde mcp-server/)
pip install -r requirements.txt          # o: poetry install

# Ejecutar servidor en modo HTTP
python -m conalca_mcp_server.server http 18840

# Con uvicorn (producción)
uvicorn conalca_mcp_server.server_mcp_compliant:app --host 127.0.0.1 --port 18840

# Gestión del servicio systemd
./manage_service.sh start|stop|restart|status|logs
```

## Tests

```bash
python test_server.py
python test_nuevo_flujo.py
```

No hay framework de testing formal; los tests son scripts ad-hoc.

## Configuración

Variables de entorno leídas desde `.env` en la raíz del repo:

| Variable | Propósito |
|----------|-----------|
| `DB_HOST`, `DB_PORT`, `DB_DATABASE` | Conexión MySQL |
| `DB_USERNAME`, `DB_PASSWORD` | Credenciales DB |
| `MCP_SERVER_PORT` (default 18840) | Puerto del servidor |
| `MCP_SERVER_HOST` (default 127.0.0.1) | Host de escucha |

## Convenciones

- **Error handling en tools:** cada herramienta envuelve su cuerpo en `try/except Exception` y retorna el error como `TextContent`.
- **Paginación:** `limit`/`offset` en todas las consultas de listado.
- **Pool de conexiones:** `minsize=1, maxsize=10`, `charset=utf8mb4`, `autocommit=True`.
- **Logging:** `logging.basicConfig` con formato `%(asctime)s - %(name)s - %(levelname)s - %(message)s`.
- **Seguridad SQL:** siempre usar parámetros `%s` en queries; nunca interpolar datos de usuario.

## Documentación existente

La documentación detallada está en archivos `.md` dentro de `mcp-server/`:

- [README.md](mcp-server/README.md) — Visión general, instalación, herramientas disponibles
- [INDICE_DOCUMENTACION_v2.md](mcp-server/INDICE_DOCUMENTACION_v2.md) — Índice maestro de documentación
- [DOCUMENTACION_COMPLETA_HERRAMIENTAS_MCP.md](mcp-server/DOCUMENTACION_COMPLETA_HERRAMIENTAS_MCP.md) — Referencia de herramientas
- [ELEVENLABS_INTEGRATION.md](mcp-server/ELEVENLABS_INTEGRATION.md) — Integración con ElevenLabs
- [RESUMEN_CONFIGURACION_MCP.md](mcp-server/RESUMEN_CONFIGURACION_MCP.md) — Resumen de configuración

## Pitfalls

- `server.py` tiene ~2900 líneas. Antes de modificar, leer la sección relevante.
- El `.env` real está en la **raíz del repo** (nivel superior a `mcp-server/`); `database.py` lo busca con `os.path.dirname(os.path.dirname(...))`.
- Hay múltiples variantes de servidor (`server.py`, `server_mcp_compliant.py`, `server_streaming.py`). El servicio systemd usa `server_mcp_compliant.py`.
