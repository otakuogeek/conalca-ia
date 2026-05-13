<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CallAnalyticsDailySheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize, WithMapping
{
    protected $dailyCalls;

    public function __construct($dailyCalls)
    {
        $this->dailyCalls = $dailyCalls;
    }

    public function title(): string
    {
        return 'Llamadas Diarias';
    }

    public function collection()
    {
        return $this->dailyCalls;
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Total',
            'Completadas',
            'Fallidas',
        ];
    }

    public function map($d): array
    {
        return [
            $d->fecha,
            $d->total,
            $d->completadas,
            $d->fallidas,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF4472C4']],
            ],
        ];
    }
}
