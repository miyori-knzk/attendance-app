<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
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
        $time = now()->format('H:i');
        $today = Carbon::today()->format('Y-m-d');
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
            'date' => $today,
        ]);

        $latestAtt = Attendance::where('user_id', $user->id)->orderBy('date', 'desc')->first();

        if ($latestAtt) {
            $latestAttDay = Carbon::parse($latestAtt->date);
            $startDay = $latestAttDay->addDay()->copy();
        } else {
            $startDay = $user->created_at;
        }

        DB::connection()->transaction(function () use ($startDay, $action, $attendance, $time, $today, $user) {
            if ($action == 'clock_in') {
                // 今日より前で、レコードがない日の勤怠テーブルの空レコード作成
                for ($date = $startDay; $date->lt($today); $date->addDay()) {
                    Attendance::firstOrCreate([
                        'user_id' => $user->id,
                        'date' => $date,
                    ]);
                }
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

    public function index(Request $request)
    {
        $formattedAttendanceRecords = [];
        $dayOfWeekArr = ['日', '月', '火', '水', '木', '金', '土'];
        $breakSum = 0;
        $workSum = 0;

        $user = auth()->user();
        $date = now();

        if ($request->filled('date')) {
            $date = Carbon::parse($request->date);
        }

        $previousMonth = $date->copy()->firstOfMonth()->subMonth();
        $nextMonth = $date->copy()->firstOfMonth()->addMonth();

        $firstOfMonth = $date->copy()->firstOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $user->id)
            ->whereDate('date', '>=', $firstOfMonth)
            ->whereDate('date', '<=', $endOfMonth)
            ->with(['clockRecord', 'breakRecords'])
            ->orderBy('date')
            ->get();

        foreach ($attendances as $attendance) {
            $attDay = Carbon::parse($attendance->date);
            $breakSum = 0;
            $workSum = 0;

            $tmpDayOfWeek = $attDay->dayOfWeek;

            if ($attendance->clockRecord) {
                $tmpClockIn = $attendance->clockRecord->clock_in ?
                                 Carbon::parse($attendance->clockRecord->clock_in) : null;
                $tmpClockOut = $attendance->clockRecord->clock_out ?
                                    Carbon::parse($attendance->clockRecord->clock_out) : null;

                if ($tmpClockIn && $tmpClockOut) {
                    $workSum = $tmpClockIn->diffInMinutes($tmpClockOut);
                } else {
                    $workSum = 0;
                }

                $breaks = $attendance->breakRecords()->get();

                foreach ($breaks as $break) {
                    $tmpBreakIn = $break->break_in ? Carbon::parse($break->break_in) : null;
                    $tmpBreakOut = $break->break_out ? Carbon::parse($break->break_out) : null;
                    if ($tmpBreakIn && $tmpBreakOut) {
                        $difMin = $tmpBreakIn->diffInMinutes($tmpBreakOut);
                    } else {
                        $difMin = 0;
                    }
                    $breakSum = $breakSum + $difMin;
                }

                $tmpClockInFormat = $tmpClockIn ? $tmpClockIn->format('H:i') : null;
                $tmpClockOutFormat = $tmpClockOut ? $tmpClockOut->format('H:i') : null;
                $tmpTotalBreakTime = $breakSum * 60;
                if ($workSum - $breakSum < 0) {
                    $tmpTotalTime = null;
                } else {
                    $tmpTotalTime = ($workSum - $breakSum) * 60;
                }
            } else {
                $tmpClockInFormat = null;
                $tmpClockOutFormat = null;
                $tmpTotalBreakTime = null;
                $tmpTotalTime = null;
            }

            $formattedAttendanceRecords[] = [
                'date' => $attDay->format('m/d') . '(' . $dayOfWeekArr[$tmpDayOfWeek] . ')',
                'clock_in' => $tmpClockInFormat,
                'clock_out' => $tmpClockOutFormat,
                'total_break_time' => $tmpTotalBreakTime,
                'total_time' => $tmpTotalTime,
                'id' => $attendance->id,
            ];
        }

        return view('user.user-attendance-list', compact('previousMonth', 'date', 'nextMonth', 'formattedAttendanceRecords'));
    }
}
