<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Service\Notification\DeviceTokenService;
use Mockery;
use Tests\TestCase;

class DeviceTokenApiTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_register_device_token_successfully(): void
    {
        $userId = 'user-uuid-123';
        $token = 'fcm-token-example-1234567890';
        $deviceName = 'iPhone 15';

        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->shouldReceive('getAttribute')->with('id')->andReturn($userId);
        $mockUser->shouldReceive('getAuthIdentifier')->andReturn($userId);
        $mockUser->shouldReceive('getAuthIdentifierName')->andReturn('id');
        $mockUser->shouldReceive('getKey')->andReturn($userId);

        $this->actingAs($mockUser, 'sanctum');

        $this->mock(DeviceTokenService::class, function ($mock) use ($userId, $token, $deviceName) {
            $mock->shouldReceive('registerDevice')
                ->once()
                ->with($userId, $token, $deviceName)
                ->andReturn([
                    'success' => true,
                    'code' => 201,
                    'message' => 'Device token registered successfully',
                    'data' => [
                        'id' => 'token-uuid-123',
                        'user_id' => $userId,
                        'device_token' => $token,
                        'device_name' => $deviceName,
                    ]
                ]);
        });

        $response = $this->postJson('/api/device-tokens/register', [
            'device_token' => $token,
            'device_name' => $deviceName,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Device token registered successfully',
            ]);
    }

    public function test_unregister_device_token_successfully(): void
    {
        $userId = 'user-uuid-123';
        $token = 'fcm-token-example-1234567890';

        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->shouldReceive('getAttribute')->with('id')->andReturn($userId);
        $mockUser->shouldReceive('getAuthIdentifier')->andReturn($userId);
        $mockUser->shouldReceive('getAuthIdentifierName')->andReturn('id');
        $mockUser->shouldReceive('getKey')->andReturn($userId);

        $this->actingAs($mockUser, 'sanctum');

        $this->mock(DeviceTokenService::class, function ($mock) use ($userId, $token) {
            $mock->shouldReceive('unregisterDevice')
                ->once()
                ->with($userId, $token)
                ->andReturn([
                    'success' => true,
                    'code' => 200,
                    'message' => 'Device token removed successfully',
                ]);
        });

        $response = $this->postJson('/api/device-tokens/unregister', [
            'device_token' => $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Device token removed successfully',
            ]);
    }
}
