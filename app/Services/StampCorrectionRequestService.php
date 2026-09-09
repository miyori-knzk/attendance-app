<?php

namespace App\Services;

use App\Models\AttendanceCorrectRequest;
use Illuminate\Support\Facades\DB;

class StampCorrectionRequestService
{
    public function formatUsersAppData($applications, $user)
    {
        $formattedApplications = [];

        foreach ($applications as $application) {

            if ($application->user->id == $user->id) {
                $formattedApplications[] = [
                    'id' => $application->attendance_id,
                    'approval_status' => $application->approval_status,
                    'date' => dateFormat2($application->attendanceRecord->date),
                    'comment' => $application->comment,
                    'application_date' => dateFormat2($application->created_at),
                ];
            }
        }

        return $formattedApplications;
    }

    public function approve($attendanceCorrectRequestId)
    {
        $application = AttendanceCorrectRequest::findOrFail($attendanceCorrectRequestId);
        $breakCorrectRequests = $application->breakCorrectRequests()->orderBy('new_break_in')->get();
        $attendance = $application->attendanceRecord;
        $breakRecords = $attendance->breakRecords()->orderBy('break_in')->get();

        DB::connection()->transaction(function () use ($application, $attendance, $breakRecords, $breakCorrectRequests) {
            $this->updateClockRecord($attendance, $application);
            $this->updateBreakRecord($breakRecords, $breakCorrectRequests);
            $application->update(['status' => $attendance->attendanceCorrectRequests()->max('status') + 1]);
            $attendance->update(['comment' => $application->comment]);
        });
    }

    public function updateClockRecord($attendance, $application)
    {
        $attendance->clockRecord->update([
            'clock_in' => $application->new_clock_in,
            'clock_out' => $application->new_clock_out,
        ]);
    }

    public function updateBreakRecord($breakRecords, $breakCorrectRequests)
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
                Att::create([
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
