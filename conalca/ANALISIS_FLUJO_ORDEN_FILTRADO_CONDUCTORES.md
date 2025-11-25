# 📋 Análisis Completo: Flujo de Orden → Filtrado de Conductores y Vehículos

## 🎯 Objetivo
Analizar cómo desde una orden de transporte (cotización) se filtran conductores disponibles en una ciudad específica con el tipo de vehículo solicitado.

**Ejemplo práctico:** Orden desde **Funza** → destino Cali, vehículo **Sencillo**

---

## 📊 1. ESTRUCTURA DE DATOS: ORDEN/COTIZACIÓN

### Tabla: `cotizacion_models`

```php
// Campos clave para filtrado de conductores
[
    'id' => 123,
    'ciudad_origen' => 'funza',          // ⬅️ CIUDAD PARA FILTRAR CONDUCTORES
    'ciudad_destino' => 'cali',
    'vehiculo_requerido' => 'Sencillo',  // ⬅️ TIPO DE VEHÍCULO PARA FILTRAR
    'peso_mercancia' => '5000',          // kg
    'tipo_producto' => 'Mercancía general',
    'client_id' => 45,
    'group_cotization_id' => 78,
    'active' => 1
]
```

**Campos importantes:**
- ✅ `ciudad_origen`: Ciudad donde se requiere el vehículo
- ✅ `vehiculo_requerido`: Tipo de vehículo (Sencillo, Turbo, Tractomula, etc.)
- ✅ `peso_mercancia`: Para validar capacidad
- ✅ `tipo_producto`: Para considerar carrocería adecuada

---

## 🚛 2. ESTRUCTURA DE DATOS: CONDUCTORES Y VEHÍCULOS

### Tabla: `vehicle_owner_holder_driver`

```php
// Campos clave para filtrado
[
    'id' => 456,
    'Placa' => 'ABC123',
    'Conductor' => 'Juan Pérez',
    'Cedula' => '1234567890',
    'Telefonoconductor' => '3101234567',
    'Ciudad conductor' => 'FUNZA',      // ⬅️ CIUDAD DEL CONDUCTOR
    'Clasevehiculo' => 'SENCILLO',      // ⬅️ TIPO DE VEHÍCULO
    'Carroceria' => 'FURGON',
    'Capacidad' => 5000,                // kg
    'Estado' => 'ACTIVO',
    'Modelo' => '2020',
    
    // Campos de gestión de llamadas
    'call_status' => 'available',       // available, busy, offline
    'last_call_at' => '2025-11-10 10:30:00',
    'total_calls_received' => 15,
    'total_calls_accepted' => 8,
    'is_active' => true
]
```

**Campos de filtrado:**
1. ✅ `Ciudad conductor`: Debe coincidir con `ciudad_origen` de la cotización
2. ✅ `Clasevehiculo`: Debe coincidir con `vehiculo_requerido`
3. ✅ `Estado`: Debe ser 'ACTIVO'
4. ✅ `call_status`: Preferiblemente 'available'
5. ✅ `is_active`: true

---

## 🔍 3. PROCESO DE FILTRADO PASO A PASO

### Paso 1: Extraer datos de la cotización

```php
$cotizacion = CotizacionModel::find(123);

$ciudadOrigen = $cotizacion->ciudad_origen;      // "funza"
$vehiculoRequerido = $cotizacion->vehiculo_requerido;  // "Sencillo"
$pesoMercancia = $cotizacion->peso_mercancia;    // 5000
```

### Paso 2: Normalizar datos para búsqueda

```php
// Normalizar ciudad (mayúsculas, sin tildes)
$ciudadNormalizada = strtoupper($this->normalizarTexto($ciudadOrigen));
// "funza" → "FUNZA"

// Normalizar tipo de vehículo
$vehiculoNormalizado = VehicleOwnerHolderDriver::normalizeVehicleTypeStatic($vehiculoRequerido);
// "Sencillo" → "SENCILLO"
// "Tracto mula" → "TRACTOMULA"
```

### Paso 3: Consultar conductores locales (Base de Datos)

