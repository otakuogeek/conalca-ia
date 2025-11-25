# 🤖 Cómo Usar el Llenado Automático del Formulario

## ✨ Características Nuevas

El ChatBox ahora puede **llenar automáticamente** todo el formulario con datos de ejemplo o con la información que le proporciones.

---

## 🚀 Opción 1: Llenar con Datos de Ejemplo

### Frases que activan el llenado automático:

Escribe cualquiera de estas frases en el chat:

```
llena con ejemplos
rellena con datos de prueba
completa el formulario con ejemplo
carga datos de test
llena todo el formulario
puedes llenar con ejemplo
```

### 📦 Datos que se cargarán automáticamente:

#### **Paso 1 - Datos Básicos**
- Cliente: **2551**
- Tipo de viaje: **NACIONAL**
- Moneda: **PESOS**
- Fuente solicitud: **TELEFONO DESPACHADOR**
- Ciudad facturación: **11001000** (Bogotá)
- Vendedor: **53165050**
- Tipo operación: **DISTRIBUCION**
- Centro costo: **TRANSLIDHER BOGOTA**

#### **Paso 2 - Detalle**
- Origen: **11001000** (Bogotá)
- Destino: **11001000** (Bogotá)
- Cantidad mercancía: **1**
- Peso: **2000** kg
- Producto: **1**
- Empaque: **1**
- Cantidad vehículos: **1**
- Clase vehículo: **1**
- Carrocería: **1**
- Tipo flete: **CARGA SUELTA**
- Cargue cuenta de: **EMPRESA**
- Descargue cuenta de: **DESTINATARIO**
- Seguro cuenta de: **CLIENTE**
- Kit seguridad: **NO**

#### **Paso 3 - Cargue**
- Fecha cargue: **Hoy**
- Hora cargue: **16:00**
- Remitente: **1**
- Destinatario: **1**
- Contacto: **2345678**
- Promesa servicio: **Hoy**

#### **Paso 4 - Contenedor**
- Requiere contenedor: **NO**

#### **Paso 5 - Internacional**
- Modalidad: **OTM**

#### **Paso 6 - Acompañamiento**
- Vehículos acompañamiento: **1**
- Tipo: **MOTORIZADO**
- Cuenta de: **CLIENTE**
- Valor: **1**

---

## 🎯 Opción 2: Llenar Campo por Campo

Puedes dar los datos naturalmente y el chat los irá llenando:

### Ejemplos:

```
Cliente 2551, viaje nacional en pesos
```

```
Origen Bogotá, destino Medellín, peso 2000 kilos
```

```
Tipo de flete carga suelta, cargue por cuenta de la empresa
```

```
Fecha de cargue hoy a las 4 pm
```

---

## 🎤 Opción 3: Usar el Micrófono

1. Haz clic en el botón del **micrófono** 🎤
2. Di: *"Llena el formulario con datos de ejemplo"*
3. O dicta los datos uno por uno

---

## 🔍 Verificación

Después de llenar automáticamente, verás:

1. **En el chat**: Un mensaje de confirmación
2. **En el formulario**: Todos los campos llenos con valores
3. **En consola** (F12): Logs mostrando cada campo que se llenó

---

## ⚙️ Cómo Funciona

Cuando escribes frases como "llena con ejemplos":

1. El ChatBox **detecta la intención** mediante expresiones regulares
2. Emite eventos `chatBus.emit('fill-field', campo, valor)` para cada campo
3. Los componentes `Step1.jsx`, `Step2.jsx`, etc. **escuchan** esos eventos
4. Actualizan sus estados y muestran los valores en el UI

---

## 🛠️ Para Desarrolladores

### Estructura del código:

```javascript
// En ChatBox.jsx

// 1. Datos de ejemplo predefinidos
const DATOS_EJEMPLO = {
  codigo_cliente: '2551',
  tipo_viaje: 'NACIONAL',
  // ... más campos
};

// 2. Función que emite todos los campos
const llenarConEjemplo = () => {
  Object.entries(DATOS_EJEMPLO).forEach(([field, value]) => {
    chatBus.emit('fill-field', field, value);
  });
};

// 3. Detección en send()
const send = async () => {
  const text = inputRef.current.value.trim();
  
  const esLlenarEjemplo = /(llena|rellena).*ejemplo/i.test(text);
  
  if (esLlenarEjemplo) {
    llenarConEjemplo();
    // Mostrar mensaje de confirmación
  }
};
```

### Para modificar los datos de ejemplo:

Edita el objeto `DATOS_EJEMPLO` en `/resources/js/components/SolicitudWizard/ChatBox.jsx` (línea ~510)

---

## 📝 Notas

- Los datos de ejemplo corresponden a los **valores mínimos requeridos** para crear una solicitud válida
- Después de llenar automáticamente, puedes **modificar** cualquier campo manualmente
- Los campos con valor `"1"` son placeholders que debes reemplazar con datos reales
- La fecha se llena con **hoy** automáticamente

---

## ✅ Testing Completo

Para probar extremo a extremo:

1. Abre el modal de solicitud
2. Escribe: `"llena con ejemplos"`
3. Verifica que todos los campos del **Paso 1** estén llenos
4. Haz clic en **"Siguiente"**
5. Verifica que todos los campos del **Paso 2** estén llenos
6. Continúa hasta el **Paso 6**
7. Todos los pasos deberían tener valores válidos

---

## 🐛 Troubleshooting

### "No se llenan los campos"

**Solución:**
```bash
cd /home/ubuntu/mcp/conalca
npm run build
```

### "Algunos campos no se cargan"

Verifica la consola del navegador (F12). Deberías ver logs como:
```
✅ cliente_codigo = 2551
✅ tipo_viaje = NACIONAL
```

Si no aparecen, revisa que `chatBus.emit()` se esté ejecutando.

### "Error en consola"

Limpia el cache del navegador con `Ctrl+Shift+R`

---

## 📞 Contacto

Si encuentras problemas o quieres agregar más datos de ejemplo, contacta al equipo de desarrollo.
