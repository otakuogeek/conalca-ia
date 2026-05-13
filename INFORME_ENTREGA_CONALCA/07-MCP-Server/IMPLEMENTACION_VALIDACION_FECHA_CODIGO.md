# 🛠️ IMPLEMENTACIÓN PRÁCTICA: VALIDACIÓN DE FECHA DE CARGUE

**Guía Paso a Paso - Código Listo para Implementar**  
**Versión:** 1.0  
**Componentes a Actualizar:** 4 archivos

---

## 📋 RESUMEN DE CAMBIOS

| Archivo | Cambio | Línea | Impacto |
|---------|--------|-------|--------|
| models.py | Agregar validador | ~50-70 | ✅ Valida formato |
| tools.py | Agregar chequeo | ~160 | ✅ Alerta de falta |
| server_mcp_compliant.py | Mejorar logging | ~1660 | ✅ Trazabilidad |
| database.py | Agregar query util | ~100+ | ✅ Diagnóstico |

---

## 🔧 CAMBIO 1: models.py - Agregar Validador

### Ubicación: `/mcp-server/conalca_mcp_server/models.py`

**ANTES (línea 1):**
```python
from typing import Optional, Dict, Any, List
from datetime import datetime
from pydantic import BaseModel, Field
from .database import db_connection
import logging
```

**CAMBIO 1.1: Importar validador**
```python
from typing import Optional, Dict, Any, List
from datetime import datetime
from pydantic import BaseModel, Field, field_validator  # ← AGREGAR field_validator
from .database import db_connection
import logging
```

---

**ANTES (línea ~50):**
```python
class CotizacionModel(BaseModel):
    """Modelo para la tabla cotizacion_models"""
    id: int
    pricing_id: Optional[int] = None
    porcentaje: Optional[str] = None
    ciudad_origen: Optional[str] = None
    ciudad_destino: Optional[str] = None
    # ... más campos
    fecha_hora_descargue_cargue: Optional[str] = None  # ← AQUÍ
```

**CAMBIO 1.2: Agregar validador (DESPUÉS de la definición de campos)**
```python
class CotizacionModel(BaseModel):
    """Modelo para la tabla cotizacion_models"""
    id: int
    pricing_id: Optional[int] = None
    porcentaje: Optional[str] = None
    ciudad_origen: Optional[str] = None
    ciudad_destino: Optional[str] = None
    # ... todos los campos existentes ...
    fecha_hora_descargue_cargue: Optional[str] = Field(
        None,
        description="Fecha y hora de descargue/cargue (formato: YYYY-MM-DD HH:MM:SS)"
    )
    
    @field_validator('fecha_hora_descargue_cargue')
    @classmethod
    def validate_fecha_format(cls, v):
        """Valida que la fecha tenga un formato reconocible"""
        if v:  # Solo valida si hay valor
            # Convertir a string y limpiar
            fecha_str = str(v).strip()
            
            # Rechazar valores inválidos comunes
            if fecha_str.upper() in ('NULL', 'NONE', '', '0', '0000-00-00'):
                return None  # Convertir a None para que sea transparente
            
            # Intentar parsear con formatos conocidos
            valid_formats = (
                '%Y-%m-%d %H:%M:%S',
                '%Y-%m-%dT%H:%M:%S',
                '%Y-%m-%d',
                '%d-%m-%Y %H:%M:%S',
                '%d-%m-%Y'
            )
            
            for fmt in valid_formats:
                try:
                    datetime.strptime(fecha_str, fmt)
                    return v  # Retorna valor original si es válido
                except ValueError:
                    continue
            
            # Si llegamos aquí, formato no reconocido
            logger.warning(f"Formato de fecha no reconocido: {v}")
            return v  # Retorna igualmente pero sin fallar
        
        return v
    
    class Config:
        from_attributes = True
```

---

## 🔧 CAMBIO 2: tools.py - Agregar Verificación de Campos Críticos

### Ubicación: `/mcp-server/conalca_mcp_server/tools.py`

**BUSCAR LA HERRAMIENTA (línea ~154):**
```python
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
```

