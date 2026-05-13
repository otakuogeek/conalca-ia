# 🚚 NUEVA HERRAMIENTA: `precioviaje` - CONSULTA DE PRECIO DE VIAJE

---

## 📋 INFORMACIÓN GENERAL

| **Campo** | **Valor** |
|-----------|-----------|
| **Nombre** | `precioviaje` |
| **Descripción** | **Obtiene el precio específico de un viaje para responder cuando el chofer pregunta el valor** |
| **Método HTTP** | `POST` (JSON-RPC 2.0) |
| **Operación CRUD** | `GET` (Lectura con JOIN) |
| **Estado** | ✅ **ACTIVA EN PRODUCCIÓN** |

---

## 🔧 PARÁMETROS DE ENTRADA

```json
{
  "cotizacion_id": {
    "type": "integer",
    "description": "ID de la cotización para consultar el precio del viaje",
    "required": true,
    "minimum": 1
  }
}
```

---

## 🗄️ PROCESO DE CONSULTA

### **Paso 1: Obtener Cotización**
- **Tabla:** `cotizacion_models`
- **Campo clave:** `pricing_id`
- **SQL:** `SELECT * FROM cotizacion_models WHERE id = ?`

### **Paso 2: Obtener Precio**
- **Tabla:** `pricings`
- **Relación:** `cotizacion_models.pricing_id → pricings.id`
- **SQL:** `SELECT * FROM pricings WHERE id = ?`

---

## 📤 RESPUESTA ESTRUCTURADA

```json
{
  "success": true,
  "cotizacion_id": 32,
  "pricing_id": 2751,
  "precio_viaje": "2900000",
  "informacion_viaje": {
    "origen": "Medellin",
    "destino": "BOGOTÁ",
    "tipo_vehiculo": "Tracto Mula S3",
    "tipo_precio": "vn",
    "peso_desde": null,
    "peso_hasta": null
  },
  "informacion_cotizacion": {
    "vehiculo_requerido": "Tracto Mula S3",
    "tipo_carroceria": "Estacas",
    "ciudad_origen": "medellin",
    "ciudad_destino": "bogota",
    "peso_mercancia": "2000",
    "tipo_mercancia": "Granel sólido"
  },
  "mensaje_chofer": "El precio del viaje desde Medellin hasta BOGOTÁ es de $2,900,000.00 COP"
}
```

---

## 🎯 CASO DE USO PRINCIPAL

### **Escenario: Chofer pregunta precio del viaje**

1. **Chofer llama:** "¿Cuánto vale el viaje de la cotización 32?"
2. **ElevenLabs ejecuta:** `precioviaje` con `cotizacion_id: 32`
3. **Sistema consulta:**
   - Cotización ID 32 → `pricing_id: 2751`
   - Precio ID 2751 → `price: "2900000"`
4. **Respuesta al chofer:** *"El precio del viaje desde Medellín hasta Bogotá es de $2,900,000.00 COP"*

---

## 🚀 EJEMPLO DE USO

### **Llamada HTTP**
```bash
curl -X POST https://conalcaia.conalca.com.co/mcp/ \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": "precio_consulta",
    "method": "tools/call",
    "params": {
      "name": "precioviaje",
      "arguments": {"cotizacion_id": 32}
    }
  }'
```

### **Respuesta Exitosa**
```json
{
  "jsonrpc": "2.0",
  "id": "precio_consulta",
  "result": {
    "content": [{
      "type": "text",
      "text": "{\"success\": true, \"precio_viaje\": \"2900000\", \"mensaje_chofer\": \"El precio del viaje desde Medellin hasta BOGOTÁ es de $2,900,000.00 COP\"}"
    }]
  }
}
```

---

## ⚠️ MANEJO DE ERRORES

### **Error 1: Cotización no encontrada**
```json
{
  "success": false,
  "error": "No se encontró cotización con ID: 999"
}
```

### **Error 2: Sin precio asociado**
```json
{
  "success": false,
  "error": "La cotización 31 no tiene precio asociado (pricing_id es null)"
}
```

### **Error 3: Precio no encontrado**
```json
{
  "success": false,
  "error": "No se encontró precio con ID: 1234"
}
```

---

## 📊 DATOS DE PRUEBA REALES

| **Cotización ID** | **Pricing ID** | **Precio** | **Ruta** | **Vehículo** |
|-------------------|----------------|------------|----------|--------------|
| 31 | 3087 | $850,000 | Funza → Bogotá | Tracto Mula S3 |
| 32 | 2751 | $2,900,000 | Medellín → Bogotá | Tracto Mula S3 |
| 33 | 3087 | $850,000 | Funza → Bogotá | Tracto Mula S3 |

---

## 🔗 HERRAMIENTAS RELACIONADAS

| **Herramienta** | **Relación** |
|-----------------|--------------|
| `get_cotizaciones` | Lista todas las cotizaciones disponibles |
| `get_pricings` | Lista todos los precios del sistema |
| `get_cotizacion_with_pricing_info` | Información completa de cotización + precio |
| `search_pricings_by_route` | Busca precios por ruta específica |

---

## ✅ ESTADO Y MÉTRICAS

| **Métrica** | **Valor** |
|-------------|-----------|
| **Herramienta #** | **21** (más reciente) |
| **Tiempo de Respuesta** | < 300ms |
| **Tipo de Consulta** | JOIN entre 2 tablas |
| **Formato de Respuesta** | JSON estructurado |
| **Integración ElevenLabs** | ✅ Compatible |
| **Estado en Producción** | ✅ Activa |

---

## 🎉 VENTAJAS PRINCIPALES

1. **📞 Respuesta Directa:** Ideal para preguntas de choferes sobre precios
2. **⚡ Consulta Rápida:** Una sola llamada obtiene toda la información
3. **🔗 Relación Automática:** Conecta automáticamente cotización → precio
4. **💬 Mensaje Listo:** Genera texto formateado para el chofer
5. **🛡️ Manejo de Errores:** Validaciones completas y mensajes claros

---

**📍 Servidor:** `https://conalcaia.conalca.com.co/mcp/`  
**🔧 Total Herramientas:** 21 activas  
**📅 Fecha de Implementación:** Octubre 5, 2025  
**👨‍💻 Desarrollado para:** Consultas directas de choferes sobre precios de viajes