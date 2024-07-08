<?php

namespace App\Helper;

use App\Models\Loan;
use App\Models\Wallet;
use App\Models\Deposit;
use App\Models\Transfer;
use App\Utils\DateUtils;
use App\Models\LoanWallet;
use App\Models\SavingGoal;
use App\Models\AdminWallet;
use App\Models\SavingGroup;
use App\Models\Transaction;
use App\Models\SavingWallet;
use App\Enums\GoalStatusEnum;
use App\Enums\LoanStatusEnum;
use App\Models\AdminLoanWallet;
use App\Utils\TransactionUtils;
use App\Models\WithdrawalWallet;
use App\Helper\CancelSavingGroup;
use App\Models\SavingGroupMember;
use App\Models\TransactionWallet;
use App\Enums\WalletOperationEnum;
use Illuminate\Support\Facades\DB;
use App\Enums\ActingWalletTypeEnum;
use App\Enums\TransactionStatusEnum;
use App\Http\Resources\LoanResource;
use App\Http\Resources\DepositResource;
use App\Services\ApiTransactionService;
use App\Http\Resources\TransferResource;
use App\Enums\SavingGroupMemberSatusEnum;
use App\Http\Resources\SavingGoalResource;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\SavingGroupMemberResource;

/**
 * All methods requiring the transaction API can be found here.
 */
abstract class TransactionHelper
{

    /**
     * Create Saving Goal
     *
     * @param \App\Models\User $user
     * @param float $amount
     * @param float $targetAmount
     * @param string $deadline
     * @param float $penaltyFeesPer
     * @param string|null $reason
     * @return SavingGoalResource
     *
     *  @group Saving Goal
     */
    static public function makeSaving(\App\Models\User $user, float $amount, float $targetAmount, string $deadline, float $penaltyFeesPer, string $reason = null): SavingGoalResource
    {
        $internalFeesPer = TransactionUtils::getInternalFeesPer();

        DB::beginTransaction();

        $fees = $amount * $internalFeesPer;

        $user->wallet->move($fees + $amount);
        $user->savingWallet->add($amount);

        $adminWallet = AdminWallet::first();
        $adminWallet->add($fees);

        $saving = SavingGoal::create([
            'user_id' => $user->id,
            'target_amount' => $targetAmount,
            'current_amount' => $amount,
            'description' => $reason,
            'penalty_fees_per' => $penaltyFeesPer,
            'deadline' => DateUtils::stringToDateTime($deadline)
        ]);

        $deposit_transaction_in_saving_wallet = Transaction::create([
            'user_id' => $user->id,
            'tx_type' => SavingGoal::class,
            'tx_id' => $saving->id,
            'reference' => TransactionUtils::generateReference(),
            'reason' => $reason,
            'amount' => $amount,
            'fees' => $fees,
        ]);

        TransactionWallet::create([
            'transaction_id' => $deposit_transaction_in_saving_wallet->id,
            'wallet_type' => SavingWallet::class,
            'wallet_id' => $user->savingWallet->id,
            'operation' => WalletOperationEnum::ADD->value,
        ]);

        $withdrawal_transaction_in_main_wallet = Transaction::create([
            'reference' => TransactionUtils::generateReference(),
            'user_id' => $user->id,
            'tx_type' => SavingGoal::class,
            'tx_id' => $saving->id,
            'reason' => $reason,
            'amount' => $amount + $fees,
            'fees' => $fees,
        ]);

        TransactionWallet::create([
            'transaction_id' => $withdrawal_transaction_in_main_wallet->id,
            'wallet_type' => Wallet::class,
            'wallet_id' => $user->wallet->id,
        ]);

        DB::commit();

        return new SavingGoalResource($saving);
    }


