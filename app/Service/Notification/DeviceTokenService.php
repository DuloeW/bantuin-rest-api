<?php

namespace App\Service\Notification;

use App\Models\DeviceToken;
use App\Traits\ServiceResponse;
use Exception;
use Illuminate\Support\Facades\Log;

class DeviceTokenService
{
    use ServiceResponse;

    public function registerDevice(string $userId, string $token, ?string $deviceName = null): array
    {
        try {
            $existingToken = DeviceToken::where('device_token', $token)->first();

            if ($existingToken && $existingToken->user_id === $userId) {
                $existingToken->update(['device_name' => $deviceName]);

                return $this->successPayload(
                    $existingToken->toArray(),
                    'Device token updated successfully',
                    200
                );
            } elseif ($existingToken) {
                $existingToken->delete();
            }

            $deviceToken = DeviceToken::create([
                'user_id' => $userId,
                'device_token' => $token,
                'device_name' => $deviceName,
            ]);

            return $this->successPayload(
                $deviceToken->toArray(),
                'Device token registered successfully',
                201
            );
        } catch (Exception $e) {
            Log::error('registerDevice error: ' . $e->getMessage());
            return $this->errorPayload('Failed to register device token', [], 500);
        }
    }

    public function unregisterDevice(string $userId, string $token): array
    {
        try {
            $deviceToken = DeviceToken::where('user_id', $userId)
                ->where('device_token', $token)
                ->first();

            if (!$deviceToken) {
                return $this->errorPayload('Device token not found', [], 404);
            }

            $deviceToken->delete();

            return $this->successPayload([], 'Device token removed successfully', 200);
        } catch (Exception $e) {
            Log::error('unregisterDevice error: ' . $e->getMessage());
            return $this->errorPayload('Failed to remove device token', [], 500);
        }
    }

    public function getActiveDevices(string $userId): array
    {
        try {
            $devices = DeviceToken::where('user_id', $userId)
                ->select('id', 'device_token', 'device_name', 'created_at', 'updated_at')
                ->get();

            return $this->successPayload(
                $devices->toArray(),
                'Active devices retrieved successfully',
                200
            );
        } catch (Exception $e) {
            Log::error('getActiveDevices error: ' . $e->getMessage());
            return $this->errorPayload('Failed to retrieve active devices', [], 500);
        }
    }
}
