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
  'producto'           : 'producto',

  'peso'               : 'peso',
  'peso total'         : 'peso',
  'peso kg'            : 'peso',
  'kilogramos'         : 'peso',
  'kg'                 : 'peso',
  'kilos'              : 'peso',

  // ---- NUEVOS PARA PASO 2 ----
  'tipo de carga'      : 'tipo_carga',
  'tipo carga'         : 'tipo_carga',
  'carga'              : 'tipo_carga',

  'descripcion mercancia' : 'descripcion_mercancia',
  'descripción mercancía' : 'descripcion_mercancia',
  'descripcion de la mercancia': 'descripcion_mercancia',
  'descripción de la mercancía': 'descripcion_mercancia',
  'mercancia descripcion' : 'descripcion_mercancia',

  'lugar recogida contenedor'  : 'lugar_recogida_contenedor',
  'recogida contenedor'        : 'lugar_recogida_contenedor',
  'contenedor vacio'           : 'lugar_recogida_contenedor',

  'cantidad vehiculos'         : 'cantidad_vehiculos',
  'cantidad vehículos'         : 'cantidad_vehiculos',

  'clase vehiculo'             : 'clase_vehiculo',
  'carroceria'                 : 'carroceria',
  'carrocería'                 : 'carroceria',

  'minimo modelo'              : 'minimo_modelo',
  'mínimo modelo'              : 'minimo_modelo',

  'tipo flete'                 : 'tipo_flete',
  'flete ministerio'           : 'flete_ministerio',
  'flete conductor'            : 'flete_conductor',

  'tipo tarifa'                : 'tipo_tarifa',
  'tarifa cliente'             : 'tarifa_cliente',

  'cargue cuenta de'           : 'cargue_cuenta_de',
  'descargue cuenta de'        : 'descargue_cuenta_de',
  'seguro cuenta de'           : 'seguro_cuenta_de',

  'kit seguridad'              : 'kit_seguridad',
  'tipo remesa rndc'           : 'tipo_remesa_rndc',

  'tipo mercancia rndc'        : 'tipo_remesa_rndc', 
  'origen'             : 'origen',
  'ciudad origen'      : 'origen',
  'desde'              : 'origen',

  'destino'            : 'destino',
  'ciudad destino'     : 'destino',
  'hacia'              : 'destino',

  'contenedor'         : 'contenedor',
  'modalidad internacional' : 'modalidad_internacional',
  'modalidad'               : 'modalidad_internacional',


    // ---- Step 3 – Cargue ----
  'fecha cargue'           : 'fecha_cargue',
  'fecha_cargue'           : 'fecha_cargue',

  'hora cargue'            : 'hora_cargue',
  'horacargue'             : 'hora_cargue',
  'hora de cargue'         : 'hora_cargue',

  'remitente'              : 'remitente',
  'remitente_codigo'       : 'remitente',
  'remitente codigo'       : 'remitente',

  'destinatario'           : 'destinatario',
  'destinatario_codigo'    : 'destinatario',

  'promesa servicio'       : 'promesa_servicio',
  'promesa_servicio'       : 'promesa_servicio',

  'promesaservicio_hora'   : 'promesa_servicio_hora',
  'hora promesa servicio'  : 'promesa_servicio_hora',

  'documento transporte'   : 'documento_transporte',
  'documento_transporte'   : 'documento_transporte',

  'observacion cargue'     : 'observacion_cargue',
  'observación cargue'     : 'observacion_cargue',

  'contacto'               : 'contacto',
  'telefono contacto'      : 'contacto',

  'email'                  : 'email',
  'correo'                 : 'email',

    // ---- Step 6 – Costos ----
  'costo'                    : 'tipvalrem_codigo',
  'tipo costo'               : 'tipvalrem_codigo',
  'tipo de costo'            : 'tipvalrem_codigo',
  'tipvalrem'                : 'tipvalrem_codigo',
  'tipo valor remesa'        : 'tipvalrem_codigo',
  'valor remesa'             : 'tipvalrem_codigo',

  'valor unitario'           : 'valor_unitario',
  'unitario'                 : 'valor_unitario',
  'valor_unitario'           : 'valor_unitario',

  'valor costo unitario'     : 'valor_costo_unitario',
  'costo unitario'           : 'valor_costo_unitario',
  'valor_costo_unitario'     : 'valor_costo_unitario',

  'facturable'               : 'facturable',
  'es facturable'            : 'facturable',

  'observacion costo'        : 'observacion_costo',
  'observación costo'        : 'observacion_costo',
  'observacion_costo'        : 'observacion_costo',
  'observación del costo'    : 'observacion_costo',

  'aplica flete'             : 'aplica_flete',
  'aplica_flete'             : 'aplica_flete',
  'aplicaflete'              : 'aplica_flete',

  'proveedor'                : 'proveedor_codigo',
  'proveedor_codigo'         : 'proveedor_codigo',
  'codigo proveedor'         : 'proveedor_codigo',
  'código proveedor'         : 'proveedor_codigo'
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

    case 'fuente_solicitud': {
      const u = value.toUpperCase();
      if (u.includes('DESPACH')) return 'TELEFONO DESPACHADOR';       // teléfono despachador
      if (u.includes('ATENC')) return 'TELEFONO ATENCION CLIENTE';   // teléfono atención cliente
      if (u.includes('MAIL') || u.includes('CORREO')) return 'MAIL';
      if (u.includes('FAX')) return 'FAX';
      if (u.includes('SIA')) return 'SIA';
      if (u.includes('WEB') || u.includes('PAG')) return 'PAGINA WEB';
      return u; // fallback uppercase
    }

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

    case 'tipo_carga': {
      const upper = value.toUpperCase();
      if (upper.includes('CONT')) return 'CARGA_CONTENEDORIZADA';
      if (upper.includes('SUEL')) return 'CARGA_SUELTA';
      return upper.replace(/\s+/g, '_'); // fallback normalization
    }

    case 'centro_costo_despacho': {
      const CENTROS = [
        'TRANSLIDHER BARRANQUILLA','TRANSLIDHER BOGOTA','TRANSLIDHER UBATE',
        'TRANSLIDHER CARTAGENA','TRANSLIDHER BUENAVENTURA','TRANSLIDHER CALI',
        'TRANSLIDHER SANTA MARTA','TRANSLIDHER PEREIRA','TRANSLIDHER IPIALES',
        'TRANSLIDHER MEDELLIN','CONALCA MANIZALEZ','CONALCA BUENAVENTURA',
        'CONALCA CALI','ALMACENAMIENTO MOSQUERA','OTM CONALCA CTG',
        'OTM CONALCA SNMT','CONALCA IPIALES','ALMACENAMIENTO CALI',
        'CONALCA CARTAGENA','CONALCA UBATE','OTM CONALCA BTA',
        'BUN MAERSK DEDICADO','BOG MAERSK DEDICADO','CLO MAERSK DEDICADO',
        'CONALCA PEREIRA','CONALCA BARRANQUILLA','ARMENIA - BAVARIA',
        'CONALCA PAGOS ANT','CONALCA SANTA MARTA','CARTAGENA - BAVARIA',
        'TUNJA - BAVARIA','SANTAMARTA - BAVARIA','YUMBO - BAVARIA',
        'CONALCA BOGOTA','CONALCA BUCARAMANGA','OTM CONALCA BAQ',
        'OTM CONALCA BUN','BUENAVENTURA PANTOS','CONALCA MEDELLIN',
        'ALMACENAMIENTO','BOGOTA GLOBAL','BUENAVENTURA GLOBAL',
        'CALI GLOBAL EXPRESS','BOGOTA CONENVIOS','MEDELLIN GLOBAL EXPRESS',
        'BOGOTA CONALOG','TRANSIFRONT CUCUTA'
      ];
      const up = value.toUpperCase().trim();
      // Exact match first
      const exact = CENTROS.find(c => c === up);
      if (exact) return exact;
      // Contains match (input contains option or option contains input)
      const contains = CENTROS.find(c => c.includes(up) || up.includes(c));
      if (contains) return contains;
      // Word-based fuzzy: find best match by number of matching words
      const words = up.split(/[\s\-]+/).filter(Boolean);
      let bestMatch = null, bestScore = 0;
      for (const c of CENTROS) {
        const cWords = c.split(/[\s\-]+/);
        const score = words.filter(w => cWords.some(cw => cw.includes(w) || w.includes(cw))).length;
        if (score > bestScore) { bestScore = score; bestMatch = c; }
      }
      if (bestMatch && bestScore > 0) return bestMatch;
      return up;
    }

    case 'lugar_recogida_contenedor':
    case 'ciudad_facturacion':
    case 'origen':
    case 'destino':
      return value;
      // return value.replace(/[^\d]/g, '');

    case 'descripcion_mercancia':
      return value;

    case 'fecha_cargue':
    case 'promesa_servicio': {
      const normalized = value.replace(/\./g, '/').replace(/-/g, '/');
      const parts = normalized.split(/[\/]/);
      if (parts.length === 3) {
        // assume dd/MM/yyyy
        const [d, m, y] = parts;
        if (d.length === 2 && m.length === 2 && y.length === 4) {
          return `${y}-${m}-${d}`;
        }
      }
      // already ISO or malformed: return trimmed string
      return value;
    }
    
    case 'hora_cargue':
    case 'promesa_servicio_hora':
      return value.replace(/[^\d:]/g, '').slice(0, 5);

    case 'peso':
    case 'valor_mercancia':
    case 'cantidad_mercancia':
    case 'cantidad_vehiculos':
    case 'tarifa_cliente':
    case 'flete_conductor':
    case 'flete_ministerio':
    // STEP 6 – Costos
    case 'valor_unitario':
    case 'valor_costo_unitario':
      // Extraer solo números y puntos/comas
      const number = value.replace(/[^\d.,]/g, '').replace(',', '.');
      return number;

    case 'facturable':
    case 'aplica_flete': {
      const upper = value.toUpperCase();
      if (upper.includes('SI') || upper.includes('SÍ') || upper.includes('YES')) return 'SI';
      if (upper.includes('NO')) return 'NO';
      return upper;
    }

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
// centro_costo_despacho: DEBE ser exactamente uno de estos valores:
// TRANSLIDHER BARRANQUILLA, TRANSLIDHER BOGOTA, TRANSLIDHER UBATE, TRANSLIDHER CARTAGENA,
// TRANSLIDHER BUENAVENTURA, TRANSLIDHER CALI, TRANSLIDHER SANTA MARTA, TRANSLIDHER PEREIRA,
// TRANSLIDHER IPIALES, TRANSLIDHER MEDELLIN, CONALCA MANIZALEZ, CONALCA BUENAVENTURA,
// CONALCA CALI, ALMACENAMIENTO MOSQUERA, OTM CONALCA CTG, OTM CONALCA SNMT,
// CONALCA IPIALES, ALMACENAMIENTO CALI, CONALCA CARTAGENA, CONALCA UBATE, OTM CONALCA BTA,
// BUN MAERSK DEDICADO, BOG MAERSK DEDICADO, CLO MAERSK DEDICADO, CONALCA PEREIRA,
// CONALCA BARRANQUILLA, ARMENIA - BAVARIA, CONALCA PAGOS ANT, CONALCA SANTA MARTA,
// CARTAGENA - BAVARIA, TUNJA - BAVARIA, SANTAMARTA - BAVARIA, YUMBO - BAVARIA,
// CONALCA BOGOTA, CONALCA BUCARAMANGA, OTM CONALCA BAQ, OTM CONALCA BUN,
// BUENAVENTURA PANTOS, CONALCA MEDELLIN, ALMACENAMIENTO, BOGOTA GLOBAL,
// BUENAVENTURA GLOBAL, CALI GLOBAL EXPRESS, BOGOTA CONENVIOS, MEDELLIN GLOBAL EXPRESS,
// BOGOTA CONALOG, TRANSIFRONT CUCUTA
// cliente_codigo: numérico/string (p.ej. "2551")

