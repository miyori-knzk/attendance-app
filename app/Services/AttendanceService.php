<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\BreakRecord;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function formatAttDatas($date, $user = null)
    {
        $formattedAttendanceRecords = [];

        if ($user == null) {
            $user = auth()->user();
        }

        $attendances = AttendanceRecord::getMonthUserData($date, $user);

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

    public function attDatasCollection($date)
    {
        $attendanceRecords = [];
        $attendanceColl = collect();

        $attendances = AttendanceRecord::todayData($date)->get();

        if ($attendances->count() <= 0) {
            return $attendanceColl;
        }

        foreach ($attendances as $attendance) {
            $attDay = dateFormat($attendance->date);

            $tmpDayOfWeek = $attDay->dayOfWeek;

            $tmpClocks = $this->calculateWorkTime($attendance);
            $breakSum = $this->calculateBreakTime($attendance);
            $totalTime = $this->calculateTotalTime($breakSum, $tmpClocks['workSum']);

            $attendanceRecords[] = [
                'user_id' => $attendance->user_id,
                'date' => $attDay->format('m/d') . '(' . jpWeekday($tmpDayOfWeek) . ')',
                'clock_in' => dateTimeToHi($tmpClocks['tmpClockIn']),
                'clock_out' => dateTimeToHi($tmpClocks['tmpClockOut']),
                'total_break_time' => $breakSum,
                'total_time' => $totalTime,
                'id' => $attendance->id,
            ];
        }

        $attendanceColl = collect($attendanceRecords)->map(fn ($item) => (object) $item);

        return $attendanceColl;
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

        $tmpDatas = $this->makeAttendanceData($attendanceRecord);

        foreach ($tmpDatas as $key => $val) {
            $data[$key] = $val;
        }

        $data['id'] = $attendanceRecord->id;
        $data['user_id'] = $attendanceRecord->user_id;

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
        $data['breaks'] = $attendanceRecord->breakRecords()->get()->toArray();
        $data['comment'] = $attendanceRecord->comment;

        return $data;
    }

    public function saveRequestRecord($request, $attendanceRecord)
    {
        $clockArr = [];
        $validated = $request->validated();

        $clockArr['new_clock_in'] = hisToHi($validated['new_clock_in']);
        $clockArr['new_clock_out'] = hisToHi($validated['new_clock_out']);

        $breakIn = $validated['new_break_in'];
        $breakOut = $validated['new_break_out'];

        $breakArr = $this->makeBreakArr($breakIn, $breakOut, 'new_');

        DB::connection()->transaction(function () use ($validated, $breakArr, $attendanceRecord, $clockArr) {

            $correctRequest = $attendanceRecord->attendanceCorrectRequests()->create($validated);
            $correctRequest->clockCorrectRequest()->create($clockArr);

            if (count($breakArr) > 0) {
                foreach ($breakArr as $break) {
                    $tmp = $correctRequest->breakCorrectRequests()->create($break);
                }
            }

        });
    }

    public function makeBreakArr($breakIn, $breakOut, $type = null)
    {
        $breakArr = [];

        foreach ($breakIn as $key => $inVal) {
            if ($inVal != null) {
                $breakArr[] = [
                    $type . 'break_in' => $inVal,
                    $type . 'break_out' => $breakOut[$key],
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
        for ($date = $startDay; $date->lt($today); $date = $date->addDay()) {
            AttendanceRecord::firstOrCreate([
                'user_id' => $user->id,
                'date' => $date,
            ]);
        }
    }

    public function updateAttendance($validated, $id)
    {
        $data = $this->newDataFormat($validated);
        $breakIn = $data['break_in'];
        $breakOut = $data['break_out'];

        $breakArr = $this->makeBreakArr($breakIn, $breakOut);

        $attendanceRecord = AttendanceRecord::findOrFail($id);
        $attendanceRecord->comment = $data['comment'];

        DB::connection()->transaction(function () use ($attendanceRecord, $data, $breakArr) {
            $this->saveBreakRecords($attendanceRecord, $breakArr);
            $attendanceRecord->clockRecord->update($data);
            $attendanceRecord->save();
        });
    }

    public function newDataFormat($validated)
    {
        $formattedData = [];

        foreach ($validated as $key => $val) {
            if (str_starts_with($key, 'new_')) {
                $tmpKey = str_replace('new_', '', $key);
                $formattedData[$tmpKey] = $val;
            } else {
                $formattedData[$key] = $val;
            }
        }

        return $formattedData;
    }

    public function saveBreakRecords($attendanceRecord, $breakArr)
    {
        $breakRecords = $attendanceRecord->breakRecords()->get();

        $max = max($breakRecords->count(), count($breakArr));

        for ($i = 0; $i < $max; $i++) {

            $existData = $breakRecords[$i] ?? null;
            $fixData = $breakArr[$i] ?? null;
            $objFixData = (object) $fixData;

            if ($existData && $objFixData) {
                $existData->update([
                    'break_in' => $objFixData->break_in,
                    'break_out' => $objFixData->break_out,
                ]);

                continue;
            }

            if (! $existData && $objFixData) {
                BreakRecord::create([
                    'attendance_record_id' => $attendanceRecord->id,
                    'break_in' => $objFixData->break_in,
                    'break_out' => $objFixData->break_out,
                ]);

                continue;
            }

            if ($existData && ! $objFixData) {
                $existData->delete();

                continue;
            }
        }
    }
}
