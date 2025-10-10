<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Llamada;
use App\Http\Controllers\ConversationalAgentController;
use Illuminate\Support\Facades\Log;

class BackfillCallData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calls:backfill-data {--dry-run : Show what would be updated without making changes} {--limit=50 : Limit the number of calls to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Completar información faltante de llamadas existentes en la base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando proceso de backfill para datos de llamadas...');
        
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        if ($dryRun) {
            $this->warn('🔍 Modo DRY-RUN: Solo se mostrarán los cambios, no se aplicarán');
        }

        // Obtener llamadas que necesitan actualización
        $query = Llamada::whereNotNull('elevenlabs_conversation_id')
            ->where(function ($query) {
                $query->whereNull('call_status')
                    ->orWhereNull('call_initiated_at')
                    ->orWhereNull('call_direction')
                    ->orWhereNull('call_type');
            });

        if ($limit > 0) {
            $query->limit($limit);
        }

        $llamadas = $query->get();

        if ($llamadas->isEmpty()) {
            $this->info('✅ No se encontraron llamadas que necesiten actualización.');
            return 0;
        }

        $this->info("📊 Encontradas {$llamadas->count()} llamadas que necesitan actualización");

        $progressBar = $this->output->createProgressBar($llamadas->count());
        $progressBar->setFormat('verbose');

        $updated = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($llamadas as $llamada) {
            $progressBar->advance();

            try {
                $changes = $this->analyzeCallChanges($llamada);
                
                if (empty($changes)) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->showChanges($llamada, $changes);
                    $updated++;
                } else {
                    if ($this->updateCall($llamada, $changes)) {
                        $updated++;
                    } else {
                        $errors++;
                    }
                }

            } catch (\Exception $e) {
                $errors++;
                $this->error("Error procesando llamada {$llamada->id_llamada}: " . $e->getMessage());
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Mostrar resumen
        $this->info('📈 Resumen del proceso:');
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Total procesadas', $llamadas->count()],
                ['Actualizadas', $updated],
                ['Errores', $errors],
                ['Sin cambios', $skipped],
            ]
        );

        if ($dryRun && $updated > 0) {
            $this->info('💡 Para aplicar los cambios, ejecute el comando sin --dry-run');
        }

        return 0;
    }

    private function analyzeCallChanges(Llamada $llamada)
    {
        $changes = [];

        // Completar información básica faltante
        if (!$llamada->call_initiated_at && $llamada->created_at) {
            $changes['call_initiated_at'] = $llamada->created_at;
        }

        if (!$llamada->call_direction) {
            $changes['call_direction'] = Llamada::DIRECTION_OUTBOUND;
        }

        if (!$llamada->call_type) {
            $changes['call_type'] = Llamada::TYPE_AGENT;
        }

        // Determinar estado basándose en información disponible
        if (!$llamada->call_status) {
            $status = $this->determineCallStatus($llamada);
            if ($status) {
                $changes['call_status'] = $status;
            }
        }

        // Actualizar metadata si no existe
        if (!$llamada->call_metadata) {
            $changes['call_metadata'] = [
                'backfill_update' => now()->toISOString(),
                'conversation_id' => $llamada->elevenlabs_conversation_id,
                'sip_call_id' => $llamada->elevenlabs_sip_call_id,
                'original_status' => $llamada->status,
                'backfill_source' => 'artisan_command'
            ];
        }

        // Agregar notas internas si no existen
        if (!$llamada->internal_notes && $llamada->call_notes) {
            $changes['internal_notes'] = 'Backfilled from call_notes: ' . $llamada->call_notes;
        }

        return $changes;
    }

    private function determineCallStatus(Llamada $llamada)
    {
        // Si hay notas de llamada, intentar inferir el estado
        if ($llamada->call_notes) {
            $notes = strtolower($llamada->call_notes);
            
            if (str_contains($notes, '486') || str_contains($notes, 'busy')) {
                return Llamada::CALL_STATUS_BUSY;
            }
            
            if (str_contains($notes, '503') || str_contains($notes, 'service unavailable')) {
                return Llamada::CALL_STATUS_FAILED;
            }
            
            if (str_contains($notes, '408') || str_contains($notes, 'timeout')) {
                return Llamada::CALL_STATUS_NO_ANSWER;
            }
            
            if (str_contains($notes, 'initiated')) {
                return Llamada::CALL_STATUS_INITIATED;
            }
        }

        // Basarse en el estado actual del sistema
        switch ($llamada->status) {
            case Llamada::STATUS_EN_CURSO:
                return Llamada::CALL_STATUS_INITIATED;
            case Llamada::STATUS_FINALIZADA:
                return Llamada::CALL_STATUS_COMPLETED;
            case Llamada::STATUS_ACEPTADA:
                return Llamada::CALL_STATUS_COMPLETED;
            case Llamada::STATUS_RECHAZADA:
                return Llamada::CALL_STATUS_FAILED;
            default:
                return Llamada::CALL_STATUS_INITIATED;
        }
    }

    private function showChanges(Llamada $llamada, array $changes)
    {
        $this->newLine();
        $this->info("🔍 Llamada ID: {$llamada->id_llamada} (Conversation: {$llamada->elevenlabs_conversation_id})");
        
        $table = [];
        foreach ($changes as $field => $newValue) {
            $oldValue = $llamada->{$field} ?? 'NULL';
            if (is_array($newValue) || is_object($newValue)) {
                $newValue = json_encode($newValue, JSON_PRETTY_PRINT);
            }
            if (is_array($oldValue) || is_object($oldValue)) {
                $oldValue = json_encode($oldValue, JSON_PRETTY_PRINT);
            }
            
            $table[] = [$field, $oldValue, $newValue];
        }
        
        $this->table(['Campo', 'Valor Actual', 'Nuevo Valor'], $table);
    }

    private function updateCall(Llamada $llamada, array $changes)
    {
        try {
            $llamada->update($changes);
            return true;
        } catch (\Exception $e) {
            Log::error("Error actualizando llamada {$llamada->id_llamada}: " . $e->getMessage());
            return false;
        }
    }
}
