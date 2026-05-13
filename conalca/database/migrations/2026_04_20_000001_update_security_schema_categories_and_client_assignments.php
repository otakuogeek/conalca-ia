<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Cambiar el enum de category para soportar las nuevas categorías
        // MySQL requiere ALTER COLUMN para cambiar enum values
        DB::statement("ALTER TABLE security_schema_price_ranges MODIFY COLUMN category ENUM('alto_riesgo','bajo_riesgo','alto_riesgo_nivel_1','alto_riesgo_nivel_2','bajo_riesgo_quimicos') NOT NULL DEFAULT 'alto_riesgo'");

        // Migrar datos existentes a las nuevas categorías
        DB::table('security_schema_price_ranges')
            ->where('category', 'alto_riesgo')
            ->update(['category' => 'alto_riesgo_nivel_1']);

        // 2. Crear tabla de asignación de esquema de seguridad a clientes
        Schema::create('security_schema_client_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('assigned_by')->nullable(); // user_id del super admin
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
            $table->unique('client_id'); // Un cliente solo puede tener una asignación
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_schema_client_assignments');

        // Revertir categorías
        DB::table('security_schema_price_ranges')
            ->where('category', 'alto_riesgo_nivel_1')
            ->update(['category' => 'alto_riesgo']);

        DB::statement("ALTER TABLE security_schema_price_ranges MODIFY COLUMN category ENUM('alto_riesgo','bajo_riesgo') NOT NULL DEFAULT 'alto_riesgo'");
    }
};
