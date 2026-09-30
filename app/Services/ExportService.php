<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\CarbonImmutable;

class ExportService
{
    private AttendanceService $attendanceService;

    /**
     * ExportService constructor
     */
    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * CSVデータの作成
     *
     * @param array バリデーション済みのデータ(user_id,year_monthが格納されている)
     * @return array CSVデータを格納した配列
     */
    public function makeExportData(array $validated): array
    {
        $data = [];

        $date = dateFormat($validated['year_month'] . '-01');
        $user = User::findOrFail($validated['user_id']);
        $attendances = AttendanceRecord::getMonthUserData($date, $user);

        $filename = $validated['year_month'] . $user->name . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        $callback = function () use ($attendances) {

            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

            if ($attendances->count() > 0) {
                foreach ($attendances as $attendance) {
                    $attDay = dateFormat($attendance->date);

                    $tmpDayOfWeek = $attDay->dayOfWeek;

                    $tmpClocks = $this->attendanceService->calculateWorkTime($attendance);
                    $breakSum = $this->attendanceService->calculateBreakTime($attendance);
                    $totalTime = $this->attendanceService->calculateTotalTime($breakSum, $tmpClocks['workSum']);

                    fputcsv($handle, [
                        $attDay->format('Y/m/d') . '(' . jpWeekday($tmpDayOfWeek) . ')',
                        dateTimeToHi($tmpClocks['tmpClockIn']),
                        dateTimeToHi($tmpClocks['tmpClockOut']),
                        CarbonImmutable::parse($breakSum * 60)->format('G:i'),
                        CarbonImmutable::parse($totalTime * 60)->format('G:i'),
                    ]);
                }
            }
            fclose($handle);
        };

        $data['callback'] = $callback;
        $data['filename'] = $filename;
        $data['headers'] = $headers;

        return $data;
    }
}
