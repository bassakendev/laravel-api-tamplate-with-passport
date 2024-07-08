<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingGroupMember extends Model
{
    use HasFactory, SoftDeletes, BelongsToUser;

    protected $guarded = ['id'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(SavingGroup::class);
    }
}
