<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('llamadas_conductores', function (Blueprint $table) {
            $table->string('capacidad')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('llamadas_conductores', function (Blueprint $table) {
            $table->integer('capacidad')->nullable()->change();
        });
    }
};
