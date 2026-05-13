# 📚 ÍNDICE DE DOCUMENTACIÓN - SERVIDOR MCP v2.0

**Última actualización:** Diciembre 5, 2025  
**Estado:** ✅ Documentación completa

---

## 🎯 DOCUMENTOS PRINCIPALES

### 1. 📋 RESUMEN_CONFIGURACION_MCP.md
**Para:** Todos (Lectura rápida)  
**Propósito:** Resumen ejecutivo de los cambios implementados  
**Tiempo de lectura:** 5 minutos  
**Contenido:**
- Objetivo del cambio
- Antes vs Después
- Cambios técnicos resumidos
- Estado del servidor
- Próximos pasos
- Checklist de verificación

👉 **LEER PRIMERO** si quieres entender qué se hizo y por qué.

---

### 2. 🔧 CONFIGURACION_NUEVA_BD_CONDUCTORES.md
**Para:** Desarrolladores / DevOps  
**Propósito:** Documentación técnica detallada  
**Tiempo de lectura:** 20 minutos  
**Contenido:**
- Estructura de tabla `llamadas_conductores`
- Métodos nuevos en `models.py`
- Herramientas MCP actualizadas
- Flujo completo del sistema
- Queries SQL útiles
- Trazabilidad y monitoreo
- Troubleshooting

👉 **CONSULTAR** cuando necesites entender cómo funciona internamente el sistema.

---

### 3. 📝 PROMPT_NATALIA_ELEVENLABS_V2.md
**Para:** Configuración de ElevenLabs  
**Propósito:** Prompt completo para el agente conversacional  
**Tiempo de lectura:** 15 minutos  
**Contenido:**
- Identidad del agente (Natalia Álvarez)
- Protocolo de conversación completo
- Manejo de respuestas (acepta/rechaza/duda)
- Reglas y límites
- Cambios técnicos v2.0
- Ejemplo de flujo completo

👉 **COPIAR Y PEGAR** este contenido en ElevenLabs Dashboard → Prompt.

---

### 4. ⚡ INSTRUCCIONES_ELEVENLABS.md
**Para:** Usuario final / Configurador  
**Propósito:** Guía paso a paso para actualizar ElevenLabs  
**Tiempo de lectura:** 5 minutos  
**Contenido:**
- Paso a paso para actualizar prompt
- Configuración de las 4 herramientas
- Verificación rápida
- Prompt resumido (versión corta)
- Troubleshooting común

👉 **SEGUIR** estos pasos para configurar ElevenLabs en 5 minutos.

---

### 5. 🧪 test_nuevo_flujo.py
**Para:** Testing / QA  
**Propósito:** Script de pruebas automatizadas  
**Tiempo de ejecución:** 1-2 minutos  
**Contenido:**
- Test 1: `get_conductor_by_conversation_id()`
- Test 2: `save_driver_decision_new()`
- Test 3: `update_estado_llamada_conductor()`
- Test 4: Estadísticas de la tabla

👉 **EJECUTAR** con: `venv/bin/python test_nuevo_flujo.py`

---

## 🗂️ ARCHIVOS MODIFICADOS (No documentos)

### Backend MCP:
- `conalca_mcp_server/models.py` - Agregados 3 métodos nuevos
- `conalca_mcp_server/server.py` - Actualizadas 2 herramientas

### Base de Datos:
- Tabla `llamadas_conductores` - Creada por migración Laravel

---

## 🚀 FLUJO DE TRABAJO RECOMENDADO

### Para Entender el Sistema:
```
1. RESUMEN_CONFIGURACION_MCP.md (5 min)
   ↓
2. CONFIGURACION_NUEVA_BD_CONDUCTORES.md (20 min)
   ↓
3. Ejecutar test_nuevo_flujo.py (2 min)
```

### Para Configurar ElevenLabs:
```
1. INSTRUCCIONES_ELEVENLABS.md (5 min)
   ↓
2. PROMPT_NATALIA_ELEVENLABS_V2.md (copiar contenido)
   ↓
3. Probar llamada de test en ElevenLabs
```

### Para Troubleshooting:
```
1. Revisar INSTRUCCIONES_ELEVENLABS.md → Sección Troubleshooting
   ↓
2. Ejecutar test_nuevo_flujo.py para diagnosticar
   ↓
3. Consultar CONFIGURACION_NUEVA_BD_CONDUCTORES.md → Sección Troubleshooting
   ↓
4. Revisar logs: tail -f mcp_server.log
```

---

## 📊 COMPARACIÓN DE ARCHIVOS

