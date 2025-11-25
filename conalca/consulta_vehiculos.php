<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$service = app(\App\Services\ArcangelService::class);

echo "=== CONSULTA ARCANGEL - TODAS LAS CIUDADES ===" . PHP_EOL . PHP_EOL;

try {
    // Obtener ciudades disponibles
    $ciudades = $service->getCiudades(false);
    
    echo "Ciudades disponibles: " . implode(', ', $ciudades) . PHP_EOL . PHP_EOL;
    
    // Consultar cada ciudad
    foreach ($ciudades as $ciudad) {
        echo "--- {$ciudad} ---" . PHP_EOL;
        
        try {
            $result = $service->getVehiculosCercanos($ciudad, false);
            $total = $result['total_vehiculos'] ?? 0;
            
            echo "Total: {$total} vehículos" . PHP_EOL;
            
            if ($total > 0 && !empty($result['vehiculos'])) {
                echo PHP_EOL;
                
                // Ver estructura del primer vehículo
                if (isset($result['vehiculos'][0])) {
                    echo "  Estructura de datos:" . PHP_EOL;
                    echo "  Campos: " . implode(', ', array_keys($result['vehiculos'][0])) . PHP_EOL . PHP_EOL;
                }
                
                foreach ($result['vehiculos'] as $i => $v) {
                    echo "  " . ($i + 1) . ". ";
                    echo ($v['conductor_nombre'] ?? $v['nombre_conductor'] ?? $v['conductor'] ?? 'N/A');
                    echo " - " . ($v['placa'] ?? 'N/A') . PHP_EOL;
                    
                    echo "     Clase: " . ($v['clase'] ?? $v['tipo_vehiculo'] ?? 'N/A');
                    echo " | Score: " . ($v['score'] ?? $v['calificacion'] ?? 0) . "/10";
                    echo " | Tel: " . ($v['telefono'] ?? $v['conductor_telefono'] ?? 'N/A') . PHP_EOL;
                }
                
                // Resumen por clase
                $porClase = [];
                foreach ($result['vehiculos'] as $v) {
                    $clase = $v['clase'] ?? $v['tipo_vehiculo'] ?? 'SIN CLASE';
                    if (!isset($porClase[$clase])) {
                        $porClase[$clase] = 0;
                    }
                    $porClase[$clase]++;
                }
                
                echo PHP_EOL . "  Tipos disponibles:" . PHP_EOL;
                foreach ($porClase as $clase => $cantidad) {
                    echo "    • {$clase}: {$cantidad}" . PHP_EOL;
                }
            }
            
        } catch (Exception $e) {
            echo "  Error: " . $e->getMessage() . PHP_EOL;
        }
        
        echo PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
