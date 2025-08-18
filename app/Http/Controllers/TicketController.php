<?php

namespace App\Http\Controllers;

use App\Enums\ActivityType;
use App\Http\Requests\Ticket\StoreTicketMessageRequest;
use App\Http\Requests\Ticket\AssignTicketRequest;
use App\Http\Requests\Ticket\CreateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Http\Responses\ApiResponse;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\ActionLogger;
use Illuminate\Http\Request;
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
            $user_id = $user->id ?? null;

            $ticket = DB::transaction(function () use ($user, $data, $user_id) {
                $ticket = Ticket::create([
                    'subject' => $data['subject'],
                    'name' => $user->name,
                    'state' => $user->details->state ?? null,
                    'email' => $user->email,
                    'status' => 'pending',
                    'user_id' => $user_id,
                ]);

                $message = TicketMessage::create([
                    'message' => $data['message'],
                    'ticket_id' => $ticket->id ?? null,
                    'sender_id' => $user_id,
                ]);

                $ticket->setRelation('messages', collect([$message]));
                $user->sendNotification('Your complaint has been received and our support team will attend to you as soon as possible.', 'ticket');
                ActionLogger::log(
                    ActivityType::SUPPORT->value,
                    "Support ticket opened: {$data['subject']}",
                    $user_id,
                    $user->details->state ?? null
                );

                return $ticket;
            });

            return ApiResponse::success('Ticket created successfully', new TicketResource($ticket->load(['messages', 'support'])));
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Gets logged in member tickets
     *
     * @param Request $request
     * @return ApiResponse
     */
    public function mine(Request $request) {
        try {
            $status = $request->query('status', '');
            $q = $request->query('q', '');
            $user = auth()->user();

            $tickets = Ticket::where('user_id', $user->id ?? null)
                ->when($status, function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->when($q, function ($query) use ($q) {
                    $query->where('subject', 'like', "%{$q}%");
                })
                ->get();

            return ApiResponse::success('Member tickets fetched successfully', TicketResource::collection($tickets));
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Gets assigned tickets
     *
     * @param Request $request
     * @return ApiResponse
     */
    public function support(Request $request) {
        try {
            $status = $request->query('status', '');
            $q = $request->query('q', '');
            $user = auth()->user();

            $tickets = Ticket::where('assigned_to', $user->id ?? null)
                ->when($status, function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->when($q, function ($query) use ($q) {
                    $query->where('subject', 'like', "%{$q}%");
                })
                ->get();

            return ApiResponse::success('Assigned tickets fetched successfully', TicketResource::collection($tickets));
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Gets state tickets
     *
     * @param Request $request
     * @return ApiResponse
     */
    public function state(Request $request) {
        try {
            $status = $request->query('status', '');
            $q = $request->query('q', '');
            $user = auth()->user();

            $tickets = Ticket::where('state', $user->details->state ?? null)
                ->when($status, function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->when($q, function ($query) use ($q) {
                    $query->where('subject', 'like', "%{$q}%");
                })
                ->get();

            return ApiResponse::success('State tickets fetched successfully', TicketResource::collection($tickets));
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Gets tickets
     *
     * @param Request $request
     * @return ApiResponse
     */
    public function index(Request $request) {
        try {
            $status = $request->query('status', '');
            $state = $request->query('state', '');
            $q = $request->query('q', '');

            $tickets = Ticket::when($status, function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->when($state, function ($query) use ($state) {
                    $query->where('state', $state);
                })
                ->when($q, function ($query) use ($q) {
                    $query->where('subject', 'like', "%{$q}%");
                })
                ->get();

            return ApiResponse::success('Tickets fetched successfully', TicketResource::collection($tickets));
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Get ticket
     *
     * @param int $id
     * @return ApiResponse
     */
    public function view(int $id) {
        try {
            $ticket = Ticket::find($id);
            if (!$ticket) {
                return ApiResponse::error('Ticket not found', 404);
            }

            return ApiResponse::success('Tickets fetched successfully', new TicketResource($ticket->load('messages')));
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Assign ticket
     *
     * @param AssignTicketRequest $request
     * @return ApiResponse
     */
    public function assign(AssignTicketRequest $request) {
        try {
            $user = auth()->user();
            $data = $request->validated();
            $ticket_id = $data['ticket_id'];
            $support_id = $data['support_id'];

            $ticket = Ticket::find($ticket_id);
            if (!$ticket) {
                return ApiResponse::error('Ticket not found', 404);
            }

            $support = User::role('support-staff', 'api')->find($support_id);
            if (!$support) {
                return ApiResponse::error('Support staff not found', 404);
            }

            $ticket->update([
                'assigned_to' => $support_id,
                'assigned_at' => now(),
                'assigned_by' => $user->id ?? null,
                'status' => 'open'
            ]);
            $support->sendAssignedTicketNotification($ticket);
            ActionLogger::audit("Assigned ticket to {$support->name}", $user->id ?? null);

            $member = User::find($ticket->user_id);
            $member->sendNotification('Our support team are currently checking your complaint, you will receive feedback soon. Thanks for your understanding.', 'ticket');

            return ApiResponse::success('Ticket assigned to support', new TicketResource($ticket));
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Close ticket
     *
     * @param int $id
     * @return ApiResponse
     */
    public function close(int $id) {
        try {
            $user = auth()->user();

            $ticket = Ticket::find($id);
            if (!$ticket) {
                return ApiResponse::error('Ticket not found', 404);
            }

            $subject = $ticket->subject ?? null;
            $ticket->close();

            $user->sendClosedTicketNotification($ticket);
            ActionLogger::audit("{$user->name} closed a ticket: {$subject}", $user->id ?? null);

            $member = User::find($ticket->user_id);
            $member->sendNotification('Your complaint has been resolved, kindly confirm or you can create a new ticket if you encounter any other issue. Thank you.', 'ticket');

            return ApiResponse::success('Ticket closed', new TicketResource($ticket));
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Send a new ticket message
     *
     * @param \App\Http\Requests\Ticket\StoreTicketMessageRequest $request
     * @return ApiResponse
     */
    public function sendMessage(StoreTicketMessageRequest $request) {
        try {
            $validated = $request->validated();
            $sender_id = auth()->id();
            $ticket_id = $validated['ticket_id'];
            $sender = User::find($sender_id);

            $ticket = Ticket::find($ticket_id);
            $message = TicketMessage::create([
                'sender_id' => $sender_id,
                'ticket_id' => $ticket_id,
                'message'   => $validated['message'],
            ]);

            $receiver = null;
            if ($sender_id === $ticket->user_id) {
                $receiver = User::find($ticket->assigned_to);
            } else {
                $receiver = User::find($ticket->user_id);
            }

            if ($receiver) {
                $receiver->sendNotification('Your have a new ticket message from ' . $sender->name, 'message');
            }

            return ApiResponse::success('Ticket message created successfully.', $message, 201);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
