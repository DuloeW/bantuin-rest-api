<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Service\User\UserService;
use Mockery;
use Tests\TestCase;

class GetUsersPostsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_users_posts_returns_user_and_posts(): void
    {
        $userId = 'user-uuid-123';
        
        $expectedResult = [
            'success' => true,
            'code' => 200,
            'message' => 'user posts retrieved successfully',
            'data' => [
                'user' => [
                    'id' => $userId,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ],
                'posts' => [
                    [
                        'id' => 1,
                        'title' => 'Post 1',
                    ],
                ]
            ]
        ];

        // Mock the user model to authenticate
        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->shouldReceive('getAuthIdentifier')->andReturn($userId);
        $mockUser->shouldReceive('getAuthIdentifierName')->andReturn('id');
        $mockUser->shouldReceive('getKey')->andReturn($userId);
        $this->actingAs($mockUser, 'sanctum');

        $this->mock(UserService::class, function ($mock) use ($userId, $expectedResult) {
            $mock->shouldReceive('getUsersPosts')
                ->once()
                ->andReturn($expectedResult);
        });

        $response = $this->getJson("/api/users/posts/{$userId}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'user posts retrieved successfully',
                'data' => [
                    'user' => [
                        'id' => $userId,
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                    ],
                    'posts' => [
                        [
                            'id' => 1,
                            'title' => 'Post 1',
                        ]
                    ]
                ]
            ]);
    }
}
