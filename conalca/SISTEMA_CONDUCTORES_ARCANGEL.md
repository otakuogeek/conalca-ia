# 🚛 Sistema de Búsqueda de Conductores en Arcángel

## 📋 Resumen de Implementación

Se ha implementado un sistema completo para buscar conductores disponibles **exclusivamente en la API de Arcángel** (sin consultar la base de datos local), filtrados por ciudad origen y tipo de vehículo de la orden/cotización.

---

## 🎯 Características Implementadas

### 1. ✅ Backend API (Laravel)

**Archivo:** `/app/Http/Controllers/Api/ArcangelDriversController.php`

**Endpoints creados:**

#### `POST /api/arcangel/buscar-conductores`
Busca conductores para una cotización específica.

**Request:**
```json
{
  "cotizacion_id": 83,
  "min_score": 7,
  "limit": 50
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "cotizacion": {
      "id": 83,
      "ciudad_origen": "bogota",
      "ciudad_destino": "cali",
      "vehiculo_requerido": "Tractomula",
      "peso_mercancia": "25000"
    },
    "conductores": [
      {
        "placa": "ABC123",
        "conductor": "Juan Pérez",
        "telefono": "3001234567",
        "clase_vehiculo": "TRACTOMULA",
        "carroceria": "FURGON",
        "capacidad": 25000,
        "score": 9.2,
        "disponible": true,
        "ultima_actualizacion": "2025-11-16 10:30:00"
      }
    ],
    "total": 4,
    "filtros": {
      "ciudad": "BOGOTA",
      "vehiculo": "TRACTOMULA",
      "min_score": 7
    }
  },
  "message": "Se encontraron 4 conductores disponibles"
}
```

#### `POST /api/arcangel/buscar-conductores-filtros`
Busca conductores por ciudad y tipo de vehículo sin necesidad de cotización.

**Request:**
```json
{
  "ciudad": "Bogotá",
  "vehiculo": "Sencillo",
  "min_score": 7,
  "limit": 50
}
```

---

### 2. ✅ Frontend - Componente React

**Archivo:** `/resources/js/components/CotizacionInicial/DriversModal.jsx`

**Características del modal:**
- 🎨 Diseño moderno con gradientes purple/indigo
- 📊 Tabla responsive con información completa de conductores
- ⭐ Sistema de scores con códigos de color (verde ≥9, amarillo ≥7, rojo <7)
- ✅ Indicadores de disponibilidad
- 🔄 Botón de actualización para refrescar datos
- 📞 Botones de acción para llamar a conductores
- 🔍 Filtros aplicados visibles
- 📈 Resumen estadístico (total, disponibles, score alto)
- ⚠️ Manejo de estados: loading, error, sin resultados

**Props:**
```jsx
<DriversModal
  isOpen={true}
  onClose={() => setShowModal(false)}
  cotizacionId={83}
  cotizacionData={{
    id: 83,
    ciudad_origen: "bogota",
    ciudad_destino: "cali",
    vehiculo_requerido: "Tractomula",
    peso_mercancia: "25000"
  }}
/>
```

---

### 3. ✅ Rutas API

**Archivo:** `/routes/api.php`

```php
// Conductores disponibles (protegidas con auth:sanctum)
Route::post('/buscar-conductores', [ArcangelDriversController::class, 'buscarConductores'])
    ->name('api.arcangel.buscar.conductores');

Route::post('/buscar-conductores-filtros', [ArcangelDriversController::class, 'buscarPorFiltros'])
    ->name('api.arcangel.buscar.filtros');
```

---

### 4. ✅ Página de Prueba

**Archivo:** `/resources/views/test-drivers-modal.blade.php`

**URL:** `http://tu-dominio.com/test-drivers-modal`

Página HTML standalone con:
- Formulario para ingresar ID de cotización
- Botón de búsqueda
- Tabla dinámica de resultados
- Estados de loading/error
- Diseño responsive con Tailwind CSS

---

## 🔧 Flujo de Funcionamiento

### 1. Usuario inicia búsqueda:
```
Usuario → Input ID Cotización → Click "Buscar Conductores"
```

### 2. Frontend realiza petición:
```javascript
POST /api/arcangel/buscar-conductores
{
  "cotizacion_id": 83,
  "min_score": 7,
  "limit": 50
}
```

### 3. Backend procesa:
```php
// ArcangelDriversController.php
1. Validar datos de entrada
2. Obtener cotización de BD
3. Normalizar ciudad y vehículo (BOGOTA, TRACTOMULA)
4. Llamar ArcangelService->getVehiculosFiltrados()
5. Formatear respuesta
6. Retornar JSON
```

### 4. ArcangelService consulta API:
```php
// ArcangelService.php
1. Generar token de autenticación
2. GET /api/arcangel/getVehiculosCercanos/?ciudad=BOGOTA
3. Filtrar por clase de vehículo (TRACTOMULA)
4. Filtrar por score mínimo (≥7)
5. Aplicar límite (50 vehículos)
6. Cachear resultado (15 minutos)
7. Retornar array de vehículos
```

### 5. Frontend muestra resultados:
```
Modal → Tabla → [Conductor 1, Conductor 2, ...] → Botones de acción
```

---

## 📊 Ejemplo Completo

### Cotización #83
```
Origen: Bogotá
Destino: Cali
Vehículo: Tractomula
Peso: 25,000 kg
```

### Consulta a Arcángel
```
GET /getVehiculosCercanos/?ciudad=BOGOTA
Filter: clase = TRACTOMULA
Min Score: 7
```

