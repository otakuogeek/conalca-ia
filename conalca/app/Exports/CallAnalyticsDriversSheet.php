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

class CallAnalyticsDriversSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize, WithMapping
{
    protected $drivers;

    public function __construct($drivers)
    {
        $this->drivers = $drivers;
    }

    public function title(): string
    {
        return 'Conductores';
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
            'Tipo Vehículo',
            'Ciudad Actual',
            'Ciudad Origen',
            'Ciudad Destino',
            'Veces Llamado',
            'Respondió',
            'No Respondió',
            'Veces Disponible',
            'Grupos',
            'Última Llamada',
        ];
    }

    public function map($d): array
    {
        return [
            $d->nombre_conductor,
            $d->telefono,
            $d->tipo_vehiculo ?? '',
            $d->ciudad_actual ?? '',
            $d->ciudad_origen ?? '',
            $d->ciudad_destino ?? '',
            $d->veces_llamado,
            $d->respondio,
            $d->no_respondio,
            $d->veces_disponible,
            $d->grupos ?? '',
            $d->ultima_llamada,
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
