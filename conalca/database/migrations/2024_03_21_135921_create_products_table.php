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
        Schema::create('products', function (Blueprint $table) {
            $table->integer('producto_codigo')->primary();
            $table->string('producto_codigo_ministerio')->nullable();
            $table->string('producto_nombre');
            $table->string('tippro_nombre')->nullable();
            $table->date('producto_fechacreacion')->nullable();
            $table->string('natcar_nombre')->nullable();
            $table->string('usuario_nombre')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
