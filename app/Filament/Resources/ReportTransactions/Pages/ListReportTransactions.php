<?php

namespace App\Filament\Resources\ReportTransactions\Pages;

use App\Filament\Resources\ReportTransactions\ReportTransactionResource;
//use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReportTransactions extends ListRecords
{
    protected static string $resource = ReportTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //CreateAction::make(),
        ];
    }
}
