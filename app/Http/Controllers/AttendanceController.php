<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', Carbon::today())
            ->first();

        $user->attendance_status = $attendance
            ? $attendance->status
            : '勤務外';

        $formattedDate = Carbon::now()->isoFormat('YYYY年MM月DD日(ddd)');
        $formattedTime = Carbon::now()->format('H:i');

        return view('user.attendance-register', compact(
            'user',
            'formattedDate',
            'formattedTime'
        ));
    }

    public function list(Request $request)
    {
        // ログインしているユーザーを取得
        $user = auth()->user();

        // URLにdateがあればその月、なければ今月
        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))
            : Carbon::now();

        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        $attendanceRecords = Attendance::where('user_id', $user->id)
            ->whereYear('work_date', $date->year)
            ->whereMonth('work_date', $date->month)
            ->get();

        $formattedAttendanceRecords = [];

        foreach ($attendanceRecords as $attendance) {

            $breaks = BreakTime::where('attendance_id', $attendance->id)->get();

            $totalBreakMinutes = 0;

            foreach ($breaks as $break) {
                if ($break->break_start && $break->break_end) {
                    $breakStart = Carbon::parse($break->break_start);
                    $breakEnd = Carbon::parse($break->break_end);

                    $totalBreakMinutes += $breakStart->diffInMinutes($breakEnd);
                }
            }
            $totalWorkMinutes = null;

            if ($attendance->clock_out) {
                $clockIn = Carbon::parse($attendance->clock_in);
                $clockOut = Carbon::parse($attendance->clock_out);

                $totalWorkMinutes =
                    $clockIn->diffInMinutes($clockOut) - $totalBreakMinutes;
            }

            $formattedAttendanceRecords[] = [
                'id' => $attendance->id,
                'date' => Carbon::parse($attendance->work_date)->format('m/d'),
                'clock_in' => $attendance->clock_in
                    ? Carbon::parse($attendance->clock_in)->format('H:i')
                    : '',
                'clock_out' => $attendance->clock_out
                    ? Carbon::parse($attendance->clock_out)->format('H:i')
                    : '',
                'total_break_time' => $totalBreakMinutes > 0
                    ? sprintf('%02d:%02d', intdiv($totalBreakMinutes, 60), $totalBreakMinutes % 60)
                    : null,
                'total_time' => $totalWorkMinutes !== null
                    ? sprintf('%02d:%02d', intdiv($totalWorkMinutes, 60), $totalWorkMinutes % 60)
                    : null,
            ];
        }

        return view('user.user-attendance-list', compact(
            'user',
            'date',
            'previousMonth',
            'nextMonth',
            'formattedAttendanceRecords'
        ));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', Carbon::today())
            ->first();

        if ($request->action === 'clock_in') {

            Attendance::create([
                'user_id' => $user->id,
                'status' => '出勤中',
                'work_date' => Carbon::today(),
                'clock_in' => Carbon::now()->format('H:i:s'),
            ]);
        } elseif ($request->action === 'break_in') {

            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start' => Carbon::now()->format('H:i:s'),
            ]);

            $attendance->update([
                'status' => '休憩中',
            ]);
        } elseif ($request->action === 'break_out') {

            $break = BreakTime::where('attendance_id', $attendance->id)
                ->whereNull('break_end')
                ->latest()
                ->first();

            $break->update([
                'break_end' => Carbon::now()->format('H:i:s'),
            ]);

            $attendance->update([
                'status' => '出勤中',
            ]);
        } elseif ($request->action === 'clock_out') {

            $attendance->update([
                'status' => '退勤済',
                'clock_out' => Carbon::now()->format('H:i:s'),
            ]);
        }

        return redirect('/attendance');
    }
}
