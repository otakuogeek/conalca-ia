<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tara_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('tara_contenedor_20', 10, 2)->default(2300)->comment('Tara en kg para contenedor de 20 pies');
            $table->decimal('tara_contenedor_40', 10, 2)->default(3400)->comment('Tara en kg para contenedor de 40 pies');
            $table->timestamps();
        });

        // Insertar registro por defecto con valores actuales
        DB::table('tara_settings')->insert([
            'tara_contenedor_20' => 2300,
            'tara_contenedor_40' => 3400,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tara_settings');
    }
};
