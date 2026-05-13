#!/usr/bin/env node

/**
 * Script de prueba simple para verificar error 500
 * Uso: node test-error-500.js [url]
 */

import http from 'http';
import https from 'https';

const baseUrl = process.argv[2] || 'http://localhost:8000';
const url = new URL(baseUrl);

const client = url.protocol === 'https:' ? https : http;

console.log('\n=================================================');
console.log('VERIFICACIÓN DE ERROR 500 - CONALCA IA');
console.log('=================================================\n');
console.log(`→ Probando URL: ${baseUrl}\n`);

const options = {
    hostname: url.hostname,
    port: url.port || (url.protocol === 'https:' ? 443 : 80),
    path: url.pathname,
    method: 'GET',
    headers: {
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    }
};

const req = client.request(options, (res) => {
    console.log('=================================================');
    console.log('RESPUESTA HTTP');
    console.log('=================================================');
    console.log(`Status Code: ${res.statusCode}`);
    console.log(`Status Message: ${res.statusMessage}`);
    console.log('\nHeaders:');
    Object.entries(res.headers).forEach(([key, value]) => {
        console.log(`  ${key}: ${value}`);
    });
    console.log('\n=================================================');

    let body = '';
    
    res.on('data', (chunk) => {
        body += chunk;
    });

    res.on('end', () => {
        if (res.statusCode === 500) {
            console.log('\n✗ ERROR 500 DETECTADO\n');
            console.log('Body (primeros 1000 caracteres):');
            console.log('=================================================');
            console.log(body.substring(0, 1000));
            console.log('=================================================\n');
            
            // Intentar extraer mensaje de error
            const errorMatch = body.match(/<title>(.*?)<\/title>/i);
            if (errorMatch) {
                console.log(`Título: ${errorMatch[1]}\n`);
            }
            
            const msgMatch = body.match(/<div class="message"[^>]*>(.*?)<\/div>/is);
            if (msgMatch) {
                console.log(`Mensaje: ${msgMatch[1].replace(/<[^>]*>/g, '').trim()}\n`);
            }
            
        } else if (res.statusCode === 200) {
            console.log('\n✓ RESPUESTA EXITOSA (200 OK)\n');
            console.log(`Tamaño de respuesta: ${body.length} bytes\n`);
        } else {
            console.log(`\n→ Código de respuesta: ${res.statusCode}\n`);
        }
    });
});

req.on('error', (e) => {
    console.error('\n✗ ERROR DE CONEXIÓN:\n');
    console.error(e.message);
    console.error('\n=================================================\n');
    
    if (e.code === 'ECONNREFUSED') {
        console.log('→ El servidor no está respondiendo en el puerto especificado.');
        console.log('→ Asegúrate de que Laravel esté corriendo con:');
        console.log('   php artisan serve --host=0.0.0.0 --port=8000\n');
    }
    
    process.exit(1);
});

req.end();
