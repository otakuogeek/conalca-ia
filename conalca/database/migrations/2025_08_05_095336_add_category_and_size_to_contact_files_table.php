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
        Schema::table('contact_files', function (Blueprint $table) {
            $table->string('category')->default('general')->after('file_path');
            $table->bigInteger('size')->nullable()->after('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_files', function (Blueprint $table) {
            $table->dropColumn(['category', 'size']);
        });
    }
};
