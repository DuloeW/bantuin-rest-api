<?php

namespace App\Filament\Resources\ReportPosts;

use App\Models\ReportPost;
use App\Filament\Resources\ReportPosts\Schemas\ReportPostForm;
use App\Filament\Resources\ReportPosts\Tables\ReportPostsTable;
use App\Filament\Resources\ReportPosts\Pages;

use Filament\Resources\Resource;
use Filament\Schemas\Schema; 
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

use BackedEnum;
use UnitEnum;

class ReportPostResource extends Resource
{
    protected static ?string $model = ReportPost::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-flag';
    
    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    public static function form(Schema $schema): Schema
    {
        // Memanggil fungsi configure sesuai standar terbarumu
        return ReportPostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        // Panggil konfigurasi kolom tabel dulu
        $table = ReportPostsTable::configure($table);

        // Lalu tambahkan actions-nya
        return $table
            ->filters([
                //
            ])
            ->recordActions([
               EditAction::make(),
            ])
            ->groupedBulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReportPosts::route('/'),
            'edit' => Pages\EditReportPost::route('/{record}/edit'),
        ];
    }
}