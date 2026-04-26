<?php

namespace Tests\Feature\Api;

use App\Service\AuthService;
use Illuminate\Validation\PresenceVerifierInterface;
use Mockery;
use Tests\TestCase;

class RegisterApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind('validation.presence', function () {
            return new class implements PresenceVerifierInterface {
                public function getCount($collection, $column, $value, $excludeId = null, $idColumn = null, array $extra = [])
                {
                    return 0;
                }

                public function getMultiCount($collection, $column, array $values, array $extra = [])
                {
                    return 0;
                }

                public function setConnection($connection)
                {
                    // No-op for tests.
                }
            };
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_register_returns_token_when_payload_is_valid(): void
    {
        $payload = [
            'first_name' => 'Bayu',
            'last_name' => 'Duloe',
            'email' => 'bayu@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $serviceInput = $payload;
        unset($serviceInput['password_confirmation']);

        $this->mock(AuthService::class, function ($mock) use ($serviceInput) {
            $mock->shouldReceive('register')
                ->once()
                ->with($serviceInput)
                ->andReturn([
                    'success' => true,
                    'code' => 201,
                    'message' => 'registration successful',
                    'data' => [
                        'access_token' => 'token_register_abc',
                        'token_type' => 'Bearer',
                    ],
                ]);
        });

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => true,
                'message' => 'registration successful',
                'data' => [
                    'access_token' => 'token_register_abc',
                    'token_type' => 'Bearer',
                ],
            ]);
    }

    public function test_register_returns_error_when_service_fails(): void
    {
        $payload = [
            'first_name' => 'Bayu',
            'last_name' => 'Duloe',
            'email' => 'bayu@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $serviceInput = $payload;
        unset($serviceInput['password_confirmation']);

        $this->mock(AuthService::class, function ($mock) use ($serviceInput) {
            $mock->shouldReceive('register')
                ->once()
                ->with($serviceInput)
                ->andReturn([
                    'success' => false,
                    'code' => 400,
                    'message' => 'registration failed',
                    'data' => [],
                ]);
        });

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'registration failed',
                'data' => [],
            ]);
    }

    public function test_register_returns_validation_error_when_password_confirmation_is_invalid(): void
    {
        $payload = [
            'first_name' => 'Bayu',
            'last_name' => 'Duloe',
            'email' => 'bayu@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ];

        $this->mock(AuthService::class, function ($mock) {
            $mock->shouldNotReceive('register');
        });

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
