<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SavingGroup
 */
class SavingGroupResource extends JsonResource
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
            'admin_id' => $this->admin_id,
            'name' => $this->name,
            'description' => $this->description,
            'deadline' => $this->deadline,
            'total_amount' => $this->total_amount,
            'total_members' => $this->total_members,
            'admission_fees' => $this->admission_fees,
            'target_amount_per_member' => $this->target_amount_per_member,
            'contribution_frequency' => $this->contribution_frequency,
            'penalty_fees_per' => $this->penalty_fees_per,
            'number_of_period' => $this->number_of_period,
            'type' => $this->type,
            'status' => $this->status,
            'members' => SavingGroupMemberResource::collection($this->members),
            /** @var string */
            'created_at' => $this->created_at,
            /** @var string */
            'updated_at' => $this->updated_at,
        ];
    }
}
