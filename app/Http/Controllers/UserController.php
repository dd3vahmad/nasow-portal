<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\UserCredential;
use App\Models\UserDetails;
use App\Models\UserMemberships;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
            $firstMembership = UserMemberships::where('user_id', $user_id)
                ->orderBy('created_at', 'asc')
                ->first();

            $user_details = [
                'id' => $user_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'status' => $firstMembership?->status ?? null,
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

    /**
     * Updates user details
     *
     * @param \Illuminate\Http\Request $request
     * @return ApiResponse
     */
    public function update(Request $request) {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|min:3',
                'last_name' => 'required|string|min:3',
                'other_name' => 'nullable|string|min:3',
                'gender' => 'required|string|in:MALE,FEMALE',
                'dob' => 'required|date',
                'address' => 'required|string',
                'specialization' => 'nullable|string',
                'state' => 'nullable|string',
                'phone' => 'required|string',
            ]);

            $user = Auth::user();
            $fullName = $validated['first_name'] . ' ' . $validated['last_name'];
            if (!empty($validated['other_name'])) {
                $fullName .= ' ' . $validated['other_name'];
            }

            $user->name = $fullName;
            $user->save();

            $user_details = UserDetails::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'other_name' => $validated['other_name'],
                    'gender' => $validated['gender'],
                    'dob' => $validated['dob'],
                    'address' => $validated['address'],
                    'specialization' => $validated['specialization'] ?? null,
                    'state' => $validated['state'] ?? null,
                    'phone' => $validated['phone'],
                ]
            );

            return ApiResponse::success('User details updated successfully', $user_details);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Changes the user password
     *
     * @param \Illuminate\Http\Request $request
     * @return ApiResponse
     */
    public function changePassword(Request $request) {
        try {
            $user = auth()->user();

            $validated = $request->validate([
                'password' => 'required|string|min:6',
            ]);

            $cred = UserCredential::where('user_id', $user->id)->first();
            if (Hash::check($validated['password'], $cred->password)) {
                return ApiResponse::error('New password must be different from the old password', 400);
            }

            $cred->password = $validated['password'];
            $cred->save();

            return ApiResponse::success('Password changed successfully');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
