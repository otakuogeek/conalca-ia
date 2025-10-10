# 📝 PROMPTS PARA PRUEBAS DEL CHAT - DATOS VÁLIDOS SILOGTRAN

## 🎯 PROMPT BÁSICO - TRANSPORTE NACIONAL

```
Necesito una cotización de transporte nacional de carga suelta desde Bogotá hasta Medellín. 
El cliente es AALPE LOGISTICA SAS. 
Voy a enviar 500 kilos de alimentos en cajas. 
Origen: Bogotá, destino: Medellín.
Valor declarado de la mercancía: 10000000 pesos.
Fecha de cargue: mañana.
```

---

## 🚀 PROMPT COMPLETO - TODOS LOS DATOS

```
Hola, necesito crear una solicitud de transporte con los siguientes datos:

DATOS GENERALES:
- Cliente: AALPE LOGISTICA SAS (código 1846)
- Tipo de viaje: Nacional
- Moneda: Pesos colombianos
- Centro de costos: CONALCA BOGOTA

ORIGEN Y DESTINO:
- Ciudad origen: Bogotá
- Dirección origen: Calle 100 # 20-30, Bodega 5
- Ciudad destino: Medellín  
- Dirección destino: Carrera 50 # 80-10, Centro Logístico

CARGA:
- Producto: MAIZ
- Cantidad: 100 unidades
- Peso total: 2500 kilos
- Volumen: 10 metros cúbicos
- Valor declarado: 15000000 pesos
- Tipo de embalaje: Sacos

FECHAS:
- Fecha de cargue: 15 de octubre de 2025
- Fecha de entrega prometida: 17 de octubre de 2025

TIPO DE FLETE:
- Carga suelta

Por favor genera la solicitud completa.
```

---

## ⚡ PROMPT RÁPIDO - AUTO-LLENADO

```
Llena todo con ejemplos usando cliente AALPE LOGISTICA de Bogotá a Medellín
```

---

## 🎯 PROMPT INTERNACIONAL - EXPORTACIÓN

```
Necesito una cotización para exportación marítima:

- Cliente: AALPE LOGISTICA SAS
- Tipo: Internacional - Exportación
- Origen: Bogotá, Colombia
- Puerto de embarque: Cartagena
- Destino: Miami, Estados Unidos
- Producto: Café (1000 sacos)
- Peso: 60000 kilos
- Contenedor: 1 x 40 pies
- Incoterm: FOB
- Valor FOB: 150000 dólares
- Fecha de embarque: 20 de octubre de 2025
```

---

## 🔄 PROMPT PASO A PASO - CONVERSACIONAL

### Paso 1
```
Hola, quiero hacer una cotización de transporte
```

### Paso 2 (cuando el chat responda)
```
Es nacional, de Bogotá a Cali
```

### Paso 3
```
El cliente es AALPE LOGISTICA SAS
```

### Paso 4
```
Voy a enviar 1000 kilos de productos químicos en tambores
```

### Paso 5
```
Valor de la mercancía: 8 millones de pesos. Fecha de cargue: 12 de octubre
```

### Paso 6
```
Perfecto, guarda toda la información
```

---

## 📋 DATOS DE REFERENCIA - VALORES VÁLIDOS

### Clientes Activos Verificados
- **1846** - AALPE LOGISTICA SAS ✅ (VÁLIDO EN SILOGTRAN)
- **3100** - AA METALS S.A.S.
- **2198** - ABB COLOMBIA LTDA
- **978** - ABBOTT LABORATORIES

### Centros de Costos Válidos
- CONALCA BOGOTA ✅
- CONALCA CALI
- CONALCA MEDELLIN
- CONALCA BARRANQUILLA
- ALMACENAMIENTO CALI
- CONALCA MANIZALEZ

### Ciudades Principales (códigos DIVIPOLA)
- **Bogotá**: 11001 → 11001000
- **Medellín**: 05001 → 05001000
- **Cali**: 76001 → 76001000
- **Barranquilla**: 08001 → 08001000
- **Cartagena**: 13001 → 13001000

### Tipos de Viaje
- Nacional ✅
- Internacional
- Urbano

### Monedas
- Pesos ✅ (COP)
- Dólares (USD)
- Bolívares (VEF)

### Tipos de Flete
- Carga suelta ✅
- Contenedor 20'
- Contenedor 40'

### Productos Ejemplo (existen en BD)
- MAIZ (código 93)
- ALIMENTOS
- CAFÉ
- QUÍMICOS
- TEXTILES

---

## 🎬 EJEMPLO DE CONVERSACIÓN COMPLETA

**Usuario:**
```
Hola, necesito cotizar un transporte
```

**Chat (esperado):**
```
¡Hola! Con gusto te ayudo a cotizar un transporte. 
Para comenzar, ¿es transporte nacional o internacional?
```

**Usuario:**
```
Nacional, de Bogotá a Medellín para el cliente AALPE LOGISTICA
```

**Chat (esperado):**
```
Perfecto, transporte nacional para AALPE LOGISTICA SAS.
¿Qué tipo de producto vas a transportar y qué cantidad?
```

**Usuario:**
```
Son 500 kilos de alimentos en cajas, valor de 5 millones de pesos
```

**Chat (esperado):**
```
Entendido:
- 500 kg de alimentos
- Valor: $5.000.000

¿Para qué fecha necesitas el cargue?
```

**Usuario:**
```
Para el 15 de octubre
```

**Chat (esperado):**
```
✅ He llenado el formulario con toda la información:
- Cliente: AALPE LOGISTICA SAS
- Origen: Bogotá → Destino: Medellín
- Producto: Alimentos (500 kg)
- Valor: $5.000.000
- Fecha cargue: 15/10/2025

Puedes revisar y ajustar los datos en el formulario lateral.
```

---

## 🎯 COMANDO MÁGICO - LLENADO AUTOMÁTICO

```
llena todo con ejemplos
```

Este comando activa el auto-llenado con datos de prueba válidos.

---

## ⚠️ NOTAS IMPORTANTES

1. **Siempre usar cliente 1846 (AALPE LOGISTICA)** - Es el único verificado en Silogtran
2. **Centro de costos**: Usar "CONALCA BOGOTA" (válido)
3. **Ciudades**: El sistema convertirá automáticamente a códigos DIVIPOLA de 8 dígitos
4. **Moneda**: Decir "pesos" o "COP" (se normaliza a "PESOS")
5. **Tipo de viaje**: Decir "nacional" o "internacional" (se normaliza a mayúsculas)

---

## 🧪 PARA PROBAR EN EL NAVEGADOR

1. Abre: https://conalcaia.conalca.com.co/solicitud-transporte
2. Activa el chat (botón en la esquina inferior derecha)
3. Copia y pega uno de los prompts de arriba
4. Observa cómo se llena automáticamente el formulario lateral
5. Completa los 6 pasos del wizard
6. Verifica que se crea exitosamente en Silogtran

---

## ✅ VALIDACIÓN ESPERADA

Al completar los 6 pasos, deberías ver:
```
✅ Solicitud guardada localmente
✅ Enviada a Silogtran exitosamente
✅ Número de solicitud Silogtran: ST 031259XXXX
```

¡Buena suerte con las pruebas! 🚀