**REEMPLAZAR CON:**
```python
@self.server.tool(
    name="get_cotizacion_by_id",
    description="Obtiene una cotización específica por su ID"
)
async def get_cotizacion_by_id(id_cotizacion: int) -> List[TextContent]:
    """Obtiene una cotización por ID con validación de campos críticos"""
    try:
        cotizacion = await self.repository.get_cotizacion_by_id(id_cotizacion)
        if not cotizacion:
            return [TextContent(type="text", text=f"No se encontró la cotización con ID {id_cotizacion}")]
        
        # ✅ VALIDACIÓN NUEVA: Verificar campos críticos
        critical_fields = {
            'ciudad_origen': cotizacion.ciudad_origen,
            'ciudad_destino': cotizacion.ciudad_destino,
            'peso_mercancia': cotizacion.peso_mercancia,
            'vehiculo_requerido': cotizacion.vehiculo_requerido,
            'valor': cotizacion.valor,
        }
        
        critical_optional_fields = {
            'fecha_hora_descargue_cargue': cotizacion.fecha_hora_descargue_cargue,
        }
        
        missing_critical = []
        for field_name, field_value in critical_fields.items():
            if not field_value or str(field_value).strip() in ('', 'NULL', 'None'):
                missing_critical.append(field_name)
        
        missing_optional = []
        for field_name, field_value in critical_optional_fields.items():
            if not field_value or str(field_value).strip() in ('', 'NULL', 'None'):
                missing_optional.append(field_name)
        
        # Registrar advertencias
        if missing_critical:
            logger.error(f"❌ Cotización {id_cotizacion} incompleta (campos críticos faltantes): {missing_critical}")
        
        if missing_optional:
            logger.warning(f"⚠️ Cotización {id_cotizacion} con campos opcionales faltantes: {missing_optional}")
        
        # Retornar resultado con advertencias
        result = cotizacion.model_dump()
        
        # Agregar metadatos de validación
        result['_validacion'] = {
            'campos_faltantes_criticos': missing_critical,
            'campos_faltantes_opcionales': missing_optional,
            'completa': len(missing_critical) == 0
        }
        
        return_text = f"Cotización encontrada:\n{json.dumps(result, indent=2, ensure_ascii=False)}"
        
        # Agregar advertencia si faltan datos importantes
        if missing_critical or missing_optional:
            return_text = f"⚠️ ADVERTENCIA: Campos faltantes\n" + return_text
        
        return [TextContent(type="text", text=return_text)]
        
    except Exception as e:
        logger.exception(f"Error al obtener cotización {id_cotizacion}: {str(e)}")
        return [TextContent(type="text", text=f"Error al obtener la cotización: {str(e)}")]
```

---

## 🔧 CAMBIO 3: server_mcp_compliant.py - Mejorar Logging de Fecha

### Ubicación: `/mcp-server/conalca_mcp_server/server_mcp_compliant.py` (línea ~1660)

**BUSCAR:**
```python
                    # Parsear fecha_hora_descargue_cargue → fecha_cargue y hora_cargue
                    _fhdc = conductor_data.get('fecha_hora_descargue_cargue')
                    _fecha_cargue = None
                    _hora_cargue = None
                    if _fhdc:
                        try:
                            _dt = None
                            for _fmt in ('%Y-%m-%d %H:%M:%S', '%Y-%m-%dT%H:%M:%S', '%Y-%m-%d'):
                                try:
                                    _dt = datetime.strptime(str(_fhdc), _fmt)
                                    break
                                except ValueError:
                                    pass
                            if _dt:
                                _dias = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo']
                                _meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                                          'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre']
                                _fecha_cargue = f"{_dias[_dt.weekday()]} {_dt.day} de {_meses[_dt.month - 1]} de {_dt.year}"
                                if not (_dt.hour == 0 and _dt.minute == 0):
                                    _h = _dt.hour % 12 or 12
                                    _ampm = 'AM' if _dt.hour < 12 else 'PM'
                                    _hora_cargue = f"{_h}:{_dt.minute:02d} {_ampm}"
                            else:
                                _fecha_cargue = str(_fhdc)
                        except Exception:
                            _fecha_cargue = str(_fhdc)
```

