<?php

namespace App\Http\Controllers;

use App\Http\Resources\MemberResource;
use App\Http\Resources\MembersResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Models\UserDocument;
use App\Models\UserMembershipRenewal;
use App\Models\UserMemberships;
use App\Services\MembershipNumberGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
            $member->sendNotification('Your membership has now been approved', 'user');

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
            $member->sendNotification('Your membership has now been suspended', 'user');

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

            $unverified_membership = UserMemberships::where('user_id', $user->id ?? null)
                ->where('status', 'unverified')
                ->first();

            if (!$unverified_membership) {
                throw new \Exception("You do not have an active and unverified membership.", 1);
            }

            $transaction_id = $request->reference_code;
            $private_key    = config('services.monicredit.private_key', '');
            $base_url       = rtrim(config('services.monicredit.base_url', ''), '/');

            if (!$transaction_id) {
                throw new \Exception("Transaction ID is required.", 1);
            }

            try {
                // ✅ Wrap the HTTP request in a try/catch
                $response = Http::timeout(10)->get("{$base_url}/payment/transactions/verify-transaction", [
                    'transaction_id' => $transaction_id,
                    'private_key'    => $private_key,
                ]);
            } catch (\Exception $e) {
                // 🚨 Log the detailed error internally
                \Log::error("Monicredit API request failed", [
                    'transaction_id' => $transaction_id,
                    'error' => $e->getMessage(),
                ]);

                // 🚫 Return a safe message to the client
                return ApiResponse::error("Payment verification service unavailable. Please try again later.");
            }

            if (!$response->ok()) {
                throw new \Exception("Unable to connect to payment provider. Try again later.", 1);
            }

            $data = $response->json();

            if (!($data['status'] ?? false)) {
                throw new \Exception($data['message'] ?? "Payment verification failed.", 1);
            }

            if (($data['data']['status'] ?? null) !== "APPROVED") {
                throw new \Exception("Payment not approved. Current status: " . ($data['data']['status'] ?? "UNKNOWN"), 1);
            }

            // ✅ Payment verified, update membership and user
            $unverified_membership->update(['status' => 'pending']);
            $user->update(['reg_status' => 'done']);

            $user->sendPendingMembershipNotification();
            $user->sendNotification(
                'Your membership application is pending and awaiting review',
                'user'
            );

            return ApiResponse::success('User membership confirmed successfully');
        } catch (\Throwable $th) {
            // Return safe error without leaking sensitive info
            return ApiResponse::error("An error occurred while confirming your membership. Please try again later.");
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

            $membership->user()->sendNotification('Your membership is now under review', 'user');

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

            $membership->user()->sendNotification('Your membership is now pending approval', 'user');

            return ApiResponse::success('Membership reviewed successfully', $membership);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Get current user membership
     *
     * @return ApiResponse
     */
    public function membership() {
        try {
            $user = Auth::user();
            $currentMembership = UserMemberships::where('user_id', $user->id)
                ->latest('created_at')
                ->select(['id', 'category', 'status', 'expires_at', 'created_at'])
                ->first();

            return APiResponse::success('User membership fetched successfully', $currentMembership);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Get current membership documents
     *
     * @return ApiResponse
     */
    public function documents() {
        try {
            $user = Auth::user();
            $documents = UserDocument::where('user_id', $user->id)
                ->select(['id', 'name', 'resource_url', 'created_at'])
                ->get();

            return ApiResponse::success('Membership documents fetched successfully', $documents);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Initiates user membership renewal
     *
     * @param \Illuminate\Http\Request $request
     * @return ApiResponse
     */
    public function initiateRenewal(Request $request) {
        try {
            $user_id = auth()->user()->id;
            $request->validate([
                'category' => 'required|in:STUD,PROF,ASSOC',
            ]);
            $data = $request->validated();

            if (UserMembershipRenewal::where('user_id', $user_id)->where('status', 'PENDING')->exists()) {
                return ApiResponse::error('A pending renewal already exists');
            }

            $order_id = uniqid('RNW-', true);
            $amount = $this->getMembershipAmount($data['category']);

            $renewal = UserMembershipRenewal::create([
                'order_id' => $order_id,
                'category' => $data['category'],
                'amount' => $amount,
                'user_id' => $user_id,
                'status' => 'PENDING',
            ]);

            return ApiResponse::success('Membership renewal initiated', [
                'order_id' => $order_id,
                'renewal' => $renewal,
            ]);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    private function getMembershipAmount($category) {
        $costs = [
            'STUD' => 500,
            'ASSOC' => 1000,
            'PROF' => 1500,
        ];
        return $costs[$category] ?? 500;
    }

    /**
     * Confirms user membership renewal
     *
     * @param \Illuminate\Http\Request $request
     * @return ApiResponse
     */
    public function confirmRenewal(Request $request) {
        try {
            $user = auth()->user();
            $user_id = $user->id;
            $request->validate([
                'transaction_id' => 'required',
            ]);
            $data = $request->validated();

            $renewal = UserMembershipRenewal::where('user_id', $user_id)
                ->where('order_id', $data['transaction_id'])
                ->where('status', 'PENDING')
                ->latest()
                ->firstOrFail();

            $paymentResponse = $this->verifyPayment($data['transaction_id']);

            if ($paymentResponse['status'] && $paymentResponse['data']['status'] === 'APPROVED') {
                if ($paymentResponse['orderid'] === $renewal->order_id && $paymentResponse['data']['amount'] == $renewal->amount) {
                    $renewal->update([
                        'verified_at' => now(),
                        'status' => 'APPROVED',
                    ]);
                    $user->sendNotification('Your membership renewal payment was successful', 'payment');

                    $new_membership = UserMemberships::create([
                        'category' => $renewal->category,
                        'user_id' => $user_id,
                    ]);
                    $user->sendNotification('Your membership renewal request was successful and is pending review.', 'user');

                    return ApiResponse::success('Membership renewed successfully', $new_membership);
                } else {
                    $renewal->update(['status' => 'FAILED']);
                    throw new \Exception('Payment details do not match renewal record');
                }
            } else {
                $renewal->update(['status' => 'FAILED']);
                Log::error('Payment verification failed', ['response' => $paymentResponse]);
                throw new \Exception('Payment verification failed');
            }
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    private function verifyPayment($transaction_id) {
        $client = new \GuzzleHttp\Client();
        $response = $client->get('https://api.monicredit.com/payment/transactions/verify-transaction', [
            'query' => [
                'transaction_id' => $transaction_id,
                'private_key' => env('MONICREDIT_PRIVATE_KEY'),
            ],
        ]);
        return json_decode($response->getBody(), true);
    }
}
