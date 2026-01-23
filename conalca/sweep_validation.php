<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "BARRIDO COMPLETO DE VALIDACIÓN - ÚLTIMOS 20 GRUPOS\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// Configuración
$LIMIT = 20; // Últimos 20 grupos
$MIN_SUCCESS_RATE = 70; // Mínimo aceptable

// Estadísticas globales
$globalStats = [
    'total_grupos' => 0,
    'grupos_excelentes' => 0, // >= 90%
    'grupos_buenos' => 0,      // >= 70%
    'grupos_regulares' => 0,   // >= 50%
    'grupos_criticos' => 0,    // < 50%
    'total_tests' => 0,
    'total_passed' => 0,
    'total_failed' => 0,
    'total_warnings' => 0,
];

$problemasPorTipo = [
    'ciudades_invalidas' => 0,
    'peso_invalido' => 0,
    'producto_invalido' => 0,
    'valor_faltante' => 0,
    'exceso_rutas' => 0,
];

// Obtener últimos grupos
$groups = DB::table('group_cotizations')
    ->orderBy('id', 'desc')
    ->limit($LIMIT)
    ->get()
    ->reverse(); // Ordenar de más antiguo a más reciente

echo "Analizando últimos {$LIMIT} grupos...\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";
echo "───────────────────────────────────────────────────────────────────────────────\n\n";

