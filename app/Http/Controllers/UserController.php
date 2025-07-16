<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
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
}
