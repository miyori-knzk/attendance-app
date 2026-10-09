<?php

namespace Tests\Feature\Api;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRecordControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 勤怠一覧が_jso_nで取得できる(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $now = CarbonImmutable::now();
        $today = $now->startOfDay();

        $firstOfThisMonth = $now->firstOfMonth()->startOfDay();
        $endOfThisMonth = $now->endOfMonth()->startOfDay();

        for ($day = $firstOfThisMonth; $day->lte($today); $day = $day->addDay()) {
            $attendanceRecord = null;
            $attendanceRecord = AttendanceRecord::factory()->create([
                'user_id' => $user->id,
                'date' => $day->format('Y-m-d'),
                'comment' => null,
            ]);

            $attendanceRecord->clockRecord()->create([
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
            ]);

            $attendanceRecord->breakRecords()->create([
                'break_in' => '12:00:00',
                'break_out' => '12:45:00',
            ]);

        }

        $firstOfLastMonth = $now->subDay()->firstOfMonth()->startOfDay();

        for ($lDay = $firstOfLastMonth; $day->lte($today); $lDay = $lDay->addDay()) {
            $oAttendanceRecord = null;
            $oAttendanceRecord = AttendanceRecord::factory()->create([
                'user_id' => $user->id,
                'date' => $lDay->format('Y-m-d'),
                'comment' => null,
            ]);

            $oAttendanceRecord->clockRecord()->create([
                'clock_in' => '09:00:00',
                'clock_out' => '17:30:00',
            ]);

            $oAttendanceRecord->breakRecords()->create([
                'break_in' => '12:00:00',
                'break_out' => '12:45:00',
            ]);

        }

        $dataCnt = AttendanceRecord::all()->count();
        $response = $this->get('/api/v1/attendance-records');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);
        $response->assertJsonFragment([
            'total' => $dataCnt,
        ]);
    }
}