### Resultados
```
┌────────────────────────┬─────────┬──────────────┬──────────────┬───────┬─────────────┐
│ Conductor              │ Placa   │ Teléfono     │ Vehículo     │ Score │ Estado      │
├────────────────────────┼─────────┼──────────────┼──────────────┼───────┼─────────────┤
│ LUIS HERNANDO CANTI    │ ASA022  │ 3157861328   │ TRACTOMULA 2 │ 8.5   │ Disponible  │
│ MARCO FIDEL CORTES     │ BAU379  │ 3125955969   │ TRACTOMULA3  │ 9.2   │ Disponible  │
│ RIGOBERTO AREVALO      │ JKU981  │ 3000000000   │ TRACTOMULA3  │ 7.8   │ Ocupado     │
│ Juan Pérez Test        │ ABC123  │ 3001234567   │ TRACTOMULA3  │ 9.5   │ Disponible  │
└────────────────────────┴─────────┴──────────────┴──────────────┴───────┴─────────────┘

Total: 4 conductores
Disponibles: 3
Score ≥8: 3
```

---

## 🚀 Cómo Usar

### Desde React Component
```jsx
import DriversModal from './DriversModal';

function MyComponent() {
  const [showModal, setShowModal] = useState(false);
  
  return (
    <>
      <button onClick={() => setShowModal(true)}>
        Ver Conductores Disponibles
      </button>
      
      <DriversModal
        isOpen={showModal}
        onClose={() => setShowModal(false)}
        cotizacionId={83}
        cotizacionData={{
          ciudad_origen: "bogota",
          ciudad_destino: "cali",
          vehiculo_requerido: "Tractomula"
        }}
      />
    </>
  );
}
```

### Desde JavaScript Vanilla
```javascript
async function buscarConductores(cotizacionId) {
  const response = await fetch('/api/arcangel/buscar-conductores', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
      cotizacion_id: cotizacionId,
      min_score: 7,
      limit: 50
    })
  });
  
  const result = await response.json();
  console.log(result.data.conductores);
}
```

### Desde Consola (Artisan)
```bash
php artisan arcangel:buscar-conductores 83 --arcangel
```

---

## 🔍 Normalización de Datos

El sistema normaliza automáticamente:

```php
// Ciudades
"Bogotá" → "BOGOTA"
"Medellín" → "MEDELLIN"
"Cali" → "CALI"

// Vehículos
"Sencillo" → "SENCILLO"
"Turbo" → "TURBO"
"Tracto Mula S3" → "TRACTOMULA"
"Tracto-Mula" → "TRACTOMULA"
```

---

## 📈 Cache

El sistema utiliza cache para optimizar consultas:

```php
// Ciudades: 60 minutos
Cache::put('arcangel_ciudades', $data, now()->addMinutes(60));

// Vehículos cercanos: 15 minutos (datos más dinámicos)
Cache::put('arcangel_vehiculos_BOGOTA', $data, now()->addMinutes(15));
```

---

## ⚡ Ventajas del Sistema

1. **✅ Sin dependencia de BD local:** Solo consulta Arcángel (datos en tiempo real)
2. **🚀 Rápido:** Cache de 15 minutos reduce llamadas a API
3. **🎯 Preciso:** Filtrado por ciudad exacta y tipo de vehículo
4. **📊 Visual:** Modal moderno con toda la información relevante
5. **♿ Accesible:** Responsive, manejo de errores, estados de carga
6. **🔧 Flexible:** Funciona desde React, JavaScript vanilla o consola
7. **📱 Mobile-friendly:** Diseño responsive con Tailwind CSS

---

## 🧪 Testing

### Probar API directamente:
```bash
# Con curl
curl -X POST http://localhost/api/arcangel/buscar-conductores \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-token-here" \
  -d '{"cotizacion_id": 83, "min_score": 7, "limit": 50}'

# Página de prueba integrada
http://localhost/test-drivers-modal
```

### Probar desde consola:
```bash
# Solo Arcángel (nueva funcionalidad)
php artisan arcangel:buscar-conductores 83 --arcangel

# Ver todas las opciones
php artisan arcangel:buscar-conductores --help
```

---

## 📝 Archivos Modificados/Creados

```
✅ Creados:
├── app/Http/Controllers/Api/ArcangelDriversController.php
├── resources/js/components/CotizacionInicial/DriversModal.jsx
├── resources/views/test-drivers-modal.blade.php
└── SISTEMA_CONDUCTORES_ARCANGEL.md (este archivo)

✅ Modificados:
├── routes/api.php (agregar rutas de conductores)
└── app/Console/Commands/BuscarConductoresParaCotizacion.php (modificado para solo Arcángel)
```

---

## 🔗 Integración con Sistema Existente

El modal se puede integrar fácilmente en:

1. **QuoteIndex.jsx:** Botón en cada cotización
2. **CallPanel.jsx:** Antes de registrar llamadas
3. **LiveWire Components:** Blade + AlpineJS
4. **Dashboard:** Vista de conductores disponibles por ciudad

---

## 🎓 Conclusión

Sistema completamente funcional que:
- ✅ **Elimina** búsqueda en base de datos local
- ✅ **Consulta** solo Arcángel API  
- ✅ **Filtra** por ciudad origen y tipo de vehículo
- ✅ **Muestra** resultados en modal interactivo
- ✅ **Cachea** resultados para performance
- ✅ **Normaliza** datos para matching preciso
- ✅ **Maneja** errores y estados de carga

**Estado:** ✅ Completamente implementado y funcional

---

**Documentación creada:** 2025-11-16  
**Versión:** 1.0  
**Autor:** Sistema Conalca IA