foreach ($groups as $group) {
    $globalStats['total_grupos']++;
    $groupId = $group->id;
    $extracted = json_decode($group->extracted_data, true);
    
    // Detectar tipo
    $isMultiRoute = isset($extracted[0]) && is_array($extracted[0]);
    $routes = [];
    
    if ($isMultiRoute) {
        $routes = array_filter($extracted, function($key) {
            return is_numeric($key);
        }, ARRAY_FILTER_USE_KEY);
    } else {
        $routes = [0 => $extracted];
    }
    
    $routeCount = count($routes);
    
    // Contadores locales
    $localTests = 0;
    $localPassed = 0;
    $localFailed = 0;
    $localWarnings = 0;
    
    // Validar cada ruta
    foreach ($routes as $idx => $ruta) {
        // TEST: Origen
        $localTests++;
        $globalStats['total_tests']++;
        $origen = $ruta['origen'] ?? $ruta['ciudad_origen'] ?? null;
        if ($origen && strlen($origen) >= 3 && !preg_match('/[0-9¿?]/', $origen) && 
            !preg_match('/(PESO|VALOR|PRODUCTO|CONTENEDOR|CON\s+UN|KILOGRAMOS|DECLARADO)/i', $origen)) {
            $localPassed++;
            $globalStats['total_passed']++;
        } else {
            $localFailed++;
            $globalStats['total_failed']++;
            $problemasPorTipo['ciudades_invalidas']++;
        }
        
        // TEST: Destino
        $localTests++;
        $globalStats['total_tests']++;
        $destino = $ruta['destino'] ?? $ruta['ciudad_destino'] ?? null;
        if ($destino && strlen($destino) >= 3 && !preg_match('/[0-9¿?]/', $destino) && 
            !preg_match('/(PESO|VALOR|PRODUCTO|CONTENEDOR|PIES|TAMANO|TAMAÑO)/i', $destino)) {
            $localPassed++;
            $globalStats['total_passed']++;
        } else {
            $localFailed++;
            $globalStats['total_failed']++;
            $problemasPorTipo['ciudades_invalidas']++;
        }
        
        // TEST: Peso
        $localTests++;
        $globalStats['total_tests']++;
        $peso = $ruta['peso_kg'] ?? $ruta['peso_mercancia'] ?? $ruta['pesoMercancia'] ?? null;
        if ($peso && is_numeric($peso) && $peso > 0 && $peso < 50000) {
            $localPassed++;
            $globalStats['total_passed']++;
            if ($peso < 100) {
                $localWarnings++;
                $globalStats['total_warnings']++;
            }
        } else {
            $localFailed++;
            $globalStats['total_failed']++;
            $problemasPorTipo['peso_invalido']++;
        }
        
        // TEST: Producto
        $localTests++;
        $globalStats['total_tests']++;
        $producto = $ruta['producto'] ?? $ruta['tipo_producto'] ?? null;
        $materialesEmbalaje = ['CARTON', 'CARTÓN', 'MADERA', 'PLASTICO', 'PLÁSTICO', 'CAJAS', 'SACOS', 'BULTOS'];
        if ($producto && strlen($producto) >= 3 && !in_array(strtoupper($producto), $materialesEmbalaje)) {
            $localPassed++;
            $globalStats['total_passed']++;
        } else {
            $localFailed++;
            $globalStats['total_failed']++;
            $problemasPorTipo['producto_invalido']++;
        }
        
        // TEST: Valor declarado
        $localTests++;
        $globalStats['total_tests']++;
        $valor = $ruta['valor_declarado'] ?? $ruta['valorMercancia'] ?? null;
        if ($valor && is_numeric($valor) && $valor > 0) {
            $localPassed++;
            $globalStats['total_passed']++;
        } else {
            $localWarnings++;
            $globalStats['total_warnings']++;
            $problemasPorTipo['valor_faltante']++;
        }
    }
    
    // Validar cotizaciones
    $localTests++;
    $globalStats['total_tests']++;
    $cotizaciones = DB::table('cotizacion_models')
        ->where('group_cotization_id', $groupId)
        ->count();
    
    if ($cotizaciones == $routeCount) {
        $localPassed++;
        $globalStats['total_passed']++;
    } elseif ($cotizaciones > $routeCount * 2) {
        $localFailed++;
        $globalStats['total_failed']++;
        $problemasPorTipo['exceso_rutas']++;
    } else {
        $localWarnings++;
        $globalStats['total_warnings']++;
    }
    
    // Calcular tasa de éxito local
    $successRate = $localTests > 0 ? round(($localPassed / $localTests) * 100, 1) : 0;
    
    // Clasificar grupo
    if ($successRate >= 90) {
        $globalStats['grupos_excelentes']++;
        $status = "🎉 EXCELENTE";
        $color = "\033[32m"; // Verde
    } elseif ($successRate >= 70) {
        $globalStats['grupos_buenos']++;
        $status = "✅ BUENO";
        $color = "\033[32m"; // Verde
    } elseif ($successRate >= 50) {
        $globalStats['grupos_regulares']++;
        $status = "⚠️  REGULAR";
        $color = "\033[33m"; // Amarillo
    } else {
        $globalStats['grupos_criticos']++;
        $status = "❌ CRÍTICO";
        $color = "\033[31m"; // Rojo
    }
    $resetColor = "\033[0m";
    
    // Imprimir resumen del grupo
    $tipo = $isMultiRoute ? "Multi ({$routeCount})" : "Única";
    echo "Grupo #{$groupId} [{$tipo}] - {$group->created_at}\n";
    echo "  Tests: {$localPassed}/{$localTests} | Tasa: {$successRate}% | {$color}{$status}{$resetColor}\n";
    
    if ($localFailed > 0) {
        echo "  ⚠️  {$localFailed} pruebas fallidas\n";
    }
    if ($localWarnings > 0) {
        echo "  ⚠️  {$localWarnings} advertencias\n";
    }
    echo "\n";
}

// REPORTE FINAL
echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "REPORTE GLOBAL DEL BARRIDO\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$globalSuccessRate = $globalStats['total_tests'] > 0 
    ? round(($globalStats['total_passed'] / $globalStats['total_tests']) * 100, 1) 
    : 0;

echo "ESTADÍSTICAS GENERALES:\n";
echo "───────────────────────────────────────────────────────────────────────────────\n";
echo "Total de grupos analizados: {$globalStats['total_grupos']}\n";
echo "Total de pruebas ejecutadas: {$globalStats['total_tests']}\n";
echo "Pruebas exitosas: {$globalStats['total_passed']}\n";
echo "Pruebas fallidas: {$globalStats['total_failed']}\n";
echo "Advertencias: {$globalStats['total_warnings']}\n";
echo "Tasa de éxito global: {$globalSuccessRate}%\n\n";

echo "DISTRIBUCIÓN POR CALIDAD:\n";
echo "───────────────────────────────────────────────────────────────────────────────\n";
echo "🎉 Excelentes (≥90%): {$globalStats['grupos_excelentes']}\n";
echo "✅ Buenos (≥70%): {$globalStats['grupos_buenos']}\n";
echo "⚠️  Regulares (≥50%): {$globalStats['grupos_regulares']}\n";
echo "❌ Críticos (<50%): {$globalStats['grupos_criticos']}\n\n";

