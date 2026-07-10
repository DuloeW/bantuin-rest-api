<?php

namespace App\Filament\Resources\Refunds;

use App\Filament\Resources\Refunds\Pages;
use App\Filament\Resources\Refunds\Schemas\RefundForm;
use App\Filament\Resources\Refunds\Tables\RefundsTable;
use App\Models\Refund;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RefundResource extends Resource
{
    protected static ?string $model = Refund::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    public static function getNavigationLabel(): string
    {
        return 'Refund';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Audit & Report';
    }

    public static function getModelLabel(): string
    {
        return 'Refund';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Refund';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return RefundForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RefundsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRefunds::route('/'),
            'edit' => Pages\EditRefund::route('/{record}/edit'),
        ];
    }
}
