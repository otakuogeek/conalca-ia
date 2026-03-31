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
                           {--limit=50 : Límite de resultados}
                           {--min-score=7 : Score mínimo para Arcángel}';
    
    protected $description = 'Busca conductores disponibles para una cotización específica según ciudad origen y tipo de vehículo';
    
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
        $minScore = (int) $this->option('min-score');
        $soloLocal = $this->option('local');
        $soloArcangel = $this->option('arcangel');
        
        // 1. Obtener cotización
        $this->info("🔍 Buscando cotización...");
        $cotizacion = CotizacionModel::find($cotizacionId);
        
        if (!$cotizacion) {
            $this->error("❌ Cotización #{$cotizacionId} no encontrada");
            return 1;
        }
        
        $this->newLine();
        $this->info("📋 Cotización #{$cotizacion->id}");
        $this->line("   Origen: {$cotizacion->ciudad_origen}");
        $this->line("   Destino: {$cotizacion->ciudad_destino}");
        $this->line("   Vehículo: {$cotizacion->vehiculo_requerido}");
        $this->line("   Peso: {$cotizacion->peso_mercancia} kg");
        $this->line("   Tipo producto: {$cotizacion->tipo_producto}");
        $this->newLine();
        
        // 2. Normalizar datos
        $ciudadOrigen = $this->normalizarTexto($cotizacion->ciudad_origen);
        $vehiculoRequerido = VehicleOwnerHolderDriver::normalizeVehicleTypeStatic(
            $cotizacion->vehiculo_requerido
        );
        
        $this->info("🔧 Datos normalizados:");
        $this->line("   Ciudad: {$ciudadOrigen}");
        $this->line("   Vehículo: {$vehiculoRequerido}");
        $this->newLine();
        
        $conductoresTotal = [];
        
        // 3. Buscar en base de datos local
        if (!$soloArcangel) {
            $this->info("🗄️  Consultando base de datos local...");
            
            $conductoresLocales = VehicleOwnerHolderDriver::query()
                ->where('Estado', 'ACTIVO')
                ->byCity($ciudadOrigen)
                ->byVehicleType($vehiculoRequerido)
                ->withValidPhone();
            
            // Agregar filtros opcionales solo si las columnas existen
            if (\Schema::hasColumn('vehicle_owner_holder_driver', 'is_active')) {
                $conductoresLocales->where('is_active', true);
            }
            
            if (\Schema::hasColumn('vehicle_owner_holder_driver', 'call_status')) {
                $conductoresLocales->where('call_status', 'available');
            }
            
            if (\Schema::hasColumn('vehicle_owner_holder_driver', 'last_call_at')) {
                $conductoresLocales->orderBy('last_call_at', 'asc');
            }
            
            $conductoresLocales = $conductoresLocales->limit($limit)->get();
            
            $this->line("   ✅ Encontrados: {$conductoresLocales->count()} conductores");
            
            foreach ($conductoresLocales as $conductor) {
                $tasaAceptacion = $conductor->total_calls_received > 0
                    ? round(($conductor->total_calls_accepted / $conductor->total_calls_received) * 100, 1)
                    : 0;
                
                $conductoresTotal[] = [
                    'fuente' => 'LOCAL',
                    'id' => $conductor->id,
                    'nombre' => $conductor->Conductor,
                    'placa' => $conductor->Placa,
                    'telefono' => $conductor->Telefonoconductor,
                    'clase_vehiculo' => $conductor->Clasevehiculo,
                    'ciudad' => $conductor->{'Ciudad conductor'},
                    'carroceria' => $conductor->Carroceria,
                    'capacidad' => $conductor->Capacidad,
                    'ultima_llamada' => $conductor->last_call_at?->format('Y-m-d H:i'),
                    'llamadas_totales' => $conductor->total_calls_received,
                    'llamadas_aceptadas' => $conductor->total_calls_accepted,
                    'tasa_aceptacion' => $tasaAceptacion,
                    'score' => null,
                    'prioridad' => $this->calcularPrioridad([
                        'ultima_llamada' => $conductor->last_call_at,
                        'llamadas_totales' => $conductor->total_calls_received,
                        'llamadas_aceptadas' => $conductor->total_calls_accepted,
                    ])
                ];
            }
            $this->newLine();
        }
        
        // 4. Buscar en Arcángel
        if (!$soloLocal) {
            $this->info("🌐 Consultando Arcángel API...");
            
            try {
                $resultadoArcangel = $this->arcangelService->getVehiculosFiltrados(
                    ciudad: $ciudadOrigen,
                    clases: $vehiculoRequerido,
                    minScore: $minScore,
                    limit: $limit,
                    useCache: false
                );
                
                $vehiculosArcangel = $resultadoArcangel['vehiculos'] ?? [];
                $totalOriginal = $resultadoArcangel['total_original'] ?? 0;
                
                $this->line("   ✅ Encontrados: " . count($vehiculosArcangel) . " vehículos (de {$totalOriginal} totales)");
                
                foreach ($vehiculosArcangel as $vehiculo) {
                    $conductoresTotal[] = [
                        'fuente' => 'ARCANGEL',
                        'id' => null,
                        'nombre' => $vehiculo['conductor'] ?? 'N/A',
                        'placa' => $vehiculo['placa'] ?? 'N/A',
                        'telefono' => $vehiculo['telefono'] ?? 'N/A',
                        'clase_vehiculo' => $vehiculo['clase'] ?? 'N/A',
                        'ciudad' => $ciudadOrigen,
                        'carroceria' => $vehiculo['carroceria'] ?? 'N/A',
                        'capacidad' => $vehiculo['capacidad'] ?? null,
                        'score' => $vehiculo['score'] ?? 0,
                        'ultima_llamada' => $vehiculo['ultimaLlamada'] ?? null,
                        'disponible' => $vehiculo['disponible'] ?? false,
                        'prioridad' => $this->calcularPrioridad([
                            'score' => $vehiculo['score'] ?? 0,
                        ])
                    ];
                }
                $this->newLine();
            } catch (\Exception $e) {
                $this->error("   ❌ Error al consultar Arcángel: " . $e->getMessage());
                $this->newLine();
            }
        }
        
        // 5. Mostrar resultados
        if (empty($conductoresTotal)) {
            $this->warn("⚠️  No se encontraron conductores disponibles");
            $this->line("");
            $this->line("💡 Sugerencias:");
            $this->line("   • Verifica que la ciudad '{$ciudadOrigen}' exista en la base de datos");
            $this->line("   • Confirma que hay conductores con vehículos tipo '{$vehiculoRequerido}'");
            $this->line("   • Revisa que los conductores tengan estado ACTIVO");
            return 0;
        }
        
        // Ordenar por prioridad
        usort($conductoresTotal, function($a, $b) {
            return $b['prioridad'] <=> $a['prioridad'];
        });
        
        $this->info("📊 Resultados encontrados: " . count($conductoresTotal));
        $this->newLine();
        
        // Tabla compacta
        $headers = ['#', 'Fuente', 'Nombre', 'Placa', 'Vehículo', 'Score', 'Prioridad'];
        $rows = [];
        
        foreach ($conductoresTotal as $index => $c) {
            $rows[] = [
                $index + 1,
                $c['fuente'],
                substr($c['nombre'], 0, 20),
                $c['placa'],
                substr($c['clase_vehiculo'], 0, 12),
                $c['score'] !== null ? number_format($c['score'], 1) : 'N/A',
                number_format($c['prioridad'], 1)
            ];
        }
        
        $this->table($headers, $rows);
        
        // Tabla detallada (top 10)
        $this->newLine();
        $this->info("🎯 Top 10 conductores priorizados:");
        $this->newLine();
        
        $headersDetalle = ['Nombre', 'Teléfono', 'Última Llamada', 'Tasa Acep.', 'Ciudad'];
        $rowsDetalle = [];
        
        foreach (array_slice($conductoresTotal, 0, 10) as $c) {
            $tasaAcep = isset($c['tasa_aceptacion']) 
                ? $c['tasa_aceptacion'] . '%' 
                : 'N/A';
            
            $ultimaLlamada = $c['ultima_llamada'] ?? 'Nunca';
            
            $rowsDetalle[] = [
                substr($c['nombre'], 0, 25),
                $c['telefono'],
                $ultimaLlamada,
                $tasaAcep,
                $c['ciudad']
            ];
        }
        
        $this->table($headersDetalle, $rowsDetalle);
        
        // Resumen final
        $this->newLine();
        $localCount = count(array_filter($conductoresTotal, fn($c) => $c['fuente'] === 'LOCAL'));
        $arcangelCount = count(array_filter($conductoresTotal, fn($c) => $c['fuente'] === 'ARCANGEL'));
        
        $this->info("📈 Resumen:");
        $this->line("   • Base de datos local: {$localCount} conductores");
        $this->line("   • Arcángel API: {$arcangelCount} conductores");
        $this->line("   • Total disponibles: " . count($conductoresTotal));
        
        return 0;
    }
    
    /**
     * Normaliza texto removiendo tildes y convirtiendo a mayúsculas
     */
    private function normalizarTexto(string $texto): string
    {
        $texto = strtoupper(trim($texto));
        $acentos = [
            'Á'=>'A', 'É'=>'E', 'Í'=>'I', 'Ó'=>'O', 'Ú'=>'U',
            'À'=>'A', 'È'=>'E', 'Ì'=>'I', 'Ò'=>'O', 'Ù'=>'U',
            'Ä'=>'A', 'Ë'=>'E', 'Ï'=>'I', 'Ö'=>'O', 'Ü'=>'U',
            'Â'=>'A', 'Ê'=>'E', 'Î'=>'I', 'Ô'=>'O', 'Û'=>'U',
            'Ñ'=>'N'
        ];
        return strtr($texto, $acentos);
    }
    
    /**
     * Calcula la prioridad de un conductor basado en múltiples factores
     */
    private function calcularPrioridad(array $datos): float
    {
        $score = 0;
        
        // 1. Score de Arcángel (40% del total)
        if (isset($datos['score'])) {
            $score += ($datos['score'] / 10) * 40;
        }
        
        // 2. Tasa de aceptación (30% del total)
        if (isset($datos['llamadas_aceptadas'], $datos['llamadas_totales']) && $datos['llamadas_totales'] > 0) {
            $tasaAceptacion = $datos['llamadas_aceptadas'] / $datos['llamadas_totales'];
            $score += $tasaAceptacion * 30;
        } elseif (!isset($datos['score'])) {
            // Si es conductor local sin historial, dar puntos base
            $score += 15;
        }
        
        // 3. Tiempo desde última llamada (30% del total)
        if (isset($datos['ultima_llamada']) && $datos['ultima_llamada'] !== null) {
            $horasDesdeUltimaLlamada = now()->diffInHours($datos['ultima_llamada']);
            // Más de 24 horas = 30 puntos, menos de 1 hora = 0 puntos
            $scoreTiempo = min($horasDesdeUltimaLlamada / 24, 1) * 30;
            $score += $scoreTiempo;
        } else {
            // Nunca llamado = máxima prioridad en este criterio
            $score += 30;
        }
        
        return round($score, 2);
    }
}
