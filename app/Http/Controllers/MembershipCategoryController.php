<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;
use App\Models\MembershipCategory;

class MembershipCategoryController extends Controller
{
    public function index(Request $request) {
        try {
            $categories = MembershipCategory::all()->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'currency' => $category->currency,
                    'price' => $category->price,
                    'updated_at' => $category->updated_at->format('D, jS M, Y'),
                ];
            });

            return ApiResponse::success("Membership categories fetched successfully.", $categories);
        } catch (\Throwable $th) {
            return ApiREsponse::error($th->getMessage());
        }
    }
}
