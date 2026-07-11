<?php

namespace App\Console\Commands;

use App\Service\Transaction\TransactionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('app:handle-overdue-transactions')]
#[Description('Automatically mark overdue transactions (helper melewati deadline) as pending_refund.')]
class HandleOverdueTransactions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TransactionService $transactionService): int
    {
        $this->info('Checking for overdue transactions...');

        $result = $transactionService->handleOverdueTransactions();

        $data = $result['data'] ?? [];

        $processedCount = $data['processed_count'] ?? 0;
        $failedCount    = $data['failed_count'] ?? 0;

        $this->info("Processed: {$processedCount} transaction(s).");

        if ($failedCount > 0) {
            $this->warn("Failed: {$failedCount} transaction(s). Check the log for details.");
            Log::warning('HandleOverdueTransactions: Some transactions failed.', $data['failed'] ?? []);
        }

        if ($processedCount === 0 && $failedCount === 0) {
            $this->line('No overdue transactions found.');
        }

        return Command::SUCCESS;
    }
}