// PASO 2 - Origen y destino
// origen: nombre de ciudad (p.ej. "Bogotá")
// destino: nombre de ciudad (p.ej. "Medellín")
// origen, destino, lugar_recogida_contenedor (para exportación), tipo_carga,
// cantidad_mercancia, peso, valor_mercancia,
// producto, empaque, cantidad_vehiculos,
// clase_vehiculo, carroceria, minimo_modelo,
// tipo_flete, flete_conductor, flete_ministerio,
// tipo_tarifa, tarifa_cliente,
// cargue_cuenta_de, descargue_cuenta_de, seguro_cuenta_de,
// descripcion_mercancia, kit_seguridad, tipo_remesa_rndc,
// sub_cliente, tipo_mercancia (solo si el usuario lo menciona explícitamente)


// PASO 3 - Información de mercancía
// tipo_mercancia: tipo de producto/mercancía (p.ej. "ALIMENTOS")
// peso: peso en kg como número (p.ej. "500")
// cantidad_mercancia: cantidad como número (p.ej. "100")
// valor_mercancia: valor monetario como número (p.ej. "1000000")

// PASO 4 - Contenedor
// contenedor: uno de [SI, NO] (indica si requiere contenedor)

// PASO 5 - Modalidad internacional
// modalidad_internacional: uno de [OTM, DTA, DTAI, NACIONALIZADA]

