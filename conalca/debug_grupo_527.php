<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\GroupCotization;
use Illuminate\Support\Facades\DB;

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║               ANÁLISIS GRUPO #527                             ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$group = GroupCotization::find(527);
if (!$group) {
    echo "❌ Grupo #527 no encontrado\n";
    exit(1);
}

echo "📊 INFORMACIÓN DEL GRUPO:\n";
echo "-----------------------------------------------------------\n";
echo "ID: {$group->id}\n";
echo "Usuario: {$group->user_id}\n";
echo "Cliente: {$group->client_id}\n";
echo "Creado: {$group->created_at}\n\n";

echo "📋 EXTRACTED_DATA:\n";
echo "-----------------------------------------------------------\n";
$extracted = json_decode($group->extracted_data, true);
if ($extracted) {
    echo json_encode($extracted, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
} else {
    echo "[Vacío o inválido]\n\n";
}

echo "📝 COTIZACIONES EN BASE DE DATOS:\n";
echo "-----------------------------------------------------------\n";
$cotizaciones = $group->cotizaciones()->get();
foreach ($cotizaciones as $cot) {
    echo "ID: {$cot->id}\n";
    echo "  Origen: {$cot->ciudad_origen} → Destino: {$cot->ciudad_destino}\n";
    echo "  Peso: {$cot->peso_mercancia} toneladas\n";
    echo "  Vehículo: {$cot->vehiculo_requerido}\n";
    echo "  Cantidad: {$cot->cantidad}\n";
    echo "  Producto: {$cot->tipo_producto}\n";
    echo "  Activo: " . ($cot->active ? 'Sí' : 'No') . "\n\n";
}

echo "💬 ÚLTIMOS MENSAJES (de metadata):\n";
echo "-----------------------------------------------------------\n";
// Intentar obtener mensajes desde conversation_sessions
$session = DB::table('conversation_sessions')->where('thread_id', 'mcp_633_1768067801')->first();
if ($session && $session->metadata) {
    $metadata = json_decode($session->metadata, true);
    if (isset($metadata['messages'])) {
        $messages = array_slice($metadata['messages'], -5); // Últimos 5
        foreach ($messages as $msg) {
            if (isset($msg['role']) && isset($msg['content'])) {
                $role = strtoupper($msg['role']);
                $content = substr($msg['content'], 0, 150);
                echo "[$role]: $content" . (strlen($msg['content']) > 150 ? '...' : '') . "\n\n";
            }
        }
    }
}
