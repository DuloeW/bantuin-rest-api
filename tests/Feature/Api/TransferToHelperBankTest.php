<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Service\Transaction\TransactionService;
use Mockery;
use Tests\TestCase;

class TransferToHelperBankTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_transfer_endpoint_requires_authentication(): void
    {
        $response = $this->postJson('/api/transactions/some-uuid/transfer');
        $response->assertStatus(401);
    }

    public function test_transfer_fails_if_helper_has_no_primary_bank_account(): void
    {
        $userId = 'requester-uuid-123';
        $transactionId = 'transaction-uuid-999';

        $expectedResult = [
            'success' => false,
            'code' => 422,
            'message' => 'Helper belum mendaftarkan rekening bank utama untuk pencairan otomatis.',
            'errors' => [
                'bank_account' => ['Helper belum mendaftarkan rekening bank utama untuk pencairan otomatis.']
            ]
        ];

        // Mock user model for authentication
        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->shouldReceive('getAuthIdentifier')->andReturn($userId);
        $mockUser->shouldReceive('getAuthIdentifierName')->andReturn('id');
        $mockUser->shouldReceive('getKey')->andReturn($userId);
        $this->actingAs($mockUser, 'sanctum');

        $this->mock(TransactionService::class, function ($mock) use ($transactionId, $expectedResult) {
            $mock->shouldReceive('disburseToHelper')
                ->once()
                ->andReturn($expectedResult);
        });

        $response = $this->postJson("/api/transactions/{$transactionId}/transfer");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Helper belum mendaftarkan rekening bank utama untuk pencairan otomatis.',
            ]);
    }

    public function test_transfer_succeeds_with_valid_bank_account_and_mocked_midtrans_response(): void
    {
        $userId = 'requester-uuid-123';
        $transactionId = 'transaction-uuid-999';

        $expectedResult = [
            'success' => true,
            'code' => 200,
            'message' => 'Dana berhasil ditransfer ke rekening bank Helper.',
            'data' => [
                'transaction_id' => $transactionId,
                'reference_no' => 'ref-998877',
                'status' => 'pending',
                'amount' => 50000.00,
                'bank' => 'BCA',
                'account_number' => '9876543210',
            ]
        ];

        // Mock user model for authentication
        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->shouldReceive('getAuthIdentifier')->andReturn($userId);
        $mockUser->shouldReceive('getAuthIdentifierName')->andReturn('id');
        $mockUser->shouldReceive('getKey')->andReturn($userId);
        $this->actingAs($mockUser, 'sanctum');

        $this->mock(TransactionService::class, function ($mock) use ($transactionId, $expectedResult) {
            $mock->shouldReceive('disburseToHelper')
                ->once()
                ->andReturn($expectedResult);
        });

        $response = $this->postJson("/api/transactions/{$transactionId}/transfer");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Dana berhasil ditransfer ke rekening bank Helper.',
                'data' => [
                    'transaction_id' => $transactionId,
                    'reference_no' => 'ref-998877',
                    'status' => 'pending',
                    'amount' => 50000.00,
                    'bank' => 'BCA',
                    'account_number' => '9876543210',
                ]
            ]);
    }
}
