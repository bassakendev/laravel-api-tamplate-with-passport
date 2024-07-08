<?php

namespace App\Models;

use App\Traits\Trackable;
use App\Traits\BelongsToUser;
use App\Traits\WalletMethodes;
use App\Utils\TransactionUtils;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanWallet extends Model
{
    use HasFactory, SoftDeletes, BelongsToUser, WalletMethodes, Trackable;

    protected $guarded = ['id'];

    public function isFull()
    {
        $this->balance < TransactionUtils::getMaxLoan();
    }
}
