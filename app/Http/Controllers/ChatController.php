<?php
namespace App\Http\Controllers;

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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:private,group',
            'participants' => 'required|array|min:1',
            'participants.*' => 'exists:users,id',
        ]);

        $user = auth()->user();

        $this->validateChatCreationPermissions($user, $request->participants);

        DB::beginTransaction();
        try {
            $chat = Chat::create([
                'name' => $request->name,
                'type' => $request->type,
                'created_by' => $user->id,
            ]);

            $chat->participants()->attach($user->id, ['is_admin' => true]);
            foreach ($request->participants as $participantId) {
                if ($participantId != $user->id) {
                    $chat->participants()->attach($participantId);
                }
            }

            DB::commit();

            return ApiResponse::success('Chats fetched successfully', $chat->load('participants'), 201);
        } catch (\Exception $e) {
            DB::rollback();
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

        switch ($user->role) {
            case 'national-admin':
                break;

            case 'state-admin':
                foreach ($participants as $participant) {
                    if (!in_array($participant->role, ['member', 'support-staff']) ||
                        ($participant->role === 'member' && $participant->state !== $user->state)) {
                        abort(403, 'You can only chat with members from your state and support staff');
                    }
                }
                break;

            case 'support-staff':
                foreach ($participants as $participant) {
                    if ($participant->role !== 'member') {
                        abort(403, 'Support staff can only chat with members');
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
     * @param \App\Models\Chat $chat
     * @return ApiResponse
     */
    public function show(Chat $chat)
    {
        try {
            $user = auth()->user();

            if (!$chat->participants->contains($user->id)) {
                abort(403, 'You are not a participant in this chat');
            }

            return ApiResponse::success('Chat details fetched', $chat->load(['participants', 'messages.user', 'messages.replyTo.user']));
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

            switch ($user->role) {
                case 'national-admin':
                    break;

                case 'state-admin':
                    $query->where(function ($q) use ($user) {
                        $q->role('support-staff', 'api')
                        ->orWhere(function ($subQ) use ($user) {
                            $subQ->role('member', 'api')
                                ->where('state', $user->state);
                        });
                    });
                    break;

                case 'support-staff':
                    $query->role('member', 'api');
                    break;

                default:
                    return ApiResponse::success('Available users fetched successfully', []);
            }

            return ApiResponse::success('Available users fetched successfully', $query->select('id', 'name', 'email', 'state')->get());
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }
}
