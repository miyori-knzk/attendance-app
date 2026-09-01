<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceUpdateRequest;
use App\Models\Application;
use App\Models\AttendanceRecord;
use App\Models\BreakRecord;
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

        $attendance = AttendanceRecord::firstOrNew([
            'user_id' => $user->id,
            'date' => $today,
        ]);

        $latestAtt = AttendanceRecord::where('user_id', $user->id)->orderBy('date', 'desc')->first();

        if ($latestAtt) {
            $startDay = CarbonImmutable::parse($latestAtt->date)->addDay();
        } else {
            $startDay = $user->created_at;
        }

        DB::connection()->transaction(function () use ($startDay, $action, $attendance, $time, $today, $user) {
            if ($action == 'clock_in') {
                // 今日より前で、レコードがない日の勤怠テーブルの空レコード作成
                for ($date = $startDay; $date->lt($today); $date->addDay()) {
                    AttendanceRecord::firstOrCreate([
                        'user_id' => $user->id,
                        'date' => $date,
                    ]);
                }
                $attendance->$action = $time;
                $attendance->save();
            } else {
                if ($action == 'clock_out') {
                    $attendance->update([$action => $time]);
                } else {
                    if ($action == 'break_in') {
                        $attendance->breakRecords()->create([$action => $time]);
                    } else {
                        $attendance->breakRecords()->orderBy('break_in', 'desc')->first()->update([$action => $time]);
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

        $attendances = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('date', '>=', $firstOfMonth)
            ->whereDate('date', '<=', $endOfMonth)
            ->with('breakRecords')
            ->orderBy('date')
            ->get();

        foreach ($attendances as $attendance) {
            $attDay = CarbonImmutable::parse($attendance->date);
            $breakSum = 0;
            $workSum = 0;

            $tmpDayOfWeek = $attDay->dayOfWeek;

            if ($attendance) {
                $tmpClockIn = $attendance->clock_in ?
                                 CarbonImmutable::parse($attendance->clock_in) : null;
                $tmpClockOut = $attendance->clock_out ?
                                    CarbonImmutable::parse($attendance->clock_out) : null;

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

    public function edit(AttendanceRecord $attendanceRecord)
    {
        $data = [];

        $data['id'] = $attendanceRecord->id;
        $data['application'] = $attendanceRecord->applicationIsPending();
        $user = auth()->user();
        $spritDate = explode('-', $attendanceRecord->date);
        $data['year'] = $spritDate[0] . '年';
        $data['date'] = $spritDate[1] . '月' . $spritDate[2] . '日';
        $data['clock_in'] = $attendanceRecord->clock_in;
        $data['clock_out'] = $attendanceRecord->clock_out;
        $data['breaks'] = $attendanceRecord->breakRecords()->get();
        $data['comment'] = $attendanceRecord->application->comment;

        return view('user.user-detail', compact('data', 'user'));
    }

    public function update(AttendanceUpdateRequest $request, AttendanceRecord $attendanceRecord)
    {
        $breakArr = [];
        $validated = $request->validated();

        $formattedKeys = str_replace('new_', '', array_keys($validated));
        $vals = array_values($validated);
        foreach ($vals as $val) {
            $formattedVals[] = hiToTime($val);
        }
        $formattedArr = array_combine($formattedKeys, $vals);

        $breakRecords = BreakRecord::where('attendance_record_id', $attendanceRecord->id)->orderBy('break_in', 'asc')->get();
        foreach ($formattedArr['break_in'] as $key => $inVal) {
            if ($inVal != null) {
                $breakArr[] = [
                    'break_in' => HiToTime($inVal),
                    'break_out' => HiToTime($formattedArr['break_out'][$key]),
                ];
            }
        }

        $application = Application::firstOrNew(['attendance_record_id' => $attendanceRecord->id]);
        $application->comment = $formattedArr['comment'];

        DB::connection()->transaction(function () use ($formattedArr, $breakArr, $attendanceRecord, $breakRecords, $application) {
            $attendanceRecord->clock_in = $formattedArr['clock_in'];
            $attendanceRecord->clock_out = $formattedArr['clock_out'];
            $attendanceRecord->save();

            $brrCnt = count($breakArr);

            if ($breakRecords->count() <= $brrCnt) {
                $cnt = 0;
                foreach ($breakRecords as $breakRecord) {
                    $breakRecords->break_in = $breakArr[$cnt]['break_in'];
                    $breakRecords->break_out = $breakArr[$cnt]['break_in'];
                    $breakRecord->save();
                }
            }

            if ($brrCnt != 0) {
                BreakRecord::create([
                    'attendance_record_id' => $attendanceRecord->id,
                    'break_in' => $breakArr[$brrCnt - 1]['break_in'],
                    'break_out' => $breakArr[$brrCnt - 1]['break_out'],
                ]);
            }

            $application->save();
        });

        return Redirect('/attendance/' . $attendanceRecord->id);
    }
}
