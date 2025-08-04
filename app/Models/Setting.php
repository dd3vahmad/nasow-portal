<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'two_factor_enabled', 'email_notification', 'sms_notification', 'color_mode', 'language', 'metadata'
    ];

    protected $casts = [
        'two_factor_enabled' => 'boolean',
        'email_notification' => 'boolean',
        'sms_notification' => 'boolean',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