```php
$conductoresLocales = VehicleOwnerHolderDriver::query()
    ->where('Estado', 'ACTIVO')
    ->where('is_active', true)
    ->byCity($ciudadNormalizada)           // Scope: Ciudad conductor = FUNZA
    ->byVehicleType($vehiculoNormalizado)  // Scope: Clasevehiculo LIKE %SENCILLO%
    ->withValidPhone()                     // Scope: Teléfono válido
    ->available()                          // Scope: call_status = 'available'
    ->get();
```

**Query SQL equivalente:**
```sql
SELECT * FROM vehicle_owner_holder_driver
WHERE Estado = 'ACTIVO'
  AND is_active = 1
  AND `Ciudad conductor` = 'FUNZA'
  AND Clasevehiculo LIKE '%SENCILLO%'
  AND Telefonoconductor IS NOT NULL
  AND Telefonoconductor != ''
  AND call_status = 'available'
ORDER BY last_call_at ASC  -- Priorizar quien no ha sido llamado recientemente
LIMIT 50;
```

### Paso 4: Consultar vehículos en Arcángel (API Externa)

```php
$arcangelService = app(ArcangelService::class);

// Obtener vehículos cercanos en la ciudad
$vehiculosArcangel = $arcangelService->getVehiculosFiltrados(
    ciudad: $ciudadNormalizada,     // "FUNZA"
    clases: $vehiculoNormalizado,   // "SENCILLO"
    minScore: 7,                    // Score mínimo de calidad
    limit: 50,
    useCache: true,
    cacheTTL: 30  // minutos
);
```

**Respuesta de Arcángel:**
```json
{
  "ciudad": "FUNZA",
  "vehiculos": [
    {
      "placa": "DEF456",
      "conductor": "María López",
      "telefono": "3209876543",
      "clase": "SENCILLO",
      "score": 9.2,
      "disponible": true,
      "ultimaLlamada": "2025-11-15 08:00:00"
    },
    {
      "placa": "GHI789",
      "conductor": "Carlos Ruiz",
      "telefono": "3158765432",
      "clase": "SENCILLO",
      "score": 8.5,
      "disponible": true,
      "ultimaLlamada": "2025-11-14 16:30:00"
    }
  ],
  "total_vehiculos": 2,
  "total_original": 15
}
```

### Paso 5: Combinar y priorizar resultados

```php
// Combinar conductores locales y de Arcángel
$conductoresCombinados = $this->combinarConductores(
    $conductoresLocales,
    $vehiculosArcangel['vehiculos']
);

// Priorizar según criterios
$conductoresPriorizados = $this->priorizarConductores($conductoresCombinados, [
    'score' => 0.4,              // 40% peso del score
    'llamadas_recientes' => 0.3, // 30% peso de última llamada
    'tasa_aceptacion' => 0.3     // 30% peso de tasa de aceptación
]);
```

**Resultado combinado:**
```php
[
    [
        'id' => 456,
        'nombre' => 'Juan Pérez',
        'placa' => 'ABC123',
        'telefono' => '3101234567',
        'clase_vehiculo' => 'SENCILLO',
        'ciudad' => 'FUNZA',
        'fuente' => 'local',        // local o arcangel
        'score' => 8.8,
        'ultima_llamada' => '2025-11-10 10:30:00',
        'llamadas_totales' => 15,
        'llamadas_aceptadas' => 8,
        'tasa_aceptacion' => 0.53,
        'prioridad' => 87.5         // Score calculado
    ],
    [
        'id' => null,
        'nombre' => 'María López',
        'placa' => 'DEF456',
        'telefono' => '3209876543',
        'clase_vehiculo' => 'SENCILLO',
        'ciudad' => 'FUNZA',
        'fuente' => 'arcangel',
        'score' => 9.2,
        'ultima_llamada' => '2025-11-15 08:00:00',
        'prioridad' => 92.0
    ]
]
```

---

## 🔧 4. IMPLEMENTACIÓN: SCOPES EN VehicleOwnerHolderDriver

### Scope: `byCity()`
```php
public function scopeByCity($query, $city)
{
    $normalized = strtoupper(trim($city));
    return $query->where('Ciudad conductor', $normalized);
}
```

