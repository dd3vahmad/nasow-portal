<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CPDLog extends Model
{
    protected $fillables = [
        'title',
        'description',
        'member_id',
        'activity_id',
        'completed_at',
        'credit_hours',
        'certificate_url',
        'status'
    ];

    /**
     * Log activity
     * @return void
     */
    public function activity() {
        $this->belongsTo(CPDActivity::class);
    }
}
