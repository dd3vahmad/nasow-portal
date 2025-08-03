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
            $avgCpdHours = $totalCpdLogMembers ? $totalCpdHours / $totalCpdLogMembers : 0;

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
                'dues' => 0,
                'total_payments' => 0
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

    /**
     * Get national admin's chart data
     * @return ApiResponse
     */
    public function national_charts() {
        try {
            $baseQuery = User::role('member', 'api')->where('reg_status', 'done');
            $completed_cpds = CpdLog::where('status', 'approved')->whereYear('created_at', Carbon::now()->year)
                ->whereNotNull('completed_at')->count();
            $incomplete_cpds = CpdLog::whereYear('created_at', Carbon::now()->year)
                ->whereNull('completed_at')->count();

            $members_per_state = (clone $baseQuery)
                ->get()
                ->groupBy(fn($log) => optional($log->details)->state ?? 'others')
                ->map(function ($group) {
                    return $group->count();
                });

            // Payment trends (count by month)
            $payment_trends = collect(range(1, 12))
                ->mapWithKeys(fn($m) => [strtolower(Carbon::create()->month($m)->format('F')) => 0])
                ->toArray();

            // Payment::whereBetween('created_at', [$from, $to])
            //     ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            //     ->groupBy('month')
            //     ->get()
            //     ->each(function ($item) use (&$payment_trends) {
            //         $monthName = strtolower(Carbon::create()->month($item->month)->format('F'));
            //         $payment_trends[$monthName] = $item->count;
            //     });

            $cpd_completion_rates = [
                'completed' => $completed_cpds,
                'incomplete' => $incomplete_cpds
            ];

            return ApiResponse::success('National admin dashboard charts fetched successfully', [
                'cpd_completion_rate' => $cpd_completion_rates,
                'payments_over_time' => $payment_trends,
                'members_per_state' => $members_per_state
            ]);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * National stats and breakdown
     *
     * @param \Illuminate\Http\Request $request
     * @return ApiResponse
     */
    public function national_breakdown(Request $request) {
        try {
            $from = $request->query('from') ? Carbon::parse($request->query('from'))->startOfDay() : Carbon::now()->startOfYear();
            $to = $request->query('to') ? Carbon::parse($request->query('to'))->endOfDay() : Carbon::now()->endOfYear();

            $baseQuery = User::role('member', 'api')->where('reg_status', 'done');

            // CPD completion rate
            $completed_cpds = CpdLog::whereBetween('created_at', [$from, $to])
                ->whereNotNull('completed_at')
                ->count();

            $incomplete_cpds = CpdLog::whereBetween('created_at', [$from, $to])
                ->whereNull('completed_at')
                ->count();

            $cpd_completion_rate = [
                'completed' => $completed_cpds,
                'incomplete' => $incomplete_cpds
            ];

            // Members per state
            $members_per_state = (clone $baseQuery)
                ->get()
                ->groupBy(fn($log) => optional($log->details)->state ?? 'others')
                ->map(fn($group) => $group->count());

            // Top 5 CPD contributors (by total credit hours)
            $top_cpd_contributors = CpdLog::where('status', 'approved')
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('completed_at')
                ->selectRaw('member_id, SUM(credit_hours) as total_hours')
                ->groupBy('member_id')
                ->orderByDesc('total_hours')
                ->take(5)
                ->with('member:id,name')
                ->get()
                ->map(fn($log) => [
                    'name' => optional($log->member)->name ?? 'Unknown',
                    'hours' => (float) $log->total_hours,
                ]);

            // Payment trends (count by month)
            $payment_trends = collect(range(1, 12))
                ->mapWithKeys(fn($m) => [strtolower(Carbon::create()->month($m)->format('F')) => 0])
                ->toArray();

            // Payment::whereBetween('created_at', [$from, $to])
            //     ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            //     ->groupBy('month')
            //     ->get()
            //     ->each(function ($item) use (&$payment_trends) {
            //         $monthName = strtolower(Carbon::create()->month($item->month)->format('F'));
            //         $payment_trends[$monthName] = $item->count;
            //     });

            // Membership growth per month
            $membership_growth_per_month = collect(range(1, 12))
                ->mapWithKeys(fn($m) => [strtolower(Carbon::create()->month($m)->format('F')) => 0])
                ->toArray();

            UserMemberships::whereBetween('verified_at', [$from, $to])
                ->selectRaw('MONTH(verified_at) as month, COUNT(*) as count')
                ->groupBy('month')
                ->get()
                ->each(function ($item) use (&$membership_growth_per_month) {
                    $monthName = strtolower(Carbon::create()->month($item->month)->format('F'));
                    $membership_growth_per_month[$monthName] = $item->count;
                });

            return ApiResponse::success('National report charts fetched successfully', [
                'cpd_completion_rate' => $cpd_completion_rate,
                'payments_over_time' => $payment_trends,
                'members_per_state' => $members_per_state,
                'top_cpd_contributors' => $top_cpd_contributors,
                'membership_growth_per_month' => $membership_growth_per_month,
            ]);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Membership application review stats
     *
     * @param \Illuminate\Http\Request $request
     * @return ApiResponse
     */
    public function review_stats(Request $request)
    {
        try {
            $memberships = UserMemberships::with('user')->get();

            $pending = 0;
            $underReview = 0;
            $pendingApproval = 0;
            $approved = 0;
            $suspended = 0;

            foreach ($memberships as $membership) {
                if ($membership->status === 'verified') {
                    $approved++;
                } elseif ($membership->status === 'suspended') {
                    $suspended++;
                } elseif ($membership->status === 'in-review') {
                    $underReview++;
                } elseif ($membership->status === 'pending-approval') {
                    $pendingApproval++;
                } elseif ($membership->status === 'pending') {
                    $pending++;
                }
            }

            return ApiResponse::success('Review statistics fetched successfully', [
                'pending' => $pending,
                'under-review' => $underReview,
                'pending-approval' => $pendingApproval,
                'approved' => $approved,
                'suspended' => $suspended,
            ]);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }


    /**
     * Get case manager specific stats
     *
     * @param \Illuminate\Http\Request $request
     * @return ApiResponse
     */
    public function cases_dashstats(Request $request) {
        try {
            $userId = auth()->id();

            $memberships = UserMemberships::where('reviewed_by', $userId)->get();
            $pending = UserMemberships::where('status', 'pending')->count();

            $underReview = 0;
            $pendingApproval = 0;
            $approved = 0;
            $suspended = 0;
            $completedToday = 0;

            $totalReviewTime = 0;
            $reviewCount = 0;

            foreach ($memberships as $membership) {
                switch ($membership->status) {
                    case 'verified':
                        $approved++;
                        break;
                    case 'suspended':
                        $suspended++;
                        break;
                    case 'in-review':
                        $underReview++;
                        break;
                    case 'pending-approval':
                        $pendingApproval++;
                        break;
                }

                if ($membership->approval_requested_at && $membership->approval_requested_at->isToday()) {
                    $completedToday++;
                }

                if (
                    $membership->approval_requested_at &&
                    $membership->reviewed_at
                ) {
                    $diffInMinutes = $membership->reviewed_at->diffInMinutes($membership->approval_requested_at);
                    $totalReviewTime += $diffInMinutes;
                    $reviewCount++;
                }
            }

            // Format average review time
            if ($reviewCount > 0) {
                $avgMinutes = round($totalReviewTime / $reviewCount);
                $hours = intdiv($avgMinutes, 60);
                $minutes = $avgMinutes % 60;

                $avgReviewTime = trim(
                    ($hours > 0 ? $hours . ' hour' . ($hours > 1 ? 's' : '') : '') . ' ' .
                    ($minutes > 0 ? $minutes . ' minute' . ($minutes > 1 ? 's' : '') : '')
                );
            } else {
                $avgReviewTime = '0 minutes';
            }

            return ApiResponse::success('Case manager dashboard statistics fetched successfully', [
                'pending' => $pending,
                'under-review' => $underReview,
                'pending-approval' => $pendingApproval,
                'approved' => $approved,
                'suspended' => $suspended,
                'completed-today' => $completedToday,
                'avg-review-time' => $avgReviewTime
            ]);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Get case manager specific charts
     *
     * @param \Illuminate\Http\Request $request
     * @return ApiResponse
     */
    public function cases_dashcharts(Request $request) {
        try {
            $memberships = UserMemberships::get();

            $pending = 0;
            $underReview = 0;
            $pendingApproval = 0;
            $approved = 0;
            $suspended = 0;

            // Step 1: Determine start month
            $start = $request->query('start');

            if ($start && preg_match('/^\d{4}-\d{2}$/', $start)) {
                $startDate = Carbon::createFromFormat('Y-m', $start)->startOfMonth();
            } else {
                $startDate = now()->copy()->subMonths(3)->startOfMonth(); // default: last 3 months + current
            }

            // Generate 4-month labels
            $months = [];
            for ($i = 0; $i < 4; $i++) {
                $label = $startDate->copy()->addMonths($i)->format('M Y');
                $key = $startDate->copy()->addMonths($i)->format('Y-m');
                $months[$key] = [
                    'label' => $label,
                    'reviews' => 0,
                    'approved' => 0,
                ];
            }

            // Step 2: Status distribution + review stats
            foreach ($memberships as $membership) {
                // Distribution
                switch ($membership->status) {
                    case 'verified':
                        $approved++;
                        break;
                    case 'suspended':
                        $suspended++;
                        break;
                    case 'in-review':
                        $underReview++;
                        break;
                    case 'pending-approval':
                        $pendingApproval++;
                        break;
                    case 'pending':
                        $pending++;
                        break;
                }

                // Reviews by month
                if ($membership->reviewed_at) {
                    $reviewMonth = $membership->reviewed_at->format('Y-m');
                    if (isset($months[$reviewMonth])) {
                        $months[$reviewMonth]['reviews']++;
                        if ($membership->status === 'verified') {
                            $months[$reviewMonth]['approved']++;
                        }
                    }
                }
            }

            return ApiResponse::success('Case manager dashboard charts fetched successfully', [
                'review-progress-distribution' => [
                    'pending' => $pending,
                    'under-review' => $underReview,
                    'pending-approval' => $pendingApproval,
                    'approved' => $approved,
                    'suspended' => $suspended,
                ],
                'monthly-review-trends' => array_values($months),
                'range-start' => $startDate->format('Y-m')
            ]);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
