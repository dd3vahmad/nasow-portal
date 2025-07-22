<?php

namespace App\Http\Controllers;

use App\Http\Requests\CPD\StoreCpdActivityRequest;
use App\Http\Responses\ApiResponse;
use App\Models\CpdActivity;
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
            $activity = CpdActivity::create($details);

            return ApiResponse::success('Activity created successfully', $activity);
        } catch (\Throwable $th) {
            var_dump('omoooo');
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
            $activities = CpdActivity::get();

            return ApiResponse::success('Activities fetched successfully', $activities);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
