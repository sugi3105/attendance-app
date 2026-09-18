<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Auth;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

//Route::get('/', function () {
//return view('welcome');
Route::get('admin/login', [AdminController::class, 'showLogin']);
Route::post('admin/login', [AdminController::class, 'login']);
Route::post('/admin/logout', function () {
    Auth::logout();
    return redirect('/admin/login');
});

Route::post('/logout', function () {
    Auth::logout();

    return redirect('/login');
});
//Route::get('/admin/attendance', [AdminController::class, 'attendance']);
Route::get('/admin/attendance/list', [AdminController::class, 'attendanceList']);
Route::get('/admin/stamp_correction_request/list', [AdminController::class, 'applicationList']);
Route::get('/admin/attendance/{id}', [AdminController::class, 'detail']);
Route::post('/admin/attendance/{id}', [AdminController::class, 'update']);

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::get('/attendance/list', [AttendanceController::class, 'list']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::get('/attendance/{id}', [AttendanceController::class, 'detail']);
    Route::post('/attendance/{id}', [AttendanceController::class, 'update']);
    Route::get('stamp_correction_request/list', [AttendanceController::class, 'application']);
    //Route::post('/application', [AttendanceController::class, 'application']);
    Route::get('/application/{id}', [AttendanceController::class, 'applicationDetail']);
});
