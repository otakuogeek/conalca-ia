╔════════════════════════════════════════════════════════════════════════════╗
║           GUÍA: IMPORTAR Y PROBAR EN POSTMAN                               ║
║                 Crear Cotizaciones con Fecha_Cargue                        ║
╚════════════════════════════════════════════════════════════════════════════╝

═══════════════════════════════════════════════════════════════════════════════
PASO 1: DESCARGAR E IMPORTAR COLECCIÓN EN POSTMAN
═══════════════════════════════════════════════════════════════════════════════

1. Ubica el archivo:
   📁 /conalca/POSTMAN_SEED_COTIZACIONES.json

2. En Postman, haz clic en:
   File → Import

3. Selecciona el archivo o copia el contenido:
   - Opción A: Drag & drop el archivo
   - Opción B: Click en "Select Files" y busca el archivo

4. Verás una ventana con 5 requests:
   ✅ 1. Crear Cotización SIMPLE
   ✅ 2. Crear Cotización COMPLETA
   ✅ 3. Crear 4 Cotizaciones (Lote)
   ✅ 4. Validar Cotización por ID
   ✅ 5. Listar Todas

═══════════════════════════════════════════════════════════════════════════════
PASO 2: CONFIGURAR EL AMBIENTE
═══════════════════════════════════════════════════════════════════════════════

