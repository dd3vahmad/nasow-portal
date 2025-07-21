<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDetails extends Model {
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'other_name',
        'gender',
        'dob',
        'specialization',
        'address',
        'phone',
        'state'
    ];

    /* Relation */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
