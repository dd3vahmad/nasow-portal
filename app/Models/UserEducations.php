<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserEducations extends Model {
    use HasFactory;

    protected $fillable = ['user_id', 'institution', 'course_of_study', 'qualification', 'year_of_graduation'];

    /* Relation */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
