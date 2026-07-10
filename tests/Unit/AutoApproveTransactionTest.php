<?php

namespace Tests\Unit;

use App\Jobs\AutoApproveTransaction;
use App\Models\Transaction;
use App\Service\Transaction\TransactionService;
use Mockery;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class AutoApproveTransactionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_job_approves_transaction_when_pending_approval(): void
    {
        $transactionId = 'tx-123';
        $requesterId = 'req-456';

        // Overload/Mock the Transaction model to bypass database
        $transactionMock = Mockery::mock('overload:' . Transaction::class);
        $transactionMock->shouldReceive('find')
            ->once()
            ->with($transactionId)
            ->andReturn($transactionMock);

        // Set status, id, and requester_id attributes
        $transactionMock->status = 'pending_approval';
        $transactionMock->id = $transactionId;
        $transactionMock->requester_id = $requesterId;

        // Mock TransactionService
        $transactionService = Mockery::mock(TransactionService::class);
        $transactionService->shouldReceive('approveTransaction')
            ->once()
            ->with($transactionId, $requesterId)
            ->andReturn([]);

        // Execute the job
        $job = new AutoApproveTransaction($transactionId);
        $job->handle($transactionService);

        // We assert true so PHPUnit doesn't complain about no assertions
        $this->assertTrue(true);
    }

    public function test_job_skips_approval_when_not_pending_approval(): void
    {
        $transactionId = 'tx-123';

        // Overload/Mock the Transaction model to bypass database
        $transactionMock = Mockery::mock('overload:' . Transaction::class);
        $transactionMock->shouldReceive('find')
            ->once()
            ->with($transactionId)
            ->andReturn($transactionMock);

        // Set status to completed (not pending_approval)
        $transactionMock->status = 'completed';

        // Mock TransactionService (should not receive approveTransaction)
        $transactionService = Mockery::mock(TransactionService::class);
        $transactionService->shouldNotReceive('approveTransaction');

        // Execute the job
        $job = new AutoApproveTransaction($transactionId);
        $job->handle($transactionService);

        $this->assertTrue(true);
    }
}
