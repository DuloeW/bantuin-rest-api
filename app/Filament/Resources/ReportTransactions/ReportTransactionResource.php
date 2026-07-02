<?php

namespace App\Filament\Resources\ReportTransactions;

use App\Models\ReportTransaction;
use App\Filament\Resources\ReportTransactions\Pages;
use App\Filament\Resources\ReportTransactions\Schemas\ReportTransactionForm;
use App\Filament\Resources\ReportTransactions\Tables\ReportTransactionsTable;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use BackedEnum;
use UnitEnum;

class ReportTransactionResource extends Resource
{
    protected static ?string $model = ReportTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    public static function form(Schema $schema): Schema
    {
        return ReportTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        $table = ReportTransactionsTable::configure($table);

        return $table
            ->recordActions([
                EditAction::make(),
            ])
            ->groupedBulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReportTransactions::route('/'),
            'edit' => Pages\EditReportTransaction::route('/{record}/edit'),
        ];
    }
}