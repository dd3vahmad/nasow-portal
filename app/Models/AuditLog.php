<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [ 'action', 'admin_id', 'meta' ];

    /**
     *  Audit admin
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, AuditLog>
     */
    public function admin() { return $this->belongsTo(User::class, 'admin_id'); }
}