// Reglas especiales:
// • Si el usuario solo menciona "OTM", "DTA", "DTAI", "NACIONALIZADA" o frases como
//   "ponle DTA", "mejor en nacionalizada", asume que se refiere a modalidad_internacional.
// • No vuelvas a escribir tipo_carga con esos valores a menos que el usuario lo indique claramente.

// PASO 6 - Costos
// tipvalrem_codigo: código o nombre del tipo de costo (buscar por nombre p.ej. "FLETE", "SEGURO", "DESCARGUE")
// valor_unitario: valor unitario numérico (p.ej. "50000")
// valor_costo_unitario: valor costo unitario numérico (p.ej. "45000")
// facturable: uno de [SI, NO]
// observacion_costo: texto libre con observaciones del costo
// aplica_flete: uno de [SI, NO]
// proveedor_codigo: código o nombre del proveedor

// Instrucciones:

// Si el usuario usa sinónimos o lenguaje natural, convierte al nombre de campo correcto y al valor exacto de las opciones de select.
// Ejemplos:
// • "viaje nacional" -> tipo_viaje = "NACIONAL"
// • "en dólares" / "USD" -> moneda = "DOLARES"
// • "no se requiere el uso del contenedor", "sin contenedor" -> contenedor = "NO"
// • "sí requiere contenedor", "usa contenedor" -> contenedor = "SI"
// • "valor unitario es 54000" -> valor_unitario = "54000"
// • "facturable es si" -> facturable = "SI"
// • "aplica flete no" -> aplica_flete = "NO"
// • "el costo es flete" -> tipvalrem_codigo = "FLETE"
// • "observacion del costo es transporte especial" -> observacion_costo = "transporte especial"
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
- centro_costo_despacho: "BOGOTA GLOBAL" (usa el valor EXACTO de la lista de centros de costo)
- cliente_codigo: "2551"

