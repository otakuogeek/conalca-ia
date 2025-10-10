<?php

namespace App\Console\Commands;

use App\Models\Goal;
use App\Notifications\GoalStatusNotification;
use App\Services\PerformanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EvaluateMonthlyGoals extends Command
{
    protected $signature   = 'goals:evaluate';
    protected $description = 'Evaluate last month goals and notify bosses';

    public function handle(PerformanceService $service): int
    {
        $date  = Carbon::now()->subMonth();
        $year  = $date->year;
        $month = $date->month;

        $goals = Goal::with(['commercial', 'boss'])
            ->where('year',  $year)
            ->where('month', $month)
            ->get();

        foreach ($goals as $goal) {
            $achieved = $service->calculateAcceptedAmount($goal->commercial, $year, $month);
            Log::info('Log de goal', [
                'goal_id'   => $goal->id,
                'achieved'  => $achieved,
                'target'    => $goal->target_amount,
                'status'    => $goal->status,
            ]);
            $goal->achieved_amount = $achieved;
            $goal->status = ($goal->target_amount > 0 && $achieved >= $goal->target_amount) ? 'achieved' : 'failed';
            $goal->save();

            // notificar al jefe
            $goal->boss->notify(new GoalStatusNotification($goal));
        }

        $this->info('Monthly goals evaluated.');
        return Command::SUCCESS;
    }
}
