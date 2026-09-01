<?php

use App\Http\Controllers\AttendanceController;
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

Route::middleware('auth')->group(function () {
    Route::prefix('attendance')->controller(AttendanceController::class)->group(function () {
        Route::get('/', 'create');
        Route::post('/', 'store');
        Route::get('/list', 'index');
        Route::get('/{attendance}', 'edit');
        Route::post('/{attendance}', 'update');
    });
});
