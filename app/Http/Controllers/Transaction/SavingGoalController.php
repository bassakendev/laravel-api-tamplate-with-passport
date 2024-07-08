<?php

namespace App\Http\Controllers\Transaction;

use App\Models\Loan;

use App\Models\SavingGoal;
use App\Utils\RequestUtils;
use Illuminate\Http\Request;
use App\Consts\ErrorMessages;
use App\Enums\GoalStatusEnum;
use App\Utils\TransactionUtils;
use App\Helper\CancelSavingGoal;
use App\Helper\TransactionHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\SavingGoalResource;

class SavingGoalController extends Controller
{

    public function list(Request $request)
    {
        $limit = $request->query('limit', RequestUtils::$limit);

        /** @var \App\Models\User */
        $user = auth()->user();

        $saving = SavingGoal::where('user_id', $user->id)->latest()->paginate($limit);

        return SavingGoalResource::collection($saving);
    }

    public function create(Request $request)
    {
        $minAmount = TransactionUtils::getMinAmount();
        $maxAmount = TransactionUtils::getMaxAmount();
        $internalFeesPer = TransactionUtils::getInternalFeesPer();

        $minPenaltyFeesPer = TransactionUtils::getMinPenaltyFeesPer();

        $request->validate([
            'target_amount' => 'required|decimal:1,5|min:' . $minAmount . '|max:' . $maxAmount,
            'current_amount' => 'required|decimal:1,5|min:' . $minAmount . '|max:' . $maxAmount,
            'description' => 'required|max:255|min:3|string',
            'deadline' => 'required|date_format:Y-m-d',
            'penalty_fees_per' => 'required|decimal:1,2|min:' . $minPenaltyFeesPer . '|max:1.00'
        ]);

        $initialDate = now()->addDays(2)->format('Y-m-d');

        abort_if($initialDate > $request->deadline, 422, ErrorMessages::$BAD_DATE);

        /** @var \App\Models\User */
        $user = auth()->user();

        abort_if($request->current_amount >= $request->target_amount, 400, ErrorMessages::$LOAN_INPUT_REQUEST_ERROR);

        $amount = $request->current_amount * $internalFeesPer + $request->current_amount;

        abort_if(!$user->wallet->check($amount), 402, ErrorMessages::$BALANCE_ERROR);

        $saving = TransactionHelper::makeSaving(
            $user,
            $request->current_amount,
            $request->target_amount,
            $request->deadline,
            $request->penalty_fees_per,
            $request->description
        );

        return response()->json($saving, 201);
    }

    public function details($id)
    {
        $saving = SavingGoal::where('id', $id)->first();
        abort_if(!$saving, 404, ErrorMessages::$SAVING_GOAL_MODEL_ERROR);

        return new SavingGoalResource($saving);
    }

    public function update(Request $request, $id)
    {
        $minAmount = TransactionUtils::getMinAmount();
        $maxAmount = TransactionUtils::getMaxAmount();
        $internalFeesPer = TransactionUtils::getInternalFeesPer();

        $request->validate([
            'amount' => 'required|decimal:1,5|min:' . $minAmount . '|max:' . $maxAmount,
        ]);

        /** @var \App\Models\User */
        $user = auth()->user();

        $amount = $request->amount * $internalFeesPer + $request->amount;

        abort_if(!$user->wallet->check($amount), 402, ErrorMessages::$BALANCE_ERROR);

        $saving = SavingGoal::where('id', $id)->where('status', GoalStatusEnum::INPROGRESS->value)->first();

        abort_if(!$saving, 404, ErrorMessages::$SAVING_GOAL_MODEL_ERROR);

        $data = TransactionHelper::updateSaving($user, $saving, $request->amount);

        return response()->json($data, 201);
    }

    public function cancel($id)
    {
        $saving = SavingGoal::where('id', $id)->where('status', GoalStatusEnum::INPROGRESS->value)->first();

        abort_if(!$saving, 404, ErrorMessages::$SAVING_GOAL_MODEL_ERROR);

        /** @var \App\Models\User */
        $user = auth()->user();

        $data = CancelSavingGoal::make($saving, $user, GoalStatusEnum::CANCELLED->value);

        return response()->json($data, 201);
    }

}
