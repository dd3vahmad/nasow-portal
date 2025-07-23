<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\AuditLog;

class ActionLogger {
    public static function log(string $type, string $description, ?int $userId = null, ?int $stateId = null, array $meta = []): void {
        Activity::create([
            'type' => $type,
            'description' => $description,
            'user_id' => $userId,
            'state_id' => $stateId,
            'meta' => json_encode($meta),
        ]);
    }

    public static function audit(string $action, int $adminId, array $meta = []): void {
        AuditLog::create([
            'action' => $action,
            'admin_id' => $adminId,
            'meta' => json_encode($meta),
        ]);
    }
}
