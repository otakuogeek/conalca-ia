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

class CallAnalyticsRawLlamadasSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize, WithMapping
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Data Completa Llamadas';
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'ID Llamada',
            'Número Destino',
            'Estado',
            'Estado Cola',
            'Estado Llamada',
            'SIP Código',
            'SIP Mensaje',
            'Razón Fallo',
            'Iniciada',
            'Respondida',
            'Completada',
            'Duración Total (s)',
            'Duración Ring (s)',
            'Duración Habla (s)',
            'Dirección',
            'Tipo',
            'ElevenLabs ID',
            'Notas Llamada',
            'Observaciones',
            'Creado',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id_llamada,
            $row->numero_destino ?? '',
            $row->status ?? '',
            $row->queue_status ?? '',
            $row->call_status ?? '',
            $row->sip_status_code ?? '',
            $row->sip_status_message ?? '',
            $row->failure_reason ?? '',
            $row->call_initiated_at ?? '',
            $row->call_answered_at ?? '',
            $row->call_completed_at ?? '',
            $row->call_duration_seconds ?? '',
            $row->ring_duration_seconds ?? '',
            $row->talk_duration_seconds ?? '',
            $row->call_direction ?? '',
            $row->call_type ?? '',
            $row->elevenlabs_conversation_id ?? '',
            $row->call_notes ?? '',
            $row->observaciones ?? '',
            $row->created_at,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF548235']],
            ],
        ];
    }
}
