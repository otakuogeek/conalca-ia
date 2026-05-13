# 🎯 MÓDULO: GESTIÓN › METAS

## 1. ¿Para qué es este módulo?
Es el sistema de **gestión de metas comerciales** mensuales. Permite al jefe comercial definir cuánto debe vender cada ejecutivo en el mes, y monitorea automáticamente el cumplimiento, generando notificaciones cuando hay alertas o logros.

## 2. ¿Qué hace?
- Permite al **jefe** definir metas mensuales por comercial (monto en dinero).
- Cada **comercial** puede ver su meta personal y su progreso.
- Calcula automáticamente el **monto aceptado** del mes corriente (cotizaciones aceptadas).
- Genera **notificaciones al jefe** cuando:
  - Un comercial cumple la meta (logro)
  - Un comercial está atrasado (alerta)
  - Fin del mes con meta no cumplida (incumplimiento)
- Mantiene **histórico** de metas por mes/año.

## 3. ¿Cómo funciona?

### Flujo del jefe definiendo metas
```
1. Jefe entra a /goals
2. Ve listado de comerciales bajo su línea
3. Define meta mensual por cada uno
4. PUT /goals/{goal} → GoalController@update
5. Se guarda goals.target_amount
```

### Flujo del comercial viendo su meta
```
1. Comercial entra a /my-goal
2. GoalController@myGoal calcula:
   - target_amount (meta)
   - accepted_amount (PerformanceService.calculateAcceptedAmount)
   - percentage_completion
3. Renderiza barra de progreso visual
```

### Cálculo automático (cron mensual)
```
Primer día del mes a las 00:00:
    ↓
php artisan goals:evaluate-monthly (EvaluateMonthlyGoals)
    ↓
Por cada comercial:
  - Calcula monto del mes anterior
  - Compara con target
  - Si <80% → notificación de incumplimiento al jefe
  - Si ≥100% → notificación de logro
    ↓
GoalStatusNotification se envía vía buzón / email
```

## 4. ¿Cómo actúa en el sistema?
Se integra con:
- **Cotizaciones:** suma los montos de cotizaciones aceptadas
- **Usuarios:** identifica jefe vs comercial vía relación `creator_id` (jerarquía)
- **Buzón / Notifications:** envía alertas
- **Dashboard:** muestra barra de progreso por comercial

## 5. Componentes del código

### Rutas
| Método | URL | Función |
|--------|-----|---------|
| GET | `/goals` | Listar metas (jefe) |
| PUT | `/goals/{goal}` | Actualizar meta |
| GET | `/my-goal` | Mi meta (comercial) |
| GET | `/boss/notifications` | Notificaciones jefe |
| POST | `/boss/notifications/{id}/read` | Marcar leída |
| GET | `/goal-management` | Vista admin metas |

### Controlador
**`app/Http/Controllers/Api/GoalController.php`** y `App\Http\Controllers\GoalController.php`

**Métodos:**
- `index()` — listado de metas
- `update($goal)` — actualizar meta
- `myGoal()` — meta personal con progreso
- `notifications()` — notificaciones para jefe
- `markNotificationRead($id)` — marcar leída

### Modelo
**`Goal`** (`goals` table):
- `commercial_id` → User (ejecutivo)
- `boss_id` → User (jefe)
- `year`, `month`
- `target_amount` — meta en pesos
- `accepted_amount` (calculado dinámicamente)
- `status` — `pending`, `achieved`, `failed`

**Relaciones:**
- `commercial()` → User
- `boss()` → User

### Servicio
**`app/Services/PerformanceService.php`**
- `calculateAcceptedAmount(User $commercial, int $year, int $month)` — suma cotizaciones aceptadas

### Comando Artisan
**`EvaluateMonthlyGoals`** — evalúa metas del mes y dispara notificaciones.

```bash
# Manual
php artisan goals:evaluate-monthly

# Cron sugerido (primer día del mes a las 00:00)
0 0 1 * * php artisan goals:evaluate-monthly
```

### Notification
**`app/Notifications/GoalStatusNotification.php`** — Spatie/Laravel Notification que se envía al jefe.

### Vista
**`resources/views/goals/index.blade.php`**

## 6. Reglas de negocio
- **Una meta por (commercial, year, month)** — unique constraint
- **Sólo aceptadas cuentan:** la suma usa `cotizaciones.decision_cliente='aceptada'`
- **Estados:**
  - `pending` (mes en curso)
  - `achieved` (≥100% al cierre)
  - `failed` (<100% al cierre)
- **Histórico:** las metas pasadas quedan registradas para análisis comparativo

## 7. Cálculo de monto aceptado
```php
PerformanceService.calculateAcceptedAmount(User $commercial, int $year, int $month)
    ↓
Query:
SELECT SUM(valor) 
FROM cotizacion_models cm
JOIN group_cotizations gc ON cm.group_cotization_id = gc.id
WHERE gc.user_id = {commercial_id}
  AND gc.decision_cliente = 'aceptada'
  AND YEAR(gc.updated_at) = {year}
  AND MONTH(gc.updated_at) = {month}
```

## 8. Permisos
| Rol | Ver | Crear/Editar Metas | Notificaciones |
|-----|:---:|:------------------:|:--------------:|
| SUPER ADMIN | ✅ Todas | ✅ Todas | ✅ |
| JEFE COMERCIAL | ✅ Su línea | ✅ Su línea | ✅ |
| GERENTE DE CUENTA | ✅ Su línea | ✅ Su línea | ✅ |
| ASISTENTE COMERCIAL | ✅ Propia | ❌ | ❌ |
| SAC | ❌ | ❌ | ❌ |
| PRICING | ❌ | ❌ | ❌ |

## 9. Mantenimiento
- **Cron del comando:** asegurar que `goals:evaluate-monthly` corra mensualmente
- **Auditoría:** las metas pasadas no se eliminan
- **Performance:** indexar `goals.commercial_id`, `year`, `month`

## 10. Archivos clave
- `app/Http/Controllers/Api/GoalController.php`
- `app/Http/Controllers/GoalController.php` (web)
- `app/Models/Goal.php`
- `app/Services/PerformanceService.php`
- `app/Console/Commands/EvaluateMonthlyGoals.php`
- `app/Notifications/GoalStatusNotification.php`
- `resources/views/goals/index.blade.php`

## 11. Migraciones
- `2025_07_10_145506_create_goals_table.php`
- `2025_07_10_145902_create_notifications_table.php`

## 12. Relacionado con
- [01-Dashboard](../01-Dashboard/) — muestra progreso
- [05-Buzon](../05-Buzon/) — recibe notificaciones
- [11-Gestion-Novedades-Alertas](../11-Gestion-Novedades-Alertas/) — centraliza alertas
- [17-Control-Usuarios](../17-Control-Usuarios/) — jerarquía comercial

## 13. Ejemplo de notificación al jefe
```
📢 Carlos Pérez está al 65% de su meta de Octubre 2026
   Faltan 8 días para terminar el mes
   Meta: $120,000,000
   Aceptado: $78,000,000
```
