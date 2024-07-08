<?php

namespace App\Console\Commands;

use App\Models\SavingGoal;
use App\Enums\GoalStatusEnum;
use Illuminate\Console\Command;
use App\Helper\CancelSavingGoal;
use Illuminate\Support\Facades\Log;

class SavingGoalStatusUpdater extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:saving-goal-status-updater';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command cancels all savings goals whose deadline has been reached';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info("Start saving goal control command.");

        $savings = SavingGoal::where('deadline', '<=', now())
            ->where('status', GoalStatusEnum::INPROGRESS->value)
            ->get();

        foreach ($savings as $saving) {
            $user = $saving->user;

            CancelSavingGoal::make($saving, $user, GoalStatusEnum::NO_REACHED->value);
        }

        Log::info("End saving goal control command.");
    }
}
