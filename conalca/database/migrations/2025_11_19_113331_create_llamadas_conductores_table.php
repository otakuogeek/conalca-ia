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
            
            // Información del conductor
            $table->string('nombre_conductor')->nullable();
            $table->string('telefono')->index();
            $table->string('placa')->nullable()->index();
            $table->string('clase_vehiculo')->nullable();
            $table->decimal('score', 3, 1)->default(0);
            $table->string('carroceria')->nullable();
            $table->integer('capacidad')->nullable();
            
            // Origen de datos
            $table->enum('fuente', ['arcangel', 'local'])->default('arcangel');
            $table->unsignedBigInteger('chofer_id_local')->nullable()->comment('ID de vehicle_owner_holder_driver si es local');
            $table->string('arcangel_id')->nullable()->comment('ID o identificador de Arcangel');
            
            // Estado y ubicación
            $table->boolean('disponible')->default(true);
            $table->string('ciudad')->nullable();
            $table->timestamp('ultima_actualizacion')->nullable();
            
            // Metadatos
            $table->json('datos_adicionales')->nullable()->comment('Información extra de Arcangel o local');
            
            $table->timestamps();
            
            // Índices
            $table->index(['telefono', 'disponible']);
            $table->index(['ciudad', 'clase_vehiculo']);
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
