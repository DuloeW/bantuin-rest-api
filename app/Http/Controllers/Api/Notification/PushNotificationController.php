<?php

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Service\Notification\NotificationService;
use Illuminate\Http\Request;

class PushNotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Send push notification ke user tertentu berdasarkan user_id
     *
     * POST /api/notifications/send
     * Body: { user_id, title, body, type?, data? }
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'title'   => 'required|string|max:255',
            'body'    => 'required|string|max:1000',
            'type'    => 'nullable|string|max:100',
            'data'    => 'nullable|array',
        ]);

        $user = User::findOrFail($validated['user_id']);

        $result = $this->notificationService->sendToUser(
            $user,
            $validated['title'],
            $validated['body'],
            $validated['data'] ?? [],
            $validated['type'] ?? 'general'
        );

        return response()->json($result, $result['code']);
    }

    /**
     * Send push notification ke diri sendiri (authenticated user) — berguna untuk testing
     *
     * POST /api/notifications/send-self
     * Body: { title, body, type?, data? }
     */
    public function sendToSelf(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body'  => 'required|string|max:1000',
            'type'  => 'nullable|string|max:100',
            'data'  => 'nullable|array',
        ]);

        $result = $this->notificationService->sendToUser(
            $request->user(),
            $validated['title'],
            $validated['body'],
            $validated['data'] ?? [],
            $validated['type'] ?? 'general'
        );

        return response()->json($result, $result['code']);
    }

    /**
     * Send push notification ke multiple users
     *
     * POST /api/notifications/broadcast
     * Body: { user_ids[], title, body, type?, data? }
     */
    public function broadcast(Request $request)
    {
        $validated = $request->validate([
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'uuid|exists:users,id',
            'title'      => 'required|string|max:255',
            'body'       => 'required|string|max:1000',
            'type'       => 'nullable|string|max:100',
            'data'       => 'nullable|array',
        ]);

        $result = $this->notificationService->sendToMultipleUsers(
            $validated['user_ids'],
            $validated['title'],
            $validated['body'],
            $validated['data'] ?? [],
            $validated['type'] ?? 'general'
        );

        return response()->json($result, $result['code']);
    }

    /**
     * Send push notification directly to a device token (NO AUTHENTICATION REQUIRED)
     * Useful for developer testing from Apidog without logging out other devices.
     *
     * POST /api/notifications/test-direct
     * Body: { device_token, title, body, type?, data? }
     */
    public function testDirect(Request $request)
    {
        $validated = $request->validate([
            'device_token' => 'required|string',
            'title'        => 'required|string|max:255',
            'body'         => 'required|string|max:1000',
            'type'         => 'nullable|string|max:100',
            'data'         => 'nullable|array',
        ]);

        $result = $this->notificationService->sendToToken(
            $validated['device_token'],
            $validated['title'],
            $validated['body'],
            $validated['data'] ?? [],
            $validated['type'] ?? 'general'
        );

        return response()->json($result, $result['code']);
    }
}
