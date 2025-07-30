<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserDetails\StoreUserDetailsRequest;
use App\Http\Responses\ApiResponse;
use App\Models\UserDetails;
use App\Models\UserMemberships;
use FFI\Exception;
use Illuminate\Http\Request;

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
            $user = auth()->user();
            $user_id = $user->id ?? null;
            $detailsPayload = $request->validated();

            $fullName = $user->name;
            $nameParts = explode(' ', trim($fullName));

            $firstName = $nameParts[0] ?? null;
            $lastName = null;
            $otherName = null;

            if (count($nameParts) === 2) {
                $lastName = $nameParts[1];
            } elseif (count($nameParts) >= 3) {
                $otherName = $nameParts[1];
                $lastName = implode(' ', array_slice($nameParts, 2));
            }

            $details = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'other_name' => $otherName,
                'gender' => $detailsPayload['gender'],
                'dob' => $detailsPayload['dob'],
                'specialization' => $detailsPayload['specialization'],
                'address' => $detailsPayload['address'],
                'phone' => $detailsPayload['phone'],
                'state' => $detailsPayload['state'],
                'user_id' => $user_id,
            ];

            $user_details = UserDetails::createOrFirst($details);
            $this->createMembership($detailsPayload['category'], $user_id);
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

        return UserMemberships::createOrFirst([
            'category' => $category,
            'user_id' => $user_id
        ])->first();
    }

    /**
     * Add user area of specialization
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add_specialization(Request $request) {
        try {
            $specialization = $request->area_of_specialization ?? null;
            if (!$specialization) {
                return ApiResponse::error('Area of specialization is required and must be a string');
            }

            $user = auth()->user();
            $user_details = UserDetails::where('user_id', $user->id ?? null)->first();
            $user_details->update(['specialization' => $specialization]);
            $user->update(['reg_status' => 'documents']);

            return ApiResponse::success('Area of specialization added successfully', $user_details);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
