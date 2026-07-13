<?php

namespace App\Filament\Resources\ReportTransactions;

use App\Models\ReportTransaction;
use App\Models\Transaction;
use App\Filament\Resources\ReportTransactions\Pages;
use App\Filament\Resources\ReportTransactions\Schemas\ReportTransactionForm;
use App\Filament\Resources\ReportTransactions\Tables\ReportTransactionsTable;
use App\Service\Notification\NotificationService;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Log;
use BackedEnum;
use UnitEnum;

class ReportTransactionResource extends Resource
{
    protected static ?string $model = ReportTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static string|UnitEnum|null $navigationGroup = 'Audit & Report';

    public static function getNavigationLabel(): string
    {
        return 'Dispute Transaksi';
    }

    public static function getModelLabel(): string
    {
        return 'Dispute Transaksi';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Dispute Transaksi';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ReportTransaction::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return ReportTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        $table = ReportTransactionsTable::configure($table);

        return $table
            ->recordActions([
                EditAction::make()
                    ->label('Tinjau & Putuskan'),

                Action::make('markInvestigating')
                    ->label('Mulai Investigasi')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Mulai Investigasi Laporan?')
                    ->modalDescription('Status laporan akan diubah menjadi "Investigating". Admin akan mulai meninjau kasus ini.')
                    ->action(fn (ReportTransaction $record) => $record->update(['status' => 'investigating']))
                    ->visible(fn (ReportTransaction $record): bool => $record->status === 'pending'),

                Action::make('resolveWithRefund')
                    ->label('Selesaikan: Refund ke Requester')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Putuskan: Refund Dana ke Requester?')
                    ->modalDescription('Tindakan ini akan menandai laporan sebagai Resolved dan transaksi sebagai Cancelled. Dana escrow akan dikembalikan ke requester. Pastikan admin_notes sudah diisi di halaman edit.')
                    ->action(function (ReportTransaction $record) {
                        $record->update([
                            'status'      => 'resolved',
                            'resolved_at' => now(),
                        ]);
                        $record->transaction?->update(['status' => 'cancelled']);

                        // Notifikasi ke requester dan helper
                        $trx      = $record->transaction?->load(['requester', 'helper', 'offer.post']);
                        $postTitle = $trx?->offer?->post?->title ?? 'Project';
                        $notif    = app(NotificationService::class);

                        if ($trx?->requester) {
                            try {
                                $notif->sendToUser(
                                    $trx->requester,
                                    'Dispute Diselesaikan – Refund Disetujui',
                                    'Admin telah menyelesaikan sengketa untuk "' . $postTitle . '". Dana akan dikembalikan ke akun Anda.',
                                    [
                                        'transaction_id' => (string) $trx->id,
                                        'screen'         => 'dispute_detail',
                                        'result'         => 'refunded',
                                    ],
                                    'dispute_resolved'
                                );
                            } catch (\Exception $e) {
                                Log::error('Dispute resolve notif to requester failed: ' . $e->getMessage());
                            }
                        }

                        if ($trx?->helper) {
                            try {
                                $notif->sendToUser(
                                    $trx->helper,
                                    'Dispute Diselesaikan – Refund ke Requester',
                                    'Admin telah memutuskan bahwa sengketa untuk "' . $postTitle . '" diselesaikan dengan pengembalian dana ke requester.',
                                    [
                                        'transaction_id' => (string) $trx->id,
                                        'screen'         => 'dispute_detail',
                                        'result'         => 'refunded',
                                    ],
                                    'dispute_resolved'
                                );
                            } catch (\Exception $e) {
                                Log::error('Dispute resolve notif to helper failed: ' . $e->getMessage());
                            }
                        }
                    })
                    ->visible(fn (ReportTransaction $record): bool => in_array($record->status, ['pending', 'investigating'])),

                Action::make('resolveNoRefund')
                    ->label('Selesaikan: Tolak Klaim Requester')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Putuskan: Tolak Klaim, Dana Tetap ke Helper?')
                    ->modalDescription('Tindakan ini akan menandai laporan sebagai Resolved. Transaksi tetap Disputed tetapi tidak ada refund. Pastikan admin_notes sudah diisi di halaman edit.')
                    ->action(function (ReportTransaction $record) {
                        $record->update([
                            'status'      => 'resolved',
                            'resolved_at' => now(),
                        ]);

                        // Notifikasi ke requester dan helper
                        $trx      = $record->transaction?->load(['requester', 'helper', 'offer.post']);
                        $postTitle = $trx?->offer?->post?->title ?? 'Project';
                        $notif    = app(NotificationService::class);

                        if ($trx?->requester) {
                            try {
                                $notif->sendToUser(
                                    $trx->requester,
                                    'Dispute Diselesaikan – Klaim Ditolak',
                                    'Admin telah meninjau sengketa untuk "' . $postTitle . '". Klaim refund Anda tidak dikabulkan. Silakan cek detail untuk informasi lebih lanjut.',
                                    [
                                        'transaction_id' => (string) $trx->id,
                                        'screen'         => 'dispute_detail',
                                        'result'         => 'no_refund',
                                    ],
                                    'dispute_resolved'
                                );
                            } catch (\Exception $e) {
                                Log::error('Dispute no-refund notif to requester failed: ' . $e->getMessage());
                            }
                        }

                        if ($trx?->helper) {
                            try {
                                $notif->sendToUser(
                                    $trx->helper,
                                    'Dispute Diselesaikan – Klaim Requester Ditolak',
                                    'Admin telah memutuskan bahwa klaim untuk "' . $postTitle . '" tidak dikabulkan. Dana tidak dikembalikan ke requester.',
                                    [
                                        'transaction_id' => (string) $trx->id,
                                        'screen'         => 'dispute_detail',
                                        'result'         => 'no_refund',
                                    ],
                                    'dispute_resolved'
                                );
                            } catch (\Exception $e) {
                                Log::error('Dispute no-refund notif to helper failed: ' . $e->getMessage());
                            }
                        }
                    })
                    ->visible(fn (ReportTransaction $record): bool => in_array($record->status, ['pending', 'investigating'])),
            ])
            ->groupedBulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReportTransactions::route('/'),
            'edit'  => Pages\EditReportTransaction::route('/{record}/edit'),
        ];
    }
}