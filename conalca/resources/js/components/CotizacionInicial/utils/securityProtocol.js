/**
 * Protocolo de Seguridad - Clasificación de riesgo y medidas según tipo de producto y valor declarado
 * Cobertura por subrogación CONALCA: Hasta $800.000.000 por unidad de carga por vehículo
 */

const COBERTURA_MAXIMA = 800_000_000;

// Keywords para Alto Riesgo Grupo 1 (tecnología, telecomunicaciones, medicamentos, etc.)
const HIGH_RISK_GROUP1_KEYWORDS = [
  'COMPUTADOR', 'LAPTOP', 'PORTATIL', 'CELULAR', 'SMARTPHONE', 'TABLET', 'IPAD',
  'USB', 'MOUSE', 'TECLADO', 'MONITOR', 'PANTALLA',
  'TELECOMUNICAC', 'CABLE', 'FIBRA OPTICA', 'ROUTER', 'SWITCH', 'ANTENA',
  'MEDICAMENT', 'FARMACEUT', 'VACUNA', 'SUERO',
  'LICOR', 'AGUARDIENTE', 'WHISKY', 'RON', 'VODKA', 'BEBIDA ESPIRITUOSA', 'CERVEZA',
  'CIGARR', 'TABACO', 'PUROS',
  'COBRE', 'MINERAL DE COBRE',
  'VIDEOJUEGO', 'SOFTWARE', 'CONSOLA',
  'PERFUME', 'PERFUMERIA', 'FRAGANCIA',
  'ZINC',
];

// Keywords para Alto Riesgo Grupo 2 (textiles, llantas, autopartes, confitería, etc.)
const HIGH_RISK_GROUP2_KEYWORDS = [
  'TEXTIL', 'TELA', 'TEJIDO', 'CONFECCION', 'ROPA', 'PRENDA', 'VESTIDO', 'CAMISETA', 'PANTALON',
  'LLANTA', 'NEUMATICO', 'CAUCHO VULCANIZ',
  'AUTOPARTE', 'REPUESTO', 'ACCESORIO VEHICUL',
  'LECHE EN POLVO', 'LACTEO',
  'CONFITE', 'DULCE', 'CHOCOLATE', 'CACAO', 'BOMBOM', 'GOMA DE MASCAR', 'CARAMELO', 'GALLETA',
  'LINEA BLANCA', 'NEVERA', 'LAVADORA', 'REFRIGERADOR', 'ESTUFA', 'HORNO', 'MICROONDAS',
  'JUGUETE',
  'CUIDADO PERSONAL', 'BELLEZA', 'COSMETICO', 'MAQUILLAJE', 'CHAMPU', 'SHAMPOO', 'JABON',
  'INSECTICIDA', 'PLAGUICIDA', 'FUMIGANTE',
  'POLIPROPILENO', 'POLIETILENO', 'PLASTICO',
];

// Keywords para Mercancía Química (Decreto 1609)
const CHEMICAL_KEYWORDS = [
  'QUIMIC', 'ACIDO', 'SOLVENTE', 'REACTIVO', 'SUSTANCIA PELIGROSA',
  'DECRETO 1609', 'MERCANC.*PELIGROS',
  'PETROLEO', 'HIDROCARBURO', 'GASOLINA', 'DIESEL', 'COMBUSTIBLE',
  'AMONIACO', 'CLORO', 'NITRATO', 'FOSFATO', 'SULFATO',
  'EXPLOSIVO', 'INFLAMABLE', 'RADIOACTIVO', 'CORROSIVO', 'OXIDANTE', 'TOXICO',
  'GAS COMPRIMIDO', 'GAS LICUADO',
];

/**
 * Clasifica el producto en su categoría de riesgo
 * @param {string} productName - Nombre o código del producto
 * @param {string} cargoType - Tipo de carga del cliente (dangerous, etc.)
 * @returns {'high_risk_1' | 'high_risk_2' | 'chemical' | 'low_risk'}
 */
export const classifyProduct = (productName, cargoType = '') => {
  const normalized = (productName || '').toUpperCase();
  const cargoNorm = (cargoType || '').toUpperCase();

  // Si el tipo de carga es "dangerous" o "peligrosa" → chemical
  if (cargoNorm === 'DANGEROUS' || cargoNorm === 'PELIGROSA') {
    return 'chemical';
  }

  // Check chemical first
  if (CHEMICAL_KEYWORDS.some(kw => {
    if (kw.includes('.*')) return new RegExp(kw, 'i').test(normalized);
    return normalized.includes(kw);
  })) {
    return 'chemical';
  }

  // Check high risk group 1
  if (HIGH_RISK_GROUP1_KEYWORDS.some(kw => normalized.includes(kw))) {
    return 'high_risk_1';
  }

  // Check high risk group 2
  if (HIGH_RISK_GROUP2_KEYWORDS.some(kw => normalized.includes(kw))) {
    return 'high_risk_2';
  }

  return 'low_risk';
};

/**
 * Determina las medidas de seguridad requeridas
 * @param {string} riskCategory - Categoría de riesgo
 * @param {number} valorDeclarado - Valor declarado de la mercancía
 * @returns {{ nacional: string[], urbano: string[], level: string, color: string, label: string }}
 */
