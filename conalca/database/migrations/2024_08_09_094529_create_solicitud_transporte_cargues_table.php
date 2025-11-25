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
        Schema::create('solicitud_transporte_cargues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_transporte_id')->constrained();
            $table->date('fecha_cargue')->nullable();
            $table->time('hora_cargue')->nullable();
            $table->string('remitente')->nullable();
            $table->string('direccion_cargue')->nullable();
            $table->string('observacion_cargue')->nullable();
            $table->string('contacto')->nullable();
            $table->string('destinario')->nullable();
            $table->string('promesa_servicio')->nullable();
            $table->string('documento_transporte')->nullable();
            $table->string('manifiesto_cliente')->nullable();
            $table->string('remesa_cliente')->nullable();
            $table->string('remision_cliente')->nullable();
            $table->string('codigo_entrega')->nullable();
            $table->string('remitente_codigo')->nullable();
            $table->string('destinatario_codigo')->nullable();
            $table->string('promesa_servicio_hora')->nullable();
            $table->string('promesaservicio_hora')->nullable();
            $table->string('remesion_cliente')->nullable();
            $table->string('codigo_entrega')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_transporte_cargues');
    }
};
