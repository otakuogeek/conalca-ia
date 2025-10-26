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
// centro_costo_despacho: debe ser uno de los centros válidos (p.ej. "CONALCA BOGOTA", "CONALCA CALI", "CONALCA MEDELLIN")
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
// centro_costo_despacho: debe ser uno de los centros válidos (p.ej. "CONALCA BOGOTA", "CONALCA CALI", "CONALCA MEDELLIN")
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
Eres un asistente de cotización de transporte. Tu ÚNICA tarea es detectar información en los mensajes del usuario y llamar a la función "rellenar" para cada campo que identifiques.

NUNCA expliques qué campos necesitas. NUNCA des ejemplos. NUNCA des información detallada.

SIEMPRE usa la función "rellenar" cuando detectes datos. Después de rellenar todos los campos posibles, da una respuesta corta confirmando lo que llenaste.

**COMANDO ESPECIAL - LLENADO COMPLETO:**
Si el usuario dice "llena todo con ejemplos", "completa el formulario con datos de ejemplo", "llena todos los campos", o similar, debes llamar a la función "rellenar" para TODOS estos campos con valores de ejemplo:

PASO 1 - Datos básicos:
- tipo_viaje: "NACIONAL"
- moneda: "PESOS"
- fuente_solicitud: "PAGINA WEB"
- condicion_despacho: "EXW"
- condicion_facturacion: "A DOMICILIO"
- ciudad_facturacion: "11001000"
- vendedor: "53165050"
- tipo_operacion: "DISTRIBUCION"
- centro_costo_despacho: "CONALCA BOGOTA"
- cliente_codigo: "2551"

PASO 2 - Detalle del servicio:
- origen: "Bogotá"
- destino: "Medellín"
- cantidad_mercancia: "100"
- peso: "500"
- valor_mercancia: "25000000"
- flete_conductor: "150000"
- flete_ministerio: "50000"
- producto: "Alimentos"
- empaque: "Cajas"
- cantidad_vehiculos: "1"
- tipo_flete: "CARGA SUELTA"
- tipo_tarifa: "PESO"
- cargue_cuenta_de: "CLIENTE"
- descargue_cuenta_de: "DESTINATARIO"
- seguro_cuenta_de: "CLIENTE"
- kit_seguridad: "SI"
- tipo_remesa_rndc: "REMESA GENERAL"
- descripcion_mercancia: "Productos alimenticios envasados"
- tarifa_cliente: "300000"

PASO 3 - Cargue:
- fecha_cargue: "2025-10-10"
- hora_cargue: "08:00"
- remitente: "123"
- destinatario: "456"
- contacto: "300-555-1234"
- promesa_servicio: "2025-10-12"
- documento_transporte: "DOC001"
- observacion_cargue: "Carga frágil, manejar con cuidado"

PASO 4 - Contenedor:
- contenedor: "NO"

PASO 5 - Internacional:
- modalidad_internacional: "OTM"

PASO 6 - Acompañamiento:
- vehiculo_acom: "0"

Cuando detectes la solicitud de llenado completo, llama a "rellenar" una vez por cada campo de la lista anterior.

Campos principales para detección normal:
- tipo_viaje: [NACIONAL, URBANO, INTERNACIONAL]
- moneda: [PESOS, DOLARES] 
- origen/destino: texto de ciudades
- peso: número
- producto: tipo de mercancía
- cantidad_mercancia: número
- valor_mercancia: número

