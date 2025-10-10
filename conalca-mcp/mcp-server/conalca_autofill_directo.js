/**
 * 🎯 SCRIPT DE INTEGRACIÓN DIRECTA - CONALCA QUOTES
 * Script específico para la página de cotizaciones actual
 * Optimizado para el DOM y estructura existente
 */

// ===============================================
// 📋 CONFIGURACIÓN PARA PÁGINA ACTUAL
// ===============================================

const CONALCA_FORM_INTEGRATION = {
    // URL del servidor MCP
    mcpUrl: 'http://127.0.0.1:18840/',
    
    // Configuración de debug
    debug: true,
    
    // Selectores específicos de la página actual
    selectors: {
        // Paso 1 - Datos básicos
        tipo_viaje: 'select[wire\\:model\\.live*="tipo_viaje"], select[wire\\:model*="tipo_viaje"]',
        moneda: 'select[wire\\:model\\.live*="moneda"], select[wire\\:model*="moneda"]',
        fuente_solicitud: 'select[wire\\:model\\.live*="fuente"], select[wire\\:model*="fuente"]',
        tipo_operacion: 'select[wire\\:model\\.live*="operacion"], select[wire\\:model*="operacion"]',
        condicion_despacho: 'input[wire\\:model\\.live*="despacho"], input[wire\\:model*="despacho"], select[wire\\:model\\.live*="despacho"]',
        condicion_facturacion: 'input[wire\\:model\\.live*="facturacion"], input[wire\\:model*="facturacion"], select[wire\\:model\\.live*="facturacion"]',
        ciudad_facturacion: 'select[wire\\:model\\.live*="ciudad"], select[wire\\:model*="ciudad"]',
        vendedor: 'select[wire\\:model\\.live*="vendedor"], select[wire\\:model*="vendedor"]',
        centro_costo_despacho: 'input[wire\\:model\\.live*="centro"], input[wire\\:model*="centro"], select[wire\\:model\\.live*="centro"]',
        cliente: 'select[wire\\:model\\.live*="cliente"], select[wire\\:model*="cliente"]'
    },
    
    // Mapeo de valores específico para CONALCA
    valorMapping: {
        tipo_viaje: {
            'INTERCITY': ['intercity', 'intermunicipal', 'inter-municipal'],
            'LOCAL': ['local', 'urbano', 'municipal'],
            'IMPORT/EXPORT': ['import', 'export', 'internacional', 'importación', 'exportación']
        },
        fuente_solicitud: {
            'INDIVIDUAL': ['individual'],
            'COTIZACION_GRUPAL': ['grupo', 'grupal', 'cotización grupal']
        },
        tipo_operacion: {
            'GRANEL': ['granel', 'granel sólido', 'granel líquido'],
            'CONTENEDORES': ['contenedor', 'contenedores', 'fcl', 'lcl'],
            'CARGA_GENERAL': ['carga general', 'general'],
            'LIQUIDOS': ['líquido', 'líquidos']
        },
        centro_costo_despacho: {
            'BOGOTA': ['bogotá', 'bogota'],
            'MEDELLIN': ['medellín', 'medellin'],
            'CALI': ['cali'],
            'OTROS': ['otros', 'otro', 'otras ciudades']
        }
    }
};

// ===============================================
// 🚀 FUNCIÓN PRINCIPAL DE AUTO-LLENADO
// ===============================================

async function autoLlenarFormularioConalca(ordenId) {
    console.log(`🎯 [CONALCA] Iniciando auto-llenado con orden ${ordenId}`);
    
    try {
        // 1. Mostrar indicador de carga
        mostrarIndicadorCarga(true);
        
        // 2. Llamar servidor MCP
        const datos = await llamarMCPServidor(ordenId);
        
        // 3. Detectar y llenar campos
        const camposLlenados = await procesarCamposFormulario(datos.campos_formulario);
        
        // 4. Mostrar resultado
        mostrarResultadoLlenado(ordenId, camposLlenados, datos.valores_sugeridos);
        
        // 5. Anunciar por voz si está disponible
        anunciarResultado(ordenId, camposLlenados, datos.valores_sugeridos);
        
        return { success: true, camposLlenados, datos };
        
    } catch (error) {
        console.error('❌ [CONALCA] Error en auto-llenado:', error);
        mostrarError(error.message);
        return { success: false, error: error.message };
    } finally {
        mostrarIndicadorCarga(false);
    }
}

