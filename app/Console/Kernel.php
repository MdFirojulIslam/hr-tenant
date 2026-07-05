<?php

namespace App\Console;

use App\Console\Commands\DailyAttendanceGenerate;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\FetchAttendance;
use App\Console\Commands\MonthlyAttendanceGenerate;
use App\Jobs\CommonJob;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected $commands = [
        FetchAttendance::class,
        DailyAttendanceGenerate::class,
        MonthlyAttendanceGenerate::class
    ];


    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new CommonJob())->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
