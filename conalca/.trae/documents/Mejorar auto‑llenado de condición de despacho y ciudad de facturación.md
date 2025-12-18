## Problema identificado
- El chat rellena campos iniciales, pero no aplica en `condicion_despacho` ni `ciudad_facturacion`.
- La respuesta del asistente incluye líneas como `rellenar("condicion_despacho", "NUEVO")` y `rellenar("ciudad_facturacion", "11001000")`, que no se están parseando.
- Los patrones actuales solo cubren algunos campos y no detectan la sintaxis de función ni la combinación con acentos/variantes.

## Cambios propuestos
- Parsear llamadas `rellenar("campo", "valor")` dentro de `runChat` y emitir `chatBus.emit('fill-field', campo, normalizeValue(campo, valor))` para cada llamada encontrada.
- Ampliar patrones de texto natural en `runChat` para:
  - `condicion_despacho`: `condici(o|ó)n( de)? despacho[:\s]+([^\n]+)`
  - `condicion_facturacion`: `condici(o|ó)n( de)? facturaci(o|ó)n[:\s]+([^\n]+)`
  - `ciudad_facturacion`: aceptar tanto códigos DANE como nombre (si viene nombre, intentar resolver luego).
- Heurística adicional en el auto‑llenado inmediato (al enviar):
  - Detectar frases como `ciudad de facturación bogotá` y, si no hay código, emitir el nombre para que Step1 resuelva el label (o preparar resolución a código).

## Verificación
- Caso 1: Enviar “condicion de despacho EXW” → `condicion_despacho` toma "EXW" en Paso 1.
- Caso 2: Enviar “ciudad de facturación 11001000” → `ciudad_facturacion` se establece y el AsyncSelect muestra el label.
- Caso 3: Mensaje libre “ciudad bogota” seguido de respuesta del asistente con `rellenar("ciudad_facturacion", "11001000")` → se parsea y se refleja en el formulario.

## Archivos a tocar
- `resources/js/components/SolicitudWizard/ChatBox.jsx`:
  - Añadir parser de `rellenar()`.
  - Ampliar patrones para `condicion_despacho` y `condicion_facturacion` y `ciudad_facturacion`.
  - Extender heurística de auto‑llenado al enviar.

## Impacto
- No modifica la API ni otros componentes; se mejora la robustez de extracción.
- Mantiene la normalización y evita valores inválidos.

¿Confirmas estos cambios para implementarlos y probarlos en la UI?