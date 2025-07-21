<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    use HasFactory;

    protected $fillable = ['sender_id', 'ticket_id', 'message'];

    /* Relationships */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
