<?php

namespace Tests\Feature\Auth;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SanctumTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 未認証時に勤怠登録で401が返る()
    {
        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-10-11',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'apiTest',
        ]);

        $response->assertStatus(401);
        $response->assertJsonFragment([
            'message' => 'Unauthenticated.',
        ]);
    }

    /** @test */
    public function 未認証時に勤怠更新で401が返る()
    {
        $attendanceRecord = AttendanceRecord::factory()->create();

        $response = $this->postJson('/api/v1/attendance-records/' . $attendanceRecord->id, [
            'date' => '2026-10-11',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'apiTest',
        ]);

        $response->assertStatus(401);
        $response->assertJsonFragment([
            'message' => 'Unauthenticated.',
        ]);
    }

    /** @test */
    public function 未認証時に勤怠削除で401が返る()
    {
        $attendanceRecord = AttendanceRecord::factory()->create();

        $response = $this->deleteJson('/api/v1/attendance-records/' . $attendanceRecord->id);

        $response->assertStatus(401);
        $response->assertJsonFragment([
            'message' => 'Unauthenticated.',
        ]);
    }

    /** @test */
    public function 認証済みユーザーは自分の勤怠が更新できる()
    {
        $user = User::factory()->create();
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
    public function 認証済みユーザーは自分の勤怠を削除できる()
    {
        $user = User::factory()->create();
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

    /** @test */
    public function 他ユーザーの勤怠を更新しようとすると403が返る()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $otherUser->id,
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

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'この操作を実行する権限がありません。',
        ]);
    }

    /** @test */
    public function 他ユーザーの勤怠を削除しようとすると403が返る()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $otherUser->id,
            'date' => '2026-10-11',
            'comment' => 'test',
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/attendance-records/' . $attendanceRecord->id);

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'この操作を実行する権限がありません。',
        ]);

    }
}
