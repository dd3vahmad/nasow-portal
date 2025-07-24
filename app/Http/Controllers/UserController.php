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
            $user_id = $user->id ?? null;

            $details = UserDetails::where('user_id', $user_id)->first();

            $user_details = [
                'id' => $user_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'last_login' => $user->last_login ?? null,
                'reg_status' => $user->reg_status ?? null,
            ];

            if ($details) {
                $user_details = array_merge($user_details, [
                    'first_name' => $details->first_name ?? null,
                    'last_name' => $details->last_name ?? null,
                    'other_name' => $details->other_name ?? null,
                    'gender' => $details->gender ?? null,
                    'dob' => $details->dob ?? null,
                    'address' => $details->address ?? null,
                    'specialization' => $details->specialization ?? null,
                    'state' => $details->state ?? null,
                    'phone' => $details->phone ?? null,
                ]);
            }

            return ApiResponse::success('User details fetched successfully', $user_details);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
