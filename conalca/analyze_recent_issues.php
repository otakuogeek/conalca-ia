<?php
/**
 * Análisis detallado de grupos recientes con problemas de ciudades
 */

require __DIR__.'/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$pdo = new PDO(
    'mysql:host='.$_ENV['DB_HOST'].';dbname='.$_ENV['DB_DATABASE'],
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD']
);

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "ANÁLISIS DETALLADO - GRUPOS CON PROBLEMAS DE CIUDADES\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// Analizar grupos 489-491 y 508
$grupos = [489, 490, 491, 508];

foreach ($grupos as $grupoId) {
    echo "───────────────────────────────────────────────────────────────────────────────\n";
    echo "GRUPO #$grupoId\n";
    echo "───────────────────────────────────────────────────────────────────────────────\n";
    
    // Obtener extracted_data
    $stmt = $pdo->prepare("SELECT extracted_data, created_at FROM group_cotizations WHERE id = ?");
    $stmt->execute([$grupoId]);
    $grupo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$grupo) {
        echo "❌ Grupo no encontrado\n\n";
        continue;
    }
    
    $extractedData = json_decode($grupo['extracted_data'], true);
    echo "Fecha: {$grupo['created_at']}\n";
    echo "Tipo: " . ($extractedData['tipo_cotizacion'] ?? 'N/A') . "\n\n";
    
    // Ver extracted_data
    echo "EXTRACTED_DATA:\n";
    if (isset($extractedData['origen'])) {
        echo "  Origen: {$extractedData['origen']}\n";
    }
    if (isset($extractedData['destino'])) {
        echo "  Destino: {$extractedData['destino']}\n";
    }
    
    if (isset($extractedData['rutas']) && is_array($extractedData['rutas'])) {
        echo "  Rutas: " . count($extractedData['rutas']) . "\n";
        foreach ($extractedData['rutas'] as $i => $ruta) {
            $num = $i + 1;
            $origen = $ruta['origen'] ?? 'N/A';
            $destino = $ruta['destino'] ?? 'N/A';
            
            // Validar ciudades
            $origenValid = ($origen !== 'N/A' && preg_match('/^[A-ZÁÉÍÓÚÑ\s]+$/', $origen));
            $destinoValid = ($destino !== 'N/A' && preg_match('/^[A-ZÁÉÍÓÚÑ\s]+$/', $destino));
            
            $origenIcon = $origenValid ? '✅' : '❌';
            $destinoIcon = $destinoValid ? '✅' : '❌';
            
            echo "    Ruta $num: $origenIcon $origen → $destinoIcon $destino\n";
        }
    }
    
    // Obtener cotizaciones
    $stmt = $pdo->prepare("SELECT origen, destino, peso_kg FROM cotizacion_models WHERE group_cotization_id = ?");
    $stmt->execute([$grupoId]);
    $cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nCOTIZACIONES GENERADAS: " . count($cotizaciones) . "\n";
    foreach ($cotizaciones as $i => $cot) {
        $num = $i + 1;
        $origen = $cot['origen'] ?? 'N/A';
        $destino = $cot['destino'] ?? 'N/A';
        $peso = $cot['peso_kg'] ?? 'N/A';
        
        $origenValid = ($origen !== 'N/A' && preg_match('/^[A-ZÁÉÍÓÚÑ\s]+$/', $origen));
        $destinoValid = ($destino !== 'N/A' && preg_match('/^[A-ZÁÉÍÓÚÑ\s]+$/', $destino));
        
        $origenIcon = $origenValid ? '✅' : '❌';
        $destinoIcon = $destinoValid ? '✅' : '❌';
        
        echo "  Cot $num: $origenIcon $origen → $destinoIcon $destino | Peso: {$peso}kg\n";
    }
    
    // Obtener prompt del usuario
    $stmt = $pdo->prepare("
        SELECT cm.content 
        FROM conversation_messages cm
        JOIN conversation_sessions cs ON cm.session_id = cs.session_id
        WHERE cs.group_cotization_id = ? AND cm.role = 'user'
        ORDER BY cm.created_at ASC
        LIMIT 1
    ");
    $stmt->execute([$grupoId]);
    $mensaje = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($mensaje) {
        echo "\nPROMPT DEL USUARIO:\n";
        $content = $mensaje['content'];
        if (strlen($content) > 300) {
            echo "  " . substr($content, 0, 300) . "...\n";
        } else {
            echo "  $content\n";
        }
        
        // Buscar menciones de ciudades
        $ciudadesMencionadas = [];
        if (preg_match_all('/\b(BOGOTA|BOGOTÁ|MEDELLIN|MEDELLÍN|CALI|BARRANQUILLA|CARTAGENA|BUCARAMANGA|CUCUTA|CÚCUTA|PEREIRA|MANIZALES|IBAGUE|IBAGUÉ|VILLAVICENCIO|PASTO|NEIVA|ARMENIA|SANTA MARTA|VALLEDUPAR|MONTERIA|MONTERÍA|SINCELEJO|POPAYAN|POPAYÁN|TUNJA|FLORENCIA|YOPAL|RIOHACHA|QUIBDO|QUIBDÓ|ARAUCA|LETICIA|INIRIDA|INÍRIDA|MITÚ|SAN JOSE DEL GUAVIARE|SAN JOSÉ DEL GUAVIARE|PUERTO CARREÑO|MOCOA|SAN ANDRES|SAN ANDRÉS)\b/iu', $content, $matches)) {
            $ciudadesMencionadas = array_unique($matches[0]);
            echo "\nCiudades mencionadas en el prompt: " . implode(', ', $ciudadesMencionadas) . "\n";
        }
    }
    
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "FIN DEL ANÁLISIS\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
