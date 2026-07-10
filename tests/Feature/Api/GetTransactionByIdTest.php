<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Service\Transaction\TransactionService;
use Mockery;
use Tests\TestCase;

class GetTransactionByIdTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_transaction_by_id_unauthorized_if_not_logged_in(): void
    {
        $response = $this->getJson('/api/transactions/some-uuid');

        $response->assertStatus(401);
    }

    public function test_get_transaction_by_id_successfully(): void
    {
        $userId = 'user-uuid-123';
        $transactionId = 'transaction-uuid-999';

        $expectedResult = [
            'success' => true,
            'code' => 200,
            'message' => 'Transaksi berhasil diambil.',
            'data' => [
                'id' => $transactionId,
                'status' => 'pending',
                'final_price' => 150000,
            ]
        ];

        // Mock user model for authentication
        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->shouldReceive('getAuthIdentifier')->andReturn($userId);
        $mockUser->shouldReceive('getAuthIdentifierName')->andReturn('id');
        $mockUser->shouldReceive('getKey')->andReturn($userId);
        $this->actingAs($mockUser, 'sanctum');

        $this->mock(TransactionService::class, function ($mock) use ($transactionId, $expectedResult) {
            $mock->shouldReceive('getTransactionById')
                ->once()
                ->with($transactionId)
                ->andReturn($expectedResult);
        });

        $response = $this->getJson("/api/transactions/{$transactionId}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Transaksi berhasil diambil.',
                'data' => [
                    'id' => $transactionId,
                    'status' => 'pending',
                ]
            ]);
    }

    public function test_get_transaction_by_id_not_found(): void
    {
        $userId = 'user-uuid-123';
        $transactionId = 'non-existent-uuid';

        $expectedResult = [
            'success' => false,
            'code' => 404,
            'message' => 'Transaksi tidak ditemukan.',
            'data' => []
        ];

        // Mock user model for authentication
        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->shouldReceive('getAuthIdentifier')->andReturn($userId);
        $mockUser->shouldReceive('getAuthIdentifierName')->andReturn('id');
        $mockUser->shouldReceive('getKey')->andReturn($userId);
        $this->actingAs($mockUser, 'sanctum');

        $this->mock(TransactionService::class, function ($mock) use ($transactionId, $expectedResult) {
            $mock->shouldReceive('getTransactionById')
                ->once()
                ->with($transactionId)
                ->andReturn($expectedResult);
        });

        $response = $this->getJson("/api/transactions/{$transactionId}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan.',
            ]);
    }
}
