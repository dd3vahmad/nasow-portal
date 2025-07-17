<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\UserMemberships;
use Illuminate\Http\Request;

class MembershipController extends Controller {
    /**
     * Get all members
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request) {
        try {
            $members = UserMemberships::whereNot('status', 'nverified')->with(['user'])->get();

            return ApiResponse::success('Members fetched successfully', $members);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
