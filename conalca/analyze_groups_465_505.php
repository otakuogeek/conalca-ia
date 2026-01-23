<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "ANÁLISIS COMPLETO: GRUPOS DE COTIZACIÓN 465-505\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// Estadísticas globales
$stats = [
    'total_grupos' => 0,
    'rutas_incorrectas' => 0,
    'producto_incorrecto' => 0,
    'valor_faltante' => 0,
    'peso_incorrecto' => 0,
    'tara_no_aplicada' => 0,
    'multiples_rutas_exceso' => 0,
    'ciudades_incorrectas' => 0,
];

$problemGroups = [];

// Obtener grupos del rango
$groups = DB::table('group_cotizations')
    ->whereBetween('id', [465, 505])
    ->orderBy('id')
    ->get();

echo "Total de grupos a analizar: " . $groups->count() . "\n\n";
echo "Iniciando análisis...\n";
echo "───────────────────────────────────────────────────────────────────────────────\n\n";

foreach ($groups as $group) {
    $stats['total_grupos']++;
    $groupId = $group->id;
    $hasProblems = false;
    $problems = [];
    
    // Obtener extracted_data
    $extracted = json_decode($group->extracted_data, true);
    
    if (!$extracted || !is_array($extracted)) {
        $problems[] = "❌ No hay datos extraídos";
        $hasProblems = true;
    }
    
    // Analizar si es multi-ruta
    $isMultiRoute = isset($extracted[0]) && is_array($extracted[0]);
    
    if ($isMultiRoute) {
        // Filtrar solo rutas numéricas
        $routes = array_filter($extracted, function($key) {
            return is_numeric($key);
        }, ARRAY_FILTER_USE_KEY);
        
        $routeCount = count($routes);
        
        // Obtener cotizaciones en BD
        $cotizaciones = DB::table('cotizacion_models')
            ->where('group_cotization_id', $groupId)
            ->count();
        
        // Validar si hay exceso de rutas
        if ($cotizaciones > $routeCount * 2) {
            $problems[] = "⚠️ EXCESO DE RUTAS: {$cotizaciones} cotizaciones para {$routeCount} rutas esperadas";
            $stats['multiples_rutas_exceso']++;
            $hasProblems = true;
        }
        
        // Analizar cada ruta
        foreach ($routes as $idx => $ruta) {
            $routeNum = $idx + 1;
            
            // Validar ciudades
            $origen = $ruta['origen'] ?? $ruta['ciudad_origen'] ?? null;
            $destino = $ruta['destino'] ?? $ruta['ciudad_destino'] ?? null;
            
            if (!$origen || !$destino) {
                $problems[] = "❌ Ruta {$routeNum}: Ciudades faltantes (origen: " . ($origen ?? 'N/A') . ", destino: " . ($destino ?? 'N/A') . ")";
                $stats['ciudades_incorrectas']++;
                $hasProblems = true;
            }
            
            // Validar ciudades con caracteres extraños
            if ($origen && (strlen($origen) < 3 || preg_match('/[¿?]/', $origen))) {
                $problems[] = "❌ Ruta {$routeNum}: Origen inválido '{$origen}'";
                $stats['ciudades_incorrectas']++;
                $hasProblems = true;
            }
            
            if ($destino && (strlen($destino) < 3 || preg_match('/[¿?]/', $destino))) {
                $problems[] = "❌ Ruta {$routeNum}: Destino inválido '{$destino}'";
                $stats['ciudades_incorrectas']++;
                $hasProblems = true;
            }
            
            // Validar producto
            $producto = $ruta['producto'] ?? null;
            if (!$producto || $producto === 'N/A' || $producto === 'null') {
                $problems[] = "⚠️ Ruta {$routeNum}: Producto faltante";
                $stats['producto_incorrecto']++;
                $hasProblems = true;
            } elseif (in_array(strtoupper($producto), ['CARTON', 'CARTÓN', 'MADERA', 'PLASTICO', 'CAJAS', 'SACOS'])) {
                $problems[] = "❌ Ruta {$routeNum}: Producto es material de embalaje '{$producto}' (debe ser el producto real)";
                $stats['producto_incorrecto']++;
                $hasProblems = true;
            }
            
            // Validar valor declarado
            $valor = $ruta['valor_declarado'] ?? null;
            if (!$valor || $valor === 'N/A' || $valor === 0) {
                $problems[] = "⚠️ Ruta {$routeNum}: Valor declarado faltante";
                $stats['valor_faltante']++;
                $hasProblems = true;
            }
            
            // Validar peso
            $peso = $ruta['peso_kg'] ?? null;
            if (!$peso || $peso === 'N/A' || $peso === 0) {
                $problems[] = "⚠️ Ruta {$routeNum}: Peso faltante";
                $stats['peso_incorrecto']++;
                $hasProblems = true;
            } elseif ($peso < 100) {
                // Si el peso es muy bajo, puede ser que no se haya sumado la tara
                $problems[] = "⚠️ Ruta {$routeNum}: Peso sospechoso ({$peso} kg) - posible tara no aplicada";
                $stats['tara_no_aplicada']++;
                $hasProblems = true;
            }
        }
    } else {
        // Ruta única
        $origen = $extracted['origen'] ?? $extracted['ciudad_origen'] ?? null;
        $destino = $extracted['destino'] ?? $extracted['ciudad_destino'] ?? null;
        $producto = $extracted['producto'] ?? null;
        $valor = $extracted['valor_declarado'] ?? null;
        $peso = $extracted['peso_kg'] ?? null;
        
        if (!$origen || !$destino) {
            $problems[] = "❌ Ciudades faltantes";
            $stats['ciudades_incorrectas']++;
            $hasProblems = true;
        }
        
        if (!$producto || $producto === 'N/A') {
            $problems[] = "⚠️ Producto faltante";
            $stats['producto_incorrecto']++;
            $hasProblems = true;
        } elseif (in_array(strtoupper($producto), ['CARTON', 'CARTÓN', 'MADERA', 'PLASTICO'])) {
            $problems[] = "❌ Producto es material de embalaje '{$producto}'";
            $stats['producto_incorrecto']++;
            $hasProblems = true;
        }
        
        if (!$valor || $valor === 'N/A') {
            $problems[] = "⚠️ Valor declarado faltante";
            $stats['valor_faltante']++;
            $hasProblems = true;
        }
        
        if (!$peso || $peso === 'N/A') {
            $problems[] = "⚠️ Peso faltante";
            $stats['peso_incorrecto']++;
            $hasProblems = true;
        }
    }
    
    if ($hasProblems) {
        $problemGroups[$groupId] = [
            'created_at' => $group->created_at,
            'is_multi_route' => $isMultiRoute,
            'route_count' => $isMultiRoute ? count(array_filter($extracted, function($k) { return is_numeric($k); }, ARRAY_FILTER_USE_KEY)) : 1,
            'problems' => $problems
        ];
    }
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "RESUMEN DE ESTADÍSTICAS\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

echo "Total de grupos analizados: {$stats['total_grupos']}\n";
echo "Grupos con problemas: " . count($problemGroups) . "\n\n";

echo "TIPOS DE ERRORES ENCONTRADOS:\n";
echo "───────────────────────────────────────────────────────────────────────────────\n";
echo "• Ciudades incorrectas/faltantes: {$stats['ciudades_incorrectas']}\n";
echo "• Productos incorrectos/faltantes: {$stats['producto_incorrecto']}\n";
echo "• Valores declarados faltantes: {$stats['valor_faltante']}\n";
echo "• Pesos incorrectos/faltantes: {$stats['peso_incorrecto']}\n";
echo "• Tara no aplicada (peso < 100kg): {$stats['tara_no_aplicada']}\n";
echo "• Múltiples rutas con exceso: {$stats['multiples_rutas_exceso']}\n";

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "GRUPOS CON PROBLEMAS DETALLADOS\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$count = 0;
foreach ($problemGroups as $groupId => $info) {
    $count++;
    $routeType = $info['is_multi_route'] ? "Multi-ruta ({$info['route_count']} rutas)" : "Ruta única";
    
    echo "Grupo #{$groupId} [{$routeType}] - {$info['created_at']}\n";
    echo "───────────────────────────────────────────────────────────────────────────────\n";
    foreach ($info['problems'] as $problem) {
        echo "  $problem\n";
    }
    echo "\n";
    
    if ($count >= 15) {
        echo "... (mostrando primeros 15 de " . count($problemGroups) . " grupos con problemas)\n\n";
        break;
    }
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "ANÁLISIS DE PATRONES COMUNES\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// Analizar patrones temporales
$problemsByDate = [];
foreach ($problemGroups as $groupId => $info) {
    $date = substr($info['created_at'], 0, 10);
    if (!isset($problemsByDate[$date])) {
        $problemsByDate[$date] = 0;
    }
    $problemsByDate[$date]++;
}

echo "DISTRIBUCIÓN DE PROBLEMAS POR FECHA:\n";
echo "───────────────────────────────────────────────────────────────────────────────\n";
arsort($problemsByDate);
$dateCount = 0;
foreach ($problemsByDate as $date => $count) {
    echo "• $date: $count grupos con problemas\n";
    $dateCount++;
    if ($dateCount >= 10) break;
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "SOLUCIONES RECOMENDADAS\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

if ($stats['producto_incorrecto'] > 0) {
    echo "✅ PRODUCTO:\n";
    echo "   - Ya implementado: extractProducto() filtra materiales de embalaje\n";
    echo "   - Patrón: '(televisores)' extrae TELEVISORES, no CARTÓN\n";
    echo "   - Grupos afectados necesitan re-procesamiento manual\n\n";
}

if ($stats['valor_faltante'] > 0) {
    echo "✅ VALOR DECLARADO:\n";
    echo "   - Ya implementado: extractValorDeclarado() reconoce \$10,000,000 COP\n";
    echo "   - System prompt actualizado con ejemplos de formatos\n";
    echo "   - Grupos antiguos no se benefician de la mejora\n\n";
}

if ($stats['multiples_rutas_exceso'] > 0) {
    echo "✅ MÚLTIPLES RUTAS:\n";
    echo "   - Ya corregido: Patrón requiere 'de X a Y' (no solo 'X a Y')\n";
    echo "   - Evita falsos positivos como 'Hola' → 'OLA'\n";
    echo "   - Grupos con 6+ rutas fueron creados antes del fix\n\n";
}

if ($stats['tara_no_aplicada'] > 0) {
    echo "⚠️ TARA:\n";
    echo "   - Verificar lógica de suma automática (3400kg)\n";
    echo "   - Detectar 'sin tara' y sumar peso estándar\n";
    echo "   - Algunos grupos pueden tener peso real < 100kg\n\n";
}

if ($stats['ciudades_incorrectas'] > 0) {
    echo "⚠️ CIUDADES:\n";
    echo "   - Origen/destino con caracteres inválidos\n";
    echo "   - Probable causa: regex capturó texto incorrecto\n";
    echo "   - Ya corregido con patrón más restrictivo\n\n";
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "RECOMENDACIONES FINALES\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

echo "1. 🕐 GRUPOS ANTIGUOS (antes 2026-01-22 11:30):\n";
echo "   - Fueron creados ANTES de los fixes implementados\n";
echo "   - No se benefician de las mejoras automáticas\n";
echo "   - Considerar re-procesamiento manual o ignorar\n\n";

echo "2. ✅ GRUPOS NUEVOS (después 2026-01-22 12:00):\n";
echo "   - Deberían usar lógica mejorada\n";
echo "   - Si tienen problemas, revisar logs para diagnóstico\n\n";

echo "3. 🔧 PRÓXIMOS PASOS:\n";
echo "   - Crear NUEVA cotización de prueba con prompt estándar\n";
echo "   - Validar que se generen solo 2 rutas correctas\n";
echo "   - Verificar que producto, valor y peso se extraigan bien\n\n";

echo "4. 📊 MONITOREO:\n";
echo "   - Analizar logs de grupos > 505 para validar fixes\n";
echo "   - Grupos con created_at > 2026-01-22 12:00:00\n";
echo "   - Buscar: 'Valor en COP detectado', 'Producto extraído'\n\n";

$porcentajeProblemas = round((count($problemGroups) / $stats['total_grupos']) * 100, 1);
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "CONCLUSIÓN: {$porcentajeProblemas}% de grupos tienen problemas\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