// ===============================================
// 🌐 COMUNICACIÓN CON SERVIDOR MCP
// ===============================================

async function llamarMCPServidor(ordenId) {
    console.log(`📡 [CONALCA] Conectando con MCP servidor...`);
    
    const payload = {
        jsonrpc: "2.0",
        id: `conalca-${Date.now()}`,
        method: "tools/call",
        params: {
            name: "llenar_formulario",
            arguments: {
                orden_id: parseInt(ordenId),
                tipo_formulario: "cotizacion"
            }
        }
    };
    
    const response = await fetch(CONALCA_FORM_INTEGRATION.mcpUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    
    if (!response.ok) {
        throw new Error(`Error de conexión: ${response.status} ${response.statusText}`);
    }
    
    const resultado = await response.json();
    
    if (resultado.error) {
        throw new Error(`Error MCP: ${resultado.error.message}`);
    }
    
    return JSON.parse(resultado.result.content[0].text);
}

// ===============================================
// 🔧 PROCESAMIENTO DE CAMPOS
// ===============================================

async function procesarCamposFormulario(camposFormulario) {
    console.log(`🔧 [CONALCA] Procesando ${Object.keys(camposFormulario).length} campos...`);
    
    let camposLlenados = 0;
    
    for (const [nombreCampo, valor] of Object.entries(camposFormulario)) {
        if (await llenarCampoConalca(nombreCampo, valor)) {
            camposLlenados++;
        }
        
        // Pausa pequeña para no sobrecargar el DOM
        await esperar(100);
    }
    
    console.log(`✅ [CONALCA] Completado: ${camposLlenados} campos llenados`);
    return camposLlenados;
}

async function llenarCampoConalca(nombreCampo, valor) {
    // 1. Buscar campo por selector específico
    let campo = document.querySelector(CONALCA_FORM_INTEGRATION.selectors[nombreCampo]);
    
    // 2. Si no se encuentra, buscar por atributos alternativos
    if (!campo) {
        campo = buscarCampoAlternativo(nombreCampo);
    }
    
    if (!campo) {
        console.warn(`⚠️ [CONALCA] Campo no encontrado: ${nombreCampo}`);
        return false;
    }
    
    console.log(`🔧 [CONALCA] Llenando ${nombreCampo} = "${valor}"`);
    
    // 3. Llenar según tipo de campo
    if (campo.tagName.toLowerCase() === 'select') {
        return await llenarSelectConalca(campo, valor, nombreCampo);
    } else {
        return await llenarInputConalca(campo, valor, nombreCampo);
    }
}

function buscarCampoAlternativo(nombreCampo) {
    // Estrategias alternativas de búsqueda
    const estrategias = [
        `[name*="${nombreCampo}"]`,
        `[id*="${nombreCampo}"]`,
        `[placeholder*="${nombreCampo.replace('_', ' ')}"]`,
        `[wire\\:model*="${nombreCampo}"]`
    ];
    
    for (const selector of estrategias) {
        const campo = document.querySelector(selector);
        if (campo) {
            console.log(`🔍 [CONALCA] Campo encontrado con selector alternativo: ${selector}`);
            return campo;
        }
    }
    
    return null;
}

async function llenarSelectConalca(select, valor, nombreCampo) {
    const opciones = Array.from(select.options);
    let opcionSeleccionada = null;
    
    // 1. Búsqueda por mapeo específico
    const mapeo = CONALCA_FORM_INTEGRATION.valorMapping[nombreCampo];
    if (mapeo && mapeo[valor]) {
        for (const textoOpcion of mapeo[valor]) {
            opcionSeleccionada = opciones.find(opt => 
                opt.text.toLowerCase().includes(textoOpcion.toLowerCase()) ||
                opt.value.toLowerCase().includes(textoOpcion.toLowerCase())
            );
            if (opcionSeleccionada) break;
        }
    }
    
    // 2. Búsqueda directa si no hay mapeo
    if (!opcionSeleccionada) {
        opcionSeleccionada = opciones.find(opt => 
            opt.value.toLowerCase() === valor.toLowerCase() ||
            opt.text.toLowerCase().includes(valor.toLowerCase()) ||
            valor.toLowerCase().includes(opt.text.toLowerCase())
        );
    }
    
    if (opcionSeleccionada && opcionSeleccionada.value !== "") {
        select.value = opcionSeleccionada.value;
        await dispararEventosLivewire(select);
        
        console.log(`✅ [CONALCA] ${nombreCampo} → "${opcionSeleccionada.text}"`);
        return true;
    } else {
        console.warn(`⚠️ [CONALCA] No se encontró opción para ${nombreCampo}: "${valor}"`);
        console.log(`📋 [CONALCA] Opciones disponibles:`, opciones.map(o => o.text));
        return false;
    }
}

async function llenarInputConalca(input, valor, nombreCampo) {
    input.value = valor;
    input.focus();
    await dispararEventosLivewire(input);
    
    console.log(`✅ [CONALCA] ${nombreCampo} → "${valor}"`);
    return true;
}

async function dispararEventosLivewire(elemento) {
    // Eventos para Livewire
    const eventos = [
        new Event('input', { bubbles: true }),
        new Event('change', { bubbles: true }),
        new Event('blur', { bubbles: true })
    ];
    
    for (const evento of eventos) {
        elemento.dispatchEvent(evento);
        await esperar(50);
    }
    
    // Evento específico de Livewire
    if (window.Livewire) {
        try {
            elemento.dispatchEvent(new CustomEvent('livewire:update', { bubbles: true }));
        } catch (e) {
            // Silenciar errores de Livewire
        }
    }
}

// ===============================================
// 🎨 INTERFAZ DE USUARIO
// ===============================================

function mostrarIndicadorCarga(mostrar) {
    let indicador = document.getElementById('conalca-loading');
    
    if (mostrar && !indicador) {
        indicador = document.createElement('div');
        indicador.id = 'conalca-loading';
        indicador.innerHTML = `
            <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                        background: rgba(0,0,0,0.8); color: white; padding: 20px; border-radius: 10px; 
                        z-index: 9999; text-align: center;">
                <div style="font-size: 18px; margin-bottom: 10px;">🤖 CONALCA AutoFill</div>
                <div style="font-size: 14px;">Llenando formulario automáticamente...</div>
                <div style="margin-top: 10px;">
                    <div style="width: 40px; height: 4px; background: #ddd; border-radius: 2px; margin: 0 auto; overflow: hidden;">
                        <div style="width: 100%; height: 100%; background: #007bff; animation: loading 1.5s infinite;"></div>
                    </div>
                </div>
            </div>
            <style>
                @keyframes loading {
                    0% { transform: translateX(-100%); }
                    100% { transform: translateX(100%); }
                }
            </style>
        `;
        document.body.appendChild(indicador);
    } else if (!mostrar && indicador) {
        indicador.remove();
    }
}

function mostrarResultadoLlenado(ordenId, camposLlenados, valoresSugeridos) {
    // Crear notificación de éxito
    const notificacion = document.createElement('div');
    notificacion.style.cssText = `
        position: fixed; top: 20px; right: 20px; z-index: 9999;
        background: linear-gradient(135deg, #4CAF50, #45a049);
        color: white; padding: 16px 20px; border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui;
        max-width: 350px; transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    
    notificacion.innerHTML = `
        <div style="display: flex; align-items: center; margin-bottom: 8px;">
            <span style="font-size: 20px; margin-right: 8px;">✅</span>
            <strong>Formulario Completado</strong>
        </div>
        <div style="font-size: 14px; opacity: 0.9;">
            <div>📋 Orden: ${ordenId}</div>
            <div>🔧 Campos: ${camposLlenados}</div>
            <div>🚛 Ruta: ${valoresSugeridos.origen} → ${valoresSugeridos.destino}</div>
        </div>
    `;
    
    document.body.appendChild(notificacion);
    
    // Animación de entrada
    setTimeout(() => notificacion.style.transform = 'translateX(0)', 100);
    
    // Remover después de 5 segundos
    setTimeout(() => {
        notificacion.style.transform = 'translateX(100%)';
        setTimeout(() => notificacion.remove(), 300);
    }, 5000);
}

function mostrarError(mensaje) {
    const error = document.createElement('div');
    error.style.cssText = `
        position: fixed; top: 20px; right: 20px; z-index: 9999;
        background: #f44336; color: white; padding: 16px 20px;
        border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        max-width: 350px;
    `;
    error.innerHTML = `<strong>❌ Error:</strong> ${mensaje}`;
    
    document.body.appendChild(error);
    setTimeout(() => error.remove(), 5000);
}

function anunciarResultado(ordenId, camposLlenados, valoresSugeridos) {
    if ('speechSynthesis' in window) {
        const mensaje = `Formulario llenado automáticamente con ${camposLlenados} campos de la orden ${ordenId}. 
                        Viaje de ${valoresSugeridos.origen} a ${valoresSugeridos.destino}.`;
        
        const utterance = new SpeechSynthesisUtterance(mensaje);
        utterance.rate = 0.8;
        utterance.volume = 0.7;
        
        // Buscar voz en español
        speechSynthesis.getVoices().forEach(voice => {
            if (voice.lang.startsWith('es')) {
                utterance.voice = voice;
            }
        });
        
        speechSynthesis.speak(utterance);
    }
}

// ===============================================
// 🛠️ UTILIDADES
// ===============================================

function esperar(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// ===============================================
// 🚀 INICIALIZACIÓN Y COMANDOS GLOBALES
// ===============================================

// Comandos globales disponibles
window.CONALCA_AutoFill = {
    // Comando principal
    llenar: autoLlenarFormularioConalca,
    
    // Comandos específicos
    orden31: () => autoLlenarFormularioConalca(31),
    orden32: () => autoLlenarFormularioConalca(32),
    orden66: () => autoLlenarFormularioConalca(66),
    
    // Configuración
    config: CONALCA_FORM_INTEGRATION,
    
    // Debug
    debug: (ordenId = 31) => {
        CONALCA_FORM_INTEGRATION.debug = true;
        return autoLlenarFormularioConalca(ordenId);
    }
};

// ===============================================
// 📋 INSTRUCCIONES DE USO
// ===============================================

console.log(`
🚀 CONALCA AutoFill - Sistema de Auto-llenado Activado

📋 COMANDOS DISPONIBLES:

Llenar formulario:
• CONALCA_AutoFill.orden31()    // Orden individual (Funza → Bogotá)
• CONALCA_AutoFill.orden32()    // Orden grupal (Medellín → Bogotá)  
• CONALCA_AutoFill.orden66()    // Orden de prueba
• CONALCA_AutoFill.llenar(ID)   // Cualquier orden por ID

Debug y configuración:
• CONALCA_AutoFill.debug(31)    // Modo debug con orden 31
• CONALCA_AutoFill.config       // Ver configuración actual

🎯 EJEMPLO DE USO:
   CONALCA_AutoFill.orden31()   // Ejecutar desde consola

🔧 ESTADO: Sistema listo para usar
📡 SERVIDOR: ${CONALCA_FORM_INTEGRATION.mcpUrl}
`);

// Auto-agregar botones si es la página de cotizaciones
if (window.location.href.includes('quotes') || window.location.href.includes('cotizaciones')) {
    console.log('📍 Página de cotizaciones detectada - Agregando botones automáticamente...');
    
    setTimeout(() => {
        // Buscar lugar para agregar botones
        const contenedorFormulario = document.querySelector('form, .card, .panel, [class*="form"]');
        
        if (contenedorFormulario) {
            const botonesHTML = `
                <div id="conalca-autofill-buttons" style="
                    position: fixed; bottom: 20px; right: 20px; z-index: 1000;
                    background: white; padding: 15px; border-radius: 12px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    border: 1px solid #e0e0e0;
                ">
                    <div style="font-weight: bold; margin-bottom: 10px; color: #333;">
                        🤖 AutoFill CONALCA
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button onclick="CONALCA_AutoFill.orden31()" 
                                style="background: #007bff; color: white; border: none; 
                                       padding: 8px 12px; border-radius: 6px; cursor: pointer;
                                       font-size: 12px;">
                            📋 Orden 31
                        </button>
                        <button onclick="CONALCA_AutoFill.orden32()" 
                                style="background: #28a745; color: white; border: none; 
                                       padding: 8px 12px; border-radius: 6px; cursor: pointer;
                                       font-size: 12px;">
                            🏢 Orden 32
                        </button>
                        <button onclick="CONALCA_AutoFill.orden66()" 
                                style="background: #6f42c1; color: white; border: none; 
                                       padding: 8px 12px; border-radius: 6px; cursor: pointer;
                                       font-size: 12px;">
                            🚛 Orden 66
                        </button>
                    </div>
                    <button onclick="document.getElementById('conalca-autofill-buttons').style.display='none'" 
                            style="position: absolute; top: 5px; right: 8px; background: none; 
                                   border: none; color: #999; cursor: pointer; font-size: 16px;">
                        ×
                    </button>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', botonesHTML);
            console.log('✅ Botones de auto-llenado agregados');
        }
    }, 2000);
}