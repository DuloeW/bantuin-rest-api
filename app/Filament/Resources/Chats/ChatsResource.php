<?php

namespace App\Filament\Resources\Chats;

use App\Filament\Resources\Chats\Pages\ListChats;
use App\Filament\Resources\Chats\Pages\ViewChat;
use App\Filament\Resources\Chats\Tables\ChatsTable;
use App\Models\Offer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ChatsResource extends Resource
{
    protected static ?string $model = Offer::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    public static function getNavigationLabel(): string
    {
        return 'Monitor Chat';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Audit & Report';
    }

    public static function getModelLabel(): string
    {
        return 'Chat';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Monitor Chat';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return ChatsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChats::route('/'),
            'view'  => ViewChat::route('/{record}'),
        ];
    }
}
