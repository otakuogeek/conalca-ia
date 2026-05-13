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
        Schema::table('cotizacion_models', function (Blueprint $table) {
            $table->decimal('flete', 15, 2)->nullable()->after('valor')->comment('Flete ofrecido al conductor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizacion_models', function (Blueprint $table) {
            $table->dropColumn('flete');
        });
    }
};
