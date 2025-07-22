<?php

namespace App\Http\Controllers;

use App\Http\Requests\CPD\StoreCpdActivityRequest;
use App\Http\Resources\CpdLogResource;
use App\Http\Responses\ApiResponse;
use App\Models\CpdActivity;
use App\Models\CpdLog;
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
    // public function log(LogCpdActivityRequest $request) {
    //     try {
    //         $data = $request->validated();
    //         $certificate = $data['certificate'] ?? null;

    //         $details = [
    //             'title' => $data['title'],
    //             'description' => $data['description'],
    //             'type' => $data['type'],
    //             'credit_hours' => $data['credit_hours'],
    //             'hosting_body' => $data['hosting_body'] ?? 'NASOW',
    //         ];

    //         if (isset($certificate)) {
    //             $result = cloudinary()->uploadApi()->upload($certificate->getRealPath(), [
    //                 'folder' => 'activity_certificate',
    //                 'resource_type' => 'auto',
    //             ]);
    //             $secure_url = $result['secure_url'] ?? null;

    //             if (!isset($secure_url)) {
    //                 throw new \Exception('Invalid Cloudinary response: Missing secure_url');
    //             }

    //             $details['certificate_url'] = $secure_url;
    //         }
    //         $activity = CpdActivity::create($details)->get();

    //         return ApiResponse::success('Activity created successfully', $activity);
    //     } catch (\Throwable $th) {
    //         return ApiResponse::error($th->getMessage());
    //     }
    // }

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
                    $query->where('title', 'like', $q)->orWhere('description', 'like', $q);
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
            $state = $user->details->state;
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
}
