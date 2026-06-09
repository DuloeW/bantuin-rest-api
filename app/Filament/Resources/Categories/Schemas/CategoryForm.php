<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Kategori')
                ->description('Gunakan bagian ini untuk menentukan kategori jasa atau postingan.')
                ->schema([
                    TextInput::make('title')
                        ->label('Judul Kategori')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (string $state, $set) => $set('slug', Str::slug($state))),

                   
                ])->columns(2),
        ]);
    }
}