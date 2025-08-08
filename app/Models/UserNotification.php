<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    protected $fillable = [
        'user_id', 'message', 'type', 'read_at'
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function markAsRead(): bool
    {
        return $this->update(['read_at' => now()]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getIsReadAttribute(): bool
    {
        return !is_null($this->read_at);
    }
}
