╔════════════════════════════════════════════════════════════════════════════╗
║                 GUÍA: USAR FECHA_CARGUE EN API SEED                      ║
║                                                                            ║
║  Problema: El API no está tomando/mostrando la fecha_cargue cuando se     ║
║            crea una cotización. Solución: formato y mapeo de campos.      ║
╚════════════════════════════════════════════════════════════════════════════╝

═══════════════════════════════════════════════════════════════════════════════
1. CAMPO Y MAPEO
═══════════════════════════════════════════════════════════════════════════════

En el JSON que envías al API:
├─ Campo nombre:    fecha_cargue
├─ Ubicación:       rutas[*].fecha_cargue
├─ Tipo:            string
├─ Formato:         "YYYY-MM-DD HH:MM"
├─ Ejemplo:         "2026-04-29 14:30"
└─ Obligatorio:     NO

En la BD (tabla cotizacion_models):
├─ Campo nombre:    fecha_hora_descargue_cargue
├─ Tipo:            DATETIME
├─ Descripción:     Almacena la fecha y hora de cargue
└─ Se guarda como:  fecha_cargue → fecha_hora_descargue_cargue

═══════════════════════════════════════════════════════════════════════════════
2. JSON CORRECTO (EJEMPLO MÍNIMO)
═══════════════════════════════════════════════════════════════════════════════

{
  "user_id": 13,
  "client_name": "CONALCA SAS",
  "reference": "TEST-FECHA-001-" + new Date().getTime(),
  "operation_type": "DISTRIBUCION",
  "rutas": [
    {
      "ciudad_origen": "BOGOTA",
      "ciudad_destino": "MEDELLIN",
      "valor": 3980000,
      "flete": 3200000,
      "tipo_mercancia": "TEXTILES",
      "peso_mercancia": "15000",
      "cantidad": "20",
      "vehiculo_requerido": "TRACTOCAMION",
      "fecha_cargue": "2026-04-29 14:30"  ← FORMATO CORRECTO
    }
  ]
}

═══════════════════════════════════════════════════════════════════════════════
3. JSON COMPLETO (RECOMENDADO)
═══════════════════════════════════════════════════════════════════════════════

{
  "user_id": 13,
  "client_name": "CONALCA SAS",
  "reference": "TEST-FECHA-COMPLETO-" + new Date().getTime(),
  "operation_type": "DISTRIBUCION",
  "cargo_type": "general",
  "rutas": [
    {
      "solicitud": "0312636578",
      "tipo_viaje": "NACIONAL",
      "origen": "DUITAMA",
      "destino": "BOGOTA",
      "cliente": "CONALCA SAS",
      "soltra_tipooperacion": "DISTRIBUCION",
      
      // ─── CAMPOS OBLIGATORIOS ───
      "ciudad_origen": "DUITAMA",
      "ciudad_destino": "BOGOTA",
      "valor": 2576000,
      "flete": 2300000,
      
      // ─── CAMPOS OPCIONALES (Recomendados) ───
      "peso_mercancia": "30000",
      "cantidad": "28",
      "cantidad_vh": "1",
      "tipo_carroceria": "CONTENEDOR",
      "vehiculo_requerido": "TRACTOMULA 3",
      "valor_declarado": "1000000",
      "tipo_mercancia": "REPUESTOS AUTOMOTRICES",
      "tipo_product": "REPUESTOS",
      "tipo_embajale": "CONTENEDOR",
      "seguro": "Si",
      
      // ─── FECHA_CARGUE (LO IMPORTANTE) ───
      "fecha_cargue": "2026-04-29 11:00"  ← FORMATO: YYYY-MM-DD HH:MM
    }
  ]
}

═══════════════════════════════════════════════════════════════════════════════
4. VALIDACIÓN DE FORMATO
═══════════════════════════════════════════════════════════════════════════════

El API valida: date_format:Y-m-d H:i

✅ VÁLIDOS:
   "2026-04-29 11:00"      ← Correcto
   "2026-04-29 14:30"      ← Correcto
   "2026-04-29 09:15"      ← Correcto
   "2026-04-30 23:59"      ← Correcto

❌ INVÁLIDOS (Causarán error 422):
   "2026-04-29"            ← Falta hora
   "11:00"                 ← Falta fecha
   "2026-04-29 11"         ← Faltan minutos
   "29/04/2026 11:00"      ← Formato de fecha incorrecto
   "2026-04-29T11:00"      ← No usar 'T'

