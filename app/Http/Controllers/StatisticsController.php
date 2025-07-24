<?php

namespace App\Http\Controllers;

use App\Enums\RoleType;
use App\Http\Responses\ApiResponse;
use App\Models\CpdLog;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserMemberships;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    /**
     * Get national admin dashboard stats
     *
     * @param Request $request
     * @return ApiResponse
     */
    public function national(Request $request) {
        try {
            $baseQuery = User::role('member', 'api')->where('reg_status', 'done');
            $baseCpdLogQuery = CpdLog::where('status', 'approved')
                ->whereYear('created_at', Carbon::now()->year);
            $totalCpdHours = (float) $baseCpdLogQuery->sum('credit_hours');
            $totalCpdLogMembers = $baseCpdLogQuery->distinct()->count('member_id');
            $avgCpdHours = $totalCpdHours / $totalCpdLogMembers;

            $totalMembers = (clone $baseQuery)->count();
            $totalActiveMembers = (clone $baseQuery)
                ->whereHas('memberships', function ($query) {
                    $query->active();
                })
                ->count();
            $newMembersForThisMonth = (clone $baseQuery)
                ->whereHas('memberships', function ($query) {
                    $query->whereMonth('verified_at', Carbon::now()->month);
                })
                ->count();
            $totalOpenTickets = Ticket::where('status', 'open')->count();

            $stats = [
                'total_members' => $totalMembers,
                'total_active_members' => $totalActiveMembers,
                'total_new_members_this_month' => $newMembersForThisMonth,
                'open_tickets' => $totalOpenTickets,
                'total_cpd_hours' => $totalCpdHours,
                'avg_cpd_hours' => $avgCpdHours,
                'dues' => 0
            ];

            return ApiResponse::success('Stats fetched successfully', $stats);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     *  Get members stats (counts)
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function members_count(Request $request) {
        try {
            $user = auth()->user();
            $userDetails = $user->details()->first();

            $state = $user->getRoleNames()[0] === RoleType::StateAdmin->value ? ($userDetails->state ?? null) : '';
            $state = $request->query('state', $state);

            $baseQuery = UserMemberships::whereHas('user', fn ($query) => $query->where('reg_status', 'done'))
                ->whereHas('user.details', function ($query) use ($state) {
                    if ($state) {
                        $query->where('state', $state);
                    }
                });

            $totalMembers = (clone $baseQuery)->count();
            $pendingMembers = (clone $baseQuery)->where('status', 'pending')->count();
            $approvedMembers = (clone $baseQuery)->where('status', 'verified')->count();
            $suspendedMembers = (clone $baseQuery)->where('status', 'suspended')->count();

            $stats = [
                'total' => $totalMembers,
                'pending' => $pendingMembers,
                'approved' => $approvedMembers,
                'suspended' => $suspendedMembers,
            ];

            return ApiResponse::success('Members stats fetched successfully', $stats);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
