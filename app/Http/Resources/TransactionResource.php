<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Transaction
 */
class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'fees' => $this->fees,
            'reason' => $this->reason,
            'reference' => $this->reference,
            'external_reference' => $this->external_reference,
            'type' => $this->tx_type,
            /** @var string */
            'updated_at' => $this->updated_at,
        ];
    }
}
