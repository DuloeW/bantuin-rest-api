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
                    ->label('Transaction ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->limit(12)
                    ->tooltip(fn ($record) => $record->transaction?->id),

                TextColumn::make('reason_category')
                    ->label('Dispute Reason')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'revision_declined'   => 'Revision Declined by Helper',
                        'refund_declined'     => 'Refund Declined by Helper',
                        'work_unsatisfactory' => 'Unsatisfactory Work',
                        default               => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'revision_declined'   => 'warning',
                        'refund_declined'     => 'danger',
                        'work_unsatisfactory' => 'warning',
                        default               => 'gray',
                    }),

                TextColumn::make('reporter.first_name')
                    ->label('Reporter (Requester)')
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) =>
                        ($record->reporter?->first_name ?? '') . ' ' . ($record->reporter?->last_name ?? '')
                    ),

                TextColumn::make('reported.first_name')
                    ->label('Reported (Helper)')
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) =>
                        ($record->reported?->first_name ?? '') . ' ' . ($record->reported?->last_name ?? '')
                    ),

                TextColumn::make('transaction.status')
                    ->label('Transaction Status')
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
                        'disputed'         => 'Disputed',
                        'cancelled'        => 'Cancelled',
                        'pending_revision' => 'Pending Revision',
                        'pending_refund'   => 'Pending Refund',
                        'completed'        => 'Completed',
                        default            => $state ?? '-',
                    }),

                TextColumn::make('status')
                    ->label('Report Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'            => 'warning',
                        'investigating'      => 'info',
                        'resolved'           => 'success',
                        'refunded'           => 'danger',
                        'partially_refunded' => 'primary',
                        default              => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending'            => 'Pending',
                        'investigating'      => 'Investigating',
                        'resolved'           => 'Resolved (Helper)',
                        'refunded'           => 'Refunded (Requester)',
                        'partially_refunded' => 'Partially Refunded',
                        default              => $state,
                    }),

                TextColumn::make('transaction.total_price')
                    ->label('Disputed Amount')
                    ->money('idr')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Reported At')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Report Status')
                    ->options([
                        'pending'            => 'Pending',
                        'investigating'      => 'Investigating',
                        'resolved'           => 'Resolved (Helper)',
                        'refunded'           => 'Refunded (Requester)',
                        'partially_refunded' => 'Partially Refunded',
                    ]),

                SelectFilter::make('reason_category')
                    ->label('Dispute Reason')
                    ->options([
                        'revision_declined'   => 'Revision Declined by Helper',
                        'refund_declined'     => 'Refund Declined by Helper',
                        'work_unsatisfactory' => 'Unsatisfactory Work',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make()
                    ->label('Review & Decide'),
            ]);
    }
}