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
            $table->string('openai_thread_id')->nullable()->after('status');
            $table->boolean('created_from_chat')->default(false)->after('openai_thread_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_cotizations', function (Blueprint $table) {
            $table->dropColumn(['openai_thread_id', 'created_from_chat']);
        });
    }
};
