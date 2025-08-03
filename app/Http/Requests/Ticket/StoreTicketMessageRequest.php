<?php

namespace App\Http\Requests\Ticket;

use App\Http\Requests\BaseRequest;
use App\Models\Ticket;

class StoreTicketMessageRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $ticketId = $this->input('ticket_id');
        $user = $this->user();

        if (!$ticketId || !$user) {
            return false;
        }

        $ticket = Ticket::find($ticketId);

        if (!$ticket) {
            return false;
        }

        // Authenticated user must be either the ticket creator or the assigned support
        return $ticket->user_id === $user->id || $ticket->assigned_to === $user->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ticket_id' => ['required', 'integer', 'exists:tickets,id'],
            'message'   => ['required', 'string', 'min:3'],
        ];
    }

    public function messages(): array
    {
        return [
            'ticket_id.required' => 'Ticket ID is required.',
            'ticket_id.exists'   => 'The ticket does not exist.',
            'message.required'   => 'Message content is required.',
            'message.min'        => 'Message must be at least 3 characters.',
        ];
    }
}