    /**
     * Update or Progress Saving Goal.
     *
     * @param \App\Models\User $user
     * @param SavingGoal $saving
     * @param float $amount
     * @return TransactionResource
     *
     * @group Saving Goal
     */
    static public function updateSaving(\App\Models\User $user, SavingGoal $saving, float $amount): TransactionResource
    {
        $internalFeesPer = TransactionUtils::getInternalFeesPer();

        DB::beginTransaction();

        if (
            $amount >= ($saving->target_amount - $saving->current_amount)
        ) {

            $heHasLoan = $saving->loan_id != null && ($saving->loan->status == LoanStatusEnum::NOT_REFUNDED->value);
            $amount_to_add_in_withdrawal_wallet = $heHasLoan ? $saving->target_amount - ($saving->loan->amount + $saving->loan->interest) : $saving->target_amount;

            $fees = ($saving->target_amount - $saving->current_amount) * $internalFeesPer;
            $amountValue = $fees + ($saving->target_amount - $saving->current_amount);

            $withdrawal_transaction_in_main_wallet = Transaction::create([
                'reference' => TransactionUtils::generateReference(),
                'user_id' => $user->id,
                'tx_type' => SavingGoal::class,
                'tx_id' => $saving->id,
                'reason' => $saving->description,
                'amount' => $saving->target_amount - $saving->current_amount,
                'fees' => $fees,
            ]);

            TransactionWallet::create([
                'transaction_id' => $withdrawal_transaction_in_main_wallet->id,
                'wallet_type' => Wallet::class,
                'wallet_id' => $user->wallet->id,
            ]);

            $withdrawal_transaction_in_saving_wallet = Transaction::create([
                'reference' => TransactionUtils::generateReference(),
                'user_id' => $user->id,
                'tx_type' => SavingGoal::class,
                'tx_id' => $saving->id,
                'reason' => $saving->description,
                'amount' => $saving->current_amount,
            ]);

            TransactionWallet::create([
                'transaction_id' => $withdrawal_transaction_in_saving_wallet->id,
                'wallet_type' => SavingWallet::class,
                'wallet_id' => $user->savingWallet->id,
            ]);

            $deposit_transaction_in_withdrawal_wallet = Transaction::create([
                'reference' => TransactionUtils::generateReference(),
                'user_id' => $user->id,
                'tx_type' => SavingGoal::class,
                'tx_id' => $saving->id,
                'reason' => $saving->description,
                'amount' => $amount_to_add_in_withdrawal_wallet,
            ]);

            TransactionWallet::create([
                'transaction_id' => $deposit_transaction_in_withdrawal_wallet->id,
                'wallet_type' => WithdrawalWallet::class,
                'wallet_id' => $user->withdrawalWallet->id,
                'operation' => WalletOperationEnum::ADD->value,
            ]);

            $user->wallet->move($amountValue);
            $user->savingWallet->move($saving->current_amount);

            $user->withdrawalWallet->add($amount_to_add_in_withdrawal_wallet);

            if ($heHasLoan) {
                $withdrawal_transaction_in_loan_wallet = Transaction::create([
                    'reference' => TransactionUtils::generateReference(),
                    'user_id' => $user->id,
                    'tx_type' => Loan::class,
                    'tx_id' => $saving->loan->id,
                    'reason' => 'Refund loan',
                    'amount' => $saving->loan->amount,
                ]);

                TransactionWallet::create([
                    'transaction_id' => $withdrawal_transaction_in_loan_wallet->id,
                    'wallet_type' => LoanWallet::class,
                    'wallet_id' => $user->loanWallet->id,
                    'operation' => WalletOperationEnum::REMOVE->value,
                ]);

                $fees += $saving->loan->interest;
            }

            $saving->current_amount = $saving->target_amount;
            $saving->status = GoalStatusEnum::REACHED->value;
            $saving->save();
        } else {

            $fees = $amount * $internalFeesPer;
            $amountValue = $fees + $amount;

            $withdrawal_transaction_in_main_wallet = Transaction::create([
                'reference' => TransactionUtils::generateReference(),
                'user_id' => $user->id,
                'tx_type' => SavingGoal::class,
                'tx_id' => $saving->id,
                'reason' => $saving->description,
                'amount' => $amountValue - $fees,
                'fees' => $fees,
            ]);

            TransactionWallet::create([
                'transaction_id' => $withdrawal_transaction_in_main_wallet->id,
                'wallet_type' => Wallet::class,
                'wallet_id' => $user->wallet->id,
            ]);

            $deposit_transaction_in_saving_wallet = Transaction::create([
                'reference' => TransactionUtils::generateReference(),
                'user_id' => $user->id,
                'tx_type' => SavingGoal::class,
                'tx_id' => $saving->id,
                'reason' => $saving->description,
                'amount' => $amountValue - $fees,
            ]);

            TransactionWallet::create([
                'transaction_id' => $deposit_transaction_in_saving_wallet->id,
                'wallet_type' => SavingWallet::class,
                'wallet_id' => $user->savingWallet->id,
                'operation' => WalletOperationEnum::ADD->value,
            ]);

            $user->wallet->move($amountValue);
            $user->savingWallet->add($amount);
            $saving->current_amount += $amount;
            $saving->save();
        }

        $adminWallet = AdminWallet::first();
        $adminWallet->add($fees);


        DB::commit();

        return new TransactionResource($withdrawal_transaction_in_main_wallet);
    }

