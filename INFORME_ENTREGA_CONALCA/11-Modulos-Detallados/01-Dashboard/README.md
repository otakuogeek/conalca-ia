# 📊 MÓDULO: DASHBOARD

## 1. ¿Para qué es este módulo?
Es la **página de inicio** del sistema. Cuando un usuario entra al sistema después de hacer login, esta es la primera pantalla que ve. Muestra una visión general (KPIs, gráficos y alertas) del estado comercial y operativo según el rol del usuario.

## 2. ¿Qué hace?
- Muestra **indicadores clave (KPIs)**: total de cotizaciones, cotizaciones aceptadas, llamadas realizadas, conductores activos.
- Renderiza **gráficos** de tendencias (cotizaciones por mes, % aceptación, llamadas exitosas).
- Visualiza **mapa de Colombia** con la densidad de operaciones por ciudad.
- Muestra **metas en curso** del usuario y su progreso.
- Despliega **alertas** y notificaciones recientes.

## 3. ¿Cómo funciona?

### Flujo técnico
```
Usuario entra a /dashboard
    ↓
DashboardController@show carga la vista
    ↓
Frontend JS (dashboard.js, dashboard-chart.js)
    ↓
AJAX a /dashboard/chart-data
    ↓
DashboardController@getChartData consulta BD según rol
    ↓
Devuelve JSON con datasets
    ↓
Chart.js renderiza gráficos + chartjs-chart-geo dibuja mapa
```

### Datos según rol
- **SUPER ADMIN / JEFE COMERCIAL:** ve TODOS los datos del sistema.
- **GERENTE DE CUENTA:** ve datos de los clientes que tiene asignados.
- **ASISTENTE COMERCIAL:** sólo ve sus propias cotizaciones.
- **SAC:** ve métricas de llamadas y conductores.
- **PRICING:** ve métricas de pricing y rentabilidad.

## 4. ¿Cómo actúa en el sistema?
Es **sólo lectura**: agrega información de muchos módulos pero no modifica nada. Consulta:
- `GroupCotization` + `CotizacionModel` (cotizaciones)
- `Llamada` + `DriverCallResponse` (llamadas)
- `Goal` (metas)
- `Client` (clientes)
- `User` (filtros por jerarquía)

## 5. Componentes del código

### Rutas (`routes/web.php`)
| Método | URL | Controlador | Middleware |
|--------|-----|-------------|------------|
| GET | `/dashboard` | `DashboardController@show` | `auth` |
| GET | `/dashboard/chart-data` | `DashboardController@getChartData` | `auth` |

### Controlador
**Archivo:** `app/Http/Controllers/DashboardController.php`
- `show()` — renderiza vista Blade
- `getChartData(Request $request)` — endpoint JSON con datasets

### Vista Blade
**Archivo:** `resources/views/dashboardChart/`
- Incluye varios componentes:
  - Cards de KPI
  - Gráficos Chart.js
  - Mapa Colombia
  - Widgets de alertas

### JavaScript
- `resources/js/dashboard.js` — lógica del dashboard
- `resources/js/dashboard-chart.js` — wrapper de Chart.js
- Librerías: **Chart.js 4.5**, **chartjs-chart-geo 4.2**, **datamaps**

## 6. Permisos
Todos los roles autenticados pueden acceder, pero el contenido se filtra automáticamente.

## 7. Mantenimiento y operación
- **Performance:** los gráficos consultan agregaciones — si la BD crece mucho, considerar cache (Redis) de resultados con TTL de 5–15 min.
- **Personalización:** los datasets se pueden ampliar editando `getChartData()`.

## 8. Archivos clave
- `app/Http/Controllers/DashboardController.php`
- `resources/views/dashboardChart/`
- `resources/js/dashboard.js`
- `resources/js/dashboard-chart.js`

## 9. Relacionado con
- [02-Clientes](../02-Clientes/) — fuente de datos de clientes activos
- [09-Pricing](../09-Pricing/) — métricas de rentabilidad
- [10-Gestion-Metas](../10-Gestion-Metas/) — barras de progreso de metas
