<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Loan
 */
class LoanResource extends JsonResource
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
            'interest' => $this->interest,
            'pledge' => new SavingGoalResource($this->pledge),
            'reason' => $this->reason,
            'amount_refund' => $this->amount_refund,
            'transactions' => TransactionResource::collection($this->transactions),
            'status' => $this->status,
            /** @var string */
            'updated_at' => $this->updated_at,
        ];
    }
}
