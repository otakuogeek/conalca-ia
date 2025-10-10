// 🎯 SOLUCIÓN ESPECÍFICA PARA LA PÁGINA ACTUAL DE CONALCA
// Script optimizado para el DOM exacto que vemos en la captura

console.log('🚀 Iniciando CONALCA AutoFill específico...');

// Configuración específica
const CONFIG = {
    mcpUrl: 'http://127.0.0.1:18840/',
    debug: true
};

// Función principal simplificada
async function llenarFormularioAhora(ordenId = 31) {
    console.log(`🎯 Llenando formulario con orden ${ordenId}...`);
    
    try {
        // 1. Obtener datos del MCP
        const response = await fetch(CONFIG.mcpUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                jsonrpc: "2.0",
                id: "test",
                method: "tools/call",
                params: {
                    name: "llenar_formulario",
                    arguments: { orden_id: ordenId }
                }
            })
        });
        
        const resultado = await response.json();
        const datos = JSON.parse(resultado.result.content[0].text);
        
        console.log('📦 Datos recibidos:', datos.campos_formulario);
        
        // 2. Mapeo directo a los campos visibles
        const campos = {
            // Buscar por wire:model exacto
            tipo_viaje: document.querySelector('[wire\\:model*="tipo_viaje"]') || document.querySelector('select:first-of-type'),
            moneda: document.querySelector('[wire\\:model*="moneda"]') || document.querySelectorAll('select')[1],
            fuente_solicitud: document.querySelector('[wire\\:model*="fuente"]') || document.querySelectorAll('select')[2],
            tipo_operacion: document.querySelector('[wire\\:model*="operacion"]') || document.querySelectorAll('select')[3],
            condicion_despacho: document.querySelector('[wire\\:model*="despacho"]') || document.querySelector('input[placeholder*="despacho"]'),
            condicion_facturacion: document.querySelector('[wire\\:model*="facturacion"]') || document.querySelector('input[placeholder*="facturacion"]'),
            ciudad_facturacion: document.querySelector('[wire\\:model*="ciudad"]') || document.querySelector('select[wire\\:model*="ADMINISTRACION"]'),
            vendedor: document.querySelector('[wire\\:model*="vendedor"]') || document.querySelector('select:has([selected])'),
            centro_costo_despacho: document.querySelector('[wire\\:model*="centro"]') || document.querySelector('input[placeholder*="centro"]'),
            cliente: document.querySelector('[wire\\:model*="cliente"]') || document.querySelectorAll('select')[6]
        };
        
        console.log('🔍 Campos detectados:', Object.keys(campos).filter(k => campos[k]));
        
        // 3. Llenar campos uno por uno
        let llenados = 0;
        
        for (const [nombre, valor] of Object.entries(datos.campos_formulario)) {
            const campo = campos[nombre];
            if (campo && await llenarCampoEspecifico(campo, valor, nombre)) {
                llenados++;
            }
        }
        
        // 4. Resultado
        console.log(`✅ Completado: ${llenados} campos llenados`);
        mostrarNotificacion(`✅ Formulario llenado: ${llenados} campos completados con orden ${ordenId}`);
        
        return { success: true, llenados, datos };
        
    } catch (error) {
        console.error('❌ Error:', error.message);
        mostrarNotificacion(`❌ Error: ${error.message}`, 'error');
        return { success: false, error: error.message };
    }
}

