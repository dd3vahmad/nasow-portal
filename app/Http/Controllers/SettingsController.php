<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Get my settings
     *
     * @return ApiResponse
     */
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

    public function change(Request $request)
    {
        try {
            $settings = Setting::where('user_id', auth()->id())->first();

            if (!$settings) {
                return ApiResponse::error('Settings not found. Try logging in.');
            }

            $request->validate([
                'two_factor_enabled' => 'boolean',
                'email_notification' => 'boolean',
                'sms_notification' => 'boolean',
                'color_mode' => 'in:dark,light,system',
                'language' => 'string',
                'metadata' => 'array',
            ]);

            $validated = $request->only([
                'two_factor_enabled',
                'email_notification',
                'sms_notification',
                'color_mode',
                'language',
                'metadata',
            ]);

            // Update only the provided keys
            $settings->update($validated);

            return ApiResponse::success('Settings changed successfully', $settings);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
