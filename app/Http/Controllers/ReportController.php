<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $attendances = Attendance::where('user_id', $user->id)
            ->where('work_date', '>=', Carbon::now()->subMonths(5)->startOfMonth())
            ->get();

        $totalWorkMinutes = 0;

        foreach ($attendances as $attendance) {

            if ($attendance->clock_in && $attendance->clock_out) {
                $clockIn = Carbon::parse($attendance->clock_in);
                $clockOut = Carbon::parse($attendance->clock_out);

                $workMinutes = $clockIn->diffInMinutes($clockOut);


                $breaks = BreakTime::where('attendance_id', $attendance->id)->get();

                $totalBreakMinutes = 0;

                foreach ($breaks as $break) {
                    if ($break->break_start && $break->break_end) {
                        $breakStart = Carbon::parse($break->break_start);
                        $breakEnd = Carbon::parse($break->break_end);

                        $totalBreakMinutes += $breakStart->diffInMinutes($breakEnd);
                    }
                }

                $workMinutes -= $totalBreakMinutes;

                $totalWorkMinutes += $workMinutes;
            }
        }
        $summary = [
            'total_work_minutes' => $totalWorkMinutes,
        ];
    }
}
