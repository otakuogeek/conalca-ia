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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            
            // Información básica de la llamada
            $table->string('call_sid')->nullable()->unique();
            $table->string('caller_number', 20)->nullable();
            $table->string('caller_city')->nullable();
            $table->string('caller_country', 10)->nullable();
            $table->string('direction', 20)->default('inbound'); // inbound, outbound
            
            // Estado de la conversación
            $table->enum('status', ['active', 'completed', 'transferred', 'failed', 'abandoned'])
                  ->default('active');
            
            // Contenido de la conversación
            $table->json('context')->nullable(); // Historial completo de mensajes
            $table->text('last_user_input')->nullable();
            $table->text('last_bot_response')->nullable();
            $table->text('summary')->nullable(); // Resumen generado por IA
            
            // Análisis de la conversación
            $table->string('topic')->nullable(); // cotización, seguimiento, queja, etc.
            $table->float('sentiment_score')->nullable(); // -1 a 1
            $table->enum('sentiment_label', ['very_negative', 'negative', 'neutral', 'positive', 'very_positive'])
                  ->nullable();
            $table->integer('turn_count')->default(0); // Número de intercambios
            $table->integer('duration_seconds')->nullable(); // Duración en segundos
            
            // Métricas de calidad
            $table->float('comprehension_score')->nullable(); // Qué tan bien entendió la IA
            $table->boolean('goal_achieved')->nullable(); // Si se logró el objetivo
            $table->string('completion_reason')->nullable(); // transferred, resolved, abandoned
            
            // Metadatos técnicos
            $table->json('metadata')->nullable(); // Información adicional
            $table->string('voice_id')->nullable(); // ID de voz utilizada
            $table->integer('audio_generation_time_ms')->nullable(); // Tiempo de generación
            $table->integer('total_audio_size_bytes')->nullable(); // Tamaño total del audio
            
            // Timestamps
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index('call_sid');
            $table->index('status');
            $table->index('topic');
            $table->index('caller_number');
            $table->index('created_at');
            $table->index('completed_at');
            $table->index(['status', 'created_at']);
            $table->index(['topic', 'sentiment_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};