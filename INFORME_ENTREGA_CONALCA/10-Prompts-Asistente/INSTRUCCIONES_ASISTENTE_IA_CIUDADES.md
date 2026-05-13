# INSTRUCCIONES PARA EL ASISTENTE IA - EXTRACCIÓN DE CIUDADES

## 🎯 Objetivo
Asegurar que el asistente IA extraiga correctamente los nombres de ciudades de origen y destino **sin incluir palabras adicionales** como "cotización de", "ruta de", "necesito", etc.

---

## ❌ Problema Común

### Ejemplo del Error
**Mensaje del usuario:**  
`"cotización de bogotá a Bucaramanga 6 toneladas de maíz"`

**Extracción INCORRECTA:**
```json
{
  "ciudad_origen": "COTIZACION DE BOGOTA",
  "ciudad_destino": "BUCARAMANGA"
}
```

**Extracción CORRECTA:**
```json
{
  "ciudad_origen": "BOGOTA",
  "ciudad_destino": "BUCARAMANGA"
}
```

---

## ✅ Reglas para el Asistente IA

### 1. **Identificar SOLO el nombre de la ciudad**
- **Incluir:** El nombre de la ciudad únicamente (máximo 3 palabras para ciudades compuestas)
- **Excluir:** Palabras como "cotización", "ruta", "necesito", "solicito", "de", "desde", "hacia", etc.

### 2. **Palabras que NUNCA son ciudades**
Las siguientes palabras NUNCA deben ser extraídas como nombres de ciudades:

#### Campos de formulario:
- producto, peso, valor, empaque, embalaje, cantidad, origen, destino

#### Acciones:
- cambia, cambiar, modificar, actualizar, nota, importante

#### Frases conectoras:
- cotización, necesito, quiero, solicito, ruta, viaje
- importación, exportación, distribución, carga

#### Tiempo:
- enero, febrero, marzo, abril, mayo, junio, julio, agosto, septiembre, octubre, noviembre, diciembre
- las, los, del, día, días, hora, horas, mañana, tarde, noche
- am, pm, hoy, ayer, semana, mes, año

### 3. **Patrones de extracción válidos**

#### Patrón 1: "de X a Y"
```
"de bogotá a Bucaramanga 6 toneladas"
     ↓       ↓
  BOGOTA  BUCARAMANGA
```

#### Patrón 2: "desde X hasta Y"
```
"desde Medellín hasta Cali"
       ↓           ↓
   MEDELLIN      CALI
```

#### Patrón 3: "X a Y" (directo)
```
"Cali a Pasto 12 toneladas"
  ↓      ↓
CALI   PASTO
```

#### Patrón 4: Con prefijos a limpiar
```
"cotización de Cali a Medellín"
  [limpiar]    ↓       ↓
            CALI   MEDELLIN
```

### 4. **Normalización de nombres**
- Convertir a MAYÚSCULAS
- Eliminar tildes: á → A, é → E, í → I, ó → O, ú → U, ñ → N
- Mantener espacios para ciudades compuestas: "Santa Marta" → "SANTA MARTA"

### 5. **Ciudades compuestas (válidas)**
Acepta hasta 3 palabras para nombres de ciudades:
- ✅ "Santa Marta" (2 palabras)
- ✅ "Villa de Leyva" (3 palabras)
- ✅ "San Andrés" (2 palabras)
- ✅ "Puerto Asís" (2 palabras)

---

## 🧪 Validación

### Script de Pruebas
Ejecuta el siguiente comando para validar que la extracción funciona correctamente:

```bash
cd /home/ubuntu/conalca/conalca
php test_validacion_ciudades.php
```

### Casos de Prueba Incluidos
El script prueba automáticamente:
1. ✅ Frases con "cotización de"
2. ✅ Frases con "de X a Y"
3. ✅ Frases directas sin prefijo
4. ✅ Ciudades compuestas (2-3 palabras)
5. ✅ Mensajes completos con todos los detalles
6. ✅ Variaciones de escritura (mayúsculas, minúsculas, sin tildes)
7. ✅ Casos especiales que NO deben confundirse

