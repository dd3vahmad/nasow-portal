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
            'id' => $this->user_id ?? null,
            'membership_id' => $this->id ?? null,
            'name' => $this->user->name ?? null,
            'no' => $this->user->no ?? null,
            'category' => $this->category ?? null,
            'status' => $this->status ?? null,
            'email' => $this->user->email ?? null,
            'phone' => $this->user->details->phone ?? null,
            'address' => $this->user->details->address ?? null,
            'state' => $this->user->details->state ?? null,
            'specialization' => $this->user->details->specialization ?? null,
            'educations' => $this->user->educations ?? null,
            'employments' => $this->user->employments ?? null,
            'documents' => $this->user->documents ?? null,
            'reviewed' => $this->reviewed ? true : false,
            'comment' => $this->comment ?? null,
            'reviewer' => $this->reviewer ?? null,
            'last_login' => $this->user->last_login ?? null,
            'email_verified_at' => $this->user->email_verified_at ?? null,
            'registration_at' => $this->created_at ?? null,
        ];
    }
}
