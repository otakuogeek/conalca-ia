# Actualización CallPanel: Botón "Ver Listado" y Búsqueda de Conductores

## 📅 Fecha
**Diciembre 2024**

## 🎯 Objetivo
Actualizar el componente `CallPanel.jsx` para mostrar información en tiempo real de conductores disponibles en Arcángel y agregar un botón "Ver Listado" que muestre los resultados de búsqueda en un modal.

## 📝 Descripción de Cambios

### 1. **Archivo Modificado**
- **Ubicación**: `/resources/js/components/Calls/CallPanel.jsx`
- **Líneas totales**: 300+ (actualizado desde 255)

### 2. **Nuevas Importaciones**
```jsx
import { FaList } from 'react-icons/fa'; // Icono para botón Ver Listado
import DriversModal from '../CotizacionInicial/DriversModal'; // Modal de búsqueda
```

### 3. **Nuevos Estados**
```jsx
const [showDriversModal, setShowDriversModal] = useState(false); // Control modal
const [driversSearchData, setDriversSearchData] = useState(null); // Datos búsqueda
const [loadingDrivers, setLoadingDrivers] = useState(false); // Loading búsqueda
```

### 4. **Nueva Función: `handleSearchDrivers`**

**Propósito**: Buscar conductores disponibles en Arcángel API en tiempo real.

**Endpoint**: `POST /api/arcangel/buscar-conductores`

**Request Body**:
```json
{
  "cotizacion_id": 123
}
```

**Response Esperado**:
```json
{
  "success": true,
  "total": 15,
  "conductores": [
    {
      "id": "ABC123",
      "nombre": "Juan Pérez",
      "telefono": "3001234567",
      "ciudad": "BOGOTA",
      "tipo_vehiculo": "SENCILLO",
      "score": 95,
      "disponible": true
    }
  ],
  "filtros": {
    "ciudad_origen": "BOGOTA",
    "tipo_vehiculo": "SENCILLO"
  }
}
```

**Características**:
- ✅ Llamada AJAX a API de Arcángel
- ✅ Manejo de errores con SweetAlert
- ✅ Loading state con spinner
- ✅ Actualiza contador de conductores en tiempo real
- ✅ Abre modal automáticamente con resultados

### 5. **Cambios en la Interfaz**

#### **Antes**:
```jsx
<p className="text-sm">
  <strong>Total conductores:</strong> {data.total_to_call}
</p>

<button onClick={handleCall} className="w-full ...">
  <FaPhoneAlt/> Registrar Llamadas
</button>
```

#### **Después**:
```jsx
<p className="text-sm">
  <strong>Total conductores:</strong> {driversSearchData?.total || data.total_to_call}
  {driversSearchData && (
    <span className="ml-2 text-xs text-green-600">
      (Actualizado desde Arcángel)
    </span>
  )}
</p>

<div className="flex gap-2">
  <button onClick={handleCall} className="flex-1 ...">
    <FaPhoneAlt/> Registrar Llamadas
  </button>

  <button onClick={handleSearchDrivers} className="...">
    <FaList/> Ver Listado
  </button>
</div>
```

### 6. **Integración con DriversModal**

**Props Pasados al Modal**:
```jsx
<DriversModal
  isOpen={showDriversModal}
  onClose={() => setShowDriversModal(false)}
  cotizacionId={cotizacion.id}
  cotizacionData={{
    ciudad_origen: data.ciudad_origen || cotizacion.ciudad_origen,
    ciudad_destino: data.ciudad_destino || cotizacion.ciudad_destino,
    tipo_vehiculo: data.vehicle_type,
    conductores: driversSearchData.conductores || [],
    total: driversSearchData.total || 0,
  }}
/>
```

## 🎨 Diseño Visual

### Botones Layout (Flex)
```
┌─────────────────────────────────┐
│ [📞 Registrar Llamadas] [📋 Ver Listado] │
└─────────────────────────────────┘
```

### Contador de Conductores
```
Total conductores: 15 (Actualizado desde Arcángel)
                      └─ Badge verde que aparece tras búsqueda
```

## 🔄 Flujo de Trabajo

1. **Usuario ve la tarjeta CONDUCTORES**
   - Muestra: Tipo de vehículo, Total conductores (inicial)
   - Botones: "Registrar Llamadas" y "Ver Listado"

