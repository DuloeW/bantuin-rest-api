<?php

namespace App\Filament\Resources\ReportTransactions\Pages;

use App\Filament\Resources\ReportTransactions\ReportTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReportTransaction extends EditRecord
{
    protected static string $resource = ReportTransactionResource::class;

    public function getTitle(): string
    {
        return 'Review Dispute: ' . ($this->record->transaction?->id ?? $this->record->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Delete Report'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Report status & admin notes saved successfully.';
    }

    protected function afterSave(): void
    {
        $report = $this->record;
        $transaction = $report->transaction;
        $escrow = $transaction?->escrow;

        // Process financial resolution if escrow is in disputed or held state
        if (!$escrow || !in_array($escrow->status, ['disputed', 'held'])) {
            return;
        }

        match ($report->status) {
            'resolved' => $this->resolveFullReleaseToHelper($transaction, $escrow),
            'refunded' => $this->resolveFullRefundToRequester($transaction, $escrow),
            'partially_refunded' => $this->resolvePartialRefund($transaction, $escrow),
            default => null,
        };
    }

    /**
     * Resolved: Admin menentukan Helper benar → 100% dana cair ke Helper.
     */
    private function resolveFullReleaseToHelper($transaction, $escrow): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($transaction, $escrow) {
            $netAmount = $escrow->net_amount;

            // Cairkan seluruh net_amount ke Helper
            $helper = $transaction->helper;
            if ($helper) {
                $helper->increment('wallet_balance', $netAmount);
            }

            // Update escrow
            $escrow->update([
                'status' => 'released',
                'released_at' => now(),
                'release_notes' => 'Dispute resolved: full release to helper by Admin. Amount: ' . $netAmount,
                'resolved_by' => auth()->id(),
            ]);

            // Update transaction
            $transaction->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);

            if ($transaction->offer) {
                $transaction->offer->update(['status' => 'completed']);
            }
        });
    }

    /**
     * Refunded: Admin menentukan Requester benar → 100% dana kembali ke Requester.
     */
    private function resolveFullRefundToRequester($transaction, $escrow): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($transaction, $escrow) {
            $heldAmount = $escrow->held_amount;

            // Kembalikan seluruh held_amount ke Requester
            $requester = $transaction->requester;
            if ($requester) {
                $requester->increment('wallet_balance', $heldAmount);
            }

            // Update escrow
            $escrow->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'release_notes' => 'Dispute resolved: full refund to requester by Admin. Amount: ' . $heldAmount,
                'resolved_by' => auth()->id(),
            ]);

            // Update transaction
            $transaction->update([
                'status' => 'cancelled',
                'finished_at' => now(),
            ]);

            if ($transaction->offer) {
                $transaction->offer->update(['status' => 'completed']);
            }
        });
    }

    /**
     * Partially Refunded: Admin membagi 50/50 → masing-masing dapat setengah net_amount.
     */
    private function resolvePartialRefund($transaction, $escrow): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($transaction, $escrow) {
            $netAmount = $escrow->net_amount;
            $halfAmount = $netAmount / 2;

            // Berikan setengah ke Helper
            $helper = $transaction->helper;
            if ($helper) {
                $helper->increment('wallet_balance', $halfAmount);
            }

            // Berikan setengah ke Requester
            $requester = $transaction->requester;
            if ($requester) {
                $requester->increment('wallet_balance', $halfAmount);
            }

            // Update escrow
            $escrow->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'release_notes' => 'Partially refunded: split 50/50 between helper and requester by Admin. Each: ' . $halfAmount,
                'resolved_by' => auth()->id(),
            ]);

            // Update transaction
            $transaction->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);

            if ($transaction->offer) {
                $transaction->offer->update(['status' => 'completed']);
            }
        });
    }
}
