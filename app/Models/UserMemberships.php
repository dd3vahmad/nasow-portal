<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMemberships extends Model {
    use HasFactory;

    protected $fillable = [
        'category',
        'status',
        'verified_at',
        'expires_at',
        'user_id',
        'reviewed',
        'reviewed_by',
        'reviewed_at',
        'approval_requested_at',
        'comment'
    ];

    protected $appends = ['is_active'];

    /**
     * Type casts
     * @var array
     */
    protected $casts = [
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approval_requested_at' => 'datetime',
    ];

    /* Relation */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* Relation */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Checks if membership is active
     */
    public function isActive(): bool
    {
        return ($this->expires_at ?? null) && ($this->expires_at ?? null)->isFuture() && $this->status === 'verified';
    }

    /**
    * Scope for memberships that are still active (not yet expired)
    */
    public function scopeActive(Builder $query)
    {
        return $query->where('expires_at', '>', Carbon::now())->where('status', 'verified');
    }

    /**
     * (is_active) Accessor setter
     *
     * @return bool
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->isActive();
    }

    /**
     * Revokes user current membership when called
     */
    public function revoke(): bool
    {
        $this->expires_at = Carbon::now();
        return $this->save();
    }
}
