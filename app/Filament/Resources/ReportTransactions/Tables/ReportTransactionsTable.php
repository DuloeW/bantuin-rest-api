<?php

namespace App\Filament\Resources\ReportTransactions\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;

class ReportTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('transaction.id')
                ->label('TRX ID')
                ->searchable()
                ->sortable(),

            TextColumn::make('reporter.first_name')
                ->label('Reporter')
                ->searchable(),

            TextColumn::make('reported.first_name')
                ->label('Reported User')
                ->searchable(),

            ImageColumn::make('evidence_file')
                ->label('Evidence')
                ->circular(),

            TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'gray',
                    'investigating' => 'warning',
                    'resolved' => 'success',
                    default => 'gray',
                }),

            TextColumn::make('created_at')
                ->dateTime()
                ->label('Reported At')
                ->sortable(),
        ]);
    }
}