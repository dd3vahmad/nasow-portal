<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;

class SupportStaffController extends Controller
{
    /**
     * Get all support staffs
     * @param \Illuminate\Http\Request $request
     * @return ApiResponse
     */
    public function index(Request $request) {
        try {
            $state = $request->query('state');
            $q = $request->query('q');
            $staffs = User::role('support-staff', 'api')
                ->when($state, function ($query) use ($state) {
                    $query->where('state', $state);
                })
                ->when($q, function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%");
                })
                ->get();

            return ApiResponse::success('Support staffs fetched successfully', $staffs);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Get state support staffs
     *
     * @param Request $request
     * @return ApiResponse
     */
    public function state(Request $request) {
        try {
            $user = auth()->user();
            $state = $user->details?->state;

            if (!$state) {
                return ApiResponse::error('User state not found.');
            }

            $q = $request->query('q');

            $staffs = User::role('support-staff', 'api')
                ->whereHas('details', function ($query) use ($state) {
                    $query->where('state', $state);
                })
                ->when($q, function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%");
                })
                ->get();

            return ApiResponse::success('State support staffs fetched successfully', $staffs);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
