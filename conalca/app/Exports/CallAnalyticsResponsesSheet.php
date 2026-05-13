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

class CallAnalyticsResponsesSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize, WithMapping
{
    protected $responses;

    public function __construct($responses)
    {
        $this->responses = $responses;
    }

    public function title(): string
    {
        return 'Respuestas ElevenLabs';
    }

    public function collection()
    {
        return $this->responses;
    }

    public function headings(): array
    {
        return [
            'Conductor',
            'Teléfono',
            'Tipo Vehículo',
            'Placa',
            'Estado Llamada',
            'Respuesta',
            'Duración (s)',
            'Cotización ID',
            'ElevenLabs ID',
            'Fecha',
        ];
    }

    public function map($r): array
    {
        return [
            $r->driver_name,
            $r->driver_phone,
            $r->vehicle_type ?? '',
            $r->vehicle_plate ?? '',
            $r->call_status ?? '',
            $r->response_status ?? '',
            $r->call_duration ?? '',
            $r->cotizacion_id ?? '',
            $r->elevenlabs_conversation_id ?? '',
            $r->created_at,
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
