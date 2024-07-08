<?php

namespace App\Http\Controllers\Transaction;

use App\Models\Loan;

use App\Models\SavingGoal;
use App\Utils\RequestUtils;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Consts\ErrorMessages;
use App\Enums\GoalStatusEnum;
use App\Enums\LoanStatusEnum;
use App\Utils\TransactionUtils;
use App\Helper\TransactionHelper;
use App\Enums\NotificationTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\LoanResource;
use App\Consts\AdminNotificationMessage;

class LoanController extends Controller
{

    public function list(Request $request)
    {
        $limit = $request->query('limit', RequestUtils::$limit);

        /** @var \App\Models\User */
        $user = auth()->user();

        $loan = Loan::where('user_id', $user->id)->latest()->paginate($limit);

        return LoanResource::collection($loan);
    }

    public function details($id)
    {
        $loan = Loan::where('id', $id)->first();
        abort_if(!$loan, 404, ErrorMessages::$LOAN_MODEL_ERROR);

        return new LoanResource($loan);
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

        $loan = Loan::where('id', $id)->where('status', LoanStatusEnum::NOT_REFUNDED->value)->first();

        abort_if(!$loan, 404, ErrorMessages::$LOAN_MODEL_ERROR);

        $data = TransactionHelper::updateLoan($user, $loan, $request->amount);

        return response()->json($data, 201);
    }

    public function makeLoanWithSavingGoalAsCollateral(Request $request, $id)
    {
        $minLoan = TransactionUtils::getMinLoan();
        $maxLoan = TransactionUtils::getMaxLoan();

        $loanFeesPer = TransactionUtils::getLoanFeesPer();

        $request->validate(['amount' => 'required|decimal:1,5|min:' . $minLoan . '|max:' . $maxLoan,
            'reason' => 'required|string|max:255',
        ]);

        $saving = SavingGoal::where('id', $id)->where('status', GoalStatusEnum::INPROGRESS->value)->first();

        abort_if(!$saving, 404, ErrorMessages::$LOAN_MODEL_ERROR);

        /** @var \App\Models\User */
        $user = auth()->user();

        abort_if(
            $maxLoan <  ($user->loanWallet->balance + $request->amount),
            402,
            ErrorMessages::$LOAN_VALUE_ERROR
        );

        abort_if(
            $saving->current_amount <= ($request->amount + $request->amount * $loanFeesPer),
            402,
            ErrorMessages::$INSUFFICIENT_PLEDGE
        );

        abort_if(
            $saving->loan != null,
            402,
            ErrorMessages::$PLEDGE_ALREADY_USED
        );

        $data = TransactionHelper::makeLoan($request->amount, $saving, $user, $request->reason);

        Notification::create([
            'content' => AdminNotificationMessage::$NEW_LOAN,
            'type' => NotificationTypeEnum::INFO->value,
        ]);

        return response()->json($data, 201);
    }
}
