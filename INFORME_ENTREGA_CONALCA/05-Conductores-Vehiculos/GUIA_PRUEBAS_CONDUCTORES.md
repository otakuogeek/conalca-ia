# 🧪 Guía de Pruebas: Sistema de Búsqueda de Conductores

## 📋 Preparación

### Verificar que los cambios estén aplicados

```bash
cd /home/ubuntu/conalca/conalca

# 1. Verificar archivos modificados
git status

# 2. Verificar compilación frontend
ls -lh public/build/assets/app-*.js

# 3. Verificar que no haya errores
php artisan route:list | grep arcangel
```

**Output esperado**:
```
✓ Modified: app/Http/Controllers/Api/ArcangelDriversController.php
✓ Modified: resources/js/components/Calls/CallPanel.jsx
✓ Build: public/build/assets/app-2c8fef37.js (643.91 kB)
✓ Rutas: POST api/arcangel/buscar-conductores
```

---

## 🎯 Prueba 1: Backend (API)

### Test con Tinker

```bash
php artisan tinker --execute="
\$controller = app(\App\Http\Controllers\Api\ArcangelDriversController::class);
\$request = new \Illuminate\Http\Request();
\$request->merge(['cotizacion_id' => 31]);

\$response = \$controller->buscarConductores(\$request);
\$data = json_decode(\$response->getContent(), true);

echo '=== RESULTADO DE BÚSQUEDA ===' . PHP_EOL;
echo 'Success: ' . (\$data['success'] ? '✅ SI' : '❌ NO') . PHP_EOL;
echo 'Total conductores: ' . (\$data['data']['total'] ?? 0) . PHP_EOL;
echo 'Ciudad: ' . (\$data['data']['filtros']['ciudad'] ?? 'N/A') . PHP_EOL;
echo 'Vehículo original: ' . (\$data['data']['filtros']['vehiculo_original'] ?? 'N/A') . PHP_EOL;
echo 'Variantes buscadas: ' . json_encode(\$data['data']['filtros']['variantes_buscadas'] ?? []) . PHP_EOL;
echo 'Message: ' . (\$data['message'] ?? 'N/A') . PHP_EOL;
"
```

### ✅ Resultado Esperado

```
=== RESULTADO DE BÚSQUEDA ===
Success: ✅ SI
Total conductores: 2 (o más)
Ciudad: FUNZA
Vehículo original: Tracto Mula S3
Variantes buscadas: ["TRACTOMULA3","TRACTOMULA 3","TRACTOMULA S3"]
Message: Se encontraron 2 conductores disponibles
```

### ❌ Si Falla

**Error común**: "Column not found: 1054 Unknown column 'vehiculo_requerido'"

**Solución**: Verificar estructura de la tabla
```sql
SHOW COLUMNS FROM cotizacion_models LIKE '%vehiculo%';
```

---

## 🌐 Prueba 2: Frontend (Navegador)

### Paso a Paso

1. **Abrir navegador** en modo incógnito (Ctrl+Shift+N)

2. **Navegar a**:
   ```
   http://conalcaia.bissartravelclub.com/
   ```

3. **Login** con credenciales de prueba

4. **Ir a Cotizaciones** y abrir cualquier cotización existente

5. **Localizar la tarjeta "CONDUCTORES"**
   - Debe mostrar: "Registro de Llamadas"
   - Debe mostrar: "Tipo de vehículo: ..."
   - Debe mostrar: "Total conductores: X"

6. **Hacer clic en "Ver Listado"** (botón azul)

7. **Verificar que**:
   - ✅ Aparece spinner "Buscando…"
   - ✅ Se abre modal con tabla de conductores
   - ✅ Contador se actualiza: "Total conductores: X (Actualizado desde Arcángel)"
   - ✅ Tabla muestra: Placa, Conductor, Teléfono, Score, Disponibilidad

### ✅ Resultado Esperado (Consola del Navegador)

Presiona **F12** y ve a la pestaña **Console**:

```javascript
✅ ArcangelDriversController: Buscando conductores
✅ Success: true
✅ Total: 2
✅ Conductores: Array(2)
```

### ❌ Si Falla

**Error 500**:
1. Verificar logs Laravel:
   ```bash
   tail -50 storage/logs/laravel.log
   ```

2. Verificar que el endpoint esté registrado:
   ```bash
   php artisan route:list | grep buscar-conductores
   ```

**No se abre el modal**:
1. Verificar compilación:
   ```bash
   npm run build
   ```

2. Limpiar caché del navegador (Ctrl+Shift+R)

---

## 🔍 Prueba 3: Mapeo de Vehículos

### Verificar que el mapeo funciona correctamente

```php
php artisan tinker

// Copiar y pegar:
$controller = app(\App\Http\Controllers\Api\ArcangelDriversController::class);
$method = new ReflectionMethod($controller, 'convertirTipoVehiculo');
$method->setAccessible(true);

// Probar diferentes tipos
$tipos = [
    'Tracto Mula S3',
    'Sencillo',
    'Camioneta',
    'Patineta 2',
    'Doble Troque'
];

foreach ($tipos as $tipo) {
    $result = $method->invoke($controller, $tipo);
    echo "$tipo => " . json_encode($result) . PHP_EOL;
}
```

### ✅ Output Esperado