IMPORTANTE: Usa SIEMPRE las funciones antes de responder.
`;

/* ---------- Componente ---------- */
export default function ChatBox() {
  console.log('🎯 ChatBox v4.0 - REFACTORIZADO Y FUNCIONAL');
  
  // Estados principales
  const [messages, setMsgs] = useState([
    { role: 'assistant', content: 'Hola! Dime los detalles de tu envío y llenaré el formulario automáticamente. Ejemplos: "Envío nacional de 500kg de alimentos de Bogotá a Medellín" o "llena todo con ejemplos" para llenar todos los campos.' }
  ]);
  const [userInput, setUserInput] = useState('');
  const [listening, setListening] = useState(false);
  const [isProcessing, setIsProcessing] = useState(false);
  
  // Referencias
  const inputRef = useRef();
  const recognitionRef = useRef(null);
  const recorderRef = useRef(null);
  const chunksRef = useRef([]);
  const messagesEndRef = useRef(null);
  
  // Inicializar OpenAI
  const openai = new OpenAI({
    apiKey: import.meta.env.VITE_OPENAI_API_KEY,
    dangerouslyAllowBrowser: true
  });
  
  console.log('✅ OpenAI inicializado correctamente');

  // Helper para agregar mensajes
  const pushMsg = m => {
    console.log('➕ Agregando mensaje:', m);
    setMsgs(prev => [...prev, m]);
  };
  
  // Auto-scroll cuando hay nuevos mensajes
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  // Función REFACTORIZADA para comunicación con OpenAI
  const runChat = async (history) => {
    console.log('🚀 runChat iniciado');
    console.log('📜 Historia recibida:', history.length, 'mensajes');
    
    let workHistory = [
      { role: 'system', content: SYSTEM_PROMPT },
      ...history
    ];

    console.log('📤 Enviando a OpenAI API...');
    console.log('🔑 API Key presente:', !!import.meta.env.VITE_OPENAI_API_KEY);

    // Detectar si el último mensaje del usuario contiene datos específicos
    const lastUserMessage = history[history.length - 1]?.content?.toLowerCase() || '';
    
    // Detectar comando de llenado completo
    const isCompleteFormRequest = /\b(llena todo|completa el formulario|llena.*ejemplo|llena.*campos|formulario.*ejemplo|datos.*ejemplo|llena.*completo)\b/.test(lastUserMessage);
    
    // Detectar datos específicos
    const containsData = /\b(envío|envio|nacional|internacional|urbano|kilos?|kg|toneladas?|bogotá|medellín|cali|barranquilla|alimentos|textiles|pesos|dolares|usd)\b/.test(lastUserMessage);
    
    const functionCallSetting = (isCompleteFormRequest || containsData) ? { name: 'rellenar' } : 'auto';
    console.log('🎯 Function call setting:', functionCallSetting, 'for message:', lastUserMessage);
    console.log('🔄 Complete form request:', isCompleteFormRequest, '| Contains data:', containsData);

    let response = await openai.chat.completions.create({
      model        : 'gpt-4o-mini',
      messages     : workHistory,
      functions    : FUNCTIONS,
      function_call: functionCallSetting
    });

    console.log('📥 Respuesta de OpenAI:', response);

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
        console.log('🎯 Emitiendo evento fill-field:', field, value);
        chatBus.emit('fill-field', field, value);   // ← rellena el formulario
        console.log('✅ Evento emitido');

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

  // Función REFACTORIZADA para enviar mensajes
  const send = async () => {
    const text = userInput.trim();
    console.log('📤 ChatBox v4.0 - send() llamado');
    console.log('📝 Texto del input:', text);
    console.log('📊 Estado userInput:', userInput);
    
    if (!text) {
      console.warn('⚠️ Texto vacío, no se envía');
      return;
    }
    
    // Agregar mensaje del usuario
    const userMsg = { role: 'user', content: text };
    console.log('✅ Agregando mensaje del usuario:', userMsg);
    pushMsg(userMsg);
    
    // Limpiar input INMEDIATAMENTE
    setUserInput('');
    console.log('� Input limpiado');
    
    // Preparar conversación
    const convo = [...messages, userMsg];
    setIsProcessing(true);
    
    try {
      console.log('🤖 Iniciando comunicación con OpenAI...');
      await runChat(convo);
      console.log('✅ Respuesta de OpenAI recibida');
    } catch (error) {
      console.error('❌ Error en OpenAI:', error);
      pushMsg({ 
        role: 'assistant', 
        content: `⚠️ Error: ${error.message || 'No se pudo procesar tu solicitud'}` 
      });
    } finally {
      setIsProcessing(false);
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
        setUserInput(texto); // ✅ Actualizar estado en lugar del ref
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

            setUserInput(txt); // ✅ Actualizar estado en lugar del ref
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
        
        {/* Indicador de procesamiento */}
        {isProcessing && (
          <div className="mr-auto bg-gray-100 text-gray-800 rounded-xl px-4 py-2 max-w-md">
            <div className="flex items-center gap-2">
              <div className="flex gap-1">
                <span className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{animationDelay: '0ms'}}></span>
                <span className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{animationDelay: '150ms'}}></span>
                <span className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{animationDelay: '300ms'}}></span>
              </div>
              <span className="text-xs text-gray-500">Procesando...</span>
            </div>
          </div>
        )}
        
        {/* Marcador para auto-scroll */}
        <div ref={messagesEndRef} />
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
          id="chat-message-input"
          name="chat-message"
          type="text"
          autoComplete="off"
          ref={inputRef}
          value={userInput}
          onChange={(e) => setUserInput(e.target.value)}
          onKeyDown={e => e.key === 'Enter' && !isProcessing && send()}
          disabled={isProcessing}
          className={`
            flex-1 text-sm sm:text-base
            text-gray-900
            border rounded-md px-3 py-2
            focus:outline-none focus:ring-2 focus:ring-orange-500
            placeholder:text-gray-400
            ${isProcessing ? 'bg-gray-100 cursor-not-allowed' : 'bg-white'}
          `}
          placeholder={
            isProcessing 
              ? 'Procesando...' 
              : listening 
                ? 'Escuchando…' 
                : 'Escribe aquí…'
          }
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
          disabled={isProcessing || !userInput.trim()}
          className={`
            flex-shrink-0 text-white text-sm sm:text-base
            px-4 sm:px-5 py-2 rounded-md transition-colors
            ${isProcessing || !userInput.trim()
              ? 'bg-gray-300 cursor-not-allowed'
              : 'bg-orange-500 hover:bg-orange-600'}
          `}
        >
          {isProcessing ? 'Enviando...' : 'Enviar'}
        </button>
      </div>
    </div>
  );
}