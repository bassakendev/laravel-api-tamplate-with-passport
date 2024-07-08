<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\AdminWallet;
use App\Models\AdminLoanWallet;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wallet = AdminWallet::first();
        $loanWallet = AdminLoanWallet::first();

        if ($wallet == null) {
            AdminWallet::create();
        }

        if ($loanWallet == null) {
            AdminLoanWallet::create();
        }

        $settings = [
            ['name' => 'min_amount', 'value' => '100.00', 'description' => 'Minimum value of a transaction.'],
            ['name' => 'max_amount', 'value' => '300000.00', 'description' => 'Maximum value of a transaction.'],
            ['name' => 'withdrawal_fees_per', 'value' => '0.01', 'description' => 'Withdrawal fee percentage.'],
            ['name' => 'deposit_fees_per', 'value' => '0.02', 'description' => 'Deposite fee percentage.'],
            ['name' => 'internal_transaction_fees_per', 'value' => '0.02', 'description' => 'Internal transactions fee percentage.'],
            ['name' => 'external_transaction_fees_per', 'value' => '0.02', 'description' => 'External transactions fee percentage.'],
            ['name' => 'min_penalty_fees_per', 'value' => '0.02', 'description' => 'Minimum penalty fee percentage.'],
            ['name' => 'loan_fees_per', 'value' => '0.02', 'description' => 'Loan fee percentage.'],
            ['name' => 'max_loan', 'value' => '25000.00', 'description' => 'Maximum loan value.'],
            ['name' => 'min_loan', 'value' => '5000.00', 'description' => 'Minimum loan value.'],
            ['name' => 'max_transfer_amount', 'value' => '50000.00', 'description' => 'Maximum amount to transfer.'],
            ['name' => 'saving_group_using_fees_per', 'value' => '0.01', 'description' => 'Fees for using group savings goals.']
        ];

        if (Setting::count() == 0) {
            foreach ($settings as $setting) {
                Setting::create($setting);
            }
        }
    }
}
