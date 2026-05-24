<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run EOD Report generation at 11:59 PM every day
        $schedule->command('eod:report')
            ->dailyAt('23:59')
            ->description('Generate End-of-Day audit report')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/eod-report.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
