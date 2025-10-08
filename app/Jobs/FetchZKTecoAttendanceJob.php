<?php

namespace App\Jobs;

use App\Models\zkt_machine;
use Rats\Zkteco\Lib\ZKTeco;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FetchZKTecoAttendanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $zkt_info = zkt_machine::where('Status', 1)->get();

        if ($zkt_info->isEmpty()) {
            return;
        }

        foreach ($zkt_info as $info) {
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
                    $zk->disconnect();
                }
            }
        }
    }
}
