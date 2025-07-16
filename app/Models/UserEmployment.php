<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserEmployment extends Model {
    use HasFactory;

    protected $fillable = ['user_id', 'employer', 'employer_address', 'role', 'is_current', 'year'];

    /* Relation */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
