<?php

namespace App\Utils;

use App\Models\Setting;

abstract class SettingUtils
{

    /**
     * The minimum amount of a transaction
     *
     * @return float
     */
    static public function getMinAmount(): float
    {
        $min_amount = Setting::where('name', 'min_amount')->first();
        return $min_amount->value ?? 100.00;
    }

    /**
     * The maximum amount of a transaction
     *
     * @return float
     */
    static public function getMaxAmount(): float
    {
        $max_amount = Setting::where('name', 'max_amount')->first();
        return $max_amount->value ?? 300000.00;
    }

    /**
     * The withdrawal fee percentage of a transaction
     *
     * @return float
     */
    static public function getWithdrawalFeesPer(): float
    {
        $withdrawal_fees_per = Setting::where('name', 'withdrawal_fees_per')->first();
        return $withdrawal_fees_per->value ?? 0.02;
    }

    /**
     * The withdrawal fee percentage of a transaction
     *
     * @return float
     */
    static public function getDepositFeesPer(): float
    {
        $deposit_fees_per = Setting::where('name', 'deposit_fees_per')->first();
        return $deposit_fees_per->value ?? 0.02;
    }

    /**
     * The minimum penalty fee percentage of a transaction
     *
     * @return float
     */
    static public function getMinPenaltyFeesPer(): float
    {
        $min_penalty_fees_per = Setting::where('name', 'min_penalty_fees_per')->first();
        return $min_penalty_fees_per->value ?? 0.02;
    }

    /**
     * The minimum loan wallet balance
     *
     * @return float
     */
    static public function getMinLoan(): float
    {
        $min_amount = Setting::where('name', 'min_loan')->first();
        return $min_amount->value ?? 5000.00;
    }

    /**
     * The maximum loan wallet balance
     *
     * @return float
     */
    static public function getMaxLoan(): float
    {
        $max_amount = Setting::where('name', 'max_loan')->first();
        return $max_amount->value ?? 25000.00;
    }

    /**
     * The internal transaction fee percentage of a transaction
     *
     * @return float
     */
    static public function getInternalFeesPer(): float
    {
        $internal_transaction_fees_per = Setting::where('name', 'internal_transaction_fees_per')->first();
        return $internal_transaction_fees_per->value ?? 0.02;
    }

    /**
     * The external transaction fee percentage of a transaction
     *
     * @return float
     */
    static public function getExternalFeesPer(): float
    {
        $external_transaction_fees_per = Setting::where('name', 'external_transaction_fees_per')->first();
        return $external_transaction_fees_per->value ?? 0.02;
    }

    /**
     * The loan deadline as number of months
     *
     * @return int
     */
    static public function getLoanDeadline(): int
    {
        $loan_deadline_as_mumber_of_months = Setting::where('name', 'loan_deadline_as_mumber_of_months')->first();
        return $loan_deadline_as_mumber_of_months->value ?? 1;
    }

    /**
     * The loan fee percentage of a transaction
     *
     * @return float
     */
    static public function getLoanFeesPer(): float
    {
        $loan_fees_per = Setting::where('name', 'loan_fees_per')->first();
        return $loan_fees_per->value ?? 0.02;
    }

    /**
     * The using fee percentage of saving group
     *
     * @return float
     */
    static public function getSavingGroupUsingFeesPer(): float
    {
        $saving_group_using_fees_per = Setting::where('name', 'saving_group_using_fees_per')->first();
        return $saving_group_using_fees_per->value ?? 0.01;
    }

    /**
     * The maximum amount to transfer
     *
     * @return float
     */
    static public function getMaxTransferAmount(): float
    {
        $max_transfer_amount = Setting::where('name', 'max_transfer_amount')->first();
        return $max_transfer_amount->value ?? 50000.0;
    }

    /**
     * Generate unique payment reference function
     *
     * @param int $length
     * @param int $sections
     * @param string $separator
     * @return string
     */
    static public function generateReference(int $length = 6, int $sections = 6, string $separator = '-'): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $reference = '';

        for ($i = 0; $i < $sections; $i++) {
            for ($j = 0; $j < $length; $j++) {
                $reference .= $characters[rand(0, strlen($characters) - 1)];
            }
            if ($i < $sections - 1) {
                $reference .= $separator;
            }
        }

        return $reference;
    }
}
