<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\Attendance;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use Illuminate\Support\Facades\DB;

class AttendanceRecordController extends Controller
{
    /**
     *
     */
    public function index(IndexAttendanceRecordRequest $request)
    {
        $query = Attendance::query()
            ->with('user');

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
            'applications',
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
            'applications',
        ]);

        return (new AttendanceRecordResource($attendance))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateAttendanceRecordRequest $request,
        Attendance $attendanceRecord
    ) {
        $this->authorize('update', $attendanceRecord);

        $validated = $request->validated();

        $data = [];

        if (array_key_exists('user_id', $validated)) {
            $data['user_id'] = $validated['user_id'];
        }

        if (array_key_exists('date', $validated)) {
            $data['work_date'] = $validated['date'];
        }

        if (array_key_exists('clock_in', $validated)) {
            $data['clock_in'] = $validated['clock_in'];
        }

        if (array_key_exists('clock_out', $validated)) {
            $data['clock_out'] = $validated['clock_out'];
        }

        if (array_key_exists('comment', $validated)) {
            $data['note'] = $validated['comment'];
        }

        if (!empty($data)) {
            $attendanceRecord->fill($data);
            $attendanceRecord->save();
        }

        $attendanceRecord->load([
            'user',
            'breaks',
            'applications',
        ]);

        return new AttendanceRecordResource($attendanceRecord);
    }

    public function destroy(Attendance $attendanceRecord)
    {
        $this->authorize('delete', $attendanceRecord);

        $attendanceRecord->delete();

        return response()->noContent();
    }
}
