<?php

namespace App\Http\Controllers\transaction;

use App\Models\User;
use App\Utils\DateUtils;
use App\Models\SavingGroup;
use App\Utils\RequestUtils;
use Illuminate\Http\Request;
use App\Consts\ErrorMessages;
use App\Enums\GoalStatusEnum;
use App\Utils\TransactionUtils;
use App\Helper\CancelSavingGroup;
use App\Helper\TransactionHelper;
use App\Models\SavingGroupMember;
use App\Enums\SavingGroupTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\SavingGroupResource;
use App\Enums\SavingGroupContributionFrequencyEnum;

class SavingGroupController extends Controller
{
    public function list(Request $request)
    {
        $limit = $request->query('limit', RequestUtils::$limit);

        /** @var \App\Models\User */
        $user = auth()->user();

        $ids = SavingGroupMember::where('user_id', $user->id)->pluck('saving_group_id');
        $groups = SavingGroup::whereIn('id', $ids)->latest()->paginate($limit);

        return SavingGroupResource::collection($groups);
    }

    public function details($groupId)
    {
        $group = SavingGroup::find($groupId);
        abort_if(!$group, 404, ErrorMessages::$USER_OR_GROUP_MODEL_ERROR);

        return new SavingGroupResource($group);
    }

    public function newNormalSavingGroup(Request $request)
    {
        $min_amount = TransactionUtils::getMinAmount();
        $max_amount = TransactionUtils::getMaxAmount();
        $min_penalty_fees_per = TransactionUtils::getMinPenaltyFeesPer();

        $request->validate([
            'name' => 'required|max:100|min:3|string',
            'description' => 'required|max:255',
            'deadline' => 'required|date_format:Y-m-d',
            'target_amount_per_member' => 'required|decimal:1,5|min:' . $min_amount . '|max:' . $max_amount,
            'penalty_fees_per' => 'nullable|decimal:1,5|min:' . $min_penalty_fees_per,
        ]);

        $initialDate = now()->addDays(2)->format('Y-m-d');

        abort_if($initialDate > $request->deadline, 422, ErrorMessages::$BAD_DATE);

        /** @var \App\Models\User */
        $user = auth()->user();
        $internalFeesPer = TransactionUtils::getInternalFeesPer();

        $fees = $min_amount * $internalFeesPer;
        $amountToRemove = $min_amount + $fees;
        $isGoodBalance = $user->wallet->check($amountToRemove);

        abort_if(!$isGoodBalance, 402, ErrorMessages::$MEMBER_BALANCE_ERROR);

        $group = SavingGroup::create([
            'admin_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'deadline' => DateUtils::stringToDateTime($request->deadline),
            'target_amount_per_member' => $request->target_amount_per_member,
            'penalty_fees_per' => $request->penalty_fees_per ?? $min_penalty_fees_per,
            'total_members' => 1,
        ]);

        TransactionHelper::addMemberToSavingGroup($user, $group, $amountToRemove, $fees, true);

        $data = new SavingGroupResource($group);

        return response()->json($data, 201);
    }

    public function newChallengeSavingGroup(Request $request)
    {
        $min_amount = TransactionUtils::getMinAmount();
        $max_amount = TransactionUtils::getMaxAmount();
        $min_penalty_fees_per = TransactionUtils::getMinPenaltyFeesPer();
        $frequencies = implode(',', collect(SavingGroupContributionFrequencyEnum::cases())->map(fn ($status) => $status->value)->toArray());

        $request->validate([
            'name' => 'required|max:100|min:3|string',
            'description' => 'required|max:255',
            'number_of_period' => 'required|integer|min:1',
            'contribution_frequency' => 'required|in:' . $frequencies,
            'admission_fees' => 'required|decimal:1,5|min:' . $min_amount . '|max:' . $max_amount,
            'target_amount_per_member' => 'required|decimal:1,5|min:' . $min_amount . '|max:' . $max_amount,
            'penalty_fees_per' => 'nullable|decimal:1,5|min:' . $min_penalty_fees_per,
        ]);

        /** @var \App\Models\User */
        $user = auth()->user();
        $internalFeesPer = TransactionUtils::getInternalFeesPer();
        $admission_fees = $request->admission_fees;

        $fees = $admission_fees * $internalFeesPer;
        $amountToRemove = $admission_fees + $fees;
        $isGoodBalance = $user->wallet->check($amountToRemove);

        abort_if(!$isGoodBalance, 402, ErrorMessages::$MEMBER_BALANCE_ERROR);

        $group = SavingGroup::create([
            'admin_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'deadline' => DateUtils::sortFutureDate($request->number_of_period, $request->contribution_frequency),
            'number_of_period' => $request->number_of_period,
            'target_amount_per_member' => $request->target_amount_per_member,
            'penalty_fees_per' => $request->penalty_fees_per ?? $min_penalty_fees_per,
            'total_members' => 1,
            'admission_fees' => $request->admission_fees,
            'contribution_frequency' => $request->contribution_frequency,
            'type' => SavingGroupTypeEnum::CHALLENGE->value,
            'current_period_end_date' => DateUtils::sortFutureDate(1, $request->contribution_frequency)
        ]);

        TransactionHelper::addMemberToSavingGroup($user, $group, $amountToRemove, $fees, true, $request->admission_fees);

        $data = new SavingGroupResource($group);

        return response()->json($data, 201);
    }

