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
        Schema::create('solicitud_transporte_entregas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_transporte_id')->constrained();
            $table->string('nombre_cliente_ent')->nullable();
            $table->string('direccion_entrega_ent')->nullable();
            $table->string('numero_documento_ent')->nullable();
            $table->string('cantidad_ent')->nullable();
            $table->string('empaque_ent')->nullable();
            $table->string('f_12_ent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_transporte_entregas');
    }
};
