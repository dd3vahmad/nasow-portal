<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CpdLogResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'completed_at' => $this->completed_at,
            'credit_hours' => $this->credit_hours,
            'certificate_url' => $this->certificate_url,
            'status' => $this->status,

            // Relationships
            'member' => new MemberResource(optional($this->member)->details),
            'activity' => new CpdActivityResource($this->whenLoaded('activity')),
        ];
    }
}
