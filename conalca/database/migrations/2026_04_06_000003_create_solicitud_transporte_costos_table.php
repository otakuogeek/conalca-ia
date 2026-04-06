<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_transporte_costos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_transporte_id')->constrained('solicitud_transportes');
            $table->unsignedInteger('tipvalrem_codigo');
            $table->string('tipvalrem_nombre', 255)->nullable();
            $table->decimal('valor_unitario', 18, 2)->default(0);
            $table->decimal('valor_costo_unitario', 18, 2)->default(0);
            $table->string('facturable', 10)->default('NO');
            $table->text('observacion_costo')->nullable();
            $table->string('aplica_flete', 10)->default('NO');
            $table->unsignedBigInteger('proveedor_codigo')->nullable();
            $table->string('proveedor_nombre', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_transporte_costos');
    }
};
