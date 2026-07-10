<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Service\Transaction\TransactionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoApproveTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $transactionId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $transactionId)
    {
        $this->transactionId = $transactionId;
    }

    /**
     * Execute the job.
     */
    public function handle(TransactionService $transactionService): void
    {
        $transaction = Transaction::find($this->transactionId);

        if (!$transaction) {
            Log::info("AutoApproveTransaction: Transaction {$this->transactionId} not found.");
            return;
        }

        // Only approve if the transaction is still in 'pending_approval' status
        if ($transaction->status === 'pending_approval') {
            Log::info("AutoApproveTransaction: Auto-approving transaction {$this->transactionId} after timeout.");
            $transactionService->approveTransaction($transaction->id, $transaction->requester_id);
        } else {
            Log::info("AutoApproveTransaction: Transaction {$this->transactionId} is not in pending_approval status (current status: {$transaction->status}). Skipping auto-approval.");
        }
    }
}
