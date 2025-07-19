<?php

namespace App\Http\Controllers;

use App\Http\Resources\MemberResource;
use App\Http\Resources\MembersResource;
use App\Http\Responses\ApiResponse;
use App\Models\UserMemberships;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MembershipController extends Controller {
    /**
     *  Get all members stats (counts)
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(Request $request) {
        try {
            $state = $request->query('state', '');

            $baseQuery = UserMemberships::whereHas('user', fn ($query) => $query->where('reg_status', 'done'))
                ->whereHas('user.details', function ($query) use ($state) {
                    if ($state) {
                        $query->where('state', $state);
                    }
                });

            $totalMembers = (clone $baseQuery)->count();
            $pendingMembers = (clone $baseQuery)->where('status', 'pending')->count();
            $approvedMembers = (clone $baseQuery)->where('status', 'verified')->count();
            $suspendedMembers = (clone $baseQuery)->where('status', 'suspended')->count();

            $stats = [
                'total' => $totalMembers,
                'pending' => $pendingMembers,
                'approved' => $approvedMembers,
                'suspended' => $suspendedMembers,
            ];

            return ApiResponse::success('Members stats fetched successfully', $stats);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Get all members
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request) {
        try {
            $state = $request->query('state');
            $status = $request->query('status');

            $members = UserMemberships::whereHas('user', fn ($q) => $q->where('reg_status', 'done'))
                ->whereHas('user.details', function ($query) use ($state) {
                    if ($state) {
                        $query->where('state', $state);
                    }
                })
                ->when($status, fn ($query) => $query->where('status', $status))
                ->with(['user.details'])
                ->get();

            return ApiResponse::success('Members fetched successfully', MembersResource::collection($members));
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Get state members
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function state(Request $request)
    {
        try {
            $user = Auth::user();
            $userDetails = $user->details()->first();

            $state = $userDetails->state;
            $status = $request->query('status');

            $members = UserMemberships::whereHas('user', fn ($query) => $query->where('reg_status', 'done'))
                ->whereHas('user.details', function ($query) use ($state) {
                    if ($state) {
                        $query->where('state', $state);
                    }
                })
                ->when($status, fn ($query) => $query->where('status', $status))
                ->with('user.details')
                ->get();

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
            $user = $membership->user();
            $user->assignRole('member');

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

    /**
     * Delete membership
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(int $id) {
        try {
            $membership = UserMemberships::find($id);
            if (!$membership) {
                throw new \Exception('Membership not found');
            }
            $membership->delete();

            return ApiResponse::success('Membership deleted successfully', $membership);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Confirm membership
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirm(Request $request) {
        try {
            $user = Auth::user();
            if ($user->reg_status !== "review") {
                throw new \Exception('Complete membership details before confirming', 1);
            }

            $unverified_membership = UserMemberships::where('user_id', $user->id)->where('status', 'unverified')->first();
            if (!$unverified_membership) {
                throw new \Exception("You do not have an active and unverified membership.", 1);
            }

            $unverified_membership->update([ 'status' => 'pending' ]);
            $user->update(['reg_status' => 'done']);

            return ApiResponse::success('User membership confirmed successfully');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
