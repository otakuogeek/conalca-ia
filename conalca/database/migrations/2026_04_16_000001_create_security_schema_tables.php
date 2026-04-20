<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Productos clasificados como alto riesgo (por usuario)
        Schema::create('security_schema_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
        });

        // Rangos de precio por categoría y usuario
        Schema::create('security_schema_price_ranges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('category', ['alto_riesgo', 'bajo_riesgo']);
            $table->bigInteger('price_from')->nullable(); // null = "HASTA"
            $table->bigInteger('price_to');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'category']);
        });

        // Medidas de seguridad por rango y alcance (nacional/urbano)
        Schema::create('security_schema_measures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('price_range_id');
            $table->enum('scope', ['nacional', 'urbano']);
            $table->boolean('gps')->default(false);
            $table->boolean('candado_satelital')->default(false);
            $table->unsignedInteger('acompanamiento_vehicular')->default(0);
            $table->unsignedInteger('acompanamiento_motorizado')->default(0);
            $table->timestamps();

            $table->foreign('price_range_id')->references('id')->on('security_schema_price_ranges')->onDelete('cascade');
            $table->unique(['price_range_id', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_schema_measures');
        Schema::dropIfExists('security_schema_price_ranges');
        Schema::dropIfExists('security_schema_products');
    }
};
