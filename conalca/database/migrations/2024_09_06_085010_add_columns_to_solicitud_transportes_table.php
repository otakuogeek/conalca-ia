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
        Schema::table('solicitud_transportes', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->constrained();
            $table->string('origen')->nullable();
            $table->string('destino')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitud_transportes', function (Blueprint $table) {
            //
        });
    }
};
