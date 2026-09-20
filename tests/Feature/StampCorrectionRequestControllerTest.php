<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrectRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StampCorrectionRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 「承認待ち」にログインユーザーが行った申請が全て表示されている()
    {
        $userDatas = [];
        $user = User::factory()->create();
        $now = CarbonImmutable::now();

        $firstOfLastMonth = $now->subMonth()->firstOfMonth();
        $endOfLastMonth = $now->subMonth()->endOfMonth();

        for ($day = $firstOfLastMonth; $day->lte($endOfLastMonth); $day = $day->addDay()) {
            $attendanceRecord = null;

            $attendanceRecord = AttendanceRecord::factory()->create([
                'user_id' => $user->id,
                'date' => $day->format('Y-m-d'),
            ]);

            $attendanceCorrectRequest = AttendanceCorrectRequest::create([
                'attendance_record_id' => $attendanceRecord->id,
                'comment' => 'test' . $day,
            ]);

            $attendanceCorrectRequest->clockCorrectRequest()->create([
                'new_clock_in' => '09:00',
                'new_clock_out' => '17:30',
            ]);

            $attendanceCorrectRequest->breakCorrectRequests()->create([
                'new_break_in' => '12:00',
                'new_break_out' => '12:45',
            ]);
        }

        $otherUser = User::factory()->create();
        $attendanceRecord1 = AttendanceRecord::factory()->create([
            'user_id' => $otherUser->id,
            'date' => $day->format('Y-m-d'),
        ]);

        $attendanceCorrectRequest1 = AttendanceCorrectRequest::create([
            'attendance_record_id' => $attendanceRecord1->id,
            'comment' => 'test' . $day,
        ]);

        $attendanceCorrectRequest1->clockCorrectRequest()->create([
            'new_clock_in' => '09:00',
            'new_clock_out' => '17:30',
        ]);

        $attendanceCorrectRequest1->breakCorrectRequests()->create([
            'new_break_in' => '12:00',
            'new_break_out' => '12:45',
        ]);

        $applications = AttendanceCorrectRequest::with('attendanceRecord')->get();

        foreach ($applications as $application) {
            if ($application->user->id === $user->id) {
                $userDatas[] = $application;
            }
        }

        $response = $this->actingAs($user)->get('/stamp_correction_request/list');

        $response->assertViewHas('formattedApplications',
            fn ($formattedApplications) => count($formattedApplications) === count($userDatas));
        $response->assertStatus(200);
    }

    /** @test */
    public function 「承認済み」に管理者が承認した修正申請が全て表示されている()
    {
        $userDatas = [];
        $tmpApproved = [];
        $user = User::factory()->create();
        $now = CarbonImmutable::now();

        $firstOfLastMonth = $now->subMonth()->firstOfMonth();
        $endOfLastMonth = $now->subMonth()->endOfMonth()->startOfDay();

        for ($day = $firstOfLastMonth; $day->lte($endOfLastMonth); $day = $day->addDay()) {
            $attendanceRecord = null;
            $status = 1;

            if ($day == $firstOfLastMonth || $day == $endOfLastMonth) {
                $status = 2;
            }

            $attendanceRecord = AttendanceRecord::factory()->create([
                'user_id' => $user->id,
                'date' => $day->format('Y-m-d'),
            ]);

            $attendanceCorrectRequest = AttendanceCorrectRequest::create([
                'attendance_record_id' => $attendanceRecord->id,
                'status' => $status,
                'comment' => 'test' . $day,
            ]);

            $attendanceCorrectRequest->clockCorrectRequest()->create([
                'new_clock_in' => '09:00',
                'new_clock_out' => '17:30',
            ]);

            $attendanceCorrectRequest->breakCorrectRequests()->create([
                'new_break_in' => '12:00',
                'new_break_out' => '12:45',
            ]);
        }

        $response = $this->actingAs($user)->get('/stamp_correction_request/list');

        $approved = AttendanceCorrectRequest::where('status', 2)->get();

        $response->assertViewHas('formattedApplications', function ($formattedApplications) use ($approved) {
            foreach ($formattedApplications as $app) {
                if ($app['approval_status'] == '承認済み') {
                    $tmpApproved[] = $app['id'];
                }
            }

            return count($tmpApproved) == $approved->count();
        });
        $response->assertStatus(200);
    }

    /** @test */
    public function 各申請の「詳細」を押下すると勤怠詳細画面に遷移する()
    {
        $userDatas = [];
        $tmpApproved = [];
        $user = User::factory()->create();
        $now = CarbonImmutable::now();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $now->format('Y-m-d'),
        ]);

        $attendanceCorrectRequest = AttendanceCorrectRequest::create([
            'attendance_record_id' => $attendanceRecord->id,
            'comment' => 'test',
        ]);

        $attendanceCorrectRequest->clockCorrectRequest()->create([
            'new_clock_in' => '09:00',
            'new_clock_out' => '17:30',
        ]);

        $attendanceCorrectRequest->breakCorrectRequests()->create([
            'new_break_in' => '12:00',
            'new_break_out' => '12:45',
        ]);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list');
        $html = $response->getContent();
        $nextUrl = '/attendance/detail/' . $attendanceRecord->id;

        preg_match('/' . preg_quote($nextUrl, '/') . '/', $html, $url);

        $nextPage = $this->actingAs($user)->get($url[0]);
        $nextPage->assertSee('勤怠詳細');
        $nextPage->assertSee($now->format('m月d日'));
        $nextPage->assertStatus(200);
    }

    /** @test */
    public function 管理者の承認待ちの画面に修正申請が全て表示されている()
    {
        $cnt = 0;
        $users = User::factory()->create();
        $admin = User::factory()->create();
        $now = CarbonImmutable::now();

        for ($day = $now->firstOfMonth(); $day->lte($now); $day = $day->addDay()) {
            $attendanceRecord = AttendanceRecord::factory()->create([
                'user_id' => 1,
                'date' => $day->format('Y-m-d'),
            ]);
            if ($day == $now->firstOfMonth()) {
                $attendanceRecord->attendanceCorrectRequests()->create([
                    'comment' => 'test',
                    'status' => 2,
                ]);
            } else {
                $attendanceRecord->attendanceCorrectRequests()->create([
                    'comment' => 'test',
                    'status' => 1,
                ]);
            }
        }

        $pendingData = AttendanceCorrectRequest::where('status', 1)->get();

        $response = $this->actingAs($admin)->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertViewHas('applications', function ($applications) use ($pendingData, $cnt) {
            foreach ($applications as $application) {
                if ($application->approval_status == '承認待ち') {
                    $cnt++;
                }
            }

            return $pendingData->count() == $cnt;
        });
    }

    /** @test */
    public function 管理者の承認済みの画面に修正申請が全て表示されている()
    {
        $cnt = 0;
        $users = User::factory()->create();
        $admin = User::factory()->create();
        $now = CarbonImmutable::now();

        for ($day = $now->firstOfMonth(); $day->lte($now); $day = $day->addDay()) {
            $attendanceRecord = AttendanceRecord::factory()->create([
                'user_id' => 1,
                'date' => $day->format('Y-m-d'),
            ]);
            if ($day == $now->firstOfMonth()) {
                $attendanceRecord->attendanceCorrectRequests()->create([
                    'comment' => 'test',
                    'status' => 1,
                ]);
            } else {
                $attendanceRecord->attendanceCorrectRequests()->create([
                    'comment' => 'test',
                    'status' => 2,
                ]);
            }
        }

        $approvedData = AttendanceCorrectRequest::where('status', '<>', 1)->get();

        $response = $this->actingAs($admin)->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertViewHas('applications', function ($applications) use ($approvedData, $cnt) {
            foreach ($applications as $application) {
                if ($application->approval_status == '承認済み') {
                    $cnt++;
                }
            }

            return $approvedData->count() == $cnt;
        });
    }

    /** @test */
    public function 管理者の修正申請の画面に修正申請の詳細内容が正しく表示されている()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => date('Y-m-d'),
        ]);
        $attendanceCorrectRequest = $attendanceRecord->attendanceCorrectRequests()->create([
            'comment' => 'test',
        ]);
        $attendanceCorrectRequest->clockCorrectRequest()->create([
            'new_clock_in' => '09:00',
            'new_clock_out' => '17:00',
        ]);
        $attendanceCorrectRequest->breakCorrectRequests()->create([
            'new_break_in' => '12:00',
            'new_break_out' => '13:00',
        ]);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/approve/' . $attendanceCorrectRequest->id);

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('17:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('test');
        $response->assertSee($user->name);
    }

    /** @test */
    public function 管理者が行った修正申請の承認処理が正しく行われる()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => date('Y-m-d'),
        ]);
        $attendanceCorrectRequest = $attendanceRecord->attendanceCorrectRequests()->create([
            'comment' => 'test',
        ]);
        $attendanceCorrectRequest->clockCorrectRequest()->create([
            'new_clock_in' => '09:00',
            'new_clock_out' => '17:00',
        ]);
        $attendanceCorrectRequest->breakCorrectRequests()->create([
            'new_break_in' => '12:00',
            'new_break_out' => '13:00',
        ]);

        $response = $this->actingAs($admin)->post('/stamp_correction_request/approve/' . $attendanceCorrectRequest->id);

        $response->assertRedirect('/stamp_correction_request/approve/' . $attendanceCorrectRequest->id);
        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'comment' => 'test',
        ]);
        $this->assertDatabaseHas('clock_records', [
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:00',
            'clock_out' => '17:00',
        ]);
        $this->assertDatabaseHas('break_records', [
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);
    }
}
