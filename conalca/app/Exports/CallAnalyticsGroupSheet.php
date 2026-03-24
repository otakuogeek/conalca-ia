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

class CallAnalyticsGroupSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize, WithMapping
{
    protected $groups;

    public function __construct($groups)
    {
        $this->groups = $groups;
    }

    public function title(): string
    {
        return 'Por Grupo';
    }

    public function collection()
    {
        return $this->groups;
    }

    public function headings(): array
    {
        return [
            'Grupo ID',
            'Referencia',
            'Cliente',
            'Tipo',
            'Total Llamadas',
            'Conductores Únicos',
            'Completadas',
            'En Progreso',
            'Fallidas',
            'Pendientes',
            'Disponibles',
            'Tasa Respuesta %',
            'Primera Llamada',
            'Última Llamada',
        ];
    }

    public function map($group): array
    {
        $tasa = $group->total_llamadas > 0
            ? round(($group->completadas / $group->total_llamadas) * 100, 1)
            : 0;

        return [
            $group->group_cotization_id,
            $group->group_ref ?? '',
            $group->client_name ?? '',
            $group->group_type ?? '',
            $group->total_llamadas,
            $group->conductores_unicos,
            $group->completadas,
            $group->en_progreso,
            $group->fallidas,
            $group->pendientes,
            $group->disponibles,
            $tasa,
            $group->primera_llamada,
            $group->ultima_llamada,
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
