<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMemberships extends Model {
    use HasFactory;

    protected $fillable = ['category', 'status', 'verified_at', 'expires_at', 'user_id', 'reviewed', 'comment'];

    /* Relation */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Checks if membership is active
     */
    public function isActive(): bool
    {
        return ($this->expires_at ?? null) && ($this->expires_at ?? null)->isFuture();
    }

    /**
    * Scope for memberships that are still active (not yet expired)
    */
    public function scopeActive(Builder $query)
    {
        return $query->where('expires_at', '>', Carbon::now());
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
