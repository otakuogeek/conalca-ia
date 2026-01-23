<?php
/**
 * MONITOR DE CALIDAD - Validación de Grupos de Cotización
 * 
 * Este script analiza los grupos de cotización en la base de datos
 * y detecta problemas comunes como rutas falsas o datos incorrectos.
 */

require __DIR__.'/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();

$pdo = new PDO(
    'mysql:host='.$_ENV['DB_HOST'].';dbname='.$_ENV['DB_DATABASE'],
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD']
);

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "MONITOR DE CALIDAD - GRUPOS DE COTIZACIÓN\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// Obtener parámetros
$limit = isset($argv[1]) ? (int)$argv[1] : 20;
$desde = isset($argv[2]) ? $argv[2] : null;

echo "Analizando últimos $limit grupos...\n";
if ($desde) {
    echo "Desde fecha: $desde\n";
}
echo "\n";

// Query
$query = "SELECT id, extracted_data, created_at FROM group_cotizations";
if ($desde) {
    $query .= " WHERE created_at >= :desde";
}
$query .= " ORDER BY id DESC LIMIT :limit";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
if ($desde) {
    $stmt->bindValue(':desde', $desde, PDO::PARAM_STR);
}
$stmt->execute();
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$totalGrupos = count($grupos);
$gruposOk = 0;
$gruposConProblemas = 0;
$problemasPorTipo = [
    'ciudades_invalidas' => 0,
    'multiples_rutas_sospechosas' => 0,
    'sin_origen' => 0,
    'sin_destino' => 0,
    'peso_invalido' => 0,
    'producto_invalido' => 0
];

$ciudadesValidas = [
    'BOGOTA', 'MEDELLIN', 'CALI', 'BARRANQUILLA', 'CARTAGENA', 'BUCARAMANGA',
    'CUCUTA', 'PEREIRA', 'MANIZALES', 'IBAGUE', 'NEIVA', 'VILLAVICENCIO',
    'PASTO', 'POPAYAN', 'SANTA MARTA', 'MONTERIA', 'VALLEDUPAR', 'SINCELEJO',
    'RIOHACHA', 'QUIBDO', 'ARMENIA', 'BUENAVENTURA', 'TUMACO', 'TULUA',
    'PALMIRA', 'IPIALES', 'FLORENCIA', 'YOPAL', 'ARAUCA', 'LETICIA'
];

echo "───────────────────────────────────────────────────────────────────────────────\n";
echo "ANÁLISIS DETALLADO\n";
echo "───────────────────────────────────────────────────────────────────────────────\n\n";

