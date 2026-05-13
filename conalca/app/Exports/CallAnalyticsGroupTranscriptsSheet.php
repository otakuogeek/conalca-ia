<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CallAnalyticsGroupTranscriptsSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, WithMapping, WithColumnWidths
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Llamadas y Transcripciones';
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Grupo #',
            'Referencia Grupo',
            'Cliente',
            'Tipo Grupo',
            'Conductor',
            'Teléfono',
            'Vehículo',
            'Placa',
            'Estado Llamada',
            'Estado Call',
            'Disponible',
            'Duración (s)',
            'ElevenLabs ID',
            'Fecha Llamada',
            'Transcripción',
        ];
    }

    public function map($row): array
    {
        return [
            $row->group_cotization_id ?? '',
            $row->grupo_referencia ?? '',
            $row->cliente ?? '',
            $row->grupo_tipo ?? '',
            $row->nombre_conductor ?? '',
            $row->telefono ?? '',
            $row->tipo_vehiculo ?? '',
            $row->placa ?? '',
            $row->estado_llamada ?? '',
            $row->call_status ?? '',
            $row->disponible ? 'SÍ' : 'NO',
            $row->talk_duration_seconds ?? '',
            $row->elevenlabs_conversation_id ?? '',
            $row->fecha_llamada ?? $row->lc_created_at ?? '',
            $row->transcript ?? '',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 20,
            'C' => 25,
            'D' => 12,
            'E' => 25,
            'F' => 15,
            'G' => 15,
            'H' => 12,
            'I' => 14,
            'J' => 12,
            'K' => 10,
            'L' => 12,
            'M' => 20,
            'N' => 18,
            'O' => 60,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Wrap text in transcript column
        $lastRow = $this->data->count() + 1;
        $sheet->getStyle("O2:O{$lastRow}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("O2:O{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF7030A0']],
            ],
        ];
    }
}
