<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class VoiceCacheService
{
    protected $cachePrefix;
    protected $ttl;
    protected $enabled;
    protected $cleanupInterval;
    
    public function __construct()
    {
        $this->cachePrefix = config('elevenlabs.cache.prefix', 'elevenlabs_audio_');
        $this->ttl = config('elevenlabs.cache.ttl', 43200); // 12 horas
        $this->enabled = config('elevenlabs.cache.enabled', true);
        $this->cleanupInterval = config('elevenlabs.cache.cleanup_interval', 3600);
    }
    
    /**
     * Obtener audio desde caché
     */
    public function getCachedAudio($text, $voiceId, $settings = [])
    {
        if (!$this->enabled) {
            return null;
        }
        
        $cacheKey = $this->generateCacheKey($text, $voiceId, $settings);
        
        try {
            $cachedData = Cache::get($cacheKey);
            
            if ($cachedData) {
                Log::info('Audio cache hit', [
                    'cache_key' => $cacheKey,
                    'text_length' => strlen($text)
                ]);
                
                // Verificar que el archivo físico aún existe
                if (isset($cachedData['file_path']) && Storage::disk('public')->exists($cachedData['file_path'])) {
                    return $cachedData;
                } else {
                    // Limpiar entrada de caché si el archivo no existe
                    Cache::forget($cacheKey);
                    Log::warning('Cache entry removed due to missing file', ['cache_key' => $cacheKey]);
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Error retrieving cached audio', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
    
    /**
     * Guardar audio en caché
     */
    public function cacheAudio($text, $voiceId, $settings, $audioContent)
    {
        if (!$this->enabled || empty($audioContent)) {
            return false;
        }
        
        try {
            $cacheKey = $this->generateCacheKey($text, $voiceId, $settings);
            
            // Generar nombre de archivo único
            $filename = $this->generateFilename($cacheKey);
            $filePath = "voices/cache/{$filename}";
            
            // Guardar archivo físico
            Storage::disk('public')->put($filePath, $audioContent);
            
            // Preparar datos para caché
            $cacheData = [
                'file_path' => $filePath,
                'url' => Storage::disk('public')->url($filePath),
                'size' => strlen($audioContent),
                'voice_id' => $voiceId,
                'settings' => $settings,
                'created_at' => now()->toISOString(),
                'text_hash' => md5($text),
                'text_length' => strlen($text)
            ];
            
            // Guardar en caché
            Cache::put($cacheKey, $cacheData, $this->ttl);
            
            // Registrar en log para métricas
            Log::info('Audio cached successfully', [
                'cache_key' => $cacheKey,
                'file_size' => $cacheData['size'],
                'text_length' => strlen($text)
            ]);
            
            // Programar limpieza si es necesario
            $this->scheduleCleanupIfNeeded();
            
            return $cacheData;
            
        } catch (\Exception $e) {
            Log::error('Error caching audio', [
                'text_length' => strlen($text),
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Generar clave de caché única
     */
    protected function generateCacheKey($text, $voiceId, $settings)
    {
        // Normalizar texto para caché (remover espacios extra, convertir a minúsculas)
        $normalizedText = trim(preg_replace('/\s+/', ' ', strtolower($text)));
        
        // Crear hash único basado en contenido y configuración
        $contentHash = md5($normalizedText);
        $settingsHash = md5(json_encode($settings));
        
        return $this->cachePrefix . md5("{$contentHash}_{$voiceId}_{$settingsHash}");
    }
    
    /**
     * Generar nombre de archivo único
     */
    protected function generateFilename($cacheKey)
    {
        $timestamp = now()->format('Ymd_His');
        $shortKey = substr(md5($cacheKey), 0, 8);
        
        return "audio_{$timestamp}_{$shortKey}.mp3";
    }
    
    /**
     * Limpiar caché antiguo
     */
    public function cleanupOldCache($force = false)
    {
        try {
            Log::info('Starting voice cache cleanup', ['force' => $force]);
            
            // Verificar el driver de caché
            $cacheDriver = config('cache.default');
            $keys = [];
            
            if ($cacheDriver === 'redis') {
                // Para Redis, usar keys con patrón
                $pattern = $this->cachePrefix . '*';
                $keys = Cache::getRedis()->keys($pattern) ?? [];
            } else {
                // Para file driver, usar un enfoque simplificado
                $keys = $this->getFileCacheKeys();
            }
            
            $deletedCount = 0;
            $deletedSize = 0;
            
            foreach ($keys as $key) {
                $keyWithoutPrefix = $cacheDriver === 'redis' 
                    ? str_replace(config('cache.prefix') . ':', '', $key)
                    : $key;
                    
                $cacheData = Cache::get($keyWithoutPrefix);
                
                if ($cacheData && isset($cacheData['created_at'])) {
                    $createdAt = Carbon::parse($cacheData['created_at']);
                    $ageInHours = $createdAt->diffInHours(now());
                    
                    // Eliminar si es muy antiguo o si forzamos limpieza
                    if ($force || $ageInHours > ($this->ttl / 3600)) {
                        // Eliminar archivo físico
                        if (isset($cacheData['file_path']) && Storage::disk('public')->exists($cacheData['file_path'])) {
                            Storage::disk('public')->delete($cacheData['file_path']);
                            $deletedSize += $cacheData['size'] ?? 0;
                        }
                        
                        // Eliminar entrada de caché
                        Cache::forget($keyWithoutPrefix);
                        $deletedCount++;
                    }
                }
            }
            
            Log::info('Voice cache cleanup completed', [
                'deleted_entries' => $deletedCount,
                'deleted_size_mb' => round($deletedSize / (1024 * 1024), 2)
            ]);
            
            return [
                'deleted_entries' => $deletedCount,
                'deleted_size' => $deletedSize
            ];
            
        } catch (\Exception $e) {
            Log::error('Error during cache cleanup', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Obtener estadísticas del caché
     */
    public function getCacheStatistics()
    {
        try {
            $cacheDriver = config('cache.default');
            $keys = [];
            
            if ($cacheDriver === 'redis') {
                $pattern = $this->cachePrefix . '*';
                $keys = Cache::getRedis()->keys($pattern) ?? [];
            } else {
                // Para file driver, usar un enfoque simplificado
                $keys = $this->getFileCacheKeys();
            }
            
            $totalEntries = count($keys);
            $totalSize = 0;
            $oldestEntry = null;
            $newestEntry = null;
            
            foreach ($keys as $key) {
                $keyWithoutPrefix = $cacheDriver === 'redis' 
                    ? str_replace(config('cache.prefix') . ':', '', $key)
                    : $key;
                    
                $cacheData = Cache::get($keyWithoutPrefix);
                
                if ($cacheData) {
                    $totalSize += $cacheData['size'] ?? 0;
                    
                    if (isset($cacheData['created_at'])) {
                        $createdAt = Carbon::parse($cacheData['created_at']);
                        
                        if (!$oldestEntry || $createdAt->isBefore(Carbon::parse($oldestEntry))) {
                            $oldestEntry = $cacheData['created_at'];
                        }
                        
                        if (!$newestEntry || $createdAt->isAfter(Carbon::parse($newestEntry))) {
                            $newestEntry = $cacheData['created_at'];
                        }
                    }
                }
            }
            
            return [
                'enabled' => $this->enabled,
                'total_entries' => $totalEntries,
                'total_size_mb' => round($totalSize / (1024 * 1024), 2),
                'oldest_entry' => $oldestEntry,
                'newest_entry' => $newestEntry,
                'ttl_hours' => $this->ttl / 3600,
                'cleanup_interval_hours' => $this->cleanupInterval / 3600
            ];
            
        } catch (\Exception $e) {
            Log::error('Error getting cache statistics', ['error' => $e->getMessage()]);
            
            return [
                'enabled' => $this->enabled,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Programar limpieza si es necesario
     */
    protected function scheduleCleanupIfNeeded()
    {
        $lastCleanup = Cache::get('voice_cache_last_cleanup', 0);
        $now = now()->timestamp;
        
        if (($now - $lastCleanup) > $this->cleanupInterval) {
            // Marcar que se realizó limpieza
            Cache::put('voice_cache_last_cleanup', $now, $this->ttl);
            
            // Ejecutar limpieza en background (podrías usar Queue aquí)
            $this->cleanupOldCache();
        }
    }
    
    /**
     * Invalidar todo el caché
     */
    public function flush()
    {
        try {
            $pattern = $this->cachePrefix . '*';
            $keys = Cache::getRedis()->keys($pattern) ?? [];
            
            foreach ($keys as $key) {
                $keyWithoutPrefix = str_replace(config('cache.prefix') . ':', '', $key);
                $cacheData = Cache::get($keyWithoutPrefix);
                
                // Eliminar archivo físico
                if ($cacheData && isset($cacheData['file_path'])) {
                    Storage::disk('public')->delete($cacheData['file_path']);
                }
                
                // Eliminar entrada de caché
                Cache::forget($keyWithoutPrefix);
            }
            
            Log::info('Voice cache flushed completely');
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error flushing voice cache', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Verificar si el caché está funcionando
     */
    public function isHealthy()
    {
        try {
            $testKey = $this->cachePrefix . 'health_check';
            $testData = ['test' => 'data', 'timestamp' => now()->toISOString()];
            
            // Probar escritura
            Cache::put($testKey, $testData, 60);
            
            // Probar lectura
            $retrieved = Cache::get($testKey);
            
            // Limpiar prueba
            Cache::forget($testKey);
            
            return $retrieved && $retrieved['test'] === 'data';
            
        } catch (\Exception $e) {
            Log::error('Voice cache health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Obtener claves de caché para driver de archivos
     */
    protected function getFileCacheKeys()
    {
        // Para file driver, retornar array vacío por simplicidad
        // En el futuro se puede implementar búsqueda en directorio de caché
        return [];
    }
}