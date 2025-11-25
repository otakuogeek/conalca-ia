<?php
// app/Services/PerformanceService.php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class PerformanceService
{
    /** Devuelve el total en pesos aceptados para un comercial en un mes */
    public function calculateAcceptedAmount(User $commercial, int $year, int $month): float
    {
        $acceptedStates = ['ACEPTADA', 'EN TRÁNSITO', 'EN FACTURACIÓN', 'COMPLETADA'];

        return DB::table('group_cotizations as gc')
            ->join('cotizacion_models as cm', 'gc.id', '=', 'cm.group_cotization_id')
            ->join('pricings as p', 'cm.pricing_id', '=', 'p.id')
            ->where('gc.user_id', $commercial->id)
            ->whereYear('gc.created_at', $year)
            ->whereMonth('gc.created_at', $month)
            ->whereIn('gc.status', $acceptedStates)
            ->selectRaw('SUM(p.price * (cm.porcentaje / 100)) as total')
            ->value('total') ?? 0;
    }
}