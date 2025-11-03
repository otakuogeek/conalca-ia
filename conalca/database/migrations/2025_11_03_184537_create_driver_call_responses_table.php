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
        Schema::create('driver_call_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id');
            $table->unsignedBigInteger('driver_id');
            $table->string('driver_name');
            $table->string('driver_phone');
            $table->string('vehicle_type')->nullable();
            $table->string('vehicle_plate')->nullable();
            $table->string('call_status')->nullable(); // 'initiated', 'answered', 'no_answer', 'busy', 'failed'
            $table->string('response_status')->default('pending'); // 'pending', 'accepted', 'rejected'
            $table->timestamp('response_time')->nullable();
            $table->string('twilio_call_sid')->nullable(); // Para compatibilidad
            $table->integer('call_duration')->nullable(); // Duración en segundos
            $table->text('notes')->nullable();
            $table->boolean('is_selected')->default(false);
            $table->integer('retry_count')->default(0);
            $table->unsignedBigInteger('original_call_id')->nullable();
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index('cotizacion_id');
            $table->index('driver_id');
            $table->index('call_status');
            $table->index('response_status');
            $table->index('is_selected');
            
            // Claves foráneas
            $table->foreign('cotizacion_id')->references('id')->on('cotizacion_models')->onDelete('cascade');
            $table->foreign('driver_id')->references('id')->on('vehicle_owner_holder_driver')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_call_responses');
    }
};