    /**
     * Create Loan
     *
     * @param float $amountToLend
     * @param SavingGoal $saving
     * @param \App\Models\User $user
     * @param string $reason
     * @return LoanResource
     *
     * @group Loan
     */
    static public function makeLoan(float $amountToLend, SavingGoal $saving, \App\Models\User $user, string $reason): LoanResource
    {

        $loanFeesPer = TransactionUtils::getLoanFeesPer();
        $adminLoanWallet = AdminLoanWallet::first();

        DB::beginTransaction();

        $adminLoanWallet->add($amountToLend);

        $user->loanWallet->add($amountToLend);

        $loan = Loan::create([
            'user_id' => $user->id,
            // 'due_date' => today()->addMonths($dueNumberOfMonths),
            'reason' => $reason,
            'amount' => $amountToLend,
            'interest' => $amountToLend * $loanFeesPer,
        ]);

        $saving->loan_id = $loan->id;
        $saving->save();

        $deposit_transaction_in_loan_wallet = Transaction::create([
            'reference' => TransactionUtils::generateReference(),
            'user_id' => $user->id,
            'tx_type' => Loan::class,
            'tx_id' => $loan->id,
            'reason' => $reason,
            'amount' => $amountToLend,
        ]);

        TransactionWallet::create([
            'transaction_id' => $deposit_transaction_in_loan_wallet->id,
            'wallet_type' => LoanWallet::class,
            'wallet_id' => $user->loanWallet->id,
            'status' => WalletOperationEnum::ADD->value,
        ]);

        DB::commit();

        return new LoanResource($loan);
    }

    /**
     * Refunded loan
     *
     * @param \App\Models\User $user
     * @param Loan $loan
     * @param float $amount
     * @return TransactionResource
     *
     * @group Loan
     */
    static public function updateLoan(\App\Models\User $user, Loan $loan, float $amount): TransactionResource
    {
        DB::beginTransaction();

        $amountToRepay = $loan->amount + $loan->interest;

        if (
            $amount >= ($amountToRepay - $loan->amount_refunded)
        ) {

            $withdrawal_transaction_in_loan_wallet = Transaction::create([
                'reference' => TransactionUtils::generateReference(),
                'user_id' => $user->id,
                'tx_type' => Loan::class,
                'tx_id' => $loan->id,
                'reason' => $loan->reason,
                'amount' => $loan->amount,
            ]);

            TransactionWallet::create([
                'transaction_id' => $withdrawal_transaction_in_loan_wallet->id,
                'wallet_type' => LoanWallet::class,
                'wallet_id' => $user->loanWallet->id,
            ]);

            $withdrawal_transaction_in_main_wallet = Transaction::create([
                'reference' => TransactionUtils::generateReference(),
                'user_id' => $user->id,
                'tx_type' => Loan::class,
                'tx_id' => $loan->id,
                'reason' => $loan->reason,
                'amount' => $amountToRepay - $loan->amount_refunded,
            ]);

            TransactionWallet::create([
                'transaction_id' => $withdrawal_transaction_in_main_wallet->id,
                'wallet_type' => Wallet::class,
                'wallet_id' => $user->wallet->id,
            ]);

            $user->wallet->move($amountToRepay - $loan->amount_refunded);
            $user->loanWallet->move($loan->amount);

            $loan->status = LoanStatusEnum::REFUNDED->value;
            $loan->amount_refunded = $amountToRepay;
            $loan->save();

            $adminWallet = AdminWallet::first();
            $adminLoanWallet = AdminLoanWallet::first();

            $adminWallet->add($loan->interest);
            $adminLoanWallet->move($loan->amount);
        } else {

            $withdrawal_transaction_in_loan_wallet = Transaction::create([
                'reference' => TransactionUtils::generateReference(),
                'user_id' => $user->id,
                'tx_type' => Loan::class,
                'tx_id' => $loan->id,
                'reason' => $loan->reason,
                'amount' => $amount,
            ]);

            TransactionWallet::create([
                'transaction_id' => $withdrawal_transaction_in_loan_wallet->id,
                'wallet_type' => Wallet::class,
                'wallet_id' => $user->wallet->id,
            ]);

            $withdrawal_transaction_in_main_wallet = Transaction::create([
                'reference' => TransactionUtils::generateReference(),
                'user_id' => $user->id,
                'tx_type' => Loan::class,
                'tx_id' => $loan->id,
                'reason' => $loan->reason,
                'amount' => $amountToRepay,
            ]);

            TransactionWallet::create([
                'transaction_id' => $withdrawal_transaction_in_main_wallet->id,
                'wallet_type' => Wallet::class,
                'wallet_id' => $user->wallet->id,
            ]);
            $user->wallet->move($amountToRepay);

            $loan->amount_refunded += $amount;
            $loan->save();
        }

        DB::commit();

        return new TransactionResource($withdrawal_transaction_in_loan_wallet);
    }


