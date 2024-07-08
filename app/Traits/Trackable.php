<?php

namespace App\Traits;

use App\Models\TransactionWallet;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Trackable
{
    public function transactions(): MorphMany
    {
        return $this->morphMany(TransactionWallet::class, 'trackable', 'wallet_type', 'wallet_id');
    }
}
