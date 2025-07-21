<?php

namespace App\Http\Controllers;

use App\Http\Requests\Ticket\CreateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Http\Responses\ApiResponse;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    /**
     * Create a new ticket
     *
     * @param CreateTicketRequest $request
     * @return ApiResponse
     */
    public function store(CreateTicketRequest $request)
    {
        try {
            $user = auth()->user();
            $data = $request->validated();

            $ticket = DB::transaction(function () use ($user, $data) {
                $ticket = Ticket::create([
                    'subject' => $data['subject'],
                    'name' => $user->name,
                    'state' => $user->details->state ?? null,
                    'email' => $user->email,
                    'status' => 'pending',
                    'user_id' => $user->id,
                ]);

                $message = TicketMessage::create([
                    'message' => $data['message'],
                    'ticket_id' => $ticket->id,
                    'sender_id' => $user->id,
                ]);

                $ticket->setRelation('messages', collect([$message]));

                return $ticket;
            });

            return ApiResponse::success('Ticket created successfully', new TicketResource($ticket->load(['messages', 'support'])));
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
