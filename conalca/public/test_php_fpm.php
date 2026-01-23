<?php
// Endpoint de prueba para verificar qué código está cargado PHP-FPM
header('Content-Type: application/json');

$testMessage = "Todo va para bogotá. Des de Ibagué salen 4,500 kilogramos de tomates en cajas, des de Villavicencio 7 toneladas de papas en sacos, y des de Tunja 3,200 kilogramos de cebolla";

// Test del patrón
$patronDestino = '/(?:todo|todas?)\s+(?:va|van)\s+(?:para|a|hacia)\s+([a-záéíóúñ\s]+?)[\.,]/ui';
$patronOrigenes = '/(?:des\s+de|desde)\s+([a-záéíóúñ\s]+?)\s+(?:salen?|sale|van|va|\d)/ui';

$result = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'file_modified' => filemtime(__DIR__ . '/../app/Services/MCPAssistantService.php'),
    'file_modified_readable' => date('Y-m-d H:i:s', filemtime(__DIR__ . '/../app/Services/MCPAssistantService.php')),
    'destino_match' => preg_match($patronDestino, $testMessage, $destMatch) ? trim($destMatch[1]) : null,
    'origenes_count' => 0,
    'origenes' => [],
    'patron_1_6_exists' => false
];

if (preg_match_all($patronOrigenes, $testMessage, $origenMatches)) {
    $result['origenes_count'] = count($origenMatches[1]);
    $result['origenes'] = array_map('trim', $origenMatches[1]);
}

// Verificar si existe el código del Patrón 1.6
$serviceFile = file_get_contents(__DIR__ . '/../app/Services/MCPAssistantService.php');
$result['patron_1_6_exists'] = strpos($serviceFile, 'Patrón 1.6:') !== false;
$result['fix_572_exists'] = strpos($serviceFile, 'FIX #572') !== false;

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
