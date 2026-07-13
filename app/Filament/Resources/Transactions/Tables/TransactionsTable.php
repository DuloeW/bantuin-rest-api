<?php

namespace App\Filament\Resources\Transactions\Tables;

use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('requester.first_name')
                    ->label('Requester')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('helper.first_name')
                    ->label('Helper')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_price')
                    ->label('Total (Rp)')
                    ->money('idr')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'on_progress' => 'info',
                        'completed' => 'success',
                        'disputed' => 'danger',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),

                TextColumn::make('deadline')
                    ->label('Deadline')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'on_progress' => 'On Progress',
                        'completed' => 'Completed',
                        'disputed' => 'Disputed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('markAsDisputed')
                    ->label('Mark as Disputed')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Transaction $record) => $record->update(['status' => 'disputed']))
                    ->visible(fn (Transaction $record): bool => !in_array($record->status, ['completed', 'disputed', 'cancelled'])),
                Action::make('cancelTransaction')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Transaction $record) => $record->update(['status' => 'cancelled']))
                    ->visible(fn (Transaction $record): bool => !in_array($record->status, ['completed', 'cancelled'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
