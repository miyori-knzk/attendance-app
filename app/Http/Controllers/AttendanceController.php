<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceStoreRequest;
use App\Http\Requests\AttendanceUpdateRequest;
use App\Models\AttendanceRecord;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
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

    public function store(AttendanceStoreRequest $request)
    {
        $action = null;
        $actionTbl = 'break';
        $attendance = null;
        $user = auth()->user();
        $today = date('Y-m-d');

        if ($request->filled('action')) {
            $action = $request->action;
        }

        $attendance = AttendanceRecord::firstOrNew([
            'user_id' => $user->id,
            'date' => $today,
        ]);

        $latestAtt = AttendanceRecord::getLatestAttendance($user);

        if ($latestAtt) {
            $startDay = CarbonImmutable::parse($latestAtt->date)->addDay();
        } else {
            $startDay = $user->created_at;
        }

        DB::connection()->transaction(function () use ($startDay, $action, $attendance, $today, $user) {
            $time = date('H:i');
            if ($action == 'clock_in') {
                // 今日より前で、レコードがない日の勤怠テーブルの空レコード作成
                for ($date = $startDay; $date->lt($today); $date->addDay()) {
                    AttendanceRecord::firstOrCreate([
                        'user_id' => $user->id,
                        'date' => $date,
                    ]);
                }
                // 今日の分のレコード作成
                $attendance->save();
                $attendance->clockRecord()->create([$action => $time]);
            } else {
                if ($action == 'clock_out') {
                    $attendance->clockRecord()->update([$action => $time]);
                } else {
                    if ($action == 'break_in') {
                        $attendance->breakRecords()->create([$action => $time]);
                    } else {
                        $attendance->breakRecords()->whereNull('break_out')
                            ->orderBy('break_in', 'desc')
                            ->first()
                            ->update([$action => $time]);
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
            ->with('clockRecord', 'breakRecords')
            ->orderBy('date')
            ->get();

        foreach ($attendances as $attendance) {
            $attDay = CarbonImmutable::parse($attendance->date);
            $breakSum = 0;
            $workSum = 0;

            $tmpDayOfWeek = $attDay->dayOfWeek;

            $clockRecord = $attendance->clockRecord;
            $tmpClockIn = $clockRecord->clock_in ?
                                CarbonImmutable::parse($clockRecord->clock_in) : null;
            $tmpClockOut = $clockRecord->clock_in ?
                                CarbonImmutable::parse($clockRecord->clock_in) : null;

            if ($tmpClockIn && $tmpClockOut) {
                $workSum = $tmpClockIn->diffInMinutes($tmpClockOut);
            } else {
                $workSum = 0;
            }

            $breakRecords = $attendance->breakRecords()->get();

            foreach ($breakRecords as $break) {
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

    public function edit($id)
    {
        $data = [];
        $breaks = [];
        $attendanceRecord = AttendanceRecord::findOrFail($id);

        $data['id'] = $attendanceRecord->id;
        $data['application'] = $attendanceRecord->requestIsPending();
        $user = auth()->user();
        $spritDate = explode('-', $attendanceRecord->date);
        $data['year'] = $spritDate[0] . '年';
        $data['date'] = $spritDate[1] . '月' . $spritDate[2] . '日';

        if ($attendanceRecord->requestIsPending()) {
            $pendingRecord = $attendanceRecord->requestIsPending();
            $clock_in = timeFormat($pendingRecord->new_clock_in);
            $clock_out = timeFormat($pendingRecord->new_clock_in);
            foreach ($pendingRecord->breakCorrectRequests()->get() as $break) {
                $breaks[] = [
                    'break_in' => timeFormat($break->new_break_in),
                    'break_out' => timeFormat($break->new_break_out),
                ];
            }
        } else {
            $clockRecord = $attendanceRecord->clockRecord;
            $clock_in = timeFormat($clockRecord->clock_in);
            $clock_out = timeFormat($clockRecord->clock_out);
            $breaks = $attendanceRecord->breakRecords()->get();
        }

        $data['clock_in'] = $clock_in;
        $data['clock_out'] = $clock_out;
        $data['breaks'] = $breaks;
        $data['comment'] = $attendanceRecord->comment;

        return view('user.user-detail', compact('data', 'user'));
    }

    /**
     * 打刻修正申請を保存
     *
     * @param  AttendanceUpdateRequest  $request  バリデート済みリクエストオブジェクト
     * @param  int  $id  AttendanceRecord の ID
     * @return RedirectResponse
     *
     * @throws ModelNotFoundException // AttendanceRecord が見つからない場合
     */
    public function requestStore(AttendanceUpdateRequest $request, $id)
    {
        $breakArr = [];
        $bCrrRequestIds = [];
        $validated = $request->validated();

        foreach ($validated['new_break_in'] as $key => $inVal) {
            if ($inVal != null) {
                $breakArr[] = [
                    'new_break_in' => $inVal,
                    'new_break_out' => $validated['new_break_out'][$key],
                ];
            }
        }

        $attendanceRecord = AttendanceRecord::findOrFail($id);

        DB::connection()->transaction(function () use ($validated, $breakArr, $attendanceRecord) {

            $correctRequest = $attendanceRecord->attendanceCorrectRequest()->create($validated);
            $correctRequest->clockCorrectRequest()->create($validated);
            $attendanceRecord->update($validated);

            if (count($breakArr) > 0) {
                foreach ($breakArr as $break) {
                    $correctRequest->breakCorrectRequests()->create($break);
                }
            }

        });

        return Redirect('/attendance/detail/' . $attendanceRecord->id);
    }
}
