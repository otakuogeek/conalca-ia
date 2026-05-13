<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_schema_user_price_ranges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('base_range_id'); // referencia al rango del super admin
            $table->bigInteger('price_from')->nullable();
            $table->bigInteger('price_to');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('base_range_id')->references('id')->on('security_schema_price_ranges')->onDelete('cascade');
            $table->unique(['user_id', 'base_range_id']);
        });

        Schema::create('security_schema_user_measures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_price_range_id');
            $table->enum('scope', ['nacional', 'urbano']);
            $table->boolean('gps')->default(false);
            $table->boolean('candado_satelital')->default(false);
            $table->integer('acompanamiento_vehicular')->default(0);
            $table->integer('acompanamiento_motorizado')->default(0);
            $table->timestamps();

            $table->foreign('user_price_range_id')->references('id')->on('security_schema_user_price_ranges')->onDelete('cascade');
            $table->unique(['user_price_range_id', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_schema_user_measures');
        Schema::dropIfExists('security_schema_user_price_ranges');
    }
};
