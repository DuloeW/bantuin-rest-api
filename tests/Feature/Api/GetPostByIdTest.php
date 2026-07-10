<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Service\Post\PostService;
use Mockery;
use Tests\TestCase;

class GetPostByIdTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_post_by_id_returns_post_details(): void
    {
        $postId = 'post-uuid-123';
        
        $expectedResult = [
            'success' => true,
            'code' => 200,
            'message' => 'post retrieved successfully',
            'data' => [
                'id' => $postId,
                'title' => 'Sample Post Title',
                'description' => 'Sample post description',
                'type' => 'request',
            ]
        ];

        // Mock the user model to authenticate
        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->shouldReceive('getAuthIdentifier')->andReturn('user-uuid-123');
        $mockUser->shouldReceive('getAuthIdentifierName')->andReturn('id');
        $mockUser->shouldReceive('getKey')->andReturn('user-uuid-123');
        $this->actingAs($mockUser, 'sanctum');

        $this->mock(PostService::class, function ($mock) use ($postId, $expectedResult) {
            $mock->shouldReceive('getPostById')
                ->with($postId)
                ->once()
                ->andReturn($expectedResult);
        });

        $response = $this->getJson("/api/posts/{$postId}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'post retrieved successfully',
                'data' => [
                    'id' => $postId,
                    'title' => 'Sample Post Title',
                ]
            ]);
    }

    public function test_get_post_by_id_not_found(): void
    {
        $postId = 'non-existent-id';
        
        $expectedResult = [
            'success' => false,
            'code' => 404,
            'message' => 'post not found',
            'data' => []
        ];

        // Mock the user model to authenticate
        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->shouldReceive('getAuthIdentifier')->andReturn('user-uuid-123');
        $mockUser->shouldReceive('getAuthIdentifierName')->andReturn('id');
        $mockUser->shouldReceive('getKey')->andReturn('user-uuid-123');
        $this->actingAs($mockUser, 'sanctum');

        $this->mock(PostService::class, function ($mock) use ($postId, $expectedResult) {
            $mock->shouldReceive('getPostById')
                ->with($postId)
                ->once()
                ->andReturn($expectedResult);
        });

        $response = $this->getJson("/api/posts/{$postId}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'post not found',
            ]);
    }
}
