<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrectRequest;
use App\Models\AttendanceRecord;
use App\Models\BreakRecord;
use App\Models\ClockRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
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
    public function 現在の日時情報が_u_iと同じ形式で出力されている()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/attendance');

        $date = date('Y年n月j日', strtotime('today'));
        $time = now()->format('H:i');

        $response->assertSee($date);
        $response->assertSee($time);
    }

    /** @test */
    public function 勤務外の場合、勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('勤務外');
        $response->assertDontSee('退勤済');
        $response->assertDontSee('休憩中');
        $response->assertDontSee('出勤中');
    }

    /** @test */
    public function 出勤中の場合、勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'date' => date('Y-m-d'),
            'user_id' => $user->id,
        ]);
        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertDontSee('勤務外');
        $response->assertDontSee('退勤済');
        $response->assertDontSee('休憩中');
        $response->assertSee('出勤中');
    }

    /** @test */
    public function 休憩から戻って出勤中の場合、勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'date' => date('Y-m-d'),
            'user_id' => $user->id,
        ]);
        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => null,
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00',
            'break_out' => '12:45',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertDontSee('勤務外');
        $response->assertDontSee('退勤済');
        $response->assertDontSee('休憩中');
        $response->assertSee('出勤中');
    }

    /** @test */
    public function 休憩中の場合、勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'date' => date('Y-m-d'),
            'user_id' => $user->id,
        ]);
        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => null,
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00',
            'break_out' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertDontSee('勤務外');
        $response->assertDontSee('退勤済');
        $response->assertSee('休憩中');
        $response->assertDontSee('出勤中');
    }

    /** @test */
    public function 退勤済の場合、勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'date' => date('Y-m-d'),
            'user_id' => $user->id,
        ]);
        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => '17:30',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertDontSee('勤務外');
        $response->assertSee('退勤済');
        $response->assertDontSee('休憩中');
        $response->assertDontSee('出勤中');
    }

    /** @test */
    public function 出勤ボタンが正しく表示される()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('value="clock_in"', false);
        $response->assertDontSee('value="break_in"', false);
        $response->assertDontSee('value="break_out"', false);
        $response->assertDontSee('value="clock_out"', false);

    }

    /** @test */
    public function 出勤ボタンが正しく機能する()
    {
        $user = User::factory()->create();
        $now = now()->format('H:i');

        $response = $this->actingAs($user)->post('/attendance', ['action' => 'clock_in']);

        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)->where('date', now()->format('Y-m-d'))->first();

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
        ]);

        $this->assertDatabaseHas('clock_records', [
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => $now,
        ]);
    }

    /** @test */
    public function 出勤は一日一回のみできる()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/attendance');

        $attendanceRecord = AttendanceRecord::factory()->create([
            'date' => date('Y-m-d'),
            'user_id' => $user->id,
        ]);
        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => '17:30',
        ]);

        $response->assertDontSee('value="clock_in"', false);
        $response->assertDontSee('value="break_in"', false);
        $response->assertDontSee('value="break_out"', false);
        $response->assertDontSee('value="clock_out"', false);
    }

    /** @test */
    public function 出勤時刻が勤怠一覧画面で確認できる()
    {
        $user = User::factory()->create();
        $date = now();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'date' => $date->format('Y-m-d'),
            'user_id' => $user->id,
        ]);
        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertSee($date->format('m/d'));
        $response->assertSee('09:00');
    }

    /** @test */
    public function 出勤中のユーザーがログインした時に休憩ボタンが正しく表示されている()
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => date('Y-m-d'),
        ]);

        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('value="break_in"', false);
        $response->assertDontSee('value="break_out"', false);
        $response->assertDontSee('value="clock_in"', false);
    }

    /** @test */
    public function 出勤中のユーザーの休憩入ボタンが正しく機能する()
    {
        $user = User::factory()->create();
        $now = now()->format('H:i');

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => date('Y-m-d'),
        ]);

        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => null,
        ]);

        $response = $this->actingAs($user)->post('/attendance', ['action' => 'break_in']);

        $this->assertDatabaseHas('break_records', [
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => $now,
        ]);
    }

    /** @test */
    public function 休憩は一日何回でもできる()
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => date('Y-m-d'),
        ]);

        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => null,
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '10:30',
            'break_out' => '10:35',
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertDontSee('value="clock_in"', false);
        $response->assertSee('value="break_in"', false);
        $response->assertDontSee('value="break_out"', false);
    }

    /** @test */
    public function 休憩中のユーザーの休憩戻ボタンが正しく表示されている()
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => date('Y-m-d'),
        ]);

        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => null,
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00',
            'break_out' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertDontSee('value="clock_in"', false);
        $response->assertDontSee('value="clock_out"', false);
        $response->assertDontSee('value="break_in"', false);
        $response->assertSee('value="break_out"', false);
    }

    /** @test */
    public function 休憩中のユーザーの休憩戻ボタンが正しく機能する()
    {
        $user = User::factory()->create();
        $now = now()->format('H:i');

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => date('Y-m-d'),
        ]);

        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => null,
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00',
            'break_out' => null,
        ]);

        $response = $this->actingAs($user)->post('/attendance', ['action' => 'break_out']);

        $this->assertDatabaseHas('break_records', [
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00',
            'break_out' => $now,
        ]);
    }

    /** @test */
    public function 休憩戻は一日何回でもできる()
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => date('Y-m-d'),
        ]);

        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => null,
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

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('value="break_out"', false);
        $response->assertDontSee('value="break_in"', false);
        $response->assertDontSee('value="clock_in"', false);
        $response->assertDontSee('value="clock_out"', false);
    }

    /** @test */
    public function 休憩時刻が勤怠詳細画面から確認できる()
    {
        $user = User::factory()->create();
        $date = now();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'date' => $date->format('Y-m-d'),
            'user_id' => $user->id,
        ]);
        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => null,
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00',
            'break_out' => '12:45',
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendanceRecord->id);

        $response->assertSee('12:00');
        $response->assertSee('12:45');
    }

    /** @test */
    public function 退勤ボタンが正しく表示される()
    {
        $user = User::factory()->create();
        $now = now();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'date' => $now->format('Y-m-d'),
            'user_id' => $user->id,
        ]);
        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertDontSee('value="clock_in"', false);
        $response->assertSee('value="break_in"', false);
        $response->assertDontSee('value="break_out"', false);
        $response->assertSee('value="clock_out"', false);

    }

    /** @test */
    public function 退勤ボタンが正しく機能する()
    {
        $user = User::factory()->create();
        $now = now();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'date' => $now->format('Y-m-d'),
            'user_id' => $user->id,
        ]);

        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => null,
        ]);

        $response = $this->actingAs($user)->post('/attendance', ['action' => 'clock_out']);

        $this->assertDatabaseHas('clock_records', [
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => $now->format('H:i'),
        ]);
    }

    /** @test */
    public function 退勤時刻が勤怠一覧画面で確認できる()
    {
        $user = User::factory()->create();
        $date = now();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'date' => $date->format('Y-m-d'),
            'user_id' => $user->id,
        ]);
        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => '17:30',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertSee($date->format('m/d'));
        $response->assertSee('17:30');
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

    /** @test */
    public function 自分が行った勤怠情報がすべて表示されている()
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

            ClockRecord::factory()->create([
                'attendance_record_id' => $attendanceRecord->id,
                'clock_in' => '09:00',
                'clock_out' => '17:30',
            ]);

            BreakRecord::factory()->create([
                'attendance_record_id' => $attendanceRecord->id,
                'break_in' => '12:00',
                'break_out' => '12:45',
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

            ClockRecord::factory()->create([
                'attendance_record_id' => $oAttendanceRecord->id,
                'clock_in' => '09:00',
                'clock_out' => '17:30',
            ]);

            BreakRecord::factory()->create([
                'attendance_record_id' => $oAttendanceRecord->id,
                'break_in' => '12:00',
                'break_out' => '12:45',
            ]);

        }

        $userData = AttendanceRecord::getMonthUserData($firstOfThisMonth->format('Y-m-d'), $endOfThisMonth->format('Y-m-d'), $user);
        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertViewHas('formattedAttendanceRecords',
            fn ($formattedAttendanceRecords) => count($formattedAttendanceRecords) === $userData->count());
        $response->assertStatus(200);
    }

    /** @test */
    public function 勤怠一覧画面に遷移した際に現在の月が表示される()
    {
        $user = User::factory()->create();

        $thisMonth = CarbonImmutable::now()->firstOfMonth();
        $lastMonth = CarbonImmutable::now()->subMonth()->endOfMonth();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $thisMonth->format('Y-m-d'),
            'comment' => null,
        ]);

        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => '17:30',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '10:30',
            'break_out' => '10:35',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00',
            'break_out' => '12:45',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '15:00',
            'break_out' => '15:10',
        ]);

        $attendanceRecord1 = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $lastMonth->format('Y-m-d'),
            'comment' => null,
        ]);

        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord1->id,
            'clock_in' => '09:00',
            'clock_out' => '17:30',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord1->id,
            'break_in' => '10:30',
            'break_out' => '10:35',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord1->id,
            'break_in' => '12:00',
            'break_out' => '12:45',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord1->id,
            'break_in' => '15:00',
            'break_out' => '15:10',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $formatLastMonth = $lastMonth->format('m/d');
        $formatThisMonth = $thisMonth->format('m/d');
        $formatThisYm = $thisMonth->format('Y/m');

        $response->assertStatus(200);
        $response->assertViewIs('user.user-attendance-list');
        $response->assertViewHas('previousMonth');
        $response->assertViewHas('nextMonth');
        $response->assertViewHas('date');
        $response->assertViewHas('formattedAttendanceRecords');
        $response->assertSee($formatThisMonth);
        $response->assertSee($formatThisYm);

        $response->assertDontSee($formatLastMonth);
    }

    /** @test */
    public function 「前月」を押下した時に表示月の前月の情報が表示される()
    {
        $user = User::factory()->create();

        $now = CarbonImmutable::now();

        $firstOfThisMonth = $now->firstOfMonth();
        $lastMonth = $now->subMonth();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $firstOfThisMonth->format('Y-m-d'),
            'comment' => null,
        ]);

        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => '17:30',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '10:30',
            'break_out' => '10:35',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00',
            'break_out' => '12:45',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '15:00',
            'break_out' => '15:10',
        ]);

        $attendanceRecord1 = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $lastMonth->endOfMonth()->format('Y-m-d'),
            'comment' => null,
        ]);

        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord1->id,
            'clock_in' => '09:00',
            'clock_out' => '17:30',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord1->id,
            'break_in' => '10:30',
            'break_out' => '10:35',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord1->id,
            'break_in' => '12:00',
            'break_out' => '12:45',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord1->id,
            'break_in' => '15:00',
            'break_out' => '15:10',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?date=' . $lastMonth->firstOfMonth()->format('Y-m-d'));

        $formatLastMonth = $lastMonth->endOfMonth()->format('m/d');
        $formatThisMonth = $firstOfThisMonth->format('m/d');

        $response->assertStatus(200);
        $response->assertViewIs('user.user-attendance-list');
        $response->assertViewHas('previousMonth');
        $response->assertViewHas('nextMonth');
        $response->assertViewHas('date');
        $response->assertViewHas('formattedAttendanceRecords');
        $response->assertSee($lastMonth->format('Y/m'));
        $response->assertSee($formatLastMonth);
        $response->assertDontSee($formatThisMonth);
    }

    /** @test */
    public function 「翌月」を押下した時に表示月の翌月の情報が表示される()
    {
        $user = User::factory()->create();

        $now = CarbonImmutable::now();

        $firstOfThisMonth = $now->firstOfMonth();
        $nextMonth = $now->addMonth();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $firstOfThisMonth->format('Y-m-d'),
            'comment' => null,
        ]);

        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => '17:30',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '10:30',
            'break_out' => '10:35',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00',
            'break_out' => '12:45',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '15:00',
            'break_out' => '15:10',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?date=' . $nextMonth->firstOfMonth()->format('Y-m-d'));

        $formatThisMonth = $firstOfThisMonth->format('m/d');

        $response->assertStatus(200);
        $response->assertViewIs('user.user-attendance-list');
        $response->assertViewHas('previousMonth');
        $response->assertViewHas('nextMonth');
        $response->assertViewHas('date');
        $response->assertViewHas('formattedAttendanceRecords');
        $response->assertSee($nextMonth->format('Y/m'));
        $response->assertDontSee($firstOfThisMonth->format('m/d'));
    }

    /** @test */
    public function 「詳細」を押下すると、その日の勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create();
        $now = CarbonImmutable::now();

        $firstOfThisMonth = $now->firstOfMonth();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $firstOfThisMonth->format('Y-m-d'),
            'comment' => null,
        ]);

        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => '17:30',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '10:30',
            'break_out' => '10:35',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00',
            'break_out' => '12:45',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '15:00',
            'break_out' => '15:10',
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertViewHas('data');
        $response->assertViewHas('user');
        $response->assertSee('勤怠詳細');
        $response->assertSee($firstOfThisMonth->format('Y年'));
        $response->assertSee($firstOfThisMonth->format('m月'));
        $response->assertSee($firstOfThisMonth->format('d日'));
    }

    /** @test */
    public function 勤怠詳細画面の「名前」がログインユーザーの氏名になっている()
    {
        $user = User::factory()->create();
        $now = CarbonImmutable::now();

        $firstOfThisMonth = $now->firstOfMonth();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $firstOfThisMonth->format('Y-m-d'),
            'comment' => null,
        ]);

        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => '17:30',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '10:30',
            'break_out' => '10:35',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00',
            'break_out' => '12:45',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '15:00',
            'break_out' => '15:10',
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSee($user->name);
    }

    /** @test */
    public function 勤怠詳細画面の「日付」が選択した日付になっている()
    {
        $user = User::factory()->create();
        $now = CarbonImmutable::now();

        $firstOfThisMonth = $now->firstOfMonth();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $firstOfThisMonth->format('Y-m-d'),
            'comment' => null,
        ]);

        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => '17:30',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '10:30',
            'break_out' => '10:35',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00',
            'break_out' => '12:45',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '15:00',
            'break_out' => '15:10',
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSee($firstOfThisMonth->format('Y年'));
        $response->assertSee($firstOfThisMonth->format('m月'));
        $response->assertSee($firstOfThisMonth->format('d日'));
    }

    /** @test */
    public function 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している()
    {
        $user = User::factory()->create();
        $now = CarbonImmutable::now();

        $firstOfThisMonth = $now->firstOfMonth();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $firstOfThisMonth->format('Y-m-d'),
            'comment' => null,
        ]);

        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => '17:30',
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendanceRecord->id);

        $response->assertSee('09:00');
        $response->assertSee('17:30');
        $response->assertStatus(200);

    }

    /** @test */
    public function 「休憩」にて記されている時間がログインユーザーの打刻と一致している()
    {
        $user = User::factory()->create();
        $now = CarbonImmutable::now();

        $firstOfThisMonth = $now->firstOfMonth();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $firstOfThisMonth->format('Y-m-d'),
            'comment' => null,
        ]);

        ClockRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => '17:30',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '10:30',
            'break_out' => '10:35',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00',
            'break_out' => '12:45',
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '15:00',
            'break_out' => '15:10',
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendanceRecord->id);

        $response->assertSee('10:30');
        $response->assertSee('10:35');
        $response->assertSee('12:00');
        $response->assertSee('12:45');
        $response->assertSee('15:00');
        $response->assertSee('15:10');

        $response->assertStatus(200);
    }

    /** @test */
    public function 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendanceRecord->id, [
            'new_clock_in' => '18:00',
            'new_clock_out' => '17:30',
            'comment' => 'テスト',
        ]);

        $response->assertSessionHasErrors(['new_clock_out']);
    }

    /** @test */
    public function 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendanceRecord->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '17:30',
            'new_break_in' => ['18:00'],
            'new_brak_out' => ['18:30'],
            'comment' => 'テスト',
        ]);

        $response->assertSessionHasErrors(['new_break_in.0']);
    }

    /** @test */
    public function 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendanceRecord->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '17:30',
            'new_break_in' => ['17:00'],
            'new_brak_out' => ['18:30'],
            'comment' => 'テスト',
        ]);

        $response->assertSessionHasErrors(['new_break_in.0']);
    }

    /** @test */
    public function 備考欄が未入力の場合のエラーメッセージが表示される()
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendanceRecord->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '17:30',
            'comment' => '',
        ]);

        $response->assertSessionHasErrors(['comment']);
    }

    /** @test */
    public function 修正申請処理が実行される()
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendanceRecord->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '17:30',
            'new_break_in' => ['12:00'],
            'new_break_out' => ['12:45'],
            'comment' => 'テストコメント',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $attendanceCorrectRequest = AttendanceCorrectRequest::where('attendance_record_id', $attendanceRecord->id)->first();

        $this->assertDatabaseHas('attendance_correct_requests', [
            'attendance_record_id' => $attendanceRecord->id,
        ]);
        $this->assertDatabaseHas('clock_correct_requests', [
            'attendance_correct_request_id' => $attendanceCorrectRequest->id,
            'new_clock_in' => '09:00',
            'new_clock_out' => '17:30',
        ]);
        $this->assertDatabaseHas('break_correct_requests', [
            'attendance_correct_request_id' => $attendanceCorrectRequest->id,
            'new_break_in' => '12:00',
            'new_break_out' => '12:45',
        ]);
    }
}