═══════════════════════════════════════════════════════════════════════════════
5. CÓMO USAR EN PYTHON
═══════════════════════════════════════════════════════════════════════════════

from datetime import datetime

# Opción 1: Usar datetime actual más 1 hora
fecha_cargue = (datetime.now().replace(minute=0, second=0, microsecond=0) + 
                timedelta(hours=1)).strftime("%Y-%m-%d %H:%M")

# Opción 2: Fecha específica
fecha_cargue = "2026-04-29 14:30"

# Opción 3: Generar dinámicamente
fecha_cargue = datetime.now().strftime("%Y-%m-%d %H:%M")

payload = {
    'user_id': 144,
    'client_name': 'CONALCA SAS',
    'reference': f'TEST-SEED-{datetime.now().isoformat()}',
    'operation_type': 'DISTRIBUCION',
    'rutas': [
        {
            'ciudad_origen': 'DUITAMA',
            'ciudad_destino': 'BOGOTA',
            'valor': 2576000,
            'flete': 2300000,
            'peso_mercancia': '30000',
            'cantidad': '28',
            'cantidad_vh': '1',
            'tipo_carroceria': 'CONTENEDOR',
            'vehiculo_requerido': 'TRACTOMULA 3',
            'valor_declarado': '1000000',
            'fecha_cargue': fecha_cargue  # ← ¡Aquí va!
        }
    ]
}

response = requests.post(
    'https://conalcaia.conalca.com.co/api/test/seed-cotizacion',
    json=payload,
    headers={'Content-Type': 'application/json'},
    timeout=30
)

if response.status_code == 201:
    data = response.json()
    cotizacion_id = data['data']['cotizaciones'][0]['id']
    fecha_guardada = data['data']['cotizaciones'][0]['fecha_cargue']
    print(f"✅ Cotización {cotizacion_id} creada con fecha: {fecha_guardada}")

═══════════════════════════════════════════════════════════════════════════════
6. CÓMO USAR EN JAVASCRIPT
═══════════════════════════════════════════════════════════════════════════════

// Generar fecha con formato correcto
function formatearFechaCargue(date = new Date()) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  return `${year}-${month}-${day} ${hours}:${minutes}`;
}

const payload = {
  user_id: 144,
  client_name: 'CONALCA SAS',
  reference: `TEST-SEED-${new Date().getTime()}`,
  operation_type: 'DISTRIBUCION',
  rutas: [
    {
      ciudad_origen: 'DUITAMA',
      ciudad_destino: 'BOGOTA',
      valor: 2576000,
      flete: 2300000,
      peso_mercancia: '30000',
      cantidad: '28',
      cantidad_vh: '1',
      tipo_carroceria: 'CONTENEDOR',
      vehiculo_requerido: 'TRACTOMULA 3',
      valor_declarado: '1000000',
      fecha_cargue: formatearFechaCargue()  // ← Usa función auxiliar
    }
  ]
};

fetch('https://conalcaia.conalca.com.co/api/test/seed-cotizacion', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(payload)
})
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      console.log('✅ Cotización creada:', data.data.cotizaciones[0].id);
      console.log('📅 Fecha Cargue:', data.data.cotizaciones[0].fecha_cargue);
    }
  });

═══════════════════════════════════════════════════════════════════════════════
7. CURL DIRECTO (PARA PRUEBAS)
═══════════════════════════════════════════════════════════════════════════════

curl -X POST https://conalcaia.conalca.com.co/api/test/seed-cotizacion \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 13,
    "client_name": "CONALCA SAS",
    "reference": "TEST-FECHA-'$(date +%s)'",
    "operation_type": "DISTRIBUCION",
    "rutas": [
      {
        "ciudad_origen": "BOGOTA",
        "ciudad_destino": "MEDELLIN",
        "valor": 3980000,
        "flete": 3200000,
        "peso_mercancia": "15000",
        "cantidad": "20",
        "cantidad_vh": "1",
        "tipo_carroceria": "FURGON",
        "vehiculo_requerido": "TRACTOCAMION",
        "valor_declarado": "150000000",
        "tipo_mercancia": "TEXTILES",
        "fecha_cargue": "'$(date '+%Y-%m-%d %H:%M')'"
      }
    ]
  }'

═══════════════════════════════════════════════════════════════════════════════
8. VALIDACIÓN EN BASE DE DATOS
═══════════════════════════════════════════════════════════════════════════════

Después de crear una cotización, verificar que fecha_cargue se guardó:

