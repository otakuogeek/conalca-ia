<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportProductsFromCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:import {file=Productos.csv}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa productos desde un archivo CSV a la tabla products';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = base_path($this->argument('file'));

        if (!file_exists($filePath)) {
            $this->error("El archivo {$filePath} no existe.");
            return 1;
        }

        $this->info("Iniciando importación desde {$filePath}...");

        // Abrir el archivo CSV
        $file = fopen($filePath, 'r');
        
        if (!$file) {
            $this->error("No se pudo abrir el archivo.");
            return 1;
        }

        // Leer la primera línea (encabezados)
        $headers = fgetcsv($file);
        
        $imported = 0;
        $skipped = 0;
        $errors = 0;

        // Iniciar transacción para mejor rendimiento
        DB::beginTransaction();

        try {
            $bar = $this->output->createProgressBar();
            $bar->start();

            while (($row = fgetcsv($file)) !== false) {
                try {
                    // Mapear columnas del CSV
                    $data = array_combine($headers, $row);
                    
                    // Validar que tengamos los datos necesarios
                    if (empty($data['producto_codigo']) || empty($data['producto_nombre'])) {
                        $skipped++;
                        continue;
                    }

                    // Crear el producto con todos los campos del CSV
                    Product::create([
                        'producto_codigo' => $data['producto_codigo'],
                        'producto_codigo_ministerio' => $data['producto_codigo_ministerio'] ?? null,
                        'producto_nombre' => $data['producto_nombre'],
                        'tippro_nombre' => $data['tippro_nombre'] ?? null,
                        'producto_fechacreacion' => $data['producto_fechacreacion'] ?? null,
                        'natcar_nombre' => $data['natcar_nombre'] ?? null,
                        'usuario_nombre' => $data['usuario_nombre'] ?? null,
                    ]);

                    $imported++;
                    $bar->advance();

                } catch (\Exception $e) {
                    $errors++;
                    Log::error("Error importando producto: " . $e->getMessage(), [
                        'row' => $row
                    ]);
                }
            }

            DB::commit();
            $bar->finish();
            $this->newLine(2);

            fclose($file);

            // Mostrar resumen
            $this->info("✓ Importación completada:");
            $this->table(
                ['Métrica', 'Cantidad'],
                [
                    ['Productos importados', $imported],
                    ['Registros omitidos', $skipped],
                    ['Errores', $errors],
                ]
            );

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($file);
            $this->error("Error durante la importación: " . $e->getMessage());
            Log::error("Error fatal en importación de productos: " . $e->getMessage());
            return 1;
        }
    }
}
