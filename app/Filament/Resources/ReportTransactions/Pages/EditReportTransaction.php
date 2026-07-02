<?php

namespace App\Filament\Resources\ReportTransactions\Pages;

use App\Filament\Resources\ReportTransactions\ReportTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReportTransaction extends EditRecord
{
    protected static string $resource = ReportTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
