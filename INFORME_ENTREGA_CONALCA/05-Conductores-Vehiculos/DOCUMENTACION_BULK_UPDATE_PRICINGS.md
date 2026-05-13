# Documentacion API - Pricings

> **Ultima actualizacion**: 24 de Marzo, 2026
> **Version API**: 1.2
> **Base URL Produccion**: `https://conalcaia.conalca.com.co`

---

## Autenticacion

Todos los endpoints requieren autenticacion via **Sanctum (Bearer Token)**.

### Headers obligatorios
```
Authorization: Bearer {tu_token_sanctum}
Content-Type: application/json
Accept: application/json
```

> **IMPORTANTE**: Si el token es invalido o no se envia, el API retorna **401** con:
> ```json
> {"message": "Unauthenticated."}
> ```

### Como obtener un token
Solicitar al administrador del sistema que genere un token Sanctum para tu usuario.

---

## 1. Listar Pricings (GET)

```
GET /api/pricings
```

### Descripcion
Lista los registros de pricing con **paginacion** y filtros opcionales.

> **Nota**: La tabla tiene +230,000 registros. Siempre se devuelve paginado (maximo 500 por pagina).

### Parametros de Query

| Parametro | Tipo | Default | Descripcion |
|-----------|------|---------|-------------|
| `per_page` | integer | 100 | Registros por pagina (max 500) |
| `page` | integer | 1 | Numero de pagina |
| `origin` | string | - | Filtrar por ciudad origen (busqueda parcial) |
| `destination` | string | - | Filtrar por ciudad destino (busqueda parcial) |
| `vehicle_type` | string | - | Filtrar por tipo de vehiculo (busqueda parcial) |

### Ejemplo: Listar con filtros

```bash
curl -X GET "https://conalcaia.conalca.com.co/api/pricings?origin=CALI&destination=CARTAGENA&per_page=10" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### Respuesta Exitosa (200)
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 49452,
      "vehicle_type": "RIGIDO",
      "origin": "CALI",
      "destination": "CARTAGENA",
      "price": "3342855",
      "weight": "800",
      ...
    }
  ],
  "last_page": 75,
  "per_page": 10,
  "total": 745
}
```

---

## 2. Consultar un Pricing (GET por ID)

```
GET /api/pricings/{id}
```

### Ejemplo
```bash
curl -X GET "https://conalcaia.conalca.com.co/api/pricings/58727" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## 3. Bulk Create (POST)

### Endpoint
```
POST /api/pricings/bulk
```

### Descripcion
Crea multiples registros de pricing en una sola transaccion.

### Payload (Request Body)

```json
{
  "items": [
    {
      "origin": "BOGOTA",
      "destination": "CALI",
      "vehicle_type": "SENCILLO",
      "weight": 1500,
      "price": 500000
    },
    {
      "origin": "MEDELLIN",
      "destination": "CARTAGENA",
      "vehicle_type": "DOBLETROQUE",
      "weight": 3000,
      "price": 1200000
    }
  ]
}
```

### Parametros

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `items` | array | ✅ Si | Array con los registros a crear (minimo 1) |
| `items[].origin` | string | ✅ Si | Ciudad de origen (max. 255 caracteres) |
| `items[].destination` | string | ✅ Si | Ciudad de destino (max. 255 caracteres) |
| `items[].vehicle_type` | string | ✅ Si | Tipo de vehiculo (max. 255 caracteres) |
| `items[].weight` | numeric | ✅ Si | Peso en kilogramos |
| `items[].price` | numeric | ✅ Si | Precio en pesos colombianos |

### Validaciones
- ✅ Todos los campos son obligatorios para cada item
- ✅ Se debe enviar al menos 1 item
- ✅ Transaccional: si falla uno, no se crea ninguno

### Respuesta Exitosa (201 Created)

```json
{
  "message": "Registros creados correctamente",
  "total": 2,
  "pricings": [
    {
      "origin": "BOGOTA",
      "destination": "CALI",
      "vehicle_type": "SENCILLO",
      "weight": 1500,
      "price": 500000,
      "updated_at": "2026-03-24T16:35:49.000000Z",
      "created_at": "2026-03-24T16:35:49.000000Z",
      "id": 278770
    },
    {
      "origin": "MEDELLIN",
      "destination": "CARTAGENA",
      "vehicle_type": "DOBLETROQUE",
      "weight": 3000,
      "price": 1200000,
      "updated_at": "2026-03-24T16:35:49.000000Z",
      "created_at": "2026-03-24T16:35:49.000000Z",
      "id": 278771
    }
  ]
}
```

### Error 422 - Campos faltantes

```json
{
  "message": "The items.0.destination field is required. (and 3 more errors)",
  "errors": {
    "items.0.destination": ["The items.0.destination field is required."],
    "items.0.vehicle_type": ["The items.0.vehicle_type field is required."],
    "items.0.weight": ["The items.0.weight field is required."],
    "items.0.price": ["The items.0.price field is required."]
  }
}
```

### Ejemplo: curl

```bash
curl -X POST "https://conalcaia.conalca.com.co/api/pricings/bulk" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "items": [
      {
        "origin": "BOGOTA",
        "destination": "CALI",
        "vehicle_type": "SENCILLO",
        "weight": 1500,
        "price": 500000
      }
    ]
  }'