    /**
     * Add member to saving group
     *
     * @param \App\Models\User $user
     * @param SavingGroup $group
     * @param float $amountToRemove
     * @param float $fees
     * @param bool $isAdmin
     * @param float $admissionFees
     * @return SavingGroupMemberResource
     *
     * @group SavingGroup
     */
    static public function addMemberToSavingGroup(\App\Models\User $user, SavingGroup $group, float $amountToRemove, float $fees, bool $isAdmin = false, float $admissionFees = null): SavingGroupMemberResource
    {
        DB::beginTransaction();

        $min_amount = $admissionFees ? $admissionFees : TransactionUtils::getMinAmount();

        $user->wallet->move($amountToRemove);

        $withdrawal_transaction_in_main_wallet = Transaction::create([
            'reference' => TransactionUtils::generateReference(),
            'user_id' => $user->id,
            'tx_type' => SavingGroup::class,
            'tx_id' => $group->id,
            'reason' => 'Group: ' . $group->name,
            'amount' => $min_amount,
            'fees' => $fees,
        ]);

        TransactionWallet::create([
            'transaction_id' => $withdrawal_transaction_in_main_wallet->id,
            'wallet_type' => Wallet::class,
            'wallet_id' => $user->Wallet->id,
        ]);

        $user->savingWallet->add($min_amount);

        $deposit_transaction_in_saving_wallet = Transaction::create([
            'reference' => TransactionUtils::generateReference(),
            'user_id' => $user->id,
            'tx_type' => SavingGroup::class,
            'tx_id' => $group->id,
            'reason' => 'Group: ' . $group->name,
            'amount' => $min_amount,
        ]);

        TransactionWallet::create([
            'transaction_id' => $deposit_transaction_in_saving_wallet->id,
            'wallet_type' => SavingWallet::class,
            'wallet_id' => $user->savingWallet->id,
            'operation' => WalletOperationEnum::ADD->value,
        ]);

        $member = SavingGroupMember::create([
            'user_id' => $user->id,
            'saving_group_id' => $group->id,
            'current_amount' => $min_amount,
            'virtual_current_amount' => $min_amount,
            'is_admin' => $isAdmin,
            'last_payment_date' => today(),
            'status' => SavingGroupMemberSatusEnum::ACTIVE->value
        ]);

        $group->total_amount += $min_amount;
        if (!$isAdmin) $group->total_members++;

        $adminWallet = AdminWallet::first();
        $adminWallet->add($fees);

        $group->save();

        DB::commit();

        return new SavingGroupMemberResource($member);
    }


    /**
     * Make transfer
     *
     * @param \App\Models\User $sender
     * @param \App\Models\User $recipient
     * @param float $amount
     * @param float $amountToRemove
     * @param float $fees
     * @param float $walletType
     * @return TransferResource
     *
     * @group Transfer
     */
    static public function makeTransfer(\App\Models\User $sender, \App\Models\User $recipient, float $amount, float $amountToRemove, float $fees, string $walletType): TransferResource
    {
        DB::beginTransaction();

        $isMainWallet = $walletType == ActingWalletTypeEnum::MAIN->value;

        if ($isMainWallet) {
            $sender->wallet->move($amountToRemove);
        } else {
            $sender->withdrawalWallet->move($amountToRemove);
        }

        $transfer = Transfer::create([
            'recipient_id' => $recipient->id,
            'user_id' => $sender->id,
            'amount' => $amount,
        ]);

        $withdrawal_transaction_in_main_or_withdrawal_wallet = Transaction::create([
            'reference' => TransactionUtils::generateReference(),
            'user_id' => $sender->id,
            'tx_type' => Transfer::class,
            'tx_id' => $transfer->id,
            'reason' => 'Transfer',
            'amount' => $amount,
            'fees' => $fees,
        ]);

        TransactionWallet::create([
            'transaction_id' => $withdrawal_transaction_in_main_or_withdrawal_wallet->id,
            'wallet_type' => $isMainWallet ? Wallet::class : WithdrawalWallet::class,
            'wallet_id' => $isMainWallet ? $sender->Wallet->id : $sender->WithdrawalWallet->id,
        ]);

        $adminWallet = AdminWallet::first();
        $adminWallet->add($fees);

        $recipient->wallet->add($amount);

        DB::commit();

        return new TransferResource($transfer);
    }


