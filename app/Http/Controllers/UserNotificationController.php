<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    /**
     * List all notifications for the authenticated user.
     *
     * @param Request $request
     * @return ApiResponse
     */
    public function index(Request $request): ApiResponse
    {
        try {
            $notifications = $request->user()
                ->notifications()
                ->latest()
                ->paginate(15);

            return ApiResponse::success('Notifications fetched successfully', $notifications);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Get a single notification for the authenticated user.
     *
     * @param Request $request
     * @param int $id
     * @return ApiResponse
     */
    public function show(Request $request, int $id): ApiResponse
    {
        try {
            $notification = $request->user()
                ->notifications()
                ->findOrFail($id);

            return ApiResponse::success('Notification fetched successfully', $notification);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Mark a single notification as read.
     *
     * @param Request $request
     * @param int $id
     * @return ApiResponse
     */
    public function markAsRead(Request $request, int $id): ApiResponse
    {
        try {
            $notification = $request->user()
                ->notifications()
                ->findOrFail($id);

            $notification->markAsRead();

            return ApiResponse::success('Notification marked as read');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Mark multiple notifications as read.
     *
     * @param Request $request
     * @return ApiResponse
     */
    public function markManyAsRead(Request $request): ApiResponse
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer'
            ]);

            $request->user()
                ->notifications()
                ->whereIn('id', $request->ids)
                ->update(['read_at' => now()]);

            return ApiResponse::success('Notifications marked as read');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Mark a single notification as unread.
     *
     * @param Request $request
     * @param int $id
     * @return ApiResponse
     */
    public function markAsUnread(Request $request, int $id): ApiResponse
    {
        try {
            $notification = $request->user()
                ->notifications()
                ->findOrFail($id);

            $notification->update(['read_at' => null]);

            return ApiResponse::success('Notification marked as unread');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Mark multiple notifications as unread.
     *
     * @param Request $request
     * @return ApiResponse
     */
    public function markManyAsUnread(Request $request): ApiResponse
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer'
            ]);

            $request->user()
                ->notifications()
                ->whereIn('id', $request->ids)
                ->update(['read_at' => null]);

            return ApiResponse::success('Notifications marked as unread');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }
}
