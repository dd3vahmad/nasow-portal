<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'type',
        'description',
        'user_id',
        'state_id',
        'meta'
    ];

    protected $casts = [
        'meta' => 'array'
    ];

    /**
     * Activity Performer
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, Activity>
     */
    public function user() {
        return $this->belongsTo(User::class);
    }
}
