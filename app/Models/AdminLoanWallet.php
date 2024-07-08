<?php

namespace App\Models;

use App\Traits\WalletMethodes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminLoanWallet extends Model
{
    use HasFactory, SoftDeletes, WalletMethodes;

    protected $guarded = ['id'];
}
