<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\WalletResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'country' => $this->country,
            'wallets' => [
                'main_wallet' => new WalletResource($this->wallet),
                'loan_wallet' => new WalletResource($this->loanWallet),
                'saving_wallet' => new WalletResource($this->savingWallet),
            ],
            'avatar' => $this->avatar,
            'referrer_id' => $this->referrer_id,
            // 'referrer' => $this->referrer,
            // 'goddaughters' => $this->goddaughters,
            'referral_code' => $this->referral->code,
            'email_verified_at' => $this->email_verified_at,
            'preferred_lang' => $this->preferred_lang,
            /** @var string */
            'created_at' => $this->created_at,
            /** @var string */
            'updated_at' => $this->updated_at,
        ];
    }
}
