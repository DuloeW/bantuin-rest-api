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
            Section::make('Category Information')
                ->description('Use this section to define service or post categories.')
                ->schema([
                    TextInput::make('title')
                        ->label('Category Title')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (string $state, $set) => $set('slug', Str::slug($state))),

                   
                ])->columns(2),
        ]);
    }
}