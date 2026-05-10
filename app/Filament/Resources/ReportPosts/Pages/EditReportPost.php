<?php

namespace App\Filament\Resources\ReportPosts\Pages;

use App\Filament\Resources\ReportPosts\ReportPostResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReportPost extends EditRecord
{
    protected static string $resource = ReportPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
