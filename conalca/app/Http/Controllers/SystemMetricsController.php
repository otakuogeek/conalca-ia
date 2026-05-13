<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SystemMetricsController extends Controller
{
    private const CPU_SNAPSHOT_CACHE_KEY = 'system_metrics_cpu_snapshot';
    private const CPU_CORES_CACHE_KEY = 'system_metrics_cpu_cores';

    public function show(): JsonResponse
    {
        $currentCpuSnapshot = $this->readCpuSnapshot();
        $previousCpuSnapshot = Cache::get(self::CPU_SNAPSHOT_CACHE_KEY);

        if ($currentCpuSnapshot !== null) {
            Cache::put(self::CPU_SNAPSHOT_CACHE_KEY, $currentCpuSnapshot, now()->addSeconds(30));
        }

        $cpuUsage = $this->calculateCpuUsage($previousCpuSnapshot, $currentCpuSnapshot);
        $cpuCores = $this->getCpuCoreCount();


        if ($cpuUsage === null) {
            $cpuUsage = $this->calculateLoadAverageUsage($cpuCores);
        }

        return response()->json([
            'cpu' => [
                'usage_percent' => round($cpuUsage, 1),
                'cores' => $cpuCores,
            ],
            'memory' => $this->readMemoryUsage(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    private function readCpuSnapshot(): ?array
    {
        if (! is_readable('/proc/stat')) {
            return null;
        }

        $contents = file('/proc/stat', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($contents === false || empty($contents[0])) {
            return null;
        }

        $parts = preg_split('/\s+/', trim($contents[0]));

        if (! is_array($parts) || ($parts[0] ?? '') !== 'cpu') {
            return null;
        }

        $values = array_map('intval', array_slice($parts, 1));

        if (count($values) < 5) {
            return null;
        }

        $idle = ($values[3] ?? 0) + ($values[4] ?? 0);

        return [
            'idle' => $idle,
            'total' => array_sum($values),
        ];
    }

    private function calculateCpuUsage(?array $firstSnapshot, ?array $secondSnapshot): ?float
    {
        if ($firstSnapshot === null || $secondSnapshot === null) {
            return null;
        }

        $totalDiff = $secondSnapshot['total'] - $firstSnapshot['total'];
        $idleDiff = $secondSnapshot['idle'] - $firstSnapshot['idle'];

        if ($totalDiff <= 0) {
            return null;
        }

        return $this->clampPercentage((1 - ($idleDiff / $totalDiff)) * 100);
    }

    private function calculateLoadAverageUsage(?int $cores = null): float
    {
        $loadAverage = sys_getloadavg();
        $load = is_array($loadAverage) ? (float) ($loadAverage[0] ?? 0) : 0.0;
        $cores = max($cores ?? $this->getCpuCoreCount(), 1);

        return $this->clampPercentage(($load / $cores) * 100);
    }

    private function readMemoryUsage(): array
    {
        $memoryInfo = $this->readMemoryInfo();
        $totalKb = (int) ($memoryInfo['MemTotal'] ?? 0);
        $availableKb = (int) ($memoryInfo['MemAvailable'] ?? ($memoryInfo['MemFree'] ?? 0));
        $usedKb = max($totalKb - $availableKb, 0);
        $usagePercent = $totalKb > 0 ? ($usedKb / $totalKb) * 100 : 0;
        $usedBytes = $usedKb * 1024;
        $totalBytes = $totalKb * 1024;

        return [
            'usage_percent' => round($this->clampPercentage($usagePercent), 1),
            'used_bytes' => $usedBytes,
            'total_bytes' => $totalBytes,
            'used_label' => $this->formatBytes($usedBytes),
            'total_label' => $this->formatBytes($totalBytes),
        ];
    }

    private function readMemoryInfo(): array
    {
        if (! is_readable('/proc/meminfo')) {
            return [];
        }

        $lines = file('/proc/meminfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return [];
        }

        $memoryInfo = [];

        foreach ($lines as $line) {
            if (preg_match('/^([A-Za-z_()]+):\s+(\d+)/', $line, $matches) === 1) {
                $memoryInfo[$matches[1]] = (int) $matches[2];
            }
        }

        return $memoryInfo;
    }

    private function getCpuCoreCount(): int
    {
        return Cache::remember(self::CPU_CORES_CACHE_KEY, now()->addHour(), function () {
            return $this->readCpuCoreCount();
        });
    }

    private function readCpuCoreCount(): int
    {
        if (! is_readable('/proc/cpuinfo')) {
            return 1;
        }

        $cpuInfo = file_get_contents('/proc/cpuinfo');

        if ($cpuInfo === false) {
            return 1;
        }

        $count = preg_match_all('/^processor\s*:/m', $cpuInfo);

        return max((int) $count, 1);
    }

    private function clampPercentage(float $value): float
    {
        return min(max($value, 0), 100);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = (float) $bytes;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        $decimals = $size >= 10 || $unitIndex === 0 ? 1 : 2;

        return number_format($size, $decimals, '.', '') . ' ' . $units[$unitIndex];
    }
}