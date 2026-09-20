<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 勤怠一覧画面でその日になされた全ユーザーの勤怠情報が正確に確認できる()
    {
        $admin = User::factory()->create(['admin_status' => 1]);
        $now = CarbonImmutable::now();
        $formatDate = $now->format('m/d') . '(' . jpWeekday($now->dayOfWeek) . ')';

        $users = User::factory()->count(3)->create();

        $attendanceRecord1 = AttendanceRecord::factory()->create([
            'date' => $now->format('Y-m-d'),
            'user_id' => $users[0]->id,
        ]);

        $attendanceRecord1->clockRecord()->create([
            'clock_in' => '09:00',
            'clock_out' => '17:30',
        ]);

        $attendanceRecord1->breakRecords()->create([
            'break_in' => '12:00',
            'break_out' => '12:45',
        ]);

        $attendanceRecord2 = AttendanceRecord::factory()->create([
            'date' => $now->format('Y-m-d'),
            'user_id' => $users[1]->id,
        ]);

        $attendanceRecord2->clockRecord()->create([
            'clock_in' => '09:30',
            'clock_out' => '18:00',
        ]);

        $attendanceRecord2->breakRecords()->create([
            'break_in' => '13:00',
            'break_out' => '14:00',
        ]);

        $attendanceRecord3 = AttendanceRecord::factory()->create([
            'date' => $now->subDay()->format('Y-m-d'),
            'user_id' => $users[2]->id,
        ]);

        $attendanceRecord3->clockRecord()->create([
            'clock_in' => '09:00',
            'clock_out' => '17:30',
        ]);

        $attendanceRecord3->breakRecords()->create([
            'break_in' => '12:00',
            'break_out' => '12:45',
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin-attendance-list');
        $response->assertViewHas('attendanceRecords', function ($attendanceRecords) use ($formatDate) {
            $cnt = 0;
            foreach ($attendanceRecords as $att) {
                Log::debug($att->clock_in);

                if ($cnt == 0) {
                    if ($att->date == $formatDate && $att->clock_in == '09:00'
                        && $att->clock_out == '17:30' && $att->total_break_time == 45
                        && $att->total_time == 465) {
                        $cnt++;
                    }
                } else {
                    if ($att->date == $formatDate && $att->clock_in == '09:30'
                        && $att->clock_out == '18:00' && $att->total_break_time == 60
                        && $att->total_time == 450) {
                        $cnt++;
                    }
                }
            }

            return $cnt == 2;
        });

    }

    /** @test */
    public function 勤怠一覧画面に遷移した際に現在の日付が表示される()
    {
        $admin = User::factory()->create(['admin_status' => 1]);

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin-attendance-list');
        $response->assertSee(date('Y/m/d'));
        $response->assertStatus(200);
    }

    /** @test */
    public function 「前日」を押下した時に前の日の勤怠情報が表示される()
    {
        $admin = User::factory()->create(['admin_status' => 1]);
        $now = CarbonImmutable::now();
        $users = User::factory()->count(3)->create();

        $attendanceRecord1 = AttendanceRecord::factory()->create([
            'date' => $now->format('Y-m-d'),
            'user_id' => $users[0]->id,
        ]);

        $attendanceRecord1->clockRecord()->create([
            'clock_in' => '09:00',
            'clock_out' => '17:30',
        ]);

        $attendanceRecord1->breakRecords()->create([
            'break_in' => '12:00',
            'break_out' => '12:45',
        ]);

        $attendanceRecord2 = AttendanceRecord::factory()->create([
            'date' => $now->addDay()->format('Y-m-d'),
            'user_id' => $users[1]->id,
        ]);

        $attendanceRecord2->clockRecord()->create([
            'clock_in' => '09:30',
            'clock_out' => '18:00',
        ]);

        $attendanceRecord2->breakRecords()->create([
            'break_in' => '13:00',
            'break_out' => '14:00',
        ]);

        $attendanceRecord3 = AttendanceRecord::factory()->create([
            'date' => $now->subDay()->format('Y-m-d'),
            'user_id' => $users[2]->id,
        ]);

        $attendanceRecord3->clockRecord()->create([
            'clock_in' => '09:00',
            'clock_out' => '17:30',
        ]);

        $attendanceRecord3->breakRecords()->create([
            'break_in' => '12:00',
            'break_out' => '12:45',
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=' . $now->subDay()->format('Y-m-d'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin-attendance-list');
        $response->assertSee($now->subDay()->format('Y/m/d'));
        $response->assertViewHas('attendanceRecords', function ($attendanceRecords) {
            return $attendanceRecords->count() == 1;
        });
    }

    /** @test */
    public function 「翌日」を押下した時に次の日の勤怠情報が表示される()
    {
        $admin = User::factory()->create(['admin_status' => 1]);
        $now = CarbonImmutable::now();
        $users = User::factory()->count(3)->create();

        $attendanceRecord1 = AttendanceRecord::factory()->create([
            'date' => $now->format('Y-m-d'),
            'user_id' => $users[0]->id,
        ]);

        $attendanceRecord1->clockRecord()->create([
            'clock_in' => '09:00',
            'clock_out' => '17:30',
        ]);

        $attendanceRecord1->breakRecords()->create([
            'break_in' => '12:00',
            'break_out' => '12:45',
        ]);

        $attendanceRecord2 = AttendanceRecord::factory()->create([
            'date' => $now->addDay()->format('Y-m-d'),
            'user_id' => $users[1]->id,
        ]);

        $attendanceRecord2->clockRecord()->create([
            'clock_in' => '09:30',
            'clock_out' => '18:00',
        ]);

        $attendanceRecord2->breakRecords()->create([
            'break_in' => '13:00',
            'break_out' => '14:00',
        ]);

        $attendanceRecord3 = AttendanceRecord::factory()->create([
            'date' => $now->subDay()->format('Y-m-d'),
            'user_id' => $users[2]->id,
        ]);

        $attendanceRecord3->clockRecord()->create([
            'clock_in' => '09:00',
            'clock_out' => '17:30',
        ]);

        $attendanceRecord3->breakRecords()->create([
            'break_in' => '12:00',
            'break_out' => '12:45',
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=' . $now->addDay()->format('Y-m-d'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin-attendance-list');
        $response->assertSee($now->addDay()->format('Y/m/d'));
        $response->assertViewHas('attendanceRecords', function ($attendanceRecords) {
            return $attendanceRecords->count() == 1;
        });
    }

    /** @test */
    public function 勤怠詳細画面に表示されるデータが選択したものになっている()
    {
        $userDatas = [];
        $tmpApproved = [];
        $user = User::factory()->create();
        $now = CarbonImmutable::now();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $now->format('Y-m-d'),
        ]);

        $attendanceRecord->clockRecord()->create([
            'clock_in' => '09:00',
            'clock_out' => '17:30',
        ]);

        $response = $this->actingAs($user)->get('/admin/attendance/list');
        $html = $response->getContent();
        $nextUrl = '/admin/attendance/' . $attendanceRecord->id;

        preg_match('/' . preg_quote($nextUrl, '/') . '/', $html, $url);

        $nextPage = $this->actingAs($user)->get($url[0]);
        $nextPage->assertSee('勤怠詳細');
        $nextPage->assertSee($now->format('m月d日'));
        $nextPage->assertStatus(200);
    }

    /** @test */
    public function 管理者の勤怠詳細画面からの修正で出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['admin_status' => 1]);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin)->post('/admin/attendance/' . $attendanceRecord->id, [
            'new_clock_in' => '18:00',
            'new_clock_out' => '17:30',
            'comment' => 'テスト',
        ]);

        $response->assertSessionHasErrors(['new_clock_out']);

    }

    /** @test */
    public function 管理者の勤怠詳細画面からの修正で休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['admin_status' => 1]);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin)->post('/admin/attendance/' . $attendanceRecord->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '17:30',
            'new_break_in' => ['18:00'],
            'new_brak_out' => ['18:30'],
            'comment' => 'テスト',
        ]);

        $response->assertSessionHasErrors(['new_break_in.0']);
    }

    /** @test */
    public function 管理者の勤怠詳細画面からの修正で休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['admin_status' => 1]);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin)->post('/admin/attendance/' . $attendanceRecord->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '17:30',
            'new_break_in' => ['17:00'],
            'new_brak_out' => ['18:30'],
            'comment' => 'テスト',
        ]);

        $response->assertSessionHasErrors(['new_break_in.0']);

    }

    /** @test */
    public function 管理者の勤怠詳細画面からの修正で備考欄が未入力の場合のエラーメッセージが表示される()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['admin_status' => 1]);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin)->post('/admin/attendance/' . $attendanceRecord->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '17:30',
            'comment' => '',
        ]);

        $response->assertSessionHasErrors(['comment']);

    }

    /** @test */
    public function 管理者ユーザーが全ユーザーの「氏名」「メールアドレス」を確認できる()
    {
        $gUser = User::factory()->count(3)->create();
        $admin = User::factory()->create(['admin_status' => 1]);
        $tmpUsers = User::all();

        $response = $this->actingAs($admin)->get('/admin/staff/list');

        $response->assertViewIs('admin.staff-list');
        $response->assertStatus(200);
        $response->assertViewHas('users', function ($users) use ($tmpUsers) {
            $cnt = 0;
            foreach ($users as $index => $user) {
                if ($user->name == $tmpUsers[$index]->name && $user->email == $tmpUsers[$index]->email) {
                    $cnt++;
                }
            }

            return $cnt == 4;
        });
    }

    /** @test */
    public function 管理者はユーザーの勤怠情報が正しく表示できる()
    {
        $users = User::factory()->count(3)->create();
        $admin = User::factory()->create();
        $now = CarbonImmutable::now();
        $firstOfThisMonth = $now->firstOfMonth();
        $cnt = 0;

        for ($day = $firstOfThisMonth; $day->lte($now); $day = $day->addDay()) {
            $attendanceRecord = AttendanceRecord::factory()->create([
                'user_id' => 1,
                'date' => $day->format('Y-m-d'),
            ]);
            $attendanceRecord->clockRecord()->create([
                'clock_in' => '09:00',
                'clock_out' => '17:00',
            ]);
            $attendanceRecord->breakRecords()->create([
                'break_in' => '12:00',
                'break_out' => '13:00',
            ]);
        }

        $attendanceRecords = AttendanceRecord::getMonthUserData($now, User::findOrFail(1));
        $response = $this->actingAs($admin)->get('/admin/attendance/staff/1');

        $response->assertStatus(200);
        $response->assertViewHas('formattedAttendanceRecords', function ($formattedAttendanceRecords) use ($cnt, $attendanceRecords) {
            foreach ($formattedAttendanceRecords as $key => $val) {
                $tmpDate = dateFormat($attendanceRecords[$key]->date);
                $formatDate = $tmpDate->format('m/d') . '(' . jpWeekday($tmpDate->dayOfWeek) . ')';
                if ($val['date'] == $formatDate && $val['clock_in'] == '09:00'
                        && $val['clock_out'] == '17:00' && $val['total_break_time'] == 60
                        && $val['total_time'] == 420) {
                    $cnt++;
                }
            }

            return $attendanceRecords->count() == $cnt;
        });
    }

    /** @test */
    public function 「前月」を押下した時に表示月の前月の情報が表示される()
    {
        $users = User::factory()->count(3)->create();
        $admin = User::factory()->create();
        $now = CarbonImmutable::now();
        $lastMonth = $now->subMonth();
        $firstOfLastMonth = $lastMonth->firstOfMonth();

        for ($day = $firstOfLastMonth; $day->lte($now); $day = $day->addDay()) {
            $attendanceRecord = AttendanceRecord::factory()->create([
                'user_id' => 1,
                'date' => $day->format('Y-m-d'),
            ]);
            $attendanceRecord->clockRecord()->create([
                'clock_in' => '09:00',
                'clock_out' => '17:00',
            ]);
            $attendanceRecord->breakRecords()->create([
                'break_in' => '12:00',
                'break_out' => '13:00',
            ]);
        }

        $attendanceRecords = AttendanceRecord::getMonthUserData($lastMonth, User::findOrFail(1));
        $response = $this->actingAs($admin)->get('/admin/attendance/staff/1?date=' . $firstOfLastMonth->format('Y-m-d'));

        $response->assertStatus(200);
        $response->assertViewHas('formattedAttendanceRecords', function ($formattedAttendanceRecords) use ($attendanceRecords) {
            return $attendanceRecords->count() == count($formattedAttendanceRecords);
        });
    }

    /** @test */
    public function 「翌月」を押下した時に表示月の前月の情報が表示される()
    {
        $users = User::factory()->count(3)->create();
        $admin = User::factory()->create();
        $now = CarbonImmutable::now();
        $nextMonth = $now->addMonth();
        $firstOfNextMonth = $nextMonth->firstOfMonth();

        for ($day = $firstOfNextMonth; $day->lte($now); $day = $day->addDay()) {
            $attendanceRecord = AttendanceRecord::factory()->create([
                'user_id' => 1,
                'date' => $day->format('Y-m-d'),
            ]);
            $attendanceRecord->clockRecord()->create([
                'clock_in' => '09:00',
                'clock_out' => '17:00',
            ]);
            $attendanceRecord->breakRecords()->create([
                'break_in' => '12:00',
                'break_out' => '13:00',
            ]);
        }

        $attendanceRecords = AttendanceRecord::getMonthUserData($nextMonth, User::findOrFail(1));
        $response = $this->actingAs($admin)->get('/admin/attendance/staff/1?date=' . $firstOfNextMonth->format('Y-m-d'));

        $response->assertStatus(200);
        $response->assertViewHas('formattedAttendanceRecords', function ($formattedAttendanceRecords) use ($attendanceRecords) {
            return $attendanceRecords->count() == count($formattedAttendanceRecords);
        });
    }

    /** @test */
    public function 「詳細」を押下すると、その日の勤怠詳細画面に遷移する()
    {
        $users = User::factory()->create();
        $admin = User::factory()->create();
        $now = CarbonImmutable::now();

        for ($day = $now->firstOfMonth(); $day->lte($now); $day = $day->addDay()) {
            $attendanceRecord = AttendanceRecord::factory()->create([
                'user_id' => 1,
                'date' => $day->format('Y-m-d'),
            ]);
            $attendanceRecord->clockRecord()->create([
                'clock_in' => '09:00',
                'clock_out' => '17:00',
            ]);
            $attendanceRecord->breakRecords()->create([
                'break_in' => '12:00',
                'break_out' => '13:00',
            ]);
        }

        $attendanceRecords = AttendanceRecord::getMonthUserData($now, User::findOrFail(1));
        $response = $this->actingAs($admin)->get('/admin/attendance/staff/1');

        $html = $response->getContent();
        $nextUrl = '/admin/attendance/' . $attendanceRecords->first()->id;

        preg_match('/' . preg_quote($nextUrl, '/') . '/', $html, $url);

        $nextPage = $this->actingAs($admin)->get($url[0]);
        $nextPage->assertSee('勤怠詳細');
        $nextPage->assertSee(dateFormat($attendanceRecords->first()->date)->format('m月d日'));
        $nextPage->assertStatus(200);
    }
}
