<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function settings() {
        try {
            $settings = Setting::where('user_id', auth()->id())->first();
            if (!$settings) {
                return ApiResponse::error('Settings not found. Try logging in.');
            }

            return ApiResponse::success('Settings fetched successfully', $settings);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
