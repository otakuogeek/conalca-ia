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

class CallAnalyticsTopDriversSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize, WithMapping
{
    protected $drivers;

    public function __construct($drivers)
    {
        $this->drivers = $drivers;
    }

    public function title(): string
    {
        return 'Top 10';
    }

    public function collection()
    {
        return $this->drivers;
    }

    public function headings(): array
    {
        return [
            'Conductor',
            'Teléfono',
            'Total Llamadas',
            'Completadas',
            'Tasa Respuesta %',
        ];
    }

    public function map($d): array
    {
        return [
            $d->nombre_conductor,
            $d->telefono,
            $d->total_llamadas,
            $d->completadas,
            $d->tasa_respuesta,
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
