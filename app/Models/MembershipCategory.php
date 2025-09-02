<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipCategory extends Model
{
    use HasFactory;

    protected $table = 'membership_categories';

    protected $fillable = [
        'name',
        'slug',
        'currency',
        'price',
    ];

    /**
     * Cast the price to a decimal.
     */
    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * A category can have many memberships.
     */
    public function memberships()
    {
        return $this->hasMany(UserMembership::class, 'category', 'slug');
    }
}
