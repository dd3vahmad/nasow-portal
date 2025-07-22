<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CpdActivity extends Model
{
    protected $fillable = [
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
