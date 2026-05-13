# Limpieza Completa del Formulario de Cotizaciones

## Fecha: 16 de noviembre de 2025

## Problema Reportado
El usuario reportaba que al iniciar la creación de cotizaciones, la ventana no se limpiaba por completo, manteniendo datos de sesiones anteriores. También se reportaron errores 500 en el endpoint `/api/chat/quote/save-routes`.

## Soluciones Implementadas

### 1. Limpieza Completa del Formulario (Frontend)

**Archivo modificado:** `resources/js/components/CotizacionInicial/QuoteIndex.jsx`

Se mejoró la función `resetCreateFlow()` para:
- Limpiar todos los estados del flujo de cotización
- Resetear datos del cliente incluyendo los nuevos campos de parámetros automáticos
- Establecer valores por defecto correctos (clientType: 'cash')
- Limpiar parámetros de URL para evitar que se carguen datos de sesiones anteriores
- Agregar logs de depuración para verificar la limpieza

**Cambios específicos:**
```javascript
const resetCreateFlow = () => {
  console.log('🧹 Limpiando completamente el formulario de cotización');
  
  // Limpia todos los estados
  setStep(0);
  setQuoteData([]);
  setMessages([]);
  setInputMessage('');
  setPricings([]);
  setSelectedPricings({});
  setPorcentajeGlobal(17);
  
  // Resetea clientData con todos los campos
  setClientData({
    search: '',
    clientId: null,
    clientName: '',
    documentClient: '',
    clientCompanyName: '',
    clientLocation: '',
    clientPhoneNumbers: '',
    clientPersonalCell: '',
    clientEmail: '',
    clientAddress: '',
    clientBranchOffice: '',
    clientSalesRepresentative: '',
    clientContact: '',
    clientCargo: '',
    clientType: 'cash',
    operationType: '',
    typeBusiness: '',
    groupId: null,
    threadId: null,
    cargoType: '',
    candadoSatelital: false,
    jenSet: false,
    combustible: false,
    kitDerrames: false,
    pictogramas: false
  });
  
  // Limpia parámetros de URL
  if (window.location.search) {
    const cleanUrl = window.location.pathname;
    window.history.replaceState({}, document.title, cleanUrl);
  }
  
  console.log('✅ Formulario limpiado completamente');
};

const handleCreateQuote = () => {
  // Resetear todo antes de abrir el modal
  resetCreateFlow();
  handleOpenModal('create');
};
```

### 2. Corrección de Error 500 en save-routes (Backend)

#### 2.1 Modificación de la Tabla cotizacion_models

**Problema:** La columna `pricing_id` no permitía valores NULL, causando error al intentar crear cotizaciones sin precio asignado.

**Solución:** Se modificó la tabla para permitir NULL en pricing_id:
```sql
ALTER TABLE cotizacion_models 
MODIFY COLUMN pricing_id bigint(20) unsigned NULL;
```

#### 2.2 Agregación de columna user_id

**Problema:** El controlador intentaba asignar `user_id` pero la columna no existía en la tabla.

**Solución:** Se agregó la columna:
```sql
ALTER TABLE cotizacion_models 
ADD COLUMN user_id bigint(20) unsigned NULL AFTER id,
ADD INDEX idx_user_id (user_id);
```

#### 2.3 Actualización del Modelo CotizacionModel

**Archivo modificado:** `app/Models/CotizacionModel.php`

Se agregó `user_id` al array `$fillable`:
```php
protected $fillable = [
    'client_id',
    'user_id',  // ← Agregado
    'pricing_id',
    // ... resto de campos
];
```

#### 2.4 Corrección del Controlador QuoteRoutesController

**Archivo modificado:** `app/Http/Controllers/Api/QuoteRoutesController.php`

**Cambios realizados:**
1. Se removió el campo `estado` que no existe en la tabla
2. Se mantuvo `pricing_id` como NULL inicialmente
3. Se agregó el campo `active` con valor 1

```php
$cotization = CotizacionModel::create([
    'group_cotization_id' => $group->id,
    'user_id' => Auth::id(),
    'client_id' => $group->client_id,
    'ciudad_origen' => $routeData['ciudad_origen'],
    'ciudad_destino' => $routeData['ciudad_destino'],
    'peso_mercancia' => $this->parseNumericField($routeData['peso_mercancia'] ?? '0'),
    'cantidad' => $this->parseNumericField($routeData['cantidad'] ?? '1'),
    'tipo_embajale' => $routeData['tipo_embajale'] ?? 'Caja',
    'tipo_producto' => $routeData['tipo_producto'] ?? 'Mercancía general',
    'vehiculo_requerido' => $routeData['vehiculo_requerido'] ?? 'Sencillo',
    'valor_declarado' => $this->parseMoneyField($routeData['valor_declarado'] ?? '0'),
    'pricing_id' => null, // NULL hasta que se asigne un precio
    'decision_cliente' => 'pendiente',
    'active' => 1,
    'created_at' => now(),
    'updated_at' => now(),
]);
```

También se corrigió el método `getQuoteRoutes()` para usar `active` en lugar de `estado`.

## Compilación de Assets

Se compilaron los assets de React/Vite para aplicar los cambios:
```bash
npm run build
```

**Resultado:** Build exitoso en 31.79s

## Resultados Esperados

### Frontend
- ✅ El formulario se limpia completamente al hacer clic en "Crear Cotización"
- ✅ No se mantienen datos de sesiones anteriores
- ✅ Los parámetros automáticos se resetean a false
- ✅ El tipo de cliente se establece como "cash" por defecto
- ✅ Los parámetros de URL se limpian

### Backend
- ✅ El endpoint `/api/chat/quote/save-routes` ya no genera error 500
- ✅ Las cotizaciones se pueden crear sin precio asignado inicialmente
- ✅ Se registra correctamente el user_id del creador
- ✅ Se establece el estado correcto (decision_cliente: pendiente, active: 1)

## Verificación

Para verificar los cambios:
1. Acceder a la página de cotizaciones
2. Hacer clic en "Crear Cotización"
3. Verificar que todos los campos estén vacíos
4. Completar el flujo de creación con el chat IA
5. Verificar que las rutas se guarden correctamente sin errores 500

## Archivos Modificados

1. `/resources/js/components/CotizacionInicial/QuoteIndex.jsx`
2. `/app/Models/CotizacionModel.php`
3. `/app/Http/Controllers/Api/QuoteRoutesController.php`
4. Base de datos: tabla `cotizacion_models`

## Logs de Depuración

Se agregaron logs de consola para facilitar el debugging:
- `🧹 Limpiando completamente el formulario de cotización` - Al iniciar limpieza
- `✅ Formulario limpiado completamente` - Al finalizar limpieza
- Los datos de clientData se muestran en consola para verificación

## Notas Técnicas

- La relación entre `GroupCotization` y `CotizacionModel` funciona correctamente a través de `group_cotization_id`
- El campo `pricing_id` ahora permite NULL, se asigna posteriormente en el flujo
- El campo `decision_cliente` es un ENUM con valores: 'pendiente', 'aceptada', 'rechazada'
- Se mantiene la compatibilidad con el flujo existente de recuperación de cotizaciones
