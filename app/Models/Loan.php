<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Loan extends Model
{
    use HasFactory, SoftDeletes, BelongsToUser;

    protected $guarded = ['id'];

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactionable', 'tx_type', 'tx_id');
    }
    public function pledge(): HasOne
    {
        return $this->hasOne(SavingGoal::class);
    }
}
