<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RemoveHotFile extends Command
{
    protected $signature = 'vite:remove-hot';
    protected $description = 'Elimina el archivo public/hot de Vite para restablecer CSS';

    public function handle()
    {
        $hotFile = base_path('public/hot');

        if (file_exists($hotFile)) {
            $deleted = @unlink($hotFile);
            
            if (!$deleted) {
                $this->error("No se pudo eliminar: {$hotFile}");
                Log::error('RemoveHotFile: No se pudo eliminar', ['file' => $hotFile]);
                return 1;
            }

            $this->info("Archivo hot eliminado: {$hotFile}");
            Log::info('RemoveHotFile: Archivo hot eliminado correctamente');
            return 0;
        }

        $this->info('Archivo hot no existe, nada que hacer.');
        return 0;
    }
}
