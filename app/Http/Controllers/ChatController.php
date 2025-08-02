<?php
namespace App\Http\Controllers;

use App\Http\Requests\Chats\StoreChatRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * Get chats
     *
     * @param \Illuminate\Http\Request $request
     * @return ApiResponse
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();

            $chats = Chat::whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['participants', 'latestMessage.user'])
            ->withCount(['messages'])
            ->get()
            ->map(function ($chat) use ($user) {
                $chat->unread_count = $chat->unreadCount($user->id);

                if ($chat->type === 'private') {
                    $other = $chat->participants->firstWhere('id', '!=', $user->id);
                    $chat->name = $other?->name;
                    $chat->metadata = [
                        'online' => $other?->isOnline(), // assuming isOnline() exists
                        'role' => $other?->role,
                    ];
                }

                return $chat;
            });

            return ApiResponse::success('Chats fetched successfully', $chats);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    /**
     * Start a new chat
     *
     * @param  StoreChatRequest $request
     * @return ApiResponse
     */
    public function store(StoreChatRequest $request)
    {
        $user = auth()->user();
        $data = $request->validated();
        $participants = collect($data['participants'])
            ->filter(fn ($id) => $id != $user->id)
            ->values()
            ->all();

        $this->validateChatCreationPermissions($user, $participants);

        if ($data['type'] === 'private') {
            $allParticipantIds = collect($participants)->push($user->id)->unique()->sort()->values()->toArray();

            $existingChat = Chat::where('type', 'private')
                ->whereHas('participants', fn($q) => $q->whereIn('user_id', $allParticipantIds))
                ->withCount('participants')
                ->get()
                ->first(fn($chat) => $chat->participants->pluck('id')->sort()->values()->toArray() === $allParticipantIds);

            if ($existingChat) {
                return ApiResponse::success('Private chat already exists', $existingChat->load('participants'));
            }
        }

        DB::beginTransaction();
        try {
            $chat = Chat::create([
                'name' => $data['type'] === 'group' ? $data['name'] : null,
                'type' => $data['type'],
                'created_by' => $user->id,
            ]);

            // Attach the creator as admin
            $chat->participants()->attach($user->id, ['is_admin' => true]);

            // Attach other participants
            foreach ($participants as $participantId) {
                $chat->participants()->attach($participantId);
            }

            DB::commit();

            return ApiResponse::success('Chat created successfully', $chat->load('participants'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Check if user has permission to create the chat
     *
     * @param mixed $user
     * @param mixed $participantIds
     * @return void
     */
    private function validateChatCreationPermissions($user, $participantIds)
    {
        $participants = User::whereIn('id', $participantIds)->get();

        switch ($user->getRoleNames()->first()) {
            case 'national-admin':
                break;

            case 'state-admin':
                foreach ($participants as $participant) {
                    if (!in_array($participant->role, ['member', 'support-staff', 'case-manager', 'state-admin']) ||
                        ($participant->role === 'member' && $participant->state !== $user->state)) {
                        abort(403, 'You can only chat with members from your state and support staff');
                    }
                }
                break;

            case 'case-manager':
                foreach ($participants as $participant) {
                    if (!in_array($participant->role, ['member', 'support-staff', 'case-manager'])) {
                        abort(403, 'Case managers can only chat with members, support staffs and fellow case managers');
                    }
                }
                break;

            case 'support-staff':
                foreach ($participants as $participant) {
                    if (!in_array($participant->role, ['member', 'support-staff', 'case-manager'])) {
                        abort(403, 'Case managers can only chat with members, case managers and fellow support staffs');
                    }
                }
                break;

            default:
                abort(403, 'You do not have permission to create chats');
        }
    }

    /**
     * Get chat details
     *
     * @param int $chat
     * @return ApiResponse
     */
    public function show(int $chatId)
    {
        try {
            $user = auth()->user();
            $chat = Chat::findOrFail($chatId);

            if (!$chat->participants->contains($user->id)) {
                abort(403, 'You are not a participant in this chat');
            }

            $chat->load(['participants', 'messages.user', 'messages.replyTo.user']);

            if ($chat->type === 'private') {
                $other = $chat->participants->firstWhere('id', '!=', $user->id);
                $chat->name = $other?->name;
                $chat->metadata = [
                    'online' => $other?->isOnline(),
                    'role' => $other?->role,
                ];
            }

            return ApiResponse::success('Chat details fetched', $chat);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    /**
     * Get available users to start a chat with
     *
     * @param \Illuminate\Http\Request $request
     * @return ApiResponse
     */
    public function getAvailableUsers(Request $request)
    {
        try {
            $user = auth()->user();
            $query = User::where('id', '!=', $user->id);

            switch ($user->getRoleNames()->first()) {
                case 'national-admin':
                    break;

                case 'state-admin':
                    $query->where(function ($q) use ($user) {
                        $q->role('support-staff', 'api')
                        ->orWhere(function ($subQ) {
                            $subQ->role('state-admin', 'api');
                        })
                        ->orWhere(function ($subQ) {
                            $subQ->role('case-manager', 'api');
                        })
                        ->orWhere(function ($subQ) use ($user) {
                            $subQ->role('member', 'api')
                                ->where('state', $user->state);
                        });
                    });
                    break;

                case 'case-manager':
                    $query->where(function ($q) {
                        $q->role('member', 'api')
                        ->orWhere(function ($subQ) {
                            $subQ->role('support-staff', 'api');
                        })
                        ->orWhere(function ($subQ) {
                            $subQ->role('case-manager', 'api');
                        })
                        ->orWhere(function ($subQ) {
                            $subQ->role('guest', 'api');
                        });
                    });
                    break;

                case 'support-staff':
                    $query->where(function ($q) {
                        $q->role('member', 'api')
                        ->orWhere(function ($subQ) {
                            $subQ->role('support-staff', 'api');
                        })
                        ->orWhere(function ($subQ) {
                            $subQ->role('case-manager', 'api');
                        })
                        ->orWhere(function ($subQ) {
                            $subQ->role('guest', 'api');
                        });
                    });
                    break;

                default:
                    return ApiResponse::success('Available users fetched successfully', []);
            }

            return ApiResponse::success(
                'Available users fetched successfully',
                $query
                        ->select('id', 'name', 'email')->with('details')
                        ->get()
                        ->map(function ($u) {
                            $u['role'] = $u->getRoleNames()->first();
                            $u->makeHidden('roles');

                            return $u;
                        }
                    )
                );
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    public function addParticipant(Request $request, int $chatId)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = auth()->user();
        $chat = Chat::findOrFail($chatId);
        $participantToAdd = User::findOrFail($request->user_id);

        $currentUserParticipant = $chat->participants()->where('user_id', $user->id)->first();
        if (!$currentUserParticipant || !$currentUserParticipant->pivot->is_admin) {
            return ApiResponse::error('Only chat admins can add participants', 403);
        }

        $this->validateChatCreationPermissions($user, [$participantToAdd->id]);

        if ($chat->participants->contains($participantToAdd->id)) {
            return ApiResponse::error('User is already a participant', 400);
        }

        $chat->participants()->attach($participantToAdd->id, [
            'is_admin' => false,
            'joined_at' => now()
        ]);

        return ApiResponse::success('Participant added successfully');
    }

    public function removeParticipant(int $chatId, int $participantId)
    {
        $user = auth()->user();

        $chat = Chat::findOrFail($chatId);
        $participant = User::findOrFail($participantId);

        if ($user->id !== $participant->id) {
            $currentUserParticipant = $chat->participants()->where('user_id', $user->id)->first();
            if (!$currentUserParticipant || !$currentUserParticipant->pivot->is_admin) {
                return ApiResponse::error('Only chat admins can remove other participants', 403);
            }
        }

        if ($chat->created_by === $participant->id) {
            return ApiResponse::error('Cannot remove the chat creator', 403);
        }
        $chat->participants()->detach($participant->id);

        return ApiResponse::success('Participant removed successfully');
    }
}
