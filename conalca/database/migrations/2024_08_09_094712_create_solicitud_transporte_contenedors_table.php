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
        Schema::create('solicitud_transporte_contenedors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_transporte_id')->constrained();
            $table->string('tipo_carga_cont')->nullable();
            $table->string('tamano_contenedor')->nullable();
            $table->string('sitio_entrega_cont')->nullable();
            $table->string('cantidad_cont')->nullable();
            $table->string('peso_contenedor')->nullable();
            $table->string('tipo_contenedor')->nullable();
            $table->date('fecha_entrega_cont')->nullable();
            $table->string('contenedor')->nullable();
            $table->string('numero_cont')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_transporte_contenedors');
    }
};
