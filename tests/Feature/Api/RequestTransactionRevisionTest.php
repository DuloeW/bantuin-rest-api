<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Service\Transaction\TransactionService;
use Mockery;
use Tests\TestCase;

class RequestTransactionRevisionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_request_revision_unauthorized_if_not_logged_in(): void
    {
        $response = $this->postJson('/api/transactions/some-uuid/revision', [
            'revision_notes' => 'Tolong diperbaiki bagian ini.',
        ]);

        $response->assertStatus(401);
    }

    public function test_request_revision_successfully(): void
    {
        $userId = 'user-uuid-123';
        $transactionId = 'transaction-uuid-999';

        $payload = [
            'revision_notes' => 'Tolong diperbaiki bagian ini.',
        ];

        $expectedResult = [
            'success' => true,
            'code' => 200,
            'message' => 'Permintaan revisi berhasil dikirim.',
            'data' => [
                'id' => 'revision-uuid-1',
                'revision_notes' => 'Tolong diperbaiki bagian ini.',
                'status' => 'pending',
                'images' => []
            ]
        ];

        // Mock user model for authentication
        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->shouldReceive('getAuthIdentifier')->andReturn($userId);
        $mockUser->shouldReceive('getAuthIdentifierName')->andReturn('id');
        $mockUser->shouldReceive('getKey')->andReturn($userId);
        $this->actingAs($mockUser, 'sanctum');

        $this->mock(TransactionService::class, function ($mock) use ($transactionId, $userId, $payload, $expectedResult) {
            $mock->shouldReceive('requestRevision')
                ->once()
                ->with($transactionId, $userId, Mockery::subset($payload), [])
                ->andReturn($expectedResult);
        });

        $response = $this->postJson("/api/transactions/{$transactionId}/revision", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Permintaan revisi berhasil dikirim.',
                'data' => [
                    'revision_notes' => 'Tolong diperbaiki bagian ini.',
                ]
            ]);
    }

    public function test_request_revision_validation_errors(): void
    {
        $userId = 'user-uuid-123';
        $transactionId = 'transaction-uuid-999';

        // Mock user model for authentication
        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->shouldReceive('getAuthIdentifier')->andReturn($userId);
        $mockUser->shouldReceive('getAuthIdentifierName')->andReturn('id');
        $mockUser->shouldReceive('getKey')->andReturn($userId);
        $this->actingAs($mockUser, 'sanctum');

        // Missing notes
        $response = $this->postJson("/api/transactions/{$transactionId}/revision", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['revision_notes']);
    }
}