2. **Usuario hace clic en "Ver Listado"**
   - Se activa `loadingDrivers = true` (botón muestra spinner)
   - Se llama a `/api/arcangel/buscar-conductores`
   - Se actualiza `driversSearchData` con resultados

3. **Sistema muestra resultados**
   - Actualiza contador: "Total conductores: 15 (Actualizado desde Arcángel)"
   - Abre `DriversModal` con tabla completa de conductores

4. **Usuario puede**:
   - Ver lista completa con scores y disponibilidad
   - Filtrar por disponibilidad, ciudad, tipo de vehículo
   - Cerrar modal y mantener información actualizada

5. **Usuario hace clic en "Registrar Llamadas"**
   - Inicia proceso normal de registro de llamadas
   - Usa datos actualizados si se ejecutó búsqueda previa

## 🧪 Testing

### Prueba Manual
1. Abrir cotización con conductores
2. Verificar botón "Ver Listado" visible
3. Hacer clic → debe mostrar spinner
4. Verificar modal abierto con datos
5. Verificar contador actualizado
6. Cerrar modal → datos deben persistir

### Casos de Error
- **Sin conexión API**: SweetAlert con mensaje de error
- **Cotización sin ID**: Console.error + mensaje
- **Sin resultados**: Modal muestra tabla vacía con mensaje
- **Timeout**: Error catch con notificación

## 📊 Beneficios

### Para el Usuario
✅ **Información en tiempo real**: Datos actualizados desde Arcángel al instante  
✅ **Mejor visibilidad**: Ve todos los conductores disponibles antes de llamar  
✅ **Decisiones informadas**: Puede evaluar scores y disponibilidad  
✅ **UX mejorada**: Acceso rápido sin salir de la vista actual  

### Para el Sistema
✅ **Eliminada dependencia local**: Solo busca en Arcángel API  
✅ **Datos centralizados**: Una sola fuente de verdad (Arcángel)  
✅ **Rendimiento**: Búsqueda on-demand, no carga innecesaria  
✅ **Escalabilidad**: Puede manejar miles de conductores sin problemas  

## 🔗 Archivos Relacionados

### Componentes
- `/resources/js/components/Calls/CallPanel.jsx` ← **Modificado**
- `/resources/js/components/CotizacionInicial/DriversModal.jsx` ← Reutilizado

### API
- `/app/Http/Controllers/Api/ArcangelDriversController.php`
- Endpoint: `POST /api/arcangel/buscar-conductores`

### Rutas
- `/routes/api.php` → Registro de endpoint bajo `/api/arcangel`

### Servicios
- `/app/Services/ArcangelService.php` → Conexión con API externa

## 📝 Notas Adicionales

### Dependencias
- **React Icons**: `react-icons/fa` (FaList para icono)
- **DriversModal**: Ya existente y funcional
- **Tailwind CSS**: Estilos para botones y layout

### Consideraciones
1. **CSRF Token**: Se envía en headers para seguridad Laravel
2. **Loading States**: Doble loading (botón registrar + botón ver listado)
3. **Estado Persistente**: `driversSearchData` persiste entre renders
4. **Memoria**: No se limpia automáticamente (considerar cleanup si necesario)

### Posibles Mejoras Futuras
- [ ] Caché de búsqueda por 5 minutos
- [ ] Botón "Actualizar" para refrescar datos
- [ ] Indicador visual cuando datos están desactualizados
- [ ] Toast notification en lugar de SweetAlert (más ligero)
- [ ] Paginación si hay >100 conductores

## ✅ Estado Actual

**Compilación**: ✅ Exitosa (30.48s)  
**Tests**: ⏳ Pendiente testing manual  
**Deployment**: ⏳ Pendiente en producción  

## 🚀 Cómo Usar

### Para Desarrolladores
```bash
# Compilar cambios
cd /home/ubuntu/conalca/conalca
npm run build

# Verificar archivo modificado
git diff resources/js/components/Calls/CallPanel.jsx
```

### Para Usuarios
1. Navegar a cualquier cotización
2. Localizar tarjeta "CONDUCTORES - Registro de Llamadas"
3. Hacer clic en botón azul "Ver Listado"
4. Revisar tabla completa de conductores disponibles
5. (Opcional) Registrar llamadas con botón rojo

---

**Autor**: GitHub Copilot  
**Fecha**: Diciembre 2024  
**Versión**: 1.0  
**Estado**: ✅ Implementado y Compilado
