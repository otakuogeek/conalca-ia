# 🎯 SOLUCIÓN LISTA - AUTO-LLENADO FORMULARIO CONALCA

**PROBLEMA RESUELTO:** El formulario no se llena automáticamente  
**SOLUCIÓN:** Script JavaScript que integra con servidor MCP  
**ESTADO:** ✅ **FUNCIONANDO Y PROBADO**

---

## 🚀 IMPLEMENTACIÓN INMEDIATA

### Opción 1: Ejecutar Desde Consola (INMEDIATO)

**Paso 1:** Abrir la página de cotizaciones de CONALCA
```
https://conalca.conalca.com.co/quotes
```

**Paso 2:** Abrir la consola del navegador (F12)

**Paso 3:** Copiar y pegar este script:

```javascript
// COPIAR TODO ESTE CÓDIGO Y PEGARLO EN LA CONSOLA

// ===============================================
// 🚀 CONALCA AUTOFILL - VERSIÓN DIRECTA
// ===============================================

const CONALCA_FORM_CONFIG = {
    mcpUrl: 'http://127.0.0.1:18840/',
    valorMapping: {
        tipo_viaje: {
            'INTERCITY': ['intercity', 'inter city', 'intermunicipal'],
            'LOCAL': ['local', 'urbano'],
            'IMPORT/EXPORT': ['import', 'export', 'internacional']
        },
        fuente_solicitud: {
            'INDIVIDUAL': ['individual'],
            'COTIZACION_GRUPAL': ['grupo', 'grupal', 'cotización grupal']
        },
        tipo_operacion: {
            'GRANEL': ['granel', 'granel sólido'],
            'CONTENEDORES': ['contenedor', 'contenedores', 'fcl', 'lcl'],
            'CARGA_GENERAL': ['carga general', 'general'],
            'LIQUIDOS': ['líquido', 'líquidos']
        }
    }
};

async function llenarFormularioConalca(ordenId) {
    console.log(`🎯 Llenando formulario con orden ${ordenId}...`);
    
    try {
        // 1. Llamar servidor MCP
        const payload = {
            jsonrpc: "2.0",
            id: `test-${Date.now()}`,
            method: "tools/call",
            params: {
                name: "llenar_formulario",
                arguments: { orden_id: parseInt(ordenId) }
            }
        };
        
        const response = await fetch(CONALCA_FORM_CONFIG.mcpUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const resultado = await response.json();
        const datos = JSON.parse(resultado.result.content[0].text);
        
        console.log('📦 Datos recibidos:', datos);
        
        // 2. Llenar cada campo
        let camposLlenados = 0;
        
        for (const [campo, valor] of Object.entries(datos.campos_formulario)) {
            if (await llenarCampo(campo, valor)) {
                camposLlenados++;
            }
        }
        
        // 3. Mostrar resultado
        console.log(`✅ Completado: ${camposLlenados} campos llenados`);
        alert(`✅ Formulario llenado con ${camposLlenados} campos de la orden ${ordenId}\n\n🚛 Ruta: ${datos.valores_sugeridos.origen} → ${datos.valores_sugeridos.destino}\n📦 Mercancía: ${datos.valores_sugeridos.tipo_mercancia}`);
        
        return datos;
        
    } catch (error) {
        console.error('❌ Error:', error);
        alert(`❌ Error: ${error.message}`);
    }
}

async function llenarCampo(nombreCampo, valor) {
    // Buscar campo por múltiples estrategias
    const selectores = [
        `select[wire\\:model*="${nombreCampo}"]`,
        `input[wire\\:model*="${nombreCampo}"]`,
        `select[name*="${nombreCampo}"]`,
        `input[name*="${nombreCampo}"]`,
        `[id*="${nombreCampo}"]`
    ];
    
    let campo = null;
    for (const selector of selectores) {
        campo = document.querySelector(selector);
        if (campo) break;
    }
    
    if (!campo) {
        console.warn(`⚠️ Campo no encontrado: ${nombreCampo}`);
        return false;
    }
    
    // Llenar según tipo
    if (campo.tagName.toLowerCase() === 'select') {
        return llenarSelect(campo, valor, nombreCampo);
    } else {
        campo.value = valor;
        ['input', 'change', 'blur'].forEach(evento => {
            campo.dispatchEvent(new Event(evento, { bubbles: true }));
        });
        console.log(`✅ ${nombreCampo} = "${valor}"`);
        return true;
    }
}

function llenarSelect(select, valor, nombreCampo) {
    const opciones = Array.from(select.options);
    let opcionSeleccionada = null;
    
    // Buscar por mapeo específico
    const mapeo = CONALCA_FORM_CONFIG.valorMapping[nombreCampo];
    if (mapeo && mapeo[valor]) {
        for (const textoOpcion of mapeo[valor]) {
            opcionSeleccionada = opciones.find(opt => 
                opt.text.toLowerCase().includes(textoOpcion.toLowerCase()) ||
                opt.value.toLowerCase().includes(textoOpcion.toLowerCase())
            );
            if (opcionSeleccionada) break;
        }
    }
    
    // Buscar directamente
    if (!opcionSeleccionada) {
        opcionSeleccionada = opciones.find(opt => 
            opt.value.toLowerCase() === valor.toLowerCase() ||
            opt.text.toLowerCase().includes(valor.toLowerCase())
        );
    }
    
    if (opcionSeleccionada && opcionSeleccionada.value !== "") {
        select.value = opcionSeleccionada.value;
        ['input', 'change', 'blur'].forEach(evento => {
            select.dispatchEvent(new Event(evento, { bubbles: true }));
        });
        console.log(`✅ ${nombreCampo} = "${opcionSeleccionada.text}"`);
        return true;
    } else {
        console.warn(`⚠️ No se encontró opción para ${nombreCampo}: "${valor}"`);
        return false;
    }
}

// ===============================================
// 🎮 COMANDOS LISTOS PARA USAR
// ===============================================

window.ConalcaAutoFill = {
    orden31: () => llenarFormularioConalca(31),
    orden32: () => llenarFormularioConalca(32),
    orden66: () => llenarFormularioConalca(66),
    custom: (id) => llenarFormularioConalca(id)
};

console.log(`
🚀 CONALCA AutoFill ACTIVADO

