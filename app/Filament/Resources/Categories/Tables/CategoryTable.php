<?php

namespace App\Filament\Resources\Categories\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class CategoryTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')
                ->label('Title')
                ->searchable()
                ->sortable(),

            TextColumn::make('slug')
                ->label('Slug')
                ->fontFamily('mono')
                ->color('gray'),

            TextColumn::make('posts_count')
                ->label('Total Posts')
                ->counts('posts') 
                ->badge()
                ->color('info'),

            TextColumn::make('created_at')
                ->label('Created At')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ]);
    }
}