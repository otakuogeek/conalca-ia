#!/usr/bin/env php
<?php

/**
 * Script de importación directa de productos desde CSV a MySQL
 * Uso: php import_productos_csv.php
 */

// Configuración de base de datos
$dbConfig = [
    'host' => 'ai-transport.czqmsk4ck839.us-west-1.rds.amazonaws.com',
    'port' => 3306,
    'database' => 'conalca',
    'username' => 'admin',
    'password' => '1Dy81fsrX0htEBWodTJ9',
];

// Archivo CSV
$csvFile = __DIR__ . '/Productos.csv';

echo "\n=================================================\n";
echo "IMPORTACIÓN DE PRODUCTOS - CSV A MySQL\n";
echo "=================================================\n\n";

// Verificar que el archivo existe
if (!file_exists($csvFile)) {
    die("ERROR: No se encontró el archivo CSV: {$csvFile}\n");
}

echo "✓ Archivo CSV encontrado: {$csvFile}\n";

try {
    // Conectar a MySQL
    echo "→ Conectando a la base de datos...\n";
    $dsn = sprintf(
        "mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['database']
    );
    
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✓ Conexión establecida exitosamente\n\n";
    
    // Verificar la tabla products
    echo "→ Verificando estructura de la tabla 'products'...\n";
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll();
    
    echo "✓ Tabla 'products' encontrada con " . count($columns) . " columnas\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";
    
    // Contar registros actuales
    $currentCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    echo "→ Registros actuales en la tabla: {$currentCount}\n\n";
    
    if ($currentCount > 0) {
        echo "⚠ ADVERTENCIA: La tabla ya contiene {$currentCount} registros.\n";
        echo "¿Desea continuar? Los registros duplicados serán omitidos. (s/n): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($line) !== 's') {
            die("\nImportación cancelada por el usuario.\n");
        }
        echo "\n";
    }
    
    // Abrir archivo CSV
    echo "→ Abriendo archivo CSV...\n";
    $file = fopen($csvFile, 'r');
    
    if (!$file) {
        die("ERROR: No se pudo abrir el archivo CSV\n");
    }
    
    // Leer encabezados
    $headers = fgetcsv($file);
    echo "✓ Columnas CSV: " . implode(', ', $headers) . "\n\n";
    
    // Preparar consulta de inserción
    $sql = "INSERT IGNORE INTO products (
        producto_codigo,
        producto_codigo_ministerio,
        producto_nombre,
        tippro_nombre,
        producto_fechacreacion,
        natcar_nombre,
        usuario_nombre
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $insertStmt = $pdo->prepare($sql);
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    echo "→ Iniciando importación...\n\n";
    
    $imported = 0;
    $skipped = 0;
    $errors = 0;
    $lineNumber = 1; // Primera línea es encabezado
    $batchSize = 100;
    $batchCount = 0;
    
    while (($row = fgetcsv($file)) !== false) {
        $lineNumber++;
        $batchCount++;
        
        try {
            // Combinar encabezados con valores
            $data = array_combine($headers, $row);
            
            // Convertir fecha si es necesario (YYYY-MM-DD a timestamp o dejarlo como está)
            $fechaCreacion = $data['producto_fechacreacion'] ?? null;
            if ($fechaCreacion && strtotime($fechaCreacion)) {
                $fechaCreacion = strtotime($fechaCreacion);
            } else {
                $fechaCreacion = null;
            }
            
            // Ejecutar inserción
            $result = $insertStmt->execute([
                (int)$data['producto_codigo'],
                $data['producto_codigo_ministerio'] === 'NULL' ? null : (int)$data['producto_codigo_ministerio'],
                $data['producto_nombre'] === 'NULL' ? null : $data['producto_nombre'],
                $data['tippro_nombre'] ?? null,
                $fechaCreacion,
                $data['natcar_nombre'] ?? null,
                $data['usuario_nombre'] ?? null,
            ]);
            
            if ($insertStmt->rowCount() > 0) {
                $imported++;
            } else {
                $skipped++;
            }
            
            // Mostrar progreso cada 100 registros
            if ($batchCount >= $batchSize) {
                echo sprintf(
                    "  Procesados: %d | Importados: %d | Omitidos: %d | Errores: %d\r",
                    $lineNumber - 1,
                    $imported,
                    $skipped,
                    $errors
                );
                $batchCount = 0;
            }
            
        } catch (PDOException $e) {
            $errors++;
            // Log de errores en archivo
            file_put_contents(
                __DIR__ . '/import_errors.log',
                sprintf("[%s] Línea %d: %s\n", date('Y-m-d H:i:s'), $lineNumber, $e->getMessage()),
                FILE_APPEND
            );
        }
    }
    
    fclose($file);
    
    // Confirmar transacción
    $pdo->commit();
    
    echo "\n\n=================================================\n";
    echo "IMPORTACIÓN COMPLETADA\n";
    echo "=================================================\n";
    echo "Total de registros procesados: " . ($lineNumber - 1) . "\n";
    echo "✓ Importados exitosamente:     {$imported}\n";
    echo "⊘ Omitidos (duplicados):       {$skipped}\n";
    echo "✗ Errores:                      {$errors}\n";
    
    if ($errors > 0) {
        echo "\nVer detalles de errores en: import_errors.log\n";
    }
    
    // Verificar total final
    $finalCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    echo "\n→ Total de registros en la tabla: {$finalCount}\n";
    echo "=================================================\n\n";
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "\n\n✗ ERROR DE BASE DE DATOS:\n";
    echo $e->getMessage() . "\n\n";
    exit(1);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "\n\n✗ ERROR:\n";
    echo $e->getMessage() . "\n\n";
    exit(1);
}

echo "✓ Proceso finalizado correctamente.\n\n";
exit(0);
