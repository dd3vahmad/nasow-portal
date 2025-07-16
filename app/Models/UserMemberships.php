<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMemberships extends Model {
    use HasFactory;

    protected $fillable = ['no', 'category', 'status', 'verified_at', 'expires_at', 'user_id'];

    /* Relation */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
