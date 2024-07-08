<?php

namespace App\Http\Controllers\Transaction;

use App\Utils\RequestUtils;
use Illuminate\Http\Request;
use App\Helper\TransactionHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\DepositResource;
use App\Models\Deposit;
use App\Services\ApiTransactionService;

class DepositController extends Controller
{
    public function list(Request $request)
    {
        $limit = $request->query('limit', RequestUtils::$limit);

        /** @var \App\Models\User */
        $user = auth()->user();

        $deposit = Deposit::where('user_id', $user->id)->latest()->paginate($limit);

        return DepositResource::collection($deposit);
    }

    public function deposit(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'amount' => 'required|decimal:1,5|min:5',
        ]);

        /** @var \App\Models\User */
        $user = auth()->user();

        $transaction = new ApiTransactionService;

        $res = $transaction->deposit(
            $request->amount,
            $request->phone,
            'Deposit'
        );

        unset($transaction); //delete instance reference.

        if ($res['is_error']) {

            response()->json(['message' => $res['message']], 402);
        }

        $data = TransactionHelper::makeDeposit($user, $request->amount, $res['reference'], $res['external_reference']);

        return response()->json($data, 201);
    }
}
