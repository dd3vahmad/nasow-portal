<?php
namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Http\Requests\Chats\StoreMessageRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Chat;
use App\Models\Message;
use App\Models\MessageRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        $data = $request->validated();
        $user = auth()->user();

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

        MessageRead::create([
            'message_id' => $message->id,
            'user_id' => $user->id,
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return ApiResponse::success('Message sent successfully', $message->load(['user', 'replyTo.user']), 201);
    }

    /**
     * Marks a message as read
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Chat $chat
     * @return ApiResponse
     */
    public function markAsRead(Request $request)
    {
        try {
            $user = auth()->user();

            $messageIds = $request->input('message_ids', []);

            foreach ($messageIds as $messageId) {
                MessageRead::firstOrCreate([
                    'message_id' => $messageId,
                    'user_id' => $user->id,
                ]);
            }

            return ApiResponse::success('Messages marked as read');
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
}
