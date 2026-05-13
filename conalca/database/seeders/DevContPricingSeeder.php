<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pricing;
use Illuminate\Support\Facades\DB;

class DevContPricingSeeder extends Seeder
{
    /**
     * Seed return container (DEV CONT) pricings for IMPORTACION operations.
     * Data from official rate table.
     */
    public function run(): void
    {
        // Delete any existing DEV CONT pricings to avoid duplicates
        Pricing::where('condition', 'IMPORTACION')
            ->where('vehicle_type', 'LIKE', 'DEV CNT%')
            ->delete();

        $devContPricings = [
            // ========================
            // BOGOTÁ → CARTAGENA
            // ========================
            ['origin' => 'BOGOTÁ', 'destination' => 'CARTAGENA', 'vehicle_type' => "DEV CNT 20' COMPENSACIÓN", 'price' => '600000', 'time_day' => '6', 'container' => '20', 'complements' => 'DESPUÉS DE DESCARGUE DEL CONSOLIDADO'],
            ['origin' => 'BOGOTÁ', 'destination' => 'CARTAGENA', 'vehicle_type' => "DEV CNT 20' EXPRESO FULL", 'price' => '2718285', 'time_day' => '3', 'container' => '20', 'complements' => 'COSTO CONSOLIDADO SOLO APLICARA PARA TIPO DE VIAJE MANIFIESTO IDA-REGRESO'],
            ['origin' => 'BOGOTÁ', 'destination' => 'CARTAGENA', 'vehicle_type' => "DEV CNT 40' COMPENSACIÓN", 'price' => '700000', 'time_day' => '6', 'container' => '40', 'complements' => ''],
            ['origin' => 'BOGOTÁ', 'destination' => 'CARTAGENA', 'vehicle_type' => "DEV CNT 40' EXPRESO FULL", 'price' => '3779792', 'time_day' => '3', 'container' => '40', 'complements' => ''],

            // ========================
            // BOGOTÁ → BUENAVENTURA
            // ========================
            ['origin' => 'BOGOTÁ', 'destination' => 'BUENAVENTURA', 'vehicle_type' => "DEV CNT 20' COMPENSACIÓN", 'price' => '600000', 'time_day' => '6', 'container' => '20', 'complements' => 'DESPUÉS DEL DESCARGUE DEL CONSOLIDADO'],
            ['origin' => 'BOGOTÁ', 'destination' => 'BUENAVENTURA', 'vehicle_type' => "DEV CNT 20' EXPRESO FULL", 'price' => '1471572', 'time_day' => '3', 'container' => '20', 'complements' => 'COSTO CONSOLIDADO O EXPRESO PACTADO SOLO APLICARA PARA TIPO DE VIAJE MANIFIESTO IDA-REGRESO'],
            ['origin' => 'BOGOTÁ', 'destination' => 'BUENAVENTURA', 'vehicle_type' => "DEV CNT 40' COMPENSACIÓN", 'price' => '700000', 'time_day' => '6', 'container' => '40', 'complements' => ''],
            ['origin' => 'BOGOTÁ', 'destination' => 'BUENAVENTURA', 'vehicle_type' => "DEV CNT 40' EXPRESO FULL", 'price' => '2073396', 'time_day' => '3', 'container' => '40', 'complements' => ''],

            // ========================
            // BOGOTÁ → BARRANQUILLA
            // ========================
            ['origin' => 'BOGOTÁ', 'destination' => 'BARRANQUILLA', 'vehicle_type' => "DEV CNT 20' COMPENSACIÓN", 'price' => '600000', 'time_day' => '6', 'container' => '20', 'complements' => 'DESPUÉS DEL DESCARGUE DEL CONSOLIDADO'],
            ['origin' => 'BOGOTÁ', 'destination' => 'BARRANQUILLA', 'vehicle_type' => "DEV CNT 20' EXPRESO FULL", 'price' => '2591439', 'time_day' => '3', 'container' => '20', 'complements' => 'COSTO CONSOLIDADO SOLO APLICARA PARA TIPO DE VIAJE MANIFIESTO IDA-REGRESO'],
            ['origin' => 'BOGOTÁ', 'destination' => 'BARRANQUILLA', 'vehicle_type' => "DEV CNT 40' COMPENSACIÓN", 'price' => '700000', 'time_day' => '6', 'container' => '40', 'complements' => ''],
            ['origin' => 'BOGOTÁ', 'destination' => 'BARRANQUILLA', 'vehicle_type' => "DEV CNT 40' EXPRESO FULL", 'price' => '3631366', 'time_day' => '3', 'container' => '40', 'complements' => ''],

            // ========================
            // BOGOTÁ → SANTA MARTA
            // ========================
            ['origin' => 'BOGOTÁ', 'destination' => 'SANTA MARTA', 'vehicle_type' => "DEV CNT 20' COMPENSACIÓN", 'price' => '600000', 'time_day' => '6', 'container' => '20', 'complements' => 'COSTO CONSOLIDADO SOLO APLICARA PARA TIPO DE VIAJE MANIFIESTO IDA-REGRESO'],
            ['origin' => 'BOGOTÁ', 'destination' => 'SANTA MARTA', 'vehicle_type' => "DEV CNT 20' EXPRESO FULL", 'price' => '2397809', 'time_day' => '3', 'container' => '20', 'complements' => ''],
            ['origin' => 'BOGOTÁ', 'destination' => 'SANTA MARTA', 'vehicle_type' => "DEV CNT 40' COMPENSACIÓN", 'price' => '700000', 'time_day' => '6', 'container' => '40', 'complements' => ''],
            ['origin' => 'BOGOTÁ', 'destination' => 'SANTA MARTA', 'vehicle_type' => "DEV CNT 40' EXPRESO FULL", 'price' => '3351208', 'time_day' => '3', 'container' => '40', 'complements' => ''],

            // ========================
            // CALI → CARTAGENA
            // ========================
            ['origin' => 'CALI', 'destination' => 'CARTAGENA', 'vehicle_type' => "DEV CNT 20' COMPENSACIÓN", 'price' => '800000', 'time_day' => '6', 'container' => '20', 'complements' => 'DESPUÉS DEL DESCARGUE DEL CONSOLIDADO'],
            ['origin' => 'CALI', 'destination' => 'CARTAGENA', 'vehicle_type' => "DEV CNT 20' EXPRESO FULL", 'price' => '0', 'time_day' => '3', 'container' => '20', 'complements' => 'PUNTUAL PACTADO - COSTO CONSOLIDADO SOLO APLICARA PARA TIPO DE VIAJE MANIFIESTO IDA-REGRESO'],
            ['origin' => 'CALI', 'destination' => 'CARTAGENA', 'vehicle_type' => "DEV CNT 40' COMPENSACIÓN", 'price' => '1000000', 'time_day' => '5', 'container' => '40', 'complements' => ''],
            ['origin' => 'CALI', 'destination' => 'CARTAGENA', 'vehicle_type' => "DEV CNT 40' EXPRESO FULL", 'price' => '0', 'time_day' => '3', 'container' => '40', 'complements' => 'PUNTUAL PACTADO'],

            // ========================
            // CALI → BUENAVENTURA
            // ========================
            ['origin' => 'CALI', 'destination' => 'BUENAVENTURA', 'vehicle_type' => "DEV CNT 20' COMPENSACIÓN", 'price' => '0', 'time_day' => '2', 'container' => '20', 'complements' => 'DESPUÉS DEL DESCARGUE DEL CONSOLIDADO'],
            ['origin' => 'CALI', 'destination' => 'BUENAVENTURA', 'vehicle_type' => "DEV CNT 20' EXPRESO FULL", 'price' => '850000', 'time_day' => '1', 'container' => '20', 'complements' => ''],
            ['origin' => 'CALI', 'destination' => 'BUENAVENTURA', 'vehicle_type' => "DEV CNT 40' COMPENSACIÓN", 'price' => '0', 'time_day' => '2', 'container' => '40', 'complements' => ''],
            ['origin' => 'CALI', 'destination' => 'BUENAVENTURA', 'vehicle_type' => "DEV CNT 40' EXPRESO FULL", 'price' => '1000000', 'time_day' => '1', 'container' => '40', 'complements' => ''],

            // ========================
            // MEDELLÍN → CARTAGENA
            // ========================
            ['origin' => 'MEDELLÍN', 'destination' => 'CARTAGENA', 'vehicle_type' => "DEV CNT 20' COMPENSACIÓN", 'price' => '600000', 'time_day' => '4', 'container' => '20', 'complements' => 'DESPUÉS DEL DESCARGUE DEL CONSOLIDADO'],
            ['origin' => 'MEDELLÍN', 'destination' => 'CARTAGENA', 'vehicle_type' => "DEV CNT 20' EXPRESO FULL", 'price' => '1817322', 'time_day' => '2', 'container' => '20', 'complements' => 'COSTO CONSOLIDADO SOLO APLICARA PARA TIPO DE VIAJE MANIFIESTO IDA-REGRESO'],
            ['origin' => 'MEDELLÍN', 'destination' => 'CARTAGENA', 'vehicle_type' => "DEV CNT 40' COMPENSACIÓN", 'price' => '700000', 'time_day' => '4', 'container' => '40', 'complements' => ''],
            ['origin' => 'MEDELLÍN', 'destination' => 'CARTAGENA', 'vehicle_type' => "DEV CNT 40' EXPRESO FULL", 'price' => '2488933', 'time_day' => '2', 'container' => '40', 'complements' => ''],

            // ========================
            // MEDELLÍN → BARRANQUILLA
            // ========================
            ['origin' => 'MEDELLÍN', 'destination' => 'BARRANQUILLA', 'vehicle_type' => "DEV CNT 20' COMPENSACIÓN", 'price' => '600000', 'time_day' => '4', 'container' => '20', 'complements' => 'DESPUÉS DEL DESCARGUE DEL CONSOLIDADO'],
            ['origin' => 'MEDELLÍN', 'destination' => 'BARRANQUILLA', 'vehicle_type' => "DEV CNT 20' EXPRESO FULL", 'price' => '1905230', 'time_day' => '2', 'container' => '20', 'complements' => 'COSTO CONSOLIDADO SOLO APLICARA PARA TIPO DE VIAJE MANIFIESTO IDA-REGRESO'],
            ['origin' => 'MEDELLÍN', 'destination' => 'BARRANQUILLA', 'vehicle_type' => "DEV CNT 40' COMPENSACIÓN", 'price' => '700000', 'time_day' => '4', 'container' => '40', 'complements' => ''],
            ['origin' => 'MEDELLÍN', 'destination' => 'BARRANQUILLA', 'vehicle_type' => "DEV CNT 40' EXPRESO FULL", 'price' => '2609567', 'time_day' => '2', 'container' => '40', 'complements' => ''],

            // ========================
            // MEDELLÍN → BUENAVENTURA
            // ========================
            ['origin' => 'MEDELLÍN', 'destination' => 'BUENAVENTURA', 'vehicle_type' => "DEV CNT 20' COMPENSACIÓN", 'price' => '600000', 'time_day' => '6', 'container' => '20', 'complements' => 'DESPUÉS DEL DESCARGUE DEL CONSOLIDADO'],
            ['origin' => 'MEDELLÍN', 'destination' => 'BUENAVENTURA', 'vehicle_type' => "DEV CNT 20' EXPRESO FULL", 'price' => '1438976', 'time_day' => '3', 'container' => '20', 'complements' => 'COSTO CONSOLIDADO O EXPRESO PACTADO SOLO APLICARA PARA TIPO DE VIAJE MANIFIESTO IDA-REGRESO'],
            ['origin' => 'MEDELLÍN', 'destination' => 'BUENAVENTURA', 'vehicle_type' => "DEV CNT 40' COMPENSACIÓN", 'price' => '700000', 'time_day' => '6', 'container' => '40', 'complements' => ''],
            ['origin' => 'MEDELLÍN', 'destination' => 'BUENAVENTURA', 'vehicle_type' => "DEV CNT 40' EXPRESO FULL", 'price' => '2126581', 'time_day' => '3', 'container' => '40', 'complements' => ''],
        ];

        $now = now();

        foreach ($devContPricings as $row) {
            // Insert in the provided direction (city → port)
            Pricing::create([
                'origin'      => $row['origin'],
                'destination'  => $row['destination'],
                'vehicle_type' => $row['vehicle_type'],
                'price'        => $row['price'],
                'weight'       => '0',
                'condition'    => 'IMPORTACION',
                'time_day'     => $row['time_day'],
                'container'    => $row['container'],
                'complements'  => $row['complements'],
                'type_pricing' => 'dev_cont',
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            // Also insert reverse direction (port → city) so it works
            // regardless of how the parent route is configured
            Pricing::create([
                'origin'      => $row['destination'],  // port
                'destination'  => $row['origin'],       // city
                'vehicle_type' => $row['vehicle_type'],
                'price'        => $row['price'],
                'weight'       => '0',
                'condition'    => 'IMPORTACION',
                'time_day'     => $row['time_day'],
                'container'    => $row['container'],
                'complements'  => $row['complements'],
                'type_pricing' => 'dev_cont',
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        $totalInserted = count($devContPricings) * 2; // both directions
        $this->command->info("✅ Inserted {$totalInserted} DEV CONT pricings ({$totalInserted}/2 forward + {$totalInserted}/2 reverse).");
    }
}
