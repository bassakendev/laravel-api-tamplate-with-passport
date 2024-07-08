<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SavingGoal
 */
class SavingGoalResource extends JsonResource
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
            'current_amount' => $this->current_amount,
            'target_amount' => $this->target_amount,
            'reason' => $this->description,
            'deadline' => $this->deadline,
            'loan_id' => $this->loan_id,
            'penalty_fees_per' => $this->penalty_fees_per,
            'transactions' => TransactionResource::collection($this->transactions),
            /** @var string */
            'created_at' => $this->created_at,
            /** @var string */
            'updated_at' => $this->updated_at,
        ];
    }
}
