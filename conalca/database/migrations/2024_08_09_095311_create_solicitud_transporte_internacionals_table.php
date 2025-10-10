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
        Schema::create('solicitud_transporte_int', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_transporte_id')->constrained();
            $table->string('modalidad_internacional')->nullable();
            $table->string('tipo_viaje_int')->nullable();
            $table->string('numero_documento_int')->nullable();
            $table->string('fecha_llegada_int')->nullable();
            $table->string('aduana_int')->nullable();
            $table->string('nombre_cliente')->nullable();
            $table->string('nombre_exportador')->nullable();
            $table->string('datos_agente_aduana')->nullable();
            $table->string('datos_bodega_ingresa')->nullable();
            $table->string('ciudad_int')->nullable();
            $table->string('tipo_operacion_int')->nullable();
            $table->string('quien_paga_almacenamiento')->nullable();
            $table->string('ciudad_otra')->nullable();
            $table->string('tipo_otro')->nullable();
            $table->string('nombre_importador')->nullable();
            $table->string('descripcion_mercancia')->nullable();
            $table->string('cantidad_peso_mercancia')->nullable();
            $table->string('fecha_vencimiento_modalidad')->nullable();
            $table->string('paso_frontera')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_transporte_internacionals');
    }
};
