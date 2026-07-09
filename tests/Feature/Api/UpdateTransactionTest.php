<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Service\Transaction\TransactionService;
use Mockery;
use Tests\TestCase;

class UpdateTransactionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_update_transaction_unauthorized_if_not_logged_in(): void
    {
        $response = $this->postJson('/api/transactions/some-uuid/update', [
            'status' => 'completed',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_transaction_successfully(): void
    {
        $userId = 'user-uuid-123';
        $transactionId = 'transaction-uuid-999';

        $payload = [
            'status' => 'completed',
            'completion_notes' => 'Pekerjaan selesai dengan baik.',
            'finished_at' => '2026-07-07 13:00:00',
        ];

        $expectedResult = [
            'success' => true,
            'code' => 200,
            'message' => 'Transaksi berhasil diperbarui.',
            'data' => [
                'id' => $transactionId,
                'status' => 'completed',
                'completion_notes' => 'Pekerjaan selesai dengan baik.',
                'finished_at' => '2026-07-07T13:00:00.000000Z',
            ]
        ];

        // Mock user model for authentication
        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->shouldReceive('getAuthIdentifier')->andReturn($userId);
        $mockUser->shouldReceive('getAuthIdentifierName')->andReturn('id');
        $mockUser->shouldReceive('getKey')->andReturn($userId);
        $this->actingAs($mockUser, 'sanctum');

        $this->mock(TransactionService::class, function ($mock) use ($transactionId, $payload, $expectedResult) {
            $mock->shouldReceive('updateTransaction')
                ->once()
                ->with($transactionId, Mockery::subset($payload), [])
                ->andReturn($expectedResult);
        });

        $response = $this->postJson("/api/transactions/{$transactionId}/update", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Transaksi berhasil diperbarui.',
                'data' => [
                    'id' => $transactionId,
                    'status' => 'completed',
                ]
            ]);
    }

    public function test_update_transaction_validation_errors(): void
    {
        $userId = 'user-uuid-123';
        $transactionId = 'transaction-uuid-999';

        // Mock user model for authentication
        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->shouldReceive('getAuthIdentifier')->andReturn($userId);
        $mockUser->shouldReceive('getAuthIdentifierName')->andReturn('id');
        $mockUser->shouldReceive('getKey')->andReturn($userId);
        $this->actingAs($mockUser, 'sanctum');

        // Invalid status value
        $response = $this->postJson("/api/transactions/{$transactionId}/update", [
            'status' => 'invalid-status',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}
