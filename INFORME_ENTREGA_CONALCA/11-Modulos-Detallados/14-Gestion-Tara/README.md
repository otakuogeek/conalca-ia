# 📦 MÓDULO: GESTIÓN › TARA

## 1. ¿Para qué es este módulo?
Configura la **tara** (peso del contenedor vacío) que el sistema usa al cotizar transporte con contenedores. La tara es crítica porque el camión transporta el contenedor + la carga, y la capacidad máxima legal limita el peso total. Saber la tara correcta evita ofrecer cotizaciones con sobrepeso.

## 2. ¿Qué hace?
- Lista los **tipos de contenedor** configurados y su peso (tara).
- Permite **editar** el peso de cada tipo.
- Estos valores los usa el **chat IA** para calcular el peso neto vs bruto durante la cotización con contenedores.
- Se aplica automáticamente cuando el chat detecta menciones de contenedor.

## 3. ¿Cómo funciona?

### Configuración por defecto
| Tipo de contenedor | Tara (kg) |
|--------------------|----------:|
| 20 pies (20') | **2,300 kg** |
| 40 pies (40') | **3,400 kg** |
| Otros | Personalizable |

### Flujo del chat IA aplicando tara
```
Usuario: "Necesito flete con contenedor de 40 pies para 25 toneladas"
    ↓
MCPAssistantService detecta menciones de "contenedor"
  → hasContenedor($texto) = true
    ↓
Detecta tipo: "40 pies"
  → getTaraByContenedor("40 pies") = 3400 kg
    ↓
Calcula peso bruto:
  Peso carga: 25000 kg
  + Tara: 3400 kg
  = Peso bruto: 28400 kg
    ↓
Verifica capacidad de vehículo
  → Si excede → sugiere vehículo más grande
  → Si OK → continúa cotización
```

### Flujo del admin actualizando
```
Admin entra a /tara-settings
    ↓
Ve listado con 20', 40' y otros
    ↓
Click en uno → modo edición
    ↓
Cambia el valor
    ↓
PUT /tara-settings/{id} → TaraSettingController@update
    ↓
Guarda en tara_settings
```

## 4. ¿Cómo actúa en el sistema?
Es **fuente crítica de configuración**:
- Sin tara correcta → cotizaciones con sobrepeso = multas, rechazos, accidentes
- El chat IA consulta esto **antes** de sugerir vehículo
- Los conductores también ven el peso bruto cuando la IA les ofrece el viaje

**Es uno de los módulos más documentados** porque ha tenido varios bugs históricos (ver carpeta `08-Correcciones-Fixes/`).

## 5. Componentes del código

### Rutas
| Método | URL | Función | Middleware |
|--------|-----|---------|------------|
| GET | `/tara-settings` | Vista panel | `auth`, `role:SUPER ADMIN\|JEFE COMERCIAL\|SAC` |
| PUT | `/tara-settings/{taraSetting}` | Actualizar | `auth`, `role:SUPER ADMIN\|JEFE COMERCIAL\|SAC` |

### Controlador
**`app/Http/Controllers/TaraSettingController.php`**

**Métodos:**
- `index()` — listar taras
- `update(Request, TaraSetting)` — actualizar valor

### Modelo
**`TaraSetting`** (`tara_settings` table):
- `container_type` — `20`, `40`, `45`, `other`
- `weight_kg` — peso en kilogramos
- `description`
- `is_active`

### Vista
**`resources/views/tara-settings/index.blade.php`** — tabla simple con edición inline.

### Migración
- `2026_03_04_150000_create_tara_settings_table.php`

### Lógica de detección (en MCPAssistantService)
```php
// Detección de contenedor
hasContenedor($texto): bool
    → busca patrones: "contenedor", "container", "ctn", "ctnr"

// Obtención de tara por tipo
getTaraByContenedor($texto): int
    → si menciona "20" → consulta tara 20'
    → si menciona "40" → consulta tara 40'
    → default → 3400 kg
```

## 6. Reglas de negocio
- **Solo aplica si hay contenedor:** carga suelta NO tiene tara
- **Detección automática:** el chat IA detecta menciones de contenedor en el lenguaje natural
- **Valor cacheable:** estos valores cambian poco — pueden cachearse en Redis
- **Histórico no se guarda automáticamente**

## 7. Bugs históricos y fixes
Este módulo tiene varios bugs documentados (ver `08-Correcciones-Fixes/`):
- `FIX_BUG_TARA_DATAEXTRACTION.md` — bug en extracción de datos
- `FIX_BUG_TARA_EDICION.md` — bug al editar campos
- `FIX_PESO_TARA_VALIDACION.md` — validación cuando hay contenedor
- `FIX_CONTENEDOR_DOBLE_R.md` — bug con "contenedoor"
- `PREVENCION_REGRESION.md` — pruebas para prevenir regresión

## 8. Permisos
| Rol | Acceso |
|-----|:------:|
| SUPER ADMIN | ✅ |
| JEFE COMERCIAL | ✅ |
| GERENTE DE CUENTA | ❌ |
| ASISTENTE COMERCIAL | ❌ |
| SAC | ✅ |
| PRICING | ❌ |

## 9. Validación crítica
Cuando una cotización tiene **carga + contenedor**, el sistema debe:
1. Sumar peso_neto + tara = peso_bruto
2. Verificar peso_bruto ≤ capacidad_vehiculo
3. Si no → error o sugerir vehículo más grande

Si el desarrollador modifica `MCPAssistantService.getTaraByContenedor()`, debe ejecutar:
```bash
php artisan test --filter TaraTest
# o los tests standalone
php test_tara_completo.php
php test_tara_inteligente.php
```

## 10. Archivos clave
- `app/Http/Controllers/TaraSettingController.php`
- `app/Models/TaraSetting.php`
- `app/Services/MCPAssistantService.php` (lógica de aplicación)
- `resources/views/tara-settings/index.blade.php`
- Tests: `test_tara_*.php` (en raíz del proyecto)

## 11. Relacionado con
- [09-Pricing](../09-Pricing/) — cálculo de capacidad
- [13-Gestion-Panel-Porcentajes](../13-Gestion-Panel-Porcentajes/) — porcentaje sobre precio final
- Chat IA de cotizaciones (módulo central)

## 12. Documentación adicional
Carpeta `08-Correcciones-Fixes/`:
- `FIX_BUG_TARA_DATAEXTRACTION.md`
- `FIX_BUG_TARA_EDICION.md`
- `FIX_PESO_TARA_VALIDACION.md`
- `PREVENCION_REGRESION.md`
