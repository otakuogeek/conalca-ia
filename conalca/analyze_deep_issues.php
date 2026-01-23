<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "ANÁLISIS PROFUNDO: GRUPOS CON CIUDADES/PESOS FALTANTES\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// Analizar grupos con problemas específicos
$groupsWithIssues = DB::table('group_cotizations')
    ->whereBetween('id', [474, 490])
    ->orderBy('id')
    ->get();

echo "Analizando grupos 474-490 (muchos tienen ciudades faltantes)...\n\n";

foreach ($groupsWithIssues as $group) {
    $extracted = json_decode($group->extracted_data, true);
    
    // Ver si hay datos en extracted_data pero en otro formato
    if ($extracted && is_array($extracted)) {
        $hasOrigen = isset($extracted['origen']) || isset($extracted['ciudad_origen']);
        $hasDestino = isset($extracted['destino']) || isset($extracted['ciudad_destino']);
        
        if (!$hasOrigen && !$hasDestino) {
            echo "Grupo #{$group->id} - {$group->created_at}\n";
            echo "───────────────────────────────────────────────────────────────────────────────\n";
            echo "extracted_data keys: " . implode(', ', array_keys($extracted)) . "\n";
            echo "extracted_data preview:\n";
            print_r(array_slice($extracted, 0, 10));
            echo "\n";
            
            // Ver si hay mensajes en la conversación
            $session = DB::table('conversation_sessions')
                ->where('client_id', $group->client_id)
                ->latest('created_at')
                ->first();
            
            if ($session) {
                $messages = DB::table('conversation_messages')
                    ->where('session_id', $session->session_id)
                    ->orderBy('created_at')
                    ->get();
                
                echo "Mensajes en sesión: {$messages->count()}\n";
                if ($messages->count() > 0) {
                    $firstMsg = $messages->first();
                    echo "Primer mensaje ({$firstMsg->sender}): " . substr($firstMsg->content, 0, 150) . "...\n";
                }
            }
            
            // Ver cotizaciones creadas
            $cotizaciones = DB::table('cotizacion_models')
                ->where('group_cotization_id', $group->id)
                ->get();
            
            echo "Cotizaciones en BD: {$cotizaciones->count()}\n";
            if ($cotizaciones->count() > 0) {
                $firstCot = $cotizaciones->first();
                echo "Primera cotización: {$firstCot->ciudad_origen} → {$firstCot->ciudad_destino}\n";
            }
            
            echo "\n";
        }
    }
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "ANÁLISIS DE CASOS ESPECÍFICOS CON PESO FALTANTE\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// Analizar grupos multi-ruta con peso faltante
$multiRouteGroups = DB::table('group_cotizations')
    ->whereBetween('id', [471, 481])
    ->whereIn('id', [471, 473, 480, 481])
    ->get();

foreach ($multiRouteGroups as $group) {
    $extracted = json_decode($group->extracted_data, true);
    
    echo "Grupo #{$group->id} - Multi-ruta\n";
    echo "───────────────────────────────────────────────────────────────────────────────\n";
    
    $routes = array_filter($extracted, function($key) {
        return is_numeric($key);
    }, ARRAY_FILTER_USE_KEY);
    
    echo "Rutas detectadas: " . count($routes) . "\n\n";
    
    foreach ($routes as $idx => $ruta) {
        $routeNum = $idx + 1;
        echo "  Ruta {$routeNum}:\n";
        echo "    Origen: " . ($ruta['origen'] ?? $ruta['ciudad_origen'] ?? 'N/A') . "\n";
        echo "    Destino: " . ($ruta['destino'] ?? $ruta['ciudad_destino'] ?? 'N/A') . "\n";
        echo "    Peso: " . ($ruta['peso_kg'] ?? 'N/A') . " kg\n";
        echo "    Producto: " . ($ruta['producto'] ?? 'N/A') . "\n";
        echo "    Valor: " . ($ruta['valor_declarado'] ?? 'N/A') . "\n";
        
        // Mostrar TODOS los campos para entender estructura
        $campos = array_keys($ruta);
        echo "    Campos disponibles: " . implode(', ', $campos) . "\n\n";
    }
    
    echo "\n";
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "DIAGNÓSTICO Y SOLUCIONES\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

echo "PROBLEMA 1: Ciudades faltantes en grupos 474-490\n";
echo "────────────────────────────────────────────────\n";
echo "Posible causa: extracted_data tiene estructura diferente\n";
echo "Solución: Verificar si los datos están en otro campo o formato\n\n";

echo "PROBLEMA 2: Pesos faltantes en multi-rutas\n";
echo "────────────────────────────────────────────\n";
echo "Posible causa: El peso está en el texto pero no se extrae por ruta\n";
echo "Solución: Mejorar extractMultipleRoutesData() para capturar peso por ruta\n\n";

echo "PROBLEMA 3: Valores declarados faltantes\n";
echo "──────────────────────────────────────────\n";
echo "Posible causa: Formato no reconocido o no se llama extractValorDeclarado()\n";
echo "Solución: Ya implementada, solo afecta grupos antiguos\n\n";
