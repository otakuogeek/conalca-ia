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
        Schema::create('conversation_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('call_sid')->unique();
            $table->unsignedBigInteger('cotizacion_id')->nullable(); // Nullable para llamadas directas
            $table->unsignedBigInteger('driver_id')->nullable(); // Nullable para llamadas directas
            $table->string('driver_phone')->nullable(); // Para llamadas directas
            $table->enum('conversation_type', ['cotization_call', 'direct_call'])->default('cotization_call');
            $table->enum('status', ['active', 'completed', 'failed', 'timeout'])->default('active');
            $table->integer('turn_count')->default(0);
            $table->string('final_decision')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['call_sid']);
            $table->index(['cotizacion_id', 'driver_id']);
            $table->index(['status', 'created_at']);
            $table->index(['conversation_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_sessions');
    }
};
