<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V1\AttendanceRecordController;
use Illuminate\Support\Facades\Route;

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

Route::post('login', [AuthController::class, 'login'])->name('api.login');
Route::middleware('auth:sanctum')->group(function () {
    Route::post('v1/attendance-records', [AttendanceRecordController::class, 'store']);
    Route::post('logout', [AuthController::class, 'logout'])->name('api.logout');
});

Route::prefix('v1/attendance-records')->controller(AttendanceRecordController::class)->group(function () {
    Route::get('', 'index');
    Route::get('{attendanceRecord}', 'show');
});
