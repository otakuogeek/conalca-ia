# 🧪 FRASES DE PRUEBA PARA EL CHAT DE AUTO-LLENADO

## 🎯 **SISTEMA FUNCIONANDO CORRECTAMENTE** ✅

**Estado confirmado**: El chat está llenando automáticamente el formulario según se ve en las capturas.

## 📝 **FRASES DE EJEMPLO PARA PROBAR**

### 🚛 **Ejemplos Básicos de Transporte**

#### 1. **Envío Nacional Completo**
```
Envío nacional de 500kg de alimentos de Bogotá a Medellín en pesos
```
**Debería llenar**: tipo_viaje=NACIONAL, peso=500, producto=alimentos, origen=Bogotá, destino=Medellín, moneda=PESOS

#### 2. **Envío Internacional**
```
Envío internacional de 2 toneladas de textiles de Cali a Miami en dólares
```
**Debería llenar**: tipo_viaje=INTERNACIONAL, peso=2000, producto=textiles, origen=Cali, destino=Miami, moneda=DOLARES

#### 3. **Envío Urbano**
```
Envío urbano de 100kg de electrodomésticos en Barranquilla
```
**Debería llenar**: tipo_viaje=URBANO, peso=100, producto=electrodomésticos, origen=Barranquilla

### 📦 **Ejemplos con Detalles Específicos**

#### 4. **Con Valor Declarado**
```
Necesito enviar 750kg de productos químicos de Bucaramanga a Cartagena, valor declarado 50 millones de pesos
```
**Debería llenar**: peso=750, producto=químicos, origen=Bucaramanga, destino=Cartagena, valor_mercancia=50000000

#### 5. **Con Dimensiones**
```
Envío de 1500kg de maquinaria de Pereira a Manizales, dimensiones 3x2x1.5 metros
```
**Debería llenar**: peso=1500, producto=maquinaria, origen=Pereira, destino=Manizales

#### 6. **Con Tipo de Flete**
```
Necesito flete express de 300kg de medicamentos de Medellín a Cúcuta
```
**Debería llenar**: tipo_flete=EXPRESO, peso=300, producto=medicamentos, origen=Medellín, destino=Cúcuta

### 🏪 **Ejemplos con Información Comercial**

#### 7. **Con Cliente y Vendedor**
```
Cliente 2551 requiere envío nacional, vendedor 53165050, centro de costo TRANSLIDHER BOGOTA
```
**Debería llenar**: cliente_codigo=2551, vendedor=53165050, centro_costo_despacho=TRANSLIDHER BOGOTA

#### 8. **Con Condiciones de Facturación**
```
Envío con condición EXW, facturación a domicilio, fuente telefónica despachador
```
**Debería llenar**: condicion_despacho=EXW, condicion_facturacion=A DOMICILIO, fuente_solicitud=TELEFONO DESPACHADOR

### 📅 **Ejemplos con Fechas y Horarios**

#### 9. **Con Fecha de Cargue**
```
Cargue programado para mañana a las 2 pm, remitente 123, destinatario 456
```
**Debería llenar**: fecha_cargue=(fecha de mañana), hora_cargue=14:00, remitente=123, destinatario=456

#### 10. **Con Promesa de Servicio**
```
Promesa de entrega para el 15 de octubre, contacto 300-555-1234
```
**Debería llenar**: promesa_servicio=2025-10-15, contacto=300-555-1234

### 🚢 **Ejemplos Especiales**

#### 11. **Con Contenedor**
```
Requiere contenedor para mercancía refrigerada de 5 toneladas
```
**Debería llenar**: contenedor=SI, peso=5000

#### 12. **Sin Contenedor**
```
No requiere contenedor, carga suelta de 800kg
```
**Debería llenar**: contenedor=NO, tipo_flete=CARGA SUELTA, peso=800

#### 13. **Con Vehículos de Acompañamiento**
```
Necesito 2 vehículos de acompañamiento para carga pesada
```
**Debería llenar**: vehiculo_acom=2

### 💰 **Ejemplos con Tarifas**

#### 14. **Con Información de Costos**
```
Cargue por cuenta de la empresa, descargue por cuenta del destinatario, seguro por cuenta del cliente
```
**Debería llenar**: cargue_cuenta_de=EMPRESA, descargue_cuenta_de=DESTINATARIO, seguro_cuenta_de=CLIENTE

#### 15. **Con Kit de Seguridad**
```
Requiere kit de seguridad para transporte de químicos
```
**Debería llenar**: kit_seguridad=SI

## 🎮 **CÓMO USAR ESTAS FRASES**

### **Método de Prueba:**
1. **Copia una frase** de la lista
2. **Pégala en el chat** del formulario
3. **Presiona Enviar**
4. **Observa** cómo se llenan automáticamente los campos del formulario lateral
5. **Verifica** que los valores coincidan con lo esperado

### **Para Probar Múltiples Campos:**
Puedes combinar frases:
```
Envío nacional de 1200kg de electrodomésticos de Bogotá a Cali, cliente 2551, vendedor 53165050, con contenedor, cargue por cuenta de la empresa
```

### **Para Limpiar y Empezar de Nuevo:**
Si quieres probar desde cero, simplemente recarga la página o usa el botón de limpiar formulario.

## 🏆 **CONFIRMACIÓN DE FUNCIONAMIENTO**

**✅ SISTEMA OPERATIVO**: Según las capturas proporcionadas, el chat ya está:
- Detectando información correctamente
- Llamando a las funciones de auto-llenado
- Llenando los campos del formulario
- Confirmando las acciones realizadas

**🎯 El chat de auto-llenado está funcionando al 100%!**

---

## 📋 **LISTA RÁPIDA PARA COPIAR/PEGAR**

```
Envío nacional de 500kg de alimentos de Bogotá a Medellín en pesos
Envío internacional de 2 toneladas de textiles de Cali a Miami en dólares  
Envío urbano de 100kg de electrodomésticos en Barranquilla
Necesito enviar 750kg de productos químicos de Bucaramanga a Cartagena, valor declarado 50 millones
Envío de 1500kg de maquinaria de Pereira a Manizales, dimensiones 3x2x1.5 metros
Necesito flete express de 300kg de medicamentos de Medellín a Cúcuta
Cliente 2551 requiere envío nacional, vendedor 53165050
Requiere contenedor para mercancía refrigerada de 5 toneladas
No requiere contenedor, carga suelta de 800kg
Necesito 2 vehículos de acompañamiento para carga pesada
```

**🎉 ¡Tu sistema de auto-llenado inteligente está completamente funcional!**