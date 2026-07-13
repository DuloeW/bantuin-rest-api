<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->wasChanged('is_verified')) {
            $notificationService = app(\App\Service\Notification\NotificationService::class);
            
            if ($this->record->is_verified) {
                $notificationService->sendToUser(
                    $this->record,
                    'Verification Successful',
                    'Congratulations! Your account has been successfully verified by admin.',
                    ['type' => 'account_verified'],
                    'account_verified'
                );
            } else {
                $notificationService->sendToUser(
                    $this->record,
                    'Verification Revoked',
                    'Your account verification status has been revoked by admin.',
                    ['type' => 'account_unverified'],
                    'account_unverified'
                );
            }
        }
    }
}