```
Tracto Mula S3 => ["TRACTOMULA3","TRACTOMULA 3","TRACTOMULA S3"]
Sencillo => ["SENCILLO"]
Camioneta => ["CAMIONETA","TURBO"]
Patineta 2 => ["PATINETA2","PATINETA 2"]
Doble Troque => ["DOBLE TROQUE","DOBLETROQUE"]
```

---

## 🚀 Prueba 4: Rendimiento

### Medir tiempo de respuesta

```bash
time curl -X POST http://localhost/api/arcangel/buscar-conductores \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"cotizacion_id": 31}'
```

### ✅ Tiempo Esperado

```
real    0m1.234s  ← Debe ser < 3 segundos
user    0m0.012s
sys     0m0.008s
```

**Nota**: Primera llamada puede tardar más (sin caché). Siguientes llamadas son instantáneas (con caché).

---

## 📊 Prueba 5: Diferentes Ciudades

### Probar con múltiples ciudades

```bash
php artisan arcangel:consultar-localidad "BOGOTA" | head -20
php artisan arcangel:consultar-localidad "MEDELLIN" | head -20
php artisan arcangel:consultar-localidad "CALI" | head -20
```

### ✅ Resultado Esperado

Cada ciudad debe mostrar:
```
✓ Ciudad: BOGOTA
✓ Total vehículos: XX
✓ Tipos: TRACTOMULA3, SENCILLO, CAMIONETA, etc.
```

---

## 🧹 Limpieza y Mantenimiento

### Limpiar caché después de cambios

```bash
# Limpiar caché de Laravel
php artisan cache:clear

# Limpiar caché de rutas
php artisan route:clear

# Limpiar caché de configuración
php artisan config:clear

# Optimizar aplicación
php artisan optimize
```

### Verificar logs

```bash
# Ver últimos errores
tail -100 storage/logs/laravel.log | grep ERROR

# Ver logs en tiempo real
tail -f storage/logs/laravel.log | grep ArcangelDriversController
```

---

## 📝 Casos de Prueba

### Caso 1: Cotización con Tractomula

**Input**: Cotización con "Tracto Mula S3" en Funza

**Expected**:
- ✅ Encuentra conductores con TRACTOMULA3, TRACTOMULA 3, TRACTOMULA S3
- ✅ Total: 2+ conductores
- ✅ Modal se abre correctamente

### Caso 2: Cotización con Sencillo

**Input**: Cotización con "Sencillo" en Bogotá

**Expected**:
- ✅ Encuentra conductores con SENCILLO
- ✅ Total: 10+ conductores
- ✅ Modal se abre correctamente

### Caso 3: Cotización sin conductores

**Input**: Cotización con "TIPO_INEXISTENTE" en ciudad pequeña

**Expected**:
- ✅ Success: true
- ✅ Total: 0
- ✅ Message: "No se encontraron conductores disponibles en CIUDAD con vehículos tipo: TIPO_INEXISTENTE"
- ✅ Modal muestra tabla vacía con mensaje

---

## 🐛 Debugging

### Si algo falla

1. **Verificar estructura de datos**:
   ```sql
   SELECT id, ciudad_origen, vehiculo_requerido 
   FROM cotizacion_models 
   LIMIT 5;
   ```

2. **Verificar conexión con Arcángel**:
   ```bash
   php artisan arcangel:listar-ciudades | head -10
   ```

3. **Verificar tokens de autenticación**:
   ```bash
   php artisan tinker --execute="
   \$service = app(\App\Services\ArcangelService::class);
   try {
       \$token = \$service->getToken();
       echo 'Token: ' . substr(\$token, 0, 20) . '...' . PHP_EOL;
   } catch (\Exception \$e) {
       echo 'ERROR: ' . \$e->getMessage() . PHP_EOL;
   }
   "
   ```

4. **Verificar rutas API**:
   ```bash
   php artisan route:list | grep -E "arcangel|conductor"
   ```

---

## ✅ Checklist Final

### Antes de marcar como completo

- [ ] Backend: Endpoint responde 200 OK
- [ ] Backend: Encuentra conductores con mapeo correcto
- [ ] Backend: Variantes se muestran en respuesta
- [ ] Frontend: Botón "Ver Listado" visible
- [ ] Frontend: Modal se abre con datos
- [ ] Frontend: Contador se actualiza
- [ ] Frontend: Badge "Actualizado" aparece
- [ ] Logs: No hay errores 500
- [ ] Logs: Logs informativos presentes
- [ ] Performance: Respuesta < 3 segundos
- [ ] Cache: Funciona correctamente
- [ ] Documentación: Completa y actualizada

---

## 📞 Soporte

Si encuentras algún problema:

1. **Revisar logs**:
   ```bash
   tail -100 storage/logs/laravel.log
   ```

2. **Verificar consola del navegador** (F12)

3. **Probar endpoint directamente**:
   ```bash
   curl -X POST http://localhost/api/arcangel/buscar-conductores \
     -H "Content-Type: application/json" \
     -d '{"cotizacion_id": 31}'
   ```

4. **Consultar documentación**:
   - `RESUMEN_EJECUTIVO_CORRECCION_CONDUCTORES.md`
   - `CORRECCION_MAPEO_VEHICULOS_ARCANGEL.md`
   - `SISTEMA_CONDUCTORES_ARCANGEL.md`

---

**Última actualización**: Noviembre 16, 2025  
**Versión**: 1.0  
**Estado**: ✅ Listo para Producción
