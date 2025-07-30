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
            'id' => $this->user_id ?? null,
            'membership_id' => $this->id ?? null,
            'no' => $this->user->no ?? null,
            'name' => $this->user->name ?? null,
            'email' => $this->user->email ?? null,
            'phone' => $this->user->details->phone ?? null,
            'state' => $this->user->details->state ?? null,
            'status' => $this->status ?? null,
            'category' => $this->category ?? null,
            'reviewed' => $this->reviewed ? true : false,
            'comment' => $this->comment ?? null,
            'registration_date' => $this->created_at ?? null,
        ];
    }
}
