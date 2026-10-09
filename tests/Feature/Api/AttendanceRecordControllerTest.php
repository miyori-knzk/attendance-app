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
        $response = $this->getJson('/api/v1/attendance-records');

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

    /** @test */
    public function 勤怠詳細が_jso_nで取得できる(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => date('Y-m-d'),
            'comment' => 'JsonTest',
        ]);

        $attendanceRecord->clockRecord()->create([
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $attendanceRecord->breakRecords()->create([
            'break_in' => '12:00:00',
            'break_out' => '12:45:00',
        ]);

        $response = $this->getJson('/api/v1/attendance-records/' . $attendanceRecord->id);
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $attendanceRecord->id,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'date' => $attendanceRecord->date,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                [
                    'break_in' => '12:00',
                    'break_out' => '12:45',
                ],

            ],
            'comment' => $attendanceRecord->comment,
        ]);
    }

    /** @test */
    public function 存在しない_i_dでは404とエラー_jso_nが返る()
    {
        $response = $this->getJson('/api/v1/attendance-records/9999');

        $response->assertStatus(404);
        $response->assertExactJson([
            'error' => '勤怠情報が見つかりませんでした。',
        ]);
    }
}
