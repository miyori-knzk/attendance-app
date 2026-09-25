<?php

namespace App\Services;

use App\Http\Requests\AttendanceUpdateRequest;
use App\Models\AttendanceRecord;
use App\Models\BreakRecord;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * 勤怠一覧画面に表示する際のデータをフォーマット
     *
     * @param  CarbonImmutable  $date  指定された日付が属する月を表示　'Y-m-d'形式
     * @param  User|null  $user  指定なしの場合はログインユーザーを対象にする
     */
    public function formatAttDatas(CarbonImmutable $date, ?User $user = null): array
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
                'total_break_time' => $breakSum * 60,
                'total_time' => $totalTime * 60,
                'id' => $attendance->id,
            ];
        }

        return $formattedAttendanceRecords;
    }

    /**
     * 管理者の勤怠一覧画面で表示する際のデータをCollection形式でフォーマット
     *
     * @param  string  $date  指定された日付が属する月を表示　'Y-m-d'形式
     * @return \Illuminate\Support\Collection;
     */
    public function attDatasCollection(string $date): Collection
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
                'total_break_time' => $breakSum * 60,
                'total_time' => $totalTime * 60,
                'id' => $attendance->id,
            ];

        }

        $attendanceColl = collect($attendanceRecords)->map(fn ($item) => (object) $item);

        return $attendanceColl;
    }

    /**
     * 出退勤時間をフォーマットし、勤務時間を計算
     */
    public function calculateWorkTime(AttendanceRecord $attendance): array
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

    /**
     * 休憩時間の合計を計算
     */
    public function calculateBreakTime(AttendanceRecord $attendance): int
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

    /**
     * 休憩時間を引いた勤務時間を計算
     */
    public function calculateTotalTime(int $breakSum, int $workSum): ?int
    {
        if ($workSum < $breakSum) {
            return $tmpTotalTime = null;
        }

        return $tmpTotalTime = $workSum - $breakSum;
    }

    /**
     * 勤怠詳細画面に表示するデータをフォーマット
     *
     * @param  int  $id  AttendanceRecordのID
     */
    public function makeEditData(int $id): array
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

    /**
     * 勤怠詳細画面の勤怠情報を配列にフォーマット
     */
    public function makeAttendanceData(AttendanceRecord $attendanceRecord): array
    {
        $data = [];

        $clockRecord = $attendanceRecord->clockRecord;
        $data['clock_in'] = $clockRecord->clock_in;
        $data['clock_out'] = $clockRecord->clock_out;
        $data['breaks'] = $attendanceRecord->breakRecords()->get()->toArray();
        $data['comment'] = $attendanceRecord->comment;

        return $data;
    }

    /**
     * 修正申請を保存
     *
     * @param  AttendanceUpdateRequest  $request  バージョン済みのリクエスト
     */
    public function saveRequestRecord(AttendanceUpdateRequest $request, AttendanceRecord $attendanceRecord): void
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

    /**
     * 休憩の入戻をセットで配列に変換
     *
     * @param  array  $breakIn  休憩入の配列
     * @param  array  $breakOut  休憩戻の配列
     * @param  string|null  $type  修正申請の場合は'new_'を入れる
     */
    public function makeBreakArr(array $breakIn, array $breakOut, ?string $type = null): array
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
     * 出退勤、休憩の入戻登録処理
     * 本日から前の出勤日の間にデータがなければAttendanceRecordのみ作成する
     *
     * @param  string  $action(clock_in,  clock_out, break_in, break_out)
     */
    public function storeAttendanceRecord(string $action): void
    {
        $user = auth()->user();
        $today = CarbonImmutable::today();

        $attendance = AttendanceRecord::firstOrNew([
            'user_id' => $user->id,
            'date' => $today->format('Y-m-d'),
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

    /**
     * 最新の勤怠レコードを取得、なければユーザー作成日を返す
     */
    public function getStartDay(User $user): Carbon|CarbonImmutable
    {
        $latestAtt = AttendanceRecord::getLatestAttendance($user);

        if ($latestAtt) {
            $startDay = dateFormat($latestAtt->date)->addDay()->startOfDay();
        } else {
            $startDay = $user->created_at->startOfDay();
        }

        return $startDay;
    }

    /**
     * 勤怠実績がない場合の空レコード作成
     */
    public function createOnlyAttendanceRecode(CarbonImmutable|Carbon $startDay, CarbonImmutable $today, User $user): void
    {
        for ($date = $startDay; $date->lt($today); $date = $date->addDay()) {
            AttendanceRecord::firstOrCreate([
                'user_id' => $user->id,
                'date' => $date,
            ]);
        }
    }

    /**
     * 管理者の修正処理（直接勤怠レコードをupdate）
     *
     * @param  int  $id  AttendanceRecordのID
     */
    public function updateAttendance(array $validated, int $id): void
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

    /**
     * new_がついたinputフォームをnew_無しにして配列に変換
     */
    public function newDataFormat(array $validated): array
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

    /**
     * 休憩レコードの作成・更新
     *
     * @param  AttendanceRecord  $attendanceRecord
     */
    public function saveBreakRecords(storeAttendanceRecord $attendanceRecord, array $breakArr): void
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