### Scope: `byVehicleType()`
```php
public function scopeByVehicleType($query, $vehicleType)
{
    $normalized = self::normalizeVehicleTypeStatic($vehicleType);
    
    // Manejar variaciones de TRACTOMULA
    if ($normalized === 'TRACTOMULA') {
        return $query->where(function ($q) {
            $q->where('Clasevehiculo', 'LIKE', '%TRACTOMULA%')
              ->orWhere('Clasevehiculo', 'LIKE', '%TRACTO MULA%');
        });
    }
    
    return $query->where('Clasevehiculo', 'LIKE', '%' . $normalized . '%');
}
```

### Scope: `available()`
```php
public function scopeAvailable($query)
{
    return $query->where('call_status', 'available')
                 ->where('is_active', true);
}
```

### Scope: `withValidPhone()`
```php
public function scopeWithValidPhone($query)
{
    return $query->whereNotNull('Telefonoconductor')
                 ->where('Telefonoconductor', '!=', '')
                 ->where('Telefonoconductor', 'not regexp', '^[0\s\-]+$');
}
```

---

## 🌐 5. INTEGRACIÓN CON ARCÁNGEL API

### Método: `getVehiculosCercanos()`
```php
// En ArcangelService.php
public function getVehiculosCercanos(string $ciudad, bool $useCache = false, int $cacheTTL = 5): array
{
    $cacheKey = "arcangel_vehiculos_{$ciudad}";
    
    if ($useCache && Cache::has($cacheKey)) {
        return Cache::get($cacheKey);
    }
    
    $token = $this->generateToken();
    
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
    ])->get($this->baseUrl . 'getVehiculosCercanos/', [
        'ciudad' => $ciudad
    ]);
    
    $data = $this->handleResponse($response, 'getVehiculosCercanos');
    
    if ($useCache) {
        Cache::put($cacheKey, $data, now()->addMinutes($cacheTTL));
    }
    
    return $data;
}
```

### Método: `getVehiculosFiltrados()`
```php
public function getVehiculosFiltrados(
    string $ciudad,
    string|array|null $clases = null,
    ?int $minScore = null,
    ?int $limit = null,
    bool $useCache = true,
    int $cacheTTL = 30
): array {
    // Obtener todos los vehículos de la ciudad
    $result = $this->getVehiculosCercanos($ciudad, $useCache, $cacheTTL);
    $vehiculos = $result['vehiculos'] ?? [];
    
    // Filtrar por clase(s)
    if ($clases !== null) {
        $clasesArray = is_array($clases) 
            ? array_map('strtoupper', $clases) 
            : [strtoupper($clases)];
        
        $vehiculos = array_filter($vehiculos, function ($vehiculo) use ($clasesArray) {
            return in_array(strtoupper($vehiculo['clase'] ?? ''), $clasesArray);
        });
    }
    
    // Filtrar por score mínimo
    if ($minScore !== null) {
        $vehiculos = array_filter($vehiculos, function ($vehiculo) use ($minScore) {
            return ($vehiculo['score'] ?? 0) >= $minScore;
        });
    }
    
    // Aplicar límite
    if ($limit !== null) {
        $vehiculos = array_slice($vehiculos, 0, $limit);
    }
    
    return [
        'ciudad' => $ciudad,
        'vehiculos' => $vehiculos,
        'total_vehiculos' => count($vehiculos),
    ];
}
```

---

## 📞 6. EJEMPLO COMPLETO: COMANDO DE CONSOLA

### Comando: `php artisan arcangel:buscar-conductores`

