# 📋 Ejemplo de Prompt Completo para el Chat Inteligente

## 🎯 Objetivo

Este documento muestra cómo usar el chat para **llenar automáticamente el formulario** de solicitud de transporte con los datos específicos de tu caso de uso.

---

## 📝 DATOS DEL CASO DE PRUEBA

### Paso 1 - Encabezado
```
Cliente: 2551
Tipo de viaje: NACIONAL
Moneda: PESOS
Fuente solicitud: TELEFONO DESPACHADOR
Ciudad facturación: 11001000 (Bogotá D.C.)
Vendedor: 53165050
Tipo operación: DISTRIBUCION
Centro costo despacho: TRANSLIDHER BOGOTA
```

### Paso 2 - Detalle
```
Origen: 11001000 (Bogotá D.C.)
Destino: 11001000 (Bogotá D.C.)
Cantidad mercancía: 1
Peso: 2000 kg
Producto: 1
Empaque: 1
Cantidad vehículos: 1
Clase vehículo: 1
Carrocería: 1
Mínimo modelo: 1
Tipo flete: CARGA SUELTA
Flete conductor: 1
Flete ministerio: 1
Tipo tarifa: GENERAL
Tarifa cliente: 1
Valor mercancía: 1
Cargue cuenta de: EMPRESA
Descargue cuenta de: DESTINATARIO
Seguro cuenta de: CLIENTE
Kit seguridad: NO
Tipo remesa RNDC: 3
```

### Paso 3 - Cargue
```
Fecha cargue: HOY (2025-10-04)
Hora cargue: 16:00
Remitente: 1
Destinatario: 1
Contacto: 2345678
Promesa servicio: HOY (2025-10-04)
Documento transporte: 1
Observación cargue: 1
```

### Paso 4 - Contenedor
```
Contenedor: NO (implícito por defecto)
```

### Paso 5 - Internacional
```
Modalidad: OTM (solo si aplica)
```

### Paso 6 - Acompañamiento
```
Vehículo acompañamiento: 1
Tipo: MOTORIZADO
Cuenta de: CLIENTE
Valor: 1
```

---

## 💬 EJEMPLOS DE PROMPTS PARA PROBAR

### 🔹 Opción 1: Prompt Natural Completo

```
Hola, necesito crear una solicitud de transporte con los siguientes datos:

Cliente 2551, viaje nacional en pesos, telefono despachador, ciudad facturacion 
11001000 que es Bogota, vendedor 53165050, tipo operacion distribucion, 
centro costo TRANSLIDHER BOGOTA.

Para el detalle: origen y destino ambos 11001000, cantidad mercancia 1, 
peso 2000 kg, producto 1, empaque 1, un vehiculo, clase vehiculo 1, 
carroceria 1, minimo modelo 1, tipo flete carga suelta, flete conductor 1, 
flete ministerio 1, tarifa general, tarifa cliente 1, valor mercancia 1, 
cargue lo paga empresa, descargue destinatario, seguro cliente, sin kit 
seguridad, tipo remesa 3.

Para el cargue: fecha hoy, hora 4 pm, remitente 1, destinatario 1, 
contacto 2345678, promesa servicio hoy, documento transporte 1.

Sin contenedor, y vehiculo acompañamiento 1, tipo motorizado, lo paga cliente, 
valor 1.
```

### 🔹 Opción 2: Prompt por Pasos

**Paso 1:**
```
Cliente 2551, viaje nacional en pesos, telefono despachador, ciudad facturacion 
11001000, vendedor 53165050, distribucion, centro costo TRANSLIDHER BOGOTA
```

**Paso 2:**
```
Origen 11001000, destino 11001000, cantidad mercancia 1, peso 2000, producto 1, 
empaque 1, un vehiculo, clase 1, carroceria 1, modelo 1, carga suelta, 
flete conductor 1, flete ministerio 1, tarifa general, tarifa cliente 1, 
valor mercancia 1, cargue empresa, descargue destinatario, seguro cliente, 
sin kit, remesa 3
```

**Paso 3:**
```
Fecha cargue hoy, hora 4 pm, remitente 1, destinatario 1, contacto 2345678, 
promesa hoy, documento 1
```

**Paso 6:**
```
Un vehiculo acompañamiento motorizado, lo paga cliente, valor 1
```

### 🔹 Opción 3: Prompt Ultra-Corto

```
Cliente 2551, nacional pesos, telefono despachador, facturacion 11001000, 
vendedor 53165050, distribucion, TRANSLIDHER BOGOTA, origen destino 11001000, 
peso 2000, carga suelta, cargue empresa descargue destinatario seguro cliente, 
hoy 4pm, sin kit, remesa 3, moto acompañamiento cliente
```

---

## 🎤 EJEMPLO PARA USAR CON VOZ

Puedes decir esto al micrófono:

