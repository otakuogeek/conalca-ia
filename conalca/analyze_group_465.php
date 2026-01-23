<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$group = DB::table('group_cotizations')->where('id', 465)->first();
if (!$group) {
    echo "❌ Grupo 465 no encontrado\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "ANÁLISIS DETALLADO GRUPO 465\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$extracted = json_decode($group->extracted_data, true);

echo "DATOS EXTRAÍDOS (extracted_data):\n";
echo "-------------------------------------------------------------------------------\n";
if (is_array($extracted)) {
    if (isset($extracted[0]) && is_array($extracted[0])) {
        // Múltiples rutas
        foreach ($extracted as $idx => $ruta) {
            $num = $idx + 1;
            echo "Ruta $num:\n";
            echo "  Origen: " . ($ruta['origen'] ?? $ruta['ciudad_origen'] ?? 'N/A') . "\n";
            echo "  Destino: " . ($ruta['destino'] ?? $ruta['ciudad_destino'] ?? 'N/A') . "\n";
            echo "  Peso: " . ($ruta['peso_kg'] ?? $ruta['peso_mercancia'] ?? 'N/A') . " kg\n";
            echo "  Incluye tara: " . (($ruta['incluye_tara'] ?? false) ? 'SÍ' : 'NO') . "\n";
            echo "  Producto: " . ($ruta['producto'] ?? $ruta['tipo_producto'] ?? 'N/A') . "\n";
            echo "  Cantidad: " . ($ruta['cantidad'] ?? 'N/A') . "\n";
            echo "  Empaque: " . ($ruta['empaque'] ?? $ruta['tipo_embalaje'] ?? 'N/A') . "\n";
            echo "  Vehículo: " . ($ruta['vehiculo'] ?? $ruta['vehiculo_requerido'] ?? 'N/A') . "\n";
            echo "  Valor declarado: " . ($ruta['valor_declarado'] ?? $ruta['valorMercancia'] ?? 'N/A') . "\n\n";
        }
    } else {
        // Una sola ruta
        echo "  Origen: " . ($extracted['origen'] ?? $extracted['ciudad_origen'] ?? 'N/A') . "\n";
        echo "  Destino: " . ($extracted['destino'] ?? $extracted['ciudad_destino'] ?? 'N/A') . "\n";
        echo "  Peso: " . ($extracted['peso_kg'] ?? $extracted['peso_mercancia'] ?? 'N/A') . " kg\n";
        echo "  Incluye tara: " . (($extracted['incluye_tara'] ?? false) ? 'SÍ' : 'NO') . "\n";
        echo "  Producto: " . ($extracted['producto'] ?? $extracted['tipo_producto'] ?? 'N/A') . "\n";
        echo "  Cantidad: " . ($extracted['cantidad'] ?? 'N/A') . "\n";
        echo "  Empaque: " . ($extracted['empaque'] ?? $extracted['tipo_embalaje'] ?? 'N/A') . "\n";
        echo "  Vehículo: " . ($extracted['vehiculo'] ?? $extracted['vehiculo_requerido'] ?? 'N/A') . "\n";
        echo "  Valor declarado: " . ($extracted['valor_declarado'] ?? $extracted['valorMercancia'] ?? 'N/A') . "\n\n";
    }
} else {
    echo "Formato no reconocido:\n";
    print_r($extracted);
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "HISTORIAL DE CHAT:\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// Buscar la sesión asociada al grupo
$session = DB::table('conversation_sessions')
    ->where('client_id', $group->client_id ?? 0)
    ->latest('created_at')
    ->first();

if (!$session) {
    echo "⚠️ No se encontró sesión de conversación para el cliente {$group->client_id}\n\n";
} else {
    echo "Session ID: {$session->session_id}\n";
    echo "Cliente ID: {$session->client_id}\n";
    echo "Creado: {$session->created_at}\n\n";
    
    // Obtener mensajes de esta sesión
    $messages = DB::table('conversation_messages')
        ->where('session_id', $session->session_id)
        ->orderBy('created_at')
        ->get();
    
    if ($messages->isEmpty()) {
        echo "⚠️ No hay mensajes en esta sesión\n\n";
    } else {
        echo "Total de mensajes: " . $messages->count() . "\n\n";
        foreach ($messages as $msg) {
            $role = strtoupper($msg->role ?? 'UNKNOWN');
            echo "[$role - {$msg->created_at}]:\n";
            echo substr($msg->content ?? $msg->message ?? '', 0, 500);
            if (strlen($msg->content ?? $msg->message ?? '') > 500) {
                echo "... (mensaje truncado)";
            }
            echo "\n" . str_repeat('-', 79) . "\n\n";
        }
    }
    
    // Metadata de la sesión
    if ($session->metadata) {
        echo "\nMETADATA DE LA SESIÓN:\n";
        echo str_repeat('-', 79) . "\n";
        $metadata = json_decode($session->metadata, true);
        if ($metadata) {
            foreach ($metadata as $key => $value) {
                if (is_array($value)) {
                    echo "$key: " . json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                } else {
                    echo "$key: $value\n";
                }
            }
        }
        echo "\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "COTIZACIONES CREADAS:\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$cotizaciones = DB::table('cotizacion_models')
    ->where('group_cotization_id', 465)
    ->orderBy('id')
    ->get();

if ($cotizaciones->isEmpty()) {
    echo "⚠️ No hay cotizaciones creadas para este grupo\n";
} else {
    foreach ($cotizaciones as $idx => $cot) {
        $num = $idx + 1;
        echo "Cotización $num (ID: $cot->id):\n";
        echo "  Origen: $cot->ciudad_origen\n";
        echo "  Destino: $cot->ciudad_destino\n";
        echo "  Peso: $cot->peso_mercancia kg\n";
        echo "  Producto: " . ($cot->tipo_producto ?? 'N/A') . "\n";
        echo "  Cantidad: " . ($cot->cantidad ?? 'N/A') . "\n";
        echo "  Empaque: " . ($cot->tipo_embajale ?? 'N/A') . "\n";
        echo "  Vehículo: $cot->vehiculo_requerido\n";
        echo "  Valor declarado: " . ($cot->valor_declarado ?? 'N/A') . "\n\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "ANÁLISIS DE PROBLEMAS:\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// Analizar problemas detectados
if (is_array($extracted)) {
    $rutas = isset($extracted[0]) && is_array($extracted[0]) ? $extracted : [$extracted];
    
    foreach ($rutas as $idx => $ruta) {
        $num = $idx + 1;
        echo "Ruta $num:\n";
        
        // Verificar precio
        $valor = $ruta['valor_declarado'] ?? $ruta['valorMercancia'] ?? 0;
        if ($valor == 0 || $valor == null) {
            echo "  ❌ PROBLEMA: Valor declarado no detectado o es 0\n";
        } else {
            echo "  ✅ Valor declarado: " . number_format($valor, 0, ',', '.') . "\n";
        }
        
        // Verificar producto
        $producto = $ruta['producto'] ?? $ruta['tipo_producto'] ?? '';
        if (empty($producto) || $producto == 'N/A') {
            echo "  ❌ PROBLEMA: Producto no detectado\n";
        } else {
            echo "  ✅ Producto detectado: $producto\n";
        }
        
        // Verificar tara
        $peso = $ruta['peso_kg'] ?? $ruta['peso_mercancia'] ?? 0;
        $incluyeTara = $ruta['incluye_tara'] ?? false;
        if (!$incluyeTara && $peso < 3400) {
            echo "  ⚠️ ADVERTENCIA: Peso muy bajo ($peso kg) y no incluye tara\n";
        }
        
        echo "\n";
    }
}
