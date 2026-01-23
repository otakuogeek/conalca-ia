<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "VALIDACIÓN COMPLETA DEL ÚLTIMO GRUPO CREADO\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// 1. OBTENER ÚLTIMO GRUPO
$lastGroup = DB::table('group_cotizations')
    ->orderBy('id', 'desc')
    ->first();

if (!$lastGroup) {
    echo "❌ No se encontró ningún grupo\n";
    exit(1);
}

$groupId = $lastGroup->id;
$extracted = json_decode($lastGroup->extracted_data, true);

echo "📊 GRUPO #{$groupId}\n";
echo "───────────────────────────────────────────────────────────────────────────────\n";
echo "Fecha creación: {$lastGroup->created_at}\n";
echo "Cliente ID: {$lastGroup->client_id}\n\n";

// 2. OBTENER PROMPT ORIGINAL
$session = DB::table('conversation_sessions')
    ->where('client_id', $lastGroup->client_id)
    ->latest('created_at')
    ->first();

$promptOriginal = null;
if ($session) {
    $messages = DB::table('conversation_messages')
        ->where('session_id', $session->session_id)
        ->where('role', 'user')
        ->orderBy('created_at')
        ->first();
    
    if ($messages) {
        $promptOriginal = $messages->content;
    }
}

if ($promptOriginal) {
    echo "📝 PROMPT ORIGINAL:\n";
    echo "───────────────────────────────────────────────────────────────────────────────\n";
    echo wordwrap($promptOriginal, 75) . "\n\n";
} else {
    echo "⚠️ No se encontró prompt original en mensajes\n";
    if ($lastGroup->prompt_original) {
        $promptOriginal = $lastGroup->prompt_original;
        echo "📝 PROMPT (desde group_cotizations):\n";
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        echo wordwrap($promptOriginal, 75) . "\n\n";
    }
}

// 3. DETECTAR SI ES MULTI-RUTA
$isMultiRoute = isset($extracted[0]) && is_array($extracted[0]);
$routes = [];

if ($isMultiRoute) {
    $routes = array_filter($extracted, function($key) {
        return is_numeric($key);
    }, ARRAY_FILTER_USE_KEY);
    echo "✅ Tipo: MULTI-RUTA ({" . count($routes) . "} rutas)\n\n";
} else {
    $routes = [0 => $extracted];
    echo "✅ Tipo: RUTA ÚNICA\n\n";
}

// 4. VALIDACIÓN EXHAUSTIVA DE CADA RUTA
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "VALIDACIÓN DETALLADA DE RUTAS\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$warnings = 0;

