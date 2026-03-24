<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CallAnalyticsResumenSheet implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function headings(): array
    {
        return ['Métrica', 'Valor'];
    }

    public function array(): array
    {
        return [
            ['Total Conductores Contactados', $this->data['totalCalls']],
            ['Total Llamadas Realizadas', $this->data['totalLlamadas']],
            ['Completadas', $this->data['completedCalls']],
            ['En Progreso', $this->data['inProgressCalls']],
            ['Pendientes', $this->data['pendingCalls']],
            ['Fallidas', $this->data['failedCalls']],
            ['Con ElevenLabs ID', $this->data['elevenlabsCalls']],
            ['Respondidas (llamadas)', $this->data['answeredCalls']],
            ['Sin Respuesta (llamadas)', $this->data['noAnswerCalls']],
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
