<?php

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Service\Notification\DeviceTokenService;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    protected $deviceTokenService;

    public function __construct(DeviceTokenService $deviceTokenService)
    {
        $this->deviceTokenService = $deviceTokenService;
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'device_token' => 'required|string|min:10',
            'device_name' => 'nullable|string|max:255',
        ]);

        $result = $this->deviceTokenService->registerDevice(
            $request->user()->id,
            $validated['device_token'],
            $validated['device_name'] ?? null
        );

        return response()->json($result, $result['code']);
    }

    public function unregister(Request $request)
    {
        $validated = $request->validate([
            'device_token' => 'required|string|min:10',
        ]);

        $result = $this->deviceTokenService->unregisterDevice(
            $request->user()->id,
            $validated['device_token']
        );

        return response()->json($result, $result['code']);
    }
}
