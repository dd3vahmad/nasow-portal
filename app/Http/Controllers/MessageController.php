<?php
namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Http\Requests\Chats\StoreMessageRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    /**
     * Sends a message
     *
     * @param StoreMessageRequest $request
     * @param int $chat
     * @throws \Exception
     * @return ApiResponse
     */
    public function store(StoreMessageRequest $request, int $chatId)
    {
        try {
            $data = $request->validated();
            $user = auth()->user();
            
            // Update user's last activity for online status
            $user->updateLastActivity();
            
            $chat = Chat::findOrFail($chatId);

        if (!$chat->participants->contains($user->id)) {
            return ApiResponse::error('You are not a participant in this chat', 403);
        }

        $attachments = [];

        if (!empty($data['attachments'])) {
            foreach ($data['attachments'] as $index => $file) {
                try {
                    if (!$file || !$file instanceof \Illuminate\Http\UploadedFile || !$file->isValid()) {
                        throw new \Exception('Invalid or missing file at index ' . $index . ': ' . ($file ? $file->getClientOriginalName() : 'No file'));
                    }

                    $result = cloudinary()->uploadApi()->upload($file->getRealPath(), [
                        'folder' => 'chat_attachments/' . $user->id,
                        'resource_type' => 'auto',
                    ]);

                    if (!isset($result['secure_url'])) {
                        throw new \Exception('Invalid Cloudinary response: Missing secure_url');
                    }

                    $attachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'url' => $result['secure_url'],
                        'public_id' => $result['public_id'],
                        'size' => $file->getSize(),
                        'type' => $file->getMimeType(),
                        'format' => $result['format'] ?? null,
                    ];
                } catch (\Exception $e) {
                    Log::error('File upload failed: ' . $e->getMessage());
                    return ApiResponse::error('File upload failed: ' . $e->getMessage(), 422);
                }
            }
        }

        $message = Message::create([
            'chat_id' => $chat->id,
            'user_id' => $user->id,
            'content' => $data['content'] ?? null,
            'type' => $data['type'] ?? 'text',
            'reply_to' => $data['reply_to'] ?? null,
            'attachments' => $attachments,
        ]);

        broadcast(new MessageSent($message))->toOthers();

        $notificationMessage = $chat->type === 'private'
            ? "You have a new message from {$user->name}"
            : "You have a new message in {$chat->name}";

        foreach ($chat->participants as $participant) {
            if ($participant->id !== $user->id) {
                $participant->sendNotification($notificationMessage);
            }
        }

        return ApiResponse::success('Message sent successfully', $message->load(['user', 'replyTo.user']), 201);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Sets user typing state
     *
     * @param \Illuminate\Http\Request $request
     * @param int $chatId
     * @return ApiResponse
     */
    public function typing(Request $request, int $chatId)
    {
        $request->validate([
            'is_typing' => 'required|boolean',
        ]);

        try {
            $user = auth()->user();

            $chat = Chat::find($chatId);
            if (!$chat->participants->contains($user->id)) {
                return ApiResponse::error('You are not a participant in this chat', 403);
            }

            broadcast(new UserTyping($chat->id, $user, $request->is_typing))->toOthers();

            return ApiResponse::success('Typing status updated');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Downloads message attachments
     *
     * @param mixed $messageId
     * @param mixed $attachmentIndex
     * @return ApiResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function downloadAttachment($messageId, $attachmentIndex)
    {
        $message = Message::findOrFail($messageId);
        $user = auth()->user();

        if (!$message->chat->participants->contains($user->id)) {
            return ApiResponse::error('You do not have access to this message', 403);
        }

        $attachments = $message->attachments ?? [];
        if (!isset($attachments[$attachmentIndex])) {
            return ApiResponse::error('File not found', 404);
        }

        $attachment = $attachments[$attachmentIndex];

        try {
            if (!isset($attachment['url'])) {
                return ApiResponse::error('File URL not found', 404);
            }
            return redirect($attachment['url']);

        } catch (\Exception $e) {
            Log::error('File download failed: ' . $e->getMessage());
            return ApiResponse::error( 'File not found or inaccessible', 404);
        }
    }

    /**
     * Clear all messages from a chat
     *
     * @param int $chatId
     * @return ApiResponse
     */
    public function clearChatMessages(int $chatId)
    {
        try {
            $user = auth()->user();
            $chat = Chat::findOrFail($chatId);

            // Check if user is a participant
            if (!$chat->participants->contains($user->id)) {
                return ApiResponse::error('You are not a participant in this chat', 403);
            }

            // For group chats, only admins can clear messages
            if ($chat->type === 'group') {
                $currentUserParticipant = $chat->participants()->where('user_id', $user->id)->first();
                if (!$currentUserParticipant || !$currentUserParticipant->pivot->is_admin) {
                    return ApiResponse::error('Only chat admins can clear group chat messages', 403);
                }
            }

            // Delete all messages in the chat
            $chat->messages()->delete();

            return ApiResponse::success('Chat messages cleared successfully');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }
}