echo "PROBLEMAS DETECTADOS POR TIPO:\n";
echo "───────────────────────────────────────────────────────────────────────────────\n";
echo "• Ciudades inválidas: {$problemasPorTipo['ciudades_invalidas']}\n";
echo "• Pesos inválidos: {$problemasPorTipo['peso_invalido']}\n";
echo "• Productos inválidos: {$problemasPorTipo['producto_invalido']}\n";
echo "• Valores faltantes: {$problemasPorTipo['valor_faltante']}\n";
echo "• Exceso de rutas: {$problemasPorTipo['exceso_rutas']}\n\n";

// EVALUACIÓN FINAL
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "EVALUACIÓN FINAL\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$gruposAceptables = $globalStats['grupos_excelentes'] + $globalStats['grupos_buenos'];
$porcentajeAceptable = round(($gruposAceptables / $globalStats['total_grupos']) * 100, 1);

if ($globalSuccessRate >= 90) {
    echo "🎉 SISTEMA EN EXCELENTE ESTADO\n";
    echo "   El {$porcentajeAceptable}% de los grupos son aceptables o mejores\n";
    echo "   La extracción de datos funciona correctamente\n\n";
} elseif ($globalSuccessRate >= 70) {
    echo "✅ SISTEMA EN BUEN ESTADO\n";
    echo "   El {$porcentajeAceptable}% de los grupos son aceptables\n";
    echo "   Hay algunos problemas menores que corregir\n\n";
} elseif ($globalSuccessRate >= 50) {
    echo "⚠️  SISTEMA REQUIERE ATENCIÓN\n";
    echo "   Solo el {$porcentajeAceptable}% de los grupos son aceptables\n";
    echo "   Se detectaron problemas significativos\n\n";
} else {
    echo "❌ SISTEMA EN ESTADO CRÍTICO\n";
    echo "   Solo el {$porcentajeAceptable}% de los grupos son aceptables\n";
    echo "   Se requiere intervención inmediata\n\n";
}

// RECOMENDACIONES ESPECÍFICAS
if ($problemasPorTipo['ciudades_invalidas'] > $globalStats['total_grupos'] * 0.2) {
    echo "⚠️  ALTO número de ciudades inválidas detectadas\n";
    echo "   → Revisar patrón de extracción de ciudades\n";
    echo "   → Validar que prompts usen formato 'de X a Y'\n\n";
}

if ($problemasPorTipo['producto_invalido'] > $globalStats['total_grupos'] * 0.1) {
    echo "⚠️  Productos inválidos detectados\n";
    echo "   → Verificar que extractProducto() se esté llamando\n";
    echo "   → Revisar filtros de materiales de embalaje\n\n";
}

if ($problemasPorTipo['valor_faltante'] > $globalStats['total_grupos'] * 0.3) {
    echo "⚠️  Muchos valores declarados faltantes\n";
    echo "   → Verificar que extractValorDeclarado() se esté llamando\n";
    echo "   → Revisar patrones de reconocimiento de valores\n\n";
}

if ($problemasPorTipo['exceso_rutas'] > 0) {
    echo "⚠️  Grupos con exceso de rutas detectados\n";
    echo "   → Revisar patrón de detección de múltiples rutas\n";
    echo "   → Verificar que no se generen rutas duplicadas\n\n";
}

// PRÓXIMOS PASOS
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "PRÓXIMOS PASOS\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

if ($globalSuccessRate >= 90) {
    echo "1. ✅ Sistema funcionando correctamente\n";
    echo "2. 📊 Monitorear grupos nuevos periódicamente\n";
    echo "3. 📝 Documentar casos de éxito como referencia\n";
} else {
    echo "1. 🔍 Analizar grupos con tasa < 70% individualmente\n";
    echo "2. 🔧 Aplicar correcciones específicas según tipo de error\n";
    echo "3. 🧪 Realizar pruebas con prompts estándar\n";
    echo "4. 📊 Re-ejecutar este script para validar mejoras\n";
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "FIN DEL BARRIDO\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
