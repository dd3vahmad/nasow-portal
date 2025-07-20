<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
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
            'name' => $this->user->name,
            'no' => $this->no,
            'category' => $this->category,
            'status' => $this->status,
            'email' => $this->user->email,
            'phone' => $this->user->details->phone ?? null,
            'address' => $this->user->details->address ?? null,
            'state' => $this->user->details->state ?? null,
            'last_login' => $this->user->last_login,
            'email_verified_at' => $this->user->email_verified_at,
            'registration_at' => $this->created_at,
        ];
    }
}
