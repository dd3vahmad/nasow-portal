<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject',
        'email',
        'name',
        'state',
        'assigned_to',
        'assigned_at',
        'assigned_by',
        'status',
        'closed_at',
        'avg_response_time',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /* Actions */
    public function close()
    {
        $this->update(['closed_at' => now()]);
    }

    public function changeStatus(string $status)
    {
        $this->update(['status' => $status]);
    }

    /* Relationships */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function support()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }
}
