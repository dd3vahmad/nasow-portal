<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CPDActivity extends Model
{
    protected $fillables = [
        'title',
        'description',
        'type',
        'credit_hours',
        'hosting_body',
        'certificate_url'
    ];

    /** Relations */
    public function logs() {
        return $this->hasMany(CPDLog::class);
    }
}
