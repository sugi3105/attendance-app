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

    public function list()
    {
        return view('user.user-attendance-list');
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
