<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserDetails\StoreUserDetailsRequest;
use App\Http\Responses\ApiResponse;
use App\Models\UserDetails;
use App\Models\UserMemberships;
use App\Services\MembershipNumberGenerator;
use FFI\Exception;
use Illuminate\Support\Facades\Auth;

class UserDetailsController extends Controller
{
    /**
     * Store user details
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreUserDetailsRequest $request) {
        try {
            $user = Auth::user();
            $detailsPayload = $request->validated();

            $details = [
                'first_name' => $detailsPayload->first_name,
                'last_name' => $detailsPayload->last_name,
                'other_name' => $detailsPayload->other_name,
                'gender' => $detailsPayload->gender,
                'dob' => $detailsPayload->dob,
                'specialization' => $detailsPayload->specialization,
                'address' => $detailsPayload->address,
                'phone' => $detailsPayload->phone,
                'state' => $detailsPayload->state,
                'user_id' => $user->id,
            ];

            $user_details = UserDetails::createOrFirst($details);
            $this->createMembership($detailsPayload->category, $user->id);
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

        return UserMemberships::createOrFirst([
            'no' => $membership_no,
            'category' => $category,
            'user_id' => $user_id
        ])->first();
    }
}
