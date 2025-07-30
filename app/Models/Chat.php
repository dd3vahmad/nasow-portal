<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $fillable = ['name', 'type', 'created_by', 'metadata'];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function participants()
    {
        return $this->belongsToMany(User::class, 'chat_participants')
                    ->withPivot(['joined_at', 'last_seen', 'is_admin'])
                    ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function unreadCount($userId)
    {
        return $this->messages()
            ->whereDoesntHave('reads', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->count();
    }
}
