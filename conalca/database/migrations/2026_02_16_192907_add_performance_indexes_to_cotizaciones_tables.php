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
            $table->index('user_id', 'idx_gc_user_id');
            $table->index('status', 'idx_gc_status');
            $table->index('created_at', 'idx_gc_created_at');
        });

        Schema::table('cotizacion_models', function (Blueprint $table) {
            if (!Schema::hasIndex('cotizacion_models', 'idx_cm_group_cotization_id')) {
                $table->index('group_cotization_id', 'idx_cm_group_cotization_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_cotizations', function (Blueprint $table) {
            $table->dropIndex('idx_gc_user_id');
            $table->dropIndex('idx_gc_status');
            $table->dropIndex('idx_gc_created_at');
        });

        Schema::table('cotizacion_models', function (Blueprint $table) {
            $table->dropIndex('idx_cm_group_cotization_id');
        });
    }
};