```php
<?php

namespace App\Console\Commands;

use App\Models\CotizacionModel;
use App\Models\VehicleOwnerHolderDriver;
use App\Services\ArcangelService;
use Illuminate\Console\Command;

class BuscarConductoresParaCotizacion extends Command
{
    protected $signature = 'arcangel:buscar-conductores 
                           {cotizacion_id : ID de la cotización}
                           {--local : Solo buscar en base de datos local}
                           {--arcangel : Solo buscar en Arcángel}
                           {--limit=50 : Límite de resultados}';
    
    protected $description = 'Busca conductores disponibles para una cotización específica';
    
    protected ArcangelService $arcangelService;
    
    public function __construct(ArcangelService $arcangelService)
    {
        parent::__construct();
        $this->arcangelService = $arcangelService;
    }
    
    public function handle()
    {
        $cotizacionId = $this->argument('cotizacion_id');
        $limit = (int) $this->option('limit');
        $soloLocal = $this->option('local');
        $soloArcangel = $this->option('arcangel');
        
        // 1. Obtener cotización
        $cotizacion = CotizacionModel::find($cotizacionId);
        
        if (!$cotizacion) {
            $this->error("❌ Cotización #{$cotizacionId} no encontrada");
            return 1;
        }
        
        $this->info("📋 Cotización #{$cotizacion->id}");
        $this->line("   Origen: {$cotizacion->ciudad_origen}");
        $this->line("   Destino: {$cotizacion->ciudad_destino}");
        $this->line("   Vehículo: {$cotizacion->vehiculo_requerido}");
        $this->newLine();
        
        // 2. Normalizar datos
        $ciudadOrigen = $this->normalizarTexto($cotizacion->ciudad_origen);
        $vehiculoRequerido = VehicleOwnerHolderDriver::normalizeVehicleTypeStatic(
            $cotizacion->vehiculo_requerido
        );
        
        $this->info("🔍 Buscando conductores...");
        $this->line("   Ciudad normalizada: {$ciudadOrigen}");
        $this->line("   Vehículo normalizado: {$vehiculoRequerido}");
        $this->newLine();
        
        $conductoresTotal = [];
        
        // 3. Buscar en base de datos local
        if (!$soloArcangel) {
            $this->info("🗄️  Consultando base de datos local...");
            
            $conductoresLocales = VehicleOwnerHolderDriver::query()
                ->where('Estado', 'ACTIVO')
                ->where('is_active', true)
                ->byCity($ciudadOrigen)
                ->byVehicleType($vehiculoRequerido)
                ->withValidPhone()
                ->available()
                ->orderBy('last_call_at', 'asc')
                ->limit($limit)
                ->get();
            
            $this->line("   ✅ Encontrados: {$conductoresLocales->count()} conductores");
            
            foreach ($conductoresLocales as $conductor) {
                $conductoresTotal[] = [
                    'fuente' => 'local',
                    'id' => $conductor->id,
                    'nombre' => $conductor->Conductor,
                    'placa' => $conductor->Placa,
                    'telefono' => $conductor->Telefonoconductor,
                    'clase_vehiculo' => $conductor->Clasevehiculo,
                    'ciudad' => $conductor->{'Ciudad conductor'},
                    'ultima_llamada' => $conductor->last_call_at?->format('Y-m-d H:i:s'),
                    'llamadas_totales' => $conductor->total_calls_received,
                    'llamadas_aceptadas' => $conductor->total_calls_accepted,
                ];
            }
        }
        
        // 4. Buscar en Arcángel
        if (!$soloLocal) {
            $this->info("🌐 Consultando Arcángel API...");
            
            try {
                $resultadoArcangel = $this->arcangelService->getVehiculosFiltrados(
                    ciudad: $ciudadOrigen,
                    clases: $vehiculoRequerido,
                    minScore: 7,
                    limit: $limit,
                    useCache: true,
                    cacheTTL: 30
                );
                
                $vehiculosArcangel = $resultadoArcangel['vehiculos'] ?? [];
                $this->line("   ✅ Encontrados: " . count($vehiculosArcangel) . " vehículos");
                
                foreach ($vehiculosArcangel as $vehiculo) {
                    $conductoresTotal[] = [
                        'fuente' => 'arcangel',
                        'id' => null,
                        'nombre' => $vehiculo['conductor'] ?? 'N/A',
                        'placa' => $vehiculo['placa'] ?? 'N/A',
                        'telefono' => $vehiculo['telefono'] ?? 'N/A',
                        'clase_vehiculo' => $vehiculo['clase'] ?? 'N/A',
                        'ciudad' => $ciudadOrigen,
                        'score' => $vehiculo['score'] ?? 0,
                        'disponible' => $vehiculo['disponible'] ?? false,
                    ];
                }
            } catch (\Exception $e) {
                $this->error("   ❌ Error al consultar Arcángel: " . $e->getMessage());
            }
        }
        
        $this->newLine();
        
        // 5. Mostrar resultados
        if (empty($conductoresTotal)) {
            $this->warn("⚠️  No se encontraron conductores disponibles");
            return 0;
        }
        
        $this->info("📊 Resultados encontrados: " . count($conductoresTotal));
        $this->newLine();
        
        $this->table(
            ['Fuente', 'Nombre', 'Placa', 'Teléfono', 'Vehículo', 'Ciudad'],
            array_map(function ($c) {
                return [
                    strtoupper($c['fuente']),
                    $c['nombre'],
                    $c['placa'],
                    $c['telefono'],
                    $c['clase_vehiculo'],
                    $c['ciudad']
                ];
            }, $conductoresTotal)
        );
        
        return 0;
    }
    
    private function normalizarTexto(string $texto): string
    {
        $texto = strtoupper(trim($texto));
        $acentos = ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N'];
        return strtr($texto, $acentos);
    }
}
```

