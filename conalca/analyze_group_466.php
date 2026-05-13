<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$group = DB::table('group_cotizations')->where('id', 466)->first();
if (!$group) {
    echo "❌ Grupo 466 no encontrado\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "ANÁLISIS GRUPO 466 - EXCESO DE RUTAS\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// Analizar session y mensajes usando client_id
$session = DB::table('conversation_sessions')
    ->where('client_id', $group->client_id)
    ->latest('created_at')
    ->first();

if ($session) {
    echo "SESSION INFO:\n";
    echo "-------------------------------------------------------------------------------\n";
    echo "Session ID: {$session->session_id}\n";
    echo "Created: {$session->created_at}\n\n";
    
    $messages = DB::table('conversation_messages')
        ->where('session_id', $session->session_id)
        ->orderBy('created_at')
        ->get();
    
    echo "MENSAJES DEL CHAT:\n";
    echo "-------------------------------------------------------------------------------\n";
    echo "Total de mensajes: " . $messages->count() . "\n\n";
    
    foreach ($messages as $idx => $msg) {
        $num = $idx + 1;
        echo "Mensaje $num ({$msg->sender}):\n";
        $content = strlen($msg->content) > 300 ? substr($msg->content, 0, 300) . '...' : $msg->content;
        echo "$content\n\n";
    }
}

if ($group->prompt_original) {
    echo "\nPROMPT ORIGINAL:\n";
    echo "-------------------------------------------------------------------------------\n";
    echo $group->prompt_original . "\n";
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "EXTRACTED_DATA:\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$extracted = json_decode($group->extracted_data, true);

if (is_array($extracted)) {
    if (isset($extracted[0]) && is_array($extracted[0])) {
        echo "✅ Total de rutas extraídas: " . count($extracted) . "\n\n";
        foreach ($extracted as $idx => $ruta) {
            $num = $idx + 1;
            echo "Ruta $num:\n";
            echo "  Origen: " . ($ruta['origen'] ?? $ruta['ciudad_origen'] ?? 'N/A') . "\n";
            echo "  Destino: " . ($ruta['destino'] ?? $ruta['ciudad_destino'] ?? 'N/A') . "\n";
            echo "  Peso: " . ($ruta['peso_kg'] ?? 'N/A') . " kg\n";
            echo "  Producto: " . ($ruta['producto'] ?? 'N/A') . "\n";
            echo "  Valor: " . ($ruta['valor_declarado'] ?? 'N/A') . "\n";
            echo "  Incluye tara: " . ($ruta['incluye_tara'] ?? 'N/A') . "\n\n";
        }
    }
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "COTIZACIONES CREADAS EN LA BD:\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$cotizaciones = DB::table('cotizacion_models')
    ->where('group_cotization_id', 466)
    ->orderBy('id')
    ->get();

echo "✅ Total de cotizaciones creadas: " . $cotizaciones->count() . "\n\n";

foreach ($cotizaciones as $idx => $cot) {
    $num = $idx + 1;
    echo "Cotización $num (ID: $cot->id):\n";
    echo "  {$cot->ciudad_origen} → {$cot->ciudad_destino}\n";
    echo "  Peso: {$cot->peso_mercancia} kg\n";
    echo "  Producto: " . ($cot->tipo_producto ?? 'N/A') . "\n\n";
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "PROBLEMA IDENTIFICADO:\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

if (count($extracted) > 2) {
    echo "⚠️  Se extrajeron " . count($extracted) . " rutas cuando el prompt solo menciona 2\n";
    echo "    El sistema debe estar creando rutas duplicadas o interpretando mal el prompt\n";
}
