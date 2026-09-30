<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 管理ユーザーは_cs_vデータのダウンロードができる(): void
    {
        $adminUser = User::factory()->create(['admin_status' => 1]);
        $user = User::factory()->create();
        $now = CarbonImmutable::now();

        $AttendanceRecord = AttendanceRecord::factory()->create([
            'date' => getFirstOfMonth($now)->format('Y-m-d'),
            'user_id' => $user->id,
        ]);

        $AttendanceRecord->clockRecord()->create([
            'clock_in' => '09:00:00',
            'clock_out' => '17:00:00',
        ]);

        $AttendanceRecord->breakRecords()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $AttendanceRecord1 = AttendanceRecord::factory()->create([
            'date' => getEndOfMonth($now->subMonth())->format('Y-m-d'),
            'user_id' => $user->id,
        ]);

        $AttendanceRecord1->clockRecord()->create([
            'clock_in' => '09:30:00',
            'clock_out' => '17:00:00',
        ]);

        $AttendanceRecord1->breakRecords()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($adminUser)->post('/export', [
            'user_id' => $user->id,
            'year_month' => $now->format('Y-m'),
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString(getFirstOfMonth($now)->format('Y/m/d'), $content);
        $this->assertStringContainsString('09:00', $content);
        $this->assertStringNotContainsString(getEndOfMonth($now->subMonth())->format('Y/m/d'), $content);
    }

    /** @test */
    public function 表示付きを変更した場合、その月の_cs_vがダウンロードされる(): void
    {
        $adminUser = User::factory()->create(['admin_status' => 1]);
        $user = User::factory()->create();
        $now = CarbonImmutable::now();

        $AttendanceRecord = AttendanceRecord::factory()->create([
            'date' => getFirstOfMonth($now)->format('Y-m-d'),
            'user_id' => $user->id,
        ]);

        $AttendanceRecord->clockRecord()->create([
            'clock_in' => '09:00:00',
            'clock_out' => '17:00:00',
        ]);

        $AttendanceRecord->breakRecords()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $AttendanceRecord1 = AttendanceRecord::factory()->create([
            'date' => getEndOfMonth($now->subMonth())->format('Y-m-d'),
            'user_id' => $user->id,
        ]);

        $AttendanceRecord1->clockRecord()->create([
            'clock_in' => '09:30:00',
            'clock_out' => '17:00:00',
        ]);

        $AttendanceRecord1->breakRecords()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($adminUser)->post('/export', [
            'user_id' => $user->id,
            'year_month' => $now->subMonth()->format('Y-m'),
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringNotContainsString(getFirstOfMonth($now)->format('Y/m/d'), $content);
        $this->assertStringContainsString('09:30', $content);
        $this->assertStringContainsString(getEndOfMonth($now->subMonth())->format('Y/m/d'), $content);
    }
}
