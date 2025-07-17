<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\UserDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Get authenticated user details
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        try {
            $user = Auth::user();
            $role = $user->getRoleNames()->first();

            $details = UserDetails::where('user_id', $user->id)->first();

            $user_details = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'last_login' => $user->last_login,
                'reg_status' => $user->reg_status,
            ];

            if ($details) {
                $user_details = array_merge($user_details, [
                    'first_name' => $details->first_name,
                    'last_name' => $details->last_name,
                    'other_name' => $details->other_name,
                    'gender' => $details->gender,
                    'dob' => $details->dob,
                    'address' => $details->address,
                    'specialization' => $details->specialization,
                    'state' => $details->state,
                    'phone' => $details->phone,
                ]);
            }

            return ApiResponse::success('User details fetched successfully', $user_details);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
