<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id ?? null,
            'type' => $this->type ?? null,
            'description' => $this->description ?? null,
            'state' => $this->state ?? null,
            'meta' => $this->meta ?? null,
            'user' => $this->whenLoaded('user')->name ?? null,
            'occurred_at' => $this->created_at ?? null
        ];
    }
}
