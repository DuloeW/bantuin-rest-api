<?php

namespace App\Filament\Resources\ReportTransactions\Pages;

use App\Filament\Resources\ReportTransactions\ReportTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReportTransaction extends EditRecord
{
    protected static string $resource = ReportTransactionResource::class;

    public function getTitle(): string
    {
        return 'Tinjau Dispute: ' . ($this->record->transaction?->id ?? $this->record->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Hapus Laporan'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Status laporan & catatan admin berhasil disimpan.';
    }
}
