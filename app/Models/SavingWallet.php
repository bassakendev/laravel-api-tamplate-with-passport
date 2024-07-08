<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use App\Traits\Trackable;
use App\Traits\WalletMethodes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SavingWallet extends Model
{
    use HasFactory, SoftDeletes, BelongsToUser, WalletMethodes, Trackable;

    protected $guarded = ['id'];

}
