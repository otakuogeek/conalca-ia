<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ZinformacionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zinformacion {orden_id? : ID de la orden a consultar} {--search= : Buscar por texto en ciudades o productos} {--all : Mostrar todas las órdenes} {--stats : Mostrar estadísticas de completitud} {--limit=10 : Límite de resultados}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consultar información operativa completa de órdenes con campos estáticos de la tabla cotizacion_models';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 HERRAMIENTA ZINFORMACION - Consulta de Órdenes');
        $this->line('=' . str_repeat('=', 60));

        $ordenId = $this->argument('orden_id');
        $search = $this->option('search');
        $all = $this->option('all');
        $stats = $this->option('stats');
        $limit = $this->option('limit');

        if ($ordenId) {
            $this->mostrarOrdenDetallada($ordenId);
        } elseif ($stats) {
            $this->mostrarEstadisticas();
        } elseif ($search || $all) {
            $this->buscarOrdenes($search, $limit);
        } else {
            $this->mostrarAyuda();
        }

        return 0;
    }

    private function mostrarOrdenDetallada($ordenId)
    {
        $orden = DB::table('cotizacion_models')
            ->leftJoin('group_cotizations', 'cotizacion_models.group_cotization_id', '=', 'group_cotizations.id')
            ->select([
                'cotizacion_models.id',
                'cotizacion_models.ciudad_origen',
                'cotizacion_models.ciudad_destino', 
                'cotizacion_models.ciudad_origen_dane',
                'cotizacion_models.ciudad_destino_dane',
                'cotizacion_models.peso_mercancia',
                'cotizacion_models.cantidad',
                'cotizacion_models.tipo_embajale',
                'cotizacion_models.dimensiones_exactas',
                'cotizacion_models.registro_fotografico',
                'cotizacion_models.tipo_producto',
                'cotizacion_models.temperatura_mercancia',
                'cotizacion_models.humedad',
                'cotizacion_models.vehiculo_requerido',
                'cotizacion_models.regimen_nacionalizado',
                'cotizacion_models.agente_aduanas',
                'cotizacion_models.descargue_cargue',
                'cotizacion_models.consolidado_expreso',
                'cotizacion_models.fcl_lcl',
                'cotizacion_models.sitio_devolucion_contenedor',
                'cotizacion_models.numero_documento_bl',
                'cotizacion_models.fecha_hora_descargue_cargue',
                'cotizacion_models.cantidad_vh',
                'cotizacion_models.un',
                'cotizacion_models.ruta',
                'cotizacion_models.frecuencia',
                'cotizacion_models.esquema_seguridad',
                'cotizacion_models.tipo_carroceria',
                'cotizacion_models.valor_declarado',
                'cotizacion_models.tipo_mercancia',
                'cotizacion_models.ventanas_horarios_recibidos',
                'cotizacion_models.seguro',
                'cotizacion_models.silogtran_status',
                'cotizacion_models.tipo',
                'cotizacion_models.operation_type',
                'cotizacion_models.itesoltra_vehiculoacompanamiento',
                'cotizacion_models.tipaco_codigo',
                'cotizacion_models.itesoltra_acompanamientocuentade',
                'cotizacion_models.itesoltra_acompanamientovalor',
                'cotizacion_models.created_at',
                'cotizacion_models.updated_at',
                // Información del grupo de cotización
                'group_cotizations.type as grupo_tipo',
                'group_cotizations.reference as grupo_referencia',
                'group_cotizations.status as grupo_status'
            ])
            ->where('cotizacion_models.id', $ordenId)
            ->first();

        if (!$orden) {
            $this->error("❌ No se encontró la orden con ID: $ordenId");
            return;
        }

        $this->mostrarDetalleOrden($orden);
    }

    private function mostrarDetalleOrden($orden)
    {
        $this->info("📋 ORDEN #{$orden->id}");
        $this->line(str_repeat('-', 60));

        // Validar campos obligatorios
        $faltantes = $this->validarCamposObligatorios($orden);
        if (!empty($faltantes)) {
            $this->line("⚠️  <fg=red>CAMPOS OBLIGATORIOS FALTANTES:</>");
            foreach ($faltantes as $faltante) {
                $this->line("   ❌ $faltante");
            }
            $this->line("");
        } else {
            $this->line("✅ <fg=green>TODOS LOS CAMPOS OBLIGATORIOS COMPLETOS</>");
            $this->line("");
        }

        // Información básica de la orden
        $this->line("🎯 <fg=cyan>INFORMACIÓN GENERAL</>");
        $this->line("   Tipo: " . ($orden->tipo ?? 'No especificado'));
        $this->line("   Operación: " . ($orden->operation_type ?? 'No especificado'));
        $this->line("   Creada: " . ($orden->created_at ?? 'No disponible'));
        $this->line("");

        // CAMPOS OBLIGATORIOS - Destacados
        $this->line("📋 <fg=yellow>INFORMACIÓN OBLIGATORIA PARA COTIZACIÓN</>");
        $this->line("   <fg=green>Peso Mercancía:</> " . ($orden->peso_mercancia ?? '⚠️ FALTANTE'));
        $this->line("   <fg=green>Cantidad:</> " . ($orden->cantidad ?? '⚠️ FALTANTE'));
        $this->line("   <fg=green>Tipo Embalaje:</> " . ($orden->tipo_embajale ?? '⚠️ FALTANTE'));
        $this->line("   <fg=green>Dimensiones:</> " . ($orden->dimensiones_exactas ?? '⚠️ FALTANTE'));
        $this->line("   <fg=green>Tipo Producto:</> " . ($orden->tipo_producto ?? '⚠️ FALTANTE'));
        $this->line("   <fg=green>Vehículo Requerido:</> " . ($orden->vehiculo_requerido ?? '⚠️ FALTANTE'));
        $this->line("   <fg=green>Frecuencia:</> " . ($orden->frecuencia ?? '⚠️ FALTANTE'));
        $this->line("   <fg=green>Esquema Seguridad:</> " . ($orden->esquema_seguridad ?? '⚠️ FALTANTE'));
        $this->line("   <fg=green>Tipo Carrocería:</> " . $this->resolverCarroceria($orden->tipo_carroceria));
        $this->line("   <fg=green>Tipo Mercancía:</> " . ($orden->tipo_mercancia ?? '⚠️ FALTANTE'));
        $this->line("");

        // INFORMACIÓN ESTÁTICA ADICIONAL DE LA TABLA
        $this->line("📦 <fg=cyan>INFORMACIÓN ESTÁTICA DE MERCANCÍA</>");
        $this->line("   Registro Fotográfico: " . ($orden->registro_fotografico ?? 'No especificado'));
        $this->line("   Temperatura Mercancía: " . ($orden->temperatura_mercancia ?? 'No requerida'));
        $this->line("   Humedad: " . ($orden->humedad ?? 'No especificada'));
        $this->line("");

        // INFORMACIÓN DE CARGA Y DESCARGA
        $this->line("🚛 <fg=cyan>INFORMACIÓN DE CARGA Y DESCARGA</>");
        $this->line("   Fecha y Hora Descargue/Cargue: " . ($orden->fecha_hora_descargue_cargue ?? 'No especificada'));
        $this->line("   Descargue/Cargue: " . ($orden->descargue_cargue ?? 'No especificado'));
        $this->line("");

        // Información del grupo
        if ($orden->grupo_tipo || $orden->grupo_referencia) {
            $this->line("📁 <fg=cyan>GRUPO DE COTIZACIÓN</>");
            $this->line("   Tipo: " . ($orden->grupo_tipo ?? 'No especificado'));
            $this->line("   Referencia: " . ($orden->grupo_referencia ?? 'No especificada'));
            $this->line("   Estado: " . ($orden->grupo_status ?? 'No especificado'));
            $this->line("");
        }

        // Información de ruta
        $this->line("🗺️  <fg=cyan>RUTA</>");
        $this->line("   Origen: " . ($orden->ciudad_origen ?? 'No especificado'));
        $this->line("   Destino: " . ($orden->ciudad_destino ?? 'No especificado'));
        $this->line("   DANE Origen: " . ($orden->ciudad_origen_dane ?? 'No disponible'));
        $this->line("   DANE Destino: " . ($orden->ciudad_destino_dane ?? 'No disponible'));
        $this->line("   Ruta: " . ($orden->ruta ?? 'No especificada'));
        $this->line("");

        // Información de mercancía (campos adicionales no obligatorios)
        $this->line("📦 <fg=cyan>INFORMACIÓN ADICIONAL DE MERCANCÍA</>");
        $this->line("   Valor Declarado: " . ($orden->valor_declarado ?? 'No especificado'));
        $this->line("");

        // Información del vehículo (campos adicionales)
        $this->line("🚛 <fg=cyan>INFORMACIÓN ADICIONAL DE VEHÍCULO</>");
        $this->line("   Cantidad Vehículos: " . ($orden->cantidad_vh ?? 'No especificada'));
        
        if ($orden->itesoltra_vehiculoacompanamiento) {
            $this->line("   Acompañamiento: ✅ Sí");
            $this->line("   Cuenta de Acompañamiento: " . ($orden->itesoltra_acompanamientocuentade ?? 'No especificada'));
            if ($orden->itesoltra_acompanamientovalor) {
                $this->line("   Valor Acompañamiento: $" . number_format($orden->itesoltra_acompanamientovalor, 2));
            }
        }
        $this->line("");

        // Información logística (campos no obligatorios)
        $this->line("📋 <fg=cyan>INFORMACIÓN LOGÍSTICA ADICIONAL</>");
        $this->line("   Seguro: " . ($orden->seguro ?? 'No especificado'));
        $this->line("   Ventanas Horarios: " . ($orden->ventanas_horarios_recibidos ?? 'No especificadas'));
        $this->line("");

        // Información de comercio exterior (si aplica)
        if ($orden->regimen_nacionalizado || $orden->fcl_lcl || $orden->numero_documento_bl) {
            $this->line("🌍 <fg=cyan>COMERCIO EXTERIOR</>");
            if ($orden->regimen_nacionalizado) {
                $this->line("   Régimen: " . $orden->regimen_nacionalizado);
            }
            if ($orden->agente_aduanas) {
                $this->line("   Agente Aduanas: " . $orden->agente_aduanas);
            }
            if ($orden->fcl_lcl) {
                $this->line("   FCL/LCL: " . $orden->fcl_lcl);
            }
            if ($orden->numero_documento_bl) {
                $this->line("   Documento BL: " . $orden->numero_documento_bl);
            }
            if ($orden->sitio_devolucion_contenedor) {
                $this->line("   Devolución Contenedor: " . $orden->sitio_devolucion_contenedor);
            }
            $this->line("");
        }

        // Documentación
        if ($orden->registro_fotografico || $orden->un) {
            $this->line("📄 <fg=cyan>DOCUMENTACIÓN</>");
            if ($orden->registro_fotografico) {
                $this->line("   Registro Fotográfico: " . $orden->registro_fotografico);
            }
            if ($orden->un) {
                $this->line("   UN: " . $orden->un);
            }
            $this->line("");
        }

        // Estado del sistema
        if ($orden->silogtran_status) {
            $this->line("💻 <fg=cyan>SISTEMA</>");
            $this->line("   Estado Silogtran: " . $orden->silogtran_status);
            if ($orden->tipaco_codigo) {
                $this->line("   Código Tipaco: " . $orden->tipaco_codigo);
            }
            $this->line("");
        }
    }

    private function validarCamposObligatorios($orden)
    {
        $camposObligatorios = [
            'peso_mercancia' => 'Peso de la mercancía',
            'cantidad' => 'Cantidad',
            'tipo_embajale' => 'Tipo de embalaje',
            'dimensiones_exactas' => 'Dimensiones exactas',
            'tipo_producto' => 'Tipo de producto',
            'vehiculo_requerido' => 'Vehículo requerido',
            'frecuencia' => 'Frecuencia',
            'esquema_seguridad' => 'Esquema de seguridad',
            'tipo_carroceria' => 'Tipo de carrocería',
            'tipo_mercancia' => 'Tipo de mercancía'
        ];

        $faltantes = [];
        foreach ($camposObligatorios as $campo => $descripcion) {
            if (empty($orden->$campo)) {
                $faltantes[] = $descripcion;
            }
        }

        return $faltantes;
    }

    private function mostrarEstadisticas()
    {
        $this->info('📊 ESTADÍSTICAS DE COMPLETITUD DE CAMPOS OBLIGATORIOS');
        $this->line('=' . str_repeat('=', 60));

        $ordenes = DB::table('cotizacion_models')
            ->select([
                'id',
                'peso_mercancia',
                'cantidad', 
                'tipo_embajale',
                'dimensiones_exactas',
                'tipo_producto',
                'vehiculo_requerido',
                'frecuencia',
                'esquema_seguridad',
                'tipo_carroceria',
                'tipo_mercancia'
            ])
            ->get();

        $totalOrdenes = $ordenes->count();
        $ordenesCompletas = 0;
        $estadisticasCampos = [
            'peso_mercancia' => 0,
            'cantidad' => 0,
            'tipo_embajale' => 0,
            'dimensiones_exactas' => 0,
            'tipo_producto' => 0,
            'vehiculo_requerido' => 0,
            'frecuencia' => 0,
            'esquema_seguridad' => 0,
            'tipo_carroceria' => 0,
            'tipo_mercancia' => 0
        ];

        foreach ($ordenes as $orden) {
            $faltantes = $this->validarCamposObligatorios($orden);
            if (empty($faltantes)) {
                $ordenesCompletas++;
            }

            // Contar campos completos
            foreach ($estadisticasCampos as $campo => $count) {
                if (!empty($orden->$campo)) {
                    $estadisticasCampos[$campo]++;
                }
            }
        }

        $porcentajeCompletas = $totalOrdenes > 0 ? round(($ordenesCompletas / $totalOrdenes) * 100, 1) : 0;

        $this->line("📈 <fg=cyan>RESUMEN GENERAL</>");
        $this->line("   Total de órdenes: {$totalOrdenes}");
        $this->line("   Órdenes completas: {$ordenesCompletas}");
        $this->line("   Porcentaje completas: {$porcentajeCompletas}%");
        $this->line("");

        $this->line("📋 <fg=cyan>COMPLETITUD POR CAMPO OBLIGATORIO</>");
        $nombresCampos = [
            'peso_mercancia' => 'Peso de mercancía',
            'cantidad' => 'Cantidad',
            'tipo_embajale' => 'Tipo de embalaje',
            'dimensiones_exactas' => 'Dimensiones exactas',
            'tipo_producto' => 'Tipo de producto',
            'vehiculo_requerido' => 'Vehículo requerido',
            'frecuencia' => 'Frecuencia',
            'esquema_seguridad' => 'Esquema de seguridad',
            'tipo_carroceria' => 'Tipo de carrocería',
            'tipo_mercancia' => 'Tipo de mercancía'
        ];

        foreach ($estadisticasCampos as $campo => $completos) {
            $porcentaje = $totalOrdenes > 0 ? round(($completos / $totalOrdenes) * 100, 1) : 0;
            $faltantes = $totalOrdenes - $completos;
            $icono = $porcentaje == 100 ? '✅' : ($porcentaje >= 80 ? '🟡' : '❌');
            $nombre = $nombresCampos[$campo];
            
            $this->line("   {$icono} {$nombre}: {$completos}/{$totalOrdenes} ({$porcentaje}%) - Faltantes: {$faltantes}");
        }

        $this->line("");
        $this->info("💡 Use 'php artisan zinformacion --all' para ver todas las órdenes");
    }

    private function buscarOrdenes($search, $limit)
    {
        $query = DB::table('cotizacion_models')
            ->leftJoin('group_cotizations', 'cotizacion_models.group_cotization_id', '=', 'group_cotizations.id')
            ->select([
                'cotizacion_models.id',
                'cotizacion_models.ciudad_origen',
                'cotizacion_models.ciudad_destino',
                'cotizacion_models.tipo_mercancia',
                'cotizacion_models.peso_mercancia',
                'cotizacion_models.cantidad',
                'cotizacion_models.tipo_embajale',
                'cotizacion_models.dimensiones_exactas',
                'cotizacion_models.tipo_producto',
                'cotizacion_models.vehiculo_requerido',
                'cotizacion_models.frecuencia',
                'cotizacion_models.esquema_seguridad',
                'cotizacion_models.tipo_carroceria',
                'cotizacion_models.registro_fotografico',
                'cotizacion_models.temperatura_mercancia',
                'cotizacion_models.humedad',
                'cotizacion_models.fecha_hora_descargue_cargue',
                'cotizacion_models.created_at',
                'group_cotizations.reference as grupo_referencia'
            ]);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('cotizacion_models.ciudad_origen', 'LIKE', "%$search%")
                  ->orWhere('cotizacion_models.ciudad_destino', 'LIKE', "%$search%")
                  ->orWhere('cotizacion_models.tipo_mercancia', 'LIKE', "%$search%")
                  ->orWhere('cotizacion_models.tipo_producto', 'LIKE', "%$search%")
                  ->orWhere('cotizacion_models.vehiculo_requerido', 'LIKE', "%$search%");
            });
        }

        $ordenes = $query->orderBy('cotizacion_models.id', 'desc')
                        ->limit($limit)
                        ->get();

        if ($ordenes->isEmpty()) {
            $this->warn("⚠️  No se encontraron órdenes con los criterios especificados");
            return;
        }

        $this->info("📊 RESULTADOS ENCONTRADOS: " . $ordenes->count());
        $this->line(str_repeat('=', 60));

        $headers = ['ID', 'Origen', 'Destino', 'Mercancía', 'Peso', 'Cantidad', 'Vehículo', 'Carrocería', 'Completa'];
        $rows = [];

        foreach ($ordenes as $orden) {
            // Verificar si tiene campos obligatorios completos
            $faltantes = $this->validarCamposObligatorios($orden);
            $completa = empty($faltantes) ? '✅' : '❌';
            
            $rows[] = [
                $orden->id,
                substr($orden->ciudad_origen ?? 'N/A', 0, 8),
                substr($orden->ciudad_destino ?? 'N/A', 0, 8),
                substr($orden->tipo_mercancia ?? 'N/A', 0, 10),
                substr($orden->peso_mercancia ?? 'N/A', 0, 6),
                substr($orden->cantidad ?? 'N/A', 0, 4),
                substr($orden->vehiculo_requerido ?? 'N/A', 0, 10),
                substr($orden->tipo_carroceria ?? 'N/A', 0, 8),
                $completa
            ];
        }

        $this->table($headers, $rows);

        $this->line("");
        $this->info("💡 Para ver detalles completos de una orden, use: php artisan zinformacion [ID]");
    }

    private function resolverCarroceria($tipoCarroceria)
    {
        if (!$tipoCarroceria) {
            return 'No especificada';
        }

        // Buscar en la tabla bodywork
        $bodywork = DB::table('bodywork')
            ->where('Nombre', 'LIKE', "%$tipoCarroceria%")
            ->orWhere('Codigo', $tipoCarroceria)
            ->first();

        if ($bodywork) {
            return "{$bodywork->Nombre} (Código: {$bodywork->Codigo})";
        }

        return $tipoCarroceria . ' (Sin mapear en bodywork)';
    }

    private function mostrarAyuda()
    {
        $this->info('🔍 HERRAMIENTA ZINFORMACION - Ayuda');
        $this->line('=' . str_repeat('=', 60));
        $this->line('');
        $this->line('<fg=yellow>EJEMPLOS DE USO:</>');
        $this->line('');
        $this->line('🔹 Ver orden específica:');
        $this->line('   php artisan zinformacion 31');
        $this->line('');
        $this->line('🔹 Buscar órdenes por texto:');
        $this->line('   php artisan zinformacion --search="bogota"');
        $this->line('   php artisan zinformacion --search="granel"');
        $this->line('');
        $this->line('🔹 Ver todas las órdenes (limitadas):');
        $this->line('   php artisan zinformacion --all');
        $this->line('   php artisan zinformacion --all --limit=20');
        $this->line('');
        $this->line('🔹 Ver estadísticas de completitud:');
        $this->line('   php artisan zinformacion --stats');
        $this->line('');
        $this->line('🔹 Búsquedas con límite:');
        $this->line('   php artisan zinformacion --search="medellin" --limit=5');
        $this->line('');
        $this->line('<fg=red>NOTA:</> Esta herramienta muestra campos estáticos de cotizacion_models');
        $this->line('excluyendo información sensible como datos del cliente, porcentajes de');
        $this->line('ganancia, estados de decisión, conductores seleccionados y cualquier');
        $this->line('información posterior a las llamadas con conductores.');
    }
}
