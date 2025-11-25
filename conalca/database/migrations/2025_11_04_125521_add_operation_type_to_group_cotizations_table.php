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
        Schema::table('group_cotizations', function (Blueprint $table) {
            // Agregar campo operation_type después del campo type
            $table->string('operation_type')->nullable()->after('type')->comment('Tipo de operación: DISTRIBUCION, EXPORTACION, IMPORTACION');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_cotizations', function (Blueprint $table) {
            $table->dropColumn('operation_type');
        });
    }
};
