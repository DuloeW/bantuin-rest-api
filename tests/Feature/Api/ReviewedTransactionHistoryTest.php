<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Service\Transaction\TransactionService;
use Mockery;
use Tests\TestCase;

class ReviewedTransactionHistoryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_reviewed_history_endpoint_unauthorized_if_not_logged_in(): void
    {
        $response = $this->getJson('/api/transactions/reviewed');
        $response->assertStatus(401);
    }

    public function test_reviewed_history_endpoint_returns_data_successfully(): void
    {
        $userId = 'user-uuid-123';

        $expectedResult = [
            'success' => true,
            'code' => 200,
            'message' => 'Riwayat transaksi yang sudah di-review berhasil diambil.',
            'data' => [
                [
                    'id' => 'transaction-uuid-999',
                    'requester_id' => $userId,
                    'helper_id' => 'helper-uuid-456',
                    'final_price' => 50000,
                    'status' => 'completed',
                    'reviews' => [
                        [
                            'id' => 'review-uuid-888',
                            'transaction_id' => 'transaction-uuid-999',
                            'reviewer_id' => $userId,
                            'reviewed_id' => 'helper-uuid-456',
                            'rating' => 5,
                            'comment' => 'Sangat membantu dan cepat selesai!',
                        ]
                    ]
                ]
            ]
        ];

        // Mock user model for authentication
        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->shouldReceive('getAuthIdentifier')->andReturn($userId);
        $mockUser->shouldReceive('getAuthIdentifierName')->andReturn('id');
        $mockUser->shouldReceive('getKey')->andReturn($userId);
        $this->actingAs($mockUser, 'sanctum');

        $this->mock(TransactionService::class, function ($mock) use ($userId, $expectedResult) {
            $mock->shouldReceive('getReviewedTransactions')
                ->once()
                ->with($userId)
                ->andReturn($expectedResult);
        });

        $response = $this->getJson('/api/transactions/reviewed');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Riwayat transaksi yang sudah di-review berhasil diambil.',
                'data' => [
                    [
                        'id' => 'transaction-uuid-999',
                        'status' => 'completed',
                        'reviews' => [
                            [
                                'rating' => 5,
                                'comment' => 'Sangat membantu dan cepat selesai!',
                            ]
                        ]
                    ]
                ]
            ]);
    }
}
