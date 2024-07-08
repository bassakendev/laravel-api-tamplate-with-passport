<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Deposit extends Model
{
    use HasFactory, SoftDeletes, BelongsToUser;

    protected $guarded = ['id'];

    public function transaction(): MorphOne
    {
        return $this->morphOne(Transaction::class, 'transactionable', 'tx_type', 'tx_id');
    }
}