SELECT 
    id,
    grupo_cotization_id,
    ciudad_origen,
    ciudad_destino,
    fecha_hora_descargue_cargue AS fecha_cargue,
    decision_cliente,
    created_at
FROM cotizacion_models
WHERE id = 2572;

Resultado esperado:
┌─────┬───────────────────┬──────────────┬────────────────┬────────────────────────┬──────────────────┬─────────────────────┐
│ id  │ group_cotization_id│ ciudad_origen│ ciudad_destino │ fecha_cargue           │ decision_cliente  │ created_at          │
├─────┼───────────────────┼──────────────┼────────────────┼────────────────────────┼──────────────────┼─────────────────────┤
│2572 │ 1924              │ BOGOTA       │ MEDELLIN       │ 2026-04-29 14:30:00    │ aceptada         │ 2026-04-29 11:32:00 │
└─────┴───────────────────┴──────────────┴────────────────┴────────────────────────┴──────────────────┴─────────────────────┘

═══════════════════════════════════════════════════════════════════════════════
9. COTIZACIONES DE PRUEBA CREADAS (PARA MCP)
═══════════════════════════════════════════════════════════════════════════════

Las siguientes cotizaciones ya están creadas con fecha_cargue:

  • ID 2571 | Grupo 1923 | DUITAMA → BOGOTA      | Fecha: 2026-04-29 11:00
  • ID 2572 | Grupo 1924 | BOGOTA → MEDELLIN    | Fecha: 2026-04-29 14:30
  • ID 2573 | Grupo 1925 | CARTAGENA → BOGOTA   | Fecha: 2026-04-29 09:15
  • ID 2574 | Grupo 1926 | CALI → BUCARAMANGA   | Fecha: 2026-04-29 16:45

Todas tienen:
✅ decision_cliente = 'aceptada'
✅ status = 'En tránsito'
✅ fecha_hora_descargue_cargue populada
✅ Listos para validación del MCP

═══════════════════════════════════════════════════════════════════════════════
10. ERRORES COMUNES Y SOLUCIONES
═══════════════════════════════════════════════════════════════════════════════

Error 422 - "fecha_cargue": ["The fecha_cargue field must be a date matching the format Y-m-d H:i."]

Causa: Formato incorrecto
Soluciones:
  ❌ "2026-04-29"         → ✅ "2026-04-29 14:30"
  ❌ "29-04-2026 14:30"   → ✅ "2026-04-29 14:30"
  ❌ "2026-04-29T14:30"   → ✅ "2026-04-29 14:30"
  ❌ "14:30"              → ✅ "2026-04-29 14:30"

Error: Fecha_cargue null/vacía en respuesta

Causa: No se envió el campo o no se guardó
Verificaciones:
  1. Revisar que esté en rutas[*].fecha_cargue (no en nivel de grupo)
  2. Verificar formato: "YYYY-MM-DD HH:MM"
  3. Revisar en BD: SELECT fecha_hora_descargue_cargue FROM cotizacion_models WHERE id = X;

═══════════════════════════════════════════════════════════════════════════════
11. RESUMEN RÁPIDO
═══════════════════════════════════════════════════════════════════════════════

┌─────────────────────────────────────────────────────────────────┐
│ PASO A PASO PARA CREAR COTIZACIÓN CON FECHA_CARGUE             │
└─────────────────────────────────────────────────────────────────┘

1. Armar JSON con estructura:
   {
     "user_id": 13,
     "client_name": "CONALCA SAS",
     "rutas": [
       {
         "ciudad_origen": "BOGOTA",
         "ciudad_destino": "MEDELLIN",
         "valor": 3980000,
         "flete": 3200000,
         "fecha_cargue": "2026-04-29 14:30"  ← OBLIGATORIO AQUÍ
       }
     ]
   }

2. Asegurar formato: "YYYY-MM-DD HH:MM" (24 horas)
   ✅ "2026-04-29 14:30"
   ❌ "14:30"
   ❌ "2026-04-29"

3. Enviar POST a: /api/test/seed-cotizacion

4. Verificar en respuesta:
   cotizaciones[0].fecha_cargue = "2026-04-29 14:30"

5. Validar en BD:
   SELECT fecha_hora_descargue_cargue FROM cotizacion_models WHERE id = X;

═══════════════════════════════════════════════════════════════════════════════

Para preguntas o issues: Revisar /api/test/seed-cotizacion en TestSeedController.php
