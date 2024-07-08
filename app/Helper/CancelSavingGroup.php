<?php

namespace App\Helper;

use App\Models\Wallet;
use App\Models\SavingGoal;
use App\Models\AdminWallet;
use App\Models\SavingGroup;
use App\Models\Transaction;
use App\Models\SavingWallet;
use App\Enums\GoalStatusEnum;
use App\Utils\TransactionUtils;
use App\Models\WithdrawalWallet;
use App\Models\SavingGroupMember;
use App\Models\TransactionWallet;
use App\Enums\SavingGroupTypeEnum;
use Illuminate\Support\Facades\DB;
use App\Enums\WalletOperationEnum;
use App\Enums\SavingGroupMemberSatusEnum;
use App\Http\Resources\TransactionResource;
use \Illuminate\Http\Resources\Json\AnonymousResourceCollection;

abstract class CancelSavingGroup
{

    /**
     * Cancel saving group participation
     *
     * @param SavingGroup $group
     * @param SavingGroupMember $member
     * @param string $status
     * @param string $type
     * @return TransactionResource
     */
    static public function make(SavingGroup $group, SavingGroupMember $member, string $status, string $type): TransactionResource
    {

        $penaltyFees = $group->penalty_fees_per;
        $adminWallet = AdminWallet::first();

        DB::beginTransaction();

        if ($type == SavingGroupTypeEnum::NORMAL->value) {

            $penalty = $member->current_amount * $penaltyFees;

            $member->user->savingWallet->move($member->current_amount);
            $member->user->withdrawalWallet->add($member->current_amount - $penalty);

            $adminWallet->add($penalty);

            $deposit_transaction_in_withdrawal_wallet = Transaction::create([
                'reference' => TransactionUtils::generateReference(),
                'user_id' => $member->user->id,
                'tx_type' => SavingGroup::class,
                'tx_id' => $group->id,
                'reason' => 'Cancellation with penalty of the savings group.',
                'amount' => $member->current_amount - $penalty,
                'fees' => 0,
            ]);

            TransactionWallet::create([
                'transaction_id' => $deposit_transaction_in_withdrawal_wallet->id,
                'wallet_type' => WithdrawalWallet::class,
                'wallet_id' => $member->user->withdrawalWallet->id,
                'operation' => WalletOperationEnum::ADD->value,
            ]);

            $withdrawal_transaction_in_saving_wallet = Transaction::create([
                'reference' => TransactionUtils::generateReference(),
                'user_id' => $member->user->id,
                'tx_type' => SavingGroup::class,
                'tx_id' => $group->id,
                'reason' => 'Cancellation with penalty of the savings group.',
                'amount' => $member->current_amount,
                'fees' => 0,
            ]);

            TransactionWallet::create([
                'transaction_id' => $withdrawal_transaction_in_saving_wallet->id,
                'wallet_type' => SavingWallet::class,
                'wallet_id' => $member->user->savingWallet->id,
            ]);
        } else {

            $member->user->savingWallet->move($member->current_amount);

            $withdrawal_transaction_in_saving_wallet = Transaction::create([
                'reference' => TransactionUtils::generateReference(),
                'user_id' => $member->user->id,
                'tx_type' => SavingGroup::class,
                'tx_id' => $group->id,
                'reason' => 'Cancellation with penalty of the savings group.',
                'amount' => $member->current_amount,
            ]);

            TransactionWallet::create([
                'transaction_id' => $withdrawal_transaction_in_saving_wallet->id,
                'wallet_type' => SavingWallet::class,
                'wallet_id' => $member->user->savingWallet->id,
            ]);

            if ($status == GoalStatusEnum::CANCELLED->value) {
                $members = $group->members()->where('id', '!=', $member->id)
                    ->where('participation_status', GoalStatusEnum::INPROGRESS->value)
                    ->get();
            } else {
                $members = $group->members()
                    ->where('participation_status', GoalStatusEnum::INPROGRESS->value)
                    ->where('virtual_current_amount', '>=', $group->target_amount_per_member)
                    ->get();
            }


            $nb_of_members = count($members);

            if ($nb_of_members > 0) {

                $amount_to_distribute = $member->current_amount / $nb_of_members;

                foreach ($members as $m) {

                    if ($amount_to_distribute >= ($group->target_amount_per_member * $group->number_of_period - $m->current_amount)) {
                        $amountToAdToAdminWallet = $amount_to_distribute - ($group->target_amount_per_member * $group->number_of_period - $m->current_amount);
                        $adminWallet->add($amountToAdToAdminWallet);
                        self::helperForOverflowAmount($group, $m);
                    } else {

                        $m->user->savingWallet->add($amount_to_distribute);

                        $m->current_amount += $amount_to_distribute;
                        $m->virtual_current_amount += $amount_to_distribute;

                        $m->save();

                        $deposit_transaction_in_saving_wallet = Transaction::create([
                            'reference' => TransactionUtils::generateReference(),
                            'user_id' => $m->user->id,
                            'tx_type' => SavingGroup::class,
                            'tx_id' => $group->id,
                            'reason' => 'Sharing a challenger\'s amount out of the race (challenge saving group).',
                            'amount' => $amount_to_distribute,
                            'fees' => 0,
                        ]);

                        TransactionWallet::create([
                            'transaction_id' => $deposit_transaction_in_saving_wallet->id,
                            'wallet_type' => SavingWallet::class,
                            'wallet_id' => $m->user->savingWallet->id,
                            'operation' => WalletOperationEnum::ADD->value,
                        ]);
                    }
                }
            } else {
                $adminWallet->add($member->current_amount);
            }
        }

        $group->total_members = ($status == GoalStatusEnum::CANCELLED->value) ? $group->total_members-- : $group->total_members;

        $member->participation_status = $status;
        $member->status = ($status == GoalStatusEnum::CANCELLED->value) ? SavingGroupMemberSatusEnum::LEFT->value : $member->status;

        $group->save();
        $member->save();

        DB::commit();

        return new TransactionResource($withdrawal_transaction_in_saving_wallet);
    }