Opción A: Desarrollo Local (http://localhost:8000)
─────────────────────────────────────────────────
Si tienes el servidor corriendo en local:
  • URL: http://localhost:8000/api/test/seed-cotizacion
  • Headers: Content-Type: application/json
  • ✅ Listo para usar

Opción B: Producción (https://conalcaia.conalca.com.co)
────────────────────────────────────────────────────
Cambiar en cada request la URL:
  De: http://localhost:8000/api/test/seed-cotizacion
  A:  https://conalcaia.conalca.com.co/api/test/seed-cotizacion

═══════════════════════════════════════════════════════════════════════════════
PASO 3: PROBAR EL ENDPOINT SIMPLE
═══════════════════════════════════════════════════════════════════════════════

1. Haz clic en "1. Crear Cotización SIMPLE (Mínimo)"

2. Verás el Body (JSON):
   {
     "user_id": 13,
     "client_name": "CONALCA SAS",
     "reference": "TEST-SIMPLE-{{$timestamp}}",
     "operation_type": "DISTRIBUCION",
     "rutas": [
       {
         "ciudad_origen": "BOGOTA",
         "ciudad_destino": "MEDELLIN",
         "valor": 3980000,
         "flete": 3200000,
         "fecha_cargue": "2026-04-29 14:30"  ← IMPORTANTE
       }
     ]
   }

3. Haz clic en SEND (botón azul)

4. Espera la respuesta (200 o 201 = SUCCESS)

5. Verás en la respuesta:
   {
     "success": true,
     "data": {
       "group_id": 1924,
       "cotizaciones": [
         {
           "id": 2572,
           "fecha_cargue": "2026-04-29 14:30"  ← CONFIRMADO
         }
       ]
     }
   }

═══════════════════════════════════════════════════════════════════════════════
PASO 4: PROBAR CON COTIZACIÓN COMPLETA
═══════════════════════════════════════════════════════════════════════════════

Haz clic en "2. Crear Cotización COMPLETA (Recomendado)"

Este template incluye:
  ✅ Todos los campos de validación
  ✅ Información completa de la carga
  ✅ fecha_cargue con formato correcto
  ✅ Decision automática a "aceptada"
  ✅ Status "En tránsito"

SEND y verifica que llegue con fecha_cargue

═══════════════════════════════════════════════════════════════════════════════
PASO 5: ENVIAR MÚLTIPLES COTIZACIONES (LOTE)
═══════════════════════════════════════════════════════════════════════════════

Haz clic en "3. Crear 4 Cotizaciones Múltiples (Lote)"

Este request crea 4 cotizaciones diferentes en UN SOLO ENVÍO:
  1. BOGOTA → MEDELLIN (14:30)
  2. CARTAGENA → BOGOTA (09:15)
  3. CALI → BUCARAMANGA (16:45)
  4. BARRANQUILLA → SANTA MARTA (10:00)

Cada una con fecha_cargue diferente.

SEND y verifica todos los IDs en la respuesta

═══════════════════════════════════════════════════════════════════════════════
PASO 6: VALIDAR UNA COTIZACIÓN ESPECÍFICA
═══════════════════════════════════════════════════════════════════════════════

Haz clic en "4. Validar Cotización por ID"

Edita la URL cambiando el ID:
  De: http://localhost:8000/api/cotizaciones/2571
  A:  http://localhost:8000/api/cotizaciones/2572  (o el ID que quieras)

SEND y verifica que aparezca fecha_cargue en la respuesta

═══════════════════════════════════════════════════════════════════════════════
PASO 7: LISTAR TODAS LAS COTIZACIONES DE PRUEBA
═══════════════════════════════════════════════════════════════════════════════

Haz clic en "5. Listar Todas las Cotizaciones de Prueba"

SEND y verás todas las cotizaciones creadas con este endpoint

═══════════════════════════════════════════════════════════════════════════════
CÓMO PERSONALIZAR LOS REQUESTS
═══════════════════════════════════════════════════════════════════════════════

En el Body, puedes cambiar valores:

1. CAMBIAR CLIENTE:
   "client_name": "CONALCA SAS"  →  "client_name": "365 OPERADOR"

2. CAMBIAR RUTAS:
   "ciudad_origen": "BOGOTA"  →  "ciudad_origen": "CALI"
   "ciudad_destino": "MEDELLIN"  →  "ciudad_destino": "BARRANQUILLA"

3. CAMBIAR FECHA_CARGUE:
   "fecha_cargue": "2026-04-29 14:30"  →  "fecha_cargue": "2026-04-30 10:00"
   
   FORMATO OBLIGATORIO: "YYYY-MM-DD HH:MM" (24 horas)
   ✅ "2026-04-29 14:30"
   ❌ "14:30"
   ❌ "2026-04-29"

4. CAMBIAR VALORES:
   "valor": 3980000  →  "valor": 5000000
   "flete": 3200000  →  "flete": 4000000

═══════════════════════════════════════════════════════════════════════════════
RESPUESTA EXITOSA (HTTP 201)
═══════════════════════════════════════════════════════════════════════════════

{
  "success": true,
  "message": "Grupo de cotización #1924 creado exitosamente en estado 'En tránsito'",
  "data": {
    "group_id": 1924,
    "status": "En tránsito",
    "reference": "TEST-SIMPLE-1714398947393",
    "client": {
      "id": 613,
      "nombre": "COMPAÑÍA NACIONAL DE CABLES SAS",
      "documento": 860002775
    },
    "cotizaciones": [
      {
        "id": 2572,
        "group_cotization_id": 1924,
        "ciudad_origen": "BOGOTA",
        "ciudad_destino": "MEDELLIN",
        "valor": "3980000",
        "flete": "3200000",
        "vehiculo_requerido": "TRACTOCAMION",
        "tipo_mercancia": "Carga general",
        "peso_mercancia": null,
        "fecha_cargue": "2026-04-29 14:30",  ← CONFIRMADO
        "decision_cliente": "aceptada"
      }
    ],
    "valor_total": 3980000
  }
}

═══════════════════════════════════════════════════════════════════════════════
RESPUESTA CON ERROR (HTTP 422)
═══════════════════════════════════════════════════════════════════════════════

Ejemplo 1: Formato de fecha incorrecto
──────────────────────────────────────
{
  "success": false,
  "message": "Validación fallida",
  "errors": {
    "rutas.0.fecha_cargue": [
      "The rutas.0.fecha_cargue field must be a date matching the format Y-m-d H:i."
    ]
  }
}

SOLUCIÓN: Cambiar "2026-04-29" a "2026-04-29 14:30"

Ejemplo 2: Falta campo obligatorio (valor)
────────────────────────────────────────
{
  "success": false,
  "message": "Validación fallida",
  "errors": {
    "rutas.0.valor": ["The rutas.0.valor field is required."]
  }
}

SOLUCIÓN: Agregar "valor": 3980000 en cada ruta

═══════════════════════════════════════════════════════════════════════════════
SCRIPT PHP PARA VALIDAR DESPUÉS
═══════════════════════════════════════════════════════════════════════════════

Después de enviar desde Postman, ejecuta en terminal:

php /conalca/validar_cotizaciones_1927_1930.php

(Cambiar los IDs 1927-1930 por los IDs que creaste)

Esto mostrará:
  ✅ Si la fecha_cargue se guardó correctamente
  ✅ Ruta, valor, flete
  ✅ Decision_cliente
  ✅ Estado

═══════════════════════════════════════════════════════════════════════════════
CHECKLIST: ANTES DE ENVIAR
═══════════════════════════════════════════════════════════════════════════════

Antes de hacer SEND, verifica:

☐ URL correcta (localhost o producción)
☐ Headers: Content-Type = application/json
☐ Method = POST (para crear)
☐ user_id existe (13 o 11)
☐ client_name está en comillas
☐ Ciudad_origen tiene valor
☐ Ciudad_destino tiene valor
☐ Valor > 0
☐ Flete > 0
☐ fecha_cargue = "YYYY-MM-DD HH:MM"
☐ NO hay comas extra al final de objetos

═══════════════════════════════════════════════════════════════════════════════
ARCHIVOS GENERADOS
═══════════════════════════════════════════════════════════════════════════════

Archivos de validación y prueba:
  📄 POSTMAN_SEED_COTIZACIONES.json
     → Colección completa para importar en Postman

  📄 validar_cotizaciones_1927_1930.php
     → Script PHP para validar cotizaciones por ID
     → Uso: php validar_cotizaciones_1927_1930.php

  📄 crear_test_cotizaciones.php
     → Script PHP que crea 4 cotizaciones con API

  📄 crear_cotizaciones_con_fecha.php
     → Script PHP que crea directamente en BD

  📄 GUIA_FECHA_CARGUE_API.md
     → Guía completa con ejemplos Python/JS/CURL

═══════════════════════════════════════════════════════════════════════════════
TIPS FINALES
═══════════════════════════════════════════════════════════════════════════════

1. Postman tiene variable {{$timestamp}} que cambia en cada envío
   → Reference siempre será único

2. Si necesitas una fecha específica:
   Edita manualmente: "fecha_cargue": "2026-04-30 10:00"

3. Para ver histórico de requests:
   Postman → History → Click en request anterior

4. Para guardar variables de respuesta:
   Postman → Tests tab → pm.globals.set("cotizacion_id", ...)

5. Desactiva verificación SSL si hay problemas (NO recomendado en prod):
   Postman → Preferences → General → SSL certificate verification = OFF

═══════════════════════════════════════════════════════════════════════════════
