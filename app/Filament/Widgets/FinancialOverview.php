<?php

namespace App\Filament\Widgets;

use App\Models\EscrowTransaction;
use App\Models\Payment;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $heldEscrow = EscrowTransaction::where('status', 'held')->sum('held_amount');
        $adminFees = EscrowTransaction::where('status', 'released')->sum('fee_amount');
        $grossVolume = Payment::where('status', 'completed')->sum('amount');
        $refundedAmount = EscrowTransaction::where('status', 'refunded')->sum('held_amount');

        $totalTransactions = Transaction::count();
        $successfulTransactions = EscrowTransaction::where('status', 'released')->count();
        $refundedTransactions = EscrowTransaction::where('status', 'refunded')->count();
        $disputedTransactions = EscrowTransaction::where('status', 'disputed')->count();

        return [
            Stat::make('Funds Held (Escrow)', 'Rp '.number_format((float) $heldEscrow, 0, ',', '.'))
                ->description('Money not yet released to helper')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('warning'),

            Stat::make('Total Admin Revenue', 'Rp '.number_format((float) $adminFees, 0, ',', '.'))
                ->description('Total admin fees from completed transactions')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Gross Volume', 'Rp '.number_format((float) $grossVolume, 0, ',', '.'))
                ->description('Total gross volume from successful payments')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),

            Stat::make('Refunded Funds', 'Rp '.number_format((float) $refundedAmount, 0, ',', '.'))
                ->description('Total money refunded to requesters')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('danger'),

            Stat::make('Total Transactions', $totalTransactions)
                ->description('Total overall transactions')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('gray'),

            Stat::make('Successful Transactions', $successfulTransactions)
                ->description('Transactions that have been completed')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Refunded Transactions', $refundedTransactions)
                ->description('Transactions that have been refunded')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('warning'),

            Stat::make('Disputes', $disputedTransactions)
                ->description('Transactions currently disputed')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
