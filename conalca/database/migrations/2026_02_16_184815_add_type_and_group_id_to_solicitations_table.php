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
        Schema::table('solicitations', function (Blueprint $table) {
            $table->string('type')->nullable()->default(null)->after('status');
            $table->unsignedBigInteger('group_id')->nullable()->after('type');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitations', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn(['type', 'group_id']);
        });
    }
};
