<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Database\Seeder;

class AttendanceRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $endDay = dateFormat(date('2026-10-17'))->startOfDay();

        $user1 = User::findOrFail(1);
        $user2 = User::findOrFail(2);

        $startDay1 = $user1->created_at->startOfDay();
        $startDay2 = $user2->created_at->startOfDay();

        // user1の勤怠データ
        for ($day1 = $startDay1; $day1->lte($endDay); $day1 = $day1->addDay()) {
            $AttendanceRecord1 = AttendanceRecord::factory()->create([
                'user_id' => $user1->id,
                'date' => $day1,
            ]);

            if ($day1->month == $endDay->month) {
                if ($day1->day < 11) {
                    $AttendanceRecord1->clockRecord()->create([
                        'clock_in' => '09:00',
                        'clock_out' => '18:00',
                    ]);

                    $AttendanceRecord1->breakRecords()->create([
                        'break_in' => '12:00',
                        'break_out' => '13:00',
                    ]);
                } elseif ($day1->day < 14) {
                    $AttendanceRecord1->clockRecord()->create([
                        'clock_in' => '09:00',
                        'clock_out' => '20:00',
                    ]);

                    $AttendanceRecord1->breakRecords()->create([
                        'break_in' => '12:00',
                        'break_out' => '13:00',
                    ]);
                } elseif ($day1->day < 16) {
                    $AttendanceRecord1->clockRecord()->create([
                        'clock_in' => '09:30',
                        'clock_out' => '18:00',
                    ]);

                    $AttendanceRecord1->breakRecords()->create([
                        'break_in' => '12:00',
                        'break_out' => '13:00',
                    ]);
                } elseif ($day1->day < 17) {
                    $AttendanceRecord1->clockRecord()->create([
                        'clock_in' => '09:00',
                        'clock_out' => '17:00',
                    ]);

                    $AttendanceRecord1->breakRecords()->create([
                        'break_in' => '12:00',
                        'break_out' => '13:00',
                    ]);
                } else {
                    $AttendanceRecord1->clockRecord()->create([
                        'clock_in' => '08:00',
                        'clock_out' => '21:00',
                    ]);

                    $AttendanceRecord1->breakRecords()->create([
                        'break_in' => '12:00',
                        'break_out' => '13:00',
                    ]);
                }

            } else {

                if ($day1->day < 16) {
                    $AttendanceRecord1->clockRecord()->create([
                        'clock_in' => '09:00',
                        'clock_out' => '18:00',
                    ]);

                    $AttendanceRecord1->breakRecords()->create([
                        'break_in' => '12:00',
                        'break_out' => '13:00',
                    ]);
                }
            }

        }

        // user2の勤怠データ
        for ($day2 = $startDay2; $day2->lte($endDay); $day2 = $day2->addDay()) {
            $AttendanceRecord2 = AttendanceRecord::factory()->create([
                'user_id' => $user2->id,
                'date' => $day2,
            ]);
            if ($day2->dayOfWeek != 0 && $day2->dayOfWeek != 6) {
                $AttendanceRecord2->clockRecord()->create([
                    'clock_in' => '09:00',
                    'clock_out' => '18:00',
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
