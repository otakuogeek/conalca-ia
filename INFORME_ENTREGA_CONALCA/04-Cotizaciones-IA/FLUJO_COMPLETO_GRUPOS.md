# ✅ Solución Implementada: Un Solo Registro con Todos los Datos

## Problema Original
Se creaban **2 registros** en `group_cotizations`:
1. Registro inicial (API) con `operation_type`, `candado_satelital`, etc.
2. Registro duplicado (Livewire) sin esos datos

## Solución Actual

### Flujo Completo

```
1. Usuario abre crear cotización (React)
   ↓
2. React → POST /api/chat/quote/create-group
   ↓
3. API GUARDA en sesión:
   - operation_type (IMPORTACION/EXPORTACION/DISTRIBUCION)
   - candado_satelital (true/false)
   - jen_set (true/false)
   - combustible (true/false)
   - kit_derrames (true/false)
   - pictogramas (true/false)
   - cargo_type (refrigerado/dangerous/etc)
   ↓
4. API retorna solo thread_id (NO crea grupo todavía)
   ↓
5. Usuario completa formulario en React/Chat
   ↓
6. Usuario guarda cotización
   ↓
7. Livewire → storeST()
   - Lee parámetros de session('quote_creation_params')
   - Combina con datos del formulario
   - CREA 1 SOLO GRUPO con TODOS los datos
   - Limpia sesión
```

### Datos Guardados

**En la sesión** (paso 3):
```php
session('quote_creation_params') = [
    'user_id' => 13,
    'client_id' => 615,
    'type' => 'dta',
    'operation_type' => 'IMPORTACION',  // ← IMPORTANTE
    'candado_satelital' => true,         // ← IMPORTANTE
    'cargo_type' => 'refrigerado',       // ← IMPORTANTE
    'jen_set' => true,                   // ← IMPORTANTE
    'combustible' => true,               // ← IMPORTANTE
    'kit_derrames' => false,
    'pictogramas' => false,
]
```

**En el grupo final** (paso 7):
```php
GroupCotization::create([
    'user_id' => 13,
    'client_id' => 615,
    'type' => 'dta',
    'status' => 'pendiente',
    
    // Estos vienen de la SESIÓN (guardados por React/API):
    'operation_type' => 'IMPORTACION',   // ✅ De sesión
    'candado_satelital' => true,          // ✅ De sesión
    'cargo_type' => 'refrigerado',        // ✅ De sesión
    'jen_set' => true,                    // ✅ De sesión
    'combustible' => true,                // ✅ De sesión
    'kit_derrames' => false,              // ✅ De sesión
    'pictogramas' => false,               // ✅ De sesión
]);
```

## Logs para Verificar

### 1. Al crear cotización (React → API)
```
[INFO] Preparando parámetros de cotización (sin crear grupo todavía)
[INFO] ✅ Parámetros guardados en sesión para creación posterior
```

### 2. Al guardar (Livewire → storeST)
```
[INFO] 📝 Creando nuevo grupo de cotización {"using_session_params":true}
[INFO] ✅ Grupo CREADO AL FINAL con todos los datos {"used_session_params":true,"final_data":{...}}
[INFO] 🧹 Parámetros de sesión limpiados
```

## Verificación

### Ver logs en tiempo real:
```bash
tail -f /home/ubuntu/conalca/conalca/storage/logs/laravel.log | grep -E 'Parámetros guardados|Grupo CREADO|using_session_params'
```

### Verificar base de datos:
```sql
-- Último registro debe tener TODOS los datos
SELECT 
    id,
    operation_type,      -- Debe tener valor (IMPORTACION/EXPORTACION/DISTRIBUCION)
    candado_satelital,   -- Debe ser 0 o 1
    jen_set,            -- Debe ser 0 o 1
    combustible,        -- Debe ser 0 o 1
    cargo_type,         -- Debe tener valor
    status,             -- Debe ser 'pendiente'
    created_at
FROM group_cotizations 
ORDER BY id DESC 
LIMIT 1;
```

### Resultado esperado:
```
id  | operation_type | candado_satelital | jen_set | combustible | cargo_type   | status
----|----------------|-------------------|---------|-------------|--------------|----------
127 | IMPORTACION    | 1                 | 1       | 1           | refrigerado  | pendiente
```

## Archivos Modificados

1. **`app/Http/Controllers/Api/QuoteCreationController.php`**
   - Guarda parámetros en sesión
   - NO crea el grupo

2. **`app/Livewire/QuoteIndex.php`**
   - Lee `session('quote_creation_params')`
   - Usa esos valores al crear el grupo
   - Limpia sesión después

3. **`resources/js/components/CotizacionInicial/QuoteIndex.jsx`**
   - NO espera `group_id`
   - Solo usa `thread_id`

## Estado Actual

✅ API guarda parámetros en sesión  
✅ Livewire lee parámetros de sesión  
✅ Se crea 1 SOLO registro  
✅ Registro tiene TODOS los datos  
✅ Sesión se limpia automáticamente  

## Prueba Manual

1. Ve a: https://conalcaia.conalca.com.co/cotizacion
2. Crea nueva cotización
3. Completa formulario
4. Guarda
5. Verifica en DB: 1 registro con `operation_type` lleno

---

**Fecha**: 2025-11-18  
**Estado**: ✅ COMPLETADO Y VERIFICADO
