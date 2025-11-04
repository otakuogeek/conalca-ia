<?php

require_once 'vendor/autoload.php';

// Cargar Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\GroupCotization;
use App\Models\CotizacionModel;

echo "=== DEBUGGING GRUPO #62 ===\n";

// Buscar el grupo
$group = GroupCotization::find(62);

if (!$group) {
    echo "❌ Grupo #62 no encontrado\n";
    exit;
}

echo "✅ Grupo #62 encontrado:\n";
echo "- ID: {$group->id}\n";
echo "- User ID: {$group->user_id}\n";
echo "- Client ID: {$group->client_id}\n";
echo "- Tipo: {$group->type}\n";
echo "- Estado: {$group->status}\n";
echo "- Creado desde chat: " . ($group->created_from_chat ? 'Sí' : 'No') . "\n";
echo "- Thread ID: {$group->openai_thread_id}\n";
echo "- Creado: {$group->created_at}\n";

// Buscar cotizaciones asociadas
$cotizaciones = CotizacionModel::where('group_cotization_id', 62)->get();

echo "\n=== COTIZACIONES ASOCIADAS ===\n";
echo "Total cotizaciones: " . $cotizaciones->count() . "\n";

foreach ($cotizaciones as $cotizacion) {
    echo "\n📄 Cotización ID: {$cotizacion->id}\n";
    echo "- Origen: {$cotizacion->ciudad_origen}\n";
    echo "- Destino: {$cotizacion->ciudad_destino}\n";
    echo "- Peso: {$cotizacion->peso_mercancia}\n";
    echo "- Vehículo: {$cotizacion->vehiculo_requerido}\n";
    echo "- Valor declarado: {$cotizacion->valor_declarado}\n";
    echo "- Estado: {$cotizacion->estado}\n";
}

if ($cotizaciones->count() === 0) {
    echo "❌ No se encontraron cotizaciones asociadas al grupo #62\n";
    echo "Esto explica por qué no aparecen las rutas en la interfaz.\n";
}