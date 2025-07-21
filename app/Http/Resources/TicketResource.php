<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'email' => $this->email,
            'name' => $this->name,
            'state' => $this->state,
            'status' => $this->status,
            'assigned_to' => $this->whenLoaded('support', function () {
                return [
                    'id' => $this->support->id,
                    'name' => $this->support->name,
                    'email' => $this->support->email,
                ];
            }),
            'assigned_at' => optional($this->assigned_at)->toDateTimeString(),
            'closed_at' => optional($this->closed_at)->toDateTimeString(),
            'avg_response_time' => $this->avg_response_time,
            'messages' => TicketMessageResource::collection($this->whenLoaded('messages')),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