foreach ($routes as $idx => $ruta) {
    $routeNum = $isMultiRoute ? ($idx + 1) : 1;
    
    echo "🔍 RUTA #{$routeNum}\n";
    echo "───────────────────────────────────────────────────────────────────────────────\n";
    
    // TEST 1: Validar origen
    $totalTests++;
    $origen = $ruta['origen'] ?? $ruta['ciudad_origen'] ?? null;
    if ($origen && strlen($origen) >= 3 && !preg_match('/[0-9¿?]/', $origen)) {
        if (!preg_match('/(PESO|VALOR|PRODUCTO|CONTENEDOR|CON\s+UN|KILOGRAMOS)/i', $origen)) {
            echo "  ✅ Origen: $origen\n";
            $passedTests++;
        } else {
            echo "  ⚠️  Origen sospechoso: $origen (contiene palabras clave)\n";
            $warnings++;
            $failedTests++;
        }
    } else {
        echo "  ❌ Origen inválido: " . ($origen ?? 'NULL') . "\n";
        $failedTests++;
    }
    
    // TEST 2: Validar destino
    $totalTests++;
    $destino = $ruta['destino'] ?? $ruta['ciudad_destino'] ?? null;
    if ($destino && strlen($destino) >= 3 && !preg_match('/[0-9¿?]/', $destino)) {
        if (!preg_match('/(PESO|VALOR|PRODUCTO|CONTENEDOR|CON\s+UN|PIES)/i', $destino)) {
            echo "  ✅ Destino: $destino\n";
            $passedTests++;
        } else {
            echo "  ⚠️  Destino sospechoso: $destino (contiene palabras clave)\n";
            $warnings++;
            $failedTests++;
        }
    } else {
        echo "  ❌ Destino inválido: " . ($destino ?? 'NULL') . "\n";
        $failedTests++;
    }
    
    // TEST 3: Validar peso
    $totalTests++;
    $peso = $ruta['peso_kg'] ?? $ruta['peso_mercancia'] ?? $ruta['pesoMercancia'] ?? null;
    if ($peso && is_numeric($peso) && $peso > 0 && $peso < 50000) {
        echo "  ✅ Peso: " . number_format($peso, 0) . " kg\n";
        $passedTests++;
        
        // Sub-test: Verificar si tiene tara aplicada
        if ($peso < 100) {
            echo "     ⚠️  Peso muy bajo, posible tara faltante\n";
            $warnings++;
        }
    } else {
        echo "  ❌ Peso inválido: " . ($peso ?? 'NULL') . "\n";
        $failedTests++;
    }
    
    // TEST 4: Validar producto
    $totalTests++;
    $producto = $ruta['producto'] ?? $ruta['tipo_producto'] ?? null;
    if ($producto && strlen($producto) >= 3) {
        // Verificar que no sea material de embalaje
        $materialesEmbalaje = ['CARTON', 'CARTÓN', 'MADERA', 'PLASTICO', 'PLÁSTICO', 'PAPEL', 'CAJAS', 'SACOS', 'BULTOS'];
        if (!in_array(strtoupper($producto), $materialesEmbalaje)) {
            echo "  ✅ Producto: $producto\n";
            $passedTests++;
        } else {
            echo "  ❌ Producto es material de embalaje: $producto\n";
            $failedTests++;
        }
    } else {
        echo "  ❌ Producto faltante o inválido\n";
        $failedTests++;
    }
    
    // TEST 5: Validar valor declarado
    $totalTests++;
    $valor = $ruta['valor_declarado'] ?? $ruta['valorMercancia'] ?? null;
    if ($valor && is_numeric($valor) && $valor > 0) {
        echo "  ✅ Valor declarado: $" . number_format($valor, 0) . "\n";
        $passedTests++;
    } else {
        echo "  ⚠️  Valor declarado: " . ($valor ?? 'N/A') . "\n";
        $warnings++;
    }
    
    // TEST 6: Validar cantidad
    $totalTests++;
    $cantidad = $ruta['cantidad'] ?? $ruta['cantidadMercancia'] ?? null;
    if ($cantidad && is_numeric($cantidad) && $cantidad > 0) {
        echo "  ✅ Cantidad: " . number_format($cantidad, 0) . " unidades\n";
        $passedTests++;
    } else {
        echo "  ⚠️  Cantidad: " . ($cantidad ?? 'N/A') . "\n";
        $warnings++;
    }
    
    // TEST 7: Validar empaque
    $totalTests++;
    $empaque = $ruta['empaque'] ?? $ruta['tipo_embalaje'] ?? null;
    if ($empaque) {
        echo "  ✅ Empaque: $empaque\n";
        $passedTests++;
    } else {
        echo "  ⚠️  Empaque: N/A\n";
        $warnings++;
    }
    
    echo "\n";
}

// 5. VALIDAR COTIZACIONES EN BD
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "VALIDACIÓN DE COTIZACIONES EN BASE DE DATOS\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$cotizaciones = DB::table('cotizacion_models')
    ->where('group_cotization_id', $groupId)
    ->get();

echo "Total cotizaciones creadas: {$cotizaciones->count()}\n";
echo "Total rutas esperadas: " . count($routes) . "\n\n";

$totalTests++;
if ($cotizaciones->count() == count($routes)) {
    echo "✅ Cantidad de cotizaciones coincide con rutas\n\n";
    $passedTests++;
} elseif ($cotizaciones->count() > count($routes) * 2) {
    echo "❌ EXCESO de cotizaciones ({$cotizaciones->count()} vs " . count($routes) . " esperadas)\n\n";
    $failedTests++;
} else {
    echo "⚠️  Cantidad de cotizaciones difiere\n\n";
    $warnings++;
}

// Mostrar cada cotización
foreach ($cotizaciones as $idx => $cot) {
    $num = $idx + 1;
    echo "Cotización #{$num} (ID: {$cot->id}):\n";
    echo "  Ruta: {$cot->ciudad_origen} → {$cot->ciudad_destino}\n";
    echo "  Peso: " . number_format($cot->peso_mercancia, 0) . " kg\n";
    
    // Validar ciudades en BD
    $totalTests++;
    if (strlen($cot->ciudad_origen) >= 3 && strlen($cot->ciudad_destino) >= 3) {
        $passedTests++;
    } else {
        echo "  ❌ Ciudades inválidas en BD\n";
        $failedTests++;
    }
    
    echo "\n";
}

// 6. RESUMEN FINAL
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "RESUMEN DE VALIDACIÓN\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;

echo "Total de pruebas: $totalTests\n";
echo "✅ Pruebas exitosas: $passedTests\n";
echo "❌ Pruebas fallidas: $failedTests\n";
echo "⚠️  Advertencias: $warnings\n";
echo "📊 Tasa de éxito: $successRate%\n\n";

