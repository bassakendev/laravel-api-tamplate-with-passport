<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SavingGroupMember
 */
class SavingGroupMemberResource extends JsonResource
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
            'user' => $this->user,
            'saving_group_id' => $this->saving_group_id,
            'current_amount' => $this->current_amount,
            'is_admin' => $this->is_admin,
            'participation_status' => $this->participation_status,
            'penalty_fees_per' => $this->penalty_fees_per,
            'status' => $this->status,
            /** @var string */
            'created_at' => $this->created_at,
            /** @var string */
            'updated_at' => $this->updated_at,
        ];
    }
}
