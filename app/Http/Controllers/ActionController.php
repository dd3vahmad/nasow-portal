<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class ActionController extends Controller
{
    /**
     * Get most recent activities
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recentActivities(Request $request)
    {
        $user = auth()->user();

        $query = Activity::latest()->limit(20);

        if ($user->hasRole('state-admin')) {
            $query->where('state_id', $user->details->state_id);
        }

        return response()->json($query->get());
    }

    /**
     * Get admins audit logs
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function auditLogs()
    {
        return AuditLog::with('admin')->latest()->limit(50)->get();
    }
}
