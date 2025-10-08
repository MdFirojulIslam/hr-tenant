<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Rats\Zkteco\Lib\ZKTeco;
use App\Models\zkt_attendence;
use App\Models\zkt_machine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FetchAttendance extends Command
{
    protected $signature = 'attendance:fetch';
    protected $description = 'Fetch attendance data from ZKTeco K40';

    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        $zkt_info = zkt_machine::where('Status', 1)->get();

        if ($zkt_info->isEmpty()) {
            $this->info('Cannot find any machine');
            return;
        }

        foreach ($zkt_info as $info) {
            $this->info("Processing machine: IP {$info->IP}, Port {$info->port}");
            $zk = new ZKTeco($info->IP, $info->port);

            if ($zk->connect()) {
                $attendanceData = $zk->getAttendance();

                if (!empty($attendanceData)) {
                    // Prepare data for bulk insertion
                    $newRecords = [];
                    foreach ($attendanceData as $attendance) {
                        $timestamp = Carbon::parse($attendance['timestamp']);
                        $newRecords[] = [
                            'user_id' => $attendance['id'],
                            'Machine_Id' => $info->id,
                            'date' => $timestamp->toDateString(),
                            'time' => $timestamp->toTimeString(),
                        ];
                    }

                    // Chunk the data and insert in batches
                    $chunks = array_chunk($newRecords, 500); // Process in chunks of 500
                    foreach ($chunks as $chunk) {
                        // Use raw SQL for upsert (insert or ignore if duplicate)
                        $values = implode(',', array_map(function ($record) {
                            return "(
                        '{$record['user_id']}',
                        '{$record['Machine_Id']}',
                        '{$record['date']}',
                        '{$record['time']}'
                    )";
                        }, $chunk));

                        DB::statement("
                    INSERT IGNORE INTO zkt_attendences (user_id, Machine_Id, date, time)
                    VALUES $values
                ");
                    }

                    $this->info('Attendance data fetched and updated successfully.');
                } else {
                    $this->info('No attendance data available.');
                }
 		//$zk->clearAttendance(); 
                $zk->disconnect();
            } else {
                $this->error("Unable to connect to device: IP {$info->IP}, Port {$info->port}");
            }
        }
    }
}
