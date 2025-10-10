<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('document');
            $table->string('name');
            $table->string('openai_thread_id')->nullable();
            $table->string('openai_current_run')->nullable();
            $table->date('created_at_doc')->nullable();
            $table->string('company_name')->nullable();
            $table->string('location')->nullable();
            $table->string('phone_numbers')->nullable();
            $table->string('address')->nullable();
            $table->string('personal_cell')->nullable();
            $table->string('fax')->nullable();
            $table->date('chamber_validity')->nullable();
            $table->string('document_code')->nullable();
            $table->string('branch_office')->nullable();
            $table->string('sales_representative')->nullable();
            $table->boolean('clinton_list_check')->nullable();
            $table->boolean('financial_statements')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
