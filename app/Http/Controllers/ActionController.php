<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
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
        try {
            $user = auth()->user();

            $query = Activity::latest()->limit(20);

            if ($user->hasRole('state-admin')) {
                $query->where('state', $user->details->state ?? null);
            }

            $recent_activities = $query->get();

            return ApiResponse::success('Recent activities fetched successfully', $recent_activities);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Get admins audit logs
     *
     * @return ApiResponse
     */
    public function auditLogs()
    {
        try {
            $audit_logs = AuditLog::with('admin')->latest()->limit(50)->get();

            return ApiResponse::success('Recent activities fetched successfully', $audit_logs);
        } catch (\Throwable $th) {
           return ApiResponse::error($th->getMessage());
        }
    }
}
