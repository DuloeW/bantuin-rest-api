<?php

namespace App\Filament\Resources\Transactions;

use App\Filament\Resources\Transactions\Pages\CreateTransactions;
use App\Filament\Resources\Transactions\Pages\EditTransactions;
use App\Filament\Resources\Transactions\Pages\ListTransactions;
use App\Filament\Resources\Transactions\Schemas\TransactionForm;
use App\Filament\Resources\Transactions\Tables\TransactionsTable;
use App\Models\Transaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TransactionsResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';
    public static function getNavigationLabel(): string
    {
        return 'Transaction Audit';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Audit & Report';
    }

    public static function getModelLabel(): string
    {
        return 'Transaction';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Transaction Audit';
    }
    
    public static function canCreate(): bool
    {
        return false;
    }

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return TransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransactions::route('/'),
            'edit' => EditTransactions::route('/{record}/edit'),
        ];
    }
}
