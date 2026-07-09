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

    public function test_respond_revision_accept_successfully(): void
    {
        $userId = 'helper-uuid-123';
        $revisionId = 'revision-uuid-999';

        $payload = [
            'action' => 'fixed',
            'completion_notes' => 'Pekerjaan selesai diperbaiki.',
            'completion_images' => [
                \Illuminate\Http\UploadedFile::fake()->image('proof.jpg')
            ]
        ];

        $expectedResult = [
            'success' => true,
            'code' => 200,
            'message' => 'Laporan revisi berhasil dikirim ke requester.',
            'data' => []
        ];

        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->shouldReceive('getAuthIdentifier')->andReturn($userId);
        $mockUser->shouldReceive('getAuthIdentifierName')->andReturn('id');
        $mockUser->shouldReceive('getKey')->andReturn($userId);
        $this->actingAs($mockUser, 'sanctum');

        $this->mock(TransactionService::class, function ($mock) use ($revisionId, $userId, $payload, $expectedResult) {
            $mock->shouldReceive('respondToRevision')
                ->once()
                ->with(
                    $revisionId,
                    $userId,
                    Mockery::on(function ($data) {
                        return $data['action'] === 'fixed' && $data['completion_notes'] === 'Pekerjaan selesai diperbaiki.';
                    }),
                    Mockery::any()
                )
                ->andReturn($expectedResult);
        });

        $response = $this->postJson("/api/revisions/{$revisionId}/respond", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Laporan revisi berhasil dikirim ke requester.',
            ]);
    }

    public function test_respond_revision_decline_successfully(): void
    {
        $userId = 'helper-uuid-123';
        $revisionId = 'revision-uuid-999';

        $payload = [
            'action' => 'rejected',
            'dispute_reason' => 'Permintaan revisi di luar kesepakatan.',
            'dispute_images' => [
                \Illuminate\Http\UploadedFile::fake()->image('evidence.jpg')
            ]
        ];

        $expectedResult = [
            'success' => true,
            'code' => 200,
            'message' => 'Revisi ditolak. Transaksi dialihkan ke status sengketa.',
            'data' => []
        ];

        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->shouldReceive('getAuthIdentifier')->andReturn($userId);
        $mockUser->shouldReceive('getAuthIdentifierName')->andReturn('id');
        $mockUser->shouldReceive('getKey')->andReturn($userId);
        $this->actingAs($mockUser, 'sanctum');

        $this->mock(TransactionService::class, function ($mock) use ($revisionId, $userId, $payload, $expectedResult) {
            $mock->shouldReceive('respondToRevision')
                ->once()
                ->with(
                    $revisionId,
                    $userId,
                    Mockery::on(function ($data) {
                        return $data['action'] === 'rejected' && $data['dispute_reason'] === 'Permintaan revisi di luar kesepakatan.';
                    }),
                    Mockery::any()
                )
                ->andReturn($expectedResult);
        });

        $response = $this->postJson("/api/revisions/{$revisionId}/respond", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Revisi ditolak. Transaksi dialihkan ke status sengketa.',
            ]);
    }
}
