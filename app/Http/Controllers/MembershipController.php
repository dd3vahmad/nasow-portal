<?php

namespace App\Http\Controllers;

use App\Enums\RoleType;
use App\Http\Resources\MemberResource;
use App\Http\Resources\MembersResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Models\UserMemberships;
use App\Services\MembershipNumberGenerator;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class MembershipController extends Controller {
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
            $search = $request->query('q');

            $members = UserMemberships::whereHas('user', function ($q) use ($search) {
                    $q->where('reg_status', 'done');

                    if ($search) {
                        $q->where(function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                    }
                })
                ->whereHas('user.details', function ($query) use ($state) {
                    if ($state) {
                        $query->where('state', $state);
                    }
                })
                ->when($status, function ($query) use ($status) {
                    $query->where('status', $status);
                })
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
            $user = auth()->user();
            $userDetails = $user->details;

            $state = $userDetails->state ?? null;
            $status = $request->query('status');
            $search = $request->query('q');

            $members = UserMemberships::whereHas('user', function ($query) use ($search) {
                    $query->where('reg_status', 'done');

                    if ($search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                        });
                    }
                })
                ->whereHas('user.details', function ($query) use ($state) {
                    if ($state) {
                        $query->where('state', $state);
                    }
                })
                ->when($status, function ($query) use ($status) {
                    $query->where('status', $status);
                })
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
                'suspended_at' => null
            ]);

            $user = User::where('id', $membership->user_id ?? null)->first();
            $generator = new MembershipNumberGenerator();
            $membership_no = $generator->generate('NASOW');
            $role = Role::firstOrCreate(
                ['name' => 'member'],
                ['guard_name' => 'api']
            );
            $user->details()->update([ 'no' => $membership_no ]);
            $user->assignRole($role);
            $user->sendMembershipApprovedNotification();

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

            $user = User::where('id', $membership->user_id ?? null)->first();

            if (!$membership) {
                throw new \Exception('Membership not found');
            }

            $suspendedAt = now();

            $membership->update([
                'status' => 'suspended',
                'suspended_at' => $suspendedAt,
            ]);
            $user->sendMembershipSuspendedNotification();

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
            $membership->user()->delete();

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
            $user = auth()->user();

            if (($user->reg_status ?? null) !== "review") {
                throw new \Exception('Complete membership details before confirming', 1);
            }

            $unverified_membership = UserMemberships::where('user_id', $user->id ?? null)->where('status', 'unverified')->first();
            if (!$unverified_membership) {
                throw new \Exception("You do not have an active and unverified membership.", 1);
            }

            $unverified_membership->update([ 'status' => 'pending' ]);
            $user->update(['reg_status' => 'done']);
            $user->sendPendingMembershipNotification();

            return ApiResponse::success('User membership confirmed successfully');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
