<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembersResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->user_id,
            'membership_id' => $this->id,
            'no' => $this->no,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $this->user->details->phone ?? null,
            'state' => $this->user->details->state ?? null,
            'status' => $this->status,
            'category' => $this->category,
            'registration_date' => $this->created_at,
        ];
    }
}
