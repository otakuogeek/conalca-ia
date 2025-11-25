# Configuración ElevenLabs SIP Trunk para Llamadas Salientes

## Descripción General

Este sistema integra ElevenLabs ConvAI con SIP Trunk para realizar llamadas automáticas a conductores después de registrar las cotizaciones en el sistema.

## Configuración Requerida

### 1. Variables de Entorno (.env)

```properties
# ElevenLabs API Key (ya configurada)
ELEVENLABS_API_KEY=sk_093a82577b2032a374a260265d098c38d427980b1729a9eb

# Configuración para SIP Trunk (REQUERIDO CONFIGURAR)
ELEVENLABS_AGENT_ID=your_agent_id_here
ELEVENLABS_AGENT_PHONE_NUMBER_ID=your_phone_number_id_here
```

### 2. Configuración en ElevenLabs Dashboard

Debes completar la configuración en tu cuenta de ElevenLabs:

1. **Crear un Agente ConvAI:**
   - Ve a https://elevenlabs.io/app/convai
   - Crea un nuevo agente conversacional
   - Configura la voz Andrea (ID: `qHkrJuifPpn95wK3rm2A`)
   - Copia el `agent_id` y úsalo en `ELEVENLABS_AGENT_ID`

2. **Configurar Número de Teléfono:**
   - Ve a la sección de números de teléfono en ElevenLabs
   - Configura un número para llamadas salientes
   - Copia el `phone_number_id` y úsalo en `ELEVENLABS_AGENT_PHONE_NUMBER_ID`

## Flujo de Funcionamiento

### 1. Registro de Llamadas
- Usuario hace clic en "Registrar Llamadas" en el modal de cotización
- Sistema registra las llamadas en la tabla `llamadas` con status `pendiente`
- Se muestra mensaje de éxito

### 2. Inicio de Llamadas Reales
- Después del mensaje de éxito, se pregunta si desea iniciar llamadas
- Si acepta, se utiliza el servicio `ElevenLabsCallService`
- Para cada llamada pendiente:
  - Se busca el conductor en `vehicle_owner_holder_drivers`
  - Se extrae el teléfono del campo `Telefonoconductor`
  - Se formatea para Colombia (+57)
  - Se realiza llamada vía API de ElevenLabs

### 3. Estados de Llamadas
- `pendiente`: Llamada registrada pero no iniciada
- `en_curso`: Llamada iniciada con ElevenLabs
- `finalizada`: Llamada completada
- `aceptada`: Conductor aceptó la cotización
- `rechazada`: Conductor rechazó la cotización

## Estructura de Base de Datos

### Tabla `llamadas` - Nuevos Campos
```sql
elevenlabs_conversation_id VARCHAR(255) NULL
elevenlabs_sip_call_id VARCHAR(255) NULL
call_started_at TIMESTAMP NULL
call_ended_at TIMESTAMP NULL
call_notes TEXT NULL
```

## API Endpoints

### POST /api/start-elevenlabs-calls/{cotizacionId}
Inicia llamadas reales para una cotización específica.

**Respuesta exitosa:**
```json
{
  "success": true,
  "message": "Llamadas iniciadas exitosamente",
  "total_calls": 2,
  "successful_calls": 2,
  "failed_calls": 0,
  "calls_initiated": 2,
  "details": {
    "successful": [...],
    "failed": [...]
  }
}
```

## Validaciones

### 1. Cotizaciones Rechazadas
- No se registran ni inician llamadas para cotizaciones con `decision_cliente = 'rechazada'`

### 2. Números de Teléfono
- Se valida que el número tenga al menos 7 dígitos
- Se formatea automáticamente para Colombia (+57)
- Se usa el primer número si hay múltiples separados por " - "

### 3. Conductores
- Se verifica que el conductor exista en la base de datos
- Se requiere que tenga un número de teléfono válido

## Logs y Monitoreo

### Logs Importantes
```bash
# Ver logs de llamadas ElevenLabs
tail -f storage/logs/laravel.log | grep -E "(ElevenLabs|llamada|conversation_id)"

# Ver estados de llamadas
grep -E "Llamada.*exitosa|Error.*llamada" storage/logs/laravel.log
```

### Información de Debug
- Todos los pasos se registran en `storage/logs/laravel.log`
- Se incluyen IDs de conversación y SIP call para seguimiento
- Errores detallados con stack traces

## Configuración de Producción

### 1. Verificar Credenciales
```bash
php artisan tinker
> config('services.elevenlabs.api_key')
> env('ELEVENLABS_AGENT_ID')
> env('ELEVENLABS_AGENT_PHONE_NUMBER_ID')
```

### 2. Probar Servicio
```bash
# Probar con una cotización específica
curl -X POST https://my-kontrol.online/api/start-elevenlabs-calls/34 \
  -H "Content-Type: application/json"
```

## Troubleshooting

### Error: "Agent ID not configured"
- Verificar que `ELEVENLABS_AGENT_ID` esté configurado en .env
- Reiniciar servidor después de cambios en .env

### Error: "Phone number invalid"
- Verificar que el conductor tenga teléfono en `Telefonoconductor`
- Verificar formato de número (mínimo 7 dígitos)

### Error: "ElevenLabs API error"
- Verificar que la API key sea válida
- Verificar que el agent_id exista en ElevenLabs
- Verificar créditos disponibles en la cuenta

## Próximos Pasos

1. **Configurar variables de entorno** con IDs reales de ElevenLabs
2. **Probar con cotización de prueba** para validar funcionamiento
3. **Configurar webhooks** para recibir actualizaciones de estado de llamadas
4. **Implementar panel de monitoreo** para supervisar llamadas en tiempo real

## Soporte

Para problemas con la integración, revisar:
1. Logs de Laravel (`storage/logs/laravel.log`)
2. Configuración de ElevenLabs Dashboard
3. Estado de créditos en cuenta ElevenLabs
4. Conectividad de red para APIs externas