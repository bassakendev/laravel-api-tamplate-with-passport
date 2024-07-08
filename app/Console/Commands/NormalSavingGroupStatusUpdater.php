<?php

namespace App\Console\Commands;

use App\Models\SavingGroup;
use App\Enums\GoalStatusEnum;
use Illuminate\Console\Command;
use App\Helper\CancelSavingGroup;
use App\Enums\SavingGroupTypeEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Enums\SavingGroupStatusEnum;

class NormalSavingGroupStatusUpdater extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:normal-saving-group-status-updater';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command cancels all members whose savings group deadline has been reached';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info("Start normal saving group control command.");

        Self::make();

        Log::info("End normal saving group control command.");
    }

    static public function make()
    {
        DB::beginTransaction();

        $groups = SavingGroup::where('deadline', '<=', now())
        ->where('status', SavingGroupStatusEnum::INPROGRESS->value)
        ->where('type', SavingGroupTypeEnum::NORMAL->value)
        ->get();

        foreach ($groups as $group) {
            $members = $group->members()->where('participation_status', GoalStatusEnum::INPROGRESS->value)->get();

            foreach ($members as $member) {

                CancelSavingGroup::make($group, $member, GoalStatusEnum::NO_REACHED->value, SavingGroupTypeEnum::NORMAL->value);
            }

            $group->status = SavingGroupStatusEnum::END->value;
            $group->save();
        }

        DB::commit();
    }
}
