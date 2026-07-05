<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DailyAttendanceGenerateService;

class DailyAttendanceGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:daily-attendance-generate';

    protected DailyAttendanceGenerateService $dailyAttendanceGenerateService;

    public function __construct(DailyAttendanceGenerateService $dailyAttendanceGenerateService)
    {
        parent::__construct();
        $this->dailyAttendanceGenerateService = $dailyAttendanceGenerateService;
    }

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Daily Attendance Summary Generation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Daily Attendance Summary Generation started...');
        $this->dailyAttendanceGenerateService->DailyAttendanceUpdate();
        $this->info('Daily Attendance Summary Generation completed.');
    }
}
