<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Ciudades colombianas que pueden tener este problema
$ciudadesPosiblementeAfectadas = ['tunja', 'monteria', 'medellin', 'tumaco', 'unguia'];

// Buscar grupos de los últimos 7 días
$groups = App\Models\GroupCotization::where('created_at', '>=', now()->subDays(7))
    ->orderBy('id', 'desc')
    ->get();

echo "🔍 Buscando grupos con posibles ciudades afectadas por FIX #572.1..." . PHP_EOL;
echo PHP_EOL;

$gruposAfectados = [];
foreach ($groups as $group) {
    $originalMsg = $group->original_message ?? '';
    $extractedData = json_decode($group->extracted_data ?? '{}', true);
    
    $msgLower = mb_strtolower($originalMsg, 'UTF-8');
    
    foreach ($ciudadesPosiblementeAfectadas as $ciudad) {
        if (strpos($msgLower, $ciudad) !== false) {
            $cotizaciones = $group->cotizacionesGrupo ?? collect();
            $routesCount = $cotizaciones->count();
            
            echo "📍 Grupo " . $group->id . " - Contiene \"" . strtoupper($ciudad) . "\"" . PHP_EOL;
            echo "   Rutas detectadas: " . $routesCount . PHP_EOL;
            echo "   Mensaje: " . substr($originalMsg, 0, 150) . "..." . PHP_EOL;
            echo PHP_EOL;
            
            $gruposAfectados[] = $group->id;
            break;
        }
    }
}

echo PHP_EOL;
echo "Total grupos potencialmente afectados: " . count($gruposAfectados) . PHP_EOL;
if (count($gruposAfectados) > 0) {
    echo "IDs: " . implode(', ', $gruposAfectados) . PHP_EOL;
}
