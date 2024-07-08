<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Loan
 */
class TransferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'sender' => new UserResource($this->user),
            'recipient' => new UserResource($this->recipient),
            'amount' => $this->amount,
            'transactions' => new TransactionResource($this->transaction),
            /** @var string */
            'updated_at' => $this->updated_at,
        ];
    }
}
