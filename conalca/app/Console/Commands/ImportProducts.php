<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:products {file=Productos.csv}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importar productos desde archivo CSV a la tabla products';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = base_path($this->argument('file'));
        
        if (!file_exists($filePath)) {
            $this->error("El archivo no existe: {$filePath}");
            return 1;
        }

        $this->info("Iniciando importación de productos desde: {$filePath}");

        try {
            // Abrir el archivo CSV
            $file = fopen($filePath, 'r');
            
            // Leer la primera línea (encabezados)
            $headers = fgetcsv($file);
            
            $this->info("Columnas encontradas: " . implode(', ', $headers));
            
            // Iniciar transacción
            DB::beginTransaction();
            
            $imported = 0;
            $errors = 0;
            $batch = [];
            $batchSize = 500; // Insertar en lotes de 500 registros
            
            while (($row = fgetcsv($file)) !== false) {
                try {
                    // Combinar encabezados con valores
                    $data = array_combine($headers, $row);
                    
                    // Preparar los datos para inserción
                    $productData = [
                        'producto_codigo' => (int)$data['producto_codigo'],
                        'producto_codigo_ministerio' => $data['producto_codigo_ministerio'] ?? null,
                        'producto_nombre' => $data['producto_nombre'] ?? null,
                        'tippro_nombre' => $data['tippro_nombre'] ?? null,
                        'producto_fechacreacion' => $data['producto_fechacreacion'] ?? null,
                        'natcar_nombre' => $data['natcar_nombre'] ?? null,
                        'usuario_nombre' => $data['usuario_nombre'] ?? null,
                    ];
                    
                    $batch[] = $productData;
                    
                    // Insertar en lotes
                    if (count($batch) >= $batchSize) {
                        DB::table('products')->insert($batch);
                        $imported += count($batch);
                        $this->info("Importados {$imported} productos...");
                        $batch = [];
                    }
                    
                } catch (\Exception $e) {
                    $errors++;
                    Log::error("Error al importar producto: " . $e->getMessage(), [
                        'data' => $data ?? null
                    ]);
                }
            }
            
            // Insertar el último lote si queda algo
            if (count($batch) > 0) {
                DB::table('products')->insert($batch);
                $imported += count($batch);
            }
            
            fclose($file);
            
            // Confirmar transacción
            DB::commit();
            
            $this->info("✓ Importación completada exitosamente");
            $this->info("Total de productos importados: {$imported}");
            
            if ($errors > 0) {
                $this->warn("Errores encontrados: {$errors}");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error durante la importación: " . $e->getMessage());
            Log::error("Error en importación de productos", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
