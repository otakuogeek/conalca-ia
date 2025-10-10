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
        Schema::create('sellers', function (Blueprint $table) {
            $table->integer('Codigo')->unique();
            $table->string('Nombre');
            $table->string('Ciudad');
            $table->string('email')->nullable();
            $table->integer('Documento');
            $table->string('Ciudad_Cobro');
            $table->string('Usuario');
            $table->timestamp('Fecha_Creacion')->nullable();
            $table->timestamp('Fecha_Modificacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sellers');
    }
};
