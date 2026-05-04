<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('role', 'user'))
            ->columns([
                TextColumn::make('Full Name')
                    ->getStateUsing(fn (User $record) => $record->first_name . ' ' . $record->last_name)
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('province')
                    ->searchable(),
                TextColumn::make('district')
                    ->searchable(),
                TextColumn::make('sub_district')
                    ->searchable(),
                TextColumn::make('village')
                    ->searchable(),
                TextColumn::make('neighborhood_unit')
                    ->searchable(),
                TextColumn::make('wallet_balance')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('role')
                    ->badge(),
                IconColumn::make('is_verified')
                    ->boolean(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
