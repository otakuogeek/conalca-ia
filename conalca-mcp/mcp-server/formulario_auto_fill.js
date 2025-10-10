/**
 * 🚀 CONALCA AI - SISTEMA DE LLENADO AUTOMÁTICO DE FORMULARIOS
 * Integración con servidor MCP para autocompletar formularios de cotización
 * 
 * Fecha: Octubre 9, 2025
 * Versión: 2.0 - Refactorizada y Mejorada
 * Herramienta MCP: llenar_formulario (#23)
 */

class ConalcaAutoFill {
    constructor() {
        this.mcpServerUrl = 'http://127.0.0.1:18840/';
        this.debug = true;
        this.ultimaOrdenId = null;
        this.camposMapeados = 0;
        
        this.log('🚀 CONALCA AutoFill v2.0 inicializado');
        this.inicializarSistema();
    }
    
    log(mensaje, tipo = 'info') {
        if (!this.debug) return;
        
        const timestamp = new Date().toLocaleTimeString();
        const prefijos = {
            'info': '📋',
            'success': '✅',
            'error': '❌',
            'warning': '⚠️',
            'debug': '🔧'
        };
        
        console.log(`${prefijos[tipo]} [${timestamp}] CONALCA AutoFill: ${mensaje}`);
    }
    
    inicializarSistema() {
        // Detectar si estamos en la página de cotizaciones
        if (window.location.href.includes('quotes') || window.location.href.includes('cotizaciones')) {
            this.log('Página de cotizaciones detectada', 'success');
            this.configurarEventos();
            this.agregarBotonAutoLlenado();
        } else {
            this.log('No estamos en página de cotizaciones', 'warning');
        }
    }
    
    configurarEventos() {
        // Escuchar cuando se abre el modal de ayuda
        document.addEventListener('click', (e) => {
            if (e.target.textContent.includes('lenalo con ejemplos')) {
                this.log('Modal de ayuda detectado', 'debug');
                setTimeout(() => this.mejorarModalAyuda(), 100);
            }
        });
        
        // Detectar cambios en el formulario
        this.observarCambiosFormulario();
    }
    
    agregarBotonAutoLlenado() {
        // Agregar botón al modal o al formulario principal
        setTimeout(() => {
            const modal = document.querySelector('[role="dialog"]');
            if (modal) {
                this.agregarBotonAlModal(modal);
            } else {
                this.agregarBotonAlFormulario();
            }
        }, 1000);
    }
    
    agregarBotonAlModal(modal) {
        const contenidoModal = modal.querySelector('.modal-body, .p-6, [class*="p-"]');
        if (contenidoModal && !document.querySelector('#btn-auto-llenar')) {
            const botonHTML = `
                <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <h4 class="text-lg font-semibold text-blue-800 mb-2">🤖 Llenado Automático Inteligente</h4>
                    <p class="text-sm text-blue-600 mb-3">
                        Usa datos de órdenes existentes para llenar automáticamente el formulario.
                    </p>
                    <div class="flex gap-2 flex-wrap">
                        <button id="btn-auto-llenar" onclick="window.conalcaAutoFill.autoLlenarConOrden(31)" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            📋 Llenar con Orden 31
                        </button>
                        <button onclick="window.conalcaAutoFill.autoLlenarConOrden(32)" 
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            🏢 Llenar con Orden 32
                        </button>
                        <button onclick="window.conalcaAutoFill.autoLlenarConOrden(66)" 
                                class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            🚛 Llenar con Orden 66
                        </button>
                    </div>
                    <input type="number" id="orden-personalizada" placeholder="ID de orden personalizada" 
                           class="mt-2 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <button onclick="window.conalcaAutoFill.autoLlenarConOrdenPersonalizada()" 
                            class="mt-2 w-full bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        🎯 Llenar con Orden Personalizada
                    </button>
                </div>
            `;
            contenidoModal.insertAdjacentHTML('beforeend', botonHTML);
            this.log('Botones de auto-llenado agregados al modal', 'success');
        }
    }
    
