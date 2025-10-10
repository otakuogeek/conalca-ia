<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LocalAudioStorage;

class AudioStorageDetails extends Command
{
    protected $signature = 'audio:storage-details';
    protected $description = 'Mostrar detalles completos del almacenamiento de audio';

    public function handle()
    {
        $this->info("🎵 DETALLES DEL ALMACENAMIENTO DE AUDIO");
        $this->line("═══════════════════════════════════════");
        
        try {
            $audioStorage = app(LocalAudioStorage::class);
            $stats = $audioStorage->getStorageStats();
            
            // 1. Resumen general
            $this->line("\n📊 RESUMEN GENERAL:");
            $this->table(['Métrica', 'Valor'], [
                ['📁 Archivos temporales', $stats['temp_files']],
                ['📁 Archivos de llamadas', $stats['calls_files']],
                ['💾 Tamaño temporales', $stats['temp_size_mb'] . ' MB'],
                ['💾 Tamaño llamadas', $stats['calls_size_mb'] . ' MB'],
                ['💾 TAMAÑO TOTAL', $stats['total_size_mb'] . ' MB'],
            ]);
            
            // 2. Directorios
            $this->line("\n📂 DIRECTORIOS:");
            $this->table(['Tipo', 'Ruta'], [
                ['🗂️ Temporales', $stats['temp_directory']],
                ['🗂️ Llamadas', $stats['calls_directory']],
            ]);
            
            // 3. Verificar si los directorios existen
            $this->line("\n🔍 VERIFICACIÓN DE DIRECTORIOS:");
            $tempExists = is_dir($stats['temp_directory']);
            $callsExists = is_dir($stats['calls_directory']);
            
            $this->table(['Directorio', 'Existe', 'Permisos'], [
                ['Temporales', $tempExists ? '✅ Sí' : '❌ No', $tempExists ? (is_writable($stats['temp_directory']) ? '✅ Escribible' : '❌ Solo lectura') : 'N/A'],
                ['Llamadas', $callsExists ? '✅ Sí' : '❌ No', $callsExists ? (is_writable($stats['calls_directory']) ? '✅ Escribible' : '❌ Solo lectura') : 'N/A'],
            ]);
            
            // 4. Listar archivos recientes
            if ($stats['temp_files'] > 0) {
                $this->line("\n📄 ARCHIVOS TEMPORALES RECIENTES:");
                $tempFiles = $audioStorage->listAudios('temp');
                
                if (!empty($tempFiles)) {
                    $recentFiles = array_slice($tempFiles, -5); // Últimos 5
                    $fileData = [];
                    
                    foreach ($recentFiles as $fileInfo) {
                        $fileData[] = [
                            basename($fileInfo['path']),
                            round($fileInfo['size'] / 1024, 2) . ' KB',
                            date('Y-m-d H:i:s', $fileInfo['modified']),
                        ];
                    }
                    
                    $this->table(['Archivo', 'Tamaño', 'Fecha'], $fileData);
                }
            }
            
            // 5. Recomendaciones
            $this->line("\n💡 RECOMENDACIONES:");
            if ($stats['temp_size_mb'] > 10) {
                $this->warn("⚠️ El directorio temporal tiene más de 10 MB. Considera ejecutar cleanup.");
                $this->line("🧹 Ejecuta: php artisan audio:cleanup");
            } else {
                $this->info("✅ El tamaño del almacenamiento está bajo control.");
            }
            
            if ($stats['temp_files'] > 20) {
                $this->warn("⚠️ Muchos archivos temporales ({$stats['temp_files']}). Considera limpieza automática.");
            }
            
            // 6. Estado del último cleanup
            $this->line("\n🧹 HISTORIAL DE LIMPIEZA:");
            if ($stats['last_cleanup']) {
                $this->info("✅ Última limpieza: " . $stats['last_cleanup']);
            } else {
                $this->warn("⚠️ No hay registros de limpieza automática");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error al obtener detalles de almacenamiento:");
            $this->line("💥 " . $e->getMessage());
        }
        
        $this->line("\n═══════════════════════════════════════");
        $this->info("🎵 Reporte de audio generado: " . now()->format('Y-m-d H:i:s'));
    }
}