### Resultado Esperado
```
✅ Tests pasados: 16/16
📈 Tasa de éxito: 100%
🎉 ¡TODOS LOS TESTS PASARON!
```

---

## 🔧 Implementación Técnica

### Archivo Modificado
**`app/Services/MCPAssistantService.php`**

### Cambio Principal (Línea ~5312)
Se agregó `cotizaci[oó]n\s+(?:de\s+)?` al patrón de prefijos para limpiar:

```php
$patronPrefijos = '/^(?:cotizaci[oó]n\s+(?:de\s+)?|distribuci[oó]n\s+nacionalizada\s+(?:de\s+)?|importaci[oó]n\s+(?:de\s+)?|exportaci[oó]n\s+(?:de\s+)?|ruta\s+(?:de\s+)?|viaje\s+(?:de\s+)?|destino\s+(?:de\s+)?|origen\s+(?:de\s+)?|de\s+la\s+|desde\s+|hacia\s+|de\s+)/ui';
```

**Función:** `extractCiudades($text)`  
**Método:** `private static function extractCiudades($text)`

---

## 📝 Prompt Recomendado para el Asistente IA

Cuando configures el asistente IA (GPT-4, Claude, etc.), incluye estas instrucciones en el prompt del sistema:

```
Al extraer ciudades de origen y destino de un mensaje:

1. SOLO extrae el nombre de la ciudad (máximo 3 palabras)
2. NUNCA incluyas palabras como: "cotización", "de", "ruta", "necesito", "solicito"
3. Las siguientes palabras NUNCA son ciudades: producto, peso, valor, origen, destino, cambia, modificar, enero, febrero, etc.
4. Normaliza los nombres: MAYÚSCULAS y sin tildes
5. Ejemplos correctos:
   - "cotización de bogotá a Bucaramanga" → origen: "BOGOTA", destino: "BUCARAMANGA"
   - "necesito de Cali a Medellín" → origen: "CALI", destino: "MEDELLIN"
   - "Santa Marta a Barranquilla" → origen: "SANTA MARTA", destino: "BARRANQUILLA"
```

---

## 🚨 Monitoreo y Alertas

### Revisar Logs
Busca en los logs el patrón de extracción de ciudades:

```bash
cd /home/ubuntu/conalca/conalca
tail -500 storage/logs/laravel.log | grep "🏙️ Ciudades detectadas"
```

### Señales de Alerta
Si ves logs como estos, hay un problema:

```
❌ "ciudad_origen": "COTIZACION DE BOGOTA"
❌ "ciudad_origen": "NECESITO CALI"
❌ "ciudad_origen": "RUTA DE PEREIRA"
```

### Acción Correctiva
1. Ejecutar el script de validación: `php test_validacion_ciudades.php`
2. Identificar qué patrón está fallando
3. Revisar la función `extractCiudades()` en MCPAssistantService.php
4. Agregar el caso problemático al script de pruebas

---

## 📚 Referencias

### Archivos Relacionados
- **Servicio principal:** `app/Services/MCPAssistantService.php`
- **Función de extracción:** `extractCiudades()` (línea ~5180)
- **Función de normalización:** `normalizeCityName()` (línea ~5450)
- **Script de validación:** `test_validacion_ciudades.php`
- **Script de corrección manual:** `fix_grupo_350.php`

### Documentación Adicional
- Ver `SOLUCION_COMPLETA_EXTRACCION_IA.md` para detalles del sistema de extracción
- Ver `MEJORAS_EXTRACCION_IA_v2.md` para historial de mejoras

---

## ✅ Checklist de Verificación

Antes de dar por resuelto un problema de extracción de ciudades:

- [ ] El script `test_validacion_ciudades.php` pasa con 100% de éxito
- [ ] Los logs muestran nombres de ciudades limpios (sin prefijos)
- [ ] Se agregó el caso problemático al script de pruebas
- [ ] Se actualizó esta documentación si fue necesario
- [ ] Se probó con al menos 3 variaciones del mensaje

---

**Última actualización:** 2026-01-19  
**Autor:** Sistema de Validación Automática  
**Versión:** 1.0
