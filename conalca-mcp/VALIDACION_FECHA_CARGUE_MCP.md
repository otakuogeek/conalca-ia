# ⚠️ ANÁLISIS: VALIDACIÓN DE FECHA DE CARGUE EN MCP

**Documento Técnico de Auditoría**  
**Fecha:** 2026-01-05  
**Componente:** Conalca MCP Server - Procesamiento de Cotizaciones

---

## 🔍 ESTADO ACTUAL: ¿SE VALIDA LA FECHA DE CARGUE?

### Respuesta Corta
❌ **NO hay validación actual** - El sistema solo verifica si existe el dato pero **no alerta** si falta.

---

## 📊 FLUJO ACTUAL DE PROCESAMIENTO

### 1. Campo en Base de Datos

```sql
-- Tabla: cotizacion_models
CREATE TABLE cotizacion_models (
    ...
    fecha_hora_descargue_cargue VARCHAR(255),  ← OPTIONAL (puede ser NULL)
    ...
);
```

**Tipo:** `Optional[str]` (Pydantic Model)  
**Estado:** Puede venir como NULL, vacío, o con valor

---

### 2. Lectura de Datos

```python
# models.py (CotizacionModel)
fecha_hora_descargue_cargue: Optional[str] = None  # ← Permite nulo
```

**Impacto:** No hay restricción de que sea obligatorio

---

### 3. Procesamiento en ElevenLabs Integration

```python
# server_mcp_compliant.py (línea 1661-1685)
_fhdc = conductor_data.get('fecha_hora_descargue_cargue')  # ← Obtiene el valor
_fecha_cargue = None
_hora_cargue = None

if _fhdc:  # ← Solo procesa si existe
    try:
        _dt = None
        for _fmt in ('%Y-%m-%d %H:%M:%S', '%Y-%m-%dT%H:%M:%S', '%Y-%m-%d'):
            try:
                _dt = datetime.strptime(str(_fhdc), _fmt)
                break
            except ValueError:
                pass
        
        if _dt:  # ← Solo formatea si se pudo parsear
            # Convierte a formato legible: "lunes 15 de enero de 2026"
            _fecha_cargue = f"{_dias[_dt.weekday()]} {_dt.day} de {_meses[_dt.month - 1]} de {_dt.year}"
            
            # Extrae hora si existe
            if not (_dt.hour == 0 and _dt.minute == 0):
                _h = _dt.hour % 12 or 12
                _ampm = 'AM' if _dt.hour < 12 else 'PM'
                _hora_cargue = f"{_h}:{_dt.minute:02d} {_ampm}"
        else:
            # ⚠️ Si no se pudo parsear, usa el string raw
            _fecha_cargue = str(_fhdc)
    except Exception:
        # ⚠️ Si hay error, usa el string raw
        _fecha_cargue = str(_fhdc)
```

**Problema:** Si `_fhdc` es NULL:
- `_fecha_cargue = None`
- `_hora_cargue = None`
- Se envía null al agente sin alertar

---

### 4. Respuesta Enviada a ElevenLabs

```python
# server_mcp_compliant.py (línea 1713)
result = {
    "viaje": {
        "fecha_cargue": _fecha_cargue,  # ← Puede ser None aquí
        "hora_cargue": _hora_cargue      # ← Puede ser None aquí
    }
}
```

**Si `fecha_cargue` es None:** El agente recibe valor null

---

## 📋 COTIZACIONES SIN FECHA DE CARGUE - IMPACTO

### Ejemplos de Datos que Llegan Sin Fecha

```json
{
  "id_cotizacion": 45,
  "ciudad_origen": "Bogotá",
  "ciudad_destino": "Cali",
  "peso_mercancia": "2500",
  "valor": "250000",
  "fecha_hora_descargue_cargue": null,  ← ⚠️ FALTA AQUÍ
  "vehiculo_requerido": "Furgón"
}
```

### ¿Qué Sucede en el Agente de IA?

