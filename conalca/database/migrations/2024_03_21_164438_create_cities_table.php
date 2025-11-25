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
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('ciudad_codigo');
            $table->string('ciudad_nombre');
            $table->string('ciudad_codigodane');
            $table->string('municipio_nombre');
            $table->string('departamento_nombre');
            $table->string('pais_nombre');
            $table->string('zonciu_nombre');
            $table->string('estado_nombre');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
