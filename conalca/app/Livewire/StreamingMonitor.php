<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\StreamingAudioService;
use App\Console\Commands\GenerateStaticAudios;

class StreamingMonitor extends Component
{
    public $refreshInterval = 3;
    public $activeSessions = [];
    public $streamingStats = [];
    public $staticAudioStats = [];
    public $autoRefresh = true;

    protected $streamingAudioService;

    public function boot(StreamingAudioService $streamingAudioService)
    {
        $this->streamingAudioService = $streamingAudioService;
    }

    public function mount()
    {
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.streaming-monitor', [
            'categories' => GenerateStaticAudios::getAvailableCategories()
        ]);
    }

    public function loadData()
    {
        $this->loadActiveSessions();
        $this->loadStreamingStats();
        $this->loadStaticAudioStats();
        
        Log::info('StreamingMonitor: Datos actualizados', [
            'active_sessions_count' => count($this->activeSessions),
            'timestamp' => now()->toISOString()
        ]);
    }

    protected function loadActiveSessions()
    {
        // Obtener sesiones activas del cache
        $cacheKeys = $this->getCacheKeys('streaming_session:*');
        $sessions = [];

        foreach ($cacheKeys as $key) {
            $sessionData = Cache::get($key);
            if ($sessionData) {
                $callSid = str_replace('streaming_session:', '', $key);
                $sessions[$callSid] = array_merge($sessionData, [
                    'call_sid' => $callSid,
                    'duration' => now()->diffInSeconds($sessionData['started_at'] ?? now()),
                    'last_activity' => $sessionData['last_activity'] ?? 'Sin actividad'
                ]);
            }
        }

        $this->activeSessions = $sessions;
    }

    protected function loadStreamingStats()
    {
        $this->streamingStats = $this->streamingAudioService->getStreamingStats();
    }

    protected function loadStaticAudioStats()
    {
        $categories = GenerateStaticAudios::getAvailableCategories();
        $stats = [];

        foreach ($categories as $category => $description) {
            $stats[$category] = [
                'description' => $description,
                'count' => $this->getStaticAudioCountForCategory($category)
            ];
        }

        $this->staticAudioStats = $stats;
    }

    protected function getStaticAudioCountForCategory($category): int
    {
        $categoryMapping = [
            'basic' => 4,
            'sentiment' => 6,
            'urgent' => 3,
            'generic' => 3,
            'greetings' => 4,
            'interactive' => 4,
            'farewells' => 3,
            'special' => 3
        ];

        return $categoryMapping[$category] ?? 0;
    }

    protected function getCacheKeys($pattern): array
    {
        // Simulación de obtener claves del cache que coincidan con el patrón
        // En producción, esto dependería del driver de cache usado
        $allKeys = [];
        
        // Para fines de demo, vamos a simular algunas sesiones
        $sampleSessions = [
            'streaming_session:CA1234567890abcdef',
            'streaming_session:CA0987654321fedcba'
        ];

        foreach ($sampleSessions as $key) {
            if (Cache::has($key)) {
                $allKeys[] = $key;
            }
        }

        return $allKeys;
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
        
        Log::info('StreamingMonitor: Auto-refresh toggled', [
            'enabled' => $this->autoRefresh
        ]);
    }

    public function clearSession($callSid)
    {
        Cache::forget("streaming_session:{$callSid}");
        $this->loadActiveSessions();
        
        Log::info('StreamingMonitor: Sesión limpiada manualmente', [
            'call_sid' => $callSid
        ]);
        
        session()->flash('message', "Sesión {$callSid} eliminada correctamente.");
    }

    public function cleanupTempFiles()
    {
        $cleanedCount = $this->streamingAudioService->cleanupStreamingFiles(30); // 30 minutos
        $this->loadStreamingStats();
        
        Log::info('StreamingMonitor: Limpieza manual ejecutada', [
            'files_cleaned' => $cleanedCount
        ]);
        
        session()->flash('message', "Se limpiaron {$cleanedCount} archivos temporales.");
    }

    public function regenerateStaticAudios()
    {
        try {
            // Ejecutar comando de generación de audios estáticos
            \Artisan::call('conalca:generate-static-audios', ['--force' => true]);
            $output = \Artisan::output();
            
            $this->loadStaticAudioStats();
            
            Log::info('StreamingMonitor: Regeneración de audios estáticos ejecutada');
            
            session()->flash('message', 'Audios estáticos regenerados correctamente.');
            
        } catch (\Exception $e) {
            Log::error('StreamingMonitor: Error regenerando audios estáticos', [
                'error' => $e->getMessage()
            ]);
            
            session()->flash('error', 'Error al regenerar audios estáticos: ' . $e->getMessage());
        }
    }

    public function getStreamingHealth(): array
    {
        $totalSessions = count($this->activeSessions);
        $totalStaticAudios = array_sum(array_column($this->staticAudioStats, 'count'));
        $totalTempFiles = $this->streamingStats['temp_audios']['count'] ?? 0;
        
        return [
            'status' => $totalSessions > 0 ? 'active' : 'idle',
            'health' => $totalStaticAudios > 20 ? 'good' : 'warning',
            'sessions' => $totalSessions,
            'static_audios' => $totalStaticAudios,
            'temp_files' => $totalTempFiles
        ];
    }

    // Método para auto-refresh via polling
    public function refreshData()
    {
        if ($this->autoRefresh) {
            $this->loadData();
        }
    }
}