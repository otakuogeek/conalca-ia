# 📋 ENDPOINT DE FILTRADO DE VEHÍCULOS - ARCANGEL API

## 🎯 Descripción

Nuevos endpoints especializados para consultar y filtrar vehículos disponibles en el sistema Arcangel, permitiendo búsquedas específicas por tipo de vehículo, score y ciudad.

---

## 🔗 Endpoints Disponibles

### 1. **Obtener Ciudades Disponibles**

```http
GET /api/arcangel/ciudades
```

**Parámetros Query (opcionales):**
- `use_cache` (boolean): Usar caché. Default: `true`

**Respuesta Exitosa (200):**
```json
{
  "success": true,
  "data": {
    "ciudades": [
      "ACACIAS",
      "AGUACHICA",
      "AGUSTIN CODAZZI",
      ...
      "ZIPAQUIRA"
    ],
    "total": 183,
    "cached_at": "2025-10-23 20:00:00"
  }
}
```

---

### 2. **Obtener Vehículos Cercanos**

```http
GET /api/arcangel/vehiculos/cercanos
```

**Parámetros Query (requeridos):**
- `ciudad` (string): Nombre de la ciudad. Ej: `BOGOTA`

**Parámetros Query (opcionales):**
- `use_cache` (boolean): Usar caché. Default: `true`

**Respuesta Exitosa (200):**
```json
{
  "success": true,
  "data": {
    "ciudad": "BOGOTA",
    "vehiculos": [
      {
        "placa": "KSQ762",
        "conductor": "JUAN CAMILO SOBA GIRALDO",
        "telefono": "3134634346",
        "clase": "SENCILLO",
        "score": 48
      },
      ...
    ],
    "total_vehiculos": 46
  }
}
```

---

### 3. **Filtrar Vehículos por Tipo** ⭐ NUEVO

```http
GET /api/arcangel/vehiculos/filtrar
```

**Parámetros Query (requeridos):**
- `ciudad` (string): Nombre de la ciudad. Ej: `BOGOTA`

**Parámetros Query (opcionales):**
- `clase` (string): Filtrar por UNA clase de vehículo. Ej: `TURBO`
- `clases[]` (array): Filtrar por MÚLTIPLES clases. Ej: `clases[]=TURBO&clases[]=CAMIONETA`
- `min_score` (integer): Score mínimo del vehículo (0-100). Ej: `30`
- `limit` (integer): Límite de resultados (1-500). Ej: `10`
- `use_cache` (boolean): Usar caché. Default: `true`

**Tipos de Clase Disponibles:**
- `SENCILLO` - Vehículos sencillos
- `TURBO` - Vehículos turbo
- `CAMIONETA` - Camionetas
- `PATINETA2` - Patinetas tipo 2
- `PATINETA3` - Patinetas tipo 3
- `TRACTOMULA 3` - Tractomulas tipo 3
- `TRACTOMULA3` - Tractomulas tipo 3 (variante)

**Respuesta Exitosa (200):**
```json
{
  "success": true,
  "data": {
    "ciudad": "BOGOTA",
    "filtros": {
      "clases": ["TURBO", "CAMIONETA"],
      "min_score": 30,
      "limit": 5
    },
    "vehiculos": [
      {
        "placa": "LUM519",
        "conductor": "LUIS HUMBERTO PARDO GUIZA",
        "telefono": "3114863109",
        "clase": "CAMIONETA",
        "score": 40
      },
      {
        "placa": "GQV137",
        "conductor": "NELSON ALEXANDER CORREA GUTIERREZ",
        "telefono": "3163099212",
        "clase": "TURBO",
        "score": 33
      },
      ...
    ],
    "total_vehiculos": 5,
    "total_original": 21
  }
}
```

---

### 4. **Obtener Clases de Vehículos Disponibles**

```http
GET /api/arcangel/vehiculos/clases
```

**Parámetros Query (requeridos):**
- `ciudad` (string): Nombre de la ciudad. Ej: `BOGOTA`

**Parámetros Query (opcionales):**
- `use_cache` (boolean): Usar caché. Default: `true`

**Respuesta Exitosa (200):**
```json
{
  "success": true,
  "data": {
    "ciudad": "BOGOTA",
    "clases": {
      "PATINETA2": 14,
      "CAMIONETA": 12,
      "TURBO": 9,
      "SENCILLO": 6,
      "TRACTOMULA 3": 3,
      "PATINETA3": 1,
      "TRACTOMULA3": 1
    },
    "total_clases": 7,
    "total_vehiculos": 46
  }
}
```

