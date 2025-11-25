<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class PricingTemplateExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $pricingType;
    protected $existingData;

    public function __construct($pricingType, $existingData = null)
    {
        $this->pricingType = $pricingType;
        $this->existingData = $existingData;
    }

    public function collection()
    {
        // If we have existing data, return it, otherwise return empty rows for template
        if ($this->existingData && $this->existingData->count() > 0) {
            return $this->existingData->map(function ($item) {
                return $this->mapDataToColumns($item);
            });
        }

        // Return a few empty rows as template
        $emptyRows = collect();
        for ($i = 0; $i < 3; $i++) {
            $emptyRows->push($this->getEmptyRow());
        }
        return $emptyRows;
    }

    public function headings(): array
    {
        return $this->getColumnsForPricingType();
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => 'FF4472C4'],
                ],
            ],
        ];
    }

    private function getColumnsForPricingType()
    {
        $baseColumns = ['type_pricing'];

        switch ($this->pricingType) {
            case 'up': // Urbanos Ipiales
                return array_merge($baseColumns, [
                    'documents', 'origin', 'destination', 'vehicle_type', 'price',
                    'extra', 'price_extra', 'price_extra2', 'download_target',
                    'iva', 'price_person'
                ]);

            case 'sm': // San Miguel
                return array_merge($baseColumns, [
                    'event', 'type_send', 'origin', 'destination', 'save_box',
                    'time_day', 'load_target', 'return', 'container',
                    'documents', 'vehicle_type', 'price'
                ]);

            case 'p': // Perú
                return array_merge($baseColumns, [
                    'origin', 'destination', 'vehicle_type', 'price',
                    'documents', 'extra', 'price_extra', 'load_target',
                    'download_target', 'iva', 'weight', 'condition'
                ]);

            case 'tt': // Trueca Tulcán
                return array_merge($baseColumns, [
                    'origin', 'destination', 'vehicle_type', 'price',
                    'documents', 'extra', 'price_extra', 'load_target',
                    'download_target', 'iva', 'weight', 'condition'
                ]);

            case 'bd': // Bogotá Dedicados
                return array_merge($baseColumns, [
                    'origin', 'destination', 'vehicle_type', 'price',
                    'documents', 'extra', 'price_extra', 'load_target',
                    'download_target', 'iva', 'weight', 'condition',
                    'price_month', 'price_week', 'price_day'
                ]);

            case 'i': // Importación
                return array_merge($baseColumns, [
                    'origin', 'destination', 'vehicle_type', 'price',
                    'documents', 'extra', 'price_extra', 'load_target',
                    'download_target', 'iva', 'weight', 'condition',
                    'container', 'volume'
                ]);

            case 'ebmcc': // Exportación Bogotá-Medellín-Cali-Cartagena
                return array_merge($baseColumns, [
                    'origin', 'destination', 'vehicle_type', 'price',
                    'documents', 'extra', 'price_extra', 'load_target',
                    'download_target', 'iva', 'weight', 'condition',
                    'container', 'volume'
                ]);

            case 'uc': // Urbanos Colombia
                return array_merge($baseColumns, [
                    'origin', 'destination', 'vehicle_type', 'price',
                    'documents', 'extra', 'price_extra', 'load_target',
                    'download_target', 'iva', 'weight', 'condition'
                ]);

            case 'vn': // Viajes Nacionales
                return array_merge($baseColumns, [
                    'origin', 'destination', 'vehicle_type', 'price',
                    'documents', 'extra', 'price_extra', 'load_target',
                    'download_target', 'iva', 'weight', 'condition',
                    'time', 'rent'
                ]);

            case 'ivco': // Impo Viajes Circulares Origen
                return array_merge($baseColumns, [
                    'origin', 'destination', 'vehicle_type', 'price',
                    'documents', 'extra', 'price_extra', 'load_target',
                    'download_target', 'iva', 'weight', 'condition',
                    'container', 'volume'
                ]);

            case 'cs': // Carga Suelta
                return array_merge($baseColumns, [
                    'origin', 'destination', 'vehicle_type', 'price',
                    'documents', 'extra', 'price_extra', 'load_target',
                    'download_target', 'iva', 'weight', 'condition',
                    'scales', 'volume'
                ]);

            case 'dv': // Devolución Vacíos
                return array_merge($baseColumns, [
                    'origin', 'destination', 'vehicle_type', 'price',
                    'documents', 'extra', 'price_extra', 'load_target',
                    'download_target', 'iva', 'weight', 'condition'
                ]);

            default:
                return array_merge($baseColumns, [
                    'origin', 'destination', 'vehicle_type', 'price',
                    'documents', 'extra', 'price_extra', 'load_target',
                    'download_target', 'iva', 'weight', 'condition'
                ]);
        }
    }

    private function mapDataToColumns($item)
    {
        $columns = $this->getColumnsForPricingType();
        $row = [];

        foreach ($columns as $column) {
            // Handle potential null values and convert to string
            $value = $item->$column ?? '';
            $row[] = $value;
        }

        return $row;
    }

    private function getEmptyRow()
    {
        $columns = $this->getColumnsForPricingType();
        $row = [];

        foreach ($columns as $column) {
            if ($column === 'type_pricing') {
                $row[] = $this->pricingType;
            } else {
                $row[] = '';
            }
        }

        return $row;
    }
}
