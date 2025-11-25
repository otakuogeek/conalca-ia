<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "=== MAPEO PARA CHATBOX ===\n";
    
    // Buscar usuarios con rol ASISTENTE COMERCIAL
    $comerciales = User::role('ASISTENTE COMERCIAL')
        ->whereNotNull('documento')
        ->where('documento', '!=', '')
        ->get(['id', 'name', 'documento', 'email']);
    
    echo "Total comerciales encontrados: " . $comerciales->count() . "\n\n";
    
    foreach ($comerciales as $comercial) {
        $nombre = strtolower($comercial->name);
        $primerNombre = explode(' ', $nombre)[0];
        echo "        '{$primerNombre}': '{$comercial->documento}',     // {$comercial->name}\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

?>