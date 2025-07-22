<?php

namespace App\Http\Controllers;

use App\Http\Requests\CPD\StoreCPDActivityRequest;
use App\Http\Responses\ApiResponse;
use App\Models\CPDActivity;
use Illuminate\Http\Request;

class CPDController extends Controller
{
    /**
     * Add CPD activity
     *
     * @param StoreCPDActivityRequest $request
     * @return ApiResponse
     */
    public function store(StoreCPDActivityRequest $request) {
        try {
            $data = $request->validated();
            $certificate = $data['certificate'];

            $details = [
                'title' => $data['title'],
                'description' => $data['description'],
                'type' => $data['type'],
                'credit_hours' => $data['credit_hours'],
                'hosting_body' => $data['hosting_body'],
            ];

            if (isset($certificate)) {
                $result = cloudinary()->uploadApi()->upload($certificate->getRealPath(), [
                    'folder' => 'activity_certificate',
                    'resource_type' => 'auto',
                ]);
                $secure_url = $result['secure_url'];

                if (!isset($secure_url)) {
                    throw new \Exception('Invalid Cloudinary response: Missing secure_url');
                }

                $details['certificate_url'] = $secure_url;
            }
            $activity = CPDActivity::create($details);

            return ApiResponse::success('Activity created successfully', $activity);
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
            $activities = CPDActivity::get();

            return ApiResponse::success('Activities fetched successfully', $activities);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
