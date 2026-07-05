<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MonthlyAttendanceGenerateService;

class MonthlyAttendanceGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:monthly-attendance-generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monthly Attendance Summary Generation';

    protected MonthlyAttendanceGenerateService $monthlyAttendanceGenerateService;

    public function __construct(MonthlyAttendanceGenerateService $monthlyAttendanceGenerateService)
    {
        parent::__construct();
        $this->monthlyAttendanceGenerateService = $monthlyAttendanceGenerateService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Monthly Attendance Summary Generation started...');
        $this->monthlyAttendanceGenerateService->monthlyAttendanceCommonInformation();
        $this->info('Monthly Attendance Summary Generation completed.');
    }
}
