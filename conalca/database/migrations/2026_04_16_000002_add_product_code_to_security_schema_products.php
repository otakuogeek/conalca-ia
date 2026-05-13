<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_schema_products', function (Blueprint $table) {
            $table->integer('product_code')->nullable()->after('name');
            $table->index('product_code');
        });
    }

    public function down(): void
    {
        Schema::table('security_schema_products', function (Blueprint $table) {
            $table->dropIndex(['product_code']);
            $table->dropColumn('product_code');
        });
    }
};
