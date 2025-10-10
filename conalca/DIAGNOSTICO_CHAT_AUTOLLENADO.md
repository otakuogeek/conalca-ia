# 🔍 DIAGNÓSTICO DEL CHAT - AUTO-LLENADO

## ✅ Estado Actual

### 📋 Configuración Verificada:
- ✅ **ChatBox.jsx**: API OpenAI configurada con `function_call` 
- ✅ **FUNCTIONS**: Función `rellenar` definida correctamente
- ✅ **EventBus**: `chatBus.emit('fill-field', field, value)` con formato correcto
- ✅ **Step1.jsx**: Listener `chatBus.on('fill-field', fill)` activo
- ✅ **Step2.jsx**: Listener `chatBus.on('fill-field', fill)` activo
- ✅ **Assets**: Compilados con `npm run build`
- ✅ **Servidor**: Nginx funcionando correctamente

### 🔧 Normalización de Datos:
- ✅ **normalizeKey()**: Convierte aliases a nombres de campo
- ✅ **normalizeValue()**: Convierte valores a formatos correctos
- ✅ **ALIAS**: Mapeo de sinónimos a campos del formulario

## 🐛 Posibles Problemas

### 1. **Verificar Console Logs**
Abrir DevTools y buscar en Console:
```
📝 Step1 recibió fill-field: campo = valor
📝 Step2 recibió fill-field: campo = valor
[func-call] campo valor => valor_normalizado
```

### 2. **Verificar Respuesta de OpenAI**
En Network Tab buscar requests a `api.openai.com` y verificar:
- Que incluya `functions: [...]`
- Que la respuesta tenga `function_call`

### 3. **Verificar EventBus**
En Console ejecutar:
```javascript
import { chatBus } from './ChatBox.jsx';
chatBus.emit('fill-field', 'tipo_viaje', 'NACIONAL');
```

## 🔧 PASOS DE DEBUGGING

### Paso 1: Agregar Logs Temporales
Modificar ChatBox.jsx línea ~609:
```javascript
console.log('[func-call]', field, rawValue, '=>', value);
console.log('🚀 Emitiendo evento fill-field:', field, value);
chatBus.emit('fill-field', field, value);
console.log('✅ Evento emitido exitosamente');
```

### Paso 2: Verificar Respuesta OpenAI
Agregar log antes del while:
```javascript
console.log('📤 OpenAI Response:', response);
console.log('📤 Function Call?:', response.choices?.[0]?.message?.function_call);
```

### Paso 3: Probar Manualmente
En DevTools Console:
```javascript
// Importar ChatBox EventBus
const { chatBus } = window;
// Emitir evento manual
chatBus.emit('fill-field', 'tipo_viaje', 'NACIONAL');
```

## 🎯 PRUEBAS RECOMENDADAS

### Test 1: Mensaje Simple
```
"Necesito un viaje nacional en pesos"
```
**Esperado**: 
- `tipo_viaje = "NACIONAL"`
- `moneda = "PESOS"`

### Test 2: Mensaje Completo
```
"Envío de 500kg de alimentos de Bogotá (código 11001000) a Medellín (código 05001000)"
```
**Esperado**:
- `peso = "500"`
- `origen = "11001000"`
- `destino = "05001000"`

### Test 3: Verificar UI
Después de cada test, verificar que los campos del formulario se llenen automáticamente.

---
*Diagnóstico creado para resolver el auto-llenado del formulario*