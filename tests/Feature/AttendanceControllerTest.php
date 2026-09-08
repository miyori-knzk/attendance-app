<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\BreakRecord;
use App\Models\ClockRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ログインユーザーは出退勤登録画面を開くことができる(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertViewIs('user.attendance-register');
        $response->assertViewHas('user');
        $response->assertViewHas('formattedDate');
        $response->assertViewHas('formattedTime');
    }

    /** @test */
    public function 未認証ユーザーは出退勤登録画面を開こうとしたときにログインページにリダイレクトされる(): void
    {
        $response = $this->get('/attendance');
        $response->assertRedirect('login');
    }

    /** @test */
    public function 出勤ボタン押下時に_attendance_recordと_clock_recordが作成される(): void
    {
        $user = User::factory()->create([
            'created_at' => date('2026-07-01'),
        ]);

        $response = $this->actingAs($user)->post('/attendance', ['action' => 'clock_in']);

        $response->assertRedirect('/attendance');
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'date' => date('Y-m-d'),
        ]);

        $clockRecord = $user->attendanceRecords()
            ->whereDate('date', date('Y-m-d'))
            ->first()
            ->clockRecord;
        $this->assertNotNull($clockRecord->clock_in);
        $this->assertNull($clockRecord->clock_out);
    }

    /** @test */
    public function 退勤ボタン押下時に_clock_recordが更新される(): void
    {
        $user = User::factory()->create([
            'created_at' => date('2026-07-01'),
        ]);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => date('Y-m-d'),
            'comment' => null,
        ]);

        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
        ]);

        $response = $this->actingAs($user)->post('/attendance', ['action' => 'clock_out']);

        $response->assertRedirect('/attendance');

        $clockRecord = $attendanceRecord->clockRecord;

        $this->assertNotNull($clockRecord->clock_in);
        $this->assertNotNull($clockRecord->clock_out);
    }

    /** @test */
    public function 休憩入りボタン押下時に_break_recordが作成される(): void
    {
        $user = User::factory()->create([
            'created_at' => date('2026-07-01'),
        ]);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => date('Y-m-d'),
            'comment' => null,
        ]);

        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
        ]);

        $response = $this->actingAs($user)->post('/attendance', ['action' => 'break_in']);

        $response->assertRedirect('/attendance');

        $breakRecord = $attendanceRecord->breakRecords()->orderBy('break_in', 'desc')->first();

        $this->assertNotNull($breakRecord->break_in);
        $this->assertNull($breakRecord->break_out);

    }

    /** @test */
    public function 休憩戻りボタン押下時に_break_recordが更新される(): void
    {
        $user = User::factory()->create([
            'created_at' => date('2026-07-01'),
        ]);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => date('Y-m-d'),
            'comment' => null,
        ]);

        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '10:30',
            'break_out' => '10:35',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00',
            'break_out' => null,
        ]);

        $response = $this->actingAs($user)->post('/attendance', ['action' => 'break_out']);

        $breakRecord = $attendanceRecord->breakRecords()->orderBy('break_in', 'desc')->first();

        $response->assertRedirect('/attendance');
        $this->assertNotNull($breakRecord->break_in);
        $this->assertNotNull($breakRecord->break_out);
    }
}
