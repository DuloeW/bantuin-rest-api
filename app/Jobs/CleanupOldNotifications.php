<?php

namespace App\Jobs;

use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CleanupOldNotifications implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $deleted = Notification::where('created_at', '<', $thirtyDaysAgo)->delete();

        Log::info("Cleaned up $deleted old notifications (older than 30 days)");
    }
}
