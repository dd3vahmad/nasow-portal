<?php

namespace App\Http\Controllers;

use App\Http\Resources\MemberResource;
use App\Http\Resources\MembersResource;
use App\Http\Responses\ApiResponse;
use App\Models\UserMemberships;
use Illuminate\Http\Request;

class MembershipController extends Controller {
    /**
     * Get all members
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request) {
        try {
            $members = UserMemberships::whereNot('status', 'unverified')->with(['user.details'])->get();

            return ApiResponse::success('Members fetched successfully', MembersResource::collection($members));
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * View a single member details
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function view(int $id) {
        try {
            $member = UserMemberships::where('user_id', $id)->with('user.details')->first();
            if (!$member) {
                throw new \Exception('Member not found');
            }

            return ApiResponse::success('Member details fetched successfully', new MemberResource($member));
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Approve membership
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(int $id) {
        try {
            $membership = UserMemberships::find($id);

            if (!$membership) {
                throw new \Exception('Membership not found');
            }

            $verifiedAt = now();
            $expiresAt = $verifiedAt->copy()->addYear();

            $membership->update([
                'status' => 'verified',
                'verified_at' => $verifiedAt,
                'expires_at' => $expiresAt,
            ]);

            return ApiResponse::success('Membership approved successfully', $membership);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Suspend membership
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function suspend(int $id) {
        try {
            $membership = UserMemberships::find($id);

            if (!$membership) {
                throw new \Exception('Membership not found');
            }

            $suspendedAt = now();

            $membership->update([
                'status' => 'suspended',
                'suspended_at' => $suspendedAt,
            ]);

            return ApiResponse::success('Membership suspended successfully', $membership);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