export const getSecurityMeasures = (riskCategory, valorDeclarado) => {
  const val = Number(valorDeclarado) || 0;

  switch (riskCategory) {
    case 'high_risk_1':
      if (val <= 150_000_000) {
        return {
          nacional: ['GPS', 'Candado satelital'],
          urbano: ['Candado satelital', '1 Motorizado'],
          level: 'Nivel 1',
          color: 'red',
          label: 'ALTO RIESGO - Grupo 1',
        };
      }
      if (val <= 500_000_000) {
        return {
          nacional: ['GPS', 'Candado satelital', '1 Acompañante vehicular'],
          urbano: ['Candado satelital', '1 Motorizado'],
          level: 'Nivel 2',
          color: 'red',
          label: 'ALTO RIESGO - Grupo 1',
        };
      }
      if (val <= COBERTURA_MAXIMA) {
        return {
          nacional: ['GPS', 'Candado satelital', '2 Acompañantes vehiculares'],
          urbano: ['Candado satelital', '2 Motorizados'],
          level: 'Nivel 3',
          color: 'red',
          label: 'ALTO RIESGO - Grupo 1',
        };
      }
      return {
        nacional: ['GPS', 'Candado satelital', '2 Acompañantes vehiculares', '⚠ Requiere autorización especial'],
        urbano: ['Candado satelital', '2 Motorizados', '⚠ Requiere autorización especial'],
        level: 'Nivel 3+',
        color: 'red',
        label: 'ALTO RIESGO - Grupo 1 (excede cobertura)',
      };

    case 'high_risk_2':
      if (val <= 350_000_000) {
        return {
          nacional: ['GPS', 'Candado satelital'],
          urbano: ['Candado satelital', '1 Motorizado'],
          level: 'Nivel 1',
          color: 'orange',
          label: 'ALTO RIESGO - Grupo 2',
        };
      }
      if (val <= 500_000_000) {
        return {
          nacional: ['GPS', 'Candado satelital', '1 Acompañante vehicular'],
          urbano: ['Candado satelital', '1 Motorizado'],
          level: 'Nivel 2',
          color: 'orange',
          label: 'ALTO RIESGO - Grupo 2',
        };
      }
      if (val <= COBERTURA_MAXIMA) {
        return {
          nacional: ['GPS', 'Candado satelital', '2 Acompañantes vehiculares'],
          urbano: ['Candado satelital', '2 Motorizados'],
          level: 'Nivel 3',
          color: 'orange',
          label: 'ALTO RIESGO - Grupo 2',
        };
      }
      return {
        nacional: ['GPS', 'Candado satelital', '2 Acompañantes vehiculares', '⚠ Requiere autorización especial'],
        urbano: ['Candado satelital', '2 Motorizados', '⚠ Requiere autorización especial'],
        level: 'Nivel 3+',
        color: 'orange',
        label: 'ALTO RIESGO - Grupo 2 (excede cobertura)',
      };

    case 'chemical':
      if (val <= 500_000_000) {
        return {
          nacional: ['GPS'],
          urbano: ['No aplica'],
          level: 'Nivel 1',
          color: 'purple',
          label: 'MERCANCÍA QUÍMICA (Decreto 1609)',
        };
      }
      if (val <= COBERTURA_MAXIMA) {
        return {
          nacional: ['GPS', 'Candado satelital'],
          urbano: ['Candado satelital', '1 Motorizado'],
          level: 'Nivel 2',
          color: 'purple',
          label: 'MERCANCÍA QUÍMICA (Decreto 1609)',
        };
      }
      return {
        nacional: ['GPS', 'Candado satelital', '⚠ Requiere autorización especial'],
        urbano: ['Candado satelital', '1 Motorizado', '⚠ Requiere autorización especial'],
        level: 'Nivel 2+',
        color: 'purple',
        label: 'MERCANCÍA QUÍMICA (excede cobertura)',
      };

    case 'low_risk':
    default:
      if (val <= 700_000_000) {
        return {
          nacional: ['GPS'],
          urbano: ['No aplica'],
          level: 'Nivel 1',
          color: 'green',
          label: 'BAJO RIESGO',
        };
      }
      if (val <= COBERTURA_MAXIMA) {
        return {
          nacional: ['GPS', 'Candado satelital'],
          urbano: ['Candado satelital', '1 Motorizado'],
          level: 'Nivel 2',
          color: 'green',
          label: 'BAJO RIESGO',
        };
      }
      return {
        nacional: ['GPS', 'Candado satelital', '⚠ Requiere autorización especial'],
        urbano: ['Candado satelital', '1 Motorizado', '⚠ Requiere autorización especial'],
        level: 'Nivel 2+',
        color: 'green',
        label: 'BAJO RIESGO (excede cobertura)',
      };
  }
};

/**
 * Obtiene el protocolo de seguridad completo para una ruta
 * @param {Object} route - Datos de la ruta
 * @param {Object} clientData - Datos del cliente
 * @param {Function} parseValor - Función para parsear valor declarado
 * @returns {Object} Protocolo de seguridad completo
 */
export const getSecurityProtocol = (route, clientData, parseValor) => {
  const productName = route.tipo_producto || route.producto || '';
  const cargoType = clientData?.cargoType || '';
  const valorDeclarado = parseValor ? parseValor(route) : (Number(route.valor_declarado) || 0);

  const riskCategory = classifyProduct(productName, cargoType);
  const measures = getSecurityMeasures(riskCategory, valorDeclarado);

  return {
    riskCategory,
    valorDeclarado,
    exceedsCobertura: valorDeclarado > COBERTURA_MAXIMA,
    coberturaMaxima: COBERTURA_MAXIMA,
    ...measures,
  };
};
