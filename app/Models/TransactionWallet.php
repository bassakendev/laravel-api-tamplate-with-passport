<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransactionWallet extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function trackable()
    {
        return $this->morphTo();
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
