<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ElevenLabsService;
use App\Services\LocalAudioStorage;
use Illuminate\Support\Facades\Log;

class GenerateStaticAudios extends Command
{
    protected $signature = 'audio:generate-static {--force : Forzar regeneración de audios existentes}';
    protected $description = 'Genera audios estáticos pregenerados para respuestas comunes del sistema';

    private $elevenLabsService;
    private $audioStorage;

    public function __construct(ElevenLabsService $elevenLabsService, LocalAudioStorage $audioStorage)
    {
        parent::__construct();
        $this->elevenLabsService = $elevenLabsService;
        $this->audioStorage = $audioStorage;
    }

    public function handle()
    {
        $this->info('🎵 Generando audios estáticos con voz Andrea...');

        // Definir audios estáticos a generar
        $staticAudios = [
            'no_response' => [
                'text' => 'Parece que no lo escuchamos. Que tenga un excelente día.',
                'filename' => 'static_no_response.mp3',
                'context' => 'farewell'
            ],
            'accept_response' => [
                'text' => 'Perfecto en unos minutos mi compañera encargada de asignar conductor a las solicitudes de transporte se pondrá en contacto con usted para confirmar valores y asignar ruta muchas gracias',
                'filename' => 'static_accept_response.mp3',
                'context' => 'conversation'
            ],
            'reject_response' => [
                'text' => 'Muchas gracias por su tiempo será ya en otra ocasión que tenga un buen día',
                'filename' => 'static_reject_response.mp3',
                'context' => 'farewell'
            ],
            'clarify_response' => [
                'text' => 'Disculpe no lo escuché bien. ¿Estaría interesado en esta propuesta de transporte?',
                'filename' => 'static_clarify_response.mp3',
                'context' => 'conversation'
            ]
        ];

        $force = $this->option('force');
        $generated = 0;
        $skipped = 0;

        foreach ($staticAudios as $key => $audioData) {
            $this->info("📢 Procesando: {$key}");
            
            try {
                // Verificar si el archivo ya existe
                $filePath = storage_path('app/public/audios/static/' . $audioData['filename']);
                
                if (!$force && file_exists($filePath)) {
                    $this->warn("   ⏭️  Audio ya existe: {$audioData['filename']}");
                    $skipped++;
                    continue;
                }

                // Crear directorio si no existe
                $staticDir = storage_path('app/public/audios/static');
                if (!is_dir($staticDir)) {
                    mkdir($staticDir, 0755, true);
                    $this->info("   📁 Directorio creado: audios/static/");
                }

                // Generar audio con ElevenLabs
                $this->line("   🔊 Generando audio con Andrea...");
                
                $audioContent = $this->elevenLabsService->generateAudioContent(
                    $audioData['text'],
                    null, // usar voice_id por defecto (Andrea)
                    $audioData['context']
                );

                if (!$audioContent) {
                    $this->error("   ❌ Error generando audio para: {$key}");
                    continue;
                }

                // Guardar archivo
                file_put_contents($filePath, $audioContent);
                
                // Verificar tamaño del archivo
                $fileSize = filesize($filePath);
                
                $this->info("   ✅ Audio generado: {$audioData['filename']} ({$fileSize} bytes)");
                
                Log::info("Audio estático generado", [
                    'key' => $key,
                    'filename' => $audioData['filename'],
                    'size' => $fileSize,
                    'text_length' => strlen($audioData['text']),
                    'context' => $audioData['context']
                ]);

                $generated++;

            } catch (\Exception $e) {
                $this->error("   ❌ Error procesando {$key}: " . $e->getMessage());
                Log::error("Error generando audio estático", [
                    'key' => $key,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info('');
        $this->info("📊 Resumen:");
        $this->info("   ✅ Generados: {$generated}");
        $this->info("   ⏭️  Omitidos: {$skipped}");
        $this->info('');
        
        if ($generated > 0) {
            $this->info('🎉 Audios estáticos listos para usar en:');
            $this->info('   📂 storage/app/public/audios/static/');
            $this->info('   🌐 URL base: /storage/audios/static/');
        }

        return 0;
    }

    /**
     * Obtener URL pública del audio estático
     */
    public static function getStaticAudioUrl(string $key): ?string
    {
        $staticAudios = [
            'no_response' => 'static_no_response.mp3',
            'accept_response' => 'static_accept_response.mp3',
            'reject_response' => 'static_reject_response.mp3',
            'clarify_response' => 'static_clarify_response.mp3'
        ];

        if (!isset($staticAudios[$key])) {
            return null;
        }

        $filename = $staticAudios[$key];
        $filePath = storage_path('app/public/audios/static/' . $filename);
        
        if (!file_exists($filePath)) {
            return null;
        }

        return asset('storage/audios/static/' . $filename);
    }
}