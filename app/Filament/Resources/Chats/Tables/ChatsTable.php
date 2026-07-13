<?php

namespace App\Filament\Resources\Chats\Tables;

use App\Models\Offer;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ChatsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(
                Offer::query()->withCount('messages')
            )
            ->columns([
                TextColumn::make('id')
                    ->label('Offer ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('helper.first_name')
                    ->label('Helper')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Offer $record): string => $record->helper?->email ?? '-'),

                TextColumn::make('requester.first_name')
                    ->label('Requester')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Offer $record): string => $record->requester?->email ?? '-'),

                TextColumn::make('post.title')
                    ->label('Post')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('offered_price')
                    ->label('Offered Price')
                    ->money('idr')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status Offer')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'accepted'  => 'success',
                        'rejected'  => 'danger',
                        'completed' => 'info',
                        default     => 'gray',
                    }),

                TextColumn::make('messages_count')
                    ->label('Total Messages')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Offer')
                    ->options([
                        'pending'   => 'Pending',
                        'accepted'  => 'Accepted',
                        'rejected'  => 'Rejected',
                        'completed' => 'Completed',
                    ]),
            ])
            ->recordActions([
                Action::make('view_chat')
                    ->label('View Chat')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (Offer $record): string => route('filament.admin.resources.chats.view', $record)),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
