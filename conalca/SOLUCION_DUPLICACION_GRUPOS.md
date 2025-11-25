# Solución Definitiva: Duplicación de Registros en group_cotizations

## Fecha: 2025-11-18 (ACTUALIZADO - VERSIÓN FINAL)

## Problema Identificado

El sistema creaba **múltiples registros duplicados** en la tabla `group_cotizations`:

1. **Registro con datos** (status='borrador'): Creado por `QuoteCreationController` con TODOS los datos
2. **Registros vacíos** (status='Pre-Solicitud'): Creados por `QuoteSaveController` SIN verificar borradores existentes
3. **Más duplicados**: Creados por `QuoteIndex@saveDraft()` sin buscar borradores previos

### Patrón de Duplicación
- **ID 130**: `operation_type="IMPORTACION"`, `candado_satelital=1`, `jen_set=1`, status="borrador" ✅
- **IDs 131, 132**: `operation_type=NULL`, todos los campos=NULL, status="Pre-Solicitud" ❌

## Causa Raíz

**Tres puntos de creación sin coordinación**:

1. **QuoteCreationController** (`/api/chat/quote/create-group`): Crea borrador inicial ✅
2. **QuoteSaveController** (`/api/chat/save-quote-from-chat`): Creaba NUEVO grupo sin verificar ❌
3. **QuoteIndex@saveDraft()**: Podía crear borrador sin verificar existentes ❌

## Solución Implementada: Sistema de Reutilización Inteligente

### Estrategia Única en Todos los Puntos

**Todos los controladores/métodos ahora:**
1. Buscan borradores existentes (últimas 24 horas)
2. Reutilizan actualizando si existen
3. Solo crean NUEVO si no hay borrador reciente
4. Mantienen los datos importantes del borrador

### 1. QuoteCreationController (API inicial)

```php
// Buscar borrador existente (24 horas)
$existingDraft = GroupCotization::where('client_id', $request->client_id)
    ->where('user_id', $userId)
    ->where('status', 'borrador')
    ->where('created_at', '>=', now()->subDay())
    ->orderBy('created_at', 'desc')
    ->first();

if ($existingDraft) {
    // REUTILIZAR actualizando
    $existingDraft->update([
        'type' => $request->type_business,
        'operation_type' => $request->operation_type,
        'candado_satelital' => $request->candado_satelital ?? false,
        'cargo_type' => $request->cargo_type,
        // ... todos los parámetros
    ]);
    $group = $existingDraft;
    Log::info('✅ Grupo BORRADOR actualizado (reutilizado)');
} else {
    // Solo crear si NO existe
    $group = GroupCotization::create([...]);
    Log::info('✅ Grupo BORRADOR creado (nuevo)');
}

session()->put('current_group_id', $group->id);
```

**Archivo**: `app/Http/Controllers/Api/QuoteCreationController.php`  
**Ruta**: `POST /api/chat/quote/create-group`

### 2. QuoteSaveController (Guardar desde chat)

```php
// Buscar borrador existente (24 horas)
$group = GroupCotization::where('client_id', $request->client_id)
    ->where('user_id', $userId)
    ->where('status', 'borrador')
    ->where('created_at', '>=', now()->subDay())
    ->orderBy('created_at', 'desc')
    ->first();

if ($group) {
    // REUTILIZAR manteniendo datos importantes
    $updateData = [
        'status' => 'Pre-Solicitud',
        'openai_thread_id' => $request->thread_id,
        'created_from_chat' => true
    ];
    
    // Solo actualizar si no existen
    if (!$group->type) {
        $updateData['type'] = $request->type_business;
    }
    if (!$group->operation_type) {
        $updateData['operation_type'] = $request->operation_type;
    }
    
    $group->update($updateData);
    
    Log::info('✅ Grupo borrador REUTILIZADO y actualizado a Pre-Solicitud', [
        'mantiene_datos' => [
            'operation_type' => $group->operation_type,
            'candado_satelital' => $group->candado_satelital,
            'jen_set' => $group->jen_set,
        ]
    ]);
} else {
    // Solo crear si NO existe borrador reciente
    $group = GroupCotization::create([...]);
    Log::info('✅ Grupo NUEVO creado (no había borrador previo)');
}
```

**Archivo**: `app/Http/Controllers/Api/QuoteSaveController.php`  
**Ruta**: `POST /api/chat/save-quote-from-chat`  
**Clave**: Mantiene `operation_type`, `candado_satelital`, `jen_set`, etc. del borrador

### 3. QuoteIndex@storeST() (Livewire - Guardar cotización)

