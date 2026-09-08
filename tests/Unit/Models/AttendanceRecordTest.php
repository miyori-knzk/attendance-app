<?php

namespace Tests\Unit\Models;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRecordTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function データがないときにget_latest_attendanceメソッドが_nul_lを返す(): void
    {
        $user = User::factory()->create();

        $latestAtt = AttendanceRecord::getLatestAttendance($user);

        $this->assertNull($latestAtt);
    }

    /** @test */
    public function get_latest_attendanceメソッドがdateが最新の_attendance_recordのデータを返す(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => date('2026-08-01'),
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => date('2026-07-30'),
        ]);

        $latestAtt = AttendanceRecord::getLatestAttendance($user);

        $this->assertEquals('2026-08-01', $latestAtt->date);
    }
}