---

## 🎯 7. EJEMPLO DE USO COMPLETO

### Caso: Orden desde Funza → Cali, vehículo Sencillo

#### 1. Ejecutar búsqueda desde cotización #123
```bash
php artisan arcangel:buscar-conductores 123
```

#### Salida:
```
📋 Cotización #123
   Origen: funza
   Destino: cali
   Vehículo: Sencillo

🔍 Buscando conductores...
   Ciudad normalizada: FUNZA
   Vehículo normalizado: SENCILLO

🗄️  Consultando base de datos local...
   ✅ Encontrados: 3 conductores

🌐 Consultando Arcángel API...
   ✅ Encontrados: 2 vehículos

📊 Resultados encontrados: 5

┌─────────┬──────────────┬─────────┬──────────────┬──────────┬───────┐
│ Fuente  │ Nombre       │ Placa   │ Teléfono     │ Vehículo │ Ciudad│
├─────────┼──────────────┼─────────┼──────────────┼──────────┼───────┤
│ LOCAL   │ Juan Pérez   │ ABC123  │ 3101234567   │ SENCILLO │ FUNZA │
│ LOCAL   │ Pedro Gómez  │ XYZ789  │ 3159876543   │ SENCILLO │ FUNZA │
│ LOCAL   │ Ana Martínez │ LMN456  │ 3208765432   │ SENCILLO │ FUNZA │
│ ARCANGEL│ María López  │ DEF456  │ 3209876543   │ SENCILLO │ FUNZA │
│ ARCANGEL│ Carlos Ruiz  │ GHI789  │ 3158765432   │ SENCILLO │ FUNZA │
└─────────┴──────────────┴─────────┴──────────────┴──────────┴───────┘
```

#### 2. Iniciar llamadas a conductores
```bash
php artisan arcangel:registrar-llamada 123 "Juan Pérez" 3101234567
```

---

## 📈 8. FLUJO DE PRIORIZACIÓN

### Algoritmo de priorización sugerido:

```php
function calcularPrioridad($conductor): float
{
    $score = 0;
    
    // 1. Score de Arcángel (40%)
    if (isset($conductor['score'])) {
        $score += ($conductor['score'] / 10) * 40;
    }
    
    // 2. Tasa de aceptación (30%)
    if (isset($conductor['llamadas_aceptadas'], $conductor['llamadas_totales'])) {
        $tasaAceptacion = $conductor['llamadas_totales'] > 0
            ? $conductor['llamadas_aceptadas'] / $conductor['llamadas_totales']
            : 0;
        $score += $tasaAceptacion * 30;
    }
    
    // 3. Tiempo desde última llamada (30%)
    if (isset($conductor['ultima_llamada'])) {
        $horasDesdeUltimaLlamada = now()->diffInHours($conductor['ultima_llamada']);
        // Más de 24 horas = 30 puntos, menos de 1 hora = 0 puntos
        $scoreTiempo = min($horasDesdeUltimaLlamada / 24, 1) * 30;
        $score += $scoreTiempo;
    } else {
        $score += 30; // Nunca llamado = máxima prioridad en este criterio
    }
    
    return round($score, 2);
}
```

