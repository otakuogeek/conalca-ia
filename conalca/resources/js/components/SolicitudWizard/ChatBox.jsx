import React, { useRef, useState, useEffect } from 'react';
import OpenAI from 'openai';
import EventEmitter from 'eventemitter3';
import { searchVendedores, searchCiudades, searchClientes } from '../../api/solicitud';

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

  'valor mercancia'    : 'valor_mercancia',
  'valor mercancía'    : 'valor_mercancia',
  'valor'              : 'valor_mercancia',

  'cantidad mercancia' : 'cantidad_mercancia',
  'cantidad mercancía' : 'cantidad_mercancia',
  'cantidad'           : 'cantidad_mercancia',

  'tipo operacion'     : 'tipo_operacion',
  'tipo operación'     : 'tipo_operacion',
  'operacion'          : 'tipo_operacion',
  'operación'          : 'tipo_operacion',

  'centro costo despacho' : 'centro_costo_despacho',
  'centro de costo'       : 'centro_costo_despacho',
  'centro costo'          : 'centro_costo_despacho',

  'condicion despacho'    : 'condicion_despacho',
  'condición despacho'    : 'condicion_despacho',
  'condiciones despacho'  : 'condicion_despacho',
  'condición de despacho' : 'condicion_despacho',

  'condicion facturacion' : 'condicion_facturacion',
  'condición facturación' : 'condicion_facturacion',
  'condiciones facturacion': 'condicion_facturacion',
  'condición de facturación': 'condicion_facturacion',

  'tipo mercancia'     : 'tipo_mercancia',
  'tipo mercancía'     : 'tipo_mercancia',
  'mercancia'          : 'tipo_mercancia',
  'mercancía'          : 'tipo_mercancia',
  'producto'           : 'tipo_mercancia',

  'peso'               : 'peso',
  'peso total'         : 'peso',
  'peso kg'            : 'peso',
  'kilogramos'         : 'peso',
  'kg'                 : 'peso',
  'kilos'              : 'peso',

  'origen'             : 'origen',
  'ciudad origen'      : 'origen',
  'desde'              : 'origen',

  'destino'            : 'destino',
  'ciudad destino'     : 'destino',
  'hacia'              : 'destino',

  'contenedor'         : 'contenedor',
  'modalidad internacional' : 'modalidad_internacional',
  'modalidad'               : 'modalidad_internacional',
  'vehiculo acompañamiento' : 'vehiculo_acom',
  'vehículo acompañamiento' : 'vehiculo_acom',
  'vehiculos acompañamiento': 'vehiculo_acom',
  'vehículos acompañamiento': 'vehiculo_acom',
  'vehiculo_acom'           : 'vehiculo_acom'
};

/* ---------- normalizar campos ---------- */
function normalizeKey(key) {
  if (!key) return '';
  const k = String(key).toLowerCase().trim();
  return ALIAS[k] || k;
}

/* ---------- normalizar valores según el campo ---------- */
function normalizeValue(field, rawValue) {
  if (!rawValue) return '';
  let value = String(rawValue).trim();

  switch (field) {
    case 'tipo_viaje':
      const tipoUpper = value.toUpperCase();
      if (tipoUpper.includes('NACIONAL')) return 'NACIONAL';
      if (tipoUpper.includes('INTERNACIONAL')) return 'INTERNACIONAL';
      if (tipoUpper.includes('URBANO')) return 'URBANO';
      return tipoUpper;

    case 'moneda':
      const monedaUpper = value.toUpperCase();
      if (monedaUpper.includes('PESO') || monedaUpper.includes('COP')) return 'PESOS';
      if (monedaUpper.includes('DOLAR') || monedaUpper.includes('USD') || monedaUpper.includes('DOLLAR')) return 'DOLARES';
      return monedaUpper;

    case 'contenedor':
      const contenedorUpper = value.toUpperCase();
      if (contenedorUpper.includes('SI') || contenedorUpper.includes('SÍ') || contenedorUpper.includes('YES')) return 'SI';
      if (contenedorUpper.includes('NO')) return 'NO';
      return contenedorUpper;

    case 'modalidad_internacional':
      const modalidadUpper = value.toUpperCase();
      if (modalidadUpper.includes('OTM')) return 'OTM';
      if (modalidadUpper.includes('DTA')) return 'DTA';
      if (modalidadUpper.includes('DTAI')) return 'DTAI';
      if (modalidadUpper.includes('NACIONALIZADA')) return 'NACIONALIZADA';
      return modalidadUpper;

    case 'peso':
    case 'valor_mercancia':
    case 'cantidad_mercancia':
    case 'vehiculo_acom':
      // Extraer solo números y puntos/comas
      const number = value.replace(/[^\d.,]/g, '').replace(',', '.');
      return number;

    default:
      return value;
  }
}