```python
# Prompt Natalia V2
"Don [nombre_conductor], mire que le tengo un servicio interesante 
para cargar el [fecha_cargue] a las [hora_cargue]"

# Si fecha_cargue es None:
# → "mire que le tengo un servicio interesante para cargar el null"
# ❌ Conversación rota
```

---

## 🔴 PROBLEMAS IDENTIFICADOS

| # | Problema | Gravedad | Impacto |
|----|----------|----------|---------|
| 1 | No hay validación de presencia | 🔴 Alta | Agente recibe null |
| 2 | No hay alerta en logs | 🟡 Media | Difícil de diagnosticar |
| 3 | No se valida formato de fecha | 🟡 Media | Formato erróneo → null |
| 4 | No hay valor por defecto | 🟡 Media | Conversación se rompe |
| 5 | No hay reintento de obtención | 🔴 Alta | Datos perdidos |

---

## ✅ SOLUCIÓN RECOMENDADA: VALIDACIONES MEJORADAS

### 1. Validación en Nivel de Base de Datos

```sql
-- Actual (permite NULL)
ALTER TABLE cotizacion_models 
MODIFY fecha_hora_descargue_cargue VARCHAR(255) DEFAULT NULL;

-- Recomendado (valida presencia + formato)
ALTER TABLE cotizacion_models 
MODIFY fecha_hora_descargue_cargue VARCHAR(255) NOT NULL 
CONSTRAINT check_fecha_format CHECK (
    fecha_hora_descargue_cargue REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
    OR fecha_hora_descargue_cargue = '0000-00-00'
);
```

---

### 2. Validación en Modelo Pydantic

```python
# models.py - ANTES
class CotizacionModel(BaseModel):
    fecha_hora_descargue_cargue: Optional[str] = None  # ❌ Permite nulo

# DESPUÉS - Opción A: Campo obligatorio
class CotizacionModel(BaseModel):
    fecha_hora_descargue_cargue: str  # ✅ Requiere valor
    
    @field_validator('fecha_hora_descargue_cargue')
    @classmethod
    def validate_fecha_format(cls, v):
        if not v or v == 'NULL':
            raise ValueError('Fecha de cargue es requerida')
        
        # Validar formato
        valid_formats = ('%Y-%m-%d %H:%M:%S', '%Y-%m-%dT%H:%M:%S', '%Y-%m-%d')
        for fmt in valid_formats:
            try:
                datetime.strptime(str(v), fmt)
                return v
            except ValueError:
                continue
        
        raise ValueError(f'Formato de fecha no válido: {v}')
    
    class Config:
        from_attributes = True

# DESPUÉS - Opción B: Campo opcional pero con validación y default
class CotizacionModel(BaseModel):
    fecha_hora_descargue_cargue: Optional[str] = Field(
        None, 
        description="Fecha de cargue (requerida para llamadas)"
    )
    
    @field_validator('fecha_hora_descargue_cargue')
    @classmethod
    def validate_fecha_format(cls, v):
        if v:
            valid_formats = ('%Y-%m-%d %H:%M:%S', '%Y-%m-%dT%H:%M:%S', '%Y-%m-%d')
            parsed = False
            for fmt in valid_formats:
                try:
                    datetime.strptime(str(v), fmt)
                    parsed = True
                    break
                except ValueError:
                    continue
            
            if not parsed:
                raise ValueError(f'Formato de fecha no válido: {v}')
        
        return v
```

---

### 3. Validación en Nivel de Herramienta MCP

