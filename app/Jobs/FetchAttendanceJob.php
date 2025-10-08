<?php

namespace App\Jobs;

use App\Models\holiday;
use App\Models\leave;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\attendence;
use App\Models\zkt_attendence;
use Illuminate\Support\Facades\DB;

class FetchAttendanceJob implements ShouldQueue
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
        // Fetch all holidays, leaves, and attendance records as before
        $holidays = holiday::all();
        $leaves = leave::select('leaves.EID', 'leaves.From_Date', 'leaves.To_Date', 'leave_types.short_name')
            ->join('leave_types', 'leaves.Leave_Type_Id', '=', 'leave_types.id')
            ->where('leaves.Status', '=', 'Approved')
            ->get();

        // Get the last processed date from the attendances table
        $lastProcessedDate = attendence::max('Date');

        // Set the start date to the last processed date or a default date
        $startDate = $lastProcessedDate
            ? Carbon::parse($lastProcessedDate)->format('Y-m-d')
            : '2023-10-30';  // Default start date if no previous record exists

        // Set the end date as the current date
        $endDate = zkt_attendence::max('Date');

        // Fetch only new attendance records from the zkt_attendence table
        $attendances = zkt_attendence::where('date', '>=', $startDate)
            ->get()
            ->groupBy(['User_id', 'date']);

        // Initialize the data array
        $insertData = [];

        foreach ($attendances as $userId => $userAttendances) {
            $currentDate = $startDate;

            // Iterate through each date from start to end date
            while ($currentDate <= $endDate) {
                // Check for attendance on the current date
                if (isset($userAttendances[$currentDate])) {
                    $attendanceRecords = $userAttendances[$currentDate];

                    $timeIn = $attendanceRecords->sortBy('time')->first()->time;
                    // $timeOut = $attendanceRecords->sortByDesc('time')->first()->time;
                    $timeOut = $attendanceRecords->max('time');
                    $status = ($timeIn > '11:00:00') ? 'L' : 'P'; // Determine status (Late or Present)

                } else {
                    $status = $this->determineStatus($userId, $currentDate, $holidays, $leaves);
                    $timeIn = $timeOut = null;
                }

                // Add record to the insert array
                $insertData[] = [
                    'EID' => $userId,
                    'Date' => $currentDate,
                    'Time_In' => $timeIn,
                    'Time_Out' => $timeOut,
                    'Status' => $status,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];

                // If the batch size exceeds 1000, insert the data and reset the array
                if (count($insertData) >= 1000) {
                    DB::table('attendences')->insert($insertData);
                    $insertData = []; // Reset insertData array after each batch insert
                }

                $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
            }
        }

        // Insert any remaining data after loop
        if (!empty($insertData)) {
            DB::table('attendences')->insert($insertData);
        }
    }


    private function determineStatus($userId, $date, $holidays, $leaves)
    {
        // Check if the date falls within any leave period
        $isOnLeave = $leaves->first(function ($leave) use ($userId, $date) {
            return $leave->EID == $userId && $date >= $leave->From_Date && $date <= $leave->To_Date;
        });

        if ($isOnLeave) {
            return $isOnLeave->short_name; // Set status to leave type short name
        }

        // Check if the date falls within any holiday period
        $isHoliday = $holidays->contains(function ($holiday) use ($date) {
            return $date >= $holiday->From_Date && $date <= $holiday->To_Date;
        });

        if ($isHoliday) {
            return 'H'; // Holiday
        }

        // Check if the date is a Friday (adjust according to your weekend setting)
        if (Carbon::parse($date)->isFriday()) {
            return 'F'; // Friday/Weekend
        }
        return 'A';
    }
}
