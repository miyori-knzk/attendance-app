<?php

namespace App\Http\Resources;

use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;

class AttendanceRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $attendanceRecord = $this->resource;
        $attendanceService = App::make(AttendanceService::class);

        $workTime = $attendanceService->calculateWorkTime($attendanceRecord);
        $breakSum = $attendanceService->calculateBreakTime($attendanceRecord);
        $totalTime = $attendanceService->calculateTotalTime($breakSum, $workTime['workSum']);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user->name,
            'date' => $this->date,
            'clock_in' => $this->clockRecord->clock_in,
            'clock_out' => $this->clockRecord->clock_out,
            'total_time' => $totalTime,
            'total_break_time' => $breakSum,
            'comment' => $this->comment,
        ];
    }
}
