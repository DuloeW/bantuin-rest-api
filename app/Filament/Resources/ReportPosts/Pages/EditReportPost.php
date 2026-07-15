<?php

namespace App\Filament\Resources\ReportPosts\Pages;

use App\Filament\Resources\ReportPosts\ReportPostResource;
use App\Service\Notification\NotificationService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditReportPost extends EditRecord
{
    protected static string $resource = ReportPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $report = $this->record;

        if ($report->wasChanged('status')) {
            $reporter = $report->reporter;

            if ($reporter) {
                try {
                    if ($report->status === 'investigating') {
                        app(NotificationService::class)->sendToUser(
                            $reporter,
                            'Your report has been received',
                            'Thank you for reporting this post. Our team is investigating it and will take appropriate action based on our community guidelines.',
                            [
                                'post_id' => (string) $report->post_id,
                            ],
                            'report_investigating'
                        );
                    } elseif ($report->status === 'resolved') {
                        app(NotificationService::class)->sendToUser(
                            $reporter,
                            'Your report has been resolved',
                            'Our team has investigated your report and taken appropriate action. Thank you for helping us keep the community safe.',
                            [
                                'post_id' => (string) $report->post_id,
                            ],
                            'report_resolved'
                        );
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send report post notification: ' . $e->getMessage());
                }
            }
        }
    }
}
