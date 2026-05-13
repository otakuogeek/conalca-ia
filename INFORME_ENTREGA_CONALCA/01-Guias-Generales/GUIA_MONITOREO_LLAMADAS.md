# 🔊 GUÍA DE MONITOREO EN TIEMPO REAL - SISTEMA DE LLAMADAS

## 📋 Situación Actual

✅ **Worker de cola ACTIVO** (PID: 210083)  
✅ **Scripts de monitoreo creados**  
✅ **Sistema listo para procesar llamadas**

---

## 🚀 OPCIÓN 1: Monitor Completo (Dashboard en Terminal)

Este monitor muestra un dashboard completo que se actualiza cada 3 segundos con:
- Estado del worker
- Llamadas en BD (pendientes, procesando, completadas, etc.)
- Trabajos en cola
- Últimas 5 llamadas registradas
- Últimos 10 eventos del sistema

### Ejecutar:
```bash
cd /home/ubuntu/conalca/conalca
./monitor_llamadas_real_time.sh
```

### Detener:
Presiona `Ctrl + C`

---

## 🔍 OPCIÓN 2: Visor de Logs en Tiempo Real

Este visor muestra SOLO los eventos relevantes del sistema de llamadas en tiempo real:

### Ejecutar:
```bash
cd /home/ubuntu/conalca/conalca
./ver_logs_llamadas.sh
```

### Qué verás:
- 👆 **AMARILLO**: Cuando haces clic en "Registrar Llamadas"
- 🚀 **CYAN**: Cuando inicia el proceso de registro
- ⚙️ **MAGENTA**: Configuración del sistema de lotes
- ✅ **VERDE**: Job despachado a la cola
- 📦 **AZUL**: Procesamiento de lotes
- 📞 **CYAN**: Procesamiento de llamada individual
- 🎙️ **MAGENTA**: Llamada a API de ElevenLabs
- 🔴 **ROJO**: Errores

### Detener:
Presiona `Ctrl + C`

---

## 🎯 FLUJO COMPLETO QUE VERÁS

Cuando hagas clic en **"Registrar Llamadas"** en el dashboard, verás este flujo:

### 1️⃣ CLICK EN BOTÓN
```
👆 [timestamp] BOTÓN CLICKEADO: CallsManager@registrarLlamadasParaGrupo
```

### 2️⃣ INICIO DEL PROCESO
```
🚀 [timestamp] INICIANDO PROCESO: initiateGroupConversationalCall
```

### 3️⃣ BÚSQUEDA DE CONDUCTORES
```
ℹ️  [timestamp] Buscando conductores en Arcángel...
```

### 4️⃣ CONFIGURACIÓN DE LOTES
```
⚙️  [timestamp] CONFIGURANDO LOTES: total_vehiculos: 5, max_concurrent: 3, total_batches: 2
```

### 5️⃣ REGISTRO EN BASE DE DATOS
```
ℹ️  [timestamp] Registrando llamada para conductor: conductor_id=X, batch_number=1, batch_position=1
ℹ️  [timestamp] Registrando llamada para conductor: conductor_id=Y, batch_number=1, batch_position=2
```

### 6️⃣ DISPATCH DEL JOB
```
✅ [timestamp] JOB DESPACHADO: ProcessBatchElevenLabsCalls, cotizacion_id=X, starting_batch=1
```

### 7️⃣ PROCESAMIENTO DEL LOTE (Worker)
```
📦 [timestamp] PROCESANDO LOTE: ProcessBatchElevenLabsCalls, batch_number=1, total_llamadas=3
```

### 8️⃣ PROCESAMIENTO DE LLAMADAS INDIVIDUALES
```
📞 [timestamp] PROCESANDO LLAMADA: ProcessElevenLabsCall, llamada_id=X
📞 [timestamp] PROCESANDO LLAMADA: ProcessElevenLabsCall, llamada_id=Y
```

### 9️⃣ LLAMADAS A ELEVENLABS API
```
🎙️  [timestamp] ELEVENLABS API: Iniciando llamada a +57XXXXXXXXXX
🎙️  [timestamp] ELEVENLABS API: Respuesta recibida, conversation_id=conv_XXXXX
```

### 🔟 SIGUIENTE LOTE (si hay más conductores)
```
📦 [timestamp] PROCESANDO LOTE: ProcessBatchElevenLabsCalls, batch_number=2
```

---

## 🛠️ VERIFICACIÓN RÁPIDA

### Ver estado del worker:
```bash
ps aux | grep "queue:work" | grep -v grep
```

### Ver llamadas pendientes:
```bash
php artisan tinker --execute="echo App\Models\Llamada::where('queue_status', 'pending')->count();"
```

