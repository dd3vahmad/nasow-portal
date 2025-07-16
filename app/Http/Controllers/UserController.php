<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserDetails\StoreUserDetailsRequest;
use App\Http\Requests\UserEducations\StoreUserEducationsRequest;
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
            $details = UserDetails::where('user_id', $user->id)->first();
            $user_details = [
                'id' => $user->id,
                'first_name' => $details->first_name,
                'last_name' => $details->last_name,
                'other_name' => $details->other_name,
                'gender' => $details->gender,
                'dob' => $details->dob,
                'address' => $details->address,
                'specialization' => $details->specialization,
                'state' => $details->state,
                'phone' => $details->phone,
                'reg_status' => $user->reg_status,
            ];

            return ApiResponse::success('User details fetched successfully', $user_details);
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
    public function store(StoreUserDetailsRequest $request) {
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

            $user_details = UserDetails::createOrFirst($details);
            $this->createMembership($request->category, $user->id);
            $user->update(['reg_status' => 'education']);

            return ApiResponse::success('User details added successfully', $user_details);
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
        if (!in_array($category, config('member_categories'))) {
            throw new Exception("Invalid category", 1);
        }

        $existing_membership = UserMemberships::where('user_id', $user_id)->active()->first();
        if ($existing_membership) {
            throw new Exception("You have an active membership. Kindly revoke it before continuing?", 1);
        }

        $generator = new MembershipNumberGenerator();
        $membership_no = $generator->generate($category);

        return UserMemberships::createOrFirst([ 'no' => $membership_no, 'category' => $category, 'user_id' => $user_id ])->first();
    }

    /**
     * Add user educations
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add_educations(StoreUserEducationsRequest $request) {
        try {
            $user = Auth::user();
            $educations = [];
            return ApiResponse::success('Educations added successfully', $educations);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