    /**
     * This function is used for payments in saving groups, exceeding the expected amounts.
     *
     * @param SavingGroup $group
     * @param SavingGroupMember $member
     * @param bool $isCancel
     * @return void
     */
    static public function helperForOverflowAmount(SavingGroup $group, SavingGroupMember $member, bool $isCancel = true)
    {
        $usingFeesPer = TransactionUtils::getSavingGroupUsingFeesPer();
        $internalFeesPer = TransactionUtils::getInternalFeesPer();

        $feesToremove = $group->target_amount_per_member * $group->number_of_period * $usingFeesPer;
        $amount_to_add_in_withdrawal_wallet = $group->target_amount_per_member * $group->number_of_period - $feesToremove;

        $amountToPaidwithoutFees = $group->target_amount_per_member * $group->number_of_period - $member->current_amount;

        $fees = $amountToPaidwithoutFees * $internalFeesPer;
        $amountToPaidwithFees = $fees + $amountToPaidwithoutFees;

        if (!$isCancel) {
            $withdrawal_transaction_in_main_wallet = Transaction::create([
                'reference' => TransactionUtils::generateReference(),
                'user_id' => $member->user->id,
                'tx_type' => SavingGroup::class,
                'tx_id' => $group->id,
                'reason' => 'Group: ' . $group->name,
                'amount' => $amountToPaidwithoutFees,
                'fees' => $fees,
            ]);

            TransactionWallet::create([
                'transaction_id' => $withdrawal_transaction_in_main_wallet->id,
                'wallet_type' => Wallet::class,
                'wallet_id' => $member->user->wallet->id,
            ]);

            $member->user->wallet->move($amountToPaidwithFees);
        }

        $withdrawal_transaction_in_saving_wallet = Transaction::create([
            'reference' => TransactionUtils::generateReference(),
            'user_id' => $member->user->id,
            'tx_type' => SavingGoal::class,
            'tx_id' => $group->id,
            'reason' => 'Group: ' . $group->name,
            'amount' => $member->current_amount,
        ]);

        TransactionWallet::create([
            'transaction_id' => $withdrawal_transaction_in_saving_wallet->id,
            'wallet_type' => SavingWallet::class,
            'wallet_id' => $member->user->savingWallet->id,
        ]);

        $deposit_transaction_in_withdrawal_wallet = Transaction::create([
            'reference' => TransactionUtils::generateReference(),
            'user_id' => $member->user->id,
            'tx_type' => SavingGoal::class,
            'tx_id' => $group->id,
            'reason' => 'Group: ' . $group->name,
            'amount' => $amount_to_add_in_withdrawal_wallet,
        ]);

        TransactionWallet::create([
            'transaction_id' => $deposit_transaction_in_withdrawal_wallet->id,
            'wallet_type' => WithdrawalWallet::class,
            'wallet_id' => $member->user->withdrawalWallet->id,
            'operation' => WalletOperationEnum::ADD->value,
        ]);

        $member->user->savingWallet->move($member->current_amount);

        $member->user->withdrawalWallet->add($amount_to_add_in_withdrawal_wallet);
        $group->total_amount += $amountToPaidwithoutFees;

        $member->current_amount = $group->target_amount_per_member * $group->number_of_period;
        $member->virtual_current_amount = $group->target_amount_per_member * $group->number_of_period;
        $member->participation_status = GoalStatusEnum::REACHED->value;

        $member->save();
        $group->save();

        $adminWallet = AdminWallet::first();
        // dd($fees);
        $adminWallet->add($fees);

        return $withdrawal_transaction_in_saving_wallet;
    }
}
