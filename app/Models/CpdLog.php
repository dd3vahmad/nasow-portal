<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CpdLog extends Model
{
    protected $fillable = [
        'title',
        'description',
        'member_id',
        'activity_id',
        'completed_at',
        'credit_hours',
        'certificate_url',
        'status'
    ];

    /** Relation */
    public function activity() {
        return $this->belongsTo(CpdActivity::class);
    }

    public function member() {
        return $this->belongsTo(User::class);
    }
}
