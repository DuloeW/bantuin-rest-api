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
            Stat::make('Dana Tertahan (Escrow)', 'Rp '.number_format((float) $heldEscrow, 0, ',', '.'))
                ->description('Uang yang belum diteruskan ke helper')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('warning'),

            Stat::make('Total Pendapatan Admin', 'Rp '.number_format((float) $adminFees, 0, ',', '.'))
                ->description('Total fee admin dari transaksi selesai')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Total Perputaran Uang', 'Rp '.number_format((float) $grossVolume, 0, ',', '.'))
                ->description('Total gross volume dari pembayaran berhasil')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),

            Stat::make('Dana Dikembalikan (Refund)', 'Rp '.number_format((float) $refundedAmount, 0, ',', '.'))
                ->description('Total uang yang dikembalikan ke requester')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('danger'),

            Stat::make('Total Transaksi', $totalTransactions)
                ->description('Total keseluruhan transaksi')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('gray'),

            Stat::make('Transaksi Sukses', $successfulTransactions)
                ->description('Transaksi yang sudah selesai')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Transaksi Refund', $refundedTransactions)
                ->description('Transaksi yang dikembalikan')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('warning'),

            Stat::make('Sengketa (Dispute)', $disputedTransactions)
                ->description('Transaksi yang sedang bermasalah')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
