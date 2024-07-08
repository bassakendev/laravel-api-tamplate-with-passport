<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SavingGoal extends Model
{
    use HasFactory, BelongsToUser;

    protected $guarded = ['id'];

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactionable', 'tx_type', 'tx_id');
    }

    public function loan(): BelongsTo|null
    {
        return $this->belongsTo(Loan::class);
    }
}
