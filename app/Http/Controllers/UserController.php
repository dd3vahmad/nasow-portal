<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Responses\ApiResponse;
use App\Models\UserDetails;
use App\Models\UserMemberships;
use App\Services\MembershipNumberGenerator;
use FFI\Exception;
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
    public function me(Request $request) {
        try {
            $user = Auth::user();

            return ApiResponse::success('User details fetched successfully', $user);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Store user details
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreUserRequest $request) {
        try {
            $user = Auth::user();

            $details = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'other_name' => $request->other_name,
                'gender' => $request->gender,
                'dob' => $request->dob,
                'specialization' => $request->specialization,
                'address' => $request->address,
                'phone' => $request->phone,
                'state' => $request->state,
                'user_id' => $user->id,
            ];

            $user = UserDetails::createOrFirst($details)->first();
            $this->createMembership($request->category, $user->id);

            return ApiResponse::success('User details added successfully', $user);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Create membership record
     *
     * @param string
     * @return UserMemberships
     */
    protected function createMembership(string $category, int $user_id) {
        if (!in_array($category, config('membership_categories'))) {
            throw new Exception("Invalid category", 1);
        }

        $generator = new MembershipNumberGenerator();
        $membership_no = $generator->generate($category);

        return UserMemberships::createOrFirst([ 'no' => $membership_no, 'category' => $category, 'user_id' => $user_id ])->first();
    }
}
