<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\Setting;
use App\Models\User;
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

    /**
     * Change user settings
     *
     * @param \Illuminate\Http\Request $request
     * @return ApiResponse
     */
    public function change(Request $request)
    {
        try {
            $user_id = auth()->id();
            $settings = Setting::where('user_id', $user_id)->first();

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

            $settings->update($validated);

            $user = User::find($user_id);
            $user->sendNotification('Your settings has been changed successfully');

            return ApiResponse::success('Settings changed successfully', $settings);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
