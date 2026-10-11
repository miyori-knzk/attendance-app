<?php

namespace Tests\Feature\Api;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
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
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'breaks' => [
                [
                    'break_in' => '12:00:00',
                    'break_out' => '12:45:00',
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

    /** @test */
    public function 勤怠が作成できる()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-10-11',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'apiTest',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'date' => '2026-10-11',
        ]);
        $response->assertJsonFragment([
            'id' => 1,
            'date' => '2026-10-11',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'apiTest',
        ]);

    }

    /** @test */
    public function 勤怠登録時にバリデーションエラーで422と日本語エラーメッセージが返る()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '',
            'clock_in' => '',
            'clock_out' => '18:00',
            'comment' => 'apiTest',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'date' => [
                '勤怠日は必須です。',
            ],
            'clock_in' => [
                '出勤時刻は必須です。',
            ],
            'clock_out' => [
                '退勤時刻は HH:MM:SS 形式で指定してください。',
            ],
        ]);
    }

    /** @test */
    public function 勤怠が更新できる()
    {
        $user = User::factory()->create(['admin_status' => 1]);
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-11',
            'comment' => '更新前',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records/' . $attendanceRecord->id, [
            'date' => '2026-10-11',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'apiTest',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('attendance_records', [
            'date' => '2026-10-11',
            'comment' => 'apiTest',
        ]);
        $this->assertDatabaseHas('clock_records', [
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
    }

    /** @test */
    public function 存在しない勤怠_i_dで更新しようとすると404を返す()
    {
        $user = User::factory()->create();
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-11',
            'comment' => '更新前',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records/999', [
            'date' => '2026-10-11',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'apiTest',
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function 勤怠を削除できる()
    {
        $user = User::factory()->create(['admin_status' => 1]);
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-11',
            'comment' => 'test',
        ]);

        $attendanceRecord->clockRecord()->create([
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $attendanceRecord->breakRecords()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/attendance-records/' . $attendanceRecord->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('attendance_records', ['id' => $attendanceRecord->id]);
        $this->assertDatabaseMissing('clock_records', ['attendance_record_id' => $attendanceRecord->id]);
        $this->assertDatabaseMissing('break_records', ['attendance_record_id' => $attendanceRecord->id]);
    }
}
