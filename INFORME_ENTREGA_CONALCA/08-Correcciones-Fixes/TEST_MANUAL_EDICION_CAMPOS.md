# 🧪 TEST MANUAL: EDICIÓN DE CAMPOS - PRESERVACIÓN DE DATOS

Este documento contiene **prompts de prueba** para verificar que al modificar un campo específico, los demás campos mantienen sus valores originales.

---

## 📋 SECUENCIA DE PRUEBAS

### **TEST 1: Modificar SOLO el peso**

#### Paso 1: Crear cotización inicial completa
```
cotización de bogotá a medellín 15 toneladas de café por valor de 25 millones empaquetado en sacos son 120 unidades tipo de vehículo tractomula
```

**Datos esperados:**
- Origen: BOGOTÁ
- Destino: MEDELLÍN
- Peso: 15,000 kg
- Producto: CAFÉ
- Vehículo: TRACTOMULA
- Empaque: SACOS
- Cantidad: 120
- Valor: 25,000,000

#### Paso 2: Modificar SOLO el peso
```
cambia el peso a 20 toneladas
```

**Verificar:**
- ✅ Peso cambia a: 20,000 kg
- ✅ Origen permanece: BOGOTÁ
- ✅ Destino permanece: MEDELLÍN
- ✅ Producto permanece: CAFÉ
- ✅ Vehículo permanece: TRACTOMULA
- ✅ Empaque permanece: SACOS
- ✅ Cantidad permanece: 120
- ✅ Valor permanece: 25,000,000

---

### **TEST 2: Modificar SOLO el vehículo**

#### Paso 1: Usar la cotización del TEST 1 (con peso ya modificado)

#### Paso 2: Modificar SOLO el vehículo
```
cambia el vehículo a sencillo
```

**Verificar:**
- ✅ Vehículo cambia a: SENCILLO
- ✅ Peso permanece: 20,000 kg
- ✅ Origen permanece: BOGOTÁ
- ✅ Destino permanece: MEDELLÍN
- ✅ Producto permanece: CAFÉ
- ✅ Empaque permanece: SACOS
- ✅ Cantidad permanece: 120
- ✅ Valor permanece: 25,000,000

---

### **TEST 3: Modificar SOLO el origen**

#### Paso 1: Usar la cotización del TEST 2

#### Paso 2: Modificar SOLO el origen
```
cambia el origen a cali
```

**Verificar:**
- ✅ Origen cambia a: CALI
- ✅ Destino permanece: MEDELLÍN
- ✅ Peso permanece: 20,000 kg
- ✅ Producto permanece: CAFÉ
- ✅ Vehículo permanece: SENCILLO
- ✅ Empaque permanece: SACOS
- ✅ Cantidad permanece: 120
- ✅ Valor permanece: 25,000,000

---

### **TEST 4: Modificar SOLO el producto**

#### Paso 1: Usar la cotización del TEST 3

#### Paso 2: Modificar SOLO el producto
```
cambia el producto a arroz
```

**Verificar:**
- ✅ Producto cambia a: ARROZ
- ✅ Origen permanece: CALI
- ✅ Destino permanece: MEDELLÍN
- ✅ Peso permanece: 20,000 kg
- ✅ Vehículo permanece: SENCILLO
- ✅ Empaque permanece: SACOS
- ✅ Cantidad permanece: 120
- ✅ Valor permanece: 25,000,000

---

### **TEST 5: Modificar MÚLTIPLES campos a la vez**

#### Paso 1: Usar la cotización del TEST 4

#### Paso 2: Modificar cantidad y empaque simultáneamente
```
cambia la cantidad a 200 y el empaque a cajas
```

**Verificar:**
- ✅ Cantidad cambia a: 200
- ✅ Empaque cambia a: CAJAS
- ✅ Producto permanece: ARROZ
- ✅ Origen permanece: CALI
- ✅ Destino permanece: MEDELLÍN
- ✅ Peso permanece: 20,000 kg
- ✅ Vehículo permanece: SENCILLO
- ✅ Valor permanece: 25,000,000

---

### **TEST 6: Modificar destino**

#### Paso 1: Usar la cotización del TEST 5

#### Paso 2: Modificar SOLO el destino
```
cambia el destino a bucaramanga
```

**Verificar:**
- ✅ Destino cambia a: BUCARAMANGA
- ✅ Origen permanece: CALI
- ✅ Peso permanece: 20,000 kg
- ✅ Producto permanece: ARROZ
- ✅ Vehículo permanece: SENCILLO
- ✅ Empaque permanece: CAJAS
- ✅ Cantidad permanece: 200
- ✅ Valor permanece: 25,000,000

---

### **TEST 7: Modificar valor declarado**

#### Paso 1: Usar la cotización del TEST 6

#### Paso 2: Modificar SOLO el valor
```
cambia el valor a 40 millones
```

**Verificar:**
- ✅ Valor cambia a: 40,000,000
- ✅ Destino permanece: BUCARAMANGA
- ✅ Origen permanece: CALI
- ✅ Peso permanece: 20,000 kg
- ✅ Producto permanece: ARROZ
- ✅ Vehículo permanece: SENCILLO
- ✅ Empaque permanece: CAJAS
- ✅ Cantidad permanece: 200

---

## 📊 CHECKLIST DE VERIFICACIÓN

Para cada test, verifica en el frontend:

1. **Antes de modificar:** Anota todos los valores de los campos
2. **Después de modificar:** Verifica que:
   - ✓ El campo modificado tiene el nuevo valor
   - ✓ TODOS los demás campos mantienen sus valores originales
   - ✓ No hay campos vacíos o con valores `null`
   - ✓ No hay campos con valores incorrectos o mezclados

---

## ❌ ERRORES COMUNES A DETECTAR

- Campo modificado NO cambia
- Otros campos se borran (quedan vacíos)
- Otros campos cambian al valor equivocado
- Datos se mezclan entre campos
- Valores numéricos se convierten incorrectamente

---

## ✅ RESULTADO ESPERADO

**TODOS los tests deben pasar:**
- 7/7 modificaciones exitosas
- 0 campos corruptos
- 100% de preservación de datos

Si algún test falla, reporta:
1. Número del test que falló
2. Campo que se modificó
3. Campos que se corrompieron (valor esperado vs valor obtenido)
4. Captura de pantalla del estado antes y después

---

## 🚀 CÓMO EJECUTAR

1. Abre el frontend de cotizaciones
2. Crea una nueva cotización
3. Sigue la secuencia de tests en orden
4. Marca cada ✅ cuando el test pase
5. Si un test falla, detente y reporta el error

---

**Fecha de creación:** 2026-01-20  
**Versión:** 1.0  
**Autor:** Sistema de Testing Conalca
