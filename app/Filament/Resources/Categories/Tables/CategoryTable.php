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
                ->label('Judul')
                ->searchable()
                ->sortable(),

            TextColumn::make('slug')
                ->label('Slug')
                ->fontFamily('mono')
                ->color('gray'),

            TextColumn::make('posts_count')
                ->label('Total Postingan')
                ->counts('posts') 
                ->badge()
                ->color('info'),

            TextColumn::make('created_at')
                ->label('Terdaftar Pada')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ]);
    }
}