/* ---------- Funciones de búsqueda ---------- */
const getFunctions = () => {
  return [
    {
      name: 'buscar_vendedores',
      description: 'Busca vendedores en el sistema',
      parameters: {
        type: 'object',
        properties: {
          query: {
            type: 'string',
            description: 'Término de búsqueda para vendedores'
          }
        },
        required: ['query']
      }
    },
    {
      name: 'buscar_ciudades',
      description: 'Busca ciudades en el sistema',
      parameters: {
        type: 'object',
        properties: {
          query: {
            type: 'string',
            description: 'Término de búsqueda para ciudades'
          }
        },
        required: ['query']
      }
    },
    {
      name: 'buscar_clientes',
      description: 'Busca clientes en el sistema',
      parameters: {
        type: 'object',
        properties: {
          query: {
            type: 'string',
            description: 'Término de búsqueda para clientes'
          }
        },
        required: ['query']
      }
    },
    {
      name: 'rellenar',
      description: 'Rellena un campo específico del formulario',
      parameters: {
        type: 'object',
        properties: {
          field: {
            type: 'string',
            description: 'Nombre del campo a rellenar'
          },
          value: {
            type: 'string',
            description: 'Valor para el campo'
          }
        },
        required: ['field', 'value']
      }
    }
  ];
};

const FUNCTIONS = getFunctions();

