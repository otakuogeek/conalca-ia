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
        Schema::create('llamadas_conductores', function (Blueprint $table) {
            $table->id();
            $table->string('identificador_unico', 100)->unique()->comment('Identificador único para la llamada');
            $table->unsignedBigInteger('cotizacion_id')->nullable()->comment('ID de la cotización');
            $table->unsignedBigInteger('group_cotization_id')->nullable()->comment('ID del grupo de cotización');
            
            // Datos del conductor
            $table->string('nombre_conductor')->comment('Nombre completo del conductor');
            $table->string('telefono')->comment('Teléfono del conductor');
            $table->string('placa')->nullable()->comment('Placa del vehículo');
            $table->string('tipo_vehiculo')->nullable()->comment('Tipo de vehículo (Arcangel)');
            $table->string('vehiculo_silogtran')->nullable()->comment('Tipo de vehículo (Silogtran)');
            $table->decimal('peso_maximo', 10, 2)->nullable()->comment('Peso máximo del vehículo en kg');
            
            // Ubicación y disponibilidad
            $table->string('ciudad_actual')->nullable()->comment('Ciudad actual del conductor');
            $table->string('ciudad_origen')->nullable()->comment('Ciudad de origen de la cotización');
            $table->string('ciudad_destino')->nullable()->comment('Ciudad de destino de la cotización');
            $table->boolean('disponible')->default(true)->comment('Estado de disponibilidad');
            $table->decimal('score', 3, 1)->nullable()->comment('Score de coincidencia');
            
            // Estado de la llamada
            $table->enum('estado_llamada', ['pendiente', 'en_progreso', 'completada', 'fallida', 'cancelada'])
                  ->default('pendiente')
                  ->comment('Estado de la llamada');
            $table->string('call_id')->nullable()->comment('ID de la llamada en ElevenLabs');
            $table->timestamp('fecha_llamada')->nullable()->comment('Fecha de la llamada');
            $table->text('respuesta_llamada')->nullable()->comment('Respuesta del conductor');
            $table->text('notas')->nullable()->comment('Notas adicionales');
            
            // Datos de cotización para contexto
            $table->string('mercancia')->nullable()->comment('Tipo de mercancía');
            $table->decimal('peso_carga', 10, 2)->nullable()->comment('Peso de la carga en kg');
            $table->string('empaque')->nullable()->comment('Tipo de empaque');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('cotizacion_id');
            $table->index('group_cotization_id');
            $table->index('estado_llamada');
            $table->index('telefono');
            $table->index('ciudad_actual');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('llamadas_conductores');
    }
};