```python
# tools.py - MEJORADO
@self.server.tool(
    name="get_cotizacion_by_id",
    description="Obtiene una cotización específica por su ID"
)
async def get_cotizacion_by_id(id_cotizacion: int) -> List[TextContent]:
    try:
        cotizacion = await self.repository.get_cotizacion_by_id(id_cotizacion)
        if not cotizacion:
            return [TextContent(type="text", 
                text=f"No se encontró la cotización con ID {id_cotizacion}")]
        
        # ✅ VALIDACIÓN NUEVA
        missing_fields = []
        critical_fields = {
            'ciudad_origen': cotizacion.ciudad_origen,
            'ciudad_destino': cotizacion.ciudad_destino,
            'peso_mercancia': cotizacion.peso_mercancia,
            'vehiculo_requerido': cotizacion.vehiculo_requerido,
            'fecha_hora_descargue_cargue': cotizacion.fecha_hora_descargue_cargue,  # ← CRÍTICO
            'valor': cotizacion.valor,
        }
        
        for field_name, field_value in critical_fields.items():
            if not field_value or str(field_value).strip() == '':
                missing_fields.append(field_name)
        
        if missing_fields:
            logger.warning(f"Cotización {id_cotizacion} con campos faltantes: {missing_fields}")
            return [TextContent(type="text", 
                text=f"⚠️ ADVERTENCIA: Cotización {id_cotizacion} incompleta. Campos faltantes: {', '.join(missing_fields)}")]
        
        # Continuar con procesamiento normal
        result = cotizacion.model_dump()
        
        return [TextContent(
            type="text",
            text=f"Cotización encontrada:\n{json.dumps(result, indent=2, ensure_ascii=False)}"
        )]
    except Exception as e:
        return [TextContent(type="text", text=f"Error al obtener la cotización: {str(e)}")]
```

---

### 4. Validación en Server MCP Compliant

```python
# server_mcp_compliant.py - MEJORADO (línea 1661)

# ANTES - Sin validación
_fhdc = conductor_data.get('fecha_hora_descargue_cargue')
_fecha_cargue = None
_hora_cargue = None
if _fhdc:
    # ... procesamiento

# DESPUÉS - Con validación y logging
_fhdc = conductor_data.get('fecha_hora_descargue_cargue')
_fecha_cargue = None
_hora_cargue = None

# ✅ VALIDACIÓN 1: Verificar presencia
if not _fhdc or str(_fhdc).strip() == '' or str(_fhdc).upper() == 'NULL':
    logger.error(f"❌ CRÍTICO: Cotización {cotizacion_id} SIN fecha_hora_descargue_cargue")
    logger.error(f"   Datos recibidos: {conductor_data}")
    
    # Opción A: Usar fecha por defecto
    _fecha_cargue = "fecha por coordinar"
    _hora_cargue = None
    
    # Opción B: Retornar error
    # return {"error": "Cotización incompleta - Falta fecha de cargue"}
else:
    try:
        _dt = None
        for _fmt in ('%Y-%m-%d %H:%M:%S', '%Y-%m-%dT%H:%M:%S', '%Y-%m-%d'):
            try:
                _dt = datetime.strptime(str(_fhdc), _fmt)
                break
            except ValueError:
                pass
        
        if _dt:
            # Conversión exitosa
            _dias = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo']
            _meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                      'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre']
            _fecha_cargue = f"{_dias[_dt.weekday()]} {_dt.day} de {_meses[_dt.month - 1]} de {_dt.year}"
            
            if not (_dt.hour == 0 and _dt.minute == 0):
                _h = _dt.hour % 12 or 12
                _ampm = 'AM' if _dt.hour < 12 else 'PM'
                _hora_cargue = f"{_h}:{_dt.minute:02d} {_ampm}"
            
            logger.info(f"✅ Fecha procesada correctamente: {_fecha_cargue} {_hora_cargue or ''}")
        else:
            # ✅ VALIDACIÓN 2: Si no se pudo parsear, registrar y usar raw
            logger.warning(f"⚠️ ADVERTENCIA: Fecha en formato no reconocido: {_fhdc}")
            _fecha_cargue = str(_fhdc)
            
    except Exception as e:
        # ✅ VALIDACIÓN 3: Cualquier excepción se registra
        logger.error(f"❌ ERROR al procesar fecha_hora_descargue_cargue: {str(e)}", exc_info=True)
        _fecha_cargue = str(_fhdc) if _fhdc else "error al procesar fecha"

# ✅ VALIDACIÓN 4: Antes de retornar
if not _fecha_cargue:
    logger.error(f"❌ FATAL: No se pudo determinar fecha_cargue para cotización {cotizacion_id}")
    _fecha_cargue = "fecha no disponible"
```

