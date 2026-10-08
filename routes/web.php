<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\StampCorrectionRequestController;
use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    if (! Auth::check()) {
        return Redirect('/login');
    }

    $user = Auth::user();
    if ($user->admin_status == 1) {
        return Redirect('/admin/attendance/list');
    }

    return Redirect('/attendance');
});

Route::middleware(['guest'])->group(function () {
    Route::get('/admin/login', function () {
        return view('admin.admin-login');
    });
});

Route::middleware('auth')->group(function () {
    Route::prefix('attendance')->middleware('verified', 'user')->controller(AttendanceController::class)->group(function () {
        Route::get('', 'create');
        Route::post('', 'store');
        Route::get('list', 'index');
        Route::get('detail/{id}', 'edit');
        Route::post('detail/{id}', 'requestStore');
        Route::get('report', 'report');
    });
    Route::prefix('stamp_correction_request')->controller(StampCorrectionRequestController::class)->group(function () {
        Route::get('list', 'index')->middleware('set.app.index.view');
        Route::get('approve/{attendance_correct_request_id}', 'show')->middleware('admin');
        Route::post('approve/{attendance_correct_request_id}', 'update')->middleware('admin');
    });
    Route::prefix('admin')->middleware('admin')->controller(AdminController::class)->group(function () {
        Route::get('attendance/list', 'index');
        Route::get('attendance/{id}', 'edit');
        Route::post('attendance/{id}', 'update');
        Route::get('attendance/staff/{id}', 'staffAttendance');
        Route::get('staff/list', 'staffIndex');
    });
    Route::post('export', [ExportController::class, 'export'])->middleware('admin');

});
