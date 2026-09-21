<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class AttendanceRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = CarbonImmutable::now();

        $user1 = User::findOrFail(1);
        $user2 = User::findOrFail(2);

        $startDay1 = $user1->created_at;
        $startDay2 = $user2->created_at;

        // user1の勤怠データ
        for ($day1 = $startDay1; $day1->lte($now); $day1 = $day1->addDay()) {

            if ($day1->dayOfWeek != 0 && $day1->dayOfWeek != 6) {
                $AttendanceRecord1 = AttendanceRecord::factory()->create([
                    'user_id' => $user1->id,
                    'date' => $day1,
                ]);

                $AttendanceRecord1->clockRecord()->create([
                    'clock_in' => '09:00',
                    'clock_out' => '17:30',
                ]);

                $AttendanceRecord1->breakRecords()->create([
                    'break_in' => '10:30',
                    'break_out' => '10:35',
                ]);

                $AttendanceRecord1->breakRecords()->create([
                    'break_in' => '12:00',
                    'break_out' => '12:45',
                ]);

                $AttendanceRecord1->breakRecords()->create([
                    'break_in' => '15:00',
                    'break_out' => '15:10',
                ]);
            }
        }

        // user2の勤怠データ
        for ($day2 = $startDay2; $day2->lte($now); $day2 = $day2->addDay()) {
            if ($day2->dayOfWeek != 0 && $day2->dayOfWeek != 6) {

                $AttendanceRecord2 = AttendanceRecord::factory()->create([
                    'user_id' => $user2->id,
                    'date' => $day2,
                ]);

                $AttendanceRecord2->clockRecord()->create([
                    'clock_in' => '09:00',
                    'clock_out' => '17:30',
                ]);

                $AttendanceRecord2->breakRecords()->create([
                    'break_in' => '10:30',
                    'break_out' => '10:35',
                ]);

                $AttendanceRecord2->breakRecords()->create([
                    'break_in' => '12:00',
                    'break_out' => '12:45',
                ]);

                $AttendanceRecord2->breakRecords()->create([
                    'break_in' => '15:00',
                    'break_out' => '15:10',
                ]);
            }
        }

    }
}
