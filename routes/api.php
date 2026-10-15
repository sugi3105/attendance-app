<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AttendanceRecordController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get(
    '/v1/attendance-records',
    [AttendanceRecordController::class, 'index']
);

Route::get(
    '/v1/attendance-records/{attendanceRecord}',
    [AttendanceRecordController::class, 'show']
);

Route::middleware('auth:sanctum')->post(
    '/v1/attendance-records',
    [AttendanceRecordController::class, 'store']
);

Route::middleware('auth:sanctum')->put(
    '/v1/attendance-records/{attendanceRecord}',
    [AttendanceRecordController::class, 'update']
);

Route::middleware('auth:sanctum')->patch(
    '/v1/attendance-records/{attendanceRecord}',
    [AttendanceRecordController::class, 'update']
);

Route::middleware('auth:sanctum')->delete(
    '/v1/attendance-records/{attendanceRecord}',
    [AttendanceRecordController::class, 'destroy']
);
