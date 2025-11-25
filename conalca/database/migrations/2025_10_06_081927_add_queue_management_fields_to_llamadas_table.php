<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('llamadas', function (Blueprint $table) {
            // Estado de la cola
            $table->enum('queue_status', ['pending', 'queued', 'processing', 'completed', 'failed', 'cancelled'])
                  ->default('pending')
                  ->after('status')
                  ->comment('Estado en la cola de llamadas');
            
            // Lote de llamadas (grupo de 3 llamadas)
            $table->integer('batch_number')->nullable()->after('queue_status')
                  ->comment('Número de lote (grupo de 3 llamadas)');
            
            // Posición dentro del lote (1, 2 o 3)
            $table->tinyInteger('batch_position')->nullable()->after('batch_number')
                  ->comment('Posición dentro del lote (1-3)');
            
            // Prioridad en la cola (menor número = mayor prioridad)
            $table->integer('queue_priority')->default(10)->after('batch_position')
                  ->comment('Prioridad en la cola (1-10, menor = mayor prioridad)');
            
            // Timestamp cuando se agregó a la cola
            $table->timestamp('queued_at')->nullable()->after('queue_priority')
                  ->comment('Momento en que se agregó a la cola');
            
            // Timestamp cuando se comenzó a procesar
            $table->timestamp('processing_started_at')->nullable()->after('queued_at')
                  ->comment('Momento en que comenzó el procesamiento');
            
            // Timestamp cuando se completó el procesamiento
            $table->timestamp('processing_completed_at')->nullable()->after('processing_started_at')
                  ->comment('Momento en que terminó el procesamiento');
            
            // Tiempo estimado de espera en segundos
            $table->integer('estimated_wait_seconds')->nullable()->after('processing_completed_at')
                  ->comment('Tiempo estimado de espera antes de iniciar');
            
            // Intentos de procesamiento
            $table->integer('processing_attempts')->default(0)->after('estimated_wait_seconds')
                  ->comment('Número de intentos de procesamiento');
            
            // Timestamp del último intento
            $table->timestamp('last_attempt_at')->nullable()->after('processing_attempts')
                  ->comment('Momento del último intento de procesamiento');
            
            // Índices para optimizar consultas de cola
            $table->index(['queue_status', 'queue_priority', 'queued_at'], 'idx_queue_processing');
            $table->index(['batch_number', 'batch_position'], 'idx_batch_management');
            $table->index('processing_started_at', 'idx_processing_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('llamadas', function (Blueprint $table) {
            // Eliminar índices
            $table->dropIndex('idx_queue_processing');
            $table->dropIndex('idx_batch_management');
            $table->dropIndex('idx_processing_time');
            
            // Eliminar columnas
            $table->dropColumn([
                'queue_status',
                'batch_number',
                'batch_position',
                'queue_priority',
                'queued_at',
                'processing_started_at',
                'processing_completed_at',
                'estimated_wait_seconds',
                'processing_attempts',
                'last_attempt_at'
            ]);
        });
    }
};
