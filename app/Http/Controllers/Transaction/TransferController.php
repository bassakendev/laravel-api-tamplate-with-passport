<?php

namespace App\Http\Controllers\Transaction;

use App\Models\User;
use App\Models\Transfer;
use App\Utils\RequestUtils;
use Illuminate\Http\Request;
use App\Consts\ErrorMessages;
use App\Utils\TransactionUtils;
use App\Helper\TransactionHelper;
use App\Enums\ActingWalletTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\TransferResource;

class TransferController extends Controller
{
    public function list(Request $request)
    {
        $limit = $request->query('limit', RequestUtils::$limit);

        /** @var \App\Models\User */
        $user = auth()->user();

        $loan = Transfer::where('user_id', $user->id)->latest()->paginate($limit);

        return TransferResource::collection($loan);
    }

    public function transferTo(Request $request)
    {
        $minAmount = TransactionUtils::getMinAmount();
        $maxAmount = TransactionUtils::getMaxTransferAmount();
        $types = implode(',', collect(ActingWalletTypeEnum::cases())->map(fn ($status) => $status->value)->toArray());
        $internalFeesPer = TransactionUtils::getInternalFeesPer();

        $request->validate([
            'amount' => 'required|decimal:1,5|min:' . $minAmount . '|max:' . $maxAmount,
            'referral_code' => 'required|string|size:10',
            'wallet_type' => 'required|string|in:' . $types,
        ]);

        /** @var \App\Models\User */
        $auth = auth()->user();

        $recipient = User::whereHas('referral', function ($referral) use ($request) {
            $referral->where('code', $request->referral_code);
        })->first();

        abort_if(!$recipient, 404, ErrorMessages::$USER_MODEL_ERROR);

        abort_if($recipient->id == $auth->id, 410, ErrorMessages::$TRANSFER_CONFLICT);

        $fees = $request->amount * $internalFeesPer;
        $amountToRemove = $request->amount + $fees;

        if ($request->wallet_type == ActingWalletTypeEnum::MAIN->value) {
            $isGoogBalance = $auth->wallet->check($amountToRemove);
        } else {
            $isGoogBalance = $auth->withdrawalWallet->check($amountToRemove);
        }

        abort_if(!$isGoogBalance, 402, ErrorMessages::$BALANCE_ERROR);

        $data = TransactionHelper::makeTransfer($auth, $recipient, $request->amount, $amountToRemove, $fees, $request->wallet_type);

        return response()->json($data, 201);

    }
}
