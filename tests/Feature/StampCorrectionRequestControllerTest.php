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
}