```php
// Prioridad 1: Sesión
if (!$this->group_cotization_id && session()->has('current_group_id')) {
    $this->group_cotization_id = session('current_group_id');
    Log::info('✅ Group ID recuperado de sesión');
}

// Prioridad 2: Buscar en DB (24 horas)
if (!$this->group_cotization_id && $this->client_id) {
    $lastDraft = GroupCotization::where('client_id', $this->client_id)
        ->where('user_id', auth()->id())
        ->where('status', 'borrador')
        ->where('created_at', '>=', now()->subDay())
        ->orderBy('created_at', 'desc')
        ->first();
    
    if ($lastDraft) {
        $this->group_cotization_id = $lastDraft->id;
        Log::info('✅ Grupo borrador encontrado en DB');
    }
}

// Actualizar o crear
if ($this->group_cotization_id) {
    $group = GroupCotization::find($this->group_cotization_id);
    if ($group) {
        $group->update([
            'status' => 'pendiente',
            'openai_thread_id' => $this->openai_thread,
            'created_from_chat' => 1,
        ]);
        Log::info('✅ Grupo ACTUALIZADO de borrador a pendiente');
        session()->forget('current_group_id');
    }
} else {
    // Flujo legacy: crear nuevo solo si no existe
    $group = GroupCotization::create([...]);
    Log::info('⚠️ Creando grupo sin borrador previo (flujo legacy)');
}
```

**Archivo**: `app/Livewire/QuoteIndex.php`  
**Método**: `storeST()` (líneas 477-570)

### 4. QuoteIndex@saveDraft() (Guardar borrador)

```php
// Prioridad 1: Sesión
if (!$this->group_cotization_id && session()->has('current_group_id')) {
    $this->group_cotization_id = session('current_group_id');
    Log::info('✅ Group ID recuperado de sesión en saveDraft');
}

// Prioridad 2: Buscar borrador existente (24 horas)
if (!$this->group_cotization_id && $this->client_id) {
    $lastDraft = GroupCotization::where('client_id', $this->client_id)
        ->where('user_id', auth()->id())
        ->where('status', 'borrador')
        ->where('created_at', '>=', now()->subDay())
        ->orderBy('created_at', 'desc')
        ->first();
    
    if ($lastDraft) {
        $this->group_cotization_id = $lastDraft->id;
        Log::info('✅ Borrador existente encontrado en DB');
    }
}

// Actualizar o crear
if ($this->group_cotization_id) {
    $group = GroupCotization::find($this->group_cotization_id);
    if ($group) {
        $group->update(['status' => 'borrador', ...]);
        Log::info('✅ Borrador ACTUALIZADO');
    }
} else {
    $group = GroupCotization::create([...]);
    Log::info('✅ Borrador CREADO desde cero');
}
```

**Archivo**: `app/Livewire/QuoteIndex.php`  
**Método**: `saveDraft()` (líneas 2166-2240)

## Flujo Completo Corregido

```
1. Usuario crea cotización en React
   └─> React → POST /api/chat/quote/create-group
       ├─> Busca borrador existente (24h)
       ├─> Si existe: ACTUALIZA con nuevos parámetros
       ├─> Si no existe: CREA nuevo borrador
       └─> Guarda group_id en session('current_group_id')

2. Usuario interactúa con chat, obtiene rutas y precios
   └─> React muestra preview de cotización

3. Usuario confirma y guarda cotización
   └─> React → POST /api/chat/save-quote-from-chat
       ├─> Busca borrador existente (24h)
       ├─> Si existe: REUTILIZA actualizando solo status→'Pre-Solicitud'
       │   └─> MANTIENE: operation_type, candado_satelital, jen_set, etc.
       ├─> Si no existe: CREA nuevo (edge case)
       └─> Crea cotizaciones individuales (rutas)

4. (Alternativo) Usuario guarda desde Livewire
   └─> Livewire → storeST() o saveDraft()
       ├─> Lee session('current_group_id') o busca en DB
       ├─> ACTUALIZA borrador existente
       └─> NO crea duplicado

RESULTADO: 1 SOLO registro con TODOS los datos ✅
```

## Ventajas de esta Solución

1. **Coordinación total**: Los 4 puntos de creación usan la misma lógica
2. **Sin pérdida de datos**: Mantiene `operation_type`, `candado_satelital`, `jen_set`, etc.
3. **Ventana de 24 horas**: Reutiliza borradores recientes, no antiguos
4. **Fallback seguro**: Si no hay borrador, crea uno nuevo (sin romper el flujo)
5. **Trazabilidad**: Logs claros en cada paso

## Logs para Monitoreo

### Creación/Reutilización
```
✅ Grupo BORRADOR actualizado (reutilizado) - QuoteCreationController
✅ Grupo BORRADOR creado (nuevo) - QuoteCreationController
✅ Grupo borrador REUTILIZADO y actualizado a Pre-Solicitud - QuoteSaveController
✅ Grupo NUEVO creado (no había borrador previo) - QuoteSaveController
✅ Group ID recuperado de sesión - QuoteIndex
✅ Grupo borrador encontrado en DB - QuoteIndex
✅ Grupo ACTUALIZADO de borrador a pendiente - QuoteIndex@storeST
✅ Borrador ACTUALIZADO - QuoteIndex@saveDraft
```