```
"Hola, necesito una solicitud para el cliente dos mil quinientos cincuenta y uno,
viaje nacional en pesos, teléfono despachador, ciudad de facturación Bogotá once 
cero cero uno cero cero cero, vendedor cinco tres uno seis cinco cero cinco cero,
distribución, centro de costo translidher bogotá.

El origen y destino son ambos once cero cero uno cero cero cero, cantidad de 
mercancía uno, peso dos mil kilos, carga suelta, el cargue lo paga la empresa, 
el descargue el destinatario, el seguro el cliente, sin kit de seguridad.

Fecha de cargue hoy, hora cuatro de la tarde, una moto de acompañamiento, 
la paga el cliente."
```

---

## 🔍 CÓDIGOS DANE MÁS COMUNES

Para referencia rápida:

| Ciudad | Código DANE |
|--------|-------------|
| Bogotá D.C. | 11001000 |
| Medellín | 05001000 |
| Cali | 76001000 |
| Barranquilla | 08001000 |
| Cartagena | 13001000 |
| Bucaramanga | 68001000 |
| Cúcuta | 54001000 |
| Pereira | 66001000 |
| Ibagué | 73001000 |
| Santa Marta | 47001000 |

---

## ✅ VERIFICACIÓN

Después de enviar el prompt, verifica que se llenaron estos campos:

**Paso 1 (10 campos):**
- ✅ cliente_codigo: "2551"
- ✅ tipo_viaje: "NACIONAL"
- ✅ moneda: "PESOS"
- ✅ fuente_solicitud: "TELEFONO DESPACHADOR"
- ✅ ciudad_facturacion: "11001000"
- ✅ vendedor: "53165050"
- ✅ tipo_operacion: "DISTRIBUCION"
- ✅ centro_costo_despacho: "TRANSLIDHER BOGOTA"

**Paso 2 (23 campos):**
- ✅ origen: "11001000"
- ✅ destino: "11001000"
- ✅ cantidad_mercancia: "1"
- ✅ peso: "2000"
- ✅ tipo_flete: "CARGA SUELTA"
- ✅ cargue_cuenta_de: "EMPRESA"
- ✅ descargue_cuenta_de: "DESTINATARIO"
- ✅ seguro_cuenta_de: "CLIENTE"
- ✅ kit_seguridad: "NO"
- ✅ tipo_remesa_rndc: "3"

**Paso 3 (8 campos):**
- ✅ fecha_cargue: "2025-10-04" (o fecha actual)
- ✅ hora_cargue: "16:00"
- ✅ contacto: "2345678"

**Paso 6 (4 campos):**
- ✅ itesoltra_vehiculoacompanamiento: "1"
- ✅ tipaco_codigo: "MOTORIZADO"
- ✅ itesoltra_acompanamientocuentade: "CLIENTE"

**TOTAL: ~45 campos llenados automáticamente** 🎉

---

## 🚨 TROUBLESHOOTING

### Si un campo no se llena:

1. **Verifica el nombre del campo** en el SYSTEM_PROMPT
2. **Usa sinónimos** (el sistema los entiende)
3. **Intenta de nuevo** con el campo específico:
   ```
   "Ciudad de facturación 11001000"
   ```

### Si el valor es incorrecto:

1. **Especifica mejor** el valor:
   ```
   En lugar de: "facturacion bogota"
   Usa: "ciudad facturacion 11001000"
   ```

2. **Corrige manualmente** en el formulario si es necesario

### Si el chat no responde:

1. **Verifica la API Key** de OpenAI en `.env`
2. **Revisa los logs** en Laravel:
   ```bash
   tail -f /home/ubuntu/mcp/conalca/storage/logs/laravel.log
   ```

---

## 📚 CAMPOS OPCIONALES CON VALORES POR DEFECTO

Estos campos **NO necesitas especificarlos** porque el sistema ya tiene valores por defecto:

```javascript
// Se agregan automáticamente al payload:
empresa_codigo: 1
soltra_observacion: "PRUEBAS WS"
soltra_mandatario: 1
soltra_fechasolicitud: fecha actual (Y-m-d)
soltra_mostrarvehiculodigitalizado: "NO"
soltra_mostrarconductordigitalizado: "NO"
usuario_codigoautorizado: "BRANDON.LOPEZ"

// Condición factura (default):
condicionfacturacion: "CON EMISION DE DESPACHO"
facturaremesahija: "NO"
solounafactura: "NO"

// Cumplido (default):
condicioncumplido: "COMODATO, REMESA"
```

---

## 🎯 CASO DE USO REAL

**Escenario:** Operador de call center recibe pedido por teléfono

**Flujo:**
1. Operador escucha al cliente
2. Abre el formulario de solicitud
3. Clic en el ícono del chat 💬
4. **Escribe o dicta** (micrófono 🎤) los datos del cliente
5. El sistema **llena automáticamente** el formulario
6. Operador **verifica** los datos
7. Corrige manualmente si es necesario
8. **Envía** la solicitud

**Tiempo ahorrado:** ~5 minutos por solicitud

---

## 🔗 RECURSOS ADICIONALES

- **Documentación del chat:** `SolicitudWizard/EJEMPLOS_USO_CHAT.md`
- **Código fuente:** `resources/js/components/SolicitudWizard/ChatBox.jsx`
- **API Backend:** `app/Http/Controllers/Api/ChatController.php`
- **Logs Laravel:** `storage/logs/laravel.log`

---

**¡Listo para probar! 🚀**
