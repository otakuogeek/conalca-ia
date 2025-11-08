<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Packing;
use Illuminate\Support\Facades\DB;

echo "=== INSERCIÓN DE EMPAQUES DE EJEMPLO ===\n\n";

$empaques = [
    ['Codigo' => '1', 'Nombre' => 'CAJA', 'Codigo Ministerio' => '001'],
    ['Codigo' => '2', 'Nombre' => 'ESTIBA', 'Codigo Ministerio' => '002'],
    ['Codigo' => '3', 'Nombre' => 'PALLET', 'Codigo Ministerio' => '003'],
    ['Codigo' => '4', 'Nombre' => 'BULTO', 'Codigo Ministerio' => '004'],
    ['Codigo' => '5', 'Nombre' => 'SACO', 'Codigo Ministerio' => '005'],
    ['Codigo' => '6', 'Nombre' => 'CANASTA', 'Codigo Ministerio' => '006'],
    ['Codigo' => '7', 'Nombre' => 'TANQUE', 'Codigo Ministerio' => '007'],
    ['Codigo' => '8', 'Nombre' => 'TAMBOR', 'Codigo Ministerio' => '008'],
    ['Codigo' => '9', 'Nombre' => 'UNIDAD', 'Codigo Ministerio' => '009'],
    ['Codigo' => '10', 'Nombre' => 'GRANEL', 'Codigo Ministerio' => '010'],
];

try {
    foreach ($empaques as $empaque) {
        $exists = Packing::where('Codigo', $empaque['Codigo'])->exists();
        
        if (!$exists) {
            DB::table('packing')->insert([
                'Codigo' => $empaque['Codigo'],
                'Nombre' => $empaque['Nombre'],
                'Codigo Ministerio' => $empaque['Codigo Ministerio'],
                'Usuario' => 'SYSTEM',
                'Fecha Creacion' => now(),
                'Fecha Modificacion' => now()
            ]);
            echo "✅ Insertado: {$empaque['Codigo']} - {$empaque['Nombre']}\n";
        } else {
            echo "⏭️  Ya existe: {$empaque['Codigo']} - {$empaque['Nombre']}\n";
        }
    }
    
    echo "\n=== VERIFICACIÓN ===\n";
    $total = Packing::count();
    echo "Total de empaques en BD: $total\n\n";
    
    echo "Listado completo:\n";
    $todos = Packing::orderBy('Codigo')->get(['Codigo', 'Nombre', 'Codigo Ministerio']);
    foreach ($todos as $e) {
        echo "- {$e->Codigo}: {$e->Nombre} (Ministerio: {$e->{'Codigo Ministerio'}})\n";
    }
    
    echo "\n✅ PROCESO COMPLETADO EXITOSAMENTE!\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
