<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Crear la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFICACIÓN COMPLETA COTIZACIONES 1910-1913 ===\n\n";

$ids = [1910, 1911, 1912, 1913];

foreach ($ids as $id) {
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║ COTIZACIÓN ID: $id\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    
    // Tabla principal
    $quote = DB::table('cotizacion_models')->where('id', $id)->first();
    
    if (!$quote) {
        echo "❌ NO ENCONTRADA EN cotizacion_models\n";
        continue;
    }
    
    echo "✅ ENCONTRADA EN cotizacion_models\n";
    echo "   Created At: {$quote->created_at}\n";
    echo "   Group ID: {$quote->group_cotization_id}\n";
    echo "   User ID: {$quote->user_id}\n";
    
    // Buscar en group_cotizations
    echo "\n   📌 DATOS DE GRUPO (group_cotization_id: {$quote->group_cotization_id}):\n";
    $group = DB::table('group_cotizations')->where('id', $quote->group_cotization_id)->first();
    
    if ($group) {
        echo "      ✓ Encontrado en group_cotizations\n";
        
        // Mostrar campos clave del grupo
        $groupFields = ['id', 'created_at', 'updated_at', 'api_source', 'source', 'origen', 'destino'];
        foreach ($groupFields as $field) {
            if (isset($group->$field) && $group->$field !== null) {
                echo "        - {$field}: {$group->$field}\n";
            }
        }
        
        // Mostrar TODOS los campos del grupo
        echo "        Todos los campos del grupo:\n";
        foreach ((array)$group as $field => $value) {
            if ($value !== null && $value !== '' && $value !== '0') {
                $val = is_string($value) && strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
                echo "          • {$field}: {$val}\n";
            }
        }
    } else {
        echo "      ⚠️ No encontrado en group_cotizations\n";
    }
    
    // Buscar en conversation_sessions por grupo
    echo "\n   💬 DATOS DE CONVERSACIÓN (group_cotization_id: {$quote->group_cotization_id}):\n";
    $sessions = DB::table('conversation_sessions')
        ->where('group_cotization_id', $quote->group_cotization_id)
        ->orWhere('cotizacion_id', $id)
        ->get();
    
    if (count($sessions) > 0) {
        echo "      ✓ Encontradas " . count($sessions) . " sesión(es) de conversación\n";
        foreach ($sessions as $session) {
            echo "        Session ID: {$session->id} | Created: {$session->created_at}\n";
        }
    } else {
        echo "      ⚠️ No encontradas sesiones de conversación\n";
    }
    
    // Buscar en call_logs
    echo "\n   📞 DATOS DE LLAMADAS (group_cotization_id: {$quote->group_cotization_id}):\n";
    $calls = DB::table('call_logs')
        ->where('group_cotization_id', $quote->group_cotization_id)
        ->get();
    
    if (count($calls) > 0) {
        echo "      ✓ Encontradas " . count($calls) . " llamada(s)\n";
        foreach ($calls as $call) {
            echo "        Call ID: {$call->id} | Created: {$call->created_at} | Status: {$call->status}\n";
        }
    } else {
        echo "      ⚠️ No encontradas llamadas registradas\n";
    }
    
    // Buscar en quotation_cargue (si existe)
    echo "\n   📋 DATOS DE CARGUE (si existen):\n";
    try {
        $cargues = DB::table('quotation_cargues')
            ->where('quotation_id', $id)
            ->orWhere('cotizacion_id', $id)
            ->get();
        
        if (count($cargues) > 0) {
            echo "      ✓ Encontrados " . count($cargues) . " registro(s) de cargue\n";
            foreach ($cargues as $cargue) {
                echo "        Cargue ID: {$cargue->id}\n";
                foreach ((array)$cargue as $field => $value) {
                    if ($value !== null && $value !== '') {
                        echo "          - {$field}: {$value}\n";
                    }
                }
            }
        } else {
            echo "      ⚠️ No encontrados registros de cargue\n";
        }
    } catch (\Exception $e) {
        echo "      ℹ️ Tabla quotation_cargues no existe o error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}
