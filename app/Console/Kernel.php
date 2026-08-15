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

        $schedule->command('mobile:dispatch-notifications check_in')
            ->dailyAt('09:50')
            ->timezone('Asia/Dhaka');

        $schedule->command('mobile:dispatch-notifications check_out')
            ->dailyAt('18:30')
            ->timezone('Asia/Dhaka');

        $schedule->command('mobile:dispatch-notifications expense')
            ->dailyAt('22:00')
            ->timezone('Asia/Dhaka');

        $schedule->command('mobile:dispatch-notifications outlet')
            ->hourly()
            ->timezone('Asia/Dhaka');

        $schedule->command('mobile:dispatch-notifications events')
            ->hourly()
            ->timezone('Asia/Dhaka');
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
