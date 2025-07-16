<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class UserCredential extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'password', 'email', 'email_verified_at'];

    /* Mutators */
    public function setPasswordAttribute($raw)
    {
        $this->attributes['password'] = Hash::make($raw);
    }

    /* Relation */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $dates = ['email_verified_at'];
}