PASO 2 - Detalle del servicio:
- origen: "Bogotá"
- destino: "Medellín"

PASO 3 - Información de mercancía:
- tipo_mercancia: "ALIMENTOS"
- peso: "500"
- cantidad_mercancia: "100"
- valor_mercancia: "1000000"
- fecha_cargue, hora_cargue, remitente, destinatario, contacto
- promesa_servicio, promesa_servicio_hora
- documento_transporte, observacion_cargue

PASO 4 - Contenedor:
- contenedor: "NO"

PASO 5 - Internacional:
- modalidad_internacional: "OTM"

PASO 6 - Costos:
- tipvalrem_codigo: "FLETE"
- valor_unitario: "50000"
- valor_costo_unitario: "45000"
- facturable: "SI"
- observacion_costo: "Costo ejemplo"
- aplica_flete: "NO"
- proveedor_codigo: "EJEMPLO PROVEEDOR"

Cuando detectes la solicitud de llenado completo, llama a "rellenar" una vez por cada campo de la lista anterior.

Campos principales para detección normal:
- tipo_viaje, moneda, fuente_solicitud
- origen, destino, lugar_recogida_contenedor
- tipo_carga, cantidad_mercancia, peso, valor_mercancia
- producto, empaque, descripcion_mercancia
- cantidad_vehiculos, clase_vehiculo, carroceria
- minimo_modelo, tipo_flete, flete_conductor, flete_ministerio
- tipo_tarifa, tarifa_cliente
- cargue_cuenta_de, descargue_cuenta_de, seguro_cuenta_de
- kit_seguridad, tipo_remesa_rndc
- modalidad_internacional
- contenedor, modalidad_internacional
- tipvalrem_codigo, valor_unitario, valor_costo_unitario, facturable, observacion_costo, aplica_flete, proveedor_codigo

