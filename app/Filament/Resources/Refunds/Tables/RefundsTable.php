<?php

namespace App\Filament\Resources\Refunds\Tables;

use App\Models\Refund;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;

class RefundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user.first_name')
                    ->label('User')
                    ->formatStateUsing(fn ($record) => ($record->user?->first_name ?? '') . ' ' . ($record->user?->last_name ?? ''))
                    ->searchable(),

                TextColumn::make('transaction.id')
                    ->label('Transaction ID')
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('amount')
                    ->label('Jumlah (Rp)')
                    ->money('idr')
                    ->sortable(),

                TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(40)
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('processed_at')
                    ->label('Diproses Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('approveRefund')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Refund')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui refund ini? Dana akan dikembalikan ke user.')
                    ->action(function (Refund $record) {
                        $record->update([
                            'status' => 'completed',
                            'processed_at' => now(),
                        ]);

                        // Update escrow status to refunded if exists
                        $escrow = $record->transaction?->escrow;
                        if ($escrow && $escrow->status === 'held') {
                            $escrow->update([
                                'status' => 'refunded',
                                'refunded_at' => now(),
                            ]);
                        }

                        // Update transaction status
                        $record->transaction?->update(['status' => 'cancelled']);

                        Notification::make()
                            ->title('Refund berhasil disetujui')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Refund $record): bool => $record->status === 'pending' || $record->status === 'processing'),
                Action::make('rejectRefund')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Refund')
                    ->modalDescription('Berikan alasan penolakan refund.')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->required(),
                    ])
                    ->action(function (Refund $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'reason' => ($record->reason ? $record->reason . ' | Ditolak: ' : 'Ditolak: ') . $data['rejection_reason'],
                            'processed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Refund ditolak')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn (Refund $record): bool => $record->status === 'pending' || $record->status === 'processing'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
