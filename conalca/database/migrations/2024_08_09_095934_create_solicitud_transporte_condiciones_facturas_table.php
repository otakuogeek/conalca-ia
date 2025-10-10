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
        Schema::create('solicitud_transporte_con', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_transporte_id')->constrained();
            $table->string('condicion_factura')->nullable();
            $table->string('factura_remesa_hija')->nullable();
            $table->string('opcion_factura_remesa')->nullable();
            $table->string('opcion_condicion_cumplida')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_transporte_condiciones_facturas');
    }
};
