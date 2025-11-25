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
        Schema::create('packing', function (Blueprint $table) {
            $table->integer('Codigo')->primary(); // Clave primaria
            // Para nombres con espacios, Laravel los maneja automáticamente
            $table->integer('Codigo Ministerio')->nullable();
            $table->text('Nombre')->nullable();
            $table->text('Usuario')->nullable();
            $table->text('Fecha Creacion')->nullable();
            $table->text('Fecha Modificacion')->nullable();
            // No incluir timestamps ya que el modelo tiene $timestamps = false
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing');
    }
};