    agregarBotonAlFormulario() {
        const formulario = document.querySelector('form, .form-container, [class*="form"]');
        if (formulario && !document.querySelector('#btn-auto-llenar-form')) {
            const botonHTML = `
                <div id="auto-fill-panel" class="fixed bottom-4 right-4 z-50 bg-white shadow-2xl rounded-xl border border-gray-200 p-4 max-w-sm">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-semibold text-gray-800">🤖 AutoFill</h3>
                        <button onclick="document.getElementById('auto-fill-panel').style.display='none'" 
                                class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <button id="btn-auto-llenar-form" onclick="window.conalcaAutoFill.autoLlenarConOrden(31)" 
                                class="bg-blue-500 text-white px-2 py-1 rounded text-xs">Orden 31</button>
                        <button onclick="window.conalcaAutoFill.autoLlenarConOrden(32)" 
                                class="bg-green-500 text-white px-2 py-1 rounded text-xs">Orden 32</button>
                        <button onclick="window.conalcaAutoFill.autoLlenarConOrden(66)" 
                                class="bg-purple-500 text-white px-2 py-1 rounded text-xs">Orden 66</button>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', botonHTML);
            this.log('Panel de auto-llenado agregado', 'success');
        }
    }
    
    async autoLlenarConOrden(ordenId) {
        this.log(`🎯 Iniciando auto-llenado con orden ${ordenId}`, 'info');
        this.ultimaOrdenId = ordenId;
        this.camposMapeados = 0;
        
        try {
            // 1. Obtener datos del servidor MCP
            const datosMCP = await this.llamarServidorMCP(ordenId);
            
            if (!datosMCP || !datosMCP.success) {
                throw new Error(`Error en servidor MCP: ${datosMCP?.error || 'Respuesta inválida'}`);
            }
            
            this.log(`📦 Datos recibidos del MCP para orden ${ordenId}`, 'success');
            
            // 2. Mapear campos del formulario
            await this.mapearCamposFormulario(datosMCP.campos_formulario);
            
            // 3. Mostrar resumen
            this.mostrarResumenLlenado(datosMCP);
            
            // 4. Hablar resultado si hay síntesis de voz disponible
            this.anunciarResultado(datosMCP);
            
        } catch (error) {
            this.log(`Error en auto-llenado: ${error.message}`, 'error');
            this.mostrarErrorUsuario(error.message);
        }
    }
    
    autoLlenarConOrdenPersonalizada() {
        const input = document.getElementById('orden-personalizada');
        const ordenId = parseInt(input.value);
        
        if (!ordenId || ordenId <= 0) {
            this.log('ID de orden inválido', 'error');
            alert('Por favor ingresa un ID de orden válido');
            return;
        }
        
        this.autoLlenarConOrden(ordenId);
    }
    
    async llamarServidorMCP(ordenId) {
        this.log(`🌐 Llamando servidor MCP en ${this.mcpServerUrl}`, 'debug');
        
        const payload = {
            jsonrpc: "2.0",
            id: `autofill-${Date.now()}`,
            method: "tools/call",
            params: {
                name: "llenar_formulario",
                arguments: {
                    orden_id: ordenId,
                    tipo_formulario: "cotizacion"
                }
            }
        };
        
        const response = await fetch(this.mcpServerUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload)
        });
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
        }
        
        const resultado = await response.json();
        
        if (resultado.error) {
            throw new Error(`Error MCP: ${resultado.error.message}`);
        }
        
        // Parsear el contenido JSON de la respuesta MCP
        const contenido = resultado.result.content[0].text;
        return JSON.parse(contenido);
    }
    
    async mapearCamposFormulario(camposFormulario) {
        this.log('🔧 Iniciando mapeo de campos del formulario', 'debug');
        
        const mapeoEstrategias = {
            // Estrategias para encontrar cada campo
            'tipo_viaje': [
                'select[wire\\:model\\.live*="tipo_viaje"]',
                'select[name*="tipo_viaje"]',
                'select[id*="tipo_viaje"]',
                'label:contains("Tipo de viaje") + select, label:contains("Tipo de viaje") ~ select'
            ],
            'moneda': [
                'select[wire\\:model\\.live*="moneda"]',
                'select[name*="moneda"]',
                'select[id*="moneda"]',
                'label:contains("Moneda") + select, label:contains("Moneda") ~ select'
            ],
            'fuente_solicitud': [
                'select[wire\\:model\\.live*="fuente"]',
                'select[name*="fuente"]',
                'select[id*="solicitud"]',
                'label:contains("Fuente") + select, label:contains("Fuente") ~ select'
            ],
            'tipo_operacion': [
                'select[wire\\:model\\.live*="operacion"]',
                'select[name*="operacion"]',
                'select[id*="operacion"]',
                'label:contains("operación") + select, label:contains("operación") ~ select'
            ],
            'condicion_despacho': [
                'input[wire\\:model\\.live*="despacho"]',
                'input[name*="despacho"]',
                'input[placeholder*="despacho"]',
                'select[wire\\:model\\.live*="despacho"]'
            ],
            'condicion_facturacion': [
                'input[wire\\:model\\.live*="facturacion"]',
                'input[name*="facturacion"]',
                'input[placeholder*="facturacion"]',
                'select[wire\\:model\\.live*="facturacion"]'
            ],
            'ciudad_facturacion': [
                'select[wire\\:model\\.live*="ciudad"]',
                'select[name*="ciudad"]',
                'select[id*="ciudad"]',
                'label:contains("Ciudad") + select, label:contains("Ciudad") ~ select'
            ],
            'vendedor': [
                'select[wire\\:model\\.live*="vendedor"]',
                'select[name*="vendedor"]',
                'select[id*="vendedor"]',
                'label:contains("Vendedor") + select, label:contains("Vendedor") ~ select'
            ],
            'centro_costo_despacho': [
                'input[wire\\:model\\.live*="centro"]',
                'input[name*="centro"]',
                'input[placeholder*="centro"]',
                'select[wire\\:model\\.live*="centro"]'
            ],
            'cliente': [
                'select[wire\\:model\\.live*="cliente"]',
                'select[name*="cliente"]',
                'select[id*="cliente"]',
                'label:contains("Cliente") + select, label:contains("Cliente") ~ select'
            ]
        };
        
        for (const [nombreCampo, valor] of Object.entries(camposFormulario)) {
            await this.llenarCampoConEstrategias(nombreCampo, valor, mapeoEstrategias[nombreCampo] || []);
        }
        
        this.log(`✅ Mapeo completado: ${this.camposMapeados} campos llenados`, 'success');
    }
    
    async llenarCampoConEstrategias(nombreCampo, valor, estrategias) {
        let campoEncontrado = false;
        
        for (const selector of estrategias) {
            try {
                const campo = document.querySelector(selector);
                if (campo) {
                    await this.llenarCampo(campo, valor, nombreCampo);
                    campoEncontrado = true;
                    break;
                }
            } catch (error) {
                // Continuar con la siguiente estrategia
                continue;
            }
        }
        
        if (!campoEncontrado) {
            // Estrategia de último recurso: buscar por texto cercano
            const campo = this.buscarCampoPorTexto(nombreCampo);
            if (campo) {
                await this.llenarCampo(campo, valor, nombreCampo);
                campoEncontrado = true;
            }
        }
        
        if (!campoEncontrado) {
            this.log(`❌ No se pudo encontrar el campo: ${nombreCampo}`, 'warning');
        }
    }
    
    buscarCampoPorTexto(nombreCampo) {
        // Mapeo de nombres de campo a texto que aparece en la UI
        const textosUI = {
            'tipo_viaje': ['tipo de viaje', 'tipo viaje'],
            'moneda': ['moneda'],
            'fuente_solicitud': ['fuente de la solicitud', 'fuente solicitud', 'fuente'],
            'tipo_operacion': ['tipo de operación', 'tipo operación', 'operación'],
            'condicion_despacho': ['condición de despacho', 'condición despacho', 'despacho'],
            'condicion_facturacion': ['condición de facturación', 'condición facturación', 'facturación'],
            'ciudad_facturacion': ['ciudad de facturación', 'ciudad facturación', 'ciudad'],
            'vendedor': ['vendedor'],
            'centro_costo_despacho': ['centro de costo', 'centro costo', 'centro'],
            'cliente': ['cliente']
        };
        
        const textos = textosUI[nombreCampo] || [nombreCampo];
        
        for (const texto of textos) {
            // Buscar labels que contengan el texto
            const labels = Array.from(document.querySelectorAll('label'));
            for (const label of labels) {
                if (label.textContent.toLowerCase().includes(texto.toLowerCase())) {
                    // Buscar el campo asociado
                    const campo = label.querySelector('select, input') || 
                                 document.querySelector(`#${label.getAttribute('for')}`) ||
                                 label.nextElementSibling?.querySelector('select, input') ||
                                 label.parentElement.querySelector('select, input');
                    
                    if (campo) {
                        return campo;
                    }
                }
            }
        }
        
        return null;
    }
    
    async llenarCampo(campo, valor, nombreCampo) {
        if (!campo || !valor) return;
        
        this.log(`🔧 Llenando ${nombreCampo}: ${valor}`, 'debug');
        
        if (campo.tagName.toLowerCase() === 'select') {
            await this.llenarSelect(campo, valor, nombreCampo);
        } else if (campo.tagName.toLowerCase() === 'input') {
            await this.llenarInput(campo, valor, nombreCampo);
        }
    }
    
    async llenarSelect(select, valor, nombreCampo) {
        // Estrategias para mapear valores a opciones de select
        const estrategiasMapeo = {
            'tipo_viaje': {
                'INTERCITY': ['intercity', 'inter city', 'intermunicipal'],
                'LOCAL': ['local', 'urbano'],
                'IMPORT/EXPORT': ['import', 'export', 'internacional']
            },
            'moneda': {
                'COP': ['cop', 'peso', 'colombiano', 'pesos']
            },
            'fuente_solicitud': {
                'INDIVIDUAL': ['individual', 'individual'],
                'COTIZACION_GRUPAL': ['grupo', 'grupal', 'group']
            },
            'tipo_operacion': {
                'GRANEL': ['granel', 'granel sólido', 'granel liquido'],
                'CONTENEDORES': ['contenedor', 'contenedores', 'fcl', 'lcl'],
                'CARGA_GENERAL': ['carga general', 'general'],
                'LIQUIDOS': ['líquido', 'liquidos', 'líquidos']
            },
            'centro_costo_despacho': {
                'BOGOTA': ['bogotá', 'bogota'],
                'MEDELLIN': ['medellín', 'medellin'],
                'CALI': ['cali'],
                'OTROS': ['otros', 'otro']
            }
        };
        
        const opciones = Array.from(select.options);
        let opcionSeleccionada = null;
        
        // Primero intentar mapeo específico
        const mapeo = estrategiasMapeo[nombreCampo];
        if (mapeo && mapeo[valor]) {
            for (const textoOpcion of mapeo[valor]) {
                opcionSeleccionada = opciones.find(opt => 
                    opt.text.toLowerCase().includes(textoOpcion.toLowerCase()) ||
                    opt.value.toLowerCase().includes(textoOpcion.toLowerCase())
                );
                if (opcionSeleccionada) break;
            }
        }
        
        // Si no hay mapeo específico, buscar por coincidencia directa
        if (!opcionSeleccionada) {
            opcionSeleccionada = opciones.find(opt => 
                opt.value.toLowerCase() === valor.toLowerCase() ||
                opt.text.toLowerCase() === valor.toLowerCase() ||
                opt.text.toLowerCase().includes(valor.toLowerCase()) ||
                opt.value.toLowerCase().includes(valor.toLowerCase())
            );
        }
        
        if (opcionSeleccionada) {
            select.value = opcionSeleccionada.value;
            this.dispararEventos(select);
            this.camposMapeados++;
            this.log(`✅ ${nombreCampo} = "${opcionSeleccionada.text}"`, 'success');
        } else {
            this.log(`⚠️ No se encontró opción para ${nombreCampo}: ${valor}`, 'warning');
        }
    }
    
    async llenarInput(input, valor, nombreCampo) {
        input.value = valor;
        this.dispararEventos(input);
        this.camposMapeados++;
        this.log(`✅ ${nombreCampo} = "${valor}"`, 'success');
    }
    
    dispararEventos(elemento) {
        // Disparar múltiples eventos para asegurar que Livewire detecte los cambios
        const eventos = ['input', 'change', 'blur'];
        
        eventos.forEach(tipoEvento => {
            const evento = new Event(tipoEvento, { bubbles: true });
            elemento.dispatchEvent(evento);
        });
        
        // Evento específico de Livewire si está disponible
        if (window.Livewire) {
            try {
                // Disparar actualización de Livewire
                elemento.dispatchEvent(new CustomEvent('livewire:update'));
            } catch (error) {
                // Silenciar errores de Livewire
            }
        }
        
        // Pequeña pausa para asegurar que los eventos se procesen
        return new Promise(resolve => setTimeout(resolve, 50));
    }
    
    mostrarResumenLlenado(datosMCP) {
        const resumen = `
🎯 RESUMEN DE AUTO-LLENADO - ORDEN ${this.ultimaOrdenId}

📋 Campos llenados: ${this.camposMapeados}
🚛 Viaje: ${datosMCP.valores_sugeridos.origen} → ${datosMCP.valores_sugeridos.destino}
📦 Mercancía: ${datosMCP.valores_sugeridos.tipo_mercancia}
⚖️ Peso: ${datosMCP.valores_sugeridos.peso_mercancia} kg
🚚 Vehículo: ${datosMCP.valores_sugeridos.vehiculo_requerido}

✅ Formulario listo para envío!
        `;
        
        console.log(resumen);
        
        // Mostrar notificación visual
        this.mostrarNotificacion(`✅ Formulario llenado con ${this.camposMapeados} campos de la orden ${this.ultimaOrdenId}`, 'success');
    }
    
    mostrarErrorUsuario(mensaje) {
        this.mostrarNotificacion(`❌ Error: ${mensaje}`, 'error');
    }
    
    mostrarNotificacion(mensaje, tipo = 'info') {
        // Crear notificación temporal
        const notificacion = document.createElement('div');
        notificacion.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg text-white font-medium max-w-sm ${
            tipo === 'success' ? 'bg-green-500' :
            tipo === 'error' ? 'bg-red-500' :
            tipo === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
        }`;
        notificacion.textContent = mensaje;
        
        document.body.appendChild(notificacion);
        
        // Animación de entrada
        notificacion.style.transform = 'translateX(100%)';
        notificacion.style.transition = 'transform 0.3s ease';
        setTimeout(() => notificacion.style.transform = 'translateX(0)', 10);
        
        // Remover después de 5 segundos
        setTimeout(() => {
            notificacion.style.transform = 'translateX(100%)';
            setTimeout(() => notificacion.remove(), 300);
        }, 5000);
    }
    
    anunciarResultado(datosMCP) {
        // Usar síntesis de voz si está disponible
        if ('speechSynthesis' in window) {
            const mensaje = `Formulario llenado automáticamente con ${this.camposMapeados} campos 
                           de la orden ${this.ultimaOrdenId}. Viaje de ${datosMCP.valores_sugeridos.origen} 
                           a ${datosMCP.valores_sugeridos.destino}.`;
            
            const utterance = new SpeechSynthesisUtterance(mensaje);
            utterance.rate = 0.8;
            utterance.pitch = 1;
            utterance.volume = 0.7;
            
            // Usar voz en español si está disponible
            const voices = speechSynthesis.getVoices();
            const spanishVoice = voices.find(voice => voice.lang.startsWith('es'));
            if (spanishVoice) {
                utterance.voice = spanishVoice;
            }
            
            speechSynthesis.speak(utterance);
        }
    }
    
    observarCambiosFormulario() {
        // Observer para detectar cuando se cargan nuevos formularios
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    const formularios = mutation.target.querySelectorAll('form, [class*="form"]');
                    if (formularios.length > 0 && !document.querySelector('#btn-auto-llenar')) {
                        this.log('Nuevo formulario detectado', 'debug');
                        setTimeout(() => this.agregarBotonAutoLlenado(), 500);
                    }
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Método para testing manual
    async testearConOrden(ordenId) {
        this.log(`🧪 MODO TEST - Probando con orden ${ordenId}`, 'debug');
        await this.autoLlenarConOrden(ordenId);
    }
    
    // Método para obtener información de depuración
    obtenerInfoDebug() {
        return {
            version: '2.0',
            ultimaOrden: this.ultimaOrdenId,
            camposMapeados: this.camposMapeados,
            url: window.location.href,
            formularios: document.querySelectorAll('form, [class*="form"]').length,
            selects: document.querySelectorAll('select').length,
            inputs: document.querySelectorAll('input').length
        };
    }
}

// 🚀 INICIALIZACIÓN AUTOMÁTICA
console.log('🔧 Cargando CONALCA AutoFill...');

// Esperar a que el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.conalcaAutoFill = new ConalcaAutoFill();
    });
} else {
    window.conalcaAutoFill = new ConalcaAutoFill();
}

// 📋 COMANDOS RÁPIDOS PARA CONSOLA
console.log(`
🚀 CONALCA AutoFill v2.0 - Comandos Disponibles:

Llenar formulario:
• window.conalcaAutoFill.autoLlenarConOrden(31)    // Orden individual
• window.conalcaAutoFill.autoLlenarConOrden(32)    // Orden grupal  
• window.conalcaAutoFill.autoLlenarConOrden(66)    // Otra orden

Testing:
• window.conalcaAutoFill.testearConOrden(31)       // Modo test
• window.conalcaAutoFill.obtenerInfoDebug()        // Info debug

Configuración:
• window.conalcaAutoFill.debug = false             // Desactivar logs
• window.conalcaAutoFill.mcpServerUrl = 'http://...' // Cambiar servidor
`);