/* ---------- Prompt del sistema ---------- */
const FUNCTION_DESCRIPTIONS = `

# Funciones disponibles:

## rellenar(field, value)
Rellena un campo específico del formulario de cotización. Usa esta función cada vez que detectes información que debe ir en un campo específico.

## buscar_vendedores(query)
Busca vendedores en el sistema. Ejemplo: buscar_vendedores("juan")

## buscar_ciudades(query)
Busca ciudades en el sistema. Ejemplo: buscar_ciudades("bogota")

## buscar_clientes(query)
Busca clientes en el sistema. Ejemplo: buscar_clientes("transportes")

# Campos del formulario:

// PASO 1 - Información básica
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

// PASO 2 - Origen y destino
// origen: nombre de ciudad (p.ej. "Bogotá")
// destino: nombre de ciudad (p.ej. "Medellín")

// PASO 3 - Información de mercancía
// tipo_mercancia: tipo de producto/mercancía (p.ej. "ALIMENTOS")
// peso: peso en kg como número (p.ej. "500")
// cantidad_mercancia: cantidad como número (p.ej. "100")
// valor_mercancia: valor monetario como número (p.ej. "1000000")

// PASO 4 - Contenedor
// contenedor: uno de [SI, NO] (indica si requiere contenedor)

// PASO 5 - Internacional
// modalidad_internacional: uno de [OTM, DTA, DTAI, NACIONALIZADA]

// PASO 6 - Acompañamiento
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

PASO 3 - Información de mercancía:
- tipo_mercancia: "ALIMENTOS"
- peso: "500"
- cantidad_mercancia: "100"
- valor_mercancia: "1000000"

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
    
    // Limpiar input inmediatamente
    setUserInput('');
    console.log('🧹 Input limpiado');
    
    // Obtener histórico completo para OpenAI
    const newHistory = [...messages, userMsg];
    console.log('📚 Historia completa:', newHistory.length, 'mensajes');
    
    setIsProcessing(true);
    console.log('⏳ Procesando...');
    
    try {
      await runChat(newHistory);
      console.log('✅ runChat completado exitosamente');
    } catch (error) {
      console.error('❌ Error en runChat:', error);
      pushMsg({ 
        role: 'assistant', 
        content: '❌ Error al procesar tu mensaje. Verifica la conexión y vuelve a intentar.' 
      });
    } finally {
      setIsProcessing(false);
      console.log('🏁 Procesamiento terminado');
    }
  };

  /* ---------- Reconocimiento de voz ---------- */
  const startListening = () => {
    if (!isSpeechApi) {
      alert('Reconocimiento de voz no soportado en este navegador');
      return;
    }

    recognitionRef.current = new SpeechRec();
    recognitionRef.current.lang = 'es-ES';
    recognitionRef.current.continuous = false;
    recognitionRef.current.interimResults = false;

    recognitionRef.current.onstart = () => {
      setListening(true);
      console.log('🎤 Reconocimiento iniciado');
    };

    recognitionRef.current.onresult = (event) => {
      const transcript = event.results[0][0].transcript;
      console.log('🗣️ Transcripción:', transcript);
      setUserInput(transcript);
    };

    recognitionRef.current.onerror = (event) => {
      console.error('❌ Error en reconocimiento:', event.error);
      setListening(false);
    };

    recognitionRef.current.onend = () => {
      setListening(false);
      console.log('🎤 Reconocimiento terminado');
    };

    recognitionRef.current.start();
  };

  const stopListening = () => {
    if (recognitionRef.current) {
      recognitionRef.current.stop();
    }
  };

  /* ---------- Manejo de teclado ---------- */
  const handleKeyPress = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      send();
    }
  };

  /* ---------- Render ---------- */
  return (
    <div className="chat-box bg-white rounded-lg shadow-lg border border-gray-200 h-full flex flex-col">
      {/* Header */}
      <div className="bg-gradient-to-r from-orange-600 to-orange-700 text-white p-4 rounded-t-lg">
        <h3 className="font-semibold text-lg flex items-center">
          <span className="mr-2">🤖</span>
          Asistente de Cotización
        </h3>
        <p className="text-orange-100 text-sm mt-1">
          Dime los detalles y llenaré el formulario automáticamente
        </p>
      </div>

      {/* Messages */}
      <div className="flex-1 p-4 overflow-y-auto max-h-96 min-h-64">
        <div className="space-y-4">
          {messages.map((msg, idx) => (
            <div key={idx} className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}>
              <div className={`max-w-xs lg:max-w-md px-4 py-2 rounded-lg ${
                msg.role === 'user' 
                  ? 'bg-orange-600 text-white' 
                  : 'bg-gray-100 text-gray-800'
              }`}>
                <p className="text-sm">{msg.content}</p>
              </div>
            </div>
          ))}
          
          {isProcessing && (
            <div className="flex justify-start">
              <div className="bg-gray-100 text-gray-800 px-4 py-2 rounded-lg max-w-xs lg:max-w-md">
                <div className="flex items-center space-x-2">
                  <div className="flex space-x-1">
                    <div className="w-2 h-2 bg-orange-600 rounded-full animate-bounce"></div>
                    <div className="w-2 h-2 bg-orange-600 rounded-full animate-bounce" style={{animationDelay: '0.1s'}}></div>
                    <div className="w-2 h-2 bg-orange-600 rounded-full animate-bounce" style={{animationDelay: '0.2s'}}></div>
                  </div>
                  <span className="text-sm text-gray-500">Procesando...</span>
                </div>
              </div>
            </div>
          )}
          <div ref={messagesEndRef} />
        </div>
      </div>

      {/* Input */}
      <div className="border-t border-gray-200 p-4">
        <div className="flex items-center space-x-2">
          <div className="flex-1">
            <textarea
              ref={inputRef}
              value={userInput}
              onChange={(e) => setUserInput(e.target.value)}
              onKeyPress={handleKeyPress}
              placeholder="Ejemplo: Envío nacional de 500kg de alimentos de Bogotá a Medellín"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
              rows="2"
              disabled={isProcessing}
            />
          </div>
          
          {/* Botón de voz */}
          {isSpeechApi && (
            <button
              onClick={listening ? stopListening : startListening}
              className={`p-2 rounded-lg transition-colors ${
                listening 
                  ? 'bg-red-500 hover:bg-red-600 text-white' 
                  : 'bg-gray-100 hover:bg-gray-200 text-gray-600'
              }`}
              disabled={isProcessing}
              title={listening ? 'Detener grabación' : 'Grabar mensaje'}
            >
              {listening ? (
                <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M10 2a4 4 0 00-4 4v4a4 4 0 008 0V6a4 4 0 00-4-4zM6 10V6a4 4 0 118 0v4a6 6 0 01-12 0z" clipRule="evenodd" />
                  <path d="M7 16h6v1a1 1 0 11-2 0v-1H9v1a1 1 0 11-2 0v-1z" />
                </svg>
              ) : (
                <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clipRule="evenodd" />
                </svg>
              )}
            </button>
          )}
          
          {/* Botón enviar */}
          <button
            onClick={send}
            disabled={!userInput.trim() || isProcessing}
            className="bg-orange-600 hover:bg-orange-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg transition-colors"
          >
            {isProcessing ? (
              <svg className="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            ) : (
              <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
              </svg>
            )}
          </button>
        </div>
      </div>
    </div>
  );
}