<?php

namespace App\Http\Controllers;

use App\Models\BreakTime;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\AttendanceRequest;
use App\Http\Requests\AdminAttendanceRequest;

class AdminController extends Controller
{
    public function showLogin()
    {
        return view('admin.admin-login');
    }

    public function login(AdminLoginRequest $request)
    {

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors([
                'email' => 'ログイン情報が登録されていません',
            ])->withInput();
        }

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'email' => 'ログイン情報が登録されていません',
            ])->withInput();
        }

        if (!$user->admin_status) {
            return back()->withErrors([
                'email' => 'ログイン情報が登録されていません',
            ])->withInput();
        }


        Auth::login($user);

        return redirect('/admin/attendance/list');
    }

    public function attendanceList(Request $request)
    {
        // 全ユーザーを取得
        $users = User::all();

        // 表示する日付を決める
        $date = $request->date
            ? Carbon::parse($request->date)
            : Carbon::today();

        // 前日・翌日
        $previousDay = $date->copy()->subDay()->format('Y-m-d');
        $nextDay = $date->copy()->addDay()->format('Y-m-d');

        // 指定された日の勤怠を全て取得
        $attendanceRecords = Attendance::whereDate(
            'work_date',
            $date
        )->get();

        // ユーザーを1人ずつ処理
        foreach ($users as $user) {

            // そのユーザーの指定日の勤怠を1件取得
            $attendance = $attendanceRecords
                ->where('user_id', $user->id)
                ->first();

            if ($attendance) {

                // 初期値
                $totalBreakMinutes = 0;
                $totalWorkMinutes = null;

                // その勤怠に紐づく休憩を取得
                $breaks = BreakTime::where(
                    'attendance_id',
                    $attendance->id
                )->get();

                // 休憩時間を計算
                foreach ($breaks as $break) {
                    if ($break->break_start && $break->break_end) {

                        $breakStart = Carbon::parse($break->break_start);
                        $breakEnd = Carbon::parse($break->break_end);

                        $totalBreakMinutes +=
                            $breakStart->diffInMinutes($breakEnd);
                    }
                }

                // 休憩時間をHH:MMにする
                $attendance->total_break_time = $totalBreakMinutes > 0
                    ? sprintf(
                        '%02d:%02d',
                        intdiv($totalBreakMinutes, 60),
                        $totalBreakMinutes % 60
                    )
                    : '';

                // 実働時間を計算
                if ($attendance->clock_out) {

                    $clockIn = Carbon::parse($attendance->clock_in);
                    $clockOut = Carbon::parse($attendance->clock_out);

                    $totalWorkMinutes =
                        $clockIn->diffInMinutes($clockOut)
                        - $totalBreakMinutes;

                    // 合計勤務時間をHH:MMにする
                    $attendance->total_time = sprintf(
                        '%02d:%02d',
                        intdiv($totalWorkMinutes, 60),
                        $totalWorkMinutes % 60
                    );
                } else {
                    $attendance->total_time = '';
                }
            }
        }

        return view(
            'admin.admin-attendance-list',
            compact(
                'users',
                'attendanceRecords',
                'date',
                'previousDay',
                'nextDay'
            )
        );
    }

    public function applicationList()
    {
        $applications = AttendanceRequest::all();

        return view(
            'admin.admin-application-list',
            compact('applications')
        );
    }

    public function detail($id)
    {
        $attendance = Attendance::find($id);

        $user = User::find($attendance->user_id);

        $breaks = BreakTime::where(
            'attendance_id',
            $attendance->id
        )->get();

        $attendanceRequest = AttendanceRequest::where(
            'attendance_id',
            $attendance->id
        )->where(
            'status',
            '承認待ち'
        )->first();

        $formattedBreaks = [];

        foreach ($breaks as $break) {
            $formattedBreaks[] = [
                'break_in' => $break->break_start,
                'break_out' => $break->break_end,
            ];
        }

        $date = Carbon::parse($attendance->work_date);

        $attendanceRecord = [
            'id' => $attendance->id,
            'year' => $date->format('Y'),
            'date' => $date->format('m/d'),
            'clock_in' => $attendance->clock_in,
            'clock_out' => $attendance->clock_out,
            'breaks' => $formattedBreaks,
            'comment' => $attendance->note,
        ];

        return view(
            'admin.admin-detail',
            compact('attendanceRecord', 'user', 'attendanceRequest')
        );
    }

    public function update(AdminAttendanceRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        $attendanceRequest = AttendanceRequest::where(
            'attendance_id',
            $attendance->id
        )->where(
            'status',
            '承認待ち'
        )->first();

        if ($attendanceRequest) {
            return back()->withErrors([
                'attendance' => '承認待ちのため修正できません。',
            ]);
        }

        $attendance->clock_in = $request->new_clock_in;
        $attendance->clock_out = $request->new_clock_out;
        $attendance->note = $request->comment;

        $breaks = BreakTime::where('attendance_id', $attendance->id)->get();

        foreach ($breaks as $index => $break) {
            $break->break_start = $request->new_break_in[$index];
            $break->break_end = $request->new_break_out[$index];
            $break->save();
        }

        if (
            !empty($request->new_break_in[1]) &&
            !empty($request->new_break_out[1])
        ) {
            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start' => $request->input('new_break_in.0'),
                'break_end' => $request->input('new_break_out.0'),
            ]);
        }

        $attendance->save();

        return redirect('/admin/attendance/' . $attendance->id);
    }
}
