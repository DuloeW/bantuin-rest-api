<?php

namespace App\Filament\Resources\Withdrawals;

use App\Filament\Resources\Withdrawals\Pages;
use App\Filament\Resources\Withdrawals\Schemas\WithdrawalForm;
use App\Filament\Resources\Withdrawals\Tables\WithdrawalsTable;
use App\Models\Withdrawal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    public static function getNavigationLabel(): string
    {
        return 'Withdrawal';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Keuangan';
    }

    public static function getModelLabel(): string
    {
        return 'Withdrawal';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Withdrawal';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return WithdrawalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WithdrawalsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWithdrawals::route('/'),
            'edit' => Pages\EditWithdrawal::route('/{record}/edit'),
        ];
    }
}
