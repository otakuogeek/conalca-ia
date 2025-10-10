<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CotizacionModel;
use App\Models\SolicitudTransporte;
use App\Models\Client;
use Carbon\Carbon;

class DashboardDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener algunos clientes existentes
        $clients = Client::take(20)->get();
        
        // Obtener algunos pricings existentes
        $pricings = \App\Models\Pricing::take(10)->get();
        
        if ($clients->isEmpty()) {
            echo "No hay clientes en la base de datos. Creando algunos...\n";
            return;
        }
        
        if ($pricings->isEmpty()) {
            echo "No hay pricings en la base de datos. Creando algunos...\n";
            return;
        }

        // Crear cotizaciones con valores para los últimos 24 meses (2 años)
        $months = [];
        for ($i = 23; $i >= 0; $i--) {
            $months[] = Carbon::now()->subMonths($i);
        }

        foreach ($months as $month) {
            foreach ($clients->random(8) as $client) {
                // Crear 1-2 cotizaciones por cliente por mes (reducido para 24 meses)
                $cotizationsCount = rand(1, 2);
                
                for ($i = 0; $i < $cotizationsCount; $i++) {
                    $cotization = CotizacionModel::create([
                        'client_id' => $client->id,
                        'pricing_id' => $pricings->random()->id,
                        'porcentaje' => rand(10, 30),
                        'ciudad_origen' => 'Bogotá',
                        'ciudad_destino' => ['Medellín', 'Cali', 'Barranquilla', 'Cartagena'][array_rand(['Medellín', 'Cali', 'Barranquilla', 'Cartagena'])],
                        'peso_mercancia' => rand(100, 5000),
                        'cantidad' => rand(1, 10),
                        'tipo_embajale' => ['Caja', 'Pallet', 'Contenedor'][array_rand(['Caja', 'Pallet', 'Contenedor'])],
                        'tipo_producto' => ['Electrónicos', 'Alimentos', 'Textiles', 'Maquinaria'][array_rand(['Electrónicos', 'Alimentos', 'Textiles', 'Maquinaria'])],
                        'vehiculo_requerido' => ['Camión', 'Furgón', 'Tractomula'][array_rand(['Camión', 'Furgón', 'Tractomula'])],
                        'valor' => rand(500000, 5000000), // Valores entre 500K y 5M
                        'valor_declarado' => rand(1000000, 10000000),
                        'ruta' => 'Nacional',
                        'frecuencia' => ['Única', 'Semanal', 'Mensual'][array_rand(['Única', 'Semanal', 'Mensual'])],
                        'seguro' => rand(0, 1),
                        'active' => 1,
                        'created_at' => $month->copy()->addDays(rand(1, 28)),
                        'updated_at' => $month->copy()->addDays(rand(1, 28)),
                    ]);

                    // Crear solicitud de transporte para algunas cotizaciones
                    if (rand(0, 1)) {
                        $estados = ['pendiente', 'en_proceso', 'completado'];
                        $estado = $estados[array_rand($estados)];
                        
                        // Si es del mes pasado o anterior, más probabilidad de estar completado
                        if ($month->lt(Carbon::now()->startOfMonth()) && rand(0, 1)) {
                            $estado = 'completado';
                        }
                        
                        SolicitudTransporte::create([
                            'client_id' => $client->id,
                            'cotizacion_model_id' => $cotization->id,
                            'tipo_viaje' => ['Nacional', 'Internacional'][array_rand(['Nacional', 'Internacional'])],
                            'moneda' => 'COP',
                            'fuente_solicitud' => ['Web', 'Teléfono', 'Email'][array_rand(['Web', 'Teléfono', 'Email'])],
                            'observacion' => 'Solicitud de transporte generada automáticamente',
                            'fecha_solicitud' => $month->copy()->addDays(rand(1, 28)),
                            'origen' => 'Bogotá',
                            'destino' => ['Medellín', 'Cali', 'Barranquilla', 'Cartagena'][array_rand(['Medellín', 'Cali', 'Barranquilla', 'Cartagena'])],
                            'estado' => $estado,
                            'steps_completed' => rand(1, 5),
                            'created_at' => $month->copy()->addDays(rand(1, 28)),
                            'updated_at' => $month->copy()->addDays(rand(1, 28)),
                        ]);
                    }
                }
            }
        }

        echo "Datos del dashboard creados exitosamente!\n";
        echo "Cotizaciones creadas: " . CotizacionModel::where('created_at', '>=', Carbon::now()->subMonths(24))->count() . "\n";
        echo "Solicitudes creadas: " . SolicitudTransporte::where('created_at', '>=', Carbon::now()->subMonths(24))->count() . "\n";
    }
}
