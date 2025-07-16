<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserEducations\StoreUserEducationsRequest;
use App\Http\Responses\ApiResponse;
use App\Models\UserEducations;
use Illuminate\Support\Facades\Auth;

class UserEducationsController extends Controller
{
    /**
     * Add user educations
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreUserEducationsRequest $request)
    {
        try {
            $user = Auth::user();
            $educationsPayload = $request->validated();

            $savedEducations = [];

            foreach ($educationsPayload as $educationData) {
                $educationData['user_id'] = $user->id;

                $education = UserEducations::create($educationData);

                $savedEducations[] = $education;
            }

            return ApiResponse::success('Educations added successfully', $savedEducations);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
