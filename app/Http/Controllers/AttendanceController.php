<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    //     Route::get('/', 'create');
    // Route::post('/', 'store');
    // Route::get('/list', 'index');
    // Route::get('/detail/{attendance}', 'edit');
    // Route::put('/detail/{attendance}', 'update');

    public function create()
    {
        $user = auth()->user();
        $formattedDate = date('Y年n月j日', strtotime('today'));
        $formattedTime = null;

        return view('user.attendance-register', compact('user', 'formattedDate', 'formattedTime'));
    }

    public function store(Request $request)
    {
        $action = null;
        $actionTbl = 'break';
        $attendance = null;
        $time = now();
        $chaildTable = null;
        $user = auth()->user();

        if ($request->filled('action')) {
            $action = $request->action;
        }

        if (substr($action, 0, 5) == 'clock') {
            $actionTbl = 'clock';
        }

        $attendance = Attendance::firstOrNew([
            'user_id' => $user->id,
            'date' => date('Y-m-d'),
        ]);

        DB::connection()->transaction(function () use ($action, $attendance, $time) {
            if ($action == 'clock_in') {
                $attendance->save();
                $attendance->clockRecord()->create([$action => $time]);
            } else {
                if ($action == 'clock_out') {
                    $attendance->clockRecord()->update([$action => $time]);
                } else {
                    if ($action == 'break_in') {
                        $attendance->breakRecords()->create([$action => $time]);
                    } else {
                        $attendance->breakRecords()->latest()->first()->update([$action => $time]);
                    }
                }
            }
        });

        return Redirect('/attendance');
    }
}
