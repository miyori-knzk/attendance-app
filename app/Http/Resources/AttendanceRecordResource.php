<?php

namespace App\Http\Resources;

use App\Services\AttendanceService;
use Carbon\CarbonImmutable;
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
        $data = [];

        $attendanceRecord = $this->resource;
        $attendanceService = App::make(AttendanceService::class);

        $workTime = $attendanceService->calculateWorkTime($attendanceRecord);
        $breakSum = $attendanceService->calculateBreakTime($attendanceRecord);
        $totalTime = $attendanceService->calculateTotalTime($breakSum, $workTime['workSum']);

        $data['id'] = $this->id;

        $data['user'] = $this->whenLoaded('user', function () {
            return new UserResource($this->user);
        });

        $data['date'] = $this->date;
        $data['clock_in'] = hisToHi($this->clockRecord->clock_in);
        $data['clock_out'] = hisToHi($this->clockRecord->clock_out);

        $data['breaks'] = $this->whenLoaded('breakRecords', function () {
            return BreakRecordResource::collection($this->breakRecords);
        });

        $data['total_time'] = $totalTime ? CarbonImmutable::parse($totalTime * 60)->format('G:i') : '';
        $data['total_break_time'] = $breakSum ? CarbonImmutable::parse($breakSum * 60)->format('G:i') : '';
        $data['comment'] = $this->comment;

        $data['applications'] = $this->whenLoaded('attendanceCorrectRequests', function () {
            return ApplicationResource::collection($this->attendanceCorrectRequests);
        });

        return $data;
    }
}