if ($successRate >= 90) {
    echo "🎉 EXCELENTE - El grupo está correctamente procesado\n";
} elseif ($successRate >= 70) {
    echo "✅ BUENO - El grupo tiene la mayoría de datos correctos\n";
} elseif ($successRate >= 50) {
    echo "⚠️  REGULAR - El grupo tiene algunos problemas\n";
} else {
    echo "❌ CRÍTICO - El grupo tiene errores significativos\n";
}

// 7. ANÁLISIS DEL PROMPT
if ($promptOriginal) {
    echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
    echo "ANÁLISIS DEL PROMPT\n";
    echo "═══════════════════════════════════════════════════════════════════════════════\n\n";
    
    // Detectar patrones en el prompt
    $patterns = [
        'rutas_de_a' => preg_match_all('/\b(?:de|desde)\s+[a-záéíóúñ]+\s+(?:a|hacia)\s+[a-záéíóúñ]+/ui', $promptOriginal),
        'pesos_kg' => preg_match_all('/\d+(?:\.\d+)?\s*(?:kg|kilos?|kilogramos?)/ui', $promptOriginal),
        'pesos_ton' => preg_match_all('/\d+(?:\.\d+)?\s*(?:ton|toneladas?)/ui', $promptOriginal),
        'valores_cop' => preg_match_all('/\$\s*[\d.,]+\s*(?:cop|pesos?)/ui', $promptOriginal),
        'valores_millones' => preg_match_all('/\d+(?:\.\d+)?\s*millones?/ui', $promptOriginal),
        'sin_tara' => preg_match('/sin\s+tara/ui', $promptOriginal),
        'con_tara' => preg_match('/con\s+tara/ui', $promptOriginal),
        'productos_parentesis' => preg_match_all('/\([^)]+\)/u', $promptOriginal),
        'cajas_de' => preg_match_all('/\d+\s*cajas?\s+de\s+/ui', $promptOriginal),
    ];
    
    echo "Patrones detectados en el prompt:\n";
    echo "───────────────────────────────────────────────────────────────────────────────\n";
    
    if ($patterns['rutas_de_a'] > 0) {
        echo "✅ {$patterns['rutas_de_a']} rutas con formato 'de X a Y'\n";
    } else {
        echo "⚠️  No se detectó formato 'de X a Y' (puede causar problemas)\n";
    }
    
    if ($patterns['pesos_kg'] > 0 || $patterns['pesos_ton'] > 0) {
        $totalPesos = $patterns['pesos_kg'] + $patterns['pesos_ton'];
        echo "✅ {$totalPesos} mención(es) de peso\n";
    } else {
        echo "⚠️  No se detectaron pesos\n";
    }
    
    if ($patterns['valores_cop'] > 0 || $patterns['valores_millones'] > 0) {
        $totalValores = $patterns['valores_cop'] + $patterns['valores_millones'];
        echo "✅ {$totalValores} mención(es) de valor declarado\n";
    } else {
        echo "⚠️  No se detectaron valores declarados\n";
    }
    
    if ($patterns['sin_tara']) {
        echo "✅ Mención de 'sin tara' (debe sumar 3400kg)\n";
    } elseif ($patterns['con_tara']) {
        echo "✅ Mención de 'con tara' (peso ya incluye tara)\n";
    }
    
    if ($patterns['productos_parentesis'] > 0) {
        echo "✅ {$patterns['productos_parentesis']} producto(s) en paréntesis\n";
    }
    
    if ($patterns['cajas_de'] > 0) {
        echo "✅ {$patterns['cajas_de']} mención(es) de 'X cajas de...'\n";
    }
}

// 8. RECOMENDACIONES
echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "RECOMENDACIONES\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

if ($failedTests > 0) {
    echo "🔧 ACCIONES NECESARIAS:\n\n";
    
    if ($successRate < 70) {
        echo "1. Este grupo tiene problemas significativos\n";
        echo "2. Revisar el prompt original y verificar formato\n";
        echo "3. Considerar re-procesamiento manual\n\n";
    }
    
    echo "FORMATO RECOMENDADO DE PROMPT:\n";
    echo "───────────────────────────────────────────────────────────────────────────────\n";
    echo "Hola, necesito cotizar [N] rutas:\n";
    echo "1. De [CIUDAD_ORIGEN] a [CIUDAD_DESTINO], [XXX] kg sin tara,\n";
    echo "   [N] cajas de [producto], valor \$X,XXX,XXX COP\n";
    echo "2. De [CIUDAD_ORIGEN] a [CIUDAD_DESTINO], [XXX] kg sin tara,\n";
    echo "   [N] cajas de [producto], valor \$X,XXX,XXX COP\n\n";
} else {
    echo "✅ Este grupo está bien procesado\n";
    echo "✅ El prompt usado puede servir como referencia para futuras pruebas\n\n";
}

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "FIN DEL ANÁLISIS\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
