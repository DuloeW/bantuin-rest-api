<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Service\Transaction\TransactionService;
use Mockery;
use Tests\TestCase;

class CancelTransactionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_cancel_transaction_unauthorized_if_not_logged_in(): void
    {
        $response = $this->postJson('/api/transactions/some-uuid/cancel');

        $response->assertStatus(401);
    }

    public function test_cancel_transaction_successfully(): void
    {
        $userId = 'user-uuid-123';
        $transactionId = 'transaction-uuid-999';

        $expectedResult = [
            'success' => true,
            'code' => 200,
            'message' => 'Transaksi berhasil dibatalkan.',
            'data' => [
                'id' => $transactionId,
                'status' => 'cancelled',
            ]
        ];

        // Mock user model for authentication
        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->shouldReceive('getAuthIdentifier')->andReturn($userId);
        $mockUser->shouldReceive('getAuthIdentifierName')->andReturn('id');
        $mockUser->shouldReceive('getKey')->andReturn($userId);
        $this->actingAs($mockUser, 'sanctum');

        $this->mock(TransactionService::class, function ($mock) use ($transactionId, $userId, $expectedResult) {
            $mock->shouldReceive('cancelTransaction')
                ->once()
                ->with($transactionId, $userId)
                ->andReturn($expectedResult);
        });

        $response = $this->postJson("/api/transactions/{$transactionId}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Transaksi berhasil dibatalkan.',
                'data' => [
                    'id' => $transactionId,
                    'status' => 'cancelled',
                ]
            ]);
    }
}
