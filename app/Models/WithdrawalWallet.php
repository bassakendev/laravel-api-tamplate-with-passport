<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use App\Traits\Trackable;
use App\Traits\WalletMethodes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WithdrawalWallet extends Model
{
    use HasFactory, SoftDeletes, WalletMethodes, BelongsToUser, Trackable;

    protected $guarded = ['id'];
}
