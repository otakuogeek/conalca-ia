# SISTEMA DE LLAMADAS CON CÓDIGO DE PAÍS AUTOMÁTICO

## ✅ Implementación Completada

### 🔧 Cambios Realizados

#### 1. **Backend - ArcangelDriversController**
**Archivo:** `app/Http/Controllers/Api/ArcangelDriversController.php`

**Nueva funcionalidad:**
- ✅ Método `llamarConductor()` - Endpoint para iniciar llamadas
- ✅ Método `formatearTelefono()` - Formatea automáticamente con +57

**Características del formateo:**
```php
// Entrada → Salida
3105672307      → +573105672307  (10 dígitos, agrega +57)
310 567 2307    → +573105672307  (con espacios, limpia y agrega +57)
310-567-2307    → +573105672307  (con guiones, limpia y agrega +57)
+573105672307   → +573105672307  (ya tiene +57, mantiene)
31056723        → +5731056723    (7-9 dígitos, agrega +57)
573105672307    → +573105672307  (12 dígitos, agrega +)
```

**Validaciones:**
- ✅ Limpia espacios y guiones
- ✅ Detecta si ya tiene código de país (+)
- ✅ Valida longitud mínima (7 dígitos)
- ✅ Agrega +57 automáticamente para números colombianos

#### 2. **API Route**
**Archivo:** `routes/api.php`

```php
Route::post('/llamar-conductor', [ArcangelDriversController::class, 'llamarConductor'])
    ->name('api.arcangel.llamar.conductor');
```

**Endpoint:** `POST /api/arcangel/llamar-conductor`

**Request:**
```json
{
  "cotizacion_id": 123,
  "conductor_nombre": "SEBASTIAN DELORIAN",
  "telefono": "3105672307",
  "placa": "ABC123"
}
```

**Response exitosa:**
```json
{
  "success": true,
  "message": "Llamada iniciada a SEBASTIAN DELORIAN",
  "data": {
    "llamada_id": 456,
    "conversation_id": "conv_xyz123",
    "telefono": "+573105672307",
    "conductor": "SEBASTIAN DELORIAN",
    "status": "en_progreso"
  }
}
```

#### 3. **Frontend - DriversModal**
**Archivo:** `resources/js/components/CotizacionInicial/DriversModal.jsx`

**Función actualizada:**
```javascript
const handleRegistrarLlamada = async (driver) => {
  // Llama al endpoint /api/arcangel/llamar-conductor
  // Envía: cotizacion_id, conductor_nombre, telefono, placa
  // El backend agrega automáticamente +57
  // Muestra alerta con resultado
};
```

**UI:**
- ✅ Botón "Llamar" en cada conductor
- ✅ Loading state durante la llamada
- ✅ Alertas de éxito/error
- ✅ Auto-refresh cada 30s mantiene datos actualizados

## 📊 Flujo de Llamada

```
Usuario → Click "Llamar" en Modal
    ↓
Frontend → POST /api/arcangel/llamar-conductor
    ↓
Backend → formatearTelefono(3105672307) → +573105672307
    ↓
Backend → Registra en tabla `llamadas`
    ↓
Backend → ElevenLabsCallService::initiateCall(+573105672307)
    ↓
ElevenLabs API → Inicia llamada al número
    ↓
Backend → Actualiza llamada con conversation_id
    ↓
Frontend ← Respuesta exitosa con datos
    ↓
Usuario ← Alerta "✅ Llamada iniciada"
```

## 🧪 Testing

### Script de Prueba
**Archivo:** `test_formateo_telefono.sh`

**Ejecutar:**
```bash
./test_formateo_telefono.sh
```

**Resultados:**
- ✅ Números de 10 dígitos: +573105672307
- ✅ Números con espacios: +573105672307
- ✅ Números con guiones: +573105672307
- ✅ Números que ya tienen +57: +573105672307

### Casos Probados
```
Input          → Output           → Razón
3105672307     → +573105672307    → 10 dígitos colombianos
3158142020     → +573158142020    → 10 dígitos colombianos
3022372269     → +573022372269    → 10 dígitos colombianos
+573105672307  → +573105672307    → Ya tiene código
310 567 2307   → +573105672307    → Limpia espacios
310-567-2307   → +573105672307    → Limpia guiones
```

## 🔍 Base de Datos

### Tabla `llamadas`
```sql
INSERT INTO llamadas (
    id_cotizacion,
    chofer_nombre,
    telefono,  -- Se guarda con +57
    placa,
    status,    -- 'iniciando' → 'en_progreso' → 'completada'
    origen,    -- 'arcangel_modal'
    elevenlabs_conversation_id,
    fecha_hora
)
```

**Estados posibles:**
- `iniciando`: Llamada recién creada
- `en_progreso`: ElevenLabs procesando
- `completada`: Llamada finalizada
- `fallida`: Error al iniciar

## 🎯 Características del Sistema

### Automático
- ✅ Detecta formato del número
- ✅ Agrega +57 si falta
- ✅ Limpia espacios/guiones
- ✅ Valida longitud

### Flexible
- ✅ Acepta números con/sin +57
- ✅ Acepta diferentes formatos
- ✅ Maneja números cortos (7-9 dígitos)
- ✅ Maneja números largos (>10 dígitos)

### Robusto
- ✅ Validación en backend
- ✅ Registro en base de datos
- ✅ Logging detallado
- ✅ Manejo de errores

## 📝 Ejemplos de Uso

### Desde el Modal
1. Abrir modal de conductores
2. Ver lista de conductores disponibles
3. Click en botón "Llamar"
4. Sistema formatea automáticamente: `3105672307` → `+573105672307`
5. Inicia llamada con ElevenLabs
6. Muestra alerta de confirmación

### Desde API (curl)
```bash
curl -X POST https://conalcaia.conalca.com.co/api/arcangel/llamar-conductor \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: token_aqui" \
  -d '{
    "cotizacion_id": 123,
    "conductor_nombre": "SEBASTIAN DELORIAN",
    "telefono": "3105672307",
    "placa": "ABC123"
  }'
```

**El sistema convierte automáticamente:**
- `"telefono": "3105672307"` 
- Se envía a ElevenLabs como: `"+573105672307"`

## ⚠️ Consideraciones

### Números Válidos
- ✅ 10 dígitos: Número colombiano → +57
- ✅ 7-9 dígitos: Número corto → +57
- ✅ 12+ dígitos: Internacional → +
- ❌ <7 dígitos: Inválido → null

### Código de País
- 🇨🇴 Colombia: +57 (por defecto)
- 🌎 Otros países: Mantiene el código existente
- 📱 Formato: +[código][número]

## 🚀 Estado Final

**✅ Implementado y Compilado**
- Backend: Endpoint funcional
- Frontend: Botones activos
- Formateo: +57 automático
- Testing: Casos probados
- Logs: Información detallada

**Archivos Modificados:**
1. `app/Http/Controllers/Api/ArcangelDriversController.php`
2. `routes/api.php`
3. `resources/js/components/CotizacionInicial/DriversModal.jsx`

**Archivos Nuevos:**
1. `test_formateo_telefono.sh`

**Frontend Compilado:** ✅
- `app-632665ea.js` (645.90 kB)

---

**Fecha:** 19 de noviembre de 2025
**Estado:** ✅ Funcional y probado
