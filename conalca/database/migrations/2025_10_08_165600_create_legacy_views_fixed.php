<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear vista tb_produto que apunta a la tabla products
        DB::statement('
            CREATE OR REPLACE VIEW tb_produto AS
            SELECT 
                producto_codigo as id,
                producto_codigo as codigo,
                producto_nombre as nome,
                producto_codigo_ministerio as codigo_ministerio,
                tippro_nombre as tipo_produto,
                natcar_nombre as natureza_carga,
                usuario_nombre as usuario,
                producto_fechacreacion as data_criacao
            FROM products
        ');

        // Crear vista tb_empaque que apunta a la tabla packing
        DB::statement('
            CREATE OR REPLACE VIEW tb_empaque AS
            SELECT 
                Codigo as id,
                `Codigo Ministerio` as codigo_ministerio,
                Nombre as nome,
                Usuario as usuario,
                `Fecha Creacion` as data_criacao,
                `Fecha Modificacion` as data_modificacao
            FROM packing
        ');

        // Crear vista tb_cotizacion que apunta a la tabla cotizacion_models
        DB::statement('
            CREATE OR REPLACE VIEW tb_cotizacion AS
            SELECT 
                id,
                pricing_id,
                ciudad_origen,
                ciudad_destino,
                peso_mercancia,
                cantidad,
                tipo_embajale,
                tipo_producto,
                vehiculo_requerido,
                valor,
                valor_declarado,
                tipo_mercancia,
                client_id,
                created_at,
                updated_at
            FROM cotizacion_models
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS tb_produto');
        DB::statement('DROP VIEW IF EXISTS tb_empaque');
        DB::statement('DROP VIEW IF EXISTS tb_cotizacion');
    }
};