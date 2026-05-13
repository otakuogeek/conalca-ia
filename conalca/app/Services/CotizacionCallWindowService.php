<?php

namespace App\Services;

use App\Models\CotizacionModel;
use Carbon\Carbon;

class CotizacionCallWindowService
{
    public function getCallRestriction(CotizacionModel $cotizacion): ?array
    {
        $rawDate = data_get($cotizacion, 'fecha_hora_descargue_cargue') ?? data_get($cotizacion, 'fecha_cargue');
        $loadingDateTime = $this->parseLoadingDateTime($rawDate);

        if (! $loadingDateTime) {
            return null;
        }

        $hasExplicitTime = $this->hasExplicitTime((string) $rawDate);
        $deadline = $hasExplicitTime ? $loadingDateTime->copy() : $loadingDateTime->copy()->endOfDay();
        $now = Carbon::now(config('app.timezone'));

        if ($deadline->greaterThan($now)) {
            return null;
        }

        return [
            'code' => 'loading_datetime_expired',
            'message' => $hasExplicitTime
                ? 'No se pueden realizar llamadas porque la fecha y hora de cargue ya pasaron.'
                : 'No se pueden realizar llamadas porque la fecha de cargue ya pasó.',
            'raw_value' => $rawDate,
            'has_explicit_time' => $hasExplicitTime,
            'loading_at' => $loadingDateTime->toDateTimeString(),
            'loading_at_label' => $this->formatDateTimeLabel($loadingDateTime),
            'checked_at' => $now->toDateTimeString(),
            'checked_at_label' => $this->formatDateTimeLabel($now),
        ];
    }

    public function parseLoadingDateTime(mixed $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        $rawValue = trim((string) $value);

        if ($rawValue === '' || strtoupper($rawValue) === 'NULL') {
            return null;
        }

        $normalizedValue = $this->normalizeDateValue($rawValue);
        $timezone = config('app.timezone');
        $formats = [
            '!Y-m-d H:i:s',
            '!Y-m-d H:i',
            '!Y-m-d\TH:i:s',
            '!Y-m-d\TH:i',
            '!Y-m-d h:i:s A',
            '!Y-m-d h:i A',
            '!d/m/Y H:i:s',
            '!d/m/Y H:i',
            '!d/m/Y h:i:s A',
            '!d/m/Y h:i A',
            '!d-m-Y H:i:s',
            '!d-m-Y H:i',
            '!d-m-Y h:i:s A',
            '!d-m-Y h:i A',
            '!Y-m-d',
            '!d/m/Y',
            '!d-m-Y',
        ];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $normalizedValue, $timezone);

                if ($parsed instanceof Carbon) {
                    return $parsed->setTimezone($timezone);
                }
            } catch (\Throwable $exception) {
                continue;
            }
        }

        try {
            return Carbon::parse($normalizedValue, $timezone)->setTimezone($timezone);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function hasExplicitTime(string $value): bool
    {
        return preg_match('/\b\d{1,2}:\d{2}(:\d{2})?\b/', $this->normalizeDateValue($value)) === 1;
    }

    private function normalizeDateValue(string $value): string
    {
        $normalizedValue = trim(preg_replace('/\s+/', ' ', $value) ?? $value);
        $normalizedValue = preg_replace_callback(
            '/\b([ap])\s*\.?\s*m\.?/i',
            fn (array $matches) => strtoupper($matches[1]) . 'M',
            $normalizedValue
        ) ?? $normalizedValue;

        return trim($normalizedValue);
    }

    private function formatDateTimeLabel(Carbon $dateTime): string
    {
        $period = $dateTime->format('A') === 'AM' ? 'a. m.' : 'p. m.';

        return $dateTime->format('d/m/Y h:i') . ' ' . $period;
    }
}