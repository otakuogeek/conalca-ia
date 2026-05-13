# 🚨 DIAGNÓSTICO Y SOLUCIÓN DEL ERROR

**PROBLEMA IDENTIFICADO:** Error al proceder con el auto-llenado  
**CAUSA PROBABLE:** Diferencia entre el DOM esperado y el DOM real  
**SOLUCIÓN:** Script ultra-específico y diagnóstico paso a paso

---

## 🔍 DIAGNÓSTICO INMEDIATO

### Paso 1: Verificar Servidor MCP
```javascript
// EJECUTAR EN CONSOLA PARA VERIFICAR CONEXIÓN
fetch('http://127.0.0.1:18840/')
  .then(response => console.log('✅ Servidor MCP disponible:', response.status))
  .catch(error => console.error('❌ Error conexión MCP:', error));
```

### Paso 2: Probar Herramienta MCP
```javascript
// PROBAR LA HERRAMIENTA DIRECTAMENTE
fetch('http://127.0.0.1:18840/', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    jsonrpc: "2.0",
    id: "test",
    method: "tools/call",
    params: {
      name: "llenar_formulario",
      arguments: { orden_id: 31 }
    }
  })
})
.then(r => r.json())
.then(data => {
  console.log('✅ MCP respuesta:', data);
  if (data.result) {
    const parsed = JSON.parse(data.result.content[0].text);
    console.log('📦 Datos formulario:', parsed.campos_formulario);
  }
})
.catch(e => console.error('❌ Error MCP:', e));
```

### Paso 3: Inspeccionar DOM Actual
```javascript
// INSPECCIONAR CAMPOS DEL FORMULARIO ACTUAL
console.log('🔍 ANÁLISIS DEL FORMULARIO ACTUAL:');
console.log('='.repeat(50));

// Todos los selects
const selects = document.querySelectorAll('select');
console.log(`📋 Total selects encontrados: ${selects.length}`);
selects.forEach((select, i) => {
  console.log(`Select ${i}:`, {
    wire_model: select.getAttribute('wire:model'),
    wire_model_live: select.getAttribute('wire:model.live'),
    name: select.name,
    id: select.id,
    opciones: Array.from(select.options).map(o => o.text).filter(t => t.trim())
  });
});

// Todos los inputs
const inputs = document.querySelectorAll('input');
console.log(`📝 Total inputs encontrados: ${inputs.length}`);
inputs.forEach((input, i) => {
  console.log(`Input ${i}:`, {
    wire_model: input.getAttribute('wire:model'),
    wire_model_live: input.getAttribute('wire:model.live'),
    name: input.name,
    id: input.id,
    placeholder: input.placeholder,
    type: input.type
  });
});
```

---

## 🎯 SOLUCIÓN ULTRA-SIMPLE

### Versión Mínima para Probar
```javascript
// ===============================================
// 🚀 VERSIÓN MÍNIMA DE PRUEBA
// ===============================================

async function probarAutoLlenado() {
  console.log('🧪 Iniciando prueba mínima...');
  
  try {
    // 1. Obtener datos MCP
    const response = await fetch('http://127.0.0.1:18840/', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        jsonrpc: "2.0",
        id: "test",
        method: "tools/call",
        params: {
          name: "llenar_formulario",
          arguments: { orden_id: 31 }
        }
      })
    });
    
    const resultado = await response.json();
    console.log('📡 Respuesta MCP:', resultado);
    
    if (!resultado.result) {
      throw new Error('No hay resultado en respuesta MCP');
    }
    
    const datos = JSON.parse(resultado.result.content[0].text);
    console.log('📦 Datos parseados:', datos.campos_formulario);
    
    // 2. Probar llenar UN campo como prueba
    const primerSelect = document.querySelector('select');
    if (primerSelect) {
      console.log('🔧 Probando primer select...');
      console.log('Opciones disponibles:', Array.from(primerSelect.options).map(o => `"${o.text}" (value: "${o.value}")`));
      
      // Intentar poner valor "NACIONAL" en primer select
      const opcionNacional = Array.from(primerSelect.options).find(opt => opt.text.includes('NACIONAL'));
      if (opcionNacional) {
        primerSelect.value = opcionNacional.value;
        primerSelect.dispatchEvent(new Event('change', { bubbles: true }));
        console.log('✅ Primer campo llenado exitosamente');
        
        alert('✅ PRUEBA EXITOSA: Primer campo llenado correctamente');
        return true;
      } else {
        console.warn('⚠️ No se encontró opción NACIONAL');
        alert('⚠️ No se encontró opción NACIONAL en primer select');
      }
    } else {
      console.error('❌ No se encontró ningún select');
      alert('❌ No se encontró ningún select en la página');
    }
    
  } catch (error) {
    console.error('❌ Error en prueba:', error);
    alert(`❌ Error: ${error.message}`);
    return false;
  }
}

// Hacer disponible globalmente
window.probarAutoLlenado = probarAutoLlenado;

console.log('🧪 PRUEBA MÍNIMA LISTA');
console.log('▶️ Ejecutar: probarAutoLlenado()');
```