### Ver trabajos en cola:
```bash
php artisan tinker --execute="echo DB::table('jobs')->count();"
```

### Ver últimos logs manualmente:
```bash
tail -f storage/logs/laravel.log | grep -E "(ProcessBatch|ElevenLabs|registrarLlamadasParaGrupo)"
```

---

## 🔴 SI NO VES ACTIVIDAD

### 1. Verificar que el worker esté corriendo:
```bash
ps aux | grep "queue:work" | grep -v grep
```

Si no está corriendo:
```bash
php artisan queue:work --queue=calls --tries=3 --timeout=300
```

### 2. Limpiar llamadas canceladas:
```bash
php artisan tinker --execute="
App\Models\Llamada::where('queue_status', 'cancelled')->update(['queue_status' => 'pending']);
echo 'Llamadas reactivadas';
"
```

### 3. Verificar que hay conductores para llamar:
```bash
php artisan tinker --execute="
echo 'Conductores registrados: ' . App\Models\LlamadaConductor::count();
"
```

---

## 📞 PRUEBA COMPLETA

### 1. Abrir DOS terminales:

**Terminal 1 - Monitor completo:**
```bash
cd /home/ubuntu/conalca/conalca
./monitor_llamadas_real_time.sh
```

**Terminal 2 - Logs en tiempo real:**
```bash
cd /home/ubuntu/conalca/conalca
./ver_logs_llamadas.sh
```

### 2. Ir al dashboard:
- Abre el navegador en el dashboard de cotizaciones
- Selecciona una cotización con conductores disponibles
- Haz clic en **"Registrar Llamadas"**

### 3. Observar:
- **Terminal 1**: Verás cambios en las estadísticas cada 3 segundos
- **Terminal 2**: Verás el flujo completo en tiempo real

---

## 🎨 LEYENDA DE COLORES (ver_logs_llamadas.sh)

| Emoji | Color | Significado |
|-------|-------|-------------|
| 👆 | AMARILLO | Click en botón "Registrar Llamadas" |
| 🚀 | CYAN | Inicio del proceso |
| ⚙️ | MAGENTA | Configuración de lotes |
| ✅ | VERDE | Job despachado exitosamente |
| 📦 | AZUL | Procesamiento de lote |
| 📞 | CYAN | Procesamiento de llamada individual |
| 🎙️ | MAGENTA | Interacción con ElevenLabs API |
| 🔴 | ROJO | Error en el proceso |
| ℹ️ | BLANCO | Información general |

---

## 📊 MÉTRICAS EN TIEMPO REAL

El monitor completo (`monitor_llamadas_real_time.sh`) muestra:

1. **Estado del Worker**: PID, CPU%, MEM%, tiempo de ejecución
2. **Llamadas en BD**:
   - Pendientes 📋
   - Procesando 🔄
   - Completadas ✅
   - Canceladas ❌
   - Fallidas ⚠️
3. **Jobs en Cola**: Trabajos pendientes y fallidos
4. **Últimas 5 Llamadas**: Con estado y timestamp
5. **Últimos 10 Eventos**: Del log filtrado

---

## 🚨 TROUBLESHOOTING

### No veo logs al hacer clic en "Registrar Llamadas"

1. Verificar que el botón esté enviando la petición:
   ```bash
   tail -f storage/logs/laravel.log | grep "POST /api/call-drivers-group"
   ```

2. Verificar que el endpoint existe:
   ```bash
   php artisan route:list | grep call-drivers-group
   ```

### El worker no procesa los jobs

1. Reiniciar el worker:
   ```bash
   pkill -f "queue:work"
   php artisan queue:work --queue=calls --tries=3 --timeout=300
   ```

2. Verificar la tabla `jobs`:
   ```bash
   php artisan tinker --execute="DB::table('jobs')->count();"
   ```

### Las llamadas se quedan en "pending"

Esto es normal si:
- El job aún no ha sido procesado por el worker
- Hay delay entre lotes (150 segundos configurados)

Verificar:
```bash
php artisan tinker --execute="
\$processing = App\Models\Llamada::where('queue_status', 'processing')->count();
echo 'Procesando: ' . \$processing;
"
```

---

## ✅ ESTADO ACTUAL DEL SISTEMA

- ✅ Worker corriendo (PID: 210083)
- ✅ Scripts de monitoreo creados y ejecutables
- ✅ Sistema de lotes configurado (3 llamadas concurrentes)
- ✅ Delay entre lotes: 150 segundos
- ✅ Delay entre llamadas: 5 segundos
- ✅ ElevenLabs API configurado
- ✅ Base de datos lista

**🎯 Ahora solo falta hacer clic en "Registrar Llamadas" y observar el monitor!**
