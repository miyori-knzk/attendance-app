<?php

use App\Http\Controllers\AttendanceController;
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
    return view('welcome');
});

Route::middleware(['auth', 'checkrole'])->group(function () {
    Route::prefix('attendance')->controller(AttendanceController::class)->group(function () {
        Route::get('', 'create');
        Route::post('', 'store');
        Route::get('list', 'index');
        Route::get('detail/{attendanceRecord}', 'edit');
        Route::post('detail/{attendanceRecord}', 'update');
    });
    Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'index']);
});
