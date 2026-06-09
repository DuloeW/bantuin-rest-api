<?php

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Traits\ServiceResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    use ServiceResponse;

    public function getPending(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);
            $page = $request->query('page', 1);

            $notifications = Notification::where('user_id', $request->user()->id)
                ->recent()
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json(
                $this->successPayload(
                    $notifications->items(),
                    'Notifications retrieved successfully',
                    200
                ),
                200
            );
        } catch (\Exception $e) {
            Log::error('getPending error: ' . $e->getMessage());
            return response()->json(
                $this->errorPayload('Failed to retrieve notifications', [], 500),
                500
            );
        }
    }

    public function markAsRead(Request $request, string $id)
    {
        try {
            $notification = Notification::where('id', $id)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$notification) {
                return response()->json(
                    $this->errorPayload('Notification not found', [], 404),
                    404
                );
            }

            $notification->update(['is_read' => true]);

            return response()->json(
                $this->successPayload([], 'Notification marked as read', 200),
                200
            );
        } catch (\Exception $e) {
            Log::error('markAsRead error: ' . $e->getMessage());
            return response()->json(
                $this->errorPayload('Failed to retrieve notifications', [], 500),
                500
            );
        }
    }
}
