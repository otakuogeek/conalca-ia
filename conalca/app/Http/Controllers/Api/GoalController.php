<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use App\Models\User;
use App\Services\PerformanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    public function index(Request $request, PerformanceService $service)
    {
        $boss = $request->user();

        // Seguridad: solo Jefe Comercial
        if (!$boss->hasRole('JEFE COMERCIAL')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // Subalternos = usuarios creados por el jefe o relación que usted determine
        $subordinates = User::whereHas('roles', fn($q) => $q->where('name', 'GERENTE DE CUENTA')
                                                          ->orWhere('name','ASISTENTE COMERCIAL')
                                                          ->orWhere('name','SAC'))
                            ->whereIn('id', function($q) use ($boss) {
                                $q->select('user_id')
                                  ->from('user_creators')
                                  ->where('created_by', $boss->id);
                            })
                            ->get();

        $year  = Carbon::now()->year;
        $month = Carbon::now()->month;

        $data = $subordinates->map(function ($user) use ($boss, $year, $month, $service) {
            $goal = Goal::firstOrCreate(
                ['commercial_id' => $user->id, 'boss_id' => $boss->id, 'year' => $year, 'month' => $month],
                ['target_amount' => 0]
            );

            $achieved = $service->calculateAcceptedAmount($user, $year, $month);
            $goal->achieved_amount = $achieved;
            $goal->status          = $achieved >= $goal->target_amount && $goal->target_amount > 0 ? 'achieved' : 'pending';
            $goal->save();

            return [
                'id'                  => $user->id,
                'name'                => $user->name,
                'goal_id'             => $goal->id,
                'meta_mensual'        => $goal->target_amount,
                'cotizaciones_mes'    => $achieved,
                'estado'              => $goal->status === 'achieved' ? 'Cumplida' : 'No cumplida',
                'ultima_actualizacion'=> $goal->updated_at->format('d/m/Y'),
            ];
        });

        return response()->json($data);
    }

    public function update(Request $request, Goal $goal)
    {
        $boss = $request->user();
        if ($goal->boss_id !== $boss->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'target_amount' => 'required|numeric|min:0'
        ]);

        $goal->update([
            'target_amount' => $request->target_amount,
            'status'        => 'pending',
        ]);

        return response()->json($goal);
    }

    public function notifications(Request $request)
    {
        $now = Carbon::now();
        return response()->json(
            $request->user()
                ->notifications()
                ->whereMonth('created_at', $now->month)
                ->whereYear('created_at', $now->year)
                ->latest()
                ->get()
        );
    }

    public function markNotificationRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        return response()->json(['success' => true]);
    }

    public function myGoal(Request $request, PerformanceService $service)
    {
        $user = $request->user();

        // Seguridad: solo roles comerciales
        if (!$user->hasAnyRole(['SUPER ADMIN','GERENTE DE CUENTA','ASISTENTE COMERCIAL','SAC', 'PRICING'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $year  = now()->year;
        $month = now()->month;

        $goal = Goal::where('commercial_id', $user->id)
                    ->where('year',  $year)
                    ->where('month', $month)
                    ->first();

        // Si todavía no hay meta asignada
        if (!$goal) {
            return response()->json([
                'target_amount'   => 0,
                'achieved_amount' => 0,
                'percentage'      => 0,
                'status'          => 'pending',
                'month'           => $month,
                'year'            => $year,
            ]);
        }

        // Recalcular en tiempo real
        $achieved = $service->calculateAcceptedAmount($user, $year, $month);
        $goal->achieved_amount = $achieved;
        $goal->status          = $achieved >= $goal->target_amount && $goal->target_amount > 0
                                ? 'achieved' : 'pending';
        $goal->save();

        return response()->json([
            'target_amount'   => $goal->target_amount,
            'achieved_amount' => $achieved,
            'percentage'      => $goal->target_amount > 0
                                ? round(($achieved / $goal->target_amount) * 100, 2)
                                : 0,
            'status'          => $goal->status,
            'month'           => $month,
            'year'            => $year,
        ]);
    }
}
