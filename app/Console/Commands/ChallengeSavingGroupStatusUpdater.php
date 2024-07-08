<?php

namespace App\Console\Commands;

use App\Utils\DateUtils;
use App\Models\SavingGroup;
use App\Enums\GoalStatusEnum;
use Illuminate\Console\Command;
use App\Helper\CancelSavingGroup;
use App\Enums\SavingGroupTypeEnum;
use Illuminate\Support\Facades\Log;
use App\Enums\SavingGroupStatusEnum;

class ChallengeSavingGroupStatusUpdater extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:challenge-saving-group-status-updater';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command cancels daily all members whose challenge savings group deadline has been reached';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info("Start challenge saving group control command.");

        Self::make();

        Log::info("End challenge saving group control command.");
    }

    static public function make()
    {
        $groups = SavingGroup::where('current_period_end_date', '<=', now())
            ->where('status', SavingGroupStatusEnum::INPROGRESS->value)
            ->where('type', SavingGroupTypeEnum::CHALLENGE->value)
            ->get();

        foreach ($groups as $group) {

            $membersToCancel = $group->members()
                ->where('participation_status', GoalStatusEnum::INPROGRESS->value)
                ->where('virtual_current_amount', '<', $group->target_amount_per_member)
                ->get();

            $membersWhoContinues = $group->members()
                ->where('participation_status', GoalStatusEnum::INPROGRESS->value)
                ->where('virtual_current_amount', '>=', $group->target_amount_per_member)
                ->get();

            foreach ($membersToCancel as $member) {

                CancelSavingGroup::make($group, $member, GoalStatusEnum::NO_REACHED->value, SavingGroupTypeEnum::CHALLENGE->value);
            }

            foreach ($membersWhoContinues as $member) {

                $member->virtual_current_amount -= $group->target_amount_per_member;
                $member->save();
            }

            $membersLeft = $group->members()
                ->where('participation_status', GoalStatusEnum::INPROGRESS->value)
                ->get();

            if (($group->current_period + 1) > $group->number_of_period || count($membersLeft) == 0) {

                $group->status = SavingGroupStatusEnum::END->value;
            } else {

                $group->current_period++;
                $group->current_period_end_date = DateUtils::addPeriodes($group->contribution_frequency, $group->current_period_end_date);
            }

            $group->save();
        }
    }
}
