# 🗺️ MÓDULO: ANÁLISIS › RUTAS DE TRANSPORTE

## 1. ¿Para qué es este módulo?
Es un panel de **análisis visual geográfico**. Muestra en un mapa de Colombia las rutas más operadas, la densidad de cotizaciones por ciudad, y patrones geográficos del negocio. Ayuda a la gerencia comercial a tomar decisiones estratégicas (en qué ciudades expandir, qué rutas priorizar, dónde reforzar conductores).

## 2. ¿Qué hace?
- Renderiza un **mapa interactivo de Colombia** (Leaflet).
- Marca **ciudades** con densidad de cotizaciones (heatmap o burbujas).
- Dibuja **líneas de rutas** entre origen-destino más usadas.
- Muestra **tendencias temporales** (cotizaciones por mes, comparativo año vs año).
- Permite **filtrar por:** rango de fechas, tipo de operación, vehículo, estado.
- Exporta datos a Excel.

## 3. ¿Cómo funciona?

### Flujo de carga
```
1. Usuario entra a /analysis
2. AnalysisController@show renderiza vista
3. Vista carga Leaflet + leaflet-routing-machine
4. AJAX → /analysis/data → AnalysisController@getAnalysisDataApi
5. Backend consulta:
   - GroupCotization + CotizacionModel (rutas y volumen)
   - City (coordenadas geo)
   - DriverCallResponse (llamadas exitosas)
6. Devuelve JSON estructurado:
   { 
     cities: [{name, lat, lng, count}],
     routes: [{origin, destination, count, value}],
     monthly: [{month, total, accepted}]
   }
7. JS dibuja markers, líneas y gráficos
```

### Filtros
```
Cambio de filtro → re-fetch /analysis/data con query params
    ↓
Backend re-consulta con WHERE date >= X AND date <= Y
    ↓
Frontend re-dibuja
```

## 4. ¿Cómo actúa en el sistema?
Es **sólo lectura analítica** — agrega data histórica sin modificar nada.

Consume datos de:
- `GroupCotization` (grupos de cotizaciones)
- `CotizacionModel` (rutas individuales)
- `City` (catálogo con lat/lng y código DANE)
- `DriverCallResponse` (efectividad de llamadas por ruta)
- `Pricing` (rentabilidad por ruta)

## 5. Componentes del código

### Rutas
| Método | URL | Controlador | Función |
|--------|-----|-------------|---------|
| GET | `/analysis` | `AnalysisController@show` | Vista del panel |
| GET | `/analysis/data` | `AnalysisController@getAnalysisDataApi` | JSON de datos |

### Controlador
**Archivo:** `app/Http/Controllers/AnalysisController.php`

**Métodos:**
- `show()` — renderiza vista Blade
- `debugCities()` — endpoint debug para verificar datos
- `getMapDataEndpoint()` — datos del mapa específicamente
- `getAnalysisDataApi()` — todos los datos para los gráficos

### Vista
**`resources/views/analysis/show.blade.php`** — incluye el mapa + gráficos + filtros.

### JavaScript
- `resources/js/analysis.js` — lógica del módulo
- Librerías:
  - **leaflet 1.9.4** — mapa base
  - **leaflet-routing-machine 3.2.12** — rutas
  - **Chart.js 4.5** — gráficos
  - **chartjs-chart-geo 4.2.5** — gráficos geográficos
  - **datamaps 0.5.9** — mapas alternativos

### Tablas consultadas
- `group_cotizations`
- `cotizacion_models`
- `cities` (con lat/lng)
- `driver_call_responses`
- `clients`

## 6. Datos visualizados típicamente
- **Top 10 rutas más cotizadas** (origen → destino con conteo)
- **Top 10 ciudades por volumen** (heatmap)
- **% aceptación por ruta** (verde=>80%, amarillo=50-80%, rojo<50%)
- **Valor total cotizado por mes** (línea temporal)
- **Tipos de vehículos más solicitados**
- **Comparativo año actual vs año anterior**

## 7. Reglas de negocio
- **Período por defecto:** últimos 12 meses
- **Filtro por rol:**
  - SUPER ADMIN / JEFE COMERCIAL → todo
  - GERENTE DE CUENTA → solo sus clientes
  - Otros → no tienen acceso
- **Ciudades sin geo:** se muestran como lista, no en el mapa
- **Cache:** considera implementar Redis cache de los datasets con TTL de 15 min (no implementado actualmente)

## 8. Permisos
| Rol | Acceso |
|-----|:------:|
| SUPER ADMIN | ✅ |
| JEFE COMERCIAL | ✅ |
| GERENTE DE CUENTA | ✅ |
| ASISTENTE COMERCIAL | ❌ |
| SAC | ❌ |
| PRICING | ❌ |

## 9. Performance
- **Consultas pesadas:** agregaciones con GROUP BY ciudades y meses
- **Optimización:** asegurar índices en:
  - `cotizacion_models.created_at`
  - `cotizacion_models.ciudad_origen`, `ciudad_destino`
  - `group_cotizations.user_id`, `client_id`
- **Migración relevante:** `2026_02_16_192907_add_performance_indexes_to_cotizaciones_tables.php`

## 10. Mantenimiento
- **Actualizar catálogo de ciudades:** asegurar que `cities` tenga `latitud`, `longitud` para todas las ciudades de Colombia (códigos DANE).
- **Calidad del dato:** si las cotizaciones tienen ciudades mal escritas (sin normalizar), no aparecerán en el mapa. El servicio `DataExtractionService` normaliza, pero hay datos legacy.

## 11. Archivos clave
- `app/Http/Controllers/AnalysisController.php`
- `resources/views/analysis/show.blade.php`
- `resources/js/analysis.js`
- `app/Models/City.php`
- Migración: `2024_03_21_164438_create_cities_table.php`

## 12. Relacionado con
- [01-Dashboard](../01-Dashboard/) — algunos KPIs vienen del mismo dataset
- [07-Analisis-Llamadas-ElevenLabs](../07-Analisis-Llamadas-ElevenLabs/) — datos de llamadas por ruta
- [09-Pricing](../09-Pricing/) — rentabilidad por ruta

## 13. Mejoras pendientes
- Cache de datasets (Redis) para reducir consultas
- Exportar mapa como imagen (PNG)
- Filtros geográficos (departamento, región)
- Heatmap de llamadas exitosas vs fallidas por ciudad
