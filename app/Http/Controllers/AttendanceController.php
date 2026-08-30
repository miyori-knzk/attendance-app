<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\CarbonImmutable;
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
        $time = date('H:i');
        $today = date('Y-m-d');
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
            $startDay = $latestAtt->date->addDay();
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
                        $attendance->breakRecords()->orderBy('clock_in', 'desc')->first()->update([$action => $time]);
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
        $date = CarbonImmutable::parse(date('Y-m-d'));

        if ($request->filled('date')) {
            $date = CarbonImmutable::parse($request->date);
        }

        $previousMonth = $date->firstOfMonth()->subMonth();
        $nextMonth = $date->firstOfMonth()->addMonth();

        $firstOfMonth = $date->firstOfMonth();
        $endOfMonth = $date->endOfMonth();

        $attendances = Attendance::where('user_id', $user->id)
            ->whereDate('date', '>=', $firstOfMonth)
            ->whereDate('date', '<=', $endOfMonth)
            ->with(['clockRecord', 'breakRecords'])
            ->orderBy('date')
            ->get();

        foreach ($attendances as $attendance) {
            $attDay = CarbonImmutable::parse($attendance->date);
            $breakSum = 0;
            $workSum = 0;

            $tmpDayOfWeek = $attDay->dayOfWeek;

            if ($attendance->clockRecord) {
                $tmpClockIn = $attendance->clockRecord->clock_in ?
                                 CarbonImmutable::parse($attendance->clockRecord->clock_in) : null;
                $tmpClockOut = $attendance->clockRecord->clock_out ?
                                    CarbonImmutable::parse($attendance->clockRecord->clock_out) : null;

                if ($tmpClockIn && $tmpClockOut) {
                    $workSum = $tmpClockIn->diffInMinutes($tmpClockOut);
                } else {
                    $workSum = 0;
                }

                $breaks = $attendance->breakRecords()->get();

                foreach ($breaks as $break) {
                    $tmpBreakIn = $break->break_in ? CarbonImmutable::parse($break->break_in) : null;
                    $tmpBreakOut = $break->break_out ? CarbonImmutable::parse($break->break_out) : null;
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

    public function edit(Attendance $attendance)
    {
        $data = [];

        $data['id'] = $attendance->id;
        $data['application'] = $attendance->applicationStatus;
        $user = auth()->user();
        $spritDate = explode('-', $attendance->date);
        $data['year'] = $spritDate[0] . '年';
        $data['date'] = $spritDate[1] . '月' . $spritDate[2] . '日';
        $data['clock_in'] = $attendance->clockRecord->clock_in;
        $data['clock_out'] = $attendance->clockRecord->clock_out;
        $data['breaks'] = $attendance->breakRecords()->get();
        $data['comment'] = $attendance->applicationRecord->comment;

        return view('user.user-detail', compact('data', 'user'));
    }
}
