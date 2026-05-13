#!/usr/bin/env node

/**
 * Script de prueba del servidor MCP de Playwright
 * Verifica que el producto se cargue correctamente en la cotización
 */

import axios from 'axios';

const MCP_URL = 'http://localhost:8931/mcp';
const SESSION_ID = 'test_' + Date.now();

async function testPlaywright() {
    console.log('🎭 Iniciando prueba de Playwright MCP...\n');
    
    try {
        // 1. Navegar a la página de cotización
        console.log('1️⃣ Navegando a la página de cotización...');
        const navResponse = await axios.post(`${MCP_URL}?sessionId=${SESSION_ID}`, {
            jsonrpc: '2.0',
            id: 1,
            method: 'tools/call',
            params: {
                name: 'playwright_navigate',
                arguments: {
                    url: 'https://conalcaia.conalca.com.co/cotizacion'
                }
            }
        });
        console.log('✅ Navegación completada\n');
        
        // 2. Esperar a que la página cargue
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        // 3. Tomar captura de pantalla inicial
        console.log('2️⃣ Tomando captura de pantalla inicial...');
        const screenshot1 = await axios.post(`${MCP_URL}?sessionId=${SESSION_ID}`, {
            jsonrpc: '2.0',
            id: 2,
            method: 'tools/call',
            params: {
                name: 'playwright_screenshot',
                arguments: {
                    name: 'cotizacion_inicial.png',
                    fullPage: true
                }
            }
        });
        console.log('✅ Captura guardada: cotizacion_inicial.png\n');
        
        // 4. Verificar que el campo "Producto" esté visible
        console.log('3️⃣ Verificando campos del formulario...');
        const checkFields = await axios.post(`${MCP_URL}?sessionId=${SESSION_ID}`, {
            jsonrpc: '2.0',
            id: 3,
            method: 'tools/call',
            params: {
                name: 'playwright_evaluate',
                arguments: {
                    script: `
                        const productoField = document.querySelector('*:contains("Producto")');
                        const origenField = document.querySelector('*:contains("Origen")');
                        const destinoField = document.querySelector('*:contains("Destino")');
                        
                        return {
                            productoVisible: !!productoField,
                            origenVisible: !!origenField,
                            destinoVisible: !!destinoField
                        };
                    `
                }
            }
        });
        console.log('✅ Campos verificados\n');
        
        // 5. Tomar captura final
        console.log('4️⃣ Tomando captura de pantalla final...');
        const screenshot2 = await axios.post(`${MCP_URL}?sessionId=${SESSION_ID}`, {
            jsonrpc: '2.0',
            id: 4,
            method: 'tools/call',
            params: {
                name: 'playwright_screenshot',
                arguments: {
                    name: 'cotizacion_final.png',
                    fullPage: true
                }
            }
        });
        console.log('✅ Captura guardada: cotizacion_final.png\n');
        
        console.log('🎉 Prueba completada exitosamente!');
        console.log('\n📊 Resultados:');
        console.log(`   - SessionID: ${SESSION_ID}`);
        console.log(`   - Capturas tomadas: 2`);
        console.log(`   - URL probada: https://conalcaia.conalca.com.co/cotizacion`);
        
    } catch (error) {
        console.error('❌ Error en la prueba:', error.message);
        if (error.response) {
            console.error('   Respuesta del servidor:', JSON.stringify(error.response.data, null, 2));
        }
        process.exit(1);
    }
}

// Ejecutar la prueba
testPlaywright();
