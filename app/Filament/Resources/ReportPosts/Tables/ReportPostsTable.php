<?php

namespace App\Filament\Resources\ReportPosts\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;

class ReportPostsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('post.id')
                ->label('Post ID')
                ->searchable()
                ->sortable(),

            TextColumn::make('reporter.first_name')
                ->label('Reporter')
                ->searchable()
                ->sortable(),

            TextColumn::make('reason_category')
                ->label('Category')
                ->searchable(),

            ImageColumn::make('evidence_file')
                ->label('Evidence')
                ->square(),

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
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ]);
    }
}