---

## ✅ 9. RESUMEN DEL FLUJO COMPLETO

```
1. COTIZACIÓN CREADA
   ├─ ciudad_origen: "funza"
   ├─ ciudad_destino: "cali"
   └─ vehiculo_requerido: "Sencillo"
   
2. NORMALIZACIÓN
   ├─ "funza" → "FUNZA"
   └─ "Sencillo" → "SENCILLO"
   
3. BÚSQUEDA LOCAL (BD)
   └─ SELECT * FROM vehicle_owner_holder_driver
      WHERE Ciudad conductor = 'FUNZA'
        AND Clasevehiculo LIKE '%SENCILLO%'
        AND Estado = 'ACTIVO'
        AND is_active = 1
      → 3 conductores encontrados
   
4. BÚSQUEDA ARCÁNGEL (API)
   └─ GET /api/arcangel/getVehiculosCercanos/
      Params: { ciudad: "FUNZA" }
      Filter: clase IN ["SENCILLO"]
      → 2 vehículos encontrados
   
5. COMBINACIÓN Y PRIORIZACIÓN
   └─ 5 conductores totales
      ├─ Calcular score de prioridad
      └─ Ordenar por prioridad DESC
   
6. REGISTRO DE LLAMADAS
   └─ INSERT INTO llamadas
      SET id_cotizacion = 123,
          chofer_id = 456,
          status = 'pendiente'
   
7. COLA DE LLAMADAS
   └─ Procesar según sistema de cola
      (Ver SISTEMA_COLA_LLAMADAS.md)
```

---

## 🔗 10. ARCHIVOS RELACIONADOS

1. **Modelos:**
   - `/app/Models/CotizacionModel.php` - Orden/cotización
   - `/app/Models/VehicleOwnerHolderDriver.php` - Conductores y vehículos
   - `/app/Models/Llamada.php` - Registro de llamadas

2. **Servicios:**
   - `/app/Services/ArcangelService.php` - API de Arcángel
   - Métodos: `getVehiculosCercanos()`, `getVehiculosFiltrados()`

3. **Comandos de Consola:**
   - `/app/Console/Commands/ConsultarLocalidadArcangel.php`
   - `/app/Console/Commands/RegistrarLlamadaConductor.php`
   - `/app/Console/Commands/ListarLlamadasPendientes.php`
   - `/app/Console/Commands/ListarCiudadesArcangel.php`

4. **Documentación:**
   - `ANALISIS_BACKEND_ARCANGEL.md` - Análisis completo de Arcángel
   - `SISTEMA_COLA_LLAMADAS.md` - Sistema de gestión de llamadas
   - `DOCUMENTACION_ARCANGEL_API.md` - Documentación de API

---

## 🎓 11. CONCLUSIONES

### ✅ Proceso Funcionando:
1. **Extracción de datos:** Desde cotización se obtiene ciudad origen y tipo de vehículo
2. **Normalización:** Texto convertido a mayúsculas sin tildes para matching consistente
3. **Búsqueda dual:** Base de datos local + API Arcángel
4. **Filtrado:** Por ciudad, tipo de vehículo, estado activo, teléfono válido
5. **Priorización:** Score combinado de múltiples factores
6. **Integración:** Sistema de llamadas con ElevenLabs

### 🚀 Mejoras Sugeridas:
1. **Cache inteligente:** Diferente TTL según tipo de datos (ciudades 60min, vehículos 30min)
2. **Fallback automático:** Si Arcángel falla, usar solo BD local
3. **Score dinámico:** Ajustar pesos según histórico de aceptación
4. **Geolocalización:** Calcular distancia real desde ciudad origen
5. **Predicción ML:** Predecir probabilidad de aceptación por conductor

---

**Documento creado:** 2025-11-16  
**Versión:** 1.0  
**Autor:** Sistema de Análisis Conalca  
