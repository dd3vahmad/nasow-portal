<?php

namespace App\Http\Controllers;

use App\Enums\ActivityType;
use App\Http\Requests\CPD\StoreCpdActivityRequest;
use App\Http\Requests\CPD\LogCpdActivityRequest;
use App\Http\Resources\CpdActivityResource;
use App\Http\Resources\CpdLogResource;
use App\Http\Responses\ApiResponse;
use App\Models\CpdActivity;
use App\Models\CpdLog;
use App\Models\User;
use App\Services\ActionLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CPDController extends Controller
{
    /**
     * Add CPD activity
     *
     * @param StoreCpdActivityRequest $request
     * @return ApiResponse
     */
    public function store(StoreCpdActivityRequest $request) {
        try {
            $user = auth()->user();
            $data = $request->validated();
            $certificate = $data['certificate'] ?? null;

            $details = [
                'title' => $data['title'],
                'description' => $data['description'],
                'type' => $data['type'],
                'credit_hours' => $data['credit_hours'],
                'hosting_body' => $data['hosting_body'] ?? 'NASOW',
            ];

            if (isset($certificate)) {
                $result = cloudinary()->uploadApi()->upload($certificate->getRealPath(), [
                    'folder' => 'activity_certificate',
                    'resource_type' => 'auto',
                ]);
                $secure_url = $result['secure_url'] ?? null;

                if (!isset($secure_url)) {
                    throw new \Exception('Invalid Cloudinary response: Missing secure_url');
                }

                $details['certificate_url'] = $secure_url;
            }
            $activity = CpdActivity::create($details)->get();
            ActionLogger::audit("CPD activity created: {$data['title']}", $user->id ?? null);

            return ApiResponse::success('Activity created successfully', $activity);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Log CPD activity
     *
     * @param LogCpdActivityRequest $request
     * @return ApiResponse
     */
    public function log(LogCpdActivityRequest $request) {
        try {
            $user = auth()->user();
            $user_id = $user->id ?? null;
            $data = $request->validated();
            $certificate = $data['certificate'] ?? null;
            $activity_id = $data['activity_id'] ?? null;

            $details = [
                'title' => $data['title'],
                'description' => $data['description'],
                'completed_at' => $data['completed_at'],
                'member_id' => $user_id,
            ];

            if (isset($activity_id)) {
                $activity = CpdActivity::find($activity_id);
                if (!$activity) {
                    return ApiResponse::error('Cpd activity with this id not found', 404);
                }
                $details['activity_id'] = $activity_id;
                $details['credit_hours'] = $activity->credit_hours ?? null;
            } else {
                $details['credit_hours'] = $data['credit_hours'];
            }

            if (isset($certificate)) {
                $result = cloudinary()->uploadApi()->upload($certificate->getRealPath(), [
                    'folder' => 'activity_certificate',
                    'resource_type' => 'auto',
                ]);
                $secure_url = $result['secure_url'] ?? null;

                if (!isset($secure_url)) {
                    throw new \Exception('Invalid Cloudinary response: Missing secure_url');
                }

                $details['certificate_url'] = $secure_url;
            }
            $log = CpdLog::create($details)->get();
            ActionLogger::log(
                ActivityType::CPD->value,
                "CPD logged: {$user->name}",
                $user_id,
                $user->details->state ?? null
            );
            $user->sendNotification('Your CPD activity has been logged successfully', 'cpd');

            return ApiResponse::success('Log created successfully', $log);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Get all CPD logs
     *
     * @param Request $request
     * @return ApiResponse
     */
    public function index(Request $request) {
        try {
            $status = $request->query('status', '');
            $state = $request->query('state', '');
            $q = $request->query('q', '');
            $type = $request->query('type', '');

            $logs = CpdLog::when($status, function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->when($state, function ($query) use ($state) {
                    $query->whereHas('member', function ($query) use ($state) {
                        $query->whereHas('details', function ($query) use ($state) {
                            $query->where('state', $state);
                        });
                    });
                })
                ->when($type, function ($query) use ($type) {
                    $query->whereHas('activity', function ($query) use ($type) {
                        $query->where('type', $type);
                    });
                })
                ->when($q, function ($query) use ($q) {
                    $query->where('title', 'like', "%$q%")->orWhere('description', 'like', "%$q%");
                })
                ->get();

            return ApiResponse::success('Logs fetched successfully', CpdLogResource::collection($logs));
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Get state CPD logs
     *
     * @param Request $request
     * @return ApiResponse
     */
    public function state(Request $request) {
        try {
            $user = auth()->user();
            $state = $user->details->state ?? null;
            $status = $request->query('status', '');
            $q = $request->query('q', '');
            $type = $request->query('type', '');

            $logs = CpdLog::when($status, function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->when($state, function ($query) use ($state) {
                    $query->whereHas('member', function ($query) use ($state) {
                        $query->whereHas('details', function ($query) use ($state) {
                            $query->where('state', $state);
                        });
                    });
                })
                ->when($type, function ($query) use ($type) {
                    $query->whereHas('activity', function ($query) use ($type) {
                        $query->where('type', $type);
                    });
                })
                ->when($q, function ($query) use ($q) {
                    $query->where('title', 'like', "%$q%")->orWhere('description', 'like', "%$q%");
                })
                ->get();

            return ApiResponse::success('Logs fetched successfully', CpdLogResource::collection($logs));
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Get the year's CPD activities
     *
     * @param Request $request
     * @return ApiResponse
     */
    public function current(Request $request) {
        try {
            $q = $request->query('q', '');
            $type = $request->query('type', '');

            $logs = CpdActivity::whereYear('created_at', Carbon::now()->year)->when($type, function ($query) use ($type) {
                    $query->where('type', $type);
                })
                ->when($q, function ($query) use ($q) {
                    $query->where('title', 'like', "%$q%")->orWhere('description', 'like', "%$q%");
                })
                ->get();

            return ApiResponse::success('Activities fetched successfully', CpdActivityResource::collection($logs));
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Get the year's recent CPD activities
     *
     * @param Request $request
     * @return ApiResponse
     */
    public function recent(Request $request) {
        try {
            $q = $request->query('q', '');
            $type = $request->query('type', '');
            $limit = $request->query('limit', 5);

            $logs = CpdActivity::whereYear('created_at', Carbon::now()->year)->when($type, function ($query) use ($type) {
                    $query->where('type', $type);
                })
                ->when($q, function ($query) use ($q) {
                    $query->where('title', 'like', "%$q%")->orWhere('description', 'like', "%$q%");
                })
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return ApiResponse::success('Activities fetched successfully', CpdActivityResource::collection($logs));
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Get all CPD activities
     *
     * @param Request $request
     * @return ApiResponse
     */
    public function activities(Request $request) {
        try {
            $q = $request->query('q', '');
            $type = $request->query('type', '');

            $logs = CpdActivity::when($type, function ($query) use ($type) {
                    $query->where('type', $type);
                })
                ->when($q, function ($query) use ($q) {
                    $query->where('title', 'like', "%$q%")->orWhere('description', 'like', "%$q%");
                })
                ->get();

            return ApiResponse::success('Activities fetched successfully', CpdActivityResource::collection($logs));
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Approve cpd log
     *
     * @param int $id
     * @return ApiResponse
     */
    public function approve(int $id) {
        try {
            $user = auth()->user();
            $log = CpdLog::find($id);
            if (!$log) {
                return ApiResponse::error('Cpd log not found');
            }
            $log->update([ 'status' => 'approved' ]);
            $title = $log->title ?? '';
            ActionLogger::audit("CPD log approved: {$title}", $user->id ?? null);

            $member = User::find($log->member_id);
            $member->sendNotification('Your CPD activity (' . $title . ') has been approved', 'cpd');

            return ApiResponse::success('Log approved successfully', $log);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Reject cpd log
     *
     * @param int $id
     * @return ApiResponse
     */
    public function reject(int $id) {
        try {
            $user = auth()->user();
            $log = CpdLog::find($id);
            if (!$log) {
                return ApiResponse::error('Cpd log not found');
            }
            $log->update([ 'status' => 'rejected' ]);
            $title = $log->title ?? '';
            ActionLogger::audit("CPD log rejected: {$title}", $user->id ?? null);

            $member = User::find($log->member_id);
            $member->sendNotification('Your CPD activity (' . $title . ') was rejected', 'cpd');

            return ApiResponse::success('Log approved successfully', $log);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Get CPD stats for member
     *
     * @param Request $request
     * @return ApiResponse
     */
    public function stats(Request $request)
    {
        try {
            $user = auth()->user();
            $user_id = $user->id ?? null;

            $total = CpdLog::where('member_id', $user_id)
                ->where('status', 'approved')
                ->sum('credit_hours');

            $types = CpdLog::where('member_id', $user_id)
                ->whereYear('created_at', Carbon::now()->year)
                ->where('status', 'approved')->with('activity')
                ->get()
                ->groupBy(fn($log) => $log->activity->type ?? 'unknown')
                ->map(function ($group) {
                    return $group->sum('credit_hours');
                });

            $stats = [
                'total' => $total,
                'types' => $types,
            ];

            return ApiResponse::success('Stats fetched successfully', $stats);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Get all member CPD logs
     *
     * @param Request $request
     * @return ApiResponse
     */
    public function member(Request $request) {
        try {
            $user = auth()->user();
            $status = $request->query('status', '');
            $q = $request->query('q', '');
            $type = $request->query('type', '');

            $logs = CpdLog::where('member_id', $user->id ?? null)
                ->when($status, function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->when($type, function ($query) use ($type) {
                    $query->whereHas('activity', function ($query) use ($type) {
                        $query->where('type', $type);
                    });
                })
                ->when($q, function ($query) use ($q) {
                    $query->where('title', 'like', "%$q%")->orWhere('description', 'like', "%$q%");
                })
                ->get();

            return ApiResponse::success('Logs fetched successfully', CpdLogResource::collection($logs));
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
