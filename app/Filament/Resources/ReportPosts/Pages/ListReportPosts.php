<?php

namespace App\Filament\Resources\ReportPosts\Pages;

use App\Filament\Resources\ReportPosts\ReportPostResource;
//use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReportPosts extends ListRecords
{
    protected static string $resource = ReportPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //CreateAction::make(),
        ];
    }
}
