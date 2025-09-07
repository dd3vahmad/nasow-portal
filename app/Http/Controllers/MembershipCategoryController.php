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

    /**
    * Create or update membership category by slug
    */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'required|string|max:255',
                'currency' => 'required|string|max:10',
                'price' => 'required|numeric',
            ]);

            $category = MembershipCategory::firstOrNew(['slug' => $data['slug']]);

            $category->name = $data['name'];
            $category->currency = $data['currency'];
            $category->price = $data['price'];

            $category->save();

            return ApiResponse::success(
                $category->wasRecentlyCreated
                    ? "Membership category created successfully."
                    : "Membership category updated successfully.",
                [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'currency' => $category->currency,
                    'price' => $category->price,
                    'updated_at' => $category->updated_at->format('D, jS M, Y'),
                ]
            );
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