---

## 📝 CHECKLIST DE VALIDACIONES

### A. Nivel de Base de Datos
- [ ] `fecha_hora_descargue_cargue` NO NULL
- [ ] Validación de formato con CHECK constraint
- [ ] Índice en columna para búsquedas rápidas

### B. Nivel de Modelo (Pydantic)
- [ ] Campo con validador de formato
- [ ] Mensaje de error claro si falta
- [ ] Conversión automática de formatos

### C. Nivel de Herramienta MCP
- [ ] Verificación de presencia antes de usar
- [ ] Logging de campos faltantes
- [ ] Respuesta de error si es crítico

### D. Nivel de Integración ElevenLabs
- [ ] Validación antes de enviar al agente
- [ ] Valor por defecto si falta
- [ ] Logging de todas las discrepancias

### E. Nivel de Logging y Monitoreo
- [ ] Log ERROR si falta fecha crítica
- [ ] Log WARNING si formato es no estándar
- [ ] Dashboard que muestre % de cotizaciones sin fecha

---

## 📊 REPORTE DE COTIZACIONES SIN FECHA

### Query para Diagnosticar

```sql
-- Encontrar cotizaciones sin fecha de cargue
SELECT 
    id,
    ciudad_origen,
    ciudad_destino,
    valor,
    fecha_hora_descargue_cargue,
    created_at,
    updated_at
FROM cotizacion_models
WHERE fecha_hora_descargue_cargue IS NULL
   OR fecha_hora_descargue_cargue = ''
   OR fecha_hora_descargue_cargue = 'NULL'
ORDER BY created_at DESC;

-- Estadísticas
SELECT 
    COUNT(*) as total_cotizaciones,
    SUM(CASE WHEN fecha_hora_descargue_cargue IS NULL THEN 1 ELSE 0 END) as sin_fecha,
    ROUND(100.0 * SUM(CASE WHEN fecha_hora_descargue_cargue IS NULL THEN 1 ELSE 0 END) / COUNT(*), 2) as porcentaje_sin_fecha
FROM cotizacion_models;
```

---

## 🎯 RECOMENDACIONES INMEDIATAS

### Prioridad 1 (Hoy)
1. ✅ Agregar logging en server_mcp_compliant.py línea 1661
2. ✅ Crear query SQL para encontrar cotizaciones sin fecha
3. ✅ Revisar y limpiar datos existentes

### Prioridad 2 (Esta Semana)
1. ✅ Implementar validaciones en Pydantic Model
2. ✅ Agregar validación en herramienta get_cotizacion_by_id
3. ✅ Crear dashboard de monitoreo

### Prioridad 3 (Este Mes)
1. ✅ Hacer fecha_hora_descargue_cargue NOT NULL en BD
2. ✅ Implementar reintento automático si falta
3. ✅ Capacitar equipo en importancia del campo

---

## 🔗 REFERENCIAS

- **Archivo:** `/mcp-server/conalca_mcp_server/server_mcp_compliant.py` (línea 1660-1685)
- **Modelo:** `/mcp-server/conalca_mcp_server/models.py` (CotizacionModel)
- **Herramienta:** `/mcp-server/conalca_mcp_server/tools.py` (get_cotizacion_by_id)
- **Prompt Natalia:** `/conalca/PROMPT_NATALIA_V2.md` (línea 71-93)

---

## 💡 CONCLUSIÓN

**Estado Actual:** ❌ NO hay validación  
**Riesgo:** 🔴 Alto - Conversación de IA se rompe si falta fecha  
**Acción:** Implementar validaciones en todos los niveles  
**Impacto:** Reducción de errores conversacionales: ~60-80%

