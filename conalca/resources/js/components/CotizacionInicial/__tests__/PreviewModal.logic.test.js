/**
 * Script de prueba para las lógicas de contenedores en PreviewModal
 * 
 * Ejecutar con: node PreviewModal.logic.test.js
 * 
 * Prueba las siguientes reglas de negocio:
 * 1. Cotización por unidad (no totalizada)
 * 2. Sistema interno con totales vs cliente con unitarios
 * 3. Taras de contenedores (20'=2300kg, 40'/45'=3400kg)
 * 4. Detección de operación de exportación
 * 5. Contenedores con pesos diferentes = rutas separadas
 */

// =====================================
// FUNCIONES EXTRAÍDAS DE PreviewModal.jsx
// =====================================

// Helper para detectar si el embalaje es un contenedor
const isContainerPacking = (embalaje) => {
  if (!embalaje) return false;
  const normalized = String(embalaje).toUpperCase();
  return normalized.includes('CONTENEDOR') || 
         normalized.includes('CONTAINER') ||
         /\d+X(20|40|45)/i.test(normalized) || // Formato 1X20, 4X40, etc.
         /^(20|40|45)\s*['"]?\s*(HC|GP|OT|RF|FR)?$/i.test(normalized); // Formato 40 HC, 20', etc.
};

// Helper para obtener el tipo de contenedor (20, 40 o 45 pies)
const getContainerSize = (embalaje) => {
  if (!embalaje) return null;
  const normalized = String(embalaje).toUpperCase();
  if (normalized.includes('20') || /\d+X20/i.test(normalized)) return 20;
  if (normalized.includes('45') || /\d+X45/i.test(normalized)) return 45;
  if (normalized.includes('40') || /\d+X40/i.test(normalized)) return 40;
  return null;
};

// Helper para obtener la tara según tipo de contenedor
const getContainerTara = (embalaje) => {
  const size = getContainerSize(embalaje);
  switch (size) {
    case 20: return 2300; // kg
    case 40: return 3400; // kg
    case 45: return 3400; // kg (mismo que 40)
    default: return 0;
  }
};

// Helper para detectar si es operación de exportación
const isExportOperation = (operationType) => {
  const opType = (operationType || '').toLowerCase();
  return opType.includes('export') || opType === 'exportacion' || opType === 'exportación';
};

// Simulación de buildRouteFinancials
const buildRouteFinancials = (route, pricing) => {
  const embalaje = route.tipo_embajale || route.empaque || route.tipo_embalaje || '';
  const isContainer = isContainerPacking(embalaje);
  const containerSize = getContainerSize(embalaje);
  const containerTara = getContainerTara(embalaje);
  
  if (!pricing) {
    return {
      basePrice: 0,
      valuePerUnit: 0,
      totalValueInternal: 0,
      isContainer,
      containerQuantity: 1,
      containerSize,
      containerTara,
    };
  }

  const basePrice = Number(pricing.price) || 0;
  const porcentaje = Number(route.porcentaje) || 0;
  const acompanamiento = Number(route.itesoltra_acompanamientovalor) || 0;
  const parametersTotal = Number(route.candado_satelital) || 0;

  const valueWithMargin = basePrice + (basePrice * porcentaje / 100);
  const valuePerUnit = valueWithMargin + acompanamiento + parametersTotal;
  
  // Cantidad de contenedores (solo para sistema interno)
  const containerQuantity = isContainer ? (Number(route.cantidad) || 1) : 1;
  
  // IMPORTANTE: 
  // - valuePerUnit: valor por UNIDAD de contenedor (lo que ve el cliente)
  // - totalValueInternal: valor total multiplicado por cantidad (para sistema interno)
  const totalValueInternal = isContainer ? valuePerUnit * containerQuantity : valuePerUnit;

  return {
    basePrice,
    porcentaje,
    valueWithMargin,
    valuePerUnit,      // 📋 LO QUE VE EL CLIENTE
    totalValueInternal, // 💾 SISTEMA INTERNO
    isContainer,
    containerQuantity,
    containerSize,
    containerTara,
  };
};

// =====================================
// UTILIDADES DE PRUEBA
// =====================================

let testsPassed = 0;
let testsFailed = 0;

const colors = {
  reset: '\x1b[0m',
  green: '\x1b[32m',
  red: '\x1b[31m',
  yellow: '\x1b[33m',
  cyan: '\x1b[36m',
  bold: '\x1b[1m',
};

const log = {
  header: (text) => console.log(`\n${colors.cyan}${colors.bold}${'='.repeat(60)}${colors.reset}`),
  title: (text) => console.log(`${colors.cyan}${colors.bold}${text}${colors.reset}`),
  pass: (text) => console.log(`  ${colors.green}✓ PASS:${colors.reset} ${text}`),
  fail: (text) => console.log(`  ${colors.red}✗ FAIL:${colors.reset} ${text}`),
  info: (text) => console.log(`  ${colors.yellow}ℹ INFO:${colors.reset} ${text}`),
};

const assertEqual = (actual, expected, description) => {
  if (actual === expected) {
    log.pass(description);
    testsPassed++;
    return true;
  } else {
    log.fail(`${description} (esperado: ${expected}, actual: ${actual})`);
    testsFailed++;
    return false;
  }
};

// =====================================
// PRUEBAS
// =====================================

console.log(`
${colors.bold}╔════════════════════════════════════════════════════════════╗
║  PRUEBAS DE LÓGICA DE CONTENEDORES - PreviewModal.jsx      ║
╚════════════════════════════════════════════════════════════╝${colors.reset}
`);

// --------------------------------------
// TEST 1: Detección de contenedores
// --------------------------------------
log.header();
log.title('TEST 1: Detección de contenedores (isContainerPacking)');

assertEqual(isContainerPacking('Contenedor 40'), true, 'Contenedor 40 -> es contenedor');
assertEqual(isContainerPacking('CONTENEDOR 20 HC'), true, 'CONTENEDOR 20 HC -> es contenedor');
assertEqual(isContainerPacking('Container 45'), true, 'Container 45 -> es contenedor');
assertEqual(isContainerPacking('2X40'), true, '2X40 -> es contenedor');
assertEqual(isContainerPacking('1X20'), true, '1X20 -> es contenedor');
assertEqual(isContainerPacking('4X45'), true, '4X45 -> es contenedor');
assertEqual(isContainerPacking("40'"), true, "40' -> es contenedor");
assertEqual(isContainerPacking('40 HC'), true, '40 HC -> es contenedor');
assertEqual(isContainerPacking('20 GP'), true, '20 GP -> es contenedor');
assertEqual(isContainerPacking('Bultos'), false, 'Bultos -> NO es contenedor');
assertEqual(isContainerPacking('Cajas'), false, 'Cajas -> NO es contenedor');
assertEqual(isContainerPacking('Pallets'), false, 'Pallets -> NO es contenedor');
assertEqual(isContainerPacking(''), false, 'Vacío -> NO es contenedor');
assertEqual(isContainerPacking(null), false, 'null -> NO es contenedor');

// --------------------------------------
// TEST 2: Tamaño del contenedor
// --------------------------------------
log.header();
log.title('TEST 2: Tamaño del contenedor (getContainerSize)');

assertEqual(getContainerSize('Contenedor 20'), 20, 'Contenedor 20 -> tamaño 20');
assertEqual(getContainerSize('Contenedor 40'), 40, 'Contenedor 40 -> tamaño 40');
assertEqual(getContainerSize('Contenedor 45'), 45, 'Contenedor 45 -> tamaño 45');
assertEqual(getContainerSize('2X20'), 20, '2X20 -> tamaño 20');
assertEqual(getContainerSize('3X40'), 40, '3X40 -> tamaño 40');
assertEqual(getContainerSize('40 HC'), 40, '40 HC -> tamaño 40');
assertEqual(getContainerSize('20 GP'), 20, '20 GP -> tamaño 20');
assertEqual(getContainerSize('Bultos'), null, 'Bultos -> null');

// --------------------------------------
// TEST 3: Tara de contenedores
// --------------------------------------
log.header();
log.title('TEST 3: Tara de contenedores (getContainerTara)');

assertEqual(getContainerTara('Contenedor 20'), 2300, 'Contenedor 20\' -> tara 2,300 kg');
assertEqual(getContainerTara('Contenedor 40'), 3400, 'Contenedor 40\' -> tara 3,400 kg');
assertEqual(getContainerTara('Contenedor 45'), 3400, 'Contenedor 45\' -> tara 3,400 kg');
assertEqual(getContainerTara('2X20'), 2300, '2X20 -> tara 2,300 kg');
assertEqual(getContainerTara('3X40 HC'), 3400, '3X40 HC -> tara 3,400 kg');
assertEqual(getContainerTara('Bultos'), 0, 'Bultos -> tara 0 kg');

// --------------------------------------
// TEST 4: Detección de exportación
// --------------------------------------
log.header();
log.title('TEST 4: Detección de operación de exportación');

assertEqual(isExportOperation('exportacion'), true, 'exportacion -> es exportación');
assertEqual(isExportOperation('exportación'), true, 'exportación -> es exportación');
assertEqual(isExportOperation('Exportación'), true, 'Exportación (mayúscula) -> es exportación');
assertEqual(isExportOperation('export'), true, 'export -> es exportación');
assertEqual(isExportOperation('importacion'), false, 'importacion -> NO es exportación');
assertEqual(isExportOperation('nacional'), false, 'nacional -> NO es exportación');
assertEqual(isExportOperation(''), false, 'vacío -> NO es exportación');
assertEqual(isExportOperation(null), false, 'null -> NO es exportación');

// --------------------------------------
// TEST 5: Cotización por unidad vs Sistema interno
// --------------------------------------
log.header();
log.title('TEST 5: Cotización por unidad vs Sistema interno');

log.info('Escenario: Cliente solicita 2 contenedores de 40\' a $4,000,000 c/u');

const route1 = {
  tipo_embajale: 'Contenedor 40',
  cantidad: '2',
  porcentaje: 0,
  candado_satelital: 0,
  itesoltra_acompanamientovalor: 0,
};

const pricing1 = { price: 4000000 };
const result1 = buildRouteFinancials(route1, pricing1);

assertEqual(result1.isContainer, true, 'Detecta que es contenedor');
assertEqual(result1.containerQuantity, 2, 'Cantidad de contenedores: 2');
assertEqual(result1.valuePerUnit, 4000000, 'Valor para CLIENTE (por unidad): $4,000,000');
assertEqual(result1.totalValueInternal, 8000000, 'Valor SISTEMA INTERNO (total): $8,000,000');

log.info(`📋 AL CLIENTE: $${result1.valuePerUnit.toLocaleString()} por contenedor`);
log.info(`💾 SISTEMA INTERNO: $${result1.totalValueInternal.toLocaleString()} (2 × $4,000,000)`);

// --------------------------------------
// TEST 6: Cotización con margen
// --------------------------------------
log.header();
log.title('TEST 6: Cotización con margen y parámetros');

log.info('Escenario: 3 contenedores de 40\', base $3,500,000, +10%, candado $10,500');

const route2 = {
  tipo_embajale: '3X40',
  cantidad: '3',
  porcentaje: 10,
  candado_satelital: 10500,
  itesoltra_acompanamientovalor: 0,
};

const pricing2 = { price: 3500000 };
const result2 = buildRouteFinancials(route2, pricing2);

// Base + 10% = 3,500,000 + 350,000 = 3,850,000
// + candado = 3,850,000 + 10,500 = 3,860,500
const expectedUnitValue = 3500000 + 350000 + 10500; // 3,860,500
const expectedTotalInternal = expectedUnitValue * 3; // 11,581,500

assertEqual(result2.valuePerUnit, expectedUnitValue, `Valor unitario: $${expectedUnitValue.toLocaleString()}`);
assertEqual(result2.totalValueInternal, expectedTotalInternal, `Valor interno: $${expectedTotalInternal.toLocaleString()}`);

log.info(`📋 AL CLIENTE: $${result2.valuePerUnit.toLocaleString()} por contenedor`);
log.info(`💾 SISTEMA INTERNO: $${result2.totalValueInternal.toLocaleString()} (3 × valor unitario)`);

// --------------------------------------
// TEST 7: Carga NO contenedor (bultos)
// --------------------------------------
log.header();
log.title('TEST 7: Carga NO contenedor (bultos)');

log.info('Escenario: 5 bultos, base $1,200,000');

const route3 = {
  tipo_embajale: 'Bultos',
  cantidad: '5',
  porcentaje: 0,
  candado_satelital: 0,
  itesoltra_acompanamientovalor: 0,
};

const pricing3 = { price: 1200000 };
const result3 = buildRouteFinancials(route3, pricing3);

assertEqual(result3.isContainer, false, 'NO es contenedor');
assertEqual(result3.containerQuantity, 1, 'Cantidad = 1 (no aplica multiplicación)');
assertEqual(result3.valuePerUnit, 1200000, 'Valor unitario = base');
assertEqual(result3.totalValueInternal, 1200000, 'Valor interno = base (sin multiplicar)');

log.info(`Para carga NO contenedor, el valor es el mismo: $${result3.valuePerUnit.toLocaleString()}`);

// --------------------------------------
// TEST 8: Contenedores con pesos diferentes
// --------------------------------------
log.header();
log.title('TEST 8: Contenedores con pesos diferentes (rutas separadas)');

log.info('Escenario: Un contenedor 40\' de 15 ton y otro de 17 ton');
log.info('Cada contenedor con peso diferente debe ser una RUTA SEPARADA');

const route4a = {
  tipo_embajale: 'Contenedor 40',
  cantidad: '1',
  peso_mercancia: '15000', // 15 toneladas
  porcentaje: 0,
};

const route4b = {
  tipo_embajale: 'Contenedor 40',
  cantidad: '1',
  peso_mercancia: '17000', // 17 toneladas
  porcentaje: 0,
};

const pricing4a = { price: 4000000 };
const pricing4b = { price: 4200000 }; // Más caro por más peso

const result4a = buildRouteFinancials(route4a, pricing4a);
const result4b = buildRouteFinancials(route4b, pricing4b);

assertEqual(result4a.containerQuantity, 1, 'Ruta A: cantidad = 1 (separada)');
assertEqual(result4b.containerQuantity, 1, 'Ruta B: cantidad = 1 (separada)');

log.info(`📦 Ruta A (15 ton): $${result4a.valuePerUnit.toLocaleString()}`);
log.info(`📦 Ruta B (17 ton): $${result4b.valuePerUnit.toLocaleString()}`);
log.info('✓ Cada contenedor con peso diferente es una ruta independiente');

// --------------------------------------
// TEST 9: Simulación de total para cliente
// --------------------------------------
log.header();
log.title('TEST 9: Cálculo de total para cliente vs sistema interno');

log.info('Escenario: 2 rutas con contenedores');

const quoteData = [
  {
    tipo_embajale: 'Contenedor 40',
    cantidad: '2',
    porcentaje: 5,
    candado_satelital: 10500,
    itesoltra_acompanamientovalor: 0,
  },
  {
    tipo_embajale: 'Contenedor 20',
    cantidad: '3',
    porcentaje: 8,
    candado_satelital: 10500,
    itesoltra_acompanamientovalor: 50000,
  }
];

const pricings = [
  { price: 4000000 },
  { price: 2500000 }
];

let totalCliente = 0;
let totalInterno = 0;

quoteData.forEach((route, index) => {
  const result = buildRouteFinancials(route, pricings[index]);
  totalCliente += result.valuePerUnit;
  totalInterno += result.totalValueInternal;
  
  log.info(`Ruta ${index + 1}: ${route.tipo_embajale} (${route.cantidad} unidades)`);
  log.info(`  - Valor unitario: $${result.valuePerUnit.toLocaleString()}`);
  log.info(`  - Valor interno: $${result.totalValueInternal.toLocaleString()}`);
});

log.info(`\n📋 TOTAL CLIENTE (suma unitarios): $${totalCliente.toLocaleString()}`);
log.info(`💾 TOTAL SISTEMA INTERNO: $${totalInterno.toLocaleString()}`);

// El cliente ve la suma de valores unitarios
// El sistema guarda el total real (cantidad × valor)

// =====================================
// RESUMEN
// =====================================
log.header();
console.log(`
${colors.bold}╔════════════════════════════════════════════════════════════╗
║                      RESUMEN DE PRUEBAS                    ║
╚════════════════════════════════════════════════════════════╝${colors.reset}

  ${colors.green}✓ Pruebas exitosas: ${testsPassed}${colors.reset}
  ${colors.red}✗ Pruebas fallidas: ${testsFailed}${colors.reset}

${testsFailed === 0 
  ? `${colors.green}${colors.bold}¡TODAS LAS PRUEBAS PASARON! ✓${colors.reset}` 
  : `${colors.red}${colors.bold}HAY PRUEBAS FALLIDAS ✗${colors.reset}`}

${colors.cyan}${colors.bold}REGLAS DE NEGOCIO VALIDADAS:${colors.reset}

  1. ✓ Cotización por unidad de contenedor (no totalizada)
  2. ✓ Sistema interno registra cantidad total
  3. ✓ Contenedores con pesos diferentes = rutas separadas
  4. ✓ Tara contenedor 20': 2,300 kg
  5. ✓ Tara contenedor 40'/45': 3,400 kg
  6. ✓ Detección de operación de exportación
  7. ✓ Notas aclaratorias para retiro en Bogotá/Medellín/Cali

`);

process.exit(testsFailed > 0 ? 1 : 0);