foreach ($grupos as $grupo) {
    $id = $grupo['id'];
    $fecha = $grupo['created_at'];
    $data = json_decode($grupo['extracted_data'], true);
    
    $problemas = [];
    $tieneProblemas = false;
    
    // Determinar si es multi-ruta
    $isMultiRuta = is_array($data) && isset($data[0]) && is_array($data[0]);
    $numRutas = $isMultiRuta ? count($data) : 1;
    
    if ($isMultiRuta) {
        $rutas = $data;
    } else {
        $rutas = [$data];
    }
    
    // Validar cada ruta
    foreach ($rutas as $idx => $ruta) {
        $rutaNum = $idx + 1;
        
        // Validar origen
        $origen = $ruta['origen'] ?? 'N/A';
        if ($origen === 'N/A' || empty($origen)) {
            $problemas[] = "Ruta $rutaNum: Sin origen";
            $problemasPorTipo['sin_origen']++;
            $tieneProblemas = true;
        } elseif (!in_array($origen, $ciudadesValidas)) {
            // Detectar orígenes sospechosos
            if (preg_match('/PRODUCTO|VALOR|DECLARADO|EMBA|TIPO|VEHICULO|EXPORTACION|MANEJO|000|COP/i', $origen)) {
                $problemas[] = "Ruta $rutaNum: Origen inválido '$origen' (parece texto basura)";
                $problemasPorTipo['ciudades_invalidas']++;
                $tieneProblemas = true;
            }
        }
        
        // Validar destino
        $destino = $ruta['destino'] ?? 'N/A';
        if ($destino === 'N/A' || empty($destino)) {
            $problemas[] = "Ruta $rutaNum: Sin destino";
            $problemasPorTipo['sin_destino']++;
            $tieneProblemas = true;
        } elseif (!in_array($destino, $ciudadesValidas)) {
            if (preg_match('/TAMANO|SU TAMANO|TAMAÑO/i', $destino)) {
                $problemas[] = "Ruta $rutaNum: Destino inválido '$destino' (bug conocido)";
                $problemasPorTipo['ciudades_invalidas']++;
                $tieneProblemas = true;
            }
        }
        
        // Validar peso
        $peso = $ruta['peso_kg'] ?? $ruta['peso_mercancia'] ?? $ruta['pesoMercancia'] ?? null;
        if ($peso === null || $peso === 'N/A' || $peso <= 0) {
            $problemas[] = "Ruta $rutaNum: Peso inválido o faltante";
            $problemasPorTipo['peso_invalido']++;
            $tieneProblemas = true;
        }
        
        // Validar producto
        $producto = $ruta['producto'] ?? 'N/A';
        if ($producto === 'REFORZADA' || $producto === 'MADERA' || $producto === 'CARTON') {
            $problemas[] = "Ruta $rutaNum: Producto inválido '$producto' (confundido con empaque)";
            $problemasPorTipo['producto_invalido']++;
            $tieneProblemas = true;
        }
    }
    
    // Detectar múltiples rutas sospechosas (8 rutas con mismo destino "SU TAMANO")
    if ($numRutas >= 6) {
        $destinos = array_map(function($r) { return $r['destino'] ?? ''; }, $rutas);
        $destinosUnicos = array_unique($destinos);
        if (count($destinosUnicos) === 1 && in_array('SU TAMANO', $destinosUnicos)) {
            $problemas[] = "$numRutas rutas con destino 'SU TAMANO' (bug #508)";
            $problemasPorTipo['multiples_rutas_sospechosas']++;
            $tieneProblemas = true;
        }
    }
    
    // Imprimir resultado
    if ($tieneProblemas) {
        echo "❌ Grupo #$id [$fecha] - $numRutas ruta(s)\n";
        foreach ($problemas as $prob) {
            echo "   • $prob\n";
        }
        echo "\n";
        $gruposConProblemas++;
    } else {
        echo "✅ Grupo #$id [$fecha] - $numRutas ruta(s) OK\n";
        $gruposOk++;
    }
}

// Resumen
echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "RESUMEN\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

echo "Total de grupos analizados: $totalGrupos\n";
echo "✅ Grupos correctos: $gruposOk (" . round(($gruposOk/$totalGrupos)*100, 1) . "%)\n";
echo "❌ Grupos con problemas: $gruposConProblemas (" . round(($gruposConProblemas/$totalGrupos)*100, 1) . "%)\n\n";

echo "PROBLEMAS DETECTADOS POR TIPO:\n";
foreach ($problemasPorTipo as $tipo => $count) {
    if ($count > 0) {
        $label = str_replace('_', ' ', ucwords($tipo, '_'));
        echo "  • $label: $count\n";
    }
}

echo "\n";

if ($gruposConProblemas === 0) {
    echo "🎉 EXCELENTE - No se detectaron problemas\n";
} elseif ($gruposConProblemas < $totalGrupos * 0.1) {
    echo "✅ BUENO - Menos del 10% tienen problemas\n";
} elseif ($gruposConProblemas < $totalGrupos * 0.3) {
    echo "⚠️  ACEPTABLE - Entre 10% y 30% tienen problemas\n";
} else {
    echo "❌ CRÍTICO - Más del 30% tienen problemas\n";
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "USO:\n";
echo "  php tests/MonitorCalidad.php [limite] [desde_fecha]\n";
echo "\n";
echo "EJEMPLOS:\n";
echo "  php tests/MonitorCalidad.php 50\n";
echo "  php tests/MonitorCalidad.php 100 '2026-01-22 22:00:00'\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
