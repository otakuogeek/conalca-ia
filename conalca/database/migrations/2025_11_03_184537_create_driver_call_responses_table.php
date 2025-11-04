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
        // Verificar si la tabla ya existe antes de crearla
        if (!Schema::hasTable('driver_call_responses')) {
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
        } else {
            // Si la tabla ya existe, verificar que tenga las columnas necesarias
            $this->ensureTableStructure();
        }
    }

    /**
     * Verificar y asegurar que la tabla tenga la estructura correcta
     */
    private function ensureTableStructure(): void
    {
        Schema::table('driver_call_responses', function (Blueprint $table) {
            // Verificar y agregar columnas que puedan faltar
            if (!Schema::hasColumn('driver_call_responses', 'cotizacion_id')) {
                $table->unsignedBigInteger('cotizacion_id');
            }
            if (!Schema::hasColumn('driver_call_responses', 'driver_id')) {
                $table->unsignedBigInteger('driver_id');
            }
            if (!Schema::hasColumn('driver_call_responses', 'driver_name')) {
                $table->string('driver_name');
            }
            if (!Schema::hasColumn('driver_call_responses', 'driver_phone')) {
                $table->string('driver_phone');
            }
            if (!Schema::hasColumn('driver_call_responses', 'vehicle_type')) {
                $table->string('vehicle_type')->nullable();
            }
            if (!Schema::hasColumn('driver_call_responses', 'vehicle_plate')) {
                $table->string('vehicle_plate')->nullable();
            }
            if (!Schema::hasColumn('driver_call_responses', 'call_status')) {
                $table->string('call_status')->nullable();
            }
            if (!Schema::hasColumn('driver_call_responses', 'response_status')) {
                $table->string('response_status')->default('pending');
            }
            if (!Schema::hasColumn('driver_call_responses', 'response_time')) {
                $table->timestamp('response_time')->nullable();
            }
            if (!Schema::hasColumn('driver_call_responses', 'twilio_call_sid')) {
                $table->string('twilio_call_sid')->nullable();
            }
            if (!Schema::hasColumn('driver_call_responses', 'call_duration')) {
                $table->integer('call_duration')->nullable();
            }
            if (!Schema::hasColumn('driver_call_responses', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('driver_call_responses', 'is_selected')) {
                $table->boolean('is_selected')->default(false);
            }
            if (!Schema::hasColumn('driver_call_responses', 'retry_count')) {
                $table->integer('retry_count')->default(0);
            }
            if (!Schema::hasColumn('driver_call_responses', 'original_call_id')) {
                $table->unsignedBigInteger('original_call_id')->nullable();
            }
        });

        // Agregar índices si no existen (Laravel los omite automáticamente si ya existen)
        try {
            Schema::table('driver_call_responses', function (Blueprint $table) {
                $table->index('cotizacion_id');
                $table->index('driver_id');
                $table->index('call_status');
                $table->index('response_status');
                $table->index('is_selected');
            });
        } catch (\Exception $e) {
            // Los índices probablemente ya existen, continuar
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_call_responses');
    }
};
