# 📝 Documentación API - Bulk Update Pricings

## Endpoint
```
PUT /api/pricings/bulk
```

## Descripción
Actualiza múltiples registros de pricing en una sola transacción. Permite actualizar solo los campos especificados para cada registro.

## Headers
```json
{
  "Content-Type": "application/json",
  "Accept": "application/json"
}
```

## Payload (Request Body)

```json
{
  "items": [
    {
      "id": 1,
      "price": 500000,
      "weight": 1500
    },
    {
      "id": 2,
      "origin": "BOGOTA",
      "destination": "MEDELLIN",
      "vehicle_type": "TRACTOMULA3",
      "weight": 5000,
      "price": 1200000
    },
    {
      "id": 3,
      "price": 800000
    }
  ]
}
```

### Parámetros

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `items` | array | ✅ Sí | Array con los registros a actualizar (mínimo 1) |
| `items[].id` | integer | ✅ Sí | ID del pricing a actualizar (debe existir) |
| `items[].origin` | string | ❌ No | Ciudad de origen (máx. 255 caracteres) |
| `items[].destination` | string | ❌ No | Ciudad de destino (máx. 255 caracteres) |
| `items[].vehicle_type` | string | ❌ No | Tipo de vehículo (máx. 255 caracteres) |
| `items[].weight` | numeric | ❌ No | Peso en kilogramos |
| `items[].price` | numeric | ❌ No | Precio en pesos colombianos |

### Validaciones
- ✅ Todos los IDs deben existir en la tabla `pricings`
- ✅ No se permiten IDs duplicados en el mismo request
- ✅ Se deben enviar al menos 1 item
- ✅ Solo se actualizan los campos enviados (actualización parcial)

## Respuesta Exitosa (200 OK)

```json
{
  "message": "Registros actualizados correctamente",
  "total": 3,
  "pricings": [
    {
      "id": 1,
      "origin": "FUNZA",
      "destination": "CALI",
      "vehicle_type": "SENCILLO",
      "weight": 1500,
      "price": 500000,
      "created_at": "2025-11-17T10:30:00.000000Z",
      "updated_at": "2025-11-17T14:15:00.000000Z"
    },
    {
      "id": 2,
      "origin": "BOGOTA",
      "destination": "MEDELLIN",
      "vehicle_type": "TRACTOMULA3",
      "weight": 5000,
      "price": 1200000,
      "created_at": "2025-11-17T10:30:00.000000Z",
      "updated_at": "2025-11-17T14:15:00.000000Z"
    },
    {
      "id": 3,
      "origin": "CARTAGENA",
      "destination": "BUENAVENTURA",
      "vehicle_type": "DOBLETROQUE",
      "weight": 3000,
      "price": 800000,
      "created_at": "2025-11-17T10:30:00.000000Z",
      "updated_at": "2025-11-17T14:15:00.000000Z"
    }
  ]
}
```

## Respuestas de Error

### Error 422 - Validación Fallida

```json
{
  "message": "Error en el item #1",
  "errors": {
    "id": ["The id field is required."],
    "price": ["The price must be a number."]
  }
}
```

### Error 422 - ID No Existe

```json
{
  "message": "The items.0.id field must exist in pricings table.",
  "errors": {
    "items.0.id": [
      "The selected items.0.id is invalid."
    ]
  }
}
```

### Error 422 - IDs Duplicados

```json
{
  "message": "The items.0.id field has a duplicate value.",
  "errors": {
    "items.1.id": [
      "The items.1.id field has a duplicate value."
    ]
  }
}
```

## Ejemplos de Uso

### Ejemplo 1: Actualizar solo precios

```bash
curl -X PUT http://localhost/api/pricings/bulk \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {"id": 1, "price": 600000},
      {"id": 2, "price": 750000},
      {"id": 3, "price": 900000}
    ]
  }'
```

### Ejemplo 2: Actualizar campos mixtos

```bash
curl -X PUT http://localhost/api/pricings/bulk \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {
        "id": 1,
        "origin": "BOGOTA",
        "price": 550000
      },
      {
        "id": 2,
        "vehicle_type": "TRACTOMULA3",
        "weight": 6000
      }
    ]
  }'
```

### Ejemplo 3: JavaScript/Axios

```javascript
import axios from 'axios';

const bulkUpdate = async () => {
  try {
    const response = await axios.put('/api/pricings/bulk', {
      items: [
        { id: 1, price: 500000, weight: 1500 },
        { id: 2, origin: 'CALI', destination: 'BOGOTA' },
        { id: 3, vehicle_type: 'DOBLETROQUE', price: 850000 }
      ]
    });
    
    console.log('Actualizados:', response.data.total);
    console.log('Registros:', response.data.pricings);
  } catch (error) {
    console.error('Error:', error.response.data);
  }
};
```

### Ejemplo 4: PHP/Laravel HTTP Client

```php
use Illuminate\Support\Facades\Http;

$response = Http::put('http://localhost/api/pricings/bulk', [
    'items' => [
        ['id' => 1, 'price' => 500000],
        ['id' => 2, 'price' => 750000, 'weight' => 2000],
        ['id' => 3, 'origin' => 'MEDELLIN', 'destination' => 'BOGOTA']
    ]
]);

$data = $response->json();
echo "Total actualizado: {$data['total']}\n";
```

## Características

✅ **Transaccional**: Todos los cambios se hacen en una transacción DB. Si falla uno, se revierten todos.
✅ **Actualización Parcial**: Solo actualiza los campos enviados, los demás se mantienen.
✅ **Validación Individual**: Cada item se valida por separado con mensajes claros.
✅ **Sin Duplicados**: Los IDs duplicados en el mismo request son rechazados.
✅ **Eficiente**: Una sola petición para actualizar múltiples registros.

## Notas Importantes

- ⚠️ La actualización es **todo o nada**: si un item falla, se revierten todos los cambios.
- ⚠️ Los campos `created_at` y `updated_at` se manejan automáticamente.
- ⚠️ El campo `id` no se puede actualizar, solo se usa para identificar el registro.
- ⚠️ No hay límite de items, pero se recomienda no exceder 100 por request para mejor performance.

## Relación con Otros Endpoints

| Endpoint | Método | Propósito |
|----------|--------|-----------|
| `/api/pricings/bulk` | POST | Crear múltiples registros |
| `/api/pricings/bulk` | **PUT** | **Actualizar múltiples registros** |
| `/api/pricings/bulk` | DELETE | Eliminar múltiples registros |
| `/api/pricings/{id}` | PUT | Actualizar un solo registro |

---

**Fecha de creación**: 17 de Noviembre, 2025  
**Versión API**: 1.0  
**Ruta Laravel**: `pricings.bulk.update`
