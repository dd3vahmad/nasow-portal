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

            return ApiResponse::success('Memberships fetched successfully', MembersResource::collection($members));
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

            return ApiResponse::success('Memberships fetched successfully', MembersResource::collection($members));
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

            return ApiResponse::success('Membership details fetched successfully', new MemberResource($member));
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * View case details for case manager
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewCase(int $id) {
        try {
            $member = UserMemberships::where('id', $id)->with('user.details')->first();
            if (!$member) {
                throw new \Exception('Member case not found');
            }

            return ApiResponse::success('Case details fetched successfully', new MemberResource($member));
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
    public function approve(int $id)
    {
        try {
            $user = auth()->user();
            $membership = UserMemberships::find($id);

            if (!$membership) {
                throw new \Exception('Membership not found');
            }

            $verifiedAt = now();
            $expiresAt = $verifiedAt->copy()->addYear();

            $updateData = [
                'status' => 'verified',
                'verified_at' => $verifiedAt,
                'expires_at' => $expiresAt,
                'suspended_at' => null
            ];

            if (!$membership->reviewed) {
                $updateData['reviewed'] = true;
            }
            if ($membership->reviewed_by === null) {
                $updateData['reviewed_by'] = $user->id;
            }

            $membership->update($updateData);

            $member = User::find($membership->user_id);
            $generator = new MembershipNumberGenerator();
            $cat = match ($membership->category) {
                'PROF'  => 'PSW',
                'ASSOC' => 'ASW',
                default => 'SSW',
            };
            $membership_no = $generator->generate($cat);
            $role = Role::firstOrCreate(
                ['name' => 'member'],
                ['guard_name' => 'api']
            );

            if (!$member->no) {
                $member->update(['no' => $membership_no]);
            }
            $member->assignRole($role);
            $member->sendMembershipApprovedNotification();

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
    public function suspend(int $id)
    {
        try {
            $user = auth()->user();
            $membership = UserMemberships::find($id);

            if (!$membership) {
                throw new \Exception('Membership not found');
            }

            $member = User::find($membership->user_id);
            if (!$member) {
                throw new \Exception('Member not found');
            }

            $updateData = [
                'status' => 'suspended',
                'suspended_at' => now()
            ];

            if (!$membership->reviewed) {
                $updateData['reviewed'] = true;
            }
            if ($membership->reviewed_by === null) {
                $updateData['reviewed_by'] = $user->id;
            }
            $membership->update($updateData);

            $member->sendMembershipSuspendedNotification();

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

    /**
     * Get memberships for review by case manager
     *
     * @param \Illuminate\Http\Request $request
     * @return ApiResponse
     */
    public function cases(Request $request)
    {
        try {
            $state = $request->query('state');
            $status = $request->query('status');
            $search = $request->query('q');

            $memberships = UserMemberships::whereHas('user', function ($q) use ($search) {
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
                ->paginate(15);

            return ApiResponse::success(
                'Memberships for review fetched successfully',
                MembersResource::collection($memberships)
            );
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Set membership as reviewed by current user
     *
     * @param int $id
     * @return ApiResponse
     */
    public function review(int $id)
    {
        try {
            $user = auth()->user();

            $membership = UserMemberships::findOrFail($id);
            if (!$membership) {
                return ApiResponse::error('Membership application not found');
            }

            if ($membership->reviewed_by) {
                return ApiResponse::error('Membership application already under review');
            }

            $membership->update([
                'reviewed_by' => $user->id,
                'status' => 'in-review',
                'reviewed_at' => now()
            ]);

            return ApiResponse::success('Membership marked successfully', $membership);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Set membership as reviewed as ask for approval
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return ApiResponse
     */
    public function requestApproval(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'comment' => 'required|string|max:255'
            ]);
            $membership = UserMemberships::findOrFail($id);

            $membership->update([
                'comment' => $validated['comment'],
                'reviewed' => true,
                'status' => 'pending-approval',
                'approval_requested_at' => now()
            ]);

            return ApiResponse::success('Membership reviewed successfully', $membership);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
