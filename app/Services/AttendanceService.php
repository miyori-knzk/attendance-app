<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function formatAttDatas($date, $user = null)
    {
        $formattedAttendanceRecords = [];

        if ($user == null) {
            $user = auth()->user();
        }

        $startDay = getFirstOfMonth($date);
        $endDay = getEndOfMonth($date);

        $attendances = AttendanceRecord::getMonthUserData($startDay, $endDay);

        if ($attendances->count() <= 0) {
            return $formattedAttendanceRecords;
        }

        foreach ($attendances as $attendance) {
            $attDay = dateFormat($attendance->date);

            $tmpDayOfWeek = $attDay->dayOfWeek;

            $tmpClocks = $this->calculateWorkTime($attendance);
            $breakSum = $this->calculateBreakTime($attendance);
            $totalTime = $this->calculateTotalTime($breakSum, $tmpClocks['workSum']);

            $formattedAttendanceRecords[] = [
                'date' => $attDay->format('m/d') . '(' . jpWeekday($tmpDayOfWeek) . ')',
                'clock_in' => dateTimeToHi($tmpClocks['tmpClockIn']),
                'clock_out' => dateTimeToHi($tmpClocks['tmpClockOut']),
                'total_break_time' => $breakSum,
                'total_time' => $totalTime,
                'id' => $attendance->id,
            ];
        }

        return $formattedAttendanceRecords;
    }

    public function calculateWorkTime($attendance)
    {
        $data = [];

        $clockRecord = $attendance->clockRecord;
        $tmpClockIn = timeFormat($clockRecord->clock_in);
        $tmpClockOut = timeFormat($clockRecord->clock_out);

        if ($tmpClockIn && $tmpClockOut) {
            $workSum = $tmpClockIn->diffInMinutes($tmpClockOut);
        } else {
            $workSum = 0;
        }

        $data['tmpClockIn'] = $tmpClockIn;
        $data['tmpClockOut'] = $tmpClockOut;
        $data['workSum'] = $workSum;

        return $data;
    }

    public function calculateBreakTime($attendance)
    {
        $breakSum = 0;
        $breakRecords = $attendance->breakRecords()->get();

        foreach ($breakRecords as $break) {
            $tmpBreakIn = timeFormat($break->break_in);
            $tmpBreakOut = timeFormat($break->break_out);

            if ($tmpBreakIn && $tmpBreakOut) {
                $difMin = $tmpBreakIn->diffInMinutes($tmpBreakOut);
            } else {
                $difMin = 0;
            }
            $breakSum = $breakSum + $difMin;
        }

        return $breakSum;
    }

    public function calculateTotalTime($breakSum, $workSum)
    {
        if ($workSum < $breakSum) {
            return $tmpTotalTime = null;
        }

        return $tmpTotalTime = $workSum - $breakSum;
    }

    public function makeEditData($id)
    {
        $attendanceRecord = AttendanceRecord::findOrFail($id);

        $data = jpDateformat($attendanceRecord->date);
        $data['application'] = $attendanceRecord->requestIsPending();

        if ($data['application']) {
            $tmpDatas = $this->makePendingData($attendanceRecord);

        } else {
            $tmpDatas = $this->makeAttendanceData($attendanceRecord);
        }

        foreach ($tmpDatas as $key => $val) {
            $data[$key] = $val;
        }

        $data['id'] = $attendanceRecord->id;

        return $data;
    }

    public function makePendingData($attendanceRecord)
    {
        $data = [];
        $breaks = [];

        $pendingRecord = $attendanceRecord->requestIsPending();
        $clock_in = $pendingRecord->new_clock_in;
        $clock_out = $pendingRecord->new_clock_in;
        foreach ($pendingRecord->breakCorrectRequests()->get() as $break) {
            $breaks[] = [
                'break_in' => $break->new_break_in,
                'break_out' => $break->new_break_out,
            ];
        }

        $data['clock_in'] = $clock_in;
        $data['clock_out'] = $clock_out;
        $data['breaks'] = $breaks;
        $data['comment'] = $pendingRecord->comment;

        return $data;
    }

    public function makeAttendanceData($attendanceRecord)
    {
        $data = [];

        $clockRecord = $attendanceRecord->clockRecord;
        $data['clock_in'] = $clockRecord->clock_in;
        $data['clock_out'] = $clockRecord->clock_out;
        $data['breaks'] = $attendanceRecord->breakRecords()->get();
        $data['comment'] = $attendanceRecord->comment;

        return $data;
    }

    public function saveRequestRecord($request, $attendanceRecord)
    {
        $validated = $request->validated();
        $breakIn = $validated['new_break_in'];
        $breakOut = $validated['new_break_out'];

        $breakArr = $this->makeNewBreakArr($breakIn, $breakOut);

        DB::connection()->transaction(function () use ($validated, $breakArr, $attendanceRecord) {

            $correctRequest = $attendanceRecord->attendanceCorrectRequests()->create($validated);
            $correctRequest->clockCorrectRequest()->create($validated);

            if (count($breakArr) > 0) {
                foreach ($breakArr as $break) {
                    $correctRequest->breakCorrectRequests()->create($break);
                }
            }

        });
    }

    public function makeNewBreakArr($breakIn, $breakOut)
    {
        $breakArr = [];

        foreach ($breakIn as $key => $inVal) {
            if ($inVal != null) {
                $breakArr[] = [
                    'new_break_in' => $inVal,
                    'new_break_out' => $breakOut[$key],
                ];
            }
        }

        return $breakArr;
    }

    /**
     * 処理の概要
     *   1. ユーザーの最新勤怠を取得し、その翌日を開始日とする。最新の勤怠が無ければユーザー作成日を開始日とする。
     *   2. 出勤時に開始日から前日までにレコードがない日があればAttendanceRecordのみ作成。
     *   3. 今日のレコードは $action に応じて clockrecord か breakrecords へ時間を保存。
     */
    public function storeAttendanceRecord($action)
    {
        $user = auth()->user();
        $today = date('Y-m-d');

        $attendance = AttendanceRecord::firstOrNew([
            'user_id' => $user->id,
            'date' => $today,
        ]);

        $startDay = $this->getStartDay($user);

        DB::connection()->transaction(function () use ($startDay, $action, $attendance, $today, $user) {
            $time = date('H:i');
            if ($action == 'clock_in') {
                // 最後の出勤もしくはユーザー作成日から昨日までのAttendanceRecord作成
                $this->createOnlyAttendanceRecode($startDay, $today, $user);
                // AttendanceRecord作成
                $attendance->save();
                $attendance->clockRecord()->create([$action => $time]);
            } else {
                if ($action == 'clock_out') {
                    $attendance->clockRecord()->update([$action => $time]);
                } else {
                    if ($action == 'break_in') {
                        $attendance->breakRecords()->create([$action => $time]);
                    } else {
                        $attendance->onBreakData()->update([$action => $time]);
                    }
                }
            }
        });
    }

    public function getStartDay($user)
    {
        $latestAtt = AttendanceRecord::getLatestAttendance($user);

        if ($latestAtt) {
            $startDay = dateFormat($latestAtt->date)->addDay();
        } else {
            $startDay = $user->created_at;
        }

        return $startDay;
    }

    public function createOnlyAttendanceRecode($startDay, $today, $user)
    {
        for ($date = $startDay; $date->lt($today); $date->addDay()) {
            AttendanceRecord::firstOrCreate([
                'user_id' => $user->id,
                'date' => $date,
            ]);
        }
    }
}