---

## 🔧 SOLUCIÓN PASO A PASO

### Opción 1: Diagnóstico Completo
1. **Ejecutar código de diagnóstico** (Paso 3 de arriba)
2. **Revisar qué campos se detectan**
3. **Ajustar selectores según resultados**

### Opción 2: Prueba Mínima
1. **Ejecutar la versión mínima** de arriba
2. **Verificar si llena al menos un campo**
3. **Expandir a más campos si funciona**

### Opción 3: Manual Dirigido
```javascript
// LLENAR MANUALMENTE CAMPO POR CAMPO
function llenarManual() {
  // Tipo de viaje - primer select
  const tipoViaje = document.querySelector('select');
  if (tipoViaje) {
    const nacional = Array.from(tipoViaje.options).find(o => o.text.includes('NACIONAL'));
    if (nacional) {
      tipoViaje.value = nacional.value;
      tipoViaje.dispatchEvent(new Event('change', { bubbles: true }));
      console.log('✅ Tipo de viaje = NACIONAL');
    }
  }
  
  // Moneda - segundo select
  const selects = document.querySelectorAll('select');
  if (selects[1]) {
    const pesos = Array.from(selects[1].options).find(o => o.text.includes('PESOS'));
    if (pesos) {
      selects[1].value = pesos.value;
      selects[1].dispatchEvent(new Event('change', { bubbles: true }));
      console.log('✅ Moneda = PESOS');
    }
  }
  
  console.log('✅ Llenado manual completado');
}

window.llenarManual = llenarManual;
```

---

## 📋 INSTRUCCIONES INMEDIATAS

### Para diagnosticar el problema:

1. **Abrir consola del navegador** (F12)
2. **Ejecutar código de diagnóstico** (Paso 3)
3. **Revisar qué campos aparecen**
4. **Compartir resultados**

### Para probar solución mínima:

1. **Copiar y pegar** la "Versión Mínima de Prueba"
2. **Ejecutar:** `probarAutoLlenado()`
3. **Ver si llena al menos un campo**

### Para llenar manualmente:

1. **Copiar función** `llenarManual()`
2. **Ejecutar:** `llenarManual()`
3. **Ver campos que se llenan**

---

## 🚨 POSIBLES CAUSAS DEL ERROR

1. **DOM diferente:** Selectores no coinciden con la estructura real
2. **Livewire interferencia:** Eventos no se disparan correctamente  
3. **Timing:** Formulario aún no está completamente cargado
4. **CORS/Conexión:** Problema con servidor MCP en localhost
5. **JavaScript bloqueado:** CSP o políticas de seguridad

---

**🎯 SIGUIENTE PASO:** Ejecutar el diagnóstico para identificar la causa exacta del error.

**📞 Una vez tengas los resultados del diagnóstico, podremos crear la solución específica para tu DOM exacto.**