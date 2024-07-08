<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionResource;
use App\Models\Transaction;

class UserDashboardController extends Controller
{
    public function common()
    {
        /** @var \App\Models\User */
        $user = auth()->user();

        $total_loan_value = $user->loanWallet->balance;
        $total_saving_value = $user->savingWallet->balance;
        $pubs = null;
        $last_10_transactions = Transaction::where('user_id', $user->id)->latest()->take(10)->get();

        $data = [
            'total_loan_value'  => $total_loan_value,
            'total_saving_value'  => $total_saving_value,
            'pubs'  => $pubs,
            'last_10_transactions'  => TransactionResource::collection($last_10_transactions),
        ];

        return response()->json($data);
    }
}
