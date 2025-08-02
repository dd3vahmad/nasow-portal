<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Chat;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Chat presence channel - only participants can join
Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    $chat = Chat::find($chatId);

    if (!$chat || !$chat->participants->contains($user->id)) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'role' => $user->getRoleNames()->first(),
        'state' => $user->details->state ?? null,
    ];
});
