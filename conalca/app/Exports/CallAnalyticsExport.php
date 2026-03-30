<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CallAnalyticsExport implements WithMultipleSheets
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        $sheets = [
            'Resumen' => new CallAnalyticsResumenSheet($this->data),
            'Por Grupo' => new CallAnalyticsGroupSheet($this->data['groupAnalysis']),
            'Conductores' => new CallAnalyticsDriversSheet($this->data['driverDetails']),
            'Top 10' => new CallAnalyticsTopDriversSheet($this->data['topDrivers']),
            'Respuestas ElevenLabs' => new CallAnalyticsResponsesSheet($this->data['driverResponses']),
            'Llamadas Diarias' => new CallAnalyticsDailySheet($this->data['dailyCalls']),
        ];

        if (!empty($this->data['rawData'])) {
            $sheets['Data Completa Conductores'] = new CallAnalyticsRawDataSheet($this->data['rawData']);
        }

        if (!empty($this->data['rawLlamadas'])) {
            $sheets['Data Completa Llamadas'] = new CallAnalyticsRawLlamadasSheet($this->data['rawLlamadas']);
        }

        if (!empty($this->data['groupTranscripts'])) {
            $sheets['Llamadas y Transcripciones'] = new CallAnalyticsGroupTranscriptsSheet($this->data['groupTranscripts']);
            $sheets['Conversaciones'] = new CallAnalyticsConversationsSheet($this->data['groupTranscripts']);
        }

        return $sheets;
    }
}
