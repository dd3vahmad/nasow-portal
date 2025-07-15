<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAuthProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'provider_user_id',
        'access_token',
        'refresh_token',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
