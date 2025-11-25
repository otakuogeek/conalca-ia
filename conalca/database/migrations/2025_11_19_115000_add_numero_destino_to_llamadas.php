<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('llamadas', function (Blueprint $table) {
            $table->string('numero_destino')->nullable()->after('conductor_id');
        });
    }

    public function down(): void
    {
        Schema::table('llamadas', function (Blueprint $table) {
            $table->dropColumn('numero_destino');
        });
    }
};