    /**
     * Uprade challenge saving group participation.
     *
     * @param SavingGroup $group
     * @param SavingGroupMember $member
     * @param float $amount
     * @return TransactionResource
     *
     * @group SavingGroup
     */
    static public function upgradeChallengeSavingGroupContribution(SavingGroup $group, SavingGroupMember $member, float $amount): TransactionResource
    {
        DB::beginTransaction();

        $internalFeesPer = TransactionUtils::getInternalFeesPer();

        if (
            $amount >= ($group->target_amount_per_member * $group->number_of_period - $member->current_amount)
        ) {

            $withdrawal_transaction_in_main_wallet = CancelSavingGroup::helperForOverflowAmount($group, $member, false);
        } else {

            $fees = $amount * $internalFeesPer;
            $amountToRemove = $fees + $amount;

            $withdrawal_transaction_in_main_wallet = Transaction::create([
                'reference' => TransactionUtils::generateReference(),
                'user_id' => $member->user->id,
                'tx_type' => SavingGroup::class,
                'tx_id' => $group->id,
                'reason' => 'Group: ' . $group->name,
                'amount' => $amountToRemove - $fees,
                'fees' => $fees,
            ]);

            TransactionWallet::create([
                'transaction_id' => $withdrawal_transaction_in_main_wallet->id,
                'wallet_type' => Wallet::class,
                'wallet_id' => $member->user->wallet->id,
            ]);

            $deposit_transaction_in_saving_wallet = Transaction::create([
                'reference' => TransactionUtils::generateReference(),
                'user_id' => $member->user->id,
                'tx_type' => SavingGoal::class,
                'tx_id' => $group->id,
                'reason' => 'Group: ' . $group->name,
                'amount' => $amountToRemove - $fees,
            ]);

            TransactionWallet::create([
                'transaction_id' => $deposit_transaction_in_saving_wallet->id,
                'wallet_type' => SavingWallet::class,
                'wallet_id' => $member->user->savingWallet->id,
                'operation' => WalletOperationEnum::ADD->value,
            ]);

            $group->total_amount += $amount;

            $member->user->wallet->move($amountToRemove);
            $member->user->savingWallet->add($amount);
            $member->current_amount += $amount;
            $member->virtual_current_amount += $amount;

            $member->save();
            $group->save();

            $adminWallet = AdminWallet::first();
            $adminWallet->add($fees);
        }

        DB::commit();

        return new TransactionResource($withdrawal_transaction_in_main_wallet);
    }


