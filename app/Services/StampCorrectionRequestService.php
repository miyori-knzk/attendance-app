<?php

namespace App\Services;

use App\Models\AttendanceCorrectRequest;
use App\Models\AttendanceRecord;
use App\Models\BreakRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class StampCorrectionRequestService
{
    /**
     * 特定のユーザーの修正申請のみフォーマット
     *
     * @param  Collection  $application
     */
    public function formatUsersAppData(Collection $applications, User $user): array
    {
        $formattedApplications = [];

        foreach ($applications as $application) {

            if ($application->user->id == $user->id) {
                $formattedApplications[] = [
                    'id' => $application->attendance_record_id,
                    'approval_status' => $application->approval_status,
                    'date' => dateFormat2($application->attendanceRecord->date),
                    'comment' => $application->comment,
                    'application_date' => dateFormat2($application->created_at),
                ];
            }
        }

        return $formattedApplications;
    }

    /**
     * 管理者の申請承認処理
     */
    public function approve(int $attendanceCorrectRequestId): void
    {
        $application = AttendanceCorrectRequest::findOrFail($attendanceCorrectRequestId);
        $breakCorrectRequests = $application->breakCorrectRequests()->orderBy('new_break_in')->get();
        $attendance = $application->attendanceRecord;
        $breakRecords = $attendance->breakRecords()->orderBy('break_in')->get();

        DB::connection()->transaction(function () use ($application, $attendance, $breakRecords, $breakCorrectRequests) {
            $this->updateClockRecord($attendance, $application);
            $this->updateBreakRecord($attendance->id, $breakRecords, $breakCorrectRequests);
            $application->update(['status' => $attendance->attendanceCorrectRequests()->max('status') + 1]);
            $attendance->update(['comment' => $application->comment]);
        });
    }

    /**
     * 出退勤レコードの登録・更新
     */
    public function updateClockRecord(AttendanceRecord $attendance, AttendanceCorrectRequest $application): void
    {
        $attendance->clockRecord()->updateOrCreate([
            'clock_in' => $application->new_clock_in,
            'clock_out' => $application->new_clock_out,
        ]);
    }

    /**
     * 休憩レコードの登録・更新
     */
    public function updateBreakRecord(int $attendanceRecordId, Collection $breakRecords, Collection $breakCorrectRequests): void
    {
        $max = max($breakRecords->count(), $breakCorrectRequests->count());

        for ($i = 0; $i < $max; $i++) {

            $attData = $breakRecords[$i] ?? null;
            $corrData = $breakCorrectRequests[$i] ?? null;

            if ($attData && $corrData) {
                $attData->update([
                    'break_in' => $corrData->new_break_in,
                    'break_out' => $corrData->new_break_out,
                ]);

                continue;
            }

            if (! $attData && $corrData) {
                BreakRecord::create([
                    'attendance_record_id' => $attendanceRecordId,
                    'break_in' => $corrData->new_break_in,
                    'break_out' => $corrData->new_break_out,
                ]);

                continue;
            }

            if ($attData && ! $corrData) {
                $attData->delete();

                continue;
            }
        }
    }
}
