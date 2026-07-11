<?php

namespace App\Console;

use App\Console\Commands\HandleOverdueTransactions;
use App\Jobs\CleanupOldNotifications;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new CleanupOldNotifications())->dailyAt('02:00');

        // Check overdue transactions every 5 minutes
        $schedule->command(HandleOverdueTransactions::class)->everyFiveMinutes();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