    /**
     * Uprade normal saving group participation.
     *
     * @param SavingGroup $group
     * @param SavingGroupMember $member
     * @param float $amount
     * @return TransactionResource
     *
     * @group SavingGroup
     */
    static public function upgradeNormalSavingGroupContribution(SavingGroup $group, SavingGroupMember $member, float $amount): TransactionResource
    {
        DB::beginTransaction();

        $usingFeesPer = TransactionUtils::getSavingGroupUsingFeesPer();
        $internalFeesPer = TransactionUtils::getInternalFeesPer();

        if (
            $amount >= ($group->target_amount_per_member - $member->current_amount)
        ) {

            $feesToremove = $group->target_amount_per_member * $usingFeesPer;
            $amount_to_add_in_withdrawal_wallet = $group->target_amount_per_member - $feesToremove;

            $amountToPaidwithoutFees = $group->target_amount_per_member - $member->current_amount;

            $fees = $amountToPaidwithoutFees * $internalFeesPer;
            $amountToPaidwithFees = $fees + $amountToPaidwithoutFees;

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

            $member->user->wallet->move($amountToPaidwithFees);
            $member->user->savingWallet->move($member->current_amount);

            $member->user->withdrawalWallet->add($amount_to_add_in_withdrawal_wallet);
            $group->total_amount += $amountToPaidwithoutFees;

            $member->current_amount = $group->target_amount_per_member;
            $member->participation_status = GoalStatusEnum::REACHED->value;

            $member->save();
            $group->save();
        } else {

            $fees = $amount * $internalFeesPer;
            $amountToRemove = $fees + $amount;

            $withdrawal_transaction_in_main_wallet = Transaction::create([
                'reference' => TransactionUtils::generateReference(),
                'user_id' => $member->user->id,
                'tx_type' => SavingGroup::class,
                'tx_id' => $group->id,
                'reason' => 'Group: ' . $group->name,
                'amount' => $amountToRemove - $fees,
                'fees' => $fees,
            ]);

            TransactionWallet::create([
                'transaction_id' => $withdrawal_transaction_in_main_wallet->id,
                'wallet_type' => Wallet::class,
                'wallet_id' => $member->user->wallet->id,
            ]);

            $deposit_transaction_in_saving_wallet = Transaction::create([
                'reference' => TransactionUtils::generateReference(),
                'user_id' => $member->user->id,
                'tx_type' => SavingGoal::class,
                'tx_id' => $group->id,
                'reason' => 'Group: ' . $group->name,
                'amount' => $amountToRemove - $fees,
            ]);

            TransactionWallet::create([
                'transaction_id' => $deposit_transaction_in_saving_wallet->id,
                'wallet_type' => SavingWallet::class,
                'wallet_id' => $member->user->savingWallet->id,
                'operation' => WalletOperationEnum::ADD->value,
            ]);

            $group->total_amount += $amount;

            $member->user->wallet->move($amountToRemove);
            $member->user->savingWallet->add($amount);
            $member->current_amount += $amount;
            $member->virtual_current_amount += $amount;

            $member->save();
            $group->save();
        }

        $adminWallet = AdminWallet::first();
        $adminWallet->add($fees);


        DB::commit();

        return new TransactionResource($withdrawal_transaction_in_main_wallet);
    }


    /**
     * Make deposit
     *
     * @param \App\Models\User $user
     * @param float $amount
     * @param string $reference
     * @param string $external_reference
     * @return Deposit
     *
     * @group Deposit
     */
    static public function makeDeposit(\App\Models\User $user, float $amount, string $reference, string $external_reference): DepositResource
    {
        DB::beginTransaction();

        $depositFeesPer = TransactionUtils::getDepositFeesPer();
        $fees = $amount * $depositFeesPer;

        // $amountToAdd = $amount - $fees;

        $deposit = Deposit::create([
            'user_id' => $user->id,
            'amount' => $amount,
        ]);

        $deposit_transaction_in_main_wallet = Transaction::create([
            'user_id' => $user->id,
            'tx_type' => Deposit::class,
            'tx_id' => $deposit->id,
            'reason' => 'Deposit',
            'amount' => $amount,
            'fees' => $fees,
            'external_reference' => $external_reference,
            'reference' => $reference,
            'status' => TransactionStatusEnum::PENDING,
        ]);

        TransactionWallet::create([
            'transaction_id' => $deposit_transaction_in_main_wallet->id,
            'wallet_type' => Wallet::class,
            'wallet_id' => $user->Wallet->id,
            'operation' => WalletOperationEnum::ADD->value,
        ]);

        // $user->wallet->add($amountToAdd);

        DB::commit();

        return new DepositResource($deposit);
    }


    /**
     * Update deposit status
     *
     * @param string $external_reference
     * @param string $reference
     * @param string $status
     * @return void
     *
     * @group Deposit
     */
    static public function updateDeposit(string $external_reference, string $reference, string $status)
    {
        DB::beginTransaction();

        $transaction = Transaction::where('external_reference', $external_reference)
            ->where('reference', $reference)
            ->where('status', TransactionStatusEnum::PENDING->value)
            ->first();

        if ($transaction) {

            $service = new ApiTransactionService;

            $status = $service->matchStatus($status);
            $transaction->status = $status;

            if ($status == TransactionStatusEnum::COMPLETED->value) {

                $transaction->user->wallet->add($transaction->amount);
            }

            $transaction->save();
            unset($service);
        }

        DB::commit();
    }
}
