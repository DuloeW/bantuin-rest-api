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
        return 'Transaction Disputes';
    }

    public static function getModelLabel(): string
    {
        return 'Transaction Dispute';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Transaction Disputes';
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
                    ->label('Review & Decide'),

                Action::make('markInvestigating')
                    ->label('Start Investigation')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Start Investigating Report?')
                    ->modalDescription('The report status will be changed to "Investigating". Admin will begin reviewing this case.')
                    ->action(fn (ReportTransaction $record) => $record->update(['status' => 'investigating']))
                    ->visible(fn (ReportTransaction $record): bool => $record->status === 'pending'),

                Action::make('resolvePartialRefund')
                    ->label('Resolve: 50/50 Split')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Decide: Split Funds 50/50?')
                    ->modalDescription('This action will split the remaining escrow balance equally between Requester and Helper. Make sure admin_notes are filled on the edit page.')
                    ->action(function (ReportTransaction $record) {
                        $record->update([
                            'status'      => 'resolved',
                            'resolved_at' => now(),
                        ]);
                        $record->transaction?->update(['status' => 'partial_refund']);
                    })
                    ->visible(fn (ReportTransaction $record): bool => in_array($record->status, ['pending', 'investigating'])),

                Action::make('resolveWithRefund')
                    ->label('Resolve: Refund Requester')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Decide: Refund Funds to Requester?')
                    ->modalDescription('This action will mark the report as Resolved and the transaction as Cancelled. Escrow funds will be returned to the requester. Make sure admin_notes are filled on the edit page.')
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
                                    'Dispute Resolved – Refund Approved',
                                    'Admin has resolved the dispute for "' . $postTitle . '". Funds will be returned to your account.',
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
                                    'Dispute Resolved – Refund to Requester',
                                    'Admin has decided that the dispute for "' . $postTitle . '" is resolved with a refund to the requester.',
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
                    ->label('Resolve: Reject Requester Claim')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Decide: Reject Claim, Funds Stay with Helper?')
                    ->modalDescription('This action will mark the report as Resolved. The transaction remains Disputed but there is no refund. Make sure admin_notes are filled on the edit page.')
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
                                    'Dispute Resolved – Claim Rejected',
                                    'Admin has reviewed the dispute for "' . $postTitle . '". Your refund claim is rejected. Please check details for more information.',
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
                                    'Dispute Resolved – Requester Claim Rejected',
                                    'Admin has decided that the claim for "' . $postTitle . '" is rejected. Funds are not returned to the requester.',
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