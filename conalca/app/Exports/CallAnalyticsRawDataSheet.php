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

class CallAnalyticsRawDataSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize, WithMapping
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Data Completa Conductores';
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Conductor',
            'Teléfono',
            'Placa',
            'Tipo Vehículo',
            'Vehículo Silogtran',
            'Peso Máximo',
            'Clase Vehículo',
            'Score',
            'Carrocería',
            'Capacidad',
            'Fuente',
            'Estado Llamada',
            'Disponible',
            'ElevenLabs ID',
            'Fecha Llamada',
            'Respuesta Llamada',
            'Notas',
            'Mercancía',
            'Peso Carga',
            'Empaque',
            'Ciudad Actual',
            'Ciudad Origen',
            'Ciudad Destino',
            'Grupo ID',
            'Grupo Referencia',
            'Grupo Tipo',
            'Cliente',
            'Creado',
            'Actualizado',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->nombre_conductor,
            $row->telefono,
            $row->placa ?? '',
            $row->tipo_vehiculo ?? '',
            $row->vehiculo_silogtran ?? '',
            $row->peso_maximo ?? '',
            $row->clase_vehiculo ?? '',
            $row->score ?? '',
            $row->carroceria ?? '',
            $row->capacidad ?? '',
            $row->fuente ?? '',
            $row->estado_llamada ?? '',
            $row->disponible ? 'Sí' : 'No',
            $row->elevenlabs_conversation_id ?? '',
            $row->fecha_llamada ?? '',
            $row->respuesta_llamada ?? '',
            $row->notas ?? '',
            $row->mercancia ?? '',
            $row->peso_carga ?? '',
            $row->empaque ?? '',
            $row->ciudad_actual ?? '',
            $row->ciudad_origen ?? '',
            $row->ciudad_destino ?? '',
            $row->group_cotization_id ?? '',
            $row->grupo_referencia ?? '',
            $row->grupo_tipo ?? '',
            $row->cliente ?? '',
            $row->created_at,
            $row->updated_at,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF2E75B6']],
            ],
        ];
    }
}