📋 COMANDOS DISPONIBLES:
• ConalcaAutoFill.orden31()     // Llenar con orden 31
• ConalcaAutoFill.orden32()     // Llenar con orden 32  
• ConalcaAutoFill.orden66()     // Llenar con orden 66
• ConalcaAutoFill.custom(123)   // Llenar con orden personalizada

🎯 EJEMPLO: ConalcaAutoFill.orden31()
`);

// Agregar botones visuales
const botonesHTML = `
<div id="autofill-panel" style="
    position: fixed; bottom: 20px; right: 20px; z-index: 9999;
    background: white; padding: 15px; border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15); border: 2px solid #007bff;
    font-family: system-ui; min-width: 200px;
">
    <div style="font-weight: bold; margin-bottom: 10px; color: #007bff; text-align: center;">
        🤖 CONALCA AutoFill
    </div>
    <div style="display: flex; flex-direction: column; gap: 8px;">
        <button onclick="ConalcaAutoFill.orden31()" 
                style="background: #007bff; color: white; border: none; 
                       padding: 10px; border-radius: 6px; cursor: pointer;">
            📋 Llenar con Orden 31
        </button>
        <button onclick="ConalcaAutoFill.orden32()" 
                style="background: #28a745; color: white; border: none; 
                       padding: 10px; border-radius: 6px; cursor: pointer;">
            🏢 Llenar con Orden 32
        </button>
        <button onclick="ConalcaAutoFill.orden66()" 
                style="background: #6f42c1; color: white; border: none; 
                       padding: 10px; border-radius: 6px; cursor: pointer;">
            🚛 Llenar con Orden 66
        </button>
    </div>
    <button onclick="document.getElementById('autofill-panel').remove()" 
            style="position: absolute; top: 5px; right: 8px; background: none; 
                   border: none; color: #999; cursor: pointer; font-size: 16px;">
        ×
    </button>
</div>
`;

document.body.insertAdjacentHTML('beforeend', botonesHTML);
console.log('✅ Panel de botones agregado en la esquina inferior derecha');
```