**CENTRO DE COSTO DESPACHO - Valores válidos:**
Para el campo centro_costo_despacho, SIEMPRE usa el valor EXACTO de esta lista (en MAYÚSCULAS tal cual):
TRANSLIDHER BARRANQUILLA, TRANSLIDHER BOGOTA, TRANSLIDHER UBATE, TRANSLIDHER CARTAGENA,
TRANSLIDHER BUENAVENTURA, TRANSLIDHER CALI, TRANSLIDHER SANTA MARTA, TRANSLIDHER PEREIRA,
TRANSLIDHER IPIALES, TRANSLIDHER MEDELLIN, CONALCA MANIZALEZ, CONALCA BUENAVENTURA,
CONALCA CALI, ALMACENAMIENTO MOSQUERA, OTM CONALCA CTG, OTM CONALCA SNMT,
CONALCA IPIALES, ALMACENAMIENTO CALI, CONALCA CARTAGENA, CONALCA UBATE, OTM CONALCA BTA,
BUN MAERSK DEDICADO, BOG MAERSK DEDICADO, CLO MAERSK DEDICADO, CONALCA PEREIRA,
CONALCA BARRANQUILLA, ARMENIA - BAVARIA, CONALCA PAGOS ANT, CONALCA SANTA MARTA,
CARTAGENA - BAVARIA, TUNJA - BAVARIA, SANTAMARTA - BAVARIA, YUMBO - BAVARIA,
CONALCA BOGOTA, CONALCA BUCARAMANGA, OTM CONALCA BAQ, OTM CONALCA BUN,
BUENAVENTURA PANTOS, CONALCA MEDELLIN, ALMACENAMIENTO, BOGOTA GLOBAL,
BUENAVENTURA GLOBAL, CALI GLOBAL EXPRESS, BOGOTA CONENVIOS, MEDELLIN GLOBAL EXPRESS,
BOGOTA CONALOG, TRANSIFRONT CUCUTA

Ejemplos de interpretación:
- "centro de costo es bogota global" → rellenar("centro_costo_despacho", "BOGOTA GLOBAL")
- "centro costo conalca bogota" → rellenar("centro_costo_despacho", "CONALCA BOGOTA")
- "centro de costo translidher cali" → rellenar("centro_costo_despacho", "TRANSLIDHER CALI")
- "centro costo maersk buenaventura" → rellenar("centro_costo_despacho", "BUN MAERSK DEDICADO")
- "despacho desde almacenamiento mosquera" → rellenar("centro_costo_despacho", "ALMACENAMIENTO MOSQUERA")
- "bavaria armenia" → rellenar("centro_costo_despacho", "ARMENIA - BAVARIA")

