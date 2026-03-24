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
            $table->string('load_type')->nullable()->after('cargo_type')->comment('Tipo de carga: carga_suelta o contenerizada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_cotizations', function (Blueprint $table) {
            $table->dropColumn('load_type');
        });
    }
};
