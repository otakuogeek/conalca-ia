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
        Schema::create('packings', function (Blueprint $table) {
            $table->integer('Codigo')->primary(); // o ->unique() si no es PK
            $table->integer('Codigo Ministerio')->nullable();
            $table->text('Nombre')->nullable();
            $table->text('Usuario')->nullable();
            $table->text('Fecha Creacion')->nullable(); // idealmente usar timestamp
            $table->text('Fecha Modificacion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packings');
    }
};
