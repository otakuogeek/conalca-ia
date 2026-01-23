<?php
// Script para limpiar OPcache desde el servidor web
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo json_encode([
        'success' => true,
        'message' => 'OPcache limpiado correctamente',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'OPcache no está habilitado',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
