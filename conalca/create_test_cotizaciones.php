#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client as GuzzleClient;

// Crear la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  CREAR COTIZACIONES CON FECHA_CARGUE DESDE API            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$apiUrl = 'http://localhost:8000/api/test/seed-cotizacion';
$httpClient = new GuzzleClient();

// Definir 4 cotizaciones de prueba con fechas diferentes
$cotizacionesParaCrear = [
    [
        'nombre' => 'Carga 1: DUITAMA → BOGOTA',
        'user_id' => 13,
        'client_name' => 'CONALCA SAS',
        'reference' => 'TEST-FECHA-CARGUE-001-' . date('His'),
        'operation_type' => 'DISTRIBUCION',
        'rutas' => [
            [
                'ciudad_origen' => 'DUITAMA',
                'ciudad_destino' => 'BOGOTA',
                'valor' => 2576000,
                'flete' => 2300000,
                'peso_mercancia' => '30000',
                'cantidad' => '28',
                'cantidad_vh' => '1',
                'tipo_carroceria' => 'CONTENEDOR',
                'vehiculo_requerido' => 'TRACTOMULA 3',
                'valor_declarado' => '1000000',
                'tipo_mercancia' => 'REPUESTOS',
                'fecha_cargue' => '2026-04-29 11:00'  // ← FECHA CARGUE
            ]
        ]
    ],
    [
        'nombre' => 'Carga 2: BOGOTA → MEDELLIN',
        'user_id' => 13,
        'client_name' => 'CONALCA SAS',
        'reference' => 'TEST-FECHA-CARGUE-002-' . date('His'),
        'operation_type' => 'DISTRIBUCION',
        'rutas' => [
            [
                'ciudad_origen' => 'BOGOTA',
                'ciudad_destino' => 'MEDELLIN',
                'valor' => 3980000,
                'flete' => 3200000,
                'peso_mercancia' => '15000',
                'cantidad' => '20',
                'cantidad_vh' => '1',
                'tipo_carroceria' => 'FURGON',
                'vehiculo_requerido' => 'TRACTOCAMION',
                'valor_declarado' => '150000000',
                'tipo_mercancia' => 'TEXTILES',
                'fecha_cargue' => '2026-04-29 14:30'  // ← FECHA CARGUE
            ]
        ]
    ],
    [
        'nombre' => 'Carga 3: CARTAGENA → BOGOTA',
        'user_id' => 13,
        'client_name' => 'CONALCA SAS',
        'reference' => 'TEST-FECHA-CARGUE-003-' . date('His'),
        'operation_type' => 'DISTRIBUCION',
        'rutas' => [
            [
                'ciudad_origen' => 'CARTAGENA',
                'ciudad_destino' => 'BOGOTA',
                'valor' => 4500000,
                'flete' => 3800000,
                'peso_mercancia' => '25000',
                'cantidad' => '15',
                'cantidad_vh' => '1',
                'tipo_carroceria' => 'PLANCHON',
                'vehiculo_requerido' => 'TRACTOMULA 3',
                'valor_declarado' => '200000000',
                'tipo_mercancia' => 'ELECTRODOMÉSTICOS',
                'fecha_cargue' => '2026-04-29 09:15'  // ← FECHA CARGUE
            ]
        ]
    ],
    [
        'nombre' => 'Carga 4: CALI → BUCARAMANGA',
        'user_id' => 13,
        'client_name' => 'CONALCA SAS',
        'reference' => 'TEST-FECHA-CARGUE-004-' . date('His'),
        'operation_type' => 'DISTRIBUCION',
        'rutas' => [
            [
                'ciudad_origen' => 'CALI',
                'ciudad_destino' => 'BUCARAMANGA',
                'valor' => 3200000,
                'flete' => 2700000,
                'peso_mercancia' => '18000',
                'cantidad' => '22',
                'cantidad_vh' => '1',
                'tipo_carroceria' => 'CONTENEDOR',
                'vehiculo_requerido' => 'TRACTOMULA 3',
                'valor_declarado' => '120000000',
                'tipo_mercancia' => 'ALIMENTOS',
                'fecha_cargue' => '2026-04-29 16:45'  // ← FECHA CARGUE
            ]
        ]
    ],
];

$cotizacionesCreadas = [];

// Enviar cada cotización al API
foreach ($cotizacionesParaCrear as $index => $data) {
    $nombre = $data['nombre'];
    echo "📤 Enviando: {$nombre}\n";
    
    try {
        $response = $httpClient->post($apiUrl, [
            'json' => $data,
            'timeout' => 30,
        ]);
        
        $respData = json_decode($response->getBody(), true);
        
        if ($respData['success'] && isset($respData['data']['cotizaciones'][0])) {
            $cot = $respData['data']['cotizaciones'][0];
            echo "   ✅ Creada - ID: {$cot['id']} | Fecha Cargue: {$cot['fecha_cargue']}\n";
            $cotizacionesCreadas[] = [
                'id' => $cot['id'],
                'group_id' => $cot['group_cotization_id'],
                'fecha_cargue_esperada' => $data['rutas'][0]['fecha_cargue']
            ];
        } else {
            echo "   ❌ Error: " . ($respData['message'] ?? 'Error desconocido') . "\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ Excepción: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Validar que las fechas se guardaron correctamente en BD
echo "\n" . str_repeat("═", 60) . "\n";
echo "✅ VALIDACIÓN EN BASE DE DATOS\n";
echo str_repeat("═", 60) . "\n\n";

foreach ($cotizacionesCreadas as $cot) {
    $cotizacion = DB::table('cotizacion_models')
        ->where('id', $cot['id'])
        ->select('id', 'grupo_cotization_id', 'ciudad_origen', 'ciudad_destino', 'fecha_hora_descargue_cargue')
        ->first();
    
    if ($cotizacion) {
        $fechaEnBD = $cotizacion->fecha_hora_descargue_cargue;
        $fechaEsperada = $cot['fecha_cargue_esperada'];
        $estado = ($fechaEnBD === $fechaEsperada) ? '✅' : '⚠️';
        
        echo "Cotización ID {$cot['id']}:\n";
        echo "  Ruta: {$cotizacion->ciudad_origen} → {$cotizacion->ciudad_destino}\n";
        echo "  Fecha esperada: {$fechaEsperada}\n";
        echo "  Fecha en BD: " . ($fechaEnBD ?? 'NULL') . "\n";
        echo "  Estado: {$estado}\n\n";
    }
}

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  RESUMEN PARA MCP                                         ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "Cotizaciones creadas con fecha_cargue:\n";
foreach ($cotizacionesCreadas as $cot) {
    echo "  • ID {$cot['id']}: {$cot['fecha_cargue_esperada']}\n";
}

echo "\n✅ Ahora el MCP puede validar estos IDs y encontrará fecha_cargue\n";
echo "   en el campo: cotizacion_models.fecha_hora_descargue_cargue\n";