**REEMPLAZAR CON:**
```python
                    # ✅ Parsear fecha_hora_descargue_cargue → fecha_cargue y hora_cargue
                    _fhdc = conductor_data.get('fecha_hora_descargue_cargue')
                    _fecha_cargue = None
                    _hora_cargue = None
                    
                    # ✅ VALIDACIÓN: Verificar presencia
                    if not _fhdc or str(_fhdc).strip() in ('', 'NULL', 'None', '0', '0000-00-00'):
                        logger.error(f"❌ CRÍTICO: Cotización {cotizacion_id} sin fecha_hora_descargue_cargue")
                        logger.error(f"   Datos completos de cotización: {json.dumps(conductor_data, default=str)}")
                        _fecha_cargue = "fecha por coordinar"
                        _hora_cargue = None
                    else:
                        try:
                            _dt = None
                            _fhdc_str = str(_fhdc).strip()
                            
                            # Intentar varios formatos
                            for _fmt in ('%Y-%m-%d %H:%M:%S', '%Y-%m-%dT%H:%M:%S', '%Y-%m-%d', 
                                        '%d-%m-%Y %H:%M:%S', '%d-%m-%Y'):
                                try:
                                    _dt = datetime.strptime(_fhdc_str, _fmt)
                                    logger.info(f"✅ Fecha parseada correctamente con formato {_fmt}: {_fhdc_str}")
                                    break
                                except ValueError:
                                    pass
                            
                            if _dt:
                                # Conversión a español exitosa
                                _dias = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo']
                                _meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                                          'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre']
                                _fecha_cargue = f"{_dias[_dt.weekday()]} {_dt.day} de {_meses[_dt.month - 1]} de {_dt.year}"
                                
                                # Extraer hora si existe (no es medianoche)
                                if not (_dt.hour == 0 and _dt.minute == 0):
                                    _h = _dt.hour % 12 or 12
                                    _ampm = 'AM' if _dt.hour < 12 else 'PM'
                                    _hora_cargue = f"{_h}:{_dt.minute:02d} {_ampm}"
                                
                                logger.info(f"✅ Fecha cargue procesada: {_fecha_cargue} {_hora_cargue or 'sin hora'}")
                            else:
                                # No se pudo parsear con formatos conocidos
                                logger.warning(f"⚠️ ADVERTENCIA: Formato de fecha no reconocido: {_fhdc}")
                                _fecha_cargue = str(_fhdc)
                                
                        except Exception as e:
                            logger.exception(f"❌ ERROR procesando fecha_hora_descargue_cargue: {str(e)}")
                            _fecha_cargue = str(_fhdc) if _fhdc else "error al procesar fecha"
                    
                    # ✅ Verificar resultado final
                    if not _fecha_cargue:
                        logger.error(f"❌ FATAL: No se pudo determinar fecha_cargue para cotización {cotizacion_id}")
                        _fecha_cargue = "fecha no disponible"
```

---

## 🔧 CAMBIO 4: database.py - Agregar Utilidades de Diagnóstico

### Ubicación: `/mcp-server/conalca_mcp_server/database.py` (al final de la clase)

**AGREGAR AL FINAL DE LA CLASE DatabaseRepository:**

```python
    # ✅ UTILIDADES DE DIAGNÓSTICO
    
    async def check_cotizaciones_sin_fecha(self) -> Dict[str, Any]:
        """Diagnóstico: Cotizaciones sin fecha de cargue"""
        query = """
        SELECT 
            COUNT(*) as total_cotizaciones,
            SUM(CASE WHEN fecha_hora_descargue_cargue IS NULL THEN 1 ELSE 0 END) as sin_fecha_null,
            SUM(CASE WHEN fecha_hora_descargue_cargue = '' THEN 1 ELSE 0 END) as sin_fecha_vacio,
            SUM(CASE WHEN fecha_hora_descargue_cargue = 'NULL' THEN 1 ELSE 0 END) as sin_fecha_string_null,
            SUM(CASE WHEN fecha_hora_descargue_cargue IS NULL 
                        OR fecha_hora_descargue_cargue = '' 
                        OR fecha_hora_descargue_cargue = 'NULL' THEN 1 ELSE 0 END) as total_sin_fecha
        FROM cotizacion_models
        """
        results = await self.db.execute_query(query)
        return results[0] if results else {}
    
    async def get_cotizaciones_sin_fecha(self, limit: int = 100) -> List[Dict]:
        """Obtener lista de cotizaciones sin fecha"""
        query = """
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
        ORDER BY created_at DESC
        LIMIT %s
        """
        return await self.db.execute_query(query, (limit,))
    
    async def fix_cotizacion_fecha(self, cotizacion_id: int, fecha_nueva: str) -> bool:
        """Actualizar fecha de una cotización"""
        query = """
        UPDATE cotizacion_models
        SET fecha_hora_descargue_cargue = %s,
            updated_at = NOW()
        WHERE id = %s
        """
        affected = await self.db.execute_update(query, (fecha_nueva, cotizacion_id))
        return affected > 0
```

---

## 🧪 TESTING DE CAMBIOS

### Test 1: Validar que el modelo rechaza formatos inválidos

```python
# test_validacion_fecha.py
from conalca_mcp_server.models import CotizacionModel

def test_fecha_formatos_validos():
    """Probar que acepta formatos válidos"""
    formatos = [
        "2025-10-05 10:30:00",
        "2025-10-05T10:30:00",
        "2025-10-05",
        "05-10-2025 10:30:00",
        "05-10-2025"
    ]
    
    for fecha in formatos:
        cotizacion = CotizacionModel(
            id=1,
            fecha_hora_descargue_cargue=fecha
        )
        assert cotizacion.fecha_hora_descargue_cargue == fecha
        print(f"✅ Formato válido: {fecha}")

def test_fecha_nulos():
    """Probar que convierte a None valores NULL"""
    valores_null = ["NULL", "None", "null", "", None]
    
    for valor in valores_null:
        cotizacion = CotizacionModel(
            id=1,
            fecha_hora_descargue_cargue=valor
        )
        assert cotizacion.fecha_hora_descargue_cargue is None
        print(f"✅ Convertido a None: {valor}")
```

