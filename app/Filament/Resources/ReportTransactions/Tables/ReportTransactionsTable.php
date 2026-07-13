<?php

namespace App\Filament\Resources\ReportTransactions\Tables;

use App\Models\ReportTransaction;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReportTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction.id')
                    ->label('ID Transaksi')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->limit(12)
                    ->tooltip(fn ($record) => $record->transaction?->id),

                TextColumn::make('reason_category')
                    ->label('Penyebab Dispute')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'revision_declined'   => '🔄 Revisi Ditolak Helper',
                        'refund_declined'     => '💰 Refund Ditolak Helper',
                        'work_unsatisfactory' => '⚠️ Pekerjaan Tidak Sesuai',
                        default               => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'revision_declined'   => 'warning',
                        'refund_declined'     => 'danger',
                        'work_unsatisfactory' => 'warning',
                        default               => 'gray',
                    }),

                TextColumn::make('reporter.first_name')
                    ->label('Pelapor (Requester)')
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) =>
                        ($record->reporter?->first_name ?? '') . ' ' . ($record->reporter?->last_name ?? '')
                    ),

                TextColumn::make('reported.first_name')
                    ->label('Terlapor (Helper)')
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) =>
                        ($record->reported?->first_name ?? '') . ' ' . ($record->reported?->last_name ?? '')
                    ),

                TextColumn::make('transaction.status')
                    ->label('Status Transaksi')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'disputed'         => 'danger',
                        'cancelled'        => 'gray',
                        'pending_revision' => 'warning',
                        'pending_refund'   => 'warning',
                        'completed'        => 'success',
                        default            => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'disputed'         => '⚠️ Disputed',
                        'cancelled'        => '❌ Cancelled',
                        'pending_revision' => '🔄 Pending Revision',
                        'pending_refund'   => '💰 Pending Refund',
                        'completed'        => '✅ Completed',
                        default            => $state ?? '-',
                    }),

                TextColumn::make('status')
                    ->label('Status Laporan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'       => 'warning',
                        'investigating' => 'info',
                        'resolved'      => 'success',
                        default         => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending'       => '⏳ Pending',
                        'investigating' => '🔍 Investigating',
                        'resolved'      => '✅ Resolved',
                        default         => $state,
                    }),

                TextColumn::make('transaction.total_price')
                    ->label('Nilai Sengketa')
                    ->money('idr')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dilaporkan Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Laporan')
                    ->options([
                        'pending'       => '⏳ Pending',
                        'investigating' => '🔍 Investigating',
                        'resolved'      => '✅ Resolved',
                    ]),

                SelectFilter::make('reason_category')
                    ->label('Penyebab Dispute')
                    ->options([
                        'revision_declined'   => '🔄 Revisi Ditolak Helper',
                        'refund_declined'     => '💰 Refund Ditolak Helper',
                        'work_unsatisfactory' => '⚠️ Pekerjaan Tidak Sesuai',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make()
                    ->label('Tinjau & Putuskan'),
            ]);
    }
}