// Función para llenar campo específico
async function llenarCampoEspecifico(elemento, valor, nombre) {
    if (!elemento) {
        console.warn(`⚠️ Campo ${nombre} no encontrado`);
        return false;
    }
    
    console.log(`🔧 Llenando ${nombre} = "${valor}"`);
    
    if (elemento.tagName === 'SELECT') {
        // Para selects, buscar la mejor opción
        const opciones = Array.from(elemento.options);
        
        // Mapeo específico de valores
        const mapeoValores = {
            'INTERCITY': ['NACIONAL'],
            'COP': ['PESOS'],
            'INDIVIDUAL': ['TELEFONO DESPACHO'],
            'GRANEL': ['DISTRIBUCION'],
            'ENTREGA_INMEDIATA': ['Urgente'],
            'CONTADO': ['A la entrega'],
            'bogota': ['ADMINISTRACION BOGOTA'],
            'CONALCA': ['JOHANNA MARCELA HERNANDEZ'],
            'OTROS': ['TRANSLIDHER BOGOTA'],
            'Cliente por asignar': ['AIR LOGISTICS R&R SAS']
        };
        
        let opcionSeleccionada = null;
        
        // Buscar por mapeo específico
        if (mapeoValores[valor]) {
            for (const textoOpcion of mapeoValores[valor]) {
                opcionSeleccionada = opciones.find(opt => 
                    opt.text.includes(textoOpcion) || opt.value.includes(textoOpcion)
                );
                if (opcionSeleccionada) break;
            }
        }
        
        // Buscar directamente
        if (!opcionSeleccionada) {
            opcionSeleccionada = opciones.find(opt => 
                opt.text.toLowerCase().includes(valor.toLowerCase()) ||
                opt.value.toLowerCase().includes(valor.toLowerCase())
            );
        }
        
        if (opcionSeleccionada && opcionSeleccionada.value) {
            elemento.value = opcionSeleccionada.value;
            elemento.dispatchEvent(new Event('change', { bubbles: true }));
            elemento.dispatchEvent(new Event('input', { bubbles: true }));
            console.log(`✅ ${nombre} → "${opcionSeleccionada.text}"`);
            return true;
        } else {
            console.warn(`⚠️ No se encontró opción para ${nombre}: "${valor}"`);
            console.log(`Opciones disponibles:`, opciones.map(o => o.text).filter(t => t.trim()));
            return false;
        }
    } else {
        // Para inputs
        elemento.value = valor;
        elemento.dispatchEvent(new Event('input', { bubbles: true }));
        elemento.dispatchEvent(new Event('change', { bubbles: true }));
        console.log(`✅ ${nombre} → "${valor}"`);
        return true;
    }
}

// Función para mostrar notificación
function mostrarNotificacion(mensaje, tipo = 'success') {
    const notif = document.createElement('div');
    notif.style.cssText = `
        position: fixed; top: 20px; right: 20px; z-index: 10000;
        background: ${tipo === 'error' ? '#dc3545' : '#28a745'};
        color: white; padding: 15px 20px; border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        font-weight: bold; max-width: 400px;
        animation: slideIn 0.3s ease;
    `;
    notif.innerHTML = mensaje;
    
    // Agregar animación CSS
    if (!document.querySelector('#notif-style')) {
        const style = document.createElement('style');
        style.id = 'notif-style';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); }
                to { transform: translateX(0); }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(notif);
    setTimeout(() => notif.remove(), 5000);
}

// Agregar botón directo al modal
function agregarBotonAlModal() {
    const modal = document.querySelector('[role="dialog"]') || document.querySelector('.modal-content') || document.querySelector('.fixed');
    if (modal && !document.querySelector('#btn-autofill-directo')) {
        const boton = document.createElement('button');
        boton.id = 'btn-autofill-directo';
        boton.innerHTML = '🤖 AUTO-LLENAR FORMULARIO';
        boton.style.cssText = `
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white; border: none; padding: 12px 20px;
            border-radius: 8px; font-weight: bold; cursor: pointer;
            margin: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: all 0.2s ease;
        `;
        boton.onmouseover = () => boton.style.transform = 'scale(1.05)';
        boton.onmouseout = () => boton.style.transform = 'scale(1)';
        boton.onclick = () => llenarFormularioAhora(31);
        
        modal.appendChild(boton);
        console.log('✅ Botón agregado al modal');
    }
}

// Comandos globales simplificados
window.ConalcaQuick = {
    llenar: llenarFormularioAhora,
    orden31: () => llenarFormularioAhora(31),
    orden32: () => llenarFormularioAhora(32)
};

// Inicialización
console.log(`
🚀 CONALCA AutoFill ESPECÍFICO ACTIVADO

📋 COMANDOS INMEDIATOS:
• ConalcaQuick.orden31()    // Llenar con orden 31
• ConalcaQuick.orden32()    // Llenar con orden 32
• ConalcaQuick.llenar(66)   // Cualquier orden

🎯 EJECUTAR AHORA: ConalcaQuick.orden31()
`);

// Agregar botón automáticamente
setTimeout(agregarBotonAlModal, 1000);

// Ejecutar inmediatamente si se desea
// ConalcaQuick.orden31();