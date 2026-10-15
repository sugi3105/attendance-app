<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\Attendance;

class AttendanceRecordController extends Controller
{
    /**
     *
     */
    public function index(IndexAttendanceRecordRequest $request)
    {
        $query = Attendance::query()
            ->with(['user', 'breaks', 'attendanceRequests.breakRequests']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('date')) {
            $query->where('work_date', $request->input('date'));
        }

        if ($request->filled('month')) {
            [$year, $month] = explode('-', $request->input('month'));

            $query->whereYear('work_date', $year)
                ->whereMonth('work_date', $month);
        }

        $query->orderByDesc('work_date')
            ->orderByDesc('id');

        $perPage = (int) $request->input('per_page', 20);

        $attendances = $query->paginate($perPage);

        return response()->json([
            'data' => AttendanceRecordResource::collection(
                $attendances->getCollection()
            ),
            'meta' => [
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
            ],
        ]);
    }

    public function show(Attendance $attendanceRecord)
    {
        $attendanceRecord->load([
            'user',
            'breaks',
            'attendanceRequests.breakRequests',
        ]);

        return new AttendanceRecordResource($attendanceRecord);
    }

    public function store(StoreAttendanceRecordRequest $request)
    {
        $validated = $request->validated();

        $attendance = Attendance::create([
            'user_id' => $validated['user_id'],
            'status' => isset($validated['clock_out'])
                ? '退勤済'
                : '出勤中',
            'work_date' => $validated['date'],
            'clock_in' => $validated['clock_in'],
            'clock_out' => $validated['clock_out'] ?? null,
            'note' => $validated['comment'] ?? null,
        ]);

        $attendance->load([
            'user',
            'breaks',
            'attendanceRequests.breakRequests',
        ]);

        return (new AttendanceRecordResource($attendance))
            ->response()
            ->setStatusCode(201);
    }
}