| Documento | Audiencia | Tipo | Lectura | Acción |
|-----------|-----------|------|---------|--------|
| RESUMEN_CONFIGURACION_MCP.md | Todos | Overview | 5 min | Leer |
| CONFIGURACION_NUEVA_BD_CONDUCTORES.md | Dev/Ops | Técnico | 20 min | Consultar |
| PROMPT_NATALIA_ELEVENLABS_V2.md | Config | Prompt | 15 min | Copiar |
| INSTRUCCIONES_ELEVENLABS.md | Usuario | Tutorial | 5 min | Seguir |
| test_nuevo_flujo.py | QA | Script | 2 min | Ejecutar |

---

## ✅ CHECKLIST RÁPIDO

### Documentación:
- [x] Resumen ejecutivo creado
- [x] Documentación técnica completa
- [x] Prompt de ElevenLabs documentado
- [x] Instrucciones de configuración
- [x] Script de pruebas implementado
- [x] Índice de navegación (este archivo)

### Sistema:
- [x] Métodos en `models.py` implementados
- [x] Herramientas en `server.py` actualizadas
- [x] Servidor MCP reiniciado
- [x] Health check: ✅ healthy

### Pendiente (Usuario):
- [ ] Actualizar prompt en ElevenLabs Dashboard
- [ ] Configurar las 4 herramientas en ElevenLabs
- [ ] Ejecutar pruebas end-to-end
- [ ] Validar en producción

---

## 🔗 ENLACES RÁPIDOS

### Health Checks:
```bash
# MCP Server
curl https://conalcaia.conalca.com.co/mcp/health

# Base de datos
mysql -h ai-transport.czqmsk4ck839.us-west-1.rds.amazonaws.com \
  -u admin -p1Dy81fsrX0htEBWodTJ9 conalca \
  -e "SELECT COUNT(*) FROM llamadas_conductores;"
```

### Logs:
```bash
# MCP Server
tail -f /home/ubuntu/conalca/conalca-mcp/mcp-server/mcp_server.log

# Laravel
tail -f /home/ubuntu/conalca/conalca/storage/logs/laravel.log
```

### Reiniciar Servidor:
```bash
cd /home/ubuntu/conalca/conalca-mcp/mcp-server
pkill -f "run_service.py"
nohup venv/bin/python run_service.py > mcp_server.log 2>&1 &
```

---

## 📞 SOPORTE

### Comandos Útiles:
```bash
# Ver conductores pendientes
mysql -h ai-transport.czqmsk4ck839.us-west-1.rds.amazonaws.com \
  -u admin -p1Dy81fsrX0htEBWodTJ9 conalca \
  -e "SELECT * FROM llamadas_conductores WHERE estado_llamada='pendiente' LIMIT 10;"

# Ver estadísticas
mysql -h ai-transport.czqmsk4ck839.us-west-1.rds.amazonaws.com \
  -u admin -p1Dy81fsrX0htEBWodTJ9 conalca \
  -e "SELECT estado_llamada, COUNT(*) FROM llamadas_conductores GROUP BY estado_llamada;"

# Test de herramientas MCP
curl -X POST https://conalcaia.conalca.com.co/mcp/tools/precioviaje \
  -H "Content-Type: application/json" \
  -d '{"cotizacion_id": 32}'
```

---

## 🎓 GLOSARIO

- **MCP:** Model Context Protocol - Servidor de herramientas para el agente
- **ElevenLabs:** Plataforma de voz AI para agentes conversacionales
- **conversation_id:** ID único de cada llamada generado por ElevenLabs
- **identificador_unico:** ID único del conductor en formato LC-{cotizacionId}-{md5(phone)}-{timestamp}
- **estado_llamada:** Estado de la llamada (pendiente/en_progreso/completada/fallida/cancelada)
- **generate_transport_offer:** Herramienta MCP que obtiene datos del conductor y viaje
- **save_driver_decision:** Herramienta MCP que guarda la decisión del conductor
- **precioviaje:** Herramienta MCP que obtiene el precio del viaje
- **zinformacion:** Herramienta MCP que obtiene información detallada de la cotización

---

## 📅 HISTORIAL DE VERSIONES

### v2.0 - Diciembre 5, 2025
- ✅ Migración a base de datos `llamadas_conductores`
- ✅ Eliminación de dependencia de API Arcangel durante llamadas
- ✅ Nuevos métodos en `models.py`
- ✅ Herramientas MCP actualizadas
- ✅ Prompt de ElevenLabs v2.0
- ✅ Documentación completa

### v1.0 - Antes de Diciembre 2025
- ❌ Flujo antiguo con tabla `vehicle_owner_holder_driver`
- ❌ Múltiples queries por llamada
- ❌ Sin trazabilidad de estados

---

**Fin del Índice**

Para comenzar, lee **RESUMEN_CONFIGURACION_MCP.md** 📋
