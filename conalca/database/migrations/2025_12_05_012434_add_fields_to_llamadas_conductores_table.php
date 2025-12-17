<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('llamadas_conductores', function (Blueprint $table) {
            // Agregar identificador único si no existe
            if (!Schema::hasColumn('llamadas_conductores', 'identificador_unico')) {
                $table->string('identificador_unico', 100)->unique()->after('id')->comment('Identificador único para la llamada');
            }
            
            // Agregar referencias a cotizaciones
            if (!Schema::hasColumn('llamadas_conductores', 'cotizacion_id')) {
                $table->unsignedBigInteger('cotizacion_id')->nullable()->after('identificador_unico')->comment('ID de la cotización');
                $table->index('cotizacion_id');
            }
            
            if (!Schema::hasColumn('llamadas_conductores', 'group_cotization_id')) {
                $table->unsignedBigInteger('group_cotization_id')->nullable()->after('cotizacion_id')->comment('ID del grupo de cotización');
                $table->index('group_cotization_id');
            }
            
            // Agregar campos de vehículo
            if (!Schema::hasColumn('llamadas_conductores', 'tipo_vehiculo')) {
                $table->string('tipo_vehiculo')->nullable()->after('placa')->comment('Tipo de vehículo (Arcangel)');
            }
            
            if (!Schema::hasColumn('llamadas_conductores', 'vehiculo_silogtran')) {
                $table->string('vehiculo_silogtran')->nullable()->after('tipo_vehiculo')->comment('Tipo de vehículo (Silogtran)');
            }
            
            if (!Schema::hasColumn('llamadas_conductores', 'peso_maximo')) {
                $table->decimal('peso_maximo', 10, 2)->nullable()->after('vehiculo_silogtran')->comment('Peso máximo del vehículo en kg');
            }
            
            // Agregar ciudades
            if (!Schema::hasColumn('llamadas_conductores', 'ciudad_origen')) {
                $table->string('ciudad_origen')->nullable()->after('ciudad')->comment('Ciudad de origen de la cotización');
            }
            
            if (!Schema::hasColumn('llamadas_conductores', 'ciudad_destino')) {
                $table->string('ciudad_destino')->nullable()->after('ciudad_origen')->comment('Ciudad de destino de la cotización');
            }
            
            // Renombrar ciudad a ciudad_actual si es necesario
            if (Schema::hasColumn('llamadas_conductores', 'ciudad') && !Schema::hasColumn('llamadas_conductores', 'ciudad_actual')) {
                $table->renameColumn('ciudad', 'ciudad_actual');
            }
            
            // Agregar estado de llamada
            if (!Schema::hasColumn('llamadas_conductores', 'estado_llamada')) {
                $table->enum('estado_llamada', ['pendiente', 'en_progreso', 'completada', 'fallida', 'cancelada'])
                      ->default('pendiente')
                      ->after('disponible')
                      ->comment('Estado de la llamada');
                $table->index('estado_llamada');
            }
            
            if (!Schema::hasColumn('llamadas_conductores', 'call_id')) {
                $table->string('call_id')->nullable()->after('estado_llamada')->comment('ID de la llamada en ElevenLabs');
            }
            
            if (!Schema::hasColumn('llamadas_conductores', 'fecha_llamada')) {
                $table->timestamp('fecha_llamada')->nullable()->after('call_id')->comment('Fecha de la llamada');
            }
            
            if (!Schema::hasColumn('llamadas_conductores', 'respuesta_llamada')) {
                $table->text('respuesta_llamada')->nullable()->after('fecha_llamada')->comment('Respuesta del conductor');
            }
            
            if (!Schema::hasColumn('llamadas_conductores', 'notas')) {
                $table->text('notas')->nullable()->after('respuesta_llamada')->comment('Notas adicionales');
            }
            
            // Agregar datos de cotización
            if (!Schema::hasColumn('llamadas_conductores', 'mercancia')) {
                $table->string('mercancia')->nullable()->after('notas')->comment('Tipo de mercancía');
            }
            
            if (!Schema::hasColumn('llamadas_conductores', 'peso_carga')) {
                $table->decimal('peso_carga', 10, 2)->nullable()->after('mercancia')->comment('Peso de la carga en kg');
            }
            
            if (!Schema::hasColumn('llamadas_conductores', 'empaque')) {
                $table->string('empaque')->nullable()->after('peso_carga')->comment('Tipo de empaque');
            }
            
            // Agregar soft deletes
            if (!Schema::hasColumn('llamadas_conductores', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('llamadas_conductores', function (Blueprint $table) {
            $columns = [
                'identificador_unico', 'cotizacion_id', 'group_cotization_id',
                'tipo_vehiculo', 'vehiculo_silogtran', 'peso_maximo',
                'ciudad_origen', 'ciudad_destino', 'estado_llamada',
                'call_id', 'fecha_llamada', 'respuesta_llamada', 'notas',
                'mercancia', 'peso_carga', 'empaque', 'deleted_at'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('llamadas_conductores', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
