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
        $totalOvertimeMinutes = 0;
        $workDayCount = 0;

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

                if ($workMinutes > 480) {
                    $totalOvertimeMinutes += $workMinutes - 480;
                }

                $workDayCount++;
            }
        }

        $avgWorkMinutes = $workDayCount > 0
            ? intdiv($totalWorkMinutes, $workDayCount)
            : 0;

        $summary = [
            'total_work_minutes' => $totalWorkMinutes,
            'total_overtime_minutes' => $totalOvertimeMinutes,
            'avg_work_minutes' => $avgWorkMinutes,
        ];

        $monthlyTrend = [];

        for ($i = 5; $i >= 0; $i--) {

            $month = Carbon::now()->subMonths($i);

            $monthlyAttendances = Attendance::where('user_id', $user->id)
                ->whereYear('work_date', $month->year)
                ->whereMonth('work_date', $month->month)
                ->get();

            $monthlyWorkMinutes = 0;
            $monthlyOvertimeMinutes = 0;

            foreach ($monthlyAttendances as $attendance) {

                if ($attendance->clock_in && $attendance->clock_out) {

                    $clockIn = Carbon::parse($attendance->clock_in);
                    $clockOut = Carbon::parse($attendance->clock_out);

                    $totalBreakMinutes = 0;

                    $breaks = BreakTime::where('attendance_id', $attendance->id)->get();


                    foreach ($breaks as $break) {

                        if ($break->break_start && $break->break_end) {

                            $breakStart = Carbon::parse($break->break_start);
                            $breakEnd = Carbon::parse($break->break_end);

                            $totalBreakMinutes += $breakStart->diffInMinutes($breakEnd);
                        }
                    }

                    $workMinutes = $clockIn->diffInMinutes($clockOut);

                    $workMinutes -= $totalBreakMinutes;

                    $monthlyWorkMinutes += $workMinutes;

                    if ($workMinutes > 480) {
                        $monthlyOvertimeMinutes += $workMinutes - 480;
                    }
                }
            }

            $monthlyTrend[] = [
                'month' => $month->format('Y-m'),
                'work_minutes' => $monthlyWorkMinutes,
                'overtime_minutes' => $monthlyOvertimeMinutes,
            ];
        }

        return view('reports.index', compact(
            'summary',
            'monthlyTrend'
        ));
    }
}
