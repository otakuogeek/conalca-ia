<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CallAnalyticsConversationsSheet implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnWidths
{
    protected $data;
    protected int $totalRows = 0;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Conversaciones';
    }

    public function headings(): array
    {
        return [
            'Grupo #',
            'Referencia',
            'Cliente',
            'Conductor',
            'Teléfono',
            'Placa',
            'Estado Llamada',
            'Disponible',
            'Fecha Llamada',
            '# Turno',
            'Quién Habla',
            'Mensaje',
        ];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->data as $call) {
            $transcript = $call->transcript ?? '';

            if (empty(trim($transcript))) {
                // Include the call even without transcript so all calls are visible
                $rows[] = [
                    $call->group_cotization_id ?? '',
                    $call->grupo_referencia ?? '',
                    $call->cliente ?? '',
                    $call->nombre_conductor ?? '',
                    $call->telefono ?? '',
                    $call->placa ?? '',
                    $call->estado_llamada ?? '',
                    ($call->disponible ?? false) ? 'SÍ' : 'NO',
                    $call->fecha_llamada ?? $call->lc_created_at ?? '',
                    '',
                    '',
                    '(Sin transcripción disponible)',
                ];
                continue;
            }

            $messages = $this->parseTranscript($transcript);

            if (empty($messages)) {
                $rows[] = [
                    $call->group_cotization_id ?? '',
                    $call->grupo_referencia ?? '',
                    $call->cliente ?? '',
                    $call->nombre_conductor ?? '',
                    $call->telefono ?? '',
                    $call->placa ?? '',
                    $call->estado_llamada ?? '',
                    ($call->disponible ?? false) ? 'SÍ' : 'NO',
                    $call->fecha_llamada ?? $call->lc_created_at ?? '',
                    1,
                    '',
                    $transcript,
                ];
                continue;
            }

            $turnNumber = 0;
            foreach ($messages as $msg) {
                $turnNumber++;
                $rows[] = [
                    $call->group_cotization_id ?? '',
                    $call->grupo_referencia ?? '',
                    $call->cliente ?? '',
                    $call->nombre_conductor ?? '',
                    $call->telefono ?? '',
                    $call->placa ?? '',
                    $call->estado_llamada ?? '',
                    ($call->disponible ?? false) ? 'SÍ' : 'NO',
                    $call->fecha_llamada ?? $call->lc_created_at ?? '',
                    $turnNumber,
                    $msg['role'],
                    $msg['message'],
                ];
            }
        }

        $this->totalRows = count($rows);
        return $rows;
    }

    /**
     * Parse a transcript string into structured messages.
     */
    private function parseTranscript(string $transcript): array
    {
        $messages = [];
        $lines = preg_split('/\n/', $transcript);
        $currentRole = null;
        $currentMessage = '';

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // Match [Agente]: message or [Conductor]: message
            if (preg_match('/^\[(Agente|Conductor|Agent|Driver|assistant|user)\]\s*:\s*(.*)$/i', $line, $matches)) {
                // Save previous message if exists
                if ($currentRole !== null && trim($currentMessage) !== '') {
                    $messages[] = [
                        'role' => $currentRole,
                        'message' => trim($currentMessage),
                    ];
                }

                $rawRole = strtolower($matches[1]);
                $currentRole = in_array($rawRole, ['agente', 'agent', 'assistant'])
                    ? 'Agente IA'
                    : 'Conductor';
                $currentMessage = $matches[2];
            } else {
                // Continuation of current message
                $currentMessage .= ' ' . $line;
            }
        }

        // Save last message
        if ($currentRole !== null && trim($currentMessage) !== '') {
            $messages[] = [
                'role' => $currentRole,
                'message' => trim($currentMessage),
            ];
        }

        return $messages;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,   // Grupo #
            'B' => 18,   // Referencia
            'C' => 22,   // Cliente
            'D' => 22,   // Conductor
            'E' => 15,   // Teléfono
            'F' => 12,   // Placa
            'G' => 14,   // Estado
            'H' => 10,   // Disponible
            'I' => 18,   // Fecha
            'J' => 8,    // # Turno
            'K' => 14,   // Quién Habla
            'L' => 70,   // Mensaje
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->totalRows + 1;

        // Wrap text in message column
        if ($lastRow > 1) {
            $sheet->getStyle("L2:L{$lastRow}")->getAlignment()->setWrapText(true);
            $sheet->getStyle("L2:L{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle("A2:K{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

            // Color rows by role
            for ($row = 2; $row <= $lastRow; $row++) {
                $role = $sheet->getCell("K{$row}")->getValue();
                if ($role === 'Agente IA') {
                    $sheet->getStyle("J{$row}:L{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFE8F0FE'); // light blue
                } elseif ($role === 'Conductor') {
                    $sheet->getStyle("J{$row}:L{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFE6F4EA'); // light green
                }
            }
        }

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF1A73E8']],
            ],
        ];
    }
}
