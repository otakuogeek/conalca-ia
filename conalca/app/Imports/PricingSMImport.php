<?php

namespace App\Imports;

use App\Models\Pricing;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class PricingSMImport implements ToCollection, WithHeadingRow
{
    protected $pricingType;

    public function __construct($pricingType = null)
    {
        $this->pricingType = $pricingType;
    }

    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            $this->processRow($row->toArray());
        }
    }

    /**
    * Process each row and update or create pricing
    */
    private function processRow(array $row)
    {
        // Skip empty rows
        if (empty(array_filter($row))) {
            return;
        }

        // Use the pricing type from constructor or from the row
        $type = $this->pricingType ?? ($row['type_pricing'] ?? 'sm');

        // Create base data array
        $data = [
            'type_pricing' => $type,
        ];

        // Map columns based on pricing type
        switch ($type) {
            case 'up': // Urbanos Ipiales
                $data = array_merge($data, [
                    'documents' => $row['documents'] ?? '',
                    'origin' => $row['origin'] ?? '',
                    'destination' => $row['destination'] ?? '',
                    'vehicle_type' => $row['vehicle_type'] ?? '',
                    'price' => $row['price'] ?? 0,
                    'extra' => $row['extra'] ?? '',
                    'price_extra' => $row['price_extra'] ?? 0,
                    'price_extra2' => $row['price_extra2'] ?? 0,
                    'download_target' => $row['download_target'] ?? '',
                    'iva' => $row['iva'] ?? 0,
                    'price_person' => $row['price_person'] ?? 0,
                ]);
                break;

            case 'sm': // San Miguel
                $data = array_merge($data, [
                    'event' => $row['event'] ?? '',
                    'type_send' => $row['type_send'] ?? '',
                    'origin' => $row['origin'] ?? '',
                    'destination' => $row['destination'] ?? '',
                    'save_box' => $row['save_box'] ?? '',
                    'time_day' => $row['time_day'] ?? '',
                    'load_target' => $row['load_target'] ?? '',
                    'return' => $row['return'] ?? '',
                    'container' => $row['container'] ?? '',
                    'documents' => $row['documents'] ?? '',
                    'vehicle_type' => $row['vehicle_type'] ?? '',
                    'price' => $row['price'] ?? 0,
                ]);
                break;

            default: // Generic mapping for other types
                $data = array_merge($data, [
                    'origin' => $row['origin'] ?? '',
                    'destination' => $row['destination'] ?? '',
                    'vehicle_type' => $row['vehicle_type'] ?? '',
                    'price' => $row['price'] ?? 0,
                    'documents' => $row['documents'] ?? '',
                    'extra' => $row['extra'] ?? '',
                    'price_extra' => $row['price_extra'] ?? 0,
                    'load_target' => $row['load_target'] ?? '',
                    'download_target' => $row['download_target'] ?? '',
                    'iva' => $row['iva'] ?? 0,
                    'weight' => $row['weight'] ?? '',
                    'condition' => $row['condition'] ?? '',
                ]);

                // Add specific fields based on type
                if (in_array($type, ['bd'])) {
                    $data['price_month'] = $row['price_month'] ?? 0;
                    $data['price_week'] = $row['price_week'] ?? 0;
                    $data['price_day'] = $row['price_day'] ?? 0;
                }

                if (in_array($type, ['i', 'ebmcc', 'ivco', 'cs'])) {
                    $data['container'] = $row['container'] ?? '';
                    $data['volume'] = $row['volume'] ?? '';
                }

                if (in_array($type, ['vn'])) {
                    $data['time'] = $row['time'] ?? '';
                    $data['rent'] = $row['rent'] ?? '';
                }

                if (in_array($type, ['cs'])) {
                    $data['scales'] = $row['scales'] ?? '';
                }
                break;
        }

        // Check if we have an ID to update an existing record
        if (isset($row['id']) && !empty($row['id']) && is_numeric($row['id'])) {
            // Update existing record by ID
            $pricing = Pricing::find($row['id']);
            if ($pricing) {
                $pricing->update($data);
                \Log::info('Updated pricing record', ['id' => $row['id'], 'type' => $type]);
            } else {
                \Log::warning('Pricing record not found for ID', ['id' => $row['id']]);
                // Create new record if ID not found
                Pricing::create($data);
                \Log::info('Created new pricing record (ID not found)', ['type' => $type]);
            }
        } else {
            // No ID provided, use updateOrCreate based on unique conditions
            $conditions = $this->getUpdateConditions($type, $data);
            $pricing = Pricing::updateOrCreate($conditions, $data);
            \Log::info('UpdateOrCreate pricing record', ['conditions' => $conditions, 'type' => $type]);
        }
    }

    /**
    * Get the conditions to identify existing records for update
    */
    private function getUpdateConditions($type, $data)
    {
        $baseConditions = ['type_pricing' => $type];

        switch ($type) {
            case 'up': // Urbanos Ipiales
                return array_merge($baseConditions, [
                    'origin' => $data['origin'],
                    'destination' => $data['destination'],
                    'vehicle_type' => $data['vehicle_type'],
                    'documents' => $data['documents']
                ]);

            case 'sm': // San Miguel
                return array_merge($baseConditions, [
                    'origin' => $data['origin'],
                    'destination' => $data['destination'],
                    'type_send' => $data['type_send'],
                    'event' => $data['event']
                ]);

            default: // Generic conditions
                return array_merge($baseConditions, [
                    'origin' => $data['origin'],
                    'destination' => $data['destination'],
                    'vehicle_type' => $data['vehicle_type']
                ]);
        }
    }
}
