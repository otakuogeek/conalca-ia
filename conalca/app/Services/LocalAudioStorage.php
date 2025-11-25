<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LocalAudioStorage
{
    protected $disk;
    protected $audioPath = 'audios';
    protected $tempPath = 'audios/temp';
    protected $callsPath = 'audios/calls';

    public function __construct()
    {
        $this->disk = Storage::disk('public');
    }

    /**
     * Guarda un archivo de audio y retorna la URL pública
     */
    public function saveAudio($audioData, $filename = null, $subdirectory = 'temp')
    {
        try {
            // Generar nombre único si no se proporciona
            if (!$filename) {
                $filename = 'audio_' . time() . '_' . Str::random(8) . '.mp3';
            }

            // Asegurar extensión .mp3
            if (!str_ends_with($filename, '.mp3')) {
                $filename .= '.mp3';
            }

            // Determinar ruta completa
            $fullPath = $this->audioPath . '/' . $subdirectory . '/' . $filename;

            // Guardar archivo
            $saved = $this->disk->put($fullPath, $audioData);

            if ($saved) {
                $publicUrl = $this->getPublicUrl($fullPath);
                
                Log::info('Audio guardado exitosamente', [
                    'filename' => $filename,
                    'path' => $fullPath,
                    'url' => $publicUrl,
                    'size' => strlen($audioData)
                ]);

                return [
                    'success' => true,
                    'url' => $publicUrl,
                    'path' => $fullPath,
                    'filename' => $filename,
                    'size' => strlen($audioData)
                ];
            } else {
                Log::error('Error al guardar audio', ['filename' => $filename]);
                return [
                    'success' => false,
                    'error' => 'No se pudo guardar el archivo'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Excepción al guardar audio', [
                'error' => $e->getMessage(),
                'filename' => $filename ?? 'unknown'
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Genera URL pública para acceder al archivo
     */
    public function getPublicUrl($path)
    {
        return asset('storage/' . $path);
    }

    /**
     * Elimina un archivo de audio
     */
    public function deleteAudio($path)
    {
        try {
            if ($this->disk->exists($path)) {
                $deleted = $this->disk->delete($path);
                
                if ($deleted) {
                    Log::info('Audio eliminado', ['path' => $path]);
                    return true;
                }
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Error al eliminar audio', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Lista archivos de audio en un directorio
     */
    public function listAudios($subdirectory = 'temp')
    {
        try {
            $path = $this->audioPath . '/' . $subdirectory;
            $files = $this->disk->files($path);
            
            return array_map(function($file) {
                return [
                    'path' => $file,
                    'url' => $this->getPublicUrl($file),
                    'size' => $this->disk->size($file),
                    'modified' => $this->disk->lastModified($file)
                ];
            }, $files);
            
        } catch (\Exception $e) {
            Log::error('Error al listar audios', [
                'subdirectory' => $subdirectory,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Limpia archivos temporales antiguos (más de 1 hora)
     */
    public function cleanupTempAudios($maxAge = 3600)
    {
        try {
            $files = $this->disk->files($this->tempPath);
            $cleaned = 0;
            $currentTime = time();

            foreach ($files as $file) {
                $lastModified = $this->disk->lastModified($file);
                
                if (($currentTime - $lastModified) > $maxAge) {
                    if ($this->disk->delete($file)) {
                        $cleaned++;
                        Log::info('Archivo temporal limpiado', ['file' => $file]);
                    }
                }
            }

            Log::info('Limpieza de archivos temporales completada', [
                'files_cleaned' => $cleaned,
                'max_age_seconds' => $maxAge
            ]);

            return $cleaned;

        } catch (\Exception $e) {
            Log::error('Error en limpieza de archivos temporales', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Obtiene estadísticas de uso de almacenamiento
     */
    public function getStorageStats()
    {
        try {
            $tempFiles = $this->disk->files($this->tempPath);
            $callFiles = $this->disk->files($this->callsPath);
            
            $tempSize = array_sum(array_map(function($file) {
                return $this->disk->size($file);
            }, $tempFiles));
            
            $callsSize = array_sum(array_map(function($file) {
                return $this->disk->size($file);
            }, $callFiles));

            // Obtener directorio físico real
            $tempDirectory = storage_path('app/public/' . $this->tempPath);
            
            // Verificar último cleanup
            $lastCleanup = null;
            if (file_exists($tempDirectory . '/.last_cleanup')) {
                $lastCleanup = file_get_contents($tempDirectory . '/.last_cleanup');
            }

            return [
                'temp_files' => count($tempFiles),
                'temp_size_bytes' => $tempSize,
                'temp_size_mb' => round($tempSize / 1024 / 1024, 2),
                'calls_files' => count($callFiles),
                'calls_size_bytes' => $callsSize,
                'calls_size_mb' => round($callsSize / 1024 / 1024, 2),
                'total_size_mb' => round(($tempSize + $callsSize) / 1024 / 1024, 2),
                'temp_directory' => $tempDirectory,
                'calls_directory' => storage_path('app/public/' . $this->callsPath),
                'last_cleanup' => $lastCleanup ? date('Y-m-d H:i:s', strtotime($lastCleanup)) : null
            ];

        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas de almacenamiento', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'temp_files' => 0,
                'temp_size_mb' => 0,
                'calls_files' => 0,
                'calls_size_mb' => 0,
                'total_size_mb' => 0,
                'temp_directory' => storage_path('app/public/' . $this->tempPath),
                'calls_directory' => storage_path('app/public/' . $this->callsPath),
                'last_cleanup' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verifica si un archivo existe
     */
    public function exists($path)
    {
        return $this->disk->exists($path);
    }

    /**
     * Obtiene contenido de un archivo
     */
    public function getAudioContent($path)
    {
        if ($this->exists($path)) {
            return $this->disk->get($path);
        }
        return null;
    }
}