**Paso 4:** Usar los comandos
```javascript
// Ejecutar cualquiera de estos comandos:
ConalcaAutoFill.orden31()   // Llenar con orden 31 (Individual)
ConalcaAutoFill.orden32()   // Llenar con orden 32 (Grupal)
ConalcaAutoFill.orden66()   // Llenar con orden 66 (Prueba)
```

---

## 📱 RESULTADO ESPERADO

### ✅ Lo que va a pasar:

1. **Ejecutas el comando** → `ConalcaAutoFill.orden31()`
2. **Se conecta al servidor MCP** → Obtiene datos de la orden 31
3. **Detecta los campos del formulario** → Encuentra todos los dropdowns e inputs
4. **Llena automáticamente cada campo:**
   - ✅ Tipo de viaje → "INTERCITY"
   - ✅ Moneda → "COP"
   - ✅ Fuente de solicitud → "INDIVIDUAL"
   - ✅ Tipo de operación → "GRANEL"
   - ✅ Condición de despacho → "ENTREGA_INMEDIATA"
   - ✅ Condición de facturación → "CONTADO"
   - ✅ Ciudad de facturación → "bogota"
   - ✅ Vendedor → "CONALCA"
   - ✅ Centro costo despacho → "OTROS"
   - ✅ Cliente → "Cliente por asignar"
5. **Muestra confirmación** → Alert con resumen del llenado
6. **Logs en consola** → Información detallada de cada campo

### 📊 Datos de las Órdenes de Prueba:

**Orden 31 (Individual):**
- 🚛 Ruta: Funza → Bogotá
- 📦 Mercancía: Granel sólido (2000 kg)
- 🚚 Vehículo: Tracto Mula S3
- 💰 Valor: $1,000,000 COP

**Orden 32 (Grupal):**
- 🚛 Ruta: Medellín → Bogotá  
- 📦 Mercancía: Granel sólido (2000 kg)
- 🚚 Vehículo: Tracto Mula S3
- 💰 Valor: $1,054,000 COP

---

## 🔧 SOLUCIÓN DE PROBLEMAS

### Problema: "Error de conexión"
**Solución:** Verificar que el servidor MCP esté funcionando
```bash
curl http://127.0.0.1:18840/
```

### Problema: "Campo no encontrado"
**Solución:** El formulario tiene estructura diferente
```javascript
// Inspeccionar campos disponibles
console.log('Campos disponibles:', Array.from(document.querySelectorAll('select, input')).map(el => el.outerHTML));
```

### Problema: "No se encuentran opciones"
**Solución:** Ver opciones disponibles en dropdowns
```javascript
// Ver opciones de un select específico
document.querySelectorAll('select').forEach((select, i) => {
    console.log(`Select ${i}:`, Array.from(select.options).map(opt => opt.text));
});
```

---

## 📈 MEJORAS FUTURAS

### Versión 2.1 - Integración Permanente
```javascript
// Agregar al archivo principal de la aplicación
// Para que se cargue automáticamente en cada página
```

### Versión 2.2 - Integración con Agente de Voz
```javascript
// Integrar con ElevenLabs para comando por voz
// "Llena el formulario con la orden 31"
```

### Versión 2.3 - Detección Automática
```javascript
// Detectar automáticamente cuándo mostrar el modal de ayuda
// y ofrecer auto-llenado inteligente
```

---

## ✅ COMPROBACIÓN FINAL

**✅ Servidor MCP funcionando:** Puerto 18840  
**✅ Herramienta llenar_formulario:** Activa (#23 de 23)  
**✅ Datos de prueba:** Órdenes 31, 32, 66 disponibles  
**✅ Script JavaScript:** Completo y probado  
**✅ Documentación:** Guías detalladas creadas  

---

## 🎯 INSTRUCCIONES PARA EL USUARIO

1. **Ir a la página de cotizaciones** de CONALCA
2. **Abrir la consola** del navegador (F12)
3. **Copiar y pegar** el script completo de arriba
4. **Ejecutar comando:** `ConalcaAutoFill.orden31()`
5. **Ver el formulario llenarse automáticamente** ✨

**🚀 ¡PROBLEMA RESUELTO! El formulario ahora se llena automáticamente con los datos de cualquier orden existente.**

---

**📅 Fecha:** Octubre 9, 2025  
**🏷️ Versión:** AutoFill v2.0  
**⚡ Estado:** ✅ **FUNCIONANDO**