```

---

## 4. Bulk Update (PUT)

### Endpoint
```
PUT /api/pricings/bulk
```

### Descripcion
Actualiza multiples registros de pricing en una sola transaccion. Permite actualizar solo los campos especificados para cada registro.

### Payload (Request Body)

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
curl -X PUT "https://conalcaia.conalca.com.co/api/pricings/bulk" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "items": [
      {"id": 58727, "price": 1300000},
      {"id": 58728, "price": 750000}
    ]
  }'
```

### Ejemplo 2: Actualizar campos mixtos

```bash
curl -X PUT "https://conalcaia.conalca.com.co/api/pricings/bulk" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "items": [
      {
        "id": 58727,
        "origin": "BOGOTA",
        "price": 550000
      },
      {
        "id": 58728,
        "vehicle_type": "TRACTOMULA3",
        "weight": 6000
      }
    ]
  }'
```

### Ejemplo 3: JavaScript/Axios

```javascript
import axios from 'axios';

const TOKEN = 'tu_token_sanctum';

const bulkUpdate = async () => {
  try {
    const response = await axios.put(
      'https://conalcaia.conalca.com.co/api/pricings/bulk',
      {
        items: [
          { id: 58727, price: 500000, weight: 1500 },
          { id: 58728, origin: 'CALI', destination: 'BOGOTA' },
        ]
      },
      {
        headers: {
          'Authorization': `Bearer ${TOKEN}`,
          'Accept': 'application/json',
        }
      }
    );
    
    console.log('Actualizados:', response.data.total);
    console.log('Registros:', response.data.pricings);
  } catch (error) {
    if (error.response?.status === 401) {
      console.error('Token invalido o expirado');
    } else {
      console.error('Error:', error.response.data);
    }
  }
};
```

### Ejemplo 4: Postman

**Configuracion en Postman:**
1. Metodo: **PUT**
2. URL: `https://conalcaia.conalca.com.co/api/pricings/bulk`
3. Pestana **Authorization**: Tipo "Bearer Token", pegar tu token
4. Pestana **Headers**: Agregar `Accept: application/json`
5. Pestana **Body**: raw > JSON:
```json
{
  "items": [
    {
      "id": 58727,
      "price": 1300000
    }
  ]
}
```

> **Tip Postman**: Si recibes HTML en vez de JSON, verifica que:
> - El header `Accept: application/json` este presente
> - El token Bearer sea valido y no haya expirado

## Caracteristicas

- **Transaccional**: Todos los cambios se hacen en una transaccion DB. Si falla uno, se revierten todos.
- **Actualizacion Parcial**: Solo actualiza los campos enviados, los demas se mantienen.
- **Validacion Individual**: Cada item se valida por separado con mensajes claros.
- **Sin Duplicados**: Los IDs duplicados en el mismo request son rechazados.
- **Eficiente**: Una sola peticion para actualizar multiples registros.

## Notas Importantes

- La actualizacion es **todo o nada**: si un item falla, se revierten todos los cambios.
- Los campos `created_at` y `updated_at` se manejan automaticamente.
- El campo `id` no se puede actualizar, solo se usa para identificar el registro.
- No hay limite de items, pero se recomienda no exceder 100 por request para mejor performance.

## Errores Comunes

| Codigo | Causa | Solucion |
|--------|-------|----------|
| **401** | Token invalido, expirado o ausente | Verificar header `Authorization: Bearer {token}` |
| **422** | Datos invalidos o ID no existe | Revisar el campo `errors` en la respuesta |
| **500** | Error interno del servidor | Reportar al equipo de desarrollo |

## Relacion con Otros Endpoints

| Endpoint | Metodo | Proposito |
|----------|--------|-----------|
| `/api/pricings` | GET | **Listar con paginacion y filtros** |
| `/api/pricings/{id}` | GET | Consultar un registro |
| `/api/pricings` | POST | Crear un registro |
| `/api/pricings/bulk` | POST | Crear multiples registros |
| `/api/pricings/bulk` | **PUT** | **Actualizar multiples registros** |
| `/api/pricings/bulk` | DELETE | Eliminar multiples registros |
| `/api/pricings/{id}` | PUT | Actualizar un solo registro |
| `/api/pricings/{id}` | DELETE | Eliminar un solo registro |

---

**Fecha de creacion**: 17 de Noviembre, 2025
**Ultima actualizacion**: 24 de Marzo, 2026
**Version API**: 1.2
**Rutas Laravel**: `pricings.bulk.store`, `pricings.bulk.update`, `pricings.bulk.delete`