---

## 📝 Ejemplos de Uso

### Ejemplo 1: Filtrar solo vehículos TURBO en Bogotá

```bash
curl -X GET "https://conalcaia.conalca.com.co/api/arcangel/vehiculos/filtrar?ciudad=BOGOTA&clase=TURBO" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Ejemplo 2: Filtrar CAMIONETA o TURBO con score mínimo 30

```bash
curl -X GET "https://conalcaia.conalca.com.co/api/arcangel/vehiculos/filtrar?ciudad=BOGOTA&clases[]=CAMIONETA&clases[]=TURBO&min_score=30" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Ejemplo 3: Top 5 vehículos SENCILLO en Medellín

```bash
curl -X GET "https://conalcaia.conalca.com.co/api/arcangel/vehiculos/filtrar?ciudad=MEDELLIN&clase=SENCILLO&limit=5" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Ejemplo 4: Obtener clases disponibles en Cali

```bash
curl -X GET "https://conalcaia.conalca.com.co/api/arcangel/vehiculos/clases?ciudad=CALI" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Ejemplo 5: Todos los vehículos con score >= 40

```bash
curl -X GET "https://conalcaia.conalca.com.co/api/arcangel/vehiculos/filtrar?ciudad=BOGOTA&min_score=40" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## 🔐 Autenticación

Todos los endpoints (excepto `/health`) requieren autenticación mediante **Laravel Sanctum**.

**Header requerido:**
```
Authorization: Bearer {token}
```

---

## ⚡ Caché y Rendimiento

- **Token de Arcangel**: Se almacena en caché por 55 minutos
- **Resultados de vehículos**: Se almacenan en caché por 30 minutos (configurable)
- **Ciudades**: Se almacenan en caché por 60 minutos

Para forzar actualización sin caché:
```bash
?use_cache=false
```

---

## ❌ Códigos de Error

| Código | Descripción |
|--------|-------------|
| 200 | Éxito |
| 401 | No autenticado |
| 422 | Datos de entrada inválidos |
| 500 | Error del servidor |
| 503 | API Arcangel no disponible |

**Ejemplo de respuesta de error:**
```json
{
  "success": false,
  "message": "Datos de entrada inválidos",
  "errors": {
    "ciudad": ["El campo ciudad es obligatorio."]
  }
}
```

---

## 🧪 Casos de Uso Comunes

### 1. **Asignación automática de vehículos**
Filtrar vehículos disponibles según el tipo de carga y obtener el mejor conductor:

```javascript
// Filtrar camionetas con buen score
const response = await axios.get('/api/arcangel/vehiculos/filtrar', {
  params: {
    ciudad: 'BOGOTA',
    clase: 'CAMIONETA',
    min_score: 30,
    limit: 1
  }
});

const mejorConductor = response.data.data.vehiculos[0];
```

### 2. **Dashboard de disponibilidad**
Mostrar estadísticas de vehículos por tipo:

```javascript
const clases = await axios.get('/api/arcangel/vehiculos/clases', {
  params: { ciudad: 'BOGOTA' }
});

// Renderizar gráfico con clases.data.data.clases
```

### 3. **Búsqueda avanzada**
Permitir al usuario filtrar por múltiples criterios:

```javascript
const filtros = {
  ciudad: selectedCity,
  clases: ['TURBO', 'SENCILLO'],
  min_score: 25,
  limit: 20
};

const vehiculos = await axios.get('/api/arcangel/vehiculos/filtrar', {
  params: filtros
});
```

---

## 📊 Métricas y Logging

Todas las consultas se registran en los logs de Laravel con la siguiente información:

```
[INFO] ArcangelService: Vehículos filtrados
  - ciudad: BOGOTA
  - clases: ["TURBO","CAMIONETA"]
  - min_score: 30
  - limit: 5
  - total_original: 46
  - total_filtrado: 5
```

---

## 🚀 Próximas Mejoras

- [ ] Filtrado por rango de score (min/max)
- [ ] Ordenamiento personalizado (score, nombre, placa)
- [ ] Búsqueda por nombre de conductor
- [ ] Filtrado por disponibilidad en tiempo real
- [ ] Geolocalización y distancia
- [ ] Historial de asignaciones por conductor

---

## 📞 Soporte

Para reportar problemas o solicitar nuevas funcionalidades, contactar al equipo de desarrollo.

**Documentación actualizada:** 23 de octubre de 2025
