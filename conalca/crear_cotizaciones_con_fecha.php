#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Crear la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  CREAR COTIZACIONES CON FECHA_CARGUE EN BD                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Obtener cliente CONALCA SAS
$client = DB::table('clients')->where('cliente', 'LIKE', '%CONALCA%')->first();
if (!$client) {
    echo "❌ No se encontró cliente CONALCA SAS\n";
    exit(1);
}

$clientId = $client->id;
$userId = 13;
echo "✓ Cliente encontrado: {$client->cliente} (ID: {$clientId})\n";
echo "✓ Usuario: {$userId}\n\n";

// Definir cotizaciones a crear
$cotizacionesData = [
    [
        'nombre' => 'Carga 1: DUITAMA → BOGOTA',
        'ciudad_origen' => 'DUITAMA',
        'ciudad_destino' => 'BOGOTA',
        'valor' => 2576000,
        'flete' => 2300000,
        'peso_mercancia' => '30000',
        'cantidad' => '28',
        'tipo_carroceria' => 'CONTENEDOR',
        'vehiculo_requerido' => 'TRACTOMULA 3',
        'valor_declarado' => '1000000',
        'tipo_mercancia' => 'REPUESTOS AUTOMOTRICES',
        'fecha_cargue' => '2026-04-29 11:00'
    ],
    [
        'nombre' => 'Carga 2: BOGOTA → MEDELLIN',
        'ciudad_origen' => 'BOGOTA',
        'ciudad_destino' => 'MEDELLIN',
        'valor' => 3980000,
        'flete' => 3200000,
        'peso_mercancia' => '15000',
        'cantidad' => '20',
        'tipo_carroceria' => 'FURGON',
        'vehiculo_requerido' => 'TRACTOCAMION',
        'valor_declarado' => '150000000',
        'tipo_mercancia' => 'TEXTILES',
        'fecha_cargue' => '2026-04-29 14:30'
    ],
    [
        'nombre' => 'Carga 3: CARTAGENA → BOGOTA',
        'ciudad_origen' => 'CARTAGENA',
        'ciudad_destino' => 'BOGOTA',
        'valor' => 4500000,
        'flete' => 3800000,
        'peso_mercancia' => '25000',
        'cantidad' => '15',
        'tipo_carroceria' => 'PLANCHON',
        'vehiculo_requerido' => 'TRACTOMULA 3',
        'valor_declarado' => '200000000',
        'tipo_mercancia' => 'ELECTRODOMÉSTICOS',
        'fecha_cargue' => '2026-04-29 09:15'
    ],
    [
        'nombre' => 'Carga 4: CALI → BUCARAMANGA',
        'ciudad_origen' => 'CALI',
        'ciudad_destino' => 'BUCARAMANGA',
        'valor' => 3200000,
        'flete' => 2700000,
        'peso_mercancia' => '18000',
        'cantidad' => '22',
        'tipo_carroceria' => 'CONTENEDOR',
        'vehiculo_requerido' => 'TRACTOMULA 3',
        'valor_declarado' => '120000000',
        'tipo_mercancia' => 'ALIMENTOS',
        'fecha_cargue' => '2026-04-29 16:45'
    ],
];

$cotizacionesCreadas = [];
$now = now();

echo "📝 Creando cotizaciones...\n\n";

foreach ($cotizacionesData as $index => $data) {
    try {
        DB::beginTransaction();
        
        // Crear grupo de cotización
        $groupId = DB::table('group_cotizations')->insertGetId([
            'user_id' => $userId,
            'client_id' => $clientId,
            'type' => 'dta',
            'operation_type' => 'DISTRIBUCION',
            'reference' => 'TEST-FECHA-' . ($index + 1) . '-' . date('YmdHis'),
            'status' => 'En tránsito',
            'cargo_type' => 'general',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        // Crear cotización
        $cotizacionId = DB::table('cotizacion_models')->insertGetId([
            'group_cotization_id' => $groupId,
            'client_id' => $clientId,
            'user_id' => $userId,
            'ciudad_origen' => $data['ciudad_origen'],
            'ciudad_destino' => $data['ciudad_destino'],
            'valor' => $data['valor'],
            'flete' => $data['flete'],
            'peso_mercancia' => $data['peso_mercancia'],
            'cantidad' => $data['cantidad'],
            'tipo_mercancia' => $data['tipo_mercancia'],
            'tipo_carroceria' => $data['tipo_carroceria'],
            'vehiculo_requerido' => $data['vehiculo_requerido'],
            'valor_declarado' => $data['valor_declarado'],
            'tipo_embajale' => 'CONTENEDOR',
            'seguro' => 'No',
            'temperatura_mercancia' => 'No aplica',
            'fecha_hora_descargue_cargue' => $data['fecha_cargue'],  // ← FECHA CARGUE
            'decision_cliente' => 'aceptada',
            'active' => true,
            'porcentaje' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        DB::commit();
        
        echo "✅ {$data['nombre']}\n";
        echo "   ID Cotización: {$cotizacionId}\n";
        echo "   ID Grupo: {$groupId}\n";
        echo "   Fecha Cargue: {$data['fecha_cargue']}\n";
        echo "   Valor: \${$data['valor']} | Flete: \${$data['flete']}\n\n";
        
        $cotizacionesCreadas[] = [
            'id' => $cotizacionId,
            'group_id' => $groupId,
            'fecha_cargue' => $data['fecha_cargue'],
            'ruta' => "{$data['ciudad_origen']} → {$data['ciudad_destino']}"
        ];
    } catch (\Exception $e) {
        DB::rollBack();
        echo "❌ Error creando {$data['nombre']}: " . $e->getMessage() . "\n\n";
    }
}

// VALIDAR EN BD
echo "\n" . str_repeat("═", 60) . "\n";
echo "🔍 VALIDACIÓN EN BASE DE DATOS\n";
echo str_repeat("═", 60) . "\n\n";

foreach ($cotizacionesCreadas as $cot) {
    $resultado = DB::table('cotizacion_models')
        ->where('id', $cot['id'])
        ->select(
            'id',
            'group_cotization_id',
            'ciudad_origen',
            'ciudad_destino',
            'fecha_hora_descargue_cargue',
            'created_at',
            'decision_cliente'
        )
        ->first();
    
    if ($resultado) {
        echo "Cotización ID {$resultado->id}:\n";
        echo "  ✓ Ruta: {$resultado->ciudad_origen} → {$resultado->ciudad_destino}\n";
        echo "  ✓ Fecha Cargue en BD: {$resultado->fecha_hora_descargue_cargue}\n";
        echo "  ✓ Decision Cliente: {$resultado->decision_cliente}\n";
        echo "  ✓ Creada: {$resultado->created_at}\n\n";
    }
}

// RESUMEN FINAL
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  RESUMEN - COTIZACIONES LISTAS PARA MCP                   ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "Cotizaciones con fecha_cargue registrada:\n";
foreach ($cotizacionesCreadas as $cot) {
    echo "  • ID {$cot['id']} | Grupo {$cot['group_id']} | Fecha: {$cot['fecha_cargue']} | {$cot['ruta']}\n";
}

echo "\n✅ CAMPO EN BD: cotizacion_models.fecha_hora_descargue_cargue\n";
echo "✅ TODAS LAS COTIZACIONES TIENEN decision_cliente = 'aceptada'\n";
echo "✅ TODAS LAS COTIZACIONES ESTÁN EN ESTADO 'En tránsito'\n";
echo "\n📌 El MCP ahora puede validar estos IDs y encontrará fecha_cargue\n";
