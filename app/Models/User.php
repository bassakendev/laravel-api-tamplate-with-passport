<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Str;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password', 'remember_token', 'activation_token'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected static function boot()
    {
        parent::boot();

        // Generate a unique referral code when creating the user
        static::created(function ($user) {
            $code = Str::random(10);
            Referral::create(['user_id' => $user->id, 'code' => $code]);

            //Wallets
            Wallet::create(['user_id' => $user->id]);
            LoanWallet::create(['user_id' => $user->id]);
            SavingWallet::create(['user_id' => $user->id]);
            WithdrawalWallet::create(['user_id' => $user->id]);
        });
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function loanWallet(): HasOne
    {
        return $this->hasOne(LoanWallet::class);
    }

    public function savingWallet(): HasOne
    {
        return $this->hasOne(SavingWallet::class);
    }

    public function withdrawalWallet(): HasOne
    {
        return $this->hasOne(WithdrawalWallet::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(SavingGoal::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(SavingGroup::class, 'admin_id');
    }

    public function groupMemberships(): BelongsToMany
    {
        return $this->belongsToMany(SavingGroup::class, 'saving_group_members')->withPivot('status');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function goddaughters(): HasMany
    {
        return $this->hasMany(User::class, 'referrer_id');
    }

    public function referral()
    {
        return $this->hasOne(Referral::class);
    }
}
