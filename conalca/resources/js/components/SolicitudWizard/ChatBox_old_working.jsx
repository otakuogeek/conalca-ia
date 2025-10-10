import React, { useRef, useState, useEffect } from 'react';
import OpenAI from 'openai';
import EventEmitter from 'eventemitter3';

export const chatBus = new EventEmitter();

/* ---------- instancia OpenAI ---------- */
// IMPORTANTE: La clave API se lee desde variable de entorno
// Configurar en .env como: VITE_OPENAI_API_KEY=sk-proj-...
const openai = new OpenAI({
  apiKey: import.meta.env.VITE_OPENAI_API_KEY || '',
  dangerouslyAllowBrowser: true
});
/* ---------- Voz a texto (Web Speech API) ---------- */
const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
const isSpeechApi = !!SpeechRec;           // true en Chrome/Edge


/* ---------- Alias para nombres de campos ---------- */
const ALIAS = {
  'codigo del cliente' : 'cliente_codigo',
  'código del cliente' : 'cliente_codigo',
  'codigo_cliente'     : 'cliente_codigo',
  'cliente'            : 'cliente_codigo',

  'tipo de viaje'      : 'tipo_viaje',
  'viaje'              : 'tipo_viaje',

  'moneda'             : 'moneda',

  'fuente de la solicitud' : 'fuente_solicitud',
  'fuente solicitud'       : 'fuente_solicitud',

  'ciudad facturacion' : 'ciudad_facturacion',
  'ciudad facturación' : 'ciudad_facturacion',
  'facturacion'        : 'ciudad_facturacion',
  'facturación'        : 'ciudad_facturacion',

  'vendedor'           : 'vendedor',

  'tipo de operacion'  : 'tipo_operacion',
  'tipo de operación'  : 'tipo_operacion',
  'tipo operacion'     : 'tipo_operacion',

  'centro de costo'          : 'centro_costo_despacho',
  'centro de costo despacho' : 'centro_costo_despacho',

  // STEP 2
  // Paso 2 – Detalle  (los que todavía faltaban)
  'destino'                     : 'destino',
  'cantidad de mercancia'       : 'cantidad_mercancia',
  'peso'                        : 'peso',
  'flete conductor'             : 'flete_conductor',
  'flete ministerio'            : 'flete_ministerio',
  'tarifa cliente'              : 'tarifa_cliente',
  'valor de mercancia'          : 'valor_mercancia',
  'clase de vehículo'           : 'clase_vehiculo',
  'carrocería'                  : 'carroceria',
  'mínimo modelo'               : 'minimo_modelo',
  'producto_codigo' : 'producto',
  'codigo producto' : 'producto',
  'empaque_codigo'  : 'empaque',
  'codigo empaque'  : 'empaque',

  'tipo de flete'         : 'tipo_flete',
  'flete'                 : 'tipo_flete',

  'tipo de tarifa'        : 'tipo_tarifa',
  'tarifa'                : 'tipo_tarifa',

  'cargue por cuenta de'  : 'cargue_cuenta_de',
  'cargue cuenta de'      : 'cargue_cuenta_de',

  'descargue cuenta de'     : 'descargue_cuenta_de',

  'seguro por cuenta de'  : 'seguro_cuenta_de',
  'seguro cuenta de'      : 'seguro_cuenta_de',

  'kit seguridad'         : 'kit_seguridad',

  'tipo de remesa'        : 'tipo_remesa_rndc',
  'remesa rndc'           : 'tipo_remesa_rndc',

  'telefono despachador' : 'fuente_solicitud',
  'telefono atencion'    : 'fuente_solicitud',
  'distribucion'         : 'tipo_operacion',

  // nombres con espacios tal como los escribes en el prompt
  'cantidad de mercancía' : 'cantidad_mercancia',

  'valor de mercancía'    : 'valor_mercancia',

  'producto código'       : 'producto',
  'empaque código'        : 'empaque',

  'cantidad de vehículos' : 'cantidad_vehiculos',

  'descargue por cuenta de': 'descargue_cuenta_de',

  'kit de seguridad'      : 'kit_seguridad',
  'tipo de remesa rndc'   : 'tipo_remesa_rndc',
  'descripción de la mercancía' : 'descripcion_mercancia',

  // STEP 3
  'fecha de cargue'        : 'fecha_cargue',
  'hora de cargue'         : 'hora_cargue',
  'remitente'              : 'remitente',
  'destinatario'           : 'destinatario',
  'contacto'               : 'contacto',
  'promesa de servicio'    : 'promesa_servicio',
  'documento de transporte': 'documento_transporte',
  'observacion cargue'     : 'observacion_cargue',
  'observación cargue'     : 'observacion_cargue',
  // Paso 3 – Cargue
  'observación de cargue'   : 'observacion_cargue',   // tilde
  'observacion de cargue'   : 'observacion_cargue',   // sin tilde
  'hora cargue'              : 'hora_cargue',          // por si escriben sin “de”
  

  // Alias adicionales para contenedor y variantes
  'requiere_contenedor' : 'contenedor',
  'requiere contenedor' : 'contenedor',
  'usa contenedor'      : 'contenedor',
  'uso contenedor'      : 'contenedor',
  'contenedor'          : 'contenedor',
  'contentedor'         : 'contenedor',   // error común
  'contendor'           : 'contenedor',
  'sin contenedor'      : 'contenedor',

  // STEP 5
  'modalidad internacional' : 'modalidad_internacional',
  'modalidad de transporte internacional' : 'modalidad_internacional',
  'internacionalizada' : 'modalidad_internacional'
};

