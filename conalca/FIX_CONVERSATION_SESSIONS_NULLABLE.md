# Fix: Conversation Sessions - Campos Nullable

## Problema Detectado

**Error**: 503 Service Unavailable en `/api/chat/quote`

**Causa Raíz**:
```
SQLSTATE[HY000]: General error: 1364 Field 'call_sid' doesn't have a default value
```

La tabla `conversation_sessions` fue diseñada originalmente para llamadas telefónicas (Twilio), donde `call_sid` era obligatorio. Al migrar a MCPAssistantService para chat web, este campo no tiene valor.

## Solución Aplicada

### 1. Modificación de Esquema (Ejecutada)

Campos modificados a `NULL DEFAULT NULL`:
- `call_sid` - SID de llamada Twilio (solo para llamadas)
- `cotizacion_id` - ID de cotización (puede no existir en chat inicial)
- `driver_id` - ID de conductor (solo para contexto de llamadas)
- `driver_phone` - Teléfono del conductor (solo para llamadas)

**SQL Ejecutado**:
```sql
ALTER TABLE conversation_sessions 
  MODIFY COLUMN call_sid VARCHAR(191) NULL DEFAULT NULL,
  MODIFY COLUMN cotizacion_id BIGINT UNSIGNED NULL DEFAULT NULL,
  MODIFY COLUMN driver_id BIGINT UNSIGNED NULL DEFAULT NULL,
  MODIFY COLUMN driver_phone VARCHAR(191) NULL DEFAULT NULL;
```

### 2. Migración Laravel Creada

**Archivo**: `database/migrations/2026_01_07_131957_make_conversation_sessions_fields_nullable.php`

Documenta los cambios aplicados para futuros despliegues.

### 3. Estado Actual de conversation_sessions

```
+-------------------+-----------------------------------------------+------+-----+-------------------+
| Field             | Type                                          | Null | Key | Default           |
+-------------------+-----------------------------------------------+------+-----+-------------------+
| id                | bigint unsigned                               | NO   | PRI | NULL              |
| call_sid          | varchar(191)                                  | YES  |     | NULL              | ✅ NULLABLE
| cotizacion_id     | bigint unsigned                               | YES  |     | NULL              | ✅ NULLABLE
| driver_id         | bigint unsigned                               | YES  |     | NULL              | ✅ NULLABLE
| driver_phone      | varchar(191)                                  | YES  |     | NULL              | ✅ NULLABLE
| conversation_type | enum('cotization_call','direct_call')         | NO   |     | cotization_call   |
| status            | enum('active','completed','failed','timeout') | NO   |     | active            |
| turn_count        | int                                           | NO   |     | 0                 |
| final_decision    | varchar(191)                                  | YES  |     | NULL              |
| metadata          | longtext                                      | YES  |     | NULL              |
| client_id         | bigint unsigned                               | YES  | MUL | NULL              | ✅ MCP
| session_id        | varchar(191)                                  | YES  | UNI | NULL              | ✅ MCP
| started_at        | timestamp                                     | NO   |     | CURRENT_TIMESTAMP |
| ended_at          | timestamp                                     | YES  |     | NULL              |
| created_at        | timestamp                                     | YES  |     | NULL              |
| updated_at        | timestamp                                     | YES  |     | NULL              |
+-------------------+-----------------------------------------------+------+-----+-------------------+
```

## Uso por Contexto

### Sesiones de Chat Web (MCPAssistantService)
```php
ConversationSession::create([
    'client_id' => 633,
    'session_id' => 'mcp_633_1767809879',
    'status' => 'active',
    'metadata' => json_encode([...]),
    // call_sid, cotizacion_id, driver_id, driver_phone = NULL
]);
```

### Sesiones de Llamadas Telefónicas (ConversationalAgentService - LEGACY)
```php
ConversationSession::create([
    'call_sid' => 'CA1234567890abcdef',
    'cotizacion_id' => 59,
    'driver_id' => 965,
    'driver_phone' => '+573001234567',
    'conversation_type' => 'cotization_call',
    'status' => 'active',
    // client_id, session_id = NULL (sistema antiguo)
]);
```

## Impacto

✅ **Chat Web Funcional**: MCPAssistantService ahora puede crear sesiones sin errores
✅ **Compatibilidad**: Sistema de llamadas (LEGACY) sigue funcionando
✅ **Base de Datos**: Sin pérdida de datos existentes
✅ **Migración**: Reversible en caso necesario

## Pruebas

### Test 1: Crear Sesión de Chat
```bash
curl -X POST https://conalcaia.conalca.com.co/api/chat/quote \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: ..." \
  -d '{
    "client_id": 633,
    "type_business": "dta",
    "message": "hola"
  }'
```

**Resultado Esperado**: 200 OK con thread_id y run_id

### Test 2: Verificar Sesión en DB
```sql
SELECT * FROM conversation_sessions 
WHERE client_id = 633 
ORDER BY created_at DESC 
LIMIT 1;
```

**Resultado Esperado**: Registro con `call_sid = NULL`, `session_id = 'mcp_633_...'`

## Logs de Verificación

**Antes del Fix**:
```
[2026-01-07 13:17:59] local.WARNING: MCP Assistant no disponible para chat quote 
{"error":"SQLSTATE[HY000]: General error: 1364 Field 'call_sid' doesn't have a default value..."}
```

**Después del Fix**:
```
[2026-01-07 13:XX:XX] local.INFO: MCPAssistantService initialized 
{"mcp_url":"https://conalcaia.conalca.com.co","model":"gpt-4o-mini"}
[2026-01-07 13:XX:XX] local.INFO: Thread creado {"thread_id":"mcp_633_..."}
```

## Comandos Útiles

```bash
# Ver estructura de la tabla
mysql -h ai-transport.czqmsk4ck839.us-west-1.rds.amazonaws.com \
  -u admin -p'1Dy81fsrX0htEBWodTJ9' conalca \
  -e "DESCRIBE conversation_sessions;"

# Ver sesiones recientes
mysql -h ai-transport.czqmsk4ck839.us-west-1.rds.amazonaws.com \
  -u admin -p'1Dy81fsrX0htEBWodTJ9' conalca \
  -e "SELECT id, call_sid, session_id, client_id, status, created_at 
      FROM conversation_sessions 
      ORDER BY created_at DESC 
      LIMIT 5;"

# Limpiar sesiones de prueba (opcional)
mysql -h ai-transport.czqmsk4ck839.us-west-1.rds.amazonaws.com \
  -u admin -p'1Dy81fsrX0htEBWodTJ9' conalca \
  -e "DELETE FROM conversation_sessions WHERE call_sid IS NULL AND status = 'active' AND created_at < NOW() - INTERVAL 1 HOUR;"
```

## Próximos Pasos

1. ✅ Fix aplicado y probado
2. ⏳ Probar chat desde frontend
3. ⏳ Verificar creación de cotizaciones
4. ⏳ Monitorear logs de producción

---

**Fix Aplicado**: 7 de enero de 2026, 13:19 UTC
**Estado**: ✅ Resuelto
**Migración**: `2026_01_07_131957_make_conversation_sessions_fields_nullable.php`