IMPORTANTE: Usa SIEMPRE las funciones antes de responder.
`;

/* ---------- Componente ---------- */
export default function ChatBox() {
  console.log('🎯 ChatBox v4.0 - REFACTORIZADO Y FUNCIONAL');

  const currentStepRef = useRef(1);
  useEffect(() => {
    const handleStepChange = step => { currentStepRef.current = step; };
    chatBus.on('step-changed', handleStepChange);
    return () => chatBus.off('step-changed', handleStepChange);
  }, []);
  
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
  const listeningRef = useRef(false);

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
  // const runChat = async (history) => {
  //   console.log('🚀 runChat iniciado');
  //   console.log('📜 Historia recibida:', history.length, 'mensajes');
    
  //   let workHistory = [
  //     { role: 'system', content: SYSTEM_PROMPT },
  //     ...history
  //   ];

  //   console.log('📤 Enviando a OpenAI API...');
  //   console.log('🔑 API Key presente:', !!import.meta.env.VITE_OPENAI_API_KEY);

  //   // Detectar si el último mensaje del usuario contiene datos específicos
  //   const lastUserMessage = history[history.length - 1]?.content?.toLowerCase() || '';
    
  //   // Detectar comando de llenado completo
  //   const isCompleteFormRequest = /\b(llena todo|completa el formulario|llena.*ejemplo|llena.*campos|formulario.*ejemplo|datos.*ejemplo|llena.*completo)\b/.test(lastUserMessage);
    
  //   // Detectar datos específicos
  //   const containsData = /\b(envío|envio|nacional|internacional|urbano|kilos?|kg|toneladas?|bogotá|medellín|cali|barranquilla|alimentos|textiles|pesos|dolares|usd|contenedor|carga|recogida|descripcion|descripción|cargue|remitente|destinatario|promesa|documento|contacto|correo|email|hora|modalidad|otm|dta|dtai|nacionalizada|acompanamiento|acompañamiento|motorizado|vehicular|cabina)\b/.test(lastUserMessage);
    
  //   const functionCallSetting = (isCompleteFormRequest || containsData) ? { name: 'rellenar' } : 'auto';
  //   console.log('🎯 Function call setting:', functionCallSetting, 'for message:', lastUserMessage);
  //   console.log('🔄 Complete form request:', isCompleteFormRequest, '| Contains data:', containsData);

  //   let response = await openai.chat.completions.create({
  //     model        : 'gpt-4o-mini',
  //     messages     : workHistory,
  //     functions    : FUNCTIONS,
  //     function_call: functionCallSetting
  //   });

  //   console.log('📥 Respuesta de OpenAI:', response);

  //   // ── mientras la IA siga pidiendo llamar a la función ─────────────────────
  //   while (response.choices?.[0]?.message?.function_call) {
  //     const assistantMsg = response.choices[0].message;

  //     /* ① añadimos la petición de función al histórico (solo para la IA,
  //       no se guarda en el estado que renderiza la UI) */
  //     workHistory.push(assistantMsg);

  //     const call = assistantMsg.function_call;
  //     try {
  //       // const args      = JSON.parse(call.arguments || '{}');
  //       // const field     = normalizeKey(args.field);
  //       // const rawValue  = String(args.value ?? '');
  //       // const value     = normalizeValue(field, rawValue);
        

  //       // const fieldRaw = args.field;
  //       // const valueRaw = String(args.value ?? '');






  //       // const args     = JSON.parse(call.arguments || '{}');
  //       // const fieldRaw = args.field;
  //       // const valueRaw = String(args.value ?? '');

  //       // let field = normalizeKey(fieldRaw);
  //       // let value = normalizeValue(field, valueRaw);

  //       // const MODALITY_VALUES = ['OTM', 'DTA', 'DTAI', 'NACIONALIZADA'];

  //       // if (field === 'tipo_carga' && MODALITY_VALUES.includes(value.toUpperCase())) {
  //       //   field = 'modalidad_internacional';
  //       // }

  //       const args     = JSON.parse(call.arguments || '{}');
  //       const fieldRaw = args.field;
  //       const valueRaw = String(args.value ?? '');

  //       let field = normalizeKey(fieldRaw);
  //       let value = normalizeValue(field, valueRaw);

  //       const MODALITY_VALUES = ['OTM', 'DTA', 'DTAI', 'NACIONALIZADA'];
  //       const ACC_VALUES      = ['MOTORIZADO', 'VEHICULAR', 'CABINA'];

  //       // Requests like "ponlo en DTA" must hit Step 5, not tipo_carga
  //       if (field === 'tipo_carga' && MODALITY_VALUES.includes(value.toUpperCase())) {
  //         field = 'modalidad_internacional';
  //       }

  //       // If the model insists on calling tipo_carga but the value is an ACC type,
  //       // force it into Step 6.
  //       if (field === 'tipo_carga' && ACC_VALUES.includes(value.toUpperCase())) {
  //         field = 'tipo_vehiculo_acom';
  //       }

  //       const activeStep = currentStepRef.current;
  //       const wantsStep6 = activeStep === 6 || rawClean.includes('acompanamiento');
  //       if (wantsStep6) {
  //         if (['cargue_cuenta_de', 'descargue_cuenta_de', 'seguro_cuenta_de'].includes(field)) {
  //           field = 'acompanamiento_cuenta_acom';
  //         }
  //         if (['valor_mercancia', 'tarifa_cliente'].includes(field)) {
  //           field = 'valor_acompanante_acom';
  //         }
  //         if (['tipo_carga', 'tipo_remesa_rndc'].includes(field) || ACC_VALUES.includes(value.toUpperCase())) {
  //           field = 'tipo_vehiculo_acom';
  //         }
  //       }

  //       // Same idea for the other ACC fields: if the raw field name clearly points
  //       // to accompaniment but the alias failed, fix it here.
        
        
  //       // const raw = String(fieldRaw || '').toLowerCase();
  //       // if (raw.includes('acompanamientocuent') && field !== 'acompanamiento_cuenta_acom') {
  //       //   field = 'acompanamiento_cuenta_acom';
  //       // }
  //       // if (raw.includes('acompanamientoval') && field !== 'valor_acompanante_acom') {
  //       //   field = 'valor_acompanante_acom';
  //       // }

  //       const rawOriginal = String(fieldRaw ?? '');
  //       const rawLower    = rawOriginal.toLowerCase();
  //       const rawClean    = rawLower.normalize('NFD').replace(/[\u0300-\u036f]/g, ''); // remove accents

  //       if (rawClean.includes('acompanamientocuent') && field !== 'acompanamiento_cuenta_acom') {
  //         field = 'acompanamiento_cuenta_acom';
  //       }
  //       if (rawClean.includes('acompanamientoval') && field !== 'valor_acompanante_acom') {
  //         field = 'valor_acompanante_acom';
  //       }


  //       chatBus.emit('fill-field', field, value);   // ← rellena el formulario

  //       /* ② confirmación interna hacia el modelo (role:function),
  //             tampoco se muestra en la UI                                 */
  //       workHistory.push({
  //         role   : 'function',
  //         name   : call.name,
  //         content: JSON.stringify({ field, value })
  //       });
  //     } catch (err) {
  //       console.error('Error parseando argumentos de function_call', err);
  //       workHistory.push({
  //         role   : 'function',
  //         name   : call?.name || 'rellenar',
  //         content: 'error'
  //       });
  //     }

  //     // nueva ronda
  //     response = await openai.chat.completions.create({
  //       model        : 'gpt-4o-mini',
  //       messages     : workHistory,
  //       functions    : FUNCTIONS,
  //       function_call: 'auto'
  //     });
  //   }

  //   // ── cuando ya no hay más llamadas, se muestra solo el mensaje final ──────
  //   const finalMsg = response.choices?.[0]?.message;
  //   if (finalMsg) pushMsg(finalMsg);
  // };

  // resources/js/components/SolicitudWizard/ChatBox.jsx
  const runChat = async (history) => {
    console.log('🚀 runChat iniciado');
    console.log('📜 Historia recibida:', history.length, 'mensajes');
    
    let workHistory = [
      { role: 'system', content: SYSTEM_PROMPT },
      ...history
    ];

    console.log('📤 Enviando a OpenAI API...');
    console.log('🔑 API Key presente:', !!import.meta.env.VITE_OPENAI_API_KEY);

    const lastUserMessage = history[history.length - 1]?.content?.toLowerCase() || '';
    const isCompleteFormRequest = /\b(llena todo|completa el formulario|llena.*ejemplo|llena.*campos|formulario.*ejemplo|datos.*ejemplo|llena.*completo)\b/.test(lastUserMessage);
    const containsData = /\b(envío|envio|nacional|internacional|urbano|kilos?|kg|toneladas?|bogotá|medellín|cali|barranquilla|alimentos|textiles|pesos|dolares|usd|contenedor|carga|recogida|descripcion|descripción|cargue|remitente|destinatario|promesa|documento|contacto|correo|email|hora|modalidad|otm|dta|dtai|nacionalizada|centro.?de.?costo|despacho|translidher|conalca|maersk|bavaria|global|almacenamiento|pantos|conenvios|conalog|transifront|costo|costos|unitario|facturable|aplica.?flete|proveedor|observacion.?costo|observación.?costo|valor.?unitario|valor.?costo)\b/.test(lastUserMessage);
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

    while (response.choices?.[0]?.message?.function_call) {
      const assistantMsg = response.choices[0].message;
      workHistory.push(assistantMsg);

      const call = assistantMsg.function_call;
      try {
        const args     = JSON.parse(call.arguments || '{}');
        const fieldRaw = args.field;
        const valueRaw = String(args.value ?? '');

        const rawOriginal = String(fieldRaw ?? '');
        const rawLower    = rawOriginal.toLowerCase();
        const rawClean    = rawLower.normalize('NFD').replace(/[\u0300-\u036f]/g, '');

        let field = normalizeKey(fieldRaw);
        let value = normalizeValue(field, valueRaw);

        const MODALITY_VALUES = ['OTM', 'DTA', 'DTAI', 'NACIONALIZADA'];

        if (field === 'tipo_carga' && MODALITY_VALUES.includes(value.toUpperCase())) {
          field = 'modalidad_internacional';
        }

        // Step 6 – Costos: remap ambiguous fields when on step 6
        const activeStep = currentStepRef.current;
        if (activeStep === 6 || rawClean.includes('costo')) {
          if (field === 'valor_mercancia' || field === 'valor') {
            field = 'valor_unitario';
          }
          if (rawClean.includes('costo unitario') || rawClean.includes('valorcostounitario')) {
            field = 'valor_costo_unitario';
          }
        }

        // Direct raw-name overrides for Step 6 fields
        if (rawClean.includes('valor_unitario') || rawClean.includes('valorunitario')) {
          field = 'valor_unitario';
        }
        if (rawClean.includes('valor_costo_unitario') || rawClean.includes('valorcostounitario')) {
          field = 'valor_costo_unitario';
        }
        if (rawClean.includes('observacion_costo') || rawClean.includes('observacioncosto')) {
          field = 'observacion_costo';
        }
        if (rawClean.includes('aplica_flete') || rawClean.includes('aplicaflete')) {
          field = 'aplica_flete';
        }
        if (rawClean.includes('tipvalrem') && field !== 'tipvalrem_codigo') {
          field = 'tipvalrem_codigo';
        }

        chatBus.emit('fill-field', field, value);

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

      response = await openai.chat.completions.create({
        model        : 'gpt-4o-mini',
        messages     : workHistory,
        functions    : FUNCTIONS,
        function_call: 'auto'
      });
    }

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

    const recognition = new SpeechRec();
    recognitionRef.current = recognition;
    listeningRef.current = true;

    recognition.lang = 'es-ES';
    recognition.continuous = true;         // keep mic open
    recognition.interimResults = true;     // needed to capture partials

    recognition.onstart = () => {
      setListening(true);
    };

    recognition.onresult = (event) => {
      let finalTranscript = '';
      for (let i = event.resultIndex; i < event.results.length; i++) {
        const result = event.results[i];
        if (result.isFinal) {
          finalTranscript += result[0].transcript + ' ';
        }
      }
      if (finalTranscript.trim()) {
        setUserInput(prev =>
          prev ? `${prev} ${finalTranscript}`.trim() : finalTranscript.trim()
        );
      }
    };

    recognition.onerror = (event) => {
      console.error('❌ Error en reconocimiento:', event.error);
      listeningRef.current = false;
      setListening(false);
    };

    recognition.onend = () => {
      if (listeningRef.current) {
        recognition.start();   // restart automatically until user stops
      } else {
        setListening(false);
      }
    };

    recognition.start();
  };

  const stopListening = () => {
    listeningRef.current = false;
    if (recognitionRef.current) {
      recognitionRef.current.stop();
    }
    setListening(false);
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

      {/* Messages Container - Expandido para ocupar más espacio */}
      <div className="flex-1 flex flex-col min-h-0">
        {/* Messages Area - Ocupa el espacio disponible */}
        <div className="flex-1 p-4 overflow-y-auto" style={{minHeight: '400px', maxHeight: 'calc(100vh - 300px)'}}>
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

        {/* Input Area - Siempre fijo en la parte inferior */}
        <div className="flex-shrink-0 border-t border-gray-200 p-4">
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
                    <path fillRule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 715 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clipRule="evenodd" />
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
    </div>
  );
}