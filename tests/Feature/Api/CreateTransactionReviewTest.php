<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Service\Transaction\TransactionService;
use Mockery;
use Tests\TestCase;

class CreateTransactionReviewTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_submit_review_unauthorized_if_not_logged_in(): void
    {
        $response = $this->postJson('/api/transactions/some-uuid/reviews', [
            'rating' => 5,
            'comment' => 'Bagus!',
        ]);

        $response->assertStatus(401);
    }

    public function test_requester_can_review_helper_successfully(): void
    {
        $userId = 'requester-uuid-123';
        $transactionId = 'transaction-uuid-999';

        $payload = [
            'rating' => 5,
            'comment' => 'Helper bekerja sangat baik!',
        ];

        $expectedResult = [
            'success' => true,
            'code' => 201,
            'message' => 'Ulasan berhasil dikirim.',
            'data' => [
                'id' => 'review-uuid-888',
                'transaction_id' => $transactionId,
                'reviewer_id' => $userId,
                'reviewed_id' => 'helper-uuid-456',
                'rating' => 5,
                'comment' => 'Helper bekerja sangat baik!',
            ]
        ];

        // Mock user model for authentication
        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->shouldReceive('getAuthIdentifier')->andReturn($userId);
        $mockUser->shouldReceive('getAuthIdentifierName')->andReturn('id');
        $mockUser->shouldReceive('getKey')->andReturn($userId);
        $this->actingAs($mockUser, 'sanctum');

        $this->mock(TransactionService::class, function ($mock) use ($transactionId, $userId, $payload, $expectedResult) {
            $mock->shouldReceive('createReview')
                ->once()
                ->with($transactionId, $userId, Mockery::subset($payload), [])
                ->andReturn($expectedResult);
        });

        $response = $this->postJson("/api/transactions/{$transactionId}/reviews", $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Ulasan berhasil dikirim.',
                'data' => [
                    'transaction_id' => $transactionId,
                    'reviewer_id' => $userId,
                    'rating' => 5,
                ]
            ]);
    }

    public function test_submit_review_validation_errors(): void
    {
        $userId = 'requester-uuid-123';
        $transactionId = 'transaction-uuid-999';

        // Mock user model for authentication
        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->shouldReceive('getAuthIdentifier')->andReturn($userId);
        $mockUser->shouldReceive('getAuthIdentifierName')->andReturn('id');
        $mockUser->shouldReceive('getKey')->andReturn($userId);
        $this->actingAs($mockUser, 'sanctum');

        // Invalid rating (over 5)
        $response = $this->postJson("/api/transactions/{$transactionId}/reviews", [
            'rating' => 6,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }
}
