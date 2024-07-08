<?php

namespace App\Traits;

use App\Models\User;

trait WalletMethodes
{
    /**
     * inspect if the balance balance is sufficient (greater than the amount parameter)
     *
     * @param float $amount
     * @return boolean
     */
    public function check(float $amount): bool
    {
        return $this->balance > $amount;
    }

    /**
     * withdraw money from wallet
     *
     * @param float $amount
     * @return void
     */
    public function move(float $amount)
    {
        $this->balance -= $amount;
        $this->save();
    }

    /**
     * add money from wallet
     *
     * @param float $amount
     * @return void
     */
    public function add(float $amount)
    {
        $this->balance += $amount;
        $this->save();
    }
}
