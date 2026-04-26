<?php

namespace Tests\Feature\Api;

use App\Service\AuthService;
use Mockery;
use Tests\TestCase;

class LoginApiTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_login_returns_token_when_credentials_are_valid(): void
    {
        $credentials = [
            'email' => 'user@example.com',
            'password' => 'secret123',
        ];

        $this->mock(AuthService::class, function ($mock) use ($credentials) {
            $mock->shouldReceive('login')
                ->once()
                ->with($credentials)
                ->andReturn([
                    'success' => true,
                    'code' => 200,
                    'message' => 'login successful',
                    'data' => [
                        'access_token' => 'token_abc',
                        'token_type' => 'Bearer',
                    ],
                ]);
        });

        $response = $this->postJson('/api/login', $credentials);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'login successful',
                'data' => [
                    'access_token' => 'token_abc',
                    'token_type' => 'Bearer',
                ],
            ]);
    }

    public function test_login_returns_error_when_credentials_are_invalid(): void
    {
        $credentials = [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ];

        $this->mock(AuthService::class, function ($mock) use ($credentials) {
            $mock->shouldReceive('login')
                ->once()
                ->with($credentials)
                ->andReturn([
                    'success' => false,
                    'code' => 400,
                    'message' => 'email or password is incorrect',
                    'data' => [],
                ]);
        });

        $response = $this->postJson('/api/login', $credentials);

        $response->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'email or password is incorrect',
                'data' => [],
            ]);
    }

    public function test_login_returns_validation_error_when_email_or_password_missing(): void
    {
        $this->mock(AuthService::class, function ($mock) {
            $mock->shouldNotReceive('login');
        });

        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }
}
