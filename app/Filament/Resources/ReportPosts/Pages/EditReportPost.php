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

        // Cek jika status berubah
        if ($report->wasChanged('status')) {
            $reporter = $report->reporter;

            if ($reporter) {
                try {
                    $postTitle = $report->post->title ?? 'Postingan';

                    if ($report->status === 'investigating') {
                        app(NotificationService::class)->sendToUser(
                            $reporter,
                            'Laporan Sedang Diproses',
                            'Laporan Anda terhadap postingan "'.$postTitle.'" sedang dalam tahap investigasi oleh tim kami.',
                            [
                                'post_id' => (string) $report->post_id,
                            ],
                            'report_investigating'
                        );
                    } elseif ($report->status === 'resolved') {
                        app(NotificationService::class)->sendToUser(
                            $reporter,
                            'Laporan Selesai Diproses',
                            'Investigasi terhadap laporan Anda pada postingan "'.$postTitle.'" telah selesai. Terima kasih atas laporan Anda.',
                            [
                                'post_id' => (string) $report->post_id,
                            ],
                            'report_resolved'
                        );
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send report post notification: '.$e->getMessage());
                }
            }
        }
    }
}
