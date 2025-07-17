<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserEmployment\StoreUserEmploymentsRequest;
use App\Http\Responses\ApiResponse;
use App\Models\UserEmployment;
use Illuminate\Support\Facades\Auth;

class UserEmploymentsController extends Controller
{
    /**
     * Add user employments
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreUserEmploymentsRequest $request)
    {
        try {
            $user = Auth::user();
            $employmentsPayload = $request->validated();

            $savedEmployments = [];

            foreach ($employmentsPayload as $employmentData) {
                $employmentData['user_id'] = $user->id;

                $employment = UserEmployment::create($employmentData);

                $savedEmployments[] = $employment;
            }
            $user->update(['reg_status' => 'specialization']);

            return ApiResponse::success('Employments added successfully', $savedEmployments);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
