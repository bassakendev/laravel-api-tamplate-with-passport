<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\TransactionWalletResource;

/**
 * @mixin Wallet
 */
class WalletResource extends JsonResource
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
            'balance' => $this->balance,
            // 'transactions' => TransactionWalletResource::collection($this->transactions),
            // /** @var string */
            // 'created_at' => $this->created_at,
            /** @var string */
            'updated_at' => $this->updated_at,
        ];
    }
}