    public function addMember($userToAddId, $groupId)
    {
        /** @var \App\Models\User */
        $auth = auth()->user();

        $user = User::find($userToAddId);
        $group = SavingGroup::find($groupId);
        $member = SavingGroupMember::where('user_id', $userToAddId)->where('saving_group_id', $groupId)->first();
        $min_amount = TransactionUtils::getMinAmount();
        $internalFeesPer = TransactionUtils::getInternalFeesPer();

        abort_if(!$user || !$group, 404, ErrorMessages::$USER_OR_GROUP_MODEL_ERROR);

        abort_if($auth->id == $user->id, 410, ErrorMessages::$ADD_MEMBER_CONFLICT);

        abort_if($member, 410, ErrorMessages::$ERROR_MEMBER);

        $is_normal = ($group->type == SavingGroupTypeEnum::NORMAL->value);

        $amount = $is_normal ? $min_amount : $group->admission_fees;
        $fees = $amount * $internalFeesPer;
        $amountToRemove = $amount + $fees;

        $admission = $is_normal ? null : $group->admission_fees;

        $isGoodBalance = $user->wallet->check($amountToRemove);

        abort_if(!$isGoodBalance, 402, ErrorMessages::$MEMBER_BALANCE_ERROR);

        $data = TransactionHelper::addMemberToSavingGroup($user, $group, $amountToRemove, $fees, false, $admission);

        return response()->json($data, 201);
    }

    public function cancelNormalParticipation($groupId)
    {
        /** @var \App\Models\User */
        $user = auth()->user();

        $member = SavingGroupMember::where('user_id', $user->id)->where('saving_group_id', $groupId)->first();
        $group = SavingGroup::find($groupId);

        abort_if(!$member || !$group, 404, ErrorMessages::$USER_OR_GROUP_MODEL_ERROR);

        $data = CancelSavingGroup::make($group, $member, GoalStatusEnum::CANCELLED->value, SavingGroupTypeEnum::NORMAL->value);

        return response()->json($data, 201);
    }

    public function cancelCallengeParticipation($groupId)
    {
        /** @var \App\Models\User */
        $user = auth()->user();

        $member = SavingGroupMember::where('user_id', $user->id)->where('saving_group_id', $groupId)->first();
        $group = SavingGroup::find($groupId);

        abort_if(!$member || !$group, 404, ErrorMessages::$USER_OR_GROUP_MODEL_ERROR);

        $data = CancelSavingGroup::make($group, $member, GoalStatusEnum::CANCELLED->value, SavingGroupTypeEnum::CHALLENGE->value);

        return response()->json($data, 201);
    }


    public function upgradeContribution(Request $request, $groupId)
    {
        $min_amount = TransactionUtils::getMinAmount();
        $max_amount = TransactionUtils::getMaxAmount();
        $internalFeesPer = TransactionUtils::getInternalFeesPer();

        $request->validate([
            'amount' => 'required|decimal:1,5|min:' . $min_amount . '|max:' . $max_amount,
        ]);

        /** @var \App\Models\User */
        $user = auth()->user();

        $member = SavingGroupMember::where('user_id', $user->id)->where('saving_group_id', $groupId)->first();
        $group = SavingGroup::find($groupId);

        abort_if(!$member || !$group, 404, ErrorMessages::$USER_OR_GROUP_MODEL_ERROR);

        $fees = $request->amount * $internalFeesPer;
        $amountToRemove = $min_amount + $fees;
        $isGoodBalance = $user->wallet->check($amountToRemove);

        abort_if(!$isGoodBalance, 402, ErrorMessages::$MEMBER_BALANCE_ERROR);

        if ($group->type == SavingGroupTypeEnum::CHALLENGE->value) {

            $data = TransactionHelper::upgradeChallengeSavingGroupContribution($group, $member, $request->amount);
        } else {

            $data = TransactionHelper::upgradeNormalSavingGroupContribution($group, $member, $request->amount);
        }

        return response()->json($data, 201);
    }

    public function joinChallengeGroup($groupId)
    {
        /** @var \App\Models\User */
        $user = auth()->user();

        $group = SavingGroup::find($groupId);

        $member = SavingGroupMember::where('user_id', $user->id)
            ->where('saving_group_id', $groupId)
            ->first();

        $internalFeesPer = TransactionUtils::getInternalFeesPer();

        abort_if(!$group, 404, ErrorMessages::$USER_OR_GROUP_MODEL_ERROR);

        abort_if($group->type == SavingGroupTypeEnum::NORMAL->value, 410, ErrorMessages::$GROUP_INSERTION_ERROR);

        abort_if($member, 410, ErrorMessages::$ERROR_MEMBER);

        $amount = $group->admission_fees;
        $fees = $amount * $internalFeesPer;
        $amountToRemove = $amount + $fees;

        $admission = $group->admission_fees;

        $isGoodBalance = $user->wallet->check($amountToRemove);

        abort_if(!$isGoodBalance, 402, ErrorMessages::$MEMBER_BALANCE_ERROR);

        $data = TransactionHelper::addMemberToSavingGroup($user, $group, $amountToRemove, $fees, false, $admission);

        return response()->json($data, 201);
    }

}
