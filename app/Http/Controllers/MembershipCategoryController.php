<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;
use App\Models\MembershipCategory;

class MembershipCategoryController extends Controller
{
    public function index(Request $request) {
        try {
            $categories = MembershipCategory::all();

            return ApiResponse::success("Membership categories fetched successfully.", $categories);
        } catch (\Throwable $th) {
            return ApiREsponse::error($th->getMessage());
        }
    }
}
