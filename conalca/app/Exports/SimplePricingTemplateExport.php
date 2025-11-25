<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SimplePricingTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $pricingType;
    protected $existingData;

    public function __construct($pricingType, $existingData = null)
    {
        $this->pricingType = $pricingType;
        $this->existingData = $existingData;
    }

    public function array(): array
    {
        $data = [];
        
        // If we have existing data, use it
        if ($this->existingData && $this->existingData->count() > 0) {
            foreach ($this->existingData as $item) {
                $data[] = $this->mapDataToRow($item);
            }
        } else {
            // Create 3 empty rows for template
            for ($i = 0; $i < 3; $i++) {
                $data[] = $this->getEmptyRow();
            }
        }
        
        return $data;
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
        $baseColumns = ['id', 'type_pricing'];

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

            default: // Generic for other types
                return array_merge($baseColumns, [
                    'origin', 'destination', 'vehicle_type', 'price',
                    'documents', 'extra', 'price_extra', 'load_target',
                    'download_target', 'iva', 'weight', 'condition'
                ]);
        }
    }

    private function mapDataToRow($item)
    {
        $columns = $this->getColumnsForPricingType();
        $row = [];

        foreach ($columns as $column) {
            $value = '';
            if (isset($item->$column)) {
                $value = $item->$column;
            }
            $row[] = $value;
        }

        return $row;
    }

    private function getEmptyRow()
    {
        $columns = $this->getColumnsForPricingType();
        $row = [];

        foreach ($columns as $column) {
            if ($column === 'id') {
                $row[] = ''; // Empty ID for new records
            } elseif ($column === 'type_pricing') {
                $row[] = $this->pricingType;
            } else {
                $row[] = '';
            }
        }

        return $row;
    }
}