### Advertencias (Edge cases)
```
⚠️ Creando grupo sin borrador previo (flujo legacy) - QuoteIndex@storeST
⚠️ Group ID inválido, limpiando - QuoteIndex
⚠️ No se encontró grupo borrador reciente - QuoteIndex
```

## Script de Limpieza

Se creó script para limpiar duplicados históricos:

```bash
bash /home/ubuntu/conalca/conalca/limpiar_borradores_duplicados.sh
```

**Última ejecución**: 2025-11-18
- **Duplicados eliminados**: 38 registros
- **Cliente 615**: 14 duplicados removidos, mantuvo ID 130 (más reciente)
- **Total clientes afectados**: 10

## Testing

### Caso 1: Flujo Normal (React → Chat → Guardar)
1. Usuario crea cotización en React → Crea/actualiza borrador
2. Usuario obtiene rutas en chat
3. Usuario guarda desde preview → Reutiliza borrador, actualiza a Pre-Solicitud
4. **Resultado**: 1 registro con TODOS los datos ✅

### Caso 2: Múltiples Intentos
1. Usuario crea cotización → Borrador ID 130
2. Usuario cierra y vuelve a crear (mismo cliente) → Reutiliza ID 130
3. Usuario modifica parámetros → Actualiza ID 130
4. Usuario guarda → ID 130 pasa a Pre-Solicitud
5. **Resultado**: 1 solo registro actualizado múltiples veces ✅

### Caso 3: Múltiples Clientes
1. Usuario crea cotización cliente A → Borrador A
2. Usuario crea cotización cliente B → Borrador B (no reutiliza A)
3. **Resultado**: 1 borrador por cliente ✅

### Caso 4: Borradores Antiguos
1. Existe borrador de hace 2 días → No se reutiliza (>24h)
2. Usuario crea cotización → Crea nuevo borrador
3. **Resultado**: Borradores antiguos se ignoran ✅

## Verificación Post-Deploy

```bash
# Ver registros recientes
mysql -u root conalca_production -e "
SELECT id, user_id, client_id, type, operation_type, 
       candado_satelital, jen_set, status, created_at 
FROM group_cotizations 
WHERE created_at >= NOW() - INTERVAL 24 HOUR
ORDER BY id DESC LIMIT 10;
"

# Ver logs en tiempo real
tail -f /home/ubuntu/conalca/conalca/storage/logs/laravel.log | grep -E "BORRADOR|REUTILIZADO|Grupo"

# Contar duplicados por cliente
mysql -u root conalca_production -e "
SELECT client_id, status, COUNT(*) as count
FROM group_cotizations
WHERE status IN ('borrador', 'Pre-Solicitud')
GROUP BY client_id, status
HAVING count > 1;
"
```

## Estado del Sistema

- ✅ **QuoteCreationController**: Reutiliza borradores existentes
- ✅ **QuoteSaveController**: Reutiliza y mantiene datos importantes
- ✅ **QuoteIndex@storeST()**: Busca en sesión y DB antes de crear
- ✅ **QuoteIndex@saveDraft()**: Busca en sesión y DB antes de crear
- ✅ **Script de limpieza**: Elimina duplicados históricos
- ✅ **Assets compilados**: npm run build
- ✅ **Caché actualizado**: config:cache, route:cache
- ✅ **PHP-FPM recargado**: kill -USR2

## Próximos Pasos

1. **Monitorear logs** durante 24-48 horas
2. **Verificar DB** que no se creen nuevos duplicados
3. **Configurar cron** para limpieza periódica (semanal):
   ```bash
   0 2 * * 0 /home/ubuntu/conalca/conalca/limpiar_borradores_duplicados.sh >> /var/log/cleanup_drafts.log 2>&1
   ```
4. **Alertas**: Configurar alerta si se detectan duplicados

## Resumen Ejecutivo

### Antes
- 3 puntos creaban grupos sin coordinación
- Duplicados: 1 con datos + 1-2 vacíos por cotización
- Cliente 615 acumuló 14 borradores duplicados

### Ahora
- 4 puntos coordinados con búsqueda previa
- Reutilización inteligente (ventana 24h)
- Mantención de datos importantes
- 38 duplicados históricos eliminados
- 1 registro único por cotización ✅

---

**Implementado**: 2025-11-18  
**Versión**: FINAL  
**Estado**: ✅ COMPLETADO - Producción actualizada  
**Archivos modificados**:
- `app/Http/Controllers/Api/QuoteCreationController.php`
- `app/Http/Controllers/Api/QuoteSaveController.php`
- `app/Livewire/QuoteIndex.php` (storeST y saveDraft)
- `limpiar_borradores_duplicados.sh` (nuevo)