const normalizeKey = (raw) => {
  const base = (raw || '').toLowerCase().trim();
  const noAccent = base.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  return ALIAS[base] ?? ALIAS[noAccent] ?? raw;
};


// helpers arriba (opcional)
const stripQuotes = s => String(s ?? '').trim().replace(/^['"]+|['"]+$/g, '');

const parseSpanishInt = (s) => {
  const m = s.match(/\d+/);
  if (m) return String(Math.max(0, parseInt(m[0], 10)));
  const map = {
    'cero':0,'uno':1,'una':1,'dos':2,'tres':3,'cuatro':4,'cinco':5,'seis':6,'siete':7,'ocho':8,'nueve':9,'diez':10,
    'once':11,'doce':12,'trece':13,'catorce':14,'quince':15,'dieciseis':16,'dieciséis':16,'diecisiete':17,
    'dieciocho':18,'diecinueve':19,'veinte':20
  };
  const v = s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,' ');
  for (const [k,val] of Object.entries(map)) {
    if (v.includes(k)) return String(val);
  }
  return '';
};

const normalizeValue = (field, rawValue) => {
  // 1) limpia comillas y espacios
  const rawClean = stripQuotes(rawValue);
  const v = rawClean
    .toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .trim();

  switch (field) {
    case 'tipo_viaje': {
      if (/^nac/.test(v) || /nacional/.test(v)) return 'NACIONAL';
      if (/^urb/.test(v) || /(urbano|local|ciudad)/.test(v)) return 'URBANO';
      if (/^int/.test(v) || /(internacional|exterior|fuera)/.test(v)) return 'INTERNACIONAL';
      return rawClean.toUpperCase();
    }
    case 'moneda': {
      if (/^(cop|m.?n.?)$/.test(v) || /(peso|col|colombia)/.test(v) || v === '$') return 'PESOS';
      if (/^(usd|us\$|u\$s|dll?s?)$/.test(v) || /(dolar|dolares)/.test(v)) return 'DOLARES';
      return rawClean.toUpperCase();
    }
    case 'contenedor': {
      if (
        v === 'no' ||
        /(^|\s)no(\s|$)/.test(v) ||
        /(sin\s+contenedor|no\s+requiere|no\s+usa|no\s+utiliza|no\s+necesita)/.test(v)
      ) return 'NO';
      if (
        v === 'si' ||
        /(requiere|con\s+contenedor|usa|utiliza|necesita)/.test(v)
      ) return 'SI';
      if (/^si$/i.test(rawClean)) return 'SI';
      if (/^no$/i.test(rawClean)) return 'NO';
      return rawClean.toUpperCase();
    }
    case 'modalidad_internacional': {
      // Mapeo estricto a las 4 opciones
      if (/^otm$/.test(v) || /terrestre\smultimodal/.test(v)) return 'OTM';
      if (/^dta$/.test(v) || /transito\saduanero/.test(v))   return 'DTA';
      if (/^dtai$/.test(v))                                  return 'DTAI';
      if (/nacionalizad/.test(v))                            return 'NACIONALIZADA';
      // fallback: intenta upper-case exacto por si ya viene bien
      const up = rawClean.toUpperCase().replace(/\s+/g,'');
      if (['OTM','DTA','DTAI','NACIONALIZADA'].includes(up)) return up;
      return up; // igual la verás en el select como no-seleccionado si no coincide
    }
    case "vehiculo_acom": {
      const n = parseSpanishInt(rawClean);
      return n || "0";
    }
    case 'tipo_flete': {
      if (/cupo/.test(v))           return 'CUPO';
      if (/consolida/.test(v))      return 'CONSOLIDADO';
      if (/expres/.test(v))         return 'EXPRESO';
      if (/galon/.test(v))          return 'GALON';
      if (/van/.test(v))            return 'VAN';
      if (/contenedor/.test(v))     return 'CONTENEDOR';
      return 'CARGA SUELTA';        // default
    }
    case 'tipo_tarifa': {
      if (/peso/.test(v))  return 'PESO';
      if (/galon/.test(v)) return 'GALON';
      return 'GENERAL';
    }
    case 'cargue_cuenta_de':
    case 'descargue_cuenta_de': {
      if (/empresa/.test(v))       return 'EMPRESA';
      if (/destinat/.test(v))      return 'DESTINATARIO';
      return 'CLIENTE';
    }
    case 'seguro_cuenta_de': {
      return /empresa/.test(v) ? 'EMPRESA' : 'CLIENTE';
    }
    case 'kit_seguridad': {
      return /(no|sin)/.test(v) ? 'NO' : 'SI';
    }
    case 'tipo_remesa_rndc': {
      if (/vac(i|í)o/.test(v))   return 'CONTENEDOR VACIO';
      if (/cargad/.test(v))      return 'CONTENEDOR CARGADO';
      return 'REMESA GENERAL';
    }
    case 'fuente_solicitud': {
      if (/despachador/.test(v))          return 'TELEFONO DESPACHADOR';
      if (/atencion/.test(v))            return 'TELEFONO ATENCION CLIENTE';
      if (/mail|correo/.test(v))         return 'MAIL';
      if (/fax/.test(v))                 return 'FAX';
      if (/sia/.test(v))                 return 'SIA';
      if (/web|pagina/.test(v))          return 'PAGINA WEB';
      return rawClean.toUpperCase();
    }
    case 'tipo_operacion': {
      if (/import/.test(v))              return 'IMPORTACION';
      if (/export/.test(v))              return 'EXPORTACION';
      return 'DISTRIBUCION';
    }
    // --------------------- STEP 3 ---------------
    case 'fecha_cargue':
    case 'remitente':
    case 'destinatario':
    case 'documento_transporte':
    case 'contacto': {
      return rawClean;        // lo deja tal cual
    }
    case 'promesa_servicio': {
      // convierte "hoy", "mañana", "dd/mm/aaaa" → YYYY-MM-DD
      if (/hoy/.test(v))      return new Date().toISOString().slice(0,10);
      if (/mañana/.test(v)) {
        const d = new Date(); d.setDate(d.getDate()+1);
        return d.toISOString().slice(0,10);
      }
      // dd/mm/aaaa o dd-mm-aaaa
      const m = rawClean.match(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/);
      if (m) {
        const [ , d, mo, y ] = m;
        // Y si el año viene en 2 dígitos, lo expande a 4 (ej: 24 → 2024)
        const year = y.length === 2 ? (parseInt(y,10)<50?'20'+y:'19'+y) : y.padStart(4,'0');
        return `${year}-${mo.padStart(2,'0')}-${d.padStart(2,'0')}`;
      }
      return rawClean;
    }
    case 'hora_cargue': {
      // "16:00", "4 pm", "4pm"
      const h = rawClean.match(/(\d{1,2})(?::(\d{2}))?\s*(am|pm)?/i);
      if (!h) return rawClean;
      let hh = parseInt(h[1],10);
      const mm = h[2] ?? '00';
      if (/pm/i.test(h[3] || '') && hh < 12) hh += 12;
      if (/am/i.test(h[3] || '') && hh === 12) hh = 0;
      return `${String(hh).padStart(2,'0')}:${mm}`;
    }
    default:
      return rawClean;
  }
};

/* ---------- Función (tool) que el modelo puede llamar ---------- */
const FUNCTIONS = [{
  name: 'rellenar',
  description: 'Rellena un campo del wizard',
  parameters: {
    type: 'object',
    properties: {
      field: { type: 'string', description: 'Nombre del campo a rellenar' },
      value: { type: 'string', description: 'Valor a colocar en el campo' }
    },
    required: ['field', 'value']
  }
}];


/* ---------- Instrucciones reforzadas para el modelo ---------- */
// const SYSTEM_PROMPT = `
// Eres un asistente que ayuda a llenar un formulario de "Encabezado".
// Siempre que el usuario entregue datos de campos, debes llamar a la función "rellenar"
// una vez por cada campo detectado. Repite tantas llamadas como sean necesarias
// hasta completar todos los campos posibles en ese mensaje. No des una respuesta final
// hasta que no hayas intentado rellenar todos los campos posibles.

// Campos válidos y formatos esperados:

// tipo_viaje: uno de [NACIONAL, URBANO, INTERNACIONAL]
// moneda: uno de [PESOS, DOLARES]
// fuente_solicitud: texto libre (p.ej. "TELEFONO DESPACHADOR")
// condicion_despacho: texto libre
// condicion_facturacion: texto libre
// ciudad_facturacion: código DANE numérico como string (p.ej. "11001000")
// vendedor: código de vendedor como string/numérico (p.ej. "53165050")
// tipo_operacion: texto libre (p.ej. "DISTRIBUCION")
// centro_costo_despacho: texto libre (p.ej. "TRANSLIDHER BOGOTA")
// cliente_codigo: numérico/string (p.ej. "2551")
// contenedor: uno de [SI, NO] (indica si requiere contenedor)
// modalidad_internacional: uno de [OTM, DTA, DTAI, NACIONALIZADA]

// Instrucciones:

// Si el usuario usa sinónimos o lenguaje natural, convierte al nombre de campo correcto y al valor exacto de las opciones de select.
// Ejemplos:
// • "viaje nacional" -> tipo_viaje = "NACIONAL"
// • "en dólares" / "USD" -> moneda = "DOLARES"
// • "no se requiere el uso del contenedor", "sin contenedor" -> contenedor = "NO"
// • "sí requiere contenedor", "usa contenedor" -> contenedor = "SI"
// Cuando detectes varios campos en un mismo mensaje, llama a "rellenar" múltiples veces, una por cada campo.
// `;

// const SYSTEM_PROMPT = `
// Eres un asistente que ayuda a llenar un formulario de "Encabezado".
// Siempre que el usuario entregue datos de campos, debes llamar a la función "rellenar"
// una vez por cada campo detectado. Repite tantas llamadas como sean necesarias
// hasta completar todos los campos posibles en ese mensaje. No des una respuesta final
// hasta que no hayas intentado rellenar todos los campos posibles.

// Campos válidos y formatos esperados:

// tipo_viaje: uno de [NACIONAL, URBANO, INTERNACIONAL]
// moneda: uno de [PESOS, DOLARES]
// fuente_solicitud: texto libre (p.ej. "TELEFONO DESPACHADOR")
// condicion_despacho: texto libre
// condicion_facturacion: texto libre
// ciudad_facturacion: código DANE numérico como string (p.ej. "11001000")
// vendedor: código de vendedor como string/numérico (p.ej. "53165050")
// tipo_operacion: texto libre (p.ej. "DISTRIBUCION")
// centro_costo_despacho: texto libre (p.ej. "TRANSLIDHER BOGOTA")
// cliente_codigo: numérico/string (p.ej. "2551")
// contenedor: uno de [SI, NO] (indica si requiere contenedor)
// modalidad_internacional: uno de [OTM, DTA, DTAI, NACIONALIZADA]
// vehiculo_acom: número entero como string (p.ej. "2") que indica la cantidad de vehículos de acompañamiento

// Instrucciones:

// Si el usuario usa sinónimos o lenguaje natural, convierte al nombre de campo correcto y al valor exacto de las opciones de select.
// Ejemplos:
// • "viaje nacional" -> tipo_viaje = "NACIONAL"
// • "en dólares" / "USD" -> moneda = "DOLARES"
// • "no se requiere el uso del contenedor", "sin contenedor" -> contenedor = "NO"
// • "sí requiere contenedor", "usa contenedor" -> contenedor = "SI"
// • "la cantidad de vehículos de acompañamiento es 2" -> vehiculo_acom = "2"
// Cuando detectes varios campos en un mismo mensaje, llama a "rellenar" múltiples veces, una por cada campo.
// `;

const SYSTEM_PROMPT = `
  Eres un asistente que ayuda a llenar un formulario dividido en pasos.
  Siempre que el usuario entregue datos de campos, debes llamar a la
  función "rellenar" UNA VEZ por cada campo detectado.  
  Repite tantas llamadas como sean necesarias hasta completar todos los
  campos que el usuario mencione en su mensaje.  
  No des una respuesta final hasta que no hayas intentado rellenar todos
  los campos posibles.

  ──────────────────────────────
  Paso 1 – Encabezado
  ──────────────────────────────
  tipo_viaje:            uno de [NACIONAL, URBANO, INTERNACIONAL]  
  moneda:                uno de [PESOS, DOLARES]  
  fuente_solicitud:      texto libre (p. ej. "TELEFONO DESPACHADOR")  
  condicion_despacho:    texto libre  
  condicion_facturacion: texto libre  
  ciudad_facturacion:    código DANE numérico como string (p. ej. "11001000")  
  vendedor:              código/numérico como string (p. ej. "53165050")  
  tipo_operacion:        texto libre (p. ej. "DISTRIBUCION")  
  centro_costo_despacho: texto libre (p. ej. "TRANSLIDHER BOGOTA")  
  cliente_codigo:        numérico/string (p. ej. "2551")

  ──────────────────────────────
  Paso 2 – Detalle del servicio
  ──────────────────────────────
  origen, destino:             código DANE numérico como string  
  cantidad_mercancia:          número  
  peso:                        número (kg)  
  valor_mercancia:             número  
  flete_conductor:             número      
  flete_ministerio:            número  
  producto:                    código de producto  
  empaque:                     código de empaque  
  cantidad_vehiculos:          número  
  clase_vehiculo:              código de clase vehículo  
  carroceria:                  código de carrocería  
  minimo_modelo:               número
  tarifa_cliente:              número 

  • tipo_flete:          uno de
    [CARGA SUELTA, CUPO, CONSOLIDADO, EXPRESO, GALON, VAN, CONTENEDOR]

  • tipo_tarifa:         uno de
    [GENERAL, PESO, GALON]

  • cargue_cuenta_de:    uno de
    [CLIENTE, EMPRESA, DESTINATARIO]

  • descargue_cuenta_de: uno de
    [CLIENTE, EMPRESA, DESTINATARIO]

  • seguro_cuenta_de:    uno de
    [CLIENTE, EMPRESA]

  • kit_seguridad:       uno de
    [SI, NO]

  • tipo_remesa_rndc:    uno de
    [REMESA GENERAL, CONTENEDOR CARGADO, CONTENEDOR VACIO]

  • fuente_solicitud: uno de
    [TELEFONO DESPACHADOR, TELEFONO ATENCION CLIENTE, MAIL, FAX, SIA, PAGINA WEB]

  • tipo_operacion: uno de
    [DISTRIBUCION, IMPORTACION, EXPORTACION]

  descripcion_mercancia: texto libre

  ──────────────────────────────
  Paso 3 – Cargue
  ──────────────────────────────
  fecha_cargue   : fecha ISO "AAAA-MM-DD" (p. ej. "2025-08-30")
  hora_cargue    : hora 24 h "HH:MM"      (p. ej. "16:00")
  remitente      : código/ID numérico     (p. ej. "1")
  destinatario   : código/ID numérico
  contacto       : teléfono o texto libre
  promesa_servicio: fecha ISO "AAAA-MM-DD"
  documento_transporte: texto/numérico
  observacion_cargue  : texto libre

  Ejemplos de mapeo:  
  • "Fecha de cargue hoy"              → fecha_cargue = "2025-08-30"  
  • "Hora de cargue 4 pm"              → hora_cargue  = "16:00"  
  • "Remitente 123"                    → remitente    = "123"

  ──────────────────────────────
  Paso 4 – Contenedor
  ──────────────────────────────
  contenedor: uno de [SI, NO]

  ──────────────────────────────
  Paso 5 – Internacional
  ──────────────────────────────
  modalidad_internacional: uno de [OTM, DTA, DTAI, NACIONALIZADA]

  ──────────────────────────────
  Paso 6 – Acompañamiento
  ──────────────────────────────
  vehiculo_acom: número entero como string (p. ej. "2")

  ──────────────────────────────
  Instrucciones de mapeo
  ──────────────────────────────
  • Usa sinónimos o lenguaje natural y conviértelos al nombre de campo y
    valor exactos.  

    Ejemplos:  
    – "viaje nacional"                 → tipo_viaje = "NACIONAL"  
    – "en dólares" / "USD"             → moneda = "DOLARES"  
    – "no se requiere contenedor"      → contenedor = "NO"  
    – "tipo de flete será cupo"        → tipo_flete = "CUPO"  
    – "que el cargue lo pague empresa" → cargue_cuenta_de = "EMPRESA"  
    – "sin kit de seguridad"           → kit_seguridad = "NO"  
    – "remesa contenedor vacío"        → tipo_remesa_rndc = "CONTENEDOR VACIO"  
    – "la cantidad de vehículos de acompañamiento es 2"
                                        → vehiculo_acom = "2"

  • Cuando detectes varios campos en un mismo mensaje, llama a
    "rellenar" varias veces, una por cada campo, y **no** proporciones
    respuesta final hasta terminar todas las llamadas necesarias.
  `;

/* ---------- Componente ---------- */
export default function ChatBox() {
  const recorderRef    = useRef(null);       
  const chunksRef      = useRef([]);         
  const [messages, setMsgs] = useState([
    { role: 'assistant', content: 'Hola 👋, escribe tus datos y los iré colocando en el formulario.' }
  ]);
  const inputRef = useRef();
  const recognitionRef = useRef(null);
  const [listening, setListening] = useState(false);

  /* helper para enviar a pantalla */
  const pushMsg = m => setMsgs(prev => [...prev, m]);

  /* bucle que procesa function-calls en cascada (con normalización) */
  // const runChat = async (history) => {
  //   let workHistory = [
  //     { role: 'system', content: SYSTEM_PROMPT },
  //     ...history
  //   ];

  //   let response = await openai.chat.completions.create({
  //     model        : 'gpt-4o-mini',
  //     messages     : workHistory,
  //     functions    : FUNCTIONS,
  //     function_call: 'auto'
  //   });

  //   // Mientras siga llamando funciones
  //   // while (response.choices?.[0]?.finish_reason === 'function_call') {
  //   while (response.choices?.[0]?.message?.function_call) {
  //     const assistantMsg = response.choices[0].message;
  //     workHistory.push(assistantMsg);
  //     setMsgs(prev => [...prev, assistantMsg]);   // ➕ guarda el assistant con function_call

  //     const call = assistantMsg.function_call;
  //     try {
  //       const args = JSON.parse(call.arguments || '{}');
  //       const field = normalizeKey(args.field);
  //       const rawValue = String(args.value ?? '');
  //       const value = normalizeValue(field, rawValue); // Nuevo: normalización de valor

  //       console.log('[func-call]', field, rawValue, '=>', value);
  //       chatBus.emit('fill-field', field, value);

  //        // 1) mensaje visible para el usuario
  //       pushMsg({ role: 'assistant', content: `He rellenado «${field}».` });

  //       // 2) mensaje role:function (no visible, pero debe quedar en el historial)
  //       const fnMsg = {
  //         role   : 'function',
  //         name   : call.name,
  //         content: JSON.stringify({ field, value })
  //       };
  //       workHistory.push(fnMsg);       // se lo enviamos al modelo
  //       setMsgs(prev => [...prev, fnMsg]); // y lo guardamos en messages
  //     } catch (err) {
  //       console.error('Error parseando argumentos de function_call', err);
  //       workHistory.push({
  //         role   : 'function',
  //         name   : call?.name || 'rellenar',
  //         content: 'error'
  //       });
  //     }

  //     response = await openai.chat.completions.create({
  //       model        : 'gpt-4o-mini',
  //       messages     : workHistory,
  //       functions    : FUNCTIONS,
  //       function_call: 'auto'
  //     });
  //   }

  //   // Cuando ya no hay más function_call muestra el mensaje final
  //   const finalMsg = response.choices?.[0]?.message;
  //   if (finalMsg) pushMsg(finalMsg);
  // };

  /* ---------- bucle que procesa function-calls en cascada (sin mostrar los intermedios) ---------- */
  const runChat = async (history) => {
    let workHistory = [
      { role: 'system', content: SYSTEM_PROMPT },
      ...history
    ];

    let response = await openai.chat.completions.create({
      model        : 'gpt-4o-mini',
      messages     : workHistory,
      functions    : FUNCTIONS,
      function_call: 'auto'
    });

    // ── mientras la IA siga pidiendo llamar a la función ─────────────────────
    while (response.choices?.[0]?.message?.function_call) {
      const assistantMsg = response.choices[0].message;

      /* ① añadimos la petición de función al histórico (solo para la IA,
        no se guarda en el estado que renderiza la UI) */
      workHistory.push(assistantMsg);

      const call = assistantMsg.function_call;
      try {
        const args      = JSON.parse(call.arguments || '{}');
        const field     = normalizeKey(args.field);
        const rawValue  = String(args.value ?? '');
        const value     = normalizeValue(field, rawValue);

        console.log('[func-call]', field, rawValue, '=>', value);
        chatBus.emit('fill-field', field, value);   // ← rellena el formulario

        /* ② confirmación interna hacia el modelo (role:function),
              tampoco se muestra en la UI                                 */
        workHistory.push({
          role   : 'function',
          name   : call.name,
          content: JSON.stringify({ field, value })
        });
      } catch (err) {
        console.error('Error parseando argumentos de function_call', err);
        workHistory.push({
          role   : 'function',
          name   : call?.name || 'rellenar',
          content: 'error'
        });
      }

      // nueva ronda
      response = await openai.chat.completions.create({
        model        : 'gpt-4o-mini',
        messages     : workHistory,
        functions    : FUNCTIONS,
        function_call: 'auto'
      });
    }

    // ── cuando ya no hay más llamadas, se muestra solo el mensaje final ──────
    const finalMsg = response.choices?.[0]?.message;
    if (finalMsg) pushMsg(finalMsg);
  };

  const send = async () => {
    const text = inputRef.current.value.trim();
    if (!text) return;
    pushMsg({ role: 'user', content: text });
    const convo = [...messages, { role: 'user', content: text }];
    inputRef.current.value = '';

    try { await runChat(convo); }
    catch (e) {
      console.error('OpenAI error', e);
      pushMsg({ role: 'assistant', content: '⚠️ Error procesando tu solicitud.' });
    }
  };

  /* Crear instancia SpeechRecognition una sola vez */
  // useEffect(() => {
  //   if (!SpeechRec) return;          // navegador sin soporte
  //   const rec = new SpeechRec();
  //   rec.lang = 'es-CO';              // ajusta idioma
  //   rec.interimResults = true;

  //   rec.onstart = () => setListening(true);
  //   rec.onend   = () => setListening(false);

  //   rec.onresult = (e) => {
  //     const texto = Array.from(e.results)
  //       .map(r => r[0].transcript)
  //       .join('');
  //     inputRef.current.value = texto;
  //   };
  //   recognitionRef.current = rec;

  //   return () => rec && rec.stop();
  // }, []);

  /* ---------- Inicializa la fuente de audio según el navegador ---------- */
  useEffect(() => {
    if (isSpeechApi) {
      /* ----- Chrome / Edge / Safari macOS (cuando lo habiliten) ----- */
      const rec = new SpeechRec();
      rec.lang = 'es-CO';
      rec.interimResults = true;

      rec.onstart  = () => setListening(true);
      rec.onend    = () => setListening(false);
      rec.onresult = (e) => {
        const texto = Array.from(e.results)
          .map(r => r[0].transcript)
          .join('');
        inputRef.current.value = texto;
      };

      recognitionRef.current = rec;
      return () => rec.stop();
    }

    /* ----- iOS: preparamos MediaRecorder + Whisper ------------------ */
    (async () => {
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        const mr     = new MediaRecorder(stream, { mimeType: 'audio/webm' });
        recorderRef.current = mr;

        mr.onstart = () => {
          chunksRef.current = [];
          setListening(true);
        };

        mr.ondataavailable = (e) => chunksRef.current.push(e.data);

        mr.onstop = async () => {
          setListening(false);
          const blob = new Blob(chunksRef.current, { type: 'audio/webm' });
          const file = new File([blob], 'voice.webm', { type: 'audio/webm' });

          try {
            const txt = await openai.audio.transcriptions.create({
              file,
              model: 'whisper-1',
              response_format: 'text',
              language: 'es'
            });

            inputRef.current.value = txt;
          } catch (err) {
            console.error('Whisper error', err);
            alert('Error al transcribir audio');
          }
        };
      } catch (err) {
        console.error('Mic permission', err);
      }
    })();
  }, []);

  // const toggleMic = () => {
  //   const rec = recognitionRef.current;
  //   if (!rec) return alert('Tu navegador no soporta reconocimiento de voz');
  //   if (listening) rec.stop();
  //   else {
  //     inputRef.current.focus();
  //     rec.start();
  //   }
  // };

  const toggleMic = () => {
    if (isSpeechApi) {
      const rec = recognitionRef.current;
      if (!rec) return alert('Tu navegador no soporta reconocimiento de voz');
      listening ? rec.stop() : rec.start();
      return;
    }

    // – iOS –
    const mr = recorderRef.current;
    if (!mr) return alert('No se pudo inicializar el micrófono');
    if (listening) mr.stop();
    else           mr.start();
  };

  /* ---------- RETURN (responsive) ---------- */
  return (
    <div className="flex flex-col h-full w-full">
      {/* LISTA DE MENSAJES */}
      <div
        ref={el => el && el.scrollTo(0, el.scrollHeight)}   /* auto-scroll */
        className="flex-1 overflow-y-auto px-3 py-4 sm:px-6 space-y-3"
      >
        {messages
          .filter(m => m.role !== 'function' && m.content)
          .map((m, i) => (
            <div
              key={i}
              className={`
                rounded-xl px-4 py-2 text-sm leading-relaxed break-words
                max-w-[80%] sm:max-w-md
                ${m.role === 'user'
                  ? 'ml-auto bg-gradient-to-br from-orange-400 to-orange-500 text-white'
                  : 'mr-auto bg-gray-100 text-gray-800'}
              `}
            >
              {m.content}
            </div>
          ))}
      </div>

      {/* FOOTER (input + mic + enviar) */}
      <div
        className="
          sticky bottom-0 left-0 right-0
          flex items-center gap-2
          bg-white/90 backdrop-blur border-t
          p-3 sm:p-4
        "
      >
        {/* INPUT */}
        <input
          ref={inputRef}
          onKeyDown={e => e.key === 'Enter' && send()}
          className="
            flex-1 text-sm sm:text-base
            border rounded-md px-3 py-2
            focus:outline-none focus:ring-2 focus:ring-orange-500
            placeholder:text-gray-400
          "
          placeholder={listening ? 'Escuchando…' : 'Escribe aquí…'}
        />

        {/* MIC */}
        <button
          type="button"
          onClick={toggleMic}
          className={`
            flex-shrink-0 w-10 h-10 sm:w-11 sm:h-11
            rounded-full grid place-items-center transition-colors
            ${listening
              ? 'bg-green-500 animate-pulse text-white'
              : 'bg-gray-200 hover:bg-gray-300 text-gray-700'}
          `}
          title={listening ? 'Detener dictado' : 'Dictar con micrófono'}
        >
          {listening ? (
            /* stop icon */
            <svg className="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
              <rect x="6" y="6" width="8" height="8" />
            </svg>
          ) : (
            /* mic icon */
            <svg className="w-4 h-4" viewBox="0 0 16 16" fill="currentColor">
              <path d="M8 12a3 3 0 0 0 3-3V4a3 3 0 0 0-6 0v5a3 3 0 0 0 3 3z" />
              <path d="M5 10.5a.5.5 0 0 1 1 0A2 2 0 0 0 8 12a2 2 0 0 0 2-1.5.5.5 0 0 1 1 0A3 3 0 0 1 8 13a3 3 0 0 1-3-2.5z" />
              <path d="M10 14.5V13h1a.5.5 0 0 0 0-1H5a.5.5 0 0 0 0 1h1v1.5a.5.5 0 0 0 1 0V13h2v1.5a.5.5 0 0 0 1 0z" />
            </svg>
          )}
        </button>

        {/* ENVIAR */}
        <button
          onClick={send}
          className="
            flex-shrink-0 bg-orange-500 hover:bg-orange-600
            text-white text-sm sm:text-base
            px-4 sm:px-5 py-2 rounded-md transition-colors
          "
        >
          Enviar
        </button>
      </div>
    </div>
  );
}