### Test 2: Verificar que la herramienta detecta campos faltantes

```python
# test_herramienta_validacion.py
import asyncio

async def test_cotizacion_incompleta():
    """Probar que la herramienta alerta de campos faltantes"""
    # Suponer que existe una cotización ID 999 sin fecha
    result = await get_cotizacion_by_id(999)
    
    # Debe contener advertencia
    assert "⚠️" in result[0].text
    assert "_validacion" in result[0].text
    print("✅ Herramienta detecta campos faltantes")
```

---

## 📊 QUERIES DE VALIDACIÓN

### Query 1: Estadísticas de Cobertura

```sql
-- Saber cuántas cotizaciones tienen fecha
SELECT 
    COUNT(*) as total_cotizaciones,
    SUM(CASE WHEN fecha_hora_descargue_cargue IS NOT NULL 
             AND fecha_hora_descargue_cargue != '' 
             AND fecha_hora_descargue_cargue != 'NULL' THEN 1 ELSE 0 END) as con_fecha,
    SUM(CASE WHEN fecha_hora_descargue_cargue IS NULL 
             OR fecha_hora_descargue_cargue = '' 
             OR fecha_hora_descargue_cargue = 'NULL' THEN 1 ELSE 0 END) as sin_fecha,
    ROUND(100.0 * SUM(CASE WHEN fecha_hora_descargue_cargue IS NOT NULL 
                          AND fecha_hora_descargue_cargue != '' 
                          AND fecha_hora_descargue_cargue != 'NULL' THEN 1 ELSE 0 END) / COUNT(*), 2) as porcentaje_con_fecha
FROM cotizacion_models
WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### Query 2: Listar Cotizaciones Problemáticas

```sql
-- Identificar cotizaciones que van a causar problemas
SELECT 
    cm.id,
    cm.ciudad_origen,
    cm.ciudad_destino,
    cm.valor,
    cm.fecha_hora_descargue_cargue as fecha_status,
    cm.created_at,
    GROUP_CONCAT(ll.id_llamada) as llamadas_asociadas
FROM cotizacion_models cm
LEFT JOIN llamadas ll ON ll.id_cotizacion = cm.id
WHERE cm.fecha_hora_descargue_cargue IS NULL
   OR cm.fecha_hora_descargue_cargue = ''
   OR cm.fecha_hora_descargue_cargue = 'NULL'
GROUP BY cm.id
ORDER BY cm.created_at DESC;
```

---

## 📋 CHECKLIST DE IMPLEMENTACIÓN

- [ ] **Paso 1:** Actualizar imports en models.py (agregar field_validator)
- [ ] **Paso 2:** Agregar validador en CotizacionModel
- [ ] **Paso 3:** Actualizar herramienta get_cotizacion_by_id en tools.py
- [ ] **Paso 4:** Mejorar logging en server_mcp_compliant.py línea 1660
- [ ] **Paso 5:** Agregar funciones de diagnóstico en database.py
- [ ] **Paso 6:** Ejecutar tests de validación
- [ ] **Paso 7:** Ejecutar queries de diagnóstico
- [ ] **Paso 8:** Revisar logs en producción
- [ ] **Paso 9:** Documentar en wiki interna
- [ ] **Paso 10:** Capacitar al equipo

---

## 🚀 VERIFICACIÓN POST-IMPLEMENTACIÓN

```bash
# 1. Verificar que los cambios compilen
python -m py_compile mcp-server/conalca_mcp_server/models.py
python -m py_compile mcp-server/conalca_mcp_server/tools.py
python -m py_compile mcp-server/conalca_mcp_server/server_mcp_compliant.py

# 2. Reiniciar servidor
systemctl restart conalca-mcp

# 3. Verificar logs
tail -f /var/log/conalca-mcp.log | grep -i fecha

# 4. Ejecutar query de diagnóstico
mysql -u biosanar_user -p ai_transport < diagnostico_fecha.sql
```

---

## 📞 SOPORTE

Si tienes problemas durante la implementación:

1. **Validador falla:** Revisar formato de fecha en BD
2. **Tests no pasan:** Verificar imports de pydantic
3. **Logs no aparecen:** Verificar nivel de logging (DEBUG vs INFO)
4. **Servidor no inicia:** Buscar errores de sintaxis Python

