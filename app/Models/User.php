<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'last_login',
        'reg_status',
        'avatar_url'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'remember_token',
    ];

    /** Relations */
    public function credentials()
    {
        return $this->hasOne(UserCredential::class);
    }
    public function details()
    {
        return $this->hasOne(UserDetails::class);
    }

    public function providers()
    {
        return $this->hasMany(UserAuthProvider::class);
    }

    public function memberships()
    {
        return $this->hasMany(UserMemberships::class);
    }

    /** Password / email verification overrides */
    public function getAuthPassword()
    {
        return optional($this->credentials)->password ?? null;
    }

    public function getEmailVerifiedAtAttribute()
    {
        return optional($this->credentials)->email_verified_at ?? null;
    }

    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    public function markEmailAsVerified(): bool
    {
        return $this->credentials()->update(['email_verified_at' => now()]);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\VerifyEmail);
    }

    public function sendPendingMembershipNotification(): void
    {
        $this->notify(new \App\Notifications\PendingMembership);
    }

    public function sendMembershipApprovedNotification(): void
    {
        $this->notify(new \App\Notifications\MembershipApproved);
    }

    public function sendMembershipSuspendedNotification()
    {
        $this->notify(new \App\Notifications\MembershipSuspended);
    }

    public function sendAssignedTicketNotification(Ticket $ticket)
    {
        $this->notify(new \App\Notifications\AssignedTicket($ticket));
    }

    public function sendClosedTicketNotification(Ticket $ticket)
    {
        $this->notify(new \App\Notifications\ClosedTicket($ticket));
    }
}
