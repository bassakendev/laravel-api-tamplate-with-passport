<?php

namespace App\Helper;

use App\Models\Loan;
use App\Models\User;
use App\Models\LoanWallet;
use App\Models\SavingGoal;
use App\Models\AdminWallet;
use App\Models\Transaction;
use App\Models\SavingWallet;
use App\Enums\GoalStatusEnum;
use App\Enums\LoanStatusEnum;
use App\Models\AdminLoanWallet;
use App\Utils\TransactionUtils;
use App\Models\WithdrawalWallet;
use App\Models\TransactionWallet;
use App\Enums\WalletOperationEnum;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\TransactionResource;

abstract class CancelSavingGoal
{

    /**
     * Cancel saving goal
     *
     * @param SavingGoal $saving
     * @param User $user
     * @param string $status
     * @return TransactionResource
     */
    static public function make(SavingGoal $saving, User $user, string $status): TransactionResource
    {

        $penaltyFees = $saving->penalty_fees_per;

        DB::beginTransaction();

        $penalty = $saving->current_amount * $penaltyFees;

        if ($saving->loan_id != null && $saving->loan->status == LoanStatusEnum::NOT_REFUNDED->value) {

            $amountToRemove = $saving->loan->amount + $saving->loan->interest;

            $user->savingWallet->move($saving->current_amount);
            $user->loanWallet->move($saving->loan->amount);
            $user->withdrawalWallet->add($saving->current_amount - $amountToRemove);

            $adminWallet = AdminWallet::first();
            $adminLoanWallet = AdminLoanWallet::first();

            $adminWallet->add($saving->loan->interest);
            $adminLoanWallet->move($saving->loan->amount);

            $saving->status = $status;
            $saving->loan->status = LoanStatusEnum::REFUNDED->value;
            $saving->loan->amount_refunded = $saving->loan->amount + $saving->loan->interest;

            $saving->save();
            $saving->loan->save();

            $withdrawal_transaction_in_saving_wallet = Transaction::create([
                'reference' => TransactionUtils::generateReference(),
                'user_id' => $user->id,
                'tx_type' => SavingGoal::class,
                'tx_id' => $saving->id,
                'reason' => 'Cancellation with penalty of the savings goal.',
                'amount' => $saving->current_amount,
            ]);

            TransactionWallet::create([
                'transaction_id' => $withdrawal_transaction_in_saving_wallet->id,
                'wallet_type' => SavingWallet::class,
                'wallet_id' => $user->loanWallet->id,
            ]);

            $withdrawal_transaction_in_loan_wallet = Transaction::create([
                'reference' => TransactionUtils::generateReference(),
                'user_id' => $user->id,
                'tx_type' => Loan::class,
                'tx_id' => $saving->loan->id,
                'reason' => 'Refund loan.',
                'amount' => $saving->loan->amount,
            ]);

            TransactionWallet::create([
                'transaction_id' => $withdrawal_transaction_in_loan_wallet->id,
                'wallet_type' => LoanWallet::class,
                'wallet_id' => $user->withdrawalWallet->id,
            ]);

            $deposit_transaction_in_withdrawal_wallet = Transaction::create([
                'reference' => TransactionUtils::generateReference(),
                'user_id' => $user->id,
                'tx_type' => SavingGoal::class,
                'tx_id' => $saving->loan->id,
                'reason' => 'Cancellation with penalty of the savings goal.',
                'amount' => $saving->current_amount - $amountToRemove,
            ]);

            TransactionWallet::create([
                'transaction_id' => $deposit_transaction_in_withdrawal_wallet->id,
                'wallet_type' => WithdrawalWallet::class,
                'wallet_id' => $user->withdrawalWallet->id,
                'operation' => WalletOperationEnum::ADD->value,
            ]);
        } else {

            $user->savingWallet->move($saving->current_amount);
            $user->withdrawalWallet->add($saving->current_amount - $penalty);

            $adminWallet = AdminWallet::first();
            $adminWallet->add($penalty);

            $deposit_transaction_in_withdrawal_wallet = Transaction::create([
                'reference' => TransactionUtils::generateReference(),
                'user_id' => $user->id,
                'tx_type' => SavingGoal::class,
                'tx_id' => $saving->id,
                'reason' => 'Cancellation with penalty of the savings goal.',
                'amount' => $saving->current_amount - $penalty,
                'fees' => 0,
            ]);

            TransactionWallet::create([
                'transaction_id' => $deposit_transaction_in_withdrawal_wallet->id,
                'wallet_type' => WithdrawalWallet::class,
                'wallet_id' => $user->withdrawalWallet->id,
                'operation' => WalletOperationEnum::ADD->value,
            ]);
        }

        $withdrawal_transaction_in_saving_wallet = Transaction::create([
            'reference' => TransactionUtils::generateReference(),
            'user_id' => $user->id,
            'tx_type' => SavingGoal::class,
            'tx_id' => $saving->id,
            'reason' => 'Cancellation with penalty of the savings goal.',
            'amount' => $saving->current_amount,
            'fees' => 0,
        ]);

        TransactionWallet::create([
            'transaction_id' => $withdrawal_transaction_in_saving_wallet->id,
            'wallet_type' => SavingWallet::class,
            'wallet_id' => $user->savingWallet->id,
        ]);

        $saving->status = GoalStatusEnum::CANCELLED->value;
        $saving->save();

        DB::commit();

        return new TransactionResource(
            $withdrawal_transaction_in_saving_wallet
        );
    }
}
