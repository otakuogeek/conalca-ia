<?php

$texto = "bogotá a cali, 5 toneladas de café, 50 sacos, valor 10 millones medellín a barranquilla, 8 toneladas de banano cartagena a bucaramanga, 3 toneladas de pescado, 40 cajas, valor 6 millones";

// Detectar pares ciudad a ciudad
$palabrasExcluidas = '(?:millones?|mil|cientos?|miles|toneladas?|bultos?|sacos?|unidades?|cajas?|vehiculos?|veh[íi]culo|turbo|patineta|camioneta?|tractomula|de|del|con|sin|y|para|desde|' .
    'alimentos?|pesca|pescados?|bananos?|cafe|cafés?|arroz|ma[íi]z|cemento|arena|carbon|ganado|lacteos?|frutas?|verduras?|granos?|legumbres?|carne|pollos?|huevos?|azucar|sal)';
$patronCiudadACiudad = '/\b(?!' . $palabrasExcluidas . '\b)([a-záéíóúñ]+(?:\s+(?!' . $palabrasExcluidas . '\b)[a-záéíóúñ]+){0,2})\s+(?:a|hacia)\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})\b/ui';

preg_match_all($patronCiudadACiudad, $texto, $matchesCiudades, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

echo "================================================================================\n";
echo "PARES DETECTADOS: " . count($matchesCiudades) . "\n";
echo "================================================================================\n\n";

foreach ($matchesCiudades as $idx => $match) {
    $origen = trim($match[1][0]);
    $destino = trim($match[2][0]);
    $matchStart = $match[0][1];
    $matchEnd = $matchStart + strlen($match[0][0]);
    
    echo "RUTA " . ($idx + 1) . ": {$origen} → {$destino}\n";
    echo "  Posición: {$matchStart} - {$matchEnd}\n";
    
    // Contexto antes
    $contextoAntes = '';
    if ($idx > 0) {
        $prevMatchEnd = $matchesCiudades[$idx - 1][0][1] + strlen($matchesCiudades[$idx - 1][0][0]);
        $contextoAntes = substr($texto, $prevMatchEnd, $matchStart - $prevMatchEnd);
    } else {
        $contextoAntes = substr($texto, 0, $matchStart);
    }
    
    // Contexto después
    $nextMatchStart = isset($matchesCiudades[$idx + 1]) ? $matchesCiudades[$idx + 1][0][1] : strlen($texto);
    $contextoDespues = substr($texto, $matchEnd, $nextMatchStart - $matchEnd);
    
    echo "  contextoAntes: '{$contextoAntes}'\n";
    echo "  contextoDespues: '{$contextoDespues}'\n";
    
    // Intentar extraer producto del contextoDespues
    if (preg_match('/(?:de|producto:?)\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,1})(?:\s*[,.]|\s+en\s+|\s+son\s+|\s+valor|\s+veh[ií]culo|\s+NOTA|\s+empaquetad|\s+y\s+|\s+\d+|\s+[A-Z][a-z]+\s+a\s+|\s*$)/ui', $contextoDespues, $prodMatch)) {
        echo "  ✓ Producto en contextoDespues: '{$prodMatch[1]}'\n";
    } else {
        echo "  ✗ NO se encontró producto en contextoDespues\n";
        
        // Intentar en contextoAntes
        if (preg_match('/(?:de|producto:?)\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,1})(?:\s*[,.]|\s+en\s+|\s+son\s+|\s+valor|\s+veh[ií]culo|\s+NOTA|\s+empaquetad|\s+y\s+|\s+\d+|\s+[A-Z][a-z]+\s+a\s+|\s*$)/ui', $contextoAntes, $prodMatch)) {
            echo "  ⚠ Producto en contextoAntes (fallback): '{$prodMatch[1]}'\n";
        }
    }
    
    echo "\n";
}
